/**
 * Builder Operations → reducer actions.
 *
 * The AI never touches React state directly. It returns a list of validated
 * Operations (see includes/AI/Operations/), and this module maps each one onto
 * the SAME reducer actions a human's clicks dispatch. Element/column ids are
 * resolved against live state here, so the model only needs to name an
 * element_id — never a column or index it can't see.
 *
 * `applyOperations` folds the whole batch through the reducer so ids are
 * resolved against the evolving state (an insert followed by an update sees the
 * inserted element). It is a pure function — the reducer is passed in to avoid a
 * circular import with campaignStore.
 */

import { getElement, getDefaultSettings } from '../fields/FieldRegistry';
import { getGlobalCurrencySymbol } from '../utils/currency';

/**
 * Registry defaults under the model's own settings, so a key the model omitted
 * falls back to the element's default rather than to nothing.
 *
 * This is the same merge `stableIds()` does for `set_layout`, and its absence here
 * is why an AI-inserted widget could land on the canvas rendering *nothing*. The
 * damage is worst for Pro, whose widgets are the ones with a meaningful default
 * payload: Pro's `ProWidgetGuidance::guard_video_urls()` (on `better_payment/ai/generated_operations`) strips an invented `url` expecting the schema
 * default to take over, and `faq` ships two sample questions — but ADD_ELEMENT
 * takes `defaultSettings` verbatim and never consults the registry, so both
 * arrived empty, rendered `''`, and were shown as a blank placeholder on a site
 * that has paid for a fully working widget.
 *
 * Free elements gain the same fix (a `donation_form` inserted with no settings
 * now keeps its "Donate Now" label); Pro is simply where it was visible.
 *
 * @param {string} type     Element type key.
 * @param {Object} settings Settings the model supplied.
 * @return {Object} Merged settings.
 */
function withDefaults( type, settings ) {
	return { ...getDefaultSettings( type ), ...( settings ?? {} ) };
}

function findElement( state, elementId ) {
	const columns = state.layout?.columns || [];
	for ( const col of columns ) {
		const idx = ( col.elements || [] ).findIndex(
			( el ) => el.id === elementId
		);
		if ( idx !== -1 ) {
			return {
				columnId: col.id,
				index: idx,
				element: col.elements[ idx ],
			};
		}
	}
	return null;
}

function columnExists( state, columnId ) {
	return ( state.layout?.columns || [] ).some( ( c ) => c.id === columnId );
}

function firstColumnId( state ) {
	const columns = state.layout?.columns || [];
	return columns.length ? columns[ 0 ].id : null;
}

function clamp( n, min, max ) {
	return Math.max( min, Math.min( max, n ) );
}

/**
 * Translate a single operation into zero or more reducer actions, using the
 * current (accumulated) state to resolve ids and positions.
 *
 * @param {Object} op    An operation, wire shape `{ op: 'insert_block', ... }`.
 * @param {Object} state Current accumulator state.
 * @return {Array<Object>} Reducer action objects.
 */
