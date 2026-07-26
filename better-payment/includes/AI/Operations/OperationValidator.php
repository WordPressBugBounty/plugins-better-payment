<?php

namespace Better_Payment\Lite\AI\Operations;

use Better_Payment\Lite\AI\Schema\CampaignSchema;
use Better_Payment\Lite\AI\Support\DateGuard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validates and sanitises AI-produced operations before they reach the client.
 *
 * This is the security boundary for AI output. Model responses are never
 * trusted: unknown operation types are dropped, unknown element types and
 * setting keys are stripped, values are coerced/escaped by their control type,
 * and enum-constrained values that fall outside the allowlist are removed.
 *
 * The wire shape of one operation is `{ "op": "<name>", ...params }`.
 *
 * @see OperationRegistry              Operation catalog / param shapes.
 * @see CampaignSchema                 Element + meta allowlists.
 */
class OperationValidator {

    /**
     * Operations a single-widget turn is allowed to produce.
     *
     * `update_block` is the edit itself; `replace_image` is the same edit for a
     * photo. Everything else either restructures the page or changes campaign-wide
     * state, neither of which is what "rewrite this headline" asked for.
     *
     * @var array<int, string>
     */
    const SCOPED_OPERATIONS = [ 'update_block', 'replace_image' ];

    /**
     * Validate a batch of operations, returning only the sanitised, valid ones.
     *
     * @param mixed $operations Raw operations from the model.
     * @param array $context    Optional trusted current state: [ 'layout' => [...], 'meta' => [...] ].
     * @param array $scope      Optional [ 'element_id' => string ]. When set, the turn was
     *                          launched against one selected widget and may only change that
     *                          widget — see {@see self::in_scope()}.
     * @return array<int, array> Sanitised operations, invalid ones removed.
     */
    public static function validate_batch( $operations, array $context = [], array $scope = [] ): array {
        if ( ! is_array( $operations ) ) {
            return [];
        }

        $id_type_map = self::build_id_type_map( $context['layout'] ?? [] );
        $scope_id    = isset( $scope['element_id'] ) ? trim( (string) $scope['element_id'] ) : '';
        $clean       = [];

        foreach ( $operations as $operation ) {
            $valid = self::validate_one( $operation, $id_type_map );
            if ( null === $valid ) {
                continue;
            }
            if ( '' !== $scope_id && ! self::in_scope( $valid, $scope_id ) ) {
                continue;
            }
            $clean[] = $valid;
        }

        return $clean;
    }

    /**
     * Whether a validated operation stays inside a single-widget turn.
     *
     * The builder's per-element AI buttons act on the widget the user has selected.
     * Before this existed, the only thing tying the model to that widget was a
     * sentence in the prompt naming its id — so a "shorten this" on one headline
     * could come back as a `set_layout` rebuilding the page, or an `update_meta`
     * changing the fundraising goal, and it would be applied. The user asked to
     * edit one widget; anything else is a change they did not request and did not
     * see coming, on a page they may have spent an hour arranging.
     *
     * Dropping is the right failure: the widget stays as it was, which is the same
     * outcome as the model declining, and the free-form AI panel remains available
     * for genuinely page-wide requests.
     *
     * @param array  $operation Already-validated operation.
     * @param string $scope_id  The element id this turn is pinned to.
     * @return bool
     */
    private static function in_scope( array $operation, string $scope_id ): bool {
        $name = isset( $operation['op'] ) ? (string) $operation['op'] : '';

        if ( ! in_array( $name, self::SCOPED_OPERATIONS, true ) ) {
            return false;
        }

        $element_id = isset( $operation['element_id'] ) ? (string) $operation['element_id'] : '';

        return $element_id === $scope_id;
    }

