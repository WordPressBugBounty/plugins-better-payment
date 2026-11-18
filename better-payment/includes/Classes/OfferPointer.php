<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The seasonal offer pointer: a WordPress admin pointer beside Better Payment's menu item.
 *
 * Free upsell UI for the running campaign (Black Friday / Cyber Monday 2026), shown beside the
 * dashboard offer notice (`Admin::admin_notice()`) and inside the same dates. It uses WordPress's
 * own `wp-pointer`, so it looks like every other admin pointer.
 *
 * It shows only when all of these hold:
 * - Pro is not active (read live from `better_payment/pro_enabled`);
 * - the date is inside the campaign window;
 * - the screen is the WordPress Dashboard or Better Payment's own page;
 * - the user can `manage_options` (the capability of the menu item it points at);
 * - this user has not dismissed it (user meta, kept for good);
 * - no other WPDeveloper plugin holds the pointer slot with a smaller number.
 *
 * The pointer slot is shared: every WPDeveloper plugin that shows a pointer reads and writes the
 * option `_wpdeveloper_plugin_pointer_priority`, the smallest number wins, and a plugin releases
 * the slot when its pointer is dismissed. Better Payment's number is 9, the same priority its
 * notices use. It releases the slot only while it holds it, and again once the campaign ends
 * or Pro is activated, so a claim never outlives the pointer.
 *
 * Each campaign replaces the one before it: change the constants and the copy in `content()`.
 * A new campaign takes a new `ID`, or it inherits the previous one's dismissals.
 *
 * @since 2.5.1
 */
class OfferPointer {

	/** The campaign. Part of the dismissal's user meta key. */
	const ID = 'bfcm2026';

	/** Same window as the dashboard offer notice (`strtotime()`, so UTC). */
	const START = '12:00:00am 15th November, 2026';

	const EXPIRE = '11:59:59pm 14th December, 2026';

	const URL = 'https://betterpayment.co/bfcm2026/admin-notice';

	/** Better Payment's place in the shared pointer queue (smaller shows first). */
	const PRIORITY = 9;

	/** Shared by every WPDeveloper plugin that shows a pointer. */
	const PRIORITY_OPTION = '_wpdeveloper_plugin_pointer_priority';

	/** The menu item's own link (not the whole item: on Better Payment's page the item holds the open submenu, and the arrow would point at its middle). */
	const TARGET = '#toplevel_page_better-payment-admin > a.menu-top';

	/** Screen ids that show the pointer: the WordPress Dashboard and Better Payment's own page. */
	const SCREENS = array( 'dashboard', 'toplevel_page_better-payment-admin' );

	const CAPABILITY = 'manage_options';

	const AJAX_ACTION = 'better_payment_dismiss_offer_pointer';

	const NONCE_ACTION = 'better_payment_offer_pointer';

	const SCRIPT_HANDLE = 'better-payment-offer-pointer';

	/**
	 * Hook in. Admin requests only.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action(
			'admin_enqueue_scripts',
			static function () {
				self::enqueue();
			}
		);
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'dismiss' ) );
		add_action(
			'admin_init',
			static function () {
				self::release_when_over();
			}
		);
	}

	/**
	 * The user meta key that records a dismissal.
	 *
	 * @return string
	 */
	public static function meta_key(): string {
		return '_better_payment_offer_pointer_' . self::ID . '_dismissed';
	}

	/**
	 * Whether a moment is inside the campaign window (both ends included).
	 *
	 * @param int $now Unix timestamp.
	 * @return bool
	 */
	public static function in_window( int $now ): bool {
		return $now >= (int) strtotime( self::START ) && $now <= (int) strtotime( self::EXPIRE );
	}

	/**
	 * Whether a screen shows the pointer.
	 *
	 * @param string $screen_id `get_current_screen()->id`.
	 * @return bool
	 */
	public static function is_screen( string $screen_id ): bool {
		return in_array( $screen_id, self::SCREENS, true );
	}

	/**
	 * Whether Better Payment may show its pointer, given who holds the shared slot.
	 *
	 * Nobody holds it (the option is missing, null, empty or 0), or the holder's number is not
	 * smaller than ours. A smaller number is another plugin's pointer waiting to be dismissed.
	 *
	 * @param mixed $current The shared option's value.
	 * @return bool
	 */
	public static function may_take_slot( $current ): bool {
		$holder = is_numeric( $current ) ? (int) $current : 0;

		return $holder <= 0 || $holder >= self::PRIORITY;
	}

