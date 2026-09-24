<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reading the subscription data Pro's recurring and split checkouts write.
 *
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when
 * ProCompatibility::MIN_PRO_VERSION >= 1.4.4.
 * Pro copy: better-payment-pro/includes/SubscriptionTransactionData.php.
 * Slug: subscription_transaction_data.
 *
 * Only Pro's checkout stores `subscription_*` and `is_payment_split_payment` in a
 * transaction's `form_fields_info`. Lite still reads those keys in five places, and
 * each now asks a hook instead:
 *
 * | Lite call site                                   | Hook Pro fills                                   | Method here                  |
 * | ------------------------------------------------ | ------------------------------------------------ | ---------------------------- |
 * | `API\UserAPI` Subscriptions tab row              | `better_payment/user_api/subscription_row`       | subscription_row()           |
 * | `Helper::payment_type_label()` (API + CSV)       | `better_payment/transaction/payment_type`        | payment_type()               |
 * | `Export::form_fields_info_formatted()`           | `better_payment/export/form_fields_info`         | export_fields()              |
 * | `Import::build_form_fields()`                    | `better_payment/import/form_fields_info`         | import_fields()              |
 * | `Admin\DB::get_user_transactions_paginated()`    | `better_payment/db/user_transactions_type_where` | subscriptions_type_where()   |
 *
 * Every call site runs the method here only while lite_copy_applies(): an active
 * Pro that does not declare the slug. A free install never runs it, and a Pro that
 * declares the slug fills the hook itself. Pro's SubscriptionTransactionDataParityTest
 * fails when the method bodies here and in Pro drift.
 *
 * @since 2.4.0
 */
class ProSubscriptionTransactionData {

    /**
     * Feature slug Pro declares once it reads the data itself.
     */
    const FEATURE = 'subscription_transaction_data';

    /**
     * Whether Lite's copy runs on this request.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * Add the subscription columns to a `/user-transactions?tab=subscriptions` row.
     *
     * @param mixed $row              REST row built by UserAPI.
     * @param mixed $form_fields_info The transaction's unserialized form_fields_info.
     * @return array
     */
    public static function subscription_row( $row, $form_fields_info = [] ): array {
        $row              = is_array( $row ) ? $row : [];
        $form_fields_info = is_array( $form_fields_info ) ? $form_fields_info : [];

        $row['subscription_id']                 = sanitize_text_field( $form_fields_info['subscription_id'] ?? '' );
        $row['subscription_plan_id']            = sanitize_text_field( $form_fields_info['subscription_plan_id'] ?? '' );
        $row['subscription_status']             = sanitize_text_field( $form_fields_info['subscription_status'] ?? '' );
        $row['subscription_interval']           = sanitize_text_field( $form_fields_info['subscription_interval'] ?? '' );
        $row['subscription_created_date']       = intval( $form_fields_info['subscription_created_date'] ?? 0 );
        $row['subscription_current_period_end'] = intval( $form_fields_info['subscription_current_period_end'] ?? 0 );
        $row['is_payment_split_payment']        = ! empty( $form_fields_info['is_payment_split_payment'] ) ? intval( $form_fields_info['is_payment_split_payment'] ) : 0;
        $row['subscription_product_name']       = '';

        return $row;
    }

    /**
     * The payment type label of a transaction: "Subscription" when Pro's checkout
     * stored a subscription id, otherwise the label passed in.
     *
     * @param mixed $label            Label so far ('One Time' from Lite).
     * @param mixed $form_fields_info The transaction's unserialized form_fields_info.
     * @return string
     */
    public static function payment_type( $label, $form_fields_info = [] ): string {
        if ( is_array( $form_fields_info ) && ! empty( $form_fields_info['subscription_id'] ) ) {
            return 'Subscription';
        }

        return is_string( $label ) ? $label : 'One Time';
    }

