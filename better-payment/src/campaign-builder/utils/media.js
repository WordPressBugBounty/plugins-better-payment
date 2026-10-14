import { __ } from '@wordpress/i18n';

/**
 * Open the WordPress media frame and hand the chosen attachment back.
 *
 * `wp.media` exists because CPT::enqueue_builder_assets() calls
 * `wp_enqueue_media()` on the builder page, and nowhere else — so the guard below
 * is load-bearing, not defensive noise.
 *
 * @param {Function} onSelect         Called with { url, id, sizes, mime } for the chosen attachment.
 * @param {Object}   [options]        Frame options.
 * @param {string}   [options.type]   Media type to browse — 'image' (default), 'video', 'audio'.
 *                                    Filters the library, so the frame offers nothing else.
 * @param {string}   [options.title]  Frame title. Defaults to one appropriate to `type`.
 * @param {string}   [options.button] Confirm-button text.
 */
export function openWpMedia( onSelect, options = {} ) {
	if ( ! window.wp?.media ) return;

	const type = options.type || 'image';

	const frame = window.wp.media( {
		title: options.title || defaultTitle( type ),
		multiple: false,
		library: { type },
		button: { text: options.button || __( 'Select', 'better-payment' ) },
	} );

	frame.on( 'select', () => {
		const att = frame.state().get( 'selection' ).first().toJSON();
		onSelect( {
			url: att.url,
			id: att.id,
			// Only images carry `sizes`; keep the key present so existing callers
			// that destructure it don't have to care what was picked.
			sizes: att.sizes || {},
			mime: att.mime || '',
		} );
	} );

	frame.open();
}

/**
 * Frame title for a media type.
 *
 * Translated per type rather than interpolated: "Select %s" cannot be translated
 * correctly without knowing the noun's gender and case in the target language.
 *
 * @param {string} type Media type.
 * @return {string} Translated title.
 */
function defaultTitle( type ) {
	if ( 'video' === type ) return __( 'Select Video', 'better-payment' );
	if ( 'audio' === type ) return __( 'Select Audio', 'better-payment' );
	return __( 'Select Image', 'better-payment' );
}
