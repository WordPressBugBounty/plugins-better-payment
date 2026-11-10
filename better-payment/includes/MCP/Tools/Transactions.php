<?php
/**
 * Transaction tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Export;
use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\DryRun;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;
use Better_Payment\Lite\MCP\Support\Store;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and safe writes over the `{prefix}better_payment` transactions table.
 *
 * Every call goes through `Admin\DB`, never through raw SQL and never through the
 * plugin's own REST routes: those carry a `wp_rest` nonce check that an MCP client
 * can never satisfy.
 *
 * Deliberately absent: anything that CREATES a transaction. What a request may charge
 * is decided server-side by `Classes\PaymentRequestGuard`; a tool that wrote
 * transaction rows would route around that guard.
 *
 * @since 2.5.0
 */
class Transactions implements ToolProvider {

	/**
	 * Hard ceiling on an exported file, in rows.
	 *
	 * The admin download is unbounded because it streams straight to the browser and
	 * the request dies with it. A tool's CSV is assembled in memory, encoded into
	 * JSON and carried back through an HTTP response, so the same table that
	 * downloads fine would exhaust memory here.
	 */
	const MAX_EXPORT_ROWS = 5000;

	/**
	 * Default export size.
	 */
	const DEFAULT_EXPORT_ROWS = 500;

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		$id = array(
			'type'        => 'integer',
			'description' => 'Transaction id.',
			'minimum'     => 1,
		);

