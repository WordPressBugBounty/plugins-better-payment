<?php

namespace Better_Payment\Lite\Admin;

use Better_Payment\Lite\Controller;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Front-end legacy script loader.
 *
 * The backend half of this class enqueued the old PHP admin's scripts and styles
 * on the `better-payment-settings` / `-transactions` / `-analytics` pages. Those
 * pages no longer exist (the admin is React — see ReactAdmin), so only the
 * front-end enqueue remains: the user-dashboard templates still rely on
 * admin.min.js for their copy-to-clipboard buttons. The jQuery UI datepicker is
 * Pro's (analytics report); its scripts declare that dependency themselves.
 *
 * @since 0.0.1
 */
class Menu extends Controller{

    /**
     * Initialize the class
     *
     * @since 0.0.1
     */
    function __construct( ) {

    }

    /**
     * Register the front-end enqueue.
     *
     * @param string $end Only 'frontend' registers anything; the parameter is kept
     *                    for existing callers (Frontend passes it).
     * @since 0.0.1
     */
    public function init($end = 'frontend'){
		if('frontend' === $end){
			add_action('wp_enqueue_scripts', array($this, 'handle_scripts_and_styles_frontend'));
		}
    }

	/**
	 * Enqueue scripts and styles
	 *
	 * @since 0.0.1
	 */
	public function handle_scripts_and_styles_frontend() {
		wp_enqueue_style('toastr-css');

		wp_enqueue_script('toastr-js');
		wp_enqueue_script('better-payment-script');
        wp_enqueue_script('bp-admin-settings');
        wp_enqueue_script('better-payment-common-script');
	}
}
