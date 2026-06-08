<?php
/**
 * Template for single bp_campaign posts.
 *
 * Loaded via the template_include filter in better-payment.php.
 * Renders the full campaign using RendererService so the CPT permalink
 * (/bp-campaigns/slug/) shows the same output as [bp_campaign id="X"].
 */

use Better_Payment\Lite\Campaign\Services\RendererService;
use Better_Payment\Lite\Classes\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// For block themes, pre-render header/footer template parts so they display
// correctly on this CPT page (which bypasses the theme's block templates).
$bp_header = '';
$bp_footer = '';
if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
    $theme_slug = wp_get_theme()->get( 'TextDomain' );
    $bp_header  = do_blocks( '<!-- wp:template-part {"slug":"header","theme":"' . esc_attr( $theme_slug ) . '"} /-->' );
    $bp_footer  = do_blocks( '<!-- wp:template-part {"slug":"footer","theme":"' . esc_attr( $theme_slug ) . '"} /-->' );
}

Helper::render_header( $bp_header );

if ( have_posts() ) :
    the_post();
    $campaign_id = get_the_ID();

    if ( get_post_status( $campaign_id ) !== 'publish' ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        Helper::render_footer( $bp_footer );
        exit;
    }

    // Enqueue frontend styles.
    $css_file = BETTER_PAYMENT_PATH . '/assets/blocks/campaign-display/style.min.css';
    if ( file_exists( $css_file ) ) {
        wp_enqueue_style(
            'better-payment-campaign-display-style',
            BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css',
            [],
            filemtime( $css_file )
        );
    }

    // Enqueue frontend JS (amount selector → donate button URL).
    wp_enqueue_script( 'fundraising-campaign-script' );
    wp_enqueue_style( 'fundraising-campaign-style' );

    ?>
    <main id="bp-campaign-main" class="bp-campaign-page-wrap" role="main">
        <?php echo RendererService::render_campaign( $campaign_id, is_preview() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </main>
    <?php
endif;

Helper::render_footer( $bp_footer );
