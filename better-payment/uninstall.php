<?php
/**
 * Better Payment uninstall.
 *
 * Runs only when the plugin is deleted from the Plugins screen. Removes the MCP module's
 * access records: OAuth clients, token hashes and the activity log, which mean nothing
 * once the server is gone. Transactions, settings and campaigns are the owner's records
 * and stay.
 *
 * The plugin is not loaded here, so the names are literals.
 * `MCPUninstallTest` holds them equal to `MCPManager::stored_options()`.
 *
 * @package Better_Payment
 * @since 2.5.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$better_payment_mcp_options = array(
	'better_payment_mcp_activity',
	'better_payment_mcp_oauth_clients',
	'better_payment_mcp_oauth_codes',
	'better_payment_mcp_oauth_tokens',
);

$better_payment_site_ids = is_multisite() ? get_sites( array( 'fields' => 'ids' ) ) : array( get_current_blog_id() );

foreach ( $better_payment_site_ids as $better_payment_site_id ) {
	if ( is_multisite() ) {
		switch_to_blog( (int) $better_payment_site_id );
	}

	foreach ( $better_payment_mcp_options as $better_payment_option ) {
		delete_option( $better_payment_option );
	}

	if ( is_multisite() ) {
		restore_current_blog();
	}
}
