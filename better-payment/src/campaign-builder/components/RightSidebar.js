import { useState, useEffect, useRef, useCallback, createPortal } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDraggable } from '@dnd-kit/core';
import { getAllElements, getElement } from '../fields/FieldRegistry';
import SuggestedAmountsEditor from './SuggestedAmountsEditor';
import { openWpMedia } from '../utils/media';
import ColorPicker from './ColorPicker';

const CUSTOM_ICONS = {
    progress_bar: (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="10" width="20" height="4" rx="2" fill="currentColor" fillOpacity="0.22"/>
            <rect x="2" y="10" width="12" height="4" rx="2" fill="currentColor"/>
            <circle cx="14" cy="12" r="3" fill="currentColor"/>
        </svg>
    ),
    donation_form: (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 9c-.5-1.3-1.8-2.2-3.2-2.2C6.9 6.8 5.4 8.3 5.4 10.1c0 2.3 2.3 4.4 6.6 7.1 4.3-2.7 6.6-4.8 6.6-7.1 0-1.8-1.5-3.3-3.4-3.3C13.8 6.8 12.5 7.7 12 9z" fill="currentColor"/>
            <rect x="6" y="18.5" width="12" height="3" rx="1.5" stroke="currentColor" strokeWidth="1.4" fill="none"/>
            <line x1="9.5" y1="18.5" x2="9.5" y2="17" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
            <line x1="12"  y1="18.5" x2="12"  y2="16.5" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
            <line x1="14.5" y1="18.5" x2="14.5" y2="17" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
        </svg>
    ),
};

/**
 * Right sidebar — two tabs:
 *   "Input Fields"        — 2-col grid of draggable element cards
 *   "Input Customization" — schema-driven settings for the selected element
 */
export default function RightSidebar( {
    meta,
    layout,
    selectedElementId,
    selectedColumnId,
    dispatch,
} ) {
    const [ activeTab, setActiveTab ] = useState( 'fields' );

    useEffect( () => {
        if ( selectedElementId ) {
            setActiveTab( 'customization' );
        }
    }, [ selectedElementId ] );

    return (
        <div className="bp-cb-right-sidebar">
            <div className="bp-cb-right-sidebar__tabs">
                <button
                    className={ `bp-cb-sidebar-tab${ activeTab === 'fields' ? ' is-active' : '' }` }
                    onClick={ () => setActiveTab( 'fields' ) }
                >
                    { __( 'Elements', 'better-payment' ) }
                </button>
                <button
                    className={ `bp-cb-sidebar-tab${ activeTab === 'customization' ? ' is-active' : '' }` }
                    onClick={ () => setActiveTab( 'customization' ) }
                >
                    { __( 'Customize Element', 'better-payment' ) }
                </button>
            </div>

            <div className="bp-cb-right-sidebar__body">
                { activeTab === 'fields' ? (
                    <FieldsGrid />
                ) : (
                    <CustomizationPanel
                        meta={ meta }
                        layout={ layout }
                        selectedElementId={ selectedElementId }
                        selectedColumnId={ selectedColumnId }
                        dispatch={ dispatch }
                    />
                ) }
            </div>
        </div>
    );
}

// ── Input Fields tab ───────────────────────────────────────────────────────────

function FieldsGrid() {
    const elements = getAllElements();

    return (
        <div className="bp-cb-fields-grid">
            { elements.map( ( el ) => (
                <DraggableFieldCard key={ el.type } element={ el } />
            ) ) }
        </div>
    );
}

function DraggableFieldCard( { element } ) {
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable( {
        id: `panel-${ element.type }`,
        data: {
            source: 'panel',
            elementType: element.type,
            defaultSettings: element.defaultSettings,
        },
    } );

    return (
        <div
            ref={ setNodeRef }
            className={ `bp-cb-field-card${ isDragging ? ' is-dragging' : '' }` }
            title={ element.label }
            { ...listeners }
            { ...attributes }
        >
            { CUSTOM_ICONS[ element.type ]
                ? <span className="bp-cb-field-card__svg-icon">{ CUSTOM_ICONS[ element.type ] }</span>
                : <span className={ `dashicons dashicons-${ element.icon }` }></span>
            }
            <span className="bp-cb-field-card__label">{ element.label }</span>
        </div>
    );
}

