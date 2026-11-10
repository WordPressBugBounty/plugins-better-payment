<?php
/**
 * Credential resolution for the MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Auth;

use Better_Payment\Lite\MCP\MCPManager;
use Better_Payment\Lite\MCP\OAuth\Discovery;
use Better_Payment\Lite\MCP\OAuth\Store as OAuthStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides who is calling the MCP endpoint, from the request itself.
 *
 * **The ambient session is ignored on purpose.** A logged-in administrator's cookie
 * is not a credential here: if it were, any page on the internet could POST a tool
 * call to this endpoint from that administrator's browser and the site would run it.
 * The endpoint accepts only a credential the client presents deliberately —
 *
 * - `Authorization: Bearer <token>` — an OAuth 2.1 access token this site issued.
 *   This is what Claude, ChatGPT and other hosted clients use.
 * - `Authorization: Basic <user:application-password>` — a WordPress Application
 *   Password. This is what command-line clients use.
 *
 * — and a request carrying neither is 401, whoever happens to be logged in.
 *
 * @since 2.5.0
 */
class Authenticator {

	/**
	 * Authenticate one request and take on its identity.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_User|\WP_Error
	 */
	public static function authenticate( $request ) {
		$header = self::authorization_header( $request );

		if ( '' === $header ) {
			return self::unauthorized( __( 'This endpoint requires an access token or an Application Password.', 'better-payment' ) );
		}

		// strtolower()/strpos(), not str_starts_with(): PHP 7.4 target.
		$lower = strtolower( $header );

		if ( 0 === strpos( $lower, 'bearer ' ) ) {
			return self::from_bearer( trim( substr( $header, 7 ) ) );
		}

		if ( 0 === strpos( $lower, 'basic ' ) ) {
			return self::from_basic( trim( substr( $header, 6 ) ) );
		}

		return self::unauthorized( __( 'Unsupported authorization scheme.', 'better-payment' ) );
	}

	/**
	 * Resolve an OAuth access token.
	 *
	 * @param string $token Presented token.
	 * @return \WP_User|\WP_Error
	 */
	private static function from_bearer( $token ) {
		// The switch has to reach tokens already issued. Only the routes used to go
		// away with it, so an owner who turned OAuth off to cut a client loose left
		// that client's access token working until it expired.
		if ( ! MCPManager::oauth_enabled() ) {
			return self::unauthorized( __( 'OAuth sign-in is switched off on this site.', 'better-payment' ), 'invalid_token' );
		}

		$grant = OAuthStore::resolve_access_token( $token );

		if ( null === $grant ) {
			return self::unauthorized( __( 'The access token is invalid or has expired.', 'better-payment' ), 'invalid_token' );
		}

		$user = get_user_by( 'id', (int) $grant['user_id'] );

		if ( ! $user ) {
			// The token outlived the account it was issued to.
			OAuthStore::revoke_access_token( $token );

			return self::unauthorized( __( 'The account this token belongs to no longer exists.', 'better-payment' ), 'invalid_token' );
		}

		// The consent screen's ticks are only worth something if the grant travels
		// with the request. Recorded before the user is adopted, so nothing can run
		// between authentication and the scope being known.
		Context::set_scopes( isset( $grant['scope'] ) ? $grant['scope'] : array() );

		return self::become( $user );
	}

	/**
	 * Resolve an Application Password.
	 *
	 * @param string $encoded Base64 `user:password`.
	 * @return \WP_User|\WP_Error
	 */
	private static function from_basic( $encoded ) {
		$decoded = base64_decode( $encoded, true );

		if ( false === $decoded || false === strpos( $decoded, ':' ) ) {
			return self::unauthorized( __( 'Malformed Basic credentials.', 'better-payment' ) );
		}

		list( $username, $password ) = explode( ':', $decoded, 2 );

		if ( ! function_exists( 'wp_authenticate_application_password' ) ) {
			return self::unauthorized( __( 'Application Passwords are not available on this site.', 'better-payment' ) );
		}

		// Only Application Passwords, never the account password: this is a
		// credential a site owner can revoke on its own, and WordPress refuses it
		// over plain HTTP.
		$user = wp_authenticate_application_password( null, $username, $password );

		if ( is_wp_error( $user ) || ! ( $user instanceof \WP_User ) ) {
			return self::unauthorized( __( 'The username or Application Password is wrong.', 'better-payment' ) );
		}

		// The owner's own credential, created and revoked in wp-admin: it carries
		// whatever its owner can do, so there is no narrower grant to record.
		Context::set_unscoped();

		return self::become( $user );
	}

	/**
	 * Take on a user's identity for the rest of the request.
	 *
	 * @param \WP_User $user Authenticated user.
	 * @return \WP_User
	 */
	private static function become( \WP_User $user ) {
		wp_set_current_user( (int) $user->ID );

		return $user;
	}

	/**
	 * Read the Authorization header.
	 *
	 * Several server configurations drop it before PHP sees it, which is why the
	 * fallbacks exist; a site where all of them are empty cannot use this endpoint
	 * at all, and the 401 says so.
	 *
	 * @param mixed $request Incoming request, if there is one.
	 * @return string
	 */
	public static function authorization_header( $request ) {
		if ( $request instanceof \WP_REST_Request ) {
			$header = (string) $request->get_header( 'authorization' );

			if ( '' !== $header ) {
				return $header;
			}
		}

		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			}
		}

		// Apache with mod_php hands Basic credentials over as separate variables
		// rather than as a header.
		if ( ! empty( $_SERVER['PHP_AUTH_USER'] ) ) {
			$user = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- A password is compared as sent; sanitizing it would change it.
			$pass = isset( $_SERVER['PHP_AUTH_PW'] ) ? wp_unslash( $_SERVER['PHP_AUTH_PW'] ) : '';

			return 'Basic ' . base64_encode( $user . ':' . $pass );
		}

		return '';
	}

	/**
	 * A 401 carrying the discovery pointer MCP clients follow.
	 *
	 * RFC 9728: the `WWW-Authenticate` header names the resource metadata document,
	 * which is how a client that has never seen this site finds its authorization
	 * server and starts the OAuth flow. Without it a client can only report "401"
	 * and stop.
	 *
	 * @param string $message Human message.
	 * @param string $error   OAuth error code.
	 * @return \WP_Error
	 */
	private static function unauthorized( $message, $error = 'invalid_request' ) {
		return new \WP_Error(
			'better_payment_mcp_unauthorized',
			$message,
			array(
				'status'           => 401,
				'www_authenticate' => sprintf(
					'Bearer error="%s", error_description="%s", resource_metadata="%s"',
					$error,
					// Quoted header parameter: a stray quote would split the header.
					str_replace( '"', "'", (string) $message ),
					Discovery::protected_resource_url()
				),
			)
		);
	}
}
