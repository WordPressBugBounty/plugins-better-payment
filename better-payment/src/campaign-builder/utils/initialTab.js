/**
 * Which builder tab a saved campaign opens on.
 *
 * The Templates tab is for a campaign that has nothing to edit yet. A campaign
 * opens there only when it has neither a template key nor a saved layout with at
 * least one element. Reading the key alone sent every campaign built outside the
 * picker to Templates, even with a full page: one created over MCP
 * (`campaign_edit` → `set_layout` stores a layout and no key) or through the REST
 * create route with a `bpc_fields_layout`.
 */

/**
 * Whether a stored layout holds at least one element.
 *
 * @param {Object|Array|string|null|undefined} layout `bpc_fields_layout`, decoded or as JSON.
 * @return {boolean} True when some column has an element.
 */
export function layoutHasElements( layout ) {
	let value = layout;

	if ( typeof value === 'string' ) {
		try {
			value = JSON.parse( value );
		} catch ( e ) {
			return false;
		}
	}

	// A legacy layout is a flat list of elements (migrateLegacyLayout).
	if ( Array.isArray( value ) ) {
		return value.length > 0;
	}

	const columns = Array.isArray( value?.columns ) ? value.columns : [];

	return columns.some(
		( column ) =>
			Array.isArray( column?.elements ) && column.elements.length > 0
	);
}

/**
 * Whether a loaded campaign opens on the Templates tab.
 *
 * @param {Object|null|undefined} meta Campaign meta from `GET /campaigns/{id}`.
 * @return {boolean} True when there is nothing to edit yet.
 */
export function opensOnTemplates( meta ) {
	if ( meta?.bpc_template_key ) {
		return false;
	}

	return ! layoutHasElements( meta?.bpc_fields_layout );
}
