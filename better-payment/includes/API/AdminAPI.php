<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Traits\Helper;
use WP_Error;
use WP_REST_Controller;
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
     * Sanitize settings
     * 
     * @since 1.5.0
     * @param array $settings
     * @return array
     */
    private function sanitize_settings($settings)
    {
        foreach ($settings as $key => $value) {
            $settings[$key] = sanitize_text_field($value);
        }
        return $settings;
    }
}