    /**
     * Validate a single operation.
     *
     * @param mixed $operation
     * @param array<string, string> $id_type_map element_id => type
     * @return array|null Sanitised operation or null if invalid.
     */
    public static function validate_one( $operation, array $id_type_map = [] ) {
        if ( ! is_array( $operation ) ) {
            return null;
        }

        // Accept `op` (canonical) or `type`/`operation` as aliases.
        $name = $operation['op'] ?? ( $operation['operation'] ?? ( $operation['type'] ?? '' ) );
        $name = is_string( $name ) ? $name : '';

        if ( ! OperationRegistry::exists( $name ) ) {
            return null;
        }

        switch ( $name ) {
            case 'set_layout':
                return self::validate_set_layout( $operation );
            case 'insert_block':
                return self::validate_insert_block( $operation );
            case 'update_block':
                return self::validate_update_block( $operation, $id_type_map );
            case 'delete_block':
                return self::require_element_id( 'delete_block', $operation, $id_type_map );
            case 'move_block':
                return self::validate_move_block( $operation, $id_type_map );
            case 'update_meta':
                return self::validate_update_meta( $operation );
            case 'set_colors':
                return self::validate_set_colors( $operation );
            case 'set_donation_amounts':
                return self::validate_donation_amounts( $operation );
            case 'replace_image':
                return self::validate_replace_image( $operation, $id_type_map );
            case 'generate_image':
                return self::validate_generate_image( $operation );
        }

        return null;
    }

    // ------------------------------------------------------------------ per-op

    private static function validate_set_layout( array $op ) {
        $layout  = $op['layout'] ?? '';
        // Some models JSON-encode the nested columns array; accept that too.
        $columns = self::maybe_decode_json( $op['columns'] ?? [] );

        if ( ! in_array( $layout, CampaignSchema::layout_presets(), true ) ) {
            $layout = '1-column';
        }
        if ( ! is_array( $columns ) ) {
            return null;
        }

        $clean_cols = [];
        foreach ( $columns as $column ) {
            if ( ! is_array( $column ) ) {
                continue;
            }
            $clean_cols[] = [
                'id'       => self::sanitize_id( $column['id'] ?? '' ),
                'label'    => sanitize_text_field( $column['label'] ?? '' ),
                'width'    => self::sanitize_width( $column['width'] ?? '100%' ),
                'elements' => self::sanitize_elements( $column['elements'] ?? [] ),
            ];
        }

        if ( empty( $clean_cols ) ) {
            return null;
        }

        return [
            'op'      => 'set_layout',
            'layout'  => $layout,
            'columns' => $clean_cols,
        ];
    }

    /**
     * `insert_block` creates a NEW element. Any registered type is accepted —
     * including a Pro widget on a free install.
     *
     * A free user who explicitly asks the AI for FAQ, Video or Donors Wall gets
     * that exact widget, not a free substitute: it lands on the canvas as the
     * locked preview a palette drop already produces, its settings are reset to the
     * schema defaults on save by
     * {@see \Better_Payment\Lite\Campaign\MetaBox::enforce_pro_entitlement()}, and
     * the builder renders it through {@see \Better_Payment\Lite\Campaign\Elements\ProElementPreview}.
     * On the live page it renders nothing — the accepted, existing free-plugin
     * behaviour for an unlicensed Pro widget. The assistant tells the user it is
     * Pro-only via {@see CampaignSchema::locked_pro_types_in_operations()}.
     *
     * This used to refuse an unentitled Pro type outright, which left the two
     * insertion paths inconsistent — `set_layout` kept Pro elements (see
     * {@see self::sanitize_elements()}) while `insert_block` dropped them — so
     * "add an FAQ" silently did nothing or got answered with a free widget. Only a
     * truly unknown type is rejected now.
     */
    private static function validate_insert_block( array $op ) {
        $type = CampaignSchema::resolve_element_type( is_string( $op['type'] ?? null ) ? $op['type'] : '' );
        if ( '' === $type ) {
            return null;
        }

        $clean = [
            'op'       => 'insert_block',
            'type'     => $type,
            'settings' => self::sanitize_settings( $type, $op['settings'] ?? [] ),
        ];
        if ( isset( $op['column_id'] ) && is_string( $op['column_id'] ) && '' !== $op['column_id'] ) {
            $clean['column_id'] = self::sanitize_id( $op['column_id'] );
        }
        if ( isset( $op['index'] ) && is_numeric( $op['index'] ) ) {
            $clean['index'] = max( 0, (int) $op['index'] );
        }
        return $clean;
    }

