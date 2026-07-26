<?php

namespace Better_Payment\Lite\AI\Prompt;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\AI\AIManager;
use Better_Payment\Lite\AI\Operations\OperationRegistry;
use Better_Payment\Lite\AI\Schema\CampaignSchema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Builds the structured prompts sent to the model.
 *
 * The model receives (a) a system prompt describing its role, the campaign
 * schema, and the operation catalog, and (b) a user message carrying a compact
 * snapshot of the current campaign plus the user's instruction. Only what the
 * model needs is sent — never rendered HTML — keeping prompts small.
 *
 * Reusable templates live in Prompt/Templates/ and are loaded by name.
 *
 * @see CampaignSchema::for_prompt()
 * @see OperationRegistry::as_tools()
 */
class PromptBuilder {

    /**
     * Build the system prompt for a given mode.
     *
     * @param string $mode   'generate' | 'edit' | 'analyze' | 'brief'
     * @param string $custom Optional admin-configured system prompt appended at the end.
     * @param array  $target Optional focused element: [ 'id' => ..., 'type' => ..., 'settings' => [...] ].
     *                       When given, a "Target element" section pins the turn to that one widget.
     * @return string
     */
    public static function system_prompt( string $mode = 'edit', string $custom = '', array $target = [] ): string {
        // The brief writer produces prose, not operations. It has no use for the
        // element schema, the operation catalog or the operation-output rules —
        // handing it every widget schema would only tempt it to describe a
        // layout when its whole job is to sharpen a paragraph. Keep its prompt to
        // the template plus the site facts (currency, so it never writes a symbol).
        if ( 'brief' === $mode ) {
            $parts   = [];
            $parts[] = self::load_template( 'brief' );
            $parts[] = "## Site settings\n" . self::describe_site();
            if ( '' !== trim( $custom ) ) {
                $parts[] = "## Additional instructions\n" . trim( $custom );
            }
            /** This filter is documented above where the main prompt returns. */
            return apply_filters( 'better_payment/ai/system_prompt', implode( "\n\n", array_filter( $parts ) ), $mode );
        }

        $schema = CampaignSchema::for_prompt();

        $parts   = [];
        $parts[] = self::load_template( $mode );
        $parts[] = "## Site settings\n" . self::describe_site();
        $parts[] = "## Campaign schema you may produce\n" . self::describe_schema( $schema );

        $pro = self::describe_pro_widgets( $mode );
        if ( '' !== $pro ) {
            $parts[] = "## Pro widgets\n" . $pro;
        }

        $parts[] = "## Operations you may call\n" . self::describe_operations();

        $images = self::describe_image_support( $mode );
        if ( '' !== $images ) {
            $parts[] = "## Images\n" . $images;
        }

        $parts[] = self::load_template( 'rules' );

        $focus = self::describe_target( $target );
        if ( '' !== $focus ) {
            $parts[] = "## Target element — this turn edits ONLY this widget\n" . $focus;
        }

        if ( '' !== trim( $custom ) ) {
            $parts[] = "## Additional instructions\n" . trim( $custom );
        }

        /**
         * Filter the assembled AI system prompt.
         *
         * @param string $prompt
         * @param string $mode
         */
        return apply_filters( 'better_payment/ai/system_prompt', implode( "\n\n", array_filter( $parts ) ), $mode );
    }

    /**
     * Site facts the model would otherwise guess at.
     *
     * Currency is here because a model with no stated currency writes `$` — USD is
     * what it has seen most. On a EUR site the renderer then prints the real `€`
     * beside the model's invented `$`, and the campaign shows two currencies at
     * once. Telling it the answer is cheaper than detecting the symptom.
     *
     * Note this is context, not permission: `rules.php` still forbids writing a
     * currency symbol at all, because the renderer owns formatting. This exists so
     * the model can *reason* about amounts (a "€10,000 goal" reads differently from
     * a "$10,000" one), not so it can format them.
     *
     * @return string
     */
    private static function describe_site(): string {
        $currency = DB::get_settings( 'better_payment_settings_general_general_currency' );

        if ( ! is_string( $currency ) || '' === $currency ) {
            $currency = 'USD';
        }

        return "- Campaign currency: {$currency}. The renderer applies this itself — never write a"
            . ' currency symbol or an amount into any text or label setting.';
    }

