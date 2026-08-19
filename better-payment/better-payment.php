<?php

/**
 * Plugin Name: Better Payment
 * Description: Better Payment allows you to automate payment transactions to manage donations, make payments, sell products, and more on your Elementor and Gutenberg website.
 * Plugin URI: https://wpdeveloper.com/
 * Author: WPDeveloper
 * Version: 2.3.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author URI: https://wpdeveloper.com/
 * Text Domain: better-payment
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

require_once __DIR__ . '/vendor/autoload.php';

/**
 * The plugin main class
 *
 * @since 0.0.1
 */
final class Better_Payment {

    use Better_Payment\Lite\Traits\Helper;

    /**
     * Plugin version
     *
     * @var string
     * @since 0.0.1
     */
    const version = '2.3.2';

    /**
     * Class construcotr
     *
     * @since 0.0.1
     */
    private function __construct() {
        $this->define_constants();

        register_activation_hook(__FILE__, [$this, 'activate']);

        add_action('plugins_loaded', [$this, 'init_plugin']);
    }

    /**
     * Initialize a singleton instance
     *
     * @return \Better_Payment
     * @since 0.0.1
     */
    public static function init() {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     * @since 0.0.1
     */
    public function define_constants() {
        define('BETTER_PAYMENT_VERSION', self::version);
        // Bump this whenever the campaign CPT rewrite rules change (slug, etc.) to
        // trigger a one-time flush_rewrite_rules() on the next init for existing installs.
        define('BETTER_PAYMENT_CAMPAIGN_REWRITE_VERSION', '1');
        define('BETTER_PAYMENT_FILE', __FILE__);
        define('BETTER_PAYMENT_BASENAME', plugin_basename(__FILE__));
        define('BETTER_PAYMENT_PATH', __DIR__);
        define('BETTER_PAYMENT_URL', plugins_url('', BETTER_PAYMENT_FILE));
        define('BETTER_PAYMENT_ASSETS', BETTER_PAYMENT_URL . '/assets');
        define('BETTER_PAYMENT_ASSETS_PATH', BETTER_PAYMENT_PATH . '/assets');
        define('BETTER_PAYMENT_DEV_ASSETS', BETTER_PAYMENT_URL . '/bpbuild');
        define('BETTER_PAYMENT_DEV_ASSETS_PATH', BETTER_PAYMENT_PATH . '/bpbuild');
        define('BETTER_PAYMENT_INCLUDES_PATH', BETTER_PAYMENT_PATH . '/includes');
        define('BETTER_PAYMENT_ADMIN_PATH', BETTER_PAYMENT_INCLUDES_PATH . '/Admin');
        define('BETTER_PAYMENT_ADMIN_VIEWS_PATH', BETTER_PAYMENT_ADMIN_PATH . '/views');
    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     * @since 0.0.1
     */
    public function activate() {
        $installer = new Better_Payment\Lite\Installer();
        $installer->run();

        // Register the campaign CPT and flush rewrite rules so campaign permalinks
        // (/bp-campaign/{slug}/) resolve immediately, without needing a manual
        // Settings → Permalinks re-save. Mark the rewrite version so the init-time
        // self-heal flush (see init_campaign_module) does not run redundantly.
        ( new Better_Payment\Lite\Campaign\CPT() )->register();
        flush_rewrite_rules();
        update_option( 'better_payment_campaign_rewrite_version', BETTER_PAYMENT_CAMPAIGN_REWRITE_VERSION );

        if (get_option('better_payment_plugin_installed_fresh') !== 'yes' && get_option('better_payment_plugin_installed_time_fresh') === false) {
            update_option('better_payment_plugin_installed_fresh', 'yes');

            $now = time();
            update_option('better_payment_plugin_installed_time_fresh', $now);
            update_option('better_payment_progress_bar_dismissed_expiry_date', $now + 7 * DAY_IN_SECONDS);
        }
    }

    /**
     * Initialize the plugin
     *
     * @return void
     * @since 0.0.1
     */
    public function init_plugin() {
        new Better_Payment\Lite\Assets();

        if (defined('DOING_AJAX') && DOING_AJAX) {
            new Better_Payment\Lite\Ajax();
        }

        if (is_admin()) {
            $adminObj = new Better_Payment\Lite\Admin();
            $adminObj->init();

            if ( !$this->bp_section_dismissed() ) {
                add_action('save_post', array($this, 'bp_widget_usage_on_save'), 10, 1);
            }
        } else {
            new Better_Payment\Lite\Frontend();
        }

        /**
         * Usage tracking must be registered on CRON requests too, not just admin.
         *
         * This used to be wired only from Admin::init(), which runs solely under
         * is_admin(). But WP-Cron executes wp-cron.php as a NON-admin request, so
         * Plugin_Usage_Tracker::init() never ran there and its do_tracking()
         * callback was never attached. The daily event was scheduled and fired on
         * time — into an empty hook. The tracker therefore never sent anything on
         * its own schedule; the only payloads that ever went out were the forced
         * ones from the setup wizard's opt-in. Silent, and invisible from the
         * cron listing, which shows the event as perfectly healthy.
         *
         * Not registered on front-end requests: nothing here is needed to render a
         * page, and this is a payment plugin whose public pages should carry no
         * avoidable work. Admin + cron is the complete set of contexts the tracker
         * acts in (notice, action links, AJAX, and the scheduled send).
         *
         * @since 2.3.2
         */
        if ( is_admin() || wp_doing_cron() ) {
            add_action( 'init', array( $this, 'start_plugin_tracking' ) );
        }

        new Better_Payment\Lite\API();

        // Initialize Gutenberg blocks
        Better_Payment\Lite\Blocks\BlockManager::get_instance();
        Better_Payment\Lite\Blocks\StyleHandler::init();

        // Initialize Block Actions for payment processing (runs before Elementor Actions)
        new Better_Payment\Lite\Blocks\BlockActions();

        // Always register PayPal IPN listener + verification poll. These must run even
        // when Elementor is inactive, because PayPal payments also flow through the
        // block/campaign path. (Previously registered only inside the Elementor gate
        // via Classes\Actions, so non-Elementor sites never confirmed PayPal payments.)
        add_action( 'admin_post_better_payment_paypal_ipn',         [ 'Better_Payment\Lite\Classes\Handler', 'handle_paypal_ipn' ] );
        add_action( 'admin_post_nopriv_better_payment_paypal_ipn',  [ 'Better_Payment\Lite\Classes\Handler', 'handle_paypal_ipn' ] );
        add_action( 'wp_ajax_better_payment_check_paypal_status',        [ 'Better_Payment\Lite\Classes\Handler', 'check_paypal_status' ] );
        add_action( 'wp_ajax_nopriv_better_payment_check_paypal_status', [ 'Better_Payment\Lite\Classes\Handler', 'check_paypal_status' ] );

        if (defined('ELEMENTOR_VERSION')) {
            new Better_Payment\Lite\Classes\Actions();
            $el_integration = new Better_Payment\Lite\Admin\Elementor\EL_Integration();
            $el_integration->init();

            Better_Payment\Lite\Admin\Settings::save_default_settings();
        }

        // ── Campaign Builder module ────────────────────────────────────
        $this->init_campaign_module();

        // ── AI module (AI-native Campaign Builder) ─────────────────────
        $this->init_ai_module();

        // ── WooCommerce gateway module (Better Payment as a WC gateway) ─
        $this->init_woocommerce_module();
    }

    /**
     * Initialize the WooCommerce integration module.
     *
     * Registers the "Better Payment (Stripe)" WooCommerce payment gateway,
     * which consumes the existing Better Payment payment engine (Stripe
     * checkout creation, transaction persistence, verification, hooks).
     * No-op when WooCommerce is not active — none of the module's classes
     * load, and core never depends on WooCommerce.
     *
     * @return void
     */
    private function init_woocommerce_module() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        Better_Payment\Lite\WooCommerce\Loader::register();
    }

    /**
     * Initialize the AI module (providers, operations, REST endpoints).
     *
     * @return void
     */
    private function init_ai_module() {
        // Register providers + resolve settings-driven config.
        ( new Better_Payment\Lite\AI\AIManager() )->init();

        // REST API for AI (chat/generate/analyze/image/conversations/config).
        new Better_Payment\Lite\API\AIAPI();
    }

    /**
     * Initialize the Campaign Builder module.
     *
     * @return void
     */
    private function init_campaign_module() {
        $cpt = new Better_Payment\Lite\Campaign\CPT();

        // CPT registration (runs on init)
        add_action( 'init', [ $cpt, 'register' ] );

        // Self-heal rewrite rules for installs that updated into this version
        // (the activation hook does NOT re-run on plugin update). Runs after the
        // CPT is registered (priority 11 > default 10) so the new rule is present
        // when we flush, then records the version so it flushes only once.
        add_action( 'init', static function () {
            if ( get_option( 'better_payment_campaign_rewrite_version' ) !== BETTER_PAYMENT_CAMPAIGN_REWRITE_VERSION ) {
                flush_rewrite_rules();
                update_option( 'better_payment_campaign_rewrite_version', BETTER_PAYMENT_CAMPAIGN_REWRITE_VERSION );
            }
        }, 11 );

        // Admin-only: builder page, submenu, asset enqueue
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $cpt, 'register_builder_page' ], 20 );
            add_action( 'admin_enqueue_scripts', [ $cpt, 'enqueue_builder_assets' ] );
            add_action( 'admin_enqueue_scripts', [ $cpt, 'enqueue_list_assets' ] );
            add_action( 'load-post-new.php',     [ $cpt, 'redirect_new_post' ] );
            add_action( 'load-post.php',         [ $cpt, 'redirect_edit_post' ] );
            add_filter( 'get_edit_post_link',    [ $cpt, 'filter_edit_link' ], 10, 3 );
            add_action( 'admin_init',            [ $cpt, 'redirect_cpt_list' ] );

