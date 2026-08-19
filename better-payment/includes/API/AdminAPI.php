<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Admin\SubscriptionListFilter;
use Better_Payment\Lite\Models\SubscriptionRelationModel;
use Better_Payment\Lite\Traits\Helper;
use Better_Payment\Lite\WooCommerce\Subscriptions as WooSubscriptions;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin REST API Controller
 *
 * @since 1.5.0
 */
class AdminAPI extends WP_REST_Controller
{
    use Helper;

    /**
     * Namespace
     *
     * @var string
     */
    protected $namespace = 'better-payment/v1';

    /**
     * Constructor
     *
     * @since 1.5.0
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     *
     * @since 1.5.0
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/transactions', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_transactions'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/subscriptions', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_subscriptions'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        // Literal segment — never collides with the (?P<id>\d+) routes below,
        // whose regex only matches digits.
        register_rest_route($this->namespace, '/subscriptions/count', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_subscription_count'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        // A subscription is identified by (subscription_id, source) — the id
        // alone is ambiguous (the same numeric id can exist under two
        // sources), so every single-subscription route requires ?source=.
        register_rest_route($this->namespace, '/subscriptions/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_subscription'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/subscriptions/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_subscription'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/subscriptions/(?P<id>\d+)/status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_subscription_status'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/transactions/count', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_transaction_count'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_transaction'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_transaction'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)/mark-complete', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'mark_transaction_complete'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/transactions/(?P<id>\d+)/referer', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_transaction_referer'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_admin_permissions']
            ]
        ]);

        register_rest_route($this->namespace, '/dashboard/dismissible-section', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'show_dismissible_section'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/dashboard/dismissible-section-data', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_dismissible_section_data'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/dashboard/dismissible-section-dismiss', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'dismiss_dismissible_section'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/dashboard/sale-info-dismissed', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'is_sale_info_dismissed'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/dashboard/sale-info-dismiss', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'dismiss_sale_info'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        register_rest_route($this->namespace, '/options', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_options'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);

        // FluentCart product search
        register_rest_route($this->namespace, '/fluentcart-products', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'search_fluentcart_products'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'search' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);

        // FluentCart single product
        register_rest_route($this->namespace, '/fluentcart-product/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_fluentcart_product'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);

        // Stripe price details - fetches product name and price from Stripe API
        register_rest_route($this->namespace, '/stripe-price', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_stripe_price'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'price_id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }

    /**
     * Get transaction count
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_transaction_count($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $all = DB::get_transaction_count();
        $completed = DB::get_transaction_count('', 'v2', 0, 'completed');
        $incomplete = DB::get_transaction_count('', 'v2', 1, 'incomplete');

        return rest_ensure_response([
            'success' => true,
            'count' => [
                'all' => $all,
                'completed' => $completed,
                'incomplete' => $incomplete
            ]
        ]);
    }

    public function get_options($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $option_name = sanitize_text_field($request->get_param('option_name'));
        $options = get_option($option_name, true);
        if (!$options) {
            return rest_ensure_response("UTC");
        }
        return rest_ensure_response($options);
    }

    /**
     * Check if sale info is dismissed
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function is_sale_info_dismissed($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $is_sale_info_dismissed = get_option('better_payment_sale_info_dismissed', false);

        return rest_ensure_response([
            'success' => true,
            'isSaleInfoDismissed' => $is_sale_info_dismissed
        ]);
    }

    /**
     * Dismiss sale info
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function dismiss_sale_info($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $dismissed = update_option('better_payment_sale_info_dismissed', true);

        return rest_ensure_response([
            'success' => $dismissed,
            'message' => __('Sale info dismissed', 'better-payment')
        ]);
    }

    /**
     * Dismiss dismissible section
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function dismiss_dismissible_section($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $dismissed = update_option('better_payment_progress_bar_dismissed', true);

        return rest_ensure_response([
            'success' => $dismissed,
            'message' => __('Dismissible section dismissed', 'better-payment')
        ]);
    }

    /**
     * Validate nonce
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return bool
     */
    private function bp_valid_nonce($request) {
        $nonce = $request->get_header('x_wp_nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return false;
        }
        return true;
    }