export function operationToActions( op, state ) {
	if ( ! op || typeof op !== 'object' ) return [];
	const name = op.op || op.operation || op.type;

	switch ( name ) {
		case 'set_layout':
			return [
				{
					type: 'REPLACE_LAYOUT',
					layout: { layout: op.layout, columns: op.columns || [] },
				},
			];

		case 'insert_block': {
			const columnId =
				op.column_id && columnExists( state, op.column_id )
					? op.column_id
					: firstColumnId( state );
			// No column exists yet (e.g. the model used insert_block on an empty
			// campaign instead of set_layout) — seed a single column holding this
			// element so it isn't silently lost.
			if ( ! columnId ) {
				return [
					{
						type: 'REPLACE_LAYOUT',
						layout: {
							layout: '1-column',
							columns: [
								{
									id: 'main',
									label: 'Main Content',
									width: '100%',
									elements: [
										{
											type: op.type,
											settings: op.settings || {},
										},
									],
								},
							],
						},
					},
				];
			}
			return [
				{
					type: 'ADD_ELEMENT',
					columnId,
					elementType: op.type,
					defaultSettings: withDefaults( op.type, op.settings ),
					insertAt: op.index != null ? op.index : undefined,
				},
			];
		}

		case 'update_block': {
			const loc = findElement( state, op.element_id );
			if ( ! loc ) return [];
			return [
				{
					type: 'UPDATE_ELEMENT_SETTINGS',
					elementId: op.element_id,
					settings: op.settings || {},
				},
			];
		}

		case 'delete_block': {
			const loc = findElement( state, op.element_id );
			if ( ! loc ) return [];
			return [
				{
					type: 'REMOVE_ELEMENT',
					columnId: loc.columnId,
					elementId: op.element_id,
				},
			];
		}

		case 'move_block': {
			const loc = findElement( state, op.element_id );
			if ( ! loc || ! columnExists( state, op.to_column_id ) ) return [];
			const toCol = state.layout.columns.find(
				( c ) => c.id === op.to_column_id
			);
			const target = op.index != null ? op.index : toCol.elements.length;

			if ( loc.columnId === op.to_column_id ) {
				const oldIndex = loc.index;
				const newIndex = clamp( target, 0, toCol.elements.length - 1 );
				if ( oldIndex === newIndex ) return [];
				return [
					{
						type: 'REORDER_ELEMENTS',
						columnId: loc.columnId,
						oldIndex,
						newIndex,
					},
				];
			}
			return [
				{
					type: 'MOVE_ELEMENT',
					fromColumnId: loc.columnId,
					toColumnId: op.to_column_id,
					elementId: op.element_id,
					insertAt: clamp( target, 0, toCol.elements.length ),
				},
			];
		}

		case 'update_meta':
			if ( ! op.key ) return [];
			return [
				{ type: 'UPDATE_META', payload: { [ op.key ]: op.value } },
			];

		case 'set_colors': {
			const payload = {};
			if ( op.primary ) payload.bpc_color_primary = op.primary;
			if ( op.background ) payload.bpc_color_background = op.background;
			return Object.keys( payload ).length
				? [ { type: 'UPDATE_META', payload } ]
				: [];
		}

		case 'set_donation_amounts':
			if ( ! Array.isArray( op.amounts ) ) return [];
			return [
				{
					type: 'UPDATE_META',
					payload: { bpc_suggested_amounts: op.amounts },
				},
			];

		case 'replace_image': {
			const loc = findElement( state, op.element_id );
			if ( ! loc ) return [];
			return [
				{
					type: 'UPDATE_ELEMENT_SETTINGS',
					elementId: op.element_id,
					settings: {
						src: op.src || '',
						src_id: op.src_id || 0,
						src_sizes: op.src_sizes || {},
						alt: op.alt || '',
					},
				},
			];
		}

		// generate_image is fulfilled asynchronously by the panel (it needs a
		// network round-trip to create the attachment), so it is a no-op here.
		case 'generate_image':
			return [];

		default:
			return [];
	}
}

/** The operation name in wire form, tolerant of aliases. */
export function operationName( op ) {
	return op && ( op.op || op.operation || op.type );
}

/**
 * Fold a batch of operations through the reducer.
 *
 * @param {Object}   state      Starting state.
 * @param {Array}    operations Validated operations from the server.
 * @param {Function} reduceFn   The campaign reducer `(state, action) => state`.
 * @return {Object} New state.
 */
export function applyOperations( state, operations, reduceFn ) {
	if ( ! Array.isArray( operations ) ) return state;
	return operations.reduce( ( acc, op ) => {
		const actions = operationToActions( op, acc );
		return actions.reduce( ( s, action ) => reduceFn( s, action ), acc );
	}, state );
}

// ---------------------------------------------------------------- describing
//
// Everything below turns an operation into words a fundraiser can read. None of
// it may leak an internal identifier: an operation carries `bpc_goal_amount` and
// `progress_bar` because those are storage keys, and a panel that prints them
// verbatim ("Set bpc_goal_amount") reads as a debug log, not a product. The
// human names already exist — the element registry has a `label` per type, and
// the meta keys are a short fixed list — they were simply never consulted here.

/**
 * Display names for the campaign meta keys the AI may write.
 *
 * Deliberately a lookup and not a `bpc_`-stripping humanizer: `bpc_goal_amount`
 * humanizes to "Goal amount", but what the user set in the sidebar is called the
 * fundraising goal. Unknown keys still fall back to the humanizer so a new key
 * degrades to something readable rather than to a raw slug.
 */
const META_LABELS = {
	title: 'Campaign title',
	bpc_goal_amount: 'Fundraising goal',
	bpc_end_date: 'Campaign end date',
	bpc_status: 'Campaign status',
	bpc_allow_custom_amount: 'Custom donation amount',
	bpc_minimum_amount: 'Minimum donation',
	bpc_color_primary: 'Primary colour',
	bpc_color_background: 'Background colour',
	bpc_css_class: 'CSS class',
	bpc_suggested_amounts: 'Suggested donation amounts',
};

