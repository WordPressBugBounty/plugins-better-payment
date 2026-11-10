<?php

namespace Better_Payment\Lite\Classes;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The plugin action class
 * 
 * @since 0.0.1
 */
class Actions {
    use TraitsHelper;
    /**
     * Constructor
     * 
     * @since 0.0.1
     */
    public function __construct() {
        add_action( 'admin_post_paypal_form_handle', [ $this, 'paypal_form_handle' ] );
        add_action( 'admin_post_nopriv_paypal_form_handle', [ $this, 'paypal_form_handle' ] );

        add_action( 'wp_ajax_better_payment_stripe_get_token', [ $this, 'better_payment_stripe_get_token' ] );
        add_action( 'wp_ajax_nopriv_better_payment_stripe_get_token', [ $this, 'better_payment_stripe_get_token' ] );

        add_action( 'wp_ajax_better_payment_paystack_get_token', [ $this, 'better_payment_paystack_get_token' ] );
        add_action( 'wp_ajax_nopriv_better_payment_paystack_get_token', [ $this, 'better_payment_paystack_get_token' ] );

        // NOTE: PayPal IPN + verification-poll hooks are registered unconditionally
        // in better-payment.php (init_plugin), not here — they must run even when
        // Elementor is inactive (block/campaign payment flow).
    }


    /**
     * Handle the paypal form
     * 
     * @since 0.0.1
     */
    public function paypal_form_handle() {

        check_admin_referer( 'better-payment-paypal', 'security' );

        // redirect_previous_page() exits; the default only tells static analysis so.
        $widget_id = '';

        $page_id = ! empty( $_POST[ 'better_payment_page_id' ] ) ? intval( $_POST[ 'better_payment_page_id' ], 10 ) : 0;

        if ( ! $page_id ) {
            $this->redirect_previous_page();
        }

        if ( !empty( $_POST[ 'better_payment_widget_id' ] ) ) {
            $widget_id = sanitize_text_field( wp_unslash( $_POST[ 'better_payment_widget_id' ] ) );
        } else {
            $this->redirect_previous_page();
        }

        $el_settings = $this->better_payment_widget_settings( $page_id, $widget_id );

        if ( empty( $el_settings ) ) {
            $this->redirect_previous_page();
        }

        if ( ! PaymentRateLimiter::allow( $page_id, $widget_id ) ) {
            wp_die( esc_html( PaymentRateLimiter::message() ), esc_html__( 'Too many payment attempts', 'better-payment' ), array( 'response' => 429, 'back_link' => true ) );
        }

        if ( empty( $el_settings[ 'better_payment_paypal_business_email' ] ) ) {
            $this->redirect_previous_page();
        }

        if ( $el_settings[ 'better_payment_paypal_live_mode' ] == 'yes' ) {
            $path = "paypal";
        } else {
            $path = "sandbox.paypal";
        }

        $el_settings_currency = $el_settings[ 'better_payment_form_currency' ];
        $woo_product_id = !empty($el_settings["better_payment_form_woocommerce_product_id"]) ? intval($el_settings["better_payment_form_woocommerce_product_id"]) : 0;
        $fluentcart_product_id = !empty($el_settings["better_payment_form_fluentcart_product_id"]) ? intval($el_settings["better_payment_form_fluentcart_product_id"]) : 0;

        // Amount, quantity, currency and campaign come from the stored settings, never
        // from the request — see PaymentRequestGuard.
        $guarded = ( new PaymentRequestGuard() )->resolve_form_payment(
            $el_settings,
            wp_unslash( $_POST ),
            [
                'page_id' => $page_id,
                'gateway' => 'paypal',
            ]
        );

        if ( is_wp_error( $guarded ) ) {
            PaymentRequestGuard::reject_form_post( $guarded );
            return;
        }

        // A payer email that is not an address is refused, not stored as '' — see PayerEmail.
        $invalid_email = PayerEmail::check( wp_unslash( $_POST ) );
        if ( null !== $invalid_email ) {
            PaymentRequestGuard::reject_form_post( $invalid_email );
            return;
        }

        $el_settings_currency        = $guarded['currency'];
        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html($el_settings_currency) );

