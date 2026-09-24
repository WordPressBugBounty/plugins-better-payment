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
 * Bridges WooCommerce orders to the Better Payment payment engine.
 *
 * Two runtime responsibilities:
 *   1. On the order-received page, trigger the EXISTING verifier
 *      (Handler::stripe_payment_success()) — the engine re-retrieves the
 *      Checkout Session server-side, flips the transaction row to paid and
 *      fires `better_payment/payment_confirmed`. A $0 free-trial order's
 *      setup-mode session goes through verify_setup_return() instead.
 *   2. Consume `better_payment/payment_confirmed` and mark the linked
 *      WooCommerce order as paid. Transactions without a `wc_order_id`
 *      (i.e. every non-WooCommerce payment) are ignored with an early
 *      return, so existing traffic is untouched.
 *
 * The static build_*() helpers are pure (no WooCommerce classes) so the
 * unit suite can cover them without WooCommerce loaded.
 *
 * @since 2.4.0
 */
class OrderHandler {

    /**
     * Transaction statuses the engine treats as a successful payment.
     * Superset of Classes\Helper::APPROVED_DONATION_STATUSES, plus
     * Stripe's `no_payment_required` (fully discounted sessions).
     *
     * @var string[]
     */
    const PAID_STATUSES = array( 'paid', 'Completed', 'complete', 'completed', 'succeeded', 'success', 'no_payment_required' );

    /**
     * Wire the runtime hooks. Called only when WooCommerce is active.
     *
     * @return void
     */
    public static function register() {
        // Priority 5: run before the order-received template renders so the
        // customer sees the post-verification order state.
        add_action( 'template_redirect', array( __CLASS__, 'maybe_verify_return' ), 5 );

        // The engine's completion contract — same hook Campaign\CampaignStats
        // consumes. Fired once per transaction by the return that claimed it:
        // Handler::confirm_stripe_session(), or confirm_setup_session() for a
        // free trial's setup-mode session.
        add_action( 'better_payment/payment_confirmed', array( __CLASS__, 'on_payment_confirmed' ), 10, 1 );
    }

    /**
     * Build the Stripe Checkout Session request for a WooCommerce order.
     *
     * Pure: takes scalars, returns the request array. The shape mirrors
     * Classes\Actions::better_payment_stripe_get_token() — legacy
     * `line_items` (amount/currency/name), `payment_intent_data`,
     * `metadata.order_id` — so the engine's return-side verification and
     * the Stripe account see the exact protocol every other Better Payment
     * surface speaks.
     *
     * @param array $data {
     *     @type string $bp_order_id    Better Payment order id (stripe_xxx).
     *     @type int    $wc_order_id    WooCommerce order id.
     *     @type string $order_number   Display order number.
     *     @type float  $amount         Order total (major units).
     *     @type string $currency       ISO currency code.
     *     @type string $success_url    Return URL on success.
     *     @type string $cancel_url     Return URL on cancel.
     *     @type string $customer_email Billing email.
     *     @type string $customer_name  Billing name.
     *     @type string $site_name      Blog name for the line item label.
     * }
     * @return array
     */
    public static function build_session_request( $data ) {
        $order_number = ! empty( $data['order_number'] ) ? (string) $data['order_number'] : (string) ( isset( $data['wc_order_id'] ) ? $data['wc_order_id'] : '' );
        $site_name    = ! empty( $data['site_name'] ) ? (string) $data['site_name'] : '';

        /* translators: 1: order number, 2: site name */
        $description = trim( sprintf( __( 'Order %1$s — %2$s', 'better-payment' ), '#' . $order_number, $site_name ), " \t—" );

        $request = array(
            'success_url'                => (string) $data['success_url'],
            'cancel_url'                 => (string) $data['cancel_url'],
            'locale'                     => 'auto',
            'payment_method_types'       => array( 'card' ),
            'client_reference_id'        => (string) $data['wc_order_id'],
            'billing_address_collection' => 'auto',
            'metadata'                   => array(
                'order_id'    => (string) $data['bp_order_id'],
                'wc_order_id' => (string) $data['wc_order_id'],
            ),
            'line_items'                 => array(
                array(
                    'amount'   => (int) round( (float) $data['amount'] * 100 ),
                    'currency' => (string) $data['currency'],
                    'name'     => $description,
                    'quantity' => 1,
                ),
            ),
            'payment_intent_data'        => array(
                'capture_method' => 'automatic',
                'description'    => $description,
                'metadata'       => array(
                    'order_id'    => (string) $data['bp_order_id'],
                    'wc_order_id' => (string) $data['wc_order_id'],
                ),
            ),
        );

        if ( ! empty( $data['customer_email'] ) && is_email( $data['customer_email'] ) ) {
            $request['customer_email']                                    = sanitize_email( $data['customer_email'] );
            $request['metadata']['customer_email']                        = $request['customer_email'];
            $request['payment_intent_data']['metadata']['customer_email'] = $request['customer_email'];
        }

        if ( ! empty( $data['customer_name'] ) ) {
            $customer_name = sanitize_text_field( $data['customer_name'] );

            $request['metadata']['customer_name']                        = $customer_name;
            $request['payment_intent_data']['metadata']['customer_name'] = $customer_name;
        }

        return $request;
    }

