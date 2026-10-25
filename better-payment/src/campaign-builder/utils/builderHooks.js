import { applyFilters } from '@wordpress/hooks';

/**
 * The builder's extension points, on `wp.hooks`.
 *
 * An add-on registers its filters in a script that runs BEFORE the builder bundle
 * (it adds its handle through the PHP filter
 * `better_payment/campaign/builder_script_dependencies`). Pro's popup payment form
 * is the first user. Every filter here is applied with a value that keeps the
 * free builder exactly as it is when nothing answers.
 */

/** Filter names, so a typo in one place cannot silently disconnect an add-on. */
export const HOOKS = {
	initialMeta: 'better_payment.campaign_builder.initial_meta',
	settingsControl: 'better_payment.campaign_builder.settings_control',
	needsDonateUrl: 'better_payment.campaign_builder.needs_donate_url',
};

/**
 * Meta a NEW campaign starts with. Only new campaigns: a loaded campaign's own meta
 * replaces it, and the store drops it when an existing campaign fails to load.
 *
 * @param {Object} meta Lite's defaults.
 * @return {Object} The defaults, with any add-on's keys.
 */
export function filterInitialMeta( meta ) {
	const filtered = applyFilters( HOOKS.initialMeta, { ...meta } );

	return filtered && typeof filtered === 'object' ? filtered : meta;
}

/**
 * An add-on's rendering of a settings control, or null to let the builder draw it.
 *
 * @param {Object} props { control, value, onChange, meta, layout, proEnabled, onRequestUpgrade }.
 *                       `layout` is the campaign's current (unsaved) layout.
 * @return {*} A React node, or null.
 */
export function customSettingsControl( props ) {
	return applyFilters( HOOKS.settingsControl, null, props ) ?? null;
}

/**
 * Whether the Donate Button needs a payment page URL. True unless an add-on gives
 * the button somewhere else to go (Pro's popup form). Drives the "no donation
 * page" warning before publishing.
 *
 * @param {Object} meta Campaign meta.
 * @return {boolean} False when no URL is needed.
 */
export function needsDonateUrl( meta ) {
	return applyFilters( HOOKS.needsDonateUrl, true, meta ) !== false;
}
