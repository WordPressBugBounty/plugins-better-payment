<?php

namespace Better_Payment\Lite\Admin;

use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * React Admin handler class
 * 
 * @since 1.5.0
 */
class ReactAdmin extends Controller
{
    use TraitsHelper;
    
    protected $pro_enabled;
    protected $page_slug_prefix;
    protected $file_version;
    
    /**
     * Constructor
     * 
     * @since 1.5.0
     */
    public function __construct($page_slug_prefix = 'better-payment')
    {
        $this->page_slug_prefix = $page_slug_prefix;
        $this->file_version = defined('WP_DEBUG') && WP_DEBUG ? time() : BETTER_PAYMENT_VERSION;
        $this->pro_enabled = apply_filters('better_payment/pro_enabled', false);
        
        add_action('init', [$this, 'handle_old_page_redirect']);
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_enqueue_scripts', [$this, 'customize_admin_footer']);

        //EL Editor Style
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'editor_enqueue_scripts']);
    }

    /**
     * Handle old page redirect
     * 
     * @since 2.0.0
     */
    public function handle_old_page_redirect()
    {
        if (isset($_GET['page']) && trim(sanitize_text_field($_GET['page'])) === 'better-payment-settings') {
            wp_safe_redirect(admin_url('admin.php?page=' . $this->page_slug_prefix . '-admin&tab=dashboard'));
            exit;
        }
    }

    /**
	 * Enqueue editor scripts
	 * 
	 * @since 0.0.1
	 */
    public function editor_enqueue_scripts()
    {
        // bp icon font
        wp_enqueue_style(
            'bp-icon-admin-editor',
            BETTER_PAYMENT_ASSETS . '/icon/style.min.css',
            false
        );
    }
    
    /**
     * Register admin menu for React SPA
     * 
     * @since 1.5.0
     */
    public function register_menu()
    {
        add_menu_page(
            __('Better Payment', 'better-payment'),
            __('Better Payment', 'better-payment'),
            'manage_options',
            $this->page_slug_prefix . '-admin',
            [$this, 'render_react_admin_page'],
            BETTER_PAYMENT_ASSETS . '/img/better-payment-icon-white-small.png',
            64
        );
        
        $submenu_page_list = apply_filters('better_payment/admin/get_submenu_page_list', array(
            $this->page_slug_prefix . '-admin&tab=dashboard'   => array(
                'title'      => __('Dashboard', 'better-payment'),
                'capability' => 'manage_options',
                'callback'   => [$this, 'render_react_admin_page'],
            ),
            $this->page_slug_prefix . '-admin&tab=transactions'   => array(
                'title'      => __('Transactions', 'better-payment'),
                'capability' => 'manage_options',
                'callback'   => [$this, 'render_react_admin_page'],
            ),
            $this->page_slug_prefix . '-admin&tab=analytics'   => array(
                'title'      => __('Analytics', 'better-payment'),
                'capability' => 'manage_options',
                'callback'   => [$this, 'render_react_admin_page'],
            ),
            $this->page_slug_prefix . '-admin&tab=settings'   => array(
                'title'      => __('Settings', 'better-payment'),
                'capability' => 'manage_options',
                'callback'   => [$this, 'render_react_admin_page'],
            ),
        ), $this->page_slug_prefix );
        
        foreach( $submenu_page_list as $slug => $setting ) {
            add_submenu_page(
                $this->page_slug_prefix . '-admin',
                $setting['title'],
                $setting['title'],
                $setting['capability'],
                $slug,
                $setting['callback']
            );
        }
        
        remove_submenu_page($this->page_slug_prefix . '-admin', $this->page_slug_prefix . '-admin');
    }
    
    /**
     * Render the React admin page
     *
     * @since 1.5.0
     */
    public function render_react_admin_page()
    {
        $initial_data = $this->get_initial_admin_data();
        
        add_filter('admin_body_class', function($classes) {
            return $classes . ' better-payment-admin-page';
        });
        
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        
        ?>
        <div class="wrap">
            <div id="better-payment-admin-root">
                <!-- <div class="bp-loading-placeholder">
                    <div class="bp-loading-spinner"></div>
                    <p><?php //_e('Loading Better Payment Admin...', 'better-payment'); ?></p>
                </div> -->
            </div>
        </div>
        
        <script type="text/javascript">
            window.betterPaymentAdmin = <?php echo wp_json_encode($initial_data); ?>;
        </script>
        
        <style>
            .bp-loading-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 400px;
                gap: 16px;
                color: #646970;
            }

            .bp-loading-spinner {
                width: 32px;
                height: 32px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #0073aa;
                border-radius: 50%;
                animation: bp-spin 1s linear infinite;
            }

            @keyframes bp-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .better-payment-admin {
                background: #f0f0f1;
                min-height: calc(100vh - 32px);
                margin: -20px -20px -20px -2px;
                padding: 0;
            }

            .wrap .better-payment-admin {
                margin: -20px -20px -20px -2px;
            }

            .better-payment-admin-page .notice,
            .better-payment-admin-page .error,
            .better-payment-admin-page .updated {
                display: none !important;
            }

            .better-payment-admin-page #adminmenumain {
                display: block !important;
            }

            .better-payment-admin-page #wpadminbar {
                display: block !important;
            }

            .better-payment-admin-page #wpfooter {
                display: block !important;
            }

            @media (max-width: 960px) {
                .folded .better-payment-admin {
                    margin-left: -2px;
                }
            }

            @media (max-width: 782px) {
                .auto-fold .better-payment-admin {
                    margin-left: -2px;
                }
            }

            @media print {
                body {
                    transform: scale(1);
                    background: white !important;
                }

                body, body *, .wpcontent {
                    visibility: hidden !important;
                }

                .bp-invoice-body,
                .bp-invoice-body * {
                    visibility: visible !important;
                }

                /* Make modal fill the full page */
                .bp-invoice-body {
                    position: absolute !important;
                    left: 0;
                    top: 100;
                    width: 100% !important;
                    height: auto !important;
                }
                
                .bp-button-wrapper {
                    display: none !important;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Get initial data for React admin app
     * 
     * @since 1.5.0
     * @return array Initial data
     */
    private function get_initial_admin_data()
    {
        $settings = DB::get_settings();
        
        return [
            'apiUrl' => rest_url('better-payment/v1/'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_rest'),
            'importNonce' => wp_create_nonce('better_payment_transaction_import_nonce'),
            'logoUrl' => BETTER_PAYMENT_ASSETS . '/img/better-payment-icon-white-small.png',
            'adminUrl' => admin_url('admin.php?page=' . $this->page_slug_prefix . '-admin'),
            'adminPostUrl' => admin_url('admin-post.php'),
            'settings' => $settings,
            'proEnabled' => $this->pro_enabled,
            'currentUser' => [
                'id' => get_current_user_id(),
                'name' => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email
            ],
            'currencies' => $this->get_currency_list(),
            'currencySymbol' => $this->get_currency_symbol( $settings['better_payment_settings_general_general_currency'] ),
        ];
    }
    
    /**
     * Customize admin footer text and version display
     *
     * @since 1.5.0
     */
    public function customize_admin_footer($hook)
    {
        // Only apply to Better Payment admin pages
        if (strpos($hook, $this->page_slug_prefix . '-admin') === false) {
            return;
        }

        add_filter('admin_footer_text', [$this, 'get_footer_text']);
        add_filter('update_footer', [$this, 'get_footer_version']);
    }

    /**
     * Get custom footer text
     *
     * @since 1.5.0
     * @return string
     */
    public function get_footer_text()
    {
        return __('Thank you for using <a href="https://betterpayment.co/" target="_blank">Better Payment</a>', 'better-payment');
    }

    /**
     * Get footer version text
     *
     * @since 1.5.0
     * @return string
     */
    public function get_footer_version()
    {
        $free_version = BETTER_PAYMENT_VERSION;

        // Check if Pro version is installed and active
        if ($this->pro_enabled && defined('BETTER_PAYMENT_PRO_VERSION')) {
            $pro_version = BETTER_PAYMENT_PRO_VERSION;
            return sprintf(
                __('<span class="bp-footer-version-divider">Free Version <span class="bp-footer-version">%s</span></span> <span>Pro Version <span class="bp-footer-version">%s</span></span>', 'better-payment'),
                $free_version,
                $pro_version
            );
        }

        return sprintf(
            __('<span class="bp-free-version"><span>Free Version</span> <span class="bp-footer-version">%s</span> </span>', 'better-payment'),
            $free_version
        );
    }

    /**
     * Enqueue admin assets for React app
     * 
     * @since 1.5.0
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, $this->page_slug_prefix . '-admin') === false) {
            return;
        }
        
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-api-fetch');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-i18n');
        
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-hooks');
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('wp-api-fetch');
        wp_enqueue_script('react');
        wp_enqueue_script('react-dom');
        
        $admin_js_file = BETTER_PAYMENT_ASSETS . '/admin/admin.min.js';
        $admin_css_file = BETTER_PAYMENT_ASSETS . '/admin/admin.min.css';
        
        if (!file_exists(BETTER_PAYMENT_PATH . '/assets/admin/admin.min.js')) {
            $admin_js_file = BETTER_PAYMENT_ASSETS . '/admin/admin.js';
        }
        
        if (!file_exists(BETTER_PAYMENT_PATH . '/assets/admin/admin.min.css')) {
            $admin_css_file = BETTER_PAYMENT_ASSETS . '/admin/admin.css';
        }
        
        wp_enqueue_script(
            'better-payment-react-admin',
            $admin_js_file,
            ['wp-element', 'wp-hooks', 'wp-i18n', 'wp-api-fetch', 'react', 'react-dom'],
            $this->file_version,
            true
        );
        
        wp_enqueue_style(
            'better-payment-react-admin',
            $admin_css_file,
            [],
            $this->file_version
        );
        
        wp_add_inline_script(
            'better-payment-react-admin',
            'wp.apiFetch.use(wp.apiFetch.createNonceMiddleware("' . wp_create_nonce('wp_rest') . '"));',
            'before'
        );
        $page_slug = $this->page_slug_prefix . '-admin';
        $admin_base_url = admin_url('admin.php?page=' . $this->page_slug_prefix . '-admin');
        $inline_spa_nav = "(function(){\n\n  var PAGE_SLUG = '" . esc_js($page_slug) . "';\n  var BASE_URL = '" . esc_url($admin_base_url) . "';\n\n  function getTabFromUrl(){\n    var params = new URLSearchParams(window.location.search);\n    return params.get('tab') || 'dashboard';\n  }\n\n  function buildUrl(tab){\n    var url = new URL(BASE_URL, window.location.origin);\n    url.searchParams.set('tab', tab);\n    return url.toString();\n  }\n\n  function setActiveSubmenu(tab){\n    try {\n      var top = document.getElementById('toplevel_page_'+PAGE_SLUG);\n      if(!top) return;\n      var submenu = top.querySelector('.wp-submenu');\n      if(!submenu) return;\n      // remove current from all\n      submenu.querySelectorAll('li').forEach(function(li){ li.classList.remove('current'); });\n      // match link by tab param\n      var target = Array.prototype.find.call(submenu.querySelectorAll('a'), function(a){ return a.href && a.href.indexOf('page='+PAGE_SLUG) !== -1 && a.href.indexOf('tab='+tab) !== -1; });\n      if(target && target.parentElement){ target.parentElement.classList.add('current'); }\n      // ensure top-level marked as current submenu\n      top.classList.add('wp-has-current-submenu');\n      top.classList.add('current');\n    } catch(e){}\n  }\n\n  // Global navigate helper used by WP menu interceptors and can be used by React too\n  window.betterPaymentNavigate = function(tab, method){\n    if(!tab){ tab = 'dashboard'; }\n    var m = method === 'replace' ? 'replaceState' : 'pushState';\n    var url = buildUrl(tab);\n    try { window.history[m]({}, '', url); } catch(e){}\n    setActiveSubmenu(tab);\n    window.dispatchEvent(new CustomEvent('bp:navigate', { detail: { tab: tab } }));\n  };\n\n  // Intercept WP submenu clicks under our menu only\n  function clickHandler(e){\n    var link = e.target.closest('#toplevel_page_'+PAGE_SLUG+' .wp-submenu a');\n    if(!link) return;\n    var href = link.getAttribute('href') || '';\n    if(href.indexOf('page='+PAGE_SLUG) === -1) return;\n    e.preventDefault();\n    var url = new URL(href, window.location.origin);\n    var tab = url.searchParams.get('tab') || 'dashboard';\n    window.betterPaymentNavigate(tab, 'push');\n  }\n  document.addEventListener('click', clickHandler, true);\n\n  // Reflect browser back/forward\n  window.addEventListener('popstate', function(){ setActiveSubmenu(getTabFromUrl()); });\n\n  // Reflect initial state on load\n  document.addEventListener('DOMContentLoaded', function(){ setActiveSubmenu(getTabFromUrl()); });\n\n  // Also respond to React-driven page changes (optional hook)\n  window.addEventListener('bp:page-changed', function(e){ if(e && e.detail && e.detail.tab){ setActiveSubmenu(e.detail.tab); } });\n\n})();";
        wp_add_inline_script('better-payment-react-admin', $inline_spa_nav, 'after');
    }
}
