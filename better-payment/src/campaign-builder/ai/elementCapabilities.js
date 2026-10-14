/**
 * What a given widget can actually have written into it.
 *
 * The per-element "AI quick edit" row used to offer Rewrite / Shorten / Improve /
 * More urgent to every element except a two-entry denylist (`social_sharing`,
 * `social_links`). A denylist has to be maintained by hand and was already wrong:
 * "Shorten" appeared on `progress_bar` and `campaign_summary`, widgets whose
 * content is live figures and a handful of toggles, where the request has no
 * meaning — and a model asked to shorten one anyway will find *something* to
 * write, which is exactly the irrelevant output the row was supposed to produce
 * less of.
 *
 * So derive it instead, from the element's own `settingsSchema`. A widget offers
 * copy actions when it has at least one free-text control; the prompt then names
 * those fields so the model edits them rather than guessing.
 *
 * Pure and registry-driven — a Pro widget, or one a third party registers, is
 * handled on the same terms as a built-in with no list to update.
 */

import { getElement } from '../fields/FieldRegistry';

/** Control types that hold prose a person would ask an AI to rewrite. */
const TEXT_CONTROL_TYPES = [ 'text', 'textarea', 'rich_text' ];

/**
 * Keys that are text-typed but are not prose — a URL, a CSS class, an id. Asking
 * a model to "make it more urgent" and handing it a link field is how a
 * fundraising page ends up pointing at an invented profile.
 */
const NON_PROSE_KEYS = [
	'url',
	'link',
	'href',
	'src',
	'css_class',
	'class',
	'id',
	'anchor',
	'facebook',
	'twitter',
	'instagram',
	'youtube',
	'linkedin',
	'website',
];

/**
 * Walk a settingsSchema, including `section` children, collecting controls.
 *
 * @param {Array} controls settingsSchema entries.
 * @param {Array} out      Accumulator.
 * @return {Array} Flat list of control objects.
 */
function flattenControls( controls, out = [] ) {
	for ( const control of controls ?? [] ) {
		if ( ! control || typeof control !== 'object' ) continue;
		if ( control.key ) out.push( control );
		if ( control.children?.length ) {
			flattenControls( control.children, out );
		}
	}
	return out;
}

/**
 * The free-text setting keys of an element type, in schema order.
 *
 * @param {string} type Element type key.
 * @return {Array<string>} Setting keys holding editable prose.
 */
export function textFieldKeys( type ) {
	const schema = getElement( type )?.settingsSchema;
	if ( ! schema?.length ) return [];

	return flattenControls( schema )
		.filter(
			( c ) =>
				TEXT_CONTROL_TYPES.includes( c.type ) &&
				! String( c.key ).startsWith( '_' ) &&
				! NON_PROSE_KEYS.includes( String( c.key ) )
		)
		.map( ( c ) => String( c.key ) );
}

/**
 * Human-readable labels for those fields, for use in a prompt.
 *
 * @param {string} type Element type key.
 * @return {Array<string>} Control labels, falling back to the raw key.
 */
export function textFieldLabels( type ) {
	const schema = getElement( type )?.settingsSchema;
	if ( ! schema?.length ) return [];

	const keys = textFieldKeys( type );
	const byKey = new Map(
		flattenControls( schema ).map( ( c ) => [ String( c.key ), c.label ] )
	);

	return keys.map( ( key ) => byKey.get( key ) ?? key );
}

/**
 * Whether the copy actions (Rewrite / Shorten / Improve / More urgent) make sense
 * for this widget.
 *
 * When the registry is unavailable the schema is empty for every type — the Jest
 * `FALLBACK` registry is exactly that case — and deriving would hide the row
 * everywhere. That is a worse failure than the old denylist, so fall back to it:
 * a missing schema means "unknown", not "no text".
 *
 * @param {string} type Element type key.
 * @return {boolean} True when the copy actions should be offered.
 */
export function hasEditableCopy( type ) {
	const schema = getElement( type )?.settingsSchema;
	if ( ! schema?.length ) {
		return ! [ 'social_sharing', 'social_links' ].includes( type );
	}
	return textFieldKeys( type ).length > 0;
}
