<?php

namespace Better_Payment\Lite\AI\Services;

use Better_Payment\Lite\AI\AIManager;
use Better_Payment\Lite\AI\Layout\LayoutLibrary;
use Better_Payment\Lite\AI\Schema\CampaignSchema;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;
use Better_Payment\Lite\Campaign\Templates\CategoryRegistry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Full-campaign generation from a natural-language brief.
 *
 * Thin wrapper over {@see AIService} in 'generate' mode that also derives a
 * convenience { layout, meta } from the returned operations, so the /ai/generate
 * endpoint can hand the client a ready-to-apply campaign in addition to the raw
 * operations.
 */
class CampaignGenerator {

    /**
     * A full page layout (the large `set_layout` tool call plus its element JSON)
     * needs far more output than a conversational edit. Generation therefore uses
     * a generous token floor so the layout is not truncated and silently dropped,
     * leaving only the small meta calls applied.
     */
    const GENERATE_TOKEN_FLOOR = 8000;

    /**
     * @param string $brief
     * @param array  $context  Optional starting state.
     * @param string $category Campaign category slug the user explicitly chose in
     *                         the Smart Prompt Wizard. '' when they picked no
     *                         category tile (or wrote their own prompt), in which
     *                         case one is inferred from the brief.
     * @param array  $fields   Campaign fields the user filled in themselves, keyed
     *                         by meta key. A key present but empty means they were
     *                         asked and left it blank, which is enforced as "stays
     *                         empty" — see {@see UserFieldGuard}. Absent entirely
     *                         (the free-form prompt path) leaves the model's value.
     * @return array|\WP_Error { assistant_message, operations, layout, meta, usage, layout_generated, category }
     */
    public static function generate( string $brief, array $context = [], string $category = '', array $fields = [] ) {
        $config     = AIManager::config_for( AIManager::active_provider_id() );
        $max_tokens = max( self::GENERATE_TOKEN_FLOOR, (int) ( $config['max_tokens'] ?? 0 ) );
        $original   = $brief;

        // Detect the category once, from the ORIGINAL brief — before the
        // structure/palette instructions are appended below. Those append element
        // names ("photo"), column roles and colour words that are ours, not the
        // user's, and matching against them would classify the campaign by our own
        // boilerplate. It is returned as metadata; it no longer picks the artwork.
        $resolved = self::resolve_category( $category, $original );

        // Every AI-generated photo uses the single neutral default hero
        // (`ai-default-hero.svg`), for every category and template — never
        // category-specific artwork. This asset is always present, so a generated
        // photo can never fall back to an empty/broken `src` that renders as bare
        // alt text in the builder. The model cannot produce a real image URL, so
        // fill_default_photos() forces this onto every photo element.
        $photo_url = self::default_photo_url();

        // Two structural paths, and only ONE of them may impose a structure:
        //
        //  - The user pre-chose a layout in the template picker → build into that
        //    exact structure, and snap the model's output onto it afterwards. The
        //    structure is enforced because it is *the user's own choice*.
        //  - No layout chosen → the structure is derived from the user's BRIEF.
        //    Nothing is imposed, and `$selected` stays null so `attempt()` never
        //    runs `enforce_structure()`.
        //
        // This branch used to pick a blueprint from LayoutLibrary and treat it
        // exactly like a user selection: it appended "build this layout with
        // exactly these columns and widths", and then rewrote every column's
        // width from the blueprint regardless of what came back. A user who asked
        // for "a 50/50 column campaign" was answered with a 42%/58% page — their
        // instruction was first contradicted by a more specific competing one, and
        // then overwritten by code that ignored the model's answer entirely. The
        // model could not have complied even if it tried.
        $selected = self::selected_structure( $context );

        if ( null !== $selected ) {
            $brief .= self::structure_instruction( $selected );
        } else {
            $brief .= self::layout_instruction();
            $brief .= self::palette_instruction( LayoutLibrary::pick_palette( $original ) );
        }

        // Offer the Pro widgets on an entitled install — in both structural paths,
        // since a user who pre-picked a layout has paid for them just the same.
        $brief .= self::pro_elements_instruction();

        // Any video URL the model may keep has to come from the user, so read them
        // from the ORIGINAL brief — before our own instruction text is appended.
        $video_urls = self::urls_in( $original );

        // Attempt generation. If the page layout doesn't come through (empty or
        // dropped set_layout), retry once — forcing a tool call and nudging toward a
        // concise, complete layout — before giving up honestly.
        $result = self::attempt( $brief, $context, $selected, $max_tokens, false, $photo_url, $fields, $video_urls );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( null === $result['layout'] ) {
            $retry = self::attempt( $brief . self::retry_instruction(), $context, $selected, $max_tokens, true, $photo_url, $fields, $video_urls );
            if ( ! is_wp_error( $retry ) && null !== $retry['layout'] ) {
                $result = $retry;
            }
        }

        $layout = $result['layout'];
        $meta   = $result['meta'];

        // Name the campaign: if the model didn't set a title but the layout has a
        // campaign_title, adopt its text (so the builder isn't left "Campaign Name").
        if ( null !== $layout ) {
            $has_title_op = false;
            foreach ( $result['operations'] as $op ) {
                if ( is_array( $op ) && 'update_meta' === ( $op['op'] ?? '' ) && 'title' === ( $op['key'] ?? '' ) ) {
                    $has_title_op = true;
                    break;
                }
            }
            if ( ! $has_title_op && empty( $meta['title'] ) ) {
                $title = self::first_title( $layout );
                if ( '' !== $title ) {
                    $result['operations'][] = [ 'op' => 'update_meta', 'key' => 'title', 'value' => $title ];
                    $meta['title']          = $title;
                }
            }
        }

        $result['layout']           = $layout;
        $result['meta']             = $meta;
        $result['layout_generated'] = null !== $layout;
        // '' when the campaign matched no known category — the client can tell
        // "we used your category's artwork" from "we used the default hero".
        $result['category']         = $resolved;

        // Be honest: a generation that produced no page layout (only meta) left the
        // canvas empty. Do not report success — tell the user so they can retry.
        if ( null === $layout ) {
            $result['assistant_message'] = __(
                'I set up the campaign details, but the page layout did not come through — the response may have been too long. Please try generating again, optionally with a shorter brief.',
                'better-payment'
            );
        }

        return $result;
    }

