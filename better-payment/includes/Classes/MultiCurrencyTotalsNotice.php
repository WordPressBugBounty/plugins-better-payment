<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tells owners whose payments span several currencies that totals now keep each currency apart.
 *
 * Before 2.5.1 the User Dashboard summary, the Fundraising Campaign widget's progress and the
 * analytics totals added every transaction's amount together whatever its currency, so 20 USD
 * and 15 ZAR read as "$35.00". Classes\CurrencyTotals now keeps one total per currency. On a site
 * with a single currency nothing changes; on a site with several, a figure the owner knew drops
 * to its true size on update, with no explanation on screen. This notice is that explanation.
 *
 * The check runs once per site (not per version, so a site already on the release still runs it)
 * and the notice shows only when counted transactions hold more than one currency. It is
 * informational, shown with or without Pro, until an administrator dismisses it.
 *
 * @since 2.5.1
 */
class MultiCurrencyTotalsNotice {

	/**
	 * `[ 'status' => show|dismissed|none, 'currencies' => string[] ]`. Missing until the check runs.
	 */
	const OPTION = 'better_payment_multi_currency_totals_notice';

	const DISMISS_ACTION = 'better_payment_dismiss_multi_currency_totals';

	/** WordPress screens that show the notice (Better Payment's own page shows it on every tab). */
	const SCREENS = array( 'dashboard', 'dashboard-network', 'plugins', 'plugins-network' );

	const ADMIN_PAGE = 'better-payment-admin';

	/** At most this many codes are named; the rest are counted. */
	const MAX_LISTED = 6;

	/**
	 * Record, once per site, whether payments span several currencies.
	 *
	 * @return bool True when this run turned the notice on.
	 */
	public static function maybe_flag(): bool {
		if ( false !== get_option( self::OPTION, false ) ) {
			return false;
		}

		$currencies = self::counted_currencies();
		$show       = count( $currencies ) > 1;

		update_option(
			self::OPTION,
			array(
				'status'     => $show ? 'show' : 'none',
				'currencies' => $currencies,
			),
			false
		);

		return $show;
	}

	/**
	 * The currencies counted transactions are in, upper case, largest count first. A blank
	 * currency is the site currency (rows stored before the column was filled).
	 *
	 * @return string[]
	 */
	public static function counted_currencies(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'better_payment';
		$site  = CurrencyTotals::site();

		// Helper::COUNTED_ROW_SQL is a constant fragment with no `%`; the site code is a placeholder.
		// The statement spans several lines, so the codes are switched off for the whole of it.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table (name from $wpdb->prefix), constant SQL fragment, runs once per site.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT code FROM (
					SELECT CASE WHEN TRIM(COALESCE(currency, '')) = '' THEN %s ELSE UPPER(TRIM(currency)) END AS code
					FROM $table WHERE " . Helper::COUNTED_ROW_SQL . "
				) AS codes GROUP BY code ORDER BY COUNT(*) DESC, code ASC",
				$site
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return array_values( array_filter( array_map( 'strval', is_array( $rows ) ? $rows : array() ) ) );
	}

	/**
	 * Hook the notice and its dismiss action. Admin requests only.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
		add_action( 'better_payment_admin_notices', array( __CLASS__, 'render' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * Whether a screen shows the notice.
	 *
	 * @param string $screen_id Current screen id.
	 * @param string $page      The `page` query arg.
	 * @return bool
	 */
	public static function shows_on( string $screen_id, string $page ): bool {
		return in_array( $screen_id, self::SCREENS, true ) || self::ADMIN_PAGE === $page;
	}

	/**
	 * The currencies the notice names, when it is due; [] otherwise.
	 *
	 * @return string[]
	 */
	public static function pending_currencies(): array {
		$stored = get_option( self::OPTION );

		if ( ! is_array( $stored ) || 'show' !== ( $stored['status'] ?? '' ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'strval', (array) ( $stored['currencies'] ?? array() ) ) ) );
	}

	/**
	 * The currency list as printed: up to MAX_LISTED codes, then "and N more".
	 *
	 * @param string[] $currencies Codes.
	 * @return string
	 */
	public static function describe_currencies( array $currencies ): string {
		$listed = array_slice( $currencies, 0, self::MAX_LISTED );
		$rest   = count( $currencies ) - count( $listed );
		$text   = implode( ', ', $listed );

		if ( $rest > 0 ) {
			// The singular and plural strings are the same on purpose. English reads correctly both
			// ways ("and 1 more", "and 2 more"), and a translation still gets every plural form of
			// its language. The singular keeps %2$d: in many languages the "singular" form is also
			// used for 21, 31, …, so a hard-coded 1 would print the wrong number
			// (WordPress.WP.I18n.MissingSingularPlaceholder).
			/* translators: 1: comma-separated currency codes, e.g. "USD, EUR, GBP". 2: the number of currencies not listed; always printed from this placeholder, never written out as a word or a fixed digit. */
			$text = sprintf( _n( '%1$s and %2$d more', '%1$s and %2$d more', $rest, 'better-payment' ), $text, $rest );
		}

		return $text;
	}

	/**
	 * Print the notice.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing arg.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! self::shows_on( $screen ? (string) $screen->id : '', $page ) ) {
			return;
		}

		$currencies = self::pending_currencies();
		if ( count( $currencies ) < 2 ) {
			return;
		}

		$dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
		?>
		<div class="notice notice-info better-payment-multi-currency-totals-notice">
			<p><strong><?php esc_html_e( 'Better Payment: totals now keep each currency separate', 'better-payment' ); ?></strong></p>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: currency codes, e.g. "USD, ZAR, EUR". */
						__( 'Your site has taken payments in more than one currency (%s). Totals used to add these amounts together as if they were one currency. Now each total shows one currency, and payments in other currencies are listed beside it. Nothing stored was changed, but a total may look lower than before because it no longer includes other currencies.', 'better-payment' ),
						self::describe_currencies( $currencies )
					)
				);
				?>
			</p>
			<p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'better-payment' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * admin-post: dismiss the notice for the site, then go back.
	 *
	 * @return void
	 */
	public static function dismiss(): void {
		self::process_dismiss();

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}

	/**
	 * Check capability and nonce, then record the dismissal.
	 *
	 * @return void
	 */
	public static function process_dismiss(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'better-payment' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::DISMISS_ACTION );

		$stored           = get_option( self::OPTION );
		$stored           = is_array( $stored ) ? $stored : array( 'currencies' => array() );
		$stored['status'] = 'dismissed';
		update_option( self::OPTION, $stored, false );
	}
}
