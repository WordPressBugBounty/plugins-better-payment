<?php
/**
 * `Origin` validation for the MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides whether a request's `Origin` header may reach the endpoint.
 *
 * The MCP Streamable HTTP transport requires this check, to stop DNS rebinding. A request
 * with no `Origin` is not a browser and passes: desktop and server-side clients send none.
 * The site itself, loopback (the MCP Inspector and other local tools) and the hosted
 * clients' own web apps pass too. Everything else is refused.
 *
 * It is a second line behind `Auth\Authenticator`, which never accepts a cookie.
 *
 * @since 2.5.0
 */
class Origin {

	/**
	 * Hosts of the hosted MCP clients' web apps. Compared over HTTPS only.
	 *
	 * Hosts rather than URLs: nothing here is ever requested.
	 *
	 * @var string[]
	 */
	const CLIENT_HOSTS = array( 'claude.ai', 'claude.com', 'chatgpt.com', 'chat.openai.com' );

	/**
	 * Whether one `Origin` header value may proceed.
	 *
	 * @param string $origin Header value; '' when absent.
	 * @return bool
	 */
	public static function allows( $origin ) {
		$origin = trim( (string) $origin );

		if ( '' === $origin ) {
			return true;
		}

		$scheme = strtolower( (string) wp_parse_url( $origin, PHP_URL_SCHEME ) );
		$host   = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );

		// Includes the literal `null` a sandboxed frame sends.
		if ( '' === $host ) {
			return false;
		}

		// VS Code and Cursor run their chat UI in an Electron renderer.
		if ( 'vscode-file' === $scheme ) {
			return 'vscode-app' === $host;
		}

		$site_hosts = array(
			strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
			strtolower( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ),
		);

		if ( in_array( $host, array_merge( $site_hosts, array( 'localhost', '127.0.0.1', '[::1]' ) ), true ) ) {
			return true;
		}

		/**
		 * Filters the extra hosts that may call the MCP endpoint from a browser.
		 *
		 * @since 2.5.0
		 *
		 * @param mixed $hosts Hostnames, compared over HTTPS only.
		 */
		$hosts = (array) apply_filters( 'better_payment/mcp/allowed_origin_hosts', self::CLIENT_HOSTS );

		return 'https' === $scheme && in_array( $host, $hosts, true );
	}
}
