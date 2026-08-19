<?php

namespace Better_Payment\Lite\WooCommerce;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The "Subscription Total" summary on the cart and checkout pages.
 *
 * A subscription cart's Total row states what is charged TODAY, which on a
 * free-trial checkout is $0.00 and on every other checkout is a one-off
 * figure that says nothing about the recurring commitment. This adds a row
 * that states the commitment: the recurring amount, its schedule, and the
 * date of the first renewal charge.
 *
 * **Every figure here must mirror what the engine will actually do**, or the
 * row is a promise the plugin breaks:
 *
 * -   The **schedule, trial and cancellation policy come from the FIRST
 *     subscription item**, because that is what `Subscriptions::activate_subscription()`
 *     snapshots onto the order (one schedule per order — a documented
 *     limitation). Reading them from any other item, or blending them, would
 *     quote terms the subscription will not be created with.
 * -   The **recurring amount sums EVERY subscription item**, because
 *     `Subscriptions::create_renewal_order()` re-adds all of them at their
 *     current price. Summing only the first item would under-quote a
 *     multi-item cart.
 * -   Non-subscription items are excluded — they are bought once and never
 *     re-added to a renewal order.
 *
 * Classic cart/checkout only. The Blocks Cart/Checkout do not fire these
 * hooks; see `docs/features/woocommerce/subscriptions.md`.
 *
 * @since 2.4.0
 */
class CartTotals {

    /**
     * Store API extension namespace. The blocks script reads the summary
     * from `cart.extensions[ STORE_API_NAMESPACE ]`.
     *
     * @var string
     */
    const STORE_API_NAMESPACE = 'better-payment';

    /**
     * Blocks slot-fill script handle.
     *
     * @var string
     */
    const BLOCKS_SCRIPT = 'better-payment-wc-subscription-total';

    /**
     * Register the cart/checkout hooks — BOTH page technologies.
     *
     * A shop runs either the classic shortcode cart/checkout or the Cart and
     * Checkout **blocks**, and the two share no rendering path whatsoever.
     * Covering only the classic hooks ships a feature that is invisible on
     * every block-based shop (which is WooCommerce's default for new stores),
     * with nothing in the UI to explain the absence.
     *
     * -   **Classic** — `woocommerce_cart_totals_after_order_total` /
     *     `woocommerce_review_order_after_order_total`. Both fire inside the
     *     totals `<table>` (`cart/cart-totals.php`, `checkout/review-order.php`),
     *     so `render()` emits a `<tr>`; anything else breaks the table in
     *     every theme.
     * -   **Blocks** — the totals are React, so there is no PHP hook to render
     *     into. The summary travels as Store API cart data and a slot-fill
     *     script paints it. Both halves are required: the script alone has no
     *     data, and the data alone has nothing reading it.
     *
     * Both technologies render from the same `summary()`, so a shop cannot
     * see different figures depending on which pages it uses.
     *
     * @return void
     */
    public static function register() {
        add_action( 'woocommerce_cart_totals_after_order_total', array( __CLASS__, 'render' ) );
        add_action( 'woocommerce_review_order_after_order_total', array( __CLASS__, 'render' ) );
        add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_store_api_data' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
    }

    /**
     * Load the stylesheet and the blocks slot-fill script on the
     * cart/checkout pages only — never site-wide.
     *
     * Gated on the page, NOT on the cart holding a subscription. The blocks
     * cart is client-rendered and its contents change without a page load, so
     * a contents-based gate would decide once at page load and then be wrong:
     * remove the last subscription item and the stale script stays, add one
     * back and the script that should paint the row was never loaded. The
     * script self-suppresses when the Store API reports no subscription, so
     * the page check is the only gate that can stay correct.
     *
     * @return void
     */
    public static function enqueue_assets() {
        if ( ! function_exists( 'is_cart' ) || ! function_exists( 'is_checkout' ) ) {
            return;
        }

        if ( ! is_cart() && ! is_checkout() ) {
            return;
        }

        wp_enqueue_style( 'better-payment-wc-subscriptions' );

        // Enqueued unconditionally rather than behind a
        // wp_script_is( 'wc-blocks-checkout', 'registered' ) check: that
        // check depends on WooCommerce having registered its block assets
        // before this hook runs, and if that order ever changes the script is
        // dropped with no error and the row silently vanishes from every
        // block shop. WordPress already does the right thing — an unresolved
        // dependency simply means the script is not printed, which is exactly
        // what a classic-only shop wants (its row is already rendered in PHP).
        wp_register_script(
            self::BLOCKS_SCRIPT,
            BETTER_PAYMENT_ASSETS . '/js/woocommerce-subscription-total.js',
            array( 'wc-blocks-checkout', 'wp-element', 'wp-plugins' ),
            BETTER_PAYMENT_VERSION,
            true
        );

        wp_enqueue_script( self::BLOCKS_SCRIPT );
    }