    private static function validate_update_block( array $op, array $id_type_map ) {
        $element_id = is_string( $op['element_id'] ?? null ) ? $op['element_id'] : '';
        if ( '' === $element_id ) {
            return null;
        }
        // If we know the element's type from context, sanitise settings against it.
        // Otherwise keep a shallow-sanitised object; the client executor re-checks
        // against live state and drops unknown keys.
        $type     = $id_type_map[ $element_id ] ?? '';
        $settings = '' !== $type
            ? self::sanitize_settings( $type, $op['settings'] ?? [] )
            : self::shallow_sanitize_settings( $op['settings'] ?? [] );

        if ( empty( $settings ) ) {
            return null;
        }

        return [
            'op'         => 'update_block',
            'element_id' => self::sanitize_id( $element_id ),
            'settings'   => $settings,
        ];
    }

    private static function validate_move_block( array $op, array $id_type_map ) {
        $element_id   = is_string( $op['element_id'] ?? null ) ? $op['element_id'] : '';
        $to_column_id = is_string( $op['to_column_id'] ?? null ) ? $op['to_column_id'] : '';
        if ( '' === $element_id || '' === $to_column_id ) {
            return null;
        }
        $clean = [
            'op'           => 'move_block',
            'element_id'   => self::sanitize_id( $element_id ),
            'to_column_id' => self::sanitize_id( $to_column_id ),
        ];
        if ( isset( $op['index'] ) && is_numeric( $op['index'] ) ) {
            $clean['index'] = max( 0, (int) $op['index'] );
        }
        return $clean;
    }

    private static function validate_update_meta( array $op ) {
        $key = is_string( $op['key'] ?? null ) ? $op['key'] : '';
        if ( ! CampaignSchema::is_writable_meta_key( $key ) ) {
            return null;
        }

        // An AI-produced end date is always in the future, in every mode. This is
        // the one place every model-produced operation passes through, whatever
        // the route — generation, a conversational edit, an applied analysis
        // suggestion — which is why the rule lives here and not in any one
        // service. A past or unparseable date drops the whole operation, leaving
        // the field unset: no deadline is a valid campaign, an elapsed one is a
        // page that opens already closed. See {@see DateGuard}.
        if ( 'bpc_end_date' === $key ) {
            $date = DateGuard::usable_end_date( $op['value'] ?? '' );
            if ( '' === $date ) {
                return null;
            }
            return [
                'op'    => 'update_meta',
                'key'   => $key,
                'value' => $date,
            ];
        }

        return [
            'op'    => 'update_meta',
            'key'   => $key,
            'value' => self::sanitize_meta_value( $key, $op['value'] ?? '' ),
        ];
    }

    private static function validate_set_colors( array $op ) {
        $clean = [ 'op' => 'set_colors' ];
        if ( isset( $op['primary'] ) ) {
            $primary = sanitize_hex_color( (string) $op['primary'] );
            if ( $primary ) {
                $clean['primary'] = $primary;
            }
        }
        if ( isset( $op['background'] ) ) {
            $bg = sanitize_hex_color( (string) $op['background'] );
            if ( $bg ) {
                $clean['background'] = $bg;
            }
        }
        if ( ! isset( $clean['primary'] ) && ! isset( $clean['background'] ) ) {
            return null;
        }
        return $clean;
    }