    /**
     * One generation attempt: run the model, snap onto the chosen structure, and
     * extract { layout, meta }. An all-empty layout is dropped (returns layout null).
     *
     * @param string     $brief
     * @param array      $context
     * @param array|null $selected
     * @param int        $max_tokens
     * @param bool       $force      Force a tool call (tool_choice: required).
     * @param string     $photo_url  Image to force onto every photo element.
     * @param array      $fields     The user's own campaign fields (see generate()).
     * @param array      $video_urls URLs found in the user's brief — the only ones a
     *                               `video` element may keep (see guard_video_urls()).
     * @return array|\WP_Error
     */
    private static function attempt( string $brief, array $context, $selected, int $max_tokens, bool $force, string $photo_url = '', array $fields = [], array $video_urls = [] ) {
        $options = [ 'max_tokens' => $max_tokens ];
        if ( $force ) {
            $options['tool_choice'] = 'required';
        }

        $result = AIService::run( 'generate', $brief, $context, [], $options );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( null !== $selected ) {
            $result['operations'] = self::enforce_structure( $result['operations'], $selected );
        }

        // Give photo elements a real image (the AI can't produce one), so the
        // campaign looks finished — users swap it via the media library.
        $result['operations'] = self::fill_default_photos( $result['operations'], $photo_url );

        // Same reasoning as photos, sharper consequence: a model cannot know a real
        // video address either, and an invented one resolves to a stranger's video.
        $result['operations'] = self::guard_video_urls( $result['operations'], $video_urls );

        // The amount tiers and the button that submits them are one action: the
        // button must sit directly under the Donate Amount block. Structural, so
        // the prompt alone cannot guarantee it — this is the part that holds.
        $result['operations'] = self::enforce_donate_button_placement( $result['operations'] );

        // The user's own answers outrank the model's: a field they left blank
        // stays blank, and a value they gave is restored verbatim. Runs BEFORE
        // meta is derived below, so the operations and that meta always agree.
        $result['operations'] = UserFieldGuard::apply( $result['operations'], $fields );

        // Bake every generated widget's registry defaults into its settings, so a
        // generated element never ships missing a default the way a hand-dropped
        // one never does. The model routinely omits keys, or emits a blank
        // ("" / [] / null) for a value it has no real answer for — and a blank
        // must fall back to the default, not overwrite it. The visible casualty is
        // the Video widget: guard_video_urls() drops an invented url and the model
        // often sends an empty one, which then clobbered the default sample video
        // and rendered an empty embed. This is general — every widget, every
        // default. Runs AFTER guard_video_urls() (so an invented/empty url is gone
        // and the default now fills it) and AFTER fill_default_photos() (so the
        // forced, non-blank photo src is preserved).
        $result['operations'] = self::fill_element_defaults( $result['operations'] );

        $layout = null;
        $meta   = [];
        foreach ( $result['operations'] as $operation ) {
            $op = $operation['op'] ?? '';
            if ( 'set_layout' === $op ) {
                $layout = [ 'layout' => $operation['layout'], 'columns' => $operation['columns'] ];
            } elseif ( 'update_meta' === $op ) {
                $meta[ $operation['key'] ] = $operation['value'];
            } elseif ( 'set_colors' === $op ) {
                if ( isset( $operation['primary'] ) ) {
                    $meta['bpc_color_primary'] = $operation['primary'];
                }
                if ( isset( $operation['background'] ) ) {
                    $meta['bpc_color_background'] = $operation['background'];
                }
            } elseif ( 'set_donation_amounts' === $op ) {
                $meta['bpc_suggested_amounts'] = $operation['amounts'];
            }
        }

        // An all-empty layout must not wipe the canvas — drop the set_layout op.
        if ( null !== $layout ) {
            $total = 0;
            foreach ( $layout['columns'] as $col ) {
                $total += is_array( $col['elements'] ?? null ) ? count( $col['elements'] ) : 0;
            }
            if ( 0 === $total ) {
                $layout = null;
                $result['operations'] = array_values( array_filter(
                    $result['operations'],
                    static function ( $op ) {
                        return is_array( $op ) && 'set_layout' !== ( $op['op'] ?? '' );
                    }
                ) );
            }
        }

        $result['layout'] = $layout;
        $result['meta']   = $meta;
        return $result;
    }

