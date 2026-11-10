<?php
/**
 * The OAuth 2.1 endpoints backing the MCP server.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\OAuth;

use Better_Payment\Lite\MCP\Auth\Context;
use Better_Payment\Lite\MCP\MCPManager;
use Better_Payment\Lite\MCP\Support\Limits;
use Better_Payment\Lite\MCP\Support\RateLimiter;
use Better_Payment\Lite\MCP\Tools\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registration, authorization, token and revocation.
 *
 * Only what OAuth 2.1 leaves after the removals: authorization code with mandatory
 * PKCE S256, refresh with rotation, public clients, exact redirect matching. No
 * implicit grant, no password grant, no `plain` challenge — each of those is a
 * documented way to lose an access token, and an MCP client needs none of them.
 *
 * The authorization endpoint is the one place in the module that uses the browser's
 * WordPress session, because that is exactly what it is for: a human, signed in to
 * wp-admin, deciding what a client may do. Every other surface refuses cookies.
 *
 * @since 2.5.0
 */
class Endpoints {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$namespace = MCPManager::REST_NAMESPACE;
		$base      = MCPManager::REST_ROUTE . '/oauth';

		register_rest_route(
			$namespace,
			$base . '/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'register_client' ),
				// Dynamic Client Registration is unauthenticated by design: a client
				// must be able to introduce itself before any credential exists. It
				// creates a client id and nothing else — no access, no grant — and
				// the registry is capped so it cannot be used to fill the database.
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			$base . '/authorize',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( __CLASS__, 'authorize' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			$base . '/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			$base . '/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'revoke' ),
				'permission_callback' => '__return_true',
			)
		);

		// The discovery documents, at addresses only this plugin answers. See
		// Discovery::protected_resource_url().
		foreach ( array( 'protected-resource', 'authorization-server' ) as $document ) {
			register_rest_route(
				$namespace,
				$base . '/' . $document,
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'discovery_document' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * A discovery document.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function discovery_document( $request ) {
		$is_resource = false !== strpos( (string) $request->get_route(), 'protected-resource' );

		$response = new \WP_REST_Response(
			$is_resource ? Discovery::protected_resource_metadata() : Discovery::authorization_server_metadata()
		);

		// Read cross-origin by browser-based clients before any token exists.
		$response->header( 'Access-Control-Allow-Origin', '*' );

		return $response;
	}

	/* ---------------------------------------------------------------------
	 * Registration
	 * ------------------------------------------------------------------ */

	/**
	 * RFC 7591 Dynamic Client Registration.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function register_client( $request ) {
		if ( ! RateLimiter::allow_registration() ) {
			return self::too_many_requests();
		}

		// The same body cap as the MCP endpoint: this route is unauthenticated, and its
		// fields are capped again one by one in Store::register_client().
		if ( strlen( (string) $request->get_body() ) > Limits::max_body_bytes() ) {
			return self::json_error( 'invalid_client_metadata', __( 'The registration request is too large.', 'better-payment' ), 413 );
		}

		// Cast rather than test: get_json_params() is null for an empty or
		// unparseable body, and an empty metadata array is refused below anyway.
		$result = Store::register_client( (array) $request->get_json_params() );

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			return self::json_error( $result->get_error_code(), $result->get_error_message(), is_array( $data ) && ! empty( $data['status'] ) ? (int) $data['status'] : 400 );
		}

		return self::json( $result, 201 );
	}

	/* ---------------------------------------------------------------------
	 * Authorization
	 * ------------------------------------------------------------------ */

	/**
	 * The authorization endpoint.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return void Always exits: this endpoint answers with HTML or a redirect.
	 */
	public static function authorize( $request ) {
		$params = array_merge(
			(array) $request->get_query_params(),
			(array) $request->get_body_params()
		);

		$client_id = isset( $params['client_id'] ) ? sanitize_text_field( (string) $params['client_id'] ) : '';
		$client    = Store::get_client( $client_id );

		if ( null === $client ) {
			Consent::error(
				__( 'Unknown application', 'better-payment' ),
				__( 'This application is not registered with this site. Ask it to connect again.', 'better-payment' )
			);
		}

		$redirect_uri = isset( $params['redirect_uri'] ) ? Store::clean_redirect_uri( (string) $params['redirect_uri'] ) : '';

		// Exact match against what the client registered. Until this passes there is
		// no address it is safe to send an error to, which is why both failures above
		// render a page instead of redirecting.
		if ( '' === $redirect_uri || ! in_array( $redirect_uri, (array) $client['redirect_uris'], true ) ) {
			Consent::error(
				__( 'Redirect address not recognised', 'better-payment' ),
				__( 'The address this application asked to be returned to is not one it registered.', 'better-payment' )
			);
		}

		$state = isset( $params['state'] ) ? sanitize_text_field( (string) $params['state'] ) : '';

		if ( ! isset( $params['response_type'] ) || 'code' !== $params['response_type'] ) {
			self::redirect_error( $redirect_uri, 'unsupported_response_type', $state );
		}

		$challenge = isset( $params['code_challenge'] ) ? sanitize_text_field( (string) $params['code_challenge'] ) : '';
		$method    = isset( $params['code_challenge_method'] ) ? strtoupper( sanitize_text_field( (string) $params['code_challenge_method'] ) ) : '';

		// PKCE is not optional and `plain` is not accepted: without S256 the
		// challenge is a value an attacker who intercepted the redirect already has.
		if ( '' === $challenge || 'S256' !== $method ) {
			self::redirect_error( $redirect_uri, 'invalid_request', $state );
		}

		if ( ! is_user_logged_in() ) {
			// The browser session is the point here. Send them to log in and come
			// straight back to the same authorization request.
			wp_safe_redirect( wp_login_url( self::current_url() ) );
			exit;
		}

		if ( ! self::user_can_use_any_tool() ) {
			Consent::error(
				__( 'This account cannot use Better Payment tools', 'better-payment' ),
				__( 'Sign in as a user who can manage Better Payment, then connect again.', 'better-payment' )
			);
		}

		$requested = Context::sanitize( isset( $params['scope'] ) ? $params['scope'] : '' );

		// A submitted form, or the first look at the screen.
		if ( 'POST' === $request->get_method() ) {
			self::handle_consent( $params, $client_id, $redirect_uri, $challenge, $state, $requested );
		}

		Consent::render(
			$client,
			array(
				'self_url' => self::current_url(),
				'hidden'   => array(
					'client_id'             => $client_id,
					'redirect_uri'          => $redirect_uri,
					'response_type'         => 'code',
					'code_challenge'        => $challenge,
					'code_challenge_method' => 'S256',
					'state'                 => $state,
					'scope'                 => implode( ' ', $requested ),
				),
			)
		);
	}

	/**
	 * Act on a submitted consent form.
	 *
	 * @param array  $params       Request parameters.
	 * @param string $client_id    Client id.
	 * @param string $redirect_uri Validated redirect.
	 * @param string $challenge    PKCE challenge.
	 * @param string $state        Client state.
	 * @param array  $requested    Scopes the client asked for.
	 * @return void Always exits.
	 */
	private static function handle_consent( array $params, $client_id, $redirect_uri, $challenge, $state, array $requested ) {
		$nonce = isset( $params['_wpnonce'] ) ? sanitize_text_field( (string) $params['_wpnonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, Consent::NONCE_ACTION ) ) {
			Consent::error(
				__( 'This request expired', 'better-payment' ),
				__( 'Start the connection again from the application.', 'better-payment' )
			);
		}

		$decision = isset( $params['consent'] ) ? (string) $params['consent'] : 'deny';

		if ( 'allow' !== $decision ) {
			self::redirect_error( $redirect_uri, 'access_denied', $state );
		}

		$ticked = isset( $params['granted_scope'] ) ? (array) $params['granted_scope'] : array();
		$ticked = Context::sanitize( $ticked );

		// The owner can narrow what the client asked for, never widen it: a client
		// that requested read-only must not come away with write because a box was
		// ticked.
		$granted = array_values( array_intersect( $ticked, $requested ) );

		if ( empty( $granted ) ) {
			self::redirect_error( $redirect_uri, 'access_denied', $state );
		}

		$code = Store::issue_code( $client_id, get_current_user_id(), $redirect_uri, $challenge, $granted );

		$location = add_query_arg(
			array_filter(
				array(
					'code'  => rawurlencode( $code ),
					'state' => '' !== $state ? rawurlencode( $state ) : null,
				)
			),
			$redirect_uri
		);

		// wp_redirect(), not wp_safe_redirect(): the destination is a registered
		// client's own address and is deliberately off-site. It was matched exactly
		// against the client's registration above, which is the check that makes
		// this safe.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The client's registered address, matched exactly, is off-site by design.
		wp_redirect( $location );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Token
	 * ------------------------------------------------------------------ */

	/**
	 * The token endpoint.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function token( $request ) {
		if ( RateLimiter::is_locked() ) {
			return self::too_many_requests();
		}

		$params = (array) $request->get_body_params();

		if ( empty( $params ) ) {
			// Some clients post JSON even though RFC 6749 says form encoding.
			$params = (array) $request->get_json_params();
		}

		$grant_type = isset( $params['grant_type'] ) ? sanitize_text_field( (string) $params['grant_type'] ) : '';
		$client_id  = isset( $params['client_id'] ) ? sanitize_text_field( (string) $params['client_id'] ) : '';

		if ( null === Store::get_client( $client_id ) ) {
			RateLimiter::record_failure();

			return self::json_error( 'invalid_client', __( 'Unknown client.', 'better-payment' ), 401 );
		}

		if ( 'authorization_code' === $grant_type ) {
			$grant = Store::consume_code(
				isset( $params['code'] ) ? (string) $params['code'] : '',
				$client_id,
				isset( $params['redirect_uri'] ) ? Store::clean_redirect_uri( (string) $params['redirect_uri'] ) : '',
				isset( $params['code_verifier'] ) ? (string) $params['code_verifier'] : ''
			);

			if ( is_wp_error( $grant ) ) {
				return self::grant_failure( $grant );
			}

			return self::json(
				Store::issue_tokens( $client_id, (int) $grant['user_id'], Context::sanitize( $grant['scope'] ) )
			);
		}

		if ( 'refresh_token' === $grant_type ) {
			$tokens = Store::rotate_refresh_token(
				isset( $params['refresh_token'] ) ? (string) $params['refresh_token'] : '',
				$client_id
			);

			if ( is_wp_error( $tokens ) ) {
				return self::grant_failure( $tokens );
			}

			return self::json( $tokens );
		}

		return self::json_error( 'unsupported_grant_type', __( 'Only authorization_code and refresh_token are supported.', 'better-payment' ), 400 );
	}

	/**
	 * The token endpoint's answer to a refused grant.
	 *
	 * A store too busy to take the request is a 503 the client may retry, and it is
	 * not a failed credential: the code or refresh token was not spent. Anything else
	 * is `invalid_grant` and counts against the caller.
	 *
	 * @param \WP_Error $error The store's refusal.
	 * @return \WP_REST_Response
	 */
	private static function grant_failure( \WP_Error $error ) {
		if ( 'temporarily_unavailable' === $error->get_error_code() ) {
			$response = self::json_error( 'temporarily_unavailable', $error->get_error_message(), 503 );
			$response->header( 'Retry-After', '1' );

			return $response;
		}

		RateLimiter::record_failure();

		return self::json_error( 'invalid_grant', $error->get_error_message(), 400 );
	}

	/**
	 * RFC 7009 revocation.
	 *
	 * Always answers 200, whatever was presented: telling a caller that a token it
	 * handed over was unknown is a probe it does not need answered.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function revoke( $request ) {
		$params = (array) $request->get_body_params();
		$token  = isset( $params['token'] ) ? (string) $params['token'] : '';

		if ( '' !== $token ) {
			Store::revoke_access_token( $token );
		}

		return self::json( array( 'revoked' => true ) );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Whether the signed-in user can use at least one registered tool.
	 *
	 * @return bool
	 */
	private static function user_can_use_any_tool() {
		foreach ( Registry::all() as $tool ) {
			if ( current_user_can( $tool['capability'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The URL this request arrived at, rebuilt for a form action or a login return.
	 *
	 * @return string
	 */
	private static function current_url() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return home_url( $path );
	}

	/**
	 * Report a failure to the client by redirect, as OAuth requires.
	 *
	 * @param string $redirect_uri Registered redirect.
	 * @param string $error        OAuth error code.
	 * @param string $state        Client state.
	 * @return void Always exits.
	 */
	private static function redirect_error( $redirect_uri, $error, $state ) {
		$location = add_query_arg(
			array_filter(
				array(
					'error' => rawurlencode( $error ),
					'state' => '' !== $state ? rawurlencode( $state ) : null,
				)
			),
			$redirect_uri
		);

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The client's registered address, matched exactly, is off-site by design.
		wp_redirect( $location );
		exit;
	}

	/**
	 * A JSON response with the no-store headers the token endpoint requires.
	 *
	 * @param array $body   Response body.
	 * @param int   $status HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function json( array $body, $status = 200 ) {
		$response = new \WP_REST_Response( $body, $status );

		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Access-Control-Allow-Origin', '*' );

		return $response;
	}

	/**
	 * The 429 a limited caller gets.
	 *
	 * @return \WP_REST_Response
	 */
	private static function too_many_requests() {
		$response = self::json_error( 'too_many_requests', __( 'Too many attempts. Try again later.', 'better-payment' ), 429 );

		$response->header( 'Retry-After', (string) RateLimiter::retry_after() );

		return $response;
	}

	/**
	 * An OAuth error response.
	 *
	 * @param string $code    OAuth error code.
	 * @param string $message Human description.
	 * @param int    $status  HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function json_error( $code, $message, $status ) {
		return self::json(
			array(
				'error'             => (string) $code,
				'error_description' => (string) $message,
			),
			$status
		);
	}
}
