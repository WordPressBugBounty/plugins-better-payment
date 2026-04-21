<?php

namespace Better_Payment\Lite;

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
            // Removed: better-payment-babel-bundle - not used and unnecessary
            'better-payment-controls' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/controls/modules.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/controls/modules.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/controls/modules.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => array_merge(
                    $this->get_controls_dependencies(),
                    ['wp-polyfill', 'better-payment-block-localize','better-payment-vendor-bundle']),
            ],
            'better-payment-controls-frontend' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/blocks/controls/frontend.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/blocks/controls/frontend.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/blocks/controls/frontend.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => []
            ],
            'better-payment-common-script' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/common.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/common.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/common.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery', 'wp-util']
            ],
            'better-payment-stripe' => [
                'src'     => 'https://js.stripe.com/v3/',
            ],
            'better-payment' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/js/better-payment.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/js/better-payment.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/js/better-payment.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery', 'better-payment-stripe', 'toastr-js']
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
            'toastr-js-admin' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/toastr/js/toastr.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/vendor/toastr/js/toastr.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/vendor/toastr/js/toastr.min.js') : BETTER_PAYMENT_VERSION,
                'deps'    => ['jquery']
            ],
            'sweetalert2-js' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/sweetalert2/js/sweetalert2.min.js',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/vendor/sweetalert2/js/sweetalert2.min.js') ? filemtime(BETTER_PAYMENT_PATH . '/assets/vendor/sweetalert2/js/sweetalert2.min.js') : BETTER_PAYMENT_VERSION,
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
            'bp-settings-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/style.min.css',
            ],
            'bp-icon-admin' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/icon/style.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/icon/style.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'toastr-css-admin' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/toastr/css/toastr.min.css',
            ],
            'sweetalert2-css' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/vendor/sweetalert2/css/sweetalert2.min.css',
            ],
            'better-payment-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/frontend.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/frontend.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/frontend.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'better-payment-admin-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/admin.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/admin.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/admin.min.css') : BETTER_PAYMENT_VERSION,
            ],
            'bp-font-awesome' => [
                'src'     => file_exists( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    ? BETTER_PAYMENT_ASSETS . '/vendor/fontawesome/css/all.min.css'
                    : 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css',
                'version' => file_exists( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    ? filemtime( BETTER_PAYMENT_PATH . '/assets/vendor/fontawesome/css/all.min.css' )
                    : '6',
            ],
            'fundraising-campaign-style' => [
                'src'     => BETTER_PAYMENT_ASSETS . '/css/fundraising-campaign.min.css',
                'version' => file_exists(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign.min.css') ? filemtime(BETTER_PAYMENT_PATH . '/assets/css/fundraising-campaign.min.css') : BETTER_PAYMENT_VERSION,
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
            ],
            'currency_symbols' => $this->get_currency_symbols_list(),
        ]);

        wp_localize_script('bp-admin-settings', 'betterPaymentObj', array(
            'nonce'  => wp_create_nonce('better_payment_admin_nonce'),
            'alerts' => [
                'confirm' => __('Are you sure?', 'better-payment'),
                'confirm_description' => __("You won't be able to revert this!", 'better-payment'),
                'yes' => __('Yes, delete it!', 'better-payment'),
                'no' => __('No, cancel!', 'better-payment'),
            ],
            'messages' => [
                'success' => __('Changes saved successfully!', 'better-payment'),
                'error' => __('Opps! something went wrong!', 'better-payment'),
                'no_action_taken' => __('No action taken!', 'better-payment'),
            ]
        ));

        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $hook = $screen && isset($screen->id) ? $screen->id : null;
            if ($hook && $hook == 'elementor_page_elementor-element-manager') {
                wp_enqueue_style('bp-icon-admin', BETTER_PAYMENT_ASSETS . '/icon/style.min.css', array(), BETTER_PAYMENT_VERSION);
            }
        }     
    }
}