// ── Input Customization tab ────────────────────────────────────────────────────


function CustomizationPanel( { meta, layout, selectedElementId, selectedColumnId, dispatch } ) {
    const [ innerTab, setInnerTab ] = useState( 'general' );

    let selectedElement = null;
    if ( selectedElementId && layout?.columns ) {
        for ( const col of layout.columns ) {
            const found = col.elements.find( ( el ) => el.id === selectedElementId );
            if ( found ) { selectedElement = found; break; }
        }
    }

    const def = selectedElement ? getElement( selectedElement.type ) : null;

    return (
        <div className="bp-cb-element-settings">

            { /* General / Advanced tabs — always visible */ }
            <div className="bp-cb-inner-tabs">
                <button
                    className={ `bp-cb-inner-tab${ innerTab === 'general' ? ' is-active' : '' }` }
                    onClick={ () => setInnerTab( 'general' ) }
                >
                    { __( 'General', 'better-payment' ) }
                </button>
                <button
                    className={ `bp-cb-inner-tab${ innerTab === 'advanced' ? ' is-active' : '' }` }
                    onClick={ () => setInnerTab( 'advanced' ) }
                >
                    { __( 'Advanced', 'better-payment' ) }
                </button>
            </div>

            { innerTab === 'general' && (
                selectedElement ? (
                    <>
                        <div className="bp-cb-element-settings__header">
                            <span className={ `dashicons dashicons-${ def?.icon || 'block-default' }` } />
                            <span className="bp-cb-element-settings__title">
                                { def?.label || __( 'Element Settings', 'better-payment' ) }
                            </span>
                        </div>

                        { selectedElement.type === 'progress_bar' && (
                            <p className="bp-cb-widget-note">
                                { __( 'Note: Progress bars will be visible only when there is a goal set for the campaign.', 'better-payment' ) }
                            </p>
                        ) }

                        { ( selectedElement.type === 'social_links' || selectedElement.type === 'social_sharing' ) && (
                            <p className="bp-cb-social-info">
                                { __( "Don't see a social network here that you use and would like added?", 'better-payment' ) }{ ' ' }
                                <a href="https://wpdeveloper.com/support" target="_blank" rel="noreferrer">
                                    { __( 'Let us know!', 'better-payment' ) }
                                </a>
                            </p>
                        ) }

                        <ElementSettings
                            element={ selectedElement }
                            schema={ def?.settingsSchema || [] }
                            meta={ meta }
                            dispatch={ dispatch }
                        />
                    </>
                ) : (
                    <div className="bp-cb-customization-empty">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                            <circle cx="20" cy="20" r="18" stroke="#ddd" strokeWidth="2"/>
                            <path d="M14 20h12M20 14v12" stroke="#ccc" strokeWidth="2" strokeLinecap="round"/>
                        </svg>
                        <p>{ __( 'Click an element on the canvas to customize it.', 'better-payment' ) }</p>
                    </div>
                )
            ) }

            { innerTab === 'advanced' && (
                <ThemeColors meta={ meta } dispatch={ dispatch } />
            ) }
        </div>
    );
}

// ── Theme Colors (Advanced tab) ────────────────────────────────────────────────

const TEMPLATE_BUTTON_DEFAULTS = {
    'blank-1col':       '#5AA152',
    'blank-2col':       '#5AA152',
    'blank-3col':       '#5AA152',
    'charity-basic':    '#B49A5F',
    'medical-relief':   '#7A8347',
    'education-fund':   '#8FA040',
    'golf-destinations':'#B8A46A',
    'disaster-relief':  '#c0392b',
};

const COLOR_FIELDS = [
    { key: 'bpc_color_button', label: __( 'Button', 'better-payment' ), default: '#5AA152' },
];

const PICKER_W     = 270;
const PICKER_H_EST = 320; // estimated picker height after size reduction

