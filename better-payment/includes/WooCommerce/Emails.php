<?php

namespace Better_Payment\Lite\WooCommerce;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Customer lifecycle emails for Better Payment subscriptions —
 * "subscription cancelled" and "subscription completed".
 *
 * Built from the standard WooCommerce email pattern (WC_Email subclass +
 * delayed send + reactivation guard), mapped onto Better Payment's own
 * statuses:
 *
 *   cancelled — the ONLY status with a reactivation route back to active
 *              (Subscriptions::reactivate() accepts cancelled/past_due), so
 *              its email is sent after a grace delay. Two guards keep a
 *              reactivated subscription from being told it is cancelled:
 *              subscription_reactivated unschedules the pending event, and
 *              the send callback re-checks the status is STILL 'cancelled'
 *              at send time — the re-check is the authoritative guard, the
 *              unschedule just avoids a pointless cron wake-up.
 *   completed — the subscription's schedule ended (the "expired" case in
 *              other subscription systems). No core flow completes a subscription
 *              any more (the renewal-cap feature was removed); the email
 *              remains for integrations that fire the action. Terminal with
 *              NO reactivation route, so the email sends immediately; a
 *              grace delay would protect nothing.
 *
 * The emails themselves are ordinary WC_Email registrations
 * (Emails\SubscriptionCancelledEmail / Emails\SubscriptionCompletedEmail),
 * so the shop owner enables/disables them and edits subject, heading and
 * additional content from WooCommerce → Settings → Emails — no Better
 * Payment settings are added. Both are enabled by default, like
 * WooCommerce's own customer emails.
 *
 * This registrar is deliberately WC-free (no WC_Email reference at parse
 * time) so the scheduling logic runs in the WooCommerce-less test
 * environment; only the email classes themselves extend WC_Email, and they
 * are instantiated solely inside the `woocommerce_email_classes` filter,
 * which only WooCommerce fires.
 *
 * Only loaded when WooCommerce is active (see Loader::register()).
 *
 * @since 2.4.0
 */
class Emails {

    /**
     * WC_Email ids (WooCommerce → Settings → Emails rows). Template file
     * names derive from these — see template_html()/template_plain().
     */
    const CANCELLED_EMAIL_ID = 'bp_subscription_cancelled';
    const COMPLETED_EMAIL_ID = 'bp_subscription_completed';

    /**
     * Single-event cron hook that sends the delayed cancelled email.
     * Args: array( int $order_id ) — the id must stay a plain int, because
     * unscheduling matches args by exact serialization.
     */
    const CANCELLED_SEND_HOOK = 'better_payment_woocommerce_send_cancelled_email';

    /**
     * Notification hooks the WC_Email classes bind their trigger() to.
     * Fired through notify() after WC()->mailer() has instantiated the
     * email classes.
     */
    const CANCELLED_NOTIFICATION = 'better_payment/woocommerce/subscription_cancelled_email_notification';
    const COMPLETED_NOTIFICATION = 'better_payment/woocommerce/subscription_completed_email_notification';

    /**
     * Wire the feature. Called from Loader::register(), i.e. only when
     * WooCommerce is active.
     *
     * @return void
     */
    public static function register() {
        add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_email_classes' ) );

        // Cancelled: schedule after a grace delay; reactivation unschedules.
        add_action( 'better_payment/woocommerce/subscription_cancelled', array( __CLASS__, 'schedule_cancelled_email' ) );
        add_action( 'better_payment/woocommerce/subscription_reactivated', array( __CLASS__, 'unschedule_cancelled_email' ) );
        add_action( self::CANCELLED_SEND_HOOK, array( __CLASS__, 'send_cancelled_email' ) );

        // Completed: terminal, no reactivation route — send immediately.
        add_action( 'better_payment/woocommerce/subscription_completed', array( __CLASS__, 'send_completed_email' ) );

        register_deactivation_hook( BETTER_PAYMENT_FILE, array( __CLASS__, 'unschedule_all' ) );
    }

