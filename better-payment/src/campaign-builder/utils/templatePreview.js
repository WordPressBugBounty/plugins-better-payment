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
	const src = tpl && tpl.preview_image;
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
