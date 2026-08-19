<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Thin Stripe API service over the plugin's global Stripe configuration.
 *
 * Extracted as a reusable, WooCommerce-agnostic entry point so integration
 * modules (the WooCommerce gateway today, future consumers tomorrow) can
 * create Checkout Sessions through the exact same wire protocol the existing
 * surfaces use (see Classes\Actions::better_payment_stripe_get_token()):
 * HTTP Basic auth with the secret key, `Stripe-Version: 2019-05-16`, the
 * legacy `line_items` shape, and a 70s timeout. The existing surfaces are
 * deliberately NOT migrated onto this service — their behavior must not
 * change (they resolve keys per-widget, not globally).
 *
 * @since 2.4.0
 */
class StripeService {

    /**
     * Pinned Stripe API version — must match every existing Better Payment
     * Stripe call so all plugin traffic speaks one protocol version.
     */
    const API_VERSION = '2019-05-16';

    /**
     * Stripe API base URL.
     */
    const API_BASE = 'https://api.stripe.com/v1';

    /**
     * Resolve the global Stripe keys (Better Payment → Settings → Stripe).
     *
     * Live/test resolution mirrors API\AdminAPI::get_stripe_product_details()'s
     * canonical pattern over the `better_payment_settings` option.
     *
     * @return array{public_key: string, secret_key: string, live_mode: bool}
     */
    public static function get_global_keys() {
        $is_live_mode = 'yes' === DB::get_settings( 'better_payment_settings_payment_stripe_live_mode' );

        $public_key = $is_live_mode
            ? DB::get_settings( 'better_payment_settings_payment_stripe_live_public' )
            : DB::get_settings( 'better_payment_settings_payment_stripe_test_public' );
        $secret_key = $is_live_mode
            ? DB::get_settings( 'better_payment_settings_payment_stripe_live_secret' )
            : DB::get_settings( 'better_payment_settings_payment_stripe_test_secret' );

        return array(
            'public_key' => is_string( $public_key ) ? trim( $public_key ) : '',
            'secret_key' => is_string( $secret_key ) ? trim( $secret_key ) : '',
            'live_mode'  => $is_live_mode,
        );
    }

    /**
     * Whether the global Stripe configuration is usable (both keys present
     * for the currently selected mode).
     *
     * @return bool
     */
    public static function is_configured() {
        $keys = self::get_global_keys();

        return ! empty( $keys['public_key'] ) && ! empty( $keys['secret_key'] );
    }

