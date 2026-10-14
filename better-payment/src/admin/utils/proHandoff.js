/**
 * Slots Better Payment Pro fills in the React admin and the quick-setup wizard.
 *
 * Pro-feature UI moved out of Lite (docs/contributing/extending.md → "Moving Pro
 * code out of Lite"). Lite applies a JS filter where the feature belongs, starting
 * from `null`; Pro 1.4.4+ registers its component on it. A free install renders
 * nothing there. Lite's transitional copies were deleted when
 * `ProCompatibility::MIN_PRO_VERSION` reached 1.4.4.
 */

/**
 * PayPal refund credentials (live/test client ID + secret) on the Settings page.
 * Only Pro's refunds read those keys. Callback: `( node, { mode, settings, onChange } )`.
 */
export const PAYPAL_REFUND_CREDENTIALS_SETTINGS_FILTER =
	'better_payment_settings_paypal_refund_credentials';

/**
 * The same fields in the quick-setup wizard's PayPal step.
 * Callback: `( node, { mode, data, onDataChange } )`.
 */
export const PAYPAL_REFUND_CREDENTIALS_QUICK_SETUP_FILTER =
	'better_payment_quick_setup_paypal_refund_credentials';

/**
 * The PayPal mode the credential fields belong to. The quick-setup step shows
 * the test fields only when live mode is explicitly `'no'`.
 *
 * @param {Object} settings Settings or wizard data.
 * @return {'live'|'test'} Mode.
 */
export const paypalModeOf = ( settings ) =>
	settings?.better_payment_settings_payment_paypal_live_mode === 'no'
		? 'test'
		: 'live';