    /**
     * Build the transaction row for Handler::payment_create().
     *
     * Pure. `referer` is `woocommerce` (alongside the existing `widget`,
     * `elementor-form`, `gutenberg-block`), and `form_fields_info` carries
     * `wc_order_id` — the back-reference on_payment_confirmed() resolves.
     *
     * @param array  $data    Same shape as build_session_request().
     * @param object $session Decoded Stripe Checkout Session.
     * @return array
     */
    public static function build_transaction_data( $data, $session ) {
        $form_fields_info = array(
            'wc_order_id'     => (int) $data['wc_order_id'],
            'wc_order_number' => ! empty( $data['order_number'] ) ? (string) $data['order_number'] : (string) $data['wc_order_id'],
            'source'          => 'stripe',
            'amount'          => (float) $data['amount'],
            'primary_email'   => ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '',
            'primary_name'    => ! empty( $data['customer_name'] ) ? sanitize_text_field( $data['customer_name'] ) : '',
        );

        return array(
            'amount'           => (float) $data['amount'],
            'order_id'         => (string) $data['bp_order_id'],
            'payment_date'     => current_time( 'mysql' ),
            'source'           => 'stripe',
            'transaction_id'   => ! empty( $session->payment_intent ) ? sanitize_text_field( $session->payment_intent ) : '',
            'customer_info'    => maybe_serialize( $session ),
            'form_fields_info' => maybe_serialize( $form_fields_info ),
            'obj_id'           => sanitize_text_field( $session->id ),
            'status'           => ! empty( $session->payment_status ) ? sanitize_text_field( $session->payment_status ) : 'unpaid',
            'currency'         => (string) $data['currency'],
            'referer'          => 'woocommerce',
            'campaign_id'      => '',
        );
    }

    /**
     * Extract the WooCommerce order id from a transaction row's
     * form_fields_info payload. Returns 0 for every non-WooCommerce row.
     *
     * @param mixed $form_fields_info Raw (possibly serialized) column value.
     * @return int
     */
    public static function extract_wc_order_id( $form_fields_info ) {
        $fields = maybe_unserialize( $form_fields_info );

        if ( ! is_array( $fields ) || empty( $fields['wc_order_id'] ) ) {
            return 0;
        }

        return (int) $fields['wc_order_id'];
    }

    /**
     * Whether a transaction status string counts as paid.
     *
     * @param mixed $status Row status.
     * @return bool
     */
    public static function is_paid_status( $status ) {
        return is_string( $status ) && in_array( $status, self::PAID_STATUSES, true );
    }

    /**
     * Whether the verified paid amount matches the order total (to the
     * cent). Pure; used before marking an order paid so a transaction that
     * settled for a different amount can never silently complete an order.
     *
     * @param mixed $paid_amount Row amount (what Stripe reports was paid).
     * @param mixed $order_total WooCommerce order total.
     * @return bool
     */
    public static function amount_matches( $paid_amount, $order_total ) {
        return abs( (float) $paid_amount - (float) $order_total ) < 0.01;
    }

