<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-time repair of the `better_payment_settings` option's keys.
 *
 * Two bugs wrote keys nothing reads:
 *
 *   1. **The e-mail templates.** The React Settings screen saved the admin
 *      message as `..._general_email_message` and every customer field as
 *      `..._general_email_customer_{field}`, while the mailer (`Handler`), the
 *      payment-form block (`BlockManager`) and `DB::default_settings()` read
 *      `..._general_email_message_admin` and `..._general_email_{field}_customer`.
 *      An admin who typed a customer subject saw it saved and re-displayed, but
 *      every e-mail went out with the default. The value under the wrong key is
 *      what the admin last saw and typed in the only admin UI, so a non-empty one
 *      wins and is moved onto the canonical key. An empty one never overwrites a
 *      canonical value. The wrong keys are then removed.
 *   2. **A JSON list posted to `POST /settings`.** Its numeric keys were merged
 *      into the option as settings named `0`, `1`, … No setting key is numeric,
 *      so every integer key is removed.
 *
 * Runs once per site on `wp_loaded` (flag below), not on a version change: a
 * site already on the release that shipped the fix would never run a
 * version-gated migration. Running it again is harmless.
 *
 * @since 2.4.3
 */
class SettingsKeyRepair {

	/**
	 * Set once the repair has run on this site.
	 */
	const OPTION = 'better_payment_settings_keys_repaired';

	/**
	 * The settings option.
	 */
	const SETTINGS_OPTION = 'better_payment_settings';

	/**
	 * Wrong key => canonical key.
	 *
	 * @return array<string, string>
	 */
	public static function key_map() {
		$prefix = 'better_payment_settings_general_email_';
		$map    = array(
			$prefix . 'message' => $prefix . 'message_admin',
		);

		foreach ( array( 'subject', 'message', 'from_name', 'from_email', 'reply_to', 'cc', 'bcc', 'send_as' ) as $field ) {
			$map[ $prefix . 'customer_' . $field ] = $prefix . $field . '_customer';
		}

		return $map;
	}

	/**
	 * Repair the option, unless that already happened.
	 *
	 * @return bool True when this run changed the stored option.
	 */
	public static function maybe_run() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return false;
		}

		$changed = false;
		$stored  = get_option( self::SETTINGS_OPTION, array() );

		if ( is_array( $stored ) ) {
			$repaired = self::repair( $stored );

			if ( $repaired !== $stored ) {
				update_option( self::SETTINGS_OPTION, $repaired );
				$changed = true;
			}
		}

		update_option( self::OPTION, 'yes' );

		return $changed;
	}

	/**
	 * The repaired settings array. Pure: reads and writes nothing.
	 *
	 * @param array $settings Stored settings.
	 * @return array
	 */
	public static function repair( array $settings ) {
		foreach ( self::key_map() as $wrong => $canonical ) {
			if ( ! array_key_exists( $wrong, $settings ) ) {
				continue;
			}

			if ( ! self::is_blank( $settings[ $wrong ] ) ) {
				$settings[ $canonical ] = $settings[ $wrong ];
			}

			unset( $settings[ $wrong ] );
		}

		foreach ( array_keys( $settings ) as $key ) {
			if ( is_int( $key ) ) {
				unset( $settings[ $key ] );
			}
		}

		return $settings;
	}

	/**
	 * Whether a stored value is empty for the purpose of the move.
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	private static function is_blank( $value ) {
		if ( is_string( $value ) ) {
			return '' === trim( $value );
		}

		return null === $value || array() === $value;
	}
}
