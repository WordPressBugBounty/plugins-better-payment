<?php
/**
 * MCP module bootstrap.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP;

use Better_Payment\Lite\MCP\Admin\ConnectionsController;
use Better_Payment\Lite\MCP\OAuth\Discovery;
use Better_Payment\Lite\MCP\OAuth\Endpoints;
use Better_Payment\Lite\MCP\OAuth\Store as OAuthStore;
use Better_Payment\Lite\MCP\Support\ActivityLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires Better Payment's MCP server.
 *
 * Better Payment is an MCP server in the ordinary sense: one URL, the Streamable HTTP
 * transport, OAuth 2.1 for hosted clients and Application Passwords for local ones.
 * Claude, ChatGPT, Cursor and every other MCP client connect to it the same way they
 * connect to any other server, with no adapter plugin, no bridge process and nothing
 * written specially for one vendor.
 *
 * Gates, in order, so the module is inert unless it is both possible and wanted:
 *
 * 1. **The site opted in.** `better_payment_settings_general_general_mcp` defaults to
 *    'no', so a plugin update changes nothing anywhere until an administrator turns
 *    it on. While it is off the routes are not registered at all — not registered and
 *    answering 403, which would still be an endpoint to probe.
 * 2. **The credential in the request**, resolved by `Auth\Authenticator`. Never the
 *    browser session.
 * 3. **The grant's scope**, for an OAuth token, and then
 * 4. **the WordPress capability** each tool requires.
 *
 * @since 2.5.0
 */
class MCPManager {

	/**
	 * Settings key holding the module's enable flag.
	 */
	const ENABLED_KEY = 'better_payment_settings_general_general_mcp';

	/**
	 * Settings key holding the optional OAuth server's flag.
	 */
	const OAUTH_KEY = 'better_payment_settings_general_general_mcp_oauth';

	/**
	 * REST namespace the endpoint lives in.
	 *
	 * Its own namespace rather than the plugin's `better-payment/v1`: this route
	 * speaks JSON-RPC, authenticates itself, and shares no conventions with the
	 * controllers beside it. The short URL is also the one a site owner pastes into
	 * a client, so it is worth keeping readable.
	 */
	const REST_NAMESPACE = 'better-payment';

	/**
	 * Route within the namespace.
	 */
	const REST_ROUTE = '/mcp';

	/**
	 * The short, advertised path — what a site owner pastes into a client.
	 *
	 * `/wp-json/` carries a segment that means everything to WordPress and nothing
	 * to the person copying the address by hand. `PrettyEndpoint` forwards this path
	 * to the REST route, which stays registered: anything already connected to the
	 * long URL keeps working.
	 */
	const PRETTY_PATH = '/better-payment/mcp';

	/**
	 * Wire the module.
	 *
	 * @return void
	 */
	public function init() {
		// The listeners attach unconditionally and each one re-reads the flag when
		// it fires. Reading the option at plugin-load time instead would bake in
		// whatever the setting was before `plugins_loaded`, which is earlier than
		// the settings screen's own save — and it is what the route registration
		// below depends on being current.
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );

		PrettyEndpoint::init();
		Discovery::init();
	}

	/**
	 * Register the endpoint, and the OAuth routes when that half is switched on.
	 *
	 * @return void
	 */
	public static function register_routes() {
		if ( ! self::is_enabled() ) {
			// Not registered, rather than registered and answering 403: an endpoint
			// that answers at all is an endpoint worth probing.
			return;
		}

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => array( 'GET', 'POST', 'DELETE' ),
				'callback'            => array( Server::class, 'handle' ),
				// The endpoint authenticates itself. A permission callback here
				// would run WordPress's own cookie/nonce logic first, and a cookie
				// is exactly what must not be accepted — see Auth\Authenticator.
				'permission_callback' => '__return_true',
			)
		);

		ConnectionsController::register_routes();

		if ( self::oauth_enabled() ) {
			Endpoints::register_routes();
		}
	}

	/**
	 * Whether the module is switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = 'yes' === (string) \Better_Payment\Lite\Admin\DB::get_settings( self::ENABLED_KEY );

		/**
		 * Filters whether Better Payment's MCP server runs.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $enabled Whether the module is enabled.
		 */
		return (bool) apply_filters( 'better_payment/mcp/enabled', $enabled );
	}

	/**
	 * Whether this site is also acting as an OAuth authorization server.
	 *
	 * Separate from the module flag on purpose. A site that only ever connects a
	 * command-line client needs Application Passwords and nothing else; OAuth exists
	 * for hosted clients that will not hold a static credential, and a site that has
	 * none should not be running an authorization server.
	 *
	 * @return bool
	 */
	public static function oauth_enabled() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$enabled = 'yes' === (string) \Better_Payment\Lite\Admin\DB::get_settings( self::OAUTH_KEY );

		/**
		 * Filters whether the MCP OAuth authorization server runs.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $enabled Whether OAuth is enabled.
		 */
		return (bool) apply_filters( 'better_payment/mcp/oauth_enabled', $enabled );
	}

	/**
	 * The URL a site owner pastes into an MCP client.
	 *
	 * The short form wherever the site's own rewrites can serve it, the REST URL
	 * otherwise — a site on plain permalinks must not be handed an address that 404s
	 * at the web server before WordPress runs. The REST URL keeps working either
	 * way, so this is which one is *advertised*, never which one is accepted.
	 *
	 * @return string
	 */
	public static function endpoint_url() {
		if ( PrettyEndpoint::is_available() ) {
			return home_url( self::PRETTY_PATH );
		}

		return rest_url( ltrim( self::REST_NAMESPACE . self::REST_ROUTE, '/' ) );
	}

	/**
	 * URL of one OAuth endpoint.
	 *
	 * Follows `endpoint_url()`, so a discovery document describes one address family
	 * rather than mixing the two.
	 *
	 * @param string $action One of `authorize`, `token`, `register`, `revoke`,
	 *                       `protected-resource`, `authorization-server`.
	 * @return string
	 */
	public static function oauth_url( $action ) {
		return untrailingslashit( self::endpoint_url() ) . '/oauth/' . sanitize_key( $action );
	}

	/**
	 * Everything the plugin stores for this module.
	 *
	 * Named in one place so the uninstaller and the tests cannot fall out of step
	 * with each other.
	 *
	 * @return string[]
	 */
	public static function stored_options() {
		return array(
			ActivityLog::OPTION,
			OAuthStore::CLIENTS_OPTION,
			OAuthStore::CODES_OPTION,
			OAuthStore::TOKENS_OPTION,
		);
	}
}