    /**
     * Create a Stripe Checkout Session.
     *
     * Same request mechanics as the existing surfaces (Basic auth header,
     * pinned API version, form-encoded body, 70s timeout). The caller owns
     * the request array shape; this method owns only the transport.
     *
     * @param mixed  $session_args Checkout Session request parameters (array).
     * @param string $secret_key   Stripe secret key.
     *
     * @return object|\WP_Error Decoded session object on success.
     */
    public static function create_checkout_session( $session_args, $secret_key ) {
        if ( empty( $secret_key ) || ! is_array( $session_args ) ) {
            return new \WP_Error( 'stripe_error', __( 'Stripe is not configured.', 'better-payment' ) );
        }

        $response = wp_safe_remote_post(
            self::API_BASE . '/checkout/sessions',
            array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $secret_key ) . ':' ),
                    'Stripe-Version' => self::API_VERSION,
                ),
                'body'    => $session_args,
                'timeout' => 70,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );

        if ( empty( $body ) ) {
            return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
        }

        $session = json_decode( $body );

        if ( ! is_object( $session ) ) {
            return new \WP_Error( 'stripe_error', __( 'Unexpected response from the Stripe API endpoint.', 'better-payment' ) );
        }

        if ( isset( $session->error ) ) {
            $message = ! empty( $session->error->message )
                ? sanitize_text_field( $session->error->message )
                : __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' );

            return new \WP_Error( 'stripe_error', $message );
        }

        if ( empty( $session->id ) ) {
            return new \WP_Error( 'stripe_error', __( 'Unexpected response from the Stripe API endpoint.', 'better-payment' ) );
        }

        return $session;
    }

    /**
     * Retrieve a Checkout Session, optionally expanding related objects.
     *
     * Added for the WooCommerce subscriptions feature: after a verified
     * subscription checkout the module retrieves the session with
     * `expand[]=payment_intent` to learn the reusable customer + payment
     * method Stripe attached. Same transport mechanics as
     * create_checkout_session() (Basic auth, pinned API version, 70s
     * timeout); that method keeps its own inline handling untouched.
     *
     * @since 2.4.0
     *
     * @param mixed  $session_id Checkout Session id (cs_...).
     * @param string $secret_key Stripe secret key.
     * @param mixed  $expand     Object paths to expand (e.g. array( 'payment_intent' )).
     *
     * @return object|\WP_Error Decoded session object on success.
     */
    public static function retrieve_checkout_session( $session_id, $secret_key, $expand = array() ) {
        if ( empty( $secret_key ) || empty( $session_id ) || ! is_string( $session_id ) ) {
            return new \WP_Error( 'stripe_error', __( 'Stripe is not configured.', 'better-payment' ) );
        }

        $url = self::API_BASE . '/checkout/sessions/' . rawurlencode( sanitize_text_field( $session_id ) );

        if ( ! empty( $expand ) && is_array( $expand ) ) {
            $url = add_query_arg( array( 'expand' => array_map( 'sanitize_text_field', array_values( $expand ) ) ), $url );
        }

        $response = wp_safe_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $secret_key ) . ':' ),
                    'Stripe-Version' => self::API_VERSION,
                ),
                'timeout' => 70,
            )
        );

        return self::decode_object_response( $response );
    }

    /**
     * Create a PaymentIntent.
     *
     * Added for the WooCommerce subscriptions feature: renewal orders are
     * charged server-side with an off-session PaymentIntent against the
     * customer + payment method saved at the first checkout. The caller owns
     * the request array shape; this method owns only the transport.
     *
     * @since 2.4.0
     *
     * @param mixed  $intent_args PaymentIntent request parameters (array).
     * @param string $secret_key  Stripe secret key.
     *
     * @return object|\WP_Error Decoded PaymentIntent object on success.
     */
    public static function create_payment_intent( $intent_args, $secret_key ) {
        if ( empty( $secret_key ) || ! is_array( $intent_args ) ) {
            return new \WP_Error( 'stripe_error', __( 'Stripe is not configured.', 'better-payment' ) );
        }

        $response = wp_safe_remote_post(
            self::API_BASE . '/payment_intents',
            array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $secret_key ) . ':' ),
                    'Stripe-Version' => self::API_VERSION,
                ),
                'body'    => $intent_args,
                'timeout' => 70,
            )
        );

        return self::decode_object_response( $response );
    }

    /**
     * Create a Stripe Customer.
     *
     * Added for the WooCommerce subscriptions feature's free-trial checkout:
     * a $0 order collects the card through a setup-mode Checkout Session,
     * and a setup session only attaches the saved payment method to a
     * customer the caller supplies — so one is created up front. The caller
     * owns the request array shape; this method owns only the transport.
     *
     * @since 2.4.0
     *
     * @param mixed  $customer_args Customer request parameters (array).
     * @param string $secret_key    Stripe secret key.
     *
     * @return object|\WP_Error Decoded Customer object on success.
     */
    public static function create_customer( $customer_args, $secret_key ) {
        if ( empty( $secret_key ) || ! is_array( $customer_args ) ) {
            return new \WP_Error( 'stripe_error', __( 'Stripe is not configured.', 'better-payment' ) );
        }

        $response = wp_safe_remote_post(
            self::API_BASE . '/customers',
            array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $secret_key ) . ':' ),
                    'Stripe-Version' => self::API_VERSION,
                ),
                'body'    => $customer_args,
                'timeout' => 70,
            )
        );

        return self::decode_object_response( $response );
    }

    /**
     * Decode a Stripe API response into an object, normalizing transport
     * failures and Stripe error payloads into WP_Error. Shared by the
     * transports added for subscriptions.
     *
     * @since 2.4.0
     *
     * @param array|\WP_Error $response wp_safe_remote_* result.
     * @return object|\WP_Error
     */
    private static function decode_object_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );

        if ( empty( $body ) ) {
            return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
        }

        $object = json_decode( $body );

        if ( ! is_object( $object ) ) {
            return new \WP_Error( 'stripe_error', __( 'Unexpected response from the Stripe API endpoint.', 'better-payment' ) );
        }

        if ( isset( $object->error ) ) {
            $message = ! empty( $object->error->message )
                ? sanitize_text_field( $object->error->message )
                : __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' );

            return new \WP_Error( 'stripe_error', $message );
        }

        if ( empty( $object->id ) ) {
            return new \WP_Error( 'stripe_error', __( 'Unexpected response from the Stripe API endpoint.', 'better-payment' ) );
        }

        return $object;
    }
}