	/**
	 * Whether a user dismissed this campaign's pointer.
	 *
	 * @param int $user_id User id.
	 * @return bool
	 */
	public static function is_dismissed( int $user_id ): bool {
		return $user_id > 0 && '' !== (string) get_user_meta( $user_id, self::meta_key(), true );
	}

	/**
	 * Every condition but the shared slot: Pro, dates, screen, capability, dismissal.
	 *
	 * @param string $screen_id Screen id.
	 * @param int    $now       Unix timestamp.
	 * @return bool
	 */
	public static function is_due( string $screen_id, int $now ): bool {
		if ( true === apply_filters( 'better_payment/pro_enabled', false ) ) {
			return false;
		}

		return self::in_window( $now )
			&& self::is_screen( $screen_id )
			&& current_user_can( self::CAPABILITY )
			&& ! self::is_dismissed( get_current_user_id() );
	}

	/**
	 * What the pointer says. Plain text: the script builds the markup with DOM methods.
	 *
	 * @return array{title: string, text: string, button: string, dismiss: string}
	 */
	public static function content(): array {
		return array(
			'title'   => __( 'Black Friday Is On!', 'better-payment' ),
			'text'    => __( 'Up to 40% off Better Payment Pro. Unlock donation campaigns, subscriptions and an AI campaign builder.', 'better-payment' ),
			'button'  => __( 'View Deals', 'better-payment' ),
			'dismiss' => __( 'Dismiss', 'better-payment' ),
		);
	}