function ThemeColors( { meta, dispatch } ) {
    const [ openKey,   setOpenKey   ] = useState( null );
    const [ pickerPos, setPickerPos ] = useState( { top: 0, left: 0, caretLeft: 0, caretAtBottom: true } );
    const swatchRefs = useRef( {} );
    const pickerRef  = useRef( null );

    const openPickerFor = useCallback( ( key ) => {
        if ( openKey === key ) { setOpenKey( null ); return; }

        const el = swatchRefs.current[ key ];
        if ( el ) {
            const rect = el.getBoundingClientRect();

            // Horizontal: center picker over swatch, clamp to viewport.
            let left = rect.left + rect.width / 2 - PICKER_W / 2;
            left = Math.max( 8, Math.min( left, window.innerWidth - PICKER_W - 8 ) );
            const caretLeft = rect.left + rect.width / 2 - left;

            // Vertical: prefer above the swatch, fall back to below.
            const spaceAbove    = rect.top - 10;
            const caretAtBottom = spaceAbove >= PICKER_H_EST;
            let top = caretAtBottom
                ? rect.top - PICKER_H_EST - 10
                : rect.bottom + 10;

            // Clamp so the picker never leaves the viewport.
            top = Math.max( 8, Math.min( top, window.innerHeight - PICKER_H_EST - 8 ) );

            setPickerPos( { top, left, caretLeft, caretAtBottom } );
        }
        setOpenKey( key );
    }, [ openKey ] );

    // Close picker when clicking outside both the popup and the swatches.
    useEffect( () => {
        if ( ! openKey ) return;
        const handler = ( e ) => {
            const inPicker  = pickerRef.current?.contains( e.target );
            const inSwatch  = Object.values( swatchRefs.current ).some( el => el?.contains( e.target ) );
            if ( ! inPicker && ! inSwatch ) setOpenKey( null );
        };
        document.addEventListener( 'mousedown', handler );
        return () => document.removeEventListener( 'mousedown', handler );
    }, [ openKey ] );

    const update = ( key, val ) =>
        dispatch( { type: 'UPDATE_META', payload: { [ key ]: val } } );

    const activeField = COLOR_FIELDS.find( ( f ) => f.key === openKey );

    return (
        <div className="bp-theme-colors">
            <p className="bp-theme-colors__title">
                { __( 'Theme Colors', 'better-payment' ) }
            </p>

            { COLOR_FIELDS.map( ( { key, label, default: def } ) => {
                const value = meta[ key ] || def;
                return (
                    <div key={ key } className="bp-theme-color-row">
                        <span className="bp-theme-color-row__label">{ label }</span>
                        <div className="bp-theme-color-row__control">
                            <button
                                ref={ ( el ) => { swatchRefs.current[ key ] = el; } }
                                type="button"
                                className={ `bp-theme-color-row__swatch${ openKey === key ? ' is-active' : '' }` }
                                style={ { background: value } }
                                onClick={ () => openPickerFor( key ) }
                            />
                            <input
                                type="text"
                                className="bp-theme-color-row__input"
                                value={ value }
                                readOnly
                                onClick={ () => openPickerFor( key ) }
                            />
                        </div>
                    </div>
                );
            } ) }

            { openKey && activeField && createPortal(
                <div
                    ref={ pickerRef }
                    className="bp-color-picker-popup"
                    style={ {
                        position: 'fixed',
                        top:      pickerPos.top,
                        left:     pickerPos.left,
                        width:    PICKER_W,
                        zIndex:   999999,
                    } }
                >
                    <ColorPicker
                        value={ meta[ openKey ] || activeField.default }
                        defaultValue={
                            TEMPLATE_BUTTON_DEFAULTS[ meta.bpc_template_key ] || activeField.default
                        }
                        onChange={ ( hex ) => update( openKey, hex ) }
                        onClose={ () => setOpenKey( null ) }
                    />
                    <div
                        className={ `bp-color-picker-popup__caret${ pickerPos.caretAtBottom ? '' : ' is-top' }` }
                        style={ { left: pickerPos.caretLeft } }
                    />
                </div>,
                document.body
            ) }
        </div>
    );
}

