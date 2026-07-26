<?php

namespace Better_Payment\Lite\AI\Schema;

use Better_Payment\Lite\Campaign\Elements\ElementRegistry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical, machine-readable description of a Better Payment campaign.
 *
 * This is the single source of truth the AI layer uses to (a) tell a model what
 * it is allowed to produce and (b) validate what a model returns. It never
 * hardcodes the element list — everything is derived live from
 * {@see ElementRegistry::get_all()} so the AI schema can never drift from the
 * builder's real capabilities.
 *
 * A campaign is structured JSON:
 *   {
 *     "layout": "1-column" | "2-column" | "3-column",
 *     "columns": [
 *       { "id": string, "label": string, "width": "NN%",
 *         "elements": [ { "id": string, "type": <element type>, "settings": { ... } } ] }
 *     ]
 *   }
 * plus flat campaign meta (goal amount, colours, donation amounts, ...).
 *
 * @see \Better_Payment\Lite\AI\Operations\OperationValidator  Uses these allowlists.
 * @see \Better_Payment\Lite\AI\Prompt\PromptBuilder           Serialises this for the model.
 */
class CampaignSchema {

    /**
     * Layout presets the builder understands.
     *
     * @return array<int, string>
     */
    public static function layout_presets(): array {
        return [ '1-column', '2-column', '3-column' ];
    }

    /**
     * Every registered element type key (e.g. campaign_title, photo, ...).
     *
     * @return array<int, string>
     */
    public static function element_types(): array {
        return array_keys( ElementRegistry::get_all() );
    }

    /**
     * Whether a given element type is registered.
     *
     * Deliberately entitlement-BLIND. A campaign built while Pro was active may
     * still hold a `donors_wall`; when the licence lapses that element must
     * survive an AI edit turn untouched, exactly as
     * {@see \Better_Payment\Lite\Campaign\MetaBox::enforce_pro_entitlement()}
     * restores its settings rather than deleting it. Making this method
     * entitlement-aware would make an "edit the title" turn — whose `set_layout`
     * echoes the whole page back — silently delete the user's Pro elements.
     *
     * Entitlement decides what we *offer* ({@see self::offerable_element_types()}),
     * never what we recognise.
     */
    public static function is_element_type( string $type ): bool {
        return in_array( $type, self::element_types(), true );
    }

    /**
     * Whether Pro is active on this install, read live per request.
     *
     * Never inferred from stored campaign data — same contract the builder and
     * MetaBox follow.
     */
    public static function pro_enabled(): bool {
        return (bool) apply_filters( 'better_payment/pro_enabled', false );
    }

    /**
     * Whether an element type is flagged Pro in the registry.
     *
     * The `pro` flag is present whether or not Pro is installed — Lite owns all
     * three schemas — so this asks "is this a Pro widget", not "is it locked".
     */
    public static function is_pro_element_type( string $type ): bool {
        $schema = ElementRegistry::get( $type );

        return null !== $schema && ! empty( $schema['pro'] );
    }

    /**
     * The element types this install may be offered: everything registered, minus
     * the Pro ones when Pro is inactive.
     *
     * A free install used to be handed `donors_wall`, `faq` and `video` in the
     * prompt under a rule reading "Only use the element types listed above" — so
     * the model reasonably used them, and they reached a page that has no
     * renderer for them and drops them to nothing. This is the list that decides
     * what the model is told exists.
     *
     * @return array<int, string>
     */
    public static function offerable_element_types(): array {
        $types = self::element_types();

        if ( self::pro_enabled() ) {
            return $types;
        }

        return array_values( array_filter(
            $types,
            static function ( $type ) {
                return ! self::is_pro_element_type( $type );
            }
        ) );
    }

    /**
     * Whether a type may be offered on this install (fed to the model in the
     * prompt schema).
     *
     * NOTE this is no longer the gate for *inserting* an element. A free user who
     * explicitly asks the AI for a Pro widget gets that exact widget dropped onto
     * the canvas as a locked preview — see
     * {@see \Better_Payment\Lite\AI\Operations\OperationValidator::validate_insert_block()}
     * and {@see self::locked_pro_types_in_operations()}. Offering (proactive) and
     * inserting (on explicit request) are now different questions.
     */
    public static function is_offerable_element_type( string $type ): bool {
        return in_array( $type, self::offerable_element_types(), true );
    }

