<?php
/**
 * Credential redaction for MCP tool output.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strips gateway credentials out of anything a tool is about to return.
 *
 * Settings hold live Stripe / PayPal / Paystack credentials. A tool result is handed
 * to an external AI client, so a credential that reaches the return value has left
 * the site. Presence is reported instead of the value, which is all an assistant
 * needs to answer "is Stripe configured?".
 *
 * PHP 7.4 target: str_contains() is 8.0+, so strpos() is used throughout.
 *
 * @since 2.5.0
 */
class Redactor {

	/**
	 * Substrings that mark a settings key as a credential.
	 *
	 * @return string[]
	 */
	public static function secret_markers() {
		$markers = array(
			'secret',
			'client_id',
			'client_secret',
			'api_key',
			'_key',
			'token',
			'password',
			'passphrase',
			'signature',
			'webhook',
		);

		/**
		 * Filters the substrings that mark a settings key as a credential.
		 *
		 * Add to this list when a new gateway introduces a credential key; never
		 * shorten it to expose a value.
		 *
		 * @since 2.5.0
		 *
		 * @param string[] $markers Lowercase substrings.
		 */
		return (array) apply_filters( 'better_payment/mcp/secret_markers', $markers );
	}

	/**
	 * Whether a settings key holds a credential.
	 *
	 * @param string $key Settings key.
	 * @return bool
	 */
	public static function is_secret( $key ) {
		$key = strtolower( (string) $key );

		foreach ( self::secret_markers() as $marker ) {
			$marker = strtolower( (string) $marker );

			if ( '' !== $marker && false !== strpos( $key, $marker ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace every credential value in a settings array with a presence report.
	 *
	 * @param mixed $settings Settings map, as read from the option.
	 * @return array
	 */
	public static function redact_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		$out = array();

		foreach ( $settings as $key => $value ) {
			if ( self::is_secret( $key ) ) {
				$out[ $key ] = array(
					'redacted' => true,
					'is_set'   => self::has_value( $value ),
				);
				continue;
			}

			$out[ $key ] = is_scalar( $value ) || is_array( $value ) ? $value : null;
		}

		return $out;
	}

	/**
	 * Whether a stored credential has a usable value.
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	public static function has_value( $value ) {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return '' !== trim( (string) $value );
	}
}