    /**
     * Instruction appended to the retry brief when the first attempt produced no
     * usable layout.
     *
     * The type list is derived, never hardcoded. It used to be a literal string of
     * the ten free types, which meant a Pro install's retry told the model its Pro
     * widgets did not exist — and a retry is exactly the attempt that has to
     * produce the finished page. It also silently went stale the moment any
     * element was added to the registry.
     */
    private static function retry_instruction(): string {
        $types = implode( ', ', CampaignSchema::offerable_element_types() );

        return "\n\n" . sprintf(
            /* translators: %s: comma-separated list of allowed element type keys. */
            __( "Your previous attempt did not return a usable page layout. This time, call set_layout FIRST and keep every element's copy concise so the whole call fits in one response. Use ONLY these exact element type keys: %s.", 'better-payment' ),
            $types
        );
    }

    /**
     * Instruction naming the Pro widgets, appended only on an entitled install.
     *
     * The blueprint's per-column `pro` hints say *where* these go; this says what
     * they are and when they earn their place, so the model treats them as
     * deliberate additions rather than boxes to fill. Without it a Pro install
     * generated pages indistinguishable from a free one — the user paid for three
     * widgets the assistant never reached for.
     *
     * Two of the three carry a correctness constraint rather than a stylistic one,
     * repeated here because this is the text that actually invites their use:
     * Donors Wall renders live transaction data (writing donor names into a
     * fundraising page would be fabricating a record of who gave money), and Video
     * must not carry an invented URL.
     */
    private static function pro_elements_instruction(): string {
        if ( ! CampaignSchema::pro_enabled() ) {
            return '';
        }

        return "\n\n" . __( "PRO WIDGETS AVAILABLE — this site has Better Payment Pro, so you may also use `donors_wall`, `faq` and `video`. Use them where they genuinely strengthen THIS campaign, not as a checklist: `faq` when a donor would hesitate over how the money is spent or whether giving is safe; `donors_wall` beside the donation ask to show momentum; `video` in the hero only when the brief actually supplies a video URL. Do not write donor names, amounts or dates into `donors_wall` — it renders real donations by itself. Do not invent a `video` URL.", 'better-payment' );
    }

    /**
     * Settle on the campaign's category: the one the user explicitly chose, else
     * one inferred from the brief, else '' for a campaign that fits no category
     * we know ("a new category" — it gets the default hero).
     *
     * An explicit choice always wins. Only an *unknown* slug falls through to
     * inference, so a user who picked "Medical" never gets Environmental artwork
     * because their story happened to mention a flood.
     *
     * @param string $category Slug from the wizard; '' when none was picked.
     * @param string $brief    The user's brief, for inference.
     * @return string A category slug, or ''.
     */
    public static function resolve_category( string $category, string $brief ): string {
        if ( CategoryRegistry::exists( $category ) ) {
            return $category;
        }
        return CategoryRegistry::match_text( $brief );
    }