    /**
     * State plainly that the three Pro widgets are live on this install.
     *
     * The whole-page schema already lists them once Pro is active (they become
     * offerable), but a bare type key in a long list reads as one more option.
     * This says they are fully supported, so a request like
     * "answer the questions donors keep asking" reaches `faq` instead of being
     * answered with another `campaign_description` paragraph.
     *
     * Never in 'analyze' mode. An analysis is a review of the campaign the owner
     * built, and `CampaignAnalyzer::scope_to_campaign()` drops `insert_block` and
     * `set_layout` outright — so a Pro section there could only produce report
     * prose recommending widgets the report is forbidden to add. Same reason that
     * method exists: a review is not an upsell.
     *
     * On a FREE install the same section flips purpose: it names the three Pro
     * widgets — which are deliberately absent from the schema above — so the model
     * can honour an explicit request for one ("add an FAQ") by inserting that exact
     * widget instead of quietly substituting a free one. It stays out of 'analyze'
     * for the same reason the Pro-active copy does: a review is not an upsell.
     *
     * @param string $mode 'generate' | 'edit' | 'analyze'
     * @return string Empty in 'analyze' mode, or when no Pro widgets are registered.
     */
    private static function describe_pro_widgets( string $mode ): string {
        if ( 'analyze' === $mode ) {
            return '';
        }

        if ( ! CampaignSchema::pro_enabled() ) {
            return self::describe_locked_pro_widgets();
        }

        $types = array_values(
            array_filter(
                CampaignSchema::offerable_element_types(),
                [ CampaignSchema::class, 'is_pro_element_type' ]
            )
        );

        if ( empty( $types ) ) {
            return '';
        }

        return 'Better Payment Pro is active on this site, so these widgets are fully available'
            . ' and fully supported — treat them exactly like the free ones: '
            . implode( ', ', $types ) . '.'
            . "\nUse one when it genuinely does a job no free widget does, not to fill space."
            . ' Their settings and content rules are listed with every other element above,'
            . ' and they must be followed just as strictly.';
    }

    /**
     * Free-install variant: name the locked Pro widgets and the one thing to do
     * with them.
     *
     * They are absent from the schema on purpose, so the model does not reach for
     * them unprompted (the free page has no renderer for them). But a user who asks
     * for one by name must get that widget, not a free stand-in — the plugin shows
     * it as a locked preview and the assistant explains the restriction. This
     * carves the narrow exception to the "only use the listed element types" rule.
     *
     * @return string Empty when no Pro widgets are registered.
     */
    private static function describe_locked_pro_widgets(): string {
        $types = CampaignSchema::pro_element_types();
        if ( empty( $types ) ) {
            return '';
        }

        $keys = implode( ', ', $types );

        return 'Better Payment Pro is NOT active on this site. `' . $keys . '` are locked Pro'
            . ' widgets: they are intentionally absent from the element list above, and you must NOT'
            . ' add one on your own initiative.'
            . "\nThe ONE exception: when the user EXPLICITLY asks for one of them by name (\"add an"
            . ' FAQ\", \"put a video here\", \"show a donors wall\"), you MUST honour it:'
            . "\n1. Insert that exact widget using its own type key (" . $keys . ') — via insert_block,'
            . ' or as an element inside set_layout. Never substitute a free widget for it, and never'
            . ' silently skip it.'
            . "\n2. Do not write any of its content settings; a locked widget only ever shows sample"
            . ' content.'
            . "\n3. In your natural-language reply, tell the user it is a Pro-only feature that will"
            . ' appear as a locked preview and needs a Better Payment Pro upgrade to go live.';
    }

