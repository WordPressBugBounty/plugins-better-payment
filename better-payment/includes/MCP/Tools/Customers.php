<?php
/**
 * Customer lookup tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;
use Better_Payment\Lite\MCP\Support\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Looks up one payer's history by e-mail address — the support-desk surface.
 *
 * This is the most privacy-sensitive family in the module: it returns one named
 * person's complete payment history to an external AI client. Three consequences,
 * all deliberate:
 *
 * 1. Both tools carry their OWN capability, so a support role can be granted the
 *    lookup without also being granted the site's options.
 * 2. The family carries its own sub-toggle, `better_payment/mcp/customer_lookup_enabled`,
 *    so a site can run every other tool with this one switched off.
 * 3. It requires an exact e-mail address. There is no "list all customers" tool and
 *    there should not be — bulk export of payer identities is not a question an
 *    assistant needs to be able to ask.
 *
 * @since 2.5.0
 */
class Customers implements ToolProvider {

	/**
	 * Capability the lookup requires.
	 *
	 * Not `manage_options`: a support user should be able to answer "what did this
	 * customer pay?" without also being able to rewrite the site's settings.
	 */
	const CAPABILITY = 'edit_others_posts';

	/**
	 * Whether the customer-lookup family is switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filters whether the MCP customer-lookup tools are available.
		 *
		 * @since 2.5.0
		 *
		 * @param bool $enabled Whether customer lookup is exposed over MCP.
		 */
		return (bool) apply_filters( 'better_payment/mcp/customer_lookup_enabled', true );
	}

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		if ( ! self::is_enabled() ) {
			return array();
		}

		$email = array(
			'type'        => 'string',
			'description' => 'Exact e-mail address of the payer.',
			'format'      => 'email',
		);

		return array(
			'customer_transactions' => array(
				'title'       => __( 'Customer payment history', 'better-payment' ),
				'description' => __( 'List the Better Payment transactions belonging to one payer, found by exact e-mail address. Returns that person\'s payment history including their submitted form fields.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array_merge(
						Schema::pagination(),
						array(
							'email' => $email,
							'type'  => array(
								'type'        => 'string',
								'description' => 'Narrow to recurring subscription payments, or list every transaction.',
								'enum'        => array( 'transactions', 'subscriptions' ),
								'default'     => 'transactions',
							),
						)
					),
					array( 'email' )
				),
				'capability'  => self::CAPABILITY,
				'callback'    => array( self::class, 'transactions' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'customer_analytics'    => array(
				'title'       => __( 'Customer payment totals', 'better-payment' ),
				'description' => __( 'Totals and counts for one payer, found by exact e-mail address: how much they have paid, how many payments completed, and how many are incomplete or refunded.', 'better-payment' ),
				'inputSchema' => Schema::object( array( 'email' => $email ), array( 'email' ) ),
				'capability'  => self::CAPABILITY,
				'callback'    => array( self::class, 'analytics' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),
		);
	}

	/**
	 * Validate and normalise the e-mail argument.
	 *
	 * @param mixed $input Tool arguments.
	 * @return string|\WP_Error
	 */
	private static function email( $input ) {
		$input = is_array( $input ) ? $input : array();
		$email = isset( $input['email'] ) ? sanitize_email( (string) $input['email'] ) : '';

		if ( '' === $email || ! is_email( $email ) ) {
			return Result::invalid( __( 'A valid e-mail address is required.', 'better-payment' ) );
		}

		return $email;
	}

	/**
	 * One payer's transactions.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function transactions( $input = array() ) {
		$email = self::email( $input );

		if ( is_wp_error( $email ) ) {
			return $email;
		}

		$input    = is_array( $input ) ? $input : array();
		$page     = Schema::clamp_page( isset( $input['page'] ) ? $input['page'] : 1 );
		$per_page = Schema::clamp_per_page( isset( $input['per_page'] ) ? $input['per_page'] : null );

		if ( ! Store::has_transactions() ) {
			return array(
				'email'        => $email,
				'transactions' => array(),
				'total'        => 0,
				'page'         => $page,
				'per_page'     => $per_page,
			);
		}

		$result = DB::get_user_transactions_paginated(
			$email,
			array(
				'page'     => $page,
				'per_page' => $per_page,
				'type'     => ( isset( $input['type'] ) && 'subscriptions' === $input['type'] ) ? 'subscriptions' : 'transactions',
			)
		);

		$result = Result::plain( $result );
		$result = is_array( $result ) ? $result : array();

		// A lookup by e-mail is already an explicit request for that person's
		// record, so their submitted fields are part of the answer.
		if ( isset( $result['transactions'] ) && is_array( $result['transactions'] ) ) {
			$result['transactions'] = Result::transactions( $result['transactions'], true );
		}

		$result['email'] = $email;

		return $result;
	}

	/**
	 * One payer's totals.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function analytics( $input = array() ) {
		$email = self::email( $input );

		if ( is_wp_error( $email ) ) {
			return $email;
		}

		if ( ! Store::has_transactions() ) {
			return array(
				'email'     => $email,
				'analytics' => array(),
			);
		}

		return array(
			'email'     => $email,
			'analytics' => Result::plain( DB::get_user_analytics_by_email( $email ) ),
			'currency'  => (string) DB::get_settings( 'better_payment_settings_general_general_currency' ),
		);
	}
}