/** `goal_label` → `Goal label`. Last-resort readability for an unmapped key. */
function humanizeKey( key ) {
	const words = String( key ?? '' )
		.replace( /^bpc_/, '' )
		.replace( /[_-]+/g, ' ' )
		.trim();
	return words ? words.charAt( 0 ).toUpperCase() + words.slice( 1 ) : '';
}

/** Display name for a campaign meta key. */
export function metaLabel( key ) {
	return META_LABELS[ key ] ?? humanizeKey( key );
}

/**
 * Display name for an element, resolved against the live layout.
 *
 * An operation names an `element_id`, never a type — so the type comes from
 * state and the label from the registry. Falls back to a generic noun when the
 * element has since been deleted, rather than showing the id.
 */
export function elementLabel( state, elementId ) {
	const found = findElement( state, elementId );
	const type = found?.element?.type;
	if ( ! type ) return 'this element';
	return getElement?.( type )?.label ?? humanizeKey( type );
}

/** Longest preview a report row shows before it is clipped. */
const VALUE_MAX = 70;

/**
 * The keys worth showing for one item of a repeated value, in order.
 *
 * A repeater's own `titleField` is the same idea — an FAQ row is its question,
 * a link is its label. `amount` is handled before this list, because it is a
 * number that wants a currency symbol rather than a bare string.
 */
const ITEM_LABEL_KEYS = [
	'question',
	'label',
	'title',
	'name',
	'heading',
	'headline',
	'text',
	'description',
	'alt',
	'url',
];

/**
 * `10` → `10`, `10.5` → `10.5`, `1500` → `1,500`; `''` when not a number.
 *
 * @param {*} value The raw amount, as the model sent it (often a string).
 * @return {string} Display text, or `''`.
 */
function formatNumber( value ) {
	const n = Number( value );
	if ( ! Number.isFinite( n ) ) return '';

	// No trailing `.00`, same rule as Campaign\Support\Money::format() on the
	// PHP side: a preset of `25` is written "25", never "25.00".
	const rounded = Math.round( n * 100 ) / 100;
	return rounded.toLocaleString( 'en-US', {
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	} );
}

/**
 * One item of a repeated setting as display text; `null` when it has none.
 *
 * `bpc_suggested_amounts` is an array of `{ id, amount, description,
 * is_default }` objects (`OperationValidator::validate_donation_amounts()`), and
 * an FAQ's `items` an array of `{ question, answer, open }`. Joining such an
 * array straight away stringifies each entry through `Object.prototype.toString`,
 * which is how the analysis report came to offer "Suggested donation amounts —
 * [object Object], [object Object]" beside an Apply button: the row named the
 * field correctly and then described what it would write as nothing at all.
 *
 * @param {*} item One entry of the array.
 * @return {string|null} Display text, or `null` when the entry says nothing.
 */
function formatItem( item ) {
	if ( item === null || item === undefined ) return null;
	if ( Array.isArray( item ) ) return null;

	if ( typeof item !== 'object' ) {
		const text = String( item ).trim();
		return text ? text : null;
	}

	// A donation preset is its amount, with the site's currency symbol — the
	// figure is the whole point of the row, and `25` alone reads as a count.
	if (
		item.amount !== undefined &&
		item.amount !== null &&
		item.amount !== ''
	) {
		const amount = formatNumber( item.amount );
		if ( amount ) return `${ getGlobalCurrencySymbol() }${ amount }`;
	}

	for ( const key of ITEM_LABEL_KEYS ) {
		const candidate = item[ key ];
		if ( typeof candidate === 'string' && candidate.trim() ) {
			return candidate.trim();
		}
		if ( typeof candidate === 'number' && Number.isFinite( candidate ) ) {
			return String( candidate );
		}
	}

	// An object with nothing readable in it is dropped rather than described:
	// "[object Object]" is worse than a row that names only its field.
	return null;
}

/**
 * Clip a preview, so one long value can't push the Apply button off the row.
 *
 * @param {*} text Display text.
 * @return {string|null} The text, clipped, or `null` when there is none.
 */
function clip( text ) {
	const trimmed = String( text ?? '' ).trim();
	if ( ! trimmed ) return null;
	return trimmed.length > VALUE_MAX
		? `${ trimmed.slice( 0, VALUE_MAX ) }…`
		: trimmed;
}

