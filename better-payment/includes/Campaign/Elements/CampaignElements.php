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

    public static function register_all(): void {

        ElementRegistry::register( 'campaign_title', [
            'label'           => __( 'Campaign Title', 'better-payment' ),
            'icon'            => 'heading',
            'defaultSettings' => [
                'align' => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'title',
                    'label'       => __( 'Campaign Title', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Campaign name', 'better-payment' ),
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
        ] );

        ElementRegistry::register( 'campaign_description', [
            'label'           => __( 'Campaign Description', 'better-payment' ),
            'icon'            => 'editor-paragraph',
            'defaultSettings' => [
                'headline' => '',
                'content'  => '',
                'width'    => 100,
                'align'    => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'headline',
                    'label'       => __( 'Headline', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Headline', 'better-payment' ),
                ],
                [
                    'key'   => 'content',
                    'label' => __( 'Campaign Description', 'better-payment' ),
                    'type'  => 'rich_text',
                    'info'  => __( 'Supports bold, italic, underline, links, and lists.', 'better-payment' ),
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
                    'info'         => __( 'Content width as a percentage of its container.', 'better-payment' ),
                ],
                [
                    'key'   => 'align',
                    'label' => __( 'Align', 'better-payment' ),
                    'type'  => 'align',
                ],
            ],
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
                'headline'      => '',
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
                    'key'   => 'headline',
                    'label' => __( 'Headline', 'better-payment' ),
                    'type'  => 'text',
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
                'headline'     => '',
                'show_raised'  => true,
                'show_donors'  => true,
                'show_percent' => true,
                'show_days'    => true,
                'width'        => 100,
                'align'        => 'left',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'headline',
                    'label'       => __( 'Headline', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => '',
                ],
                [
                    'key'   => 'choose_label',
                    'label' => __( 'Choose what information to show:', 'better-payment' ),
                    'type'  => 'section_label',
                ],
                [
                    'key'   => 'summary_note',
                    'label' => __( 'Note: Some items might not be visible or enabled depending on campaign goal and date settings. Save and refresh the builder to see updated values.', 'better-payment' ),
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
                'url'          => '',
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
                    'key'         => 'url',
                    'label'       => __( 'Payment Form Page URL', 'better-payment' ),
                    'type'        => 'url',
                    'placeholder' => 'https://',
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
                'headline' => '',
            ],
            'settingsSchema'  => [
                [
                    'key'         => 'headline',
                    'label'       => __( 'Headline', 'better-payment' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Headline', 'better-payment' ),
                    'info'        => __( 'Optional heading shown above the donation amounts.', 'better-payment' ),
                ],
                [
                    'key'     => 'bpc_suggested_amounts',
                    'label'   => __( 'Suggested Donation Amounts', 'better-payment' ),
                    'type'    => 'suggested_amounts',
                    'metaKey' => 'bpc_suggested_amounts',
                ],
                [
                    'key'          => 'bpc_allow_custom_amount',
                    'label'        => __( 'Allow Custom Donations', 'better-payment' ),
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