		return array(
			'transactions_list'         => array(
				'title'       => __( 'List transactions', 'better-payment' ),
				'description' => __( 'List Better Payment transactions, newest first. Filter by status bucket, gateway, currency, date range or free text. Personal data (customer name, email and submitted form fields) is withheld unless include_customer_data is true.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array_merge(
						Schema::pagination(),
						array(
							'status'                => Schema::transaction_status(),
							'gateway'               => Schema::gateway(),
							'currency'              => array(
								'type'        => 'string',
								'description' => 'Three-letter currency code, or "all".',
							),
							'search'                => array(
								'type'        => 'string',
								'description' => 'Free-text search across the transaction record.',
							),
							'date_from'             => Schema::date( 'Earliest payment date to include (YYYY-MM-DD).' ),
							'date_to'               => Schema::date( 'Latest payment date to include (YYYY-MM-DD).' ),
							'order_by'              => array(
								'type'        => 'string',
								'description' => 'Sort column.',
								'enum'        => array( 'id', 'payment_date', 'email', 'amount' ),
								'default'     => 'payment_date',
							),
							'order'                 => array(
								'type'        => 'string',
								'description' => 'Sort direction.',
								'enum'        => array( 'ASC', 'DESC' ),
								'default'     => 'DESC',
							),
							'include_customer_data' => Schema::customer_opt_in(),
						)
					)
				),
				'callback'    => array( self::class, 'list_transactions' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'transaction_get'           => array(
				'title'       => __( 'Get a transaction', 'better-payment' ),
				'description' => __( 'Fetch one Better Payment transaction by its numeric id, including the payer details and submitted form fields.', 'better-payment' ),
				'inputSchema' => Schema::object( array( 'id' => $id ), array( 'id' ) ),
				'callback'    => array( self::class, 'get_transaction' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'transactions_count'        => array(
				'title'       => __( 'Count transactions', 'better-payment' ),
				'description' => __( 'Count Better Payment transactions matching a status bucket, gateway and date range. Cheaper than listing when only the total is needed.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'status'    => Schema::transaction_status(),
						'gateway'   => Schema::gateway(),
						'date_from' => Schema::date( 'Earliest payment date to include (YYYY-MM-DD).' ),
						'date_to'   => Schema::date( 'Latest payment date to include (YYYY-MM-DD).' ),
					)
				),
				'callback'    => array( self::class, 'count_transactions' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'transactions_analytics'    => array(
				'title'       => __( 'Transaction totals', 'better-payment' ),
				'description' => __( 'Totals and counts for Better Payment transactions, split into completed, incomplete and refunded, over an optional date range.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'date_from' => Schema::date( 'Earliest payment date to include (YYYY-MM-DD).' ),
						'date_to'   => Schema::date( 'Latest payment date to include (YYYY-MM-DD).' ),
						'gateway'   => Schema::gateway(),
					)
				),
				'callback'    => array( self::class, 'analytics' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'transactions_export'       => array(
				'title'       => __( 'Export transactions as CSV', 'better-payment' ),
				'description' => __( 'Build the Better Payment transactions CSV — the same file the admin Export button downloads — and return it as text. Personal data (payer name, email and submitted form fields) is withheld unless include_customer_data is true. Bounded by max_rows; the result says whether it was truncated.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'status'                => Schema::transaction_status(),
						'gateway'               => Schema::gateway(),
						'currency'              => array(
							'type'        => 'string',
							'description' => 'Three-letter currency code, or "all".',
						),
						'search'                => array(
							'type'        => 'string',
							'description' => 'Free-text search across the transaction record.',
						),
						'date_from'             => Schema::date( 'Earliest payment date to include (YYYY-MM-DD).' ),
						'date_to'               => Schema::date( 'Latest payment date to include (YYYY-MM-DD).' ),
						'max_rows'              => array(
							'type'        => 'integer',
							'description' => 'Maximum rows to place in the file.',
							'minimum'     => 1,
							'maximum'     => self::MAX_EXPORT_ROWS,
							'default'     => self::DEFAULT_EXPORT_ROWS,
						),
						'include_customer_data' => Schema::customer_opt_in(),
					)
				),
				'callback'    => array( self::class, 'export_transactions' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'transaction_mark_complete' => array(
				'title'       => __( 'Mark a transaction complete', 'better-payment' ),
				'description' => __( 'Mark one Better Payment transaction as completed. Use only for payments confirmed outside the gateway; it records a status, it does not capture money.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'id'      => $id,
						'dry_run' => DryRun::property(),
					),
					array( 'id' )
				),
				'callback'    => array( self::class, 'mark_complete' ),
				'annotations' => array(
					'idempotentHint' => true,
				),
			),

			'transaction_delete'        => array(
				'title'       => __( 'Delete a transaction', 'better-payment' ),
				'description' => __( 'Permanently delete one Better Payment transaction record. This removes the payment history row only; it does not refund the payer. Cannot be undone.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'id'      => $id,
						'dry_run' => DryRun::property(),
					),
					array( 'id' )
				),
				'callback'    => array( self::class, 'delete_transaction' ),
				'annotations' => array(
					'destructiveHint' => true,
				),
			),
		);
	}

	/**
	 * Build the `Admin\DB::get_transactions()` filter array from tool arguments.
	 *
	 * @param array $input       Tool arguments.
	 * @param bool  $with_paging Whether to add paging keys.
	 * @return array
	 */
	private static function filters( array $input, $with_paging = true ) {
		$filters = array();

		if ( $with_paging ) {
			$page     = Schema::clamp_page( isset( $input['page'] ) ? $input['page'] : 1 );
			$per_page = Schema::clamp_per_page( isset( $input['per_page'] ) ? $input['per_page'] : null );

			$filters['paged']    = $page;
			$filters['per_page'] = $per_page;
			$filters['offset']   = ( $page - 1 ) * $per_page;
		}

		if ( ! empty( $input['status'] ) && 'all' !== $input['status'] ) {
			$filters['status'] = sanitize_text_field( (string) $input['status'] );
		}

		if ( ! empty( $input['gateway'] ) ) {
			$filters['source'] = sanitize_text_field( (string) $input['gateway'] );
		}

		if ( ! empty( $input['currency'] ) ) {
			$filters['currency'] = sanitize_text_field( (string) $input['currency'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$filters['search_text'] = sanitize_text_field( (string) $input['search'] );
		}

		// DB::get_transactions() applies a range only when it has both ends, and
		// ignores a lone bound without a word. Each bound is its own argument here, so
		// "since the 1st" must not quietly mean "ever": the missing end is left open.
		if ( ! empty( $input['date_from'] ) || ! empty( $input['date_to'] ) ) {
			$filters['payment_date_from'] = ! empty( $input['date_from'] ) ? sanitize_text_field( (string) $input['date_from'] ) : '1970-01-01';
			$filters['payment_date_to']   = ! empty( $input['date_to'] ) ? sanitize_text_field( (string) $input['date_to'] ) : '9999-12-31';
		}

		if ( ! empty( $input['order_by'] ) ) {
			$filters['order_by'] = sanitize_text_field( (string) $input['order_by'] );
		}

		if ( ! empty( $input['order'] ) ) {
			$filters['order'] = 'ASC' === strtoupper( (string) $input['order'] ) ? 'ASC' : 'DESC';
		}

		return $filters;
	}

	/**
	 * Total matching rows for one argument set.
	 *
	 * @param array $input Tool arguments.
	 * @return int
	 */
	private static function total_for( array $input ) {
		$status = ! empty( $input['status'] ) ? (string) $input['status'] : 'all';

		return (int) DB::get_transaction_count(
			self::filters( $input, false ),
			'v2',
			0,
			'all' === $status ? '' : $status
		);
	}

	/**
	 * List transactions.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function list_transactions( $input = array() ) {
		$input = is_array( $input ) ? $input : array();

		$page     = Schema::clamp_page( isset( $input['page'] ) ? $input['page'] : 1 );
		$per_page = Schema::clamp_per_page( isset( $input['per_page'] ) ? $input['per_page'] : null );

		// A missing table is an empty dataset, not an error: a half-activated site
		// should answer "no transactions", not surface a $wpdb failure to the client.
		if ( ! Store::has_transactions() ) {
			return array(
				'transactions' => array(),
				'total'        => 0,
				'page'         => $page,
				'per_page'     => $per_page,
				'pages'        => 0,
			);
		}

		$rows  = DB::get_transactions( self::filters( $input ), 0, 'v2' );
		$total = self::total_for( $input );

		return array(
			'transactions' => Result::transactions( $rows, ! empty( $input['include_customer_data'] ) ),
			'total'        => $total,
			'page'         => $page,
			'per_page'     => $per_page,
			'pages'        => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
		);
	}

	/**
	 * Fetch one transaction.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function get_transaction( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$id    = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $id < 1 ) {
			return Result::invalid( __( 'A transaction id is required.', 'better-payment' ) );
		}

		if ( ! Store::has_transactions() ) {
			return Result::not_found( __( 'No transaction records exist on this site.', 'better-payment' ) );
		}

		$row = DB::get_transaction( $id );

		if ( empty( $row ) ) {
			return Result::not_found( __( 'Transaction not found.', 'better-payment' ) );
		}

		// A single-record lookup is an explicit request for that record, so the
		// payer details are part of the answer rather than an opt-in.
		return array( 'transaction' => Result::transaction( $row, true ) );
	}

	/**
	 * Count transactions.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function count_transactions( $input = array() ) {
		$input = is_array( $input ) ? $input : array();

		if ( ! Store::has_transactions() ) {
			return array( 'total' => 0 );
		}

		return array( 'total' => self::total_for( $input ) );
	}

	/**
	 * Totals per status bucket.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function analytics( $input = array() ) {
		$input = is_array( $input ) ? $input : array();

		if ( ! Store::has_transactions() ) {
			return array(
				'analytics' => array(),
				'currency'  => '',
			);
		}

		// DB::get_transactions_analytics_dashboard() returns all zeros unless it is
		// handed the rows to aggregate — it takes a filtered set rather than running
		// its own query. Fetch every matching row first: without a per_page of its
		// own, DB::get_transactions() returns its default page of 20 and the totals
		// describe those 20 rows only.
		$filters             = self::filters( $input, false );
		$filters['per_page'] = max( 1, self::total_for( $input ) );

		$rows = DB::get_transactions( $filters, 0, 'v2' );
		$rows = is_array( $rows ) ? $rows : array();

		return array(
			'analytics' => Result::plain( DB::get_transactions_analytics_dashboard( $rows ) ),
			'matched'   => count( $rows ),
			'currency'  => (string) DB::get_settings( 'better_payment_settings_general_general_currency' ),
		);
	}

	/**
	 * Mark a transaction completed.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function mark_complete( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$id    = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $id < 1 ) {
			return Result::invalid( __( 'A transaction id is required.', 'better-payment' ) );
		}

		if ( ! Store::has_transactions() ) {
			return Result::not_found( __( 'No transaction records exist on this site.', 'better-payment' ) );
		}

		$existing = DB::get_transaction( $id );

		if ( empty( $existing ) ) {
			return Result::not_found( __( 'Transaction not found.', 'better-payment' ) );
		}

		$status = isset( $existing->status ) ? (string) $existing->status : '';

		// The same lines the admin screen's mark-complete route draws
		// (AdminAPI::mark_transaction_completed()). A refunded or failed payment
		// marked Completed is counted as money received, and a pre-2.4.0 free
		// checkout stores an amount that was never charged.
		if ( Helper::LEGACY_FREE_CHECKOUT_STATUS === $status ) {
			return Result::refused( __( 'This free checkout cannot be marked as completed: its stored amount was never charged.', 'better-payment' ) );
		}

		if ( in_array( strtolower( $status ), array( 'refunded', 'failed' ), true ) ) {
			return Result::refused(
				/* translators: %s: transaction status. */
				sprintf( __( 'A %s transaction cannot be marked as completed.', 'better-payment' ), strtolower( $status ) )
			);
		}

		// Already received: an idempotent success, with nothing to write.
		if ( in_array( strtolower( $status ), array( 'completed', 'paid', 'success' ), true ) ) {
			return array(
				'updated'     => false,
				'transaction' => Result::transaction( $existing, true ),
			);
		}

		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				/* translators: %d: transaction id. */
				sprintf( __( 'Transaction %d would be marked completed.', 'better-payment' ), $id ),
				array(
					'transaction' => $id,
					'status'      => array(
						'from' => isset( $existing->status ) ? (string) $existing->status : '',
						'to'   => 'completed',
					),
				)
			);
		}

		DB::mark_as_completed( $id );

		// Re-read rather than trusting the affected-row count: $wpdb::update()
		// returns 0 both when nothing matched and when the row already held the
		// target status, and the second case is a success for an idempotent call.
		return array(
			'updated'     => true,
			'transaction' => Result::transaction( DB::get_transaction( $id ), true ),
		);
	}

