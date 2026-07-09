<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers all built-in campaign element types into the ElementRegistry.
 *
 * Mirrors Fluent Forms' DefaultElements.php pattern: a structured PHP
 * definition drives both the PHP renderer and the JS builder's settings panel.
 *
 * Each element schema shape:
 * [
 *   'type'            => string,    // unique key
 *   'label'           => string,    // display name in builder
 *   'icon'            => string,    // dashicons suffix (without 'dashicons-')
 *   'defaultSettings' => array,     // settings applied when element is dropped
 *   'settingsSchema'  => array,     // controls shown in the right panel
 * ]
 *
 * settingsSchema control shape:
 * [
 *   'key'         => string,   // settings key
 *   'label'       => string,   // control label
 *   'type'        => string,   // text | number | color | toggle | select | textarea
 *   'placeholder' => string,   // optional
 *   'min'         => int,      // for number type
 *   'max'         => int,      // for number type
 *   'rows'        => int,      // for textarea type
 *   'options'     => array,    // for select type: [['value'=>..., 'label'=>...], ...]
 * ]
 */
class CampaignElements {

    /**
     * Curated web-safe font-family options shared by typography-enabled elements.
     * Value is the full CSS font stack; empty value inherits the theme font.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function font_family_options(): array {
        $families = [
            ''                                                     => __( 'Default', 'better-payment' ),
            'Arial, Helvetica, sans-serif'                         => 'Arial',
            "'Arial Black', Gadget, sans-serif"                    => 'Arial Black',
            "'Comic Sans MS', cursive, sans-serif"                 => 'Comic Sans MS',
            "'Courier New', Courier, monospace"                    => 'Courier New',
            'Georgia, serif'                                       => 'Georgia',
            "'Helvetica Neue', Helvetica, Arial, sans-serif"       => 'Helvetica',
            'Impact, Charcoal, sans-serif'                         => 'Impact',
            "'Lucida Sans Unicode', 'Lucida Grande', sans-serif"   => 'Lucida Sans',
            "'Palatino Linotype', 'Book Antiqua', Palatino, serif" => 'Palatino',
            'Tahoma, Geneva, sans-serif'                           => 'Tahoma',
            "'Times New Roman', Times, serif"                      => 'Times New Roman',
            "'Trebuchet MS', Helvetica, sans-serif"                => 'Trebuchet MS',
            'Verdana, Geneva, sans-serif'                          => 'Verdana',
        ];

        $options = [];
        foreach ( $families as $value => $label ) {
            $options[] = [ 'value' => $value, 'label' => $label ];
        }

        return $options;
    }

    /**
     * Font-style options shared by typography-enabled elements.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function font_style_options(): array {
        return [
            [ 'value' => '',        'label' => __( 'Default', 'better-payment' ) ],
            [ 'value' => 'normal',  'label' => __( 'Normal', 'better-payment' ) ],
            [ 'value' => 'italic',  'label' => __( 'Italic', 'better-payment' ) ],
            [ 'value' => 'oblique', 'label' => __( 'Oblique', 'better-payment' ) ],
        ];
    }

    /**
     * Font-weight options shared by typography-enabled elements.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function font_weight_options(): array {
        return [
            [ 'value' => '',    'label' => __( 'Default', 'better-payment' ) ],
            [ 'value' => '100', 'label' => __( '100 (Thin)', 'better-payment' ) ],
            [ 'value' => '200', 'label' => __( '200 (Extra Light)', 'better-payment' ) ],
            [ 'value' => '300', 'label' => __( '300 (Light)', 'better-payment' ) ],
            [ 'value' => '400', 'label' => __( '400 (Normal)', 'better-payment' ) ],
            [ 'value' => '500', 'label' => __( '500 (Medium)', 'better-payment' ) ],
            [ 'value' => '600', 'label' => __( '600 (Semi Bold)', 'better-payment' ) ],
            [ 'value' => '700', 'label' => __( '700 (Bold)', 'better-payment' ) ],
            [ 'value' => '800', 'label' => __( '800 (Extra Bold)', 'better-payment' ) ],
            [ 'value' => '900', 'label' => __( '900 (Black)', 'better-payment' ) ],
        ];
    }

    /**
     * Text-transform options shared by typography-enabled elements.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function text_transform_options(): array {
        return [
            [ 'value' => '',           'label' => __( 'Default', 'better-payment' ) ],
            [ 'value' => 'uppercase',  'label' => __( 'Uppercase', 'better-payment' ) ],
            [ 'value' => 'lowercase',  'label' => __( 'Lowercase', 'better-payment' ) ],
            [ 'value' => 'capitalize', 'label' => __( 'Capitalize', 'better-payment' ) ],
            [ 'value' => 'none',       'label' => __( 'Normal', 'better-payment' ) ],
        ];
    }

    /**
     * Text-decoration options shared by typography-enabled elements.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function text_decoration_options(): array {
        return [
            [ 'value' => '',             'label' => __( 'Default', 'better-payment' ) ],
            [ 'value' => 'underline',    'label' => __( 'Underline', 'better-payment' ) ],
            [ 'value' => 'overline',     'label' => __( 'Overline', 'better-payment' ) ],
            [ 'value' => 'line-through', 'label' => __( 'Line Through', 'better-payment' ) ],
            [ 'value' => 'none',         'label' => __( 'None', 'better-payment' ) ],
        ];
    }

    /**
     * Full typography control group shared by typography-enabled elements,
     * wrapped in a single collapsible "Typography" section. Mirrors Elementor's
     * Typography group (Family, Size, Weight, Transform, Style, Decoration, Line
     * Height, Letter Spacing, Word Spacing, Color).
     *
     * @param array $overrides Per-control overrides keyed by control key
     *                         (e.g. [ 'font_size' => [ 'defaultValue' => 32 ] ]).
     *                         Values are merged into that control descriptor.
     * @return array<int, array<string, mixed>> A single-element array holding the
     *                         collapsible section (type 'section') whose `children`
     *                         are the individual typography controls.
     */
    private static function typography_schema( array $overrides = [], string $prefix = '', string $section_label = '', string $section_key = '_typography' ): array {
        if ( '' === $section_label ) {
            $section_label = __( 'Typography', 'better-payment' );
        }
        $children = [
            [
                'key'          => 'font_family',
                'label'        => __( 'Font Family', 'better-payment' ),
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => self::font_family_options(),
            ],
            [
                'key'   => 'font_size',
                'label' => __( 'Font Size (px)', 'better-payment' ),
                'type'  => 'number',
                'min'   => 8,
                'max'   => 200,
            ],
            [
                'key'          => 'font_weight',
                'label'        => __( 'Font Weight', 'better-payment' ),
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => self::font_weight_options(),
            ],
            [
                'key'          => 'text_transform',
                'label'        => __( 'Text Transform', 'better-payment' ),
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => self::text_transform_options(),
            ],
            [
                'key'          => 'font_style',
                'label'        => __( 'Font Style', 'better-payment' ),
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => self::font_style_options(),
            ],
            [
                'key'          => 'text_decoration',
                'label'        => __( 'Text Decoration', 'better-payment' ),
                'type'         => 'select',
                'defaultValue' => '',
                'options'      => self::text_decoration_options(),
            ],
            [
                'key'   => 'line_height',
                'label' => __( 'Line Height (px)', 'better-payment' ),
                'type'  => 'number',
                'min'   => 0,
                'max'   => 400,
            ],
            [
                'key'   => 'letter_spacing',
                'label' => __( 'Letter Spacing (px)', 'better-payment' ),
                'type'  => 'number',
                'min'   => -20,
                'max'   => 100,
            ],
            [
                'key'   => 'word_spacing',
                'label' => __( 'Word Spacing (px)', 'better-payment' ),
                'type'  => 'number',
                'min'   => -20,
                'max'   => 100,
            ],
            [
                'key'   => 'color',
                'label' => __( 'Text Color', 'better-payment' ),
                'type'  => 'color',
            ],
        ];

        // Overrides are keyed by the BASE control key (e.g. 'font_size'), applied
        // before the prefix so callers don't need to know the prefix.
        if ( ! empty( $overrides ) ) {
            foreach ( $children as $i => $control ) {
                if ( isset( $overrides[ $control['key'] ] ) ) {
                    $children[ $i ] = array_merge( $control, $overrides[ $control['key'] ] );
                }
            }
        }

        // Prefix the control keys so a single element can carry more than one
        // independent typography set. The Description widget uses this to style
        // its title and body separately ('title_font_family' vs 'font_family');
        // the renderer strips the prefix before applying the CSS.
        if ( '' !== $prefix ) {
            foreach ( $children as $i => $control ) {
                $children[ $i ]['key'] = $prefix . $control['key'];
            }
        }

        // Return the controls wrapped in a single collapsible section (type =>
        // 'section' with children) instead of a flat section_label + siblings.
        // The builder renders this as one expandable accordion (see
        // RightSidebar.js CollapsibleSection).
        return [
            [
                'key'         => $section_key,
                'label'       => $section_label,
                'type'        => 'section',
                'collapsible' => true,
                'collapsed'   => true,
                'children'    => $children,
            ],
        ];
    }

