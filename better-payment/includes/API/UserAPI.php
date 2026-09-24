<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Classes\ProSubscriptionTransactionData;
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

        // Subscription rows are Better Payment's own Stripe recurring and split
        // payments — Pro features, shown only in the Subscriptions tab Pro renders.
        // Without Pro nothing can display them, so don't query for them.
        if ( 'subscriptions' === $tab && ! apply_filters( 'better_payment/pro_enabled', false ) ) {
            return rest_ensure_response( [
                'transactions' => [],
                'total'        => 0,
                'page'         => max( 1, (int) $request->get_param( 'page' ) ),
                'per_page'     => max( 1, (int) $request->get_param( 'per_page' ) ),
                'pages'        => 1,
            ] );
        }

        $result = DB::get_user_transactions_paginated( $email, [
            'page'     => $request->get_param( 'page' ),
            'per_page' => $request->get_param( 'per_page' ),
            'type'     => $tab,
        ] );

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
                // From the stored row: an unconfirmed pre-2.4.0 free checkout reads Incomplete
                // (Helper::transaction_status_type()), as it is in no dashboard figure.
                'status_type'    => Helper::transaction_status_type( $txn, 'v2' ),
                'status_color'   => Helper::transaction_status_color( $txn ),
                'status_label'   => Helper::transaction_status_label( $txn ),
                'payment_date'   => $payment_date_formatted,
                'transaction_id' => sanitize_text_field( $txn->transaction_id ),
                'source'         => strtolower( sanitize_text_field( $txn->source ) ),
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                // 'One Time' unless Pro labels the row (better_payment/transaction/payment_type).
                'is_subscription'=> Helper::payment_type_label( $form_fields_info ),
                'is_imported'    => ! empty( $form_fields_info['is_imported'] ) && 1 === intval( $form_fields_info['is_imported'] ),
            ];

            if ( 'subscriptions' === $tab ) {
                // Pro builds the subscription columns on `better_payment/user_api/subscription_row`.
                // Lite's copy serves only an active Pro that does not (<= 1.4.3).
                if ( ProSubscriptionTransactionData::lite_copy_applies() ) {
                    $row = ProSubscriptionTransactionData::subscription_row( $row, $form_fields_info );
                }

                $built = apply_filters( 'better_payment/user_api/subscription_row', $row, $form_fields_info, $txn );
                $row   = is_array( $built ) ? $built : $row;

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
