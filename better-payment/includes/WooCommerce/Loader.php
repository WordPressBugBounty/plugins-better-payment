<?php

namespace Better_Payment\Lite\WooCommerce;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Wires the WooCommerce integration module.
 *
 * Called from the bootstrap only when `class_exists( 'WooCommerce' )` — on
 * sites without WooCommerce none of this module loads. Dependency direction
 * is strictly WooCommerce module → Better Payment core; core never
 * references this namespace.
 *
 * @since 2.4.0
 */
class Loader {

    /**
     * Register the module's hooks.
     *
     * @return void
     */
    public static function register() {
        // HPOS (custom order tables) compatibility — all order access in this
        // module goes through the WC CRUD API.
        add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_compatibility' ) );

        // Gateway registration (classic checkout + settings screen).
        add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );

        // Blocks checkout support.
        add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_blocks_support' ) );

        // Return-URL verification + payment_confirmed consumer.
        OrderHandler::register();

        // Recurring payments (Better Payment subscriptions).
        Subscriptions::register();

        // "Subscription Total" summary on the cart + checkout totals table.
        CartTotals::register();

        // My Account > Subscriptions tab (customer list + single view).
        MyAccount::register();

        // Subscription lifecycle customer emails (cancelled + completed).
        Emails::register();
    }

    /**
     * Add the gateway to WooCommerce's list.
     *
     * @param array $gateways Registered gateway class names/instances.
     * @return array
     */
    public static function register_gateway( $gateways ) {
        $gateways[] = Gateway::class;

        return $gateways;
    }

    /**
     * Register the blocks checkout payment method integration.
     *
     * @return void
     */
    public static function register_blocks_support() {
        if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            static function ( $registry ) {
                $registry->register( new Blocks() );
            }
        );
    }

    /**
     * Declare HPOS compatibility.
     *
     * @return void
     */
    public static function declare_hpos_compatibility() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                BETTER_PAYMENT_FILE,
                true
            );
        }
    }
}