        // Rounded the way PayPal takes it (a quantity can multiply 0.1 into 0.30000000000000004),
        // and stored as sent, since the IPN compares mc_gross against the stored amount.
        $primary_payment_amount          = Handler::paypal_amount( $guarded['amount'], $el_settings_currency );
        $primary_payment_amount_quantity = $guarded['quantity'];

        $order_id = Handler::new_order_id( 'paypal' );

        // PayPal sends the payer back to these URLs, and they arrive in the request. Only a
        // URL on this site is accepted; anything else falls back to the form's page, with the
        // same status arguments Handler::paypal_button() posts, so the thank-you / error
        // message still shows (a multilingual site on several domains can hit the fallback).
        $site_url          = get_permalink( $page_id );
        $return_fallback   = Handler::paypal_return_fallback( $site_url, $widget_id, 'success' );
        $cancel_fallback   = Handler::paypal_return_fallback( $site_url, $widget_id, 'error' );
        $return_url        = ! empty( $_POST['return'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return'] ) ), $return_fallback ) : '';
        $cancel_return_url = ! empty( $_POST['cancel_return'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['cancel_return'] ) ), $cancel_fallback ) : '';

        $request_data = [
            'business'      => $el_settings[ 'better_payment_paypal_business_email' ],
            'currency_code' => $el_settings_currency,
            'rm'            => '2',
            'return'        => $return_url,
            'cancel_return' => add_query_arg( 'better_payment_paypal_id', $order_id, $cancel_return_url ),
            'item_number'   => $order_id,
            'item_name'     => ! empty( $el_settings['better_payment_form_title'] ) ? esc_html( $el_settings['better_payment_form_title'] ) : esc_html__('Better Payment', 'better-payment'),
            'amount'        => $primary_payment_amount,
            'cmd'           => $el_settings[ 'better_payment_paypal_button_type' ],
            'notify_url'    => admin_url( 'admin-post.php?action=better_payment_paypal_ipn' ),
        ];

        //Form fields data to send via email
        $better_form_fields = [
            'amount' => sanitize_text_field($el_settings_currency_symbol) . $primary_payment_amount,
            'referer_page_id' => $page_id,
            'referer_widget_id' => $widget_id,
            'woo_product_id' => $woo_product_id,
            'fluentcart_product_id' => $fluentcart_product_id,
            'source' => 'paypal',
            'amount_quantity' => ! empty( $primary_payment_amount_quantity ) ? intval( $primary_payment_amount_quantity ) : '',
            'paypal_business_email' => Handler::paypal_business_identifier( $el_settings['better_payment_paypal_business_email'] ),
            // The PayPal environment the order was sent to; the IPN must come from the same one.
            'paypal_mode'           => 'yes' === $el_settings['better_payment_paypal_live_mode'] ? 'live' : 'sandbox',
            // Persisted so the async PayPal IPN can rebuild the e-mail body + on/off toggle.
            'email_settings'        => Handler::extract_email_settings( $el_settings ),
        ];

        // Fields only Pro's forms store (layout 6's products and platform flags) — see PaymentEntitlement.
        $better_form_fields = PaymentEntitlement::transaction_fields( $better_form_fields, $el_settings, $guarded );

        // Fields the form set win over what the visitor posts: a custom field the admin

        // named like one of them ("Paypal Mode", "Amount") must not overwrite it.

        $better_form_fields = $better_form_fields + $this->fetch_better_form_fields($el_settings, $_POST);

        if ( !empty( $better_form_fields[ 'primary_first_name' ] ) ) {
            $request_data[ 'primary_first_name' ] = sanitize_text_field( $better_form_fields[ 'primary_first_name' ] );
        }

        if ( !empty( $better_form_fields[ 'primary_last_name' ] ) ) {
            $request_data[ 'primary_last_name' ] = sanitize_text_field( $better_form_fields[ 'primary_last_name' ] );
        }

        if ( !empty( $better_form_fields[ 'primary_email' ] ) ) {
            $request_data[ 'primary_email' ] = sanitize_email( $better_form_fields[ 'primary_email' ] );
        }

        if ( !empty( $better_form_fields[ 'primary_reference_number' ] ) ) {
            $request_data[ 'invoice' ] = sanitize_text_field( $better_form_fields[ 'primary_reference_number' ] );
        }
        
        $campaign_id = $guarded['campaign_id'];

        Handler::payment_create(
            [
                'amount'       => floatval( $primary_payment_amount ),
                'order_id'     => $order_id,
                'payment_date' => gmdate( 'Y-m-d H:i:s' ),
                'source'       => 'paypal',
                'form_fields_info'     => maybe_serialize( $better_form_fields ),
                'currency'     => sanitize_text_field($el_settings_currency),
                'referer'      => "widget",
                'campaign_id'  => $campaign_id,
            ]
        );
        $paypal_url = "https://www.$path.com/cgi-bin/webscr?";
        $paypal_url .= http_build_query( $request_data );
        // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Redirect to the PayPal gateway (paypal.com / sandbox.paypal.com), an external host by design.
        wp_redirect( esc_url_raw($paypal_url) );
    }

