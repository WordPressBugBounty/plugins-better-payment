<?php

namespace Better_Payment\Lite\Admin;

use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Classes\ProSubscriptionTransactionData;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * This class responsible for database work
 * using wordpress functionality 
 * get_option and update_option.
 * 
 * @since 0.0.1
 */
class DB {

    /**
     * Helper's counting rule (an unconfirmed pre-2.4.0 `no_payment_required` row is not counted; a
     * confirmed one adds 0), aliased so the two deprecated queries below spell it `self::…` exactly
     * as Pro's AnalyticsModel copies do (AnalyticsModelParityTest compares the bodies).
     */
    private const COUNTED_ROW_SQL      = Helper::COUNTED_ROW_SQL;
    private const COUNTABLE_AMOUNT_SQL = Helper::COUNTABLE_AMOUNT_SQL;

    public static function get_table_name() {
        global $wpdb;

        $table   = "{$wpdb->prefix}better_payment";
        return $table;
    }
    /**
     * Get all default settings value.
     *
     * @param string $name
     * @return array
     * @since 0.0.1
     */
    public static function default_settings() {
        return apply_filters('better_payment_option_default_settings', array(
            'better_payment_settings_general_general_paypal' => 'yes',
            'better_payment_settings_general_general_stripe' => 'yes',
            'better_payment_settings_general_general_paystack' => '',
            'better_payment_settings_general_general_user_dashboard' => 'yes',
            'better_payment_settings_general_general_fundraising_campaign' => 'yes',
            'better_payment_settings_general_general_email' => 'yes',
            'better_payment_settings_general_general_currency' => 'USD',

            'better_payment_settings_general_email_to' => '',
            'better_payment_settings_general_email_subject' => '',
            'better_payment_settings_general_email_message_admin' => '',
            'better_payment_settings_general_email_from_email' => '',
            'better_payment_settings_general_email_from_name' => '',
            'better_payment_settings_general_email_reply_to' => '',
            'better_payment_settings_general_email_cc' => '',
            'better_payment_settings_general_email_bcc' => '',
            'better_payment_settings_general_email_send_as' => 'html',

            'better_payment_settings_general_email_subject_customer' => '',
            'better_payment_settings_general_email_message_customer' => '',
            'better_payment_settings_general_email_from_email_customer' => '',
            'better_payment_settings_general_email_from_name_customer' => '',
            'better_payment_settings_general_email_reply_to_customer' => '',
            'better_payment_settings_general_email_cc_customer' => '',
            'better_payment_settings_general_email_bcc_customer' => '',
            'better_payment_settings_general_email_send_as_customer' => 'html',

            'better_payment_settings_payment_paypal_live_mode' => 'no',
            'better_payment_settings_payment_paypal_email' => '',            
            'better_payment_settings_opt_in' => '',
            'better_payment_settings_payment_paypal_live_client_id' => '',
            'better_payment_settings_payment_paypal_test_client_id' => '',
            'better_payment_settings_payment_paypal_live_secret' => '',
            'better_payment_settings_payment_paypal_test_secret' => '',
            'better_payment_settings_payment_stripe_live_mode' => 'no',
            'better_payment_settings_payment_stripe_live_public' => '',
            'better_payment_settings_payment_stripe_live_secret' => '',
            'better_payment_settings_payment_stripe_test_public' => '',
            'better_payment_settings_payment_stripe_test_secret' => '',
            'better_payment_settings_payment_paystack_live_mode' => 'no',
            'better_payment_settings_payment_paystack_live_public' => '',
            'better_payment_settings_payment_paystack_live_secret' => '',
            'better_payment_settings_payment_paystack_test_public' => '',
            'better_payment_settings_payment_paystack_test_secret' => '',

            // AI Assistant (Campaign Builder). See includes/AI/.
            'better_payment_settings_ai_enabled'          => '',
            'better_payment_settings_ai_provider'         => 'openai',
            'better_payment_settings_ai_model'            => '',
            'better_payment_settings_ai_image_model'      => '',
            'better_payment_settings_ai_temperature'      => '0.7',
            'better_payment_settings_ai_max_tokens'       => '4096',
            'better_payment_settings_ai_system_prompt'    => '',
            'better_payment_settings_ai_api_key_openai'     => '',
            'better_payment_settings_ai_api_key_claude'     => '',
            'better_payment_settings_ai_api_key_gemini'     => '',
            'better_payment_settings_ai_api_key_openrouter' => '',

            // E-commerce > Subscription. Booleans follow the 'yes'/'no' string
            // convention used across this settings array.
            'better_payment_settings_ecommerce_subscription_renewal_process'     => 'auto',
            'better_payment_settings_ecommerce_subscription_stripe_auto_renew'   => 'yes',
            'better_payment_settings_ecommerce_subscription_auto_renewal_toggle' => 'yes',
            'better_payment_settings_ecommerce_subscription_myaccount_tab'       => 'yes',
            'better_payment_settings_ecommerce_subscription_active_role'         => 'subscriber',
            'better_payment_settings_ecommerce_subscription_inactive_role'       => 'customer',
        ));
    }
    /**
     * Get all settings value from options table.
     *
     * Returns the full settings array when $name is empty, otherwise the single
     * value for that key (which may be a string, array, etc.).
     *
     * @param string $name
     * @return mixed
     * @since 0.0.1
     */
    public static function get_settings($name = '')
    {
        $settings = get_option('better_payment_settings', true);
        $default = self::default_settings();

        $settings = wp_parse_args($settings, $default);

        if (!empty($name) && isset($settings[$name])) {
            return $settings[$name];
        }

        if (!empty($name) && !isset($settings[$name]) && isset($default[$name])) {
            return $default[$name];
        }

        if (!empty($name) && !isset($settings[$name])  && !isset($default[$name])) {
            return '';
        }

        return is_array($settings) ? $settings : [];
    }
    /**
     * Update settings 
     * @param array $value
     * @return boolean
     * @since 0.0.1
     */
    public static function update_settings($value, $key = '')
    {
        if (!empty($key)) {
            return update_option($key, $value);
        }
        return update_option('better_payment_settings', $value);
    }

