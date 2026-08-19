<?php

namespace Better_Payment\Lite\WooCommerce;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Handler;
use Better_Payment\Lite\Classes\StripeService;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Better Payment (Stripe) — WooCommerce payment gateway.
 *
 * A redirect gateway over the Better Payment payment engine. It owns only
 * the WooCommerce side: settings (enable/title/description — Stripe
 * credentials stay in Better Payment → Settings → Stripe), order mapping,
 * and the redirect. Payment creation, verification, transactions, hooks and
 * the payment lifecycle are the engine's (see OrderHandler + Classes\*).
 *
 * Only loaded when WooCommerce is active (see Loader::register()).
 *
 * @since 2.4.0
 */
class Gateway extends \WC_Payment_Gateway {

    /**
     * Gateway id.
     */
    const GATEWAY_ID = 'better_payment_stripe';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id                 = self::GATEWAY_ID;
        $this->has_fields         = false;
        $this->method_title       = __( 'Better Payment (Stripe)', 'better-payment' );
        $this->method_description = __( 'Accept payments through Better Payment\'s existing Stripe integration. Stripe credentials are managed in Better Payment → Settings → Stripe.', 'better-payment' );
        $this->supports           = array( 'products' );
        $this->icon               = '';

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            function () {
                $this->process_admin_options();
            }
        );
        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
    }

    /**
     * Minimal settings — deliberately no Stripe credentials here. The
     * gateway consumes Better Payment's existing Stripe configuration.
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'     => array(
                'title'   => __( 'Enable/Disable', 'better-payment' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Better Payment (Stripe)', 'better-payment' ),
                'default' => 'no',
            ),
            'title'       => array(
                'title'       => __( 'Title', 'better-payment' ),
                'type'        => 'text',
                'description' => __( 'The payment method title customers see at checkout.', 'better-payment' ),
                'default'     => __( 'Better Payment', 'better-payment' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'better-payment' ),
                'type'        => 'textarea',
                'description' => __( 'The payment method description customers see at checkout.', 'better-payment' ),
                'default'     => __( 'Pay securely using Stripe via Better Payment.', 'better-payment' ),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * URL of the Better Payment Stripe settings screen.
     *
     * @return string
     */
    public static function bp_stripe_settings_url() {
        return admin_url( 'admin.php?page=better-payment-admin&tab=settings&section=stripe' );
    }

    /**
     * Settings screen: configuration notice + the standard options table.
     *
     * @return void
     */
    public function admin_options() {
        ?>
        <h2><?php echo esc_html( $this->get_method_title() ); ?></h2>
        <p><?php echo esc_html( $this->get_method_description() ); ?></p>
        <div class="notice notice-info inline" style="margin: 12px 0; padding: 12px;">
            <p style="margin: 0 0 8px;">
                <strong><?php esc_html_e( 'This gateway uses Better Payment\'s existing Stripe configuration.', 'better-payment' ); ?></strong><br>
                <?php esc_html_e( 'Manage your Stripe account from Better Payment → Settings → Stripe.', 'better-payment' ); ?>
            </p>
            <?php if ( ! StripeService::is_configured() ) : ?>
                <p style="margin: 0 0 8px; color: #b32d2e;">
                    <?php esc_html_e( 'Stripe keys are not configured yet — the gateway will stay hidden at checkout until they are.', 'better-payment' ); ?>
                </p>
            <?php endif; ?>
            <a href="<?php echo esc_url( self::bp_stripe_settings_url() ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Open Better Payment Stripe Settings', 'better-payment' ); ?>
            </a>
        </div>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Available only when enabled AND Better Payment's Stripe keys exist
     * for the currently selected mode.
     *
     * @return bool
     */
    public function is_available() {
        return parent::is_available() && StripeService::is_configured();
    }

    /**
     * Create the Better Payment payment request for the order and redirect
     * to Stripe Checkout.
     *
     * @param int $order_id WooCommerce order id.
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wc_add_notice( __( 'Unable to process the order. Please try again.', 'better-payment' ), 'error' );
            return array( 'result' => 'failure' );
        }

        $keys = StripeService::get_global_keys();

        if ( empty( $keys['secret_key'] ) || empty( $keys['public_key'] ) ) {
            wc_add_notice( __( 'This payment method is not configured. Please choose another payment method.', 'better-payment' ), 'error' );
            OrderHandler::log( 'process_payment blocked for order #' . $order->get_id() . ': Stripe keys missing.', 'error' );
            return array( 'result' => 'failure' );
        }

        // A $0 subscription order is a free-trial checkout: there is nothing
        // to charge, but the subscription must still activate — and, when
        // automatic renewal is on, the card must still be collected (via a
        // Stripe setup-mode session) so the first payment can be charged
        // off-session when the trial ends.
        $is_subscription_order       = Subscriptions::order_contains_subscription( $order );
        $is_free_subscription_order  = $is_subscription_order && (float) $order->get_total() < 0.01;

        if ( $is_free_subscription_order && ! Subscriptions::should_save_payment_method() ) {
            // Site-wide manual renewal policy: no card is ever stored, so
            // there is nothing to send the customer to Stripe for. Complete
            // the free order and activate the subscription directly; the
            // first payment is invoiced when the trial ends (the same
            // manual-renewal path every renewal takes on this policy).
            $order->add_order_note( __( 'Better Payment: free trial checkout — nothing to charge. Renewal payments will be invoiced for manual payment.', 'better-payment' ) );
            $order->payment_complete();
            $order->save();

            if ( function_exists( 'wc_empty_cart' ) ) {
                wc_empty_cart();
            }

            OrderHandler::log( 'Free trial order #' . $order->get_id() . ' completed without a Stripe session (manual renewal policy).' );

            // The module's own completion contract — activates the
            // subscription (see Subscriptions::on_order_paid()).
            do_action( 'better_payment/woocommerce/payment_complete', $order, null );

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }

        // Same id scheme as every existing Better Payment Stripe surface.
        $bp_order_id = 'stripe_' . uniqid();

        $success_url = add_query_arg(
            array(
                'better_payment_stripe_status' => 'success',
                'better_payment_stripe_id'     => $bp_order_id,
            ),
            $this->get_return_url( $order )
        );

        $stripe_customer_id = '';

        if ( $is_free_subscription_order ) {
            // Setup-mode session: collect + save the card without charging.
            // The customer object is created up front — a setup session only
            // attaches the payment method to a customer the caller supplies.
            $customer = StripeService::create_customer(
                array(
                    'email'    => $order->get_billing_email(),
                    'name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                    'metadata' => array( 'wc_order_id' => (string) $order->get_id() ),
                ),
                $keys['secret_key']
            );

            if ( is_wp_error( $customer ) ) {
                wc_add_notice( __( 'The payment could not be started. Please try again or choose another payment method.', 'better-payment' ), 'error' );
                OrderHandler::log( 'Stripe customer creation failed for free trial order #' . $order->get_id() . ': ' . $customer->get_error_message(), 'error' );
                return array( 'result' => 'failure' );
            }

            $stripe_customer_id = (string) $customer->id;

            $session_request = Subscriptions::build_setup_session_request(
                array(
                    'bp_order_id' => $bp_order_id,
                    'wc_order_id' => $order->get_id(),
                    'success_url' => $success_url,
                    'cancel_url'  => $order->get_cancel_order_url_raw(),
                    'customer'    => $stripe_customer_id,
                )
            );
        } else {
            $session_request = OrderHandler::build_session_request(
                array(
                    'bp_order_id'    => $bp_order_id,
                    'wc_order_id'    => $order->get_id(),
                    'order_number'   => $order->get_order_number(),
                    'amount'         => (float) $order->get_total(),
                    'currency'       => $order->get_currency(),
                    'success_url'    => $success_url,
                    'cancel_url'     => $order->get_cancel_order_url_raw(),
                    'customer_email' => $order->get_billing_email(),
                    'customer_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                    'site_name'      => get_bloginfo( 'name' ),
                )
            );

            // Subscription carts additionally ask Stripe to keep the payment
            // method reusable for off-session renewals (see Subscriptions) —
            // but only while automatic renewal is enabled site-wide. With a
            // manual renewal policy the card would be stored without ever being
            // charged, so it is not stored at all; renewals are invoiced instead.
            if ( $is_subscription_order && Subscriptions::should_save_payment_method() ) {
                $session_request = Subscriptions::add_off_session_setup( $session_request );
            }
        }

        OrderHandler::log( 'Payment started for order #' . $order->get_id() . ' (' . $bp_order_id . ').' );

        $session = StripeService::create_checkout_session( $session_request, $keys['secret_key'] );

        if ( is_wp_error( $session ) ) {
            wc_add_notice( __( 'The payment could not be started. Please try again or choose another payment method.', 'better-payment' ), 'error' );
            $order->add_order_note(
                sprintf(
                    /* translators: %s: error message */
                    __( 'Better Payment: could not create the Stripe Checkout Session — %s', 'better-payment' ),
                    $session->get_error_message()
                )
            );
            OrderHandler::log( 'Checkout Session creation failed for order #' . $order->get_id() . ': ' . $session->get_error_message(), 'error' );
            return array( 'result' => 'failure' );
        }

        // Persist the transaction through the engine's own writer.
        $transaction_data = OrderHandler::build_transaction_data(
            array(
                'bp_order_id'    => $bp_order_id,
                'wc_order_id'    => $order->get_id(),
                'order_number'   => $order->get_order_number(),
                'amount'         => (float) $order->get_total(),
                'currency'       => $order->get_currency(),
                'customer_email' => $order->get_billing_email(),
                'customer_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
            ),
            $session
        );

        if ( $is_free_subscription_order ) {
            // A setup-mode session reports payment_status
            // 'no_payment_required' from the moment it is CREATED — before
            // the customer has entered any card. The row must start 'unpaid'
            // so the return-side verification
            // (OrderHandler::verify_setup_return()) is the single-shot flip.
            $transaction_data['status'] = 'unpaid';
        }

        $transaction_row_id = Handler::payment_create( $transaction_data );

        if ( ! $transaction_row_id ) {
            wc_add_notice( __( 'The payment could not be started. Please try again or choose another payment method.', 'better-payment' ), 'error' );
            OrderHandler::log( 'Transaction row insert failed for order #' . $order->get_id() . '.', 'error' );
            return array( 'result' => 'failure' );
        }

        // Integration metadata only — no new tables.
        $order->update_meta_data( '_bp_transaction_id', (string) $transaction_row_id );
        $order->update_meta_data( '_bp_payment_id', $bp_order_id );
        $order->update_meta_data( '_bp_gateway', 'stripe' );
        $order->update_meta_data( '_bp_payment_status', $is_free_subscription_order || empty( $session->payment_status ) ? 'unpaid' : sanitize_text_field( $session->payment_status ) );

        if ( '' !== $stripe_customer_id ) {
            // The customer id is known now; the payment method arrives with
            // the completed setup session (harvest_payment_method()).
            $order->update_meta_data( Subscriptions::CUSTOMER_META, sanitize_text_field( $stripe_customer_id ) );
        }
        $order->add_order_note(
            sprintf(
                /* translators: 1: Better Payment order id, 2: Stripe Checkout Session id */
                __( 'Better Payment: redirecting to Stripe Checkout. Payment ID: %1$s, Session: %2$s.', 'better-payment' ),
                $bp_order_id,
                sanitize_text_field( $session->id )
            )
        );
        $order->save();

        if ( function_exists( 'wc_empty_cart' ) ) {
            wc_empty_cart();
        }

        // Newer Stripe API versions return a hosted URL; the pinned legacy
        // version may not — fall back to the receipt page, which redirects
        // via Stripe.js exactly like every existing Better Payment surface.
        $redirect = ! empty( $session->url ) ? $session->url : $order->get_checkout_payment_url( true );

        OrderHandler::log( 'Redirecting order #' . $order->get_id() . ' to Stripe Checkout (' . ( ! empty( $session->url ) ? 'hosted url' : 'Stripe.js' ) . ').' );

        return array(
            'result'   => 'success',
            'redirect' => $redirect,
        );
    }

    /**
     * Receipt page — Stripe.js redirect fallback for sessions without a
     * hosted URL. Mirrors the redirectToCheckout mechanism the existing
     * surfaces use.
     *
     * @param int $order_id WooCommerce order id.
     * @return void
     */
    public function receipt_page( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order || ! $order->needs_payment() ) {
            return;
        }

        $row_id = (int) $order->get_meta( '_bp_transaction_id' );
        $row    = $row_id ? DB::get_transaction( $row_id ) : null;
        $keys   = StripeService::get_global_keys();

        if ( empty( $row->obj_id ) || empty( $keys['public_key'] ) ) {
            echo '<p>' . esc_html__( 'The payment session could not be found. Please try placing the order again.', 'better-payment' ) . '</p>';
            return;
        }

        if ( wp_script_is( 'better-payment-stripe', 'registered' ) ) {
            wp_enqueue_script( 'better-payment-stripe' );
        } else {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external SDK, unversioned by design (matches Assets.php).
            wp_enqueue_script( 'better-payment-stripe', 'https://js.stripe.com/v3/', array(), null, true );
        }

        $inline = sprintf(
            '(function(){if(typeof Stripe==="undefined"){return;}Stripe(%s).redirectToCheckout({sessionId:%s});})();',
            wp_json_encode( $keys['public_key'] ),
            wp_json_encode( $row->obj_id )
        );
        wp_add_inline_script( 'better-payment-stripe', $inline );

        echo '<p>' . esc_html__( 'Redirecting to secure Stripe Checkout…', 'better-payment' ) . '</p>';
        echo '<p><a href="' . esc_url( $order->get_checkout_payment_url( true ) ) . '" class="button">' . esc_html__( 'Click here if you are not redirected automatically.', 'better-payment' ) . '</a></p>';
    }
}
