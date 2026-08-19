<?php

namespace Better_Payment\Lite\WooCommerce;

use Better_Payment\Lite\Models\SubscriptionRelationModel;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * My Account > Subscriptions tab.
 *
 * Adds two WooCommerce My Account endpoints — a Subscriptions list and a
 * single-subscription view — where customers see and manage their Better
 * Payment subscriptions. All management actions reuse the existing
 * nonce-guarded POST handlers in {@see Subscriptions} (customer cancel,
 * auto-renewal toggle) and WooCommerce's own key-guarded order-pay URL for
 * past-due renewal invoices; this class never mutates subscription state
 * itself.
 *
 * Registration notes:
 * - The endpoints are added through `woocommerce_get_query_vars`, which
 *   WooCommerce itself turns into rewrite endpoints — no direct
 *   `add_rewrite_endpoint()` call is needed.
 * - Rewrite rules are flushed ONCE, keyed on a stored version option —
 *   never unconditionally on every request.
 * - The endpoints stay registered even while the My Account Tab setting is
 *   off; the setting gates the menu item and the rendered content, so
 *   toggling it needs no rewrite flush.
 * - State-changing actions are POST forms with per-order nonces, handled on
 *   `template_redirect` (proper redirect-after-post) — never GET links, and
 *   never handled mid-template after headers are sent.
 *
 * @since 2.4.0
 */
class MyAccount {

    /**
     * My Account endpoint slug for the subscriptions list. The endpoint
     * value, when present, is the list page number.
     */
    const ENDPOINT = 'bp-subscriptions';

    /**
     * My Account endpoint slug for the single-subscription view. The
     * endpoint value is the parent (subscription) order id.
     */
    const VIEW_ENDPOINT = 'bp-view-subscription';

    /**
     * Option storing the rewrite version the rules were last flushed for.
     */
    const REWRITE_OPTION = 'better_payment_wc_myaccount_rewrite_version';

    /**
     * Bump to force a one-time rewrite flush after an endpoint change.
     */
    const REWRITE_VERSION = '1';

    /**
     * Subscriptions shown per list page.
     */
    const PER_PAGE = 10;

