<?php

namespace Better_Payment\Lite\Campaign\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Card metadata for the four Pro-only campaign templates, so a free install can
 * SHOW them — locked — instead of pretending they do not exist.
 *
 * ## Why this lives in Lite
 *
 * It is the same split {@see \Better_Payment\Lite\Campaign\Elements\ProElementStubs}
 * makes for the three Pro elements: **Lite owns the description of the product,
 * Pro owns the product.** A free install has no copy of Pro on disk, so without a
 * catalogue here the Pro templates are simply absent from the picker and a free
 * user has no way to learn they exist. What crosses the boundary is a card —
 * label, one-line description, category tags, a thumbnail and an accent colour.
 * No layout, no elements, no settings, no business logic.
 *
 * ## What is deliberately NOT here
 *
 * `columns` is always `array()`. That is the enforcement, not a shortcut: the
 * locked entry physically cannot build a page, so even a client that ignored
 * every UI gate and dispatched `APPLY_TEMPLATE` on one would produce an empty
 * campaign rather than a free copy of a Pro design. The real layouts stay in
 * `Better_Payment\Pro\Campaign\BuilderTemplates` and ship only with Pro.
 *
 * ## Entitlement
 *
 * Read live from `better_payment/pro_enabled` on every call, exactly like every
 * other Pro gate in the builder — never inferred from stored campaign data. With
 * Pro active {@see self::get_locked()} returns nothing at all: Pro registers the
 * real templates under these same keys through
 * `better_payment/campaign_templates`, and a paying customer must never be shown
 * a lock, a crown or an upsell for something they already own. (The keys
 * matching is not a coincidence to be tidied away — it is what makes the locked
 * card and the real template the same product to the user, and Pro's
 * `BuilderTemplatesRegistrationTest` fails if the two lists drift.)
 *
 * ## Card order
 *
 * The picker renders templates in registry order, so this array is a layout
 * decision. These four are merged in after Lite's own eight and continue their
 * 2-column → split → 1-column rotation rather than grouping by preset, and the
 * accents are spread so no two similar hues land side by side in a three-up
 * grid. **The same order is repeated in Pro's `BuilderTemplates` and a test
 * asserts the two match** — otherwise a free install and a paid one would show
 * the same twelve cards in two different arrangements.
 *
 * ## `theme_class` is carried here on purpose
 *
 * Unlike the rest of a locked card, `theme_class` is not advertising — it is
 * what a campaign resolves on every page view. A customer who built with Pro and
 * then let the licence lapse still has `_bpc_template_key` pointing at one of
 * these. The design behind `bp-v2-water` and the other three ships with Pro and
 * does not load without it, but Lite's campaign-display stylesheet keeps the four
 * classes in its shared v2 selector list, backed by zero-specificity `:where()`
 * fallback tokens. Carrying the class keeps that page a plain, readable v2 design
 * instead of dropping it out of the v2 system altogether, and reactivating Pro
 * restores the full design with no data change. It is a class name, not CSS.
 *
 * ## Thumbnails and full-page previews — the only preview a locked card has
 *
 * Lite ships its own copies under `assets/img/campaign/templates/preview/pro/`.
 * Pointing at Pro's asset URL would 404 on the only install that needs these
 * cards — the one without Pro — and a template whose thumbnail fails to load
 * falls back to a flat `preview_color` block, which sells nothing. Keep the two
 * sets of files in sync when Pro's artwork changes.
 *
 * The card thumbnail is only the top of the page, so each card also carries
 * `preview_full`: a desktop (1280px) and a mobile (390px) screenshot of the
 * WHOLE design, under `preview/pro/full/`, which the Preview lightbox scrolls
 * with a Desktop/Mobile switch. Lite carries no copy of Pro's layouts, so a
 * locked card is never rendered live — these images are what lets a free user
 * see a Pro design end to end. They are pictures, exactly like the thumbnail:
 * nothing in them can build a page. Made with
 * `node scripts/capture-template-thumbnails.mjs --full <key> --out <dir>` on a
 * site with Pro active (see docs/features/campaign-builder/templates.md).
 *
 * @since 2.3.2
 */
