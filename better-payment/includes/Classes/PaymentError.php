<?php

namespace Better_Payment\Lite\Classes;

use WP_Error;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a failed payment tells the payer, and what it tells the admin.
 *
 * A gateway's own error text is written for the merchant: "Invalid API Key provided",
 * a `line_items[0][amount]` parameter name, a cURL message with a host in it. Handlers
 * used to pass it straight to the payer, who could do nothing with it, while the admin
 * saw nothing at all. So the payer gets a plain message for the kind of failure, and the
 * real reason goes into a small log the admin reads on the Transactions screen.
 *
 * A failed session creation has no transaction row, which is why the log is its own
 * capped, non-autoloaded option. Repeats of one failure merge into one entry, and only
 * handlers past the rate limiter and the settings check write here, so a visitor can
 * neither grow it nor flush it. It holds no payer data: what a gateway echoes back goes
 * through redact() first.
 *
 * @since 2.5.0
 */
class PaymentError {

	/** The option that holds the log. */
	const OPTION = 'better_payment_payment_errors';

	/** Entries kept, newest first. */
	const LIMIT = 50;

	/** Longest gateway message kept. */
	const MESSAGE_LENGTH = 300;

	const CONFIG   = 'config';
	const AMOUNT   = 'amount';
	const CURRENCY = 'currency';
	const BUSY     = 'busy';
	const UNKNOWN  = 'unknown';

	/**
	 * Plain message for a kind of failure.
	 *
	 * @param string $kind One of the class constants.
	 * @return string
	 */
	public static function message( string $kind ): string {
		switch ( $kind ) {
			case self::CONFIG:
				return __( 'This payment method is not available right now.', 'better-payment' );
			case self::AMOUNT:
				return __( 'This amount cannot be processed.', 'better-payment' );
			case self::CURRENCY:
				return __( 'This currency cannot be processed.', 'better-payment' );
			case self::BUSY:
				return __( 'The payment service is busy. Please try again.', 'better-payment' );
		}

		return __( 'We could not start your payment. Please try again.', 'better-payment' );
	}

	/**
	 * Record a Stripe failure and answer with the payer's message.
	 *
	 * @param array|WP_Error $response wp_remote_*() result.
	 * @param string         $surface  `widget`, `block`, `elementor_form` or `return`.
	 * @param int            $page_id  The form's page.
	 * @return string
	 */
	public static function stripe( $response, string $surface, int $page_id ): string {
		$failure = self::read_stripe( $response );

		return self::report( 'stripe', $surface, $page_id, $failure );
	}

	/**
	 * Record a Paystack failure and answer with the payer's message.
	 *
	 * @param array|WP_Error $response wp_remote_*() result.
	 * @param string         $surface  `widget`, `block`, `elementor_form` or `return`.
	 * @param int            $page_id  The form's page.
	 * @return string
	 */
	public static function paystack( $response, string $surface, int $page_id ): string {
		$failure = self::read_paystack( $response );

		return self::report( 'paystack', $surface, $page_id, $failure );
	}

	/**
	 * Record a gateway that cannot run, such as a missing key.
	 *
	 * @param string $gateway `stripe`, `paypal` or `paystack`.
	 * @param string $surface `widget`, `block` or `elementor_form`.
	 * @param int    $page_id The form's page.
	 * @param string $reason  What the admin must fix.
	 * @return string The payer's message.
	 */
	public static function not_configured( string $gateway, string $surface, int $page_id, string $reason ): string {
		return self::report(
			$gateway,
			$surface,
			$page_id,
			array(
				'kind'    => self::CONFIG,
				'code'    => 'not_configured',
				'message' => $reason,
			)
		);
	}

	/**
	 * Answer a refused checkout from an add-on.
	 *
	 * The error's message is written for the payer. `gateway_message` in its data is the
	 * gateway's own text, which only the admin reads.
	 *
	 * @param WP_Error $error   The refusal.
	 * @param string   $gateway Gateway slug.
	 * @param string   $surface `widget`, `block` or `elementor_form`.
	 * @param int      $page_id The form's page.
	 * @return string
	 */
	public static function checkout_refusal( WP_Error $error, string $gateway, string $surface, int $page_id ): string {
		$data = $error->get_error_data();

		if ( is_array( $data ) && ! empty( $data['gateway_message'] ) ) {
			self::record(
				array(
					'gateway' => $gateway,
					'surface' => $surface,
					'page_id' => $page_id,
					'kind'    => self::UNKNOWN,
					'code'    => (string) $error->get_error_code(),
					'message' => (string) $data['gateway_message'],
				)
			);
		}

		return $error->get_error_message();
	}

