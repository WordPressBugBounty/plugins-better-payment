<?php

namespace Better_Payment\Lite;

use Better_Payment\Lite\API\AdminAPI;
use Better_Payment\Lite\API\UserAPI;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Class
 *
 * @since 0.0.1
 */
class API extends Controller {

    /**
     * Initialize the class
     *
     * @since 0.0.1
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_api' ] );

        new AdminAPI();
        new UserAPI();
    }

    /**
     * Register the API
     *
     * @return void
     * @since 0.0.1
     */
    public function register_api() {
        //
    }
}