    /**
     * Register the My Account hooks. Called from Loader::register() — only
     * when WooCommerce is active.
     *
     * @return void
     */
    public static function register() {
        // Endpoint registration. WooCommerce adds a rewrite endpoint for
        // every query var in this filter, so this is the single source of
        // registration for both endpoints.
        add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'add_query_vars' ) );

        // One-time rewrite flush so the new endpoints resolve without a
        // manual Settings > Permalinks save.
        add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 50 );

        // The Subscriptions tab, above Logout.
        add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ), 200 );

        // Endpoint content + browser/page titles.
        add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render_list' ) );
        add_action( 'woocommerce_account_' . self::VIEW_ENDPOINT . '_endpoint', array( __CLASS__, 'render_view' ) );
        add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( __CLASS__, 'list_title' ) );
        add_filter( 'woocommerce_endpoint_' . self::VIEW_ENDPOINT . '_title', array( __CLASS__, 'view_title' ) );

        // Status badge / table styling, on My Account pages only.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 20 );
    }

    /**
     * Whether the My Account Subscriptions tab is enabled (E-commerce >
     * Subscription > My Account Subscriptions Tab setting). Read live on
     * every request — never cached, never inferred.
     *
     * @return bool
     */
    public static function enabled() {
        return 'yes' === Subscriptions::setting( 'myaccount_tab' );
    }

    /**
     * Register both endpoints as WooCommerce query vars. WooCommerce turns
     * these into rewrite endpoints on `init`.
     *
     * @param array $vars WooCommerce query vars (internal name => slug).
     * @return array
     */
    public static function add_query_vars( $vars ) {
        $vars[ self::ENDPOINT ]      = self::ENDPOINT;
        $vars[ self::VIEW_ENDPOINT ] = self::VIEW_ENDPOINT;

        return $vars;
    }

    /**
     * Flush rewrite rules once per endpoint-schema version. Flushing them
     * unconditionally rebuilds and re-saves the whole rule set on every
     * request; keying on an option keeps this a one-time cost.
     *
     * @return void
     */
    public static function maybe_flush_rewrite_rules() {
        if ( self::REWRITE_VERSION === get_option( self::REWRITE_OPTION ) ) {
            return;
        }

        flush_rewrite_rules();
        update_option( self::REWRITE_OPTION, self::REWRITE_VERSION );
    }

    /**
     * Add the Subscriptions tab to the My Account menu (gated on the
     * My Account Tab setting).
     *
     * @param array $items Menu items (endpoint => label).
     * @return array
     */
    public static function add_menu_item( $items ) {
        if ( ! self::enabled() ) {
            return $items;
        }

        return self::insert_menu_item( (array) $items, __( 'Subscriptions', 'better-payment' ) );
    }

    /**
     * Pure: insert the Subscriptions item before Logout, preserving the
     * existing order. Appends when there is no logout item (a theme may have
     * removed it), and never duplicates an existing entry.
     *
     * @param array  $items Menu items (endpoint => label).
     * @param string $label Tab label.
     * @return array
     */
    public static function insert_menu_item( $items, $label ) {
        if ( isset( $items[ self::ENDPOINT ] ) ) {
            return $items;
        }

        $updated = array();

        foreach ( $items as $endpoint => $item_label ) {
            if ( 'customer-logout' === $endpoint ) {
                $updated[ self::ENDPOINT ] = $label;
            }

            $updated[ $endpoint ] = $item_label;
        }

        if ( ! isset( $updated[ self::ENDPOINT ] ) ) {
            $updated[ self::ENDPOINT ] = $label;
        }

        return $updated;
    }

    /**
     * Browser/page title for the list endpoint.
     *
     * @param string $title Default endpoint title.
     * @return string
     */
    public static function list_title( $title ) {
        return __( 'Subscriptions', 'better-payment' );
    }

    /**
     * Browser/page title for the single-subscription endpoint.
     *
     * @param string $title Default endpoint title.
     * @return string
     */
    public static function view_title( $title ) {
        $order_id = absint( get_query_var( self::VIEW_ENDPOINT ) );

        if ( $order_id > 0 ) {
            /* translators: %d: subscription (parent order) id */
            return sprintf( __( 'Subscription #%d', 'better-payment' ), $order_id );
        }

        return __( 'Subscription', 'better-payment' );
    }

    /**
     * URL of the subscriptions list (optionally a specific page).
     *
     * @param int $page List page number (1 emits the bare endpoint URL).
     * @return string
     */
    public static function list_url( $page = 1 ) {
        if ( ! function_exists( 'wc_get_endpoint_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
            return '';
        }

        $page = max( 1, (int) $page );

        return wc_get_endpoint_url( self::ENDPOINT, ( $page > 1 ) ? (string) $page : '', wc_get_page_permalink( 'myaccount' ) );
    }

    /**
     * URL of one subscription's My Account view.
     *
     * @param int $order_id Parent (subscription) order id.
     * @return string
     */
    public static function view_url( $order_id ) {
        if ( ! function_exists( 'wc_get_endpoint_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
            return '';
        }

        return wc_get_endpoint_url( self::VIEW_ENDPOINT, (string) absint( $order_id ), wc_get_page_permalink( 'myaccount' ) );
    }

    /**
     * Enqueue the subscription table/badge styles on My Account pages only —
     * never site-wide (a status stylesheet loaded on every frontend page is
     * pure weight on the pages that show no subscription).
     *
     * @return void
     */
    public static function enqueue_styles() {
        if ( ! self::enabled() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
            return;
        }

        wp_enqueue_style( 'better-payment-wc-subscriptions' );
    }

    /* ---------------------------------------------------------------------
     * List view
     * ------------------------------------------------------------------- */

    /**
     * Render the subscriptions list endpoint.
     *
     * Ownership is enforced at the query: only the current user's own
     * subscription parent orders are fetched. Logged-out visitors get
     * WooCommerce's own My Account login form before this ever runs, but the
     * guard stays anyway — an endpoint callback must never rely on the page
     * around it for authentication.
     *
     * @param mixed $current_page Endpoint value (list page number).
     * @return void
     */
    public static function render_list( $current_page ) {
        if ( ! self::enabled() || ! is_user_logged_in() || ! function_exists( 'wc_get_orders' ) ) {
            return;
        }

        $page   = max( 1, absint( $current_page ) );
        $result = Subscriptions::customer_subscriptions( get_current_user_id(), $page, self::PER_PAGE );

        if ( empty( $result['orders'] ) ) {
            self::render_empty_list();
            return;
        }

        ?>
        <table class="woocommerce-orders-table shop_table shop_table_responsive my_account_orders bp-subscriptions-table">
            <thead>
                <tr>
                    <th><span class="nobr"><?php esc_html_e( 'Subscription', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Product', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Status', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Next payment', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Total', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Actions', 'better-payment' ); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $result['orders'] as $order ) : ?>
                    <?php
                    if ( ! $order instanceof \WC_Order ) {
                        continue;
                    }

                    $status = (string) $order->get_meta( Subscriptions::STATUS_META );
                    ?>
                    <tr>
                        <td data-title="<?php esc_attr_e( 'Subscription', 'better-payment' ); ?>">
                            <a href="<?php echo esc_url( self::view_url( $order->get_id() ) ); ?>">
                                <?php echo esc_html( '#' . $order->get_order_number() ); ?>
                            </a>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Product', 'better-payment' ); ?>">
                            <?php echo esc_html( self::product_names( $order ) ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Status', 'better-payment' ); ?>">
                            <?php self::render_status_badge( $status ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Next payment', 'better-payment' ); ?>">
                            <?php echo esc_html( self::next_payment_text( $order, $status ) ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Total', 'better-payment' ); ?>">
                            <?php echo wp_kses_post( self::amount_text( $order ) ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Actions', 'better-payment' ); ?>">
                            <a class="woocommerce-button button view" href="<?php echo esc_url( self::view_url( $order->get_id() ) ); ?>">
                                <?php esc_html_e( 'View', 'better-payment' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        self::render_pagination( $page, (int) $result['max_pages'] );
    }

    /**
     * Empty state — a notice plus a route to the shop, mirroring
     * WooCommerce's own empty orders list.
     *
     * @return void
     */
    protected static function render_empty_list() {
        ?>
        <div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
            <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
                <a class="woocommerce-Button button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
                    <?php esc_html_e( 'Browse products', 'better-payment' ); ?>
                </a>
            <?php endif; ?>
            <?php esc_html_e( 'You have no subscriptions yet.', 'better-payment' ); ?>
        </div>
        <?php
    }

    /**
     * Previous/next pagination for the list. Built from the endpoint
     * constants — never a hardcoded slug, which breaks the moment the
     * endpoint slug is customized.
     *
     * @param int $page      Current page (1-based).
     * @param int $max_pages Total pages.
     * @return void
     */
    protected static function render_pagination( $page, $max_pages ) {
        if ( $max_pages <= 1 ) {
            return;
        }

        ?>
        <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
            <?php if ( $page > 1 ) : ?>
                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( self::list_url( $page - 1 ) ); ?>">
                    <?php esc_html_e( 'Previous', 'better-payment' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $page < $max_pages ) : ?>
                <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( self::list_url( $page + 1 ) ); ?>">
                    <?php esc_html_e( 'Next', 'better-payment' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ---------------------------------------------------------------------
     * Single subscription view
     * ------------------------------------------------------------------- */

    /**
     * Render the single-subscription endpoint.
     *
     * Security gates, in order: feature enabled, viewer logged in, the order
     * exists, the order IS a subscription parent (has subscription status
     * meta), and the viewer is the order's own customer. A failed gate
     * renders an error notice — never a redirect, because endpoint content
     * runs mid-template after headers are sent (redirecting here lands the
     * customer on a broken '/404' path instead of the notice).
     *
     * @param mixed $order_id Endpoint value (parent order id).
     * @return void
     */
    public static function render_view( $order_id ) {
        if ( ! self::enabled() || ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            self::render_invalid_notice();
            return;
        }

        $order = wc_get_order( absint( $order_id ) );

        if (
            ! $order instanceof \WC_Order
            || '' === (string) $order->get_meta( Subscriptions::STATUS_META )
            || get_current_user_id() !== $order->get_customer_id()
        ) {
            self::render_invalid_notice();
            return;
        }

        $status = (string) $order->get_meta( Subscriptions::STATUS_META );

        self::render_details_table( $order, $status );
        self::render_actions( $order );
        self::render_related_orders( $order );

        ?>
        <p class="bp-subscription-back">
            <a class="woocommerce-button button" href="<?php echo esc_url( self::list_url() ); ?>">
                <?php esc_html_e( 'Back to subscriptions', 'better-payment' ); ?>
            </a>
        </p>
        <?php
    }

    /**
     * "Invalid subscription" notice — same message for a missing order, a
     * non-subscription order, and someone else's subscription, so the
     * response does not confirm whether a guessed id exists.
     *
     * @return void
     */
    protected static function render_invalid_notice() {
        if ( function_exists( 'wc_print_notice' ) ) {
            wc_print_notice( __( 'That subscription could not be found.', 'better-payment' ), 'error' );
        }

        ?>
        <p>
            <a class="woocommerce-button button" href="<?php echo esc_url( self::list_url() ); ?>">
                <?php esc_html_e( 'Back to subscriptions', 'better-payment' ); ?>
            </a>
        </p>
        <?php
    }

    /**
     * The subscription details table (status, schedule, dates, renewals,
     * auto-renewal state, payment method, parent order link).
     *
     * @param \WC_Order $order  Parent (subscription) order.
     * @param string    $status Raw subscription status.
     * @return void
     */
    protected static function render_details_table( $order, $status ) {
        $created      = $order->get_date_created();
        $next_payment = self::next_payment_text( $order, $status );
        $count        = (int) $order->get_meta( Subscriptions::RENEWAL_COUNT_META );

        ?>
        <h2 class="bp-subscription-section-title"><?php esc_html_e( 'Subscription details', 'better-payment' ); ?></h2>
        <table class="woocommerce-table shop_table bp-subscription-details">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Status', 'better-payment' ); ?></th>
                    <td><?php self::render_status_badge( $status ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Product', 'better-payment' ); ?></th>
                    <td><?php echo esc_html( self::product_names( $order ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Amount', 'better-payment' ); ?></th>
                    <td><?php echo wp_kses_post( self::amount_text( $order ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Started', 'better-payment' ); ?></th>
                    <td><?php echo esc_html( $created ? date_i18n( get_option( 'date_format' ), $created->getTimestamp() ) : '—' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Next payment', 'better-payment' ); ?></th>
                    <td><?php echo esc_html( '' !== $next_payment ? $next_payment : '—' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Renewals', 'better-payment' ); ?></th>
                    <td><?php echo esc_html( (string) $count ); ?></td>
                </tr>
                <?php if ( 'yes' === (string) $order->get_meta( Subscriptions::TRIAL_APPLIED_META ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Free trial', 'better-payment' ); ?></th>
                        <td><?php esc_html_e( 'Applied on signup — the first payment was charged when the trial ended.', 'better-payment' ); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( Subscriptions::should_save_payment_method() ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Automatic renewal', 'better-payment' ); ?></th>
                        <td>
                            <?php
                            echo esc_html(
                                Subscriptions::auto_renewal_enabled_for( $order )
                                    ? __( 'On', 'better-payment' )
                                    : __( 'Off', 'better-payment' )
                            );
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ( '' !== (string) $order->get_payment_method_title() ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Payment method', 'better-payment' ); ?></th>
                        <td><?php echo esc_html( $order->get_payment_method_title() ); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Signup order', 'better-payment' ); ?></th>
                    <td>
                        <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                            <?php echo esc_html( '#' . $order->get_order_number() ); ?>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Customer actions: pay a pending renewal invoice, cancel, and the
     * auto-renewal toggle. Cancel and the toggle are the existing
     * nonce-guarded POST forms from {@see Subscriptions} — rendered in
     * 'subscription' context so their handlers redirect back here. Each
     * renders only when its own policy allows it (user-cancel snapshot,
     * Customer Auto-Renew Control setting, live status, stored payment method).
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return void
     */
    protected static function render_actions( $order ) {
        $pending = Subscriptions::pending_renewal_order( $order );

        ob_start();

        if ( $pending instanceof \WC_Order && get_current_user_id() === $pending->get_customer_id() ) {
            ?>
            <a class="woocommerce-button button bp-subscription-pay" href="<?php echo esc_url( $pending->get_checkout_payment_url() ); ?>">
                <?php esc_html_e( 'Pay renewal invoice', 'better-payment' ); ?>
            </a>
            <?php
        }

        Subscriptions::render_auto_renew_toggle( $order, 'subscription' );
        Subscriptions::render_customer_cancel_button( $order, 'subscription' );

        $actions = trim( (string) ob_get_clean() );

        if ( '' === $actions ) {
            return;
        }

        ?>
        <h2 class="bp-subscription-section-title"><?php esc_html_e( 'Manage subscription', 'better-payment' ); ?></h2>
        <div class="bp-subscription-actions">
            <?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from fully escaped partials. ?>
        </div>
        <?php
    }

    /**
     * Related orders (signup + renewals) from the subscription/order
     * relation table. Only orders that belong to the viewing customer are
     * listed — a relation row must never leak another customer's order.
     *
     * @param \WC_Order $parent Parent (subscription) order.
     * @return void
     */
    protected static function render_related_orders( $parent ) {
        $relations = SubscriptionRelationModel::get_orders( $parent->get_id(), 'woo' );

        if ( empty( $relations ) ) {
            return;
        }

        $rows = array();

        foreach ( $relations as $relation ) {
            $order = wc_get_order( absint( $relation->order_id ) );

            if ( ! $order instanceof \WC_Order || get_current_user_id() !== $order->get_customer_id() ) {
                continue;
            }

            $rows[] = array(
                'order' => $order,
                'type'  => (string) $relation->type,
            );
        }

        if ( empty( $rows ) ) {
            return;
        }

        ?>
        <h2 class="bp-subscription-section-title"><?php esc_html_e( 'Related orders', 'better-payment' ); ?></h2>
        <table class="woocommerce-table shop_table shop_table_responsive bp-subscription-orders">
            <thead>
                <tr>
                    <th><span class="nobr"><?php esc_html_e( 'Order', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Date', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Type', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Status', 'better-payment' ); ?></span></th>
                    <th><span class="nobr"><?php esc_html_e( 'Total', 'better-payment' ); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) : ?>
                    <?php
                    $order   = $row['order'];
                    $created = $order->get_date_created();
                    ?>
                    <tr>
                        <td data-title="<?php esc_attr_e( 'Order', 'better-payment' ); ?>">
                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                <?php echo esc_html( '#' . $order->get_order_number() ); ?>
                            </a>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Date', 'better-payment' ); ?>">
                            <?php echo esc_html( $created ? date_i18n( get_option( 'date_format' ), $created->getTimestamp() ) : '—' ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Type', 'better-payment' ); ?>">
                            <?php echo esc_html( 'renew' === $row['type'] ? __( 'Renewal', 'better-payment' ) : __( 'Signup', 'better-payment' ) ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Status', 'better-payment' ); ?>">
                            <?php echo esc_html( function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : ucfirst( $order->get_status() ) ); ?>
                        </td>
                        <td data-title="<?php esc_attr_e( 'Total', 'better-payment' ); ?>">
                            <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /* ---------------------------------------------------------------------
     * Display helpers
     * ------------------------------------------------------------------- */

    /**
     * The subscription product name(s) on an order, comma-joined.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return string
     */
    protected static function product_names( $order ) {
        $names = array();

        foreach ( Subscriptions::order_subscription_items( $order ) as $item ) {
            $names[] = $item->get_name();
        }

        return implode( ', ', $names );
    }

    /**
     * "$10.00 / month" — the order total plus the billing schedule
     * snapshotted on the parent order.
     *
     * @param \WC_Order $order Parent (subscription) order.
     * @return string HTML (wc_price output).
     */
    protected static function amount_text( $order ) {
        $interval = max( 1, (int) $order->get_meta( Subscriptions::INTERVAL_META ) );
        $period   = Subscriptions::sanitize_period( $order->get_meta( Subscriptions::PERIOD_META ) );
        $price    = function_exists( 'wc_price' )
            ? wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) )
            : $order->get_currency() . ' ' . $order->get_total();

        return sprintf(
            /* translators: 1: formatted price, 2: billing schedule ("month", "every 3 months") */
            __( '%1$s / %2$s', 'better-payment' ),
            $price,
            Subscriptions::describe_schedule( $interval, $period )
        );
    }

    /**
     * Next-payment date text — empty on terminal statuses (a cancelled
     * subscription has no next charge, and a stale date reads as one).
     *
     * @param \WC_Order $order  Parent (subscription) order.
     * @param string    $status Raw subscription status.
     * @return string
     */
    protected static function next_payment_text( $order, $status ) {
        $next = (int) $order->get_meta( Subscriptions::NEXT_PAYMENT_META );

        if ( $next <= 0 || ! in_array( $status, array( 'active', 'past_due' ), true ) ) {
            return '';
        }

        return date_i18n( get_option( 'date_format' ), $next );
    }

    /**
     * Status badge markup.
     *
     * @param string $status Raw subscription status.
     * @return void
     */
    protected static function render_status_badge( $status ) {
        $status = strtolower( (string) $status );

        printf(
            '<span class="bp-subscription-status bp-subscription-status--%1$s">%2$s</span>',
            esc_attr( sanitize_html_class( $status, 'unknown' ) ),
            esc_html( Subscriptions::customer_status_label( $status ) )
        );
    }
}