    /**
     * The default hero image for AI-generated campaigns — used for EVERY generated
     * photo, in every category. A neutral, on-brand illustration that suits any
     * fundraiser and reads as intentional (not a broken/empty box); users replace
     * it via the media library. Filterable so a site can point it at its own photo
     * globally.
     *
     * Every generated photo gets this one asset. Category-specific artwork was
     * retired here on purpose: a category photo can be missing on disk (a broken
     * `src` rendering as bare alt text), whereas `ai-default-hero.svg` always ships
     * with the plugin. Category detection ({@see self::resolve_category()}) remains
     * for metadata only.
     */
    public static function default_photo_url(): string {
        $default = defined( 'BETTER_PAYMENT_ASSETS' )
            ? BETTER_PAYMENT_ASSETS . '/img/campaign/ai-default-hero.svg'
            : '';
        /**
         * Filter the default image applied to AI-generated photo elements.
         *
         * @param string $url
         */
        return (string) apply_filters( 'better_payment/ai/default_photo_url', $default );
    }

    /**
     * Point every generated photo element at `$url` — always the default hero
     * ({@see self::default_photo_url()}), which is what {@see self::generate()}
     * passes.
     *
     * A text model cannot produce a real image URL, so any `src` it emits is a
     * hallucination that renders as a broken image. We therefore ALWAYS replace
     * the src (and reset the attachment id/sizes), while keeping the model's
     * descriptive `alt`. Real images come from the `generate_image` operation
     * instead, which runs client-side after generation.
     *
     * @param array  $operations
     * @param string $url Image URL; falls back to the default hero when '', and
     *                    only skips the pass if that is empty too (never blanks a
     *                    photo).
     * @return array
     */
    public static function fill_default_photos( array $operations, string $url = '' ): array {
        if ( '' === $url ) {
            $url = self::default_photo_url();
        }
        if ( '' === $url ) {
            return $operations;
        }

        foreach ( $operations as &$op ) {
            if ( ! is_array( $op ) ) {
                continue;
            }
            $name = $op['op'] ?? '';

            if ( 'set_layout' === $name && ! empty( $op['columns'] ) && is_array( $op['columns'] ) ) {
                foreach ( $op['columns'] as &$col ) {
                    if ( empty( $col['elements'] ) || ! is_array( $col['elements'] ) ) {
                        continue;
                    }
                    foreach ( $col['elements'] as &$el ) {
                        if ( is_array( $el ) && 'photo' === ( $el['type'] ?? '' ) ) {
                            $el['settings'] = self::default_photo_settings( $el['settings'] ?? [], $url );
                        }
                    }
                    unset( $el );
                }
                unset( $col );
            } elseif ( 'insert_block' === $name && 'photo' === ( $op['type'] ?? '' ) ) {
                $op['settings'] = self::default_photo_settings( $op['settings'] ?? [], $url );
            }
        }
        unset( $op );

        return $operations;
    }

    /**
     * Force a photo element's settings onto the default image, keeping the model's
     * alt text when it provided one.
     *
     * @param mixed  $settings
     * @param string $url
     * @return array
     */
    private static function default_photo_settings( $settings, string $url ): array {
        $settings = is_array( $settings ) ? $settings : [];
        $settings['src']       = $url;
        $settings['src_id']    = 0;
        $settings['src_sizes'] = [];
        if ( empty( $settings['alt'] ) ) {
            $settings['alt'] = __( 'Campaign image', 'better-payment' );
        }
        return $settings;
    }

    /**
     * Every http(s) URL appearing in a string.
     *
     * @return array<int, string>
     */
    public static function urls_in( string $text ): array {
        if ( ! preg_match_all( '#https?://[^\s<>"\'\)\]]+#i', $text, $matches ) ) {
            return [];
        }

        $urls = [];
        foreach ( $matches[0] as $url ) {
            // Trailing sentence punctuation is not part of the address.
            $urls[] = rtrim( (string) $url, '.,;:!?' );
        }

        return array_values( array_unique( array_filter( $urls ) ) );
    }

