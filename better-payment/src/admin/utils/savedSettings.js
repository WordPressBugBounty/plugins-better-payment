/**
 * The last-saved plugin settings, shared across remounts of the Settings page.
 *
 * PHP localises the settings once per page load into
 * `window.betterPaymentAdmin.settings`, and `pages/Settings.js` seeds its state
 * from that blob on mount (it does not re-fetch). The React admin switches tabs
 * without a page load, so Settings unmounts when the user leaves it and remounts
 * on return — seeded again from the same page-load blob. Without writing a save
 * back here, a toggle turned on and saved came back turned off on the next visit
 * to the tab, and only a full reload showed the real value.
 */

/**
 * @return {Object} The settings as of the last page load or successful save.
 */
export function readSavedSettings() {
	return window.betterPaymentAdmin?.settings || {};
}

/**
 * Record settings the server has accepted, so the next mount starts from them.
 *
 * Mutates the existing global rather than replacing it: other admin modules read
 * `window.betterPaymentAdmin` for unrelated keys (currencies, proEnabled, …).
 *
 * @param {Object} settings The settings that were just saved.
 */
export function rememberSavedSettings( settings ) {
	if ( ! window.betterPaymentAdmin ) {
		window.betterPaymentAdmin = {};
	}
	window.betterPaymentAdmin.settings = settings;
}
