<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\AI\AIManager;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;
use Better_Payment\Lite\Campaign\Templates\CategoryRegistry;
use Better_Payment\Lite\Campaign\Templates\TemplateManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the bp_campaign Custom Post Type and the
 * hidden campaign builder admin page.
 */
class CPT extends Controller {

    /**
     * Register the CPT and the builder admin page.
     */
    public function register() {
        $args = apply_filters( 'better_payment/campaign/cpt_args', [
            'label'               => __( 'Campaigns', 'better-payment' ),
            'labels'              => [
                'name'               => __( 'Campaigns', 'better-payment' ),
                'singular_name'      => __( 'Campaign', 'better-payment' ),
                'add_new'            => __( 'Add New', 'better-payment' ),
                'add_new_item'       => __( 'Add New Campaign', 'better-payment' ),
                'edit_item'          => __( 'Edit Campaign', 'better-payment' ),
                'new_item'           => __( 'New Campaign', 'better-payment' ),
                'view_item'          => __( 'View Campaign', 'better-payment' ),
                'search_items'       => __( 'Search Campaigns', 'better-payment' ),
                'not_found'          => __( 'No campaigns found', 'better-payment' ),
                'not_found_in_trash' => __( 'No campaigns found in trash', 'better-payment' ),
            ],
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'rewrite'             => [ 'slug' => 'bp-campaign', 'with_front' => false ],
            'supports'            => [ 'title', 'thumbnail' ],
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-heart',
        ] );

        register_post_type( 'bp_campaign', $args );
    }

    /**
     * Register the campaign builder page under the Better Payment menu.
     *
     * Registered as a real submenu of 'better-payment-admin' (not orphaned with
     * empty parent) so WordPress keeps the sidebar open and the Campaigns item
     * highlighted automatically. The submenu link is hidden via CSS so it doesn't
     * appear as a visible menu entry.
     */
    public function register_builder_page() {
        add_submenu_page(
            'better-payment-admin',
            __( 'Campaign Builder', 'better-payment' ),
            __( 'Campaign Builder', 'better-payment' ),
            'manage_options',
            'bp-campaign-builder',
            [ $this, 'render_builder_page' ]
        );

        // Hide the submenu link — it should never appear in the sidebar.
        add_action( 'admin_head', static function () {
            echo '<style>#adminmenu a[href="admin.php?page=bp-campaign-builder"]{display:none!important}</style>';
        } );

        // Redirect the active-submenu highlight from the hidden "Campaign Builder"
        // entry to the visible "Campaigns" tab so it appears selected in the sidebar.
        add_filter( 'submenu_file', static function ( $submenu_file ) {
            if ( isset( $_GET['page'] ) && 'bp-campaign-builder' === $_GET['page'] ) {
                return 'better-payment-admin&tab=campaigns';
            }
            return $submenu_file;
        } );
    }

    /**
     * Inject the Campaigns entry into the Better Payment submenu list
     * immediately after Transactions.
     *
     * @param array  $list
     * @param string $prefix
     * @return array
     */
    public function inject_campaigns_submenu( array $list, string $prefix ): array {
        $new               = [];
        $transactions_key  = $prefix . '-admin&tab=transactions';

        foreach ( $list as $slug => $item ) {
            $new[ $slug ] = $item;

            if ( $slug === $transactions_key ) {
                $new['edit.php?post_type=bp_campaign'] = [
                    'title'      => __( 'Campaigns', 'better-payment' ),
                    'capability' => 'manage_options',
                    'callback'   => '',
                ];
            }
        }

        return $new;
    }