    /**
     * Drop a `video` element's `url` unless the user's brief actually contained it.
     *
     * The exact problem {@see self::fill_default_photos()} solves, with a worse
     * failure mode. A text model has no way to know a real video address, so asked
     * for a campaign video it produces a well-formed YouTube URL with an invented
     * ID — and that ID belongs to *something*. The result is an unrelated
     * stranger's video embedded on a fundraising page, presented as the campaign's
     * own appeal. That is materially worse than a broken image, because it looks
     * entirely intentional.
     *
     * A URL the user typed is theirs and is kept verbatim. Anything else is
     * removed, which lets the element's schema default apply — the same sample
     * video a user gets when they drop the Video widget by hand, so the outcome is
     * a widget in its ordinary unconfigured state rather than a false claim.
     *
     * Note this cannot be left to the prompt: `rules.php` already forbids inventing
     * values, and a prompt is a request. This is the part that holds.
     *
     * @param array $operations
     * @param array $allowed URLs found in the user's brief.
     * @return array
     */
    public static function guard_video_urls( array $operations, array $allowed = [] ): array {
        foreach ( $operations as &$op ) {
            if ( ! is_array( $op ) ) {
                continue;
            }
            $name = $op['op'] ?? '';

            if ( 'set_layout' === $name && ! empty( $op['columns'] ) && is_array( $op['columns'] ) ) {
                foreach ( $op['columns'] as &$col ) {
                    if ( empty( $col['elements'] ) || ! is_array( $col['elements'] ) ) {
                        continue;
                    }
                    foreach ( $col['elements'] as &$el ) {
                        if ( is_array( $el ) && 'video' === ( $el['type'] ?? '' ) ) {
                            $el['settings'] = self::guarded_video_settings( $el['settings'] ?? [], $allowed );
                        }
                    }
                    unset( $el );
                }
                unset( $col );
            } elseif ( 'insert_block' === $name && 'video' === ( $op['type'] ?? '' ) ) {
                $op['settings'] = self::guarded_video_settings( $op['settings'] ?? [], $allowed );
            }
        }
        unset( $op );

        return $operations;
    }

    /**
     * Remove an unvouched `url` from a video element's settings.
     *
     * Matching is exact against the brief's URLs, plus a prefix match so a user's
     * link that the model reproduced with extra query parameters (`&t=30s`) still
     * counts as theirs.
     *
     * @param mixed $settings
     * @param array $allowed
     * @return array
     */
    private static function guarded_video_settings( $settings, array $allowed ): array {
        $settings = is_array( $settings ) ? $settings : [];

        if ( ! isset( $settings['url'] ) ) {
            return $settings;
        }

        $url = trim( (string) $settings['url'] );
        if ( '' === $url ) {
            return $settings;
        }

        foreach ( $allowed as $candidate ) {
            $candidate = trim( (string) $candidate );
            if ( '' === $candidate ) {
                continue;
            }
            if ( $url === $candidate || 0 === strpos( $url, $candidate ) ) {
                return $settings;
            }
        }

        unset( $settings['url'] );

        return $settings;
    }

    /**
     * Merge each generated element's registry defaults UNDER its settings, so a
     * generated widget carries the same complete defaults a hand-dropped one does.
     *
     * A model value overrides the default only when it is a real value. A blank —
     * `null`, an empty/whitespace string, or an empty array — is treated as "not
     * provided" and the default is kept. This is the crucial difference from a
     * plain `array_merge`: the client's own merge (`freshIds`/`withDefaults`) fills
     * only keys the model *omitted*, so a key the model emitted as `""` (which the
     * model does whenever it has no real value) still clobbered the default. That
     * is why a generated Video rendered an empty embed instead of the sample video.
     *
     * Generation-only on purpose. This is NOT the "emptied means empty" contract
     * that governs edits: there is no prior user state on a freshly generated page,
     * so a blank from the model is a non-answer, never a deliberate clear. Edits
     * (CampaignEditor) never run through here, and templates apply client-side
     * without it — so Pro's deliberately-empty template video `url` is untouched.
     *
     * @param array $operations
     * @return array
     */
    public static function fill_element_defaults( array $operations ): array {
        foreach ( $operations as &$op ) {
            if ( ! is_array( $op ) ) {
                continue;
            }
            $name = $op['op'] ?? '';

            if ( 'set_layout' === $name && ! empty( $op['columns'] ) && is_array( $op['columns'] ) ) {
                foreach ( $op['columns'] as &$col ) {
                    if ( empty( $col['elements'] ) || ! is_array( $col['elements'] ) ) {
                        continue;
                    }
                    foreach ( $col['elements'] as &$el ) {
                        if ( is_array( $el ) && ! empty( $el['type'] ) ) {
                            $el['settings'] = self::with_element_defaults(
                                (string) $el['type'],
                                is_array( $el['settings'] ?? null ) ? $el['settings'] : []
                            );
                        }
                    }
                    unset( $el );
                }
                unset( $col );
            } elseif ( 'insert_block' === $name && ! empty( $op['type'] ) ) {
                $op['settings'] = self::with_element_defaults(
                    (string) $op['type'],
                    is_array( $op['settings'] ?? null ) ? $op['settings'] : []
                );
            }
        }
        unset( $op );

        return $operations;
    }

