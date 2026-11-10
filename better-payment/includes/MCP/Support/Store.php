<?php
/**
 * Storage availability checks for MCP tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Models\SubscriptionRelationModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guards reads against a missing custom table.
 *
 * `{prefix}better_payment` and `{prefix}better_payment_subscription_order` are created
 * by Installer on activation. They can be absent on a site whose activation was
 * interrupted, on a multisite subsite that was never activated, or in a partially
 * restored backup. Without this check every read answers with a $wpdb error string
 * instead of an empty result, and the client reports a broken tool rather than an
 * empty dataset.
 *
 * @since 2.5.0
 */
class Store {

	/**
	 * Per-request memo of table existence.
	 *
	 * @var array<string, bool>
	 */
	private static $checked = array();

	/**
	 * Whether a table exists.
	 *
	 * @param string $table Fully-qualified table name.
	 * @return bool
	 */
	public static function table_exists( $table ) {
		global $wpdb;

		$table = (string) $table;

		if ( '' === $table || ! isset( $wpdb ) ) {
			return false;
		}

		if ( isset( self::$checked[ $table ] ) ) {
			return self::$checked[ $table ];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- schema probe, memoized per request.
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		self::$checked[ $table ] = ( $found === $table );

		return self::$checked[ $table ];
	}

	/**
	 * Whether the transactions table is available.
	 *
	 * @return bool
	 */
	public static function has_transactions() {
		if ( ! class_exists( DB::class ) ) {
			return false;
		}

		return self::table_exists( DB::get_table_name() );
	}

	/**
	 * Whether the e-commerce subscription relation table is available.
	 *
	 * @return bool
	 */
	public static function has_subscriptions() {
		if ( ! class_exists( SubscriptionRelationModel::class ) ) {
			return false;
		}

		return self::table_exists( SubscriptionRelationModel::get_table_name() );
	}

	/**
	 * Drop the memo. Test helper.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$checked = array();
	}
}