	/**
	 * Delete a transaction.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function delete_transaction( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$id    = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $id < 1 ) {
			return Result::invalid( __( 'A transaction id is required.', 'better-payment' ) );
		}

		if ( ! Store::has_transactions() ) {
			return Result::not_found( __( 'No transaction records exist on this site.', 'better-payment' ) );
		}

		if ( empty( DB::get_transaction( $id ) ) ) {
			return Result::not_found( __( 'Transaction not found.', 'better-payment' ) );
		}

		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				/* translators: %d: transaction id. */
				sprintf( __( 'Transaction %d would be permanently deleted.', 'better-payment' ), $id ),
				array( 'delete_transaction' => $id )
			);
		}

		$deleted = DB::delete_transaction( $id );

		return array(
			'deleted' => ( false !== $deleted && $deleted > 0 ),
			'id'      => $id,
		);
	}

	/**
	 * Build the transactions CSV.
	 *
	 * Reuses `Classes\Export`, which is where the column order, the status labels and
	 * the flattened form fields are decided. A second formatter here would drift from
	 * the admin download the first time a column changed, and then the two exports of
	 * the same table would disagree.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function export_transactions( $input = array() ) {
		$input = is_array( $input ) ? $input : array();

		$max = isset( $input['max_rows'] ) && is_numeric( $input['max_rows'] )
			? (int) $input['max_rows']
			: self::DEFAULT_EXPORT_ROWS;

		if ( $max < 1 ) {
			$max = self::DEFAULT_EXPORT_ROWS;
		}

		if ( $max > self::MAX_EXPORT_ROWS ) {
			$max = self::MAX_EXPORT_ROWS;
		}

		$include_customer = ! empty( $input['include_customer_data'] );

		if ( ! Store::has_transactions() ) {
			return array(
				'filename'               => 'better-payment-transactions-' . gmdate( 'Y-m-d' ) . '.csv',
				'csv'                    => '',
				'rows'                   => 0,
				'total_matched'          => 0,
				'truncated'              => false,
				'customer_data_included' => $include_customer,
			);
		}

		$filters             = self::filters( $input, false );
		$filters['per_page'] = $max;

		$total  = self::total_for( $input );
		$export = ( new Export() )->build_export( $filters, true, ';', $include_customer );

		return array(
			'filename'               => (string) $export['filename'],
			'csv'                    => (string) $export['csv'],
			'rows'                   => (int) $export['rows'],
			'total_matched'          => $total,
			// Reported rather than silently capped: a client that asked for a full
			// export must be able to tell that it did not get one.
			'truncated'              => $total > (int) $export['rows'],
			'customer_data_included' => $include_customer,
		);
	}
}