    /**
     * The registry defaults for `$type`, with any non-blank model value laid on top.
     *
     * @param string $type
     * @param array  $settings Model-supplied settings.
     * @return array
     */
    private static function with_element_defaults( string $type, array $settings ): array {
        $defaults = ElementRegistry::get_defaults( $type );
        if ( empty( $defaults ) ) {
            return $settings;
        }

        $merged = $defaults;
        foreach ( $settings as $key => $value ) {
            if ( self::is_blank_value( $value ) ) {
                continue; // keep the default
            }
            $merged[ $key ] = $value;
        }

        return $merged;
    }

    /**
     * Whether a model-supplied setting value counts as "not provided", so the
     * registry default should stand.
     *
     * `null`, empty/whitespace strings and empty arrays are blank. `0`, `false`
     * and `'0'` are meaningful (a `number_to_show` of 0, a toggle turned off) and
     * must override the default, so they are NOT blank.
     *
     * @param mixed $value
     * @return bool
     */
    private static function is_blank_value( $value ): bool {
        if ( null === $value ) {
            return true;
        }
        if ( is_string( $value ) ) {
            return '' === trim( $value );
        }
        if ( is_array( $value ) ) {
            return empty( $value );
        }

        return false;
    }

    /**
     * Keep the Donate Button (`donation_form`) directly beneath the Donate Amount
     * (`donate_amount`) block in every generated campaign.
     *
     * The amount tiers and the button that submits them are one action split
     * across two widgets: a donor picks an amount and the very next thing they
     * must see is the button that takes it. A model composing "for the strongest
     * narrative" will happily park the button in the opposite column, or three
     * elements further down — which reads as two unrelated controls and loses the
     * click. So on every `set_layout`, whenever a `donate_amount` block is present:
     *
     *  - an existing `donation_form` anywhere in the layout is moved to sit
     *    immediately after it, in the SAME column (pulled across columns if the
     *    model split them apart);
     *  - if the layout has no `donation_form` at all, one is inserted there with
     *    its registry defaults — every amount block must be followed by a button
     *    (the same requirement `generate.php` states, made real here).
     *
     * Structural only — element settings are never touched. Same reasoning as the
     * photo and video guards: the prompt asks for this, and this is what holds it.
     *
     * @param array $operations
     * @return array
     */
    public static function enforce_donate_button_placement( array $operations ): array {
        foreach ( $operations as &$op ) {
            if ( ! is_array( $op ) || 'set_layout' !== ( $op['op'] ?? '' ) ) {
                continue;
            }
            if ( empty( $op['columns'] ) || ! is_array( $op['columns'] ) ) {
                continue;
            }
            $op['columns'] = self::place_donate_button( $op['columns'] );
        }
        unset( $op );

        return $operations;
    }

    /**
     * Position (or create) the Donate Button so it immediately follows the first
     * Donate Amount block. No-op when the layout carries no `donate_amount`.
     *
     * @param array $columns
     * @return array
     */
    private static function place_donate_button( array $columns ): array {
        if ( null === self::find_element( $columns, 'donate_amount' ) ) {
            return $columns;
        }

        // Take any existing button out of the layout (wherever the model put it)
        // so it can be dropped back in the right place; build a default when the
        // layout has none.
        $existing = self::find_element( $columns, 'donation_form' );
        if ( null !== $existing ) {
            list( $bci, $bei ) = $existing;
            $button            = $columns[ $bci ]['elements'][ $bei ];
            array_splice( $columns[ $bci ]['elements'], $bei, 1 );
        } else {
            $button = self::default_donate_button();
        }

        // Re-find the amount block AFTER the removal — pulling the button out from
        // above it in the same column shifts its index — then drop the button in
        // directly beneath it.
        $amount_at = self::find_element( $columns, 'donate_amount' );
        if ( null === $amount_at ) {
            return $columns; // Defensive: donate_amount was never removed, so unreachable.
        }
        list( $aci, $aei ) = $amount_at;
        array_splice( $columns[ $aci ]['elements'], $aei + 1, 0, array( $button ) );

        return $columns;
    }

    /**
     * The [column index, element index] of the first element of `$type`, or null.
     *
     * @param array  $columns
     * @param string $type
     * @return array|null
     */
    private static function find_element( array $columns, string $type ) {
        foreach ( $columns as $ci => $col ) {
            $elements = is_array( $col['elements'] ?? null ) ? $col['elements'] : [];
            foreach ( $elements as $ei => $el ) {
                if ( is_array( $el ) && $type === ( $el['type'] ?? '' ) ) {
                    return [ $ci, $ei ];
                }
            }
        }
        return null;
    }

