<?php

namespace Better_Payment\Lite\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fires the hooks through which an add-on loads campaign theme CSS.
 *
 * The designs of the four Pro `-v2` themes (bp-v2-water, -fooddrive, -wildlife,
 * -climate) and the theming of Pro's widgets used to be compiled into Lite's
 * campaign-display stylesheet, because Pro had no way to add CSS to a campaign
 * page. They ship with Pro now (src/css/campaign-themes.scss), which loads them
 * through the `better_payment/campaign/enqueue_styles` action and the
 * `better_payment/campaign/preview_stylesheets` filter fired here.
 *
 * Without Pro, no surface loads any Pro design — not a live page, not the
 * Campaign Builder, not a preview document. A campaign built from a Pro template
 * keeps its theme class, and the zero-specificity `:where()` fallback tokens in
 * the campaign-display stylesheet render it as a plain v2 page. A free install's
 * locked Pro template cards preview as their screenshot only. Lite's transitional
 * copy of the Pro themes was deleted when ProCompatibility::MIN_PRO_VERSION
 * reached 1.4.4.
 *
 * @since 2.4.0
 */
final class CampaignStyles {

    /**
     * The campaign-display stylesheet every theme builds on.
     */
    const DISPLAY_HANDLE = 'better-payment-campaign-display-style';

    /**
     * Enqueue the theme stylesheets for one campaign surface.
     *
     * Call it right after the campaign-display stylesheet is enqueued or registered.
     *
     * @param string $context 'frontend' for a live campaign page, 'builder' for the
     *                        Campaign Builder admin page.
     * @return void
     */
    public static function enqueue_theme_styles( string $context = 'frontend' ): void {
        /**
         * Fires wherever a campaign's stylesheets are enqueued, so an add-on can load
         * its own theme CSS after Lite's campaign-display stylesheet.
         *
         * @param string $context 'frontend' or 'builder'.
         */
        do_action( 'better_payment/campaign/enqueue_styles', $context );
    }

    /**
     * Stylesheet URLs a standalone preview document links after campaign-display.
     *
     * Preview documents (the builder canvas, the template picker's lightbox) are
     * built as HTML strings and never run wp_head, so they cannot use enqueues.
     *
     * @return string[]
     */
    public static function preview_stylesheets(): array {
        /**
         * Filter the extra stylesheets linked into a campaign preview document.
         *
         * @param string[] $urls Absolute stylesheet URLs.
         */
        $urls = apply_filters( 'better_payment/campaign/preview_stylesheets', array() );

        // A misbehaving callback can return anything; keep only strings.
        return array_values( array_filter( (array) $urls, 'is_string' ) );
    }
}
