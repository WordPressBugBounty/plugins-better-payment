<?php

namespace Better_Payment\Lite\WooCommerce;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Handler;
use Better_Payment\Lite\Classes\StripeService;
use Better_Payment\Lite\Models\SubscriptionRelationModel;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Better Payment subscriptions for WooCommerce — recurring payments over the
 * Better Payment (Stripe) gateway.
 *
 * Self-contained and BP-native: products opt in via Better Payment product
 * meta, subscription state lives as order meta on the parent order (no new
 * tables, no CPT), and WP-Cron drives renewals. The first checkout saves a
 * reusable Stripe customer + payment method (`setup_future_usage`, see
 * Gateway::process_payment()); each due renewal creates a pending WooCommerce
 * order and charges it server-side with an off-session PaymentIntent through
 * StripeService. Every renewal is recorded in the Better Payment transactions
 * table (`referer=woocommerce`) exactly like a first payment.
 *
 * Lifecycle (all state on the PARENT order):
 *   active   — renewals are charged automatically when due.
 *   past_due — a renewal charge failed; auto-charging stops and the customer
 *              is invoiced the pending renewal order. Paying it (any route
 *              through this gateway) reactivates the subscription.
 *   cancelled — terminal; set from the admin order action, the customer's
 *              My Account cancel button (when the product allows it), or the
 *              cancel() API.
 *   completed — terminal; not set by any core flow (the renewal-cap feature
 *              was removed). The status, its label/badge, the completed
 *              email and the role sync remain so an integration may still
 *              complete a subscription (set the status meta and fire
 *              `better_payment/woocommerce/subscription_completed`).
 *
 * A subscription purchase is locked to THIS gateway at checkout: with a
 * subscription product in the cart (or a renewal invoice / subscription order
 * on the order-pay page) every other gateway is removed from the available
 * list, because no other gateway can save the reusable off-session payment
 * method renewals are charged with (see restrict_available_gateways()).
 *
 * A subscription is also bought ON ITS OWN: the cart may hold one subscription
 * product, never a second one and never a one-off product beside it, because
 * every subscription fact above hangs off a single parent order (see
 * cart_composition_error()).
 *
 * Only loaded when WooCommerce is active (see Loader::register()).
 *
 * @since 2.4.0
 */
class Subscriptions {

    /**
     * Product meta: 'yes' marks a product as a Better Payment subscription.
     */
    const PRODUCT_ENABLED_META = '_bp_subscription_enabled';

    /**
     * Product meta: billing interval count (int >= 1).
     */
    const PRODUCT_INTERVAL_META = '_bp_subscription_interval';

    /**
     * Product meta: billing period (day|week|month|year).
     */
    const PRODUCT_PERIOD_META = '_bp_subscription_period';

    /**
     * Product meta: free trial duration in days (int >= 0, 0 = no trial).
     * An eligible customer pays NOTHING at checkout (the line
     * calculates at $0 via the calculation-scoped
     * zero_trial_price() filter — the displayed price stays real) and the
     * FIRST payment is charged when the trial ends — the paid schedule
     * starts after the trial, it is not "first cycle + trial". Eligibility
     * is one trial per customer per product (trial_eligible()); a returning
     * subscriber pays the regular price at checkout and renews on the plain
     * schedule. Card collection on a $0 order happens through a Stripe
     * setup-mode Checkout Session (Gateway::process_payment()).
     */
    const PRODUCT_TRIAL_DAYS_META = '_bp_subscription_trial_days';

    /**
     * Parent-order meta: 'yes' when the free trial was actually applied to
     * this order (customer was eligible and checked out at $0). Absent for
     * paid checkouts — including trial products bought by a returning
     * subscriber.
     */
    const TRIAL_APPLIED_META = '_bp_subscription_trial_applied';

    /**
     * Product meta: custom Add to Cart button text for the subscription
     * product ('' = WooCommerce's default label). Applied on both the single
     * product page and the shop-loop button.
     */
    const BUTTON_TEXT_META = '_bp_subscription_button_text';

    /**
     * Product meta AND parent-order snapshot: 'yes' lets the customer cancel
     * the subscription from their My Account order page.
     */
    const USER_CANCEL_META = '_bp_subscription_user_cancel';

    /**
     * Parent-order meta: number of renewal payments settled so far.
     */
    const RENEWAL_COUNT_META = '_bp_subscription_renewal_count';

    /**
     * Parent-order meta: subscription status
     * (active|past_due|cancelled|completed).
     */
    const STATUS_META = '_bp_subscription_status';

    /**
     * Parent-order meta: next renewal due date (UNIX timestamp, stored as string).
     */
    const NEXT_PAYMENT_META = '_bp_subscription_next_payment';

    /**
     * Parent-order meta: when the most recent renewal payment settled
     * (UNIX timestamp, stored as string). Absent until the first renewal —
     * the initial checkout is the start date, not a renewal payment.
     */
    const LAST_PAYMENT_META = '_bp_subscription_last_payment';

    /**
     * Parent-order meta: who cancelled the subscription — 'customer' or
     * 'admin' — and the acting user's id. Stamped by cancel() when the
     * caller declares the actor, cleared by reactivate() so a subscription
     * cancelled again later never reports a stale actor.
     */
    const CANCELLED_BY_TYPE_META = '_bp_subscription_cancelled_by_type';
    const CANCELLED_BY_META      = '_bp_subscription_cancelled_by';

    /**
     * Parent-order meta: billing schedule snapshot taken at activation, so a
     * later product edit never rewrites a customer's agreed schedule.
     */
    const INTERVAL_META = '_bp_subscription_interval';
    const PERIOD_META   = '_bp_subscription_period';

    /**
     * Order meta: reusable Stripe identifiers saved from the first checkout.
     */
    const CUSTOMER_META       = '_bp_stripe_customer_id';
    const PAYMENT_METHOD_META = '_bp_stripe_payment_method_id';

    /**
     * Renewal-order meta: back-reference to the parent (subscription) order.
     */
    const RENEWAL_PARENT_META = '_bp_subscription_parent';

    /**
     * Parent-order meta: 'no' when the customer switched automatic renewal
     * off from their My Account page ('' / 'yes' = renew automatically).
     * Only meaningful while automatic renewal is enabled site-wide.
     */
    const AUTO_RENEW_META = '_bp_subscription_auto_renew';

    /**
     * Parent-order meta: id of the manual-renewal order that was invoiced
     * and is still awaiting payment. Guards the hourly cron from stacking a
     * new invoice on every run while one is already outstanding.
     */
    const PENDING_RENEWAL_META = '_bp_subscription_pending_renewal';

    /**
     * Option-key prefix of the E-commerce > Subscription settings (Better
     * Payment → Settings → E-commerce → Subscription; defaults in
     * Admin\DB::default_settings()).
     */
    const SETTING_PREFIX = 'better_payment_settings_ecommerce_subscription_';

    /**
     * Cron hook that processes due renewals.
     */
    const CRON_HOOK = 'better_payment_woocommerce_subscriptions_due';

    /**
     * Supported billing periods (strtotime-compatible units).
     *
     * @var string[]
     */
    const PERIODS = array( 'day', 'week', 'month', 'year' );

