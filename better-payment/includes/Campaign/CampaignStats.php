<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Computes and caches campaign stats (total raised, donors, donations, progress %).
 *
 * Two counts, because they answer two questions:
 * - `donation_count` is every approved donation *transaction* — one per row, so a
 *   $20/month donor adds one per renewal. It matches the per-donation list the
 *   Donors Wall renders (one card per approved row).
 * - `donor_count` is the number of *people*: distinct email addresses, compared
 *   trimmed and case-insensitively, plus one for every approved row with no email
 *   (an anonymous gift cannot be matched to anyone, so it is never merged into
 *   another donor, and never dropped the way `COUNT(DISTINCT email)` drops it).
 *   Every surface that says "donors" reads this one. It used to be `COUNT(*)`, so
 *   one person giving five times was shown as five donors.
 *
 * Which rows count, and what they add, is Helper::approved_donation_sql() and
 * Helper::COUNTABLE_AMOUNT_SQL — the one definition every aggregation shares.
 *
 * A confirmed donation that charged nothing — a 100%-off promotion code, a subscription trial —
 * is a donor too: Handler::confirm_stripe_session() stores it `paid` with its real amount, 0, so
 * it adds one to `donation_count` (and counts its donor) and nothing to `total_raised`. Nothing here filters on the amount.
 * Rows confirmed before 2.4.0 stored such a session as `no_payment_required` at the form's
 * amount: one that was confirmed counts as a donor and adds 0 (its stored amount was never
 * charged); one that never was (Stripe reports the same status for a free checkout at creation)
 * is not counted at all. See Helper::LEGACY_CONFIRMATION_MARKER.
 *
 * Amounts are never added across currencies. A campaign's goal is in its own currency
 * (CampaignCurrency::of(): stamped at creation, or the site currency for a campaign created
 * before 2.5.0), and each row stores the currency it was charged in. A row can still be in
 * another one: an older campaign follows the site currency, so changing that setting leaves
 * its earlier rows in the old one. So `raised_by_currency` sums each currency on its own,
 * and `total_raised` is the campaign currency's sum only, the one figure the goal, the
 * progress and close-on-goal can compare against. A row with no currency (older rows)
 * counts as the campaign currency. `currency` names the campaign currency the total is in.
 */
class CampaignStats extends Controller {

    private static int $cache_ttl = 2 * MINUTE_IN_SECONDS;

    /**
     * Transient key prefix. Versioned so a change in what the cached array holds rolls every
     * campaign's cache over at once: `v2` added `donation_count` and made `donor_count` count
     * people; `v3` added `currency` and `raised_by_currency`, and made `total_raised` the site
     * currency's sum only. An older key written by an earlier release simply expires.
     */
    const CACHE_PREFIX = 'bpc_stats_v3_';

    /**
     * Get stats for a campaign, using a 2-minute transient cache.
     */
    public static function get_stats( int $campaign_id, bool $use_cache = true ): array {
        $transient_key = self::CACHE_PREFIX . $campaign_id;

        if ( $use_cache ) {
            $cached = get_transient( $transient_key );
            // A total cached in another currency than the campaign's current one is stale:
            // an older campaign follows the site currency, and its `total_raised` and
            // `raised_by_currency` would read the new currency's donations as "other"
            // money (the page showed "$0 + $350" and 0% for a campaign that had raised
            // $350). Recompute instead. A stamped campaign's currency changes only while
            // it has no donation, and then the same check applies.
            if ( is_array( $cached ) && CampaignCurrency::of( $campaign_id ) === ( $cached['currency'] ?? '' ) ) {
                return $cached;
            }
        }

        $raw = self::query_stats( $campaign_id );

        $goal   = (float) get_post_meta( $campaign_id, '_bpc_goal_amount', true );
        $raised = $raw['total_raised'];

        $progress = ( $goal > 0 ) ? min( 100.0, round( ( $raised / $goal ) * 100, 1 ) ) : 0.0;

        $end_date       = get_post_meta( $campaign_id, '_bpc_end_date', true );
        $days_remaining = self::days_remaining( is_string( $end_date ) ? $end_date : '' );

        $stats = apply_filters( 'better_payment/campaign/stats', array_merge( $raw, [
            'goal'           => $goal,
            'progress'       => $progress,
            'days_remaining' => $days_remaining,
        ] ), $campaign_id );

        set_transient( $transient_key, $stats, self::$cache_ttl );

        return $stats;
    }

    /**
     * Whole days left before a campaign closes, or null when it has no end date.
     *
     * The end date is INCLUSIVE: a campaign ending on the 31st is live through
     * 23:59:59 that day — the same rule AI\Support\DateGuard uses when it decides
     * whether an end date is past. Counting down to 00:00 of the end date closed
     * every campaign a day early (0 days left all through its last day). A value
     * that carries its own time of day is used as given.
     *
     * Times are compared in the site's timezone, like current_time( 'timestamp' ).
     *
     * @param string $end_date Stored `_bpc_end_date` (usually Y-m-d).
     * @param int    $now      Site-local "timestamp" (current_time( 'timestamp' )); 0 = now.
     * @return int|null Days left (0 once it has ended), or null with no/unparseable end date.
     */
    public static function days_remaining( string $end_date, int $now = 0 ): ?int {
        // One reading of the end date, shared with the rule that closes the campaign.
        $end = CampaignLifecycle::end_timestamp( $end_date );
        if ( null === $end ) {
            return null;
        }

        $now  = $now > 0 ? $now : (int) current_time( 'timestamp' );
        $diff = $end - $now;

        return max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
    }

    /**
     * The site's general currency. A campaign's own currency is CampaignCurrency::of().
     *
     * @return string ISO code, upper case; USD when unset.
     */
    public static function site_currency(): string {
        return CampaignCurrency::site();
    }