    /**
     * How the model should handle a request for a picture — gated on the active
     * provider's image capability.
     *
     * The `generate_image` operation is always in the catalog, but nothing told
     * the model to *reach for it* when a user asks for one. So "regenerate the
     * campaign with an AI-generated image" was answered with a `photo` element
     * whose `src` the model invented or left blank: generation forces the default
     * hero over it, but the edit path deliberately does not (it must preserve the
     * user's real uploads), so the blank `src` survived and rendered as a broken
     * image. This section names the tool and the exact request words that should
     * trigger it, and how to target it without duplicating the photo.
     *
     * Capability-aware like {@see self::describe_pro_widgets()}: only an
     * image-capable, configured provider (OpenAI / Gemini) can fulfil
     * `generate_image`, so on a text-only provider we tell the model it cannot —
     * and to say so — rather than have it emit an op that 502s.
     *
     * Never in 'analyze' mode: an analysis applies nothing and
     * {@see CampaignAnalyzer::scope_to_campaign()} drops `generate_image` /
     * `insert_block` anyway, so promising image creation there would only mislead.
     *
     * @param string $mode 'generate' | 'edit' | 'analyze'
     * @return string
     */
    private static function describe_image_support( string $mode ): string {
        if ( 'analyze' === $mode ) {
            return '';
        }

        $provider     = AIManager::active_provider();
        $can_generate = null !== $provider && $provider->supports_images() && $provider->is_configured();

        if ( $can_generate ) {
            return 'You CAN create real images with the `generate_image` operation.'
                . "\n- When the user asks you to create, generate, add, or replace an image or photo"
                . ' — e.g. "add an AI-generated image", "regenerate with a new hero photo" — you MUST'
                . ' call `generate_image` with a vivid `prompt` describing the picture. Only that'
                . ' operation produces a real image; a `photo` element cannot, because you have no'
                . ' genuine image URL to give it.'
                . "\n- To change the picture in an existing photo element, pass its `element_id`. To"
                . ' add a new image, pass the `column_id` it belongs in — and do NOT also add a'
                . ' separate `photo` element for that same spot, or the campaign ends up with two.'
                . "\n- Never invent an image URL, and never leave a `photo` element with an empty or"
                . ' placeholder `src`; use `generate_image` for the picture instead.';
        }

        return 'You CANNOT generate images: the active AI provider does not support image generation'
            . ' (or is missing its API key). If the user asks you to create or add an image, do NOT'
            . ' add a `photo` element with an invented or empty `src` — it renders as a broken image.'
            . ' Instead, say plainly that image generation needs an image-capable provider (OpenAI or'
            . ' Gemini) selected under Settings → AI, and make no image change this turn.';
    }

