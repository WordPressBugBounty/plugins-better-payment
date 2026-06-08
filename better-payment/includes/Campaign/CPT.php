<?php

namespace Better_Payment\Lite\Campaign;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;
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
     * Register the hidden campaign builder page under the Better Payment menu.
     */
    public function register_builder_page() {
        add_submenu_page(
            '',
            __( 'Campaign Builder', 'better-payment' ),
            __( 'Campaign Builder', 'better-payment' ),
            'manage_options',
            'bp-campaign-builder',
            [ $this, 'render_builder_page' ]
        );

        // Hidden pages (parent = '') are not found by get_admin_page_title(), leaving
        // $title null and causing a strip_tags() deprecation in admin-header.php.
        // Set the global before the header loads.
        add_action( 'current_screen', static function ( $screen ) {
            if ( 'admin_page_bp-campaign-builder' === $screen->id ) {
                global $title;
                $title = __( 'Campaign Builder', 'better-payment' );
            }
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

        wp_enqueue_script( 'bp-campaign-builder' );
        wp_enqueue_style( 'bp-campaign-builder' );

        $nonce = wp_create_nonce( 'wp_rest' );

        ?>
        <div id="bp-campaign-builder"
             data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
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
            'templates' => array_values( TemplateManager::get_all() ),
            'restUrl'   => rest_url( 'better-payment/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => admin_url(),
        ] );
    }

    /**
     * Enqueue campaign builder assets on the builder page only.
     */
    public function enqueue_builder_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

        if ( $page !== 'bp-campaign-builder' ) {
            return;
        }

        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : BETTER_PAYMENT_VERSION;

        wp_register_script(
            'bp-campaign-builder',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-builder/campaign-builder.min.js',
            [ 'react', 'react-dom', 'wp-element', 'wp-api-fetch', 'wp-i18n' ],
            $version,
            true
        );

        wp_register_style(
            'bp-campaign-builder',
            BETTER_PAYMENT_ASSETS . '/admin/campaign-builder/campaign-builder.min.css',
            [],
            $version
        );

        // Load campaign display CSS so the preview modal renders correctly.
        $display_css = BETTER_PAYMENT_PATH . '/assets/blocks/campaign-display/style.min.css';
        if ( file_exists( $display_css ) ) {
            wp_enqueue_style(
                'better-payment-campaign-display-style',
                BETTER_PAYMENT_ASSETS . '/blocks/campaign-display/style.min.css',
                [],
                filemtime( $display_css )
            );
        }

        wp_enqueue_media();

        // Zero out all WP admin chrome spacing so the builder fills edge-to-edge.
        wp_add_inline_style( 'bp-campaign-builder', '
            #wpcontent { padding-left: 0 !important; }
            #adminmenushadow { display: none !important; }
            #wpbody-content { overflow: hidden !important; padding: 0 !important; }
            #wpbody-content .wrap { margin: 0 !important; padding: 0 !important; max-width: none !important; }
        ' );

        // Global currency from plugin settings — builder uses this everywhere.
        $global_currency = DB::get_settings( 'better_payment_settings_general_general_currency' );
        if ( ! is_string( $global_currency ) || $global_currency === '' ) {
            $global_currency = 'USD';
        }

        // Localize element registry, templates, and global settings to JS builder.
        wp_localize_script( 'bp-campaign-builder', 'betterPaymentCampaignData', [
            'elements'       => array_values( ElementRegistry::get_all() ),
            'templates'      => array_values( TemplateManager::get_all() ),
            'globalCurrency' => $global_currency,
            'restUrl'        => rest_url( 'better-payment/v1/' ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'pluginUrl'      => plugins_url( '', BETTER_PAYMENT_BASENAME ),
        ] );
    }
}