	/**
	 * Read the kind of failure out of a Stripe answer.
	 *
	 * @param array|WP_Error $response wp_remote_*() result.
	 * @return array{kind: string, code: string, message: string}
	 */
	public static function read_stripe( $response ): array {
		if ( is_wp_error( $response ) ) {
			return self::connection_failure( $response );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$error  = is_array( $body ) && isset( $body['error'] ) && is_array( $body['error'] ) ? $body['error'] : array();

		$type  = self::text( $error['type'] ?? '' );
		$code  = self::text( $error['code'] ?? '' );
		$param = self::text( $error['param'] ?? '' );
		$kind  = self::UNKNOWN;

		if ( in_array( $type, array( 'authentication_error', 'permission_error' ), true ) || in_array( $status, array( 401, 403 ), true ) ) {
			$kind = self::CONFIG;
		} elseif ( false !== strpos( $code, 'amount' ) || false !== strpos( $param, 'amount' ) ) {
			$kind = self::AMOUNT;
		} elseif ( false !== strpos( $code, 'currency' ) || false !== strpos( $param, 'currency' ) ) {
			$kind = self::CURRENCY;
		} elseif ( in_array( $type, array( 'rate_limit_error', 'api_error', 'api_connection_error' ), true ) || 429 === $status || $status >= 500 ) {
			$kind = self::BUSY;
		}

		return array(
			'kind'    => $kind,
			'code'    => '' !== $code ? $code : $type,
			'message' => self::text( $error['message'] ?? '' ),
		);
	}

	/**
	 * Read the kind of failure out of a Paystack answer.
	 *
	 * Paystack sends no error type, only a sentence and the HTTP status.
	 *
	 * @param array|WP_Error $response wp_remote_*() result.
	 * @return array{kind: string, code: string, message: string}
	 */
	public static function read_paystack( $response ): array {
		if ( is_wp_error( $response ) ) {
			return self::connection_failure( $response );
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$body    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$message = is_array( $body ) ? self::text( $body['message'] ?? '' ) : '';
		$lower   = strtolower( $message );
		$kind    = self::UNKNOWN;

		if ( in_array( $status, array( 401, 403 ), true ) ) {
			$kind = self::CONFIG;
		} elseif ( false !== strpos( $lower, 'currency' ) ) {
			$kind = self::CURRENCY;
		} elseif ( false !== strpos( $lower, 'amount' ) ) {
			$kind = self::AMOUNT;
		} elseif ( 429 === $status || $status >= 500 ) {
			$kind = self::BUSY;
		}

		return array(
			'kind'    => $kind,
			'code'    => (string) $status,
			'message' => $message,
		);
	}

	/**
	 * Remove what must not sit in an option: keys, addresses and long digit runs.
	 *
	 * @param string $text Gateway text.
	 * @return string
	 */
	public static function redact( string $text ): string {
		$text = (string) preg_replace( '/\b[a-z]{2,4}_(?:test|live)_[A-Za-z0-9*]+/', '[key]', $text );
		$text = (string) preg_replace( '/[^\s@]+@[^\s@]+\.[^\s@]+/', '[email]', $text );
		$text = (string) preg_replace( '/\d{12,}/', '[number]', $text );

		return substr( $text, 0, self::MESSAGE_LENGTH );
	}

	/**
	 * Add a failure to the log, merging it into an identical entry.
	 *
	 * @param array $entry `gateway`, `surface`, `page_id`, `kind`, `code`, `message`.
	 * @return void
	 */
	public static function record( array $entry ): void {
		$entry = array(
			'gateway' => sanitize_key( (string) ( $entry['gateway'] ?? '' ) ),
			'surface' => sanitize_key( (string) ( $entry['surface'] ?? '' ) ),
			'page_id' => (int) ( $entry['page_id'] ?? 0 ),
			'kind'    => sanitize_key( (string) ( $entry['kind'] ?? self::UNKNOWN ) ),
			'code'    => self::text( $entry['code'] ?? '' ),
			'message' => self::redact( self::text( $entry['message'] ?? '' ) ),
			'count'   => 1,
			'time'    => gmdate( 'Y-m-d H:i:s' ),
		);

		$log = self::entries();

		foreach ( $log as $index => $known ) {
			$same = true;
			foreach ( array( 'gateway', 'surface', 'page_id', 'kind', 'code', 'message' ) as $key ) {
				$same = $same && $known[ $key ] === $entry[ $key ];
			}

			if ( $same ) {
				$entry['count'] = (int) $known['count'] + 1;
				unset( $log[ $index ] );
				break;
			}
		}

		array_unshift( $log, $entry );

		update_option( self::OPTION, array_slice( array_values( $log ), 0, self::LIMIT ), false );
	}

	/**
	 * The log, newest first.
	 *
	 * @return array[]
	 */
	public static function entries(): array {
		$log = get_option( self::OPTION, array() );

		return is_array( $log ) ? array_values( array_filter( $log, 'is_array' ) ) : array();
	}

	/**
	 * Empty the log.
	 *
	 * @return void
	 */
	public static function clear(): void {
		delete_option( self::OPTION );
	}

	/**
	 * Log a failure and answer with the payer's message.
	 *
	 * @param string $gateway Gateway slug.
	 * @param string $surface Where it failed.
	 * @param int    $page_id The form's page.
	 * @param array  $failure `kind`, `code`, `message`.
	 * @return string
	 */
	private static function report( string $gateway, string $surface, int $page_id, array $failure ): string {
		self::record(
			array(
				'gateway' => $gateway,
				'surface' => $surface,
				'page_id' => $page_id,
				'kind'    => $failure['kind'],
				'code'    => $failure['code'],
				'message' => $failure['message'],
			)
		);

		return self::message( $failure['kind'] );
	}

	/**
	 * A request that never reached the gateway.
	 *
	 * @param WP_Error $error The transport error.
	 * @return array{kind: string, code: string, message: string}
	 */
	private static function connection_failure( WP_Error $error ): array {
		return array(
			'kind'    => self::BUSY,
			'code'    => (string) $error->get_error_code(),
			'message' => $error->get_error_message(),
		);
	}

	/**
	 * A scalar as clean text; anything else as ''.
	 *
	 * @param mixed $value Decoded JSON value.
	 * @return string
	 */
	private static function text( $value ): string {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}
