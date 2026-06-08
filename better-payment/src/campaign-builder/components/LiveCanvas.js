import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useSortable, SortableContext } from '@dnd-kit/sortable';
import { useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';

// ── SVG icons (match ElementCard toolbar exactly) ─────────────────────────────

const IconDrag = () => (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
        <path d="M6 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" fill="currentColor"/>
    </svg>
);

const IconEdit = () => (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
        <path d="M11.5 2.5l2 2-7 7H4.5v-2l7-7z" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
);

const IconDelete = () => (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
        <path d="M2 4h12M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724 5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724 10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4 12 13.333C12 13.687 11.86 14.026 11.609 14.276 11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276 4.14 14.026 4 13.687 4 13.333L3.333 4" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
);

const IconMoveUp = () => (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
        <path d="M8 12V4M4 8l4-4 4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
);

const IconMoveDown = () => (
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
        <path d="M8 4v8m4-4-4 4-4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
);

// ── ElementHotspot — draggable transparent overlay over one rendered element ──

function ElementHotspot( {
    element,
    columnId,
    index,
    totalElements,
    rect,
    isSelected,
    isDropTarget,
    dispatch,
} ) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable( {
        id: element.id,
        data: { source: 'canvas', columnId, element },
    } );

    if ( ! rect ) return null;

    const style = {
        position: 'absolute',
        top:      rect.top,
        left:     rect.left,
        width:    rect.width,
        height:   rect.height,
        // Only apply dnd-kit transform to the actively dragged hotspot.
        transform:  isDragging ? CSS.Transform.toString( transform ) : undefined,
        transition: isDragging ? transition : undefined,
        zIndex:     isDragging ? 50 : 2,
    };

    const isFirst = index === 0;
    const isLast  = index === totalElements - 1;

    return (
        <div
            ref={ setNodeRef }
            style={ style }
            className={ [
                'bp-lc-hotspot',
                isSelected   ? 'is-selected'   : '',
                isDragging   ? 'is-dragging'   : '',
                isDropTarget ? 'is-drop-target' : '',
            ].filter( Boolean ).join( ' ' ) }
            onClick={ ( e ) => {
                e.stopPropagation();
                dispatch( { type: 'SELECT_ELEMENT', elementId: element.id, columnId } );
            } }
            { ...attributes }
        >
            {/* Floating toolbar — appears on hover / when selected */}
            <div className="bp-lc-hotspot__toolbar">
                <button
                    type="button"
                    className="bp-lc-hotspot__toolbar-btn bp-lc-hotspot__toolbar-btn--drag"
                    title={ __( 'Drag to reorder', 'better-payment' ) }
                    onClick={ ( e ) => e.stopPropagation() }
                    { ...listeners }
                >
                    <IconDrag />
                </button>

                { ! isFirst && (
                    <button
                        type="button"
                        className="bp-lc-hotspot__toolbar-btn"
                        title={ __( 'Move up', 'better-payment' ) }
                        onClick={ ( e ) => {
                            e.stopPropagation();
                            dispatch( { type: 'REORDER_ELEMENTS', columnId, oldIndex: index, newIndex: index - 1 } );
                        } }
                    >
                        <IconMoveUp />
                    </button>
                ) }

                { ! isLast && (
                    <button
                        type="button"
                        className="bp-lc-hotspot__toolbar-btn"
                        title={ __( 'Move down', 'better-payment' ) }
                        onClick={ ( e ) => {
                            e.stopPropagation();
                            dispatch( { type: 'REORDER_ELEMENTS', columnId, oldIndex: index, newIndex: index + 1 } );
                        } }
                    >
                        <IconMoveDown />
                    </button>
                ) }

                <button
                    type="button"
                    className="bp-lc-hotspot__toolbar-btn"
                    title={ __( 'Edit element', 'better-payment' ) }
                    onClick={ ( e ) => {
                        e.stopPropagation();
                        dispatch( { type: 'SELECT_ELEMENT', elementId: element.id, columnId } );
                    } }
                >
                    <IconEdit />
                </button>

                <button
                    type="button"
                    className="bp-lc-hotspot__toolbar-btn bp-lc-hotspot__toolbar-btn--delete"
                    title={ __( 'Delete', 'better-payment' ) }
                    onClick={ ( e ) => {
                        e.stopPropagation();
                        dispatch( { type: 'REMOVE_ELEMENT', columnId, elementId: element.id } );
                    } }
                >
                    <IconDelete />
                </button>
            </div>
        </div>
    );
}

