<?php

namespace Better_Payment\Lite\Models;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * E-commerce subscription <-> order relation model.
 *
 * Owns the `{prefix}better_payment_subscription_order` table (created in
 * Installer::get_schema()). Every row links an e-commerce subscription to
 * one of its orders and names the integration that owns it in `source`
 * ('woo', 'fluentcart', 'surecart', ...), so several e-commerce integrations
 * can share the table without their ids colliding.
 *
 * Contract: **e-commerce subscription data only.** Better Payment's own
 * (Elementor/campaign) subscription payments live in the `better_payment`
 * transactions table and must never be written here.
 *
 * What a row means:
 * - `subscription_id` — the integration's subscription identifier. For the
 *   WooCommerce module that is the parent (subscription) order id.
 * - `order_id`        — the related order: the parent order itself for a
 *   `new` row, a renewal order for a `renew` row.
 * - `order_item_id`   — the subscription line item on that order (0 when the
 *   integration has no item-level ids).
 *
 * @since 2.4.0
 */
class SubscriptionRelationModel {

    /**
     * Relation types (row = the order that STARTED the subscription vs. a
     * renewal of it).
     */
    const TYPE_NEW   = 'new';
    const TYPE_RENEW = 'renew';

    /**
     * Known e-commerce sources. The column accepts any sanitized slug (a new
     * integration should not need a core edit to record rows) — these consts
     * exist so bundled integrations never typo their own name.
     */
    const SOURCE_WOO        = 'woo';
    const SOURCE_FLUENTCART = 'fluentcart';
    const SOURCE_SURECART   = 'surecart';

    /**
     * The full table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . 'better_payment_subscription_order';
    }

    /**
     * Relation types as key => translated label.
     *
     * @return array
     */
    public static function types() {
        $types = array(
            self::TYPE_NEW   => __( 'New Subscription Order', 'better-payment' ),
            self::TYPE_RENEW => __( 'Renewal Order', 'better-payment' ),
        );

        /**
         * Filters the subscription order relation types.
         *
         * @since 2.4.0
         *
         * @param array $types Type key => translated label.
         */
        return apply_filters( 'better_payment/subscription/order_relation_types', $types );
    }

    /**
     * Known sources as key => label, for admin UI listings.
     *
     * @return array
     */
    public static function known_sources() {
        $sources = array(
            self::SOURCE_WOO        => __( 'WooCommerce', 'better-payment' ),
            self::SOURCE_FLUENTCART => __( 'FluentCart', 'better-payment' ),
            self::SOURCE_SURECART   => __( 'SureCart', 'better-payment' ),
        );

        /**
         * Filters the known e-commerce subscription sources.
         *
         * @since 2.4.0
         *
         * @param array $sources Source slug => label.
         */
        return apply_filters( 'better_payment/subscription/order_relation_sources', $sources );
    }

    /**
     * Pure: clamp a relation type to the registered set ('' when unknown —
     * an unknown type must fail record(), not be silently rewritten).
     *
     * @param mixed $type Raw value.
     * @return string
     */
    public static function sanitize_type( $type ) {
        $type = sanitize_key( (string) $type );

        return array_key_exists( $type, self::types() ) ? $type : '';
    }

    /**
     * Pure: sanitize a source slug. Any non-empty sanitized slug is accepted
     * (a third-party integration must be able to record its own source
     * without filtering a whitelist first); '' when unusable. Capped at the
     * column width.
     *
     * @param mixed $source Raw value.
     * @return string
     */
    public static function sanitize_source( $source ) {
        $source = sanitize_key( (string) $source );

        return substr( $source, 0, 50 );
    }

