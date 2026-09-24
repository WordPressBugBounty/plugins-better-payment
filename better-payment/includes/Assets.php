<?php

namespace Better_Payment\Lite;

use Better_Payment\Lite\Classes\ProPaymentFormScripts;
use Better_Payment\Lite\Classes\ProUserDashboardAssets;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets handler class
 *
 * @since 0.0.1
 */
class Assets extends Controller
{
    use TraitsHelper;

    /**
     * Guard flag — register_assets() must only build and register handles once per request.
     *
     * @var bool
     */
    private $registered = false;

    /**
     * Class constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'register_localize_script']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_localize_script()
    {
        // Localize BetterPaymentLocalize for block editor
        wp_localize_script('better-payment-block-localize', 'BetterPaymentLocalize', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'better_payment_block_nonce' ),
        ));
    }

    /**
     * All available scripts
     *
     * The Pro-feature copies (Classes\ProPaymentFormScripts, Classes\ProUserDashboardAssets)
     * are always registered, but they are a dependency of `better-payment` and
     * `better-payment-common-script` only while ProCompatibility::lite_copy_applies()
     * says so, and nothing else enqueues them.
     *
     * @return array
     * @since 0.0.1
     */
    public function get_scripts()
    {
        return [
            'better-payment-block-localize' => [
                'src'     => file_exists( BETTER_PAYMENT_PATH . '/assets/js/block-localize.min.js' )
                    ? BETTER_PAYMENT_ASSETS . '/js/block-localize.min.js'
                    : BETTER_PAYMENT_ASSETS . '/js/block-localize.js',
                'version' => BETTER_PAYMENT_VERSION,
                'deps'    => array(),
            ],
            'better-payment-vendor-bundle' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/vendors/js/bundles.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/vendors/js/bundles.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/vendors/js/bundles.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array(),
            ],
            'better-payment-babel-bundle' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/vendors/js/bundle.babel.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/vendors/js/bundle.babel.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/vendors/js/bundle.babel.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array(),
            ],
            'better-payment-controls' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/controls/modules.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/controls/modules.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/controls/modules.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array_merge(
                    $this->get_controls_dependencies(),
                    ['wp-polyfill', 'better-payment-block-localize','better-payment-vendor-bundle', 'better-payment-babel-bundle']),
            ],
            'better-payment-controls-frontend' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/controls/frontend.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/controls/frontend.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/controls/frontend.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => []
            ],
            'better-payment-common-script' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/common.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/common.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/common.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array_merge( ['jquery', 'wp-util'], ProUserDashboardAssets::script_depends() ),
            ],
            // Transitional copy of Pro's User Dashboard handlers (datepicker, date range modal, View All); see Classes\ProUserDashboardAssets.
            ProUserDashboardAssets::SCRIPT_HANDLE => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/user-dashboard-pro.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/user-dashboard-pro.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/user-dashboard-pro.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery', 'jquery-ui-datepicker'],
            ],
            'better-payment-stripe' => [
                'src'     => 'https://js.stripe.com/v3/',
            ],
            'better-payment' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/better-payment.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/better-payment.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/better-payment.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array_merge( ['jquery', 'better-payment-stripe', 'toastr-js'], ProPaymentFormScripts::script_depends() ),
            ],
            // Transitional copy of Pro's Payment Form quantity total (layouts 4 and 5); see Classes\ProPaymentFormScripts.
            ProPaymentFormScripts::HANDLE => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/payment-form-pro-quantity.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/payment-form-pro-quantity.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/payment-form-pro-quantity.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery'],
            ],
            'bp-dashboard-pagination' => [
                'src'     => file_exists( BETTER_PAYMENT_PATH . '/assets/js/bp-dashboard-pagination.min.js' )
                    ? BETTER_PAYMENT_ASSETS . '/js/bp-dashboard-pagination.min.js'
                    : BETTER_PAYMENT_ASSETS . '/js/bp-dashboard-pagination.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/bp-dashboard-pagination.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/bp-dashboard-pagination.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => [],
            ],
            'toastr-js' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/toastr/js/toastr.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/vendor/toastr/js/toastr.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/vendor/toastr/js/toastr.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery']
            ],
            'bp-admin-settings' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/admin.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/admin.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/admin.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery']
            ],
            'better-payment-script' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/frontend.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/frontend.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/frontend.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery']
            ],
            'fundraising-campaign-script' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/fundraising-campaign.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/fundraising-campaign.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/fundraising-campaign.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery']
            ],
        ];
    }

    /**
     * All available styles
     *
     * @return array
     * @since 0.0.1
     */
    public function get_styles()
    {
        return [
            'better-payment-common-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/common.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/common.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/common.min.css') : BETTER_PAYMENT_VERSION,
            ],
            // Transitional copy of Pro's User Dashboard card styles; a dependency of the admin
            // stylesheet only while it applies, so it prints between common and admin. See
            // Classes\ProUserDashboardAssets.
            ProUserDashboardAssets::STYLE_HANDLE => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/user-dashboard-pro.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/user-dashboard-pro.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/user-dashboard-pro.min.css') : BETTER_PAYMENT_VERSION,
                'deps'    => ['better-payment-common-style'],
            ],
            'bp-dashboard-pagination-style' => [
                'src'     => file_exists( BETTER_PAYMENT_PATH . '/assets/css/bp-dashboard-pagination.min.css' )
                    ? BETTER_PAYMENT_ASSETS . '/css/bp-dashboard-pagination.min.css'
                    : BETTER_PAYMENT_ASSETS . '/css/bp-dashboard-pagination.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/bp-dashboard-pagination.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/bp-dashboard-pagination.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'better-payment-el' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/better-payment-el.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/better-payment-el.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/better-payment-el.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'bp-icon-front' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/icon/style.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'toastr-css' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/toastr/css/toastr.min.css',
            ],
            'jquery-ui' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/jquery-ui/css/jquery-ui.min.css',
            ],
            'bp-icon-admin' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/icon/style.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'better-payment-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/frontend.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/frontend.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/frontend.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'better-payment-admin-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/admin.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/admin.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/admin.min.css') : BETTER_PAYMENT_VERSION,
                'deps'    => ProUserDashboardAssets::style_depends(),
            ],
            'bp-font-awesome' => [
                'src'     => file_exists( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    ? BETTER_PAYMENT_ASSETS . '/vendor/fontawesome/css/all.min.css'
                    : 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css',
                'version' => file_exists( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    ? filemtime( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    : '6',
            ],
            'better-payment-wc-subscriptions' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/woocommerce-subscriptions.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/woocommerce-subscriptions.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/woocommerce-subscriptions.css') : BETTER_PAYMENT_VERSION,
            ],
            'fundraising-campaign-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/fundraising-campaign.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign.min.css') : BETTER_PAYMENT_VERSION,
            ],
            // The Pro Updates tab; loaded by Classes\ProFundraisingUpdateStyles for Pro releases that do not load their own.
            'fundraising-campaign-updates-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/fundraising-campaign-updates.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign-updates.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign-updates.min.css') : BETTER_PAYMENT_VERSION,
                'deps'    => ['fundraising-campaign-style'],
            ],
            'better-payment-editor-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/editor.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/editor.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/editor.min.css') : BETTER_PAYMENT_VERSION,
                'admin_enqueue' => true,
            ],
            'better-payment-controls-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/controls/style.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/controls/style.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/controls/style.min.css') : BETTER_PAYMENT_VERSION,
                'admin_enqueue' => true,
            ],
        ];
    }

    /**
     * Get controls dependencies from asset file
     *
     * @return array
     * @since 0.0.1
     */
    private function get_controls_dependencies()
    {
        $asset_file = BETTER_PAYMENT_PATH . '/assets/blocks/controls/modules.min.asset.php';

        if (file_exists($asset_file)) {
            $asset = include $asset_file;
            return isset($asset['dependencies']) ? $asset['dependencies'] : ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-data'];
        }

        return ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-data'];
    }

    /**
     * Register scripts and styles
     *
     * @return void
     * @since 0.0.1
     */
    public function register_assets()
    {
        if ( $this->registered ) {
            return;
        }
        $this->registered = true;

        $scripts = $this->get_scripts();
        $styles  = $this->get_styles();

        foreach ($scripts as $handle => $script) {
            $version = isset($script['version']) ? $script['version'] : false;
            $deps = isset($script['deps']) ? $script['deps'] : false;

            wp_register_script($handle, $script['src'], $deps, $version, true);
        }

        foreach ($styles as $handle => $style) {
            $version = isset($style['version']) ? $style['version'] : false;
            $deps = isset($style['deps']) ? $style['deps'] : false;
            $admin_enqueue = isset($style['admin_enqueue']) ? $style['admin_enqueue'] : false;

            wp_register_style($handle, $style['src'], $deps, $version);

            if ($admin_enqueue && is_admin()) {
                wp_enqueue_style($handle, $style['src'], $deps, $version);
            }
        }


        wp_localize_script('better-payment', 'betterPayment', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('better-payment'),
            'confirm' => __('Are you sure?', 'better-payment'),
            'error' => __('Something went wrong', 'better-payment'),
            'custom_texts' => [
                'redirecting' => __('Redirecting', 'better-payment'),
                // 'field' => __('Field', 'better-payment'),
                // 'required' => __('Required', 'better-payment'),
                'field_is_required' => __('field is required', 'better-payment'),
                'business_email_is_required' => __('Business Email is required', 'better-payment'),
                'payment_amount_field_is_required' => __('Payment Amount field is required', 'better-payment'),
                'minimum_amount_is_one' => __('Minimum amount is 1', 'better-payment'),
                'something_went_wrong' => __('Something went wrong', 'better-payment'),
                'paypal_payment_timeout' => __('Payment verification timed out. Please contact support if your payment was charged.', 'better-payment'),
            ],
            'currency_symbols' => $this->get_currency_symbols_list(),
        ]);

        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $hook = $screen && isset($screen->id) ? $screen->id : null;
            if ($hook && $hook == 'elementor_page_elementor-element-manager') {
                wp_enqueue_style('bp-icon-admin', BETTER_PAYMENT_ASSETS . '/icon/style.min.css', array(), BETTER_PAYMENT_VERSION);
            }
        }
    }
}
