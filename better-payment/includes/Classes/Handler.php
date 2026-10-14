<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The plugin handler class
 * 
 * @since 0.0.1
 */
class Handler extends Controller{
    use TraitsHelper;

    /**
     * Status a Stripe Checkout Session order waits in until it is confirmed.
     */
    const STRIPE_PENDING_STATUS = 'unpaid';

    /**
     * Status a confirmed Checkout Session order is stored with. See confirm_stripe_session().
     */
    const STRIPE_CONFIRMED_STATUS = 'paid';

    /**
     * PayPal button
     * 
     * @since 0.0.1
     */
    public static function paypal_button( $widget_id = '' , $settings = [], $args = [] ) {
        global $wp;
        
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $paypal_button_text             = ! empty( $settings["better_payment_form_form_buttons_paypal_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_paypal_button_text"] ) : self::default_button_text( $args['button_text_default'] );

        $return_url = add_query_arg( $wp->query_vars, get_the_permalink() . '?better_payment_paypal_status=success&better_payment_widget_id=' . $widget_id );
        $cancel_url = add_query_arg( $wp->query_vars, get_the_permalink() . '?better_payment_error_status=error&better_payment_widget_id=' . $widget_id );

        ob_start();
        $error_info = [
             'business_email' => !empty($settings['better_payment_paypal_business_email'])
        ];
        ?>
        <input type="hidden" name="return" value="<?php echo esc_url( $return_url ); ?>">
        <input type="hidden" name="action" value="paypal_form_handle">
        <input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce('better-payment-paypal') ); ?>">
        <input type="hidden" name="cancel_return" value="<?php echo esc_url( $cancel_url ); ?>">
        <button data-paypal-info = <?php echo esc_attr(wp_json_encode( $error_info) ) ; ?> class="button is-medium is-fullwidth better-payment-paypal-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'paypal') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $paypal_button_text ); ?></button>
        
