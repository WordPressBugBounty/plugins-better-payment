<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the gateway secret keys an Elementor form uses, on the server only.
 *
 * Up to 2.4.1 the secret-key controls of the Better Payment widget and of the
 * Elementor Pro form actions used the global secret keys from Better Payment →
 * Settings as their control `default`. Elementor sends every control's default to
 * the editor in the widget config (`elementor_ajax` → `get_widgets_config`), so
 * any user who could open the editor — a Contributor on their own draft — could
 * read the site's live Stripe and Paystack secret keys. The non-admin guard in
 * `Better_Payment_Widget::register_controls()` checks `is_edit_mode()`, which is
 * false for that request, so it never applied.
 *
 * The controls now default to empty, and an empty secret is resolved here from
 * the global settings at payment time. The value each form ends up with is the
 * same as before: the same global key, picked by the same rule.
 *
 * @since 2.4.2
 */
class GatewaySecrets {

	/**
	 * Widget setting => the global setting it falls back to.
	 *
	 * The Paystack secret is resolved separately, because its fallback depends on
	 * the global Paystack live mode.
	 */
	const STRIPE_WIDGET_KEYS = array(
		'better_payment_stripe_secret_key'      => 'better_payment_settings_payment_stripe_test_secret',
		'better_payment_stripe_secret_key_live' => 'better_payment_settings_payment_stripe_live_secret',
	);

	/**
	 * The Paystack secret-key setting of the widget and of the form action.
	 */
	const PAYSTACK_KEY = 'better_payment_paystack_secret_key';

	/**
	 * Fill the empty secret keys of a Better Payment widget's settings.
	 *
	 * A key the form sets itself is kept as it is.
	 *
	 * @param mixed      $settings Widget settings (`get_settings_for_display()`).
	 * @param array|null $global   Global settings; read from the database when null.
	 *
	 * @return mixed The settings, with empty secret keys filled in.
	 */
	public static function fill_widget_settings( $settings, $global = null ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$global = self::global_settings( $global );

		foreach ( self::STRIPE_WIDGET_KEYS as $setting => $global_setting ) {
			if ( self::is_empty( $settings, $setting ) ) {
				$settings[ $setting ] = self::global_value( $global, $global_setting );
			}
		}

		if ( self::is_empty( $settings, self::PAYSTACK_KEY ) ) {
			$settings[ self::PAYSTACK_KEY ] = self::paystack_secret( $global );
		}

		return $settings;
	}

	/**
	 * The Stripe secret key of an Elementor Pro form, falling back to the global key.
	 *
	 * The fallback follows the global Stripe live mode, as the control default did.
	 *
	 * @param mixed      $value  The form's own `better_payment_stripe_secret_key`.
	 * @param array|null $global Global settings; read from the database when null.
	 *
	 * @return string
	 */
	public static function stripe_form_secret( $value, $global = null ) {
		if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
			return (string) $value;
		}

		$global  = self::global_settings( $global );
		$is_live = 'yes' === self::global_value( $global, 'better_payment_settings_payment_stripe_live_mode' );

		return self::global_value( $global, $is_live ? 'better_payment_settings_payment_stripe_live_secret' : 'better_payment_settings_payment_stripe_test_secret' );
	}

	/**
	 * The Paystack secret key of an Elementor Pro form, falling back to the global key.
	 *
	 * @param mixed      $value  The form's own `better_payment_paystack_secret_key`.
	 * @param array|null $global Global settings; read from the database when null.
	 *
	 * @return string
	 */
	public static function paystack_form_secret( $value, $global = null ) {
		if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
			return (string) $value;
		}

		return self::paystack_secret( self::global_settings( $global ) );
	}

	/**
	 * Whether an empty widget secret-key setting has a global key to fall back to.
	 *
	 * The editor can no longer see the global key, so the "not configured" notices
	 * read this flag instead. It is a yes/no answer, never the key.
	 *
	 * @param string     $setting A key of STRIPE_WIDGET_KEYS, or PAYSTACK_KEY.
	 * @param array|null $global  Global settings; read from the database when null.
	 *
	 * @return bool
	 */
	public static function widget_has_fallback( $setting, $global = null ) {
		$settings = self::fill_widget_settings( array(), $global );

		return isset( $settings[ $setting ] ) && '' !== $settings[ $setting ];
	}

	/**
	 * Name of the hidden widget control that carries widget_has_fallback() to the editor.
	 *
	 * @param string $setting A secret-key setting.
	 *
	 * @return string
	 */
	public static function fallback_control( $setting ) {
		return $setting . '_fallback';
	}

	/**
	 * Elementor condition terms: true when a widget has no secret key to pay with.
	 *
	 * That is its own key empty AND no global key to fall back to. Checking the
	 * widget's key alone showed "not configured" on every form, since the control
	 * defaults to empty.
	 *
	 * @param string $setting A secret-key setting.
	 *
	 * @return array
	 */
	public static function missing_secret_condition( $setting ) {
		return array(
			'relation' => 'and',
			'terms'    => array(
				array(
					'name'  => $setting,
					'value' => '',
				),
				array(
					'name'  => self::fallback_control( $setting ),
					'value' => '',
				),
			),
		);
	}

	/**
	 * Whether any global Stripe or Paystack secret key is saved.
	 *
	 * @param array|null $global Global settings; read from the database when null.
	 *
	 * @return bool
	 */
	public static function has_global_secret( $global = null ) {
		$global = self::global_settings( $global );

		$settings = array(
			'better_payment_settings_payment_stripe_test_secret',
			'better_payment_settings_payment_stripe_live_secret',
			'better_payment_settings_payment_paystack_test_secret',
			'better_payment_settings_payment_paystack_live_secret',
		);

		foreach ( $settings as $setting ) {
			if ( '' !== self::global_value( $global, $setting ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The global Paystack secret for the global Paystack live mode.
	 *
	 * @param array $global Global settings.
	 *
	 * @return string
	 */
	private static function paystack_secret( array $global ) {
		$is_live = 'yes' === self::global_value( $global, 'better_payment_settings_payment_paystack_live_mode' );

		return self::global_value( $global, $is_live ? 'better_payment_settings_payment_paystack_live_secret' : 'better_payment_settings_payment_paystack_test_secret' );
	}

	/**
	 * @param array|null $global Global settings, or null to read them.
	 *
	 * @return array
	 */
	private static function global_settings( $global ) {
		if ( is_array( $global ) ) {
			return $global;
		}

		$settings = DB::get_settings();

		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * @param array  $global  Global settings.
	 * @param string $setting Setting name.
	 *
	 * @return string The value, or '' when it is missing or not a string.
	 */
	private static function global_value( array $global, $setting ) {
		if ( ! isset( $global[ $setting ] ) || ! is_scalar( $global[ $setting ] ) ) {
			return '';
		}

		return trim( (string) $global[ $setting ] );
	}

	/**
	 * @param array  $settings Widget settings.
	 * @param string $setting  Setting name.
	 *
	 * @return bool True when the setting is missing or blank.
	 */
	private static function is_empty( array $settings, $setting ) {
		return ! isset( $settings[ $setting ] ) || ! is_scalar( $settings[ $setting ] ) || '' === trim( (string) $settings[ $setting ] );
	}
}
