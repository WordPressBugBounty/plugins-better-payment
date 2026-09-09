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
 * Template shape:
 *   'key'           => string  Unique key; must equal the array key.
 *   'category'      => string  'blank' (wireframe starters) | 'prebuilt' (designed).
 *   'tags'          => array   Category filters in the builder sidebar. Only tags
 *                              known to the UI are filterable — reuse an existing
 *                              one ('charity', 'medical', 'education',
 *                              'environmental', 'club-organizations') unless you
 *                              also extend the sidebar's CATEGORIES list.
 *   'label'         => string  Card title.
 *   'description'   => string  Card subtitle; also matched by the search box.
 *   'layout'        => string  '1-column' | '2-column' | '3-column'.
 *   'columns'       => array   [ [ 'id', 'label', 'width' => '65%', 'elements' => [
 *                              [ 'id', 'type', 'settings' => [] ], ... ] ], ... ]
 *   'default_title' => string  Optional. Seeds the campaign title when applied.
 *   'preview_image' => string  Optional. Card thumbnail.
 *   'preview_color' => string  Optional. Solid-colour fallback when no image loads.
 *   'theme_class'   => string  Optional. Extra class on the campaign wrapper for
 *                              CSS to target. Ships no styles of its own.
 *   'pro'           => bool    Optional. Marks the card as a Pro template. Set
 *                              only by {@see ProTemplateCatalog} on a free
 *                              install, where such a card is shown locked with an
 *                              upgrade CTA and carries no `columns`. Pro's own
 *                              registration never sets it — with Pro active a
 *                              template must be indistinguishable from a free one.
 *
 * An element `settings` key only does something where RendererService reads it.
 * The schema (`defaultSettings` / `settingsSchema`, see CampaignElements) is what
 * the builder UI exposes, not the full set of live keys: an unrecognised key is
 * stored and silently ignored, while a key absent from the schema may still be
 * honoured (`donate_amount` reads `preset_amounts` as a legacy fallback). Prefer
 * schema-declared keys — undeclared ones can't be edited in the builder.
 *
 * Typography (`font_size`, `font_weight`, `letter_spacing`, `line_height`,
 * `color`, ... on `campaign_title` / `campaign_description`) is schema-only and
 * is where a template's visual identity comes from. Absence is meaningful: unset
 * renders a plain default that theme CSS can beat, set is emitted inline with
 * `!important`.
 *
 * Element ids are regenerated on apply, so they need only be unique within the
 * template.
 *
 * To register a custom template from another plugin:
 *   add_filter( 'better_payment/campaign_templates', function( $templates ) {
 *       $templates['my-template'] = [ ... ];
 *       return $templates;
 *   } );
 *
 * Templates registered from another plugin must supply `preview_image` as an
 * absolute URL — a relative path is resolved against Better Payment's own
 * directory. Registering a template that uses Pro-only element types is what
 * makes it Pro-only: the filter simply isn't added when the add-on is inactive.
 *
 * That absence used to be the whole story for Better Payment Pro's four
 * templates — invisible on a free install, so nobody could want them. They are
 * now advertised as locked cards by {@see ProTemplateCatalog}, merged in just
 * before the filter and only while Pro is inactive.
 */
class TemplateManager {

    /**
     * Per-request memo for get_all().
     *
     * @var array<string, array>|null
     */
    private static $cache = null;

    /**
     * Get all available campaign templates.
     *
     * Memoized per request. get_all() is called on every public campaign page
     * view (RendererService looks up `theme_class`), on the builder and campaign
     * list screens, and from the REST route — rebuilding this array and running
     * a get_users() query each time is pure waste, and the set cannot change
     * within a request. Registrations via `better_payment/campaign_templates`
     * land long before the first call (plugins register on load / plugins_loaded,
     * every caller runs on admin_enqueue_scripts, rest_api_init or render).
     *
     * Tests that add or remove the filter mid-request must reset the memo first:
     * `InvokesPrivate::set_static_property( TemplateManager::class, 'cache', null )`.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        if ( self::$cache !== null ) {
            return self::$cache;
        }

        $first_users      = get_users( [ 'fields' => [ 'ID' ], 'number' => 1 ] );
        $first_creator_id = ! empty( $first_users ) ? (int) $first_users[0]->ID : get_current_user_id();
        $tpl_img          = BETTER_PAYMENT_ASSETS . '/img/campaign/templates';

        // Design tokens for the templates below, resolved once so a template
        // definition stays readable as data. See self::palette().
        $charity   = self::palette( 'charity-basic-v2' );
        $medical   = self::palette( 'medical-relief-v2' );
        $education = self::palette( 'education-fund-v2' );
        $golf      = self::palette( 'golf-destinations-v2' );
        $childcare = self::palette( 'child-healthcare-v2' );
        $forest    = self::palette( 'tree-plantation-v2' );
        $animal    = self::palette( 'animal-rescue-v2' );
        $relief    = self::palette( 'disaster-relief-v2' );

        // Sidebar shared by the frozen `charity-basic` template. Retained because
        // that template is frozen verbatim, not because anything new uses it.
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
                'label'       => __( 'Custom', 'better-payment' ),
                'description' => __( 'A full-width row, then two equal columns.', 'better-payment' ),
                // The 'split' preset renders as two rows: a full-width column on top,
                // then two 50/50 columns below (scoped flex-wrap — see
                // src/blocks/campaign-display/style.scss `.bp-campaign--split`).
                'layout'      => 'split',
                'columns'     => [
                    [
                        'id'       => 'top',
                        'label'    => __( 'Top (Full Width)', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [],
                    ],
                    [
                        'id'       => 'bottom-left',
                        'label'    => __( 'Bottom Left', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [],
                    ],
                    [
                        'id'       => 'bottom-right',
                        'label'    => __( 'Bottom Right', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [],
                    ],
                ],
            ],

            /*
             * ── Frozen originals ──────────────────────────────────────────────
             *
             * The eight templates below are the pre-2.3.2 designs. They are kept
             * verbatim and marked `hidden`: the picker no longer offers them
             * (self::get_for_picker()), but they must stay in the registry
             * because RendererService resolves `theme_class` from here on every
             * page view of every campaign built from one. Deleting an entry would
             * strip the styling off live pages; editing one would restyle them.
             *
             * The two without a `theme_class` (`charity-basic`, `medical-relief`)
             * are frozen for consistency rather than necessity — nothing reads
             * them back once they are hidden — so that "pre-2.3.2 templates are
             * frozen" has no exceptions to remember.
             *
             * Do not touch these. New work goes into the `-v2` designs below.
             */