            // Save post meta when post is saved via standard WP (rare path)
            add_action( 'save_post_bp_campaign', [ new Better_Payment\Lite\Campaign\MetaBox(), 'save' ] );

            // Custom columns on campaign list table
            $admin_list = new Better_Payment\Lite\Campaign\CampaignListColumns();
            add_filter( 'manage_bp_campaign_posts_columns',        [ $admin_list, 'add_columns' ] );
            add_action( 'manage_bp_campaign_posts_custom_column',  [ $admin_list, 'render_column' ], 10, 2 );
        }

        // Serve a custom template for bp_campaign single pages so RendererService renders
        // the campaign content instead of the theme's empty single.php.
        add_filter( 'template_include', static function ( $template ) {
            if ( is_singular( 'bp_campaign' ) ) {
                $custom = BETTER_PAYMENT_PATH . '/templates/single-bp_campaign.php';
                return file_exists( $custom ) ? $custom : $template;
            }
            return $template;
        } );

        // Register element types and templates (must run before CPT enqueue_builder_assets).
        // Deferred to init so __() calls don't trigger translation loading during plugins_loaded.
        add_action( 'init', [ 'Better_Payment\Lite\Campaign\Elements\CampaignElements', 'register_all' ], 5 );

        // Campaign display block (server-side render)
        $block = new Better_Payment\Lite\Campaign\CampaignBlock();
        add_action( 'init', [ $block, 'register' ], 20 );

        // Shortcode [bp_campaign id="42"]
        $shortcode = new Better_Payment\Lite\Campaign\Shortcode();
        $shortcode->register();

        // REST API for campaigns
        new Better_Payment\Lite\API\CampaignAPI();

        // Bust campaign stats cache when a payment is confirmed by any gateway.
        Better_Payment\Lite\Campaign\CampaignStats::register_hooks();
    }
}