    /**
     * Get the stripe token
     * 
     * @since 0.0.1
     */
    public function better_payment_stripe_get_token() {
        if ( !check_admin_referer( 'better-payment', 'security' ) ) {
            wp_send_json_error();
        }

        $setting_data_page_id = isset($_POST[ 'setting_data' ]['page_id']) ? intval( $_POST[ 'setting_data' ]['page_id'] ) : 0;
        $setting_data_widget_id = isset($_POST[ 'setting_data' ]['widget_id']) ? sanitize_text_field( wp_unslash( $_POST[ 'setting_data' ]['widget_id'] ) ) : 0;

        if ( !empty( $setting_data_page_id ) ) {
            $page_id = $setting_data_page_id;
        } else {
            $err_msg = __( 'Page ID is missing', 'better-payment' );
            wp_send_json_error( esc_html($err_msg) );
        }

        if ( !empty( $setting_data_widget_id ) ) {
            $widget_id = sanitize_text_field( $setting_data_widget_id );
        } else {
            $err_msg = __( 'Widget ID is missing', 'better-payment' );
            wp_send_json_error( esc_html($err_msg) );
        }

        $el_settings = $this->better_payment_widget_settings( $page_id, $widget_id );

        // Before any setting is read: an unknown, or unpublished, form has none.
        if ( empty( $el_settings ) ) {
            wp_send_json_error( esc_html(__( 'Setting Data is missing', 'better-payment' )) );
        }

        if ( ! PaymentRateLimiter::allow( $page_id, $widget_id ) ) {
            wp_send_json_error( esc_html( PaymentRateLimiter::message() ) );
        }

        $better_payment_keys = [
            'public_key' => 'yes' === sanitize_text_field( $el_settings[ 'better_payment_stripe_live_mode' ] ) ? sanitize_text_field( $el_settings[ 'better_payment_stripe_public_key_live' ] ) : sanitize_text_field( $el_settings[ 'better_payment_stripe_public_key' ] ),
            'secret_key' => Handler::stripe_secret_key( $el_settings ),
        ];

        // Amount, quantity, currency, price ids, coupon and campaign come from the stored
        // settings, never from the request — see PaymentRequestGuard.
        $guard   = new PaymentRequestGuard();
        $guarded = $guard->resolve_form_payment(
            $el_settings,
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw field array handed to PaymentRequestGuard, which validates/sanitizes each field it reads; nonce checked at the top of the handler.
            isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [],
            [
                'page_id' => $page_id,
                'gateway' => 'stripe',
            ]
        );

        if ( is_wp_error( $guarded ) ) {
            wp_send_json_error( esc_html( $guarded->get_error_message() ) );
        }

        // A payer email that is not an address is refused, not stored as '' — see PayerEmail.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- PayerEmail only validates; nonce checked at the top of the handler.
        $invalid_email = PayerEmail::check( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [] );
        if ( null !== $invalid_email ) {
            wp_send_json_error( esc_html( $invalid_email->get_error_message() ) );
        }

        $amount          = $guarded['amount'];
        $amount_quantity = $guarded['quantity'];

        if ( empty( $better_payment_keys['public_key'] ) || empty( $better_payment_keys['secret_key'] ) ) {
            wp_send_json_error( esc_html( PaymentError::not_configured( 'stripe', 'widget', $setting_data_page_id, __( 'The Stripe secret key is missing.', 'better-payment' ) ) ) );
        }

        $header_info = array(
            'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $better_payment_keys['secret_key'] ) . ':' ),
            'Stripe-Version' => '2019-05-16'
        );

        $order_id = Handler::new_order_id( 'stripe' );

        $el_settings_currency = $el_settings[ 'better_payment_form_currency' ];
        $woo_product_id = !empty($el_settings["better_payment_form_woocommerce_product_id"]) ? intval($el_settings["better_payment_form_woocommerce_product_id"]) : 0;
        $fluentcart_product_id = !empty($el_settings["better_payment_form_fluentcart_product_id"]) ? intval($el_settings["better_payment_form_fluentcart_product_id"]) : 0;

        $el_settings_currency = $guarded['currency'];

        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html($el_settings_currency) );