            'charity-basic' => [
                'key'           => 'charity-basic',
                'category'      => 'prebuilt',
                'hidden'        => true,
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
                                    'button_color' => '#B49A5F',
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
                'hidden'        => true,
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
                                    'button_color' => '#7A8347',
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
                'hidden'        => true,
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
                'hidden'        => true,
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
                'hidden'        => true,
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
                'hidden'        => true,
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
                'hidden'        => true,
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
                'hidden'        => true,
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

            /*
             * ── The -v2 templates ─────────────────────────────────────────────
             *
             * Reissues of the eight templates above.
             *
             * The six of those that carry a `theme_class` could not be redesigned
             * in place. Unlike `columns` — a
             * snapshot copied into the campaign at apply time — `theme_class` is
             * resolved out of this registry on every page view, by the template
             * key stored in the campaign's `_bpc_template_key`, and the class it
             * names has real CSS behind it. Restyling that class would have
             * silently restyled every campaign ever built from the template, and
             * because the original blocks lean on `:nth-child()` — they assume a
             * photo is the first element in the column, a description the second
             * — reordering the template's elements would have pointed those rules
             * at whatever now sits in those positions. On live fundraising pages.
             *
             * (`charity-basic` and `medical-relief` set no `theme_class` and so
             * were never at risk; they are reissued alongside the rest purely so
             * the picker offers one coherent set rather than a mix of two visual
             * languages.)
             *
             * So the originals are frozen exactly as they shipped and marked
             * `hidden`, which keeps them resolvable by the renderer while
             * retiring them from the picker (see self::get_for_picker()), and the
             * new designs arrive under new keys with new theme classes. Existing
             * campaigns are byte-for-byte unaffected; only new ones get these.
             *
             * The new theme CSS (src/blocks/campaign-display/style.scss →
             * "v2 template themes") deliberately styles element classes only —
             * `.bp-campaign-summary`, `.bp-progress-bar`, `.bp-amount-label` —
             * and never element position, so a user adding, deleting or
             * reordering elements can no longer knock the design out of
             * alignment. That is the bug the original six shipped with.
             */

            /*
             * Layout mix is deliberate. Lite ships three starter presets and the
             * picker should demonstrate all three, so the eight designs are split
             * 3 / 3 / 2 across `2-column`, `split` and `1-column`. Six of them
             * were 2-column at first, which made the prebuilt row look like one
             * layout in eight colourways and left `split` — the most interesting
             * preset Lite has — represented by a single card.
             *
             * Which template gets which is not arbitrary either: `split` suits a
             * page with a full-width hero and then a story/ask fork; `1-column`
             * suits an appeal or a prospectus read top to bottom, where a sidebar
             * would only compete; `2-column` is the classic story-plus-sticky-ask.
             *
             * ── The order below IS the order of the cards ─────────────────────
             *
             * The picker renders these in registry order, so this array literal
             * is a layout decision, not just a list. They are round-robined
             * 2-column → split → 1-column rather than grouped by preset: grouped,
             * the grid reads as three blocks of near-identical thumbnails and a
             * user scanning the first row sees one shape. Interleaved, every row
             * of a three-up grid shows all three.
             *
             * Categories and accent colours are spread along the same sequence —
             * no two neighbours share a tag, and no two similar hues sit side by
             * side (which is why the two greens, Golf and Tree Plantation, are
             * three cards apart). Only the layout rotation is pinned by a test;
             * the rest is judgement, so re-check it by eye when adding a ninth.
             */

            'charity-basic-v2' => [
                'key'           => 'charity-basic-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'charity' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/charity-basic-v2.webp',
                'label'         => __( 'Refugee Relief Fund', 'better-payment' ),
                'default_title' => __( 'Refugee Relief Fund', 'better-payment' ),
                'description'   => __( 'Story-led appeal with a sticky donation panel — for humanitarian and emergency relief funds.', 'better-payment' ),
                'theme_class'   => 'bp-v2-charity',
                'preview_color' => '#c2410c',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '62%',
                        'elements' => [
                            self::title_el( $charity, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $charity,
                                                            '',
                                                            'Families arrive carrying what they could hold in their arms. The first weeks decide the next year — whether there is a roof, whether the children go back to school, whether anyone is well enough to work. That window is what this fund exists to cover.',
                                                            [ 'size' => 20 ]
                                                        ),
                            self::photo_el( 'el_hero', 'charity/Refugee-Relief-Fund.webp', 'Aid workers unloading supplies for refugee families' ),
                            self::desc_el(
                                                            'el_story',
                                                            $charity,
                                                            'Where your donation goes',
                                                            'Shelter, bedding, food, clean water, medicine and school kits — bought as close to the people receiving them as possible, so the money stays in the communities hosting new arrivals. The rest keeps the work moving: transport, storage, and the caseworkers who make sure help reaches the families who need it most rather than the ones who are easiest to reach.'
                                                        ),
                            self::desc_el(
                                                            'el_why_now',
                                                            $charity,
                                                            'Why it matters today',
                                                            'Help is cheapest and furthest-reaching at the beginning. A family supported in their first month rarely needs emergency help again; a family reached much later often needs it for years. Giving now is not the same as giving later.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::organizer_el(
                                                            'Campaign Organizer',
                                                            'I run this appeal and answer every message personally. If you would like to talk before giving, please do get in touch.'
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '38%',
                        'elements' => [
                            self::progress_el( 'Appeal progress', 'Raised so far:', 'Appeal target:' ),
                            self::summary_el( [ 'raised', 'donors', 'percent', 'days' ] ),
                            self::amounts_el( 'Give what you can' ),
                            self::donate_el( $charity, 'Give to this appeal' ),
                            self::share_el( 'Share this appeal' ),
                            self::links_el(
                                                            'Follow our work',
                                                            [
                                                                'facebook'  => 'https://facebook.com/',
                                                                'twitter'   => 'https://twitter.com/',
                                                                'instagram' => 'https://instagram.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                ],
            ],

            'child-healthcare-v2' => [
                'key'           => 'child-healthcare-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'medical' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/child-healthcare-v2.webp',
                'label'         => __( 'Child Healthcare Fund', 'better-payment' ),
                'default_title' => __( 'Child Healthcare Fund', 'better-payment' ),
                'description'   => __( 'A dark, high-contrast appeal for paediatric care — headline first, then story and ask.', 'better-payment' ),
                'theme_class'   => 'bp-v2-childcare',
                'preview_color' => '#38bdf8',
                'layout'        => 'split',
                'columns'       => [
                    [
                        'id'       => 'top',
                        'label'    => __( 'Hero (Full Width)', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [
                            self::title_el( $childcare, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $childcare,
                                                            '',
                                                            'Children do not get to wait for a funding round to close. Every week a treatment is delayed is a week of growing up spent on a ward instead of in a classroom — and the treatments that work best are almost always the ones that start soonest.',
                                                            [ 'size' => 21 ]
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-left',
                        'label'    => __( 'Story', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            self::photo_el( 'el_hero', 'medical/Child-Healthcare-Fund.webp', 'A child being cared for on a hospital ward' ),
                            self::desc_el(
                                                            'el_story',
                                                            $childcare,
                                                            'What this fund pays for',
                                                            'Theatre time, and the specialists who use it. Equipment a paediatric ward cannot borrow from anywhere else. And the travel, meals and beds that decide whether a parent can be in the room at all — the part of a child\'s treatment no clinical budget covers and every family notices.'
                                                        ),
                            self::desc_el(
                                                            'el_after',
                                                            $childcare,
                                                            'After the goal is met',
                                                            'Nothing here stops when the total is reached. Anything raised beyond it goes to the next child on the list, and we say publicly where it went. You should know that before you give, not afterwards.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::organizer_el(
                                                            'Appeal Lead',
                                                            'I run this appeal and post an update whenever there is real news to report — not before.'
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-right',
                        'label'    => __( 'Donate', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            self::progress_el( 'Progress so far', 'Raised:', 'Goal:' ),
                            self::summary_el( [ 'raised', 'donors', 'percent', 'days' ] ),
                            self::amounts_el( 'Choose an amount' ),
                            self::donate_el( $childcare, 'Give Now' ),
                            self::share_el( 'Share this appeal' ),
                            self::links_el(
                                                            'Follow the appeal',
                                                            [
                                                                'facebook'  => 'https://facebook.com/',
                                                                'twitter'   => 'https://twitter.com/',
                                                                'instagram' => 'https://instagram.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                ],
            ],

            'disaster-relief-v2' => [
                'key'           => 'disaster-relief-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'environmental' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/disaster-relief-v2.webp',
                'label'         => __( 'Emergency Disaster Relief', 'better-payment' ),
                'default_title' => __( 'Emergency Disaster Relief', 'better-payment' ),
                'description'   => __( 'Single-column emergency appeal that puts the figures and the ask above the fold.', 'better-payment' ),
                'theme_class'   => 'bp-v2-relief',
                'preview_color' => '#dc2626',
                'layout'        => '1-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Appeal', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [
                            self::title_el( $relief, 'left' ),
                            self::desc_el(
                                                                                        'el_lede',
                                                                                        $relief,
                                                                                        'This is happening now',
                                                                                        'Teams are on the ground and supplies are staged. What moves this week depends on what is raised in the next few days, not next month. If you are going to give, giving now is worth more than giving later.',
                                                                                        [ 'size' => 20, 'accent_headline' => true ]
                                                                                    ),
                            // Figures before the photo, on purpose. This is a
                                                                                    // single-column page, so everything competes for the same
                                                                                    // vertical space — and a full-width hero pushes the ask a
                                                                                    // screen and a half down, which is the opposite of what an
                                                                                    // emergency appeal is for.
                                                                                    self::progress_el( '', 'Raised:', 'Goal:' ),
                            self::summary_el( [ 'raised', 'donors', 'percent', 'days' ] ),
                            self::photo_el( 'el_hero', 'environmental/Emergency-Disaster-Relief.webp', 'Relief teams distributing emergency supplies' ),
                            self::amounts_el( 'Give what you can' ),
                            self::donate_el( $relief, 'Donate Now' ),
                            self::desc_el(
                                                                                        'el_buys',
                                                                                        $relief,
                                                                                        'What your donation buys',
                                                                                        'A family food parcel. A shelter kit for a household with no roof left. A water filter, and the fuel that gets it to a village the road no longer reaches. These are the units a response is actually measured in — not appeals, and not totals.'
                                                                                    ),
                            self::desc_el(
                                                                                        'el_situation',
                                                                                        $relief,
                                                                                        'How the response is running',
                                                                                        'Distribution is handled by people who were here before this happened and will still be here afterwards. That is the difference between aid that arrives and aid that is announced.',
                                                                                        [ 'accent_headline' => true ]
                                                                                    ),
                            self::share_el( 'Share this appeal', 'left' ),
                            self::links_el(
                                                                                        'Follow the response',
                                                                                        [
                                                                                            'facebook' => 'https://facebook.com/',
                                                                                            'twitter'  => 'https://twitter.com/',
                                                                                        ]
                                                                                    ),
                        ],
                    ],
                ],
            ],

            'medical-relief-v2' => [
                'key'           => 'medical-relief-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'medical' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/medical-relief-v2.webp',
                'label'         => __( 'Medical Emergency Fund', 'better-payment' ),
                'default_title' => __( 'Medical Emergency Fund', 'better-payment' ),
                'description'   => __( 'Calm, clinical two-column layout for treatment appeals and family fundraisers.', 'better-payment' ),
                'theme_class'   => 'bp-v2-medical',
                'preview_color' => '#0f766e',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '55%',
                        'elements' => [
                            self::title_el( $medical, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $medical,
                                                            '',
                                                            'A diagnosis arrives and everything else stops. Treatment starts immediately, the bills start with it, and the person who would normally be earning is the one in the ward.',
                                                            [ 'size' => 20 ]
                                                        ),
                            self::desc_el(
                                                            'el_story',
                                                            $medical,
                                                            'The treatment ahead',
                                                            'Care like this is measured in months rather than appointments: scans, cycles of treatment, the slow recovery between them, and the journeys to reach a hospital equipped to give it. None of it is quick, and none of it waits for a fundraising page to catch up.'
                                                        ),
                            self::photo_el( 'el_hero', 'medical/Medical-Emergency-Fund.webp', 'A patient receiving care in hospital' ),
                            self::desc_el(
                                                            'el_costs',
                                                            $medical,
                                                            'What your donation pays for',
                                                            'Hospital fees and medication. Scans and the follow-up appointments after them. Travel to treatment, and somewhere to stay when it runs late. And the income a family loses while one of them is at a bedside instead of at work — the cost nobody budgets for and every family feels.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::share_el( 'Share this fundraiser' ),
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '45%',
                        'elements' => [
                            self::summary_el( [ 'raised', 'donors', 'days' ] ),
                            self::progress_el( 'Treatment funded', 'Funded:', 'Treatment target:' ),
                            self::amounts_el( 'Cover a cost' ),
                            self::donate_el( $medical, 'Fund treatment' ),
                            self::organizer_el(
                                                            'Family Organizer',
                                                            'I post an update here as treatment progresses. Message me if you have questions before giving.'
                                                        ),
                            self::links_el(
                                                            'Follow for updates',
                                                            [
                                                                'facebook' => 'https://facebook.com/',
                                                                'twitter'  => 'https://twitter.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                ],
            ],

            'education-fund-v2' => [
                'key'           => 'education-fund-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'education' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/education-fund-v2.webp',
                'label'         => __( 'Student Success Fund', 'better-payment' ),
                'default_title' => __( 'Student Success Fund', 'better-payment' ),
                'description'   => __( 'Full-width hero, then story and donation side by side — for scholarship and hardship funds.', 'better-payment' ),
                'theme_class'   => 'bp-v2-education',
                'preview_color' => '#4f46e5',
                'layout'        => 'split',
                'columns'       => [
                    [
                        'id'       => 'top',
                        'label'    => __( 'Hero (Full Width)', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [
                            self::title_el( $education, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $education,
                                                            '',
                                                            'Almost nobody leaves education because they stopped caring. They leave because a bus fare ran out, a laptop broke, or a term bill landed in a month the family could not absorb.',
                                                            [ 'size' => 21 ]
                                                        ),
                            self::progress_el( 'Progress so far', 'Raised:', 'Goal:' ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-left',
                        'label'    => __( 'Story', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            // The artwork carries the campaign name in the image
                                                        // itself, so at full hero width it restated the H1 in a
                                                        // second typeface and a second palette. At half width it
                                                        // reads as an illustration beside the story, which is
                                                        // what it is.
                                                        self::photo_el( 'el_hero', 'education/Student-Success-Fund.webp', 'Students working together in a classroom' ),
                            self::desc_el(
                                                            'el_covers',
                                                            $education,
                                                            'What the fund covers',
                                                            'Tuition and exam fees. Textbooks, lab materials, and a laptop that actually works. Travel to and from campus. And a small hardship grant for the months when none of the above is the real problem. Wherever it can be, the money is paid straight to the institution, so it lands where it was given for.'
                                                        ),
                            self::desc_el(
                                                            'el_reaches',
                                                            $education,
                                                            'Who it reaches',
                                                            'Students who have already done the hard part — earned the place, kept the grades, and then hit a wall that has nothing to do with ability. A modest amount at the right moment is often the whole difference between a degree finished and a year abandoned.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-right',
                        'label'    => __( 'Donate', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            self::summary_el( [ 'raised', 'donors', 'percent', 'days' ] ),
                            self::amounts_el( 'Choose an amount' ),
                            self::donate_el( $education, 'Fund a Student' ),
                            self::organizer_el(
                                                            'Fund Coordinator',
                                                            'I manage this fund and publish an update at the end of every term. Questions before you give are always welcome.'
                                                        ),
                            self::share_el( 'Share this fund' ),
                            self::links_el(
                                                            'Follow the fund',
                                                            [
                                                                'facebook' => 'https://facebook.com/',
                                                                'twitter'  => 'https://twitter.com/',
                                                                'linkedin' => 'https://linkedin.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                ],
            ],

            'golf-destinations-v2' => [
                'key'           => 'golf-destinations-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'club-organizations' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/golf-destinations-v2.webp',
                'label'         => __( 'Elite Golf Club Membership', 'better-payment' ),
                'default_title' => __( 'Elite Golf Club Membership', 'better-payment' ),
                'description'   => __( 'A single-column prospectus for clubs and societies collecting subscriptions.', 'better-payment' ),
                'theme_class'   => 'bp-v2-golf',
                'preview_color' => '#15803d',
                'layout'        => '1-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Membership', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [
                            self::title_el( $golf, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $golf,
                                                            '',
                                                            'A course that rewards a good round and forgives an honest one, a clubhouse worth staying in afterwards, and a membership small enough that people know your name.',
                                                            [ 'size' => 20 ]
                                                        ),
                            self::photo_el( 'el_hero', 'club-organizations/Elite-Golf-Club-Membership.webp', 'The clubhouse and first fairway at dawn' ),
                            self::progress_el( 'Subscriptions received', 'Received:', 'Target:' ),
                            self::summary_el( [ 'raised', 'donors', 'days' ] ),
                            self::amounts_el( 'Membership category' ),
                            self::donate_el( $golf, 'Join the Club' ),
                            self::desc_el(
                                                            'el_about',
                                                            $golf,
                                                            'About the club',
                                                            'The club has kept the same character for as long as anyone can remember: unhurried, welcoming, and serious about the golf without being solemn about it. Members play year-round, guests are genuinely welcome, and the calendar is full enough to matter without ever feeling like an obligation.'
                                                        ),
                            self::desc_el(
                                                            'el_includes',
                                                            $golf,
                                                            'What membership includes',
                                                            'Full course access and priority booking, with guest rates for the people you bring. A locker and winter storage. Coaching, the competition calendar and the off-season programme. The clubhouse and its terrace — and reciprocal arrangements with clubs beyond this one.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::share_el( 'Share with a member', 'left' ),
                            self::links_el(
                                                            'Club channels',
                                                            [
                                                                'facebook'  => 'https://facebook.com/',
                                                                'instagram' => 'https://instagram.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                ],
            ],

            'animal-rescue-v2' => [
                'key'           => 'animal-rescue-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'charity' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/animal-rescue-v2.webp',
                'label'         => __( 'Animal Rescue Fund', 'better-payment' ),
                'default_title' => __( 'Animal Rescue Fund', 'better-payment' ),
                'description'   => __( 'Warm, story-led page for shelters and rescue groups taking ongoing donations.', 'better-payment' ),
                'theme_class'   => 'bp-v2-animal',
                'preview_color' => '#be185d',
                'layout'        => '2-column',
                'columns'       => [
                    [
                        'id'       => 'main',
                        'label'    => __( 'Main Content', 'better-payment' ),
                        'width'    => '68%',
                        'elements' => [
                            self::photo_el( 'el_hero', 'charity/Animal-Rescue-Fund.webp', 'A rescued dog being cared for at the shelter' ),
                            self::title_el( $animal, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $animal,
                                                            '',
                                                            'Every animal that comes through the door arrives mid-story. Some need a fortnight and a warm room. Some need surgery, months of rehabilitation and a foster carer with more patience than most people have. This fund is what stops the difference coming down to what happens to be in the account that week.',
                                                            [ 'size' => 20 ]
                                                        ),
                            self::desc_el(
                                                            'el_what',
                                                            $animal,
                                                            'What your donation covers',
                                                            'Veterinary treatment and medication. Vaccination, neutering and microchipping. Food, bedding, and the heating bill for a building that cannot be allowed to get cold. Fuel for the emergency call-outs that come in at hours nobody chose.'
                                                        ),
                            self::desc_el(
                                                            'el_hardest',
                                                            $animal,
                                                            'The ones who take longest',
                                                            'The straightforward cases rehome themselves. The animals who need us are the old, the frightened and the badly hurt — the ones a shelter running on empty has to turn away. Every donation here is really a decision about how often we get to say yes.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::links_el(
                                                            'Follow the shelter',
                                                            [
                                                                'facebook'  => 'https://facebook.com/',
                                                                'instagram' => 'https://instagram.com/',
                                                                'tiktok'    => 'https://tiktok.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'sidebar',
                        'label'    => __( 'Sidebar', 'better-payment' ),
                        'width'    => '32%',
                        'elements' => [
                            self::progress_el( 'Shelter fund', 'Raised:', 'Target:' ),
                            self::summary_el( [ 'raised', 'donors', 'days' ] ),
                            self::amounts_el( 'Choose your gift' ),
                            self::donate_el( $animal, 'Sponsor an animal' ),
                            self::organizer_el(
                                                            'Shelter Manager',
                                                            'I look after the animals here and write the updates. If you would like to visit before donating, just ask.'
                                                        ),
                            self::share_el( 'Share the shelter' ),
                        ],
                    ],
                ],
            ],

            'tree-plantation-v2' => [
                'key'           => 'tree-plantation-v2',
                'category'      => 'prebuilt',
                'tags'          => [ 'environmental' ],
                'preview_image' => 'assets/img/campaign/templates/preview/v2/tree-plantation-v2.webp',
                'label'         => __( 'Tree Plantation Campaign', 'better-payment' ),
                'default_title' => __( 'Tree Plantation Campaign', 'better-payment' ),
                'description'   => __( 'Calm, green reforestation page built around cost-per-tree giving.', 'better-payment' ),
                'theme_class'   => 'bp-v2-forest',
                'preview_color' => '#047857',
                'layout'        => 'split',
                'columns'       => [
                    [
                        'id'       => 'top',
                        'label'    => __( 'Hero (Full Width)', 'better-payment' ),
                        'width'    => '100%',
                        'elements' => [
                            self::photo_el( 'el_hero', 'environmental/Tree-Plantation-Campaign.webp', 'Newly planted saplings on restored land' ),
                            self::title_el( $forest, 'left' ),
                            self::desc_el(
                                                            'el_lede',
                                                            $forest,
                                                            '',
                                                            'Planting a tree is the cheap part. Choosing the right species, getting it into the ground in the right season, and coming back for years to make sure it survived — that is the part that needs funding, and the part that decides whether any of it mattered.',
                                                            [ 'size' => 21 ]
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-left',
                        'label'    => __( 'Story', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            self::desc_el(
                                                            'el_how',
                                                            $forest,
                                                            'How the planting works',
                                                            'Seedlings are raised locally and planted by the people who live on the land being restored. Each site is tended long after the photographs are taken: watered through the first dry seasons, thinned, replanted where it failed, and left alone only once it can look after itself.'
                                                        ),
                            self::desc_el(
                                                            'el_where',
                                                            $forest,
                                                            'Where we plant',
                                                            'On ground that has been cleared, burned or farmed past exhaustion — where a forest will not come back on its own. Species are chosen for the place rather than for the pace, so what grows here belongs here.',
                                                            [ 'accent_headline' => true ]
                                                        ),
                            self::links_el(
                                                            'Follow the planting',
                                                            [
                                                                'facebook'  => 'https://facebook.com/',
                                                                'instagram' => 'https://instagram.com/',
                                                                'youtube'   => 'https://youtube.com/',
                                                            ]
                                                        ),
                        ],
                    ],
                    [
                        'id'       => 'bottom-right',
                        'label'    => __( 'Donate', 'better-payment' ),
                        'width'    => '50%',
                        'elements' => [
                            self::progress_el( 'Progress so far', 'Raised:', 'Goal:' ),
                            self::summary_el( [ 'raised', 'donors', 'percent', 'days' ] ),
                            self::amounts_el( 'Plant a tree' ),
                            self::donate_el( $forest, 'Plant Trees' ),
                            self::organizer_el(
                                                            'Planting Coordinator',
                                                            'I look after the sites and report back at the end of each planting season, survival rates included.'
                                                        ),
                            self::share_el( 'Share this campaign' ),
                        ],
                    ],
                ],
            ],

        ];

        // Locked cards for the four Pro templates — added only when Pro is
        // inactive, so a free user can see what Pro's designs look like instead
        // of never learning they exist. They carry no `columns`, so there is
        // nothing to apply; see ProTemplateCatalog. Merged BEFORE the filter on
        // purpose: Pro registers the real templates under these same keys, so
        // even if the entitlement read were somehow wrong, the genuine template
        // still wins over its own placeholder.
        $templates = array_merge( $templates, ProTemplateCatalog::get_locked() );

        /**
         * Filters the campaign templates offered in the builder.
         *
         * Fires once per request (the result is memoized). Add-ons key their own
         * templates in — see the class docblock for the expected shape. Templates
         * registered from outside this plugin must give `preview_image` as an
         * absolute URL; plugin-relative paths resolve against Better Payment's
         * own directory.
         *
         * @param array<string, array> $templates Templates keyed by template key.
         */
        // @var is deliberate: the @param above describes what we pass in, but a
        // third-party listener can return anything at all.
        /** @var mixed $filtered */
        $filtered = apply_filters( 'better_payment/campaign_templates', $templates );

        // An add-on returning a non-array must not take the built-in templates down
        // with it, nor fatal the `: array` return — keep the unfiltered set instead.
        self::$cache = is_array( $filtered ) ? $filtered : $templates;

        return self::$cache;
    }

    /**
     * Templates the builder's picker is allowed to offer.
     *
     * {@see self::get_all()} is the registry — it must keep serving every
     * template ever shipped, because {@see \Better_Payment\Lite\Campaign\Services\RendererService}
     * resolves a live campaign's `theme_class` out of it on every single page
     * view, keyed by the `_bpc_template_key` stored when the campaign was built.
     * Drop a retired template from there and every campaign built from it loses
     * its styling on the next page load.
     *
     * The picker has the opposite need: a retired design must stop being offered
     * to new campaigns. `hidden` is what separates the two, and this is the only
     * place it is honoured — the four UI surfaces (the builder, the campaign
     * list, the React admin and the REST route) call this; the three renderer
     * call sites deliberately do not.
     *
     * Keys are preserved (the callers `array_values()` themselves), and a
     * template with no `hidden` key behaves exactly as before, so add-ons
     * registering through `better_payment/campaign_templates` need no changes.
     *
     * @since 2.3.2
     *
     * @return array<string, array>
     */
    public static function get_for_picker(): array {
        return array_filter(
            self::get_all(),
            static function ( $template ) {
                return empty( $template['hidden'] );
            }
        );
    }

    /* --------------------------------------------------------------------- */
    /* Design tokens                                                          */
    /* --------------------------------------------------------------------- */

    /**
     * Per-template design tokens.
     *
     * A template's visual identity comes from two places and no others: the
     * typography and colours it sets on its own elements (emitted inline with
     * `!important` by the renderer), and — for the `-v2` templates only — the
     * stylesheet block keyed on its `theme_class`. This table holds the first,
     * and the SCSS holds the second; `accent` is deliberately the same value in
     * both so a button and its theme agree.
     *
     * Every `accent` is dark enough to carry white text at 4.5:1 — it colours the
     * donate button and the selected amount chip, both of which the renderer sets
     * `#fff` on. Teal, amber and sky all failed that at their obvious mid-tone
     * (#0d9488 → 3.7:1, #d97706 → 3.2:1), so they are one step darker here; sky
     * could not be darkened without losing the dark theme's only bright colour,
     * and instead gets dark button text in the SCSS. Check a new accent before
     * adding one.
     *
     * `accent` drives the donate button and the amount chips; `ink` and `muted`
     * are heading and body colours; `title` / `head` / `body` are the type
     * scale. Sizes are px, weights are CSS weight strings, `track` is
     * letter-spacing in px (negative tightens large headings) and `leading` is a
     * unitless multiplier — the formats
     * `RendererService::css_font_size()` / `css_length()` / `css_line_height()`
     * accept. A value outside them is silently dropped rather than failing
     * loudly, which is why `CampaignTemplatesTest` asserts the formats.
     *
     * @since 2.3.2
     *
     * @param string $key Template key.
     * @return array
     */
    private static function palette( string $key ): array {
        $palettes = [

            // Terracotta on warm stone. Humanitarian without being sombre.
            'charity-basic-v2' => [
                'accent' => '#c2410c',
                'ink'    => '#1c1917',
                'muted'  => '#57534e',
                'title'  => [ 'size' => 46, 'weight' => '800', 'track' => -1.2, 'leading' => 1.06 ],
                'head'   => [ 'size' => 25, 'weight' => '700', 'track' => -0.4 ],
                'body'   => [ 'size' => 17, 'leading' => 1.75 ],
            ],

            // Clinical teal. Calm and precise — the tone a medical appeal needs.
            'medical-relief-v2' => [
                'accent' => '#0f766e',
                'ink'    => '#0f172a',
                'muted'  => '#475569',
                'title'  => [ 'size' => 44, 'weight' => '800', 'track' => -1.1, 'leading' => 1.08 ],
                'head'   => [ 'size' => 24, 'weight' => '700', 'track' => -0.35 ],
                'body'   => [ 'size' => 17, 'leading' => 1.75 ],
            ],

            // Indigo. Optimistic and contemporary — a scholarship prospectus.
            'education-fund-v2' => [
                'accent' => '#4f46e5',
                'ink'    => '#1e1b4b',
                'muted'  => '#4c4a6a',
                'title'  => [ 'size' => 50, 'weight' => '800', 'track' => -1.5, 'leading' => 1.04 ],
                'head'   => [ 'size' => 25, 'weight' => '700', 'track' => -0.4 ],
                'body'   => [ 'size' => 17, 'leading' => 1.75 ],
            ],

            // Fairway green on cream. Restrained, club-stationery formality.
            'golf-destinations-v2' => [
                'accent' => '#15803d',
                'ink'    => '#14261a',
                'muted'  => '#4b5c50',
                'title'  => [ 'size' => 40, 'weight' => '700', 'track' => 1.2, 'leading' => 1.12, 'transform' => 'uppercase' ],
                'head'   => [ 'size' => 22, 'weight' => '700', 'track' => 0.2 ],
                'body'   => [ 'size' => 17, 'leading' => 1.8 ],
            ],

            // Sky on midnight. The one dark template — see the SCSS note on why
            // the theme, not the element settings, owns the light text.
            'child-healthcare-v2' => [
                'accent' => '#38bdf8',
                'ink'    => '#f1f5f9',
                'muted'  => '#a8b6c8',
                'title'  => [ 'size' => 50, 'weight' => '800', 'track' => -1.6, 'leading' => 1.04 ],
                'head'   => [ 'size' => 25, 'weight' => '700', 'track' => -0.4 ],
                'body'   => [ 'size' => 17, 'leading' => 1.75 ],
            ],

            // Emerald on mint. Growth, quietly.
            'tree-plantation-v2' => [
                'accent' => '#047857',
                'ink'    => '#0f2e23',
                'muted'  => '#47615a',
                'title'  => [ 'size' => 48, 'weight' => '800', 'track' => -1.4, 'leading' => 1.05 ],
                'head'   => [ 'size' => 25, 'weight' => '700', 'track' => -0.4 ],
                'body'   => [ 'size' => 17, 'leading' => 1.75 ],
            ],

            // Amber on warm white. Friendly, hand-on-the-shoulder.
            // Rose on blush, soft and round. Amber sat a few degrees from
            // charity-basic's terracotta, so the two cards read as one design in
            // two shades — the whole reason this template was reworked.
            'animal-rescue-v2' => [
                'accent' => '#be185d',
                'ink'    => '#3f0c22',
                'muted'  => '#6b4453',
                'title'  => [ 'size' => 44, 'weight' => '800', 'track' => -1.0, 'leading' => 1.1 ],
                'head'   => [ 'size' => 24, 'weight' => '700', 'track' => -0.2 ],
                'body'   => [ 'size' => 17, 'leading' => 1.8 ],
            ],

            // Red, uppercase, tight. Urgency before a word is read.
            'disaster-relief-v2' => [
                'accent' => '#dc2626',
                'ink'    => '#18181b',
                'muted'  => '#3f3f46',
                'title'  => [ 'size' => 50, 'weight' => '800', 'track' => -1.6, 'leading' => 1.03 ],
                'head'   => [ 'size' => 26, 'weight' => '700', 'track' => -0.5 ],
                'body'   => [ 'size' => 18, 'leading' => 1.7 ],
            ],
        ];

        return $palettes[ $key ];
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                                */
    /* --------------------------------------------------------------------- */

    /**
     * URL for campaign artwork shipped with the plugin.
     *
     * Templates must never hotlink a third-party image host. Four of the
     * original prebuilt templates pull a second photo from `images.unsplash.com`
     * — that URL is copied verbatim into every campaign built from them, so the
     * page depends on a CDN nobody here controls, leaks each visitor's IP and
     * referrer to it, and shows a broken image the day the photo is pulled. The
     * `-v2` templates ship every image they use.
     *
     * @since 2.3.2
     *
     * @param string $path Path below assets/img/campaign/templates/.
     * @return string
     */
    private static function template_image( string $path ): string {
        return BETTER_PAYMENT_ASSETS . '/img/campaign/templates/' . $path;
    }

    /**
     * Memo for {@see self::default_organizer_id()}.
     *
     * @var int|null
     */
    private static $organizer_id = null;

    /**
     * The user the Organizer element points at until the campaign owner changes it.
     *
     * The first user on the site, falling back to whoever is building the
     * campaign. Memoized so building nine templates costs one query, not nine.
     *
     * @since 2.3.2
     *
     * @return int
     */
    private static function default_organizer_id(): int {
        if ( self::$organizer_id !== null ) {
            return self::$organizer_id;
        }

        $users              = get_users( [ 'fields' => [ 'ID' ], 'number' => 1 ] );
        self::$organizer_id = ! empty( $users ) ? (int) $users[0]->ID : get_current_user_id();

        return self::$organizer_id;
    }

    /* --------------------------------------------------------------------- */
    /* Element builders                                                       */
    /* --------------------------------------------------------------------- */

    /**
     * Campaign title, styled from the palette's title scale.
     *
     * The text is left unset on purpose — `APPLY_TEMPLATE` seeds it from the
     * template's `default_title` (or the campaign's existing title, if the user
     * has already named it), so hardcoding it here would overwrite a name the
     * user chose.
     *
     * @param array  $p     Palette.
     * @param string $align left|center|right.
     * @return array
     */
    private static function title_el( array $p, string $align = 'left' ): array {
        $settings = [
            'align'          => $align,
            'font_size'      => $p['title']['size'],
            'font_weight'    => $p['title']['weight'],
            'letter_spacing' => $p['title']['track'],
            'line_height'    => $p['title']['leading'],
            'color'          => $p['ink'],
        ];

        if ( ! empty( $p['title']['transform'] ) ) {
            $settings['text_transform'] = $p['title']['transform'];
        }

        return [ 'id' => 'el_title', 'type' => 'campaign_title', 'settings' => $settings ];
    }

    /**
     * Description block with its headline and body styled independently.
     *
     * `title_*` keys style the headline, unprefixed keys the body — two separate
     * typography sets on one element, which is what lets a template carry a real
     * heading/body hierarchy rather than one flat block of text.
     *
     * @param string $id       Element id.
     * @param array  $p        Palette.
     * @param string $headline Headline text ('' renders no headline).
     * @param string $content  Body copy.
     * @param array  $opts     Optional: 'align', 'size' (body px), 'accent_headline' (bool).
     * @return array
     */
    private static function desc_el( string $id, array $p, string $headline, string $content, array $opts = [] ): array {
        $settings = [
            'headline'             => $headline,
            'content'              => $content,
            'title_font_size'      => $p['head']['size'],
            'title_font_weight'    => $p['head']['weight'],
            'title_letter_spacing' => $p['head']['track'],
            'title_color'          => ! empty( $opts['accent_headline'] ) ? $p['accent'] : $p['ink'],
            'font_size'            => $opts['size'] ?? $p['body']['size'],
            'line_height'          => $p['body']['leading'],
            'color'                => $p['muted'],
        ];

        if ( isset( $opts['align'] ) ) {
            $settings['align'] = $opts['align'];
        }

        return [ 'id' => $id, 'type' => 'campaign_description', 'settings' => $settings ];
    }

    /**
     * Photo element.
     *
     * @param string $id    Element id.
     * @param string $path  Path below assets/img/campaign/templates/.
     * @param string $alt   Alt text.
     * @param string $align left|center|right.
     * @return array
     */
    private static function photo_el( string $id, string $path, string $alt, string $align = 'center' ): array {
        return [
            'id'       => $id,
            'type'     => 'photo',
            'settings' => [
                'src'   => self::template_image( $path ),
                'alt'   => $alt,
                'width' => 100,
                'align' => $align,
            ],
        ];
    }

    /**
     * Progress bar.
     *
     * `donate_label` / `goal_label` are prefixes the renderer prints an amount
     * after — never put a figure in one, or the page reads "Goal: $5,000 $5,000".
     *
     * @param string $headline Headline ('' renders no headline).
     * @param string $raised   Label printed before the raised figure.
     * @param string $goal     Label printed before the goal figure.
     * @param string $align    left|center|right.
     * @return array
     */
    private static function progress_el( string $headline, string $raised, string $goal, string $align = 'left' ): array {
        return [
            'id'       => 'el_progress',
            'type'     => 'progress_bar',
            'settings' => [
                'headline'      => $headline,
                'show_donated'  => true,
                'show_goal'     => true,
                'round_amounts' => true,
                'donate_label'  => $raised,
                'goal_label'    => $goal,
                'width'         => 100,
                'align'         => $align,
            ],
        ];
    }

    /**
     * Campaign summary figures.
     *
     * @param array  $show  Which figures to show: raised, donors, percent, days.
     * @param string $align left|center|right.
     * @return array
     */
    private static function summary_el( array $show, string $align = 'left' ): array {
        return [
            'id'       => 'el_summary',
            'type'     => 'campaign_summary',
            'settings' => [
                'headline'     => '',
                'show_raised'  => in_array( 'raised', $show, true ),
                'show_donors'  => in_array( 'donors', $show, true ),
                'show_percent' => in_array( 'percent', $show, true ),
                'show_days'    => in_array( 'days', $show, true ),
                'width'        => 100,
                'align'        => $align,
            ],
        ];
    }

    /**
     * Suggested donation amounts.
     *
     * Only `headline` is an element setting — the amounts themselves live in
     * campaign meta (`bpc_suggested_amounts`, a metaKey-bound control), which a
     * template cannot seed through element settings.
     *
     * @param string $headline Headline text.
     * @return array
     */
    private static function amounts_el( string $headline ): array {
        return [ 'id' => 'el_amounts', 'type' => 'donate_amount', 'settings' => [ 'headline' => $headline ] ];
    }

    /**
     * Donate button, coloured from the palette accent.
     *
     * Always placed directly after {@see self::amounts_el()} where both appear:
     * choosing an amount and confirming it is one action, and a button parked
     * further down the page reads as an unrelated control.
     *
     * @param array  $p     Palette.
     * @param string $label Button label.
     * @return array
     */
    private static function donate_el( array $p, string $label ): array {
        return [
            'id'       => 'el_donate',
            'type'     => 'donation_form',
            'settings' => [
                'button_label' => $label,
                'button_color' => $p['accent'],
                'url'          => '',
                'width'        => 100,
                'align'        => 'center',
            ],
        ];
    }

    /**
     * Organizer block bound to the default organizer.
     *
     * @param string $role_title  Role shown under the name.
     * @param string $description Short bio.
     * @return array
     */
    private static function organizer_el( string $role_title, string $description ): array {
        return [
            'id'       => 'el_organizer',
            'type'     => 'organizer',
            'settings' => [
                'creator_user_id' => self::default_organizer_id(),
                'role_title'      => $role_title,
                'description'     => $description,
                'width'           => 100,
                'align'           => 'left',
            ],
        ];
    }

    /**
     * Social sharing buttons.
     *
     * @param string $headline Headline text.
     * @param string $align    left|center|right.
     * @return array
     */
    private static function share_el( string $headline, string $align = 'left' ): array {
        return [
            'id'       => 'el_share',
            'type'     => 'social_sharing',
            'settings' => [
                'headline'  => $headline,
                'facebook'  => true,
                'twitter'   => true,
                'linkedin'  => true,
                'threads'   => true,
                'pinterest' => false,
                'mastodon'  => false,
                'bluesky'   => false,
                'align'     => $align,
            ],
        ];
    }

    /**
     * Social profile links.
     *
     * The URLs are bare network home pages, never an invented handle: a
     * plausible-looking `facebook.com/<something>` points a fundraiser at an
     * account nobody involved controls.
     *
     * @param string $headline Headline text.
     * @param array  $links    Network => URL.
     * @param string $align    left|center|right.
     * @return array
     */
    private static function links_el( string $headline, array $links, string $align = 'left' ): array {
        return [
            'id'       => 'el_links',
            'type'     => 'social_links',
            'settings' => array_merge(
                [ 'headline' => $headline, 'align' => $align ],
                $links
            ),
        ];
    }

}