    /**
     * Record one subscription <-> order relation. Idempotent: an identical
     * row (same subscription, order, type and source) is never duplicated.
     *
     * @param array $args {
     *     @type int    $subscription_id Integration's subscription id (> 0).
     *     @type int    $order_id        Related order id (> 0).
     *     @type int    $order_item_id   Subscription line item id (optional, default 0).
     *     @type string $type            self::TYPE_NEW | self::TYPE_RENEW.
     *     @type string $source          E-commerce source slug (e.g. self::SOURCE_WOO).
     * }
     * @return int Row id (the existing row's id when already recorded), 0 on invalid input or failure.
     */
    public static function record( $args ) {
        global $wpdb;

        $subscription_id = isset( $args['subscription_id'] ) ? absint( $args['subscription_id'] ) : 0;
        $order_id        = isset( $args['order_id'] ) ? absint( $args['order_id'] ) : 0;
        $order_item_id   = isset( $args['order_item_id'] ) ? absint( $args['order_item_id'] ) : 0;
        $type            = self::sanitize_type( isset( $args['type'] ) ? $args['type'] : '' );
        $source          = self::sanitize_source( isset( $args['source'] ) ? $args['source'] : '' );

        if ( $subscription_id <= 0 || $order_id <= 0 || '' === $type || '' === $source ) {
            return 0;
        }

        $existing = self::find_id( $subscription_id, $order_id, $type, $source );

        if ( $existing > 0 ) {
            return $existing;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        $inserted = $wpdb->insert(
            self::get_table_name(),
            array(
                'subscription_id' => $subscription_id,
                'order_id'        => $order_id,
                'order_item_id'   => $order_item_id,
                'type'            => $type,
                'source'          => $source,
            ),
            array( '%d', '%d', '%d', '%s', '%s' )
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * The id of an already-recorded identical relation row (0 = none).
     *
     * @param int    $subscription_id Subscription id.
     * @param int    $order_id        Order id.
     * @param string $type            Relation type.
     * @param string $source          Source slug.
     * @return int
     */
    public static function find_id( $subscription_id, $order_id, $type, $source ) {
        global $wpdb;

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table owned by this model; name is not user input.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE subscription_id = %d AND order_id = %d AND type = %s AND source = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                absint( $subscription_id ),
                absint( $order_id ),
                (string) $type,
                (string) $source
            )
        );
    }

    /**
     * All relation rows of one subscription, oldest first. Optionally
     * restricted to one source.
     *
     * @param int    $subscription_id Subscription id.
     * @param string $source          Optional source slug ('' = any).
     * @return object[] Rows (id, subscription_id, order_id, order_item_id, type, source).
     */
    public static function get_orders( $subscription_id, $source = '' ) {
        global $wpdb;

        $table  = self::get_table_name();
        $source = self::sanitize_source( $source );

        if ( '' !== $source ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE subscription_id = %d AND source = %s ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    absint( $subscription_id ),
                    $source
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE subscription_id = %d ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    absint( $subscription_id )
                )
            );
        }

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * The subscription an order belongs to (0 = none recorded). Optionally
     * restricted to one source — pass it when the caller knows the
     * integration, since order ids from different platforms may collide.
     *
     * @param int    $order_id Order id.
     * @param string $source   Optional source slug ('' = any).
     * @return int
     */
    public static function get_subscription_id( $order_id, $source = '' ) {
        global $wpdb;

        $table  = self::get_table_name();
        $source = self::sanitize_source( $source );

        if ( '' !== $source ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT subscription_id FROM {$table} WHERE order_id = %d AND source = %s ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    absint( $order_id ),
                    $source
                )
            );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT subscription_id FROM {$table} WHERE order_id = %d ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                absint( $order_id )
            )
        );
    }

    /**
     * Paginated list of distinct e-commerce subscriptions, newest first.
     *
     * One entry per (subscription_id, source) pair — a subscription with ten
     * renewal orders is still one row. `renewal_orders` counts its recorded
     * renewals. Ordered by subscription_id DESC (for the bundled integrations
     * the id is a post/order id, so descending ≈ newest subscription first).
     *
     * @param array $args paged, per_page
     * @return array { subscriptions: object[], total: int, pages: int, page: int, per_page: int }
     * @since 2.4.0
     */
    public static function get_subscriptions_paginated( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'paged'    => 1,
            'per_page' => 20,
        );
        $args     = wp_parse_args( $args, $defaults );
        $table    = self::get_table_name();
        $paged    = max( 1, intval( $args['paged'] ) );
        $per_page = max( 1, intval( $args['per_page'] ) );
        $offset   = ( $paged - 1 ) * $per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table owned by this model; name is not user input.
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM ( SELECT subscription_id FROM {$table} GROUP BY subscription_id, source ) AS grouped" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT subscription_id, source,
                        SUM( CASE WHEN type = %s THEN 1 ELSE 0 END ) AS renewal_orders
                 FROM {$table}
                 GROUP BY subscription_id, source
                 ORDER BY subscription_id DESC, source ASC
                 LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                self::TYPE_RENEW,
                $per_page,
                $offset
            )
        );

        return array(
            'subscriptions' => is_array( $rows ) ? $rows : array(),
            'total'         => $total,
            'pages'         => $total > 0 ? (int) ceil( $total / $per_page ) : 1,
            'page'          => $paged,
            'per_page'      => $per_page,
        );
    }

    /**
     * Every distinct e-commerce subscription as a grouped row — the same
     * shape and order get_subscriptions_paginated() returns, without the
     * LIMIT.
     *
     * Exists for the admin list's status/search filters, which cannot become
     * a WHERE clause: the fields they match on (status, customer, product)
     * are not in this table, they arrive when each row is hydrated by its
     * integration. Filtering therefore has to hydrate the whole set first,
     * and this is the query that yields it. Callers that are NOT filtering
     * must keep using get_subscriptions_paginated() — the fast path exists
     * precisely so the default pageview never pays for this.
     *
     * The GROUP BY and ORDER BY must stay identical to
     * get_subscriptions_paginated()'s, or a filtered list would come back in
     * a different order from an unfiltered one.
     *
     * @return object[] Rows with subscription_id, source, renewal_orders.
     * @since 2.4.0
     */
    public static function get_subscription_groups() {
        global $wpdb;

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT subscription_id, source,
                        SUM( CASE WHEN type = %s THEN 1 ELSE 0 END ) AS renewal_orders
                 FROM {$table}
                 GROUP BY subscription_id, source
                 ORDER BY subscription_id DESC, source ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                self::TYPE_RENEW
            )
        );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Every distinct e-commerce subscription as (subscription_id, source)
     * pairs — the unpaginated id list behind get_subscriptions_paginated().
     * Used for whole-table aggregates (the admin tab's summary counts).
     *
     * @return object[] Rows with subscription_id + source.
     * @since 2.4.0
     */
    public static function get_distinct_subscriptions() {
        global $wpdb;

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        $rows = $wpdb->get_results(
            "SELECT subscription_id, source FROM {$table} GROUP BY subscription_id, source" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Delete every relation row of one subscription (housekeeping — e.g. when
     * an integration erases a subscription). Optionally one source only.
     *
     * @param int    $subscription_id Subscription id.
     * @param string $source          Optional source slug ('' = any).
     * @return int Rows deleted.
     */
    public static function delete_for_subscription( $subscription_id, $source = '' ) {
        global $wpdb;

        $where  = array( 'subscription_id' => absint( $subscription_id ) );
        $format = array( '%d' );
        $source = self::sanitize_source( $source );

        if ( '' !== $source ) {
            $where['source'] = $source;
            $format[]        = '%s';
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table owned by this model.
        $deleted = $wpdb->delete( self::get_table_name(), $where, $format );

        return is_numeric( $deleted ) ? (int) $deleted : 0;
    }
}