    /**
     * Clear every pending delayed-email event on plugin deactivation.
     * wp_unschedule_hook() drops the hook's events regardless of args, so
     * per-order events don't linger pointing at an unloaded listener.
     *
     * @return void
     */
    public static function unschedule_all() {
        wp_unschedule_hook( self::CANCELLED_SEND_HOOK );
    }

    /**
     * Filter callback for `woocommerce_email_classes`: add the two
     * subscription lifecycle emails. The subclasses extend WC_Email, so
     * they are only instantiated here — inside WooCommerce's own mailer
     * bootstrap — never at module load.
     *
     * @param mixed $emails Registered email instances (id => WC_Email).
     * @return mixed
     */
    public static function register_email_classes( $emails ) {
        if ( ! is_array( $emails ) || ! class_exists( 'WC_Email' ) ) {
            return $emails;
        }

        $emails[ self::CANCELLED_EMAIL_ID ] = new Emails\SubscriptionCancelledEmail();
        $emails[ self::COMPLETED_EMAIL_ID ] = new Emails\SubscriptionCompletedEmail();

        return $emails;
    }

    /* ---------------------------------------------------------------------
     * Cancelled email — delayed send + reactivation guard
     * ------------------------------------------------------------------- */

    /**
     * The grace delay (seconds) between a subscription being cancelled and
     * the customer email going out. During the window a reactivation
     * silently discards the email. 0 (or negative) sends immediately.
     *
     * @return int
     */
    public static function cancelled_email_delay() {
        /**
         * Filters the delay before the "subscription cancelled" customer
         * email is sent. Return 0 to send immediately.
         *
         * @since 2.4.0
         *
         * @param int $delay Delay in seconds. Default HOUR_IN_SECONDS.
         */
        return (int) apply_filters( 'better_payment/woocommerce/cancelled_email_delay', HOUR_IN_SECONDS );
    }

    /**
     * Consume `better_payment/woocommerce/subscription_cancelled`: queue the
     * customer email after the grace delay. Re-cancelling (cancel →
     * reactivate → cancel) replaces any pending event rather than stacking
     * a second one.
     *
     * @param mixed $order Parent (subscription) order.
     * @return void
     */
    public static function schedule_cancelled_email( $order ) {
        $order_id = self::order_id( $order );

        if ( $order_id < 1 ) {
            return;
        }

        $delay = self::cancelled_email_delay();

        if ( $delay <= 0 ) {
            self::send_cancelled_email( $order_id );
            return;
        }

        // Dedupe: exactly one pending event per subscription.
        wp_clear_scheduled_hook( self::CANCELLED_SEND_HOOK, array( $order_id ) );
        wp_schedule_single_event( time() + $delay, self::CANCELLED_SEND_HOOK, array( $order_id ) );
    }

    /**
     * Consume `better_payment/woocommerce/subscription_reactivated`: the
     * subscription is live again — discard any pending cancelled email.
     *
     * @param mixed $order Parent (subscription) order.
     * @return void
     */
    public static function unschedule_cancelled_email( $order ) {
        $order_id = self::order_id( $order );

        if ( $order_id < 1 ) {
            return;
        }

        wp_clear_scheduled_hook( self::CANCELLED_SEND_HOOK, array( $order_id ) );
    }

    /**
     * Send the "subscription cancelled" email — the delayed cron callback
     * (and the immediate path when the delay is 0).
     *
     * The status re-check is the authoritative reactivation guard: even if
     * an unschedule was missed, a subscription that is no longer
     * 'cancelled' is never emailed about a cancellation.
     *
     * @param mixed $order_id Parent (subscription) order id.
     * @return void
     */
    public static function send_cancelled_email( $order_id ) {
        $order_id = absint( $order_id );

        if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( ! self::should_send( (string) $order->get_meta( Subscriptions::STATUS_META ), 'cancelled' ) ) {
            return;
        }

        self::notify( self::CANCELLED_NOTIFICATION, $order_id );
    }

