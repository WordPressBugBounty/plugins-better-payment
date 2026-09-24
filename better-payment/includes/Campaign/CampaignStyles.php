<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Classes\ProCompatibility;

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
 * locked Pro template cards preview as their screenshot only.
 *
 * The one thing left here that is Pro's is Lite's transitional copy of the Pro
 * themes, loaded only for an active Pro that does not ship its own (see
 * {@see self::lite_copy_applies()}).
 *
 * @since 2.4.0
 */
final class CampaignStyles {

    /**
     * The campaign-display stylesheet every theme builds on.
     */
    const DISPLAY_HANDLE = 'better-payment-campaign-display-style';

    // ── Transitional copy of the Pro themes ─────────────────────────────────────
    // Transitional copy for Better Payment Pro <= 1.4.3; delete when
    // ProCompatibility::MIN_PRO_VERSION >= 1.4.4. Pro copy:
    // better-payment-pro/src/css/campaign-themes.scss (loaded by
    // better-payment-pro/includes/Campaign/CampaignStyles.php). Slug:
    // campaign_theme_styles.
    // Deleting it means: FEATURE, LITE_COPY_HANDLE, LITE_COPY_PATH,
    // lite_copy_applies(), lite_copy_file(), the two blocks marked "Transitional
    // copy" below, and src/css/campaign-pro-themes.scss with its built
    // assets/css/campaign-pro-themes.min.css.

    /**
     * Feature slug Pro declares on `better_payment/pro_provides`.
     */
    const FEATURE = 'campaign_theme_styles';

    /**
     * Handle of Lite's transitional copy.
     */
    const LITE_COPY_HANDLE = 'better-payment-campaign-pro-themes';

    /**
     * Lite's transitional copy, relative to the plugin directory.
     */
    const LITE_COPY_PATH = '/assets/css/campaign-pro-themes.min.css';

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
        // Transitional copy — see lite_copy_applies().
        if ( self::lite_copy_applies() && file_exists( self::lite_copy_file() ) ) {
            wp_enqueue_style(
                self::LITE_COPY_HANDLE,
                BETTER_PAYMENT_ASSETS . self::LITE_COPY_PATH,
                array( self::DISPLAY_HANDLE ),
                (string) filemtime( self::lite_copy_file() )
            );
        }

        /**
         * Fires wherever a campaign's stylesheets are enqueued, so an add-on can load
         * its own theme CSS after Lite's campaign-display stylesheet.
         *
         * @param string $context 'frontend' or 'builder'.
         */
        do_action( 'better_payment/campaign/enqueue_styles', $context );
    }

    /**
     * Whether Lite's transitional copy of the Pro themes should load.
     *
     * Transitional copy for Better Payment Pro <= 1.4.3; delete when
     * ProCompatibility::MIN_PRO_VERSION >= 1.4.4. Pro copy:
     * better-payment-pro/src/css/campaign-themes.scss. Slug: campaign_theme_styles.
     *
     * True only for an active Pro that does not declare the slug, on every
     * surface alike. A free install never loads it: the design is Pro's product.
     * Entitlement and the Pro declaration are read live on every call.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * Absolute path of Lite's transitional copy.
     *
     * Transitional copy for Better Payment Pro <= 1.4.3; delete when
     * ProCompatibility::MIN_PRO_VERSION >= 1.4.4. Slug: campaign_theme_styles.
     *
     * @return string
     */
    private static function lite_copy_file(): string {
        return BETTER_PAYMENT_PATH . self::LITE_COPY_PATH;
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
        $urls = array();

        // Transitional copy — see lite_copy_applies().
        if ( self::lite_copy_applies() && file_exists( self::lite_copy_file() ) ) {
            $urls[] = add_query_arg( 'ver', (string) filemtime( self::lite_copy_file() ), BETTER_PAYMENT_ASSETS . self::LITE_COPY_PATH );
        }

        /**
         * Filter the extra stylesheets linked into a campaign preview document.
         *
         * @param string[] $urls Absolute stylesheet URLs.
         */
        $urls = apply_filters( 'better_payment/campaign/preview_stylesheets', $urls );

        // A misbehaving callback can return anything; keep only strings.
        return array_values( array_filter( (array) $urls, 'is_string' ) );
    }
}