/**
 * Initializes the main plugin
 *
 * @return \Better_Payment
 * @since 0.0.1
 */

Better_Payment::init();

/**
 * Plugin migrator
 *
 * @since 0.0.2
 */
function better_payment_migrator() {
    Better_Payment\Lite\Classes\Migrator::migrator();
}


/**
 * On wp load
 *
 * @return void
 * @since 0.0.1
 */
add_action('wp_loaded', function () {
    if (get_option('better_payment_version') != BETTER_PAYMENT_VERSION) {
        better_payment_migrator();
        update_option('better_payment_version', BETTER_PAYMENT_VERSION);
    }

    $setup_wizard = get_option('better_payment_setup_wizard');

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $is_from_better_payment = (isset( $_POST['form_data'] ) && isset( $_POST['form_data'][0] ) && isset( $_POST['form_data'][0]['name'] ) && strpos( $_POST['form_data'][0]['name'], 'better_payment_' ) !== false) || (isset( $_POST['is_tracking'] ) && $_POST['is_tracking'] === 'true') || (isset( $_POST['action'] ) && $_POST['action'] === 'save_setup_wizard_data');

        if ( ! $is_from_better_payment ) {
            return;
        }
    }

    if ($setup_wizard == 'redirect') {
        Better_Payment\Lite\Admin\Setup_Wizard::redirect();
    }

    if ($setup_wizard == 'init') {
        new Better_Payment\Lite\Admin\Setup_Wizard();
    }
});

/**
 * Dispatch actions
 *
 * @since 0.0.1
 */
\Better_Payment\Lite\Admin::dispatch_actions();
