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
$better_payment_header = '';
$better_payment_footer = '';
if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
    $better_payment_theme_slug = wp_get_theme()->get( 'TextDomain' );
    $better_payment_header  = do_blocks( '<!-- wp:template-part {"slug":"header","theme":"' . esc_attr( $better_payment_theme_slug ) . '"} /-->' );
    $better_payment_footer  = do_blocks( '<!-- wp:template-part {"slug":"footer","theme":"' . esc_attr( $better_payment_theme_slug ) . '"} /-->' );
}

Helper::render_header( $better_payment_header );

if ( have_posts() ) :
    the_post();
    $better_payment_campaign_id = get_the_ID();

    if ( get_post_status( $better_payment_campaign_id ) !== 'publish' ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        Helper::render_footer( $better_payment_footer );
        exit;
    }

    // Enqueue frontend styles.
    $better_payment_css_file = BETTER_PAYMENT_PATH . '/assets/blocks/campaign-display/style.min.css';
    if ( file_exists( $better_payment_css_file ) ) {
        wp_enqueue_style(
            'better-payment-campaign-display-style',
            BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css',
            [],
            filemtime( $better_payment_css_file )
        );
    }

    // Theme CSS that loads on top of campaign-display (Pro's template themes).
    \Better_Payment\Lite\Campaign\CampaignStyles::enqueue_theme_styles( 'frontend' );

    // Enqueue frontend JS (amount selector → donate button URL).
    wp_enqueue_script( 'fundraising-campaign-script' );
    wp_enqueue_style( 'fundraising-campaign-style' );

    ?>
    <main id="bp-campaign-main" class="bp-campaign-page-wrap" role="main">
        <?php echo RendererService::render_campaign( $better_payment_campaign_id, is_preview() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- RendererService escapes every value as it builds the campaign markup. ?>
    </main>
    <?php
endif;

Helper::render_footer( $better_payment_footer );
