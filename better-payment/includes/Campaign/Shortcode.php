<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Campaign\Services\RendererService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [bp_campaign id="42"] shortcode — renders a campaign on the frontend.
 * Rendering is delegated to RendererService, which supports both the
 * column-based layout schema and the legacy flat-array format.
 */
class Shortcode extends Controller {

    public function register() {
        add_shortcode( 'bp_campaign', [ $this, 'render' ] );
    }

    public function render( $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'bp_campaign' );

        $campaign_id = absint( $atts['id'] );
        if ( ! $campaign_id ) {
            return '';
        }

        $post = get_post( $campaign_id );
        if ( ! $post || $post->post_type !== 'bp_campaign' || $post->post_status !== 'publish' ) {
            return '';
        }

        $this->enqueue_frontend_styles();
        $this->enqueue_frontend_scripts();

        return RendererService::render_campaign( $campaign_id );
    }

    private function enqueue_frontend_scripts(): void {
        if ( wp_script_is( 'fundraising-campaign-script', 'enqueued' ) ) {
            return;
        }
        wp_enqueue_script( 'fundraising-campaign-script' );
    }

    private function enqueue_frontend_styles(): void {
        $handle   = 'better-payment-campaign-display-style';
        $css_file = BETTER_PAYMENT_PATH . '/assets/blocks/campaign-display/style.min.css';

        if ( ! wp_style_is( $handle, 'registered' ) && file_exists( $css_file ) ) {
            wp_register_style(
                $handle,
                BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css',
                [],
                filemtime( $css_file )
            );
        }

        if ( ! wp_style_is( $handle, 'enqueued' ) ) {
            wp_enqueue_style( $handle );
        }
    }
}
