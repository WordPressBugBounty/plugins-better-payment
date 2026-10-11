<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-time fix for WooCommerce transaction rows stored in site time.
 *
 * `payment_date` is UTC: every form, block, Elementor and Paystack path writes
 * it with `gmdate()` / `date()` (WordPress runs PHP in UTC), and every PHP
 * reader converts it with `wp_date( …, strtotime( payment_date ) )`. The
 * WooCommerce module (checkout rows and subscription renewal rows, both
 * `referer = 'woocommerce'`) wrote `current_time( 'mysql' )` instead — site
 * time. On a site east of UTC those rows sorted above newer payments in the
 * admin list, and showed the wrong time in receipts, the user dashboard and
 * the CSV export.
 *
 * The writers are fixed; this converts the rows they already wrote, once.
 *
 * - **Not tied to a version change.** Runs on the first `wp_loaded` that finds
 *   no flag, so a site already on the release that carries it is fixed too.
 * - **Claimed atomically.** The flag is created with `INSERT IGNORE`; only the
 *   request whose insert lands converts. Two concurrent requests can never both
 *   shift a row.
 * - **Bounded by id.** The highest row id is read *before* the claim, and only
 *   rows at or below it are converted, so a row written by the fixed code after
 *   the claim — already UTC — is never shifted.
 * - **Per row, with DST.** `get_gmt_from_date()` converts each value with the
 *   site timezone as it was on that date. A site that changed its timezone since
 *   the row was written cannot be recovered exactly; it is converted with the
 *   current one.
 *
 * @since 2.4.1
 */
class PaymentDateMigration {

	/**
	 * Flag option. Its value is the highest row id the conversion covered.
	 */
	const OPTION = 'better_payment_woo_payment_date_utc';

	/**
	 * Rows read per batch.
	 */
	const BATCH = 500;

	/**
	 * Convert the rows, unless that already happened.
	 *
	 * @return int Rows converted (0 when already done or nothing to do).
	 */
	public static function maybe_run() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return 0;
		}

		global $wpdb;

		$table = "{$wpdb->prefix}better_payment";

		// No table yet (activation has not run): nothing to fix, and nothing written
		// before the fix can appear later. Claim so the check is not repeated.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		$max_id       = $table_exists ? (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table}" ) : 0;

		if ( ! self::claim( $max_id ) ) {
			return 0;
		}

		if ( $max_id <= 0 ) {
			return 0;
		}

		$converted = 0;
		$after_id  = 0;

		do {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, payment_date FROM {$table} WHERE referer = %s AND id > %d AND id <= %d ORDER BY id ASC LIMIT %d",
					'woocommerce',
					$after_id,
					$max_id,
					self::BATCH
				)
			);

			foreach ( (array) $rows as $row ) {
				$after_id = (int) $row->id;
				$utc      = self::to_utc( $row->payment_date );

				if ( null === $utc || $utc === $row->payment_date ) {
					continue;
				}

				$updated = $wpdb->update(
					$table,
					array( 'payment_date' => $utc ),
					array( 'id' => (int) $row->id ),
					array( '%s' ),
					array( '%d' )
				);

				if ( $updated ) {
					++$converted;
				}
			}
		} while ( is_array( $rows ) && count( $rows ) === self::BATCH );

		return $converted;
	}

	/**
	 * A site-time `Y-m-d H:i:s` as UTC, or null when it is not a usable date.
	 *
	 * @param mixed $site_time Stored value.
	 * @return string|null
	 */
	public static function to_utc( $site_time ) {
		$site_time = is_string( $site_time ) ? trim( $site_time ) : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $site_time ) || 0 === strpos( $site_time, '0000-' ) ) {
			return null;
		}

		$utc = get_gmt_from_date( $site_time, 'Y-m-d H:i:s' );

		return '' !== $utc ? $utc : null;
	}

	/**
	 * Create the flag, atomically.
	 *
	 * `add_option()` is not a claim: it writes with `ON DUPLICATE KEY UPDATE`,
	 * so a second request with a different value succeeds too.
	 *
	 * @param int $max_id Highest row id covered.
	 * @return bool True for the one request that created it.
	 */
	private static function claim( $max_id ) {
		global $wpdb;

		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
				self::OPTION,
				(string) $max_id,
				'yes'
			)
		);

		// Keep the options cache in line with the row just written (or lost).
		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( self::OPTION, 'options' );

		return 1 === (int) $inserted;
	}
}