    /**
     * On the order-received page, run the engine's Stripe verification for
     * orders paid through this gateway.
     *
     * Reuses Handler::stripe_payment_success() verbatim: it reads
     * `better_payment_stripe_id` from the request, re-retrieves the session
     * from the Stripe API, flips the row to paid (single-shot via its
     * `status='unpaid'` guard) and fires `better_payment/payment_confirmed`
     * — which on_payment_confirmed() below turns into the order update.
     *
     * @return void
     */
    public static function maybe_verify_return() {
        if ( ! function_exists( 'wc_get_order' ) || ! function_exists( 'is_wc_endpoint_url' ) ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Stripe redirect; verification is a server-side API retrieval keyed by our own order id.
        if ( empty( $_GET['better_payment_stripe_status'] ) || 'success' !== $_GET['better_payment_stripe_status'] || empty( $_GET['better_payment_stripe_id'] ) ) {
            return;
        }

        if ( ! is_wc_endpoint_url( 'order-received' ) ) {
            return;
        }

        $order_id = absint( get_query_var( 'order-received' ) );
        $order    = $order_id ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return;
        }

        $order_key = ! empty( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( ! $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
            return;
        }

        if ( Gateway::GATEWAY_ID !== $order->get_payment_method() ) {
            return;
        }

        // Only verify the transaction that belongs to THIS order — a visitor
        // holding a valid order key must not be able to trigger verification
        // of arbitrary transaction ids through this path.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above; matched against server-side order meta.
        $requested_id = sanitize_text_field( wp_unslash( $_GET['better_payment_stripe_id'] ) );

        if ( $requested_id !== $order->get_meta( '_bp_payment_id' ) ) {
            return;
        }

        if ( ! $order->needs_payment() ) {
            return; // Already confirmed (page refresh / duplicate return).
        }

        $keys = StripeService::get_global_keys();

        if ( empty( $keys['secret_key'] ) ) {
            self::log( 'Return verification skipped for order #' . $order->get_id() . ': Stripe keys are no longer configured.', 'error' );
            return;
        }

        self::log( 'Verifying Stripe return for order #' . $order->get_id() . '.' );

        // A $0 free-trial subscription order went through a SETUP-mode
        // session: there is no payment to verify, and the engine's payment
        // verifier keys off payment_status — which a setup session reports
        // as 'no_payment_required' from the moment it is created, completed
        // or not. Completion is proven by its SetupIntent instead.
        if ( (float) $order->get_total() < 0.01 && Subscriptions::order_contains_subscription( $order ) ) {
            self::verify_setup_return( $order, $keys['secret_key'] );
            return;
        }

        // Pass the id we just authorized against this order's own meta —
        // never let the engine re-read it from the request. It reads
        // $_REQUEST by default, and under PHP's request_order=GP a POST body
        // overwrites the query string, so the id checked above and the id
        // verified below would be different values. That gap also picks the
        // setup-session branch from THIS order's total while verifying a
        // DIFFERENT order's transaction: a $0 free-trial row routed through
        // the payment verifier is written 'no_payment_required' (what Stripe
        // reports for a setup session from creation, card entered or not),
        // which is_paid_status() treats as paid — activating a subscription
        // with no SetupIntent, exactly what verify_setup_return() prevents.
        $result = Handler::stripe_payment_success(
            array(
                'better_payment_stripe_secret_key' => $keys['secret_key'],
                'better_payment_stripe_id'         => $requested_id,
            )
        );

        if ( is_wp_error( $result ) ) {
            $order->add_order_note(
                sprintf(
                    /* translators: %s: error message */
                    __( 'Better Payment: Stripe verification could not be completed — %s. The payment may still confirm on a later visit to this page.', 'better-payment' ),
                    $result->get_error_message()
                )
            );
            self::log( 'Stripe verification failed for order #' . $order->get_id() . ': ' . $result->get_error_message(), 'error' );
            return;
        }

        if ( false === $result ) {
            // No 'unpaid' row matched: either already verified through
            // another visit, or the id didn't match a transaction.
            self::log( 'Stripe verification for order #' . $order->get_id() . ' found no pending transaction (already processed or unknown id).' );
            return;
        }

        self::log( 'Stripe payment verified for order #' . $order->get_id() . '.' );
    }

    /**
     * Verify the return from a SETUP-mode Checkout Session (free-trial
     * checkout: $0 order, card collected without a charge).
     *
     * The counterpart of Handler::stripe_payment_success() for sessions with
     * no payment: retrieves the session with its SetupIntent expanded and
     * treats a succeeded SetupIntent (a saved payment method) as completion.
     * On success the transaction row flips 'unpaid' → 'paid' (single-shot,
     * same guard the engine uses) and `better_payment/payment_confirmed`
     * fires — from there the standard pipeline completes the order and
     * activates the subscription.
     *
     * The row is written 'paid' and NOT Stripe's own 'no_payment_required',
     * for the same reason build_renewal_transaction_data() rewrites
     * 'succeeded': the admin transaction taxonomy (Classes\Helper's v2 map,
     * its JS twin, and the `status IN (…)` filter DB::get_transactions()
     * builds from them) classifies unknown statuses as "Incomplete". A row
     * absent from BOTH the completed and incomplete buckets is worse than
     * mislabelled — it is invisible under either filter tab while still
     * appearing on the unfiltered list. 'paid' is the exact status a verified
     * first payment writes, so a $0 checkout counts as completed everywhere
     * without every consumer needing to learn a second spelling.
     * ('no_payment_required' remains in PAID_STATUSES and in the taxonomy for
     * rows confirmed before 2.4.0, which stored Stripe's raw payment_status
     * for a $0 session. Handler::confirm_stripe_session() writes 'paid' for
     * those too now, for the same reason.)
     *
     * @param \WC_Order $order      The $0 subscription order.
     * @param string    $secret_key Stripe secret key.
     * @return void
     */
    public static function verify_setup_return( $order, $secret_key ) {
        $payment_id = (string) $order->get_meta( '_bp_payment_id' );

        if ( '' === $payment_id ) {
            return;
        }

        $row = Handler::get_stripe_row( 'order_id', $payment_id );

        if ( empty( $row->id ) || Handler::STRIPE_PENDING_STATUS !== $row->status || empty( $row->obj_id ) || 0 !== strpos( (string) $row->obj_id, 'cs_' ) ) {
            self::log( 'Setup verification for order #' . $order->get_id() . ' found no pending transaction (already processed or unknown id).' );
            return;
        }

        $session = StripeService::retrieve_checkout_session( (string) $row->obj_id, $secret_key, array( 'setup_intent' ) );

        if ( is_wp_error( $session ) ) {
            $order->add_order_note(
                sprintf(
                    /* translators: %s: error message */
                    __( 'Better Payment: Stripe verification could not be completed — %s. The payment may still confirm on a later visit to this page.', 'better-payment' ),
                    $session->get_error_message()
                )
            );
            self::log( 'Setup-session verification failed for order #' . $order->get_id() . ': ' . $session->get_error_message(), 'error' );
            return;
        }

        switch ( self::confirm_setup_session( $row, $session ) ) {
            case 'confirmed':
                self::log( 'Setup session verified for order #' . $order->get_id() . ' — payment method saved, no payment required; transaction recorded as paid.' );
                return;

            case 'open':
            case 'expired':
                self::log( 'Setup session for order #' . $order->get_id() . ' is not completed yet — no payment method was saved.', 'error' );
                return;

            case 'mismatch':
                self::log( 'Setup verification for order #' . $order->get_id() . ' retrieved a session that is not this transaction\'s setup session.', 'error' );
                return;

            default:
                // A parallel return (a reload, a second tab) won the claim.
                self::log( 'Setup verification for order #' . $order->get_id() . ' found the transaction already processed.' );
        }
    }

    /**
     * Confirm a pending $0 free-trial row from its retrieved setup-mode
     * session — the order-received page's claim. WooCommerce-free: it writes
     * the row and fires the engine hook; on_payment_confirmed() does the rest.
     *
     * The row flips 'unpaid' → 'paid' with ONE conditional update (see
     * verify_setup_return() for why 'paid'), storing the SetupIntent id as the
     * transaction id and the session as the customer info. Only the request
     * whose update matched fires `better_payment/payment_confirmed`; a return
     * that arrives second (a reload, a second tab) fires nothing.
     *
     * @param object|null $row     Pending row (`id`, `obj_id`).
     * @param mixed       $session Checkout Session retrieved with `setup_intent` expanded.
     * @return string 'confirmed' | 'settled' (no longer pending) | 'open' (no payment method saved
     *                yet) | 'expired' | 'mismatch' (not this row's setup session) | 'error'.
     */
    public static function confirm_setup_session( $row, $session ) {
        global $wpdb;

        if ( ! is_object( $row ) || empty( $row->id ) || empty( $row->obj_id ) ) {
            return 'mismatch';
        }

        $decision = self::setup_session_decision( (string) $row->obj_id, $session );

        if ( 'confirm' !== $decision ) {
            return 'not_setup' === $decision ? 'mismatch' : $decision;
        }

        $setup_intent = $session->setup_intent;
        $table        = $wpdb->prefix . 'better_payment';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single-shot flip, mirrors the engine verifier.
        $updated = $wpdb->update(
            $table,
            array(
                'status'         => 'paid',
                'transaction_id' => ! empty( $setup_intent->id ) ? sanitize_text_field( $setup_intent->id ) : '',
                'customer_info'  => maybe_serialize( $session ),
            ),
            array(
                'id'     => (int) $row->id,
                'status' => 'unpaid',
            )
        );

        if ( 1 !== (int) $updated ) {
            // A parallel return won the flip — nothing left to do.
            $current = Handler::get_stripe_row( 'id', (int) $row->id );

            if ( ! empty( $current->id ) && Handler::STRIPE_PENDING_STATUS !== $current->status ) {
                self::log( 'Setup session for transaction #' . (int) $row->id . ' was already settled when the return reached it.' );
                return 'settled';
            }

            return 'error';
        }

        do_action( 'better_payment/payment_confirmed', (int) $row->id );

        return 'confirmed';
    }

    /**
     * What a retrieved setup-mode Checkout Session means for its pending row. Pure.
     *
     * @param string $obj_id  The row's Checkout Session id.
     * @param mixed  $session Session retrieved with `setup_intent` expanded.
     * @return string 'confirm' | 'mismatch' (another session) | 'not_setup' (a payment session) |
     *                'expired' | 'open' (no payment method saved yet).
     */
    public static function setup_session_decision( $obj_id, $session ) {
        if ( ! is_object( $session ) || ( isset( $session->id ) && (string) $session->id !== (string) $obj_id ) ) {
            return 'mismatch';
        }

        if ( isset( $session->mode ) && 'setup' !== $session->mode ) {
            return 'not_setup';
        }

        if ( self::setup_intent_succeeded( $session ) ) {
            return 'confirm';
        }

        return isset( $session->status ) && 'expired' === $session->status ? 'expired' : 'open';
    }

    /**
     * Whether a setup session's (expanded) SetupIntent saved a payment method:
     * `succeeded`, or a payment method attached. Pure.
     *
     * @param mixed $session Session retrieved with `setup_intent` expanded.
     * @return bool
     */
    public static function setup_intent_succeeded( $session ) {
        $setup_intent = is_object( $session ) && ! empty( $session->setup_intent ) && is_object( $session->setup_intent ) ? $session->setup_intent : null;

        return null !== $setup_intent
            && ( ( isset( $setup_intent->status ) && 'succeeded' === $setup_intent->status ) || ! empty( $setup_intent->payment_method ) );
    }

    /**
     * Consume the engine's completion hook and mark the linked WooCommerce
     * order as paid. No-op for every transaction without a wc_order_id.
     *
     * @param int $transaction_row_id Better Payment table row id.
     * @return void
     */
    public static function on_payment_confirmed( $transaction_row_id ) {
        $row = DB::get_transaction( (int) $transaction_row_id );

        if ( empty( $row->id ) ) {
            return;
        }

        $wc_order_id = self::extract_wc_order_id( isset( $row->form_fields_info ) ? $row->form_fields_info : '' );

        if ( ! $wc_order_id ) {
            return; // Not a WooCommerce transaction — existing flows land here.
        }

        // Trust wc_order_id ONLY on rows this gateway created. Every other
        // surface hardcodes its own referer at insert time, and form-field
        // payloads on those surfaces are visitor-influenced — a custom form
        // field could otherwise smuggle a wc_order_id into an unrelated
        // (e.g. $1 widget) transaction and mark an arbitrary order paid.
        if ( ! isset( $row->referer ) || 'woocommerce' !== $row->referer ) {
            return;
        }

        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $wc_order_id );

        if ( ! $order ) {
            self::log( 'Payment confirmed for transaction #' . (int) $transaction_row_id . ' but WooCommerce order #' . $wc_order_id . ' was not found.', 'error' );
            return;
        }

        if ( ! $order->needs_payment() ) {
            return; // Duplicate confirmation — order already paid/cancelled.
        }

        if ( ! self::is_paid_status( isset( $row->status ) ? $row->status : '' ) ) {
            self::log( 'Payment confirmed hook fired for transaction #' . (int) $transaction_row_id . ' with non-paid status "' . ( isset( $row->status ) ? $row->status : '' ) . '" — order #' . $wc_order_id . ' left unchanged.', 'error' );
            return;
        }

        // The verified paid amount/currency must match the order — never
        // complete an order from a transaction that settled differently.
        $row_currency = isset( $row->currency ) ? strtoupper( (string) $row->currency ) : '';

        if ( ! self::amount_matches( isset( $row->amount ) ? $row->amount : 0, $order->get_total() ) || strtoupper( $order->get_currency() ) !== $row_currency ) {
            $order->update_status(
                'on-hold',
                sprintf(
                    /* translators: 1: paid amount, 2: paid currency, 3: order total, 4: order currency */
                    __( 'Better Payment: verified payment amount (%1$s %2$s) does not match the order total (%3$s %4$s). Order placed on hold for manual review.', 'better-payment' ),
                    isset( $row->amount ) ? $row->amount : '0',
                    $row_currency,
                    $order->get_total(),
                    $order->get_currency()
                )
            );
            self::log( 'Amount/currency mismatch for order #' . $wc_order_id . ' (transaction #' . (int) $transaction_row_id . '): paid ' . ( isset( $row->amount ) ? $row->amount : '0' ) . ' ' . $row_currency . ' vs order ' . $order->get_total() . ' ' . $order->get_currency() . '. Order set to on-hold.', 'error' );
            return;
        }

        $transaction_id = ! empty( $row->transaction_id ) ? sanitize_text_field( $row->transaction_id ) : '';

        $order->update_meta_data( '_bp_payment_status', sanitize_text_field( $row->status ) );
        $order->update_meta_data( '_bp_paid_at', current_time( 'mysql' ) );
        $order->add_order_note(
            sprintf(
                /* translators: 1: Stripe transaction id, 2: Better Payment record id */
                __( 'Better Payment: Stripe payment confirmed. Transaction ID: %1$s (Better Payment record #%2$d).', 'better-payment' ),
                $transaction_id ? $transaction_id : '—',
                (int) $transaction_row_id
            )
        );
        $order->payment_complete( $transaction_id );
        $order->save();

        self::log( 'WooCommerce order #' . $wc_order_id . ' marked paid from Better Payment transaction #' . (int) $transaction_row_id . '.' );

        /**
         * Fires after a WooCommerce order has been marked paid from a
         * Better Payment transaction.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $order The WooCommerce order.
         * @param object    $row   The Better Payment transaction row.
         */
        do_action( 'better_payment/woocommerce/payment_complete', $order, $row );
    }

    /**
     * Log through WooCommerce's logger (Better Payment core has no logging
     * service; inside a WooCommerce module the host logger is the sink).
     *
     * @param string $message Log message.
     * @param string $level   'info' or 'error'.
     * @return void
     */
    public static function log( $message, $level = 'info' ) {
        if ( ! function_exists( 'wc_get_logger' ) ) {
            return;
        }

        wc_get_logger()->log( $level, $message, array( 'source' => 'better-payment' ) );
    }
}