/** A setting value as short display text; `null` when it isn't worth showing. */
function formatValue( value ) {
	if ( value === true ) return 'On';
	if ( value === false ) return 'Off';
	if ( value === null || value === undefined ) return null;

	if ( Array.isArray( value ) ) {
		const items = value.map( formatItem ).filter( Boolean );
		if ( ! items.length ) return null;

		// Clipped like any other value: eight presets or thirty FAQ questions
		// would otherwise run the row off the panel.
		return clip( items.join( ', ' ) );
	}

	if ( typeof value === 'object' ) {
		// A lone object (not in an array) gets the same treatment, so a setting
		// holding one record is described rather than skipped.
		return clip( formatItem( value ) );
	}

	return clip( value );
}

/**
 * "Headline: Every gift counts · Goal label: Our goal" — what an update_block
 * would actually change, so an Apply button is auditable before it is clicked.
 * Caps at two entries; a settings dump is as unreadable as a JSON one.
 */
function summarizeSettings( settings ) {
	const entries = Object.entries( settings || {} )
		.map( ( [ key, value ] ) => {
			const shown = formatValue( value );
			return shown === null
				? null
				: `${ humanizeKey( key ) }: ${ shown }`;
		} )
		.filter( Boolean );

	if ( ! entries.length ) return '';
	if ( entries.length <= 2 ) return entries.join( ' · ' );
	return `${ entries.slice( 0, 2 ).join( ' · ' ) } +${
		entries.length - 2
	} more`;
}

/** Named settings a suggestion would write, and the values it would write. */
function settingFields( settings ) {
	const fields = [];
	const values = [];
	Object.entries( settings || {} ).forEach( ( [ key, value ] ) => {
		const shown = formatValue( value );
		if ( shown === null ) return;
		fields.push( humanizeKey( key ) );
		values.push( shown );
	} );
	return { fields, values };
}

/**
 * Which part of the campaign a suggestion is about.
 *
 * `groupKey` is what the report's action list groups on, so it must be stable
 * across suggestions touching the same thing — the element id where there is
 * one, a constant otherwise. Campaign-wide settings (goal, colours, donation
 * amounts) belong to no widget, so they collect under one heading rather than
 * each inventing their own.
 *
 * @param {Object} op    The operation.
 * @param {Object} state Live builder state.
 * @return {{groupKey: string, group: string, fields: string[], values: string[]}} Target.
 */
export function suggestionTarget( op, state ) {
	const name = operationName( op );
	const settings = () => settingFields( op.settings );

	switch ( name ) {
		case 'update_block': {
			const { fields, values } = settings();
			return {
				groupKey: `el:${ op.element_id }`,
				group: elementLabel( state, op.element_id ),
				fields,
				values,
			};
		}
		case 'replace_image':
			return {
				groupKey: `el:${ op.element_id }`,
				group: elementLabel( state, op.element_id ),
				fields: [ 'Image' ],
				values: [ formatValue( op.alt ) ?? '' ].filter( Boolean ),
			};
		case 'delete_block':
		case 'move_block':
			return {
				groupKey: `el:${ op.element_id }`,
				group: elementLabel( state, op.element_id ),
				fields: [],
				values: [],
			};
		case 'update_meta':
			return {
				groupKey: 'campaign',
				group: 'Campaign settings',
				fields: [ metaLabel( op.key ) ],
				values: [ formatValue( op.value ) ?? '' ].filter( Boolean ),
			};
		case 'set_donation_amounts':
			return {
				groupKey: 'campaign',
				group: 'Campaign settings',
				fields: [ metaLabel( 'bpc_suggested_amounts' ) ],
				values: [ formatValue( op.amounts ) ?? '' ].filter( Boolean ),
			};
		case 'set_colors':
			return {
				groupKey: 'campaign',
				group: 'Campaign settings',
				fields: [
					op.primary ? metaLabel( 'bpc_color_primary' ) : '',
					op.background ? metaLabel( 'bpc_color_background' ) : '',
				].filter( Boolean ),
				values: [ op.primary ?? '', op.background ?? '' ].filter(
					Boolean
				),
			};
		default:
			return {
				groupKey: 'other',
				group: 'Other changes',
				fields: [],
				values: [],
			};
	}
}

/**
 * The report's closing action list: which fields need work, grouped by what
 * they belong to.
 *
 * The findings above are prose — they say a widget reads weakly, not which
 * field to open. This turns the same suggestions into a checklist the user can
 * work down, naming the field ("Button text") under its widget ("Donate
 * Button"), each with its own Apply.
 *
 * Order is first-appearance, not alphabetical: the model reports its findings
 * in priority order, and re-sorting them would silently discard that ranking.
 * Already-applied suggestions stay in place, marked — a checklist that deletes
 * finished rows loses the record of what was done.
 *
 * @param {Array}  suggestions `[ { op, applied } ]` as held on the message.
 * @param {Object} state       Live builder state.
 * @return {Array<{key: string, label: string, items: Array}>} Groups, in order.
 */
