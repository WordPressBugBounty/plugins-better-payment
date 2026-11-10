<?php
/**
 * Rate limits for the MCP endpoint's unauthenticated surface.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limits failed credentials and client registrations, per IP.
 *
 * Tokens cannot be guessed, so this is not about guessing them. An Application Password
 * attempt runs a slow password hash against every password the named user has, and
 * registration writes to the database for anyone who asks. Both are work a stranger can
 * make this site do.
 *
 * Only a presented credential that failed counts. A success does NOT clear the count:
 * it used to, so an attacker holding any working credential (a subscriber's own
 * Application Password) could interleave nine bad guesses with one good login forever.
 * The fixed window expires on its own, and a stranded client can wait it out rather
 * than extending its own lockout by retrying.
 *
 * IPv6 callers are counted per /64, the block one subscriber line is given; counting
 * the full address let a caller rotate through 2^64 addresses for free.
 *
 * @since 2.5.0
 */
class RateLimiter {

	/**
	 * Failed credentials allowed per window.
	 */
	const FAILURES = 10;

	/**
	 * Client registrations allowed per window.
	 */
	const REGISTRATIONS = 10;

	/**
	 * Window length in seconds.
	 */
	const WINDOW = 900;

	/**
	 * Transient key prefix for failed credentials.
	 */
	const FAILURE_PREFIX = 'bp_mcp_fail_';

	/**
	 * Transient key prefix for registrations.
	 */
	const REGISTRATION_PREFIX = 'bp_mcp_reg_';

	/**
	 * Whether this IP has used up its failed credentials.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		$config = self::config();

		return $config['failures'] > 0 && self::count( self::FAILURE_PREFIX ) >= $config['failures'];
	}

	/**
	 * Count one failed credential.
	 *
	 * @return void
	 */
	public static function record_failure() {
		self::increment( self::FAILURE_PREFIX );
	}

	/**
	 * Forget this IP's failures.
	 *
	 * @return void
	 */
	public static function clear() {
		$key = self::key( self::FAILURE_PREFIX );

		if ( '' !== $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Count one registration and say whether it may go ahead.
	 *
	 * @return bool
	 */
	public static function allow_registration() {
		$config = self::config();

		if ( $config['registrations'] > 0 && self::count( self::REGISTRATION_PREFIX ) >= $config['registrations'] ) {
			return false;
		}

		self::increment( self::REGISTRATION_PREFIX );

		return true;
	}

	/**
	 * Seconds a limited caller should wait.
	 *
	 * @return int
	 */
	public static function retry_after() {
		return (int) self::config()['window'];
	}

	/**
	 * The limits, after the filter.
	 *
	 * @return array{failures: int, registrations: int, window: int}
	 */
	public static function config() {
		/**
		 * Filters the MCP rate limits. A limit below 1 turns that limit off.
		 *
		 * @since 2.5.0
		 *
		 * @param array $config `failures`, `registrations`, and `window` in seconds.
		 */
		$config = (array) apply_filters(
			'better_payment/mcp/rate_limit',
			array(
				'failures'      => self::FAILURES,
				'registrations' => self::REGISTRATIONS,
				'window'        => self::WINDOW,
			)
		);

		return array(
			'failures'      => isset( $config['failures'] ) ? (int) $config['failures'] : self::FAILURES,
			'registrations' => isset( $config['registrations'] ) ? (int) $config['registrations'] : self::REGISTRATIONS,
			'window'        => ! empty( $config['window'] ) ? max( 1, (int) $config['window'] ) : self::WINDOW,
		);
	}

	/**
	 * The current count for one limit.
	 *
	 * @param string $prefix Transient prefix.
	 * @return int
	 */
	private static function count( $prefix ) {
		$key = self::key( $prefix );

		if ( '' === $key ) {
			return 0;
		}

		$entry = get_transient( $key );

		return is_array( $entry ) && isset( $entry['count'] ) ? (int) $entry['count'] : 0;
	}

	/**
	 * Add one to a limit, keeping the window that is already open.
	 *
	 * @param string $prefix Transient prefix.
	 * @return void
	 */
	private static function increment( $prefix ) {
		$key = self::key( $prefix );

		if ( '' === $key ) {
			return;
		}

		$now    = time();
		$window = self::config()['window'];
		$entry  = get_transient( $key );

		if ( ! is_array( $entry ) || ! isset( $entry['count'], $entry['start'] ) || $now - (int) $entry['start'] >= $window ) {
			$entry = array(
				'count' => 0,
				'start' => $now,
			);
		}

		++$entry['count'];

		set_transient( $key, $entry, max( 1, $window - ( $now - (int) $entry['start'] ) ) );
	}

	/**
	 * Transient key for this IP, or '' when there is no IP to count against.
	 *
	 * @param string $prefix Transient prefix.
	 * @return string
	 */
	private static function key( $prefix ) {
		$ip = self::bucket( self::client_ip() );

		return '' === $ip ? '' : $prefix . md5( $ip );
	}

	/**
	 * The address a limit counts against: an IPv4 address as it is, an IPv6 address
	 * by its /64 (an IPv4-mapped IPv6 address as its IPv4 address), anything else as
	 * given.
	 *
	 * @param string $ip Client IP.
	 * @return string
	 */
	public static function bucket( $ip ) {
		$ip = trim( (string) $ip );

		if ( '' === $ip || false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return $ip;
		}

		$packed = inet_pton( $ip );

		if ( false === $packed || 16 !== strlen( $packed ) ) {
			return $ip;
		}

		// ::ffff:a.b.c.d is an IPv4 caller.
		if ( str_repeat( "\0", 10 ) . "\xff\xff" === substr( $packed, 0, 12 ) ) {
			return (string) inet_ntop( substr( $packed, 12 ) );
		}

		return (string) inet_ntop( substr( $packed, 0, 8 ) . str_repeat( "\0", 8 ) ) . '/64';
	}

	/**
	 * The caller's IP. `REMOTE_ADDR` only: forwarded headers can be spoofed.
	 *
	 * @return string
	 */
	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the IP the MCP rate limits count against.
		 *
		 * For a site behind a proxy, where `REMOTE_ADDR` is the proxy.
		 *
		 * @since 2.5.0
		 *
		 * @param string $ip Client IP.
		 */
		return (string) apply_filters( 'better_payment/mcp/client_ip', $ip );
	}
}
