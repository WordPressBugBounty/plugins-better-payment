<?php

namespace Better_Payment\Lite\Admin;

use Better_Payment\Lite\Classes\Plugin_Usage_Tracker;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly.

/**
 * Setup Wizard
 *
 * The wizard UI is the React quick-setup app (src/admin/quick-setup), mounted by
 * views/better-payment-quick-setup.php. The PHP-rendered steps, footer and their
 * jQuery script (src/js/setup-wizard.js) predated it and were no longer called,
 * so they have been removed.
 *
 * @since 0.0.2
 */
class Setup_Wizard
{
    use TraitsHelper;

    protected $file_version;

    /**
     * Setup Wizard constructor.
     *
     * @since 0.0.2
     */
    public function __construct()
    {
		$this->file_version = defined('WP_DEBUG') && WP_DEBUG ? time() : BETTER_PAYMENT_VERSION;
        add_action('admin_enqueue_scripts', array($this, 'setup_wizard_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_setup_wizard_data', [$this, 'save_setup_wizard_data']);
        add_action( 'wp_ajax_bp_quick_setup_save_tracking', [ $this, 'bp_quick_setup_save_tracking' ] );
        add_action('in_admin_header', [$this, 'remove_notice'], 1000);
    }

    /**
     * Remove all notice in setup wizard page
     *
     * @since 0.0.2
     */
    public function remove_notice()
    {
        if (isset($_GET['page']) && $_GET['page'] == 'better-payment-setup-wizard') {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }

    /**
     * setup_wizard_scripts
     * @param $hook
     * @return array
     *
     * @since 0.0.2
     */
    public function setup_wizard_scripts($hook)
    {
        if (isset($hook) && $hook == 'admin_page_better-payment-setup-wizard') {
            // Determine the correct file extension based on debug mode
            $js_ext = file_exists(BETTER_PAYMENT_PATH . '/assets/admin/quick-setup/quick-setup.min.js') ? '.min.js' : '.js';
            $css_ext = file_exists(BETTER_PAYMENT_PATH . '/assets/admin/quick-setup/quick-setup.min.css') ? '.min.css' : '.css';

            // Enqueue React Quick Setup CSS
            wp_enqueue_style(
                'bp-quick-setup-css',
                BETTER_PAYMENT_ASSETS . '/admin/quick-setup/quick-setup' . $css_ext,
                array(),
                $this->file_version
            );

            // Enqueue React Quick Setup JS with WordPress dependencies
            wp_enqueue_script(
                'bp-quick-setup-js',
                BETTER_PAYMENT_ASSETS . '/admin/quick-setup/quick-setup' . $js_ext,
                // wp-hooks: the PayPal step's refund-credentials slot, which Pro fills (utils/proHandoff.js).
                array('wp-element', 'wp-i18n', 'wp-hooks'),
                $this->file_version,
                true
            );

            // Localize script with the data the React wizard reads.
            wp_localize_script('bp-quick-setup-js', 'betterPaymentObjWizard', array(
                'admin_nonce'  => wp_create_nonce('better_payment_admin_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'settings' => DB::get_settings(),
                'is_tracking' => get_option('better_payment_settings_opt_in') === 'yes' ? 1 : 0,
                'currencies' => $this->get_currency_list(),
                'is_pro' => defined('BETTER_PAYMENT_PRO_VERSION') && BETTER_PAYMENT_PRO_VERSION ? true : false,
                // Arrives as '1' / '' (wp_localize_script casts scalars); read it with parseProEnabled().
                'pro_enabled' => \Better_Payment\Lite\Classes\ProCompatibility::pro_enabled(),
            ));
        }
        return [];
    }

    /**
     * Create admin menu for setup wizard
     *
     * @since 0.0.2
     */
    public function admin_menu()
    {

        add_submenu_page(
            "admin.php?page=better-payment-setup-wizard",
            esc_html__('Better Payment ', 'better-payment'),
            esc_html__('Better Payment ', 'better-payment'),
            'manage_options',
            'better-payment-setup-wizard',
            [$this, 'render_wizard']
        );
    }

    /**
     * render_wizard
     *
     * @since 0.0.1
     */
    public function render_wizard()
    {
    ?>
        <div class="better-payment-setup-wizard-wrap">
            <?php
            include BETTER_PAYMENT_ADMIN_VIEWS_PATH . '/better-payment-quick-setup.php';
            ?>
        </div>
    <?php
    }

    public function bp_quick_setup_save_tracking() {
        // Verify the Nonce
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'better_payment_admin_nonce')) {
			wp_send_json_error( __( 'Nonce verification failed.', 'better-payment' ) );
			return;
		}

        if (!current_user_can('manage_options')) {
            wp_send_json_error( __( 'Insufficient permissions.', 'better-payment' ) );
            return;
		}

        $context = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'proceed';

        if ( isset( $_POST[ 'is_tracking' ] ) && $_POST[ 'is_tracking' ] === 'true' ) {
            /**
             * `better_payment_settings_opt_in` is NOT the tracking gate — that is
             * `wpins_allow_tracking`, written by wpins_process() below. This option
             * is wizard UI state: it is localized as `is_tracking` (see
             * setup_wizard_scripts()) and makes QuickSetupWizard open on the
             * Settings step with back-navigation to Start disabled.
             *
             * So the Skip path deliberately does not write it. Skip must leave the
             * wizard behaving exactly as it does today — Start screen shown, Back
             * still available — while still turning tracking on.
             *
             * @since 2.3.2
             */
            if ( 'skip' !== $context ) {
                update_option('better_payment_settings_opt_in', 'yes');
            }

            self::wpins_process();
            wp_send_json_success( __( 'Tracking data saved successfully.', 'better-payment' ) );
        } else {
            wp_send_json_error( __( 'Invalid tracking parameter.', 'better-payment' ) );
        }
    }

    /**
     * Save setup wizard data
     *
     * @since 0.0.1
     */
    public static function save_setup_wizard_data()
	{
		/**
		 * Verify the Nonce
		 */
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'better_payment_admin_nonce')) {
			return;
		}

