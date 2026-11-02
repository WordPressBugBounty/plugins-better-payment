<?php

namespace Better_Payment\Lite\Campaign\Styles;

use Better_Payment\Lite\Campaign\Elements\ElementRegistry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Compiles the style controls of a campaign's elements into one scoped
 * `<style>` block.
 *
 * Inline `style=""` cannot express `:hover`, a selected chip or a media query,
 * so style controls declare `selectors` instead and this class turns the
 * stored values into rules:
 *
 *     'selectors' => [ '{{EL}} .bp-campaign-donate-btn a:hover' => 'background-color: {{VALUE}}' ]
 *
 * `{{EL}}` becomes `{scope} .bp-element-wrap[data-bp-element-id="{id}"]` —
 * the wrapper `render_element()` prints on every path. The builder preview
 * also wraps each element in a `.bp-builder-el-wrap` carrying the same
 * data attribute, which is why the class is part of the selector: a padding
 * must not be applied twice.
 *
 * Values never reach the stylesheet raw. Each is validated for its control
 * type (colour, number, keyword from the control's own options, four-sided
 * length, shadow) and dropped when invalid. An element id that is not
 * `[A-Za-z0-9_-]` is skipped. Every declaration carries `!important`, the
 * same rule as the typography controls: a user's override has to beat the
 * template theme. An unset control prints nothing.
 */
class ElementStyleCompiler {

    /** Placeholder for the element scope in a selector. */
    const EL = '{{EL}}';

    /**
     * The scope a campaign's rules hang off. Same scope as the campaign colour
     * block: 0 is the isolated builder preview iframe.
     *
     * @param int $campaign_id Campaign id.
     */
    public static function campaign_scope( int $campaign_id ): string {
        return $campaign_id > 0
            ? '.bp-campaign[data-campaign-id="' . $campaign_id . '"]'
            : '.bp-campaign';
    }

    /**
     * The `<style>` block for a campaign's columns, or '' when no element has a
     * style set.
     *
     * @param array $columns     Normalized layout columns.
     * @param int   $campaign_id Campaign id (0 = preview).
     * @param array $meta        Campaign meta, for controls with a `fallback` (the
     *                           Donate Button's colour falls back to the primary).
     */
    public static function style_tag( array $columns, int $campaign_id, array $meta = [] ): string {
        $scope    = self::campaign_scope( $campaign_id );
        $schemas  = ElementRegistry::get_all();
        $rules    = [];
        $elements = [];

        foreach ( $columns as $column ) {
            if ( ! is_array( $column ) || ! is_array( $column['elements'] ?? null ) ) {
                continue;
            }
            foreach ( $column['elements'] as $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }
                $elements[] = $element;
                $type       = is_string( $element['type'] ?? null ) ? $element['type'] : '';
                if ( ! isset( $schemas[ $type ] ) ) {
                    continue;
                }
                $rules = self::merge_rules( $rules, self::element_rules( $element, (array) ( $schemas[ $type ]['settingsSchema'] ?? [] ), $scope, null, $meta ) );
            }
        }

        $css = self::rules_to_css( $rules );

        /**
         * Filters the compiled element CSS of one campaign. An add-on appends its
         * own rules here — Pro adds the tablet and mobile values inside media
         * queries. The default is Lite's own CSS, unchanged.
         *
         * @param string $css         Compiled CSS (no `<style>` tag).
         * @param array  $elements    Every element of the campaign, in order.
         * @param string $scope       Campaign scope selector.
         * @param int    $campaign_id Campaign id (0 = preview).
         */
        $css = (string) apply_filters( 'better_payment/campaign/element_styles', $css, $elements, $scope, $campaign_id );
        $css = self::safe_css( $css );