        <?php
        $paypal_button_html = ob_get_clean();
        $paypal_button_html = apply_filters( 'better_payment/elementor/editor/paypal_button_html', $paypal_button_html, $widget_id, $settings );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Every value in the markup above is escaped where it is printed (esc_url/esc_attr/esc_html); the hidden inputs would not survive wp_kses with bp_allowed_tags().
        echo $paypal_button_html;
    }

    /**
     * Stripe button
     * 
     * @since 0.0.1
     */
    public static function stripe_button( $widget_id = '' , $settings = [], $args = [] ) {
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $stripe_button_text             = ! empty( $settings["better_payment_form_form_buttons_stripe_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_stripe_button_text"] ) : self::default_button_text( $args['button_text_default'] );

        ob_start();
        ?>
        <button class="button is-medium is-fullwidth better-payment-stripe-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'stripe') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $stripe_button_text ); ?></button>
        <?php
        $stripe_button_html = ob_get_clean();
        $stripe_button_html = apply_filters( 'better_payment/elementor/editor/stripe_button_html', $stripe_button_html, $widget_id, $settings );

        echo wp_kses( $stripe_button_html,  (new Handler())->bp_allowed_tags() );
    }

    /**
     * Paystack button
     * 
     * @since 0.0.1
     */
    public static function paystack_button( $widget_id = '' , $settings = [], $args = [] ) {
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $paystack_button_text           = ! empty( $settings["better_payment_form_form_buttons_paystack_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_paystack_button_text"] ) : self::default_button_text( $args['button_text_default'] );
        
        ob_start();
        ?>
        <button class="button is-medium is-fullwidth better-payment-paystack-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'paystack') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $paystack_button_text ); ?></button>
        <?php
        $paystack_button_html = ob_get_clean();
        $paystack_button_html = apply_filters( 'better_payment/elementor/editor/paystack_button_html', $paystack_button_html, $widget_id, $settings );

        echo wp_kses( $paystack_button_html,  (new Handler())->bp_allowed_tags() );
    }

    /**
     * The page PayPal returns the payer to when the posted return URL is not on
     * this site: the form's page, with the arguments paypal_button() would have
     * posted, so manage_response() still shows the thank-you or error message.
     *
     * @since 2.4.2
     *
     * @param string|false $page_url  Permalink of the form's page.
     * @param string       $widget_id Widget or block id.
     * @param string       $outcome   'success' or 'error'.
     * @return string
     */
    public static function paypal_return_fallback( $page_url, $widget_id, $outcome ) {
        $args = 'success' === $outcome
            ? array( 'better_payment_paypal_status' => 'success' )
            : array( 'better_payment_error_status' => 'error' );

        $args['better_payment_widget_id'] = $widget_id;

        return add_query_arg( $args, (string) $page_url );
    }

    /**
     * The transaction status shown in the notification e-mail, translated.
     *
     * The template used to translate the value at render time
     * (`esc_html_e( $status )`), which Plugin Check rejects because a variable
     * cannot be a msgid. The known statuses are translated here as literals
     * instead; any other status prints as before, with its first letter
     * capitalised.
     *
     * @since 2.4.2
     *
     * @param string $status Stored transaction status.
     * @return string
     */
    public static function email_status_label( $status ) {
        $labels = array(
            'paid'       => __( 'Paid', 'better-payment' ),
            'unpaid'     => __( 'Unpaid', 'better-payment' ),
            'completed'  => __( 'Completed', 'better-payment' ),
            'success'    => __( 'Success', 'better-payment' ),
            'pending'    => __( 'Pending', 'better-payment' ),
            'refunded'   => __( 'Refunded', 'better-payment' ),
            'failed'     => __( 'Failed', 'better-payment' ),
            'cancelled'  => __( 'Cancelled', 'better-payment' ),
            'incomplete' => __( 'Incomplete', 'better-payment' ),
        );

        $key = strtolower( (string) $status );

        return isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( (string) $status );
    }

    /**
     * The translated fallback label for a payment button.
     *
     * The two defaults callers pass (Lite's own, and Pro's donation layout) are translated as
     * literals; any other caller-supplied text is shown as given.
     *
     * @param string $text Default button text, already sanitized.
     * @return string
     */
    private static function default_button_text( $text ) {
        if ( 'Proceed to Payment' === $text ) {
            return __( 'Proceed to Payment', 'better-payment' );
        }

        if ( 'Proceed to Donate' === $text ) {
            return __( 'Proceed to Donate', 'better-payment' );
        }

        return $text;
    }

    public static function hide_show_payment_button_class( $settings, $type = 'paypal' ){
        $button_hidden_class = 'is-hidden';
        $types = ['paypal', 'stripe', 'paystack'];
        
        if( ! in_array($type, $types) ){
            return $button_hidden_class;
        }

        $is_type_enable = ! empty( $settings[ "better_payment_form_{$type}_enable" ] ) && 'yes' === $settings[ "better_payment_form_{$type}_enable" ];
        
        if( ! $is_type_enable ){
            return $button_hidden_class;
        }
        
        $is_paypal_enable = ! empty( $settings[ "better_payment_form_paypal_enable" ] ) && 'yes' === $settings[ "better_payment_form_paypal_enable" ];
        $is_stripe_enable = ! empty( $settings[ "better_payment_form_stripe_enable" ] ) && 'yes' === $settings[ "better_payment_form_stripe_enable" ];
        $is_paystack_enable = ! empty( $settings[ "better_payment_form_paystack_enable" ] ) && 'yes' === $settings[ "better_payment_form_paystack_enable" ];
        
        switch( $type ){
            case 'paypal':
                $button_hidden_class = ''; 
                break;

            case 'stripe':
                if( ( ! $is_paypal_enable ) ){
                    $button_hidden_class = ''; 
                }
                
                break;

            case 'paystack':
                if( ( ! $is_paypal_enable ) && ( ! $is_stripe_enable ) ){
                    $button_hidden_class = ''; 
                }
                break;

            default:
                break;
        }

        return $button_hidden_class;
    }

    /**
     * Payment create in db
     *
     * @since 0.0.1
     */
    public static function payment_create( $data ) {
        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom transactions table; no WP API for it.
        $wpdb->insert( $table, self::normalize_pending_stripe_status( $data ) );
        if ( $wpdb->insert_id ) {
            return $wpdb->insert_id;
        } else {
            return false;
        }
    }

    /**
     * A Stripe Checkout Session order is always created pending.
     *
     * Every Stripe surface stores the session's `payment_status` at creation. For a session with
     * nothing to charge right now — a subscription trial, a 100%-off promotion code, setup mode —
     * Stripe already reports `no_payment_required` before anyone has completed the checkout. Stored
     * as is, the order could never be confirmed (confirmation claims it from `unpaid`), so the trial
     * or free donation never reached the thank-you page, its receipts or the campaign's donors.
     * Only a Checkout Session row (`obj_id` `cs_…`) is touched: a renewal PaymentIntent row is
     * written after its charge and keeps its status.
     *
     * @since 2.4.0
     *
     * @param mixed $data Row data for payment_create().
     * @return mixed
     */
    public static function normalize_pending_stripe_status( $data ) {
        if ( ! is_array( $data ) || ! isset( $data['status'], $data['obj_id'] ) || ! is_string( $data['obj_id'] ) ) {
            return $data;
        }

        if ( 'no_payment_required' === $data['status'] && 0 === strpos( $data['obj_id'], 'cs_' ) ) {
            $data['status'] = self::STRIPE_PENDING_STATUS;
        }

        return $data;
    }

    /**
     * A new, unguessable order id: `{gateway}_{24 hex chars}`.
     *
     * The order id is the only key the public return pages and the PayPal status poll look
     * an order up by. uniqid() is the current time in microseconds, so ids created around a
     * known moment could be enumerated to read other visitors' order status and receipts.
     * 96 random bits cannot be. Fits the 50-char `order_id` column with room to spare.
     *
     * @since 2.3.4
     *
     * @param string $gateway 'paypal' | 'stripe' | 'paystack'.
     * @return string
     */
    public static function new_order_id( $gateway ) {
        try {
            $random = bin2hex( random_bytes( 12 ) );
        } catch ( \Exception $e ) {
            $random = strtolower( wp_generate_password( 24, false ) );
        }

        return sanitize_key( $gateway ) . '_' . $random;
    }

    /**
     * Mamage response
     * 
     * @since 0.0.1
     */
    public static function manage_response( $settings = [], $widget_id = '' ) {
        $status = false;

        //if widget id is different then return false
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        if ( !empty( $_REQUEST[ 'better_payment_widget_id' ] ) && $_REQUEST[ 'better_payment_widget_id' ] !== $widget_id ) {
            return $status;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        if ( !empty( $_REQUEST[ 'better_payment_error_status' ] ) ) {
            // Determine order_id based on payment method
            $order_id = '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
            if ( !empty( $_REQUEST['better_payment_stripe_id'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
                $order_id = sanitize_text_field( wp_unslash( $_REQUEST['better_payment_stripe_id'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
            } elseif ( !empty( $_REQUEST['better_payment_paystack_id'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
                $order_id = sanitize_text_field( wp_unslash( $_REQUEST['better_payment_paystack_id'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
            } elseif ( !empty( $_REQUEST['better_payment_paypal_id'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
                $order_id = sanitize_text_field( wp_unslash( $_REQUEST['better_payment_paypal_id'] ) );
            }
            
            self::render_failed_response( $settings, $order_id );

            return $status;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        if ( !empty( $_REQUEST[ 'better_payment_paypal_status' ] ) && $_REQUEST[ 'better_payment_paypal_status' ] == 'success' ) {
            $status = self::paypal_payment_success( $settings );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        } elseif ( !empty( $_REQUEST[ 'better_payment_stripe_status' ] ) && $_REQUEST[ 'better_payment_stripe_status' ] == 'success' ) {
            $status = self::stripe_payment_success( $settings );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        } elseif ( !empty( $_REQUEST[ 'better_payment_paystack_status' ] ) && $_REQUEST[ 'better_payment_paystack_status' ] == 'success' ) {
            $status = self::paystack_payment_success( $settings );
        }

        if ( is_wp_error( $status ) ) {
            // A verification that confirmed no payment never renders the success page or
            // redirects to the success URL (a WP_Error is truthy, and used to do both).
            if ( 'bp_stripe_payment_processing' === $status->get_error_code() ) {
                self::stripe_processing_message_template();

                // The return URL keeps its query args, so reloading it verifies the order again.
                return $status;
            }

            if ( 'bp_stripe_payment_incomplete' === $status->get_error_code() ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
                $stripe_order_id = ! empty( $_REQUEST['better_payment_stripe_id'] ) && is_scalar( $_REQUEST['better_payment_stripe_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['better_payment_stripe_id'] ) ) : '';

                self::render_failed_response( $settings, $stripe_order_id );

                // Like a cancelled checkout: the form renders again under the notice.
                $status = false;
            }
        } elseif ( ! empty( $status['pending'] ) ) {
            // Use home_url() instead of HTTP_HOST to avoid open-redirect via forged Host headers.
            $return_url = home_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
            // PayPal uses rm=2 (POST redirect), so item_number arrives in $_POST and is absent from the URL.
            // Add it explicitly so the poll-complete redirect (a plain GET) still carries item_number.
            if ( ! empty( $status['order_id'] ) && strpos( $return_url, 'item_number=' ) === false ) {
                $return_url = add_query_arg( 'item_number', $status['order_id'], $return_url );
            }
            ?>
            <section class="bp-thank_page">
                <div class="bp-thank_page-wrapper bp-paypal-pending"
                     data-order-id="<?php echo esc_attr( $status['order_id'] ); ?>"
                     data-return-url="<?php echo esc_url( $return_url ); ?>">

                    <div class="bp-thank_page-logo">
                        <span class="bp-paypal-pending__spinner-wrap">
                            <span class="bp-paypal-pending__ring"></span>
                            <img class="bp-paypal-pending__logo"
                                 src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/paypal-2.svg' ); ?>"
                                 alt="PayPal">
                        </span>
                    </div>

                    <div class="bp-thank_page-text bp-paypal-pending__body">
                        <h2 class="bp-font_ibm bp-page_header bp-paypal-pending__heading">
                            <?php esc_html_e( 'Verifying your payment…', 'better-payment' ); ?>
                        </h2>
                        <p class="bp-font_ibm bp-payment_info bp-paypal-pending__sub">
                            <?php esc_html_e( 'Please do not close this page. This usually takes a few seconds.', 'better-payment' ); ?>
                        </p>
                        <div class="bp-paypal-pending__dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>

                    <div class="bp-paypal-pending__bar">
                        <div class="bp-paypal-pending__bar-inner"></div>
                    </div>

                </div>
            </section>
            <?php
        } elseif ( $status ) {
            self::success_message_template( $settings, $status );
            $redirection_url_success = ! empty( $settings['better_payment_form_success_page_url']['url'] ) ? esc_url( $settings['better_payment_form_success_page_url']['url'] ) : '';

            if( $redirection_url_success ){
                ?>
                <script>
                    setTimeout(function(){
                        window.location.replace("<?php echo esc_url( $redirection_url_success ); ?>");
                    }, 2000);
                </script>
                <?php
            }
        }
        self::remove_arg();

        return $status;
    }

    /**
     * Paypal payment success
     *
     * The DB write happens exclusively in handle_paypal_ipn() after PayPal verifies
     * the IPN post-back. This method is read-only: it never writes to the DB.
     *
     * Display data priority:
     *   1. DB row (IPN already fired and confirmed) — authoritative.
     *   2. PayPal return-URL params ($_REQUEST) — for display only when IPN hasn't
     *      fired yet (local dev, race condition). Safe because no DB write occurs here.
     *
     * @since 0.0.1
     */
    public static function paypal_payment_success( $settings = [] ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- PayPal return URL cannot carry a nonce; read-only display, the order is looked up by its unguessable id and nothing is written here.
        $item_number = ! empty( $_REQUEST['item_number'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['item_number'] ) ) : '';

        if ( empty( $item_number ) ) {
            return false;
        }

        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                "SELECT transaction_id, status, email, amount, currency FROM $table WHERE order_id = %s LIMIT 1",
                $item_number
            )
        );

        // No matching order — reject (prevents fake success display with forged item_number).
        if ( empty( $row ) ) {
            return false;
        }

        // IPN confirmed — return verified DB data. The payer's e-mail and the PayPal transaction
        // id only for an unguessable order id: before 2.4.0 ids were `paypal_` . uniqid(), which is
        // time-based and can be enumerated, so anyone could read them off the return page.
        if ( $row->status === 'Completed' ) {
            $unguessable = self::paypal_order_id_is_unguessable( $item_number );

            return [
                'amount'         => floatval( $row->amount ),
                'email'          => $unguessable ? sanitize_email( $row->email ) : '',
                'transaction_id' => $unguessable ? sanitize_text_field( $row->transaction_id ) : '',
                'currency'       => sanitize_text_field( $row->currency ),
                'method'         => 'paypal',
            ];
        }

        // Order exists but IPN hasn't arrived yet — signal pending so JS can poll.
        return [ 'pending' => true, 'order_id' => $item_number ];
    }

    /**
     * Whether a PayPal order id came from new_order_id() (24 random characters), not the
     * enumerable `paypal_` . uniqid() of releases before 2.4.0.
     *
     * @since 2.4.1
     *
     * @param mixed $order_id The order id.
     * @return bool
     */
    public static function paypal_order_id_is_unguessable( $order_id ) {
        return is_string( $order_id ) && 1 === preg_match( '/^paypal_[0-9a-z]{24}$/', $order_id );
    }

    /**
     * Whether an order id of any gateway came from new_order_id() (`{gateway}_` + 24 random
     * characters), not the enumerable `{gateway}_` . uniqid() of releases before 2.4.0.
     *
     * @since 2.4.1
     *
     * @param mixed $order_id The order id.
     * @return bool
     */
    public static function order_id_is_unguessable( $order_id ) {
        return is_string( $order_id ) && 1 === preg_match( '/^[a-z0-9-]+_[0-9a-z]{24}$/', $order_id );
    }

    /**
     * AJAX: check whether a PayPal order has been confirmed by IPN.
     *
     * Called by the frontend polling loop after the user lands on the return URL
     * before the IPN fires. Returns {status:'pending'} or {status:'completed'}.
     *
     * Lives on Handler (not Actions) so it can be registered unconditionally —
     * PayPal payments flow through the block/campaign path without Elementor.
     */
    public static function check_paypal_status() {
        check_ajax_referer( 'better-payment', 'security' );

        $order_id = ! empty( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';

        if ( empty( $order_id ) ) {
            wp_send_json_error( [ 'message' => 'Missing order_id' ] );
        }

        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                "SELECT status FROM $table WHERE order_id = %s LIMIT 1",
                $order_id
            )
        );

        if ( empty( $row ) ) {
            wp_send_json_error( [ 'message' => 'Order not found' ] );
        }

        if ( $row->status === 'Completed' ) {
            wp_send_json_success( [ 'status' => 'completed' ] );
        }

        wp_send_json_success( [ 'status' => 'pending' ] );
    }

    /**
     * PayPal IPN listener.
     *
     * PayPal POSTs the raw IPN body here server-to-server. We echo it back with
     * cmd=_notify-validate; only a VERIFIED response from PayPal's servers reaches
     * apply_paypal_ipn(), the only writer. The browser return URL
     * (paypal_payment_success) never writes.
     *
     * @since 2.1.3
     */
    public static function handle_paypal_ipn() {
        $raw_post = file_get_contents( 'php://input' );

        if ( empty( $raw_post ) ) {
            status_header( 400 );
            exit;
        }

        $data = [];
        wp_parse_str( $raw_post, $data );

        // PayPal includes test_ipn=1 for sandbox notifications.
        $verify_url = ! empty( $data['test_ipn'] )
            ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.paypal.com/cgi-bin/webscr';

        $response = wp_remote_post(
            $verify_url,
            [
                'body'      => 'cmd=_notify-validate&' . $raw_post,
                'timeout'   => 30,
                'sslverify' => true,
                'headers'   => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
            ]
        );

        // Always respond 200 to PayPal to prevent retries, but only act on VERIFIED.
        if ( is_wp_error( $response ) || wp_remote_retrieve_body( $response ) !== 'VERIFIED' ) {
            status_header( 200 );
            exit;
        }

        self::apply_paypal_ipn( $data );

        status_header( 200 );
        exit;
    }

    /**
     * Whether a notification's PayPal environment matches the order's.
     *
     * Orders since 2.4.1 record `paypal_mode` (`live` | `sandbox`) when created, and must match
     * exactly. An older order has no record, so a live notification is accepted (a real payment
     * must never be stranded) and a sandbox one only while the site's PayPal setting is not live.
     *
     * @since 2.4.1
     *
     * @param mixed  $form_info The order's `form_fields_info`, unserialized.
     * @param string $ipn_mode  `live` or `sandbox` (from the notification's `test_ipn`).
     * @return bool
     */
    public static function paypal_ipn_mode_matches( $form_info, $ipn_mode ) {
        $order_mode = is_array( $form_info ) && isset( $form_info['paypal_mode'] ) ? $form_info['paypal_mode'] : '';

        if ( 'live' === $order_mode || 'sandbox' === $order_mode ) {
            return $order_mode === $ipn_mode;
        }

        if ( 'live' === $ipn_mode ) {
            return true;
        }

        return 'yes' !== DB::get_settings( 'better_payment_settings_payment_paypal_live_mode' );
    }

    /**
     * Apply a PayPal IPN to the order it names.
     *
     * Call ONLY with a notification handle_paypal_ipn() posted back and PayPal answered VERIFIED —
     * nothing here authenticates the data. Changes nothing unless the notification is a
     * `Completed` payment for a pending order (`status IS NULL`) paid to the merchant's address,
     * for at least the order's amount, in its currency, under a transaction id no order holds.
     * Then the row is claimed with one update (`WHERE id = … AND status IS NULL`): PayPal redelivers
     * a notification it saw no 200 for and can deliver one twice at once, and only the delivery that
     * wins the claim sends the receipts.
     *
     * @since 2.4.0
     *
     * @param array $data The notification's fields.
     * @return string 'confirmed' | 'ignored' | 'unmatched' | 'refused' | 'duplicate'
     */
    public static function apply_paypal_ipn( array $data ) {
        $data = array_map( 'sanitize_text_field', $data );

        if ( empty( $data['payment_status'] ) || empty( $data['item_number'] ) || $data['payment_status'] !== 'Completed' ) {
            return 'ignored';
        }

        global $wpdb;
        $table   = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $results = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                "SELECT id, amount, currency, form_fields_info, referer FROM $table WHERE order_id = %s AND status IS NULL LIMIT 1",
                $data['item_number']
            )
        );

        if ( empty( $results->id ) ) {
            return 'unmatched';
        }

        // Validate receiver_email matches the merchant's configured PayPal email.
        $form_info      = maybe_unserialize( $results->form_fields_info );
        $expected_email = ! empty( $form_info['paypal_business_email'] ) ? strtolower( $form_info['paypal_business_email'] ) : '';
        $receiver       = ! empty( $data['receiver_email'] ) ? strtolower( $data['receiver_email'] ) : ( ! empty( $data['business'] ) ? strtolower( $data['business'] ) : '' );

        if ( $expected_email && $receiver && $expected_email !== $receiver ) {
            return 'refused';
        }

        // A sandbox notification is VERIFIED by PayPal's sandbox, which pays in play money to an
        // account anyone can create under any address. It must never confirm a live order.
        $ipn_mode = ! empty( $data['test_ipn'] ) ? 'sandbox' : 'live';

        if ( ! self::paypal_ipn_mode_matches( $form_info, $ipn_mode ) ) {
            return 'refused';
        }

        // Validate the paid amount is not less than the expected order amount.
        $paid_amount = ! empty( $data['mc_gross'] ) ? floatval( $data['mc_gross'] ) : 0;
        if ( $paid_amount < floatval( $results->amount ) ) {
            return 'refused';
        }

        // Validate currency matches.
        $paid_currency = ! empty( $data['mc_currency'] ) ? strtoupper( $data['mc_currency'] ) : '';
        if ( $paid_currency && $results->currency && strtoupper( $results->currency ) !== $paid_currency ) {
            return 'refused';
        }

        $txn_id = ! empty( $data['txn_id'] ) ? $data['txn_id'] : '';

        // Prevent txn_id replay: reject if this transaction ID was already recorded.
        if ( $txn_id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                    "SELECT id FROM $table WHERE transaction_id = %s LIMIT 1",
                    $txn_id
                )
            );
            if ( $existing ) {
                return 'duplicate';
            }
        }

        $payer_email = ! empty( $data['payer_email'] ) ? sanitize_email( $data['payer_email'] ) : '';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $claimed = $wpdb->update(
            $table,
            [
                'transaction_id' => $txn_id,
                'status'         => $data['payment_status'],
                'email'          => $payer_email,
                'customer_info'  => maybe_serialize( $data ),
            ],
            [
                'id'     => (int) $results->id,
                'status' => null,
            ]
        );

        if ( 1 !== (int) $claimed ) {
            // A concurrent delivery of this notification confirmed the order first.
            return 'duplicate';
        }

        $is_elementor_form = ! empty( $results->referer ) && $results->referer === 'elementor-form' ? 1 : 0;

        // Rebuild the e-mail settings persisted at payment-create time. IPN runs
        // server-to-server with no live widget context, so without this the body
        // would collapse to the bare transaction id (every section flag defaults
        // to 0). Legacy / pending rows created before this fix fall back to [].
        $email_settings = ! empty( $form_info['email_settings'] ) && is_array( $form_info['email_settings'] )
            ? $form_info['email_settings']
            : [];

        // Mirror the Stripe / Paystack gate: honour the form's "Send Email" toggle,
        // and always e-mail for Elementor Pro Form submissions.
        if (
            ( isset( $email_settings['better_payment_form_email_enable'] ) && 'yes' === $email_settings['better_payment_form_email_enable'] )
            || $is_elementor_form
        ) {
            self::better_email_notification( $txn_id, $payer_email, $email_settings, 'PayPal', $results->form_fields_info, $is_elementor_form );
        }

        return 'confirmed';
    }

    /**
     * Stripe payment success — the return page.
     *
     * Reads the transaction id from the request, EXCEPT when the caller
     * supplies `better_payment_stripe_id` in $settings. A caller that has
     * already authorized the id against server-side state (the WooCommerce
     * return handler matches it to the order's own `_bp_payment_id`) must
     * pass it explicitly: a caller-side guard that reads $_GET cannot bind
     * this lookup, because PHP's default request_order=GP lets a POST body
     * overwrite the query string and the two would then see different ids.
     *
     * A pending order is verified against Stripe and confirmed through
     * confirm_stripe_session(). An order an earlier return (a reload, a second
     * tab) already confirmed renders the success page again without asking
     * Stripe or re-running anything. See stripe_settled_return().
     *
     * @since 0.0.1
     *
     * @param array $settings Form settings; `better_payment_stripe_secret_key` is required to verify.
     * @return array|false|\WP_Error Success data, false for no order to show, or why nothing was confirmed.
     */
    public static function stripe_payment_success( $settings = [] ) {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        $data = $_REQUEST;

        if ( ! empty( $settings[ 'better_payment_stripe_id' ] ) ) {
            $data[ 'better_payment_stripe_id' ] = $settings[ 'better_payment_stripe_id' ];
        }

        if ( empty( $data['better_payment_stripe_id'] ) || ! is_scalar( $data['better_payment_stripe_id'] ) ) {
            return false;
        }

        $order_id = sanitize_text_field( (string) $data['better_payment_stripe_id'] );
        $row      = self::get_stripe_row( 'order_id', $order_id );

        if ( empty( $row->id ) ) {
            return false;
        }

        if ( self::STRIPE_PENDING_STATUS !== $row->status ) {
            return self::stripe_settled_return( $row, $order_id );
        }

        if ( empty( $row->obj_id ) || empty( $settings[ 'better_payment_stripe_secret_key' ] ) ) {
            return false;
        }

        // Subscription and split-payment orders are Pro's: Pro (or, for an older Pro, Lite's
        // transitional copy) adds the subscription expansion and the recurring / split flags.
        $verification = self::stripe_verification_for( $row );

        $response = wp_safe_remote_post(
            'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode( (string) $row->obj_id ),
            array(
                'method'  => 'GET',
                'headers' => array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $settings[ 'better_payment_stripe_secret_key' ] ) . ':' ),
                    'Stripe-Version' => '2019-05-16',
                ),
                'timeout' => 70,
                'body'    => array(
                    'expand' => $verification['expand'],
                ),
            )
        );

        if ( is_wp_error( $response ) || empty( $response[ 'body' ] ) ) {
            return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
        }

        $session = json_decode( $response[ 'body' ] );

        if ( ! is_object( $session ) || isset( $session->error ) ) {
            return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
        }

        return self::confirm_stripe_session( $row, $session, $settings, $verification );
    }

    /**
     * Confirm a pending Stripe order from its verified Checkout Session.
     *
     * The one place a Stripe payment is confirmed: the return page. In order:
     *
     * 1. The session must be this row's (when it carries an id), must not be a setup-mode session
     *    (the WooCommerce free-trial checkout, which OrderHandler confirms by its SetupIntent), and
     *    must be paid (stripe_session_is_paid()). Nothing below runs for a session that is not: an
     *    abandoned checkout, or a completed one whose delayed payment has not cleared (a later
     *    return confirms it once it has).
     * 2. Pro's verified-session step, the customer e-mail and the charged amount are computed. The
     *    amount is `amount_total / 100` whenever Stripe reports one — 0 for a 100%-off promotion
     *    code or a trial — and the form's amount is kept as `original_amount` when they differ.
     *    Pro's step must stay free of side effects: a request that loses the claim has asked it too.
     * 3. The row is claimed with ONE update, `WHERE id = … AND status = 'unpaid'`, which writes the
     *    confirmed status, amount, e-mail, session and Pro's details together. However many returns
     *    race for a session (a reload, a second tab), exactly one update matches.
     * 4. Only that winner fires `better_payment/stripe_payment/success` and
     *    `better_payment/payment_confirmed` and sends the receipts. A caller that lost the claim to a
     *    confirmation gets the same success data back, so the visitor still sees the thank-you page.
     *
     * The confirmed status is always `paid`, including for a session Stripe reports as
     * `no_payment_required`: campaign totals and donor counts, Pro's Donors Wall and the WooCommerce
     * bridge count `paid`, and a $0 donation is still a donation. Stripe's own value stays in
     * `customer_info`.
     *
     * @since 2.4.0
     *
     * @param object|null $row         Pending row from get_stripe_row() (null is refused).
     * @param mixed       $session     Decoded Checkout Session, with $verification['expand'] expanded where Pro needs it (anything but an object is refused).
     * @param array|null $settings     Form settings for the receipts; null or [] for none.
     * @param array|null $verification stripe_verification_for( $row ); computed when null.
     * @return array|\WP_Error Success data for the thank-you page, or why nothing was confirmed.
     */
    public static function confirm_stripe_session( $row, $session, $settings = null, $verification = null ) {
        global $wpdb;

        $table    = "{$wpdb->prefix}better_payment";
        $settings = is_array( $settings ) ? $settings : [];

        if ( ! is_object( $row ) || empty( $row->id ) || ! is_object( $session ) ) {
            return new \WP_Error( 'bp_stripe_confirm_invalid', __( 'There is no Stripe payment to confirm.', 'better-payment' ) );
        }

        if ( isset( $session->id ) && ( empty( $row->obj_id ) || (string) $session->id !== (string) $row->obj_id ) ) {
            return new \WP_Error( 'bp_stripe_session_mismatch', __( 'Stripe did not confirm a payment for this checkout.', 'better-payment' ) );
        }

        if ( isset( $session->mode ) && 'setup' === $session->mode ) {
            return new \WP_Error( 'bp_stripe_setup_session', __( 'This checkout saves a payment method and is confirmed by its order.', 'better-payment' ) );
        }

        $stored_fields    = isset( $row->form_fields_info ) ? maybe_unserialize( $row->form_fields_info ) : [];
        $form_fields_info = is_array( $stored_fields ) ? $stored_fields : [];

        if ( ! is_array( $verification ) || ! isset( $verification['expand'] ) ) {
            $verification = self::stripe_verification_for( $row );
        }

        // Stripe returns the session whether or not anyone paid for it. Nothing below —
        // Pro's verified-session step, the row update, the confirmation hooks, the e-mails,
        // the success page — may run for a session that is not complete and paid. The row
        // stays 'unpaid', so a later return can still confirm it.
        if ( ! self::stripe_session_is_paid( $session ) ) {
            return self::stripe_unpaid_session_error( $session );
        }

        $action_data = [
            'form_fields_info' => $form_fields_info,
            'is_payment_recurring' => $verification['is_payment_recurring'],
            'is_payment_split_payment' => $verification['is_payment_split_payment'],
            'checkout_session_id' => $row->obj_id,
        ];

        // A subscription's id, customer e-mail and details, and the thank-you page's
        // recurring / split flags, are Pro's to add.
        $verified = PaymentEntitlement::stripe_verified_session(
            [
                'form_fields_info' => $form_fields_info,
                'transaction_id'   => ! empty( $row->transaction_id ) ? sanitize_text_field( $row->transaction_id ) : '',
                'email'            => self::stripe_session_customer_email( $session ),
                'frontend_data'    => [],
            ],
            $session,
            $verification
        );

        $confirmed_fields = is_array( $verified['form_fields_info'] ) ? $verified['form_fields_info'] : $form_fields_info;
        $transaction_id   = $verified['transaction_id'];
        $customer_email   = $verified['email'];
        $amounts          = self::stripe_paid_amounts( $session, isset( $row->amount ) ? $row->amount : 0, $confirmed_fields );
        $paid_amount      = $amounts['amount'];
        $confirmed_fields = $amounts['form_fields_info'];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $claimed = $wpdb->update(
            $table,
            array(
                'amount'           => $paid_amount,
                'status'           => self::STRIPE_CONFIRMED_STATUS,
                'email'            => $customer_email,
                'customer_info'    => maybe_serialize( $session ),
                'form_fields_info' => maybe_serialize( $confirmed_fields ),
                'transaction_id'   => $transaction_id,
            ),
            array(
                'id'     => (int) $row->id,
                'status' => self::STRIPE_PENDING_STATUS,
            )
        );

        if ( 1 !== (int) $claimed ) {
            // Another return (a reload, a second tab) got here first (or the write failed).
            // Whatever it recorded is the outcome; nothing is fired or sent twice.
            $current = self::get_stripe_row( 'id', (int) $row->id );

            if ( ! empty( $current->id ) && self::STRIPE_CONFIRMED_STATUS === $current->status ) {
                return self::stripe_frontend_data( $current, $verified['frontend_data'] );
            }

            return new \WP_Error( 'bp_stripe_confirm_failed', __( 'The payment could not be recorded. Please reload this page.', 'better-payment' ) );
        }

        do_action('better_payment/stripe_payment/success', $action_data);
        do_action( 'better_payment/payment_confirmed', (int) $row->id );

        $frontend_data = [
            'amount' => $paid_amount,
            'email' => $customer_email,
            'transaction_id' => $transaction_id,
            'currency' => ! empty( $row->currency ) ? $row->currency : __( 'USD', 'better-payment' ),
            'method' => 'stripe',
        ] + $verified['frontend_data'];

        //Send email notification
        if (
            ( isset( $settings[ 'better_payment_form_email_enable' ] ) && 'yes' === $settings[ 'better_payment_form_email_enable' ] )
            || ( isset( $row->referer ) && 'elementor-form' === $row->referer )
        ) {
            $is_elementor_form = isset( $row->referer ) && 'elementor-form' === $row->referer ? 1 : 0;

            self::better_email_notification( $transaction_id, $customer_email, $settings, 'Stripe', $row->form_fields_info, $is_elementor_form );
        }

        return $frontend_data;
    }

    /**
     * A Stripe transaction row, looked up by `id`, `order_id` or `obj_id` (the Checkout Session id).
     *
     * @since 2.4.0
     *
     * @param string     $field 'id' | 'order_id' | 'obj_id'.
     * @param mixed      $value Value to match; anything but a non-empty scalar matches nothing.
     * @return object|null
     */
    public static function get_stripe_row( $field, $value ) {
        global $wpdb;

        if ( ! in_array( $field, [ 'id', 'order_id', 'obj_id' ], true ) || ! is_scalar( $value ) || '' === (string) $value ) {
            return null;
        }

        $table  = "{$wpdb->prefix}better_payment";
        $format = 'id' === $field ? '%d' : '%s';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb->prefix, column and format (the %d/%s placeholder) from the whitelist above.
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT id, order_id, obj_id, amount, transaction_id, currency, email, status, source, referer, form_fields_info, payment_date FROM $table WHERE $field = $format LIMIT 1", $value ) );

        return is_object( $row ) ? $row : null;
    }

    /**
     * How a row's Checkout Session is retrieved and whether it is a subscription — Pro's answer.
     *
     * @since 2.4.0
     *
     * @param object|null $row Transaction row; null reads as a row with no stored fields.
     * @return array `expand`, `is_payment_recurring`, `is_payment_split_payment`.
     */
    public static function stripe_verification_for( $row ) {
        $form_fields_info = is_object( $row ) && isset( $row->form_fields_info ) ? maybe_unserialize( $row->form_fields_info ) : [];

        return PaymentEntitlement::stripe_verification_args(
            [
                'expand'                   => [ 'payment_intent' ],
                'is_payment_recurring'     => false,
                'is_payment_split_payment' => false,
            ],
            is_array( $form_fields_info ) ? $form_fields_info : []
        );
    }

    /**
     * What a verified Checkout Session charged, and what the transaction's fields record about it.
     *
     * The charged amount is `amount_total / 100` whenever Stripe reports a numeric total — 0
     * included: a 100%-off promotion code or a trial charges nothing, and a row claiming otherwise
     * would add money nobody paid to a campaign's total. It used to fall back to the form's amount
     * whenever the total was 0. Only a session with no total at all keeps the row's amount.
     *
     * `exact_paid_amount` is always recorded. When the charge differs from the amount the order was
     * created for, that amount is kept as `original_amount`; `is_coupon_applied` and
     * `paid_amount_diff` (read by the transaction details and the receipt) are set only when a
     * discount explains the difference — a trial is not a coupon.
     *
     * @since 2.4.0
     *
     * @param mixed $session          Decoded Checkout Session.
     * @param mixed $row_amount       The row's amount (what the order was created for).
     * @param array $form_fields_info The fields to record into.
     * @return array `amount` (float) and `form_fields_info`.
     */
    public static function stripe_paid_amounts( $session, $row_amount, array $form_fields_info ) {
        $original = is_numeric( $row_amount ) ? (float) $row_amount : 0.0;
        $total    = is_object( $session ) && isset( $session->amount_total ) && is_numeric( $session->amount_total ) ? (float) $session->amount_total : null;
        $paid     = null === $total ? $original : $total / 100;

        $form_fields_info['is_coupon_applied'] = 0;
        $form_fields_info['exact_paid_amount'] = $paid;
        unset( $form_fields_info['original_amount'], $form_fields_info['paid_amount_diff'] );

        if ( (int) round( $paid * 100 ) !== (int) round( $original * 100 ) ) {
            $form_fields_info['original_amount'] = $original;

            $discounted = ( is_object( $session ) && isset( $session->total_details->amount_discount ) && is_numeric( $session->total_details->amount_discount ) && (float) $session->total_details->amount_discount > 0 )
                || ( isset( $form_fields_info['stripe_coupon_code'] ) && is_scalar( $form_fields_info['stripe_coupon_code'] ) && '' !== trim( (string) $form_fields_info['stripe_coupon_code'] ) );

            if ( $discounted ) {
                $form_fields_info['paid_amount_diff']  = $original - $paid;
                $form_fields_info['is_coupon_applied'] = 1;
            }
        }

        return [
            'amount'           => $paid,
            'form_fields_info' => $form_fields_info,
        ];
    }

    /**
     * The customer's e-mail on a Checkout Session: the charge's billing e-mail when the PaymentIntent
     * is expanded, else the session's `customer_details` / `customer_email`.
     *
     * @since 2.4.0
     *
     * @param mixed $session Decoded Checkout Session.
     * @return string
     */
    public static function stripe_session_customer_email( $session ) {
        if ( ! is_object( $session ) ) {
            return '';
        }

        if ( isset( $session->payment_intent->charges->data ) && is_array( $session->payment_intent->charges->data ) && ! empty( $session->payment_intent->charges->data ) ) {
            $charge = current( $session->payment_intent->charges->data );

            if ( isset( $charge->billing_details->email ) && is_string( $charge->billing_details->email ) && '' !== $charge->billing_details->email ) {
                return sanitize_email( $charge->billing_details->email );
            }
        }

        if ( isset( $session->customer_details->email ) && is_string( $session->customer_details->email ) && '' !== $session->customer_details->email ) {
            return sanitize_email( $session->customer_details->email );
        }

        if ( isset( $session->customer_email ) && is_string( $session->customer_email ) ) {
            return sanitize_email( $session->customer_email );
        }

        return '';
    }

    /**
     * Whether an order id is one Handler::new_order_id() minted for Stripe.
     *
     * A return for an order that is already confirmed shows its receipt from the row, by id alone.
     * Orders from before 2.3.4 carry a time-based id that could be enumerated, so only an
     * unguessable one gets that page.
     *
     * @since 2.4.0
     *
     * @param mixed $order_id Order id.
     * @return bool
     */
    public static function stripe_order_id_is_unguessable( $order_id ) {
        return is_string( $order_id ) && 1 === preg_match( '/^stripe_[0-9a-f]{24}$/', $order_id );
    }

    /**
     * The return page for an order that is no longer pending.
     *
     * - Confirmed (`paid`): the success data, rebuilt from the row — no Stripe call, no hook, no
     *   e-mail — with Pro's thank-you page flags. Only for an unguessable order id.
     * - Anything else (refunded, an older release's statuses): nothing, as before.
     *
     * @param object $row      The row.
     * @param string $order_id The order id the return names.
     * @return array|false
     */
    private static function stripe_settled_return( $row, $order_id ) {
        if ( self::STRIPE_CONFIRMED_STATUS !== $row->status || ! self::stripe_order_id_is_unguessable( $order_id ) ) {
            return false;
        }

        $form_fields_info = isset( $row->form_fields_info ) ? maybe_unserialize( $row->form_fields_info ) : [];

        // Only the thank-you page flags are used, and Pro derives them from the stored fields.
        $details = PaymentEntitlement::stripe_verified_session(
            [
                'form_fields_info' => is_array( $form_fields_info ) ? $form_fields_info : [],
                'transaction_id'   => (string) $row->transaction_id,
                'email'            => (string) $row->email,
                'frontend_data'    => [],
            ],
            new \stdClass(),
            self::stripe_verification_for( $row )
        );

        return self::stripe_frontend_data( $row, $details['frontend_data'] );
    }

    /**
     * Success data for a confirmed Stripe row.
     *
     * @param object $row   Confirmed row.
     * @param array  $extra Pro's thank-you page flags.
     * @return array
     */
    private static function stripe_frontend_data( $row, array $extra = [] ) {
        return [
            'amount'         => (float) $row->amount,
            'email'          => sanitize_email( (string) $row->email ),
            'transaction_id' => sanitize_text_field( (string) $row->transaction_id ),
            'currency'       => ! empty( $row->currency ) ? sanitize_text_field( $row->currency ) : __( 'USD', 'better-payment' ),
            'method'         => 'stripe',
        ] + $extra;
    }

    /**
     * Whether a retrieved Stripe Checkout Session has really been paid for.
     *
     * Retrieving a session proves only that it exists. A visitor can come back to the return
     * URL from a checkout they abandoned (`status` is `open`, or `expired`), and a completed
     * checkout paid with a delayed method (bank debits, vouchers) is `complete` with
     * `payment_status: unpaid` until the money arrives — sometimes days later, sometimes never.
     * Both used to confirm the order: the row, `better_payment/payment_confirmed`, Pro's
     * payment types, the receipts and the success page.
     *
     * Paid means `payment_status` is `paid` (the funds are there), or `no_payment_required`
     * (a 100%-off promotion code, a subscription trial) on a `complete` session: Stripe also
     * reports `no_payment_required` for such a session before it is completed. A present
     * `status` other than `complete` is never paid. A missing `status` is tolerated only for
     * `paid` — requests pin `Stripe-Version: 2019-05-16`, and `paid` is only ever reported for
     * a completed checkout — so a response without the field cannot stall every payment.
     * Anything else missing or unrecognised is unpaid.
     *
     * @since 2.4.0
     *
     * @param mixed $session Decoded Checkout Session.
     * @return bool
     */
    public static function stripe_session_is_paid( $session ) {
        if ( ! is_object( $session ) ) {
            return false;
        }

        $status         = isset( $session->status ) && is_string( $session->status ) ? $session->status : '';
        $payment_status = isset( $session->payment_status ) && is_string( $session->payment_status ) ? $session->payment_status : '';

        if ( 'paid' === $payment_status ) {
            return '' === $status || 'complete' === $status;
        }

        return 'no_payment_required' === $payment_status && 'complete' === $status;
    }

    /**
     * Why a Stripe return did not confirm its order.
     *
     * `bp_stripe_payment_processing`: the checkout was completed but Stripe has not been paid
     * yet (a delayed payment method). `bp_stripe_payment_incomplete`: anything else — the
     * checkout was never completed. The message is shown in WooCommerce order notes.
     *
     * @since 2.4.0
     *
     * @param mixed $session Decoded Checkout Session.
     * @return \WP_Error
     */
    public static function stripe_unpaid_session_error( $session ) {
        $status         = is_object( $session ) && isset( $session->status ) && is_string( $session->status ) ? $session->status : '';
        $payment_status = is_object( $session ) && isset( $session->payment_status ) && is_string( $session->payment_status ) ? $session->payment_status : '';
        $data           = [
            'status'         => sanitize_text_field( $status ),
            'payment_status' => sanitize_text_field( $payment_status ),
        ];

        if ( 'complete' === $status && 'unpaid' === $payment_status ) {
            return new \WP_Error( 'bp_stripe_payment_processing', __( 'Stripe has not confirmed this payment yet. It is confirmed once the payment clears.', 'better-payment' ), $data );
        }

        return new \WP_Error( 'bp_stripe_payment_incomplete', __( 'Stripe did not confirm a payment for this checkout.', 'better-payment' ), $data );
    }

    /**
     * The return page for a completed Stripe checkout whose payment has not cleared yet.
     *
     * Deliberately neither the success page (nothing has been paid) nor the failure page (the
     * payment may still succeed).
     *
     * @since 2.4.0
     *
     * @return void
     */
    private static function stripe_processing_message_template() {
        ?>
        <section class="bp-thank_page">
            <div class="bp-thank_page-wrapper bp-stripe-processing">
                <div class="bp-thank_page-text">
                    <h2 class="bp-font_ibm bp-page_header"><?php esc_html_e( 'Your payment is processing', 'better-payment' ); ?></h2>
                    <p class="bp-font_ibm bp-payment_info"><?php esc_html_e( 'Stripe has not confirmed your payment yet. Some payment methods take a few days to clear. You do not need to pay again; reload this page later to see your confirmation.', 'better-payment' ); ?></p>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * The failed-payment page, with the optional redirect to the form's error page.
     *
     * @param array  $settings Form settings.
     * @param string $order_id The order the return is for.
     * @return void
     */
    private static function render_failed_response( $settings, $order_id ) {
        self::failed_message_template( $settings, self::get_failed_transaction_data( $order_id ) );

        $redirection_url_error = ! empty( $settings['better_payment_form_error_page_url']['url'] ) ? esc_url( $settings['better_payment_form_error_page_url']['url'] ) : '';

        if ( $redirection_url_error ) {
            ?>
            <script>
                setTimeout(function(){
                    window.location.replace("<?php echo esc_url( $redirection_url_error ); ?>");
                }, 2000);
            </script>
            <?php
        }
    }

    /**
     * Paystack payment success
     *
     * Verifies a pending order against Paystack, then claims it with one update
     * (`WHERE id = … AND status = 'unpaid'`). Only the request that wins the claim fires
     * `better_payment/payment_confirmed`, sends the receipts and fires
     * `better_payment/payment/success`, so a reload, a double-click or two tabs returning at once
     * confirm the order exactly once. A request that loses the claim to a confirmation, and a later
     * return for an order that is already confirmed, render the success page from the row — only
     * when the return's `reference` is the one that order holds.
     *
     * @since 0.0.1
     */
    public static function paystack_payment_success( $settings = [] ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Gateway return URL (PayPal/Stripe/Paystack redirect back) cannot carry a nonce; the order is verified server-side against the gateway and the DB row.
        $data = $_REQUEST;

        if ( empty( $data[ 'better_payment_paystack_id' ] ) || ! is_scalar( $data[ 'better_payment_paystack_id' ] ) ) {
            return false;
        }

        if ( empty( $data['reference'] ) || ! is_scalar( $data['reference'] ) ) {
            return false;
        }

        global $wpdb;
        $table     = "{$wpdb->prefix}better_payment";
        $order_id  = sanitize_text_field( (string) $data[ 'better_payment_paystack_id' ] );
        $reference = sanitize_text_field( (string) $data['reference'] );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $results   = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
            $wpdb->prepare( "SELECT id,obj_id,transaction_id,amount,currency,email,status,form_fields_info,referer FROM $table WHERE order_id=%s limit 1", $order_id )
        );

        if ( empty( $results->id ) ) {
            return false;
        }

        if ( 'unpaid' !== $results->status ) {
            return self::paystack_settled_return( $results, $reference );
        }

        $header_info = array(
            'Authorization'  => 'Bearer ' . sanitize_text_field( isset( $settings[ 'better_payment_paystack_secret_key' ] ) ? $settings[ 'better_payment_paystack_secret_key' ] : '' ),
            "Cache-Control: no-cache",
        );

        $response = wp_safe_remote_get(
            'https://api.paystack.co/transaction/verify/' . rawurlencode( $reference ),
            array(
                'headers' => $header_info,
                'timeout' => 70,
            )
        );

        $response = json_decode(wp_remote_retrieve_body($response));

        if ( empty( $response->status ) ) {
            return false;
        }

        $verified = isset( $response->data ) ? $response->data : null;

        if ( ! self::paystack_verification_matches( $results, $verified )
            || self::paystack_reference_claimed_elsewhere( $verified->reference, (int) $results->id ) ) {
            return false;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $claimed = $wpdb->update(
            $table,
            array(
                'status'        => ! empty( $response->data->status ) ? sanitize_text_field( $response->data->status ) : '',
                'amount'        => ! empty( $response->data->amount ) ? floatval( ( $response->data->amount ) / 100 ) : 0,
                'obj_id'        => ! empty( $response->data->id ) ? sanitize_text_field( $response->data->id ) : '',
                'transaction_id' => ! empty( $response->data->reference ) ? sanitize_text_field( $response->data->reference ) : '',
                'customer_info' => ! empty( $response->data->customer ) ? maybe_serialize( $response->data->customer ) : '',
                'email'         => ! empty( $response->data->customer->email ) ? sanitize_email( $response->data->customer->email ) : '',
            ),
            array(
                'id'     => (int) $results->id,
                'status' => 'unpaid',
            )
        );

        if ( 1 !== (int) $claimed ) {
            // Another return confirmed this order first (or the write failed): nothing is fired or sent twice.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
            $current = $wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                $wpdb->prepare( "SELECT id,transaction_id,amount,currency,email,status FROM $table WHERE id=%d limit 1", (int) $results->id )
            );

            return ! empty( $current->id ) ? self::paystack_settled_return( $current, $reference ) : false;
        }

        do_action( 'better_payment/payment_confirmed', (int) $results->id );

        $better_customer_email = ! empty( $response->data->customer->email ) ? maybe_serialize( $response->data->customer->email ) : '';
        $transaction_id = ! empty( $response->data->reference ) ? sanitize_text_field( $response->data->reference ) : '';

        $frontend_data = [
            'amount' => ! empty( $response->data->amount ) ? floatval( ( $response->data->amount ) / 100 ) : 0,
            'email' => ! empty( $better_customer_email ) ? $better_customer_email : '',
            'transaction_id' => $transaction_id,
            'currency' => ! empty( $response->data->currency ) ? sanitize_text_field( $response->data->currency ) : __( 'USD', 'better-payment' ),
            'method' => 'paystack',
        ];

        //Send email notification
        if (
            $better_customer_email
            && (
                ( isset( $settings[ 'better_payment_form_email_enable' ] ) && $settings[ 'better_payment_form_email_enable' ] == 'yes' )
                || ( $results->referer === 'elementor-form' )
            )
        ) {
            $is_elementor_form = ! empty( $results->referer ) && $results->referer === 'elementor-form'  ? 1 : 0;
            self::better_email_notification($transaction_id, $better_customer_email, $settings, 'Paystack', $results->form_fields_info, $is_elementor_form);
        }

        do_action( 'better_payment/payment/success', $order_id, (int) $results->id, 'paystack' );

        return $frontend_data;
    }

    /**
     * The return page for a Paystack order that is no longer pending.
     *
     * A confirmed order (`success`) shows its success page again, from the row, when the return
     * carries the reference the order holds — the order id alone is not enough to see a receipt.
     * Anything else shows nothing, as before.
     *
     * @param object $row       The row.
     * @param string $reference The return's `reference`.
     * @return array|false
     */
    private static function paystack_settled_return( $row, $reference ) {
        $stored = isset( $row->transaction_id ) ? (string) $row->transaction_id : '';

        if ( ! isset( $row->status ) || 'success' !== $row->status || '' === $stored || ! hash_equals( $stored, (string) $reference ) ) {
            return false;
        }

        return [
            'amount'         => (float) $row->amount,
            'email'          => sanitize_email( (string) $row->email ),
            'transaction_id' => sanitize_text_field( $stored ),
            'currency'       => ! empty( $row->currency ) ? sanitize_text_field( $row->currency ) : __( 'USD', 'better-payment' ),
            'method'         => 'paystack',
        ];
    }

    /**
     * Whether a verified Paystack transaction really pays for the pending order.
     *
     * The verify call looks up whatever `reference` the return URL carries, and the return
     * URL is the visitor's to edit. Without this, a cheap paid transaction could be replayed
     * against an expensive pending order and mark it paid. Checked, in order:
     * - the transaction itself succeeded. Paystack's top-level `status: true` only says the
     *   reference resolved — it is just as true for an abandoned or failed checkout, which is
     *   how a visitor who paid nothing used to reach the success page;
     * - the reference is the one Paystack issued for this order at initialize (rows created
     *   before references were stored have none, and fall through to the amount check —
     *   paystack_reference_claimed_elsewhere() keeps that fallback from being replayable);
     * - the amount paid covers the amount the order was created for — which
     *   PaymentRequestGuard has already held to what the form allows;
     * - the currency is the order's.
     *
     * @param object $row      Pending row: transaction_id, amount, currency.
     * @param mixed  $verified `data` object from Paystack's /transaction/verify response.
     * @return bool
     */
    private static function paystack_verification_matches( $row, $verified ) {
        if ( ! is_object( $verified ) ) {
            return false;
        }

        if ( ! isset( $verified->status ) || 'success' !== $verified->status ) {
            return false;
        }

        $expected_reference = isset( $row->transaction_id ) ? (string) $row->transaction_id : '';
        $verified_reference = isset( $verified->reference ) ? (string) $verified->reference : '';

        if ( '' !== $expected_reference && $verified_reference !== $expected_reference ) {
            return false;
        }

        $paid = isset( $verified->amount ) && is_numeric( $verified->amount ) ? floatval( $verified->amount ) / 100 : 0.0;

        if ( $paid + 0.005 < floatval( isset( $row->amount ) ? $row->amount : 0 ) ) {
            return false;
        }

        $paid_currency  = isset( $verified->currency ) ? strtoupper( (string) $verified->currency ) : '';
        $order_currency = isset( $row->currency ) ? strtoupper( (string) $row->currency ) : '';

        return '' === $paid_currency || '' === $order_currency || $paid_currency === $order_currency;
    }

    /**
     * Whether a Paystack reference already belongs to a different order.
     *
     * Every order created since references were stored holds its own reference from
     * initialize, so this only ever decides the legacy fallback — an order with no stored
     * reference. Without it, one genuine payment could be replayed to mark every such
     * order paid. A reference no order has claimed can still settle exactly one of them.
     *
     * @param mixed $reference Verified reference.
     * @param int   $row_id    The order being verified.
     * @return bool True when the reference must not settle this order.
     */
    private static function paystack_reference_claimed_elsewhere( $reference, $row_id ) {
        global $wpdb;

        $reference = is_scalar( $reference ) ? sanitize_text_field( (string) $reference ) : '';

        if ( '' === $reference ) {
            return true;
        }

        $table = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table name from $wpdb->prefix.
        $other = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE transaction_id = %s AND id <> %d LIMIT 1", $reference, (int) $row_id ) );

        return ! empty( $other );
    }

    /**
     * Success message template
     * 
     * @since 0.0.1
     */
    public static function success_message_template( $settings = [], $tr_id = '' ) {
        if ( empty( $tr_id ) || is_wp_error($tr_id)) {
            return false;
        }
        wp_enqueue_script('better-payment-admin-script');
        $image_url = BETTER_PAYMENT_ASSETS . '/img/success.svg';
        $show_icon = 0;
        $default_icon = 1;
        $helper_obj = new Helper();

        $store_name = ( isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && !empty( $settings['better_payment_form_title'] ) ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo('name') );

        $payment_desc_text = !empty( $settings['better_payment_form_success_message_heading'] ) 
            ? esc_html($settings['better_payment_form_success_message_heading']) 
            : esc_html( __('You paid', 'better-payment') . ' [currency_symbol][amount] ' . __('to', 'better-payment') . ' [store_name]');

        $bp__currency_symbol = esc_html( ! empty( $tr_id['currency'] ) ? $helper_obj->get_currency_symbol( $tr_id['currency'] ) : '' );

        $payment_desc_text = str_replace( '[currency_symbol]', '<span class="bp-bold">' . $bp__currency_symbol . '</span>', $payment_desc_text );

        $payment_desc_text = str_replace( '[amount]', '<span class="bp-bold">' . esc_html( ! empty( $tr_id['amount'] ) ? $tr_id['amount'] : 0 ) . '</span>', $payment_desc_text );
        
        $payment_desc_text = str_replace( '[store_name]', '<span class="bp-bold">' . esc_html( $store_name ) . '</span>', $payment_desc_text );

        $payment_mail_text = !empty( $settings['better_payment_form_success_message_sub_heading'] ) ? esc_html($settings['better_payment_form_success_message_sub_heading']) : esc_html( __('Payment Confirmation email will be sent to ', 'better-payment') . '[customer_email]');

        $payment_mail_text = str_replace( '[customer_email]', '<span class="bp-bold">' . esc_html( ! empty( $tr_id['email'] ) ? $tr_id['email'] : '' ) . '</span>', $payment_mail_text );

        if ( !empty( $settings[ 'better_payment_form_success_message_icon' ][ 'library' ] ) ) {
            if ( $settings[ 'better_payment_form_success_message_icon' ][ 'library' ] == 'svg' ) {
                $image_url = $settings[ 'better_payment_form_success_message_icon' ][ 'value' ][ 'url' ];
                $default_icon = 0;
            } else {
                $show_icon = 1;
                $image_url = $settings[ 'better_payment_form_success_message_icon' ][ 'value' ];
                $default_icon = 0;
            }
        }


        $allowed_payment_methods = [
            'paypal' => BETTER_PAYMENT_ASSETS . '/img/paypal-2.svg',
            'stripe' => BETTER_PAYMENT_ASSETS . '/img/stripe-2.svg',
            'paystack' => BETTER_PAYMENT_ASSETS . '/img/paystack-2.svg',
        ];
        $payment_method_logo = isset( $tr_id['method'] ) && isset( $allowed_payment_methods[ $tr_id['method'] ] ) ? $allowed_payment_methods[ $tr_id['method'] ] : ''; 

        $better_payment_form_success_message_thanks = !empty( $settings['better_payment_form_success_message_thanks'] ) 
            ? esc_html( $settings['better_payment_form_success_message_thanks'] ) 
            : esc_html__('Thank You!', 'better-payment');
        $better_payment_form_success_message_transaction = !empty( $settings['better_payment_form_success_message_transaction'] ) 
            ? esc_html( $settings['better_payment_form_success_message_transaction'] ) 
            : esc_html__('Transaction ID', 'better-payment');
        $better_payment_form_success_message_amount_text = !empty( $settings['better_payment_form_success_message_amount_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_amount_text'] ) 
            : esc_html__( 'Amount', 'better-payment' );
        $better_payment_form_success_message_currency_text = !empty( $settings['better_payment_form_success_message_currency_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_currency_text'] ) 
            : esc_html__( 'Currency', 'better-payment' );
        $better_payment_form_success_message_pay_method_text = !empty( $settings['better_payment_form_success_message_pay_method_text'] ) ? esc_html( $settings['better_payment_form_success_message_pay_method_text'] ) : esc_html__( 'Payment Method', 'better-payment' );
        // Rows only Pro's payment types add: Payment Type, and a split payment's "Paid Amount … out of …".
        $pro_rows_after_payment_method   = PaymentEntitlement::success_rows( 'after_payment_method', $settings, $tr_id, [ 'currency_symbol' => $bp__currency_symbol ] );
        $pro_rows_after_merchant_details = PaymentEntitlement::success_rows( 'after_merchant_details', $settings, $tr_id, [ 'currency_symbol' => $bp__currency_symbol ] );
        $better_payment_form_success_message_merchant_details_text = !empty( $settings['better_payment_form_success_message_merchant_details_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_merchant_details_text'] ) 
            : esc_html__( 'Merchant Details', 'better-payment' );
        $better_payment_form_success_message_merchant_details_value = isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && ! empty( $settings['better_payment_form_title'] ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo( 'name' ) );
        $better_payment_form_success_message_purchase_details_text = !empty( $settings['better_payment_form_success_message_purchase_details_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_purchase_details_text'] ) 
            : esc_html__( 'Purchase Details', 'better-payment' );
        $better_payment_form_success_message_print_text = !empty( $settings['better_payment_form_success_message_print_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_print_text'] ) 
            : esc_html__( 'Print', 'better-payment' );
        $better_payment_form_success_message_view_details_btn_text = !empty( $settings['better_payment_form_success_message_view_details_btn_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_view_details_btn_text'] ) 
            : esc_html__( 'View Details', 'better-payment' );
        $better_payment_form_success_page_view_details_url = !empty( $settings['better_payment_form_success_page_view_details_url']['url'] ) ? esc_url( $settings['better_payment_form_success_page_view_details_url']['url'] ) : 'javascript:void(0)';
        ?>
        <section class="bp-thank_page">
            <div class="bp-thank_page-wrapper">
                <div class="bp-thank_page-logo">
                    <?php if( $default_icon ): ?>
                        <span class="bp-thank_page-logo_wrapper">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/success-2.svg' ); ?>" alt="Better Payment logo">
                        </span>
                    <?php endif; ?>
                    <?php if( ! $default_icon ): ?>
                        <?php if( $show_icon ): ?>
                        <?php
                            // Add 'pf-block' class if this is a Gutenberg block (not Elementor)
                            $success_wrapper_class = 'bp-thank_page-custom-logo_wrapper-success';
                            if ( ! empty( $settings['better_payment_form_source'] ) && 'block' === $settings['better_payment_form_source'] ) {
                                $success_wrapper_class .= ' pf-block';
                            }
                        ?>
                        <div class="<?php echo esc_attr( $success_wrapper_class ); ?>">
                            <?php
                                // Render icon safely (handles FontAwesome, Dashicons, SVG with or without Elementor)
                                if (!empty($settings['better_payment_form_success_message_icon'])) {
                                    self::render_icon($settings['better_payment_form_success_message_icon'], ['aria-hidden' => 'true']);
                                } else {
                                    // Fallback to default icons
                                    echo '<i class="' . esc_attr($image_url) . '"></i>';
                                }
                            ?>
                        </div>
                        <?php endif; ?>
                        <?php if( ! $show_icon ): ?>
                            <span class="bp-thank_page-logo_wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="Success logo">
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="bp-thank_page-text">
                    <h2 class="bp-font_ibm bp-page_header">
                        <?php echo wp_kses_post( $better_payment_form_success_message_thanks ); ?>
                        <span> &#127881;</span>
                    </h2>
                    <p class="bp-font_ibm bp-reason_for-text">
                        <?php echo wp_kses_post( $payment_desc_text ); ?>
                    </p>
                    <?php if (isset( $settings['better_payment_form_email_enable'] ) && !empty( $tr_id['email'] )): ?>
                        <p class="bp-font_ibm bp-payment_info">
                            <?php echo wp_kses_post( $payment_mail_text ); ?>
                        </p>
                    <?php endif; ?>

                    <div class="bp-flex bp-transaction_info-wrapper">
                        <p class="bp-flex bp-font_ibm  bp-transaction_info"> <span class="line-height-0">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/wallet.svg' ); ?>" alt="Better Payment wallet"> 
                            </span>
                            <span>
                                <?php echo wp_kses_post( $better_payment_form_success_message_transaction ); ?>: 
                            </span>
                            <span class="bp-bold bp-transaction_id"><?php echo esc_html( ! empty( $tr_id['transaction_id']) ? $tr_id['transaction_id'] : '' ); ?></span>
                        </p>
                    
                        <button class="bp-transaction_data-copy_btn ">
                            <svg width="20" height="20" viewBox="0 0 20 20"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M15.0002 15.601C16.3257 15.601 17.4002 14.5265 17.4002 13.201V8.40098C17.4002 5.38399 17.4002 3.87549 16.463 2.93823C15.5257 2.00098 14.0172 2.00098 11.0002 2.00098H7.80024C6.47476 2.00098 5.40024 3.07549 5.40024 4.40098M10.2002 18.001H7.80024C5.5375 18.001 4.40613 18.001 3.70319 17.298C3.00024 16.5951 3.00024 15.4637 3.00024 13.201V9.20098C3.00024 6.93823 3.00024 5.80686 3.70319 5.10392C4.40613 4.40098 5.5375 4.40098 7.80024 4.40098H10.2002C12.463 4.40098 13.5944 4.40098 14.2973 5.10392C15.0002 5.80686 15.0002 6.93824 15.0002 9.20098V13.201C15.0002 15.4637 15.0002 16.5951 14.2973 17.298C13.5944 18.001 12.463 18.001 10.2002 18.001Z"
                                stroke-width="1.152" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bp-thank_page-info">
                    <ul class="bp-page_info-list">
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_amount_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold"><?php echo $bp__currency_symbol . esc_html( ! empty( $tr_id['amount'] ) ? $tr_id['amount'] : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $bp__currency_symbol is esc_html()'d where it is built. ?></span>
                        </li>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class=" bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_currency_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold"><?php echo esc_html( ! empty( $tr_id['currency'] ) ? $tr_id['currency'] : '' ); ?></span>
                        </li>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_pay_method_text );
                                ?>
                            </span>
                            <div class="bp-payment_method-logo-wrapper">
                                <?php if ( !empty( $payment_method_logo ) ) : ?>
                                    <img src="<?php echo esc_url( $payment_method_logo ); ?>" alt="<?php echo esc_attr( $tr_id['method'] ); ?>" class="bp-payment_method-logo">
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php foreach ( $pro_rows_after_payment_method as $pro_row ) : ?>
                            <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                                <span class="bp-info_content">
                                    <?php echo wp_kses_post( $pro_row['label'] ); ?>
                                </span>
                                <span class="bp-info_content  bp-bold"><?php echo wp_kses_post( $pro_row['value'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_merchant_details_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold">
                                <?php echo wp_kses_post( $better_payment_form_success_message_merchant_details_value );  ?>
                            </span>
                        </li>
                        <?php foreach ( $pro_rows_after_merchant_details as $pro_row ) : ?>
                            <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                                <span class="bp-info_content">
                                    <?php echo wp_kses_post( $pro_row['label'] ); ?>
                                </span>
                                <span class="bp-info_content  bp-bold"><?php echo wp_kses_post( $pro_row['value'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php
                        $has_woo_products = !empty($settings['better_payment_form_woocommerce_product_id']) || !empty($settings['better_payment_form_woocommerce_product_ids']);
                        $has_fluentcart_products = !empty($settings['better_payment_form_fluentcart_product_id']) || !empty($settings['better_payment_form_fluentcart_product_ids']);

                        if( $has_woo_products || $has_fluentcart_products ):
                        ?>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_purchase_details_text );
                                ?>
                            </span>
                            <?php
                            $product_names = [];

                            if( !empty( $settings['better_payment_form_woocommerce_product_id'] ) ):
                                $product_names[] = get_the_title( $settings['better_payment_form_woocommerce_product_id'] );
                            endif;

                            if( !empty( $settings['better_payment_form_woocommerce_product_ids'] ) ):
                                $product_ids = $settings['better_payment_form_woocommerce_product_ids'];
                                foreach( $product_ids as $product_id ):
                                    $product_names[] = get_the_title( $product_id );
                                endforeach;
                            endif;

                            if( !empty( $settings['better_payment_form_fluentcart_product_id'] ) && function_exists('fluentCart') && class_exists( '\FluentCart\App\Models\Product' ) ):
                                try {
                                    $fluentcart_product = \FluentCart\App\Models\Product::query()->find($settings['better_payment_form_fluentcart_product_id']);
                                    if ($fluentcart_product) {
                                        $product_names[] = sanitize_text_field( $fluentcart_product->post_title );
                                    }
                                } catch ( \Exception $e ) {
                                    //
                                }
                            endif;

                            if( !empty( $settings['better_payment_form_fluentcart_product_ids'] ) && function_exists('fluentCart') && class_exists( '\FluentCart\App\Models\Product' ) ):
                                $product_ids = $settings['better_payment_form_fluentcart_product_ids'];
                                foreach( $product_ids as $product_id ):
                                    try {
                                        $fluentcart_product = \FluentCart\App\Models\Product::query()->find($product_id);
                                        if ($fluentcart_product) {
                                            $product_names[] = sanitize_text_field( $fluentcart_product->post_title );
                                        }
                                    } catch ( \Exception $e ) {
                                        //
                                    }
                                endforeach;
                            endif;

                            if( !empty($product_names) ):
                                echo '<span class="bp-info_content  bp-bold">' . esc_html( implode( ', ', $product_names ) ) . '</span>';
                            endif;
                            ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bp-flex bp-button_group">
                    <button class="bp-flex bp-font_ibm bp-print_btn">
                        <span style="line-height: 0;">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/print.svg' ); ?>" alt="Better Payment print">
                        </span>
                        <span class="bp-font_ibm">
                            <?php echo wp_kses_post( $better_payment_form_success_message_print_text ); ?>
                        </span>
                    </button>
                    
                    <div class="bp-flex bp-btn_wrapper">
                        <a href="<?php echo $better_payment_form_success_page_view_details_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url()'d where it is built; the fallback is the literal javascript:void(0), which esc_url() would strip. ?>" target="_blank" class="bp-font_ibm bp-details_btn">
                            <?php echo wp_kses_post( $better_payment_form_success_message_view_details_btn_text ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Error message template
     * 
     * @since 0.0.1
     */
    public static function failed_message_template( $settings = [], $transaction_data = false ) {

        $image_url = BETTER_PAYMENT_ASSETS . '/img/fail.svg';
        $show_icon = 0;
        $default_icon = 1;
        $failed_heading = !empty( $settings['better_payment_form_error_message_heading'] ) ? esc_html( $settings['better_payment_form_error_message_heading'] ) : esc_html__( 'Payment Failed!', 'better-payment' );
        $failed_sub_heading = !empty( $settings['better_payment_form_error_message_sub_heading'] ) ? esc_html( $settings['better_payment_form_error_message_sub_heading'] ) : esc_html__( 'Your payment has failed. Please check your payment details', 'better-payment' );
        $transaction_id_text = !empty( $settings['better_payment_form_error_message_transaction_id_text'] ) ? esc_html( $settings['better_payment_form_error_message_transaction_id_text'] ) : esc_html__( 'Transaction ID', 'better-payment' );
        $show_details_button = !empty( $settings['better_payment_form_error_details_button_switch'] ) && $settings['better_payment_form_error_details_button_switch'] == 'yes' ? true : false;
        $details_button_text = !empty( $settings['better_payment_form_error_details_button_text'] ) ? esc_html( $settings['better_payment_form_error_details_button_text'] ) : esc_html__( 'View Details', 'better-payment' );
        $better_payment_form_error_details_page_url = !empty( $settings['better_payment_form_error_details_page_url']['url'] ) ? esc_url( $settings['better_payment_form_error_details_page_url']['url'] ) : 'javascript:void(0)';
        $store_name = ( isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && !empty( $settings['better_payment_form_title'] ) ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo('name') );

        // Get transaction details
        $transaction_amount = '';
        $transaction_id = '';
        $currency_symbol = '';
        
        if ( !empty( $transaction_data ) ) {
            $transaction_amount = !empty( $transaction_data['amount'] ) ? floatval( $transaction_data['amount'] ) : '';
            $currency_symbol = !empty( $transaction_data['currency_symbol'] ) ? esc_html( $transaction_data['currency_symbol'] ) : '';
            $transaction_id = !empty( $transaction_data['transaction_id'] ) ? esc_html( $transaction_data['transaction_id'] ) : '';
        }

        if ( !empty( $settings[ 'better_payment_form_error_message_icon' ][ 'library' ] ) ) {
            if ( $settings[ 'better_payment_form_error_message_icon' ][ 'library' ] == 'svg' ) {
                $image_url = $settings[ 'better_payment_form_error_message_icon' ][ 'value' ][ 'url' ];
                $default_icon = 0;
            } else {
                $show_icon = 1;
                $image_url = $settings[ 'better_payment_form_error_message_icon' ][ 'value' ];
                $default_icon = 0;
            }
        }
        ?>
        <section class="payment-failed-screen-section">
            <div class="bp-thank_page-wrapper">
                <div class="bp-thank_page-logo">
                    <?php if( $default_icon ): ?>
                    <span class="bp-thank_page-logo_wrapper">
                        <svg width="58" height="58" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M40.804 10.7998H16.8039C13.9409 10.8041 11.1964 11.9434 9.17192 13.9678C7.14746 15.9923 6.00822 18.7368 6.00391 21.5998H51.604C51.5997 18.7368 50.4604 15.9923 48.436 13.9678C46.4115 11.9434 43.667 10.8041 40.804 10.7998ZM6.00391 26.3998V35.9998C6.00822 38.8628 7.14746 41.6073 9.17192 43.6318C11.1964 45.6562 13.9409 46.7955 16.8039 46.7998H40.804C43.667 46.7955 46.4115 45.6562 48.436 43.6318C50.4604 41.6073 51.5997 38.8628 51.604 35.9998V26.3998H6.00391ZM24.0039 38.3998H16.8039C16.1674 38.3998 15.557 38.1469 15.1069 37.6968C14.6568 37.2468 14.4039 36.6363 14.4039 35.9998C14.4039 35.3633 14.6568 34.7529 15.1069 34.3028C15.557 33.8527 16.1674 33.5998 16.8039 33.5998H24.0039C24.6404 33.5998 25.2509 33.8527 25.7009 34.3028C26.151 34.7529 26.4039 35.3633 26.4039 35.9998C26.4039 36.6363 26.151 37.2468 25.7009 37.6968C25.2509 38.1469 24.6404 38.3998 24.0039 38.3998Z" fill="#F44336"></path>
                            <circle cx="45.5" cy="41.5" r="6.5" fill="white"></circle>
                            <foreignObject x="21.4686" y="17.3701" width="47.2066" height="47.3399">
                                <div xmlns="http://www.w3.org/1999/xhtml" style="backdrop-filter:blur(6.36px);clip-path:url(#bgblur_0_3595_8284_clip_path);height:100%;width:100%">
                                </div>
                            </foreignObject>
                            <g filter="url(#filter0_d_3595_8284)" data-figma-bg-blur-radius="12.71">
                                <path d="M55.765 38.782C55.3067 36.6386 54.2191 34.6825 52.6429 33.1664C50.6276 31.1788 47.9133 30.0699 45.0893 30.0804C42.1969 30.0848 39.4243 31.2411 37.3791 33.2958C35.334 35.3505 34.183 38.136 34.1786 41.0417V41.8849C34.2614 43.0459 34.5336 44.1853 34.9843 45.2576C36.0299 47.7766 37.9733 49.8131 40.4344 50.9688C42.8955 52.1245 45.6972 52.3162 48.2915 51.5064C50.8859 50.6967 53.0862 48.9437 54.4622 46.5904C55.8381 44.2371 56.2907 41.4528 55.7314 38.782H55.765ZM46.4154 44.7686L45.0893 43.4364L43.7632 44.7686C43.6072 44.9266 43.4215 45.0521 43.217 45.1377C43.0124 45.2233 42.793 45.2674 42.5714 45.2674C42.3498 45.2674 42.1304 45.2233 41.9259 45.1377C41.7213 45.0521 41.5357 44.9266 41.3796 44.7686C41.2223 44.6118 41.0974 44.4253 41.0122 44.2198C40.927 44.0143 40.8831 43.7939 40.8831 43.5713C40.8831 43.3486 40.927 43.1282 41.0122 42.9227C41.0974 42.7172 41.2223 42.5307 41.3796 42.374L41.8664 41.8849L42.7057 41.0417L41.3796 39.7095C41.0636 39.3919 40.886 38.9613 40.886 38.5122C40.886 38.0631 41.0636 37.6324 41.3796 37.3149C41.6957 36.9973 42.1244 36.8189 42.5714 36.8189C43.0184 36.8189 43.4471 36.9973 43.7632 37.3149L45.0893 38.6471L46.4154 37.3149C46.7314 36.9973 47.1601 36.8189 47.6071 36.8189C48.0541 36.8189 48.4828 36.9973 48.7989 37.3149C49.115 37.6324 49.2926 38.0631 49.2926 38.5122C49.2926 38.9613 49.115 39.3919 48.7989 39.7095L47.4729 41.0417L48.2618 41.8343L48.7989 42.374C48.9563 42.5307 49.0811 42.7172 49.1664 42.9227C49.2516 43.1282 49.2954 43.3486 49.2954 43.5713C49.2954 43.7939 49.2516 44.0143 49.1664 44.2198C49.0811 44.4253 48.9563 44.6118 48.7989 44.7686C48.6429 44.9266 48.4572 45.0521 48.2527 45.1377C48.0481 45.2233 47.8287 45.2674 47.6071 45.2674C47.3855 45.2674 47.1661 45.2233 46.9616 45.1377C46.7571 45.0521 46.5714 44.9266 46.4154 44.7686Z" fill="#F44336" fill-opacity="0.5" shape-rendering="crispEdges"></path>
                            </g>
                            <defs>
                                <filter id="filter0_d_3595_8284" x="21.4686" y="17.3701" width="47.2066" height="47.3399" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
                                    <feOffset dx="-4"></feOffset>
                                    <feGaussianBlur stdDeviation="2"></feGaussianBlur>
                                    <feComposite in2="hardAlpha" operator="out"></feComposite>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.04 0"></feColorMatrix>
                                    <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3595_8284"></feBlend>
                                    <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3595_8284" result="shape"></feBlend>
                                </filter>
                                <clipPath id="bgblur_0_3595_8284_clip_path" transform="translate(-21.4686 -17.3701)">
                                    <path d="M55.765 38.782C55.3067 36.6386 54.2191 34.6825 52.6429 33.1664C50.6276 31.1788 47.9133 30.0699 45.0893 30.0804C42.1969 30.0848 39.4243 31.2411 37.3791 33.2958C35.334 35.3505 34.183 38.136 34.1786 41.0417V41.8849C34.2614 43.0459 34.5336 44.1853 34.9843 45.2576C36.0299 47.7766 37.9733 49.8131 40.4344 50.9688C42.8955 52.1245 45.6972 52.3162 48.2915 51.5064C50.8859 50.6967 53.0862 48.9437 54.4622 46.5904C55.8381 44.2371 56.2907 41.4528 55.7314 38.782H55.765ZM46.4154 44.7686L45.0893 43.4364L43.7632 44.7686C43.6072 44.9266 43.4215 45.0521 43.217 45.1377C43.0124 45.2233 42.793 45.2674 42.5714 45.2674C42.3498 45.2674 42.1304 45.2233 41.9259 45.1377C41.7213 45.0521 41.5357 44.9266 41.3796 44.7686C41.2223 44.6118 41.0974 44.4253 41.0122 44.2198C40.927 44.0143 40.8831 43.7939 40.8831 43.5713C40.8831 43.3486 40.927 43.1282 41.0122 42.9227C41.0974 42.7172 41.2223 42.5307 41.3796 42.374L41.8664 41.8849L42.7057 41.0417L41.3796 39.7095C41.0636 39.3919 40.886 38.9613 40.886 38.5122C40.886 38.0631 41.0636 37.6324 41.3796 37.3149C41.6957 36.9973 42.1244 36.8189 42.5714 36.8189C43.0184 36.8189 43.4471 36.9973 43.7632 37.3149L45.0893 38.6471L46.4154 37.3149C46.7314 36.9973 47.1601 36.8189 47.6071 36.8189C48.0541 36.8189 48.4828 36.9973 48.7989 37.3149C49.115 37.6324 49.2926 38.0631 49.2926 38.5122C49.2926 38.9613 49.115 39.3919 48.7989 39.7095L47.4729 41.0417L48.2618 41.8343L48.7989 42.374C48.9563 42.5307 49.0811 42.7172 49.1664 42.9227C49.2516 43.1282 49.2954 43.3486 49.2954 43.5713C49.2954 43.7939 49.2516 44.0143 49.1664 44.2198C49.0811 44.4253 48.9563 44.6118 48.7989 44.7686C48.6429 44.9266 48.4572 45.0521 48.2527 45.1377C48.0481 45.2233 47.8287 45.2674 47.6071 45.2674C47.3855 45.2674 47.1661 45.2233 46.9616 45.1377C46.7571 45.0521 46.5714 44.9266 46.4154 44.7686Z"></path>
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                    <?php else: ?>
                        <?php if( $show_icon ): ?>
                        <?php
                            // Add 'pf-block' class if this is a Gutenberg block (not Elementor)
                            $error_wrapper_class = 'bp-thank_page-custom-logo_wrapper-error';
                            if ( ! empty( $settings['better_payment_form_source'] ) && 'block' === $settings['better_payment_form_source'] ) {
                                $error_wrapper_class .= ' pf-block';
                            }
                        ?>
                        <div class="<?php echo esc_attr( $error_wrapper_class ); ?>">
                            <?php
                                // Render icon safely (handles FontAwesome, Dashicons, SVG with or without Elementor)
                                if (!empty($settings['better_payment_form_error_message_icon'])) {
                                    self::render_icon($settings['better_payment_form_error_message_icon'], ['aria-hidden' => 'true']);
                                } else {
                                    // Fallback to default icons
                                    echo '<i class="' . esc_attr($image_url) . '"></i>';
                                }
                            ?>
                        </div>
                        <?php else: ?>
                            <span class="bp-thank_page-logo_wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="Error logo">
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="bp-thank_page-text">
                    <h2 class="bp-font_ibm bp-page_header"><?php echo wp_kses_post( $failed_heading ); ?></h2>
                    <p class="bp-font_ibm bp-reason_for-text">Payment of <?php echo $currency_symbol . esc_html( $transaction_amount ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $currency_symbol is esc_html()'d where it is built. ?> to <?php echo $store_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html()'d where it is built. ?> failed.</p>
                    <p class="bp-font_ibm bp-payment_info"><?php echo wp_kses_post( $failed_sub_heading ); ?></p>

                    <div class="bp-flex bp-transaction_info-wrapper">
                        <p class="bp-flex bp-font_ibm  bp-transaction_info"> <span>
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_2914_6421)">
                                    <path d="M12.2002 12.825C11.8821 12.825 11.6242 13.0829 11.6242 13.401C11.6242 13.7191 11.8821 13.977 12.2002 13.977V12.825ZM17.0002 13.401L17.4075 13.8083C17.5156 13.7002 17.5762 13.5537 17.5762 13.401C17.5762 13.2482 17.5156 13.1017 17.4075 12.9937L17.0002 13.401ZM14.993 14.5937C14.768 14.8186 14.768 15.1833 14.993 15.4083C15.2179 15.6332 15.5826 15.6332 15.8075 15.4083L14.993 14.5937ZM15.8075 11.3937C15.5826 11.1687 15.2179 11.1687 14.993 11.3937C14.768 11.6186 14.768 11.9833 14.993 12.2083L15.8075 11.3937ZM10.6002 16.377C10.9184 16.377 11.1762 16.1191 11.1762 15.801C11.1762 15.4829 10.9184 15.225 10.6002 15.225V16.377ZM16.4242 9.40098C16.4242 9.71909 16.6821 9.97698 17.0002 9.97698C17.3184 9.97698 17.5762 9.71909 17.5762 9.40098H16.4242ZM7.40024 13.177C7.71836 13.177 7.97624 12.9191 7.97624 12.601C7.97624 12.2829 7.71836 12.025 7.40024 12.025V13.177ZM4.20024 12.025C3.88213 12.025 3.62424 12.2829 3.62424 12.601C3.62424 12.9191 3.88213 13.177 4.20024 13.177V12.025ZM9.80024 13.177C10.1184 13.177 10.3762 12.9191 10.3762 12.601C10.3762 12.2829 10.1184 12.025 9.80024 12.025V13.177ZM9.40024 12.025C9.08213 12.025 8.82424 12.2829 8.82424 12.601C8.82424 12.9191 9.08213 13.177 9.40024 13.177V12.025ZM1.00024 7.22498C0.682128 7.22498 0.424244 7.48286 0.424244 7.80098C0.424244 8.11909 0.682128 8.37698 1.00024 8.37698V7.22498ZM17.0002 8.37698C17.3184 8.37698 17.5762 8.11909 17.5762 7.80098C17.5762 7.48286 17.3184 7.22498 17.0002 7.22498V8.37698ZM12.2002 13.977H17.0002V12.825H12.2002V13.977ZM16.593 12.9937L14.993 14.5937L15.8075 15.4083L17.4075 13.8083L16.593 12.9937ZM17.4075 12.9937L15.8075 11.3937L14.993 12.2083L16.593 13.8083L17.4075 12.9937ZM7.40024 3.57698H10.6002V2.42498H7.40024V3.57698ZM10.6002 15.225H7.40024V16.377H10.6002V15.225ZM7.40024 15.225C5.87547 15.225 4.78983 15.2238 3.96572 15.113C3.15819 15.0044 2.68857 14.8002 2.3448 14.4564L1.53021 15.271C2.1237 15.8645 2.87695 16.1289 3.81222 16.2547C4.73093 16.3782 5.90803 16.377 7.40024 16.377V15.225ZM0.424244 9.40098C0.424244 10.8932 0.423021 12.0703 0.546538 12.989C0.672282 13.9243 0.936721 14.6775 1.53021 15.271L2.3448 14.4564C2.00103 14.1127 1.79684 13.643 1.68827 12.8355C1.57747 12.0114 1.57624 10.9258 1.57624 9.40098H0.424244ZM10.6002 3.57698C12.125 3.57698 13.2107 3.5782 14.0348 3.689C14.8423 3.79757 15.3119 4.00176 15.6557 4.34553L16.4703 3.53094C15.8768 2.93745 15.1235 2.67301 14.1883 2.54727C13.2696 2.42375 12.0925 2.42498 10.6002 2.42498V3.57698ZM17.5762 9.40098C17.5762 7.90876 17.5775 6.73166 17.4539 5.81296C17.3282 4.87768 17.0638 4.12443 16.4703 3.53094L15.6557 4.34553C15.9995 4.6893 16.2037 5.15892 16.3122 5.96646C16.423 6.79056 16.4242 7.8762 16.4242 9.40098H17.5762ZM7.40024 2.42498C5.90803 2.42498 4.73093 2.42375 3.81222 2.54727C2.87695 2.67301 2.1237 2.93745 1.53021 3.53094L2.3448 4.34553C2.68857 4.00176 3.15819 3.79757 3.96572 3.689C4.78983 3.5782 5.87547 3.57698 7.40024 3.57698V2.42498ZM1.57624 9.40098C1.57624 7.8762 1.57747 6.79056 1.68827 5.96646C1.79684 5.15892 2.00103 4.6893 2.3448 4.34553L1.53021 3.53094C0.936721 4.12443 0.672282 4.87768 0.546538 5.81296C0.423021 6.73166 0.424244 7.90876 0.424244 9.40098H1.57624ZM7.40024 12.025H4.20024V13.177H7.40024V12.025ZM9.80024 12.025H9.40024V13.177H9.80024V12.025ZM1.00024 8.37698H17.0002V7.22498H1.00024V8.37698Z" fill="#8F9AB0"></path>
                                </g>
                                <defs>
                                    <clipPath id="clip0_2914_6421">
                                        <rect width="18" height="18" fill="white"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                            </span>
                            <span><?php echo wp_kses_post( $transaction_id_text ); ?>:</span>
                            <span class="bp-bolt bp-transaction_id"><?php echo $transaction_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html()'d where it is built. ?></span>
                        </p>

                        <button class="bp-transaction_data-copy_btn ">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.0002 15.601C16.3257 15.601 17.4002 14.5265 17.4002 13.201V8.40098C17.4002 5.38399 17.4002 3.87549 16.463 2.93823C15.5257 2.00098 14.0172 2.00098 11.0002 2.00098H7.80024C6.47476 2.00098 5.40024 3.07549 5.40024 4.40098M10.2002 18.001H7.80024C5.5375 18.001 4.40613 18.001 3.70319 17.298C3.00024 16.5951 3.00024 15.4637 3.00024 13.201V9.20098C3.00024 6.93823 3.00024 5.80686 3.70319 5.10392C4.40613 4.40098 5.5375 4.40098 7.80024 4.40098H10.2002C12.463 4.40098 13.5944 4.40098 14.2973 5.10392C15.0002 5.80686 15.0002 6.93824 15.0002 9.20098V13.201C15.0002 15.4637 15.0002 16.5951 14.2973 17.298C13.5944 18.001 12.463 18.001 10.2002 18.001Z" stroke-width="1.152"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php if( $show_details_button ): ?>
                <div class="bp-btn_wrapper">
                    <a href="<?php echo $better_payment_form_error_details_page_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url()'d where it is built; the fallback is the literal javascript:void(0), which esc_url() would strip. ?>" target="_blank" class="bp-font_ibm bp-details_btn"><?php echo $details_button_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html()'d where it is built. ?></a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Remove args from url
     * 
     * @since 0.0.1
     */
    public static function remove_arg() {
        ?>
        <script>

            if(typeof params === 'undefined'){
                let params = '';                
            }

            params = new URLSearchParams(location.search);

            if (params.has('better_payment_paypal_status') || params.has('better_payment_stripe_status') || params.has('better_payment_paystack_status')) {
                params.delete('better_payment_paypal_status');
                params.delete('better_payment_stripe_status');
                params.delete('better_payment_paystack_status');
                params.delete('better_payment_widget_id');
                params.delete('better_payment_stripe_id');
                params.delete('better_payment_paystack_id');
                params.delete('item_number');
                window.history.replaceState({}, '', `${location.pathname}?${params}`);
            }
        </script>
        <?php
    }

    /**
     * Get failed transaction data
     * 
     * @since 1.0.0
     */
    public static function get_failed_transaction_data( $order_id = '' ) {
        if ( empty( $order_id ) ) {
            return false;
        }
        
        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $result = $wpdb->get_row(
            $wpdb->prepare( 
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
                "SELECT amount, currency, transaction_id, order_id, source FROM $table WHERE order_id=%s LIMIT 1", 
                $order_id 
            )
        );
        
        if ( !empty( $result ) ) {
            $helper_obj = new Helper();
            return [
                'amount' => floatval( $result->amount ),
                'currency' => sanitize_text_field( $result->currency ),
                'currency_symbol' => $helper_obj->get_currency_symbol( $result->currency ),
                // The order id is request data, and ids before 2.4.0 were `{gateway}_` . uniqid() —
                // enumerable — so the gateway's transaction id is shown only for an unguessable id.
                'transaction_id' => ! empty( $result->transaction_id ) && self::order_id_is_unguessable( $order_id ) ? sanitize_text_field( $result->transaction_id ) : __( 'N/A', 'better-payment' ),
                'method' => sanitize_text_field( $result->source ),
            ];
        }
        
        return false;
    }

    /**
     * Whitelist the e-mail related settings that better_email_notification() reads.
     *
     * PayPal confirms payments asynchronously through the IPN listener
     * (handle_paypal_ipn), where the live Elementor widget / block settings are NOT
     * available. Stripe and Paystack build their e-mail synchronously on the return
     * page, so they still have the full settings array in hand. To reach parity we
     * persist this subset into the transaction's form_fields_info at payment-create
     * time, and the IPN handler rebuilds the same $settings to produce an identical
     * e-mail body (heading, From / To / Transaction Summary / footer sections,
     * subjects, headers) and to honour the "Send Email" on/off toggle.
     *
     * @param mixed $settings Full widget / block / form settings (guarded: non-array input returns []).
     *
     * @return array Whitelisted e-mail settings (safe to serialize into form_fields_info).
     * @since 2.2.2
     */
    public static function extract_email_settings( $settings = [] ) {
        $email_settings = [];

        if ( ! is_array( $settings ) ) {
            return $email_settings;
        }

        foreach ( $settings as $key => $value ) {
            if (
                0 === strpos( $key, 'better_payment_email_' )
                || 0 === strpos( $key, 'better_payment_form_email_' )
                || 'better_payment_form_title' === $key
            ) {
                $email_settings[ $key ] = $value;
            }
        }

        return $email_settings;
    }

    /**
     * Email template
     *
     * @since 0.0.1
     */
    public static function better_email_notification($transaction_id, $customer_email, $settings, $referrer='Stripe', $form_fields_info='', $is_elementor_form = 0){
        //Send Email: customer and admin
        $better_txn_id = sanitize_text_field( $transaction_id );
        $better_cus_email = sanitize_email( $customer_email );
        /* translators: %s: site name. */
        $default_subject = sprintf(__('Better Payment transaction on %s', 'better-payment'), esc_html(get_option('blogname')));
        $better_payment_global_settings = DB::get_settings();

        $better_payment_email_to = !empty($better_payment_global_settings['better_payment_settings_general_email_to']) ? $better_payment_global_settings['better_payment_settings_general_email_to'] : get_option( 'admin_email' );

        $better_payment_email_subject = !empty($better_payment_global_settings['better_payment_settings_general_email_subject']) ? $better_payment_global_settings['better_payment_settings_general_email_subject'] :  $default_subject;
        $better_payment_email_content = !empty($better_payment_global_settings['better_payment_settings_general_email_message_admin']) ? $better_payment_global_settings['better_payment_settings_general_email_message_admin'] : 'Email Content';
        $better_payment_email_from = !empty($better_payment_global_settings['better_payment_settings_general_email_from_email']) ? $better_payment_global_settings['better_payment_settings_general_email_from_email'] : '';
        $better_payment_email_from_name = !empty($better_payment_global_settings['better_payment_settings_general_email_from_name']) ? $better_payment_global_settings['better_payment_settings_general_email_from_name'] : '';
        $better_payment_email_reply_to = !empty($better_payment_global_settings['better_payment_settings_general_email_reply_to']) ? $better_payment_global_settings['better_payment_settings_general_email_reply_to'] : '';
        $better_payment_email_cc = !empty($better_payment_global_settings['better_payment_settings_general_email_cc']) ? $better_payment_global_settings['better_payment_settings_general_email_cc'] : '';
        $better_payment_email_bcc = !empty($better_payment_global_settings['better_payment_settings_general_email_bcc']) ? $better_payment_global_settings['better_payment_settings_general_email_bcc'] : '';
        $better_payment_email_content_type = !empty($better_payment_global_settings['better_payment_settings_general_email_send_as']) ? $better_payment_global_settings['better_payment_settings_general_email_send_as'] : 'html';
        
        $better_payment_email_subject_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_subject_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_subject_customer'] : $default_subject;
        $better_payment_email_content_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_message_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_message_customer'] : 'Email Content';
        $better_payment_email_from_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_from_email_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_from_email_customer'] : '';
        $better_payment_email_from_name_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_from_name_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_from_name_customer'] : '';
        $better_payment_email_reply_to_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_reply_to_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_reply_to_customer'] : '';
        $better_payment_email_cc_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_cc_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_cc_customer'] : '';
        $better_payment_email_bcc_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_bcc_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_bcc_customer'] : '';
        $better_payment_email_content_type_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_send_as_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_send_as_customer'] : 'html';
        $args = [];

        $args['email_form_name'] = ! empty( $settings['better_payment_form_title'] ) ? sanitize_text_field( $settings['better_payment_form_title'] ) : __( 'N/A', 'better-payment' );
        $args['email_content_heading_show'] = ! empty( $settings['better_payment_email_content_heading'] ) && 'yes' === $settings['better_payment_email_content_heading'] ? 1 : 0;
        $args['email_content_from_section_show'] = ! empty( $settings['better_payment_email_content_from_section'] ) && 'yes' === $settings['better_payment_email_content_from_section'] ? 1 : 0;
        $args['email_content_to_section_show'] = ! empty( $settings['better_payment_email_content_to_section'] ) && 'yes' === $settings['better_payment_email_content_to_section'] ? 1 : 0;
        $args['email_content_transaction_summary_show'] = ! empty( $settings['better_payment_email_content_transaction_summary'] ) && 'yes' === $settings['better_payment_email_content_transaction_summary'] ? 1 : 0;
        $args['email_content_footer_text_show'] = ! empty( $settings['better_payment_email_content_footer_text'] ) && 'yes' === $settings['better_payment_email_content_footer_text'] ? 1 : 0;
        $args['email_content_customer'] = ! empty( $settings['better_payment_email_content_customer'] ) ? $settings['better_payment_email_content_customer'] : '';
        $args['email_content_admin'] = ! empty( $settings['better_payment_email_content'] ) ? $settings['better_payment_email_content'] : '';
        $args['is_elementor_form'] = $is_elementor_form;

        if ( ! empty( $settings['better_payment_email_content_greeting'] ) ) {
            $args['email_content_greeting'] = $settings['better_payment_email_content_greeting'];
        }
        
        if ( ! empty( $settings['better_payment_form_email_logo']['url'] ) ) {
            $args['email_logo_url'] = $settings['better_payment_form_email_logo']['url'];
        }
        
        if ( ! empty( $settings['better_payment_form_email_attachment']['id'] ) ) {
            $args['email_attachment_id'] = intval( $settings['better_payment_form_email_attachment']['id'] );
            $args['email_attachment_path'] = get_attached_file( $args['email_attachment_id'] );
        }
        
        if ( ! empty( $settings['better_payment_form_email_attachment_pdf_show'] ) && ! empty( $settings['better_payment_form_email_attachment_pdf']['id'] ) ) {
            $args['email_attachment_id'] = intval( $settings['better_payment_form_email_attachment_pdf']['id'] );
            $args['email_attachment_path'] = get_attached_file( $args['email_attachment_id'] );
        }

        if(isset($settings['better_payment_email_to'])){
            $better_payment_email_to = sanitize_email($settings['better_payment_email_to']);
        }

        if(isset($settings['better_payment_email_subject'])){
            $better_payment_email_subject = sanitize_text_field($settings['better_payment_email_subject']);
        }

        if(isset($settings['better_payment_email_subject_customer'])){
            $better_payment_email_subject_customer = sanitize_text_field($settings['better_payment_email_subject_customer']);
        }

        if(isset($settings['better_payment_email_content_type'])){
            $better_payment_email_content_type = sanitize_text_field($settings['better_payment_email_content_type']);
        }
        
        if(isset($settings['better_payment_email_content_type_customer'])){
            $better_payment_email_content_type_customer = sanitize_text_field($settings['better_payment_email_content_type_customer']);
        }

        $args['email_content_type'] = $better_payment_email_content_type;
        $args['email_content_type_customer'] = $better_payment_email_content_type_customer;

		$line_break = $better_payment_email_content_type == 'html' ? '<br>' : "\n";
		$line_break_customer = $better_payment_email_content_type_customer == 'html' ? '<br>' : "\n";

        global $wpdb;
        $table   = "{$wpdb->prefix}better_payment";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom transactions table; no WP API for it, and payment rows must never be served from a cache.
        $transaction_obj = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix; values go through placeholders.
            $wpdb->prepare( "SELECT * FROM $table WHERE transaction_id=%s limit 1", sanitize_text_field( $transaction_id ) )
        );

        if(isset($settings['better_payment_email_content'])){
            $better_payment_email_content = sanitize_text_field($settings['better_payment_email_content']);
            $better_payment_email_content = self::better_render_email_body( $better_payment_email_content, $form_fields_info, $line_break, 'admin', $transaction_obj, $args );
        } else if ( !empty($better_payment_email_content) ){
            $better_payment_email_content = self::better_render_email_body( $better_payment_email_content, $form_fields_info, $line_break, 'admin', $transaction_obj, $args );
        }

        if(isset($settings['better_payment_email_content_customer'])){
            $better_payment_email_content_customer = sanitize_text_field($settings['better_payment_email_content_customer']);
            $better_payment_email_content_customer = self::better_render_email_body( $better_payment_email_content_customer, $form_fields_info, $line_break_customer, 'customer', $transaction_obj, $args );
        } else if ( !empty($better_payment_email_content_customer) ){
            $better_payment_email_content_customer = self::better_render_email_body( $better_payment_email_content_customer, $form_fields_info, $line_break, 'customer', $transaction_obj, $args ); //$line_break_customer not used : need to check type on parent method and add headers accordingly.
        }

        if(isset($settings['better_payment_email_from'])){
            $better_payment_email_from = sanitize_email($settings['better_payment_email_from']);
        }

        if(isset($settings['better_payment_email_from_name'])){
            $better_payment_email_from_name = sanitize_text_field($settings['better_payment_email_from_name']);
        }

        if(isset($settings['better_payment_email_reply_to'])){
            $better_payment_email_reply_to = sanitize_email($settings['better_payment_email_reply_to']);
        }

        if(isset($settings['better_payment_email_cc'])){
            $better_payment_email_cc = sanitize_email($settings['better_payment_email_cc']);
        }

        if(isset($settings['better_payment_email_bcc'])){
            $better_payment_email_bcc = sanitize_email($settings['better_payment_email_bcc']);
        }

        //Customer Email
        if(isset($settings['better_payment_email_from_customer'])){
            $better_payment_email_from_customer = sanitize_email($settings['better_payment_email_from_customer']);
        }

        if(isset($settings['better_payment_email_from_name_customer'])){
            $better_payment_email_from_name_customer = sanitize_text_field($settings['better_payment_email_from_name_customer']);
        }

        if(isset($settings['better_payment_email_reply_to_customer'])){
            $better_payment_email_reply_to_customer = sanitize_email($settings['better_payment_email_reply_to_customer']);
        }

        if(isset($settings['better_payment_email_cc_customer'])){
            $better_payment_email_cc_customer = sanitize_email($settings['better_payment_email_cc_customer']);
        }

        if(isset($settings['better_payment_email_bcc_customer'])){
            $better_payment_email_bcc_customer = sanitize_email($settings['better_payment_email_bcc_customer']);
        }

        if($better_payment_email_content == ''){
            $better_payment_email_content = __('New better payment transaction! ', 'better-payment');
        }

        if($better_payment_email_content_customer == ''){
            $better_payment_email_content_customer = __('New better payment transaction! ', 'better-payment');
        }

        $better_payment_email_headers = [];
        $better_payment_email_headers_customer = [];

        if($better_payment_email_content_type == 'html'){
            $better_payment_email_headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        
        if($better_payment_email_content_type_customer == 'html'){
            $better_payment_email_headers_customer[] = 'Content-Type: text/html; charset=UTF-8';
        }

        if($better_payment_email_from != ''){
            $better_payment_email_headers[] = "From: $better_payment_email_from_name <$better_payment_email_from>";
        }

        if($better_payment_email_cc != ''){
            $better_payment_email_headers[] = "Cc: <$better_payment_email_cc>";
        }

        if($better_payment_email_bcc != ''){
            $better_payment_email_headers[] = "BCc: $better_payment_email_bcc";
        }

        if($better_payment_email_reply_to != ''){
           $better_payment_email_headers[] = "Reply-To: $better_payment_email_reply_to";
        }

        //Customer Email
        if($better_payment_email_from_customer != ''){
            $better_payment_email_headers_customer[] = "From: $better_payment_email_from_name_customer <$better_payment_email_from_customer>";
        }

        $form_fields_info_array = [];
        
        if( ! empty( $form_fields_info ) ){
            $form_fields_info_array = maybe_unserialize( $form_fields_info );
        }

        $form_fields_info_cus_email = ! empty( $form_fields_info_array['primary_email'] ) ? sanitize_email( $form_fields_info_array['primary_email'] ) : '';
        
        if($better_payment_email_cc_customer != '' || ! empty( $form_fields_info_cus_email ) ){
            $better_payment_email_cc_customer_multiple = implode(',', [$form_fields_info_cus_email, $better_payment_email_cc_customer]);
            $better_payment_email_headers_customer[] = "Cc: $better_payment_email_cc_customer_multiple";
        }

        if($better_payment_email_bcc_customer != ''){
            $better_payment_email_headers_customer[] = "BCc: $better_payment_email_bcc_customer";
        }

        if($better_payment_email_reply_to_customer != ''){
           $better_payment_email_headers_customer[] = "Reply-To: $better_payment_email_reply_to_customer";
        }

        $email_attachments = ! empty( $args['email_attachment_path'] ) ? [ $args['email_attachment_path'] ] : [];
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');

        $file_info = count( $email_attachments ) ? pathinfo($email_attachments[0]) : [];
        $file_extension = count( $file_info ) ? strtolower($file_info['extension']) : '';

        if ( ! in_array( $file_extension, $allowed_extensions ) ){
            $email_attachments = [];
        }

        self::send_better_email($better_cus_email, $better_payment_email_subject_customer, $better_payment_email_content_customer, $better_payment_email_headers_customer, $email_attachments); //customer mail
        self::send_better_email($better_payment_email_to, $better_payment_email_subject, $better_payment_email_content, $better_payment_email_headers); //admin mail
    }

    /**
     * Sends an HTML email.
     *
     * @since  0.0.2
     *
     * @param string $to
     * @param string $subject
     * @param array  $message
     *
     * @return bool false indicates mail sending failed while true doesn't gurantee that mail sent.
     * true just indicate script processed successfully without any errors. 
     * Ref: https://developer.wordpress.org/reference/functions/wp_mail/
     */
    public static function send_better_email( $to, $subject, $message, $headers=[], $attachments = [] ) {
        $response = false;

        $mailValidated = filter_var($to, FILTER_VALIDATE_EMAIL);

        if ($mailValidated) {
            $response = wp_mail( $to, $subject, $message, $headers, $attachments );
        }

        return $response;
    }

    /**
     * Email body
     *
     * 
     * @return string
     * @since  0.0.1
     */
    public static function better_render_email_body( $email_body, $form_fields_info, $line_break, $type = 'admin', $transaction_obj = null, $args = [] ) {
        $transaction_id = 
        $currency =
        $payment_date =
        $customer_name =
        $customer_first_name = 
        $customer_last_name = 
        $amount = '';
        $helper_obj = new Helper();
        
        if(null !== $transaction_obj && is_object($transaction_obj)){
           $transaction_id = $transaction_obj->transaction_id;
           $amount = $transaction_obj->amount;
           $status = $transaction_obj->status;
           $source = $transaction_obj->source;
           $currency = $transaction_obj->currency;
           $currency_symbol    = $helper_obj->get_currency_symbol( esc_html($currency) );
           $payment_date = wp_date(get_option('date_format').' '.get_option('time_format'), strtotime($transaction_obj->payment_date));
        }

        $referer_content_page_link = '#';

        $form_fields_info_arr = maybe_unserialize($form_fields_info);
        $form_fields_info_arr = is_array( $form_fields_info_arr ) ? $form_fields_info_arr : [];

        $email_logo_url = ! empty( $args['email_logo_url'] ) ? $args['email_logo_url'] : '';
        $email_content_greeting = ! empty( $args['email_content_greeting'] ) ? 1 : 0;
        $email_form_name = ! empty( $args['email_form_name'] ) ? $args['email_form_name'] : __( 'N/A', 'better-payment' );

        $form_field_rows = [];

        foreach ( $form_fields_info_arr as $key => $field ) {
            //Hide few fields: storage keys, not fields the visitor filled in (the From section hides the same ones)
            if(
                $key === 'referer_page_id' || $key === 'referer_widget_id' || $key === 'source' || $key === 'el_form_fields'
                || $key === 'amount_quantity'
                || $key === 'woo_product_id'
                || $key === 'woo_product_ids'
                || $key === 'fluentcart_product_id'
                || $key === 'fluentcart_product_ids'
                || $key === 'detailed_product_info'
                || $key === 'email_settings'
            ) {
                if($key === 'referer_page_id'){
                    $referer_content_page_link = !empty($field) ? get_permalink( $field ) : $referer_content_page_link;
                }

                continue;
            }

            $label_key = $key;

            if($key === 'primary_first_name'){
                $label_key = 'first_name';
                $customer_first_name = $field;
            }
            if($key === 'primary_last_name'){
                $label_key = 'last_name';
                $customer_last_name = $field;
            }

            if($key === 'primary_email'){
                $label_key = 'email';
            }

            if($key === 'primary_payment_amount'){
                $label_key = 'payment_amount';
            }

            $key_formatted = self::better_title_case($label_key);
            if($key_formatted === 'Amount'){
                $key_formatted = __('Paid', 'better-payment');
            }

            $form_field_rows[] = [
                'key'   => $key,
                'label' => $key_formatted,
                'value' => $field,
            ];
        }

        // A Pro payment's own fields (split payments, layout 6) are hidden or relabelled by Pro.
        $all_fields_rows = PaymentEntitlement::email_rows( $form_field_rows, 'all_fields', $form_fields_info_arr );

        if($customer_first_name) {
            $customer_name = $customer_first_name . ' ';
        }
        if($customer_last_name) {
            $customer_name .= $customer_last_name . ' ';
        }

        $allowed_sources = ['paypal', 'stripe', 'paystack'];
        $source_image_url = BETTER_PAYMENT_ASSETS . '/img/stripe.png';
        $source_image_alt = 'Stripe';
        
        if( in_array( strtolower( $transaction_obj->source ), $allowed_sources ) ){
            $source_image_url = strtolower( $transaction_obj->source ) == 'paypal' ? BETTER_PAYMENT_ASSETS . '/img/paypal.png' : BETTER_PAYMENT_ASSETS . "/img/{$transaction_obj->source}.png";
            $source_image_alt = strtolower( $transaction_obj->source ) == 'paypal' ? 'PayPal' : ucfirst( strtolower( $transaction_obj->source ) );
        }
        
        $amount_quantity = ! empty( $form_fields_info_arr['amount_quantity'] ) ? intval( $form_fields_info_arr['amount_quantity'] ) : 1;
        // #ToDo Product id or ids with comma
        $woo_product_id = ! empty( $form_fields_info_arr['woo_product_id'] ) ? intval( $form_fields_info_arr['woo_product_id'] ) : 0;
        $fluentcart_product_id = ! empty( $form_fields_info_arr['fluentcart_product_id'] ) ? intval( $form_fields_info_arr['fluentcart_product_id'] ) : 0;
        $woo_product_ids = ! empty( $form_fields_info_arr['woo_product_ids'] ) ? maybe_unserialize( $form_fields_info_arr['woo_product_ids'] ) : [0];
        $fluentcart_product_ids = ! empty( $form_fields_info_arr['fluentcart_product_ids'] ) ? maybe_unserialize( $form_fields_info_arr['fluentcart_product_ids'] ) : [0];
        $product_name = '';
        $product_permalink = '';
        $product_image_src = '';

        // Handle WooCommerce products
        if (function_exists('wc_get_product') && $woo_product_id ) {
            $bp_woocommerce_product = wc_get_product($woo_product_id);
            $product = $bp_woocommerce_product;

            if ($product) {
                $product_name = $product->get_name();
                $product_permalink = get_permalink($product->get_id());
                $product_price = $product->get_price();
                $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
                $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
            }
        }

        if (function_exists('fluentCart') && $fluentcart_product_id ) {
            try {
                $fluentcart_product = \FluentCart\App\Models\Product::query()
                    ->with(['detail', 'variants'])
                    ->find($fluentcart_product_id);

                if ($fluentcart_product) {
                    $product_name = $fluentcart_product->post_title;
                    $product_permalink = get_permalink($fluentcart_product->ID);

                    // Get price from the first variant or detail
                    $product_price = 0;
                    if (!empty($fluentcart_product->variants) && count($fluentcart_product->variants) > 0) {
                        $product_price = $fluentcart_product->variants[0]->item_price;
                    } elseif (!empty($fluentcart_product->detail)) {
                        $product_price = $fluentcart_product->detail->min_price;
                    }

                    $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );
                    $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
                }
            } catch ( \Exception $e ) {
                // Handle error silently
            }
        }

        $detailed_product_info = isset( $form_fields_info_arr['detailed_product_info'] ) ? maybe_unserialize( $form_fields_info_arr['detailed_product_info'] ) : [];

        $all_data = [
            'amount' => $amount,
            'amount_quantity' => $amount_quantity,
            'woo_product_id' => $woo_product_id,
            'woo_product_ids' => $woo_product_ids,
            'fluentcart_product_id' => $fluentcart_product_id,
            'fluentcart_product_ids' => $fluentcart_product_ids,
            'product_name' => $product_name,
            'product_permalink' => $product_permalink,
            'product_image_src' => $product_image_src,
            'detailed_product_info' => $detailed_product_info,
            'amount_single' => $amount_quantity > 0 ? floatval( $amount / $amount_quantity ) : $amount,
            'currency' => ! empty( $currency ) ? $currency : '',
            'currency_symbol' => ! empty( $currency_symbol ) ? $currency_symbol : '',
            'payment_date_time' => ! empty( $payment_date ) ? $payment_date : '',
            'transaction_id' => ! empty( $transaction_id ) ? $transaction_id : '',
            'payment_date_only' => wp_date(get_option('date_format'), strtotime($transaction_obj->payment_date)),
            'status' => self::email_status_label( $status ),
            'form_fields_info_arr' => $form_fields_info_arr,
            // 'admin_name' => $admin_name,
            'customer_name' => $customer_name,
            'site_title' => get_bloginfo( 'name' ),
            'site_admin_email' => get_bloginfo( 'admin_email' ),
            'form_name' => $email_form_name,
            'payment_method' => ucfirst( $source ),
            'email_logo_url' => $email_logo_url,
            'source_image_url' => $source_image_url,
            'source_image_alt' => $source_image_alt,
            'view_transaction_link' => Helper::transaction_view_url( (int) $transaction_obj->id ),
            'email_content_heading_show' => intval( $args['email_content_heading_show'] ),
            'email_content_from_section_show' => intval( $args['email_content_from_section_show'] ),
            'email_content_to_section_show' => intval( $args['email_content_to_section_show'] ),
            'email_content_transaction_summary_show' => intval( $args['email_content_transaction_summary_show'] ),
            'email_content_footer_text_show' => intval( $args['email_content_footer_text_show'] ),
            // The form's messages, with `[bp-all-fields]` replaced where the author placed it.
            'email_content_customer' => self::render_email_message( $args['email_content_customer'] ?? '', $all_fields_rows, 'plain' === ( $args['email_content_type_customer'] ?? 'html' ) ),
            'email_content_admin' => self::render_email_message( $args['email_content_admin'] ?? '', $all_fields_rows, 'plain' === ( $args['email_content_type'] ?? 'html' ) ),
            'is_elementor_form' => intval( $args['is_elementor_form'] ),
            'email_content_type' => $args['email_content_type'] ?? 'html',
            'email_content_type_customer' => $args['email_content_type_customer'] ?? 'html',
        ];

        // Email V2: the template is the whole body.
        ob_start();

        include BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/template-email-notification.php";
        
        $email_body = ob_get_contents();
        ob_end_clean();

		return $email_body;
	}

    /**
     * A form's e-mail message, with `[bp-all-fields]` replaced by the submitted fields.
     *
     * The template prints the message; the fields used to be built into a body the template's
     * output then overwrote, so the token reached every e-mail as literal text. The message is
     * the form's own setting and is kses-filtered as it always was. The fields are the
     * visitor's, so they are escaped here and inserted after the filter — never passed through
     * it or do_shortcode(), where a submitted value could add markup or run a shortcode.
     *
     * @since 2.4.0
     *
     * @param mixed   $message  The form's message setting.
     * @param array[] $rows     `label` and `value` of each field to list, in order.
     * @param bool    $is_plain Whether the e-mail is sent as plain text.
     * @return string
     */
    public static function render_email_message( $message, array $rows, $is_plain = false ) {
        $message = wp_kses_post( is_scalar( $message ) ? (string) $message : '' );

        if ( false === strpos( $message, '[bp-all-fields]' ) ) {
            return $message;
        }

        return str_replace( '[bp-all-fields]', self::email_all_fields_markup( $rows, $is_plain ), $message );
    }

    /**
     * The `[bp-all-fields]` block: a two-column table, or `Label: value` lines in a plain-text e-mail.
     *
     * @param array[] $rows     `label` and `value` of each field.
     * @param bool    $is_plain Plain text instead of HTML.
     * @return string
     */
    private static function email_all_fields_markup( array $rows, $is_plain ) {
        $lines = [];
        $cells = '';

        foreach ( $rows as $row ) {
            if ( ! isset( $row['label'] ) || ! is_scalar( $row['label'] ) || ! array_key_exists( 'value', $row ) ) {
                continue;
            }

            // A nested value (stored settings, a product list) is not something a visitor typed.
            if ( ! is_scalar( $row['value'] ) && null !== $row['value'] ) {
                continue;
            }

            $label = (string) $row['label'];
            $value = (string) $row['value'];

            if ( $is_plain ) {
                $lines[] = rtrim( wp_strip_all_tags( $label ), ': ' ) . ': ' . wp_strip_all_tags( $value );
                continue;
            }

            $cells .= '<tr>'
                . '<td style="padding: 7px; border: 1px solid #666;"><strong>' . esc_html( $label ) . '</strong></td>'
                . '<td style="padding: 7px; border: 1px solid #666;">' . esc_html( $value ) . '</td>'
                . '</tr>';
        }

        if ( $is_plain ) {
            return implode( "\n", $lines );
        }

        return '<table class="bp-all-fields" style="margin-bottom: 20px; font-size: 14px; border-collapse: collapse; width: 100%;">' . $cells . '</table>';
    }

    /**
     * String helper method
     * helps to convert string to title case
     *
     * @return string
     * @since  0.0.1
     */
    public static function better_title_case($string){
        $string = str_replace('_',' ', $string);
        $string = ucwords($string);
        return $string;
    }

    /**
     * Safe icon rendering for payment form messages
     *
     * Handles multiple icon types: FontAwesome, Dashicons, SVG from media library
     * Works with or without Elementor being active
     *
     * @param array $icon_settings Icon settings from block/widget controls
     * @param array $html_attributes Optional HTML attributes for the icon element
     *
     * @return void (outputs HTML directly)
     * @since 2.0.4
     */
    public static function render_icon( $icon_settings = [], $html_attributes = [] ) {
        // Bail if no settings provided
        if ( empty( $icon_settings ) || ! is_array( $icon_settings ) ) {
            return;
        }

        // If value is empty, nothing to render
        if ( empty( $icon_settings['value'] ) ) {
            return;
        }

        $library = ! empty( $icon_settings['library'] ) ? $icon_settings['library'] : '';
        $value   = $icon_settings['value'];

        // Try to use Elementor's Icons_Manager if it's available and loaded
        if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Icons_Manager' ) ) {
            try {
                \Elementor\Icons_Manager::render_icon( $icon_settings, $html_attributes );
                return;
            } catch ( \Exception $e ) {
                // If Elementor rendering fails, fall back to manual rendering
            }
        }

        // Handle SVG (uploaded via media library)
        if ( $library === 'svg' ) {
            if ( is_array( $value ) && ! empty( $value['url'] ) ) {
                $svg_url = $value['url'];
                echo '<img src="' . esc_url( $svg_url ) . '" alt="Icon" class="bp-icon-svg" />';
                return;
            }
        }

        // Handle FontAwesome icons (all variants: fa, fas, far, fab, fal, fad, etc.)
        if ( in_array( $library, [ 'fa', 'fas', 'far', 'fab', 'fal', 'fad', 'fontawesome', 'font-awesome' ], true ) ) {
            if ( is_string( $value ) && ! empty( $value ) ) {
                // Build complete FontAwesome class
                $icon_class = 'fa';

                // Add variant if specified (fas, far, fab, fal)
                if ( ! empty( $library ) && $library !== 'fa' && $library !== 'fontawesome' && $library !== 'font-awesome' ) {
                    $icon_class = esc_attr( $library );
                } else {
                    // Default to 'fas' if no variant specified
                    $icon_class = 'fas';
                }

                // Add the specific icon class
                $icon_class .= ' ' . esc_attr( $value );

                $html_class = ! empty( $html_attributes['class'] ) ? ' ' . esc_attr( $html_attributes['class'] ) : '';
                $aria_hidden = isset( $html_attributes['aria-hidden'] ) ? ' aria-hidden="' . esc_attr( $html_attributes['aria-hidden'] ) . '"' : ' aria-hidden="true"';

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_class, $html_class and $aria_hidden are esc_attr()'d where they are built above.
                echo '<i class="' . $icon_class . $html_class . '"' . $aria_hidden . '></i>';
                return;
            }
        }

        // Handle Dashicons
        if ( in_array( $library, [ 'dashicons' ], true ) ) {
            if ( is_string( $value ) && ! empty( $value ) ) {
                $icon_class = 'dashicons dashicons-' . esc_attr( $value );

                $html_class = ! empty( $html_attributes['class'] ) ? ' ' . esc_attr( $html_attributes['class'] ) : '';
                $aria_hidden = isset( $html_attributes['aria-hidden'] ) ? ' aria-hidden="' . esc_attr( $html_attributes['aria-hidden'] ) . '"' : ' aria-hidden="true"';

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_class, $html_class and $aria_hidden are esc_attr()'d where they are built above.
                echo '<span class="' . $icon_class . $html_class . '"' . $aria_hidden . '></span>';
                return;
            }
        }

        // Handle custom CSS classes (like 'bp-icon bp-check-circle')
        // These are custom icon fonts or CSS-based icons defined by the theme/plugin
        if ( 'custom' === $library ) {
            if ( is_string( $value ) && ! empty( $value ) ) {
                $html_class = ! empty( $html_attributes['class'] ) ? ' ' . esc_attr( $html_attributes['class'] ) : '';
                $aria_hidden = isset( $html_attributes['aria-hidden'] ) ? ' aria-hidden="' . esc_attr( $html_attributes['aria-hidden'] ) . '"' : ' aria-hidden="true"';

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $html_class and $aria_hidden are esc_attr()'d where they are built above.
                echo '<i class="' . esc_attr( $value ) . $html_class . '"' . $aria_hidden . '></i>';
                return;
            }
        }

        // If value is a plain string (might be a FontAwesome class without library specified)
        if ( is_string( $value ) && ! empty( $value ) && empty( $library ) ) {
            // Check if it looks like a FontAwesome class
            if ( strpos( $value, 'fa' ) !== false || strpos( $value, 'fa-' ) !== false ) {
                $icon_class = 'fas ' . esc_attr( $value );

                $html_class = ! empty( $html_attributes['class'] ) ? ' ' . esc_attr( $html_attributes['class'] ) : '';
                $aria_hidden = isset( $html_attributes['aria-hidden'] ) ? ' aria-hidden="' . esc_attr( $html_attributes['aria-hidden'] ) . '"' : ' aria-hidden="true"';

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_class, $html_class and $aria_hidden are esc_attr()'d where they are built above.
                echo '<i class="' . $icon_class . $html_class . '"' . $aria_hidden . '></i>';
                return;
            }

            // Check if it looks like a dashicon
            if ( strpos( $value, 'dashicon' ) !== false || strpos( $value, 'dashicons-' ) !== false ) {
                $icon_class = 'dashicons ' . esc_attr( $value );

                $html_class = ! empty( $html_attributes['class'] ) ? ' ' . esc_attr( $html_attributes['class'] ) : '';
                $aria_hidden = isset( $html_attributes['aria-hidden'] ) ? ' aria-hidden="' . esc_attr( $html_attributes['aria-hidden'] ) . '"' : ' aria-hidden="true"';

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_class, $html_class and $aria_hidden are esc_attr()'d where they are built above.
                echo '<span class="' . $icon_class . $html_class . '"' . $aria_hidden . '></span>';
                return;
            }
        }
    }
}
