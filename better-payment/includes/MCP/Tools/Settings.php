<?php
/**
 * Settings tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Campaign\CampaignCurrency;
use Better_Payment\Lite\MCP\Tools\Campaigns as CampaignTools;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\DryRun;
use Better_Payment\Lite\MCP\Support\Redactor;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads plugin settings (credentials redacted) and writes a narrow allowlist.
 *
 * Two rules carry the whole security position of this file:
 *
 * 1. A credential is never returned. `Support\Redactor` reports presence instead.
 *    Settings hold live Stripe, PayPal and Paystack keys, and a tool result is handed
 *    to an external AI client.
 * 2. A credential is never written, at any privilege level. A wrong live key stops
 *    payments site-wide, and no assistant needs to type one. The write allowlist is
 *    explicit, and a key outside it is REFUSED rather than silently skipped — a
 *    client that believes it wrote a value that vanished is worse off than one that
 *    is told no.
 *
 * The MCP module's own switches (`..._general_mcp`, `..._general_mcp_oauth`) are not
 * on the allowlist, so a tool cannot widen its own access.
 *
 * @since 2.5.0
 */
class Settings implements ToolProvider {

	/**
	 * Settings keys an MCP client may write.
	 *
	 * Feature toggles, currency and the e-mail templates. Nothing that authenticates
	 * to a payment gateway, and nothing outside the plugin's own option.
	 *
	 * @return string[]
	 */
	public static function writable_keys() {
		$keys = array(
			'better_payment_settings_general_general_paypal',
			'better_payment_settings_general_general_stripe',
			'better_payment_settings_general_general_paystack',
			'better_payment_settings_general_general_user_dashboard',
			'better_payment_settings_general_general_fundraising_campaign',
			'better_payment_settings_general_general_email',
			'better_payment_settings_general_general_currency',
			'better_payment_settings_general_email_to',
			'better_payment_settings_general_email_subject',
			'better_payment_settings_general_email_message_admin',
			'better_payment_settings_general_email_from_name',
			'better_payment_settings_general_email_subject_customer',
			'better_payment_settings_general_email_message_customer',
			'better_payment_settings_general_email_from_name_customer',
		);

		/**
		 * Filters the settings keys an MCP client may write.
		 *
		 * Credentials must never be added here. Redactor::is_secret() is enforced on
		 * top of this list, so an accidental addition is refused anyway.
		 *
		 * @since 2.5.0
		 *
		 * @param string[] $keys Writable settings keys.
		 */
		return (array) apply_filters( 'better_payment/mcp/writable_settings_keys', $keys );
	}

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return array(
			'settings_get'    => array(
				'title'       => __( 'Read Better Payment settings', 'better-payment' ),
				'description' => __( 'Read Better Payment settings. Gateway credentials are never returned — each credential key reports only whether a value is set.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'prefix' => array(
							'type'        => 'string',
							'description' => 'Return only keys starting with this prefix, e.g. better_payment_settings_payment_stripe.',
						),
					)
				),
				'callback'    => array( self::class, 'get_settings' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'settings_schema' => array(
				'title'       => __( 'Describe Better Payment settings', 'better-payment' ),
				'description' => __( 'List every Better Payment settings key with its default, whether it holds a credential, and whether an MCP client may write it. Call this before settings_update.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'describe_settings' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'gateway_status'  => array(
				'title'       => __( 'Payment gateway status', 'better-payment' ),
				'description' => __( 'Report which Better Payment gateways are enabled, whether each is in live or test mode, whether the credentials that mode needs are present (PayPal: business e-mail; Stripe and Paystack: public and secret key), whether it is ready to take payments, and which credential fields are missing. No credential value is returned.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'gateway_status' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'settings_update' => array(
				'title'       => __( 'Update Better Payment settings', 'better-payment' ),
				'description' => __( 'Update Better Payment settings from an allowlist covering feature toggles, currency and e-mail templates. Gateway credentials cannot be written through this tool and any attempt is refused. The currency setting is the currency new campaigns start in: it does not change an existing campaign that has its own currency. When the currency changes, the result lists under campaigns which campaigns keep their own currency (and whether each can still be switched with campaign_edit) and which follow the new one; tell the user this rather than saying the campaigns now use the new currency.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'settings' => array(
							'type'                 => 'object',
							'description'          => 'Map of settings key to new value. Every key must appear in settings_schema with writable true.',
							'additionalProperties' => true,
						),
						'dry_run'  => DryRun::property(),
					),
					array( 'settings' )
				),
				'callback'    => array( self::class, 'update_settings' ),
				'annotations' => array(
					'idempotentHint' => true,
				),
			),
		);
	}

	/**
	 * Read settings, redacted.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function get_settings( $input = array() ) {
		$input    = is_array( $input ) ? $input : array();
		$settings = DB::get_settings();
		$settings = is_array( $settings ) ? $settings : array();

		$prefix = isset( $input['prefix'] ) ? (string) $input['prefix'] : '';

		if ( '' !== $prefix ) {
			$filtered = array();

			foreach ( $settings as $key => $value ) {
				// strpos(), not str_starts_with(): the plugin targets PHP 7.4.
				if ( 0 === strpos( (string) $key, $prefix ) ) {
					$filtered[ $key ] = $value;
				}
			}

			$settings = $filtered;
		}

		return array( 'settings' => Redactor::redact_settings( $settings ) );
	}

	/**
	 * Describe every settings key.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function describe_settings( $input = array() ) {
		unset( $input );

		$defaults = (array) DB::default_settings();
		$writable = self::writable_keys();

		$keys = array();

		foreach ( $defaults as $key => $default ) {
			$is_secret = Redactor::is_secret( $key );

			$keys[] = array(
				'key'       => (string) $key,
				'is_secret' => $is_secret,
				'writable'  => ( ! $is_secret && in_array( $key, $writable, true ) ),
				'default'   => $is_secret ? null : Result::plain( $default ),
			);
		}

		return array( 'keys' => $keys );
	}

	/**
	 * The credentials each gateway needs to take a payment, per mode.
	 *
	 * Field name => settings key. The same rule as the setup progress and Pro's
	 * popup: PayPal takes payments with the business e-mail alone; Stripe and
	 * Paystack need the public and the secret key of the mode they are in. Keys a gateway only uses for something else
	 * (Pro's PayPal refund client id / secret, a Stripe webhook secret) do not count.
	 *
	 * @since 2.5.0
	 *
	 * @return array<string, mixed> Gateway => [ 'live' => [ field => key ], 'test' => [ field => key ] ].
	 */
	public static function gateway_credential_keys() {
		$paypal = array( 'business_email' => 'better_payment_settings_payment_paypal_email' );

		$keys = array(
			'stripe'   => array(
				'live' => array(
					'public_key' => 'better_payment_settings_payment_stripe_live_public',
					'secret_key' => 'better_payment_settings_payment_stripe_live_secret',
				),
				'test' => array(
					'public_key' => 'better_payment_settings_payment_stripe_test_public',
					'secret_key' => 'better_payment_settings_payment_stripe_test_secret',
				),
			),
			'paypal'   => array(
				'live' => $paypal,
				'test' => $paypal,
			),
			'paystack' => array(
				'live' => array(
					'public_key' => 'better_payment_settings_payment_paystack_live_public',
					'secret_key' => 'better_payment_settings_payment_paystack_live_secret',
				),
				'test' => array(
					'public_key' => 'better_payment_settings_payment_paystack_test_public',
					'secret_key' => 'better_payment_settings_payment_paystack_test_secret',
				),
			),
		);

		/**
		 * Filters the credentials `gateway_status` requires per gateway and mode.
		 *
		 * A gateway added here is reported too; its switch is read from
		 * `better_payment_settings_general_general_{gateway}` and its mode from
		 * `better_payment_settings_payment_{gateway}_live_mode`.
		 *
		 * @since 2.5.0
		 *
		 * @param array $keys Gateway => [ 'live' => [ field => key ], 'test' => [ field => key ] ].
		 */
		return (array) apply_filters( 'better_payment/mcp/gateway_credential_keys', $keys );
	}

	/**
	 * Per-gateway readiness report.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function gateway_status( $input = array() ) {
		unset( $input );

		$settings = DB::get_settings();
		$settings = is_array( $settings ) ? $settings : array();

		$read = function ( $key, $fallback = '' ) use ( $settings ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
		};

		$report = array();

		foreach ( self::gateway_credential_keys() as $gateway => $modes ) {
			$gateway   = sanitize_key( (string) $gateway );
			$live_mode = ( 'yes' === $read( 'better_payment_settings_payment_' . $gateway . '_live_mode', 'no' ) );
			$mode      = $live_mode ? 'live' : 'test';
			$required  = ( is_array( $modes ) && isset( $modes[ $mode ] ) && is_array( $modes[ $mode ] ) ) ? $modes[ $mode ] : array();
			$missing   = array();

			foreach ( $required as $field => $key ) {
				if ( ! Redactor::has_value( $read( (string) $key ) ) ) {
					$missing[] = (string) $field;
				}
			}

			$enabled         = ( 'yes' === $read( 'better_payment_settings_general_general_' . $gateway ) );
			$credentials_set = ( ! empty( $required ) && empty( $missing ) );

			$report[ $gateway ] = array(
				'enabled'         => $enabled,
				'live_mode'       => $live_mode,
				'credentials_set' => $credentials_set,
				'ready'           => ( $enabled && $credentials_set ),
				'missing'         => $missing,
			);
		}

		return array(
			'gateways' => $report,
			'currency' => (string) DB::get_settings( 'better_payment_settings_general_general_currency' ),
		);
	}

	/**
	 * Write allowlisted settings.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function update_settings( $input = array() ) {
		$input   = is_array( $input ) ? $input : array();
		$changes = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();

		if ( empty( $changes ) ) {
			return Result::invalid( __( 'No settings supplied.', 'better-payment' ) );
		}

		$writable = self::writable_keys();
		$refused  = array();

		foreach ( array_keys( $changes ) as $key ) {
			// Two independent tests. The allowlist says what is intentionally
			// writable; the secret check is a backstop in case the allowlist is
			// extended carelessly through its filter.
			if ( Redactor::is_secret( $key ) || ! in_array( $key, $writable, true ) ) {
				$refused[] = (string) $key;
			}
		}

		if ( ! empty( $refused ) ) {
			return Result::refused(
				sprintf(
					/* translators: %s: comma-separated settings keys. */
					__( 'These settings cannot be written through MCP: %s. Call settings_schema to see which keys are writable.', 'better-payment' ),
					implode( ', ', $refused )
				)
			);
		}

		// The currency must be one the site can be set to; anything else used to be
		// stored as typed and then read as USD everywhere.
		$currency_key = 'better_payment_settings_general_general_currency';
		$new_currency = null;

		if ( array_key_exists( $currency_key, $changes ) ) {
			$requested    = is_scalar( $changes[ $currency_key ] ) ? (string) $changes[ $currency_key ] : '';
			$new_currency = CampaignCurrency::sanitize( $requested );

			if ( '' === $new_currency ) {
				return Result::invalid(
					sprintf(
						/* translators: %s: requested currency code. */
						__( '"%s" is not a currency Better Payment offers. Use a three-letter code (for example USD, EUR, ZAR).', 'better-payment' ),
						$requested
					)
				);
			}

			$changes[ $currency_key ] = $new_currency;
		}

		$settings = DB::get_settings();
		$settings = is_array( $settings ) ? $settings : array();

		$old_currency = isset( $settings[ $currency_key ] ) && is_string( $settings[ $currency_key ] ) ? strtoupper( $settings[ $currency_key ] ) : '';
		$impact       = ( null !== $new_currency && $new_currency !== $old_currency )
			? CampaignTools::site_currency_impact( $new_currency )
			: null;

		// Read after every guard, so a preview refuses exactly what the real call
		// would refuse — an off-allowlist key above has already been rejected.
		if ( DryRun::requested( $input ) ) {
			$would = array();

			foreach ( $changes as $key => $value ) {
				$would[ (string) $key ] = array(
					'from' => isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : '',
					'to'   => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
				);
			}

			return DryRun::preview(
				sprintf(
					/* translators: %d: number of settings. */
					_n( '%d setting would be updated.', '%d settings would be updated.', count( $would ), 'better-payment' ),
					count( $would )
				),
				array_merge(
					array( 'settings' => $would ),
					null !== $impact ? array( 'campaigns' => $impact ) : array()
				)
			);
		}

		$written = array();

		foreach ( $changes as $key => $value ) {
			if ( is_array( $value ) ) {
				$settings[ $key ] = array_map( 'sanitize_text_field', $value );
			} elseif ( is_bool( $value ) ) {
				$settings[ $key ] = $value ? 'yes' : 'no';
			} else {
				$settings[ $key ] = sanitize_textarea_field( (string) $value );
			}

			$written[] = (string) $key;
		}

		DB::update_settings( $settings );

		$result = array( 'updated' => $written );

		if ( null !== $impact ) {
			$result['campaigns'] = $impact;
		}

		return $result;
	}
}