	/**
	 * `admin_enqueue_scripts`: load the pointer when it is due and the slot is free.
	 *
	 * @param mixed    $hook Page hook (unused: the screen id is read instead).
	 * @param int|null $now  Unix timestamp; null reads the clock.
	 * @return bool Whether the pointer was loaded.
	 */
	public static function enqueue( $hook = '', ?int $now = null ): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! self::is_due( (string) $screen->id, null === $now ? time() : $now ) ) {
			return false;
		}

		$holder = get_option( self::PRIORITY_OPTION );
		if ( ! self::may_take_slot( $holder ) ) {
			return false;
		}

		// Take the slot (or keep it), so a plugin later in the queue waits for this pointer.
		if ( ! is_numeric( $holder ) || (int) $holder !== self::PRIORITY ) {
			update_option( self::PRIORITY_OPTION, self::PRIORITY );
		}

		$file = file_exists( BETTER_PAYMENT_PATH . '/assets/js/offer-pointer.min.js' ) ? 'offer-pointer.min.js' : 'offer-pointer.js';
		$path = BETTER_PAYMENT_PATH . '/assets/js/' . $file;

		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			BETTER_PAYMENT_ASSETS . '/js/' . $file,
			array( 'jquery', 'wp-pointer' ),
			file_exists( $path ) ? (string) filemtime( $path ) : BETTER_PAYMENT_VERSION,
			true
		);

		// Not wp_localize_script(): it casts every scalar to a string.
		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'window.betterPaymentOfferPointer = ' . wp_json_encode(
				array_merge(
					self::content(),
					array(
						'id'      => self::ID,
						'target'  => self::TARGET,
						'url'     => self::URL,
						'edge'    => is_rtl() ? 'right' : 'left',
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'action'  => self::AJAX_ACTION,
						'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
					)
				)
			) . ';',
			'before'
		);

		wp_add_inline_style( 'wp-pointer', self::css() );

		return true;
	}

	/** The campaign blue: WordPress's default admin blue, whatever colour scheme the user picked. */
	const COLOR = '#2271b1';

	const COLOR_HOVER = '#135e96';

	/**
	 * The pointer's own rules, on top of WordPress's `wp-pointer` stylesheet.
	 *
	 * The look is fixed: the header, the button and Dismiss are the default admin blue on white.
	 * A user's admin colour scheme recolours every core pointer and primary button, so each of
	 * those rules is answered here with one class more than the scheme's selector. The action row
	 * puts the button on the left and Dismiss on the right (core floats both to the right), and
	 * Dismiss carries an outlined circle in place of core's filled grey one.
	 *
	 * @return string
	 */
	public static function css(): string {
		$scope = '.wp-pointer.better-payment-offer-pointer';
		$blue  = self::COLOR;
		$hover = self::COLOR_HOVER;
		// A 14px circle with a cross, drawn in the blue (and in the hover blue).
		$icon = static function ( string $color ): string {
			return 'url("data:image/svg+xml,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6.75"/><path d="M5.6 5.6l4.8 4.8M10.4 5.6l-4.8 4.8"/></svg>' ) . '")';
		};

		$rules = array(
			// Core sets the width inline; Better Payment's own page makes every box border-box.
			"$scope{box-sizing:content-box;}",
			"$scope .wp-pointer-content{padding:0;background:#fff;border:1px solid #c3c4c7;border-radius:4px;box-shadow:0 3px 6px rgba(0,0,0,.08);}",
			"$scope .wp-pointer-content h3{margin:-1px -1px 0;padding:9px 18px 9px 61px;border:1px solid $blue;border-bottom:none;border-radius:4px 4px 0 0;background:$blue;color:#fff;font-size:14px;font-weight:600;line-height:1.4;}",
			"$scope .wp-pointer-content h3:before{top:6px;left:13px;width:26px;height:26px;background:#fff;color:$blue;font:normal 16px/26px dashicons;}",
			"$scope .wp-pointer-content p{margin:12px 0 13px;padding:0 21px;color:#3c434a;font-size:13px;line-height:1.42;}",
			"$scope .wp-pointer-buttons{margin:0;padding:0 17px 22px 21px;overflow:visible;}",
			"$scope .bp-offer-pointer__actions{display:flex;align-items:center;justify-content:space-between;gap:12px;}",
			"$scope .wp-pointer-buttons a{float:none;}",
			"$scope .wp-pointer-buttons a.button-primary{margin:0;min-height:28px;padding:0 11px;border:1px solid $blue;border-radius:3px;background:$blue;color:#fff;font-size:13px;font-weight:600;line-height:26px;box-shadow:none;text-shadow:none;}",
			"$scope .wp-pointer-buttons a.button-primary:hover,$scope .wp-pointer-buttons a.button-primary:focus{border-color:$hover;background:$hover;color:#fff;}",
			"$scope .wp-pointer-buttons a.button-primary:focus{box-shadow:0 0 0 1px #fff,0 0 0 3px $blue;outline:0;}",
			"$scope .wp-pointer-buttons a.close{padding-left:19px;color:$blue;font-size:13px;line-height:1.4;}",
			"$scope .wp-pointer-buttons a.close:hover,$scope .wp-pointer-buttons a.close:focus{color:$hover;}",
			"$scope .wp-pointer-buttons a.close:before{content:'';left:0;top:50%;width:14px;height:14px;margin:-7px 0 0;background:" . $icon( $blue ) . ' no-repeat center / 14px 14px;}',
			"$scope .wp-pointer-buttons a.close:hover:before,$scope .wp-pointer-buttons a.close:focus:before{background-image:" . $icon( $hover ) . ';}',
			// The arrow keeps core's white fill and grey edge under every colour scheme.
			"$scope.wp-pointer-left .wp-pointer-arrow{border-right-color:#c3c4c7;}",
			"$scope.wp-pointer-left .wp-pointer-arrow-inner{border-right-color:#fff;}",
			"$scope.wp-pointer-right .wp-pointer-arrow{border-left-color:#c3c4c7;}",
			"$scope.wp-pointer-right .wp-pointer-arrow-inner{border-left-color:#fff;}",
		);

		return implode( '', $rules );
	}

	/**
	 * AJAX: record this user's dismissal and release the shared slot.
	 *
	 * @return void
	 */
	public static function dismiss(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'better-payment' ) ), 403 );
		}

		self::record_dismissal( get_current_user_id() );

		wp_send_json_success();
	}

	/**
	 * Record a user's dismissal (kept for good) and release the shared slot.
	 *
	 * @param int $user_id User id.
	 * @return void
	 */
	public static function record_dismissal( int $user_id ): void {
		if ( $user_id > 0 ) {
			update_user_meta( $user_id, self::meta_key(), time() );
		}

		self::release_slot();
	}

	/**
	 * Give the shared slot back, only while Better Payment holds it.
	 *
	 * @return bool Whether the slot was released.
	 */
	public static function release_slot(): bool {
		$holder = get_option( self::PRIORITY_OPTION );

		if ( ! is_numeric( $holder ) || (int) $holder !== self::PRIORITY ) {
			return false;
		}

		return (bool) update_option( self::PRIORITY_OPTION, null );
	}

	/**
	 * `admin_init`: release a slot nobody dismissed once the pointer can no longer show (the
	 * campaign is over, or Pro was activated), so a plugin later in the queue is not kept
	 * waiting for a pointer that will never come back.
	 *
	 * @param int|null $now Unix timestamp; null reads the clock.
	 * @return bool Whether the slot was released.
	 */
	public static function release_when_over( ?int $now = null ): bool {
		$now = null === $now ? time() : $now;

		if ( $now > (int) strtotime( self::EXPIRE ) || true === apply_filters( 'better_payment/pro_enabled', false ) ) {
			return self::release_slot();
		}

		return false;
	}
}
