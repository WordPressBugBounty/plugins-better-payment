<?php
/**
 * Discovery tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\ActivityLog;
use Better_Payment\Lite\MCP\Support\Schema;
use Better_Payment\Lite\MCP\Support\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports what this particular installation can actually do.
 *
 * Without it a client has a list of tool names and no way to know which of them
 * describe features this site has. It calls a Pro analytics tool on a free install,
 * gets a refusal, and reports a broken integration. One cheap call up front replaces
 * that with an accurate picture.
 *
 * @since 2.5.0
 */
class Site implements ToolProvider {

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return array(
			'site_info'    => array(
				'title'       => __( 'Describe this Better Payment site', 'better-payment' ),
				'description' => __( 'Report what this Better Payment installation offers: plugin version, whether Better Payment Pro is active, which gateways are enabled, which modules are on, and the site currency. Call this first to decide which other Better Payment tools apply.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'info' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'mcp_activity' => array(
				'title'       => __( 'Recent MCP tool calls', 'better-payment' ),
				'description' => __( 'Report which Better Payment tools have been called on this site recently, who called them, and how each call ended. Tool arguments are never recorded, so this reports activity, not payload.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'limit' => array(
							'type'        => 'integer',
							'description' => 'How many recent calls to return, newest first.',
							'minimum'     => 1,
							'maximum'     => ActivityLog::MAX_LIMIT,
							'default'     => 50,
						),
					)
				),
				'callback'    => array( self::class, 'activity' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),
		);
	}

	/**
	 * Build the capability report.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function info( $input = array() ) {
		unset( $input );

		$settings = DB::get_settings();
		$settings = is_array( $settings ) ? $settings : array();

		$enabled = static function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		return array(
			'plugin'     => array(
				'name'    => 'Better Payment',
				'version' => defined( 'BETTER_PAYMENT_VERSION' ) ? BETTER_PAYMENT_VERSION : '',
			),
			'site'       => array(
				'name' => (string) get_bloginfo( 'name' ),
				'url'  => (string) home_url(),
			),
			// Read live, per request, from the master signal. Nothing about
			// entitlement is ever inferred from stored data.
			'pro_active' => (bool) apply_filters( 'better_payment/pro_enabled', false ),
			'gateways'   => array(
				'stripe'   => $enabled( 'better_payment_settings_general_general_stripe' ),
				'paypal'   => $enabled( 'better_payment_settings_general_general_paypal' ),
				'paystack' => $enabled( 'better_payment_settings_general_general_paystack' ),
			),
			'modules'    => array(
				'fundraising_campaign' => $enabled( 'better_payment_settings_general_general_fundraising_campaign' ),
				'user_dashboard'       => $enabled( 'better_payment_settings_general_general_user_dashboard' ),
				'email'                => $enabled( 'better_payment_settings_general_general_email' ),
				'ai_assistant'         => $enabled( 'better_payment_settings_general_ai_enabled' ),
				'woocommerce'          => class_exists( 'WooCommerce' ),
			),
			'storage'    => array(
				'transactions_table'  => Store::has_transactions(),
				'subscriptions_table' => Store::has_subscriptions(),
			),
			'currency'   => isset( $settings['better_payment_settings_general_general_currency'] )
				? (string) $settings['better_payment_settings_general_general_currency']
				: '',
		);
	}

	/**
	 * Report recent tool calls.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function activity( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$limit = isset( $input['limit'] ) && is_numeric( $input['limit'] ) ? (int) $input['limit'] : 50;

		return array(
			'summary' => ActivityLog::summary(),
			'calls'   => ActivityLog::recent( $limit ),
		);
	}
}
