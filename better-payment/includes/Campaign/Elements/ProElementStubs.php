<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lock stubs for the three Pro-only campaign elements — Donors Wall, FAQ and
 * Video.
 *
 * This is all Lite registers for them. Better Payment Pro owns their control
 * schemas and default settings (`better-payment-pro/includes/Campaign/ProElementSchemas.php`)
 * and replaces these entries on `better_payment/campaign_elements`.
 *
 * A stub carries only what a free install needs to show the element as a locked
 * Pro widget: a label and icon for the palette card, the `pro` flag every lock
 * affordance keys on, and a short feature list for the Customize panel's upgrade
 * banner. `settingsSchema` and `defaultSettings` are empty on purpose:
 *
 *   - a free user can still drop, select, move and delete the element, and the
 *     canvas shows {@see ProElementPreview}'s mock;
 *   - a newly added element saves with no settings
 *     ({@see \Better_Payment\Lite\Campaign\MetaBox::enforce_pro_entitlement()}),
 *     and an existing one keeps its stored settings verbatim, so reactivating Pro
 *     restores a lapsed subscriber's configuration exactly.
 *
 * The `pro` flag is a description of the element, never an entitlement. Locking
 * is decided everywhere by the live `better_payment/pro_enabled` filter.
 *
 * @since 2.4.0
 */
class ProElementStubs {

    /**
     * The three stubs, keyed by element type.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        return [
            'donors_wall' => self::stub(
                __( 'Donors Wall', 'better-payment' ),
                'groups',
                [
                    __( 'Shows real donors from your campaign payments', 'better-payment' ),
                    __( 'List, grid or auto-scrolling ticker layouts', 'better-payment' ),
                    __( 'Choose which donor details and totals to show', 'better-payment' ),
                ]
            ),
            'faq'         => self::stub(
                __( 'FAQ', 'better-payment' ),
                'editor-help',
                [
                    __( 'Answer the questions donors ask before giving', 'better-payment' ),
                    __( 'Collapsible accordion or open-list styles', 'better-payment' ),
                    __( 'Add, reorder and pre-open as many questions as you need', 'better-payment' ),
                ]
            ),
            'video'       => self::stub(
                __( 'Video', 'better-payment' ),
                'format-video',
                [
                    __( 'Embed YouTube, Vimeo or a video from your Media Library', 'better-payment' ),
                    __( 'Responsive 16:9, 4:3, 1:1 and 21:9 players', 'better-payment' ),
                    __( 'Autoplay, loop, mute and start-time controls', 'better-payment' ),
                ]
            ),
        ];
    }

    /**
     * One stub definition.
     *
     * @param string   $label    Palette and settings-panel label.
     * @param string   $icon     Dashicons suffix.
     * @param string[] $features Upgrade-banner feature list.
     * @return array
     */
    private static function stub( string $label, string $icon, array $features ): array {
        return [
            'label'           => $label,
            'icon'            => $icon,
            'pro'             => true,
            'proFeatures'     => $features,
            'settingsSchema'  => [],
            'defaultSettings' => [],
        ];
    }
}