        $request  = [
            'success_url'                => add_query_arg( [
                'better_payment_stripe_status' => 'success',
                'better_payment_stripe_id'     => $order_id,
                'better_payment_widget_id'     => $widget_id
            ], get_permalink( $setting_data_page_id )  ),
            'cancel_url'                 => add_query_arg( [
                'better_payment_error_status' => 'cancelled',
                'better_payment_stripe_id'    => $order_id,
                'better_payment_widget_id'    => $widget_id
            ], get_permalink( $setting_data_page_id )  ),
            'locale'                     => 'auto',
            'payment_method_types'       => [ 'card' ],
            'client_reference_id'        => time(),
            'billing_address_collection' => 'required',
            'metadata'                   => [
                'order_id' => $order_id
            ],
            'line_items'                 => [
                [
                    'amount'   => Handler::stripe_minor_amount( $amount, $el_settings_currency ),
                    'currency' => $el_settings_currency,
                    'name'     => ! empty( $el_settings['better_payment_form_title'] ) ? esc_html( $el_settings['better_payment_form_title'] ) : esc_html__('Better Payment', 'better-payment'),
                    'quantity' => 1
                ]
            ],
            'payment_intent_data'        => [
                'capture_method' => 'automatic',
                'description'    => ! empty( $el_settings['better_payment_form_title'] ) ? esc_html( $el_settings['better_payment_form_title'] ) : esc_html__('Better Payment', 'better-payment'),
                'metadata'       => [
                    'order_id' => $order_id
                ]
            ]
        ];

        // The typed code is kept for the receipt; what Stripe applies is decided by the guard —
        // an active promotion code, or a coupon the merchant tagged public. A bare coupon id
        // no longer works, because every coupon in the account (internal 100%-off ones
        // included) would be one guess away.
        $coupon_code = $guarded['coupon_code'];
        $discount    = $guard->resolve_stripe_discount( $coupon_code, $better_payment_keys['secret_key'] );

        if ( is_wp_error( $discount ) ) {
            wp_send_json_error( esc_html( $discount->get_error_message() ) );
        }

        if ( ! empty( $discount ) ) {
            $request['discounts'] = [ $discount ];
        }

        //Form fields data to send via email
        $better_form_fields = [
            'amount' => $el_settings_currency_symbol . $amount,
            'referer_page_id' => $page_id,
            'referer_widget_id' => $widget_id,
            'woo_product_id' => $woo_product_id,
            'fluentcart_product_id' => $fluentcart_product_id,
            'source' => 'stripe',
            'amount_quantity' => ! empty( $amount_quantity ) ? $amount_quantity : '',
            'stripe_coupon_code' => $coupon_code,
        ];

        // Fields only Pro's forms store (layout 6's products and platform flags) — see PaymentEntitlement.
        $better_form_fields = PaymentEntitlement::transaction_fields( $better_form_fields, $el_settings, $guarded );

