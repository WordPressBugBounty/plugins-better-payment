<?php
/**
 * OAuth discovery documents for the MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\OAuth;

use Better_Payment\Lite\MCP\Auth\Context;
use Better_Payment\Lite\MCP\MCPManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves the two `.well-known` documents an MCP client reads before it can log in.
 *
 * A client that has never seen this site gets a 401 with a `resource_metadata`
 * pointer, fetches the protected-resource document, learns which authorization
 * server to talk to, fetches that server's metadata, registers itself and starts the
 * flow. None of it needs a human to copy a client id anywhere, which is the whole
 * reason hosted clients can connect to a WordPress site at all.
 *
 * **The 401 points at a route inside this plugin's own REST namespace, not here.**
 * `/.well-known/` is shared by the whole site: a host may answer it for ACME before
 * WordPress runs, and another plugin may claim it earlier. RFC 9728 lets the pointer
 * name any address, and `{endpoint}/oauth/protected-resource` cannot be taken. The
 * `.well-known` paths stay for clients that build them from the URL, which they must
 * for the authorization server document, since that one is derived from the issuer.
 *
 * **Every path served here is scoped to this plugin's own endpoint path and matched
 * exactly.** The bare `/.well-known/oauth-authorization-server` is deliberately NOT
 * claimed: it is a single site-wide slot, two plugins cannot both own it, and a
 * payment plugin quietly winning that race against another plugin's MCP server is a
 * bug that surfaces as somebody else's connector breaking.
 *
 * @since 2.5.0
 */
class Discovery {

	/**
	 * Hook the document handler.
	 *
	 * Negative priority so the documents are served before `rest_api_loaded()` tries
	 * to route the same path as a REST request and 404s it.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ), -100 );
	}

	/**
	 * The issuer this server identifies itself by.
	 *
	 * The endpoint URL itself, which keeps the RFC 8414 path-insertion arithmetic to
	 * one set of paths instead of two.
	 *
	 * @return string
	 */
	public static function issuer() {
		return untrailingslashit( MCPManager::endpoint_url() );
	}

	/**
	 * Path component of the endpoint, e.g. `/wp-json/better-payment/mcp`.
	 *
	 * @return string
	 */
	private static function endpoint_path() {
		$path = wp_parse_url( self::issuer(), PHP_URL_PATH );

		return is_string( $path ) ? untrailingslashit( $path ) : '';
	}

	/**
	 * Path of the WordPress home, e.g. `/sub`, or '' at the domain root.
	 *
	 * @return string
	 */
	private static function home_path() {
		$path = wp_parse_url( home_url(), PHP_URL_PATH );

		return is_string( $path ) ? untrailingslashit( $path ) : '';
	}

	/**
	 * URL of the protected-resource document: what a 401 points a client at.
	 *
	 * @return string
	 */
	public static function protected_resource_url() {
		return MCPManager::oauth_url( 'protected-resource' );
	}

	/**
	 * URL of the authorization-server document.
	 *
	 * @return string
	 */
	public static function authorization_server_url() {
		return MCPManager::oauth_url( 'authorization-server' );
	}

	/**
	 * Every `.well-known` path that serves one document.
	 *
	 * RFC 8414 and RFC 9728 insert the endpoint's path after the segment at the root of
	 * the origin, and OpenID-style clients append the segment to the endpoint. In a
	 * subdirectory install the origin root is not WordPress at all, so a client may
	 * also try that form under the home path, with or without the home path repeated.
	 * All of them name this plugin's endpoint, so none of them is the shared bare slot.
	 *
	 * @param string $document `oauth-protected-resource` or `oauth-authorization-server`.
	 * @return string[]
	 */
	public static function well_known_paths( $document ) {
		$endpoint = self::endpoint_path();
		$home     = self::home_path();
		$segment  = '/.well-known/' . $document;
		$relative = '' !== $home && 0 === strpos( $endpoint, $home . '/' ) ? substr( $endpoint, strlen( $home ) ) : $endpoint;

		return array_values(
			array_unique(
				array(
					$segment . $endpoint,
					$home . $segment . $endpoint,
					$home . $segment . $relative,
					$endpoint . $segment,
				)
			)
		);
	}

	/**
	 * Whether discovery can work on this site at all.
	 *
	 * With plain permalinks `rest_url()` is `?rest_route=…`, the endpoint has no path
	 * of its own, and there is nowhere to hang a `.well-known` document. The settings
	 * screen says so rather than letting a site owner debug a client that cannot
	 * explain what it is missing.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return '' !== self::endpoint_path() && '' !== (string) get_option( 'permalink_structure' );
	}

	/**
	 * Serve a discovery document when the request is for one.
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( ! MCPManager::oauth_enabled() ) {
			return;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';

		if ( '' === $path ) {
			return;
		}

		if ( '' === self::endpoint_path() ) {
			return;
		}

		if ( in_array( $path, self::well_known_paths( 'oauth-protected-resource' ), true ) ) {
			self::send( self::protected_resource_metadata() );
		}

		if ( in_array( $path, self::well_known_paths( 'oauth-authorization-server' ), true ) ) {
			self::send( self::authorization_server_metadata() );
		}
	}

	/**
	 * RFC 9728 protected-resource metadata.
	 *
	 * @return array<string, mixed>
	 */
	public static function protected_resource_metadata() {
		return array(
			'resource'                 => self::issuer(),
			'authorization_servers'    => array( self::issuer() ),
			'scopes_supported'         => Context::supported(),
			'bearer_methods_supported' => array( 'header' ),
			'resource_name'            => 'Better Payment',
			'resource_documentation'   => 'https://betterpayment.co/docs/',
		);
	}

	/**
	 * RFC 8414 authorization-server metadata.
	 *
	 * @return array<string, mixed>
	 */
	public static function authorization_server_metadata() {
		return array(
			'issuer'                                => self::issuer(),
			'authorization_endpoint'                => MCPManager::oauth_url( 'authorize' ),
			'token_endpoint'                        => MCPManager::oauth_url( 'token' ),
			'registration_endpoint'                 => MCPManager::oauth_url( 'register' ),
			'revocation_endpoint'                   => MCPManager::oauth_url( 'revoke' ),
			'scopes_supported'                      => Context::supported(),
			'response_types_supported'              => array( 'code' ),
			'grant_types_supported'                 => array( 'authorization_code', 'refresh_token' ),
			// S256 only. OAuth 2.1 removes `plain`, and a downgrade to it would make
			// the challenge decorative.
			'code_challenge_methods_supported'      => array( 'S256' ),
			// Public clients: PKCE authenticates the token request, not a secret the
			// client could not keep anyway.
			'token_endpoint_auth_methods_supported' => array( 'none' ),
		);
	}

	/**
	 * Emit a document and stop.
	 *
	 * @param array $document Metadata document.
	 * @return void
	 */
	private static function send( array $document ) {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: application/json; charset=utf-8' );
			// Read cross-origin by browser-based clients before any token exists.
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Cache-Control: public, max-age=3600' );
		}

		echo wp_json_encode( $document );

		exit;
	}
}