function ElementSettings( { element, schema, meta, dispatch } ) {
    const updateElement = ( patch ) =>
        dispatch( { type: 'UPDATE_ELEMENT_SETTINGS', elementId: element.id, settings: patch } );
    const updateMeta = ( patch ) =>
        dispatch( { type: 'UPDATE_META', payload: patch } );
    const s = element.settings || {};

    if ( schema.length === 0 ) {
        return (
            <p className="bp-cb-customization-hint">
                { __( 'No settings for this element.', 'better-payment' ) }
            </p>
        );
    }

    return (
        <div className="bp-cb-settings-form">
            { schema.map( ( control ) => {
                const isMeta   = !! control.metaKey;
                const rawValue = isMeta ? meta?.[ control.metaKey ] : s[ control.key ];
                const value    = rawValue !== undefined && rawValue !== null ? rawValue : ( control.defaultValue ?? '' );
                const onChange = isMeta
                    ? ( val ) => updateMeta( { [ control.metaKey ]: val } )
                    : ( val ) => updateElement( { [ control.key ]: val } );
                const onMultiChange = ( ! isMeta && control.type === 'image_upload' )
                    ? ( patch ) => updateElement( patch )
                    : undefined;
                const elementSettings = ( ! isMeta && control.type === 'image_upload' ) ? s : undefined;
                return (
                    <SettingsControl
                        key={ control.key }
                        control={ control }
                        value={ value }
                        onChange={ onChange }
                        onMultiChange={ onMultiChange }
                        elementSettings={ elementSettings }
                    />
                );
            } ) }
        </div>
    );
}

