<?php
/**
 * E-commerce subscription tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;
use Better_Payment\Lite\MCP\Support\Store;
use Better_Payment\Lite\Models\SubscriptionRelationModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the `{prefix}better_payment_subscription_order` relation table.
 *
 * This table holds E-COMMERCE subscription-to-order relations only — the `source`
 * column names the integration that owns each row (`woo`, `fluentcart`, `surecart`).
 * Better Payment's own recurring payments are NOT here; they live in the transactions
 * table. Every description below says so, because a model given only the word
 * "subscriptions" will otherwise describe the wrong dataset with full confidence.
 *
 * All access goes through Models\SubscriptionRelationModel, never raw SQL.
 *
 * @since 2.5.0
 */
class Subscriptions implements ToolProvider {

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return array(
			'subscriptions_list'   => array(
				'title'       => __( 'List e-commerce subscriptions', 'better-payment' ),
				'description' => __( 'List e-commerce subscriptions Better Payment tracks (WooCommerce, FluentCart, SureCart), with the number of renewal orders recorded for each. This does not cover Better Payment recurring payments taken through its own forms — use transactions_list for those.', 'better-payment' ),
				'inputSchema' => Schema::object( Schema::pagination() ),
				'callback'    => array( self::class, 'list_subscriptions' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'subscription_orders'  => array(
				'title'       => __( 'Orders for a subscription', 'better-payment' ),
				'description' => __( 'List the order ids recorded against one e-commerce subscription, including whether each was the initial order or a renewal.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'subscription_id' => array(
							'type'        => 'integer',
							'description' => 'Subscription id in the source e-commerce system.',
							'minimum'     => 1,
						),
						'source'          => array(
							'type'        => 'string',
							'description' => 'Integration that owns the subscription. Omit to search every source.',
						),
					),
					array( 'subscription_id' )
				),
				'callback'    => array( self::class, 'orders' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'subscription_sources' => array(
				'title'       => __( 'List subscription sources', 'better-payment' ),
				'description' => __( 'List the e-commerce integrations Better Payment records subscription relations for, and the relation types it stores.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'sources' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),
		);
	}

	/**
	 * Paginated subscription relations.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function list_subscriptions( $input = array() ) {
		$input    = is_array( $input ) ? $input : array();
		$page     = Schema::clamp_page( isset( $input['page'] ) ? $input['page'] : 1 );
		$per_page = Schema::clamp_per_page( isset( $input['per_page'] ) ? $input['per_page'] : null );

		if ( ! Store::has_subscriptions() ) {
			return array(
				'subscriptions' => array(),
				'total'         => 0,
				'page'          => $page,
				'per_page'      => $per_page,
				'pages'         => 0,
			);
		}

		return Result::plain(
			SubscriptionRelationModel::get_subscriptions_paginated(
				array(
					'paged'    => $page,
					'per_page' => $per_page,
				)
			)
		);
	}

	/**
	 * Orders recorded against one subscription.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function orders( $input = array() ) {
		$input           = is_array( $input ) ? $input : array();
		$subscription_id = isset( $input['subscription_id'] ) ? (int) $input['subscription_id'] : 0;

		if ( $subscription_id < 1 ) {
			return Result::invalid( __( 'A subscription id is required.', 'better-payment' ) );
		}

		if ( ! Store::has_subscriptions() ) {
			return array(
				'subscription_id' => $subscription_id,
				'orders'          => array(),
			);
		}

		$source = isset( $input['source'] ) ? sanitize_key( (string) $input['source'] ) : '';

		return array(
			'subscription_id' => $subscription_id,
			'source'          => $source,
			'orders'          => Result::plain( SubscriptionRelationModel::get_orders( $subscription_id, $source ) ),
		);
	}

	/**
	 * Known integrations and relation types.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function sources( $input = array() ) {
		unset( $input );

		return array(
			'sources' => Result::plain( SubscriptionRelationModel::known_sources() ),
			'types'   => Result::plain( SubscriptionRelationModel::types() ),
		);
	}
}
