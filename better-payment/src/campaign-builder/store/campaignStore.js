import { v4 as uuidv4 } from 'uuid';
import { arrayMove } from '@dnd-kit/sortable';
import { getDefaultSettings } from '../fields/FieldRegistry';
import { applyOperations } from '../ai/operationExecutor';

/**
 * Campaign builder state — useReducer store.
 *
 * State shape:
 * {
 *   meta: { title, bpc_goal_amount, ... },
 *   layout: {
 *     layout: '1-column' | '2-column' | '3-column',
 *     columns: [{ id, label, width, elements: [{ id, type, settings }] }]
 *   },
 *   selectedElementId: null | string,
 *   selectedColumnId:  null | string,
 *   templateModalOpen: boolean,     // true on new campaign with no layout
 *   isDirty:  boolean,
 *   isSaving: boolean,
 *   saveError: null | string,
 *   postStatus: 'publish' | 'draft',
 *   viewUrl: string,           // permalink when published, '' otherwise
 * }
 */

export const initialState = {
	meta: {
		title: '',
		bpc_goal_amount: '',
		bpc_end_date: '',
		bpc_status: 'active',
		bpc_allow_custom_amount: 1,
		bpc_minimum_amount: '',
		bpc_form_page_id: 0,
		bpc_color_primary: '#6b63f6',
		bpc_color_background: '',
		bpc_css_class: '',
		bpc_template_key: '',
		bpc_suggested_amounts: [
			{ id: 'sa_1', amount: '5', description: '', is_default: false },
			{ id: 'sa_2', amount: '10', description: '', is_default: false },
			{ id: 'sa_3', amount: '15', description: '', is_default: true },
			{ id: 'sa_4', amount: '20', description: '', is_default: false },
		],
	},
	layout: {
		layout: '1-column',
		columns: [],
	},
	selectedElementId: null,
	selectedColumnId: null,
	templateModalOpen: false,
	templateChosen: false,
	appliedTemplateKey: null,
	isDirty: false,
	isSaving: false,
	saveError: null,
	postStatus: 'draft',
	viewUrl: '',
	isInitializing: false,
	// AI-change reversibility + change highlighting.
	aiUndoStack: [], // stack of { layout, meta } snapshots BEFORE each AI turn (undo target)
	aiRedoStack: [], // stack of { layout, meta } snapshots undone, ready to re-apply (redo target)
	highlightedElementIds: [], // elements just changed by AI (flashed in the canvas)
};

const AI_UNDO_LIMIT = 20;

