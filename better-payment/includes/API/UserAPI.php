<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Helper;
use WP_REST_Controller;
use WP_REST_Server;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public User REST API — paginated transactions for the logged-in user.
 */
class UserAPI extends WP_REST_Controller {

    protected $namespace = 'better-payment/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/user-transactions', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_user_transactions' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'page'     => [
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                ],
                'tab'      => [
                    'default'           => 'transactions',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    public function check_permissions() {
        return is_user_logged_in();
    }

    public function get_user_transactions( $request ) {
        $current_user = wp_get_current_user();
        $email        = $current_user->user_email;
        $tab          = in_array( $request->get_param( 'tab' ), [ 'transactions', 'subscriptions' ], true )
            ? $request->get_param( 'tab' )
            : 'transactions';

        $result = DB::get_user_transactions_paginated( $email, [
            'page'     => $request->get_param( 'page' ),
            'per_page' => $request->get_param( 'per_page' ),
            'type'     => $tab,
        ] );

        $helper       = new Helper();
        $transactions = [];

        foreach ( $result['transactions'] as $txn ) {
            $form_fields_info = maybe_unserialize( $txn->form_fields_info );
            if ( ! is_array( $form_fields_info ) ) {
                $form_fields_info = [];
            }

            $customer_name = isset( $form_fields_info['primary_first_name'] )
                ? trim( sanitize_text_field( $form_fields_info['primary_first_name'] ) . ' ' . sanitize_text_field( $form_fields_info['primary_last_name'] ?? '' ) )
                : '';
            if ( empty( $customer_name ) ) {
                $customer_name = isset( $form_fields_info['first_name'] )
                    ? trim( sanitize_text_field( $form_fields_info['first_name'] ) . ' ' . sanitize_text_field( $form_fields_info['last_name'] ?? '' ) )
                    : '';
            }

            $customer_email = isset( $form_fields_info['primary_email'] )
                ? sanitize_text_field( $form_fields_info['primary_email'] )
                : '';
            if ( empty( $customer_email ) ) {
                $customer_email = isset( $form_fields_info['email'] )
                    ? sanitize_text_field( $form_fields_info['email'] )
                    : '';
            }

            $status_raw = $txn->status ? sanitize_text_field( $txn->status ) : '';
            $payment_date_formatted = wp_date(
                get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                strtotime( $txn->payment_date )
            );

            $row = [
                'id'             => intval( $txn->id ),
                'amount'         => floatval( $txn->amount ),
                'currency'       => sanitize_text_field( $txn->currency ),
                'status'         => $status_raw,
                'status_color'   => $helper->get_color_by_transaction_status( $status_raw, 'v2' ),
                'status_label'   => ucfirst( $helper->get_type_by_transaction_status( $status_raw, 'v2' ) ),
                'payment_date'   => $payment_date_formatted,
                'transaction_id' => sanitize_text_field( $txn->transaction_id ),
                'source'         => strtolower( sanitize_text_field( $txn->source ) ),
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                'is_subscription'=> ! empty( $form_fields_info['subscription_id'] ) ? 'Subscription' : 'One Time',
                'is_imported'    => ! empty( $form_fields_info['is_imported'] ) && 1 === intval( $form_fields_info['is_imported'] ),
            ];

            if ( 'subscriptions' === $tab ) {
                $row['subscription_id']              = sanitize_text_field( $form_fields_info['subscription_id'] ?? '' );
                $row['subscription_plan_id']         = sanitize_text_field( $form_fields_info['subscription_plan_id'] ?? '' );
                $row['subscription_status']          = sanitize_text_field( $form_fields_info['subscription_status'] ?? '' );
                $row['subscription_interval']        = sanitize_text_field( $form_fields_info['subscription_interval'] ?? '' );
                $row['subscription_created_date']    = intval( $form_fields_info['subscription_created_date'] ?? 0 );
                $row['subscription_current_period_end'] = intval( $form_fields_info['subscription_current_period_end'] ?? 0 );
                $row['is_payment_split_payment']     = ! empty( $form_fields_info['is_payment_split_payment'] ) ? intval( $form_fields_info['is_payment_split_payment'] ) : 0;
                $row['subscription_product_name']    = '';

                $row = apply_filters( 'better_payment/user_api/enrich_subscription_row', $row );
            }

            $transactions[] = $row;
        }

        return rest_ensure_response( [
            'transactions' => $transactions,
            'total'        => $result['total'],
            'page'         => $result['page'],
            'per_page'     => $result['per_page'],
            'pages'        => $result['pages'],
        ] );
    }
}