    /**
     * A Donate Button element carrying its registry defaults — the same widget a
     * user gets by dropping "Donate Button" onto the canvas by hand. No `id`: the
     * client assigns ids to every `set_layout` element via freshIds().
     *
     * @return array
     */
    private static function default_donate_button(): array {
        return [
            'type'     => 'donation_form',
            'settings' => ElementRegistry::get_defaults( 'donation_form' ),
        ];
    }

    /**
     * The first campaign_title element's title text in a layout, or ''.
     *
     * @param array $layout
     */
    private static function first_title( array $layout ): string {
        foreach ( $layout['columns'] ?? [] as $col ) {
            foreach ( $col['elements'] ?? [] as $el ) {
                if ( 'campaign_title' === ( $el['type'] ?? '' ) ) {
                    $title = trim( (string) ( $el['settings']['title'] ?? '' ) );
                    if ( '' !== $title ) {
                        return $title;
                    }
                }
            }
        }
        return '';
    }

    /**
     * The layout structure the user pre-selected, or null when the campaign is
     * a truly blank slate (no columns yet — the model may choose freely).
     *
     * @param array $context
     * @return array|null [ 'preset' => string, 'columns' => array ]
     */
    private static function selected_structure( array $context ) {
        $columns = $context['layout']['columns'] ?? null;
        if ( ! is_array( $columns ) || 0 === count( $columns ) ) {
            return null;
        }
        return [
            'preset'  => (string) ( $context['layout']['layout'] ?? '' ),
            'columns' => array_values( $columns ),
        ];
    }

    /**
     * Instruction appended to the brief when a layout is already selected. Lists
     * the exact columns (id + width + label) so the model returns that structure.
     *
     * @param array $selected
     */
    private static function structure_instruction( array $selected ): string {
        $lines = [];
        foreach ( $selected['columns'] as $i => $col ) {
            $lines[] = sprintf(
                '  %d) id "%s", width %s%s',
                $i + 1,
                (string) ( $col['id'] ?? ( 'col' . ( $i + 1 ) ) ),
                (string) ( $col['width'] ?? '100%' ),
                ! empty( $col['label'] ) ? ' — ' . $col['label'] : ''
            );
        }
        $preset = '' !== $selected['preset'] ? $selected['preset'] : 'multi-column';

        return "\n\n" . sprintf(
            /* translators: 1: layout preset, 2: column count, 3: bullet list of columns. */
            __( "IMPORTANT: The user has already chosen a %1\$s layout with exactly %2\$d column(s). Your set_layout MUST return exactly these columns, in this order, with these ids and widths — do not add, remove, or resize columns:\n%3\$s\nPlace appropriate elements into each column so the campaign fills the layout the user selected.", 'better-payment' ),
            $preset,
            count( $selected['columns'] ),
            implode( "\n", $lines )
        );
    }

    /**
     * Prompt section for the free-form path: the page structure comes from the
     * user's own brief, and from nothing else.
     *
     * This replaces `blueprint_instruction()`, which named a pre-built layout and
     * ordered the model to reproduce its exact column count and widths. That was
     * a second, more specific instruction competing with whatever the user had
     * actually asked for — and the user lost every time. "Create a 50/50 column
     * campaign" arrived alongside "build the Donation-first layout … width 42% …
     * width 58% … keep the column count and widths above", and the page came back
     * 42/58.
     *
     * Nothing here prescribes a shape. It tells the model to read the user's
     * words for structure and obey them literally when they are there, and to
     * compose a structure that suits *this* story when they are not. The trade is
     * deliberate: the blueprint library existed to keep generated campaigns from
     * looking cloned, and that variety now has to come from the brief rather than
     * from a rotation we control. A page that ignores what the user typed is not
     * worth the variety.
     */
    private static function layout_instruction(): string {
        return "\n\n" . __(
            "PAGE STRUCTURE — take it from the brief above, never from a default.\n"
            . "1. Read the user's brief for anything that describes the page's shape: a number of columns, a split or ratio (\"50/50\", \"two equal columns\", \"70/30\"), a sidebar and which side it is on, a full-width or single-column page, a hero band, or the order sections should appear in. If the brief says it, build EXACTLY that — the stated column count, in the stated order, with widths matching the stated ratio (\"50/50\" means two columns of width \"50%\" and \"50%\"). A structural instruction from the user is not a preference to balance against your own judgement; it is the requirement.\n"
            . "2. If the brief says nothing about structure, choose the one that best serves THIS campaign's story and audience, and vary it to suit the subject — an emergency appeal, a memorial and a school fundraiser should not come out identically composed. Do not default to a single house layout.\n"
            . '3. Either way, set each column\'s `width` explicitly as a percentage string, pick the `layout` preset that matches the column count, and give every column a short, meaningful `label`.',
            'better-payment'
        );
    }

