<?php
/**
 * The settings screen's "Test connection" check.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Admin;

use Better_Payment\Lite\MCP\MCPManager;
use Better_Payment\Lite\MCP\OAuth\Discovery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calls this site's own endpoint the way a client would, over real HTTP.
 *
 * The failures it finds cannot be seen from inside PHP: a host that strips the
 * `Authorization` header, a firewall that blocks the path, or a web server that answers
 * `/.well-known/` itself. They otherwise reach the owner as a client reporting "401".
 *
 * It uses no credential of the owner's. A made-up bearer token is enough, because the
 * 401 says whether it arrived: `invalid_token` means the server read the header, and
 * `invalid_request` means the header never reached PHP.
 *
 * @since 2.5.0
 */
class SelfTest {

	/**
	 * Header carrying the one-shot marker.
	 */
	const MARKER_HEADER = 'x-bp-mcp-self-test';

	/**
	 * Transient holding the marker.
	 */
	const MARKER_TRANSIENT = 'bp_mcp_self_test';

	/**
	 * Run every check that applies to this site.
	 *
	 * @return array{ok: bool, checks: array<int, array<string, mixed>>}
	 */
	public static function run() {
		$checks = array( self::check_endpoint() );

		if ( MCPManager::oauth_enabled() ) {
			$checks[] = self::check_document(
				'discovery',
				Discovery::protected_resource_url(),
				__( 'OAuth discovery document', 'better-payment' ),
				'resource'
			);

			if ( Discovery::is_available() ) {
				$checks[] = self::check_document(
					'well_known',
					home_url( '/.well-known/oauth-authorization-server' . self::relative_endpoint() ),
					__( 'OAuth server address', 'better-payment' ),
					'issuer'
				);
			}
		}

		return array(
			'ok'     => ! in_array( false, wp_list_pluck( $checks, 'ok' ), true ),
			'checks' => $checks,
		);
	}

	/**
	 * Whether a request is this class's own, spending the marker if so.
	 *
	 * The marker keeps a test run out of the failed-credential count. Behind a proxy
	 * `REMOTE_ADDR` is shared, so counting it could lock out real clients.
	 *
	 * @param string $marker Header value.
	 * @return bool
	 */
	public static function consume_marker( $marker ) {
		$stored = get_transient( self::MARKER_TRANSIENT );

		if ( '' === $marker || ! is_string( $stored ) || ! hash_equals( $stored, $marker ) ) {
			return false;
		}

		delete_transient( self::MARKER_TRANSIENT );

		return true;
	}

	/**
	 * Check the endpoint is reachable and receives the `Authorization` header.
	 *
	 * @return array<string, mixed>
	 */
	private static function check_endpoint() {
		$label  = __( 'MCP endpoint', 'better-payment' );
		$marker = bin2hex( random_bytes( 16 ) );

		set_transient( self::MARKER_TRANSIENT, $marker, MINUTE_IN_SECONDS );

		$response = wp_remote_post(
			MCPManager::endpoint_url(),
			self::request_args(
				array(
					'headers' => array(
						'Authorization'     => 'Bearer self-test',
						'Content-Type'      => 'application/json',
						self::MARKER_HEADER => $marker,
					),
					'body'    => '{"jsonrpc":"2.0","id":1,"method":"ping"}',
				)
			)
		);

		delete_transient( self::MARKER_TRANSIENT );

		if ( is_wp_error( $response ) ) {
			return self::result( 'endpoint', $label, false, $response->get_error_message() );
		}

		$status    = (int) wp_remote_retrieve_response_code( $response );
		$challenge = (string) wp_remote_retrieve_header( $response, 'www-authenticate' );

		if ( 401 !== $status ) {
			/* translators: %d: HTTP status code. */
			return self::result( 'endpoint', $label, false, sprintf( __( 'The endpoint answered HTTP %d, not the 401 it gives an unknown token. A firewall or cache may be in the way.', 'better-payment' ), $status ) );
		}

		if ( false === strpos( $challenge, 'error="invalid_token"' ) ) {
			return self::result( 'endpoint', $label, false, __( 'The Authorization header did not reach WordPress, so no client can sign in. Ask your host to pass it through to PHP.', 'better-payment' ) );
		}

		return self::result( 'endpoint', $label, true, __( 'Reachable, and the Authorization header arrives.', 'better-payment' ) );
	}

	/**
	 * Check one discovery document is served and is this plugin's own.
	 *
	 * @param string $id    Check id.
	 * @param string $url   Document URL.
	 * @param string $label Human label.
	 * @param string $key   Key that must hold this server's issuer.
	 * @return array<string, mixed>
	 */
	private static function check_document( $id, $url, $label, $key ) {
		$response = wp_remote_get( $url, self::request_args() );

		if ( is_wp_error( $response ) ) {
			return self::result( $id, $label, false, $response->get_error_message() );
		}

		$document = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $document ) || ! isset( $document[ $key ] ) || Discovery::issuer() !== $document[ $key ] ) {
			/* translators: %s: URL. */
			return self::result( $id, $label, false, sprintf( __( '%s did not return this plugin\'s document. The web server or another plugin may be answering that address.', 'better-payment' ), $url ) );
		}

		return self::result( $id, $label, true, __( 'Served correctly.', 'better-payment' ) );
	}

	/**
	 * The endpoint's path below the home URL.
	 *
	 * @return string
	 */
	private static function relative_endpoint() {
		return substr( Discovery::issuer(), strlen( untrailingslashit( home_url() ) ) );
	}

	/**
	 * Arguments for a request to this site.
	 *
	 * @param array $args Request-specific arguments.
	 * @return array
	 */
	private static function request_args( array $args = array() ) {
		return array_merge(
			array(
				'timeout'   => 10,
				// Core's own filter for a site calling itself, which is how its Site
				// Health loopback test copes with a self-signed certificate.
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core's hook.
			),
			$args
		);
	}

	/**
	 * One check's outcome.
	 *
	 * @param string $id      Check id.
	 * @param string $label   Human label.
	 * @param bool   $ok      Whether it passed.
	 * @param string $message What to tell the owner.
	 * @return array<string, mixed>
	 */
	private static function result( $id, $label, $ok, $message ) {
		return array(
			'id'      => $id,
			'label'   => $label,
			'ok'      => $ok,
			'message' => $message,
		);
	}
}