    /**
     * Render the campaign builder page shell — React app mounts here.
     */
    public function render_builder_page() {
        $campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
        $campaign    = $campaign_id ? get_post( $campaign_id ) : null;

        if ( $campaign && $campaign->post_type !== 'bp_campaign' ) {
            $campaign    = null;
            $campaign_id = 0;
        }

        // Optional start mode for a new campaign: `start=ai` opens straight into the
        // editor with the AI Assistant ready (from the "Campaign With AI" button).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $start = isset( $_GET['start'] ) ? sanitize_key( wp_unslash( $_GET['start'] ) ) : '';

        wp_enqueue_script( 'bp-campaign-builder' );
        wp_enqueue_style( 'bp-campaign-builder' );

        $nonce = wp_create_nonce( 'wp_rest' );

        ?>
        <div id="bp-campaign-builder"
             data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
             data-start="<?php echo esc_attr( $start ); ?>"
             data-rest-url="<?php echo esc_url( rest_url( 'better-payment/v1/' ) ); ?>"
             data-nonce="<?php echo esc_attr( $nonce ); ?>"
             data-admin-url="<?php echo esc_url( admin_url() ); ?>"
             data-campaigns-url="<?php echo esc_url( admin_url( 'admin.php?page=better-payment-admin&tab=campaigns' ) ); ?>"
        ></div>
        <?php
    }

