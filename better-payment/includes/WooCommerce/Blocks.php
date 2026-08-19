<?php

namespace Better_Payment\Lite\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Blocks checkout support for the Better Payment (Stripe) gateway.
 *
 * The gateway is a redirect method with no fields, so the client side is a
 * hand-authored registration script (assets/js/woocommerce-blocks.js — no
 * build step) that surfaces the title/description supplied here.
 *
 * Instantiated only from Loader::register_blocks_support(), which guards on
 * AbstractPaymentMethodType existing.
 *
 * @since 2.4.0
 */
class Blocks extends AbstractPaymentMethodType {

    /**
     * Payment method name (matches the gateway id).
     *
     * @var string
     */
    protected $name = Gateway::GATEWAY_ID;

    /**
     * The gateway instance, resolved lazily from the registered gateways.
     *
     * @var Gateway|null
     */
    private $gateway = null;

    /**
     * Initialize settings.
     *
     * @return void
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_' . Gateway::GATEWAY_ID . '_settings', array() );
    }

    /**
     * Resolve the registered gateway instance.
     *
     * @return Gateway|null
     */
    private function get_gateway() {
        if ( null === $this->gateway && function_exists( 'WC' ) ) {
            // payment_gateways() is a method on the WooCommerce class —
            // property access here silently resolves to null and would
            // report the method inactive on every blocks checkout.
            $gateways = WC()->payment_gateways()->payment_gateways();

            if ( isset( $gateways[ Gateway::GATEWAY_ID ] ) ) {
                $this->gateway = $gateways[ Gateway::GATEWAY_ID ];
            }
        }

        return $this->gateway;
    }

    /**
     * Whether the payment method is usable at checkout.
     *
     * @return bool
     */
    public function is_active() {
        $gateway = $this->get_gateway();

        return $gateway ? $gateway->is_available() : false;
    }

    /**
     * Register and return the client registration script.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles() {
        wp_register_script(
            'better-payment-wc-blocks',
            BETTER_PAYMENT_ASSETS . '/js/woocommerce-blocks.js',
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
            BETTER_PAYMENT_VERSION,
            true
        );

        return array( 'better-payment-wc-blocks' );
    }

    /**
     * Data exposed to the client via wc.wcSettings.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting( 'title', __( 'Better Payment', 'better-payment' ) ),
            'description' => $this->get_setting( 'description', __( 'Pay securely using Stripe via Better Payment.', 'better-payment' ) ),
            'supports'    => array( 'products' ),
        );
    }
}