    private static function validate_donation_amounts( array $op ) {
        $amounts = $op['amounts'] ?? [];
        if ( ! is_array( $amounts ) ) {
            return null;
        }
        $clean = [];
        $i     = 0;
        foreach ( $amounts as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $i++;
            $clean[] = [
                'id'          => 'sa_' . $i,
                'amount'      => (string) floatval( $item['amount'] ?? 0 ),
                'description' => sanitize_text_field( $item['description'] ?? '' ),
                'is_default'  => ! empty( $item['is_default'] ),
            ];
        }
        if ( empty( $clean ) ) {
            return null;
        }
        return [
            'op'      => 'set_donation_amounts',
            'amounts' => $clean,
        ];
    }

    private static function validate_replace_image( array $op, array $id_type_map ) {
        $element_id = is_string( $op['element_id'] ?? null ) ? $op['element_id'] : '';
        $src        = esc_url_raw( (string) ( $op['src'] ?? '' ) );
        if ( '' === $element_id || '' === $src ) {
            return null;
        }
        return [
            'op'         => 'replace_image',
            'element_id' => self::sanitize_id( $element_id ),
            'src'        => $src,
            'src_id'     => isset( $op['src_id'] ) ? absint( $op['src_id'] ) : 0,
            'src_sizes'  => is_array( $op['src_sizes'] ?? null ) ? $op['src_sizes'] : [],
            'alt'        => sanitize_text_field( $op['alt'] ?? '' ),
        ];
    }

    private static function validate_generate_image( array $op ) {
        $prompt = sanitize_textarea_field( (string) ( $op['prompt'] ?? '' ) );
        if ( '' === trim( $prompt ) ) {
            return null;
        }
        $clean = [
            'op'     => 'generate_image',
            'prompt' => $prompt,
        ];
        if ( ! empty( $op['element_id'] ) && is_string( $op['element_id'] ) ) {
            $clean['element_id'] = self::sanitize_id( $op['element_id'] );
        }
        if ( ! empty( $op['column_id'] ) && is_string( $op['column_id'] ) ) {
            $clean['column_id'] = self::sanitize_id( $op['column_id'] );
        }
        return $clean;
    }

