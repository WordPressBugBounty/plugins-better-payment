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
	styleToolbar: 'better_payment.campaign_builder.style_toolbar',
	styleValueKey: 'better_payment.campaign_builder.style_value_key',
	canvasWidth: 'better_payment.campaign_builder.canvas_width',
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

/**
 * An add-on's toolbar above a selected element's Style / Advanced controls (Pro's
 * device switcher), or null.
 *
 * @param {Object} props { device, setDevice, element, tab }.
 * @return {*} A React node, or null.
 */
export function styleToolbar( props ) {
	return applyFilters( HOOKS.styleToolbar, null, props ) ?? null;
}

/**
 * The setting key a style control reads and writes while `device` is being
 * edited. Lite stores one value per control, so it is always the control's own
 * key; an add-on with per-device values answers `{key}_tablet` and the like.
 *
 * @param {Object} control Schema control (has `selectors`).
 * @param {string} device  The device being edited ('desktop' without an add-on).
 * @param {Object} element The selected element.
 * @return {string} Setting key.
 */
export function styleValueKey( control, device, element ) {
	const key = applyFilters( HOOKS.styleValueKey, control.key, {
		control,
		device,
		element,
	} );

	return typeof key === 'string' && /^[A-Za-z0-9_]+$/.test( key )
		? key
		: control.key;
}

/**
 * The live canvas's width while `device` is being edited, or null for the full
 * canvas. Lite has one device, so it never narrows the canvas.
 *
 * @param {string} device The device being edited.
 * @return {string|null} A CSS width such as '768px', or null.
 */
export function canvasWidth( device ) {
	const width = applyFilters( HOOKS.canvasWidth, null, device );

	return typeof width === 'string' && /^\d{2,4}px$/.test( width )
		? width
		: null;
}