    public static function register_all(): void {

        ElementRegistry::register( 'campaign_title', [
            'label'           => __( 'Campaign Title', 'better-payment' ),
            'icon'            => 'heading',
            // Typography keys are intentionally NOT seeded here — their absence is
            // how the renderer distinguishes a user override (emitted with
            // !important to beat template styles) from the template default.
            'defaultSettings' => [
                'align' => 'left',
            ],
            'settingsSchema'  => array_merge(
                [
                    [
                        'key'         => 'title',
                        'label'       => __( 'Campaign Title', 'better-payment' ),
                        'type'        => 'text',
                        'placeholder' => __( 'Campaign name', 'better-payment' ),
                    ],
                ],
                self::typography_schema( [
                    'font_size' => [ 'defaultValue' => 32 ],
                    'color'     => [ 'defaultValue' => '#1a1a2e' ],
                ] ),
                [
                    [
                        'key'   => 'align',
                        'label' => __( 'Align', 'better-payment' ),
                        'type'  => 'align',
                    ],
                ]
            ),
        ] );

        ElementRegistry::register( 'campaign_description', [
            'label'           => __( 'Campaign Description', 'better-payment' ),
            'icon'            => 'editor-paragraph',
            // Typography keys are intentionally NOT seeded — their absence marks a
            // user override, which the renderer emits with !important so it wins
            // over template styles.
            'defaultSettings' => [
                'headline' => 'Campaign Description',
                'content'  => "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).",
                'width'    => 100,
                'align'    => 'left',
            ],
            'settingsSchema'  => array_merge(
                [
                    [
                        'key'         => 'headline',
                        'label'       => __( 'Headline', 'better-payment' ),
                        'type'        => 'text',
                        'placeholder' => __( 'Headline', 'better-payment' ),
                    ],
                ],
                // Title Typography — styles the headline (h3) only. Uses `title_`
                // prefixed keys so it stays independent of the body typography.
                self::typography_schema(
                    [
                        'font_size' => [
                            'info' => __( 'Leave empty to use the heading default size.', 'better-payment' ),
                        ],
                    ],
                    'title_',
                    __( 'Title Typography', 'better-payment' ),
                    '_title_typography'
                ),
                [
                    [
                        'key'   => 'content',
                        'label' => __( 'Campaign Description', 'better-payment' ),
                        'type'  => 'rich_text',
                        'info'  => __( 'Supports bold, italic, underline, links, and lists.', 'better-payment' ),
                    ],
                ],
                // Description Typography — styles the body content. Keeps the base
                // (unprefixed) keys so previously-saved description typography
                // continues to apply to the content.
                self::typography_schema(
                    [
                        'font_size' => [
                            'placeholder' => '16',
                            'info'        => __( 'Leave empty to use the template default size.', 'better-payment' ),
                        ],
                    ],
                    '',
                    __( 'Description Typography', 'better-payment' ),
                    '_content_typography'
                ),
                [
                    [
                        'key'          => 'width',
                        'label'        => __( 'Width', 'better-payment' ),
                        'type'         => 'range',
                        'min'          => 10,
                        'max'          => 100,
                        'step'         => 1,
                        'unit'         => '%',
                        'defaultValue' => 100,
                        'info'         => __( 'Content width as a percentage of its container.', 'better-payment' ),
                    ],
                    [
                        'key'   => 'align',
                        'label' => __( 'Align', 'better-payment' ),
                        'type'  => 'align',
                    ],
                ]
            ),
        ] );

        ElementRegistry::register( 'photo', [
            'label'           => __( 'Photo', 'better-payment' ),
            'icon'            => 'format-image',
            'defaultSettings' => [
                'src'       => '',
                'src_id'    => 0,
                'src_sizes' => [],
                'alt'       => '',
                'size'      => 'full',
                'width'     => 100,
                'align'     => 'center',
            ],
            'settingsSchema'  => [
                [
                    'key'   => 'src',
                    'label' => __( 'Choose Image', 'better-payment' ),
                    'type'  => 'image_upload',
                    'info'  => __( 'Paste an external image URL or select from the media library.', 'better-payment' ),
                ],
                [
                    'key'         => 'alt',
                    'label'       => __( 'ALT Text', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Describe the image…', 'better-payment' ),
                    'info'        => __( 'Describes the image for screen readers and when the image fails to load.', 'better-payment' ),
                ],
                [
                    'key'          => 'size',
                    'label'        => __( 'Image Resolution', 'better-payment' ),
                    'type'         => 'select',
                    'defaultValue' => 'full',
                    'options'      => [
                        [ 'value' => 'thumbnail',    'label' => __( 'Thumbnail (150×150)', 'better-payment' ) ],
                        [ 'value' => 'medium',       'label' => __( 'Medium (300×300)', 'better-payment' ) ],
                        [ 'value' => 'medium_large', 'label' => __( 'Medium Large (768×auto)', 'better-payment' ) ],
                        [ 'value' => 'large',        'label' => __( 'Large (1024×1024)', 'better-payment' ) ],
                        [ 'value' => 'full',         'label' => __( 'Full (Original)', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'          => 'width',
                    'label'        => __( 'Width', 'better-payment' ),
                    'type'         => 'range',
                    'min'          => 10,
                    'max'          => 100,
                    'step'         => 1,
                    'unit'         => '%',
                    'defaultValue' => 100,
                    'info'         => __( 'Image width as a percentage of its container.', 'better-payment' ),
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ] );

        ElementRegistry::register( 'progress_bar', [
            'label'           => __( 'Progress Bar', 'better-payment' ),
            'icon'            => 'chart-bar',
            'defaultSettings' => [
                'headline'      => 'Campaign Progress',
                'show_donated'  => true,
                'show_goal'     => true,
                'round_amounts' => false,
                'donate_label'  => 'Donated:',
                'goal_label'    => 'Goal:',
                'width'         => 100,
                'align'         => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'headline',
                    'label'        => __( 'Headline', 'better-payment' ),
                    'type'         => 'text',
                    'defaultValue' => 'Campaign Progress',
                ],
                [
                    'key'   => '_campaign_info',
                    'label' => __( 'Campaign Information', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'          => 'show_donated',
                    'label'        => __( 'Show Donated', 'better-payment' ),
                    'type'         => 'switch',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'show_goal',
                    'label'        => __( 'Show Goal', 'better-payment' ),
                    'type'         => 'switch',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'round_amounts',
                    'label'        => __( 'Round Amounts', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => false,
                ],
                [
                    'key'          => 'donate_label',
                    'label'        => __( 'Donate Label:', 'better-payment' ),
                    'type'         => 'text',
                    'defaultValue' => 'Donated:',
                ],
                [
                    'key'          => 'goal_label',
                    'label'        => __( 'Goal Label:', 'better-payment' ),
                    'type'         => 'text',
                    'defaultValue' => 'Goal:',
                ],
                [
                    'key'          => 'width',
                    'label'        => __( 'Width', 'better-payment' ),
                    'type'         => 'range',
                    'min'          => 10,
                    'max'          => 100,
                    'step'         => 1,
                    'unit'         => '%',
                    'defaultValue' => 100,
                    'info'         => __( 'Controls the width of the progress bar relative to its container.', 'better-payment' ),
                ],
                [
                    'key'          => 'align',
                    'label'        => __( 'Align', 'better-payment' ),
                    'type'         => 'align',
                    'defaultValue' => 'left',
                ],
            ],
        ] );

        ElementRegistry::register( 'campaign_summary', [
            'label'           => __( 'Campaign Summary', 'better-payment' ),
            'icon'            => 'info',
            'defaultSettings' => [
                'headline'     => 'Campaign Summary',
                'show_raised'  => true,
                'show_donors'  => true,
                'show_percent' => true,
                'show_days'    => true,
                'width'        => 100,
                'align'        => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'headline',
                    'label'        => __( 'Headline', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => '',
                    'defaultValue' => 'Campaign Summary',
                ],
                [
                    'key'   => 'choose_label',
                    'label' => __( 'Choose what information to show:', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'   => 'summary_note',
                    'label' => __( 'Note: All enabled items are always shown. Amount Donated and Number of Donors update as donations come in. Percent Raised shows 0% until a campaign goal is set, and Time Remaining shows 0 days left until an end date is set. Save and refresh the builder to see updated values.', 'better-payment' ),
                    'type'  => 'note',
                ],
                [
                    'key'   => 'show_raised',
                    'label' => __( 'Amount Donated', 'better-payment' ),
                    'type'  => 'toggle',
                ],
                [
                    'key'   => 'show_donors',
                    'label' => __( 'Number of Donors', 'better-payment' ),
                    'type'  => 'toggle',
                ],
                [
                    'key'   => 'show_percent',
                    'label' => __( 'Percent Raised', 'better-payment' ),
                    'type'  => 'toggle',
                ],
                [
                    'key'   => 'show_days',
                    'label' => __( 'Time Remaining', 'better-payment' ),
                    'type'  => 'toggle',
                ],
                [
                    'key'   => 'width',
                    'label' => __( 'Width', 'better-payment' ),
                    'type'  => 'range',
                    'min'   => 10,
                    'max'   => 100,
                    'unit'  => '%',
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ] );

        ElementRegistry::register( 'donation_form', [
            'label'           => __( 'Donate Button', 'better-payment' ),
            'icon'            => 'heart',
            'defaultSettings' => [
                'button_label' => 'Donate Now',
                'button_color' => '',
                'url'          => '#',
                'open_new_tab' => false,
                'width'        => 100,
                'align'        => 'center',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'button_label',
                    'label'        => __( 'Button Label', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => __( 'Donate Now', 'better-payment' ),
                    'defaultValue' => 'Donate Now',
                ],
                [
                    'key'   => 'button_color',
                    'label' => __( 'Button Color', 'better-payment' ),
                    'type'  => 'color',
                    'info'  => __( 'Leave empty to use the campaign primary color.', 'better-payment' ),
                ],
                [
                    'key'          => 'url',
                    'label'        => __( 'Payment Form Page URL', 'better-payment' ),
                    'type'         => 'url',
                    'placeholder'  => 'https://',
                    'defaultValue' => '#',
                    'info'        => __( 'Link to the page containing your donation form. Overrides the Donation Page set in General Settings.', 'better-payment' ),
                ],
                [
                    'key'          => 'open_new_tab',
                    'label'        => __( 'Open Links In New Tab', 'better-payment' ),
                    'type'         => 'switch',
                    'defaultValue' => false,
                ],
                [
                    'key'          => 'width',
                    'label'        => __( 'Width', 'better-payment' ),
                    'type'         => 'range',
                    'min'          => 10,
                    'max'          => 100,
                    'step'         => 1,
                    'defaultValue' => 100,
                ],
                [
                    'key'          => 'align',
                    'label'        => __( 'Align', 'better-payment' ),
                    'type'         => 'align',
                    'defaultValue' => 'center',
                ],
            ],
        ] );

        // Build WP users list for the Campaign Creator select.
        $wp_users        = get_users( [ 'fields' => [ 'ID', 'display_name', 'user_email' ] ] );
        $creator_options = array_values( array_map( function ( $u ) {
            return [
                'value'      => (int) $u->ID,
                'label'      => $u->display_name,
                'avatar_url' => get_avatar_url( $u->user_email, [ 'size' => 48, 'default' => 'mysteryman' ] ),
            ];
        }, $wp_users ) );

        ElementRegistry::register( 'organizer', [
            'label'           => __( 'Organizer', 'better-payment' ),
            'icon'            => 'admin-users',
            'defaultSettings' => [
                'creator_user_id' => get_current_user_id(),
                'role_title'      => 'Organizer',
                'description'     => '',
                'width'           => 100,
                'align'           => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'role_title',
                    'label'        => __( 'Role or Title', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => 'Organizer',
                    'defaultValue' => 'Organizer',
                    'info'         => __( 'Shown beneath the creator\'s name (e.g. Organizer, Founder).', 'better-payment' ),
                ],
                [
                    'key'     => 'creator_user_id',
                    'label'   => __( 'Campaign Creator', 'better-payment' ),
                    'type'    => 'select',
                    'options' => $creator_options,
                ],
                [
                    'key'   => 'description',
                    'label' => __( 'Organizer Description', 'better-payment' ),
                    'type'  => 'rich_text',
                    'info'  => __( 'Brief bio or message shown beneath the creator\'s name.', 'better-payment' ),
                ],
                [
                    'key'          => 'width',
                    'label'        => __( 'Width', 'better-payment' ),
                    'type'         => 'range',
                    'min'          => 10,
                    'max'          => 100,
                    'step'         => 1,
                    'defaultValue' => 100,
                    'info'         => __( 'Width of the organizer block as a percentage.', 'better-payment' ),
                ],
                [
                    'key'          => 'align',
                    'label'        => __( 'Align', 'better-payment' ),
                    'type'         => 'align',
                    'defaultValue' => 'left',
                ],
            ],
        ] );

        ElementRegistry::register( 'donate_amount', [
            'label'           => __( 'Donate Amount', 'better-payment' ),
            'icon'            => 'money-alt',
            'defaultSettings' => [
                'headline' => 'Donate Amount',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'headline',
                    'label'        => __( 'Headline', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => __( 'Headline', 'better-payment' ),
                    'defaultValue' => 'Donate Amount',
                    'info'         => __( 'Optional heading shown above the donation amounts.', 'better-payment' ),
                ],
                [
                    'key'     => 'bpc_minimum_amount',
                    'label'   => __( 'Minimum Donation Amount', 'better-payment' ),
                    'type'    => 'currency',
                    'metaKey' => 'bpc_minimum_amount',
                    'info'    => __( 'Leave empty to allow no restrictions on how small the donation can be.', 'better-payment' ),
                ],
                [
                    'key'     => 'bpc_suggested_amounts',
                    'label'   => __( 'Suggested Donation Amounts', 'better-payment' ),
                    'type'    => 'suggested_amounts',
                    'metaKey' => 'bpc_suggested_amounts',
                ],
                [
                    'key'          => 'bpc_allow_custom_amount',
                    'label'        => __( 'Allow Custom Amount', 'better-payment' ),
                    'type'         => 'switch',
                    'metaKey'      => 'bpc_allow_custom_amount',
                    'defaultValue' => true,
                ],
            ],
        ] );

        ElementRegistry::register( 'social_sharing', [
            'label'           => __( 'Social Sharing', 'better-payment' ),
            'icon'            => 'share',
            'defaultSettings' => [
                'headline'     => 'Share Now',
                'twitter'      => true,
                'facebook'     => true,
                'linkedin'     => true,
                'pinterest'    => true,
                'mastodon'     => true,
                'threads'      => true,
                'bluesky'      => true,
                'open_new_tab' => true,
                'align'        => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'headline',
                    'label'        => __( 'Headline', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => __( 'Headline', 'better-payment' ),
                    'defaultValue' => 'Share Now',
                ],
                [
                    'key'   => '_networks',
                    'label' => __( 'Choose your social network(s):', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'          => 'twitter',
                    'label'        => __( 'Twitter / X', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'facebook',
                    'label'        => __( 'Facebook', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'linkedin',
                    'label'        => __( 'LinkedIn', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'pinterest',
                    'label'        => __( 'Pinterest', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'mastodon',
                    'label'        => __( 'Mastodon', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'threads',
                    'label'        => __( 'Threads', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'bluesky',
                    'label'        => __( 'Bluesky', 'better-payment' ),
                    'type'         => 'toggle',
                    'defaultValue' => true,
                ],
                [
                    'key'          => 'open_new_tab',
                    'label'        => __( 'Open Links In New Tab', 'better-payment' ),
                    'type'         => 'switch',
                    'defaultValue' => true,
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ] );

        ElementRegistry::register( 'social_links', [
            'label'           => __( 'Social Links', 'better-payment' ),
            'icon'            => 'admin-links',
            'defaultSettings' => [
                'headline'     => 'Follow Now',
                'twitter'      => 'https://twitter.com/',
                'facebook'     => 'https://facebook.com/',
                'linkedin'     => 'https://linkedin.com/',
                'instagram'    => '',
                'tiktok'       => '',
                'pinterest'    => '',
                'youtube'      => '',
                'threads'      => '',
                'bluesky'      => '',
                'mastodon'     => '',
                'open_new_tab' => true,
                'align'        => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'          => 'headline',
                    'label'        => __( 'Headline', 'better-payment' ),
                    'type'         => 'text',
                    'placeholder'  => __( 'Headline', 'better-payment' ),
                    'defaultValue' => 'Follow Now',
                ],
                [
                    'key'         => 'twitter',
                    'label'       => __( 'Twitter / X URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'facebook',
                    'label'       => __( 'Facebook URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'linkedin',
                    'label'       => __( 'LinkedIn URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'instagram',
                    'label'       => __( 'Instagram URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'tiktok',
                    'label'       => __( 'TikTok URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'pinterest',
                    'label'       => __( 'Pinterest URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'youtube',
                    'label'       => __( 'YouTube URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'threads',
                    'label'       => __( 'Threads URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'bluesky',
                    'label'       => __( 'Bluesky URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'         => 'mastodon',
                    'label'       => __( 'Mastodon URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
                ],
                [
                    'key'          => 'open_new_tab',
                    'label'        => __( 'Open Links In New Tab', 'better-payment' ),
                    'type'         => 'switch',
                    'defaultValue' => true,
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ] );
    }
}
