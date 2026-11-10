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
 * The campaign a transaction row was given to, as the admin screens show it.
 *
 * A row's `campaign_id` column holds one of two things:
 *
 *   - a Campaign Builder campaign: the `bp_campaign` post id (`"42"`);
 *   - an Elementor Fundraising Campaign widget: `bp_campaign_{page id}_{postfix}`.
 *
 * The Transactions screen used to print that raw value ("Campaign ID: 42") and had no
 * way to list one campaign's transactions. describe() turns it into a title and the
 * links an admin needs, sanitize_filter() validates it as a filter value, and options()
 * lists every campaign that has at least one transaction, for the filter dropdown.
 *
 * @since 2.5.0
 */
class TransactionCampaign {

	/**
	 * describe() results for this request, keyed by campaign id.
	 *
	 * @var array<string, array|null>
	 */
	private static $cache = array();

	/**
	 * A `campaign_id` filter value, or '' when it is not one a row could hold.
	 *
	 * @param mixed $value Raw request value.
	 * @return string
	 */
	public static function sanitize_filter( $value ): string {
		$value = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';

		if ( '' === $value || '0' === $value ) {
			return '';
		}

		if ( ctype_digit( $value ) || preg_match( '/^bp_campaign_\d+_[\w-]+$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * What the admin screens show for a row's campaign.
	 *
	 * @param mixed $campaign_id The row's `campaign_id`.
	 * @return array|null Null when the row has no campaign. Otherwise {
	 *     @type string $id       The stored value.
	 *     @type string $type     'builder' (a `bp_campaign` post) or 'widget' (Elementor Fundraising Campaign).
	 *     @type string $title    Human-readable name; never empty.
	 *     @type string $edit_url Where an admin edits it, '' when it no longer exists.
	 *     @type string $view_url Its public page, '' when it has none or no longer exists.
	 *     @type bool   $exists   False for a deleted campaign (or the page a widget lived on).
	 * }
	 */
	public static function describe( $campaign_id ): ?array {
		$campaign_id = self::sanitize_filter( $campaign_id );

		if ( '' === $campaign_id ) {
			return null;
		}

		if ( array_key_exists( $campaign_id, self::$cache ) ) {
			return self::$cache[ $campaign_id ];
		}

		$described = ctype_digit( $campaign_id )
			? self::describe_builder( $campaign_id )
			: self::describe_widget( $campaign_id );

		self::$cache[ $campaign_id ] = $described;

		return $described;
	}

	/**
	 * Every campaign with at least one transaction, for the Transactions filter.
	 *
	 * @return array[] describe() results, sorted by title.
	 */
	public static function options(): array {
		global $wpdb;

		$table = DB::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table (no WP API); table name from $wpdb->prefix; uncached on purpose (live payment data).
		$ids = $wpdb->get_col( "SELECT DISTINCT campaign_id FROM `{$table}` WHERE campaign_id IS NOT NULL AND campaign_id <> '' AND campaign_id <> '0'" );

		$options = array();
		foreach ( (array) $ids as $id ) {
			$described = self::describe( $id );
			if ( null !== $described ) {
				$options[] = $described;
			}
		}

		usort(
			$options,
			function ( $a, $b ) {
				return strnatcasecmp( $a['title'], $b['title'] );
			}
		);

		return $options;
	}

	/**
	 * Forget describe() results (tests, or after a campaign is renamed mid-request).
	 *
	 * @return void
	 */
	public static function reset_cache(): void {
		self::$cache = array();
	}

	/**
	 * A Campaign Builder campaign.
	 *
	 * @param string $campaign_id Post id.
	 * @return array
	 */
	private static function describe_builder( string $campaign_id ): array {
		$post = get_post( (int) $campaign_id );

		if ( ! $post || 'bp_campaign' !== $post->post_type ) {
			return array(
				'id'       => $campaign_id,
				'type'     => 'builder',
				/* translators: %s: campaign id. */
				'title'    => sprintf( __( 'Deleted campaign #%s', 'better-payment' ), $campaign_id ),
				'edit_url' => '',
				'view_url' => '',
				'exists'   => false,
			);
		}

		$title = trim( wp_strip_all_tags( get_the_title( $post ) ) );

		return array(
			'id'       => $campaign_id,
			'type'     => 'builder',
			/* translators: %s: campaign id. */
			'title'    => '' !== $title ? $title : sprintf( __( 'Untitled campaign #%s', 'better-payment' ), $campaign_id ),
			'edit_url' => admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . (int) $campaign_id ),
			'view_url' => 'publish' === $post->post_status ? (string) get_permalink( $post ) : '',
			'exists'   => true,
		);
	}

	/**
	 * An Elementor Fundraising Campaign widget: named after the page it lives on.
	 *
	 * @param string $campaign_id `bp_campaign_{page id}_{postfix}`.
	 * @return array
	 */
	private static function describe_widget( string $campaign_id ): array {
		preg_match( '/^bp_campaign_(\d+)_/', $campaign_id, $matches );
		$page_id = isset( $matches[1] ) ? (int) $matches[1] : 0;
		$page    = $page_id ? get_post( $page_id ) : null;

		if ( ! $page ) {
			return array(
				'id'       => $campaign_id,
				'type'     => 'widget',
				/* translators: %s: stored campaign id. */
				'title'    => sprintf( __( 'Fundraising campaign %s (page deleted)', 'better-payment' ), $campaign_id ),
				'edit_url' => '',
				'view_url' => '',
				'exists'   => false,
			);
		}

		$page_title = trim( wp_strip_all_tags( get_the_title( $page ) ) );

		return array(
			'id'       => $campaign_id,
			'type'     => 'widget',
			'title'    => sprintf(
				/* translators: %s: title of the page the Fundraising Campaign widget is on. */
				__( 'Fundraising campaign on “%s”', 'better-payment' ),
				'' !== $page_title ? $page_title : '#' . $page_id
			),
			'edit_url' => (string) get_edit_post_link( $page_id, 'raw' ),
			'view_url' => 'publish' === $page->post_status ? (string) get_permalink( $page ) : '',
			'exists'   => true,
		);
	}
}