        return '' === $css ? '' : '<style class="bp-element-styles">' . $css . '</style>';
    }

    /**
     * Rules for one element: `[ selector => [ declaration, ... ] ]`.
     *
     * Public so an add-on can compile another set of values against the same
     * selectors — Pro passes a `$value_of` that reads `{key}_tablet` to build its
     * tablet rules.
     *
     * @param array         $element  Element (`id`, `type`, `settings`).
     * @param array         $schema   The element's settingsSchema.
     * @param string        $scope    Campaign scope selector.
     * @param callable|null $value_of fn( array $control, array $settings ) => mixed. Defaults to
     *                                `$settings[ key ]`, then the control's `fallback`.
     * @param array         $meta     Campaign meta, read by a `fallback` (default reader only).
     * @return array<string, array<int, string>>
     */
    public static function element_rules( array $element, array $schema, string $scope, ?callable $value_of = null, array $meta = [] ): array {
        $id       = is_scalar( $element['id'] ?? null ) ? (string) $element['id'] : '';
        $el_scope = self::element_scope( $scope, $id );
        if ( '' === $el_scope ) {
            return [];
        }
        $settings = is_array( $element['settings'] ?? null ) ? $element['settings'] : [];

        $rules = [];
        foreach ( StyleControls::leaf_controls( $schema ) as $control ) {
            if ( empty( $control['selectors'] ) || ! is_array( $control['selectors'] ) || empty( $control['key'] ) ) {
                continue;
            }
            $raw = null !== $value_of
                ? $value_of( $control, $settings )
                : self::stored_or_fallback( $control, $settings, $meta );

            $tokens = self::tokens( $control, $raw );
            if ( null === $tokens ) {
                continue;
            }

            foreach ( $control['selectors'] as $selector => $template ) {
                $selector = self::selector( (string) $selector, $el_scope );
                if ( '' === $selector ) {
                    continue;
                }
                foreach ( self::declarations( (string) $template, $tokens ) as $decl ) {
                    $rules[ $selector ][] = $decl;
                }
            }
        }

        return $rules;
    }

    /**
     * The stored value, or — when it is unset or invalid — the control's
     * `fallback`: `[ 'meta' => key, 'value' => default ]`, the meta value first.
     *
     * A fallback is for a control whose unset state still has to paint something
     * the renderer used to paint inline: the Donate Button's colour falls back to
     * the campaign's primary, then `#6b63f6`, exactly as the inline style did.
     * Moving it here is what lets a hover colour beat it — nothing beats an inline
     * `!important`. A device reader (Pro) gets no fallback: a blank device value
     * means "inherit".
     *
     * @param array $control  Control descriptor.
     * @param array $settings Element settings.
     * @param array $meta     Campaign meta.
     * @return mixed
     */
    private static function stored_or_fallback( array $control, array $settings, array $meta ) {
        $raw = $settings[ $control['key'] ] ?? null;
        if ( empty( $control['fallback'] ) || ! is_array( $control['fallback'] ) || null !== self::tokens( $control, $raw ) ) {
            return $raw;
        }
        $fallback = $control['fallback'];
        if ( ! empty( $fallback['meta'] ) && isset( $meta[ $fallback['meta'] ] ) && null !== self::tokens( $control, $meta[ $fallback['meta'] ] ) ) {
            return $meta[ $fallback['meta'] ];
        }

        return $fallback['value'] ?? null;
    }

    /**
     * Merge two rule maps, keeping declaration order.
     *
     * @param array $a Rules.
     * @param array $b Rules.
     */
    public static function merge_rules( array $a, array $b ): array {
        foreach ( $b as $selector => $decls ) {
            $a[ $selector ] = array_merge( $a[ $selector ] ?? [], $decls );
        }

        return $a;
    }

    /**
     * Serialize a rule map.
     *
     * @param array $rules `[ selector => [ declaration, ... ] ]`.
     */
    public static function rules_to_css( array $rules ): string {
        $css = '';
        foreach ( $rules as $selector => $decls ) {
            if ( empty( $decls ) ) {
                continue;
            }
            $css .= $selector . '{' . implode( ';', $decls ) . '}';
        }

        return $css;
    }

    /**
     * Replace `{{EL}}` and refuse anything that could close the rule.
     *
     * @param string $selector Schema selector.
     * @param string $el_scope Element scope.
     */
    private static function selector( string $selector, string $el_scope ): string {
        if ( preg_match( '/[{}<;]/', str_replace( self::EL, '', $selector ) ) ) {
            return '';
        }
        // A selector list is scoped part by part: one unscoped part would style
        // the whole page.
        $parts = array_map( 'trim', explode( ',', $selector ) );
        foreach ( $parts as $part ) {
            if ( 0 !== strpos( $part, self::EL ) ) {
                return '';
            }
        }

        return implode( ',', str_replace( self::EL, $el_scope, $parts ) );
    }

    /**
     * Fill a declaration template and split it into `!important` declarations.
     *
     * @param string $template e.g. 'border-color: {{VALUE}}'.
     * @param array  $tokens   Placeholder => value.
     * @return array<int, string>
     */
    private static function declarations( string $template, array $tokens ): array {
        $filled = strtr( $template, $tokens );
        if ( preg_match( '/\{\{[A-Z]+\}\}/', $filled ) ) {
            return []; // A placeholder this control type does not provide.
        }

        $out = [];
        foreach ( explode( ';', $filled ) as $decl ) {
            $decl = trim( $decl );
            if ( '' === $decl || false === strpos( $decl, ':' ) ) {
                continue;
            }
            $out[] = $decl . ' !important';
        }

        return $out;
    }

    /**
     * The placeholder values for a control's stored value, or null when the
     * value is unset or invalid (then the control prints nothing).
     *
     * @param array $control Control descriptor.
     * @param mixed $raw     Stored value.
     * @return array<string, string>|null
     */
    public static function tokens( array $control, $raw ): ?array {
        switch ( $control['type'] ?? '' ) {
            case 'color':
                $color = self::color( $raw );
                return '' === $color ? null : [ '{{VALUE}}' => $color ];

            case 'number':
            case 'range':
                $num = self::number( $raw, $control['min'] ?? null, $control['max'] ?? null );
                if ( null === $num ) {
                    return null;
                }
                $unit = isset( $control['unit'] ) && in_array( $control['unit'], [ 'px', '%', 'em', 'rem', 'ms', 's', 'deg', '' ], true )
                    ? $control['unit']
                    : 'px';
                return [ '{{VALUE}}' => $num . $unit ];

            case 'select':
                $value = is_scalar( $raw ) ? (string) $raw : '';
                if ( '' === $value || ! preg_match( '/^[a-z0-9-]+$/', $value ) ) {
                    return null;
                }
                $allowed = array_map(
                    function ( $opt ) {
                        return is_array( $opt ) ? (string) ( $opt['value'] ?? '' ) : '';
                    },
                    (array) ( $control['options'] ?? [] )
                );
                return in_array( $value, $allowed, true ) ? [ '{{VALUE}}' => $value ] : null;

            case 'dimensions':
                return self::dimensions( $raw, $control );

            case 'box_shadow':
                $shadow = self::box_shadow( $raw );
                return '' === $shadow ? null : [ '{{VALUE}}' => $shadow ];
        }

        /**
         * Filters the placeholder values for a control type Lite does not know
         * (an add-on's own — Pro's gradient and hover effect). Return
         * `[ '{{VALUE}}' => '...' ]`, or null to print nothing. Lite still checks
         * the answer: placeholder names only, string values with no `{ } ; <`.
         *
         * @param array<string, string>|null $tokens  null.
         * @param array                      $control Control descriptor.
         * @param mixed                      $raw     Stored value.
         */
        return self::safe_tokens( apply_filters( 'better_payment/campaign/style_tokens', null, $control, $raw ) );
    }

    /**
     * An add-on's tokens, or null when any part could break out of a rule.
     *
     * @param mixed $tokens Filter answer.
     * @return array<string, string>|null
     */
    private static function safe_tokens( $tokens ): ?array {
        if ( ! is_array( $tokens ) || empty( $tokens ) ) {
            return null;
        }
        foreach ( $tokens as $name => $value ) {
            if ( ! is_string( $name ) || ! preg_match( '/^\{\{[A-Z]+\}\}$/', $name ) || ! is_string( $value )
                || '' === trim( $value ) || strlen( $value ) > 500 || preg_match( '/[{};<]/', $value ) ) {
                return null;
            }
        }

        return $tokens;
    }

    /**
     * The selector `{{EL}}` stands for: an element's wrapper inside a campaign
     * scope. '' for an id that is not `[A-Za-z0-9_-]{1,64}`.
     *
     * Public for an add-on that scopes CSS it builds itself (Pro's custom CSS).
     *
     * @param string $scope Campaign scope selector.
     * @param string $id    Element id.
     */
    public static function element_scope( string $scope, string $id ): string {
        return preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $id )
            ? $scope . ' .bp-element-wrap[data-bp-element-id="' . $id . '"]'
            : '';
    }

    /**
     * A CSS colour: hex (3, 4, 6 or 8 digits), rgb()/rgba() with numeric
     * channels, or `transparent`. Anything else is ''.
     *
     * @param mixed $val Stored value.
     */
    public static function color( $val ): string {
        $val = is_string( $val ) ? strtolower( trim( $val ) ) : '';
        if ( '' === $val ) {
            return '';
        }
        if ( 'transparent' === $val || preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $val ) ) {
            return $val;
        }
        if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+))?\s*\)$/', $val, $m ) ) {
            if ( (int) $m[1] > 255 || (int) $m[2] > 255 || (int) $m[3] > 255 ) {
                return '';
            }
            return isset( $m[4] ) // An unmatched trailing group is absent, never ''.
                ? 'rgba(' . (int) $m[1] . ',' . (int) $m[2] . ',' . (int) $m[3] . ',' . $m[4] . ')'
                : 'rgb(' . (int) $m[1] . ',' . (int) $m[2] . ',' . (int) $m[3] . ')';
        }

        return '';
    }

    /**
     * A number, clamped, formatted without trailing zeros. null when blank or
     * not numeric.
     *
     * @param mixed          $val Stored value.
     * @param int|float|null $min Lower bound.
     * @param int|float|null $max Upper bound.
     */
    public static function number( $val, $min = null, $max = null ): ?string {
        if ( is_bool( $val ) || ! is_numeric( $val ) ) {
            return null;
        }
        $num = (float) $val;
        if ( is_numeric( $min ) ) {
            $num = max( (float) $min, $num );
        }
        if ( is_numeric( $max ) ) {
            $num = min( (float) $max, $num );
        }
        $num = max( -9999.0, min( 9999.0, $num ) );

        $out = rtrim( rtrim( number_format( $num, 2, '.', '' ), '0' ), '.' );
        return '-0' === $out ? '0' : $out;
    }

    /**
     * `{{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}` from a dimensions value
     * `{ top, right, bottom, left, unit }`. A blank side is 0 once any side is
     * set; null when every side is blank.
     *
     * @param mixed $raw     Stored value.
     * @param array $control Control descriptor (`units`, `min`).
     * @return array<string, string>|null
     */
    private static function dimensions( $raw, array $control ): ?array {
        if ( ! is_array( $raw ) ) {
            return null;
        }
        $units = ! empty( $control['units'] ) && is_array( $control['units'] ) ? $control['units'] : [ 'px' ];
        $unit  = isset( $raw['unit'] ) && in_array( $raw['unit'], $units, true ) ? $raw['unit'] : $units[0];
        if ( ! in_array( $unit, StyleControls::DIMENSION_UNITS, true ) ) {
            return null;
        }

        $tokens = [];
        $any    = false;
        foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
            $num = self::number( $raw[ $side ] ?? null, $control['min'] ?? null, $control['max'] ?? null );
            if ( null !== $num ) {
                $any = true;
            }
            $tokens[ '{{' . strtoupper( $side ) . '}}' ] = null === $num || '0' === $num ? '0' : $num . $unit;
        }

        return $any ? $tokens : null;
    }

    /**
     * A box-shadow value from `{ x, y, blur, spread, color, inset }`. '' when
     * no colour is set — a shadow without a colour is not a choice anyone made.
     *
     * @param mixed $raw Stored value.
     */
    private static function box_shadow( $raw ): string {
        if ( ! is_array( $raw ) ) {
            return '';
        }
        $color = self::color( $raw['color'] ?? '' );
        if ( '' === $color ) {
            return '';
        }
        $parts = [];
        foreach ( [ 'x' => [ -200, 200 ], 'y' => [ -200, 200 ], 'blur' => [ 0, 300 ], 'spread' => [ -200, 200 ] ] as $axis => $range ) {
            $num     = self::number( $raw[ $axis ] ?? 0, $range[0], $range[1] );
            $parts[] = null === $num || '0' === $num ? '0' : $num . 'px';
        }

        return ( ! empty( $raw['inset'] ) ? 'inset ' : '' ) . implode( ' ', $parts ) . ' ' . $color;
    }

    /**
     * Last line of defence on the final CSS (the filter's output included):
     * nothing that could end the `<style>` element.
     *
     * @param string $css CSS.
     */
    private static function safe_css( string $css ): string {
        if ( false !== stripos( $css, '</' ) || false !== strpos( $css, '<' ) ) {
            return '';
        }

        return trim( $css );
    }

    /**
     * Sanitized classes from an element's `box_css_class` setting, ready for a
     * class attribute ('' when none).
     *
     * @param array $settings Element settings.
     */
    public static function css_classes( array $settings ): string {
        $raw = is_string( $settings['box_css_class'] ?? null ) ? $settings['box_css_class'] : '';
        if ( '' === trim( $raw ) ) {
            return '';
        }
        $classes = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', $raw ) ) );

        return implode( ' ', array_slice( array_unique( $classes ), 0, 10 ) );
    }
}
