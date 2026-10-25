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

// Access check BEFORE any output. A campaign that is not published is visible
// only to a user who can edit it (an admin checking a draft before publishing);
// everyone else gets a real 404. This must run before render_header(): once the
// theme header is printed, status_header( 404 ) can no longer be sent.
$better_payment_post = get_queried_object();
if (
    $better_payment_post instanceof WP_Post
    && 'publish' !== $better_payment_post->post_status
    && ! current_user_can( 'edit_post', $better_payment_post->ID )
) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    $better_payment_404 = get_404_template();
    if ( $better_payment_404 ) {
        include $better_payment_404;
        return;
    }
    Helper::render_header( '' );
    Helper::render_footer( '' );
    return;
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

    // A password-protected campaign shows WordPress's password form until the
    // visitor has entered it — never the campaign, its figures or its Donate button.
    if ( post_password_required( $better_payment_campaign_id ) ) {
        ?>
        <main id="bp-campaign-main" class="bp-campaign-page-wrap" role="main">
            <?php echo get_the_password_form( $better_payment_campaign_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup; get_the_password_form() escapes its own values. ?>
        </main>
        <?php
        Helper::render_footer( $better_payment_footer );
        return;
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
        <?php
        // Always a live render, even under `?preview=true`: any visitor can add that
        // query arg, and WordPress only lets an editor see a draft through it, so the
        // page is a real one either way. Treated as a preview, empty elements became
        // builder placeholders and the Donate Button opened a popup that a preview
        // never prints.
        echo RendererService::render_campaign( $better_payment_campaign_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- RendererService escapes every value as it builds the campaign markup.
        ?>
    </main>
    <?php
endif;

Helper::render_footer( $better_payment_footer );
