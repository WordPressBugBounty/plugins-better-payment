<?php
/**
 * The settings screen's MCP management routes.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Admin;

use Better_Payment\Lite\MCP\OAuth\Store;
use Better_Payment\Lite\MCP\Support\ActivityLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists OAuth connections, revokes one, and runs the connection test.
 *
 * These sit in the plugin's ordinary admin namespace, not on the MCP endpoint: no tool
 * reaches them and an OAuth token is not a credential here, so a connected application
 * cannot list or revoke another one. They take what any admin route takes, a cookie with
 * a REST nonce, or the administrator's own Application Password.
 *
 * @since 2.5.0
 */
class ConnectionsController {

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'better-payment/v1';

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/mcp/connections',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'index' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'revoke_all' ),
					'permission_callback' => array( __CLASS__, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/mcp/connections/(?P<family>[a-f0-9]{64})',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'revoke' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/mcp/self-test',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'self_test' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
			)
		);
	}

	/**
	 * Whether the current user may manage MCP.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Every live connection.
	 *
	 * @return \WP_REST_Response
	 */
	public static function index() {
		return new \WP_REST_Response( self::listing() );
	}

	/**
	 * Revoke every connection — the settings screen's "Disconnect all".
	 *
	 * @return \WP_REST_Response
	 */
	public static function revoke_all() {
		$revoked = Store::revoke_all();

		return new \WP_REST_Response( array_merge( self::listing(), array( 'revoked' => $revoked ) ) );
	}

	/**
	 * What every route here answers with: the live connections, and when a tool
	 * was last called (a time only — the log's tool names stay in the log).
	 *
	 * @return array<string, mixed>
	 */
	private static function listing() {
		return array(
			'connections'   => Store::connections(),
			'last_activity' => ActivityLog::last_time(),
		);
	}

	/**
	 * Revoke one connection. Takes effect on that client's next request.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function revoke( $request ) {
		if ( ! Store::revoke_family( (string) $request->get_param( 'family' ) ) ) {
			return new \WP_Error( 'not_found', __( 'That connection no longer exists.', 'better-payment' ), array( 'status' => 404 ) );
		}

		return new \WP_REST_Response( self::listing() );
	}

	/**
	 * Run the connection test.
	 *
	 * @return \WP_REST_Response
	 */
	public static function self_test() {
		return new \WP_REST_Response( SelfTest::run() );
	}
}