    /**
     * The Pro element types this batch of operations puts onto the canvas that
     * the install is NOT entitled to — the widgets that will render as a locked
     * preview and stay inactive on the live page.
     *
     * This exists so the assistant can tell the user plainly, whatever the model
     * wrote in its own reply, that a widget they asked for is a Pro-only feature.
     * Reused by {@see \Better_Payment\Lite\AI\Services\AIService::run()}.
     *
     * Detection is deliberately "newly introduced", not merely "present". A
     * lapsed-Pro user's routine edit ("shorten the title") comes back as a
     * `set_layout` that echoes the whole page — leftover Pro widgets and all — and
     * announcing "FAQ is a Pro widget" on every such turn would be noise about
     * something the user did not just do. A type therefore counts only when an
     * `insert_block` creates it, or a `set_layout` adds one the current layout did
     * not already contain. Returns `[]` when Pro is active (nothing is locked).
     *
     * @param array $operations Validated operations (canonical `op` + type keys).
     * @param array $context    Trusted current state: [ 'layout' => [...] ].
     * @return array<int, string> Distinct Pro type keys, in first-seen order.
     */
    public static function locked_pro_types_in_operations( array $operations, array $context = [] ): array {
        if ( self::pro_enabled() ) {
            return [];
        }

        $existing = self::pro_types_in_columns( $context['layout']['columns'] ?? [] );
        $found    = [];

        foreach ( $operations as $operation ) {
            if ( ! is_array( $operation ) ) {
                continue;
            }
            $name = $operation['op'] ?? ( $operation['operation'] ?? '' );

            if ( 'insert_block' === $name ) {
                $type = self::resolve_element_type( is_string( $operation['type'] ?? null ) ? $operation['type'] : '' );
                if ( '' !== $type && self::is_pro_element_type( $type ) && ! in_array( $type, $found, true ) ) {
                    $found[] = $type;
                }
                continue;
            }

            if ( 'set_layout' === $name ) {
                foreach ( self::pro_types_in_columns( $operation['columns'] ?? [] ) as $type ) {
                    if ( ! in_array( $type, $existing, true ) && ! in_array( $type, $found, true ) ) {
                        $found[] = $type;
                    }
                }
            }
        }

        return $found;
    }