    /**
     * Wire the feature. Called from Loader::register(), i.e. only when
     * WooCommerce is active.
     *
     * @return void
     */
    public static function register() {
        // Product settings (admin product edit screen, General tab).
        add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'render_product_fields' ) );
        add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_fields' ) );

        // Storefront price label ("$10.00 / month").
        add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'price_html_suffix' ), 10, 2 );

        // Free trial: an eligible customer checks out at $0 (the first
        // payment is charged when the trial ends), and the $0 checkout must
        // still run through this gateway so the card is collected for
        // renewals — WooCommerce skips payment entirely on zero-total
        // carts/orders unless told otherwise.
        //
        // The $0 is scoped to totals CALCULATION only (a get_price filter
        // added before calculate_totals and removed right after): line
        // totals come out $0 while the
        // product's stored/display price stays real. Mutating the cart
        // item's price instead (set_price(0)) made every price reader for
        // the rest of the request see 0 against a real regular price, so
        // the blocks cart displayed the trial as a fake sale — "~~100.00$~~
        // 0.00$ / Save 100.00$" — on a product that is not discounted at all.
        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'add_trial_price_filter' ), 20 );
        add_action( 'woocommerce_calculate_totals', array( __CLASS__, 'remove_trial_price_filter' ) );
        add_action( 'woocommerce_after_calculate_totals', array( __CLASS__, 'remove_trial_price_filter' ) );
        add_filter( 'woocommerce_cart_needs_payment', array( __CLASS__, 'filter_cart_needs_payment' ) );
        add_filter( 'woocommerce_order_needs_payment', array( __CLASS__, 'filter_order_needs_payment' ), 10, 2 );

        // Custom Add to Cart button text (single product page + shop loop).
        add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'filter_add_to_cart_text' ), 10, 2 );
        add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'filter_add_to_cart_text' ), 10, 2 );

        // One subscription per order: a subscription is checked out on its
        // own, never beside a second subscription and never beside a one-off
        // product. Enforced in two layers — refuse the add,
        // and re-check the whole cart on the cart/checkout pages for the
        // compositions add-to-cart validation cannot see. Both hooks are
        // shared by the classic and blocks (Store API) surfaces.
        add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart_composition' ), 10, 2 );
        add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'enforce_cart_composition' ) );

        // Activation + renewal settlement. Both the first payment and every
        // renewal payment (cron-charged or manually paid) converge on the
        // module's payment-complete hook.
        add_action( 'better_payment/woocommerce/payment_complete', array( __CLASS__, 'on_order_paid' ), 10, 2 );

        // Renewal scheduler.
        add_action( self::CRON_HOOK, array( __CLASS__, 'process_due_subscriptions' ) );

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
        }

        register_deactivation_hook( BETTER_PAYMENT_FILE, array( __CLASS__, 'unschedule' ) );

        // Admin: cancel action on the parent order edit screen.
        add_filter( 'woocommerce_order_actions', array( __CLASS__, 'register_order_action' ), 10, 2 );
        add_action( 'woocommerce_order_action_bp_cancel_subscription', array( __CLASS__, 'handle_cancel_action' ) );

        // Customer: cancel button. NOT hooked on
        // `woocommerce_order_details_after_order_table` — that hook fires on
        // both the My Account view-order page and the order-received
        // (thank-you) page, so the button turned up on a plain order receipt
        // and on every order-details screen. Subscription management lives in
        // one place: My Account > Subscriptions, whose single-subscription
        // view calls the renderer directly (MyAccount::render_view()). Only
        // the POST handler is hooked here.
        add_action( 'template_redirect', array( __CLASS__, 'maybe_handle_customer_cancel' ) );

        // Checkout: a subscription purchase can only go through this gateway.
        // Any other gateway (COD, bank transfer, another Stripe plugin, …)
        // cannot save the reusable off-session payment method renewals are
        // charged with, so the subscription would activate and then never
        // renew. Covers the cart/checkout (classic + blocks, which share this
        // filter) and the order-pay page (invoiced renewals + pending
        // subscription orders).
        add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'restrict_available_gateways' ) );

        // Customer Auto-Renew Control setting: customer-facing automatic-renewal
        // on/off. Rendered only from My Account > Subscriptions, exactly like
        // the cancel button above — never on an order-details screen.
        add_action( 'template_redirect', array( __CLASS__, 'maybe_handle_auto_renew_toggle' ) );

        // Subscriber Default/Inactive Role settings: keep the customer's
        // role in sync with their subscription's lifecycle.
        add_action( 'better_payment/woocommerce/subscription_activated', array( __CLASS__, 'assign_active_role' ) );
        add_action( 'better_payment/woocommerce/subscription_cancelled', array( __CLASS__, 'assign_inactive_role' ) );
        add_action( 'better_payment/woocommerce/subscription_completed', array( __CLASS__, 'assign_inactive_role' ) );
    }

    /**
     * Clear the renewal cron event on plugin deactivation.
     *
     * @return void
     */
    public static function unschedule() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    /* ---------------------------------------------------------------------
     * E-commerce > Subscription settings
     * ------------------------------------------------------------------- */

    /**
     * Read one E-commerce > Subscription setting. Defaults resolve through
     * DB::default_settings(), so an unsaved install behaves like the
     * documented defaults.
     *
     * @param string $key Setting key without the prefix (e.g. 'renewal_process').
     * @return string
     */
    public static function setting( $key ) {
        return (string) DB::get_settings( self::SETTING_PREFIX . $key );
    }

    /**
     * Pure: whether automatic (off-session) renewals are enabled site-wide.
     * A manual Renewal Mode OR Automatic Stripe Charging switched off both mean
     * every renewal is invoiced for manual payment instead of being charged.
     * Unknown values fall back to enabled — the defaults' behavior.
     *
     * @param mixed $renewal_process   The renewal_process setting (auto|manual).
     * @param mixed $stripe_auto_renew The stripe_auto_renew setting (yes|no).
     * @return bool
     */
    public static function auto_renewal_globally_enabled( $renewal_process, $stripe_auto_renew ) {
        return 'manual' !== (string) $renewal_process && 'no' !== (string) $stripe_auto_renew;
    }

    /**
     * Whether checkout should ask Stripe to keep the payment method
     * reusable for off-session renewals. With a site-wide manual renewal
     * policy the card would be stored without ever being charged — so it
     * isn't stored at all.
     *
     * @return bool
     */
    public static function should_save_payment_method() {
        return self::auto_renewal_globally_enabled( self::setting( 'renewal_process' ), self::setting( 'stripe_auto_renew' ) );
    }

    /**
     * Whether THIS subscription renews automatically: the site-wide policy
     * AND the customer's own auto-renewal preference (AUTO_RENEW_META,
     * default on).
     *
     * @param \WC_Order $parent Parent (subscription) order.
     * @return bool
     */
    public static function auto_renewal_enabled_for( $parent ) {
        return self::should_save_payment_method()
            && 'no' !== (string) $parent->get_meta( self::AUTO_RENEW_META );
    }

    /* ---------------------------------------------------------------------
     * Product settings
     * ------------------------------------------------------------------- */

    /**
     * Render the subscription fields on the product edit screen.
     *
     * The checkbox is always visible; every dependent field lives inside
     * `.bp-subscription-settings-fields`, toggled by the inline script below
     * so the group only shows while the product is a subscription. The
     * billing schedule is ONE field — interval count + period dropdown
     * inline ("Renew Every [3] [Month]") — not two stacked rows.
     *
     * @return void
     */
    public static function render_product_fields() {
        global $post;

        if ( ! function_exists( 'woocommerce_wp_checkbox' ) || ! $post ) {
            return;
        }

        $enabled  = get_post_meta( $post->ID, self::PRODUCT_ENABLED_META, true );
        $interval = max( 1, (int) get_post_meta( $post->ID, self::PRODUCT_INTERVAL_META, true ) );
        $period   = self::sanitize_period( get_post_meta( $post->ID, self::PRODUCT_PERIOD_META, true ) );
        $periods  = array(
            'day'   => __( 'Day', 'better-payment' ),
            'week'  => __( 'Week', 'better-payment' ),
            'month' => __( 'Month', 'better-payment' ),
            'year'  => __( 'Year', 'better-payment' ),
        );

        // WooCommerce (7.0+ admin styles) gives every non-checkbox
        // .form-field label in the options panel `line-height: 40px` to
        // vertically center a ONE-line label against its 40px input. A label
        // that wraps at the panel's 150px label column ("Free Days Before
        // First Charge") gets 40px per LINE — a huge gap between the two
        // words of one label. Restore a normal line-height and re-center
        // with padding instead, scoped to BP's own wrapper so no other
        // plugin's (or WooCommerce's) fields are touched. The selector
        // mirrors WooCommerce's own — same `.wc-wp-version-gte-70` guard,
        // same `:not(:has(checkbox/radio))` carve-out — so it outranks it
        // exactly where it applies and applies nowhere else.
        ?>
        <style>
            .wc-wp-version-gte-70 .woocommerce_options_panel .bp-subscription-options .form-field:not(:has(input[type=checkbox], input[type=radio])) label {
                line-height: 1.5;
                padding-top: 10px;
            }
        </style>
        <?php

        echo '<div class="options_group bp-subscription-options">';

        woocommerce_wp_checkbox(
            array(
                'id'          => self::PRODUCT_ENABLED_META,
                'label'       => __( 'Better Payment Subscription', 'better-payment' ),
                'description' => __( 'Bill this product on a recurring schedule through the Better Payment (Stripe) gateway.', 'better-payment' ),
            )
        );

        echo '<div class="bp-subscription-settings-fields"' . ( 'yes' === $enabled ? '' : ' style="display:none;"' ) . '>';

        ?>
        <p class="form-field bp-subscription-schedule-field">
            <label for="<?php echo esc_attr( self::PRODUCT_INTERVAL_META ); ?>"><?php esc_html_e( 'Renew Every', 'better-payment' ); ?></label>
            <input
                type="number"
                id="<?php echo esc_attr( self::PRODUCT_INTERVAL_META ); ?>"
                name="<?php echo esc_attr( self::PRODUCT_INTERVAL_META ); ?>"
                value="<?php echo esc_attr( (string) $interval ); ?>"
                min="1"
                step="1"
                style="width: 80px; margin-right: 8px;"
            />
            <select
                id="<?php echo esc_attr( self::PRODUCT_PERIOD_META ); ?>"
                name="<?php echo esc_attr( self::PRODUCT_PERIOD_META ); ?>"
                style="width: auto;"
            >
                <?php foreach ( $periods as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $period, $value ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php echo wp_kses_post( wc_help_tip( __( 'Billing schedule — e.g. "3" + "Month" renews every 3 months.', 'better-payment' ) ) ); ?>
        </p>
        <?php

        woocommerce_wp_text_input(
            array(
                'id'                => self::PRODUCT_TRIAL_DAYS_META,
                'label'             => __( 'Free Days Before First Charge', 'better-payment' ),
                'type'              => 'number',
                'value'             => (string) max( 0, (int) get_post_meta( $post->ID, self::PRODUCT_TRIAL_DAYS_META, true ) ),
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '1',
                ),
                'desc_tip'          => true,
                'description'       => __( 'The customer pays nothing at checkout; the first payment is charged when the trial ends. One trial per customer per product — returning subscribers pay the regular price. 0 = no trial.', 'better-payment' ),
            )
        );

        woocommerce_wp_checkbox(
            array(
                'id'          => self::USER_CANCEL_META,
                'label'       => __( 'Customer Can Cancel', 'better-payment' ),
                'description' => __( 'Shows a Cancel button on this subscription under My Account → Subscriptions, so the buyer can stop future renewals without contacting you.', 'better-payment' ),
            )
        );

        woocommerce_wp_text_input(
            array(
                'id'          => self::BUTTON_TEXT_META,
                'label'       => __( 'Add to Cart Button Label', 'better-payment' ),
                'type'        => 'text',
                'value'       => (string) get_post_meta( $post->ID, self::BUTTON_TEXT_META, true ),
                'placeholder' => __( 'e.g. Subscribe Now', 'better-payment' ),
                'desc_tip'    => true,
                'description' => __( 'Replaces the Add to Cart wording for this product on both the product page and the shop listings. Leave blank to keep the WooCommerce default.', 'better-payment' ),
            )
        );

        echo '</div>';
        echo '</div>';

        ?>
        <script>
            jQuery( function ( $ ) {
                var toggleBpSubscriptionFields = function () {
                    $( '.bp-subscription-settings-fields' ).toggle(
                        $( '#<?php echo esc_js( self::PRODUCT_ENABLED_META ); ?>' ).is( ':checked' )
                    );
                };

                $( '#<?php echo esc_js( self::PRODUCT_ENABLED_META ); ?>' ).on( 'change', toggleBpSubscriptionFields );
                toggleBpSubscriptionFields();
            } );
        </script>
        <?php
    }

    /**
     * Persist the subscription fields. Runs inside WooCommerce's own
     * nonce-verified product save.
     *
     * @param \WC_Product $product The product being saved.
     * @return void
     */
    public static function save_product_fields( $product ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product-save nonce before this hook fires.
        $enabled     = isset( $_POST[ self::PRODUCT_ENABLED_META ] ) ? 'yes' : 'no';
        $interval    = isset( $_POST[ self::PRODUCT_INTERVAL_META ] ) ? max( 1, absint( wp_unslash( $_POST[ self::PRODUCT_INTERVAL_META ] ) ) ) : 1;
        $period      = isset( $_POST[ self::PRODUCT_PERIOD_META ] ) ? self::sanitize_period( sanitize_text_field( wp_unslash( $_POST[ self::PRODUCT_PERIOD_META ] ) ) ) : 'month';
        $trial_days  = isset( $_POST[ self::PRODUCT_TRIAL_DAYS_META ] ) ? absint( wp_unslash( $_POST[ self::PRODUCT_TRIAL_DAYS_META ] ) ) : 0;
        $user_cancel = isset( $_POST[ self::USER_CANCEL_META ] ) ? 'yes' : 'no';
        $button_text = isset( $_POST[ self::BUTTON_TEXT_META ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::BUTTON_TEXT_META ] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $product->update_meta_data( self::PRODUCT_ENABLED_META, $enabled );
        $product->update_meta_data( self::PRODUCT_INTERVAL_META, (string) $interval );
        $product->update_meta_data( self::PRODUCT_PERIOD_META, $period );
        $product->update_meta_data( self::PRODUCT_TRIAL_DAYS_META, (string) $trial_days );
        $product->update_meta_data( self::USER_CANCEL_META, $user_cancel );
        $product->update_meta_data( self::BUTTON_TEXT_META, $button_text );
    }

    /**
     * Append the billing schedule to a subscription product's price HTML.
     *
     * @param string      $price   Price HTML.
     * @param \WC_Product $product Product.
     * @return string
     */
    public static function price_html_suffix( $price, $product ) {
        if ( ! self::product_is_subscription( $product ) || '' === (string) $price ) {
            return $price;
        }

        $interval   = max( 1, (int) $product->get_meta( self::PRODUCT_INTERVAL_META ) );
        $period     = self::sanitize_period( $product->get_meta( self::PRODUCT_PERIOD_META ) );
        $trial_days = max( 0, (int) $product->get_meta( self::PRODUCT_TRIAL_DAYS_META ) );

        $label = sprintf(
            /* translators: 1: price HTML, 2: billing schedule (e.g. "month" or "every 3 months") */
            __( '%1$s / %2$s', 'better-payment' ),
            $price,
            self::describe_schedule( $interval, $period )
        );

        if ( $trial_days > 0 ) {
            $label .= ' ' . sprintf(
                /* translators: %d: number of free trial days before the first payment */
                _n( 'with a %d-day free trial', 'with a %d-day free trial', $trial_days, 'better-payment' ),
                $trial_days
            );
        }

        return $label;
    }

    /**
     * Replace the Add to Cart label with the product's custom button text.
     * Applies only to subscription products with a non-blank custom text;
     * every other product keeps WooCommerce's own label.
     *
     * @param string $text    The default button text.
     * @param mixed  $product The product.
     * @return string
     */
    public static function filter_add_to_cart_text( $text, $product ) {
        if ( ! self::product_is_subscription( $product ) ) {
            return $text;
        }

        $custom = trim( (string) $product->get_meta( self::BUTTON_TEXT_META ) );

        return '' !== $custom ? $custom : $text;
    }

    /* ---------------------------------------------------------------------
     * Detection + pure helpers
     * ------------------------------------------------------------------- */

    /**
     * Whether a product is a Better Payment subscription product.
     *
     * @param mixed $product Product (or anything else — safely rejected).
     * @return bool
     */
    public static function product_is_subscription( $product ) {
        return $product instanceof \WC_Product && 'yes' === $product->get_meta( self::PRODUCT_ENABLED_META );
    }

    /**
     * The order's line items whose product is a subscription product.
     *
     * @param mixed $order Order (or anything else — safely rejected).
     * @return \WC_Order_Item_Product[]
     */
    public static function order_subscription_items( $order ) {
        $items = array();

        if ( ! $order instanceof \WC_Order ) {
            return $items;
        }

        foreach ( $order->get_items() as $item ) {
            if ( ! $item instanceof \WC_Order_Item_Product ) {
                continue;
            }

            if ( self::product_is_subscription( $item->get_product() ) ) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Whether the order contains at least one subscription product.
     *
     * @param \WC_Order $order Order.
     * @return bool
     */
    public static function order_contains_subscription( $order ) {
        return count( self::order_subscription_items( $order ) ) > 0;
    }

    /**
     * Whether the current cart contains at least one subscription product.
     *
     * @return bool
     */
    public static function cart_contains_subscription() {
        if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
            return false;
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['data'] ) && self::product_is_subscription( $cart_item['data'] ) ) {
                return true;
            }
        }

        return false;
    }

    /* ---------------------------------------------------------------------
     * Cart composition (one subscription per order)
     * ------------------------------------------------------------------- */

    /**
     * Whether the one-subscription-per-order rule is enforced. Read live on
     * every check so a site can opt out per request.
     *
     * @return bool
     */
    public static function single_subscription_cart_enforced() {
        /**
         * Filters whether a cart may hold only one subscription and nothing
         * else. Turning this off does NOT make the engine multi-subscription
         * — see cart_composition_error() for what actually breaks.
         *
         * @since 2.4.0
         *
         * @param bool $enforced Default true.
         */
        return (bool) apply_filters( 'better_payment/woocommerce/single_subscription_cart', true );
    }

    /**
     * Pure: the storefront error a cart composition produces, or '' when the
     * cart may be checked out.
     *
     * A Better Payment subscription is bought on its own because every
     * subscription fact lives on ONE parent order: activate_subscription()
     * snapshots the schedule from a single item, one Stripe payment method is
     * stored per order, and create_renewal_order() re-adds that order's
     * subscription products on every cycle. So a second subscription in the
     * same order would be billed on the first one's schedule, and a one-off
     * product would be silently re-charged at every renewal.
     *
     * Identity is the line's PRODUCT id, not the line: two lines of the same
     * subscription product (two variations, or a re-add) are one subscription
     * bought more than once — which the cart quantity field already allows —
     * so only a second, DIFFERENT subscription product is refused.
     *
     * @param mixed $lines Cart lines as array( 'product_id' => int, 'is_subscription' => bool ).
     * @return string Error message, or '' when the composition is allowed.
     */
    public static function cart_composition_error( $lines ) {
        $subscriptions = array();
        $others        = 0;

        foreach ( (array) $lines as $line ) {
            if ( ! is_array( $line ) ) {
                continue;
            }

            if ( empty( $line['is_subscription'] ) ) {
                ++$others;
                continue;
            }

            $product_id                   = isset( $line['product_id'] ) ? (int) $line['product_id'] : 0;
            $subscriptions[ $product_id ] = true;
        }

        if ( count( $subscriptions ) > 1 ) {
            return __( 'Only one subscription can be purchased at a time. Please remove the other subscription from your cart and buy it separately.', 'better-payment' );
        }

        if ( count( $subscriptions ) > 0 && $others > 0 ) {
            return __( 'A subscription has to be purchased on its own. Please remove the other products from your cart and buy them separately.', 'better-payment' );
        }

        return '';
    }

    /**
     * The current cart described for cart_composition_error(). Empty (and so
     * always valid) without WooCommerce.
     *
     * The key is `product_id` — the PARENT id for a variation — because that
     * is what `woocommerce_add_to_cart_validation` reports for the product
     * being added, and the two must be comparable.
     *
     * @return array
     */
    public static function cart_composition_lines() {
        $lines = array();

        if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
            return $lines;
        }

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $lines[] = array(
                'product_id'      => isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0,
                'is_subscription' => self::product_is_subscription( isset( $cart_item['data'] ) ? $cart_item['data'] : null ),
            );
        }

        return $lines;
    }

    /**
     * Refuse an add to cart that would break the one-subscription-per-order
     * rule (`woocommerce_add_to_cart_validation`).
     *
     * The Store API applies this same filter and converts the notice into its
     * error response, so the blocks cart is covered without a second hook.
     *
     * @param bool $passed     Validation result so far.
     * @param int  $product_id Product being added (parent id for a variation).
     * @return bool
     */
    public static function validate_add_to_cart_composition( $passed, $product_id ) {
        if ( ! $passed || ! function_exists( 'wc_get_product' ) || ! self::single_subscription_cart_enforced() ) {
            return $passed;
        }

        $lines   = self::cart_composition_lines();
        $lines[] = array(
            'product_id'      => (int) $product_id,
            'is_subscription' => self::product_is_subscription( wc_get_product( $product_id ) ),
        );

        $error = self::cart_composition_error( $lines );

        if ( '' === $error ) {
            return $passed;
        }

        wc_add_notice( $error, 'error' );

        return false;
    }

    /**
     * Re-check the whole cart's composition (`woocommerce_check_cart_items`,
     * which runs on the cart AND checkout pages, classic and blocks).
     *
     * Catches what add-to-cart validation cannot: a cart filled before the
     * product became a subscription, an order-again, or any add that skipped
     * validation. The notice blocks checkout until a line is removed — the
     * cart is never emptied for the customer.
     *
     * @return void
     */
    public static function enforce_cart_composition() {
        if ( ! self::single_subscription_cart_enforced() ) {
            return;
        }

        $error = self::cart_composition_error( self::cart_composition_lines() );

        if ( '' !== $error ) {
            wc_add_notice( $error, 'error' );
        }
    }

    /* ---------------------------------------------------------------------
     * Free trial ($0 at checkout, first payment at trial end)
     * ------------------------------------------------------------------- */

    /**
     * Whether the current customer may use a product's free trial. One trial
     * per customer per product: anyone who has (or ever had) a Better
     * Payment subscription containing the product pays the regular price —
     * otherwise cancelling and re-subscribing would chain free trials
     * forever. Guests cannot be checked and are given the benefit of the
     * doubt.
     *
     * @param mixed $product The product (non-products are ineligible).
     * @return bool
     */
    public static function trial_eligible( $product ) {
        $product_id = $product instanceof \WC_Product ? (int) $product->get_id() : 0;
        $user_id    = (int) get_current_user_id();

        $eligible = $product_id > 0 && ! self::customer_has_subscription_for_product( $user_id, $product_id );

        /**
         * Filters whether the current customer is eligible for a
         * subscription product's free trial.
         *
         * @since 2.4.0
         *
         * @param bool $eligible   Eligibility resolved so far.
         * @param int  $product_id Product id.
         * @param int  $user_id    Current user id (0 = guest).
         */
        return (bool) apply_filters( 'better_payment/woocommerce/trial_eligible', $eligible, $product_id, $user_id );
    }

    /**
     * Whether a customer has (or had) a Better Payment subscription order
     * containing the product. Scans the customer's subscription parent
     * orders (STATUS_META present); any status counts — a cancelled trial
     * still used up the trial. Cached per request; the scan is bounded and
     * runs only for logged-in customers on trial products.
     *
     * @param int $user_id    Customer user id (0 = guest, never matches).
     * @param int $product_id Product (or variation) id.
     * @return bool
     */
    public static function customer_has_subscription_for_product( $user_id, $product_id ) {
        static $cache = array();

        $user_id    = (int) $user_id;
        $product_id = (int) $product_id;

        if ( $user_id < 1 || $product_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
            return false;
        }

        $key = $user_id . ':' . $product_id;

        if ( isset( $cache[ $key ] ) ) {
            return $cache[ $key ];
        }

        $orders = wc_get_orders(
            array(
                'customer_id' => $user_id,
                'limit'       => 100,
                'type'        => 'shop_order',
                'return'      => 'objects',
                'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded (limit 100), per-request cached, trial products only.
                    array(
                        'key'     => self::STATUS_META,
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );

        $found = false;

        if ( is_array( $orders ) ) {
            foreach ( $orders as $order ) {
                foreach ( self::order_subscription_items( $order ) as $item ) {
                    if ( (int) $item->get_product_id() === $product_id || (int) $item->get_variation_id() === $product_id ) {
                        $found = true;
                        break 2;
                    }
                }
            }
        }

        $cache[ $key ] = $found;

        return $found;
    }

    /**
     * One page of a customer's subscription parent orders, newest first —
     * the My Account > Subscriptions list query. Ownership is enforced here
     * (customer_id), so a caller can never page through someone else's
     * subscriptions. Renewal orders never match: the subscription status
     * meta only exists on parent orders.
     *
     * @param int $user_id  Customer user id.
     * @param int $page     1-based page number.
     * @param int $per_page Orders per page.
     * @return array { @type \WC_Order[] $orders, @type int $total, @type int $max_pages }
     * @since 2.4.0
     */
    public static function customer_subscriptions( $user_id, $page = 1, $per_page = 10 ) {
        $empty = array(
            'orders'    => array(),
            'total'     => 0,
            'max_pages' => 0,
        );

        if ( ! function_exists( 'wc_get_orders' ) || (int) $user_id <= 0 ) {
            return $empty;
        }

        $result = wc_get_orders(
            array(
                'customer_id' => (int) $user_id,
                'type'        => 'shop_order',
                'limit'       => max( 1, (int) $per_page ),
                'paged'       => max( 1, (int) $page ),
                'paginate'    => true,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'return'      => 'objects',
                'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the status meta only exists on subscription parent orders; this is the intended lookup.
                    array(
                        'key'     => self::STATUS_META,
                        'compare' => 'EXISTS',
                    ),
                ),
            )
        );

        if ( ! is_object( $result ) || ! isset( $result->orders ) ) {
            return $empty;
        }

        return array(
            'orders'    => is_array( $result->orders ) ? $result->orders : array(),
            'total'     => isset( $result->total ) ? (int) $result->total : 0,
            'max_pages' => isset( $result->max_num_pages ) ? (int) $result->max_num_pages : 0,
        );
    }

    /**
     * The subscription's pending renewal invoice, when one is awaiting
     * payment (a past_due recovery or a manual-policy renewal). Null when
     * there is none, it is gone, or it no longer needs payment.
     *
     * @param mixed $parent Parent (subscription) order.
     * @return \WC_Order|null
     * @since 2.4.0
     */
    public static function pending_renewal_order( $parent ) {
        if ( ! $parent instanceof \WC_Order || ! function_exists( 'wc_get_order' ) ) {
            return null;
        }

        $pending_id = (int) $parent->get_meta( self::PENDING_RENEWAL_META );

        if ( $pending_id <= 0 ) {
            return null;
        }

        $pending = wc_get_order( $pending_id );

        if ( $pending instanceof \WC_Order && $pending->needs_payment() ) {
            return $pending;
        }

        return null;
    }

    /**
     * Start scoping trial pricing to the totals calculation: while this
     * filter is attached, get_price() answers 0 for trial-eligible
     * subscription products, so an eligible customer's line totals compute
     * to $0 — the first payment is charged when the trial ends (see
     * activate_subscription()). Attached on `woocommerce_before_calculate_totals`
     * and detached on `woocommerce_calculate_totals` /
     * `woocommerce_after_calculate_totals`, so display price readers (the
     * Store API's cart item prices, price HTML, the blocks cart's
     * sale/"Save" detection) always see the product's real price. The
     * product object is never mutated — mutating it (the pre-2.4.0 draft's
     * set_price(0)) made the blocks cart render the trial as a fake
     * 100%-off sale. Renewal orders (built server-side from the product's
     * real price) are never affected.
     *
     * @return void
     */
    public static function add_trial_price_filter() {
        add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'zero_trial_price' ), 100, 2 );
    }

    /**
     * Stop scoping trial pricing — the counterpart of
     * add_trial_price_filter(), run as soon as totals calculation ends.
     *
     * @return void
     */
    public static function remove_trial_price_filter() {
        remove_filter( 'woocommerce_product_get_price', array( __CLASS__, 'zero_trial_price' ), 100 );
    }

    /**
     * Filter callback for `woocommerce_product_get_price` while totals are
     * being calculated: 0 for a trial-eligible subscription product with a
     * trial configured, the real price for everything else.
     *
     * @param mixed $price   The product price.
     * @param mixed $product The product.
     * @return mixed
     */
    public static function zero_trial_price( $price, $product ) {
        if ( ! self::product_is_subscription( $product ) ) {
            return $price;
        }

        return self::trial_zeroed_price(
            $price,
            (int) $product->get_meta( self::PRODUCT_TRIAL_DAYS_META ),
            self::trial_eligible( $product )
        );
    }

    /**
     * Pure: the price a subscription product's line calculates at, given
     * its trial configuration and the customer's eligibility. Only a
     * configured trial (>= 1 day) AND an eligible customer zero the price.
     *
     * @param mixed $price      The product's real price.
     * @param mixed $trial_days Free trial duration in days.
     * @param bool  $eligible   Whether the customer may use the trial.
     * @return mixed 0 when the trial applies, the untouched price otherwise.
     */
    public static function trial_zeroed_price( $price, $trial_days, $eligible ) {
        if ( (int) $trial_days < 1 || ! $eligible ) {
            return $price;
        }

        return 0;
    }

    /**
     * Filter callback for `woocommerce_cart_needs_payment`: a subscription
     * cart always goes through payment, even at $0 — the gateway must run to
     * collect the card (setup-mode session) or to activate the subscription
     * (manual renewal policy). WooCommerce would otherwise complete a free
     * order without ever calling a gateway.
     *
     * @param mixed $needs WooCommerce's own answer.
     * @return bool
     */
    public static function filter_cart_needs_payment( $needs ) {
        return (bool) $needs || self::cart_contains_subscription();
    }

    /**
     * Filter callback for `woocommerce_order_needs_payment` — the order-side
     * twin of filter_cart_needs_payment(), so the receipt page, the
     * return-URL verification and on_payment_confirmed() treat a pending $0
     * trial order as payable instead of skipping it.
     *
     * @param mixed $needs WooCommerce's own answer.
     * @param mixed $order The order.
     * @return bool
     */
    public static function filter_order_needs_payment( $needs, $order ) {
        if ( $needs || ! $order instanceof \WC_Order ) {
            return (bool) $needs;
        }

        return self::needs_payment_override(
            (float) $order->get_total(),
            $order->has_status( array( 'pending', 'failed' ) ),
            self::order_contains_subscription( $order )
        );
    }

    /**
     * The id of the ONLY gateway a subscription purchase may use.
     *
     * Resolves through Gateway::GATEWAY_ID when the gateway class is loadable.
     * Gateway extends WC_Payment_Gateway, so without WooCommerce the class
     * cannot load at all (the test environment runs WC-free) — the literal
     * fallback is pinned to the constant by SubscriptionsPureTest so the two
     * can never drift.
     *
     * @return string
     */
    public static function gateway_id() {
        if ( class_exists( 'WC_Payment_Gateway' ) ) {
            return Gateway::GATEWAY_ID;
        }

        return 'better_payment_stripe';
    }

    /**
     * Pure: reduce an available-gateways list to the Better Payment gateway
     * alone when the purchase requires it.
     *
     * When the Better Payment gateway is not itself in the list (disabled or
     * unconfigured), the result is deliberately EMPTY — WooCommerce then shows
     * its "no payment methods available" notice. Letting another gateway
     * through instead would sell a subscription that can never renew.
     *
     * @param mixed $gateways Available gateways (id => gateway).
     * @param bool  $restrict Whether the purchase must use the BP gateway.
     * @return mixed
     */
    public static function filter_gateways_for_subscription( $gateways, $restrict ) {
        if ( ! $restrict || ! is_array( $gateways ) ) {
            return $gateways;
        }

        return array_intersect_key( $gateways, array( self::gateway_id() => true ) );
    }

    /**
     * Whether the purchase being paid for right now must use the BP gateway:
     * a subscription product in the cart, or — on the order-pay page — an
     * order that is a renewal invoice or contains a subscription product.
     *
     * @return bool
     */
    public static function checkout_requires_bp_gateway() {
        // Order-pay endpoint: manually paying an invoiced renewal (the
        // past_due recovery path) or a pending subscription order. The cart
        // is irrelevant here — the order alone decides.
        if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
            $order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( get_query_var( 'order-pay' ) ) ) : false;

            if ( ! $order instanceof \WC_Order ) {
                return false;
            }

            return (int) $order->get_meta( self::RENEWAL_PARENT_META ) > 0 || self::order_contains_subscription( $order );
        }

        return self::cart_contains_subscription();
    }

    /**
     * Filter callback for `woocommerce_available_payment_gateways`.
     *
     * @param mixed $gateways Available gateways (id => gateway).
     * @return mixed
     */
    public static function restrict_available_gateways( $gateways ) {
        // The WooCommerce settings screens run this filter too — the shop
        // owner must always see every gateway there.
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $gateways;
        }

        return self::filter_gateways_for_subscription( $gateways, self::checkout_requires_bp_gateway() );
    }

    /**
     * Pure: add the off-session reuse instruction to a Checkout Session
     * request built by OrderHandler::build_session_request(). Stripe then
     * attaches the payment method to the session's customer for later
     * off-session charges.
     *
     * @param array $request Checkout Session request.
     * @return array
     */
    public static function add_off_session_setup( $request ) {
        if ( ! isset( $request['payment_intent_data'] ) || ! is_array( $request['payment_intent_data'] ) ) {
            $request['payment_intent_data'] = array();
        }

        $request['payment_intent_data']['setup_future_usage'] = 'off_session';

        return $request;
    }

    /**
     * Pure: clamp a billing period to the supported set.
     *
     * @param mixed $period Raw value.
     * @return string
     */
    public static function sanitize_period( $period ) {
        return in_array( $period, self::PERIODS, true ) ? $period : 'month';
    }

    /**
     * Pure: the next renewal timestamp for a schedule.
     *
     * @param int $interval Billing interval count.
     * @param string $period Billing period.
     * @param int $from     Base timestamp (0 = now).
     * @return int
     */
    public static function next_payment_timestamp( $interval, $period, $from = 0 ) {
        $interval = max( 1, (int) $interval );
        $period   = self::sanitize_period( $period );
        $from     = (int) $from > 0 ? (int) $from : time();

        return (int) strtotime( '+' . $interval . ' ' . $period, $from );
    }

    /**
     * Pure: when a free trial ends — the timestamp of the FIRST payment on a
     * trial checkout. Nothing is paid at checkout, so the paid schedule
     * starts at trial end (NOT first cycle + trial).
     *
     * @param int $trial_days Free trial duration in days.
     * @param int $from       Base timestamp (0 = now).
     * @return int
     */
    public static function trial_end_timestamp( $trial_days, $from = 0 ) {
        $from = (int) $from > 0 ? (int) $from : time();

        return $from + ( max( 0, (int) $trial_days ) * DAY_IN_SECONDS );
    }

    /**
     * Pure: whether a zero-total order must still go through payment so the
     * gateway can collect the card. Only pending/failed subscription orders
     * at $0 qualify — everything else keeps WooCommerce's own answer.
     *
     * @param float $total                 Order total.
     * @param bool  $payable_status        Order is in a payable status (pending/failed).
     * @param bool  $contains_subscription Order contains a subscription product.
     * @return bool
     */
    public static function needs_payment_override( $total, $payable_status, $contains_subscription ) {
        return $contains_subscription && $payable_status && (float) $total < 0.01;
    }

    /**
     * Pure: whether a renewal total must be charged through Stripe at all.
     *
     * A renewal can legitimately come due at $0 — create_renewal_order()
     * re-adds the parent's products at their CURRENT price, so a 100% sale
     * price (or a free plan) produces a zero-total renewal order. Stripe
     * rejects a PaymentIntent of amount 0 outright, so attempting the charge
     * turns a perfectly healthy subscription into past_due and emails the
     * customer an invoice for $0. The counterpart of the $0 branch
     * Gateway::process_payment() already has for the FIRST payment.
     *
     * @param float $total Renewal order total.
     * @return bool
     */
    public static function renewal_requires_charge( $total ) {
        return (float) $total >= 0.01;
    }

    /**
     * Pure: build the Stripe setup-mode Checkout Session request for a $0
     * (free-trial) subscription checkout. A setup session collects and saves
     * a card without charging — the counterpart of
     * OrderHandler::build_session_request() for orders with nothing to pay.
     * No line_items / payment_intent_data (invalid in setup mode); the
     * SetupIntent carries the same metadata back-references the engine's
     * payment protocol uses.
     *
     * @param array $data {
     *     @type string $bp_order_id Better Payment order id (stripe_xxx).
     *     @type int    $wc_order_id WooCommerce order id.
     *     @type string $success_url Return URL on success.
     *     @type string $cancel_url  Return URL on cancel.
     *     @type string $customer    Stripe customer id to attach the card to.
     * }
     * @return array
     */
    public static function build_setup_session_request( $data ) {
        $metadata = array(
            'order_id'    => (string) $data['bp_order_id'],
            'wc_order_id' => (string) $data['wc_order_id'],
        );

        $request = array(
            'mode'                 => 'setup',
            'success_url'          => (string) $data['success_url'],
            'cancel_url'           => (string) $data['cancel_url'],
            'locale'               => 'auto',
            'payment_method_types' => array( 'card' ),
            'client_reference_id'  => (string) $data['wc_order_id'],
            'metadata'             => $metadata,
            'setup_intent_data'    => array(
                'metadata' => $metadata,
            ),
        );

        // Without a customer the saved payment method would attach to
        // nothing and be unusable for off-session renewals.
        if ( ! empty( $data['customer'] ) ) {
            $request['customer'] = (string) $data['customer'];
        }

        return $request;
    }

    /**
     * Pure: human description of a schedule ("month", "every 3 months").
     *
     * @param int    $interval Billing interval count.
     * @param string $period   Billing period.
     * @return string
     */
    public static function describe_schedule( $interval, $period ) {
        $interval = max( 1, (int) $interval );
        $period   = self::sanitize_period( $period );

        $singular = array(
            'day'   => __( 'day', 'better-payment' ),
            'week'  => __( 'week', 'better-payment' ),
            'month' => __( 'month', 'better-payment' ),
            'year'  => __( 'year', 'better-payment' ),
        );
        $plural   = array(
            'day'   => __( 'days', 'better-payment' ),
            'week'  => __( 'weeks', 'better-payment' ),
            'month' => __( 'months', 'better-payment' ),
            'year'  => __( 'years', 'better-payment' ),
        );

        if ( 1 === $interval ) {
            return $singular[ $period ];
        }

        return sprintf(
            /* translators: 1: interval count, 2: period plural (e.g. "months") */
            __( 'every %1$d %2$s', 'better-payment' ),
            $interval,
            $plural[ $period ]
        );
    }

    /**
     * Pure: customer-facing label for a subscription status. Unknown values
     * fall back to a readable form of the raw status (underscores to
     * spaces) rather than an empty badge — a status whose key does not
     * match the label map (on_hold vs on-hold) renders exactly that.
     *
     * @param string $status Raw `_bp_subscription_status` meta value.
     * @return string
     * @since 2.4.0
     */
    public static function customer_status_label( $status ) {
        $status = strtolower( (string) $status );

        $map = array(
            'active'    => __( 'Active', 'better-payment' ),
            'past_due'  => __( 'Payment past due', 'better-payment' ),
            'cancelled' => __( 'Cancelled', 'better-payment' ),
            'completed' => __( 'Completed', 'better-payment' ),
        );

        if ( isset( $map[ $status ] ) ) {
            return $map[ $status ];
        }

        return ucfirst( str_replace( '_', ' ', $status ) );
    }

    /**
     * Pure: build the off-session PaymentIntent request for a renewal order.
     * Amount conversion mirrors OrderHandler::build_session_request() (minor
     * units), and metadata carries the same back-references the engine's
     * session protocol uses.
     *
     * @param array $data {
     *     @type string $bp_order_id     Better Payment order id (stripe_xxx).
     *     @type int    $wc_order_id     Renewal WooCommerce order id.
     *     @type int    $parent_order_id Parent (subscription) order id.
     *     @type string $order_number    Renewal order display number.
     *     @type float  $amount          Renewal total (major units).
     *     @type string $currency        ISO currency code.
     *     @type string $customer        Stripe customer id.
     *     @type string $payment_method  Stripe payment method id.
     *     @type string $site_name       Blog name for the description.
     * }
     * @return array
     */
    public static function build_renewal_intent_request( $data ) {
        $order_number = ! empty( $data['order_number'] ) ? (string) $data['order_number'] : (string) ( isset( $data['wc_order_id'] ) ? $data['wc_order_id'] : '' );
        $site_name    = ! empty( $data['site_name'] ) ? (string) $data['site_name'] : '';

        /* translators: 1: order number, 2: site name */
        $description = trim( sprintf( __( 'Subscription renewal %1$s — %2$s', 'better-payment' ), '#' . $order_number, $site_name ), " \t—" );

        return array(
            'amount'         => (int) round( (float) $data['amount'] * 100 ),
            'currency'       => strtolower( (string) $data['currency'] ),
            'customer'       => (string) $data['customer'],
            'payment_method' => (string) $data['payment_method'],
            'off_session'    => 'true',
            'confirm'        => 'true',
            'description'    => $description,
            'metadata'       => array(
                'order_id'               => (string) $data['bp_order_id'],
                'wc_order_id'            => (string) $data['wc_order_id'],
                'bp_subscription_parent' => (string) $data['parent_order_id'],
            ),
        );
    }

    /**
     * Pure: build the Better Payment transaction row for a charged renewal.
     * Mirrors OrderHandler::build_transaction_data() so renewals appear in
     * the Better Payment transaction UI exactly like first payments (and
     * OrderHandler::extract_wc_order_id() resolves them the same way).
     *
     * @param array  $data   Same shape as build_renewal_intent_request().
     * @param object $intent Decoded Stripe PaymentIntent.
     * @return array
     */
    public static function build_renewal_transaction_data( $data, $intent ) {
        $form_fields_info = array(
            'wc_order_id'            => (int) $data['wc_order_id'],
            'wc_order_number'        => ! empty( $data['order_number'] ) ? (string) $data['order_number'] : (string) $data['wc_order_id'],
            'source'                 => 'stripe',
            'amount'                 => (float) $data['amount'],
            'primary_email'          => ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '',
            'primary_name'           => ! empty( $data['customer_name'] ) ? sanitize_text_field( $data['customer_name'] ) : '',
            'bp_subscription_parent' => (int) $data['parent_order_id'],
        );

        // Stripe reports a settled PaymentIntent as 'succeeded' — a status
        // the transaction taxonomy (Classes\Helper v2 maps, DB counters, the
        // admin list's tag) does not know, and unknown statuses classify as
        // "Incomplete". Store 'paid' — the exact status the engine's
        // verified first payment writes — so renewals count as completed
        // everywhere. A non-settled status passes through verbatim (renew()
        // only records settled charges today; the passthrough keeps a future
        // caller honest rather than laundering failures into 'paid').
        $status = ! empty( $intent->status ) ? sanitize_text_field( $intent->status ) : 'succeeded';
        if ( 'succeeded' === $status ) {
            $status = 'paid';
        }

        return array(
            'amount'           => (float) $data['amount'],
            'order_id'         => (string) $data['bp_order_id'],
            'payment_date'     => current_time( 'mysql' ),
            'source'           => 'stripe',
            'transaction_id'   => ! empty( $intent->id ) ? sanitize_text_field( $intent->id ) : '',
            'customer_info'    => maybe_serialize( $intent ),
            'form_fields_info' => maybe_serialize( $form_fields_info ),
            'obj_id'           => ! empty( $intent->id ) ? sanitize_text_field( $intent->id ) : '',
            'status'           => $status,
            'currency'         => (string) $data['currency'],
            'referer'          => 'woocommerce',
            'campaign_id'      => '',
        );
    }

    /* ---------------------------------------------------------------------
     * Activation + settlement
     * ------------------------------------------------------------------- */

    /**
     * Consume the module's payment-complete hook. A first payment on a
     * subscription cart activates the subscription; a paid renewal order
     * (cron-charged or manually paid) advances the parent's schedule.
     *
     * @param mixed       $order The paid order.
     * @param object|null $row   The Better Payment transaction row.
     * @return void
     */
    public static function on_order_paid( $order, $row ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $parent_id = (int) $order->get_meta( self::RENEWAL_PARENT_META );

        if ( $parent_id > 0 ) {
            self::settle_renewal( $order, $parent_id );
            return;
        }

        if ( '' !== (string) $order->get_meta( self::STATUS_META ) ) {
            return; // Already activated (duplicate confirmation).
        }

        if ( ! self::order_contains_subscription( $order ) ) {
            return; // Ordinary one-off purchase — the common case.
        }

        self::activate_subscription( $order, $row );
    }

    /**
     * Activate a subscription on its parent order: snapshot the schedule,
     * save the reusable Stripe customer + payment method, set the first
     * renewal date.
     *
     * @param \WC_Order   $order Parent order.
     * @param object|null $row   Better Payment transaction row.
     * @return void
     */
    public static function activate_subscription( $order, $row ) {
        $items = self::order_subscription_items( $order );

        if ( empty( $items ) ) {
            return;
        }

        // The schedule (and the trial / cancellation policies) come from the
        // first subscription item, snapshotted at activation so a later
        // product edit never rewrites a customer's agreed terms (one
        // schedule per order — documented limitation).
        $product     = $items[0]->get_product();
        $interval    = $product ? max( 1, (int) $product->get_meta( self::PRODUCT_INTERVAL_META ) ) : 1;
        $period      = self::sanitize_period( $product ? $product->get_meta( self::PRODUCT_PERIOD_META ) : 'month' );
        $trial_days  = $product ? max( 0, (int) $product->get_meta( self::PRODUCT_TRIAL_DAYS_META ) ) : 0;
        $user_cancel = ( $product && 'yes' === $product->get_meta( self::USER_CANCEL_META ) ) ? 'yes' : 'no';

        // The trial was applied iff the customer actually checked out at $0
        // for this item (zero_trial_price() zeroed the line during cart
        // totals calculation — the zeroed line IS the marker). A trial
        // product bought by an ineligible returning subscriber has a paid
        // line and renews on the plain schedule.
        $trial_applied = $trial_days > 0 && (float) $items[0]->get_total() < 0.01;

        // Trial → the first payment is due when the trial ends (nothing was
        // paid at checkout). No trial → the paid first cycle just started,
        // next payment one cycle out.
        $next = $trial_applied
            ? self::trial_end_timestamp( $trial_days )
            : self::next_payment_timestamp( $interval, $period );

        $harvested = self::harvest_payment_method( $order, $row );

        $order->update_meta_data( self::STATUS_META, 'active' );
        $order->update_meta_data( self::INTERVAL_META, (string) $interval );
        $order->update_meta_data( self::PERIOD_META, $period );
        $order->update_meta_data( self::NEXT_PAYMENT_META, (string) $next );
        $order->update_meta_data( self::USER_CANCEL_META, $user_cancel );
        $order->update_meta_data( self::RENEWAL_COUNT_META, '0' );

        if ( $trial_applied ) {
            $order->update_meta_data( self::TRIAL_APPLIED_META, 'yes' );
        }

        $trial_note = '';
        if ( $trial_applied ) {
            $trial_note = ' ' . sprintf(
                /* translators: %d: number of free trial days */
                _n( '%d-day free trial — nothing was charged at checkout; the first payment is due when the trial ends.', '%d-day free trial — nothing was charged at checkout; the first payment is due when the trial ends.', $trial_days, 'better-payment' ),
                $trial_days
            );
        } elseif ( $trial_days > 0 ) {
            $trial_note = ' ' . __( 'The product offers a free trial but this customer already used one for it, so the regular schedule applies.', 'better-payment' );
        }

        $order->add_order_note(
            sprintf(
                /* translators: 1: billing schedule, 2: next renewal date, 3: note about the saved payment method, 4: trial note (may be empty) */
                __( 'Better Payment: subscription activated — renews every %1$s. Next renewal: %2$s. %3$s%4$s', 'better-payment' ),
                self::describe_schedule( $interval, $period ),
                date_i18n( get_option( 'date_format' ), $next ),
                $harvested
                    ? __( 'A reusable payment method was saved for automatic renewals.', 'better-payment' )
                    : __( 'No reusable payment method could be saved — renewals will need manual payment.', 'better-payment' ),
                $trial_note
            )
        );
        $order->save();

        // Relation table: the order that started the subscription.
        self::record_order_relation( $order->get_id(), $order, SubscriptionRelationModel::TYPE_NEW );

        OrderHandler::log( 'Subscription activated on order #' . $order->get_id() . ' (every ' . $interval . ' ' . $period . ', next ' . gmdate( 'Y-m-d H:i:s', $next ) . ' UTC, reusable PM: ' . ( $harvested ? 'yes' : 'no' ) . ').' );

        /**
         * Fires after a Better Payment subscription is activated on an order.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $order The parent (subscription) order.
         */
        do_action( 'better_payment/woocommerce/subscription_activated', $order );
    }

    /**
     * Record a row in the e-commerce subscription <-> order relation table
     * (source 'woo'). `$subscription_id` is the parent (subscription) order
     * id; `$order` is the related order — the parent itself for TYPE_NEW, a
     * renewal order for TYPE_RENEW. The model dedupes, so re-recording the
     * same relation is harmless.
     *
     * @param int    $subscription_id Parent (subscription) order id.
     * @param mixed  $order           The related order (non-orders are rejected).
     * @param string $type            SubscriptionRelationModel::TYPE_NEW|TYPE_RENEW.
     * @return void
     */
    public static function record_order_relation( $subscription_id, $order, $type ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $items         = self::order_subscription_items( $order );
        $order_item_id = ! empty( $items ) ? (int) $items[0]->get_id() : 0;

        SubscriptionRelationModel::record(
            array(
                'subscription_id' => (int) $subscription_id,
                'order_id'        => $order->get_id(),
                'order_item_id'   => $order_item_id,
                'type'            => $type,
                'source'          => SubscriptionRelationModel::SOURCE_WOO,
            )
        );
    }

    /**
     * Save the reusable Stripe customer + payment method from the verified
     * Checkout Session onto the order.
     *
     * @param \WC_Order   $order Parent order.
     * @param object|null $row   Better Payment transaction row (obj_id = session id).
     * @return bool Whether both identifiers are now stored.
     */
    public static function harvest_payment_method( $order, $row ) {
        if ( '' !== (string) $order->get_meta( self::CUSTOMER_META ) && '' !== (string) $order->get_meta( self::PAYMENT_METHOD_META ) ) {
            return true;
        }

        $session_id = isset( $row->obj_id ) ? (string) $row->obj_id : '';

        if ( '' === $session_id || 0 !== strpos( $session_id, 'cs_' ) ) {
            return false;
        }

        $keys = StripeService::get_global_keys();

        if ( empty( $keys['secret_key'] ) ) {
            return false;
        }

        // Expand both intent kinds: a paid checkout saved the card on its
        // PaymentIntent; a $0 free-trial checkout saved it on the setup-mode
        // session's SetupIntent.
        $session = StripeService::retrieve_checkout_session( $session_id, $keys['secret_key'], array( 'payment_intent', 'setup_intent' ) );

        if ( is_wp_error( $session ) ) {
            OrderHandler::log( 'Could not retrieve session ' . $session_id . ' to save the reusable payment method for order #' . $order->get_id() . ': ' . $session->get_error_message(), 'error' );
            return false;
        }

        $customer = '';
        if ( ! empty( $session->customer ) ) {
            $customer = is_object( $session->customer ) && ! empty( $session->customer->id ) ? (string) $session->customer->id : (string) $session->customer;
        }

        $payment_method = '';
        if ( ! empty( $session->payment_intent ) && is_object( $session->payment_intent ) && ! empty( $session->payment_intent->payment_method ) ) {
            $pm             = $session->payment_intent->payment_method;
            $payment_method = is_object( $pm ) && ! empty( $pm->id ) ? (string) $pm->id : (string) $pm;
        }

        if ( '' === $payment_method && ! empty( $session->setup_intent ) && is_object( $session->setup_intent ) && ! empty( $session->setup_intent->payment_method ) ) {
            $pm             = $session->setup_intent->payment_method;
            $payment_method = is_object( $pm ) && ! empty( $pm->id ) ? (string) $pm->id : (string) $pm;
        }

        if ( '' === $customer || '' === $payment_method ) {
            return false;
        }

        $order->update_meta_data( self::CUSTOMER_META, sanitize_text_field( $customer ) );
        $order->update_meta_data( self::PAYMENT_METHOD_META, sanitize_text_field( $payment_method ) );

        return true;
    }

    /**
     * A renewal order was paid — advance the parent's schedule and (re)set
     * it to active. This is also the past_due recovery path: manually paying
     * the invoiced renewal order lands here through the same hook.
     *
     * @param \WC_Order $renewal_order The paid renewal order.
     * @param int       $parent_id     Parent (subscription) order id.
     * @return void
     */
    public static function settle_renewal( $renewal_order, $parent_id ) {
        $parent = function_exists( 'wc_get_order' ) ? wc_get_order( $parent_id ) : false;

        if ( ! $parent ) {
            OrderHandler::log( 'Renewal order #' . $renewal_order->get_id() . ' paid but parent order #' . $parent_id . ' was not found.', 'error' );
            return;
        }

        if ( 'cancelled' === (string) $parent->get_meta( self::STATUS_META ) ) {
            $parent->add_order_note(
                sprintf(
                    /* translators: %d: renewal order id */
                    __( 'Better Payment: renewal order #%d was paid but the subscription is cancelled — the schedule was not advanced.', 'better-payment' ),
                    $renewal_order->get_id()
                )
            );
            $parent->save();
            return;
        }

        $renewal_count = (int) $parent->get_meta( self::RENEWAL_COUNT_META ) + 1;

        $parent->update_meta_data( self::RENEWAL_COUNT_META, (string) $renewal_count );
        $parent->update_meta_data( self::LAST_PAYMENT_META, (string) time() );
        // A paid renewal clears the outstanding manual-renewal invoice guard
        // (no-op for auto-charged renewals).
        $parent->delete_meta_data( self::PENDING_RENEWAL_META );

        $interval = max( 1, (int) $parent->get_meta( self::INTERVAL_META ) );
        $period   = self::sanitize_period( $parent->get_meta( self::PERIOD_META ) );
        $next     = self::next_payment_timestamp( $interval, $period );

        $parent->update_meta_data( self::STATUS_META, 'active' );
        $parent->update_meta_data( self::NEXT_PAYMENT_META, (string) $next );
        $parent->add_order_note(
            sprintf(
                /* translators: 1: renewal order id, 2: next renewal date */
                __( 'Better Payment: renewal order #%1$d paid. Next renewal: %2$s.', 'better-payment' ),
                $renewal_order->get_id(),
                date_i18n( get_option( 'date_format' ), $next )
            )
        );
        $parent->save();

        OrderHandler::log( 'Renewal order #' . $renewal_order->get_id() . ' settled for subscription order #' . $parent_id . '; next renewal ' . gmdate( 'Y-m-d H:i:s', $next ) . ' UTC.' );

        /**
         * Fires after a subscription renewal payment settles.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $parent        The parent (subscription) order.
         * @param \WC_Order $renewal_order The paid renewal order.
         */
        do_action( 'better_payment/woocommerce/subscription_renewed', $parent, $renewal_order );
    }

    /* ---------------------------------------------------------------------
     * Renewal processing (cron)
     * ------------------------------------------------------------------- */

    /**
     * Charge every due subscription. Runs hourly via WP-Cron.
     *
     * @return void
     */
    public static function process_due_subscriptions() {
        if ( ! function_exists( 'wc_get_orders' ) || ! StripeService::is_configured() ) {
            return;
        }

        $orders = wc_get_orders(
            array(
                'limit'      => 20,
                'type'       => 'shop_order',
                'status'     => array( 'wc-processing', 'wc-completed' ),
                'return'     => 'objects',
                'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded (limit 20), hourly cron.
                    array(
                        'key'   => self::STATUS_META,
                        'value' => 'active',
                    ),
                    array(
                        'key'     => self::NEXT_PAYMENT_META,
                        'value'   => time(),
                        'compare' => '<=',
                        'type'    => 'NUMERIC',
                    ),
                ),
            )
        );

        if ( empty( $orders ) || ! is_array( $orders ) ) {
            return;
        }

        foreach ( $orders as $order ) {
            self::renew( $order );
        }
    }

    /**
     * Create and charge one renewal for a due subscription.
     *
     * On success the renewal order is marked paid, the payment is recorded in
     * the Better Payment transactions table, and the parent's schedule
     * advances (through the shared payment-complete hook). On failure the
     * subscription goes past_due: auto-charging stops and the customer is
     * emailed the pending renewal order to pay manually.
     *
     * @param \WC_Order $parent Parent (subscription) order.
     * @return void
     */
    public static function renew( $parent ) {
        // Idempotency guard — a slow Stripe call must not let an overlapping
        // cron run double-charge the same subscription.
        $lock_key = 'bp_wc_sub_renew_' . $parent->get_id();

        if ( get_transient( $lock_key ) ) {
            return;
        }

        set_transient( $lock_key, 1, 10 * MINUTE_IN_SECONDS );

        // Manual renewal policy — the site-wide Renewal Mode / Automatic
        // Stripe Charging settings or the customer's own auto-renew opt-out:
        // invoice the renewal instead of charging it.
        if ( ! self::auto_renewal_enabled_for( $parent ) ) {
            self::request_manual_renewal( $parent );
            return;
        }

        $customer       = (string) $parent->get_meta( self::CUSTOMER_META );
        $payment_method = (string) $parent->get_meta( self::PAYMENT_METHOD_META );

        if ( '' === $customer || '' === $payment_method ) {
            // No stored card to charge — invoice the renewal for manual
            // payment instead of stalling the subscription in past_due with
            // nothing for the customer to pay. (This is also how a
            // subscription sold while renewals were manual keeps renewing
            // after the site switches back to automatic.)
            self::request_manual_renewal( $parent );
            return;
        }

        $renewal = self::create_renewal_order( $parent );

        if ( ! $renewal instanceof \WC_Order ) {
            OrderHandler::log( 'Could not create a renewal order for subscription order #' . $parent->get_id() . '.', 'error' );
            return;
        }

        // A $0 renewal — the product is on a 100% sale, or priced free.
        // create_renewal_order() re-adds the parent's products at their
        // CURRENT price, so this is reachable on any subscription whose price
        // has since been discounted to nothing. Stripe rejects a
        // PaymentIntent of amount 0, so charging it would fail, call
        // mark_past_due() and invoice the customer $0 — turning a healthy
        // subscription into a broken one over a discount the site owner
        // chose. Complete it directly instead, exactly as
        // Gateway::process_payment() completes a $0 FIRST payment, and let
        // the shared completion contract advance the schedule.
        if ( ! self::renewal_requires_charge( (float) $renewal->get_total() ) ) {
            $renewal->add_order_note( __( 'Better Payment: subscription renewal total is zero — nothing to charge. The renewal was completed without contacting Stripe.', 'better-payment' ) );
            $renewal->payment_complete();
            $renewal->save();

            OrderHandler::log( 'Renewal order #' . $renewal->get_id() . ' for subscription order #' . $parent->get_id() . ' completed without a charge (zero total).' );

            // No Better Payment transaction row is written: nothing was paid
            // and there is no Stripe object to record. Same precedent as the
            // $0 first payment under a manual renewal policy
            // (Gateway::process_payment()).
            do_action( 'better_payment/woocommerce/payment_complete', $renewal, null );
            return;
        }

        $keys        = StripeService::get_global_keys();
        $bp_order_id = 'stripe_' . uniqid();

        $data = array(
            'bp_order_id'     => $bp_order_id,
            'wc_order_id'     => $renewal->get_id(),
            'parent_order_id' => $parent->get_id(),
            'order_number'    => $renewal->get_order_number(),
            'amount'          => (float) $renewal->get_total(),
            'currency'        => $renewal->get_currency(),
            'customer'        => $customer,
            'payment_method'  => $payment_method,
            'customer_email'  => $renewal->get_billing_email(),
            'customer_name'   => trim( $renewal->get_billing_first_name() . ' ' . $renewal->get_billing_last_name() ),
            'site_name'       => get_bloginfo( 'name' ),
        );

        $intent = StripeService::create_payment_intent( self::build_renewal_intent_request( $data ), $keys['secret_key'] );

        if ( is_wp_error( $intent ) || empty( $intent->status ) || 'succeeded' !== $intent->status ) {
            $reason = is_wp_error( $intent )
                ? $intent->get_error_message()
                : sprintf(
                    /* translators: %s: Stripe PaymentIntent status */
                    __( 'the charge did not succeed (status: %s)', 'better-payment' ),
                    ! empty( $intent->status ) ? sanitize_text_field( $intent->status ) : 'unknown'
                );

            self::mark_past_due( $parent, $renewal, $reason );

            /**
             * Fires after an automatic subscription renewal charge fails.
             *
             * @since 2.4.0
             *
             * @param \WC_Order $parent  The parent (subscription) order.
             * @param \WC_Order $renewal The pending renewal order.
             * @param string    $reason  Failure reason.
             */
            do_action( 'better_payment/woocommerce/subscription_renewal_failed', $parent, $renewal, $reason );
            return;
        }

        // Record the payment through the engine's own writer, then mark the
        // renewal order paid.
        $transaction_row_id = Handler::payment_create( self::build_renewal_transaction_data( $data, $intent ) );

        $renewal->update_meta_data( '_bp_transaction_id', (string) $transaction_row_id );
        $renewal->update_meta_data( '_bp_payment_id', $bp_order_id );
        $renewal->update_meta_data( '_bp_gateway', 'stripe' );
        $renewal->update_meta_data( '_bp_payment_status', sanitize_text_field( $intent->status ) );
        $renewal->update_meta_data( '_bp_paid_at', current_time( 'mysql' ) );
        $renewal->add_order_note(
            sprintf(
                /* translators: 1: Stripe PaymentIntent id, 2: Better Payment record id */
                __( 'Better Payment: subscription renewal charged off-session. Transaction ID: %1$s (Better Payment record #%2$d).', 'better-payment' ),
                sanitize_text_field( $intent->id ),
                (int) $transaction_row_id
            )
        );
        $renewal->payment_complete( sanitize_text_field( $intent->id ) );
        $renewal->save();

        OrderHandler::log( 'Renewal order #' . $renewal->get_id() . ' charged for subscription order #' . $parent->get_id() . ' (' . $bp_order_id . ').' );

        $row = $transaction_row_id ? DB::get_transaction( (int) $transaction_row_id ) : null;

        // Same completion contract as a verified first payment — this is what
        // advances the parent's schedule (see on_order_paid()).
        do_action( 'better_payment/woocommerce/payment_complete', $renewal, $row );
    }

    /**
     * Create the pending renewal order for a subscription: the parent's
     * subscription line items at their CURRENT product price, billed to the
     * parent's billing address through this gateway.
     *
     * @param \WC_Order $parent Parent (subscription) order.
     * @return \WC_Order|null
     */
    public static function create_renewal_order( $parent ) {
        if ( ! function_exists( 'wc_create_order' ) ) {
            return null;
        }

        $items = self::order_subscription_items( $parent );

        if ( empty( $items ) ) {
            return null;
        }

        $renewal = wc_create_order(
            array(
                'customer_id' => $parent->get_customer_id(),
                'status'      => 'wc-pending',
                'created_via' => 'better_payment_subscription',
            )
        );

        if ( is_wp_error( $renewal ) ) {
            return null;
        }

        $added = 0;

        foreach ( $items as $item ) {
            $product = $item->get_product();

            if ( ! $product ) {
                continue;
            }

            $renewal->add_product( $product, $item->get_quantity() );
            $added++;
        }

        if ( 0 === $added ) {
            $renewal->delete( true );
            return null;
        }

        $renewal->set_address( $parent->get_address( 'billing' ), 'billing' );
        $renewal->set_payment_method( Gateway::GATEWAY_ID );
        $renewal->set_payment_method_title( $parent->get_payment_method_title() );
        $renewal->update_meta_data( self::RENEWAL_PARENT_META, (string) $parent->get_id() );
        $renewal->update_meta_data( self::CUSTOMER_META, (string) $parent->get_meta( self::CUSTOMER_META ) );
        $renewal->update_meta_data( self::PAYMENT_METHOD_META, (string) $parent->get_meta( self::PAYMENT_METHOD_META ) );
        $renewal->add_order_note(
            sprintf(
                /* translators: %d: parent order id */
                __( 'Better Payment: subscription renewal order for order #%d.', 'better-payment' ),
                $parent->get_id()
            )
        );
        $renewal->calculate_totals();
        $renewal->save();

        // Relation table: renewal order -> its parent subscription.
        self::record_order_relation( $parent->get_id(), $renewal, SubscriptionRelationModel::TYPE_RENEW );

        /**
         * Fires after a subscription renewal order is created (before it is
         * charged).
         *
         * @since 2.4.0
         *
         * @param \WC_Order $renewal The pending renewal order.
         * @param \WC_Order $parent  The parent (subscription) order.
         */
        do_action( 'better_payment/woocommerce/subscription_renewal_order_created', $renewal, $parent );

        return $renewal;
    }

    /**
     * A renewal could not be charged: stop auto-charging (past_due) and, when
     * a pending renewal order exists, email it to the customer to pay
     * manually. Paying it reactivates the subscription (settle_renewal()).
     *
     * @param \WC_Order      $parent  Parent (subscription) order.
     * @param \WC_Order|null $renewal Pending renewal order, when one was created.
     * @param string         $reason  Failure reason for the order note/log.
     * @return void
     */
    public static function mark_past_due( $parent, $renewal, $reason ) {
        $parent->update_meta_data( self::STATUS_META, 'past_due' );
        $parent->add_order_note(
            sprintf(
                /* translators: %s: failure reason */
                __( 'Better Payment: automatic renewal failed — %s. Automatic charging is paused until the pending renewal order is paid.', 'better-payment' ),
                $reason
            )
        );
        $parent->save();

        OrderHandler::log( 'Subscription order #' . $parent->get_id() . ' set to past_due: ' . $reason, 'error' );

        if ( $renewal instanceof \WC_Order ) {
            $renewal->add_order_note(
                sprintf(
                    /* translators: %s: failure reason */
                    __( 'Better Payment: the automatic charge failed — %s. The order was invoiced to the customer for manual payment.', 'better-payment' ),
                    $reason
                )
            );
            $renewal->save();

            self::send_customer_invoice( $renewal );
        }
    }

    /**
     * A renewal is due but must be paid manually — because of the site-wide
     * manual renewal policy, the customer's auto-renew opt-out, or a missing
     * stored payment method. Creates the pending renewal order once and
     * emails it to the customer; paying it advances the schedule through
     * settle_renewal() exactly like an auto-charged renewal.
     *
     * Hourly-cron safe: while the previously invoiced renewal order is still
     * awaiting payment, nothing new is created or sent.
     *
     * @param \WC_Order $parent Parent (subscription) order.
     * @return void
     */
    public static function request_manual_renewal( $parent ) {
        $pending_id = (int) $parent->get_meta( self::PENDING_RENEWAL_META );

        if ( $pending_id > 0 && function_exists( 'wc_get_order' ) ) {
            $pending = wc_get_order( $pending_id );

            if ( $pending instanceof \WC_Order && $pending->needs_payment() ) {
                return; // Already invoiced — waiting on the customer.
            }
        }

        $renewal = self::create_renewal_order( $parent );

        if ( ! $renewal instanceof \WC_Order ) {
            OrderHandler::log( 'Could not create a manual renewal order for subscription order #' . $parent->get_id() . '.', 'error' );
            return;
        }

        $renewal->add_order_note( __( 'Better Payment: manual subscription renewal — invoiced to the customer for payment.', 'better-payment' ) );
        $renewal->save();

        $parent->update_meta_data( self::PENDING_RENEWAL_META, (string) $renewal->get_id() );
        $parent->add_order_note(
            sprintf(
                /* translators: %d: renewal order id */
                __( 'Better Payment: renewal due — renewal order #%d was invoiced to the customer for manual payment.', 'better-payment' ),
                $renewal->get_id()
            )
        );
        $parent->save();

        self::send_customer_invoice( $renewal );

        OrderHandler::log( 'Manual renewal order #' . $renewal->get_id() . ' invoiced for subscription order #' . $parent->get_id() . '.' );

        /**
         * Fires after a manual renewal order is created and invoiced.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $parent  The parent (subscription) order.
         * @param \WC_Order $renewal The pending renewal order.
         */
        do_action( 'better_payment/woocommerce/subscription_manual_renewal_requested', $parent, $renewal );
    }

    /**
     * Email a renewal order to its customer through WooCommerce's own
     * customer-invoice email (it carries the pay link).
     *
     * @param \WC_Order $renewal Renewal order.
     * @return void
     */
    public static function send_customer_invoice( $renewal ) {
        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        $emails  = WC()->mailer()->get_emails();
        $invoice = isset( $emails['WC_Email_Customer_Invoice'] ) ? $emails['WC_Email_Customer_Invoice'] : null;

        if ( $invoice instanceof \WC_Email_Customer_Invoice ) {
            $invoice->trigger( $renewal->get_id(), $renewal );
        }
    }

    /* ---------------------------------------------------------------------
     * Customer auto-renewal toggle (My Account)
     * ------------------------------------------------------------------- */

    /**
     * Automatic-renewal on/off control, rendered on the My Account
     * single-subscription view only ({@see MyAccount::render_view()}, context
     * 'subscription'), where the form carries a return flag so the handler
     * redirects back to the subscription page. It is deliberately not hooked
     * onto any order-details screen — see register().
     *
     * Rendered only when the Customer Auto-Renew Control setting allows it,
     * automatic renewal is enabled site-wide (otherwise every renewal is
     * manual and the toggle would do nothing), a reusable payment method is
     * stored, the subscription is live, and the viewer owns the order.
     *
     * @param mixed  $order   The order being viewed.
     * @param string $context 'subscription' (the only caller); '' omits the
     *                        return flag, redirecting to the order page.
     * @return void
     */
    public static function render_auto_renew_toggle( $order, $context = '' ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
            return;
        }

        if ( 'yes' !== self::setting( 'auto_renewal_toggle' ) || ! self::should_save_payment_method() ) {
            return;
        }

        if ( ! in_array( (string) $order->get_meta( self::STATUS_META ), array( 'active', 'past_due' ), true ) ) {
            return;
        }

        if ( '' === (string) $order->get_meta( self::CUSTOMER_META ) || '' === (string) $order->get_meta( self::PAYMENT_METHOD_META ) ) {
            return;
        }

        $auto_on = 'no' !== (string) $order->get_meta( self::AUTO_RENEW_META );

        ?>
        <form method="post" class="bp-subscription-auto-renew">
            <?php wp_nonce_field( 'bp_auto_renew_' . $order->get_id(), 'bp_auto_renew_nonce' ); ?>
            <input type="hidden" name="bp_auto_renew_order" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
            <input type="hidden" name="bp_auto_renew_value" value="<?php echo esc_attr( $auto_on ? 'no' : 'yes' ); ?>">
            <?php if ( 'subscription' === $context ) : ?>
                <input type="hidden" name="bp_return_subscription" value="1">
            <?php endif; ?>
            <p>
                <?php
                echo esc_html(
                    $auto_on
                        ? __( 'Automatic renewal is on — renewals are charged to your saved payment method.', 'better-payment' )
                        : __( 'Automatic renewal is off — you will be emailed an invoice when each renewal is due.', 'better-payment' )
                );
                ?>
            </p>
            <button type="submit" class="button">
                <?php echo esc_html( $auto_on ? __( 'Turn off automatic renewal', 'better-payment' ) : __( 'Turn on automatic renewal', 'better-payment' ) ); ?>
            </button>
        </form>
        <?php
    }

    /**
     * Handle the auto-renewal toggle submit. Nonce-guarded and restricted to
     * the order's own customer on a live subscription.
     *
     * @return void
     */
    public static function maybe_handle_auto_renew_toggle() {
        if ( empty( $_POST['bp_auto_renew_order'] ) || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order_id = absint( wp_unslash( $_POST['bp_auto_renew_order'] ) );
        $nonce    = isset( $_POST['bp_auto_renew_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bp_auto_renew_nonce'] ) ) : '';

        if ( ! $order_id || ! wp_verify_nonce( $nonce, 'bp_auto_renew_' . $order_id ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order || ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
            return;
        }

        if ( 'yes' !== self::setting( 'auto_renewal_toggle' ) ) {
            return;
        }

        if ( ! in_array( (string) $order->get_meta( self::STATUS_META ), array( 'active', 'past_due' ), true ) ) {
            return;
        }

        $enable = isset( $_POST['bp_auto_renew_value'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['bp_auto_renew_value'] ) );

        $order->update_meta_data( self::AUTO_RENEW_META, $enable ? 'yes' : 'no' );
        $order->add_order_note(
            $enable
                ? __( 'Better Payment: the customer turned automatic renewal ON — due renewals are charged to the saved payment method.', 'better-payment' )
                : __( 'Better Payment: the customer turned automatic renewal OFF — due renewals are invoiced for manual payment.', 'better-payment' )
        );
        $order->save();

        OrderHandler::log( 'Customer set auto renewal ' . ( $enable ? 'on' : 'off' ) . ' for subscription order #' . $order->get_id() . '.' );

        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice(
                $enable
                    ? __( 'Automatic renewal is now on.', 'better-payment' )
                    : __( 'Automatic renewal is now off. We will email you an invoice when a renewal is due.', 'better-payment' )
            );
        }

        wp_safe_redirect( self::customer_action_redirect( $order ) );
        exit;
    }

    /**
     * Where a customer-action handler sends the customer afterwards: back to
     * the My Account subscription view when the form came from there (the
     * `bp_return_subscription` flag — a flag, never a client-supplied URL),
     * otherwise the view-order page the shared forms have always used.
     * Only read after the action's nonce has been verified.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return string
     */
    protected static function customer_action_redirect( $order ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- callers verify their own per-order nonce before this runs.
        if ( ! empty( $_POST['bp_return_subscription'] ) ) {
            $url = MyAccount::view_url( $order->get_id() );

            if ( '' !== $url ) {
                return $url;
            }
        }

        return $order->get_view_order_url();
    }

    /* ---------------------------------------------------------------------
     * Cancellation
     * ------------------------------------------------------------------- */

    /**
     * Offer the cancel action on parent orders with a live subscription.
     *
     * @param array $actions Order actions.
     * @param mixed $order   The order being edited (newer WooCommerce passes it).
     * @return array
     */
    public static function register_order_action( $actions, $order = null ) {
        if ( ! $order instanceof \WC_Order && isset( $GLOBALS['theorder'] ) && $GLOBALS['theorder'] instanceof \WC_Order ) {
            $order = $GLOBALS['theorder'];
        }

        if ( $order instanceof \WC_Order && in_array( (string) $order->get_meta( self::STATUS_META ), array( 'active', 'past_due' ), true ) ) {
            $actions['bp_cancel_subscription'] = __( 'Cancel Better Payment Subscription', 'better-payment' );
        }

        return $actions;
    }

    /**
     * Handle the admin order action.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return void
     */
    public static function handle_cancel_action( $order ) {
        self::cancel( $order, '', 'admin' );
    }

    /**
     * Customer-facing cancel button, rendered on the My Account
     * single-subscription view only ({@see MyAccount::render_view()}, context
     * 'subscription'), where the form carries a return flag so the handler
     * redirects back to the subscription page. It is deliberately not hooked
     * onto any order-details screen — see register().
     *
     * Rendered only when the product allowed user cancellation (snapshotted
     * on the parent order at activation), the subscription is live, and the
     * viewer is the order's own customer.
     *
     * @param mixed  $order   The order being viewed.
     * @param string $context 'subscription' (the only caller); '' omits the
     *                        return flag, redirecting to the order page.
     * @return void
     */
    public static function render_customer_cancel_button( $order, $context = '' ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
            return;
        }

        if ( 'yes' !== (string) $order->get_meta( self::USER_CANCEL_META ) ) {
            return;
        }

        if ( ! in_array( (string) $order->get_meta( self::STATUS_META ), array( 'active', 'past_due' ), true ) ) {
            return;
        }

        ?>
        <form method="post" class="bp-subscription-cancel">
            <?php wp_nonce_field( 'bp_cancel_subscription_' . $order->get_id(), 'bp_cancel_subscription_nonce' ); ?>
            <input type="hidden" name="bp_cancel_subscription_order" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
            <?php if ( 'subscription' === $context ) : ?>
                <input type="hidden" name="bp_return_subscription" value="1">
            <?php endif; ?>
            <button type="submit" class="button" onclick="return confirm( '<?php echo esc_js( __( 'Cancel this subscription? No further renewals will be charged.', 'better-payment' ) ); ?>' );">
                <?php esc_html_e( 'Cancel subscription', 'better-payment' ); ?>
            </button>
        </form>
        <?php
    }

    /**
     * Handle the customer cancellation submit. Nonce-guarded and restricted
     * to the order's own customer on an order whose product allowed it.
     *
     * @return void
     */
    public static function maybe_handle_customer_cancel() {
        if ( empty( $_POST['bp_cancel_subscription_order'] ) || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order_id = absint( wp_unslash( $_POST['bp_cancel_subscription_order'] ) );
        $nonce    = isset( $_POST['bp_cancel_subscription_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bp_cancel_subscription_nonce'] ) ) : '';

        if ( ! $order_id || ! wp_verify_nonce( $nonce, 'bp_cancel_subscription_' . $order_id ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order || ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
            return;
        }

        if ( 'yes' !== (string) $order->get_meta( self::USER_CANCEL_META ) ) {
            return;
        }

        self::cancel( $order, __( 'Better Payment: subscription cancelled by the customer — no further automatic renewals will be charged.', 'better-payment' ), 'customer' );

        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( __( 'Your subscription has been cancelled.', 'better-payment' ) );
        }

        wp_safe_redirect( self::customer_action_redirect( $order ) );
        exit;
    }

    /**
     * Cancel a subscription. Terminal: renewals stop; already-created pending
     * renewal orders are left for the shop owner to keep or cancel.
     *
     * @param mixed  $order Parent (subscription) order.
     * @param string $note  Optional order-note override (e.g. the customer-
     *                      initiated cancellation message).
     * @param string $actor Who is cancelling: 'customer' | 'admin' | ''
     *                      (unknown — no actor stamp, the details view shows
     *                      no Cancelled By row).
     * @return void
     */
    public static function cancel( $order, $note = '', $actor = '' ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( ! in_array( (string) $order->get_meta( self::STATUS_META ), array( 'active', 'past_due' ), true ) ) {
            return;
        }

        if ( in_array( $actor, array( 'customer', 'admin' ), true ) ) {
            $order->update_meta_data( self::CANCELLED_BY_TYPE_META, $actor );
            $order->update_meta_data( self::CANCELLED_BY_META, (string) get_current_user_id() );
        }

        $order->update_meta_data( self::STATUS_META, 'cancelled' );
        $order->add_order_note( '' !== $note ? $note : __( 'Better Payment: subscription cancelled — no further automatic renewals will be charged.', 'better-payment' ) );
        $order->save();

        OrderHandler::log( 'Subscription cancelled on order #' . $order->get_id() . '.' );

        /**
         * Fires after a Better Payment subscription is cancelled.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $order The parent (subscription) order.
         */
        do_action( 'better_payment/woocommerce/subscription_cancelled', $order );
    }

    /**
     * Reactivate a cancelled (or past_due) subscription from the admin
     * Subscriptions tab. Sets the status back to active; when the stored
     * next-payment date already elapsed while the subscription was
     * cancelled, a fresh one is scheduled a full cycle from now — otherwise
     * the hourly cron would charge the customer the moment reactivation
     * saved.
     *
     * @param mixed  $order Parent (subscription) order.
     * @param string $note  Optional order-note override.
     * @return void
     */
    public static function reactivate( $order, $note = '' ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( ! in_array( (string) $order->get_meta( self::STATUS_META ), array( 'cancelled', 'past_due' ), true ) ) {
            return;
        }

        $next = (int) $order->get_meta( self::NEXT_PAYMENT_META );

        if ( $next <= time() ) {
            $interval = max( 1, (int) $order->get_meta( self::INTERVAL_META ) );
            $period   = self::sanitize_period( $order->get_meta( self::PERIOD_META ) );
            $next     = self::next_payment_timestamp( $interval, $period );
            $order->update_meta_data( self::NEXT_PAYMENT_META, (string) $next );
        }

        $order->update_meta_data( self::STATUS_META, 'active' );
        // The subscription is live again — a later cancellation must record
        // its own actor, never inherit this one's.
        $order->delete_meta_data( self::CANCELLED_BY_TYPE_META );
        $order->delete_meta_data( self::CANCELLED_BY_META );
        $order->add_order_note(
            '' !== $note ? $note : sprintf(
                /* translators: %s: next renewal date */
                __( 'Better Payment: subscription reactivated. Next renewal: %s.', 'better-payment' ),
                date_i18n( get_option( 'date_format' ), $next )
            )
        );
        $order->save();

        OrderHandler::log( 'Subscription reactivated on order #' . $order->get_id() . ' (next renewal ' . gmdate( 'Y-m-d H:i:s', $next ) . ' UTC).' );

        /**
         * Fires after a Better Payment subscription is reactivated.
         *
         * @since 2.4.0
         *
         * @param \WC_Order $order The parent (subscription) order.
         */
        do_action( 'better_payment/woocommerce/subscription_reactivated', $order );
    }

    /* ---------------------------------------------------------------------
     * Subscriber role sync
     * ------------------------------------------------------------------- */

    /**
     * Subscription activated → give the customer the Subscriber Default
     * Role.
     *
     * @param mixed $order Parent (subscription) order.
     * @return void
     */
    public static function assign_active_role( $order ) {
        self::swap_customer_role( $order, self::setting( 'active_role' ), true );
    }

    /**
     * Subscription cancelled/completed → give the customer the Subscriber
     * Inactive Role, unless they still hold another live subscription.
     *
     * @param mixed $order Parent (subscription) order.
     * @return void
     */
    public static function assign_inactive_role( $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        if ( self::customer_has_other_live_subscription( $order ) ) {
            return;
        }

        self::swap_customer_role( $order, self::setting( 'inactive_role' ), false );
    }

    /**
     * Pure: whether a user's current roles forbid the subscription role
     * swap. Site staff must never be demoted to a subscriber role by buying
     * or cancelling a subscription — set_role() REPLACES the user's roles.
     *
     * @param mixed $roles The user's current role slugs.
     * @return bool
     */
    public static function user_role_is_protected( $roles ) {
        /**
         * Filters the roles the subscription role sync refuses to replace.
         *
         * @since 2.4.0
         *
         * @param string[] $protected Protected role slugs.
         */
        $protected = apply_filters(
            'better_payment/woocommerce/role_sync_protected_roles',
            array( 'administrator', 'editor', 'shop_manager' )
        );

        return (bool) array_intersect( (array) $roles, (array) $protected );
    }

    /**
     * Replace the order's customer role for the subscription lifecycle.
     * No-ops on guest orders, unknown/blank target roles, protected users,
     * and users already holding the target role.
     *
     * @param mixed  $order  Parent (subscription) order.
     * @param string $role   Target role slug (from the settings).
     * @param bool   $active Whether the swap is for an activation (for the note).
     * @return void
     */
    public static function swap_customer_role( $order, $role, $active ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $role = (string) $role;

        if ( '' === $role || null === get_role( $role ) ) {
            return; // Unknown role — never assign a role that doesn't exist.
        }

        $user_id = (int) $order->get_customer_id();

        if ( $user_id <= 0 ) {
            return; // Guest order — no account to update.
        }

        $user = get_user_by( 'id', $user_id );

        if ( ! $user || in_array( $role, (array) $user->roles, true ) || self::user_role_is_protected( $user->roles ) ) {
            return;
        }

        $user->set_role( $role );

        $note_template = $active
            /* translators: %s: role slug */
            ? __( 'Better Payment: customer role set to "%s" — subscription active.', 'better-payment' )
            /* translators: %s: role slug */
            : __( 'Better Payment: customer role set to "%s" — subscription ended.', 'better-payment' );

        $order->add_order_note( sprintf( $note_template, $role ) );
        $order->save();

        OrderHandler::log( 'Customer #' . $user_id . ' role set to ' . $role . ' for subscription order #' . $order->get_id() . '.' );
    }

    /**
     * Whether the order's customer holds another live (active or past_due)
     * subscription besides this one.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return bool
     */
    public static function customer_has_other_live_subscription( $order ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return false;
        }

        $customer_id = (int) $order->get_customer_id();

        if ( $customer_id <= 0 ) {
            return false;
        }

        $others = wc_get_orders(
            array(
                'limit'       => 1,
                'type'        => 'shop_order',
                'customer_id' => $customer_id,
                'exclude'     => array( $order->get_id() ),
                'return'      => 'ids',
                'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded (limit 1), event-driven.
                    array(
                        'key'     => self::STATUS_META,
                        'value'   => array( 'active', 'past_due' ),
                        'compare' => 'IN',
                    ),
                ),
            )
        );

        return is_array( $others ) && count( $others ) > 0;
    }

    /* ---------------------------------------------------------------------
     * Admin list (React admin → Subscriptions tab)
     * ------------------------------------------------------------------- */

    /**
     * Flat display row for one subscription on the admin Subscriptions tab.
     *
     * Reads the parent (subscription) order and its `_bp_subscription_*`
     * meta. Safe to call whether or not WooCommerce is active — returns null
     * when WooCommerce (or the order) is unavailable, and the caller keeps
     * its un-hydrated base row.
     *
     * Dates are returned as site-local `Y-m-d H:i:s` strings — the same shape
     * as the transactions table's `payment_date` — so the React admin formats
     * them with the one date helper it already has.
     *
     * @param int $subscription_id Parent (subscription) order id.
     * @return array|null
     * @since 2.4.0
     */
    public static function admin_list_row( $subscription_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return null;
        }

        $order = wc_get_order( absint( $subscription_id ) );

        if ( ! $order instanceof \WC_Order ) {
            return null;
        }

        $items        = self::order_subscription_items( $order );
        $first_item   = ! empty( $items ) ? current( $items ) : null;
        $product_name = $first_item instanceof \WC_Order_Item_Product ? $first_item->get_name() : '';

        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        if ( '' === $customer_name ) {
            $customer_name = trim( (string) $order->get_formatted_billing_full_name() );
        }

        $next_payment = (int) $order->get_meta( self::NEXT_PAYMENT_META );
        $status       = (string) $order->get_meta( self::STATUS_META );
        $created      = $order->get_date_created();

        return array(
            'customer_name'  => $customer_name,
            'customer_email' => (string) $order->get_billing_email(),
            'product_name'   => (string) $product_name,
            'amount'         => (float) $order->get_total(),
            'currency'       => (string) $order->get_currency(),
            'interval'       => max( 1, (int) $order->get_meta( self::INTERVAL_META ) ),
            'period'         => self::sanitize_period( $order->get_meta( self::PERIOD_META ) ),
            'status'         => $status,
            'renewal_count'  => (int) $order->get_meta( self::RENEWAL_COUNT_META ),
            // No next charge on a terminal subscription — send '' so the UI
            // shows an em dash instead of a stale date.
            'next_payment'   => ( $next_payment > 0 && in_array( $status, array( 'active', 'past_due' ), true ) )
                ? wp_date( 'Y-m-d H:i:s', $next_payment )
                : '',
            'start_date'     => $created ? $created->date( 'Y-m-d H:i:s' ) : '',
            'order_edit_url' => (string) $order->get_edit_order_url(),
        );
    }

    /**
     * Light status lookup for one subscription — just the raw
     * `_bp_subscription_status` meta, lowercased, with no row hydration.
     * Used by the admin Subscriptions tab's summary counts, which need
     * every subscription's status but nothing else from the order.
     *
     * @param int $subscription_id Parent (subscription) order id.
     * @return string Raw status ('' when WooCommerce or the order is gone).
     * @since 2.4.0
     */
    public static function admin_status( $subscription_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return '';
        }

        $order = wc_get_order( absint( $subscription_id ) );

        if ( ! $order instanceof \WC_Order ) {
            return '';
        }

        return strtolower( (string) $order->get_meta( self::STATUS_META ) );
    }

    /**
     * Pure: the status actions the admin Subscriptions details view may
     * offer for a subscription in the given status. Status transitions are
     * the ONLY thing editable — everything else is read-only.
     *
     * `completed` is terminal (the subscription's schedule ended;
     * reactivating would restart a schedule the customer finished paying),
     * and an empty/unknown status (un-hydrated row, integration gone)
     * offers nothing.
     *
     * @param string $status Raw `_bp_subscription_status` meta value.
     * @return string[] Zero or more of 'cancel' | 'reactivate'.
     */
    public static function available_admin_actions( $status ) {
        $map = array(
            'active'    => array( 'cancel' ),
            'past_due'  => array( 'cancel', 'reactivate' ),
            'cancelled' => array( 'reactivate' ),
        );

        $status = strtolower( (string) $status );

        return isset( $map[ $status ] ) ? $map[ $status ] : array();
    }

    /**
     * Extended display data for one subscription on the admin details view:
     * the list row plus the fields only the details page shows. Returns null
     * when WooCommerce (or the order) is unavailable, same contract as
     * admin_list_row().
     *
     * @param int $subscription_id Parent (subscription) order id.
     * @return array|null
     * @since 2.4.0
     */
    public static function admin_detail( $subscription_id ) {
        $row = self::admin_list_row( $subscription_id );

        if ( null === $row ) {
            return null;
        }

        $order = wc_get_order( absint( $subscription_id ) );

        if ( ! $order instanceof \WC_Order ) {
            return null;
        }

        // Effective state, not the raw AUTO_RENEW_META flag: the site-wide
        // renewal policy AND the customer's own preference — the same test
        // renew() applies. Reporting the flag alone showed "On" while a
        // manual policy meant every renewal was actually invoiced.
        $row['auto_renew']        = self::auto_renewal_enabled_for( $order ) ? 'yes' : 'no';
        $row['available_actions'] = self::available_admin_actions( $row['status'] );

        // When the most recent renewal payment settled — '' until the first
        // renewal, and the UI drops the row entirely rather than showing an
        // empty value (same treatment next_payment gets on a terminal
        // subscription).
        $last_payment        = self::last_payment_timestamp( $order );
        $row['last_payment'] = $last_payment > 0 ? wp_date( 'Y-m-d H:i:s', $last_payment ) : '';

        // Who cancelled the subscription. Only reported while the status is
        // actually cancelled AND cancel() stamped an actor — pre-stamp
        // cancellations have no answer, and the UI drops the row rather than
        // guessing.
        $row['cancelled_by_type'] = '';
        $row['cancelled_by_name'] = '';

        if ( 'cancelled' === $row['status'] ) {
            $actor = (string) $order->get_meta( self::CANCELLED_BY_TYPE_META );

            if ( in_array( $actor, array( 'customer', 'admin' ), true ) ) {
                $row['cancelled_by_type'] = $actor;
                $row['cancelled_by_name'] = self::cancelled_by_name( $order, $actor );
            }
        }

        // Billing & Shipping card. Plain-text lines, not WooCommerce's
        // formatted-address HTML — the React admin renders text children
        // only, never markup.
        $row['billing_address']  = self::address_lines( $order->get_formatted_billing_address() );
        $row['shipping_address'] = self::address_lines( $order->get_formatted_shipping_address() );
        $row['billing_phone']    = (string) $order->get_billing_phone();

        return $row;
    }

    /**
     * Display name of the user who cancelled the subscription. Resolved live
     * from the stamped user id so a renamed account reads current; when that
     * user is gone (or the stamp predates a user id), a customer
     * cancellation still has the order's own billing name to fall back on —
     * an admin one does not, and returns '' (the UI shows the actor tag
     * alone).
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @param string    $actor 'customer' | 'admin'.
     * @return string
     * @since 2.4.0
     */
    public static function cancelled_by_name( $order, $actor ) {
        $user_id = (int) $order->get_meta( self::CANCELLED_BY_META );
        $user    = $user_id > 0 ? get_userdata( $user_id ) : false;

        if ( $user && '' !== trim( (string) $user->display_name ) ) {
            return (string) $user->display_name;
        }

        if ( 'customer' === $actor ) {
            $billing = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

            if ( '' !== $billing ) {
                return $billing;
            }
        }

        return '';
    }

    /**
     * When the subscription's most recent renewal payment settled, as a UNIX
     * timestamp — 0 when it has never renewed (the initial checkout is the
     * Started date, not a renewal payment). Reads the LAST_PAYMENT_META stamp
     * settle_renewal() writes; subscriptions renewed before the stamp existed
     * fall back to the newest paid renewal order's paid date.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return int
     * @since 2.4.0
     */
    public static function last_payment_timestamp( $order ) {
        $stamped = (int) $order->get_meta( self::LAST_PAYMENT_META );

        if ( $stamped > 0 ) {
            return $stamped;
        }

        if ( (int) $order->get_meta( self::RENEWAL_COUNT_META ) < 1 ) {
            return 0;
        }

        $renewals = wc_get_orders(
            array(
                'limit'      => 1,
                'type'       => 'shop_order',
                'status'     => array( 'wc-processing', 'wc-completed' ),
                'orderby'    => 'date',
                'order'      => 'DESC',
                'meta_key'   => self::RENEWAL_PARENT_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- bounded (limit 1), details-view only.
                'meta_value' => (string) $order->get_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- see above.
            )
        );

        $renewal = is_array( $renewals ) && ! empty( $renewals ) ? current( $renewals ) : null;

        if ( ! $renewal instanceof \WC_Order ) {
            return 0;
        }

        $paid = $renewal->get_date_paid();

        if ( ! $paid ) {
            $paid = $renewal->get_date_created();
        }

        return $paid ? $paid->getTimestamp() : 0;
    }

    /**
     * Pure: a WooCommerce formatted address (lines joined with <br/>) as an
     * array of plain-text lines. Empty/whitespace lines are dropped; an
     * empty/absent address yields an empty array (the UI renders an em dash).
     *
     * @param mixed $formatted The formatted address HTML ('' when unset).
     * @return string[]
     * @since 2.4.0
     */
    public static function address_lines( $formatted ) {
        if ( ! is_string( $formatted ) || '' === trim( $formatted ) ) {
            return array();
        }

        $lines = preg_split( '#<br\s*/?>#i', $formatted );
        $clean = array();

        foreach ( (array) $lines as $line ) {
            $line = trim( wp_strip_all_tags( (string) $line ) );

            if ( '' !== $line ) {
                $clean[] = $line;
            }
        }

        return $clean;
    }

    /**
     * Display row for one related order on the admin details view's
     * Related Orders list. Returns null when WooCommerce (or the order) is
     * unavailable — the caller keeps its un-hydrated base row.
     *
     * @param int $order_id Related (parent or renewal) order id.
     * @return array|null
     * @since 2.4.0
     */
    public static function admin_order_row( $order_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return null;
        }

        $order = wc_get_order( absint( $order_id ) );

        if ( ! $order instanceof \WC_Order ) {
            return null;
        }

        $created = $order->get_date_created();
        $status  = (string) $order->get_status();

        return array(
            'order_number' => (string) $order->get_order_number(),
            'date'         => $created ? $created->date( 'Y-m-d H:i:s' ) : '',
            'status'       => $status,
            'status_label' => function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : ucfirst( $status ),
            'total'        => (float) $order->get_total(),
            'currency'     => (string) $order->get_currency(),
            'edit_url'     => (string) $order->get_edit_order_url(),
        );
    }

    /**
     * Perform an admin status action (cancel | reactivate) on a
     * subscription. Used by the admin Subscriptions REST endpoint.
     *
     * @param int    $subscription_id Parent (subscription) order id.
     * @param string $action          'cancel' or 'reactivate'.
     * @return true|\WP_Error
     * @since 2.4.0
     */
    public static function admin_status_action( $subscription_id, $action ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return new \WP_Error( 'woocommerce_inactive', __( 'WooCommerce is not active, so this subscription cannot be managed.', 'better-payment' ), array( 'status' => 400 ) );
        }

        $order = wc_get_order( absint( $subscription_id ) );

        if ( ! $order instanceof \WC_Order ) {
            return new \WP_Error( 'subscription_not_found', __( 'Subscription order not found.', 'better-payment' ), array( 'status' => 404 ) );
        }

        $status = (string) $order->get_meta( self::STATUS_META );

        if ( ! in_array( $action, self::available_admin_actions( $status ), true ) ) {
            return new \WP_Error( 'action_not_available', __( 'This action is not available for the subscription\'s current status.', 'better-payment' ), array( 'status' => 400 ) );
        }

        if ( 'cancel' === $action ) {
            self::cancel( $order, __( 'Better Payment: subscription cancelled from the Better Payment admin — no further automatic renewals will be charged.', 'better-payment' ), 'admin' );
        } else {
            self::reactivate( $order );
        }

        return true;
    }
}