    private static function require_element_id( string $name, array $op, array $id_type_map ) {
        $element_id = is_string( $op['element_id'] ?? null ) ? $op['element_id'] : '';
        if ( '' === $element_id ) {
            return null;
        }
        return [
            'op'         => $name,
            'element_id' => self::sanitize_id( $element_id ),
        ];
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Sanitise a full list of raw elements (used by set_layout).
     *
     * Note what this deliberately does NOT do: it does not drop Pro elements on a
     * non-Pro install, even though {@see self::validate_insert_block()} refuses to
     * create one. `set_layout` is a whole-page replacement, and an edit turn as
     * ordinary as "shorten the title" comes back with the entire page echoed in
     * it. On a lapsed licence, filtering here would turn that turn into a silent
     * deletion of every Pro element the user had built while subscribed — the same
     * destruction {@see \Better_Payment\Lite\Campaign\MetaBox::enforce_pro_entitlement()}
     * exists to prevent by restoring settings rather than removing elements.
     *
     * Entitlement is enforced where it is safe to enforce it: we do not offer Pro
     * types in the prompt, we refuse to insert new ones, and MetaBox neuters their
     * settings on save. A Pro element that survives here renders as nothing on the
     * frontend, which is recoverable; deleting the user's work is not.
     *
     * @return array<int, array>
     */
    private static function sanitize_elements( $elements ): array {
        $elements = self::maybe_decode_json( $elements );
        if ( ! is_array( $elements ) ) {
            return [];
        }
        $clean = [];
        foreach ( $elements as $element ) {
            $element = self::maybe_decode_json( $element );
            if ( ! is_array( $element ) ) {
                continue;
            }
            // Resolve aliased type names (heading → campaign_title, etc.) so a
            // mis-named element is recovered rather than dropped.
            $type = CampaignSchema::resolve_element_type( is_string( $element['type'] ?? null ) ? $element['type'] : '' );
            if ( '' === $type ) {
                continue;
            }
            $entry = [
                'type'     => $type,
                'settings' => self::sanitize_settings( $type, $element['settings'] ?? [] ),
            ];
            // Preserve an incoming id only if it looks builder-generated; otherwise
            // the client mints a fresh one on apply.
            if ( ! empty( $element['id'] ) && is_string( $element['id'] ) ) {
                $entry['id'] = self::sanitize_id( $element['id'] );
            }
            $clean[] = $entry;
        }
        return $clean;
    }

    /**
     * Label settings the renderer appends a live value to.
     *
     * These are **prefixes**: `RendererService` prints `$goal_label . ' ' .
     * $currency_symbol . $goal` and `$donate_label . ' ' . $progress . '%'`. A model
     * handed a free-text field called "Goal Label:" writes the whole phrase —
     * `"Our Goal: $5,000"` — and the page then reads `"Our Goal: $5,000 €5,000"`:
     * the amount twice, in two currencies, because the model guesses USD while the
     * site is EUR. Worse, the model's number is invented, so editing the real goal
     * later leaves the label contradicting it rather than merely repeating it.
     *
     * This list is deliberately NOT "every key ending in `_label`".
     * `donation_form`'s `button_label` is echoed on its own, so "Donate $50" is a
     * legitimate CTA there; stripping it would be the bug, not the fix. Only labels
     * the renderer follows with a value belong here — check the renderer before
     * adding one.
     *
     * @return array<string, string[]> element type => label setting keys.
     */
    private static function value_suffixed_labels(): array {
        /**
         * Filter the label settings that must not contain a value.
         *
         * @param array<string, string[]> $map Element type => setting keys.
         */
        return apply_filters(
            'better_payment/ai/value_suffixed_labels',
            [
                'progress_bar' => [ 'goal_label', 'donate_label' ],
            ]
        );
    }

    /**
     * Strip an embedded amount / percentage / currency symbol from a label.
     *
     * The prompt tells the model not to write these ({@see Prompt/Templates/rules.php})
     * and gives it the site currency, but a prompt is a request. This is the part
     * that holds.
     *
     * Conservative on purpose — it removes only what unambiguously reads as a
     * value: a currency symbol with digits, comma-grouped thousands, a percentage,
     * or a stray currency symbol. A bare short number is left alone, because
     * "Season 2024 Goal" is a real label and mangling an author's text is worse
     * than the duplication this fixes. `\p{Sc}` covers $ € £ ¥ ₹ and the rest.
     *
     * @param string $label
     * @return string Cleaned label; '' if nothing survived (caller drops it and the
     *                schema default applies).
     */
    public static function strip_value_from_label( string $label ): string {
        $patterns = [
            '/\p{Sc}\s*\d[\d.,]*/u',                 // $5,000 / € 5.000
            '/\d{1,3}(?:,\d{3})+(?:\.\d+)?/u',       // 5,000 — grouped thousands
            '/\d[\d.,]*\s*%/u',                      // 40%
            '/\p{Sc}/u',                             // stray symbol
        ];

        $clean = (string) preg_replace( $patterns, '', $label );

        // Collapse the whitespace the removals left behind, then tidy trailing
        // separators — "Our Goal: $5,000" must not come back as "Our Goal: ".
        $clean = (string) preg_replace( '/\s{2,}/u', ' ', $clean );
        $clean = trim( $clean );
        $clean = (string) preg_replace( '/[\s\-–—]+$/u', '', $clean );

        return trim( $clean );
    }

    /**
     * Apply {@see self::strip_value_from_label()} where the type says it applies.
     *
     * @param string $type Element type, or '' when unknown.
     * @param string $key  Canonical setting key.
     * @param mixed  $value
     * @return mixed
     */
    private static function guard_label_value( string $type, string $key, $value ) {
        if ( ! is_string( $value ) || '' === $value ) {
            return $value;
        }

        $map = self::value_suffixed_labels();

        if ( '' !== $type ) {
            $keys = isset( $map[ $type ] ) ? (array) $map[ $type ] : [];
        } else {
            // No type context (a bare update_block). Fall back to the union of
            // every guarded key — these names exist only on the elements listed
            // above, so matching by key alone cannot hit an unrelated setting.
            $keys = array_unique( array_merge( [], ...array_values( $map ) ) );
        }

        if ( ! in_array( $key, $keys, true ) ) {
            return $value;
        }

        return self::strip_value_from_label( $value );
    }

    /**
     * Sanitise a settings object against a known element type's allowlist.
     *
     * @return array<string, mixed>
     */
    public static function sanitize_settings( string $type, $settings ): array {
        $settings = self::maybe_decode_json( $settings );
        if ( ! is_array( $settings ) ) {
            return [];
        }
        $allowed = CampaignSchema::allowed_settings_keys( $type );
        $clean   = [];
        foreach ( $settings as $key => $value ) {
            // Map aliased content keys (text → title/content/button_label, …).
            $canon = CampaignSchema::resolve_setting_key( $type, (string) $key );
            if ( ! in_array( $canon, $allowed, true ) ) {
                continue;
            }
            $sanitized = self::sanitize_setting_value( $type, $canon, $value );
            if ( null !== $sanitized ) {
                $guarded = self::guard_label_value( $type, $canon, $sanitized );

                // Drop only when the GUARD emptied it — a label that was nothing
                // but an amount. Then the renderer's `! empty()` restores the
                // schema default ("Goal:") instead of printing a bare figure.
                //
                // Testing `'' === $guarded` alone would also swallow a value the
                // model deliberately cleared, and in this codebase emptied means
                // empty — see the "use isset(), never ! empty()" note in
                // docs/features/campaign-builder/frontend-rendering.md.
                if ( '' === $guarded && '' !== $sanitized ) {
                    continue;
                }

                $clean[ $canon ] = $guarded;
            }
        }
        return $clean;
    }

    /**
     * Shallow sanitise when the element type is unknown (no context).
     * Keys are kept but values are coerced to safe scalars/arrays; the client
     * executor performs the type-aware allowlist check against live state.
     *
     * @return array<string, mixed>
     */
    private static function shallow_sanitize_settings( $settings ): array {
        if ( ! is_array( $settings ) ) {
            return [];
        }
        $clean = [];
        foreach ( $settings as $key => $value ) {
            if ( ! is_string( $key ) ) {
                continue;
            }
            if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
                $clean[ $key ] = $value;
            } elseif ( is_string( $value ) ) {
                // The label guard runs here too. This path handles update_block
                // with no type context, which is exactly the op an "edit the
                // progress bar" turn produces — guarding only the typed path
                // above would leave the common case open.
                $safe    = wp_kses_post( $value );
                $guarded = self::guard_label_value( '', $key, $safe );

                // As above: drop only when the guard emptied it, never when the
                // model deliberately sent an empty string.
                if ( '' === $guarded && '' !== $safe ) {
                    continue;
                }

                $clean[ $key ] = $guarded;
            } elseif ( is_array( $value ) ) {
                $clean[ $key ] = $value;
            }
        }
        return $clean;
    }

