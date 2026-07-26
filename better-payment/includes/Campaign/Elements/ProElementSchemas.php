<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Control schemas for the three Pro-only campaign elements — Donors Wall, FAQ
 * and Video.
 *
 * These live in Lite, not Pro, on purpose. Lite has to render the *complete*
 * control set for a Pro element (disabled, with a locked banner) so a free user
 * can see exactly what Pro buys them. That needs the schema, and a schema is
 * pure declarative data — labels, control types, option lists. It carries no
 * business logic, no data access and no markup, so nothing sensitive crosses the
 * boundary by living here.
 *
 * Keeping it here also means there is exactly ONE copy. When Pro owned these and
 * overwrote Lite's stubs, Lite could only show an empty panel; giving Lite its
 * own copy instead would have created two 350-line arrays that drift the first
 * time someone adds a control to Pro and forgets Lite — at which point the free
 * preview quietly misrepresents the product.
 *
 * What stays in Pro: every renderer, the donor queries, oEmbed/HTTP, the view
 * templates. See `Better_Payment\Pro\Campaign\BuilderElements`.
 *
 * The `pro => true` flag is attached by {@see CampaignElements::register_pro_elements()},
 * not here, and is kept on the schema whether or not Pro is active — the live
 * `better_payment/pro_enabled` filter is what decides locking, never a stored value.
 */
class ProElementSchemas {

    /**
     * The video a newly added Video element starts with, so a freshly dropped
     * element isn't a blank box.
     *
     * Lives here rather than in Pro because it is part of `defaultSettings`,
     * which is part of the schema. Pro keeps a back-compat alias.
     */
    const DEFAULT_VIDEO_URL = 'https://www.youtube.com/watch?v=jeXG-4SOlZE';

    /**
     * Seconds into {@see self::DEFAULT_VIDEO_URL} that playback starts.
     */
    const DEFAULT_VIDEO_START = 2;

    /**
     * Filterable default video URL.
     *
     * @return string
     */
    public static function default_video_url(): string {
        return (string) apply_filters( 'better_payment/campaign/default_video_url', self::DEFAULT_VIDEO_URL );
    }

