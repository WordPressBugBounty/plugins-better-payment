<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limits how often one visitor can start a payment on one form.
 *
 * The payment handlers are public (`nopriv`, with a nonce printed for every
 * visitor), and starting a payment costs the site something before anyone pays:
 * the PayPal handlers write an `unpaid` transaction row before redirecting, the
 * Stripe and Paystack handlers create a Checkout Session or a transaction at the
 * gateway, and a Pro mixed form with a custom amount creates a Stripe Price per new
 * amount. A script could repeat that without limit, flooding the ledger (and, with
 * a victim's email typed in, their User Dashboard) or the merchant's Stripe account.
 *
 * Each attempt counts against the visitor's IP and the form (page + widget). The
 * default is 10 attempts per 10 minutes, which a real donor, even one whose card is
 * declined a few times, does not reach. Administrators are never limited.
 *
 * @since 2.4.2
 */
class PaymentRateLimiter {

	/**
	 * Attempts allowed per window.
	 */
	const LIMIT = 10;

	/**
	 * Window length in seconds.
	 */
	const WINDOW = 600;

	/**
	 * Transient key prefix.
	 */
	const PREFIX = 'bp_pay_rl_';

	/**
	 * Count one attempt and say whether it may go ahead.
	 *
	 * @param int|string $page_id   Form page (or campaign) id.
	 * @param string     $widget_id Widget or block id.
	 *
	 * @return bool False when this visitor has used up the form's attempts.
	 */
	public static function allow( $page_id, $widget_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$config = self::config();
		$ip     = self::client_ip();

		if ( $config['limit'] < 1 || '' === $ip ) {
			return true;
		}

		$key   = self::PREFIX . md5( $ip . '|' . (int) $page_id . '|' . (string) $widget_id );
		$now   = time();
		$entry = get_transient( $key );

		if ( ! is_array( $entry ) || ! isset( $entry['count'], $entry['start'] ) || $now - (int) $entry['start'] >= $config['window'] ) {
			$entry = array(
				'count' => 0,
				'start' => $now,
			);
		}

		if ( (int) $entry['count'] >= $config['limit'] ) {
			return false;
		}

		++$entry['count'];

		set_transient( $key, $entry, max( 1, $config['window'] - ( $now - (int) $entry['start'] ) ) );

		return true;
	}

	/**
	 * The message shown when a visitor is limited.
	 *
	 * @return string
	 */
	public static function message() {
		return __( 'Too many payment attempts. Please wait a few minutes and try again.', 'better-payment' );
	}

	/**
	 * The limit and window, after the filter.
	 *
	 * @return array{limit: int, window: int}
	 */
	public static function config() {
		/**
		 * Filters how many payment attempts one visitor may start on one form.
		 *
		 * A limit below 1 turns the limit off.
		 *
		 * @since 2.4.2
		 *
		 * @param array $config `limit` (attempts) and `window` (seconds).
		 */
		$config = apply_filters(
			'better_payment/payment_rate_limit',
			array(
				'limit'  => self::LIMIT,
				'window' => self::WINDOW,
			)
		);

		return array(
			'limit'  => isset( $config['limit'] ) ? (int) $config['limit'] : self::LIMIT,
			'window' => isset( $config['window'] ) ? max( 1, (int) $config['window'] ) : self::WINDOW,
		);
	}

	/**
	 * The visitor's IP: `REMOTE_ADDR` only.
	 *
	 * Forwarding headers are set by the client unless a proxy overwrites them, so
	 * trusting them would let a script pick a new "IP" for every request. A site
	 * behind a proxy or CDN supplies the real address through the filter.
	 *
	 * @return string '' when there is no valid address.
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the IP address payment attempts are counted against.
		 *
		 * @since 2.4.2
		 *
		 * @param string $ip `REMOTE_ADDR`.
		 */
		$ip = (string) apply_filters( 'better_payment/payment_rate_limit/client_ip', $ip );

		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