export function groupSuggestions( suggestions, state ) {
	const groups = [];
	const byKey = new Map();

	( suggestions || [] ).forEach( ( suggestion, index ) => {
		const { op, applied } = suggestion ?? {};
		if ( ! op ) return;

		const target = suggestionTarget( op, state );
		const { title, detail } = describeSuggestion( op, state );

		if ( ! byKey.has( target.groupKey ) ) {
			const group = {
				key: target.groupKey,
				label: target.group,
				items: [],
			};
			byKey.set( target.groupKey, group );
			groups.push( group );
		}

		byKey.get( target.groupKey ).items.push( {
			index,
			applied: !! applied,
			// Fall back to the imperative sentence for operations that name no
			// field at all (a move or a delete) — a blank row would be worse.
			label: target.fields.length ? target.fields.join( ', ' ) : title,
			detail: target.values.length ? target.values.join( ' · ' ) : detail,
		} );
	} );

	return groups;
}

/**
 * A pending suggestion, as `{ title, detail }`.
 *
 * Titles name the widget and are written in the **imperative** — the card sits
 * next to an Apply button, so "Updated a block" claimed work that had not
 * happened yet and made the button look like a no-op.
 *
 * @param {Object} op    The operation.
 * @param {Object} state Live builder state, for resolving element ids to names.
 * @return {{title: string, detail: string}} Card copy.
 */
export function describeSuggestion( op, state ) {
	const name = operationName( op );
	const element = () => elementLabel( state, op.element_id );

	switch ( name ) {
		case 'update_block':
			return {
				title: `Update ${ element() }`,
				detail: summarizeSettings( op.settings ),
			};
		case 'delete_block':
			return { title: `Remove ${ element() }`, detail: '' };
		case 'move_block':
			return { title: `Move ${ element() }`, detail: '' };
		case 'replace_image':
			return {
				title: `Replace the image in ${ element() }`,
				detail: formatValue( op.alt ) ?? '',
			};
		case 'generate_image':
			return {
				title: 'Generate a new image',
				detail: formatValue( op.prompt ) ?? '',
			};
		case 'update_meta':
			return {
				title: `Set ${ metaLabel( op.key ) }`,
				detail: formatValue( op.value ) ?? '',
			};
		case 'set_donation_amounts':
			return {
				title: 'Update the donation amounts',
				detail: formatValue( op.amounts ) ?? '',
			};
		case 'set_colors':
			return {
				title: 'Update the campaign colours',
				detail: [
					op.primary ? `Primary: ${ op.primary }` : '',
					op.background ? `Background: ${ op.background }` : '',
				]
					.filter( Boolean )
					.join( ' · ' ),
			};
		case 'insert_block':
			return {
				title: `Add ${
					getElement?.( op.type )?.label ?? humanizeKey( op.type )
				}`,
				detail: '',
			};
		case 'set_layout':
			return { title: 'Rebuild the campaign layout', detail: '' };
		default:
			return { title: humanizeKey( name ) || 'Change', detail: '' };
	}
}

/**
 * Past-tense summary of an applied operation, for the panel's change log.
 *
 * @param {Object} op    The operation.
 * @param {Object} state Live builder state, for resolving element ids to names.
 */
export function describeOperation( op, state ) {
	const name = operationName( op );
	const element = () => elementLabel( state, op.element_id );

	switch ( name ) {
		case 'set_layout':
			return 'Rebuilt the campaign layout';
		case 'insert_block':
			return `Added ${
				getElement?.( op.type )?.label ?? humanizeKey( op.type )
			}`;
		case 'update_block':
			return `Updated ${ element() }`;
		case 'delete_block':
			return `Removed ${ element() }`;
		case 'move_block':
			return `Moved ${ element() }`;
		case 'update_meta':
			return `Set ${ metaLabel( op.key ) }`;
		case 'set_colors':
			return 'Updated the campaign colours';
		case 'set_donation_amounts':
			return 'Updated the donation amounts';
		case 'replace_image':
			return 'Replaced an image';
		case 'generate_image':
			return 'Generated an image';
		default:
			return humanizeKey( name ) || 'Change';
	}
}
