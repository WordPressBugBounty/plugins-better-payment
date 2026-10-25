<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asks site owners to rotate the gateway secret keys that 2.4.1 and earlier
 * exposed in the Elementor editor.
 *
 * Up to 2.4.1 the global Stripe and Paystack secret keys were the default value of
 * the Elementor secret-key controls, so any user who could open the Elementor
 * editor — a Contributor on their own draft — could read them (see
 * GatewaySecrets). The leak is closed, but a key that was already read stays valid
 * until the owner rotates it at Stripe or Paystack.
 *
 * Who sees it: administrators of a site that had a global secret key saved when it
 * updated. A fresh install, or a site that adds its keys later, never had them
 * exposed, so the check runs once per site and records `none` for them.
 *
 * Where: the WordPress Dashboard, the Plugins screen and Better Payment's own
 * Dashboard tab — not every admin screen. Better Payment's admin is one page whose
 * tabs switch without a reload, so on that page the notice is printed on every
 * load and shown only while the Dashboard tab is open.
 *
 * @since 2.4.2
 */
class SecretKeyRotationNotice {

	/**
	 * `show`, `dismissed` or `none`. Missing until the one-time check runs.
	 */
	const OPTION = 'better_payment_secret_key_rotation_notice';

	/**
	 * The admin-post action (and nonce action) that dismisses the notice.
	 */
	const DISMISS_ACTION = 'better_payment_dismiss_key_rotation';

	/**
	 * WordPress screens that show the notice (network variants for multisite).
	 */
	const SCREENS = array( 'dashboard', 'dashboard-network', 'plugins', 'plugins-network' );

	/**
	 * Better Payment's admin page slug, and the one tab of it that shows the notice.
	 */
	const ADMIN_PAGE    = 'better-payment-admin';
	const DASHBOARD_TAB = 'dashboard';

	/**
	 * Decide once per site whether the notice is needed.
	 *
	 * @return bool True when this run turned the notice on.
	 */
	public static function maybe_flag() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return false;
		}

		$show = GatewaySecrets::has_global_secret();

		update_option( self::OPTION, $show ? 'show' : 'none', false );

		return $show;
	}

	/**
	 * Hook the notice and its dismiss handler. Admin requests only.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
		// Better Payment's own screens clear `admin_notices` and re-fire this hook.
		add_action( 'better_payment_admin_notices', array( __CLASS__, 'render' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * Whether the current user should see the notice.
	 *
	 * @return bool
	 */
	public static function should_show() {
		return 'show' === get_option( self::OPTION ) && current_user_can( 'manage_options' );
	}

	/**
	 * Where a screen stands on the notice.
	 *
	 * `show` on the WordPress Dashboard, the Plugins screen and Better Payment's
	 * Dashboard tab; `hidden` on Better Payment's other tabs (printed, so a tab
	 * switch without a reload can reveal it); `none` everywhere else.
	 *
	 * @param string $screen_id Current screen id.
	 * @param string $page      The `page` query arg.
	 * @param string $tab       The `tab` query arg.
	 * @return string `show`, `hidden` or `none`.
	 */
	public static function placement( $screen_id, $page, $tab ) {
		if ( in_array( $screen_id, self::SCREENS, true ) ) {
			return 'show';
		}

		if ( self::ADMIN_PAGE === $page ) {
			return ( '' === $tab || self::DASHBOARD_TAB === $tab ) ? 'show' : 'hidden';
		}

		return 'none';
	}

	/**
	 * A read-only routing query arg of the current admin request.
	 *
	 * @param string $key `page` or `tab`.
	 * @return string Sanitised value, '' when absent.
	 */
	private static function query_arg( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing arg.
		return isset( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	/**
	 * The placement for the current request.
	 *
	 * @return string `show`, `hidden` or `none`.
	 */
	private static function current_placement() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return self::placement( $screen ? (string) $screen->id : '', self::query_arg( 'page' ), self::query_arg( 'tab' ) );
	}

	/**
	 * Print the notice.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! self::should_show() ) {
			return;
		}

		$placement = self::current_placement();
		if ( 'none' === $placement ) {
			return;
		}
		$on_admin_page = self::ADMIN_PAGE === self::query_arg( 'page' );

		$settings_url = admin_url( 'admin.php?page=better-payment-admin&tab=settings' );
		$dismiss_url  = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
		?>
		<div class="notice notice-warning better-payment-key-rotation-notice"<?php echo 'hidden' === $placement ? ' style="display:none"' : ''; ?>>
			<p><strong><?php esc_html_e( 'Better Payment: please rotate your Stripe and Paystack secret keys', 'better-payment' ); ?></strong></p>
			<p><?php esc_html_e( 'Before this update, the secret keys saved in Better Payment > Settings could be read by any user who can open the Elementor editor, including Contributors. This update fixes that. As a precaution, create new secret keys in your Stripe and Paystack dashboards, then save them in Better Payment > Settings. If a payment form has its own secret key filled in, update it there too.', 'better-payment' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Open Better Payment Settings', 'better-payment' ); ?></a>
				<a class="button" href="<?php echo esc_url( 'https://docs.stripe.com/keys#rolling-keys' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to rotate Stripe keys', 'better-payment' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'I have rotated my keys, dismiss', 'better-payment' ); ?></a>
			</p>
		</div>
		<?php
		if ( $on_admin_page ) {
			// Tabs switch without a reload: follow them, showing the notice on Dashboard only.
			wp_print_inline_script_tag(
				'(function(){var n=document.querySelector(".better-payment-key-rotation-notice");if(!n){return;}'
				. 'window.addEventListener("bp:page-changed",function(e){var t=e&&e.detail?e.detail.tab:"";'
				. 'n.style.display=t===' . wp_json_encode( self::DASHBOARD_TAB ) . '?"":"none";});})();'
			);
		}
	}

	/**
	 * Dismiss the notice for the site, then go back.
	 *
	 * @return void
	 */
	public static function dismiss() {
		self::process_dismiss();

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}

	/**
	 * Check the capability and nonce, then record the dismissal.
	 *
	 * Dies on a missing capability or a bad nonce.
	 *
	 * @return void
	 */
	public static function process_dismiss() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'better-payment' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::DISMISS_ACTION );

		update_option( self::OPTION, 'dismissed', false );
	}
}