    /**
     * Prompt section suggesting a primary brand colour — explicitly as a
     * **fallback**, never as an override.
     *
     * Same failure mode as the old blueprint instruction, one notch quieter: this
     * used to read "Set a primary brand colour near #E5484D", flatly, with no
     * deference to the brief. A user who asked for their charity's green got our
     * red, for the same reason they got 42/58 columns — a specific, imperative
     * instruction of ours arriving after theirs. A colour named in the brief is a
     * brand decision, and it is not ours to make.
     *
     * @param array $palette [ 'primary' => hex, 'mood' => string ]
     */
    private static function palette_instruction( array $palette ): string {
        if ( empty( $palette['primary'] ) ) {
            return '';
        }
        return "\n\n" . sprintf(
            /* translators: 1: hex colour, 2: mood description. */
            __( 'COLOUR — if the brief names a colour, a brand or an existing palette, use that and ignore this line. Only if it names none, set a primary brand colour near %1$s for a %2$s tone (call set_colors).', 'better-payment' ),
            (string) $palette['primary'],
            (string) ( $palette['mood'] ?? '' )
        );
    }

    /**
     * Snap any `set_layout` operation onto the user's selected structure so the
     * preset, column ids, labels and widths are always preserved — regardless of
     * what the model returned:
     *  - Column counts match → keep the model's per-column elements, force the
     *    column meta (id/label/width) from the selection.
     *  - Counts differ → rebuild the selected columns and redistribute all the
     *    model's elements across them proportionally to column width, so the
     *    layout is never left broken (the split case's classic failure).
     *
     * @param array $operations
     * @param array $selected
     * @return array
     */
    private static function enforce_structure( array $operations, array $selected ): array {
        $orig = $selected['columns'];

        foreach ( $operations as &$operation ) {
            if ( ! is_array( $operation ) || 'set_layout' !== ( $operation['op'] ?? '' ) ) {
                continue;
            }

            if ( '' !== $selected['preset'] ) {
                $operation['layout'] = $selected['preset'];
            }

            $model_cols = is_array( $operation['columns'] ?? null ) ? $operation['columns'] : [];

            if ( count( $model_cols ) === count( $orig ) ) {
                foreach ( $model_cols as $i => $col ) {
                    foreach ( [ 'id', 'label', 'width', 'widthTablet', 'widthMobile' ] as $key ) {
                        if ( isset( $orig[ $i ][ $key ] ) ) {
                            $model_cols[ $i ][ $key ] = $orig[ $i ][ $key ];
                        }
                    }
                }
                $operation['columns'] = $model_cols;
                continue;
            }

            // Column count mismatch — flatten every element the model produced and
            // redistribute them across the selected columns by width weight.
            $all = [];
            foreach ( $model_cols as $col ) {
                $elements = is_array( $col['elements'] ?? null ) ? $col['elements'] : [];
                foreach ( $elements as $el ) {
                    $all[] = $el;
                }
            }
            $operation['columns'] = self::distribute_elements( $all, $orig );
        }
        unset( $operation );

        return $operations;
    }

    /**
     * Distribute a flat list of elements across the given columns, weighted by
     * each column's width so wider columns receive more elements. Elements stay in
     * their original order and are placed contiguously.
     *
     * @param array $elements
     * @param array $columns  The selected columns (id/label/width, no elements).
     * @return array Columns with an `elements` array on each.
     */
    private static function distribute_elements( array $elements, array $columns ): array {
        $out = [];
        foreach ( $columns as $col ) {
            $col['elements'] = [];
            $out[]           = $col;
        }
        $n = count( $out );
        if ( 0 === $n ) {
            return $out;
        }

        // Cumulative width fractions → boundaries for contiguous assignment.
        $weights = [];
        $total   = 0.0;
        foreach ( $columns as $col ) {
            $w = (float) preg_replace( '/[^0-9.]/', '', (string) ( $col['width'] ?? '100' ) );
            if ( $w <= 0 ) {
                $w = 1.0;
            }
            $weights[] = $w;
            $total    += $w;
        }
        $bounds = [];
        $acc    = 0.0;
        foreach ( $weights as $w ) {
            $acc     += $w / $total;
            $bounds[] = $acc;
        }

        $count = count( $elements );
        foreach ( $elements as $index => $element ) {
            $ratio  = $count > 0 ? ( $index + 0.5 ) / $count : 0.0;
            $target = $n - 1;
            foreach ( $bounds as $i => $boundary ) {
                if ( $ratio <= $boundary ) {
                    $target = $i;
                    break;
                }
            }
            $out[ $target ]['elements'][] = $element;
        }

        return $out;
    }
}