        // Fields the form set win over what the visitor posts: a custom field the admin
        // named like one of them ("Paypal Mode", "Amount") must not overwrite it.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fetch_better_form_fields() sanitizes each field it keeps; not unslashed here to keep the stored values byte-identical. Nonce checked at the top of the handler.
        $better_form_fields = $better_form_fields + $this->fetch_better_form_fields( $el_settings, isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? $_POST['fields'] : [] );

        if ( !empty( $better_form_fields['primary_first_name'] ) ) {
            $request[ 'customer_name' ] = $better_form_fields['primary_first_name'];
        }

        if ( !empty( $better_form_fields['primary_last_name'] ) ) {
            $request[ 'customer_name' ] = !empty( $request[ 'customer_name' ] ) ? $request[ 'customer_name' ] . ' ' . $better_form_fields['primary_last_name'] : $better_form_fields['primary_last_name'];
        }

        if ( !empty( $request[ 'customer_name' ] ) ) {
            $request[ 'metadata' ][ 'customer_name' ]                          = $request[ 'customer_name' ];
            $request[ 'payment_intent_data' ][ 'metadata' ][ 'customer_name' ] = $request[ 'customer_name' ];
            unset( $request[ 'customer_name' ] );
        }

        if ( !empty( $better_form_fields['primary_email'] ) ) {
            $request[ 'customer_email' ]                                        = $better_form_fields['primary_email'];
            $request[ 'metadata' ][ 'customer_email' ]                          = $request[ 'customer_email' ];
            $request[ 'payment_intent_data' ][ 'metadata' ][ 'customer_email' ] = $request[ 'customer_email' ];

        }

        // Recurring, split-payment and mixed checkouts are Pro's: refused without Pro, built by
        // Pro — see PaymentEntitlement.
        if ( PaymentEntitlement::is_pro_form( $el_settings ) ) {
            $checkout = PaymentEntitlement::stripe_checkout(
                [
                    'request'     => $request,
                    'form_fields' => $better_form_fields,
                ],
                [
                    'settings'    => $el_settings,
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw field array handed to PaymentRequestGuard, which validates/sanitizes each field it reads; nonce checked at the top of the handler.
                    'fields'      => isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [],
                    'guarded'     => $guarded,
                    'order_id'    => $order_id,
                    'widget_id'   => $widget_id,
                    'header_info' => $header_info,
                ]
            );

            if ( is_wp_error( $checkout ) ) {
                wp_send_json_error( esc_html( PaymentError::checkout_refusal( $checkout, 'stripe', 'widget', $setting_data_page_id ) ) );
            }

            $request            = $checkout['request'];
            $better_form_fields = $checkout['form_fields'];
        }

        #ToDo: Need to prefill name field on stripe checkout
        $response = wp_safe_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            array(
                'method'  => 'POST',
                'headers' => $header_info,
                'body'    => $request,
                'timeout' => 70,
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( esc_html( PaymentError::stripe( $response, 'widget', $setting_data_page_id ) ) );
        }