    /**
     * Append the subscription keys to a CSV export's `form_fields_info` cell.
     *
     * @param mixed $fields           Cell fields so far (customer_name, customer_email).
     * @param mixed $form_fields_info The transaction's unserialized form_fields_info.
     * @return array
     */
    public static function export_fields( $fields, $form_fields_info = [] ): array {
        $fields           = is_array( $fields ) ? $fields : [];
        $form_fields_info = is_array( $form_fields_info ) ? $form_fields_info : [];

        $fields['subscription_id']                   = isset( $form_fields_info['subscription_id'] ) ? sanitize_text_field( $form_fields_info['subscription_id'] ) : '';
        $fields['subscription_customer_id']          = isset( $form_fields_info['subscription_customer_id'] ) ? sanitize_text_field( $form_fields_info['subscription_customer_id'] ) : '';
        $fields['subscription_plan_id']              = isset( $form_fields_info['subscription_plan_id'] ) ? sanitize_text_field( $form_fields_info['subscription_plan_id'] ) : '';
        $fields['subscription_interval']             = ! empty( $form_fields_info['subscription_interval'] ) ? sanitize_text_field( $form_fields_info['subscription_interval'] ) : '';
        $fields['subscription_current_period_start'] = ! empty( $form_fields_info['subscription_current_period_start'] ) ? sanitize_text_field( $form_fields_info['subscription_current_period_start'] ) : '';
        $fields['subscription_current_period_end']   = ! empty( $form_fields_info['subscription_current_period_end'] ) ? sanitize_text_field( $form_fields_info['subscription_current_period_end'] ) : '';
        $fields['subscription_status']               = ! empty( $form_fields_info['subscription_status'] ) ? sanitize_text_field( $form_fields_info['subscription_status'] ) : '';
        $fields['subscription_created_date']         = ! empty( $form_fields_info['subscription_created_date'] ) ? sanitize_text_field( $form_fields_info['subscription_created_date'] ) : '';
        $fields['is_payment_split_payment']          = ! empty( $form_fields_info['is_payment_split_payment'] ) ? intval( $form_fields_info['is_payment_split_payment'] ) : 0;

        return $fields;
    }

    /**
     * Copy the subscription keys of a CSV row whose payment type is "Subscription"
     * into the imported transaction's `form_fields_info`.
     *
     * @param mixed $fields           form_fields_info built so far.
     * @param mixed $record           The CSV row, keyed by header.
     * @param mixed $form_fields_info The row's `form_fields_info` cell, decoded by
     *                                Import::decode_form_fields_info() (never an object).
     * @return array
     */
    public static function import_fields( $fields, $record = [], $form_fields_info = [] ): array {
        $fields           = is_array( $fields ) ? $fields : [];
        $record           = is_array( $record ) ? $record : [];
        $form_fields_info = is_array( $form_fields_info ) ? $form_fields_info : [];

        if ( empty( $record['payment_type'] ) || 'subscription' !== strtolower( (string) $record['payment_type'] ) ) {
            return $fields;
        }

        $keys = [
            'subscription_id',
            'subscription_customer_id',
            'subscription_plan_id',
            'subscription_interval',
            'subscription_current_period_start',
            'subscription_current_period_end',
            'subscription_status',
            'subscription_created_date',
            'is_payment_split_payment',
        ];

        foreach ( $keys as $key ) {
            $fields[ $key ] = isset( $form_fields_info[ $key ] ) ? sanitize_text_field( $form_fields_info[ $key ] ) : '';
        }

        return $fields;
    }

    /**
     * The SQL condition that narrows a user's transactions to subscription rows.
     *
     * @param mixed  $where Condition so far (null when nothing supplies one).
     * @param string $type  Requested row type.
     * @return mixed The condition for 'subscriptions', otherwise $where unchanged.
     */
    public static function subscriptions_type_where( $where, $type = '' ) {
        if ( 'subscriptions' !== $type ) {
            return $where;
        }

        return " AND form_fields_info LIKE '%\"subscription_id\"%'";
    }
}