    /**
     * Distinct Pro element type keys present in a set of layout columns.
     *
     * @param mixed $columns
     * @return array<int, string>
     */
    private static function pro_types_in_columns( $columns ): array {
        $types = [];
        if ( ! is_array( $columns ) ) {
            return $types;
        }
        foreach ( $columns as $column ) {
            if ( ! is_array( $column ) || empty( $column['elements'] ) || ! is_array( $column['elements'] ) ) {
                continue;
            }
            foreach ( $column['elements'] as $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }
                $type = self::resolve_element_type( is_string( $element['type'] ?? null ) ? $element['type'] : '' );
                if ( '' !== $type && self::is_pro_element_type( $type ) && ! in_array( $type, $types, true ) ) {
                    $types[] = $type;
                }
            }
        }
        return $types;
    }

    /**
     * Pro element type keys registered on this install, regardless of entitlement.
     *
     * Used by the prompt builder to name the locked Pro widgets to a free install
     * (they are absent from {@see self::offerable_element_types()}), so the model
     * can honour an explicit request for one instead of substituting a free widget.
     *
     * @return array<int, string>
     */
    public static function pro_element_types(): array {
        return array_values( array_filter(
            self::element_types(),
            [ self::class, 'is_pro_element_type' ]
        ) );
    }

    /**
     * Common alias → canonical element type. Models frequently emit generic names
     * (heading, paragraph, image, button …) instead of the registry keys; mapping
     * them recovers the element instead of silently dropping it.
     *
     * @return array<string, string>
     */
    private static function type_aliases(): array {
        return apply_filters( 'better_payment/ai/element_type_aliases', [
            'heading' => 'campaign_title', 'title' => 'campaign_title', 'header' => 'campaign_title', 'headline' => 'campaign_title', 'campaigntitle' => 'campaign_title',
            'paragraph' => 'campaign_description', 'text' => 'campaign_description', 'story' => 'campaign_description', 'description' => 'campaign_description', 'content' => 'campaign_description', 'body' => 'campaign_description', 'richtext' => 'campaign_description', 'rich_text' => 'campaign_description',
            'image' => 'photo', 'img' => 'photo', 'picture' => 'photo', 'hero_image' => 'photo', 'heroimage' => 'photo', 'gallery' => 'photo',
            'button' => 'donation_form', 'cta' => 'donation_form', 'donate_button' => 'donation_form', 'donatebutton' => 'donation_form', 'donate' => 'donation_form', 'donatenow' => 'donation_form', 'donate_now' => 'donation_form',
            'progress' => 'progress_bar', 'progressbar' => 'progress_bar',
            'donation' => 'donate_amount', 'amounts' => 'donate_amount', 'donation_amounts' => 'donate_amount', 'suggested_amounts' => 'donate_amount', 'donation_amount' => 'donate_amount', 'tiers' => 'donate_amount',
            'summary' => 'campaign_summary', 'stats' => 'campaign_summary', 'statistics' => 'campaign_summary', 'campaignsummary' => 'campaign_summary',
            'share' => 'social_sharing', 'sharing' => 'social_sharing', 'social' => 'social_sharing', 'socialshare' => 'social_sharing', 'social_share' => 'social_sharing',
            'links' => 'social_links', 'sociallinks' => 'social_links', 'social_link' => 'social_links',
            'author' => 'organizer', 'creator' => 'organizer', 'organizer_card' => 'organizer', 'host' => 'organizer',
            // Pro elements. Aliased unconditionally — resolution is not permission,
            // and a free install simply never sees these types offered.
            'donors' => 'donors_wall', 'donorswall' => 'donors_wall', 'donor_list' => 'donors_wall', 'donorlist' => 'donors_wall', 'supporters' => 'donors_wall', 'recent_donors' => 'donors_wall', 'contributors' => 'donors_wall',
            'faqs' => 'faq', 'questions' => 'faq', 'accordion' => 'faq', 'q_and_a' => 'faq', 'qa' => 'faq', 'frequently_asked_questions' => 'faq',
            'youtube' => 'video', 'vimeo' => 'video', 'embed' => 'video', 'video_embed' => 'video', 'media' => 'video',
        ] );
    }

    /**
     * Resolve a possibly-aliased element type to a registered type, or '' when it
     * cannot be mapped.
     */
    public static function resolve_element_type( string $type ): string {
        $t = strtolower( trim( $type ) );
        if ( self::is_element_type( $t ) ) {
            return $t;
        }
        $norm    = preg_replace( '/[\s\-]+/', '_', $t );
        if ( self::is_element_type( $norm ) ) {
            return $norm;
        }
        $aliases = self::type_aliases();
        if ( isset( $aliases[ $t ] ) ) {
            return $aliases[ $t ];
        }
        if ( isset( $aliases[ $norm ] ) ) {
            return $aliases[ $norm ];
        }
        return '';
    }

    /**
     * Per-type alias → canonical setting key, so a mis-named content key
     * (e.g. `text` on a title) still lands in the right place.
     *
     * @return array<string, array<string, string>>
     */
    private static function setting_key_aliases(): array {
        return apply_filters( 'better_payment/ai/setting_key_aliases', [
            'campaign_title'       => [ 'text' => 'title', 'heading' => 'title', 'label' => 'title', 'headline' => 'title', 'name' => 'title' ],
            'campaign_description' => [ 'text' => 'content', 'body' => 'content', 'paragraph' => 'content', 'description' => 'content', 'story' => 'content', 'heading' => 'headline', 'title' => 'headline' ],
            'donation_form'        => [ 'text' => 'button_label', 'label' => 'button_label', 'cta' => 'button_label', 'button_text' => 'button_label', 'title' => 'button_label' ],
            'photo'                => [ 'url' => 'src', 'image' => 'src', 'source' => 'src', 'href' => 'src' ],
            'progress_bar'         => [ 'title' => 'headline', 'text' => 'headline' ],
            'campaign_summary'     => [ 'title' => 'headline', 'text' => 'headline' ],
            'donate_amount'        => [ 'title' => 'headline', 'text' => 'headline' ],
            'organizer'            => [ 'text' => 'description', 'bio' => 'description', 'name' => 'role_title', 'title' => 'role_title' ],
            'social_sharing'       => [ 'title' => 'headline', 'text' => 'headline' ],
            'social_links'         => [ 'title' => 'headline', 'text' => 'headline' ],
            'donors_wall'          => [ 'title' => 'headline', 'text' => 'headline', 'heading' => 'headline', 'limit' => 'number_to_show', 'count' => 'number_to_show' ],
            // `faq` names its heading `heading`, not `headline` — the one element
            // that breaks the pattern, so the reverse alias matters here.
            'faq'                  => [ 'title' => 'heading', 'text' => 'heading', 'headline' => 'heading', 'questions' => 'items', 'faqs' => 'items', 'list' => 'items' ],
            'video'                => [ 'src' => 'url', 'link' => 'url', 'video_url' => 'url', 'embed_url' => 'url' ],
        ] );
    }

    /**
     * Resolve a possibly-aliased setting key for a type. Returns the canonical key
     * when it is (or maps to) an allowed key, otherwise the original (which the
     * allowlist will then drop).
     */
    public static function resolve_setting_key( string $type, string $key ): string {
        if ( self::is_allowed_settings_key( $type, $key ) ) {
            return $key;
        }
        $map = self::setting_key_aliases();
        $k   = strtolower( trim( $key ) );
        if ( isset( $map[ $type ][ $k ] ) ) {
            return $map[ $type ][ $k ];
        }
        return $key;
    }

    /**
     * Campaign-level meta keys the AI is allowed to write.
     *
     * Deliberately a subset of {@see \Better_Payment\Lite\Campaign\MetaBox} keys:
     * layout (bpc_fields_layout), the form page id and the template key are
     * managed structurally, not by free-form AI meta writes.
     *
     * @return array<int, string>
     */
    public static function writable_meta_keys(): array {
        $keys = [
            'title',
            'bpc_goal_amount',
            'bpc_end_date',
            'bpc_status',
            'bpc_allow_custom_amount',
            'bpc_minimum_amount',
            'bpc_color_primary',
            'bpc_color_background',
            'bpc_css_class',
            'bpc_suggested_amounts',
        ];

        /**
         * Filter the campaign meta keys the AI layer may write.
         *
         * @param array<int, string> $keys
         */
        return apply_filters( 'better_payment/ai/writable_meta_keys', $keys );
    }

    public static function is_writable_meta_key( string $key ): bool {
        return in_array( $key, self::writable_meta_keys(), true );
    }

    /**
     * The full set of setting keys a given element type accepts.
     *
     * Built from the element's defaultSettings keys plus every control key in
     * its settingsSchema (recursing into collapsible `section` children). Synthetic
     * container keys (sections, notes — always prefixed with `_`) are excluded.
     *
     * @return array<int, string>
     */
    public static function allowed_settings_keys( string $type ): array {
        $schema = ElementRegistry::get( $type );
        if ( null === $schema ) {
            return [];
        }

        $keys = array_keys( (array) ( $schema['defaultSettings'] ?? [] ) );

        $collect = function ( $controls, &$out ) use ( &$collect ) {
            foreach ( (array) $controls as $control ) {
                if ( ! is_array( $control ) ) {
                    continue;
                }
                $ctype = $control['type'] ?? '';
                if ( in_array( $ctype, [ 'section', 'section_label', 'note' ], true ) ) {
                    if ( ! empty( $control['children'] ) ) {
                        $collect( $control['children'], $out );
                    }
                    continue;
                }
                if ( ! empty( $control['key'] ) && 0 !== strpos( (string) $control['key'], '_' ) ) {
                    $out[] = (string) $control['key'];
                }
                if ( ! empty( $control['children'] ) ) {
                    $collect( $control['children'], $out );
                }
            }
        };

        $collect( $schema['settingsSchema'] ?? [], $keys );

        return array_values( array_unique( $keys ) );
    }

    public static function is_allowed_settings_key( string $type, string $key ): bool {
        return in_array( $key, self::allowed_settings_keys( $type ), true );
    }

    /**
     * Enumerated values for a select/align control, or null when the key is free-form.
     *
     * @return array<int, string>|null
     */
    public static function enum_values( string $type, string $key ) {
        // `align` controls carry no options array but accept a fixed set.
        $control = self::find_control( $type, $key );
        if ( null === $control ) {
            return null;
        }
        if ( 'align' === ( $control['type'] ?? '' ) ) {
            return [ 'left', 'center', 'right' ];
        }
        if ( ! empty( $control['options'] ) && is_array( $control['options'] ) ) {
            $values = [];
            foreach ( $control['options'] as $option ) {
                if ( is_array( $option ) && array_key_exists( 'value', $option ) ) {
                    $values[] = (string) $option['value'];
                }
            }
            return $values;
        }
        return null;
    }

    /**
     * Locate a single control definition (recursing sections) for an element key.
     *
     * @return array|null
     */
    public static function find_control( string $type, string $key ) {
        $schema = ElementRegistry::get( $type );
        if ( null === $schema ) {
            return null;
        }

        $search = function ( $controls ) use ( &$search, $key ) {
            foreach ( (array) $controls as $control ) {
                if ( ! is_array( $control ) ) {
                    continue;
                }
                if ( ( $control['key'] ?? null ) === $key ) {
                    return $control;
                }
                if ( ! empty( $control['children'] ) ) {
                    $found = $search( $control['children'] );
                    if ( null !== $found ) {
                        return $found;
                    }
                }
            }
            return null;
        };

        return $search( $schema['settingsSchema'] ?? [] );
    }

    /**
     * A compact, prompt-friendly description of the whole builder capability set.
     *
     * Shape:
     *   [
     *     'layout_presets' => [...],
     *     'meta_keys'      => [...],
     *     'elements'       => [
     *       [ 'type' => 'campaign_title', 'label' => '...', 'guide' => '...',
     *         'settings' => [ 'title' => [ 'type' => 'text' ],
     *                         'align' => [ 'type' => 'align', 'enum' => ['left','center','right'] ] ] ],
     *       ...
     *     ],
     *   ]
     *
     * Only settable, non-container keys are described, keeping the prompt small.
     *
     * Two things shape this list beyond the raw registry:
     *  - Pro elements are omitted unless Pro is active. Describing a widget the
     *    install cannot render is not a harmless extra option: the rules tell the
     *    model these types are the ones it may use.
     *  - Each element carries a `guide` — what its content is actually *for*.
     *    A key list alone is not self-describing, and three elements sharing a
     *    key named `headline` otherwise get three copies of the same sentence.
     *
     * @return array<string, mixed>
     */
    public static function for_prompt(): array {
        $elements = [];

        foreach ( self::offerable_element_types() as $type ) {
            $elements[] = self::describe_element( $type );
        }

        return [
            'layout_presets' => self::layout_presets(),
            'meta_keys'      => self::writable_meta_keys(),
            'elements'       => $elements,
        ];
    }

    /**
     * Describe ONE element type: its label, its content guide, and every settable
     * key with that key's control type and enum.
     *
     * Extracted from {@see self::for_prompt()} so the whole-page schema and the
     * single-widget "target element" prompt section are built from the same code.
     * Two builders would drift, and a drift here is not cosmetic — the targeted
     * section exists precisely to tell the model which fields the widget in front
     * of the user actually has, so a stale copy would describe a widget that is
     * not the one being edited.
     *
     * Deliberately **entitlement-blind**, unlike {@see self::for_prompt()}. This
     * describes an element that already exists on the page, and the caller has
     * already decided it may be edited; filtering here would leave a lapsed
     * subscriber's Pro widget selectable but undescribable, so the model would be
     * asked to edit a widget it had been told nothing about. Entitlement decides
     * what is *offered* (`offerable_element_types()`) and what may be newly
     * *inserted* (`validate_insert_block()`) — never what is recognised.
     *
     * @param string $type Element type key.
     * @return array{type: string, label: string, guide: string, settings: array<string, array>}
     */
    public static function describe_element( string $type ): array {
        $schema = ElementRegistry::get( $type );
        $schema = is_array( $schema ) ? $schema : [];

        $settings = [];
        foreach ( self::allowed_settings_keys( $type ) as $key ) {
            $control = self::find_control( $type, $key );
            $entry   = [ 'type' => $control['type'] ?? 'text' ];
            $enum    = self::enum_values( $type, $key );
            if ( null !== $enum && ! empty( $enum ) ) {
                $entry['enum'] = $enum;
            }
            if ( ! empty( $control['label'] ) ) {
                $entry['label'] = (string) $control['label'];
            }
            $settings[ $key ] = $entry;
        }

        return [
            'type'     => $type,
            'label'    => (string) ( $schema['label'] ?? $type ),
            'guide'    => ElementContentGuide::for_type( $type ),
            'settings' => $settings,
        ];
    }
}
