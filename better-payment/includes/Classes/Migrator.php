<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The plugin migrator class
 * 
 * @since 0.0.2
 */
class Migrator
{

    /**
     * Initialize the plugin
     * 
     * @since 0.0.2
     */
    public static function migrator() {
        self::create_missing_tables();
        self::update_tables();
    }

    /**
     * Create any table added since this install was activated (dbDelta is
     * idempotent). The migrator only runs once per version change, so an
     * existing install picks up e.g. the better_payment_subscription_order table
     * on its first load after an update — activation never re-runs there.
     *
     * Kept separate from update_tables(), which stays ALTER-only: the test
     * environment rewrites CREATE TABLE to CREATE TEMPORARY TABLE, so a
     * create inside a column-migration test would shadow the real table.
     *
     * @since 2.4.0
     */
    public static function create_missing_tables() {
        ( new \Better_Payment\Lite\Installer() )->create_tables();
    }

    /**
     * Update the plugin tables
     *
     * @since 0.0.2
     */
    public static function update_tables() {
        //Add column
        // `refund_info` is Pro's: only Pro's refunds write it, and Better Payment Pro
        // 1.4.4+ adds the column itself (Pro Installer::add_refund_info_column()). Lite
        // still adds it, idempotently, as a data guard for Pro 1.4.3: that Pro relies
        // on Lite for it, and on a fresh install its refund would reach the gateway
        // and then fail to record, leaving a refunded row marked paid. Stop once no
        // supported site can run Pro 1.4.3.
        self::add_column( 'refund_info', 'longtext' );
        self::add_column( 'campaign_id', 'text' );
    }
    
    private static function add_column( $column_name, $column_type ) {
        global $wpdb;
        $wpdb->hide_errors();
        $table_name = "{$wpdb->prefix}better_payment";
        
        // $row = $wpdb->get_results(  "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = '$column_name'"  );
        
        $columns = $wpdb->get_col( "DESC $table_name", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- table name from $wpdb->prefix; schema introspection of the plugin custom table, no WP API.
        
        if (in_array($column_name, $columns)) {
            return;
        }
        
        // Identifiers cannot be placeholders, so this ran through prepare() with none, which
        // WordPress reports as _doing_it_wrong. Both values are literals from update_tables();
        // anything that is not a plain identifier and type is refused rather than run.
        if ( ! preg_match( '/^[a-z_][a-z0-9_]*$/', $column_name ) || ! preg_match( '/^[a-z]+(\(\d+(,\d+)?\))?$/', $column_type ) ) {
            return;
        }

        // Table name from $wpdb->prefix; column name and type validated above.
        $wpdb->query( "ALTER TABLE $table_name ADD $column_name $column_type" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- see the comment above; adds a column to the plugin custom table, no WP API.
    }
}
