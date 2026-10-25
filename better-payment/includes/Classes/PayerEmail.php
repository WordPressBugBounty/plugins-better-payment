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
 * Refuses a payment whose payer email is not an address.
 *
 * The handlers used to pass the posted email through sanitize_email(), which turns
 * `not-an-email` into '' without a word: Stripe got a Checkout Session, the ledger an
 * `unpaid` row with no email, and the payer no receipt. The browser checks the format
 * too (src/js/_form-validation.js), but the handlers are public, so the rule is held here.
 *
 * Only a value that was sent is checked. An empty email stays allowed: forms whose
 * email field is optional (and old pages that have none) keep working. Whether an
 * email is required at all is the form's business, not this check's.
 *
 * @since 2.4.2
 */
class PayerEmail {

	/**
	 * Error code for a refused email.
	 */
	const ERROR_CODE = 'bp_invalid_email';

	/**
	 * Check every email key the handler reads from the posted fields.
	 *
	 * @param mixed    $fields Posted fields (unslashed). Anything but an array passes.
	 * @param string[] $keys   Keys holding the payer's email.
	 * @return WP_Error|null Null when every sent email is an address.
	 */
	public static function check( $fields, array $keys = array( 'primary_email' ) ) {
		if ( ! is_array( $fields ) ) {
			return null;
		}

		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $fields ) ) {
				continue;
			}

			if ( ! self::is_acceptable( $fields[ $key ] ) ) {
				return new WP_Error( self::ERROR_CODE, self::message() );
			}
		}

		return null;
	}

	/**
	 * Empty, or an email address.
	 *
	 * @param mixed $value Posted value.
	 * @return bool
	 */
	public static function is_acceptable( $value ) {
		if ( null === $value ) {
			return true;
		}

		if ( ! is_scalar( $value ) ) {
			return false;
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return true;
		}

		// is_email() on the value as sent, not on sanitize_email()'s output: that strips
		// characters until something passes, so `a b@example.org` would be charged as
		// `ab@example.org` — a receipt to someone else.
		return false !== is_email( $value );
	}

	/**
	 * The refusal shown to the payer.
	 *
	 * @return string
	 */
	public static function message() {
		return __( 'Please enter a valid email address.', 'better-payment' );
	}
}
