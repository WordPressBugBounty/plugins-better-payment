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
 * It is the same split {@see \Better_Payment\Lite\Campaign\Elements\ProElementSchemas}
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
 * these, and the CSS behind `bp-v2-*` lives in Lite either way. Omitting the
 * class here would strip the design off their live page the moment Pro switched
 * off, which is a far worse downgrade than losing the Pro elements (those
 * degrade to nothing by design). The layouts still ship only with Pro; this
 * hands back styling for a page they already built.
 *
 * ## Thumbnails
 *
 * Lite ships its own copies under `assets/img/campaign/templates/preview/pro/`.
 * Pointing at Pro's asset URL would 404 on the only install that needs these
 * cards — the one without Pro — and a template whose thumbnail fails to load
 * falls back to a flat `preview_color` block, which sells nothing. Keep the two
 * sets of files in sync when Pro's artwork changes.
 *
 * @since 2.3.2
 */
class ProTemplateCatalog {

    /**
     * Path below Lite's assets/ where the mirrored Pro thumbnails live.
     */
    const PREVIEW_DIR = 'assets/img/campaign/templates/preview/pro';

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
            'pro-emergency-relief-v2'   => array(
                'key'           => 'pro-emergency-relief-v2',
                'label'         => __( 'Food Drive Campaign', 'better-payment' ),
                'description'   => __( 'Community food appeal with a live donation ticker, video and FAQ beside a sticky giving panel.', 'better-payment' ),
                'tags'          => array( 'charity' ),
                'layout'        => '2-column',
                'theme_class'   => 'bp-v2-fooddrive',
                'preview_color' => '#f59e0b',
                'preview_image' => self::PREVIEW_DIR . '/Food-Drive-Campaign-v2.webp',
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
            ),
        );

        // Applied here rather than repeated in each entry: `pro` is what the
        // builder's shared lock predicate reads (utils/proEnabled.js), and
        // `columns` being empty is the contract the whole class rests on — both
        // are properties of "this is a catalogue entry", not of any one template.
        foreach ( $templates as $key => $template ) {
            $templates[ $key ]['category'] = 'prebuilt';
            $templates[ $key ]['pro']      = true;
            $templates[ $key ]['columns']  = array();
        }

        return $templates;
    }

    /**
     * The four Pro template keys.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys( self::catalog() );
    }
}