        if (!current_user_can('manage_options')) {
            return;
		}

        try{
            Settings::save_default_settings(1);
            Settings::save_settings(json_decode(stripslashes($_POST['form_data']), true));

            update_option( 'better_payment_setup_wizard', 'complete' );

            // if (!did_action('elementor/loaded')) {
            //     wp_send_json_success( [ 'redirect_url' => admin_url() ] );
            // }else {
                wp_send_json_success( [ 'redirect_url' => admin_url( 'admin.php?page=better-payment-admin&tab=dashboard' ) ] );
            // }
        }catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }

		die;
	}

    /**
     * WPIns process
     *
     * The single tracking-activation routine. Every path that turns usage
     * tracking on goes through here — the wizard's "Proceed To Next Step" and
     * "Skip It" buttons, and the automatic opt-in for fresh installs in
     * Traits\Helper::maybe_auto_enable_usage_tracking().
     *
     * @param bool $force_send Send the payload immediately. True for the wizard
     *                         buttons, which run inside an AJAX request. Pass
     *                         FALSE from anything that runs during a page render:
     *                         send_data() posts with `blocking => true` and a 30s
     *                         timeout, so a forced send there would stall the
     *                         response. Consent alone is enough — the caller's
     *                         Plugin_Usage_Tracker::init() schedules the daily
     *                         event and the first run sends.
     *
     * @return void
     * @since 0.0.1
     */
    public static function wpins_process( $force_send = true ){
        $plugin_name = basename( BETTER_PAYMENT_FILE, '.php' );

        if ( class_exists( '\Better_Payment\Lite\Classes\Plugin_Usage_Tracker' ) ){
            $tracker = Plugin_Usage_Tracker::get_instance( BETTER_PAYMENT_FILE, [
                'opt_in'       => true,
                'goodbye_form' => true,
                'item_id'      => '64e1f724b5e14edb343e'
            ] );
            $tracker->set_is_tracking_allowed( true );

            if ( $force_send ) {
                $tracker->do_tracking( true );
            }
        }
    }

    /**
     * Redirect
     *
     * @since 0.0.1
     */
    public static function redirect()
    {
        update_option('better_payment_setup_wizard', 'init');
        wp_redirect(admin_url('admin.php?page=better-payment-setup-wizard'));
    }
}