        $response_ar = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! empty( $response_ar->payment_intent ) || ( ! empty( $response_ar->mode ) && 'subscription' === $response_ar->mode ) ) {
            $campaign_id = $guarded['campaign_id'];

            Handler::payment_create(
                [
                    'amount'         => $amount,
                    'order_id'       => $order_id,
                    'payment_date'   => gmdate( 'Y-m-d H:i:s' ),
                    'source'         => 'stripe',
                    'transaction_id' => sanitize_text_field($response_ar->payment_intent),
                    'customer_info'  => maybe_serialize( $response_ar ),
                    'form_fields_info'  => maybe_serialize( $better_form_fields ),
                    'obj_id'         => sanitize_text_field($response_ar->id),
                    'status'         => sanitize_text_field($response_ar->payment_status),
                    'currency'       => $el_settings_currency,
                    'referer'        => "widget",
                    'campaign_id'    => $campaign_id,
                ]
            );
            wp_send_json_success(
                [
                    'stripe_data'       => sanitize_text_field($response_ar->id),
                    'stripe_public_key' => sanitize_text_field( $better_payment_keys['public_key'] )
                ]
            );
        } else {
            wp_send_json_error( esc_html( PaymentError::stripe( $response, 'widget', $setting_data_page_id ) ) );
        }
    }

    /**
     * Get the paystack token
     * 
     * @since 0.0.1
     */
    public function better_payment_paystack_get_token() {
        if ( !check_admin_referer( 'better-payment', 'security' ) ) {
            wp_send_json_error();
        }

        $error_message = '';

        if( empty( $_POST[ 'setting_data' ]['page_id'] ) ){
           $error_message = __( 'Page ID is missing', 'better-payment' );
        }

        if( empty( $_POST[ 'setting_data' ]['widget_id'] ) ){
            $error_message = __( 'Widget ID is missing', 'better-payment' );
        }

        if( ! empty( $error_message ) ){
            wp_send_json_error( esc_html( $error_message ) );
        }

        $page_id = intval( $_POST[ 'setting_data' ]['page_id'] );
        $widget_id = sanitize_text_field( wp_unslash( $_POST[ 'setting_data' ]['widget_id'] ) );
        $el_settings = $this->better_payment_widget_settings( $page_id, $widget_id );

        if ( empty( $el_settings ) ) {
            wp_send_json_error( esc_html(__( 'Setting Data is missing', 'better-payment' )) );
        }

        if ( ! PaymentRateLimiter::allow( $page_id, $widget_id ) ) {
            wp_send_json_error( esc_html( PaymentRateLimiter::message() ) );
        }

        if ( empty( $el_settings[ 'better_payment_paystack_public_key' ] ) || empty( $el_settings[ 'better_payment_paystack_secret_key' ] ) ) {
            wp_send_json_error( esc_html( PaymentError::not_configured( 'paystack', 'widget', $page_id, __( 'The Paystack secret key is missing.', 'better-payment' ) ) ) );
        }

        // Amount, quantity, currency and campaign come from the stored settings, never
        // from the request — see PaymentRequestGuard.
        $guarded = ( new PaymentRequestGuard() )->resolve_form_payment(
            $el_settings,
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw field array handed to PaymentRequestGuard, which validates/sanitizes each field it reads; nonce checked at the top of the handler.
            isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [],
            [
                'page_id' => $page_id,
                'gateway' => 'paystack',
            ]
        );

        if ( is_wp_error( $guarded ) ) {
            wp_send_json_error( esc_html( $guarded->get_error_message() ) );
        }

        // A payer email that is not an address is refused, not stored as '' — see PayerEmail.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- PayerEmail only validates; nonce checked at the top of the handler.
        $invalid_email = PayerEmail::check( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : [] );
        if ( null !== $invalid_email ) {
            wp_send_json_error( esc_html( $invalid_email->get_error_message() ) );
        }

        $amount          = $guarded['amount'];
        $amount_quantity = $guarded['quantity'];

        $header_info = array(
            'Authorization'  => 'Bearer ' . sanitize_text_field( $el_settings[ 'better_payment_paystack_secret_key' ] ),
            "Cache-Control: no-cache",
        );

        $order_id = Handler::new_order_id( 'paystack' );

        $el_settings_currency = $el_settings[ 'better_payment_form_currency' ];
        $woo_product_id = !empty($el_settings["better_payment_form_woocommerce_product_id"]) ? intval($el_settings["better_payment_form_woocommerce_product_id"]) : 0;
        $fluentcart_product_id = !empty($el_settings["better_payment_form_fluentcart_product_id"]) ? intval($el_settings["better_payment_form_fluentcart_product_id"]) : 0;

        $el_settings_currency = $guarded['currency'];

        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html($el_settings_currency) );

        $redirection_url_success    = get_permalink( $page_id );
        $redirection_url_error      = get_permalink( $page_id );

        $request  = [
            'amount'   => Handler::paystack_minor_amount( $amount ),
            'currency' => $el_settings_currency,
            'cart_id'  => $order_id,
            'callback_url'  => add_query_arg( [
                'better_payment_paystack_status' => 'success',
                'better_payment_paystack_id'     => $order_id,
                'better_payment_widget_id'     => $widget_id
            ], $redirection_url_success ),
            'metadata' => [
                'cancel_action' => add_query_arg( [
                    'better_payment_error_status' => 'cancelled',
                    'better_payment_paystack_id'  => $order_id,
                    'better_payment_widget_id'    => $widget_id
                ], $redirection_url_error )
            ]
        ];

        //Form fields data to send via email
        $better_form_fields = [
            'amount' => $el_settings_currency_symbol . $amount,
            'referer_page_id' => $page_id,
            'referer_widget_id' => $widget_id,
            'woo_product_id' => $woo_product_id,
            'fluentcart_product_id' => $fluentcart_product_id,
            'source' => 'paystack',
            'amount_quantity' => ! empty( $amount_quantity ) ? $amount_quantity : '',
        ];

        // Fields only Pro's forms store (layout 6's products and platform flags) — see PaymentEntitlement.
        $better_form_fields = PaymentEntitlement::transaction_fields( $better_form_fields, $el_settings, $guarded );

        // Fields the form set win over what the visitor posts: a custom field the admin
        // named like one of them ("Paypal Mode", "Amount") must not overwrite it.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fetch_better_form_fields() sanitizes each field it keeps; not unslashed here to keep the stored values byte-identical. Nonce checked at the top of the handler.
        $better_form_fields = $better_form_fields + $this->fetch_better_form_fields( $el_settings, isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? $_POST['fields'] : [] );

        if ( !empty( $better_form_fields['primary_email'] ) ) {
            $request[ 'email' ] = $better_form_fields['primary_email'];
        }

        if ( !empty( $better_form_fields['primary_first_name'] ) ) {
            $request[ 'customer_name' ] = $better_form_fields['primary_first_name'];
        }

        if ( !empty( $better_form_fields['primary_last_name'] ) ) {
            $request[ 'customer_name' ] = !empty( $request[ 'customer_name' ] ) ? $request[ 'customer_name' ] . ' ' . $better_form_fields['primary_last_name'] : $better_form_fields['primary_last_name'];
        }

        if ( !empty( $request[ 'customer_name' ] ) ) {
            $request[ 'metadata' ][ 'customer_name' ]   = $request[ 'customer_name' ];
            unset( $request[ 'customer_name' ] );
        }

        $response = wp_safe_remote_post(
            'https://api.paystack.co/transaction/initialize',
            array(
                'method'  => 'POST',
                'headers' => $header_info,
                'body'    => $request,
                'timeout' => 70,
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( esc_html( PaymentError::paystack( $response, 'widget', $page_id ) ) );
        }

        $response_ar = json_decode( wp_remote_retrieve_body( $response ) );

        if( empty( $response_ar->status ) || empty( $response_ar->data ) ){
            wp_send_json_error( esc_html( PaymentError::paystack( $response, 'widget', $page_id ) ) );
        }

        $campaign_id = $guarded['campaign_id'];

        Handler::payment_create(
            [
                'amount'         => $amount,
                'order_id'       => $order_id,
                'payment_date'   => gmdate( 'Y-m-d H:i:s' ),
                'source'         => 'paystack',
                // Paystack's reference, so verification can bind it to this order.
                'transaction_id' => ! empty( $response_ar->data->reference ) ? sanitize_text_field( $response_ar->data->reference ) : '',
                'customer_info'  => maybe_serialize( $response_ar ),
                'form_fields_info'  => maybe_serialize( $better_form_fields ),
                'status'         => 'unpaid',
                'currency'       => $el_settings_currency,
                'referer'        => "widget",
                'campaign_id'    => $campaign_id,
            ]
        );

        $authorization_url = ! empty( $response_ar->data->authorization_url ) ? esc_url_raw( $response_ar->data->authorization_url ) : '';

        wp_send_json_success(
            [
                'authorization_url' => $authorization_url,
            ]
        );
    }

    /**
     * Widget settings
     * 
     * @since 1.0.0
     */
    public function better_payment_widget_settings( $page_id, $widget_id ) {
        // page_id is the visitor's to choose. A form on a page they cannot see — draft,
        // private, scheduled, password-protected — must be neither payable nor probeable.
        if ( ! $this->is_payable_form_page( $page_id ) ) {
            return [];
        }

        $settings = $this->get_elementor_widget_settings( $page_id, $widget_id );

        return $settings;
    }

    /**
     * Redirect to referer page
     * 
     * @since 1.0.0
     */
    public function redirect_previous_page() {
        $location = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? esc_url_raw( wp_unslash( $_SERVER[ 'HTTP_REFERER' ] ) ) : '';
        wp_safe_redirect( $location );
        exit();
    }

    /**
     * Fetch form fields
     * 
     * @since 0.0.4
     */

    public function fetch_better_form_fields($el_settings, $post_data_form_fields) {
        $better_form_fields = array();

        $better_payment_helper_obj = new Helper();
        $post_data_primary_first_name = '';
        $post_data_primary_last_name = '';
        $post_data_primary_email = '';

        $post_fields = $post_data_form_fields;

        // The fields repeater a Pro form renders is Pro's to name — see PaymentEntitlement.
        $el_settings['better_payment_form_fields'] = PaymentEntitlement::form_fields( is_array( $el_settings ) ? $el_settings : [] );

        //in case of duplicate post name using the first one
        if(isset($el_settings['better_payment_form_fields']) && count($el_settings['better_payment_form_fields'])){
            foreach ($el_settings['better_payment_form_fields'] as $item) {

                $item_field_name = $better_payment_helper_obj->titleToSnake($item["better_payment_field_name_heading"]);
                if(!empty($item['better_payment_primary_field_type']) ){
                    $item_primary_field_type = $item['better_payment_primary_field_type'];

                    if( isset($post_fields[$item_primary_field_type]) ) {
                        $post_fields[$item_primary_field_type] =  is_array($post_fields[$item_primary_field_type]) ? $post_fields[$item_primary_field_type][0] : $post_fields[$item_primary_field_type];
                    }

                    if( isset($post_fields[$item_field_name]) ) {
                        $post_fields[$item_field_name] =  is_array($post_fields[$item_field_name]) ? $post_fields[$item_field_name][0] : $post_fields[$item_field_name];
                    }

                    if ( 'primary_first_name' == $item_primary_field_type && isset($post_fields[$item_primary_field_type])){
                        $better_form_fields[$item_primary_field_type] =  sanitize_text_field($post_fields[$item_primary_field_type]);
                        $post_data_primary_first_name = sanitize_text_field($post_fields[$item_primary_field_type]);

                    }else if ( 'primary_last_name' == $item_primary_field_type && isset($post_fields[$item_primary_field_type])){
                        $better_form_fields[$item_primary_field_type] = sanitize_text_field($post_fields[$item_primary_field_type]);
                        $post_data_primary_last_name = sanitize_text_field($post_fields[$item_primary_field_type]);

                    }else if( 'primary_email' == $item_primary_field_type && isset($post_fields[$item_primary_field_type])){
                        $better_form_fields[$item_primary_field_type] = sanitize_email($post_fields[$item_primary_field_type]);
                        $post_data_primary_email = sanitize_email($post_fields[$item_primary_field_type]);

                    }else if ( 'primary_payment_amount' == $item_primary_field_type && isset($post_fields[$item_primary_field_type]) ){
                        $better_form_fields[$item_primary_field_type] = floatval($post_fields[$item_primary_field_type]);

                    }else if ( 'primary_reference_number' == $item_primary_field_type && isset($post_fields[$item_primary_field_type]) ){
                        $better_form_fields[$item_primary_field_type] = sanitize_text_field($post_fields[$item_primary_field_type]);

                    } else {
                        $better_form_fields[$item_field_name] = !empty($post_fields[$item_field_name]) ? sanitize_text_field($post_fields[$item_field_name]) : '';
                    }

                }
            }
        }

        return $better_form_fields;
    }
}