    /**
     * Coerce/escape a single setting value based on its control type.
     *
     * @param mixed $value
     * @return mixed|null Null drops the key (e.g. invalid enum value).
     */
    public static function sanitize_setting_value( string $type, string $key, $value ) {
        $control = CampaignSchema::find_control( $type, $key );
        $ctype   = $control['type'] ?? 'text';

        switch ( $ctype ) {
            case 'color':
                $hex = sanitize_hex_color( (string) $value );
                return $hex ? $hex : '';

            case 'number':
            case 'range':
            case 'currency':
                // Drop empty / non-numeric values so the element's own default
                // applies. Emitting '' here would override a meaningful default
                // (e.g. width 100) and collapse the element on render.
                if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
                    return null;
                }
                return 0 + $value;

            case 'toggle':
            case 'switch':
                return (bool) $value;

            case 'select':
            case 'align':
                $enum = CampaignSchema::enum_values( $type, $key );
                $val  = (string) $value;
                if ( is_array( $enum ) && ! in_array( $val, $enum, true ) ) {
                    return null; // drop out-of-enum value
                }
                return $val;

            case 'url':
                return esc_url_raw( (string) $value );

            case 'rich_text':
                return wp_kses_post( (string) $value );

            case 'textarea':
                return sanitize_textarea_field( (string) $value );

            case 'image_upload':
                // src is a URL; other image sub-keys handled by defaultSettings coercion below.
                return is_array( $value ) ? $value : esc_url_raw( (string) $value );

            default:
                // Preserve arrays (e.g. src_sizes), ints, bools; text-sanitise strings.
                if ( is_array( $value ) ) {
                    return $value;
                }
                if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
                    return $value;
                }
                return sanitize_text_field( (string) $value );
        }
    }

    /**
     * Sanitise a campaign meta value by key.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitize_meta_value( string $key, $value ) {
        switch ( $key ) {
            case 'bpc_goal_amount':
            case 'bpc_minimum_amount':
                return is_numeric( $value ) ? (float) $value : '';
            case 'bpc_allow_custom_amount':
                return (int) ( ! empty( $value ) );
            case 'bpc_color_primary':
            case 'bpc_color_background':
                $hex = sanitize_hex_color( (string) $value );
                return $hex ? $hex : '';
            case 'bpc_css_class':
                return sanitize_html_class( (string) $value );
            case 'bpc_suggested_amounts':
                return is_array( $value ) ? $value : [];
            default:
                return sanitize_text_field( (string) $value );
        }
    }

    /**
     * If a value is a JSON-encoded array/object string, decode it; otherwise
     * return it unchanged. Guards against models that stringify nested tool-call
     * arguments (e.g. columns/elements/settings as a JSON string).
     *
     * @param mixed $value
     * @return mixed
     */
    private static function maybe_decode_json( $value ) {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $trimmed = trim( $value );
        if ( '' === $trimmed || ( '[' !== $trimmed[0] && '{' !== $trimmed[0] ) ) {
            return $value;
        }
        $decoded = json_decode( $trimmed, true );
        return is_array( $decoded ) ? $decoded : $value;
    }

    private static function sanitize_id( $id ): string {
        // Builder ids are like `el_ab12cd34ef56`, `col_abcd1234`, or template names.
        return preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $id );
    }

    private static function sanitize_width( $width ): string {
        $width = (string) $width;
        if ( preg_match( '/^\d{1,3}(\.\d+)?%$/', $width ) ) {
            return $width;
        }
        if ( is_numeric( $width ) ) {
            return ( 0 + $width ) . '%';
        }
        return '100%';
    }

    /**
     * Map element_id => type from a trusted layout context.
     *
     * @return array<string, string>
     */
    private static function build_id_type_map( $layout ): array {
        $map = [];
        if ( ! is_array( $layout ) || empty( $layout['columns'] ) || ! is_array( $layout['columns'] ) ) {
            return $map;
        }
        foreach ( $layout['columns'] as $column ) {
            if ( ! is_array( $column ) || empty( $column['elements'] ) ) {
                continue;
            }
            foreach ( $column['elements'] as $element ) {
                if ( is_array( $element ) && ! empty( $element['id'] ) && ! empty( $element['type'] ) ) {
                    $map[ (string) $element['id'] ] = (string) $element['type'];
                }
            }
        }
        return $map;
    }
}