    /**
     * Get all transactions from better payment table.
     *
     * @param array|string $args Filters, as wp_parse_args() reads them (every caller passes an array).
     * @return array
     * @since 0.0.1
     */
    public static function get_transactions($args = [], $count_only=0, $version = 'v1', $fetchNull = 0, $transaction_type = '')
    {
        global $wpdb;
        $items = '';

        $defaults = array(
            'search_text' => '',
            'payment_date_from' => '',
            'payment_date_to' => '',
            'order_by' => 'payment_date',
            'order' => 'DESC',
            'currency' => 'all',
            'source' => '', 
            'paged' => 1,
            'per_page' => 20, 
            'offset' => 0,
            'status' => 'all', 
        );

        $allowed_order_by = array(
            'id',
            'payment_date',
            'email',
            'amount'
        );

        $allowed_order = array(
            'DESC',
            'ASC'
        );

        $better_payment_db_obj = new DB();
        $better_payment_helper_obj = new Helper();

        $allowed_statuses = $better_payment_db_obj->allowed_statuses('all', 'v2');
        $allowed_statuses_types = $better_payment_db_obj->allowed_statuses_types('v2');

        $args = wp_parse_args($args, $defaults);
        $table   = self::get_table_name();

        $search_text  = sanitize_text_field($args['search_text']);
        $search_text = empty($search_text) ? '' : "%$search_text%";

        $order_by = in_array($args['order_by'], $allowed_order_by) ? sanitize_text_field($args['order_by']) : $defaults['order_by'];
        $order = in_array($args['order'], $allowed_order) ? sanitize_text_field($args['order']) : $defaults['order'];
        $offset = $args['offset'] > 0 ? intval($args['offset']) : $defaults['offset'];
        $per_page = $args['per_page'] > 0 ? intval($args['per_page']) : $defaults['per_page'];
        
        if($args['status'] == 'null') {
            $status = sanitize_text_field($args['status']);
        }else {
            $allowed_statuses_by_version = ($version == 'v1') ? $allowed_statuses : $allowed_statuses_types;
            $status = in_array($args['status'], $allowed_statuses_by_version) ? sanitize_text_field($args['status']) : $defaults['status'];
        }
        
        $statuses_by_transaction_type = array();

        if( !empty($transaction_type) ){
            $statuses_by_types = $better_payment_helper_obj->transaction_statuses_with_type_v2();
            $transaction_type  = is_string( $transaction_type ) ? $transaction_type : 'all';

            if ( 'null' === $transaction_type ) {
                // The count twin of `status=null` below.
                $status           = 'null';
                $transaction_type = '';
            } else {
                // An unknown type is every row, as an unknown `status` is. It used to fall back to the
                // string 'all' and fatal in array_map() below.
                $transaction_type             = isset( $statuses_by_types[ $transaction_type ] ) ? $transaction_type : 'all';
                $statuses_by_transaction_type = $statuses_by_types[ $transaction_type ]['statuses'];
                $status                       = '';
            }
        }
        
        $whereQuery = ''; 

        $valid_search_text = $search_text != '' && strlen($search_text) >= 2;
        if($valid_search_text){
            $whereQuery = $wpdb->prepare(' AND ( email LIKE %s OR form_fields_info LIKE %s OR transaction_id LIKE %s OR amount LIKE %s OR source LIKE %s ) ', $search_text, $search_text ,$search_text, $search_text, $search_text );
        }

        if( $args['source'] != '' ){
            $whereQuery .= $wpdb->prepare(" AND source = %s", $args['source']); 
        }

        if( $args['currency'] != 'all' ){
            $whereQuery .= $wpdb->prepare(" AND currency = %s", $args['currency']); 
        }

        if( $status != 'all' ){
            if($status == 'null'){
                $whereQuery .= esc_sql(" AND status is NULL");
            }else {
                //version 2
                if('v1' === $version){
                    $whereQuery .= $wpdb->prepare( " AND status = %s OR status = %s", $status, strtolower($status) ); 
                } else {
                    
                    if($transaction_type){
                        $statuses_by_type = $statuses_by_transaction_type;
                    }else {
                        $statuses_by_type = $better_payment_db_obj->allowed_statuses($status, 'v2');
                    }

                    $statuses = array_map(function($status) {
                        return "'" . esc_sql($status) . "'";
                    }, $statuses_by_type);

                    $statuses = implode(',', $statuses);

                    $bucket = $transaction_type ? $transaction_type : $status;

                    // NULL is the incomplete bucket's own status (PayPal's pending sentinel): `IN ()`
                    // cannot match it, so the Incomplete tab always ORs it in and lists the rows its
                    // total and stat card count.
                    $nullDataQuery = ( $fetchNull || 'incomplete' === $bucket ) ? " OR status IS NULL " : '';

                    // The counting rule, for the tabs: an unconfirmed pre-2.4.0 `no_payment_required`
                    // row is in no count, so it is listed under Incomplete, never Completed, as
                    // Helper::transaction_status_type() labels it. A confirmed one stays Completed;
                    // `refunded` is unchanged. Helper constants only (no request data, no `%`).
                    $unconfirmed_sql = 'incomplete' === $bucket ? ' OR ' . Helper::UNCONFIRMED_LEGACY_SQL . ' ' : '';
                    $counted_sql     = 'completed' === $bucket ? ' AND ' . Helper::COUNTED_ROW_SQL . ' ' : '';

                    // `all` is every row, as the All tab's list is: its `IN ()` list would leave NULL and
                    // unlisted statuses out of the total.
                    if ( 'all' !== $bucket ) {
                        $whereQuery .= " AND ( status IN ( $statuses ) " . esc_sql($nullDataQuery) . $unconfirmed_sql . " ) " . $counted_sql;
                    }
                }
            }
        }
        
        if( $args['payment_date_from'] != '' && $args['payment_date_to'] != ''){
            $whereQuery .= $wpdb->prepare(" AND payment_date BETWEEN %s AND %s", $args['payment_date_from'], $args['payment_date_to']); 
        }

        if($count_only === 1){
            if($valid_search_text){
                $items = $wpdb->get_var(
                        "SELECT count(id) FROM $table WHERE 1
                        $whereQuery
                        ORDER BY $order_by $order
                        "
                );
            }else {
                $items = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT count(id) FROM $table WHERE 1
                        $whereQuery
                        ORDER BY $order_by $order
                        LIMIT %d, %d
                        ",
                        $offset,
                        $per_page
                    )
                );
            }
            
        }else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE 1
                    $whereQuery
                    ORDER BY $order_by $order
                    LIMIT %d, %d
                    ",
                    $offset,
                    $per_page
                )
            );
            
        }

        return $items;
    }

    /**
     * Get the count of total transactions
     *
     * @return int
     * @since 0.0.1
     */
    public static function get_transaction_count($args = '', $version = 'v1', $fetchNull = 0, $transaction_type = '')
    {
        global $wpdb;
        $table = self::get_table_name();

        $count = 0;
        
        if( !empty($transaction_type) ){
            $count = self::get_transactions($args, 1, $version, $fetchNull, $transaction_type);
            return $count;
        }

        if($args !== ''){
            $count = self::get_transactions($args, 1, $version, $fetchNull);
        }else {
            $count = $wpdb->get_var(
                "SELECT count(id) FROM $table"
            );
        }

        return $count;
    }

    /**
     * Get transactions analytics
     *
     * @return array<string, mixed>
     * @since 0.0.1
     */
    public static function get_transactions_analytics_dashboard($filtered_transactions = array())
    {
        global $wpdb;
        $transaction_analytics = [];

        $table = self::get_table_name();
        $better_payment_db_obj = new DB();

        $total_transactions_count = 0;
        $completed_transactions_count = 0;
        $incomplete_transactions_count = 0;
        $refunded_transactions_count = 0;

        $total_transactions_amount = 0;
        $completed_transactions_amount = 0;
        $incomplete_transactions_amount = 0;
        $refunded_transactions_amount = 0;

        if ( is_array( $filtered_transactions ) && count( $filtered_transactions ) ) {
            $total_transactions_count = $better_payment_db_obj->get_transactions_amount_by_statuses(array(), '', '', 'count', 1, $filtered_transactions );
            $total_transactions_amount = $better_payment_db_obj->get_transactions_amount_by_statuses(array(), '', '', 'amount', 1, $filtered_transactions );
    
            $completed_statuses = $better_payment_db_obj->allowed_statuses('completed', 'v2');
            $completed_transactions_count = $better_payment_db_obj->get_transactions_amount_by_statuses( $completed_statuses, '', '', 'count', 0, $filtered_transactions );
            $completed_transactions_amount = $better_payment_db_obj->get_transactions_amount_by_statuses( $completed_statuses, '', '', 'amount', 0, $filtered_transactions );
    
            $incomplete_statuses = $better_payment_db_obj->allowed_statuses('incomplete', 'v2');
            $incomplete_transactions_count = $better_payment_db_obj->get_transactions_amount_by_statuses( $incomplete_statuses, '', '', 'count', 1, $filtered_transactions );
            $incomplete_transactions_amount = $better_payment_db_obj->get_transactions_amount_by_statuses( $incomplete_statuses, '', '', 'amount', 1, $filtered_transactions );
            
            $refunded_statuses = $better_payment_db_obj->allowed_statuses('refunded', 'v2');
            $refunded_transactions_count = $better_payment_db_obj->get_transactions_amount_by_statuses( $refunded_statuses, '', '', 'count', 0, $filtered_transactions );
            $refunded_transactions_amount = $better_payment_db_obj->get_transactions_amount_by_statuses( $refunded_statuses, '', '', 'amount', 0, $filtered_transactions );
        }

        $transaction_analytics['total_transactions_count']        = $total_transactions_count;
        $transaction_analytics['completed_transactions_count']    = $completed_transactions_count;
        $transaction_analytics['incomplete_transactions_count']   = $incomplete_transactions_count;
        $transaction_analytics['refunded_transactions_count']     = $refunded_transactions_count;
        
        $transaction_analytics['total_transactions_amount']        = $total_transactions_amount;
        $transaction_analytics['completed_transactions_amount']    = $completed_transactions_amount;
        $transaction_analytics['incomplete_transactions_amount']   = $incomplete_transactions_amount;
        $transaction_analytics['refunded_transactions_amount']     = $refunded_transactions_amount;

        // Recent transactions
        
        return $transaction_analytics;
    }

    /**
     * Get transaction details from better payment table.
     *
     * @param string $args
     * @return object 
     * @since 0.0.1
     */
    public static function get_transaction($id)
    {
        global $wpdb;

        $table = self::get_table_name();
        $transaction_id = (int) sanitize_text_field($id);
        
        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $transaction_id
            )
        );

        return $item;
    }
    
    /**
     * Get analytics counts and amounts for a specific user via a single SQL aggregate query.
     * Returns the same shape as get_transactions_analytics_dashboard() without loading all rows.
     *
     * An unconfirmed pre-2.4.0 `no_payment_required` row is left out of every figure; a confirmed
     * one counts as completed and adds 0 to the amounts (Helper::COUNTED_ROW_SQL /
     * Helper::COUNTABLE_AMOUNT_SQL).
     *
     * @param string $email
     * @return array
     */
    public static function get_user_analytics_by_email( $email ) {
        global $wpdb;
        $table    = self::get_table_name();
        $email    = sanitize_text_field( $email );
        $ffi_like = '%' . $wpdb->esc_like( '"' . $email . '"' ) . '%';

        // Helper constants only: no request data, and no `%` for prepare() to misread.
        $countable_amount = 'CAST(' . Helper::COUNTABLE_AMOUNT_SQL . ' AS DECIMAL(10,2))';
        $completed        = "status IN ('paid','Completed','completed','success','" . Helper::LEGACY_FREE_CHECKOUT_STATUS . "')";
        $counted_row      = Helper::COUNTED_ROW_SQL;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) AS total_count,
                    SUM({$countable_amount}) AS total_amount,
                    SUM(CASE WHEN {$completed}
                        THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN {$completed}
                        THEN {$countable_amount} ELSE 0 END) AS completed_amount,
                    SUM(CASE WHEN status IN ('pending','Pending','unpaid','incomplete','Incomplete','failed') OR status IS NULL
                        THEN 1 ELSE 0 END) AS incomplete_count,
                    SUM(CASE WHEN status IN ('pending','Pending','unpaid','incomplete','Incomplete','failed') OR status IS NULL
                        THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) AS incomplete_amount,
                    SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) AS refunded_count,
                    SUM(CASE WHEN status = 'refunded'
                        THEN CAST(amount AS DECIMAL(10,2)) ELSE 0 END) AS refunded_amount
                FROM $table WHERE ( email = %s OR form_fields_info LIKE %s ) AND {$counted_row}",
                $email,
                $ffi_like
            )
        );

        if ( ! $row ) {
            $row = (object) array_fill_keys(
                array( 'total_count', 'total_amount', 'completed_count', 'completed_amount', 'incomplete_count', 'incomplete_amount', 'refunded_count', 'refunded_amount' ),
                0
            );
        }

        return [
            'total_transactions_count'        => (int) $row->total_count,
            'total_transactions_amount'        => (float) $row->total_amount,
            'completed_transactions_count'     => (int) $row->completed_count,
            'completed_transactions_amount'    => (float) $row->completed_amount,
            'incomplete_transactions_count'    => (int) $row->incomplete_count,
            'incomplete_transactions_amount'   => (float) $row->incomplete_amount,
            'refunded_transactions_count'      => (int) $row->refunded_count,
            'refunded_transactions_amount'     => (float) $row->refunded_amount,
        ];
    }

    /**
     * Get paginated transactions for a specific user email.
     *
     * @param string $email
     * @param array  $args  page, per_page, type ('transactions'|'subscriptions')
     * @return array { transactions: array, total: int, pages: int }
     */
    public static function get_user_transactions_paginated( $email, $args = [] ) {
        global $wpdb;

        $defaults = [
            'page'     => 1,
            'per_page' => 10,
            'type'     => 'transactions',
        ];
        $args     = wp_parse_args( $args, $defaults );
        $table    = self::get_table_name();
        $email    = sanitize_text_field( $email );
        $page     = max( 1, intval( $args['page'] ) );
        $per_page = max( 1, intval( $args['per_page'] ) );
        $offset   = ( $page - 1 ) * $per_page;

        $type_where = self::user_transactions_type_where( (string) $args['type'] );
        if ( null === $type_where ) {
            // Nothing defines this row type (e.g. 'subscriptions' without Pro): answer
            // with no rows, never with every row the user owns.
            return [
                'transactions' => [],
                'total'        => 0,
                'pages'        => 1,
                'page'         => $page,
                'per_page'     => $per_page,
            ];
        }

        // Match rows where the email column is set directly OR where the email
        // appears as a quoted value inside the serialized form_fields_info blob.
        // The initial payment_create() insert never writes the email column —
        // only the payment-confirmed callback does — so many rows have the user's
        // email exclusively inside form_fields_info.
        $ffi_like = '%' . $wpdb->esc_like( '"' . $email . '"' ) . '%';

        $email_where = 'email = %s OR form_fields_info LIKE %s';

        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(id) FROM $table WHERE ( $email_where )" . $type_where,
                $email,
                $ffi_like
            )
        );

        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE ( $email_where )" . $type_where . " ORDER BY payment_date DESC LIMIT %d OFFSET %d",
                $email,
                $ffi_like,
                $per_page,
                $offset
            )
        );

        return [
            'transactions' => $transactions,
            'total'        => $total,
            'pages'        => $total > 0 ? (int) ceil( $total / $per_page ) : 1,
            'page'         => $page,
            'per_page'     => $per_page,
        ];
    }

    /**
     * The SQL condition narrowing get_user_transactions_paginated() to a row type.
     *
     * 'transactions' (or no type) is every row the user owns. Every other type is
     * defined by `better_payment/db/user_transactions_type_where` ($where, $type),
     * which starts from null: Pro supplies the 'subscriptions' condition for its
     * recurring and split payments. Lite's copy of it
     * (ProSubscriptionTransactionData::subscriptions_type_where()) serves only an
     * active Pro that does not (<= 1.4.3). A type nobody defines returns null, and
     * the caller answers with no rows.
     *
     * @since 2.4.0
     *
     * @param string $type Requested row type.
     * @return string|null SQL fragment starting with " AND", '' for all rows, null for none.
     */
    private static function user_transactions_type_where( string $type ) {
        if ( '' === $type || 'transactions' === $type ) {
            return '';
        }

        $where = null;

        if ( ProSubscriptionTransactionData::lite_copy_applies() ) {
            $where = ProSubscriptionTransactionData::subscriptions_type_where( $where, $type );
        }

        $where = apply_filters( 'better_payment/db/user_transactions_type_where', $where, $type );

        return is_string( $where ) ? $where : null;
    }

    /**
     * Delete a transaction
     *
     * @param  int $id
     *
     * @return int|boolean
     * @since 0.0.1
     */
    public static function delete_transaction($id)
    {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Allowed transaction statuses
     * 
     * @since 0.0.1
     */
    public function allowed_statuses($type = 'all', $version = 'v1'){
        $helperObj = new Helper();
        $statuses = $helperObj->get_statuses_by_transaction_type($type, $version);
        
        return $statuses;
    }

    /**
     * Allowed transaction statuses
     * 
     * @since 0.0.1
     */
    public function allowed_statuses_types($version = 'v1'){
        $helperObj = new Helper();
        $statuses = $helperObj->get_transaction_types($version);
        
        return $statuses;
    }

    /**
     * Get transactions amount
     *
     * Lite never calls this; Better Payment Pro <= 1.4.3's AnalyticsModel does, and
     * tests/Unit/ProCouplingContractTest.php pins it. Pro 1.4.4 carries its own copy.
     *
     * @deprecated 2.4.0 Kept only for Better Payment Pro <= 1.4.3; delete when MIN_PRO_VERSION >= 1.4.4.
     *             Pro copy: better-payment-pro/includes/Models/AnalyticsModel.php::get_transactions_by_statuses().
     *
     * @return array
     * @since 0.0.1
     */
    public function get_transactions_by_statuses($statuses = array(), $payment_date_from = '', $payment_date_to = '', $fetchNull=0) {
        global $wpdb;

        $table = DB::get_table_name();
        $allowed_statuses = $this->allowed_statuses('all', 'v2');
        
        $nullDataQuery = $fetchNull ? " OR status IS NULL " : '';

        $statuses = is_array($statuses) && count($statuses) ? $statuses : $allowed_statuses;

        #ToDo: Consider currency while calculating total amount
        $statuses = array_map(function($status) use ($allowed_statuses) {
            if(!in_array($status, $allowed_statuses)) {
                return false;
            }

            return "'" . esc_sql($status) . "'";
        }, $statuses);

        $statuses = implode(',', $statuses);

        if(!empty($payment_date_from) && !empty($payment_date_to)){
            $amount = $wpdb->get_results(
                "SELECT " . self::COUNTABLE_AMOUNT_SQL . " AS amount, payment_date, email FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . ") AND " . self::COUNTED_ROW_SQL . " AND payment_date BETWEEN '" . esc_sql($payment_date_from) . "' AND '" . esc_sql($payment_date_to) . "'"
            );
        } else if(!empty($payment_date_from) && empty($payment_date_to)){
            $amount = $wpdb->get_results(
                "SELECT " . self::COUNTABLE_AMOUNT_SQL . " AS amount, payment_date, email FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . ") AND " . self::COUNTED_ROW_SQL . " AND payment_date >= '" . esc_sql($payment_date_from) . "'"
            );
        } else {
            $amount = $wpdb->get_results(
                "SELECT " . self::COUNTABLE_AMOUNT_SQL . " AS amount, payment_date, email FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . " ) AND " . self::COUNTED_ROW_SQL
            );
        }

        return $amount;
    }

    /**
     * Get transactions amount
     *
     * Lite's caller, get_transactions_analytics_dashboard(), always passes
     * $filtered_transactions and is answered from that array. Without it the answer
     * comes from SQL, which only Better Payment Pro <= 1.4.3's AnalyticsModel asks
     * for — see transactions_amount_by_statuses_query().
     *
     * @return mixed
     * @since 0.0.1
     */
    public function get_transactions_amount_by_statuses($statuses = array(), $payment_date_from = '', $payment_date_to = '', $count_or_amount = 'amount', $fetchNull=0, $filtered_transactions = array() ) {
        $allowed_statuses = $this->allowed_statuses('all', 'v2');
        
        $nullDataQuery = $fetchNull ? " OR status IS NULL " : '';
        
        $statuses = is_array($statuses) && count($statuses) ? $statuses : $allowed_statuses;
        $statuses_original = $statuses;

        #ToDo: Consider currency while calculating total amount
        $statuses = array_map(function($status) use ($allowed_statuses) {
            if(!in_array($status, $allowed_statuses)) {
                return false;
            }

            return "'" . esc_sql($status) . "'";
        }, $statuses);

        $statuses = implode(',', $statuses);

        if( is_array($filtered_transactions) && count($filtered_transactions) ){
            $filtered_transactions_by_statuses = array();
            $filtered_transactions_amount = 0;
            
            foreach($filtered_transactions as $filtered_transaction) {
                // Helper's counting rule, as the SQL copies below apply it: an unconfirmed pre-2.4.0
                // free checkout is no transaction to count, and a confirmed one adds 0.
                if ( Helper::is_unconfirmed_legacy_free_checkout( $filtered_transaction ) ) {
                    continue;
                }

                if( in_array($filtered_transaction->status, $statuses_original) ) {
                    $filtered_transactions_by_statuses[] = $filtered_transaction->id;
                    $filtered_transactions_amount += Helper::countable_amount( $filtered_transaction );
                }
            }

            if($count_or_amount == 'count'){
                $filtered_transactions_by_statuses_count = count($filtered_transactions_by_statuses);
                return $filtered_transactions_by_statuses_count;
            }
            
            if($count_or_amount == 'amount'){
                return $filtered_transactions_amount;
            }
        }

        return $this->transactions_amount_by_statuses_query( $statuses, $nullDataQuery, $payment_date_from, $payment_date_to, $count_or_amount );
    }

    /**
     * The SQL count or sum behind get_transactions_amount_by_statuses() when no
     * filtered transactions are passed.
     *
     * @deprecated 2.4.0 Kept only for Better Payment Pro <= 1.4.3; delete when MIN_PRO_VERSION >= 1.4.4.
     *             Pro copy: better-payment-pro/includes/Models/AnalyticsModel.php::transactions_amount_by_statuses_query(),
     *             kept identical by Pro's AnalyticsModelParityTest.
     *
     * @param string $statuses          Quoted, comma-separated statuses for IN().
     * @param string $nullDataQuery     " OR status IS NULL " or ''.
     * @param string $payment_date_from Lower payment_date bound.
     * @param string $payment_date_to   Upper payment_date bound.
     * @param string $count_or_amount   'count' or 'amount'.
     * @return mixed
     */
    private function transactions_amount_by_statuses_query( $statuses, $nullDataQuery, $payment_date_from, $payment_date_to, $count_or_amount ) {
        global $wpdb;

        $table = DB::get_table_name();

        if($count_or_amount == 'count') {
            $count = (int) $wpdb->get_var(
                "SELECT count(id) FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . " ) AND " . self::COUNTED_ROW_SQL
            );

            return $count;
        }

        if(!empty($payment_date_from) && !empty($payment_date_to)){
            $amount = $wpdb->get_var(
                "SELECT sum(" . self::COUNTABLE_AMOUNT_SQL . ") FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . ") AND " . self::COUNTED_ROW_SQL . " AND payment_date BETWEEN '" . esc_sql($payment_date_from) . "' AND '" . esc_sql($payment_date_to) . "'"
            );
        }else {
            $amount = $wpdb->get_var(
                // #TODO Amount based on filtered transactions
                "SELECT sum(" . self::COUNTABLE_AMOUNT_SQL . ") FROM $table WHERE ( status IN (" . $statuses . ") " . esc_sql($nullDataQuery) . ") AND " . self::COUNTED_ROW_SQL
            );
        }

        return $amount;
    }

    public static function mark_as_completed($id)
    {
        global $wpdb;
        $table = self::get_table_name();

        $id = (int) sanitize_text_field($id);

        if ($id > 0) {
            return $wpdb->update($table, ['status' => 'Completed'], ['id' => $id]);
        }

        return false;
    }
}
