<?php

namespace Better_Payment\Lite\Classes;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-time revoke of the usage-tracking consent that 2.3.2 – 2.4.1 recorded
 * without asking.
 *
 * Those releases turned tracking on in two ways the admin never chose:
 *
 *   1. **The automatic opt-in.** A fresh install was marked
 *      `better_payment_tracking_auto_optin = pending`, and the next admin or cron
 *      request recorded consent if the setup wizard was never answered.
 *   2. **"Skip It".** The wizard's skip link recorded consent too.
 *
 * Both broke WordPress.org Guideline 7 (issue #147). The code is gone; this
 * removes the consent it already recorded, once per site.
 *
 * Who is revoked: every site where this plugin's consent is on record but
 * `better_payment_settings_opt_in` is not `yes`. Every wizard opt-in since
 * 2.0.0 wrote `yes` from "Proceed To Next Step", so a site without it never
 * clicked that button on any release that offered it. That includes a site
 * installed before 2.3.2 whose admin first opened the wizard, and clicked
 * Skip, on 2.3.2 – 2.4.1: it has no `better_payment_tracking_auto_optin`
 * marker, so the marker cannot be the test. An opt-in from a 1.x release may
 * not have written `yes` either and is revoked too. Revoking a real opt-in
 * only loses data; keeping a fake one breaks Guideline 7.
 *
 * What is revoked: only this plugin's entry in `wpins_allow_tracking` (other
 * WPDeveloper plugins share that option), and this plugin's own cron event.
 * The obsolete marker is deleted on every site.
 *
 * The done-flag is written last. A request that reads the flag as set (a
 * spawned wp-cron.php, say) then also reads the revoked consent, so it cannot
 * send once more in between. Running twice is harmless.
 *
 * @since 2.4.2
 */
class TrackingConsentRepair {

	/**
	 * Set once the repair has run on this site.
	 */
	const OPTION = 'better_payment_tracking_consent_repaired';

	/**
	 * The marker the removed automatic opt-in wrote on a fresh install. Only
	 * deleted; it does not decide who is revoked.
	 */
	const LEGACY_MARKER = 'better_payment_tracking_auto_optin';

	/**
	 * Written only by the wizard's "Proceed To Next Step" button.
	 */
	const CONSENT_OPTION = 'better_payment_settings_opt_in';

	/**
	 * The tracker's scheduled event (see Plugin_Usage_Tracker::__construct()).
	 */
	const EVENT_HOOK = 'better_payment_put_do_weekly_action';

	/**
	 * Revoke unconsented tracking, unless that already happened.
	 *
	 * @return bool True when this run revoked consent.
	 */
	public static function maybe_run() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return false;
		}

		$revoked = self::revoke_unconsented();

		delete_option( self::LEGACY_MARKER );
		update_option( self::OPTION, 'yes' );

		return $revoked;
	}

	/**
	 * Remove this plugin's consent unless "Proceed To Next Step" recorded it.
	 *
	 * @return bool True when consent was removed.
	 */
	private static function revoke_unconsented() {
		if ( 'yes' === get_option( self::CONSENT_OPTION ) ) {
			return false;
		}

		$slug           = basename( BETTER_PAYMENT_FILE, '.php' );
		$allow_tracking = get_option( 'wpins_allow_tracking' );

		if ( ! is_array( $allow_tracking ) || ! isset( $allow_tracking[ $slug ] ) ) {
			return false;
		}

		unset( $allow_tracking[ $slug ] );
		update_option( 'wpins_allow_tracking', $allow_tracking );

		wp_clear_scheduled_hook( self::EVENT_HOOK );

		return true;
	}
}
