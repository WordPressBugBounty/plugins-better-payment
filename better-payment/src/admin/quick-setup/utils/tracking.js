/**
 * Usage-tracking opt-in request for the Quick Setup wizard.
 *
 * Only the "Proceed To Next Step" button sends this. It is the plugin's one
 * explicit opt-in. "Skip It" sends nothing: skipping is declining, and
 * WordPress.org Guideline 7 requires an explicit opt-in. 2.3.2 – 2.4.1 also
 * opted in from Skip, and the server now refuses that (`context=skip`).
 *
 * The request still carries `context: 'proceed'`, because the server checks
 * it and refuses every other value.
 *
 * See Setup_Wizard::bp_quick_setup_save_tracking().
 */

export const TRACKING_ACTION = 'bp_quick_setup_save_tracking';

/**
 * The only context the server accepts as consent.
 */
export const TRACKING_CONSENT_CONTEXT = 'proceed';

/**
 * Build the POST fields for the opt-in request.
 *
 * Kept separate from the request itself so it stays pure and testable.
 *
 * @param {Object} [wizardData] Localized wizard data; defaults to the global.
 * @return {Object} Flat map of POST fields.
 */
export const buildTrackingPayload = ( wizardData ) => {
	const data = wizardData ?? window?.betterPaymentObjWizard ?? {};

	return {
		action: TRACKING_ACTION,
		_wpnonce: data?.admin_nonce ?? '',
		is_tracking: 'true',
		context: TRACKING_CONSENT_CONTEXT,
	};
};

/**
 * Send the opt-in request. Call it only from the explicit consent button.
 *
 * @param {Object} [wizardData] Localized wizard data; defaults to the global.
 * @return {Promise<Object>} The decoded AJAX response.
 */
export const sendTrackingOptIn = ( wizardData ) => {
	const data = wizardData ?? window?.betterPaymentObjWizard ?? {};
	const body = new FormData();

	Object.entries( buildTrackingPayload( data ) ).forEach(
		( [ key, value ] ) => body.append( key, value )
	);

	return fetch( data?.ajaxUrl, {
		method: 'POST',
		body,
	} ).then( ( res ) => res.json() );
};
