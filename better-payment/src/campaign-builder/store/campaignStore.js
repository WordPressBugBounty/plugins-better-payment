import { v4 as uuidv4 } from 'uuid';
import { arrayMove } from '@dnd-kit/sortable';

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
        bpc_color_button:  '',
        bpc_css_class: '',
        bpc_template_key: '',
        bpc_suggested_amounts: [
            { id: 'sa_1', amount: '5',  description: '', is_default: false },
            { id: 'sa_2', amount: '10', description: '', is_default: false },
            { id: 'sa_3', amount: '15', description: '', is_default: true  },
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
};

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
    return { id: 'el_' + uuidv4().replace( /-/g, '' ).slice( 0, 12 ), type, settings: { ...defaultSettings } };
}

function freshIds( columns ) {
    return columns.map( ( col ) => ( {
        ...col,
        id: col.id || 'col_' + uuidv4().slice( 0, 8 ),
        elements: ( col.elements || [] ).map( ( el ) => ( {
            ...el,
            id: 'el_' + uuidv4().replace( /-/g, '' ).slice( 0, 12 ),
        } ) ),
    } ) );
}

function updateColumn( columns, columnId, updater ) {
    return columns.map( ( col ) => col.id === columnId ? updater( col ) : col );
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
                const parts = sa.split( ',' ).map( ( s ) => s.trim() ).filter( Boolean );
                loadedMeta.bpc_suggested_amounts = parts.map( ( amount, i ) => ( {
                    id: 'sa_' + ( i + 1 ),
                    amount,
                    description: '',
                    is_default: false,
                } ) );
            } else if ( ! Array.isArray( sa ) || sa.length === 0 ) {
                loadedMeta.bpc_suggested_amounts = initialState.meta.bpc_suggested_amounts;
            }

            return {
                ...state,
                meta: { ...state.meta, ...loadedMeta },
                layout,
                templateModalOpen: false,
                templateChosen: !!loadedMeta.bpc_template_key,
                appliedTemplateKey: loadedMeta.bpc_template_key || null,
                isDirty: false,
                postStatus: action.postStatus || 'draft',
                viewUrl: action.viewUrl || '',
                isInitializing: false,
            };
        }

        case 'UPDATE_META':
            return { ...state, meta: { ...state.meta, ...action.payload }, isDirty: true };

        // ── Template ────────────────────────────────────────────────────────
        case 'OPEN_TEMPLATE_MODAL':
            return { ...state, templateModalOpen: true };

        case 'CLOSE_TEMPLATE_MODAL':
            return { ...state, templateModalOpen: false };

        case 'APPLY_TEMPLATE': {
            const tpl = action.template;
            const defaultTitle = tpl.default_title || tpl.label;
            const metaUpdate = { bpc_template_key: tpl.key || '' };
            // Only seed title from template when the user hasn't set one yet.
            if ( defaultTitle && ! state.meta.title ) metaUpdate.title = defaultTitle;

            // Effective title after this action — used to seed campaign_title elements.
            const effectiveTitle = metaUpdate.title || state.meta.title;
            let columns = freshIds( tpl.columns );
            if ( effectiveTitle ) {
                columns = columns.map( ( col ) => ( {
                    ...col,
                    elements: col.elements.map( ( el ) =>
                        el.type === 'campaign_title' && ! el.settings.title
                            ? { ...el, settings: { ...el.settings, title: effectiveTitle } }
                            : el
                    ),
                } ) );
            }
            const layout = { layout: tpl.layout, columns };

            // Set default button color per template.
            const templateButtonColors = {
                'blank-1col':       '#5AA152',
                'blank-2col':       '#5AA152',
                'blank-3col':       '#5AA152',
                'charity-basic':    '#B49A5F',
                'medical-relief':   '#7A8347',
                'education-fund':   '#8FA040',
                'golf-destinations':'#B8A46A',
                'disaster-relief':  '#c0392b',
            };
            if ( templateButtonColors[ tpl.key ] ) {
                metaUpdate.bpc_color_button = templateButtonColors[ tpl.key ];
            }

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
            if ( action.elementType === 'campaign_title' && state.meta.title && ! elementDefaults.title ) {
                elementDefaults.title = state.meta.title;
            }
            const newEl = makeElement( action.elementType, elementDefaults );
            const columns = updateColumn( state.layout.columns, action.columnId, ( col ) => {
                const elements = [ ...col.elements ];
                const insertAt = action.insertAt != null ? action.insertAt : elements.length;
                elements.splice( insertAt, 0, newEl );
                return { ...col, elements };
            } );
            return {
                ...state,
                layout: { ...state.layout, columns },
                selectedElementId: newEl.id,
                selectedColumnId: action.columnId,
                isDirty: true,
            };
        }

        case 'REMOVE_ELEMENT': {
            const columns = updateColumn( state.layout.columns, action.columnId, ( col ) => ( {
                ...col,
                elements: col.elements.filter( ( el ) => el.id !== action.elementId ),
            } ) );
            return {
                ...state,
                layout: { ...state.layout, columns },
                selectedElementId: state.selectedElementId === action.elementId ? null : state.selectedElementId,
                selectedColumnId: state.selectedElementId === action.elementId ? null : state.selectedColumnId,
                isDirty: true,
            };
        }

        case 'DUPLICATE_ELEMENT': {
            const columns = updateColumn( state.layout.columns, action.columnId, ( col ) => {
                const idx = col.elements.findIndex( ( el ) => el.id === action.elementId );
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
            } );
            return { ...state, layout: { ...state.layout, columns }, isDirty: true };
        }

        case 'REORDER_ELEMENTS': {
            const columns = updateColumn( state.layout.columns, action.columnId, ( col ) => ( {
                ...col,
                elements: arrayMove( col.elements, action.oldIndex, action.newIndex ),
            } ) );
            return { ...state, layout: { ...state.layout, columns }, isDirty: true };
        }

        case 'MOVE_ELEMENT': {
            const { fromColumnId, toColumnId, elementId, insertAt } = action;
            let movedEl = null;

            let columns = state.layout.columns.map( ( col ) => {
                if ( col.id === fromColumnId ) {
                    movedEl = col.elements.find( ( el ) => el.id === elementId ) || null;
                    return { ...col, elements: col.elements.filter( ( el ) => el.id !== elementId ) };
                }
                return col;
            } );

            if ( ! movedEl ) return state;

            columns = columns.map( ( col ) => {
                if ( col.id === toColumnId ) {
                    const elements = [ ...col.elements ];
                    elements.splice( insertAt != null ? insertAt : elements.length, 0, movedEl );
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
                        ? { ...el, settings: { ...el.settings, ...action.settings } }
                        : el
                ),
            } ) );
            return { ...state, layout: { ...state.layout, columns }, isDirty: true };
        }

        case 'SELECT_ELEMENT':
            return {
                ...state,
                selectedElementId: action.elementId,
                selectedColumnId: action.columnId,
            };

        case 'DESELECT_ELEMENT':
            return { ...state, selectedElementId: null, selectedColumnId: null };

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