    /**
     * Expose the summary as Store API cart data, so the blocks cart/checkout
     * can render it and — critically — re-render it on every cart change.
     *
     * Registered on `woocommerce_blocks_loaded`; a no-op on shops without the
     * blocks bundle.
     *
     * @return void
     */
    public static function register_store_api_data() {
        if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' )
            || ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema' ) ) {
            return;
        }

        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
                'namespace'       => self::STORE_API_NAMESPACE,
                'data_callback'   => array( __CLASS__, 'store_api_payload' ),
                'schema_callback' => array( __CLASS__, 'store_api_schema' ),
                'schema_type'     => ARRAY_A,
            )
        );
    }

    /**
     * The summary as the blocks client consumes it.
     *
     * Every string arrives **fully formatted and translated**. Currency
     * formatting honours a dozen shop settings (symbol, position, decimals,
     * separators) and the date honours the site's date format and locale —
     * re-implementing either in JS would drift from the classic row on the
     * same shop. The amount is plain text rather than `wc_price()` HTML so
     * the client can render it as a text node instead of injecting markup.
     *
     * @return array
     */
    public static function store_api_payload() {
        $summary = self::summary();

        if ( null === $summary ) {
            return array(
                'has_subscription' => false,
                'label'            => '',
                'amount'           => '',
                'next_billing'     => '',
                'cancel_note'      => '',
            );
        }

        return array(
            'has_subscription' => true,
            'label'            => self::label(),
            'amount'           => self::plain_price( self::amount_html( $summary ) ),
            'next_billing'     => self::next_billing_text( $summary ),
            'cancel_note'      => $summary['allow_cancel'] ? self::cancel_note() : '',
        );
    }

    /**
     * Schema for the Store API payload.
     *
     * @return array
     */
    public static function store_api_schema() {
        $string_field = static function ( $description ) {
            return array(
                'description' => $description,
                'type'        => 'string',
                'readonly'    => true,
            );
        };

        return array(
            'has_subscription' => array(
                'description' => __( 'Whether the cart contains a Better Payment subscription product.', 'better-payment' ),
                'type'        => 'boolean',
                'readonly'    => true,
            ),
            'label'            => $string_field( __( 'Row label.', 'better-payment' ) ),
            'amount'           => $string_field( __( 'Formatted recurring amount and schedule.', 'better-payment' ) ),
            'next_billing'     => $string_field( __( 'Formatted date of the first renewal charge.', 'better-payment' ) ),
            'cancel_note'      => $string_field( __( 'Cancellation note, empty when the product does not allow user cancellation.', 'better-payment' ) ),
        );
    }

    /**
     * Collect the current cart's subscription items in the shape
     * build_summary() consumes. Returns an empty array when the cart holds
     * no subscription product.
     *
     * @return array[] Ordered as the cart is; see build_summary() for keys.
     */
    public static function cart_items() {
        if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
            return array();
        }

        // Totals calculation zeroes a trial-eligible product's price through
        // a scoped filter that is removed the moment calculation ends, so by
        // template time the real price is back. Drop it explicitly anyway:
        // reading a price through that filter would quote the RECURRING
        // amount as $0.00 — the one figure this row exists to state — and
        // the next calculate_totals() re-adds the filter itself, so this
        // cannot leave trial pricing broken.
        Subscriptions::remove_trial_price_filter();

        $items = array();

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

            if ( ! Subscriptions::product_is_subscription( $product ) ) {
                continue;
            }

            $quantity = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;

            $items[] = array(
                // Respects the shop's cart tax-display setting, so the
                // recurring figure is quoted the same way as the Total
                // directly above it.
                'recurring_total' => (float) wc_get_price_to_display( $product, array( 'qty' => $quantity ) ),
                // What this line costs TODAY — $0.00 when the trial applied.
                // That is the same marker activate_subscription() uses to
                // decide a trial was taken, so both agree by construction.
                'line_total'      => isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0,
                'interval'        => max( 1, (int) $product->get_meta( Subscriptions::PRODUCT_INTERVAL_META ) ),
                'period'          => Subscriptions::sanitize_period( $product->get_meta( Subscriptions::PRODUCT_PERIOD_META ) ),
                'trial_days'      => max( 0, (int) $product->get_meta( Subscriptions::PRODUCT_TRIAL_DAYS_META ) ),
                'allow_cancel'    => 'yes' === $product->get_meta( Subscriptions::USER_CANCEL_META ),
            );
        }

        return $items;
    }

    /**
     * Pure: reduce subscription cart items to the summary this row renders.
     *
     * @param mixed $items Items from cart_items() — a list of arrays, each
     *                     with `recurring_total`, `line_total`, `interval`,
     *                     `period`, `trial_days` and `allow_cancel`. Anything
     *                     else yields null rather than a garbage quote.
     * @param int   $now   Base timestamp for the next-payment date (0 = now).
     * @return array|null Null when there is nothing to summarise, else
     *                    `total`, `interval`, `period`, `trial_days`,
     *                    `trial_applied`, `next_payment`, `allow_cancel`.
     */
    public static function build_summary( $items, $now = 0 ) {
        if ( ! is_array( $items ) || empty( $items ) ) {
            return null;
        }

        $items = array_values( $items );
        $first = $items[0];

        $interval   = isset( $first['interval'] ) ? max( 1, (int) $first['interval'] ) : 1;
        $period     = Subscriptions::sanitize_period( isset( $first['period'] ) ? $first['period'] : 'month' );
        $trial_days = isset( $first['trial_days'] ) ? max( 0, (int) $first['trial_days'] ) : 0;

        // The trial was taken iff the customer is actually checking out at $0
        // for this line — exactly the test activate_subscription() applies to
        // the placed order, so the quoted first-charge date matches the one
        // the subscription is created with.
        $trial_applied = $trial_days > 0 && abs( isset( $first['line_total'] ) ? (float) $first['line_total'] : 0.0 ) < 0.01;

        $total = 0.0;
        foreach ( $items as $item ) {
            $total += isset( $item['recurring_total'] ) ? (float) $item['recurring_total'] : 0.0;
        }

        if ( $total <= 0 ) {
            // Nothing recurring to quote (a free subscription product). A
            // "$0.00 / month" row tells the customer nothing.
            return null;
        }

        return array(
            'total'         => $total,
            'interval'      => $interval,
            'period'        => $period,
            'trial_days'    => $trial_days,
            'trial_applied' => $trial_applied,
            'next_payment'  => $trial_applied
                ? Subscriptions::trial_end_timestamp( $trial_days, $now )
                : Subscriptions::next_payment_timestamp( $interval, $period, $now ),
            // The cancellation promise follows the SAME item the policy is
            // snapshotted from at activation, so the row never offers a
            // cancel button the customer will not get.
            'allow_cancel'  => ! empty( $first['allow_cancel'] ),
        );
    }

    /**
     * Summary for the current cart, or null when there is nothing to show.
     *
     * @return array|null
     */
    public static function summary() {
        return self::build_summary( self::cart_items() );
    }

    /**
     * The row label.
     *
     * @return string
     */
    public static function label() {
        return __( 'Subscription Total', 'better-payment' );
    }

    /**
     * The conditional cancellation note.
     *
     * @return string
     */
    public static function cancel_note() {
        return __( 'Cancel anytime from your account.', 'better-payment' );
    }

    /**
     * Recurring amount + schedule, as `wc_price()` HTML.
     *
     * Formatted with describe_schedule(), the same helper behind the
     * product-page price suffix, so the customer reads identical wording in
     * both places.
     *
     * @param array $summary From build_summary().
     * @return string
     */
    public static function amount_html( $summary ) {
        return sprintf(
            /* translators: 1: recurring price, 2: billing schedule (e.g. "month" or "every 3 months") */
            __( '%1$s / %2$s', 'better-payment' ),
            wc_price( $summary['total'] ),
            Subscriptions::describe_schedule( $summary['interval'], $summary['period'] )
        );
    }

    /**
     * The next-billing line.
     *
     * @param array $summary From build_summary().
     * @return string
     */
    public static function next_billing_text( $summary ) {
        return sprintf(
            /* translators: %s: date of the first renewal charge */
            __( 'Next billing on: %s', 'better-payment' ),
            date_i18n( get_option( 'date_format' ), $summary['next_payment'] )
        );
    }

    /**
     * Pure: `wc_price()` HTML reduced to plain text ("$220.00 / month").
     *
     * The currency symbol arrives as an HTML entity for most currencies
     * (`&#036;`, `&euro;`), so stripping tags alone would render the entity
     * literally in a React text node.
     *
     * @param string $price_html Formatted price HTML.
     * @return string
     */
    public static function plain_price( $price_html ) {
        return trim( html_entity_decode( wp_strip_all_tags( (string) $price_html ), ENT_QUOTES, 'UTF-8' ) );
    }

    /**
     * Render the classic "Subscription Total" row.
     *
     * @return void
     */
    public static function render() {
        $summary = self::summary();

        if ( null === $summary ) {
            return;
        }

        $label        = self::label();
        $amount       = self::amount_html( $summary );
        $next_billing = self::next_billing_text( $summary );

        ?>
        <tr class="bp-subscription-total">
            <th><?php echo esc_html( $label ); ?></th>
            <td data-title="<?php echo esc_attr( $label ); ?>">
                <span class="bp-subscription-total__amount"><?php echo wp_kses_post( $amount ); ?></span>
                <span class="bp-subscription-total__meta"><?php echo esc_html( $next_billing ); ?></span>
                <?php if ( $summary['allow_cancel'] ) : ?>
                    <span class="bp-subscription-total__meta bp-subscription-total__cancel">
                        <?php echo esc_html( self::cancel_note() ); ?>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