    /**
     * All three schemas, keyed by element type.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        return [
            'donors_wall' => self::donors_wall(),
            'faq'         => self::faq(),
            'video'       => self::video(),
        ];
    }

    /**
     * @return array
     */
    public static function donors_wall(): array {
        return [
            'label'           => __( 'Donors Wall', 'better-payment' ),
            'icon'            => 'groups',
            'defaultSettings' => [
                'headline'       => __( 'Recent Donors', 'better-payment' ),
                'layout'         => 'list',
                'columns'        => 2,
                'number_to_show' => 10,
                'order_by'       => 'date',
                'order'          => 'desc',
                'hide_if_empty'  => false,
                'show_summary'   => true,
                'show_name'      => true,
                'show_amount'    => true,
                'show_avatar'    => true,
                'show_date'      => true,
                'accent_color'   => '',
                'width'          => 100,
                'align'          => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'headline',
                    'label'       => __( 'Headline', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Recent Donors', 'better-payment' ),
                ],
                [
                    'key'     => 'layout',
                    'label'   => __( 'Layout', 'better-payment' ),
                    'type'    => 'select',
                    'options' => [
                        [ 'value' => 'list',   'label' => __( 'List (vertical)', 'better-payment' ) ],
                        [ 'value' => 'grid',   'label' => __( 'Grid', 'better-payment' ) ],
                        [ 'value' => 'ticker', 'label' => __( 'Ticker (auto-scroll)', 'better-payment' ) ],
                    ],
                ],
                // A select, not a range: three discrete values are chosen, not
                // dialled. It was a slider whose value read "4%" — the builder's
                // range control used to assume every range was a percentage.
                // Shown only for the grid, where columns mean anything at all.
                [
                    'key'          => 'columns',
                    'label'        => __( 'Grid Columns', 'better-payment' ),
                    'type'         => 'select',
                    'defaultValue' => 2,
                    'condition'    => [ 'layout' => 'grid' ],
                    'options'      => [
                        [ 'value' => 2, 'label' => __( '2 columns', 'better-payment' ) ],
                        [ 'value' => 3, 'label' => __( '3 columns', 'better-payment' ) ],
                        [ 'value' => 4, 'label' => __( '4 columns', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'          => 'number_to_show',
                    'label'        => __( 'Number of Donors To Show', 'better-payment' ),
                    'type'         => 'number',
                    'min'          => 0,
                    'max'          => 100,
                    'defaultValue' => 10,
                    'info'         => __( 'Set to 0 to hide the donor list.', 'better-payment' ),
                ],
                [
                    'key'     => 'order_by',
                    'label'   => __( 'Order By', 'better-payment' ),
                    'type'    => 'select',
                    'options' => [
                        [ 'value' => 'date',   'label' => __( 'Date', 'better-payment' ) ],
                        [ 'value' => 'amount', 'label' => __( 'Amount', 'better-payment' ) ],
                    ],
                ],
                // Two variants of the same `order` control, gated on `order_by`,
                // so the direction labels read naturally for what's being sorted.
                // Both write the same `order` key with the same `desc`/`asc`
                // values — the renderer is unchanged and the chosen value survives
                // switching Order By. Only one is ever visible (isControlVisible).
                [
                    'key'       => 'order',
                    'label'     => __( 'Order', 'better-payment' ),
                    'type'      => 'select',
                    'condition' => [ 'order_by' => 'amount' ],
                    'options'   => [
                        [ 'value' => 'desc', 'label' => __( 'High to Low', 'better-payment' ) ],
                        [ 'value' => 'asc',  'label' => __( 'Low to High', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'       => 'order',
                    'label'     => __( 'Order', 'better-payment' ),
                    'type'      => 'select',
                    'condition' => [ 'order_by' => 'date' ],
                    'options'   => [
                        [ 'value' => 'desc', 'label' => __( 'Newest First', 'better-payment' ) ],
                        [ 'value' => 'asc',  'label' => __( 'Oldest First', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'   => 'hide_if_empty',
                    'label' => __( 'Hide If No Donors', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => '_donor_info_label',
                    'label' => __( 'Donor Information', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'   => 'show_summary',
                    'label' => __( 'Show Totals Summary', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'show_name',
                    'label' => __( 'Show Name', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'show_amount',
                    'label' => __( 'Show Amount', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'show_avatar',
                    'label' => __( 'Show Avatar', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'show_date',
                    'label' => __( 'Show Time', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => '_style_label',
                    'label' => __( 'Style', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'   => 'accent_color',
                    'label' => __( 'Accent Color', 'better-payment' ),
                    'type'  => 'color',
                    'info'  => __( 'Leave empty to inherit the campaign color.', 'better-payment' ),
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
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function faq(): array {
        return [
            'label'           => __( 'FAQ', 'better-payment' ),
            'icon'            => 'editor-help',
            'defaultSettings' => [
                'heading'      => __( 'Frequently Asked Questions', 'better-payment' ),
                'items'        => [
                    [
                        'question' => __( 'How will my donation help?', 'better-payment' ),
                        'answer'   => __( 'Your donation will directly support the goals of this campaign and help make a meaningful difference for the people or cause it aims to support.', 'better-payment' ),
                    ],
                    [
                        'question' => __( 'Is my donation secure?', 'better-payment' ),
                        'answer'   => __( 'Yes. Donations are processed through a secure payment system to help keep your payment information safe.', 'better-payment' ),
                    ],
                    [
                        'question' => __( 'Can I donate any amount?', 'better-payment' ),
                        'answer'   => __( 'Yes. Every contribution matters, regardless of the amount. You can donate an amount that feels comfortable for you.', 'better-payment' ),
                    ],
                    [
                        'question' => __( 'Can I share this campaign with others?', 'better-payment' ),
                        'answer'   => __( 'Absolutely! Sharing the campaign with your friends, family, and social network is a great way to help spread awareness and support the cause.', 'better-payment' ),
                    ],
                ],
                'style'        => 'accordion',
                'icon_style'   => 'chevron',
                'single_open'  => true,
                'first_open'   => true,
                'accent_color' => '',
                'width'        => 100,
                'align'        => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'heading',
                    'label'       => __( 'Heading', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Frequently Asked Questions', 'better-payment' ),
                ],
                [
                    'key'        => 'items',
                    'label'      => __( 'FAQ Items', 'better-payment' ),
                    'type'       => 'repeater',
                    'itemLabel'  => __( 'Question', 'better-payment' ),
                    'titleField' => 'question',
                    'addLabel'   => __( 'Add Question', 'better-payment' ),
                    'max'        => 30,
                    'fields'     => [
                        [
                            'key'         => 'question',
                            'label'       => __( 'Question', 'better-payment' ),
                            'type'        => 'text',
                            'placeholder' => __( 'Enter a question', 'better-payment' ),
                        ],
                        [
                            'key'         => 'answer',
                            'label'       => __( 'Answer', 'better-payment' ),
                            'type'        => 'textarea',
                            'rows'        => 3,
                            'placeholder' => __( 'Enter the answer', 'better-payment' ),
                        ],
                        [
                            'key'   => 'open',
                            'label' => __( 'Open by default?', 'better-payment' ),
                            'type'  => 'switch',
                        ],
                    ],
                ],
                [
                    'key'     => 'style',
                    'label'   => __( 'Style', 'better-payment' ),
                    'type'    => 'select',
                    'options' => [
                        [ 'value' => 'accordion', 'label' => __( 'Accordion (collapsible)', 'better-payment' ) ],
                        [ 'value' => 'list',      'label' => __( 'Open list', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'       => 'icon_style',
                    'label'     => __( 'Toggle Icon', 'better-payment' ),
                    'type'      => 'select',
                    // Accordion-only: the open-list style shows every answer, so
                    // there is no collapse toggle to put an icon on.
                    'condition' => [ 'style' => 'accordion' ],
                    'options'   => [
                        [ 'value' => 'chevron', 'label' => __( 'Chevron', 'better-payment' ) ],
                        [ 'value' => 'plus',    'label' => __( 'Plus / Minus', 'better-payment' ) ],
                        [ 'value' => 'none',    'label' => __( 'No icon', 'better-payment' ) ],
                    ],
                ],
                [
                    'key'       => 'single_open',
                    'label'     => __( 'Open One At A Time', 'better-payment' ),
                    'type'      => 'switch',
                    // Accordion-only: an open list has nothing to close.
                    'condition' => [ 'style' => 'accordion' ],
                    'info'      => __( 'Opening a question closes the others (accordion only).', 'better-payment' ),
                ],
                [
                    'key'       => 'first_open',
                    'label'     => __( 'Open First Item By Default', 'better-payment' ),
                    'type'      => 'switch',
                    // Accordion-only: open-list items are all expanded already.
                    'condition' => [ 'style' => 'accordion' ],
                ],
                [
                    'key'   => 'accent_color',
                    'label' => __( 'Accent Color', 'better-payment' ),
                    'type'  => 'color',
                    'info'  => __( 'Leave empty to inherit the campaign color.', 'better-payment' ),
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
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function video(): array {
        return [
            'label'           => __( 'Video', 'better-payment' ),
            'icon'            => 'format-video',
            'defaultSettings' => [
                'url'          => self::default_video_url(),
                'aspect_ratio' => '16-9',
                'autoplay'     => false,
                'muted'        => false,
                'loop'         => false,
                'controls'     => true,
                'start_time'   => self::DEFAULT_VIDEO_START,
                'width'        => 100,
                'align'        => 'center',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'url',
                    'label'       => __( 'Video URL', 'better-payment' ),
                    'type'        => 'url',
                    // Renders a Media Library button beside the field (Lite's
                    // `url` control), so an uploaded video can be chosen without
                    // leaving the builder to copy its URL.
                    'mediaType'   => 'video',
                    'placeholder' => 'https://www.youtube.com/watch?v=…',
                    'info'        => __( 'Paste a YouTube or Vimeo link, or choose an uploaded video from your Media Library.', 'better-payment' ),
                ],
                [
                    'key'     => 'aspect_ratio',
                    'label'   => __( 'Aspect Ratio', 'better-payment' ),
                    'type'    => 'select',
                    'options' => [
                        [ 'value' => '16-9', 'label' => '16:9' ],
                        [ 'value' => '4-3',  'label' => '4:3' ],
                        [ 'value' => '1-1',  'label' => '1:1' ],
                        [ 'value' => '21-9', 'label' => '21:9' ],
                    ],
                ],
                [
                    'key'   => '_playback_label',
                    'label' => __( 'Playback', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'   => 'autoplay',
                    'label' => __( 'Autoplay', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'muted',
                    'label' => __( 'Muted', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'loop',
                    'label' => __( 'Loop', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'   => 'controls',
                    'label' => __( 'Show Controls', 'better-payment' ),
                    'type'  => 'switch',
                ],
                [
                    'key'          => 'start_time',
                    'label'        => __( 'Start Time (seconds)', 'better-payment' ),
                    'type'         => 'number',
                    'min'          => 0,
                    'defaultValue' => 0,
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
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ];
    }
}