class ProTemplateCatalog {

    /**
     * Path below Lite's assets/ where Lite's copies of the Pro card thumbnails live.
     */
    const PREVIEW_DIR = 'assets/img/campaign/templates/preview/pro';

    /**
     * Path below Lite's assets/ where the full-page previews of the Pro templates
     * live, as `{key}-desktop.webp` and `{key}-mobile.webp`.
     *
     * @since 2.5.0
     */
    const FULL_PREVIEW_DIR = self::PREVIEW_DIR . '/full';

    /**
     * The locked Pro template cards, or an empty array when Pro is active.
     *
     * @return array<string, array> Templates keyed by template key.
     */
    public static function get_locked(): array {
        if ( apply_filters( 'better_payment/pro_enabled', false ) ) {
            return array();
        }

        return self::catalog();
    }

    /**
     * Every Pro template card, regardless of entitlement.
     *
     * Entitlement-blind on purpose, so tests and the Pro-side drift check can
     * read the list without having to fake a licence. Production code wants
     * {@see self::get_locked()}.
     *
     * @return array<string, array>
     */
    public static function catalog(): array {
        $templates = array(
            'pro-emergency-relief-v3'   => array(
                'key'           => 'pro-emergency-relief-v3',
                'label'         => __( 'Food Drive Campaign', 'better-payment' ),
                'description'   => __( 'Community food appeal with a live donation ticker, video and FAQ beside a sticky giving panel.', 'better-payment' ),
                'tags'          => array( 'charity' ),
                'layout'        => '2-column',
                'theme_class'   => 'bp-v3-fooddrive',
                'preview_color' => '#f59e0b',
                'preview_image' => self::PREVIEW_DIR . '/Food-Drive-Campaign-v3.webp',
            ),
            'pro-nonprofit-landing-v3'  => array(
                'key'           => 'pro-nonprofit-landing-v3',
                'label'         => __( 'Climate Action Campaign', 'better-payment' ),
                'description'   => __( 'Long-form single-column landing page — hero, proof, and the ask repeated where readers reach it.', 'better-payment' ),
                'tags'          => array( 'environmental' ),
                'layout'        => '1-column',
                'theme_class'   => 'bp-v3-climate',
                'preview_color' => '#1d4ed8',
                'preview_image' => self::PREVIEW_DIR . '/Climate-Action-Campaign-v3.webp',
            ),
            'pro-modern-fundraising-v3' => array(
                'key'           => 'pro-modern-fundraising-v3',
                'label'         => __( 'Clean Water Campaign', 'better-payment' ),
                'description'   => __( 'Full-width hero, then story and donation side by side — with video, FAQ and a live donor wall.', 'better-payment' ),
                'tags'          => array( 'charity' ),
                'layout'        => 'split',
                'theme_class'   => 'bp-v3-water',
                'preview_color' => '#0369a1',
                'preview_image' => self::PREVIEW_DIR . '/Clean-Water-Campaign-v3.webp',
            ),
            'pro-community-support-v3'  => array(
                'key'           => 'pro-community-support-v3',
                'label'         => __( 'Wildlife Conservation Campaign', 'better-payment' ),
                'description'   => __( 'Habitat restoration page built around a public supporters wall, with video and FAQ.', 'better-payment' ),
                'tags'          => array( 'environmental' ),
                'layout'        => '1-column',
                'theme_class'   => 'bp-v3-wildlife',
                'preview_color' => '#4d7c0f',
                'preview_image' => self::PREVIEW_DIR . '/Wildlife-Conservation-Campaign-v3.webp',
            ),
        );

        // The -v2 cards, retired by the -v3 reissue. Hidden: never offered, but
        // still in the registry so a campaign built from one with Pro, whose
        // licence has since lapsed, keeps resolving its theme class (and the
        // `:where()` fallback tokens behind it).
        $retired = array(
            'pro-emergency-relief-v2'   => array(
                'key'           => 'pro-emergency-relief-v2',
                'label'         => __( 'Food Drive Campaign', 'better-payment' ),
                'description'   => __( 'Community food appeal with a live donation ticker, video and FAQ beside a sticky giving panel.', 'better-payment' ),
                'tags'          => array( 'charity' ),
                'layout'        => '2-column',
                'theme_class'   => 'bp-v2-fooddrive',
                'preview_color' => '#f59e0b',
                'preview_image' => self::PREVIEW_DIR . '/Food-Drive-Campaign-v2.webp',
                'hidden'        => true,
            ),
            'pro-nonprofit-landing-v2'  => array(
                'key'           => 'pro-nonprofit-landing-v2',
                'label'         => __( 'Climate Action Campaign', 'better-payment' ),
                'description'   => __( 'Long-form single-column landing page — hero, proof, and the ask repeated where readers reach it.', 'better-payment' ),
                'tags'          => array( 'environmental' ),
                'layout'        => '1-column',
                'theme_class'   => 'bp-v2-climate',
                'preview_color' => '#1d4ed8',
                'preview_image' => self::PREVIEW_DIR . '/Climate-Action-Campaign-v2.webp',
                'hidden'        => true,
            ),
            'pro-modern-fundraising-v2' => array(
                'key'           => 'pro-modern-fundraising-v2',
                'label'         => __( 'Clean Water Campaign', 'better-payment' ),
                'description'   => __( 'Full-width hero, then story and donation side by side — with video, FAQ and a live donor wall.', 'better-payment' ),
                'tags'          => array( 'charity' ),
                'layout'        => 'split',
                'theme_class'   => 'bp-v2-water',
                'preview_color' => '#0369a1',
                'preview_image' => self::PREVIEW_DIR . '/Clean-Water-Campaign-v2.webp',
                'hidden'        => true,
            ),
            'pro-community-support-v2'  => array(
                'key'           => 'pro-community-support-v2',
                'label'         => __( 'Wildlife Conservation Campaign', 'better-payment' ),
                'description'   => __( 'Habitat restoration page built around a public supporters wall, with video and FAQ.', 'better-payment' ),
                'tags'          => array( 'environmental' ),
                'layout'        => '1-column',
                'theme_class'   => 'bp-v2-wildlife',
                'preview_color' => '#4d7c0f',
                'preview_image' => self::PREVIEW_DIR . '/Wildlife-Conservation-Campaign-v2.webp',
                'hidden'        => true,
            ),
        );

        // Applied here rather than repeated in each entry: `pro` is what the
        // builder's shared lock predicate reads (utils/proEnabled.js), and
        // `columns` being empty is the contract the whole class rests on — both
        // are properties of "this is a catalogue entry", not of any one template.
        foreach ( $templates as $key => $template ) {
            $templates[ $key ]['preview_full'] = self::full_previews( $key );
        }

        $templates = array_merge( $templates, $retired );
        foreach ( $templates as $key => $template ) {
            $templates[ $key ]['category'] = 'prebuilt';
            $templates[ $key ]['pro']      = true;
            $templates[ $key ]['columns']  = array();
        }

        return $templates;
    }

    /**
     * The full-page preview images of one Pro template, by device.
     *
     * Named by key, so a template added to the catalogue needs only its two
     * images — and `ProTemplateCatalogFullPreviewTest` fails until they exist.
     *
     * @since 2.5.0
     *
     * @param string $key Template key.
     * @return array{desktop: string, mobile: string} Plugin-relative paths.
     */
    public static function full_previews( string $key ): array {
        return array(
            'desktop' => self::FULL_PREVIEW_DIR . '/' . $key . '-desktop.webp',
            'mobile'  => self::FULL_PREVIEW_DIR . '/' . $key . '-mobile.webp',
        );
    }

    /**
     * The four OFFERED Pro template keys, in card order. The retired `-v2` cards
     * are in {@see self::catalog()} but not here: this is what Pro sells, and what
     * Pro's BuilderTemplatesRegistrationTest compares with its own offered list.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys(
            array_filter(
                self::catalog(),
                static function ( $template ) {
                    return empty( $template['hidden'] );
                }
            )
        );
    }
}