    /**
     * Get dismissible section data
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_dismissible_section_data($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $settings = DB::get_settings();
        $progress_steps['steps'] = $this->bp_calculate_progress_steps($settings);
        $completed_steps = count( array_filter($progress_steps['steps'], function($step) { return $step['completed']; }) );
        $total_steps = count($progress_steps['steps']);
        $progress_steps['percentage'] = $total_steps > 0 ? ($completed_steps / $total_steps) * 100 : 0;

        return rest_ensure_response([
            'success' => true,
            'data' => $progress_steps
        ]);
    }

    /**
     * Check if dismissible section should be shown
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function show_dismissible_section($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $show_dismissible_section = $this->bp_section_dismissed();

        return rest_ensure_response([
            'success' => true,
            'sectionDismissed' => $show_dismissible_section
        ]);
    }

    /**
     * Check admin permissions
     *
     * @since 1.5.0
     * @return bool
     */
    public function check_admin_permissions()
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized', 'Unauthorized', ['status' => 401]);
        }
        return true;
    }

    /**
     * Get transactions with filters
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_transactions($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $paged = $request->get_param('paged') ?  intval($request->get_param('paged')) : 1;
            $per_page = $request->get_param('per_page') ?  intval($request->get_param('per_page')) : 20;
            $status = $request->get_param('status') ? sanitize_text_field($request->get_param('status')) : 'all';
            $search_text = $request->get_param('search_text') ? sanitize_text_field($request->get_param('search_text')) : '';
            $payment_date_from = $request->get_param('payment_date_from') ? sanitize_text_field($request->get_param('payment_date_from')) : '';
            $payment_date_to = $request->get_param('payment_date_to') ? sanitize_text_field($request->get_param('payment_date_to')) : '';
            $order_by = $request->get_param('order_by') ? sanitize_text_field($request->get_param('order_by')) : 'payment_date';
            $order = $request->get_param('order') ? sanitize_text_field($request->get_param('order')) : 'DESC';
            $source = $request->get_param('source') ? sanitize_text_field($request->get_param('source')) : '';
            $currency = $request->get_param('currency') ? sanitize_text_field($request->get_param('currency')) : 'all';

            $filters = [];
            $filters['per_page'] = $per_page;
            $filters['paged'] = $paged;
            $filters['offset'] = ($paged - 1) * $per_page;
            if ($status && $status !== 'all') {
                $filters['status'] = $status;
            }
            if ($search_text) {
                $filters['search_text'] = $search_text;
            }
            if ($payment_date_from) {
                $filters['payment_date_from'] = $payment_date_from;
            }
            if ($payment_date_to) {
                $filters['payment_date_to'] = $payment_date_to;
            }
            if ($order_by) {
                $filters['order_by'] = $order_by;
            }
            if ($order) {
                $filters['order'] = $order;
            }
            if ($source) {
                $filters['source'] = $source;
            }
            if ($currency) {
                $filters['currency'] = $currency;
            }

            $transactions = DB::get_transactions($filters, 0, 'v2');

            foreach ($transactions as $key => $transaction) {
                $transactions[$key]->form_fields_info = maybe_unserialize($transaction->form_fields_info);
            }
            unset($filters['offset']);
            $total = DB::get_transaction_count($filters, 'v2', null, $status);

            return rest_ensure_response([
                'transactions' => $transactions,
                'total' => $total,
                'page' => $paged,
                'per_page' => $per_page,
                'pages' => ceil($total / $per_page)
            ]);
        } catch (\Exception $e) {
            return new WP_Error('transactions_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get paginated E-COMMERCE subscriptions, optionally narrowed by status
     * and/or a free-text search.
     *
     * Data source is the `{prefix}better_payment_subscription_order` relation
     * table — one row per distinct (subscription_id, source) — NOT the
     * transactions table, so Better Payment's own (Elementor/campaign) Stripe
     * subscription payments never appear here. Each row is hydrated by its
     * integration: 'woo' via WooSubscriptions::admin_list_row() (parent-order
     * meta), anything else via the `better_payment/admin/subscription_list_row`
     * filter. A row that cannot hydrate (integration inactive, order deleted)
     * still lists with its ids rather than silently disappearing.
     *
     * Two paths, because the filterable fields do not exist in SQL:
     *
     * - **Unfiltered** (every default pageview) — the relation table is
     *   grouped, ordered and LIMITed in SQL, and only the current page's rows
     *   are ever hydrated. Unchanged from before filtering existed.
     * - **Filtered** — status, customer, product, source label and start date
     *   all arrive during hydration, so there is nothing to push into a WHERE
     *   clause:
     *   the whole set is fetched, hydrated, filtered by
     *   SubscriptionListFilter and paginated in PHP. The extra hydration is
     *   the price of the feature and is confined to filtered requests.
     *
     * Both paths return the identical envelope, so the client cannot tell
     * them apart.
     *
     * @since 2.4.0
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_subscriptions($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $paged = $request->get_param('paged') ? intval($request->get_param('paged')) : 1;
            $per_page = $request->get_param('per_page') ? intval($request->get_param('per_page')) : 20;

            $status = SubscriptionListFilter::sanitize_status($request->get_param('status'));
            $search = SubscriptionListFilter::sanitize_search($request->get_param('search_text'));
            // Filters the "Started" column, so the params are named for it
            // rather than borrowed from the Transactions tab's payment_date_*.
            $date_from = SubscriptionListFilter::sanitize_date($request->get_param('start_date_from'));
            $date_to = SubscriptionListFilter::sanitize_date($request->get_param('start_date_to'));

            if (SubscriptionListFilter::is_filtered($status, $search, $date_from, $date_to)) {
                $rows = [];

                foreach (SubscriptionRelationModel::get_subscription_groups() as $relation) {
                    $rows[] = $this->hydrate_subscription_row($relation);
                }

                $result = SubscriptionListFilter::paginate(
                    SubscriptionListFilter::apply($rows, $status, $search, $date_from, $date_to),
                    $paged,
                    $per_page
                );

                $subscriptions = $result['subscriptions'];
            } else {
                $result = SubscriptionRelationModel::get_subscriptions_paginated([
                    'paged'    => $paged,
                    'per_page' => $per_page,
                ]);

                $subscriptions = [];

                foreach ($result['subscriptions'] as $relation) {
                    $subscriptions[] = $this->hydrate_subscription_row($relation);
                }
            }

            return rest_ensure_response([
                'subscriptions' => $subscriptions,
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'pages' => $result['pages'],
            ]);
        } catch (\Exception $e) {
            return new WP_Error('subscriptions_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Build one admin Subscriptions list row from a relation-table group row.
     *
     * The single place a display row is assembled, so the filtered and
     * unfiltered paths of get_subscriptions() can never disagree about what a
     * row contains — which is what makes it safe for SubscriptionListFilter
     * to match against the same fields the list renders.
     *
     * @since 2.4.0
     * @param object $relation Grouped relation row (subscription_id, source, renewal_orders).
     * @return array Display row.
     */
    private function hydrate_subscription_row($relation)
    {
        $known_sources = SubscriptionRelationModel::known_sources();
        $source = (string) $relation->source;

        $row = [
            'subscription_id' => (int) $relation->subscription_id,
            'source'          => $source,
            'source_label'    => isset($known_sources[$source]) ? $known_sources[$source] : ucfirst($source),
            'renewal_orders'  => (int) $relation->renewal_orders,
            'customer_name'   => '',
            'customer_email'  => '',
            'product_name'    => '',
            'amount'          => null,
            'currency'        => '',
            'interval'        => 0,
            'period'          => '',
            'status'          => '',
            'renewal_count'   => 0,
            'next_payment'    => '',
            'start_date'      => '',
            'order_edit_url'  => '',
        ];

        if (SubscriptionRelationModel::SOURCE_WOO === $source) {
            $hydrated = WooSubscriptions::admin_list_row((int) $relation->subscription_id);
            if (is_array($hydrated)) {
                $row = array_merge($row, $hydrated);
            }
        }

        /**
         * Lets an e-commerce integration hydrate (or amend) its own
         * subscription rows on the admin Subscriptions tab.
         *
         * Runs on every listed row, including on a filtered request — the
         * admin list's status/search filters match on the row this filter
         * returns, so an integration that hydrates `status` only here is
         * still filterable.
         *
         * @since 2.4.0
         *
         * @param array  $row      Display row (see shape above).
         * @param object $relation Raw relation-table group row (subscription_id, source, renewal_orders).
         */
        return apply_filters('better_payment/admin/subscription_list_row', $row, $relation);
    }

    /**
     * Summary counts for the admin Subscriptions tab's stat cards: every
     * recorded e-commerce subscription, plus how many are currently active
     * or cancelled. Status comes from each subscription's integration —
     * 'woo' via a light order-meta read (WooSubscriptions::admin_status()),
     * anything else via the `better_payment/admin/subscription_status`
     * filter — so a row whose integration is inactive still counts toward
     * `all` but toward neither status bucket, mirroring how the list shows
     * un-hydrated rows rather than dropping them.
     *
     * @since 2.4.0
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_subscription_count($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $pairs = SubscriptionRelationModel::get_distinct_subscriptions();

            $active = 0;
            $cancelled = 0;

            foreach ($pairs as $pair) {
                $source = (string) $pair->source;
                $status = '';

                if (SubscriptionRelationModel::SOURCE_WOO === $source) {
                    $status = WooSubscriptions::admin_status((int) $pair->subscription_id);
                }

                /**
                 * Lets an e-commerce integration report one subscription's
                 * current status for the admin tab's summary counts. Return
                 * the raw `_bp_subscription_status`-style value ('active',
                 * 'cancelled', 'past_due', …) or '' when unknown.
                 *
                 * @since 2.4.0
                 *
                 * @param string $status          Status resolved so far ('' unless woo).
                 * @param int    $subscription_id Subscription id (within its source).
                 * @param string $source          Source slug.
                 */
                $status = strtolower((string) apply_filters('better_payment/admin/subscription_status', $status, (int) $pair->subscription_id, $source));

                if ('active' === $status) {
                    $active++;
                } elseif ('cancelled' === $status) {
                    $cancelled++;
                }
            }

            return rest_ensure_response([
                'success' => true,
                'count' => [
                    'all' => count($pairs),
                    'active' => $active,
                    'cancelled' => $cancelled,
                ],
            ]);
        } catch (\Exception $e) {
            return new WP_Error('subscription_count_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Resolve and validate the (id, source) pair every single-subscription
     * route needs. Returns [id, source, relation order rows] or a WP_Error —
     * a pair with no relation rows is a subscription this plugin has never
     * recorded, i.e. 404.
     *
     * @since 2.4.0
     * @param WP_REST_Request $request
     * @return array|WP_Error [int $id, string $source, object[] $orders]
     */
    private function resolve_subscription($request)
    {
        $id = intval($request->get_param('id'));
        $source = SubscriptionRelationModel::sanitize_source($request->get_param('source'));

        if (!$id || '' === $source) {
            return new WP_Error('invalid_subscription', __('A subscription id and source are required.', 'better-payment'), ['status' => 400]);
        }

        $orders = SubscriptionRelationModel::get_orders($id, $source);

        if (empty($orders)) {
            return new WP_Error('subscription_not_found', __('Subscription not found.', 'better-payment'), ['status' => 404]);
        }

        return [$id, $source, $orders];
    }

    /**
     * Get one e-commerce subscription for the admin details view: the list
     * row's fields plus details-only fields (auto renew, available status
     * actions) and the related orders recorded in the
     * relation table. Hydration mirrors get_subscriptions(): 'woo' via the
     * WooCommerce module, anything else via the
     * `better_payment/admin/subscription_detail` filter. An un-hydratable
     * subscription still returns its base row + order ids (the UI renders
     * dashes and offers no actions).
     *
     * @since 2.4.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_subscription($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $resolved = $this->resolve_subscription($request);
        if (is_wp_error($resolved)) {
            return $resolved;
        }
        list($id, $source, $relations) = $resolved;

        $known_sources = SubscriptionRelationModel::known_sources();
        $types = SubscriptionRelationModel::types();

        $detail = [
            'subscription_id'   => $id,
            'source'            => $source,
            'source_label'      => isset($known_sources[$source]) ? $known_sources[$source] : ucfirst($source),
            'customer_name'     => '',
            'customer_email'    => '',
            'product_name'      => '',
            'amount'            => null,
            'currency'          => '',
            'interval'          => 0,
            'period'            => '',
            'status'            => '',
            'renewal_count'     => 0,
            'auto_renew'        => '',
            'next_payment'      => '',
            // '' until the subscription has a settled renewal — the UI only
            // renders the Last Payment row when this is non-empty.
            'last_payment'      => '',
            // Who cancelled: 'customer' | 'admin', with the actor's display
            // name. '' unless the subscription is cancelled and its
            // cancellation recorded an actor — the UI drops the row when the
            // type is empty.
            'cancelled_by_type' => '',
            'cancelled_by_name' => '',
            'start_date'        => '',
            'order_edit_url'    => '',
            'available_actions' => [],
            // Billing & Shipping card: plain-text address lines (never
            // formatted-address HTML — the React admin renders text only).
            'billing_address'   => [],
            'shipping_address'  => [],
            'billing_phone'     => '',
            'orders'            => [],
        ];

        if (SubscriptionRelationModel::SOURCE_WOO === $source) {
            $hydrated = WooSubscriptions::admin_detail($id);
            if (is_array($hydrated)) {
                $detail = array_merge($detail, $hydrated);
            }
        }

        // Related orders, newest first (the relation table stores them in
        // recording order, oldest first).
        foreach (array_reverse($relations) as $relation) {
            $type = (string) $relation->type;

            $order_row = [
                'order_id'     => (int) $relation->order_id,
                'type'         => $type,
                'type_label'   => isset($types[$type]) ? $types[$type] : ucfirst($type),
                'order_number' => '',
                'date'         => '',
                'status'       => '',
                'status_label' => '',
                'total'        => null,
                'currency'     => '',
                'edit_url'     => '',
            ];

            if (SubscriptionRelationModel::SOURCE_WOO === $source) {
                $hydrated_order = WooSubscriptions::admin_order_row((int) $relation->order_id);
                if (is_array($hydrated_order)) {
                    $order_row = array_merge($order_row, $hydrated_order);
                }
            }

            $detail['orders'][] = $order_row;
        }

        /**
         * Lets an e-commerce integration hydrate (or amend) its own
         * subscription's admin details view — including the related-order
         * rows and the `available_actions` its status endpoint supports.
         *
         * @since 2.4.0
         *
         * @param array  $detail          Detail payload (see shape above).
         * @param int    $subscription_id Subscription id within its source.
         * @param string $source          Source slug ('woo', 'fluentcart', …).
         */
        $detail = apply_filters('better_payment/admin/subscription_detail', $detail, $id, $source);

        return rest_ensure_response($detail);
    }

    /**
     * Perform a status action (cancel | reactivate) on a subscription. The
     * WooCommerce module handles its own source; any other integration may
     * handle its subscriptions via the
     * `better_payment/admin/subscription_status_action` filter — with no
     * handler the action is refused rather than silently "succeeding".
     *
     * @since 2.4.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_subscription_status($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $resolved = $this->resolve_subscription($request);
        if (is_wp_error($resolved)) {
            return $resolved;
        }
        list($id, $source) = $resolved;

        $action = sanitize_key((string) $request->get_param('subscription_action'));

        if ('' === $action) {
            return new WP_Error('invalid_action', __('A subscription action is required.', 'better-payment'), ['status' => 400]);
        }

        if (SubscriptionRelationModel::SOURCE_WOO === $source) {
            $result = WooSubscriptions::admin_status_action($id, $action);
        } else {
            /**
             * Lets an e-commerce integration handle admin status actions on
             * its own subscriptions. Return true on success, a WP_Error to
             * refuse with a message, or leave the null default to signal the
             * action is unsupported for this source (a handler must check
             * $source and leave other integrations' subscriptions alone).
             *
             * @since 2.4.0
             *
             * @param null|true|WP_Error $result          Handling result.
             * @param int                $subscription_id Subscription id within its source.
             * @param string             $source          Source slug ('fluentcart', …).
             * @param string             $action          Requested action slug.
             */
            $result = apply_filters('better_payment/admin/subscription_status_action', null, $id, $source, $action);

            if (null === $result) {
                return new WP_Error('action_not_supported', __('This subscription cannot be managed from here — its integration does not support status changes.', 'better-payment'), ['status' => 400]);
            }
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'cancel' === $action
                ? __('Subscription cancelled — no further renewals will be charged.', 'better-payment')
                : __('Subscription updated successfully.', 'better-payment'),
        ]);
    }

    /**
     * Delete a subscription from the admin Subscriptions tab. A still-live
     * ('woo': active/past_due) subscription is CANCELLED first — deleting
     * only the relation rows would leave an invisible subscription renewing
     * via cron — then its relation rows are removed so it no longer lists.
     * Other integrations get the `better_payment/admin/subscription_delete`
     * action to stop their side before the rows go.
     *
     * @since 2.4.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_subscription($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $resolved = $this->resolve_subscription($request);
        if (is_wp_error($resolved)) {
            return $resolved;
        }
        list($id, $source) = $resolved;

        if (SubscriptionRelationModel::SOURCE_WOO === $source && function_exists('wc_get_order')) {
            $order = wc_get_order($id);
            if ($order instanceof \WC_Order) {
                // No-op unless the subscription is active/past_due (cancel()
                // guards itself), so deleting a cancelled/completed
                // subscription adds no order note.
                WooSubscriptions::cancel($order, __('Better Payment: subscription cancelled — it was deleted from the Better Payment admin. No further automatic renewals will be charged.', 'better-payment'), 'admin');
            }
        }

        /**
         * Fires before a subscription's relation rows are deleted from the
         * admin Subscriptions tab. An integration should stop the
         * subscription on its own side here — after this, Better Payment no
         * longer tracks it.
         *
         * @since 2.4.0
         *
         * @param int    $subscription_id Subscription id within its source.
         * @param string $source          Source slug ('woo', 'fluentcart', …).
         */
        do_action('better_payment/admin/subscription_delete', $id, $source);

        $deleted = SubscriptionRelationModel::delete_for_subscription($id, $source);

        if (!$deleted) {
            return new WP_Error('delete_failed', __('Failed to delete subscription.', 'better-payment'), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Subscription deleted successfully.', 'better-payment'),
        ]);
    }

    /**
     * Get single transaction
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_transaction($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $id = $request->get_param('id');
            $transaction = DB::get_transaction($id);
            $transaction->form_fields_info = maybe_unserialize($transaction->form_fields_info);
            $woo_products = maybe_unserialize($transaction->form_fields_info['detailed_product_info'] ?? []);
            $transaction->woo_products = $woo_products['woo_products'] ?? [];
            $transaction->woo_products = array_values($transaction->woo_products);


            if (!$transaction) {
                return new WP_Error('transaction_not_found', 'Transaction not found', ['status' => 404]);
            }

            return rest_ensure_response($transaction);
        } catch (\Exception $e) {
            return new WP_Error('transaction_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Mark transaction as complete
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function mark_transaction_complete($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $id = intval($request->get_param('id'));

            if (!$id) {
                return new WP_Error('invalid_id', 'Invalid transaction ID', ['status' => 400]);
            }

            // Check if transaction exists
            $transaction = DB::get_transaction($id);
            if (!$transaction) {
                return new WP_Error('transaction_not_found', 'Transaction not found', ['status' => 404]);
            }

            // Check if transaction is already completed
            $completed_statuses = ['Completed', 'completed', 'Paid', 'paid', 'Success', 'success', 'Refunded', 'refunded', 'Failed', 'failed'];
            if (in_array($transaction->status, $completed_statuses)) {
                return new WP_Error('already_completed', 'Transaction is already in a completed state', ['status' => 400]);
            }

            // Mark transaction as completed
            if (DB::mark_as_completed($id)) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => __('Transaction marked as completed!', 'better-payment')
                ]);
            } else {
                return new WP_Error('mark_complete_failed', 'Failed to mark transaction as completed', ['status' => 500]);
            }
        } catch (\Exception $e) {
            return new WP_Error('mark_complete_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get transaction referer information
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_transaction_referer($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $id = intval($request->get_param('id'));

            if (!$id) {
                return new WP_Error('invalid_id', 'Invalid transaction ID', ['status' => 400]);
            }

            // Get transaction
            $transaction = DB::get_transaction($id);
            if (!$transaction) {
                return new WP_Error('transaction_not_found', 'Transaction not found', ['status' => 404]);
            }

            // Extract form fields info
            $form_fields_info = maybe_unserialize($transaction->form_fields_info);
            $referer_page_id = !empty($form_fields_info['referer_page_id']) ? $form_fields_info['referer_page_id'] : '';
            $referer_widget_id = !empty($form_fields_info['referer_widget_id']) ? $form_fields_info['referer_widget_id'] : '';

            // Initialize response data
            $referer_data = [
                'referer_url' => $transaction->referer,
                'page_title' => __('N/A', 'better-payment'),
                'page_link' => '#',
                'widget_name' => __('N/A', 'better-payment'),
                'widget_settings' => [],
                'site_logo' => '',
                'site_name' => ''
            ];

            // Get widget settings if available
            if (!empty($referer_page_id) && !empty($referer_widget_id)) {
                $widget_settings = $this->get_elementor_widget_settings($referer_page_id, $referer_widget_id);

                if (!empty($widget_settings)) {
                    // Get widget name from settings
                    $widget_name = !empty($widget_settings['form_name']) ? $widget_settings['form_name'] : __('N/A', 'better-payment');
                    $widget_name = ($transaction->referer !== 'elementor-form' && !empty($widget_settings['better_payment_form_title']))
                        ? $widget_settings['better_payment_form_title']
                        : $widget_name;

                    $referer_data['widget_name'] = $widget_name;
                    $referer_data['widget_settings'] = $widget_settings;
                }
            }

            // Get page information if available
            if (!empty($referer_page_id)) {
                $referer_data['page_title'] = get_the_title($referer_page_id);
                $referer_data['page_link'] = get_permalink($referer_page_id);
                $referer_data['site_logo'] = wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full')[0];
                $referer_data['site_name'] = get_bloginfo('name');;
            }

            return rest_ensure_response([
                'success' => true,
                'data' => $referer_data
            ]);
        } catch (\Exception $e) {
            return new WP_Error('referer_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Delete transaction
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_transaction($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $id = intval($request->get_param('id'));

            if (!$id) {
                return new WP_Error('invalid_id', 'Invalid transaction ID', ['status' => 400]);
            }

            // Check if transaction exists
            $transaction = DB::get_transaction($id);
            if (!$transaction) {
                return new WP_Error('transaction_not_found', 'Transaction not found', ['status' => 404]);
            }

            // Delete the transaction
            if (DB::delete_transaction($id)) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => __('Transaction deleted successfully', 'better-payment')
                ]);
            } else {
                return new WP_Error('delete_failed', 'Failed to delete transaction', ['status' => 500]);
            }
        } catch (\Exception $e) {
            return new WP_Error('delete_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get settings
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_settings($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $settings = DB::get_settings();
            return rest_ensure_response($settings);
        } catch (\Exception $e) {
            return new WP_Error('settings_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Update settings
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_settings($request)
    {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        try {
            $settings = $request->get_json_params();
            $settings = $this->sanitize_settings($settings);
            $result = DB::update_settings($settings);

            return rest_ensure_response([
                'success' => true,
                'message' => __('Settings updated successfully', 'better-payment'),
                'settings' => $result
            ]);
        } catch (\Exception $e) {
            return new WP_Error('settings_update_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Search FluentCart products
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function search_fluentcart_products($request) {
        $search = $request->get_param('search');

        if (!function_exists('fluentCart') || !class_exists('\FluentCart\App\Models\Product')) {
            return rest_ensure_response([]);
        }

        try {
            $product_model = new \FluentCart\App\Models\Product();

            $query = $product_model->newQuery()
                ->where('post_status', 'publish')
                ->limit(10);

            if (!empty($search)) {
                $query->where('post_title', 'LIKE', '%' . sanitize_text_field($search) . '%');
            }

            $products = $query->get();
            $product_list = [];

            if (!empty($products)) {
                foreach ($products as $product) {
                    $product_list[] = [
                        'value' => strval($product->ID),
                        'label' => $product->post_title
                    ];
                }
            }

            return rest_ensure_response($product_list);
        } catch (\Exception $e) {
            return rest_ensure_response([]);
        }
    }

    /**
     * Get single FluentCart product
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_fluentcart_product($request) {
        $product_id = absint($request->get_param('id'));

        if (!function_exists('fluentCart') || !class_exists('\FluentCart\App\Models\Product')) {
            return new WP_Error('fluentcart_not_available', 'FluentCart is not available', ['status' => 404]);
        }

        try {
            $product = \FluentCart\App\Models\Product::with('detail', 'variants')->find($product_id);

            if (!$product) {
                return new WP_Error('product_not_found', 'Product not found', ['status' => 404]);
            }

            $price = '';
            if ($product->detail && $product->detail->min_price) {
                $price = strval($product->detail->min_price);
            } elseif ($product->variants && count($product->variants) > 0) {
                $price = strval($product->variants[0]->item_price);
            }

            return rest_ensure_response([
                'name' => $product->post_title,
                'price' => $price,
                'permalink' => get_permalink($product->ID) ?: ''
            ]);
        } catch (\Exception $e) {
            return new WP_Error('product_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get Stripe price details
     *
     * Fetches product name and price from Stripe API using the price ID.
     *
     * @since 1.5.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_stripe_price($request) {
        if (!$this->bp_valid_nonce($request)) {
            return new WP_Error('invalid_nonce', 'Invalid Request', ['status' => 403]);
        }

        $price_id = sanitize_text_field($request->get_param('price_id'));

        // Validate price ID format (should start with 'price_')
        if (empty($price_id) || strpos($price_id, 'price_') !== 0) {
            return new WP_Error('invalid_price_id', 'Invalid Stripe Price ID format', ['status' => 400]);
        }

        // Get Stripe settings
        $global_settings = get_option('better_payment_settings');
        $is_live_mode = !empty($global_settings['better_payment_settings_payment_stripe_live_mode'])
            && 'yes' === $global_settings['better_payment_settings_payment_stripe_live_mode'];

        $secret_key = $is_live_mode
            ? (!empty($global_settings['better_payment_settings_payment_stripe_live_secret'])
                ? $global_settings['better_payment_settings_payment_stripe_live_secret'] : '')
            : (!empty($global_settings['better_payment_settings_payment_stripe_test_secret'])
                ? $global_settings['better_payment_settings_payment_stripe_test_secret'] : '');

        if (empty($secret_key)) {
            return new WP_Error('missing_api_key', 'Stripe API key not configured', ['status' => 400]);
        }

        // Fetch price details from Stripe using Helper trait method
        $price_details = $this->get_stripe_price_details($price_id, $secret_key);

        if (empty($price_details) || isset($price_details['error'])) {
            $error_message = isset($price_details['error']['message'])
                ? $price_details['error']['message']
                : 'Failed to fetch Stripe price details';
            return new WP_Error('stripe_api_error', $error_message, ['status' => 500]);
        }

        // Extract product name - need to fetch product details separately
        $product_name = '';
        if (!empty($price_details['product'])) {
            $product_id = $price_details['product'];
            $product_details = $this->get_stripe_product_details($product_id, $secret_key);
            $product_name = !empty($product_details['name']) ? $product_details['name'] : '';
        }

        // Extract price amount and currency
        $amount = isset($price_details['unit_amount']) ? floatval($price_details['unit_amount']) / 100 : 0;
        $currency = isset($price_details['currency']) ? strtoupper($price_details['currency']) : 'USD';

        return rest_ensure_response([
            'success' => true,
            'product_name' => $product_name,
            'amount' => $amount,
            'currency' => $currency,
            'formatted_price' => $currency . ' ' . number_format($amount, 2)
        ]);
    }

    /**
     * Get Stripe product details
     *
     * @since 1.5.0
     * @param string $product_id Stripe product ID
     * @param string $secret_key Stripe secret key
     * @return array Product details or empty array
     */
    private function get_stripe_product_details($product_id, $secret_key) {
        if (empty($product_id) || empty($secret_key)) {
            return [];
        }

        $api_url = 'https://api.stripe.com/v1/products/' . $product_id;

        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data) || !is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Sanitize settings
     *
     * @since 1.5.0
     * @param array $settings
     * @return array
     */
    private function sanitize_settings($settings)
    {
        // Keys whose value may legitimately contain newlines — sanitize_text_field
        // would collapse them. The AI system prompt is multi-line free text.
        $multiline_keys = apply_filters('better_payment_settings_multiline_keys', array(
            'better_payment_settings_ai_system_prompt',
        ));

        foreach ($settings as $key => $value) {
            if (in_array($key, $multiline_keys, true)) {
                $settings[$key] = sanitize_textarea_field($value);
                continue;
            }
            $settings[$key] = sanitize_text_field($value);
        }
        return $settings;
    }
}
