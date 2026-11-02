/**
 * Resolves the thumbnail URL for a campaign template.
 *
 * Templates shipped by Better Payment itself store `preview_image` as a path
 * relative to this plugin's directory (e.g. `assets/img/campaign/templates/...`),
 * which is resolved against the localized `pluginUrl`.
 *
 * Templates registered by another plugin through the
 * `better_payment/campaign_templates` filter live outside this plugin's
 * directory, so they supply an absolute URL instead. Those are returned
 * untouched — prefixing them with this plugin's URL would produce a 404.
 *
 * @param {Object} tpl       Template definition.
 * @param {string} pluginUrl Better Payment's plugin URL, with or without a trailing slash.
 * @return {string} Resolved URL, or '' when the template has no preview image.
 */
export function getPreviewImageUrl( tpl, pluginUrl = '' ) {
	return resolveAssetUrl( tpl?.preview_image, pluginUrl );
}

/**
 * The devices a full-page preview can be shown for, in toggle order.
 */
export const PREVIEW_DEVICES = [ 'desktop', 'mobile' ];

/**
 * Resolves one of a template's full-page preview images.
 *
 * `preview_full` is `{ desktop, mobile }`, each a screenshot of the WHOLE
 * template (not the card's top-of-page crop), resolved exactly like
 * `preview_image`. Lite declares it on the locked Pro cards a free install sees
 * (ProTemplateCatalog), which have no layout to render live, so the lightbox can
 * still show the design end to end.
 *
 * @param {Object} tpl       Template definition.
 * @param {string} device    'desktop' | 'mobile'.
 * @param {string} pluginUrl Better Payment's plugin URL.
 * @return {string} Resolved URL, or '' when the template has none for that device.
 */
export function getFullPreviewUrl( tpl, device, pluginUrl = '' ) {
	if ( ! PREVIEW_DEVICES.includes( device ) ) return '';
	return resolveAssetUrl( tpl?.preview_full?.[ device ], pluginUrl );
}

/**
 * The devices a template has a full-page preview for, in toggle order.
 *
 * @param {Object} tpl Template definition.
 * @return {string[]} Subset of PREVIEW_DEVICES.
 */
export function fullPreviewDevices( tpl ) {
	return PREVIEW_DEVICES.filter(
		( device ) => getFullPreviewUrl( tpl, device ) !== ''
	);
}

/**
 * A plugin-relative path resolved against Better Payment's URL; an absolute URL
 * as is.
 *
 * @param {*}      src       Candidate path or URL.
 * @param {string} pluginUrl Better Payment's plugin URL.
 * @return {string} Resolved URL, or '' for anything that is not a non-empty string.
 */
function resolveAssetUrl( src, pluginUrl ) {
	if ( ! src || typeof src !== 'string' ) return '';
	if ( isAbsoluteUrl( src ) ) return src;
	return `${ String( pluginUrl ).replace( /\/+$/, '' ) }/${ src.replace(
		/^\/+/,
		''
	) }`;
}

/**
 * True for anything that already resolves on its own: a scheme-qualified URL
 * (https:, data:, ...) or a protocol-relative one (//cdn.example.com/x.webp).
 *
 * @param {string} src Candidate URL or path.
 * @return {boolean}
 */
export function isAbsoluteUrl( src ) {
	return /^(?:[a-z][a-z0-9+.-]*:|\/\/)/i.test( src );
}