    /**
     * Describe the one widget this turn is allowed to touch.
     *
     * The builder's per-element "AI quick edit" buttons used to convey their target
     * as nothing but a sentence in the user message ("… Target element id: …"), on
     * top of a system prompt describing every element type equally. Asked to
     * shorten one headline the model was told the schema of the whole page and
     * given no reason to believe the request was local — so it answered with copy
     * for whichever widget it found most interesting, and, because nothing scoped
     * the result, those edits were applied.
     *
     * This section names the widget, its purpose, the exact fields it has, and what
     * is currently in them. The prompt is the half that makes the output *relevant*;
     * {@see \Better_Payment\Lite\AI\Operations\OperationValidator::validate_batch()}
     * with a scope is the half that makes it *safe*. Neither replaces the other.
     *
     * @param array $target [ 'id' => string, 'type' => string, 'settings' => array ]
     * @return string Empty when no usable target was given.
     */
    private static function describe_target( array $target ): string {
        $id   = isset( $target['id'] ) ? (string) $target['id'] : '';
        $type = isset( $target['type'] ) ? (string) $target['type'] : '';

        if ( '' === $id || '' === $type || ! CampaignSchema::is_element_type( $type ) ) {
            return '';
        }

        $described = CampaignSchema::describe_element( $type );
        $settings  = isset( $target['settings'] ) && is_array( $target['settings'] ) ? $target['settings'] : [];

        // Only the keys this element actually accepts, so "current values" cannot
        // advertise a stale key the user could then ask the model to edit.
        $current = [];
        foreach ( $settings as $key => $value ) {
            if ( CampaignSchema::is_allowed_settings_key( $type, (string) $key ) ) {
                $current[ $key ] = $value;
            }
        }
        $current_json = wp_json_encode( self::trim_settings( $current ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        $lines   = [];
        $lines[] = sprintf( '- Widget: %s (`%s`)', $described['label'], $type );
        $lines[] = sprintf( '- Element id: `%s` — use this exact value as `element_id`.', $id );

        $guide = trim( (string) $described['guide'] );
        if ( '' !== $guide ) {
            $lines[] = '- What this widget\'s content is for: ' . $guide;
        }

        $lines[] = '- The only fields it has: ' . self::describe_setting_keys( $described['settings'] );
        $lines[] = "- What is in them right now:\n```json\n" . $current_json . "\n```";
        $lines[] = '';
        $lines[] = 'For this turn:';
        $lines[] = '1. The user\'s request is about THIS widget. Read it that way even if the wording is generic ("make it shorter" means this widget\'s copy).';
        $lines[] = '2. Reply with exactly one `update_block` on `' . $id . '` (or `replace_image` if it is an image). Do NOT call `set_layout`, `insert_block`, `delete_block`, `move_block`, `update_meta`, `set_colors` or `set_donation_amounts` — every one of them is discarded on this turn, so a change you put there simply will not happen.';
        $lines[] = '3. Send only the keys you are actually changing, and only keys from the list above. Anything else is dropped.';
        $lines[] = '4. Write for what this widget does. If it has no free-text field worth rewriting, change the setting that genuinely serves the request rather than inventing prose for a field that is a toggle or a number.';

        return implode( "\n", $lines );
    }

    /**
     * Build the user message: instruction + compact campaign context.
     *
     * @param string $instruction
     * @param array  $context [ 'layout' => [...], 'meta' => [...] ]
     * @return string
     */
    public static function user_message( string $instruction, array $context = [] ): string {
        $snapshot = self::compact_context( $context );
        $json     = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        return "Current campaign state:\n```json\n{$json}\n```\n\nUser request: " . trim( $instruction );
    }

    /**
     * Reduce builder state to only what the model needs.
     *
     * @param array $context
     * @return array
     */
    public static function compact_context( array $context ): array {
        $meta   = is_array( $context['meta'] ?? null ) ? $context['meta'] : [];
        $layout = is_array( $context['layout'] ?? null ) ? $context['layout'] : [];

        $columns = [];
        foreach ( (array) ( $layout['columns'] ?? [] ) as $column ) {
            if ( ! is_array( $column ) ) {
                continue;
            }
            $elements = [];
            foreach ( (array) ( $column['elements'] ?? [] ) as $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }
                $elements[] = [
                    'id'       => $element['id'] ?? '',
                    'type'     => $element['type'] ?? '',
                    'settings' => self::trim_settings( $element['settings'] ?? [] ),
                ];
            }
            $columns[] = [
                'id'       => $column['id'] ?? '',
                'width'    => $column['width'] ?? '',
                'elements' => $elements,
            ];
        }

        // Only keep meta keys the AI can act on.
        $kept_meta = [];
        foreach ( CampaignSchema::writable_meta_keys() as $key ) {
            if ( array_key_exists( $key, $meta ) ) {
                $kept_meta[ $key ] = $meta[ $key ];
            }
        }

        return [
            'layout' => [
                'layout'  => $layout['layout'] ?? '1-column',
                'columns' => $columns,
            ],
            'meta'   => $kept_meta,
        ];
    }

    /**
     * Drop long/verbose setting values from the context to save tokens.
     *
     * @param mixed $settings
     * @return array
     */
    private static function trim_settings( $settings ): array {
        if ( ! is_array( $settings ) ) {
            return [];
        }
        $out = [];
        foreach ( $settings as $key => $value ) {
            if ( is_string( $value ) && strlen( $value ) > 400 ) {
                $out[ $key ] = mb_substr( $value, 0, 400 ) . '…';
            } else {
                $out[ $key ] = $value;
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ describe

    /**
     * @param array $schema
     */
    /**
     * Serialise the schema, giving each element its key list AND a line saying
     * what that element's content is for.
     *
     * The `Content:` line is not decoration. Without it the model sees `headline`
     * on `progress_bar`, `campaign_summary`, `social_sharing` and `donors_wall`
     * and writes one interchangeable sentence into all four, because a key name is
     * not a brief. Each widget has a distinct job on the page and the copy has to
     * reflect it — see {@see \Better_Payment\Lite\AI\Schema\ElementContentGuide}.
     *
     * @param array $schema
     */
    private static function describe_schema( array $schema ): string {
        $lines   = [];
        $lines[] = 'Layout presets: ' . implode( ', ', $schema['layout_presets'] ) . '.';
        $lines[] = 'Writable campaign meta keys: ' . implode( ', ', $schema['meta_keys'] ) . '.';
        $lines[] = 'Element types, their settings, and what each one\'s content is for.';
        $lines[] = 'Write content that fits the widget you are filling — these are different jobs on the page, not the same paragraph repeated:';
        foreach ( $schema['elements'] as $element ) {
            $lines[] = sprintf(
                '- %s (%s) — settings: %s',
                $element['type'],
                (string) ( $element['label'] ?? $element['type'] ),
                self::describe_setting_keys( (array) ( $element['settings'] ?? [] ) )
            );

            $guide = trim( (string) ( $element['guide'] ?? '' ) );
            if ( '' !== $guide ) {
                $lines[] = '  Content: ' . $guide;
            }
        }
        return implode( "\n", $lines );
    }

    /**
     * Render one element's settings map as `key (kind)` pairs.
     *
     * The control kind is not decoration either. `CampaignSchema::describe_element()`
     * has always computed it and this method used to throw it away, emitting a bare
     * key list — so `show_days`, `columns` and `headline` reached the model looking
     * identical, and nothing in the prompt said that two of them are not places to
     * write a sentence. Enumerated keys additionally list their accepted values,
     * which is the only way the model can know `layout` takes `list|grid|ticker`
     * rather than a description of a layout.
     *
     * @param array<string, array> $settings key => [ 'type' => ..., 'enum' => [...] ]
     * @return string
     */
    private static function describe_setting_keys( array $settings ): string {
        $keys = [];
        foreach ( $settings as $key => $spec ) {
            if ( ! empty( $spec['enum'] ) ) {
                $keys[] = $key . ' (' . implode( '|', (array) $spec['enum'] ) . ')';
                continue;
            }

            $keys[] = $key . ' (' . ( '' !== (string) ( $spec['type'] ?? '' ) ? (string) $spec['type'] : 'text' ) . ')';
        }
        return implode( ', ', $keys );
    }

    private static function describe_operations(): string {
        $lines = [];
        foreach ( OperationRegistry::get_all() as $name => $def ) {
            $params = [];
            foreach ( (array) ( $def['params'] ?? [] ) as $pkey => $pspec ) {
                $params[] = $pkey . ( ! empty( $pspec['required'] ) ? '*' : '' );
            }
            $lines[] = sprintf( '- %s(%s) — %s', $name, implode( ', ', $params ), $def['summary'] ?? '' );
        }
        return implode( "\n", $lines ) . "\n(* = required. Call operations as tools; do not emit raw HTML.)";
    }

    /**
     * Load a prompt template file by name from Prompt/Templates/.
     */
    private static function load_template( string $name ): string {
        $file = __DIR__ . '/Templates/' . sanitize_file_name( $name ) . '.php';
        if ( ! file_exists( $file ) ) {
            return '';
        }
        $content = include $file;
        return is_string( $content ) ? $content : '';
    }
}