const ALIGN_OPTIONS = [
    {
        value: 'left',
        title: 'Align left',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <rect x="2" y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="2" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="2" y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
    {
        value: 'center',
        title: 'Align center',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <rect x="2"   y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="4.5" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="3"   y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
    {
        value: 'right',
        title: 'Align right',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <rect x="2" y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="7" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="4" y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
];

// ── SVG icons for the rich-text toolbar ──────────────────────────────────────
const TB = {
    bold:    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h8a4 4 0 010 8H6zm0 8h9a4 4 0 010 8H6z" stroke="currentColor" strokeWidth="2" fill="none" strokeLinejoin="round"/></svg>,
    italic:  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>,
    under:   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M6 4v6a6 6 0 0012 0V4"/><line x1="4" y1="20" x2="20" y2="20"/></svg>,
    link:    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>,
    ol:      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>,
    ul:      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>,
    clear:   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M17 7l-10 10"/><path d="M7 7h10v10"/></svg>,
};

const BLOCKS = [
    { value: 'p',  label: 'Normal' },
    { value: 'h2', label: 'Heading 2' },
    { value: 'h3', label: 'Heading 3' },
    { value: 'h4', label: 'Heading 4' },
];

const QUERY_CMDS = [ 'bold', 'italic', 'underline', 'insertOrderedList', 'insertUnorderedList' ];

function RichTextControl( { value, onChange } ) {
    const editorRef      = useRef( null );
    const containerRef   = useRef( null );
    const focusedRef     = useRef( false );
    const lastValueRef   = useRef( value );
    const savedRangeRef  = useRef( null );
    const linkInputRef   = useRef( null );
    const [ activeFormats, setActiveFormats ] = useState( {} );
    const [ blockValue, setBlockValue ]       = useState( 'p' );
    const [ linkPopover, setLinkPopover ]     = useState( null );
    // linkPopover: null | { mode: 'insert'|'view', href: string, anchorEl: Node|null, top: number, left: number }

    // Seed editor on mount
    useEffect( () => {
        if ( editorRef.current ) {
            editorRef.current.innerHTML = value || '';
            lastValueRef.current        = value || '';
        }
    }, [] ); // eslint-disable-line react-hooks/exhaustive-deps

    // Sync external value changes when not focused (different element selected)
    useEffect( () => {
        if ( editorRef.current && ! focusedRef.current && value !== lastValueRef.current ) {
            editorRef.current.innerHTML = value || '';
            lastValueRef.current        = value || '';
        }
    }, [ value ] );

    // Auto-focus link input when popover opens in insert mode
    useEffect( () => {
        if ( linkPopover?.mode === 'insert' && linkInputRef.current ) {
            linkInputRef.current.focus();
            linkInputRef.current.select();
        }
    }, [ linkPopover ] );

    // Close popover on outside click
    useEffect( () => {
        if ( ! linkPopover ) return;
        const onDown = ( e ) => {
            if ( ! containerRef.current?.contains( e.target ) ) {
                setLinkPopover( null );
            }
        };
        document.addEventListener( 'mousedown', onDown );
        return () => document.removeEventListener( 'mousedown', onDown );
    }, [ linkPopover ] );

    const updateActiveState = useCallback( () => {
        const formats = {};
        QUERY_CMDS.forEach( ( c ) => {
            try { formats[ c ] = document.queryCommandState( c ); } catch ( e ) { formats[ c ] = false; }
        } );

        // Extra check for list context via DOM traversal (more reliable cross-browser)
        const sel = window.getSelection();
        if ( sel && sel.rangeCount ) {
            let node = sel.getRangeAt( 0 ).startContainer;
            while ( node && node !== editorRef.current ) {
                const tag = node.nodeName?.toLowerCase();
                if ( tag === 'ul' ) { formats.insertUnorderedList = true; break; }
                if ( tag === 'ol' ) { formats.insertOrderedList   = true; break; }
                if ( tag === 'li' ) {
                    const parent = node.parentNode?.nodeName?.toLowerCase();
                    if ( parent === 'ul' ) formats.insertUnorderedList = true;
                    if ( parent === 'ol' ) formats.insertOrderedList   = true;
                    break;
                }
                node = node.parentNode;
            }
        }

        setActiveFormats( formats );

        // Detect current block tag
        if ( sel && sel.rangeCount ) {
            let node = sel.getRangeAt( 0 ).startContainer;
            while ( node && node !== editorRef.current ) {
                const tag = node.nodeName?.toLowerCase();
                if ( [ 'p', 'h2', 'h3', 'h4' ].includes( tag ) ) {
                    setBlockValue( tag );
                    return;
                }
                node = node.parentNode;
            }
        }
        setBlockValue( 'p' );
    }, [] );

    const cmd = useCallback( ( command, arg = null ) => {
        editorRef.current?.focus();
        document.execCommand( command, false, arg );
        updateActiveState();
    }, [ updateActiveState ] );

    const handleInput = useCallback( () => {
        const html = editorRef.current?.innerHTML || '';
        lastValueRef.current = html;
        onChange( html );
    }, [ onChange ] );

    // Save the current selection and compute popover position relative to container
    const openLinkPopover = useCallback( ( e ) => {
        e.preventDefault();
        const sel = window.getSelection();
        if ( ! sel || ! sel.rangeCount ) return;

        const range = sel.getRangeAt( 0 );
        savedRangeRef.current = range.cloneRange();

        // Find if cursor/selection is inside an anchor
        let anchor = null;
        let node = range.startContainer;
        while ( node && node !== editorRef.current ) {
            if ( node.nodeName === 'A' ) { anchor = node; break; }
            node = node.parentNode;
        }

        // Position below the selection, spanning the full container width (left/right set via CSS)
        const containerRect = containerRef.current?.getBoundingClientRect() ?? { top: 0 };
        const selRect = range.getBoundingClientRect();
        const top = selRect.bottom - containerRect.top + 4;

        if ( anchor ) {
            setLinkPopover( { mode: 'view', href: anchor.href, anchorEl: anchor, top } );
        } else {
            setLinkPopover( { mode: 'insert', href: 'https://', anchorEl: null, top } );
        }
    }, [] );

    // Restore saved selection, then apply execCommand
    const restoreAndExec = useCallback( ( command, arg = null ) => {
        editorRef.current?.focus();
        if ( savedRangeRef.current ) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange( savedRangeRef.current );
        }
        document.execCommand( command, false, arg );
        handleInput();
        updateActiveState();
    }, [ handleInput, updateActiveState ] );

    const handleLinkSave = useCallback( ( href ) => {
        setLinkPopover( null );
        if ( href && href !== 'https://' ) {
            restoreAndExec( 'createLink', href );
        }
    }, [ restoreAndExec ] );

    const handleLinkRemove = useCallback( () => {
        setLinkPopover( null );
        // Select the full anchor node before unlinking
        const anchor = linkPopover?.anchorEl;
        if ( anchor && editorRef.current?.contains( anchor ) ) {
            editorRef.current.focus();
            const range = document.createRange();
            range.selectNode( anchor );
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange( range );
            document.execCommand( 'unlink', false, null );
            handleInput();
        }
    }, [ linkPopover, handleInput ] );

    const handleLinkEdit = useCallback( () => {
        setLinkPopover( ( prev ) => ( { ...prev, mode: 'insert' } ) );
    }, [] );

    const handleBlockChange = useCallback( ( e ) => {
        const tag = e.target.value;
        editorRef.current?.focus();
        document.execCommand( 'formatBlock', false, tag );
        setBlockValue( tag );
    }, [] );

    const BTNS = [
        { id: 'bold',                icon: TB.bold,  title: 'Bold (Ctrl+B)'      },
        { id: 'italic',              icon: TB.italic,title: 'Italic (Ctrl+I)'    },
        { id: 'underline',           icon: TB.under, title: 'Underline (Ctrl+U)' },
        { id: 'insertOrderedList',   icon: TB.ol,    title: 'Ordered list'       },
        { id: 'insertUnorderedList', icon: TB.ul,    title: 'Unordered list'     },
        { id: 'removeFormat',        icon: TB.clear, title: 'Clear formatting'   },
        { id: 'link',                icon: TB.link,  title: 'Insert link', isLink: true },
    ];

    return (
        <div className="bp-cb-rich-text" ref={ containerRef }>
            { /* Row 1: block style dropdown */ }
            <div className="bp-cb-rich-text__toolbar bp-cb-rich-text__toolbar--row1">
                <select
                    className="bp-cb-rich-text__block-select"
                    value={ blockValue }
                    onChange={ handleBlockChange }
                >
                    { BLOCKS.map( ( b ) => (
                        <option key={ b.value } value={ b.value }>{ b.label }</option>
                    ) ) }
                </select>
            </div>

            { /* Row 2: format buttons */ }
            <div className="bp-cb-rich-text__toolbar bp-cb-rich-text__toolbar--row2">
                { BTNS.map( ( btn ) => (
                    <button
                        key={ btn.id }
                        type="button"
                        title={ btn.title }
                        className={ `bp-cb-rich-text__tool-btn${ activeFormats[ btn.id ] ? ' is-active' : '' }` }
                        onMouseDown={ btn.isLink ? openLinkPopover : ( e ) => { e.preventDefault(); cmd( btn.id ); } }
                    >
                        { btn.icon }
                    </button>
                ) ) }
            </div>

            { /* Inline link popover */ }
            { linkPopover && (
                <div
                    className={ `bp-cb-rich-text__link-popover bp-cb-rich-text__link-popover--${ linkPopover.mode }` }
                    style={ { top: linkPopover.top } }
                    onKeyDown={ ( e ) => { if ( e.key === 'Escape' ) setLinkPopover( null ); } }
                >
                    { linkPopover.mode === 'insert' ? (
                        <>
                            <span className="bp-cb-rich-text__link-label">
                                { __( 'Enter link:', 'better-payment' ) }
                            </span>
                            <input
                                ref={ linkInputRef }
                                type="url"
                                className="bp-cb-rich-text__link-input"
                                defaultValue={ linkPopover.href }
                                onKeyDown={ ( e ) => {
                                    if ( e.key === 'Enter' ) { e.preventDefault(); handleLinkSave( e.target.value ); }
                                } }
                            />
                            <button
                                type="button"
                                className="bp-cb-rich-text__link-save"
                                onMouseDown={ ( e ) => { e.preventDefault(); handleLinkSave( linkInputRef.current?.value ); } }
                            >
                                { __( 'Save', 'better-payment' ) }
                            </button>
                        </>
                    ) : (
                        <>
                            <a
                                href={ linkPopover.href }
                                className="bp-cb-rich-text__link-visit"
                                target="_blank"
                                rel="noreferrer"
                            >
                                { linkPopover.href.length > 40 ? linkPopover.href.slice( 0, 40 ) + '…' : linkPopover.href }
                            </a>
                            <span className="bp-cb-rich-text__link-sep">|</span>
                            <button type="button" className="bp-cb-rich-text__link-action" onMouseDown={ ( e ) => { e.preventDefault(); handleLinkEdit(); } }>
                                { __( 'Edit', 'better-payment' ) }
                            </button>
                            <span className="bp-cb-rich-text__link-sep">|</span>
                            <button type="button" className="bp-cb-rich-text__link-action bp-cb-rich-text__link-action--remove" onMouseDown={ ( e ) => { e.preventDefault(); handleLinkRemove(); } }>
                                { __( 'Remove', 'better-payment' ) }
                            </button>
                        </>
                    ) }
                </div>
            ) }

            <div
                ref={ editorRef }
                className="bp-cb-rich-text__editor"
                contentEditable
                suppressContentEditableWarning
                data-placeholder={ __( 'Write a description for your campaign…', 'better-payment' ) }
                onFocus={ () => { focusedRef.current = true; } }
                onBlur={ () => { focusedRef.current = false; handleInput(); } }
                onInput={ handleInput }
                onKeyUp={ updateActiveState }
                onMouseUp={ updateActiveState }
            />
        </div>
    );
}

function ImageUploadControl( { value, onMultiChange, elementSettings } ) {
    const openMedia = useCallback( () => {
        openWpMedia( ( { url, id, sizes } ) =>
            onMultiChange( { src: url, src_id: id, src_sizes: sizes } )
        );
    }, [ onMultiChange ] );

    const srcSizes   = elementSettings?.src_sizes || {};
    const size       = elementSettings?.size || 'full';
    const displaySrc = ( size !== 'full' && srcSizes[ size ]?.url ) ? srcSizes[ size ].url : ( value || '' );
    const hasImage   = !! displaySrc;

    return (
        <div className="bp-cb-image-upload">

            { /* Elementor-style preview area */ }
            <div className="bp-cb-image-picker" onClick={ openMedia }>
                { hasImage ? (
                    <>
                        <img src={ displaySrc } alt="" className="bp-cb-image-picker__img" />
                        <button
                            type="button"
                            className="bp-cb-image-picker__delete"
                            title={ __( 'Remove image', 'better-payment' ) }
                            onClick={ ( e ) => { e.stopPropagation(); onMultiChange( { src: '', src_id: 0, src_sizes: {} } ); } }
                        >
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                <path d="M2 4h12M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724C5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724C10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4L12 13.333C12 13.687 11.86 14.026 11.609 14.276C11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276C4.14 14.026 4 13.687 4 13.333L3.333 4" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </button>
                    </>
                ) : (
                    <div className="bp-cb-image-picker__placeholder">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="11" stroke="currentColor" strokeWidth="1.5"/>
                            <path d="M12 7v10M7 12h10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                        </svg>
                    </div>
                ) }

                { /* Choose Image bar */ }
                <div className="bp-cb-image-picker__bar" onClick={ ( e ) => e.stopPropagation() }>
                    <button type="button" className="bp-cb-image-picker__choose" onClick={ openMedia }>
                        { __( 'Choose Image', 'better-payment' ) }
                    </button>
                </div>
            </div>

            { /* URL input + Clear */ }
            <input
                type="url"
                className="bp-cb-image-upload__url"
                value={ value ?? '' }
                placeholder="https://"
                onChange={ ( e ) => onMultiChange( { src: e.target.value, src_id: 0, src_sizes: {} } ) }
            />
            { hasImage && (
                <button
                    type="button"
                    className="bp-cb-image-upload__btn bp-cb-image-upload__btn--clear"
                    onClick={ () => onMultiChange( { src: '', src_id: 0, src_sizes: {} } ) }
                >
                    { __( 'Clear', 'better-payment' ) }
                </button>
            ) }
        </div>
    );
}

function SettingsControl( { control, value, onChange, onMultiChange, elementSettings } ) {
    const { key, label, type, placeholder, min, max, rows, options, info, unit, step } = control;

    if ( type === 'section_label' ) {
        return (
            <div className="bp-cb-section-label">
                <span>{ label }</span>
            </div>
        );
    }

    if ( type === 'note' ) {
        return <p className="bp-cb-widget-note">{ label }</p>;
    }

    return (
        <div className="bp-cb-control">
            { type !== 'toggle' && type !== 'switch' && type !== 'image_upload' && type !== 'rich_text' && (
                <label className="bp-cb-field-label" htmlFor={ `ctrl-${ key }` }>
                    { label }
                </label>
            ) }

            { type === 'image_upload' && (
                <>
                    <label className="bp-cb-field-label">
                        { label }
                    </label>
                    <ImageUploadControl value={ value } onMultiChange={ onMultiChange } elementSettings={ elementSettings } />
                </>
            ) }

            { type === 'rich_text' && (
                <>
                    <label className="bp-cb-field-label">
                        { label }
                    </label>
                    <RichTextControl value={ value } onChange={ onChange } />
                </>
            ) }

            { type === 'range' && (
                <div className="bp-cb-range-wrap">
                    <input
                        id={ `ctrl-${ key }` }
                        type="range"
                        min={ min ?? 10 }
                        max={ max ?? 100 }
                        step={ step ?? 1 }
                        value={ value ?? ( control.defaultValue ?? 100 ) }
                        onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) }
                    />
                    <span className="bp-cb-range-value">
                        { value ?? ( control.defaultValue ?? 100 ) }{ unit || '%' }
                    </span>
                </div>
            ) }

            { type === 'align' && (
                <div className="bp-cb-align-group">
                    { ALIGN_OPTIONS.map( ( opt ) => (
                        <button
                            key={ opt.value }
                            type="button"
                            title={ opt.title }
                            className={ `bp-cb-align-btn${ ( value || 'left' ) === opt.value ? ' is-active' : '' }` }
                            onClick={ () => onChange( opt.value ) }
                        >
                            { opt.icon }
                        </button>
                    ) ) }
                </div>
            ) }

            { type === 'text' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="text"
                    value={ value ?? '' }
                    placeholder={ placeholder || '' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }

            { type === 'url' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="url"
                    value={ value ?? '' }
                    placeholder={ placeholder || 'https://' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }

            { type === 'number' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="number"
                    value={ value ?? '' }
                    min={ min }
                    max={ max }
                    onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) }
                />
            ) }

            { type === 'color' && (
                <div className="bp-color-row">
                    <input
                        id={ `ctrl-${ key }` }
                        type="color"
                        value={ value || '#6b63f6' }
                        onChange={ ( e ) => onChange( e.target.value ) }
                    />
                    <span className="bp-color-value">{ value || '#6b63f6' }</span>
                </div>
            ) }

            { type === 'toggle' && (
                <label className="bp-cb-toggle-label" htmlFor={ `ctrl-${ key }` }>
                    <input
                        id={ `ctrl-${ key }` }
                        type="checkbox"
                        checked={ !! value }
                        onChange={ ( e ) => onChange( e.target.checked ) }
                    />
                    <span>{ label }</span>
                </label>
            ) }

            { type === 'switch' && (
                <label className="bp-cb-switch-label" htmlFor={ `ctrl-${ key }` }>
                    <span className="bp-cb-switch-wrap">
                        <input
                            id={ `ctrl-${ key }` }
                            type="checkbox"
                            checked={ !! value }
                            onChange={ ( e ) => onChange( e.target.checked ) }
                        />
                        <span className="bp-cb-switch-track" />
                    </span>
                    <span>{ label }</span>
                </label>
            ) }

            { type === 'select' && (
                <select
                    id={ `ctrl-${ key }` }
                    value={ value ?? '' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                >
                    { ( options || [] ).map( ( opt ) => (
                        <option key={ opt.value } value={ opt.value }>{ opt.label }</option>
                    ) ) }
                </select>
            ) }

            { type === 'textarea' && (
                <textarea
                    id={ `ctrl-${ key }` }
                    rows={ rows || 4 }
                    value={ value ?? '' }
                    placeholder={ placeholder || '' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }

            { type === 'suggested_amounts' && (
                <SuggestedAmountsEditor
                    items={ Array.isArray( value ) ? value : [] }
                    onChange={ onChange }
                    compact
                />
            ) }
        </div>
    );
}
