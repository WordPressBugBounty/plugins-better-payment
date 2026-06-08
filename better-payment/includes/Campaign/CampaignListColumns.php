<?php

namespace Better_Payment\Lite\Campaign;

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
            $currency = $meta['bpc_currency'] ?? 'USD';
            $raised   = $stats['total_raised'];
            $progress = $stats['progress'];

            printf(
                '<strong>%s %s</strong> raised<br><small>%d donors &nbsp;|&nbsp; %d%%</small>',
                esc_html( $currency ),
                esc_html( number_format( $raised, 2 ) ),
                (int) $stats['donor_count'],
                (int) $progress
            );

            if ( $goal > 0 ) {
                printf( '<br><small>of %s %s goal</small>', esc_html( $currency ), esc_html( number_format( $goal, 2 ) ) );
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
}
