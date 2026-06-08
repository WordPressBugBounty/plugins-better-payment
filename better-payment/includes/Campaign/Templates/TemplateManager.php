<?php

namespace Better_Payment\Lite\Campaign\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages campaign layout templates shown in the template selection modal.
 *
 * Templates define the initial page-level column layout that the builder starts
 * with. Each template specifies a layout type (1-column, 2-column, 3-column)
 * and pre-places elements into columns.
 *
 * To register a custom template from another plugin:
 *   add_filter( 'better_payment/campaign_templates', function( $templates ) {
 *       $templates['my-template'] = [ ... ];
 *       return $templates;
 *   } );
 */
class TemplateManager {

    /**
     * Get all available campaign templates.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        $first_users      = get_users( [ 'fields' => [ 'ID' ], 'number' => 1 ] );
        $first_creator_id = ! empty( $first_users ) ? (int) $first_users[0]->ID : get_current_user_id();
        $tpl_img          = BETTER_PAYMENT_ASSETS . '/img/campaign/templates';

        $shared_sidebar = [
            [
                'id'       => 'el_organizer',
                'type'     => 'organizer',
                'settings' => [ 'creator_user_id' => $first_creator_id ],
            ],
            [
                'id'       => 'el_social_sharing',
                'type'     => 'social_sharing',
                'settings' => [
                    'headline'  => 'Share Now',
                    'twitter'   => true,
                    'facebook'  => true,
                    'linkedin'  => true,
                    'pinterest' => true,
                    'mastodon'  => true,
                    'threads'   => false,
                    'bluesky'   => false,
                ],
            ],
            [
                'id'       => 'el_social_links',
                'type'     => 'social_links',
                'settings' => [
                    'headline'  => 'Follow Now',
                    'twitter'   => 'https://twitter.com/',
                    'facebook'  => 'https://facebook.com/',
                    'linkedin'  => 'https://linkedin.com/in/',
                    'instagram' => '',
                    'tiktok'    => '',
                    'pinterest' => '',
                    'youtube'   => '',
                    'threads'   => '',
                    'bluesky'   => '',
                    'mastodon'  => '',
                ],
            ],
        ];

        $templates = [

            'blank-1col' => [
                'key'         => 'blank-1col',
                'category'    => 'blank',
                'tags'        => [],
                'label'       => __( '1 Column', 'better-payment' ),
                'description' => __( 'Single column, vertical stack.', 'better-payment' ),
                'layout'      => '1-column',
                'columns'     => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [],
                    ],
                ],
            ],

            'blank-2col' => [
                'key'         => 'blank-2col',
                'category'    => 'blank',
                'tags'        => [],
                'label'       => __( '2 Column', 'better-payment' ),
                'description' => __( 'Main content area with sidebar.', 'better-payment' ),
                'layout'      => '2-column',
                'columns'     => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '65%',
                        'elements' => [],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '35%',
                        'elements' => [],
                    ],
                ],
            ],

            'blank-3col' => [
                'key'         => 'blank-3col',
                'category'    => 'blank',
                'tags'        => [],
                'label'       => __( '3 Column', 'better-payment' ),
                'description' => __( 'Three equal-width columns.', 'better-payment' ),
                'layout'      => '3-column',
                'columns'     => [
                    [
                        'id'       => 'col1',
                        'label'    => __( 'Column 1', 'better-payment' ),
                        'width'    => '33.33%',
                        'elements' => [],
                    ],
                    [
                        'id'       => 'col2',
                        'label'    => __( 'Column 2', 'better-payment' ),
                        'width'    => '33.33%',
                        'elements' => [],
                    ],
                    [
                        'id'       => 'col3',
                        'label'    => __( 'Column 3', 'better-payment' ),
                        'width'    => '33.33%',
                        'elements' => [],
                    ],
                ],
            ],

            'charity-basic' => [
                'key'           => 'charity-basic',
                'category'      => 'prebuilt',
                'tags'          => [ 'charity' ],
                'preview_image' => 'assets/img/campaign/templates/preview/charity-basic.webp',
                'label'         => __( 'Refugee Relief Fund', 'better-payment' ),
                'default_title' => __( 'Refugee Relief Fund', 'better-payment' ),
                'description'   => __( 'Support refugee communities with immediate aid and long-term recovery.', 'better-payment' ),
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '65%',
                        'elements' => [
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/charity/Refugee-Relief-Fund.webp',
                                    'alt' => 'Humanitarian aid workers helping refugee families',
                                ],
                            ],
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Help Refugees Find Safety, Shelter, and Hope',
                                    'content'  => 'Millions of people are forced to flee their homes due to conflict, persecution, and disaster. Your donation provides immediate relief — emergency shelter, food, clean water, and medical care — to refugee families who have lost everything. Every contribution, no matter the size, helps rebuild lives and restore dignity.',
                                ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => true, 'show_days' => true ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '',
                                    'url'          => '',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '35%',
                        'elements' => $shared_sidebar,
                    ],
                ],
            ],

            'medical-relief' => [
                'key'           => 'medical-relief',
                'category'      => 'prebuilt',
                'tags'          => [ 'medical' ],
                'preview_image' => 'assets/img/campaign/templates/preview/medical-relief.webp',
                'label'         => __( 'Medical Emergency Fund', 'better-payment' ),
                'default_title' => __( 'Medical Emergency Fund', 'better-payment' ),
                'description'   => __( 'Bring hope during medical emergencies through compassionate giving.', 'better-payment' ),
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_desc1',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => '',
                                    'content'  => 'On June 12, 2023 Brook\'s wife Maleena was admitted to hospital while 20 weeks pregnant, where she was diagnosed with Stage 4 B-cell Lymphoma. At just 22 years old, she has been confronted with a life-altering diagnosis and is now facing a grueling fight. After experiencing inflammation and pain in her legs in March, Brooke spoke with her GP and sought treatment from chiropractors and physios.',
                                ],
                            ],
                            [
                                'id'       => 'el_photo1',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/medical/Medical-Emergency-Fund.webp',
                                    'alt' => 'Patient receiving hospital care',
                                ],
                            ],
                            [
                                'id'       => 'el_desc2',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => '',
                                    'content'  => 'Devastated by Maleena\'s diagnosis, Brooke found himself navigating a whirlwind of emotions and responsibilities. He became Maleena\'s unwavering pillar of support, attending every doctor\'s appointment and chemotherapy session with her, his heart aching at the sight of his young wife enduring such pain. The couple, once planning nursery decorations and baby names, now found themselves discussing treatment options and potential outcomes. Despite the overwhelming fear and uncertainty, they clung to each other, drawing strength from their love and the life growing inside Maleena. Friends and family rallied around them, offering words of encouragement and practical help, reminding them that they were not alone in this battle. In the face of adversity, Brooke and Maleena vowed to fight together, holding onto hope and cherishing every moment they had together, knowing that their love would be the guiding light in the darkest of times.',
                                ],
                            ],
                            [
                                'id'       => 'el_photo2',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => 'https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?auto=format&w=740&q=80&fit=crop',
                                    'alt' => 'Doctor providing compassionate care',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => true, 'show_days' => true ],
                            ],
                            [
                                'id'       => 'el_progress',
                                'type'     => 'progress_bar',
                                'settings' => [ 'show_donated' => true, 'show_goal' => true ],
                            ],
                            [
                                'id'       => 'el_funding_note',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => '',
                                    'content'  => 'This project will only be funded if at least $270,000 is raised by December 22, 2025.',
                                ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '',
                                    'url'          => '',
                                ],
                            ],
                            [
                                'id'       => 'el_organizer',
                                'type'     => 'organizer',
                                'settings' => [ 'creator_user_id' => $first_creator_id ],
                            ],
                        ],
                    ],
                ],
            ],

            'education-fund' => [
                'key'           => 'education-fund',
                'category'      => 'prebuilt',
                'tags'          => [ 'education' ],
                'preview_image' => 'assets/img/campaign/templates/preview/education-fund.webp',
                'label'         => __( 'Student Success Fund', 'better-payment' ),
                'default_title' => __( 'Student Success Fund', 'better-payment' ),
                'description'   => __( 'Empower the next generation through education.', 'better-payment' ),
                'theme_class'   => 'school-trip',
                'preview_color' => '#5c6b38',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Raise fund for our school trip!',
                                    'content'  => 'We are announcing a campaign to make the dream of a once-in-a-lifetime field trip come true for our graduating 5th graders: an unforgettable adventure in California! Your support will enable us to purchase necessary supplies, ensure safe travel, and provide accommodations for both parents and teachers accompanying the students. With your contribution, we can create lasting memories and valuable learning experiences, fostering a sense of camaraderie and curiosity that will stay with these young minds for a lifetime.',
                                ],
                            ],
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/education/Student-Success-Fund.webp',
                                    'alt' => 'Student on a field trip outdoors',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_photo_hero',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => 'https://images.unsplash.com/photo-1587560699334-cc4ff634909a?q=80&w=1740&auto=format&fit=crop',
                                    'alt' => 'Students on a school camping trip',
                                ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                            [
                                'id'       => 'el_quote',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Together, we can inspire these young minds and equip them with the tools they need to make a positive impact on the world.',
                                    'content'  => '',
                                ],
                            ],
                            [
                                'id'       => 'el_body',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => '',
                                    'content'  => 'Your generosity will not only provide the means for this adventure but will also contribute to the overall educational enrichment of our students. This trip isn\'t just a mere excursion; it\'s a chance for our 5th graders to expand their horizons, learn about different cultures, and engage with historical and natural wonders that will enhance their academic understanding.',
                                ],
                            ],
                            [
                                'id'       => 'el_progress',
                                'type'     => 'progress_bar',
                                'settings' => [ 'show_donated' => true, 'show_goal' => true ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#8fa040',
                                    'url'          => '',
                                ],
                            ],
                            [
                                'id'       => 'el_social_links',
                                'type'     => 'social_links',
                                'settings' => [
                                    'headline'  => 'Follow on',
                                    'twitter'   => 'https://twitter.com/',
                                    'facebook'  => 'https://facebook.com/',
                                    'linkedin'  => 'https://linkedin.com/in/',
                                    'instagram' => '',
                                    'tiktok'    => '',
                                    'pinterest' => '',
                                    'youtube'   => '',
                                    'threads'   => '',
                                    'bluesky'   => '',
                                    'mastodon'  => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'golf-destinations' => [
                'key'           => 'golf-destinations',
                'category'      => 'prebuilt',
                'tags'          => [ 'club-organizations' ],
                'preview_image' => 'assets/img/campaign/templates/preview/golf-destinations.webp',
                'label'         => __( 'Elite Golf Club Membership', 'better-payment' ),
                'default_title' => __( 'Elite Golf Club Membership', 'better-payment' ),
                'description'   => __( 'Join an exclusive golfing experience built for champions.', 'better-payment' ),
                'theme_class'   => 'golf-destinations',
                'preview_color' => '#b8a46a',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/club-organizations/Elite-Golf-Club-Membership.webp',
                                    'alt' => 'Golfer taking a swing on the course',
                                ],
                            ],
                            [
                                'id'       => 'el_desc_main',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'With 100% of donations going towards grants and programs',
                                    'content'  => 'Every dollar contributed goes directly to maintaining our beloved club facilities, funding community programs, and supporting the next generation of golfers. Your generosity helps preserve a cherished institution that brings people together — from weekend players to seasoned champions. Join us in securing the future of this extraordinary place.',
                                ],
                            ]
                        ],
                    ],
                    [
                        'id'       => 'content',
                        'label'    => __( 'Donate', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'About This Club',
                                    'content'  => 'We are announcing a campaign to support our cherished local country club, a haven for our community, especially our elderly residents. Our non-profit is passionately rallying for funds to ensure the maintenance of this vital space and to sustain the heartwarming annual events that bring together generations. By contributing, you\'re preserving a beloved institution that unites the young and the old, fostering a sense of belonging and community spirit. Join us in safeguarding this haven for all ages!',
                                ],
                            ],
                            [
                                'id'       => 'el_progress',
                                'type'     => 'progress_bar',
                                'settings' => [ 'show_donated' => true, 'show_goal' => true ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#b8a46a',
                                    'url'          => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'child-healthcare' => [
                'key'           => 'child-healthcare',
                'category'      => 'prebuilt',
                'tags'          => [ 'medical' ],
                'preview_image' => 'assets/img/campaign/templates/preview/child-healthcare.webp',
                'label'         => __( 'Child Healthcare Fund', 'better-payment' ),
                'default_title' => __( 'Child Healthcare Fund', 'better-payment' ),
                'description'   => __( 'Help children access life-saving treatments, vaccinations, surgeries, and ongoing medical care.', 'better-payment' ),
                'theme_class'   => 'child-healthcare',
                'preview_color' => '#ecc30b',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/medical/Child-Healthcare-Fund.webp',
                                    'alt' => 'Doctor giving compassionate care to a young child patient',
                                ],
                            ],
                            [
                                'id'       => 'el_tagline',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Every child deserves a healthy future',
                                    'content'  => 'Your generosity funds life-saving treatments, vaccinations, surgeries, and ongoing care for children who need it most. Together we can ensure no child suffers without access to proper medical support.',
                                ],
                            ],
                            [
                                'id'       => 'el_summary_left',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'donate',
                        'label'    => __( 'Donation Panel', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_label',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Let\'s Help The Children',
                                    'content'  => '',
                                ],
                            ],
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_amount_label',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Select an Amount',
                                    'content'  => '',
                                ],
                            ],
                            [
                                'id'       => 'el_amounts',
                                'type'     => 'donate_amount',
                                'settings' => [
                                    'preset_amounts' => '5,10,15,20',
                                ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#ecc30b',
                                    'url'          => '',
                                ],
                            ],
                            [
                                'id'       => 'el_sharing',
                                'type'     => 'social_sharing',
                                'settings' => [
                                    'headline'  => 'Share Now',
                                    'twitter'   => true,
                                    'facebook'  => true,
                                    'linkedin'  => true,
                                    'pinterest' => true,
                                    'mastodon'  => true,
                                    'threads'   => false,
                                    'bluesky'   => false,
                                ],
                            ],
                            [
                                'id'       => 'el_links',
                                'type'     => 'social_links',
                                'settings' => [
                                    'headline'  => 'Follow Now',
                                    'twitter'   => 'https://twitter.com/',
                                    'facebook'  => 'https://facebook.com/',
                                    'linkedin'  => '',
                                    'instagram' => 'https://instagram.com/',
                                    'tiktok'    => '',
                                    'pinterest' => '',
                                    'youtube'   => '',
                                    'threads'   => '',
                                    'bluesky'   => '',
                                    'mastodon'  => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'tree-plantation' => [
                'key'           => 'tree-plantation',
                'category'      => 'prebuilt',
                'tags'          => [ 'environmental' ],
                'preview_image' => 'assets/img/campaign/templates/preview/tree-plantation.webp',
                'label'         => __( 'Tree Plantation Campaign', 'better-payment' ),
                'default_title' => __( 'Tree Plantation Campaign', 'better-payment' ),
                'description'   => __( 'Support environmental restoration through tree planting, conservation, and climate action initiatives.', 'better-payment' ),
                'theme_class'   => 'tree-plantation',
                'preview_color' => '#61aa4f',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_photo1',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/environmental/Tree-Plantation-Campaign.webp',
                                    'alt' => 'Hands holding a young plant growing from rich soil',
                                ],
                            ],
                            [
                                'id'       => 'el_desc_body',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Keep the scene green by taking the lead',
                                    'content'  => 'Your support will make a significant impact, enabling us to organize community clean-up events where volunteers, equipped with the necessary resources, can work together to remove garbage and restore the beach to its natural state. Additionally, your contributions will empower us to implement educational programs aimed at raising awareness about the importance of environmental conservation. We believe that through collective action, we can not only clean up our beloved beach but also inspire a lasting change in our community\'s attitudes towards environmental responsibility. Every donation, no matter how big or small, brings us one step closer to a cleaner, healthier environment for everyone. Join us in this vital endeavor, and let\'s create a positive ripple effect that benefits both our local ecosystem and the people who call this community home.',
                                ],
                            ],
                            [
                                'id'       => 'el_photo2',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => 'https://images.unsplash.com/photo-1520962880247-cfaf541c8724?auto=format&w=1200&q=80&fit=crop',
                                    'alt' => 'Environmental activists marching for climate action',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'content',
                        'label'    => __( 'Donation Content', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            [
                                'id'       => 'el_label',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Save Earth',
                                    'content'  => 'Together, let\'s reclaim the serenity of our shorelines and ensure a cleaner, greener future for generations to come.',
                                ],
                            ],
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => '',
                                    'content'  => 'We are announcing a campaign dedicated to restoring the natural beauty of our local beach, which has sadly fallen victim to years of neglect and pollution. Our non-profit is on a mission to raise funds for cleaning supplies, vehicles, and essential costs to orchestrate a massive cleanup effort. By contributing, you\'re not just supporting a cleaner beach but also promoting environmental health and community pride.',
                                ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#61aa4f',
                                    'url'          => '',
                                ],
                            ],
                            [
                                'id'       => 'el_social_sharing',
                                'type'     => 'social_sharing',
                                'settings' => [
                                    'headline'  => 'Share Now',
                                    'twitter'   => true,
                                    'facebook'  => true,
                                    'linkedin'  => true,
                                    'pinterest' => true,
                                    'mastodon'  => true,
                                    'threads'   => false,
                                    'bluesky'   => false,
                                ],
                            ],
                            [
                                'id'       => 'el_social_links',
                                'type'     => 'social_links',
                                'settings' => [
                                    'headline'  => 'Follow Now',
                                    'twitter'   => 'https://twitter.com/',
                                    'facebook'  => 'https://facebook.com/',
                                    'linkedin'  => '',
                                    'instagram' => 'https://instagram.com/',
                                    'tiktok'    => '',
                                    'pinterest' => '',
                                    'youtube'   => '',
                                    'threads'   => '',
                                    'bluesky'   => '',
                                    'mastodon'  => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'animal-rescue' => [
                'key'           => 'animal-rescue',
                'category'      => 'prebuilt',
                'tags'          => [ 'charity' ],
                'preview_image' => 'assets/img/campaign/templates/preview/animal-rescue.webp',
                'label'         => __( 'Animal Rescue Fund', 'better-payment' ),
                'default_title' => __( 'Animal Rescue Fund', 'better-payment' ),
                'description'   => __( 'Help rescue abandoned, injured, and homeless animals through shelter, food, medical care, and adoption support.', 'better-payment' ),
                'theme_class'   => 'animal-rescue',
                'preview_color' => '#69B0B8',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '65%',
                        'elements' => [
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/charity/Animal-Rescue-Fund.webp',
                                    'alt' => 'Adorable puppies waiting for a loving home',
                                ],
                            ],
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Donate today to support our mission to rescue, rehabilitate, and rehome',
                                    'content'  => 'I\'m thrilled to launch our new campaign aimed at supporting an incredible cause: an animal sanctuary dedicated to rescuing abandoned and lost animals and finding them loving homes. With your help, we aim to raise funds to maintain this sanctuary, providing a safe haven for these adorable pets and ensuring they receive the care they deserve. Your contributions will not only help us sustain the facility but also enable us to actively seek new, caring families for these animals, giving them a chance at a brighter and happier future. Together, we can make a real difference in the lives of these innocent creatures.',
                                ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#69B0B8',
                                    'url'          => '',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '35%',
                        'elements' => [
                            [
                                'id'       => 'el_organizer',
                                'type'     => 'organizer',
                                'settings' => [ 'creator_user_id' => $first_creator_id ],
                            ],
                            [
                                'id'       => 'el_social_sharing',
                                'type'     => 'social_sharing',
                                'settings' => [
                                    'headline'  => 'Share Now',
                                    'twitter'   => true,
                                    'facebook'  => true,
                                    'linkedin'  => true,
                                    'pinterest' => true,
                                    'mastodon'  => true,
                                    'threads'   => false,
                                    'bluesky'   => false,
                                ],
                            ],
                            [
                                'id'       => 'el_social_links',
                                'type'     => 'social_links',
                                'settings' => [
                                    'headline'  => 'Follow Now',
                                    'twitter'   => 'https://twitter.com/',
                                    'facebook'  => 'https://facebook.com/',
                                    'linkedin'  => '',
                                    'instagram' => 'https://instagram.com/',
                                    'tiktok'    => '',
                                    'pinterest' => '',
                                    'youtube'   => '',
                                    'threads'   => '',
                                    'bluesky'   => '',
                                    'mastodon'  => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'disaster-relief' => [
                'key'           => 'disaster-relief',
                'category'      => 'prebuilt',
                'tags'          => [ 'environmental' ],
                'preview_image' => 'assets/img/campaign/templates/preview/disaster-relief.webp',
                'label'         => __( 'Emergency Disaster Relief', 'better-payment' ),
                'default_title' => __( 'Emergency Disaster Relief', 'better-payment' ),
                'description'   => __( 'Help communities recover when they need it most.', 'better-payment' ),
                'theme_class'   => 'disaster-relief',
                'preview_color' => '#c0392b',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '65%',
                        'elements' => [
                            [
                                'id'       => 'el_title',
                                'type'     => 'campaign_title',
                                'settings' => [],
                            ],
                            [
                                'id'       => 'el_photo',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => $tpl_img . '/environmental/Emergency-Disaster-Relief.webp',
                                    'alt' => 'Disaster aftermath showing damaged buildings',
                                ],
                            ],
                            [
                                'id'       => 'el_progress',
                                'type'     => 'progress_bar',
                                'settings' => [ 'show_donated' => true, 'show_goal' => true ],
                            ],
                            [
                                'id'       => 'el_desc',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'About This Campaign',
                                    'content'  => 'We are announcing a new campaign aimed at providing crucial support to the victims of a recent devastating natural disaster. Together, we are rallying behind our fellow community members, raising funds through this non-profit initiative to help them rebuild homes, restore essential services, and reclaim their lives. Join us in making a difference, as every contribution brings us one step closer to bringing hope and stability back to those in need.',
                                ],
                            ],
                            [
                                'id'       => 'el_photo2',
                                'type'     => 'photo',
                                'settings' => [
                                    'src' => 'https://images.unsplash.com/photo-1547032175-7fc8c7bd15b3?q=80&w=2340&auto=format&fit=crop',
                                    'alt' => 'Rescue and recovery efforts after disaster',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '35%',
                        'elements' => [
                            [
                                'id'       => 'el_donate_intro',
                                'type'     => 'campaign_description',
                                'settings' => [
                                    'headline' => 'Donate Today',
                                    'content'  => 'This project will only be funded if at least $270,000 is raised by December 22, 2025.',
                                ],
                            ],
                            [
                                'id'       => 'el_summary',
                                'type'     => 'campaign_summary',
                                'settings' => [ 'show_raised' => true, 'show_donors' => true, 'show_percent' => false, 'show_days' => false ],
                            ],
                            [
                                'id'       => 'el_donate',
                                'type'     => 'donation_form',
                                'settings' => [
                                    'button_label' => 'Donate Now',
                                    'button_color' => '#c0392b',
                                    'url'          => '',
                                ],
                            ],
                            [
                                'id'       => 'el_social_sharing',
                                'type'     => 'social_sharing',
                                'settings' => [
                                    'headline'  => 'Share',
                                    'twitter'   => true,
                                    'facebook'  => true,
                                    'linkedin'  => true,
                                    'pinterest' => true,
                                    'mastodon'  => true,
                                    'threads'   => false,
                                    'bluesky'   => false,
                                ],
                            ],
                            [
                                'id'       => 'el_social_links',
                                'type'     => 'social_links',
                                'settings' => [
                                    'headline'  => 'Follow on',
                                    'twitter'   => 'https://twitter.com/',
                                    'facebook'  => 'https://facebook.com/',
                                    'linkedin'  => '',
                                    'instagram' => '',
                                    'tiktok'    => '',
                                    'pinterest' => '',
                                    'youtube'   => '',
                                    'threads'   => '',
                                    'bluesky'   => '',
                                    'mastodon'  => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

        ];

        return apply_filters( 'better_payment/campaign_templates', $templates );
    }
}
