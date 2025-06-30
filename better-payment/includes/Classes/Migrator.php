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
        self::update_tables();
    }

    /**
     * Update the plugin tables
     * 
     * @since 0.0.2
     */
    public static function update_tables() {
        //Add column
        self::add_column( 'refund_info', 'longtext' );
        self::add_column( 'campaign_id', 'text' );
    }
    
    private static function add_column( $column_name, $column_type ) {
        global $wpdb;
        $wpdb->hide_errors();
        $table_name = "{$wpdb->prefix}better_payment";
        
        // $row = $wpdb->get_results(  "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = '$column_name'"  );
        
        $columns = $wpdb->get_col( "DESC $table_name", 0 );
        
        if (in_array($column_name, $columns)) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("ALTER TABLE $table_name ADD $column_name $column_type"));
    }
}
