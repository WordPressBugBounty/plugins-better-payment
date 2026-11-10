<?php
/**
 * The short URL the MCP endpoint is advertised at.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves `/better-payment/mcp` by forwarding it to the REST route behind it.
 *
 * The address is the one part of this module a site owner handles directly: they
 * copy it out of the settings screen and paste it into Claude, ChatGPT or an IDE
 * config, sometimes by hand. `/wp-json/better-payment/mcp` carries a path segment
 * that means nothing to them and everything to WordPress, so the short form is what
 * gets advertised; the REST route stays registered and keeps working, because
 * anything already connected to the long URL must not break.
 *
 * It is a forward, not a second implementation. The request is rebuilt as a
 * `WP_REST_Request` and handed to the REST server, so routing, `rest_pre_dispatch`
 * and `Server::handle()` behave identically on both addresses — there is no second
 * code path to keep in step, and no second place for an auth check to go missing.
 *
 * Hooked at a negative `parse_request` priority, like `OAuth\Discovery`, so it runs
 * before WordPress resolves the path as a page and 404s it.
 *
 * @since 2.5.0
 */
class PrettyEndpoint {

	/**
	 * Hook the handler.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ), -100 );
	}

	/**
	 * Whether the short URL can work on this site.
	 *
	 * It needs the same front-controller rewrite every pretty permalink needs, so a
	 * site on plain permalinks keeps `rest_url()` as its advertised address rather
	 * than being handed one that 404s at the web server before WordPress ever runs.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return '' !== (string) get_option( 'permalink_structure' );
	}

	/**
	 * Path this handler answers on, without a trailing slash.
	 *
	 * Derived from `home_url()` so a subdirectory install works unchanged.
	 *
	 * @return string
	 */
	public static function base_path() {
		$path = wp_parse_url( home_url( MCPManager::PRETTY_PATH ), PHP_URL_PATH );

		return is_string( $path ) ? untrailingslashit( $path ) : '';
	}

	/**
	 * Forward the request when it is for this endpoint.
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( ! MCPManager::is_enabled() || ! self::is_available() ) {
			return;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';
		$base = self::base_path();

		if ( '' === $path || '' === $base ) {
			return;
		}

		$route = self::route_for( $path, $base );

		if ( null === $route ) {
			return;
		}

		self::forward( $route );
	}

	/**
	 * The REST route one incoming path maps to, or null when it is not ours.
	 *
	 * Exact matches only. A prefix match would claim every URL under
	 * `/better-payment/`, including pages a site may legitimately have there.
	 *
	 * @param string $path Requested path, without a trailing slash.
	 * @param string $base This endpoint's base path.
	 * @return string|null
	 */
	public static function route_for( $path, $base ) {
		$rest_base = '/' . MCPManager::REST_NAMESPACE . MCPManager::REST_ROUTE;

		if ( $path === $base ) {
			return $rest_base;
		}

		// The OAuth endpoints travel with the short URL, so the discovery document
		// can describe one address family rather than two.
		foreach ( array( 'authorize', 'token', 'register', 'revoke', 'protected-resource', 'authorization-server' ) as $action ) {
			if ( $path === $base . '/oauth/' . $action ) {
				return $rest_base . '/oauth/' . $action;
			}
		}

		return null;
	}

	/**
	 * Rebuild the request as a REST request, dispatch it, and emit the response.
	 *
	 * @param string $route REST route to dispatch.
	 * @return void Always exits.
	 */
	private static function forward( $route ) {
		// Declared before the server is built: `wp_authenticate_application_password()`
		// returns early unless the request is an API request, so without this an
		// Application Password would authenticate on the long URL and fail on the
		// short one.
		if ( ! defined( 'REST_REQUEST' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Core's own constant.
			define( 'REST_REQUEST', true );
		}

		$server = rest_get_server();
		$method = isset( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			: 'GET';

		$request = new \WP_REST_Request( $method, $route );

		$request->set_headers( $server->get_headers( wp_unslash( $_SERVER ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this endpoint authenticates the request's own credential; see Auth\Authenticator.
		$request->set_query_params( wp_unslash( $_GET ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above. The OAuth token endpoint is form-encoded and PKCE-authenticated.
		$request->set_body_params( wp_unslash( $_POST ) );
		$request->set_body( self::raw_body() );

		$response = rest_ensure_response( $server->dispatch( $request ) );

		self::emit( $response );

		exit;
	}

	/**
	 * The raw request body.
	 *
	 * @return string
	 */
	private static function raw_body() {
		$body = file_get_contents( 'php://input' );

		return is_string( $body ) ? $body : '';
	}

	/**
	 * Send a REST response as an ordinary HTTP response.
	 *
	 * @param \WP_REST_Response $response Response to send.
	 * @return void
	 */
	private static function emit( $response ) {
		$status = (int) $response->get_status();
		$data   = $response->get_data();

		if ( ! headers_sent() ) {
			status_header( $status );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

			foreach ( $response->get_headers() as $name => $value ) {
				header( $name . ': ' . $value );
			}
		}

		// A 202 acknowledging a JSON-RPC notification carries no body, and "null" is
		// not the same thing as nothing.
		if ( null === $data ) {
			return;
		}

		echo wp_json_encode( $data );
	}
}