/** Flat list of every element id in a layout. */
function collectElementIds( layout ) {
	const ids = [];
	( layout?.columns || [] ).forEach( ( c ) =>
		( c.elements || [] ).forEach( ( e ) => e?.id && ids.push( e.id ) )
	);
	return ids;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/** Convert old flat-array layout (from Charitable-style builder) to column schema. */
function migrateLegacyLayout( raw ) {
	if ( raw && typeof raw === 'object' && raw.columns ) {
		return raw; // already new format
	}
	const elements = Array.isArray( raw ) ? raw : [];
	return {
		layout: '1-column',
		columns: [
			{ id: 'main', label: 'Main Content', width: '100%', elements },
		],
	};
}

function makeElement( type, defaultSettings = {} ) {
	return {
		id: 'el_' + uuidv4().replace( /-/g, '' ).slice( 0, 12 ),
		type,
		settings: { ...defaultSettings },
	};
}

/**
 * Shape an id must have to be kept as-is: the server's `sanitize_id()` charset
 * (`[A-Za-z0-9_-]`) with a length cap. Anything else is re-minted.
 */
const ID_PATTERN = /^[\w-]{1,64}$/;

/**
 * Element ids share dnd-kit's id space with the column drop zones (`col-{id}`)
 * and the palette cards (`panel-{type}`), so an element id with either prefix
 * would be mistaken for one of those mid-drag.
 */
const RESERVED_ELEMENT_ID = /^(col|panel)-/;

function isUsableColumnId( id ) {
	return typeof id === 'string' && ID_PATTERN.test( id );
}

function isUsableElementId( id ) {
	return isUsableColumnId( id ) && ! RESERVED_ELEMENT_ID.test( id );
}

/**
 * Mint an id not already in `taken`, and record it there.
 *
 * @param {Function} mint  Returns a candidate id.
 * @param {Set}      taken Ids already in use; the minted id is added.
 * @return {string} The minted id.
 */
function mintUnique( mint, taken ) {
	let id = mint();
	while ( taken.has( id ) ) {
		id = mint();
	}
	taken.add( id );
	return id;
}

const mintElementId = () => 'el_' + uuidv4().replace( /-/g, '' ).slice( 0, 12 );
const mintColumnId = () => 'col_' + uuidv4().slice( 0, 8 );

/**
 * Give every column and element a usable, unique id, and merge registry
 * defaults under each element's settings.
 *
 * `keepElementIds` is the whole difference between the two callers:
 *
 * - **false — a template** (`freshIds`). Every element is new; template ids only
 *   need to be unique within the template, so they are all replaced.
 * - **true — an AI `set_layout`** (`stableIds`). The model echoes the page it
 *   was shown, and an echoed element is the SAME element. Re-minting its id made
 *   it a stranger to `MetaBox::enforce_pro_entitlement()`, which on a free or
 *   lapsed install saved a leftover FAQ / Donors Wall / Video as `[]` and
 *   destroyed the configuration the user built while subscribed. So an id is
 *   kept when it is well-formed and the first of its kind in the layout (element
 *   ids are unique across ALL columns); a missing, malformed or duplicate id is
 *   re-minted. Kept ids are decided before any id is minted, so a minted id can
 *   never take an id a later element legitimately echoed.
 *
 * Column ids are kept when well-formed and unique in both modes, which is what
 * templates always relied on.
 *
 * @param {Array}   columns        Raw columns.
 * @param {boolean} keepElementIds Keep echoed element ids (see above).
 * @return {Array} Normalized columns.
 */
function normalizeColumns( columns, keepElementIds ) {
	const keptElementIds = new Set();
	const keep = columns.map( ( col ) =>
		( col?.elements ?? [] ).map( ( el ) => {
			const id = el?.id;
			if (
				! keepElementIds ||
				! isUsableElementId( id ) ||
				keptElementIds.has( id )
			) {
				return false;
			}
			keptElementIds.add( id );
			return true;
		} )
	);

	const takenElementIds = new Set( keptElementIds );
	const takenColumnIds = new Set();

	return columns.map( ( col, c ) => {
		const columnId =
			isUsableColumnId( col?.id ) && ! takenColumnIds.has( col.id )
				? col.id
				: mintColumnId();
		takenColumnIds.add( columnId );

		return {
			...col,
			id: columnId,
			elements: ( col?.elements ?? [] ).map( ( el, e ) => ( {
				...el,
				id: keep[ c ][ e ]
					? el.id
					: mintUnique( mintElementId, takenElementIds ),
				// Merge registry defaults under the explicit settings so keys the
				// template / model omits (e.g. headline) fall back to element defaults.
				settings: {
					...getDefaultSettings( el?.type ),
					...( el?.settings ?? {} ),
				},
			} ) ),
		};
	} );
}

/**
 * Template application: every element is new, so every element id is fresh.
 *
 * @param {Array} columns Template columns.
 * @return {Array} Normalized columns.
 */
function freshIds( columns ) {
	return normalizeColumns( columns, false );
}

/**
 * AI whole-page replacement: echoed element ids survive (see normalizeColumns).
 *
 * @param {Array} columns Columns from a `set_layout` operation.
 * @return {Array} Normalized columns.
 */
function stableIds( columns ) {
	return normalizeColumns( columns, true );
}

function updateColumn( columns, columnId, updater ) {
	return columns.map( ( col ) =>
		col.id === columnId ? updater( col ) : col
	);
}

// ── Reducer ───────────────────────────────────────────────────────────────────

export function reducer( state, action ) {
	switch ( action.type ) {
		case 'SET_INITIALIZING':
			return { ...state, isInitializing: action.value };

		case 'LOAD_CAMPAIGN': {
			const layout = migrateLegacyLayout( action.layout );
			const loadedMeta = { ...action.meta };

			// Migrate old comma-separated amounts string to array format.
			const sa = loadedMeta.bpc_suggested_amounts;
			if ( typeof sa === 'string' ) {
				const parts = sa
					.split( ',' )
					.map( ( s ) => s.trim() )
					.filter( Boolean );
				loadedMeta.bpc_suggested_amounts = parts.map(
					( amount, i ) => ( {
						id: 'sa_' + ( i + 1 ),
						amount,
						description: '',
						is_default: false,
					} )
				);
			} else if ( ! Array.isArray( sa ) || sa.length === 0 ) {
				loadedMeta.bpc_suggested_amounts =
					initialState.meta.bpc_suggested_amounts;
			}

			return {
				...state,
				meta: { ...state.meta, ...loadedMeta },
				layout,
				templateModalOpen: false,
				templateChosen: !! loadedMeta.bpc_template_key,
				appliedTemplateKey: loadedMeta.bpc_template_key || null,
				isDirty: false,
				postStatus: action.postStatus || 'draft',
				viewUrl: action.viewUrl || '',
				isInitializing: false,
				// Loading a campaign starts a fresh history — you must not be able to
				// undo/redo into a different campaign's state.
				aiUndoStack: [],
				aiRedoStack: [],
			};
		}

		case 'UPDATE_META':
			return {
				...state,
				meta: { ...state.meta, ...action.payload },
				isDirty: true,
			};

		// ── Template ────────────────────────────────────────────────────────
		case 'OPEN_TEMPLATE_MODAL':
			return { ...state, templateModalOpen: true };

		case 'CLOSE_TEMPLATE_MODAL':
			return { ...state, templateModalOpen: false };

		case 'APPLY_TEMPLATE': {
			const tpl = action.template;
			// Blank layouts (1 Column, 2 Column, Custom) are structure-only —
			// never seed the campaign title with the layout's name.
			const defaultTitle =
				tpl.default_title ||
				( tpl.category === 'blank' ? '' : tpl.label );
			const metaUpdate = { bpc_template_key: tpl.key || '' };
			// Adopt the template's accent as the campaign primary colour.
			// `preview_color` is what the picker's own preview renders with
			// (RendererService::render_template_preview seeds it as
			// bpc_color_primary), and it was the only thing applying a template
			// did NOT copy across — so a template previewed in amber was applied
			// and then drew its progress bar in the #6b63f6 default. Applying a
			// template is already a wholesale replacement the user confirms, so
			// the accent travels with it. Blank layouts declare no
			// `preview_color` and leave the current colour alone.
			if ( tpl.preview_color )
				metaUpdate.bpc_color_primary = tpl.preview_color;
			// Only seed title from template when the user hasn't set one yet.
			if ( defaultTitle && ! state.meta.title )
				metaUpdate.title = defaultTitle;

			// Effective title after this action — used to seed campaign_title elements.
			const effectiveTitle = metaUpdate.title || state.meta.title;
			let columns = freshIds( tpl.columns );
			if ( effectiveTitle ) {
				columns = columns.map( ( col ) => ( {
					...col,
					elements: col.elements.map( ( el ) =>
						el.type === 'campaign_title' && ! el.settings.title
							? {
									...el,
									settings: {
										...el.settings,
										title: effectiveTitle,
									},
							  }
							: el
					),
				} ) );
			}
			const layout = { layout: tpl.layout, columns };

			// Per-button colour now travels with each Donate Button element in the
			// template layout (settings.button_color), so there's no campaign-wide
			// button colour to seed here.

			return {
				...state,
				layout,
				meta: { ...state.meta, ...metaUpdate },
				templateModalOpen: false,
				templateChosen: true,
				appliedTemplateKey: tpl.key || null,
				selectedElementId: null,
				selectedColumnId: null,
				isDirty: true,
			};
		}

		// ── Elements ────────────────────────────────────────────────────────
		case 'ADD_ELEMENT': {
			const elementDefaults = { ...( action.defaultSettings || {} ) };
			// Seed campaign_title widget with the current top-nav title on first drop.
			if (
				action.elementType === 'campaign_title' &&
				state.meta.title &&
				! elementDefaults.title
			) {
				elementDefaults.title = state.meta.title;
			}
			const newEl = makeElement( action.elementType, elementDefaults );
			const columns = updateColumn(
				state.layout.columns,
				action.columnId,
				( col ) => {
					const elements = [ ...col.elements ];
					const insertAt =
						action.insertAt != null
							? action.insertAt
							: elements.length;
					elements.splice( insertAt, 0, newEl );
					return { ...col, elements };
				}
			);
			return {
				...state,
				layout: { ...state.layout, columns },
				selectedElementId: newEl.id,
				selectedColumnId: action.columnId,
				isDirty: true,
			};
		}

		case 'REMOVE_ELEMENT': {
			const columns = updateColumn(
				state.layout.columns,
				action.columnId,
				( col ) => ( {
					...col,
					elements: col.elements.filter(
						( el ) => el.id !== action.elementId
					),
				} )
			);
			return {
				...state,
				layout: { ...state.layout, columns },
				selectedElementId:
					state.selectedElementId === action.elementId
						? null
						: state.selectedElementId,
				selectedColumnId:
					state.selectedElementId === action.elementId
						? null
						: state.selectedColumnId,
				isDirty: true,
			};
		}

		case 'DUPLICATE_ELEMENT': {
			const columns = updateColumn(
				state.layout.columns,
				action.columnId,
				( col ) => {
					const idx = col.elements.findIndex(
						( el ) => el.id === action.elementId
					);
					if ( idx === -1 ) return col;
					const original = col.elements[ idx ];
					const clone = {
						id: 'el_' + uuidv4().replace( /-/g, '' ).slice( 0, 12 ),
						type: original.type,
						settings: { ...original.settings },
					};
					const elements = [ ...col.elements ];
					elements.splice( idx + 1, 0, clone );
					return { ...col, elements };
				}
			);
			return {
				...state,
				layout: { ...state.layout, columns },
				isDirty: true,
			};
		}

		case 'REORDER_ELEMENTS': {
			const columns = updateColumn(
				state.layout.columns,
				action.columnId,
				( col ) => ( {
					...col,
					elements: arrayMove(
						col.elements,
						action.oldIndex,
						action.newIndex
					),
				} )
			);
			return {
				...state,
				layout: { ...state.layout, columns },
				isDirty: true,
			};
		}

		case 'MOVE_ELEMENT': {
			const { fromColumnId, toColumnId, elementId, insertAt } = action;
			let movedEl = null;

			let columns = state.layout.columns.map( ( col ) => {
				if ( col.id === fromColumnId ) {
					movedEl =
						col.elements.find( ( el ) => el.id === elementId ) ||
						null;
					return {
						...col,
						elements: col.elements.filter(
							( el ) => el.id !== elementId
						),
					};
				}
				return col;
			} );

			if ( ! movedEl ) return state;

			columns = columns.map( ( col ) => {
				if ( col.id === toColumnId ) {
					const elements = [ ...col.elements ];
					elements.splice(
						insertAt != null ? insertAt : elements.length,
						0,
						movedEl
					);
					return { ...col, elements };
				}
				return col;
			} );

			return {
				...state,
				layout: { ...state.layout, columns },
				selectedColumnId: toColumnId,
				isDirty: true,
			};
		}

		case 'UPDATE_ELEMENT_SETTINGS': {
			const columns = state.layout.columns.map( ( col ) => ( {
				...col,
				elements: col.elements.map( ( el ) =>
					el.id === action.elementId
						? {
								...el,
								settings: {
									...el.settings,
									...action.settings,
								},
						  }
						: el
				),
			} ) );
			return {
				...state,
				layout: { ...state.layout, columns },
				isDirty: true,
			};
		}

		// ── AI operations ───────────────────────────────────────────────────
		// Replace the whole layout (AI generation / full restructure). Registry
		// defaults are merged like APPLY_TEMPLATE, but unlike a template the
		// elements the model echoed back KEEP their ids — they are the same
		// elements, and the server matches locked Pro settings by id. Only a
		// missing, malformed or duplicate id is re-minted. See normalizeColumns().
		case 'REPLACE_LAYOUT': {
			const migrated = migrateLegacyLayout( action.layout );
			const columns = stableIds( migrated.columns || [] );
			return {
				...state,
				layout: { layout: migrated.layout || '1-column', columns },
				selectedElementId: null,
				selectedColumnId: null,
				// Building via AI/layout picker commits the campaign, same as
				// applying a template — this ungates the editor/save controls.
				templateChosen: true,
				isDirty: true,
			};
		}

		// Apply a batch of AI operations as one logical edit. The mapping from
		// operations to actions lives in ai/operationExecutor.js; it folds each
		// op through this same reducer so ids resolve against evolving state.
		// Also snapshots for AI-undo and marks the changed elements for highlighting.
		case 'AI_APPLY_OPERATIONS': {
			const ops = action.operations || [];
			const prevIds = collectElementIds( state.layout );
			const next = applyOperations( state, ops, reducer );

			// Which elements to flash: newly inserted + explicitly targeted (still
			// present). Skip on a full regeneration (set_layout) — the whole canvas
			// is new, so flashing everything would be noise.
			const opName = ( o ) => o && ( o.op || o.operation || o.type );
			const hasSetLayout = ops.some(
				( o ) => opName( o ) === 'set_layout'
			);
			let highlighted = [];
			if ( ! hasSetLayout ) {
				const nextIds = collectElementIds( next.layout );
				const inserted = nextIds.filter(
					( id ) => ! prevIds.includes( id )
				);
				const targeted = ops
					.map( ( o ) => o.element_id )
					.filter( ( id ) => id && nextIds.includes( id ) );
				highlighted = Array.from(
					new Set( [ ...inserted, ...targeted ] )
				);
			}

			const snapshot = { layout: state.layout, meta: state.meta };
			const aiUndoStack = [
				...( state.aiUndoStack || [] ),
				snapshot,
			].slice( -AI_UNDO_LIMIT );

			return {
				...next,
				templateChosen: true,
				isDirty: true,
				aiUndoStack,
				// A fresh AI change invalidates the redo future — you can no longer
				// "redo" onto a branch you've diverged from.
				aiRedoStack: [],
				highlightedElementIds: highlighted,
			};
		}

		// Revert the most recent AI change (restore the snapshot taken before it),
		// stashing the current (post-change) state onto the redo stack so REDO_AI
		// can bring it back.
		case 'UNDO_AI': {
			const stack = state.aiUndoStack || [];
			if ( ! stack.length ) return state;
			const snapshot = stack[ stack.length - 1 ];
			const redoSnapshot = { layout: state.layout, meta: state.meta };
			return {
				...state,
				layout: snapshot.layout,
				meta: snapshot.meta,
				aiUndoStack: stack.slice( 0, -1 ),
				aiRedoStack: [
					...( state.aiRedoStack || [] ),
					redoSnapshot,
				].slice( -AI_UNDO_LIMIT ),
				highlightedElementIds: [],
				selectedElementId: null,
				selectedColumnId: null,
				isDirty: true,
			};
		}

		// Re-apply the most recently undone AI change. Mirror of UNDO_AI: pop the
		// redo snapshot, restore it, and push the current (pre-redo) state back onto
		// the undo stack so the change stays reversible.
		case 'REDO_AI': {
			const stack = state.aiRedoStack || [];
			if ( ! stack.length ) return state;
			const snapshot = stack[ stack.length - 1 ];
			const undoSnapshot = { layout: state.layout, meta: state.meta };
			return {
				...state,
				layout: snapshot.layout,
				meta: snapshot.meta,
				aiRedoStack: stack.slice( 0, -1 ),
				aiUndoStack: [
					...( state.aiUndoStack || [] ),
					undoSnapshot,
				].slice( -AI_UNDO_LIMIT ),
				highlightedElementIds: [],
				selectedElementId: null,
				selectedColumnId: null,
				isDirty: true,
			};
		}

		case 'CLEAR_AI_HIGHLIGHT':
			return state.highlightedElementIds?.length
				? { ...state, highlightedElementIds: [] }
				: state;

		case 'SELECT_ELEMENT':
			return {
				...state,
				selectedElementId: action.elementId,
				selectedColumnId: action.columnId,
			};

		case 'DESELECT_ELEMENT':
			return {
				...state,
				selectedElementId: null,
				selectedColumnId: null,
			};

		// ── Save ────────────────────────────────────────────────────────────
		case 'SET_SAVING':
			return { ...state, isSaving: action.isSaving };

		case 'SAVE_SUCCESS':
			return {
				...state,
				isDirty: false,
				isSaving: false,
				saveError: null,
				postStatus: action.postStatus || state.postStatus,
				viewUrl: action.viewUrl || state.viewUrl,
			};

		case 'SAVE_ERROR':
			return { ...state, isSaving: false, saveError: action.error };

		default:
			return state;
	}
}
