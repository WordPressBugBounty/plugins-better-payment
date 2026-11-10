<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Campaign\Support\Money;
use Better_Payment\Lite\Controller;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds "Stats" and "Edit in Builder" columns to the bp_campaign post list table.
 */
class CampaignListColumns extends Controller {

    public function add_columns( array $columns ): array {
        // Insert stats column before the date column
        $date = $columns['date'] ?? null;
        unset( $columns['date'] );

        $columns['campaign_stats']    = __( 'Campaign Stats', 'better-payment' );
        $columns['campaign_builder']  = __( 'Builder', 'better-payment' );

        if ( $date ) {
            $columns['date'] = $date;
        }

        return $columns;
    }

    public function render_column( string $column, int $post_id ) {
        if ( $column === 'campaign_stats' ) {
            $stats    = CampaignStats::get_stats( $post_id );
            $meta     = MetaBox::get_all( $post_id );
            $goal     = (float) ( $meta['bpc_goal_amount'] ?? 0 );
            // The currency the total and the goal are in: the campaign's own
            // (CampaignCurrency), which is also what the stats were summed in.
            $currency = (string) ( $stats['currency'] ?? CampaignCurrency::of( $post_id ) );
            $raised   = $stats['total_raised'];
            $progress = $stats['progress'];

            printf(
                '<strong>%s %s</strong> raised<br><small>%d donors &nbsp;|&nbsp; %d%%</small>',
                esc_html( $currency ),
                esc_html( Money::format( $raised ) ),
                (int) $stats['donor_count'],
                (int) $progress
            );

            // Money raised in another currency is listed, never added to the total.
            $others = CampaignStats::other_currencies( $stats );
            if ( $others ) {
                printf( '<br><small>+ %s</small>', esc_html( Money::list_by_currency( $others ) ) );
            }

            if ( $goal > 0 ) {
                printf( '<br><small>of %s %s goal</small>', esc_html( $currency ), esc_html( Money::format( $goal ) ) );
            }
        }

        if ( $column === 'campaign_builder' ) {
            $url = admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . $post_id );
            printf(
                '<a href="%s" class="button button-small">%s</a>',
                esc_url( $url ),
                esc_html__( 'Open Builder', 'better-payment' )
            );
        }
    }

    /**
     * The site's general currency code (Settings → General), USD when unset.
     * A campaign's own currency is CampaignCurrency::of().
     *
     * @return string
     */
    public static function site_currency(): string {
        return CampaignCurrency::site();
    }
}
