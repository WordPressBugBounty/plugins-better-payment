<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Computes and caches campaign stats (total raised, donor count, progress %).
 *
 * `donor_count` is the count of approved donation *transactions*, not of unique
 * donors — it must agree with the per-transaction list the Donors Wall renders
 * (one card per approved row). Counting distinct emails made the summary read
 * "3 Donors" above a 5-card list; a repeat donor (or a row with an empty email,
 * which `COUNT(DISTINCT email)` drops entirely) is one transaction here.
 *
 * Which rows count, and what they add, is Helper::approved_donation_sql() and
 * Helper::COUNTABLE_AMOUNT_SQL — the one definition every aggregation shares.
 *
 * A confirmed donation that charged nothing — a 100%-off promotion code, a subscription trial —
 * is a donor too: Handler::confirm_stripe_session() stores it `paid` with its real amount, 0, so
 * it adds one to `donor_count` and nothing to `total_raised`. Nothing here filters on the amount.
 * Rows confirmed before 2.4.0 stored such a session as `no_payment_required` at the form's
 * amount: one that was confirmed counts as a donor and adds 0 (its stored amount was never
 * charged); one that never was (Stripe reports the same status for a free checkout at creation)
 * is not counted at all. See Helper::LEGACY_CONFIRMATION_MARKER.
 */
class CampaignStats extends Controller {

    private static int $cache_ttl = 2 * MINUTE_IN_SECONDS;

    /**
     * Get stats for a campaign, using a 2-minute transient cache.
     */
    public static function get_stats( int $campaign_id, bool $use_cache = true ): array {
        $transient_key = 'bpc_stats_' . $campaign_id;

        if ( $use_cache ) {
            $cached = get_transient( $transient_key );
            if ( $cached !== false ) {
                return $cached;
            }
        }

        $raw = self::query_stats( $campaign_id );

        $goal   = (float) get_post_meta( $campaign_id, '_bpc_goal_amount', true );
        $raised = $raw['total_raised'];

        $progress = ( $goal > 0 ) ? min( 100.0, round( ( $raised / $goal ) * 100, 1 ) ) : 0.0;

        $end_date      = get_post_meta( $campaign_id, '_bpc_end_date', true );
        $days_remaining = null;

        if ( $end_date ) {
            $diff = ( strtotime( $end_date ) - current_time( 'timestamp' ) );
            $days_remaining = max( 0, (int) ceil( $diff / DAY_IN_SECONDS ) );
        }

        $stats = apply_filters( 'better_payment/campaign/stats', array_merge( $raw, [
            'goal'           => $goal,
            'progress'       => $progress,
            'days_remaining' => $days_remaining,
        ] ), $campaign_id );

        set_transient( $transient_key, $stats, self::$cache_ttl );

        return $stats;
    }

    /**
     * Bust the stats cache for a campaign — call after a new payment is linked.
     */
    public static function bust_cache( int $campaign_id ) {
        delete_transient( 'bpc_stats_' . $campaign_id );
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

        // Both fragments are Helper constants (no request data, no `%`).
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COALESCE(SUM({$amount}), 0) AS total_raised,
                    COUNT(*)                    AS donor_count,
                    MAX(payment_date)           AS last_donation_date
                FROM `{$payment_table}`
                WHERE campaign_id = %s
                   AND {$approved}",
                (string) $campaign_id
            ),
            ARRAY_A
        );

        return [
            'total_raised'       => (float) ( $row['total_raised'] ?? 0 ),
            'donor_count'        => (int)   ( $row['donor_count'] ?? 0 ),
            'last_donation_date' => $row['last_donation_date'] ?? null,
        ];
    }
}
