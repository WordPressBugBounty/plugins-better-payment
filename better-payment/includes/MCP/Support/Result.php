<?php
/**
 * JSON-safe result shaping for MCP tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts service-layer return values into something that survives wp_json_encode().
 *
 * A tool result crosses a JSON boundary. $wpdb hands back stdClass rows, post meta
 * hands back serialized strings, and a WP_Post is an object with protected state. Any
 * of those reaching the encoder produces either a silent loss or a `false` that the
 * transport can only report as an empty tool result.
 *
 * @since 2.5.0
 */
class Result {

	/**
	 * Recursively reduce objects and arrays to plain arrays and scalars.
	 *
	 * @param mixed $value Any value.
	 * @param int   $depth Recursion guard.
	 * @return mixed
	 */
	public static function plain( $value, $depth = 0 ) {
		if ( $depth > 12 ) {
			return null;
		}

		if ( is_scalar( $value ) || null === $value ) {
			return $value;
		}

		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( ! is_array( $value ) ) {
			// Resources, closures and anything else that cannot be encoded.
			return null;
		}

		$out = array();

		foreach ( $value as $key => $item ) {
			$out[ $key ] = self::plain( $item, $depth + 1 );
		}

		return $out;
	}

	/**
	 * Shape one transaction row for output.
	 *
	 * `form_fields_info` holds the payer's name, e-mail and any custom form fields,
	 * and `email` is set to the e-mail the Transactions screen shows. Both are
	 * withheld unless the caller asks for them, so the default answer to "list my
	 * recent payments" does not ship a page of personal data to an external AI
	 * client. The gateway's `customer_info` is never returned.
	 *
	 * @param object|array $row              Raw transaction row.
	 * @param bool         $include_customer Whether to include personal data.
	 * @return array
	 */
	public static function transaction( $row, $include_customer = false ) {
		$row = self::plain( $row );

		if ( ! is_array( $row ) ) {
			return array();
		}

		// maybe_unserialize() is what AdminAPI does before returning these, so the
		// shape matches what the React admin already consumes.
		if ( isset( $row['form_fields_info'] ) ) {
			$row['form_fields_info'] = self::plain( maybe_unserialize( $row['form_fields_info'] ) );
		}

		// The customer is who the Transactions screen shows: the name and e-mail typed
		// into the payment form. `customer_info` is the gateway's own record (the PayPal
		// account's name, the Stripe cardholder, the whole Checkout Session) and the
		// `email` column is the address the gateway reported. Sent beside the form
		// fields, they made an assistant name the PayPal account owner or the
		// cardholder instead of the donor the admin sees. The gateway record never
		// leaves the site, and `email` becomes the one the admin table shows.
		unset( $row['customer_info'] );

		if ( ! $include_customer ) {
			unset( $row['form_fields_info'], $row['email'] );

			return $row;
		}

		$row['email'] = self::form_email(
			isset( $row['form_fields_info'] ) ? $row['form_fields_info'] : array(),
			isset( $row['email'] ) ? (string) $row['email'] : ''
		);

		return $row;
	}

	/**
	 * The payer e-mail the Transactions screen shows for a row.
	 *
	 * PHP twin of the Transactions table's e-mail cell
	 * (`emailAddress( form_fields_info ) || transaction.email` in
	 * `src/admin/pages/Transactions.js`): the form's `primary_email`, else the legacy
	 * `email` form field, else the row's own `email` column.
	 *
	 * @param mixed  $form_fields Decoded `form_fields_info`.
	 * @param string $fallback    The row's `email` column.
	 * @return string
	 */
	public static function form_email( $form_fields, $fallback = '' ) {
		$form_fields = is_array( $form_fields ) ? $form_fields : array();

		foreach ( array( 'primary_email', 'email' ) as $key ) {
			if ( isset( $form_fields[ $key ] ) && is_scalar( $form_fields[ $key ] ) && '' !== (string) $form_fields[ $key ] ) {
				return (string) $form_fields[ $key ];
			}
		}

		return (string) $fallback;
	}

	/**
	 * Shape a list of transaction rows.
	 *
	 * @param mixed $rows             Raw rows.
	 * @param bool  $include_customer Whether to include personal data.
	 * @return array
	 */
	public static function transactions( $rows, $include_customer = false ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();

		foreach ( $rows as $row ) {
			$out[] = self::transaction( $row, $include_customer );
		}

		return $out;
	}

	/**
	 * A standard "not found" error.
	 *
	 * @param string $message Human message.
	 * @return \WP_Error
	 */
	public static function not_found( $message ) {
		return new \WP_Error( 'better_payment_mcp_not_found', (string) $message, array( 'status' => 404 ) );
	}

	/**
	 * A standard "bad request" error.
	 *
	 * @param string $message Human message.
	 * @return \WP_Error
	 */
	public static function invalid( $message ) {
		return new \WP_Error( 'better_payment_mcp_invalid', (string) $message, array( 'status' => 400 ) );
	}

	/**
	 * A standard "refused" error.
	 *
	 * @param string $message Human message.
	 * @return \WP_Error
	 */
	public static function refused( $message ) {
		return new \WP_Error( 'better_payment_mcp_refused', (string) $message, array( 'status' => 403 ) );
	}
}
