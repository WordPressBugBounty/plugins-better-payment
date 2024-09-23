<?php

namespace Better_Payment\Lite\Admin\Elementor;

use Better_Payment\Lite\Admin\Elementor\Form_Actions\Payment_Amount_Field;
use Better_Payment\Lite\Admin\Elementor\Form_Actions\Payment_Amount_Integration;
use Better_Payment\Lite\Admin\Elementor\Form_Actions\Paypal_Integration;
use Better_Payment\Lite\Admin\Elementor\Form_Actions\Stripe_Integration;
use Better_Payment\Lite\Admin\Elementor\Form_Actions\Paystack_Integration;
use Better_Payment\Lite\Classes\Handler;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The elementor integration class
 *
 * @since 0.0.1
 */
class EL_Integration {

    private $payment_amount = 'payment_amount';

    /**
     * Init
     *
     * @since 0.0.1
     */
    public function init() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
        add_action( 'elementor/widgets/register', [ $this, 'elementor_form_integration' ], 10 );
        add_action( 'elementor-pro/forms/pre_render', [ $this, 'elementor_pro_form_response' ], 10, 2 );
    }

    /**
     * Register widget
     *
     * @since 0.0.1
     */
    public function register_widget( $widgets_manager ) {
        $widgets_manager->register_widget_type( new Better_Payment_Widget() );
        $widgets_manager->register_widget_type( new User_Dashboard() );
    }

    /**
     * Elementor form integration
     *
     * @since 0.0.1
     */
    public function elementor_form_integration() {
        if(!defined('ELEMENTOR_PRO_VERSION')){
            return false;
        }

        wp_enqueue_style( 'better-payment-common-style' );

        \ElementorPro\Modules\Forms\Module::instance()->add_form_action( 'better-payment', new Payment_Amount_Integration() ); 
        \ElementorPro\Modules\Forms\Module::instance()->add_form_action( 'PayPal', new Paypal_Integration() );
        \ElementorPro\Modules\Forms\Module::instance()->add_form_action( 'Stripe', new Stripe_Integration() );
        \ElementorPro\Modules\Forms\Module::instance()->add_form_action( 'Paystack', new Paystack_Integration() );
        \ElementorPro\Modules\Forms\Module::instance()->add_form_field_type( 'payment_amount', new Payment_Amount_Field() );
    }

    /**
     * Elementor pro form response
     *
     * @since 0.0.1
     */
    public function elementor_pro_form_response( $settings, $obj ) {
        wp_enqueue_style( 'better-payment-el' );
        $response = Handler::manage_response( $settings, $obj->get_id() );
        if ( $response ) {
			$obj->add_render_attribute( 'form', 'style', 'display:none' );
            return false;
        }
    }

}