// ── ColumnDropZone — droppable zone matching the rendered column ──────────────

function ColumnDropZone( { column, rect } ) {
    const isEmpty = column.elements.length === 0;

    const { setNodeRef, isOver } = useDroppable( {
        id:   `col-${ column.id }`,
        data: { columnId: column.id },
    } );

    if ( ! rect ) return null;

    return (
        <div
            ref={ setNodeRef }
            style={ {
                position: 'absolute',
                top:      rect.top,
                left:     rect.left,
                width:    rect.width,
                height:   rect.height,
                zIndex:   1,
                pointerEvents: 'none',
            } }
            className={ [
                'bp-lc-col-zone',
                isOver  ? 'is-over'  : '',
                isEmpty ? 'is-empty' : '',
            ].filter( Boolean ).join( ' ' ) }
        >
            { isEmpty && (
                <div className="bp-lc-col-zone__hint">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>{ __( 'Drop elements here', 'better-payment' ) }</span>
                </div>
            ) }
        </div>
    );
}

// ── LiveCanvas ────────────────────────────────────────────────────────────────

export default function LiveCanvas( {
    state,
    campaignId,
    selectedElementId,
    dispatch,
    dropTargetElementId,
} ) {
    const [ previewHtml,   setPreviewHtml   ] = useState( '' );
    const [ loading,       setLoading       ] = useState( false );
    const [ iframeHeight,  setIframeHeight  ] = useState( 300 );
    const [ elementRects,  setElementRects  ] = useState( {} );
    const [ columnRects,   setColumnRects   ] = useState( {} );

    const iframeRef    = useRef( null );
    const debounceRef  = useRef( null );
    const requestIdRef = useRef( 0 );
    const isFirstRender = useRef( true );
    const previewHtmlRef = useRef( '' );

    const columns = state.layout?.columns || [];

    // Clear stale hotspot rects the moment layout changes so misplaced hotspots
    // don't linger while the new iframe preview is loading.
    useEffect( () => {
        if ( isFirstRender.current ) return;
        setElementRects( {} );
        setColumnRects( {} );
    }, [ state.layout ] ); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Fetch preview HTML (debounced 200ms, immediate on first render) ───────
    useEffect( () => {
        const delay = isFirstRender.current ? 0 : 200;
        isFirstRender.current = false;

        clearTimeout( debounceRef.current );
        debounceRef.current = setTimeout( () => {
            const requestId = ++requestIdRef.current;
            setLoading( true );

            apiFetch( {
                path:   'better-payment/v1/campaigns/preview',
                method: 'POST',
                data:   {
                    campaign_id: campaignId || 0,
                    layout:      state.layout,
                    meta:        state.meta,
                },
            } )
                .then( ( data ) => {
                    if ( requestId !== requestIdRef.current ) return;
                    const newHtml = data.html || '';
                    const unchanged = newHtml === previewHtmlRef.current;
                    previewHtmlRef.current = newHtml;
                    setPreviewHtml( newHtml );
                    setLoading( false );
                    if ( unchanged ) {
                        // React won't update srcDoc (same string) → iframe won't
                        // reload → onLoad won't fire → measure manually so hotspots
                        // are restored after the rect-clearing effect ran.
                        requestAnimationFrame( measurePositions );
                    }
                } )
                .catch( () => {
                    if ( requestId !== requestIdRef.current ) return;
                    setLoading( false );
                } );
        }, delay );

        return () => clearTimeout( debounceRef.current );
    }, [ state.layout, state.meta, campaignId ] ); // eslint-disable-line react-hooks/exhaustive-deps

    // ── Measure element/column positions after iframe loads ───────────────────
    const measurePositions = useCallback( () => {
        const iframe = iframeRef.current;
        if ( ! iframe || ! iframe.contentDocument ) return;

        const doc = iframe.contentDocument;

        // Auto-height: expand iframe to its full content height so there is no
        // internal scroll. This keeps the iframe viewport origin fixed at (0,0)
        // relative to the iframe document — a prerequisite for the rect math below.
        const contentHeight = Math.max(
            doc.documentElement.scrollHeight,
            doc.body ? doc.body.scrollHeight : 0
        );
        setIframeHeight( contentHeight );

        // getBoundingClientRect() called from inside the iframe returns coords in
        // the iframe's own viewport space (origin = iframe top-left = 0,0).
        // The overlay is position:absolute starting at the same (0,0) corner of
        // .bp-lc-inner, so we use r.top / r.left directly — no parent-window
        // offset needed.
        //
        // PADDING: expand each hotspot 4px beyond the element boundary so the
        // hover border has breathing room and doesn't sit flush on the content.
        const PAD = 4;

        const elRects = {};
        doc.querySelectorAll( '[data-bp-element-id]' ).forEach( ( el ) => {
            const r = el.getBoundingClientRect();
            elRects[ el.dataset.bpElementId ] = {
                top:    r.top    - PAD,
                left:   r.left   - PAD,
                width:  r.width  + PAD * 2,
                height: r.height + PAD * 2,
            };
        } );
        setElementRects( elRects );

        const colRects = {};
        doc.querySelectorAll( '[data-bp-column-id]' ).forEach( ( el ) => {
            const r = el.getBoundingClientRect();
            colRects[ el.dataset.bpColumnId ] = {
                top:    r.top,
                left:   r.left,
                width:  r.width,
                height: r.height,
            };
        } );
        setColumnRects( colRects );
    }, [] );

    // Re-measure on window resize.
    useEffect( () => {
        window.addEventListener( 'resize', measurePositions );
        return () => window.removeEventListener( 'resize', measurePositions );
    }, [ measurePositions ] );

    // ── Empty state ───────────────────────────────────────────────────────────
    if ( columns.length === 0 ) {
        return (
            <div className="bp-cb-canvas bp-cb-canvas--empty">
                <div className="bp-cb-canvas-placeholder">
                    <button
                        className="bp-cb-canvas-placeholder__add"
                        title={ __( 'Choose a template to start', 'better-payment' ) }
                        onClick={ () => dispatch( { type: 'OPEN_TEMPLATE_MODAL' } ) }
                    >
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                            <circle cx="16" cy="16" r="15" stroke="#bbb" strokeWidth="1.5"/>
                            <path d="M16 10v12M10 16h12" stroke="#bbb" strokeWidth="2" strokeLinecap="round"/>
                        </svg>
                    </button>
                    <p>{ __( 'Drag elements from the right panel, or choose a template.', 'better-payment' ) }</p>
                </div>
            </div>
        );
    }

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <div
            className="bp-lc-wrap"
            onClick={ () => dispatch( { type: 'DESELECT_ELEMENT' } ) }
        >
            {/* Shade — direct child of bp-lc-wrap so position:absolute;inset:0 covers
                the full canvas area (flex slot), not just the iframe content height */}
            { loading && <div className="bp-lc-shade" /> }

            {/* Outer shell — iframe + overlay share the same coordinate space */}
            <div className="bp-lc-inner" style={ { height: iframeHeight || 'auto' } }>

                {/* Live preview iframe — pointer-events disabled so overlay intercepts all input */}
                <iframe
                    ref={ iframeRef }
                    srcDoc={ previewHtml }
                    className="bp-lc-iframe"
                    style={ { height: iframeHeight } }
                    onLoad={ measurePositions }
                    title={ __( 'Campaign Preview', 'better-payment' ) }
                    sandbox="allow-same-origin"
                />

                {/* Transparent interaction overlay */}
                <div
                    className="bp-lc-overlay"
                    style={ { height: iframeHeight } }
                >
                    { columns.map( ( column ) => (
                        <SortableContext
                            key={ column.id }
                            items={ column.elements.map( ( el ) => el.id ) }
                        >
                            {/* Column drop zone for panel-drag targets */}
                            <ColumnDropZone
                                column={ column }
                                rect={ columnRects[ column.id ] }
                            />

                            {/* Per-element hotspots */}
                            { column.elements.map( ( element, index ) => (
                                <ElementHotspot
                                    key={ element.id }
                                    element={ element }
                                    columnId={ column.id }
                                    index={ index }
                                    totalElements={ column.elements.length }
                                    rect={ elementRects[ element.id ] }
                                    isSelected={ element.id === selectedElementId }
                                    isDropTarget={ element.id === dropTargetElementId }
                                    dispatch={ dispatch }
                                />
                            ) ) }
                        </SortableContext>
                    ) ) }
                </div>
            </div>
        </div>
    );
}