    /**
     * Turn per-currency sums into `raised_by_currency`.
     *
     * The campaign currency always comes first, even at 0, because `total_raised` is read from
     * it. Every other currency follows, largest first, and only when it raised something: a free
     * checkout in another currency adds no figure worth printing. A blank code (rows stored
     * before the column was filled) is the campaign currency.
     *
     * @param array  $rows Rows of `[ 'currency' => code, 'raised' => sum ]`.
     * @param string $site The campaign's currency code.
     * @return array<string, float> Code => sum, rounded to cents.
     */
    public static function group_raised( array $rows, string $site ): array {
        $site   = strtoupper( trim( $site ) );
        $totals = [ $site => 0.0 ];

        foreach ( $rows as $row ) {
            $row  = (array) $row;
            $code = strtoupper( trim( (string) ( $row['currency'] ?? '' ) ) );
            $code = '' === $code ? $site : $code;

            $totals[ $code ] = ( $totals[ $code ] ?? 0.0 ) + (float) ( $row['raised'] ?? 0 );
        }

        $others = [];
        foreach ( $totals as $code => $sum ) {
            $sum = round( $sum, 2 );
            if ( $code !== $site && $sum > 0 ) {
                $others[ $code ] = $sum;
            }
        }
        arsort( $others );

        return [ $site => round( $totals[ $site ], 2 ) ] + $others;
    }

    /**
     * The currencies a campaign raised money in besides its own.
     *
     * @param array $stats Stats from get_stats() (or the same shape).
     * @return array<string, float> Code => sum; empty when everything is in the campaign currency.
     */
    public static function other_currencies( array $stats ): array {
        $by_currency = isset( $stats['raised_by_currency'] ) && is_array( $stats['raised_by_currency'] )
            ? $stats['raised_by_currency']
            : [];
        $site        = strtoupper( (string) ( $stats['currency'] ?? '' ) );

        $others = [];
        foreach ( $by_currency as $code => $sum ) {
            if ( (string) $code !== $site && (float) $sum > 0 ) {
                $others[ (string) $code ] = (float) $sum;
            }
        }

        return $others;
    }

    /**
     * Bust the stats cache for a campaign — call after a new payment is linked.
     */
    public static function bust_cache( int $campaign_id ) {
        delete_transient( self::CACHE_PREFIX . $campaign_id );
    }

    /**
     * Register WordPress hooks.
     * Called once from the plugin bootstrap.
     */
    public static function register_hooks(): void {
        // Bust cache when a payment is confirmed (status updated to a successful state).
        // Fires from Handler.php after each gateway's wpdb->update() call.
        add_action( 'better_payment/payment_confirmed', [ static::class, 'on_payment_confirmed' ] );
    }

    /**
     * Look up the campaign_id on the confirmed payment row and bust its stats cache.
     *
     * @param int $payment_id  Row ID in wp_better_payment.
     */
    public static function on_payment_confirmed( int $payment_id ): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no WP API; this method busts the stats cache.
        $campaign_id = (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT campaign_id FROM {$wpdb->prefix}better_payment WHERE id = %d LIMIT 1",
                $payment_id
            )
        );
        if ( $campaign_id !== '' && $campaign_id !== '0' ) {
            self::bust_cache( (int) $campaign_id );
        }
    }

    private static function query_stats( int $campaign_id ): array {
        global $wpdb;

        $payment_table = $wpdb->prefix . 'better_payment';
        $approved      = Helper::approved_donation_sql();
        $amount        = Helper::COUNTABLE_AMOUNT_SQL;

        // One donor per distinct non-empty email (trimmed, lower-cased), plus one per row without one.
        $donor_email = "LOWER(TRIM(COALESCE(email, '')))";

        // Both fragments are Helper constants (no request data, no `%`); table name from $wpdb->prefix.
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- see the comment above.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, no WP API; result is cached by the caller (get_stats); table name from $wpdb->prefix.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; $amount and $approved are Helper constants (COUNTABLE_AMOUNT_SQL / approved_donation_sql()).
                "SELECT
                    COUNT(*)                    AS donation_count,
                    COUNT(DISTINCT CASE WHEN {$donor_email} <> '' THEN {$donor_email} END)
                        + COALESCE(SUM(CASE WHEN {$donor_email} = '' THEN 1 ELSE 0 END), 0) AS donor_count,
                    MAX(payment_date)           AS last_donation_date
                FROM `{$payment_table}`
                WHERE campaign_id = %s
                   AND {$approved}",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                (string) $campaign_id
            ),
            ARRAY_A
        );

        // One sum per currency: amounts in two currencies are never added together.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, no WP API; result is cached by the caller (get_stats); table name from $wpdb->prefix.
        $sums = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; $amount and $approved are Helper constants (COUNTABLE_AMOUNT_SQL / approved_donation_sql()).
                "SELECT
                    UPPER(TRIM(COALESCE(currency, ''))) AS currency,
                    COALESCE(SUM({$amount}), 0)         AS raised
                FROM `{$payment_table}`
                WHERE campaign_id = %s
                   AND {$approved}
                GROUP BY UPPER(TRIM(COALESCE(currency, '')))",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                (string) $campaign_id
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $site        = CampaignCurrency::of( $campaign_id );
        $by_currency = self::group_raised( is_array( $sums ) ? $sums : [], $site );

        return [
            'total_raised'       => $by_currency[ $site ],
            'currency'           => $site,
            'raised_by_currency' => $by_currency,
            'donor_count'        => (int)   ( $row['donor_count'] ?? 0 ),
            'donation_count'     => (int)   ( $row['donation_count'] ?? 0 ),
            'last_donation_date' => $row['last_donation_date'] ?? null,
        ];
    }
}