    /* ---------------------------------------------------------------------
     * Completed email — immediate
     * ------------------------------------------------------------------- */

    /**
     * Consume `better_payment/woocommerce/subscription_completed`: the
     * subscription ended (fired by integrations only — no core flow
     * completes a subscription). Terminal with no reactivation route, so
     * the email goes out immediately — a delay would guard nothing.
     *
     * @param mixed $order Parent (subscription) order.
     * @return void
     */
    public static function send_completed_email( $order ) {
        $order_id = self::order_id( $order );

        if ( $order_id < 1 || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        self::notify( self::COMPLETED_NOTIFICATION, $order_id );
    }

    /**
     * Fire a notification hook with the mailer loaded. WC()->mailer()
     * instantiates every registered email class (running our
     * `woocommerce_email_classes` filter), whose constructors bind
     * trigger() to these hooks — without it the do_action falls on nothing.
     *
     * @param string $hook     Notification hook name.
     * @param int    $order_id Parent (subscription) order id.
     * @return void
     */
    protected static function notify( $hook, $order_id ) {
        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        WC()->mailer();

        do_action( $hook, $order_id );
    }

    /* ---------------------------------------------------------------------
     * Pure helpers (unit-tested without WooCommerce)
     * ------------------------------------------------------------------- */

    /**
     * Pure: the order id from a WC_Order-ish object or a numeric id.
     * Tolerant on purpose — the lifecycle hooks pass WC_Order, tests and
     * the cron path pass plain ints.
     *
     * @param mixed $order Order object or id.
     * @return int 0 when no id can be resolved.
     */
    public static function order_id( $order ) {
        if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
            return (int) $order->get_id();
        }

        if ( is_numeric( $order ) ) {
            return (int) $order;
        }

        return 0;
    }

    /**
     * Pure: whether a lifecycle email may still be sent — the subscription's
     * current status must be exactly the status the email announces. This is
     * what keeps a reactivated subscription from receiving a stale
     * "cancelled" email.
     *
     * @param string $current  Current `_bp_subscription_status` meta value.
     * @param string $required Status the email announces.
     * @return bool
     */
    public static function should_send( $current, $required ) {
        return '' !== (string) $required && (string) $current === (string) $required;
    }

    /**
     * Pure: the plugin template root the email templates resolve against.
     *
     * @return string
     */
    public static function template_base() {
        return ( defined( 'BETTER_PAYMENT_PATH' ) ? BETTER_PAYMENT_PATH : '' ) . 'templates/';
    }

    /**
     * Pure: HTML template path (relative to template_base()) for an email id.
     * Ids are underscored (`bp_subscription_cancelled`), files hyphenated
     * (`emails/bp-subscription-cancelled.php`) per WooCommerce convention.
     *
     * @param string $email_id WC_Email id.
     * @return string
     */
    public static function template_html( $email_id ) {
        return 'emails/' . str_replace( '_', '-', (string) $email_id ) . '.php';
    }

    /**
     * Pure: plain-text template path for an email id.
     *
     * @param string $email_id WC_Email id.
     * @return string
     */
    public static function template_plain( $email_id ) {
        return 'emails/plain/' . str_replace( '_', '-', (string) $email_id ) . '.php';
    }

    /**
     * The customer-facing URL for a subscription: the My Account →
     * Subscriptions single view when the tab is enabled, else WooCommerce's
     * own view-order page (which always exists for a logged-in customer).
     *
     * @param mixed $order Parent (subscription) order.
     * @return string '' when no URL can be built.
     */
    public static function subscription_view_url( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return '';
        }

        if ( 'yes' === Subscriptions::setting( 'myaccount_tab' ) ) {
            $url = (string) MyAccount::view_url( $order->get_id() );

            if ( '' !== $url ) {
                return $url;
            }
        }

        return (string) $order->get_view_order_url();
    }
}