    /**
     * Rewrite the edit link for bp_campaign posts to point to the builder.
     * Covers row-action "Edit", title links, and any get_edit_post_link() call.
     *
     * @param string $url
     * @param int    $post_id
     * @param string $_context
     * @return string
     */
    public function filter_edit_link( string $url, int $post_id, string $_context ): string {
        if ( get_post_type( $post_id ) !== 'bp_campaign' ) {
            return $url;
        }

        return esc_url( admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . $post_id ) );
    }

    /**
     * Redirect edit.php?post_type=bp_campaign to the custom campaigns tab so
     * users never land on the raw WP post list screen.
     * Skipped for AJAX and REST requests.
     */
    public function redirect_cpt_list() {
        if ( wp_doing_ajax() ) {
            return;
        }

        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';

        if (
            'edit.php' === $GLOBALS['pagenow']
            && $post_type === 'bp_campaign'
        ) {
            wp_safe_redirect( admin_url( 'admin.php?page=better-payment-admin&tab=campaigns' ) );
            exit;
        }
    }

    /**
     * Redirect post.php?action=edit for bp_campaign in case the old URL is
     * reached directly (bookmarks, browser history, etc.).
     */
    public function redirect_edit_post() {
        $action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

        if ( $action !== 'edit' || ! $post_id ) {
            return;
        }

        if ( get_post_type( $post_id ) !== 'bp_campaign' ) {
            return;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . $post_id ) );
        exit;
    }

    /**
     * Redirect post-new.php for bp_campaign to the campaigns list so the
     * template-select modal flow is used instead of the classic editor.
     */
    public function redirect_new_post() {
        if (
            isset( $_GET['post_type'] ) &&
            sanitize_key( $_GET['post_type'] ) === 'bp_campaign'
        ) {
            wp_safe_redirect( admin_url( 'edit.php?post_type=bp_campaign' ) );
            exit;
        }
    }

    /**
     * Enqueue the campaign-list script and its template data on the
     * bp_campaign post list table page.
     */
    public function enqueue_list_assets( string $hook ) {
        if ( $hook !== 'edit.php' ) {
            return;
        }

        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';
        if ( $post_type !== 'bp_campaign' ) {
            return;
        }

        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : BETTER_PAYMENT_VERSION;

        wp_enqueue_script(
            'bp-campaign-list',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-list/campaign-list.min.js',
            [ 'react', 'react-dom', 'wp-element', 'wp-i18n' ],
            $version,
            true
        );

        wp_enqueue_style(
            'bp-campaign-list',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-list/campaign-list.min.css',
            [],
            $version
        );

        wp_localize_script( 'bp-campaign-list', 'betterPaymentCampaignData', [
            'templates'  => array_values( TemplateManager::get_all() ),
            'categories' => CategoryRegistry::for_client(),
            'restUrl'    => rest_url( 'better-payment/v1/' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'adminUrl'   => admin_url(),
        ] );
    }

    /**
     * Cache-busting version for a built asset — its mtime, not the plugin version.
     *
     * The builder bundle is rebuilt far more often than BETTER_PAYMENT_VERSION is
     * bumped, so versioning on the plugin version pinned every developer, and every
     * site updated in place, to whichever bundle their browser cached first. That is
     * how a Pro install kept rendering the *free* palette — crowned, dashed, amber
     * icons — and the free upgrade banner long after Pro was active: the localized
     * `proEnabled` is printed inline and was always correct, only the JS that reads
     * it was months stale.
     *
     * Falls back to the plugin version if the file is missing (an incomplete build),
     * which is no worse than the old behaviour.
     *
     * @param string $relative_path Path below the plugin root, with a leading slash.
     * @return string|int
     */
    private static function asset_version( $relative_path ) {
        $file = self::asset_path( $relative_path );

        return file_exists( $file ) ? filemtime( $file ) : BETTER_PAYMENT_VERSION;
    }

    /**
     * Absolute path to a built asset.
     *
     * @param string $relative_path Path below the plugin root, with a leading slash.
     * @return string
     */
    private static function asset_path( $relative_path ) {
        return BETTER_PAYMENT_PATH . $relative_path;
    }

    /**
     * Enqueue campaign builder assets on the builder page only.
     */
    public function enqueue_builder_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

        if ( $page !== 'bp-campaign-builder' ) {
            return;
        }

        wp_register_script(
            'bp-campaign-builder',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-builder/campaign-builder.min.js',
            [ 'react', 'react-dom', 'wp-element', 'wp-api-fetch', 'wp-i18n' ],
            self::asset_version( '/assets/admin/campaign-builder/campaign-builder.min.js' ),
            true
        );

        wp_register_style(
            'bp-campaign-builder',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-builder/campaign-builder.min.css',
            [],
            self::asset_version( '/assets/admin/campaign-builder/campaign-builder.min.css' )
        );

        // Load campaign display CSS so the preview modal renders correctly. Still
        // guarded on existence — this one is optional (an unbuilt blocks directory
        // is a normal dev state), and enqueuing a URL that 404s is worse than
        // skipping it.
        $display_css = '/assets/blocks/campaign-display/style.min.css';
        if ( file_exists( self::asset_path( $display_css ) ) ) {
            wp_enqueue_style(
                'better-payment-campaign-display-style',
                BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css',
                [],
                self::asset_version( $display_css )
            );
        }

        wp_enqueue_media();

        // Zero out all WP admin chrome spacing so the builder fills edge-to-edge.
        // The footer styling mirrors the Better Payment admin footer (see
        // ReactAdmin::get_footer_version) so the builder page\'s branded footer
        // looks identical to the other dashboard pages — the heavy React admin
        // stylesheet is not loaded here, so the rules are inlined.
        wp_add_inline_style( 'bp-campaign-builder', '
            #wpcontent { padding-left: 0 !important; }
            #adminmenushadow { display: none !important; }
            /* Match the WP admin surfaces to the builder background ($bg
               #f4f5f8) so the empty band below a short builder blends in
               instead of showing WordPress\'s default #f0f0f1. body/#wpwrap are
               the elements that stay full-height — the content-column elements
               (#wpcontent/#wpbody/#wpbody-content) collapse to the builder
               height, so recoloring only those leaves the body grey exposed. */
            body.wp-admin, #wpwrap, #wpcontent, #wpbody, #wpbody-content { background: #f4f5f8 !important; }
            /* Editor tab is a uniform white workspace (white canvas + white
               sidebar), so whiten the backstop too — App.js toggles
               body.bp-cb-tab-editor with the active tab. */
            body.bp-cb-tab-editor, body.bp-cb-tab-editor #wpwrap, body.bp-cb-tab-editor #wpcontent, body.bp-cb-tab-editor #wpbody, body.bp-cb-tab-editor #wpbody-content { background: #fff !important; }
            #wpbody-content { overflow: hidden !important; padding: 0 !important; }
            #wpbody-content .wrap { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            #wpfooter .alignright { gap: 30px; display: flex; }
            #wpfooter .alignright, #wpfooter .alignleft { color: #6a758c; font-weight: 400; font-size: 14px; }
            #wpfooter .alignright a, #wpfooter .alignleft a { font-weight: 500; color: #6b59ee; }
            #wpfooter .alignright .bp-footer-version, #wpfooter .alignleft .bp-footer-version { padding: 4px 8px; border-radius: 20px; margin: 0 8px; color: #6b59ee; background-color: #fcfcfc; }
            #wpfooter .alignright .bp-footer-version-divider, #wpfooter .alignleft .bp-footer-version-divider { position: relative; }
            #wpfooter .alignright .bp-footer-version-divider::after, #wpfooter .alignleft .bp-footer-version-divider::after { position: absolute; content: ""; background-color: #b9bfca; padding: 1px; top: 2px; bottom: 2px; right: -11px; }
            #wpfooter .bp-free-version { display: flex; align-items: center; gap: 2px; }
        ' );

        // Global currency from plugin settings — builder uses this everywhere.
        $global_currency = DB::get_settings( 'better_payment_settings_general_general_currency' );
        if ( ! is_string( $global_currency ) || $global_currency === '' ) {
            $global_currency = 'USD';
        }

        $data = [
            'elements'       => array_values( ElementRegistry::get_all() ),
            'templates'      => array_values( TemplateManager::get_all() ),
            // The category taxonomy, shared by the template picker's sidebar and
            // the AI wizard's "What are you raising funds for?" tiles. Both used
            // to hardcode their own list and drifted apart; this is the one list.
            'categories'     => CategoryRegistry::for_client(),
            'globalCurrency' => $global_currency,
            // Which weekday the AI wizard's calendar starts on (0 = Sunday), per
            // Settings → General. Without it the grid would always be Sunday-first.
            'startOfWeek'    => (int) get_option( 'start_of_week', 0 ),
            'restUrl'        => rest_url( 'better-payment/v1/' ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'pluginUrl'      => plugins_url( '', BETTER_PAYMENT_BASENAME ),
            'proEnabled'     => (bool) apply_filters( 'better_payment/pro_enabled', false ),
            'upgradeUrl'     => 'https://wpdeveloper.com/in/upgrade-better-payment-pro',
            // Seed the AI enabled/configured flags synchronously so the AI panel's
            // "disabled" / "add an API key" notice paints immediately instead of
            // flickering in after the async /ai/config round-trip resolves. The
            // panel still fetches the full config (providers, operations) after
            // mount; this only pre-answers the two flags the notice reads.
            'aiConfig'       => self::ai_config_seed(),
        ];

        /*
         * NOT wp_localize_script(). That function was built for L10n strings and
         * casts every *scalar* in the array to a string on the way out
         * (`$l10n[ $key ] = html_entity_decode( (string) $value, ... )` in
         * WP_Scripts::localize). Arrays survive; booleans and ints do not.
         *
         * `proEnabled => true` therefore reached JS as the string "1", and App.js
         * tested it with `=== true`. That comparison was never once true on any
         * install — which is why an active Pro licence still drew crowns on the
         * palette, disabled every control in the settings panel, and showed the
         * free upgrade banner. PHP was right the whole way down; the boolean died
         * in transport.
         *
         * wp_add_inline_script + wp_json_encode preserves real types, and is what
         * Blocks\BlockManager already does for window.betterPaymentBlockData —
         * which is precisely why the identical `=== true` check works over there.
         * Position 'before' puts it ahead of the bundle, same ordering as
         * wp_localize_script gave us.
         */
        wp_add_inline_script(
            'bp-campaign-builder',
            'window.betterPaymentCampaignData = ' . wp_json_encode( $data ) . ';',
            'before'
        );
    }

    /**
     * The two AI flags the builder's AI panel needs at first paint: whether the
     * feature is enabled, and whether the active provider has an API key. Mirrors
     * the `enabled` / `configured` fields of the `/ai/config` REST response so the
     * seeded value is drop-in compatible with what the async fetch returns later.
     *
     * @return array{ enabled: bool, configured: bool }
     */
    private static function ai_config_seed(): array {
        $provider = AIManager::active_provider();

        return [
            'enabled'    => AIManager::is_enabled(),
            'configured' => null !== $provider && $provider->is_configured(),
        ];
    }
}
