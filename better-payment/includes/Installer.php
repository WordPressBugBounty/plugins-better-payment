<?php

namespace Better_Payment\Lite;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installer class
 * 
 * @since 0.0.1
 */

class Installer extends Controller {

    /**
     * Run the installer
     *
     * @return void
     * @since 0.0.1
     */
    public function run() {
        $this->create_tables();
        self::enable_setup_wizard();
    }

    /**
     * Table creation schema
     *
     * @since 0.0.1
     */
    private function get_schema() {
        global $wpdb;
        return [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}better_payment(
         	 	id bigint(20) NOT NULL AUTO_INCREMENT,
  			 	order_id varchar(50) NOT NULL,
				transaction_id varchar(50) DEFAULT '',
			 	amount decimal(10,2) NOT NULL,
			 	status varchar(50) DEFAULT NULL,
			    source varchar(50) NOT NULL,
			    payment_date datetime DEFAULT NULL,
			    email varchar(50) NOT NULL DEFAULT '',
			    customer_info longtext,
			    form_fields_info longtext,
			    currency varchar(11) NOT NULL DEFAULT '',
                referer varchar(64) DEFAULT NULL,
			    obj_id text,
			    PRIMARY KEY (id),
			    KEY order_id (order_id),
			    KEY status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",

            // E-commerce subscription <-> order relation. The `source` column
            // names the e-commerce integration that owns the row: woo,
            // fluentcart, surecart, ...
            // E-commerce data ONLY — Better Payment's own (Elementor/campaign)
            // subscription payments never write here. All reads/writes go through
            // Models\SubscriptionRelationModel.
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}better_payment_subscription_order(
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                subscription_id bigint(20) unsigned NOT NULL,
                order_id bigint(20) unsigned NOT NULL,
                order_item_id bigint(20) unsigned NOT NULL DEFAULT 0,
                type varchar(20) NOT NULL DEFAULT 'new',
                source varchar(50) NOT NULL,
                PRIMARY KEY (id),
                KEY subscription_id (subscription_id),
                KEY order_id (order_id),
                KEY source (source)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ",
        ];
    }

    /**
     * Create necessary database tables
     *
     * @return void
     * @since 0.0.1
     */
    public function create_tables() {
        global $wpdb;
        $wpdb->hide_errors();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $tables = $this->get_schema();
        foreach ( $tables as $table ) {
            dbDelta( $table );
        }
    }

    /**
     * Save setup wizard data
     *
     * @since 0.0.2
     */
    public function enable_setup_wizard()
    {
        if ( !get_option( 'better_payment_setup_wizard' ) ) {
            update_option( 'better_payment_setup_wizard', 'redirect' );

            /**
             * A missing `better_payment_setup_wizard` option is this plugin's
             * definition of a fresh install — it is what decides that the setup
             * wizard is owed a redirect at all. The same test marks the install
             * as eligible for the automatic usage-tracking opt-in, so the two can
             * never disagree about what "new install" means.
             *
             * Deliberately scoped to fresh installs: an existing site that has
             * already been through (or dismissed) the wizard keeps whatever
             * tracking state it has. See
             * Traits\Helper::maybe_auto_enable_usage_tracking(), which consumes
             * this marker exactly once.
             *
             * @since 2.3.2
             */
            self::mark_tracking_auto_optin_pending();
        }
    }

    /**
     * Flag a fresh install for the automatic usage-tracking opt-in.
     *
     * Only ever sets the marker when the site has no tracking state at all. A
     * site that reached consent before this option existed (an upgrade that
     * re-runs activation, say) must not be handed a second, automatic opt-in.
     *
     * @return void
     * @since 2.3.2
     */
    public function mark_tracking_auto_optin_pending()
    {
        if ( get_option( 'better_payment_tracking_auto_optin' ) ) {
            return;
        }

        $allow_tracking = get_option( 'wpins_allow_tracking' );

        if ( is_array( $allow_tracking ) && isset( $allow_tracking[ basename( BETTER_PAYMENT_FILE, '.php' ) ] ) ) {
            return;
        }

        update_option( 'better_payment_tracking_auto_optin', 'pending' );
    }
}
