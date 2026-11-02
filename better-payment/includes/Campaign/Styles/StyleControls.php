<?php

namespace Better_Payment\Lite\Campaign\Styles;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared style-control groups for campaign builder elements.
 *
 * Every style control carries three things the rest of the builder relies on:
 *
 * - `tab`       — which sidebar tab draws it ('style' or 'advanced'; a control
 *                 with no `tab` belongs to 'content').
 * - `ai => false` — the AI never sees, writes or echoes the key. A style key in
 *                 the prompt would cost tokens on every turn and invite the model
 *                 to restyle a page nobody asked it to restyle. Set on each leaf,
 *                 never on the section or tabs around it, so a leaf may opt back
 *                 in (the Donate Button's `button_color`).
 * - `selectors` — `[ selector => declaration template ]`, compiled by
 *                 {@see ElementStyleCompiler} into one scoped `<style>` block.
 *                 `{{EL}}` is the element's `.bp-element-wrap`.
 *
 * Groups are built here rather than spelled out per element so the keys, ranges
 * and selectors cannot drift between widgets. An unset control prints nothing,
 * so the template theme keeps styling every element nobody has touched.
 */
class StyleControls {

    /** Units a dimensions control may store. */
    const DIMENSION_UNITS = [ 'px', '%', 'em', 'rem' ];

    /** Border styles offered by the border controls ('' = theme default). */
    public static function border_style_options(): array {
        return [
            [ 'value' => '', 'label' => __( 'Default', 'better-payment' ) ],
            [ 'value' => 'none', 'label' => __( 'None', 'better-payment' ) ],
            [ 'value' => 'solid', 'label' => __( 'Solid', 'better-payment' ) ],
            [ 'value' => 'dashed', 'label' => __( 'Dashed', 'better-payment' ) ],
            [ 'value' => 'dotted', 'label' => __( 'Dotted', 'better-payment' ) ],
            [ 'value' => 'double', 'label' => __( 'Double', 'better-payment' ) ],
        ];
    }

    /**
     * A colour control.
     *
     * @param string $key       Setting key.
     * @param string $label     Control label.
     * @param array  $selectors Selector => declaration template (`{{VALUE}}`).
     */
    public static function color( string $key, string $label, array $selectors ): array {
        return [
            'key'       => $key,
            'label'     => $label,
            'type'      => 'color',
            'alpha'     => true,
            'ai'        => false,
            'selectors' => $selectors,
        ];
    }

    /**
     * A four-sided length control (padding, margin, radius, border width).
     *
     * @param string $key       Setting key.
     * @param string $label     Control label.
     * @param array  $selectors Selector => template with {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}.
     * @param array  $units     Allowed units, first is the default.
     * @param array  $extra     Merged into the descriptor (e.g. `condition`, `min`).
     */
    public static function dimensions( string $key, string $label, array $selectors, array $units = [ 'px', '%', 'em' ], array $extra = [] ): array {
        return array_merge(
            [
                'key'       => $key,
                'label'     => $label,
                'type'      => 'dimensions',
                'units'     => array_values( array_intersect( $units, self::DIMENSION_UNITS ) ),
                'ai'        => false,
                'selectors' => $selectors,
            ],
            $extra
        );
    }

    /**
     * A single number with a unit (icon size, gap, bar height).
     *
     * @param string $key       Setting key.
     * @param string $label     Control label.
     * @param array  $selectors Selector => template (`{{VALUE}}` includes the unit).
     * @param int    $min       Lower bound.
     * @param int    $max       Upper bound.
     * @param string $unit      Unit appended on output.
     * @param float  $step      Field step; fractional keeps decimals in the builder.
     */
    public static function size( string $key, string $label, array $selectors, int $min = 0, int $max = 200, string $unit = 'px', float $step = 1 ): array {
        return [
            'key'       => $key,
            'label'     => $label, // The unit shows inside the field (`unit`).
            'type'      => 'number',
            'min'       => $min,
            'max'       => $max,
            'unit'      => $unit,
            'step'      => $step,
            'ai'        => false,
            'selectors' => $selectors,
        ];
    }

    /**
     * A select whose options become CSS keywords.
     *
     * @param string $key       Setting key.
     * @param string $label     Control label.
     * @param array  $options   [ [ 'value' => ..., 'label' => ... ], ... ].
     * @param array  $selectors Selector => template (`{{VALUE}}`).
     * @param array  $extra     Merged into the descriptor.
     */
    public static function keyword( string $key, string $label, array $options, array $selectors, array $extra = [] ): array {
        return array_merge(
            [
                'key'          => $key,
                'label'        => $label,
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => $options,
                'ai'           => false,
                'selectors'    => $selectors,
            ],
            $extra
        );
    }

    /**
     * A box-shadow control. Its value is one object, so the four numbers, the
     * colour and the inset flag can never be half-saved.
     *
     * @param string $key       Setting key.
     * @param string $label     Control label.
     * @param array  $selectors Selector => template (`{{VALUE}}`).
     */
    public static function box_shadow( string $key, string $label, array $selectors ): array {
        return [
            'key'       => $key,
            'label'     => $label,
            'type'      => 'box_shadow',
            'ai'        => false,
            'selectors' => $selectors,
        ];
    }

    /**
     * Border style + width + colour for one target, with width and colour shown
     * only once a visible style is chosen.
     *
     * @param string $prefix   Key prefix, e.g. 'box_'.
     * @param string $selector CSS selector (may contain {{EL}}).
     */
    public static function border( string $prefix, string $selector ): array {
        $visible = [ $prefix . 'border_style' => [ 'solid', 'dashed', 'dotted', 'double' ] ];

        return [
            self::keyword(
                $prefix . 'border_style',
                __( 'Border Type', 'better-payment' ),
                self::border_style_options(),
                [ $selector => 'border-style: {{VALUE}}' ]
            ),
            self::dimensions(
                $prefix . 'border_width',
                __( 'Border Width', 'better-payment' ),
                [ $selector => 'border-width: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ],
                [ 'px', 'em' ],
                [ 'condition' => $visible, 'min' => 0 ]
            ),
            self::color(
                $prefix . 'border_color',
                __( 'Border Color', 'better-payment' ),
                [ $selector => 'border-color: {{VALUE}}' ]
            ) + [ 'condition' => $visible ],
        ];
    }

    /**
     * Normal / Hover state tabs, plus any further states (the amount chips'
     * Selected).
     *
     * @param string $key    Container key (prefixed with '_', never stored).
     * @param array  $normal Controls for the normal state.
     * @param array  $hover  Controls for the hover state.
     * @param array  $extra  More tabs: [ [ 'key' => ..., 'label' => ..., 'children' => [...] ], ... ].
     */
    public static function state_tabs( string $key, array $normal, array $hover, array $extra = [] ): array {
        return [
            'key'  => $key,
            'type' => 'tabs',
            'tabs' => array_merge(
                [
                    [
                        'key'      => 'normal',
                        'label'    => __( 'Normal', 'better-payment' ),
                        'children' => $normal,
                    ],
                    [
                        'key'      => 'hover',
                        'label'    => __( 'Hover', 'better-payment' ),
                        'children' => $hover,
                    ],
                ],
                $extra
            ),
        ];
    }

    /**
     * Font size, weight, transform and letter spacing for one text target.
     *
     * @param string $prefix   Key prefix, e.g. 'btn_'.
     * @param string $selector CSS selector (starts with {{EL}}).
     */
    public static function typography( string $prefix, string $selector ): array {
        return [
            self::size( $prefix . 'font_size', __( 'Font Size', 'better-payment' ), [ $selector => 'font-size: {{VALUE}}' ], 8, 120, 'px', 0.5 ),
            self::keyword(
                $prefix . 'font_weight',
                __( 'Font Weight', 'better-payment' ),
                self::named_options( [ '' => __( 'Default', 'better-payment' ), '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800' ] ),
                [ $selector => 'font-weight: {{VALUE}}' ]
            ),
            self::keyword(
                $prefix . 'text_transform',
                __( 'Text Transform', 'better-payment' ),
                self::named_options(
                    [
                        ''           => __( 'Default', 'better-payment' ),
                        'none'       => __( 'None', 'better-payment' ),
                        'uppercase'  => __( 'Uppercase', 'better-payment' ),
                        'capitalize' => __( 'Capitalize', 'better-payment' ),
                        'lowercase'  => __( 'Lowercase', 'better-payment' ),
                    ]
                ),
                [ $selector => 'text-transform: {{VALUE}}' ]
            ),
            self::size( $prefix . 'letter_spacing', __( 'Letter Spacing', 'better-payment' ), [ $selector => 'letter-spacing: {{VALUE}}' ], -5, 20, 'px', 0.1 ),
        ];
    }

    /**
     * `[ value => label ]` → select options.
     *
     * @param array<int|string, string> $map Value => label (numeric values arrive as int keys).
     * @return array<int, array{value: string, label: string}>
     */
    private static function named_options( array $map ): array {
        $out = [];
        foreach ( $map as $value => $label ) {
            $out[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return $out;
    }

    /**
     * The Donate Button's Style tab: the button in Normal / Hover, its shape,
     * its type, and the minimum-donation notice above it.
     *
     * The Normal background is the existing `button_color` key — stored values
     * keep working and the AI can still set it. It falls back to the campaign's
     * primary colour, so the button paints exactly what the old inline style
     * painted; the renderer no longer prints that inline style.
     */
    public static function donate_button(): array {
        $btn = '{{EL}} .bp-campaign-donate-btn a.bp-donate_btn';
        $hov = '{{EL}} .bp-campaign-donate-btn a.bp-donate_btn:hover, {{EL}} .bp-campaign-donate-btn a.bp-donate_btn:focus-visible';

        $background          = self::color( 'button_color', __( 'Background', 'better-payment' ), [ $btn => 'background-color: {{VALUE}}' ] );
        $background['ai']    = true; // Stored before the Style tab existed; still the AI's.
        $background['info']  = __( 'Leave empty to use the campaign primary color.', 'better-payment' );
        $background['fallback'] = [
            'meta'  => 'bpc_color_primary',
            'value' => '#6b63f6',
        ];

        return [
            self::section(
                '_btn_style',
                __( 'Button', 'better-payment' ),
                [
                    self::state_tabs(
                        '_btn_states',
                        array_merge(
                            [
                                $background,
                                self::color( 'btn_text_color', __( 'Text Color', 'better-payment' ), [ $btn => 'color: {{VALUE}}' ] ),
                            ],
                            self::border( 'btn_', $btn ),
                            [ self::box_shadow( 'btn_shadow', __( 'Box Shadow', 'better-payment' ), [ $btn => 'box-shadow: {{VALUE}}' ] ) ]
                        ),
                        [
                            self::color( 'btn_background_hover', __( 'Background', 'better-payment' ), [ $hov => 'background-color: {{VALUE}}' ] ),
                            self::color( 'btn_text_color_hover', __( 'Text Color', 'better-payment' ), [ $hov => 'color: {{VALUE}}' ] ),
                            self::color( 'btn_border_color_hover', __( 'Border Color', 'better-payment' ), [ $hov => 'border-color: {{VALUE}}' ] ),
                            self::box_shadow( 'btn_shadow_hover', __( 'Box Shadow', 'better-payment' ), [ $hov => 'box-shadow: {{VALUE}}' ] ),
                        ]
                    ),
                    self::dimensions( 'btn_border_radius', __( 'Border Radius', 'better-payment' ), [ $btn => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    self::dimensions( 'btn_padding', __( 'Padding', 'better-payment' ), [ $btn => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', 'em' ], [ 'min' => 0 ] ),
                ]
            ),
            self::section( '_btn_typography', __( 'Button Typography', 'better-payment' ), self::typography( 'btn_', $btn ) ),
            self::section(
                '_btn_notice',
                __( 'Minimum Donation Notice', 'better-payment' ),
                [
                    self::color( 'btn_notice_color', __( 'Text Color', 'better-payment' ), [ '{{EL}} .bp-min-donation-notice' => 'color: {{VALUE}}' ] ),
                    self::size( 'btn_notice_font_size', __( 'Font Size', 'better-payment' ), [ '{{EL}} .bp-min-donation-notice' => 'font-size: {{VALUE}}' ], 8, 40, 'px', 0.5 ),
                ]
            ),
        ];
    }

    /**
     * The Donate Amount's Style tab: headline, the amount chips in Normal /
     * Hover / Selected, and the custom amount field.
     */
    public static function donate_amount(): array {
        $chip     = '{{EL}} .bp-donate_amounts .bp-amount-label';
        $hover    = '{{EL}} .bp-donate_amounts .bp-amount-label:hover';
        $selected = '{{EL}} .bp-donate_amounts input.bp-option-amount:checked + .bp-amount-label';
        $field    = '{{EL}} .other_amount_section';

        return [
            self::section(
                '_amt_headline',
                __( 'Headline', 'better-payment' ),
                array_merge(
                    [ self::color( 'amt_headline_color', __( 'Text Color', 'better-payment' ), [ '{{EL}} .bp-donate-headline' => 'color: {{VALUE}}' ] ) ],
                    self::typography( 'amt_headline_', '{{EL}} .bp-donate-headline' )
                )
            ),
            self::section(
                '_amt_chips',
                __( 'Amount Buttons', 'better-payment' ),
                [
                    self::state_tabs(
                        '_amt_states',
                        array_merge(
                            [
                                self::color( 'amt_background', __( 'Background', 'better-payment' ), [ $chip => 'background-color: {{VALUE}}' ] ),
                                self::color( 'amt_text_color', __( 'Text Color', 'better-payment' ), [ $chip => 'color: {{VALUE}}' ] ),
                            ],
                            self::border( 'amt_', $chip )
                        ),
                        [
                            self::color( 'amt_background_hover', __( 'Background', 'better-payment' ), [ $hover => 'background-color: {{VALUE}}' ] ),
                            self::color( 'amt_text_color_hover', __( 'Text Color', 'better-payment' ), [ $hover => 'color: {{VALUE}}' ] ),
                            self::color( 'amt_border_color_hover', __( 'Border Color', 'better-payment' ), [ $hover => 'border-color: {{VALUE}}' ] ),
                        ],
                        [
                            [
                                'key'      => 'selected',
                                'label'    => __( 'Selected', 'better-payment' ),
                                'children' => [
                                    self::color( 'amt_background_selected', __( 'Background', 'better-payment' ), [ $selected => 'background-color: {{VALUE}}' ] ),
                                    self::color( 'amt_text_color_selected', __( 'Text Color', 'better-payment' ), [ $selected => 'color: {{VALUE}}' ] ),
                                    self::color( 'amt_border_color_selected', __( 'Border Color', 'better-payment' ), [ $selected => 'border-color: {{VALUE}}' ] ),
                                ],
                            ],
                        ]
                    ),
                    self::dimensions( 'amt_border_radius', __( 'Border Radius', 'better-payment' ), [ $chip => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    self::dimensions( 'amt_padding', __( 'Padding', 'better-payment' ), [ $chip => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', 'em' ], [ 'min' => 0 ] ),
                    self::size( 'amt_gap', __( 'Gap', 'better-payment' ), [ '{{EL}} .bp-donate_amounts' => 'gap: {{VALUE}}' ], 0, 60 ),
                    self::size( 'amt_font_size', __( 'Font Size', 'better-payment' ), [ $chip => 'font-size: {{VALUE}}' ], 8, 60, 'px', 0.5 ),
                ]
            ),
            self::section(
                '_amt_field',
                __( 'Custom Amount Field', 'better-payment' ),
                array_merge(
                    [
                        self::color( 'amt_field_background', __( 'Background', 'better-payment' ), [ $field => 'background-color: {{VALUE}}', '{{EL}} .other_amount_section .campaign-custom-amount' => 'background-color: transparent' ] ),
                        self::color( 'amt_field_text_color', __( 'Text Color', 'better-payment' ), [ '{{EL}} .other_amount_section .campaign-custom-amount' => 'color: {{VALUE}}' ] ),
                    ],
                    self::border( 'amt_field_', $field ),
                    [
                        self::dimensions( 'amt_field_border_radius', __( 'Border Radius', 'better-payment' ), [ $field => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    ]
                )
            ),
        ];
    }

    /**
     * A collapsible section on the Style or Advanced tab.
     *
     * Only one section is open at a time (the builder's accordion) and a tab
     * opens on its first visible section, so the order here is the default.
     *
     * @param string $key      Container key (prefixed with '_', never stored).
     * @param string $label    Section heading.
     * @param array  $children Controls.
     * @param string $tab      'style' or 'advanced'.
     */
    public static function section( string $key, string $label, array $children, string $tab = 'style' ): array {
        return [
            'key'         => $key,
            'label'       => $label,
            'type'        => 'section',
            'tab'         => $tab,
            'collapsible' => true,
            'children'    => $children,
        ];
    }

    /**
     * The box group every element carries on the Advanced tab: spacing,
     * background, border, radius and shadow of its `.bp-element-wrap`, with a
     * hover state for the parts a hover can change.
     *
     * `ElementStyleGroupsTest` fails if a registered element does not carry it.
     *
     * @return array<int, array<string, mixed>> Sections, ready to append to a settingsSchema.
     */
    public static function box(): array {
        $el = '{{EL}}';

        return [
            self::section(
                '_box_spacing',
                __( 'Spacing', 'better-payment' ),
                [
                    self::dimensions( 'box_margin', __( 'Margin', 'better-payment' ), [ $el => 'margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%', 'em', 'rem' ] ),
                    self::dimensions( 'box_padding', __( 'Padding', 'better-payment' ), [ $el => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%', 'em', 'rem' ], [ 'min' => 0 ] ),
                ],
                'advanced'
            ),
            self::section(
                '_box_style',
                __( 'Background & Border', 'better-payment' ),
                [
                    self::state_tabs(
                        '_box_states',
                        array_merge(
                            [ self::color( 'box_background', __( 'Background', 'better-payment' ), [ $el => 'background-color: {{VALUE}}' ] ) ],
                            self::border( 'box_', $el ),
                            [
                                self::dimensions( 'box_border_radius', __( 'Border Radius', 'better-payment' ), [ $el => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                                self::box_shadow( 'box_shadow', __( 'Box Shadow', 'better-payment' ), [ $el => 'box-shadow: {{VALUE}}' ] ),
                            ]
                        ),
                        [
                            self::color( 'box_background_hover', __( 'Background', 'better-payment' ), [ $el . ':hover' => 'background-color: {{VALUE}}' ] ),
                            self::color( 'box_border_color_hover', __( 'Border Color', 'better-payment' ), [ $el . ':hover' => 'border-color: {{VALUE}}' ] ),
                            self::box_shadow( 'box_shadow_hover', __( 'Box Shadow', 'better-payment' ), [ $el . ':hover' => 'box-shadow: {{VALUE}}' ] ),
                        ]
                    ),
                ],
                'advanced'
            ),
            self::section(
                '_box_attributes',
                __( 'Attributes', 'better-payment' ),
                [
                    [
                        'key'         => 'box_css_class',
                        'label'       => __( 'CSS Classes', 'better-payment' ),
                        'type'        => 'text',
                        'ai'          => false,
                        'placeholder' => 'my-class another-class',
                        'info'        => __( 'Added to this element. Separate classes with spaces.', 'better-payment' ),
                    ],
                ],
                'advanced'
            ),
        ];
    }

    /**
     * A text target's colour + typography, as one section.
     *
     * @param string $key      Section key.
     * @param string $label    Section heading.
     * @param string $prefix   Setting key prefix.
     * @param string $selector CSS selector.
     */
    private static function text_section( string $key, string $label, string $prefix, string $selector ): array {
        return self::section(
            $key,
            $label,
            array_merge(
                [ self::color( $prefix . 'color', __( 'Text Color', 'better-payment' ), [ $selector => 'color: {{VALUE}}' ] ) ],
                self::typography( $prefix, $selector )
            )
        );
    }

    /**
     * Campaign Description: links (Normal / Hover) and paragraph spacing. The
     * headline and body typography stay the element's own Typography sections
     * (inline, AI-writable), so nothing here can collide with them.
     */
    public static function description(): array {
        $link = '{{EL}} .bp-campaign-description-content a';

        return [
            self::section(
                '_desc_links',
                __( 'Links & Paragraphs', 'better-payment' ),
                [
                    self::state_tabs(
                        '_desc_link_states',
                        [ self::color( 'desc_link_color', __( 'Link Color', 'better-payment' ), [ $link => 'color: {{VALUE}}' ] ) ],
                        [ self::color( 'desc_link_color_hover', __( 'Link Color', 'better-payment' ), [ $link . ':hover, ' . $link . ':focus-visible' => 'color: {{VALUE}}' ] ) ]
                    ),
                    self::size( 'desc_paragraph_spacing', __( 'Paragraph Spacing', 'better-payment' ), [ '{{EL}} .bp-campaign-description-content p' => 'margin-bottom: {{VALUE}}' ], 0, 80 ),
                ]
            ),
        ];
    }

    /**
     * Photo: the image's frame, height and fit, and a hover shadow.
     */
    public static function photo(): array {
        $img = '{{EL}} .bp-campaign-photo img.bp-campaign-image';

        return [
            self::section(
                '_img_style',
                __( 'Image', 'better-payment' ),
                [
                    self::state_tabs(
                        '_img_states',
                        array_merge(
                            self::border( 'img_', $img ),
                            [ self::box_shadow( 'img_shadow', __( 'Box Shadow', 'better-payment' ), [ $img => 'box-shadow: {{VALUE}}' ] ) ]
                        ),
                        [
                            self::color( 'img_border_color_hover', __( 'Border Color', 'better-payment' ), [ $img . ':hover' => 'border-color: {{VALUE}}' ] ),
                            self::box_shadow( 'img_shadow_hover', __( 'Box Shadow', 'better-payment' ), [ $img . ':hover' => 'box-shadow: {{VALUE}}' ] ),
                        ]
                    ),
                    self::dimensions( 'img_border_radius', __( 'Border Radius', 'better-payment' ), [ $img => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    self::size( 'img_height', __( 'Height', 'better-payment' ), [ $img => 'height: {{VALUE}}' ], 40, 1200 ),
                    self::keyword(
                        'img_object_fit',
                        __( 'Image Fit', 'better-payment' ),
                        self::named_options(
                            [
                                ''        => __( 'Default', 'better-payment' ),
                                'cover'   => __( 'Cover', 'better-payment' ),
                                'contain' => __( 'Contain', 'better-payment' ),
                                'fill'    => __( 'Fill', 'better-payment' ),
                            ]
                        ),
                        [ $img => 'object-fit: {{VALUE}}' ],
                        [ 'info' => __( 'Applies once a height is set.', 'better-payment' ) ]
                    ),
                ]
            ),
        ];
    }

    /**
     * Progress Bar: headline, track, fill and labels.
     *
     * The fill colour also sets `--bp-progress-color`, which the v3 themes
     * build their gradient from, so a changed fill is not undone by a theme.
     */
    public static function progress_bar(): array {
        $track = '{{EL}} .bp-progress-bar-wrap';
        $fill  = '{{EL}} .bp-progress-bar-wrap .bp-progress-bar';

        return [
            self::text_section( '_pb_headline', __( 'Headline', 'better-payment' ), 'pb_headline_', '{{EL}} .bp-progress-headline' ),
            self::section(
                '_pb_bar',
                __( 'Bar', 'better-payment' ),
                [
                    self::color( 'pb_track_color', __( 'Track Color', 'better-payment' ), [ $track => 'background-color: {{VALUE}}' ] ),
                    self::color( 'pb_fill_color', __( 'Fill Color', 'better-payment' ), [ $fill => 'background-color: {{VALUE}}; --bp-progress-color: {{VALUE}}' ] ),
                    self::size( 'pb_height', __( 'Height', 'better-payment' ), [ $track => 'height: {{VALUE}}' ], 2, 60 ),
                    self::dimensions( 'pb_border_radius', __( 'Border Radius', 'better-payment' ), [ $track . ', ' . $fill => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                ]
            ),
            self::text_section( '_pb_labels', __( 'Labels', 'better-payment' ), 'pb_label_', '{{EL}} .bp-progress-labels' ),
        ];
    }

    /**
     * Campaign Summary: headline, the figure tiles, and their values and labels.
     */
    public static function campaign_summary(): array {
        $tile = '{{EL}} .bp-campaign-summary .bp-summary-item';

        return [
            self::text_section( '_sum_headline', __( 'Headline', 'better-payment' ), 'sum_headline_', '{{EL}} .bp-summary-headline' ),
            self::section(
                '_sum_tiles',
                __( 'Tiles', 'better-payment' ),
                array_merge(
                    [ self::color( 'sum_background', __( 'Background', 'better-payment' ), [ $tile => 'background-color: {{VALUE}}' ] ) ],
                    self::border( 'sum_', $tile ),
                    [
                        self::dimensions( 'sum_border_radius', __( 'Border Radius', 'better-payment' ), [ $tile => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                        self::dimensions( 'sum_padding', __( 'Padding', 'better-payment' ), [ $tile => 'padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', 'em' ], [ 'min' => 0 ] ),
                        self::box_shadow( 'sum_shadow', __( 'Box Shadow', 'better-payment' ), [ $tile => 'box-shadow: {{VALUE}}' ] ),
                        self::size( 'sum_gap', __( 'Gap', 'better-payment' ), [ '{{EL}} .bp-campaign-summary' => 'gap: {{VALUE}}' ], 0, 60 ),
                    ]
                )
            ),
            self::text_section( '_sum_values', __( 'Values', 'better-payment' ), 'sum_value_', $tile . ' strong' ),
            self::text_section( '_sum_labels', __( 'Labels', 'better-payment' ), 'sum_label_', $tile . ' span' ),
        ];
    }

    /**
     * Organizer: avatar, name, role and description.
     */
    public static function organizer(): array {
        $avatar = '{{EL}} .bp-organizer-avatar img';

        return [
            self::section(
                '_org_avatar',
                __( 'Avatar', 'better-payment' ),
                array_merge(
                    [
                        self::size( 'org_avatar_size', __( 'Size', 'better-payment' ), [ $avatar => 'width: {{VALUE}}; height: {{VALUE}}' ], 16, 240 ),
                        self::dimensions( 'org_avatar_radius', __( 'Border Radius', 'better-payment' ), [ $avatar => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    ],
                    self::border( 'org_avatar_', $avatar )
                )
            ),
            self::text_section( '_org_name', __( 'Name', 'better-payment' ), 'org_name_', '{{EL}} .bp-organizer-name' ),
            self::text_section( '_org_role', __( 'Role', 'better-payment' ), 'org_role_', '{{EL}} .bp-organizer-role' ),
            self::text_section( '_org_description', __( 'Description', 'better-payment' ), 'org_desc_', '{{EL}} .bp-organizer-description' ),
        ];
    }

    /**
     * Social Sharing / Social Links: the headline and the icons in Normal /
     * Hover, their size, shape and spacing. One group for both widgets — they
     * print the same markup.
     */
    public static function social_icons(): array {
        $icon  = '{{EL}} .bp-social-icons .bp-social-icon';
        $hover = $icon . ':hover, ' . $icon . ':focus-visible';

        return [
            self::text_section( '_soc_headline', __( 'Headline', 'better-payment' ), 'soc_headline_', '{{EL}} .bp-social-headline' ),
            self::section(
                '_soc_icons',
                __( 'Icons', 'better-payment' ),
                [
                    self::state_tabs(
                        '_soc_states',
                        [
                            self::color( 'soc_icon_color', __( 'Icon Color', 'better-payment' ), [ $icon => 'color: {{VALUE}}' ] ),
                            self::color( 'soc_background', __( 'Background', 'better-payment' ), [ $icon => 'background-color: {{VALUE}}' ] ),
                            self::color( 'soc_border_color', __( 'Border Color', 'better-payment' ), [ $icon => 'border-color: {{VALUE}}' ] ),
                        ],
                        [
                            self::color( 'soc_icon_color_hover', __( 'Icon Color', 'better-payment' ), [ $hover => 'color: {{VALUE}}' ] ),
                            self::color( 'soc_background_hover', __( 'Background', 'better-payment' ), [ $hover => 'background-color: {{VALUE}}' ] ),
                            self::color( 'soc_border_color_hover', __( 'Border Color', 'better-payment' ), [ $hover => 'border-color: {{VALUE}}' ] ),
                        ]
                    ),
                    self::size( 'soc_size', __( 'Button Size', 'better-payment' ), [ $icon => 'width: {{VALUE}}; height: {{VALUE}}' ], 20, 120 ),
                    self::size( 'soc_icon_size', __( 'Icon Size', 'better-payment' ), [ $icon . ' svg' => 'width: {{VALUE}}; height: {{VALUE}}' ], 8, 80 ),
                    self::dimensions( 'soc_border_radius', __( 'Border Radius', 'better-payment' ), [ $icon => 'border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px', '%' ], [ 'min' => 0 ] ),
                    self::dimensions( 'soc_border_width', __( 'Border Width', 'better-payment' ), [ $icon => 'border-width: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}' ], [ 'px' ], [ 'min' => 0 ] ),
                    self::size( 'soc_gap', __( 'Gap', 'better-payment' ), [ '{{EL}} .bp-social-icons' => 'gap: {{VALUE}}' ], 0, 60 ),
                ]
            ),
        ];
    }

    /**
     * Walk a settingsSchema and yield every leaf control, descending into
     * sections and state tabs. Containers themselves are not yielded.
     *
     * @param array $controls settingsSchema (or a section's children).
     * @return array<int, array<string, mixed>>
     */
    public static function leaf_controls( array $controls ): array {
        $out = [];
        foreach ( $controls as $control ) {
            if ( ! is_array( $control ) ) {
                continue;
            }
            $type = $control['type'] ?? '';
            if ( 'tabs' === $type ) {
                foreach ( (array) ( $control['tabs'] ?? [] ) as $tab ) {
                    if ( is_array( $tab ) ) {
                        $out = array_merge( $out, self::leaf_controls( (array) ( $tab['children'] ?? [] ) ) );
                    }
                }
                continue;
            }
            if ( ! empty( $control['children'] ) && is_array( $control['children'] ) ) {
                $out = array_merge( $out, self::leaf_controls( $control['children'] ) );
                continue;
            }
            if ( in_array( $type, [ 'section', 'section_label', 'note' ], true ) ) {
                continue;
            }
            $out[] = $control;
        }

        return $out;
    }

    /**
     * Setting keys the AI must never see or write for an element: every leaf
     * control marked `ai => false`, or inside a container marked so.
     *
     * @param array $controls settingsSchema.
     * @return array<int, string>
     */
    public static function ai_hidden_keys( array $controls ): array {
        $keys = [];
        foreach ( $controls as $control ) {
            if ( ! is_array( $control ) ) {
                continue;
            }
            $hidden = isset( $control['ai'] ) && false === $control['ai'];
            if ( $hidden ) {
                foreach ( self::leaf_controls( [ $control ] ) as $leaf ) {
                    if ( ! empty( $leaf['key'] ) ) {
                        $keys[] = (string) $leaf['key'];
                    }
                }
                continue;
            }
            if ( 'tabs' === ( $control['type'] ?? '' ) ) {
                foreach ( (array) ( $control['tabs'] ?? [] ) as $tab ) {
                    $keys = array_merge( $keys, self::ai_hidden_keys( (array) ( $tab['children'] ?? [] ) ) );
                }
            } elseif ( ! empty( $control['children'] ) && is_array( $control['children'] ) ) {
                $keys = array_merge( $keys, self::ai_hidden_keys( $control['children'] ) );
            }
        }

        return array_values( array_unique( $keys ) );
    }

    /**
     * Whether a stored setting key belongs to a hidden (style) control: the key
     * itself, or a variant an add-on stores beside it as `{key}_{suffix}` (Pro's
     * per-device values, e.g. `box_padding_tablet`).
     *
     * @param string $key         Stored setting key.
     * @param array  $hidden_keys From ai_hidden_keys().
     */
    public static function is_hidden_key( string $key, array $hidden_keys ): bool {
        foreach ( $hidden_keys as $hidden ) {
            if ( $key === $hidden || 0 === strpos( $key, $hidden . '_' ) ) {
                return true;
            }
        }

        return false;
    }
}
