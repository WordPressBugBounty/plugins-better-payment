import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useSortable, SortableContext } from '@dnd-kit/sortable';
import { useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { getElement } from '../fields/FieldRegistry';
import { isElementLocked } from '../utils/proEnabled';
import ProCrownIcon from './ProCrownIcon';
import { AI_TEMPLATE } from './TemplatesTab';

// ── SVG icons (match ElementCard toolbar exactly) ─────────────────────────────

const IconDrag = () => (
	<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
		<path
			d="M6 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"
			fill="currentColor"
		/>
	</svg>
);

const IconEdit = () => (
	<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
		<path
			d="M11.5 2.5l2 2-7 7H4.5v-2l7-7z"
			stroke="currentColor"
			strokeWidth="1.3"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const IconDelete = () => (
	<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
		<path
			d="M2 4h12M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724 5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724 10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4 12 13.333C12 13.687 11.86 14.026 11.609 14.276 11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276 4.14 14.026 4 13.687 4 13.333L3.333 4"
			stroke="currentColor"
			strokeWidth="1.3"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const IconMoveUp = () => (
	<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
		<path
			d="M8 12V4M4 8l4-4 4 4"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const IconMoveDown = () => (
	<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
		<path
			d="M8 4v8m4-4-4 4-4-4"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
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
	isHighlighted,
	isProLocked,
	dispatch,
	onRequestUpgrade,
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
		top: rect.top,
		left: rect.left,
		width: rect.width,
		height: rect.height,
		// Only apply dnd-kit transform to the actively dragged hotspot.
		transform: isDragging ? CSS.Transform.toString( transform ) : undefined,
		transition: isDragging ? transition : undefined,
		zIndex: isDragging ? 50 : 2,
	};

	const isFirst = index === 0;
	const isLast = index === totalElements - 1;

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ [
				'bp-lc-hotspot',
				isSelected ? 'is-selected' : '',
				isDragging ? 'is-dragging' : '',
				isDropTarget ? 'is-drop-target' : '',
				isHighlighted ? 'is-ai-changed' : '',
				isProLocked ? 'is-pro-locked' : '',
			]
				.filter( Boolean )
				.join( ' ' ) }
			onClick={ ( e ) => {
				e.stopPropagation();
				dispatch( {
					type: 'SELECT_ELEMENT',
					elementId: element.id,
					columnId,
				} );
			} }
			{ ...attributes }
		>
			{ /* Locked-Pro overlay.

				 It lives out here in React rather than inside the preview iframe
				 for one hard reason: the iframe is `pointer-events: none`, so a
				 button rendered in there could never be clicked. A CTA that cannot
				 be pressed is worse than no CTA. Out here the hotspot is already
				 measured to the element's exact bounding rect, so the overlay lines
				 up for free, and `backdrop-filter` blurs the iframe's painted
				 pixels behind it.

				 The scrim is `pointer-events: none` (see style.scss) so drag,
				 select and the toolbar's move/delete all keep working through it —
				 a locked element must stay as manageable as any other. Only the
				 button opts back in. */ }
			{ isProLocked && (
				<div className="bp-lc-hotspot__pro-lock">
					<button
						type="button"
						className="bp-lc-hotspot__pro-lock-btn"
						onClick={ ( e ) => {
							// The hotspot's own onClick selects the element,
							// which jumps the sidebar to Customize — behind the
							// modal we are about to open. Stop it here.
							// (No pointer-down guard is needed: dnd-kit's drag
							// `listeners` live on the toolbar's drag handle, not
							// on the hotspot wrapper, so pressing this button
							// cannot start a drag.)
							e.stopPropagation();
							if ( typeof onRequestUpgrade === 'function' ) {
								onRequestUpgrade( element.type );
							}
						} }
					>
						<ProCrownIcon decorative />
						{ __( 'Get PRO to Unlock', 'better-payment' ) }
					</button>
				</div>
			) }

			{ /* Floating toolbar — appears on hover / when selected */ }
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
							dispatch( {
								type: 'REORDER_ELEMENTS',
								columnId,
								oldIndex: index,
								newIndex: index - 1,
							} );
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
							dispatch( {
								type: 'REORDER_ELEMENTS',
								columnId,
								oldIndex: index,
								newIndex: index + 1,
							} );
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
						dispatch( {
							type: 'SELECT_ELEMENT',
							elementId: element.id,
							columnId,
						} );
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
						dispatch( {
							type: 'REMOVE_ELEMENT',
							columnId,
							elementId: element.id,
						} );
					} }
				>
					<IconDelete />
				</button>
			</div>
		</div>
	);
}

// ── ColumnDropZone — droppable zone matching the rendered column ──────────────

function ColumnDropZone( { column, rect, onRequestElements } ) {
	const isEmpty = column.elements.length === 0;

	const { setNodeRef, isOver } = useDroppable( {
		id: `col-${ column.id }`,
		data: { columnId: column.id },
	} );

	if ( ! rect ) return null;

	return (
		<div
			ref={ setNodeRef }
			style={ {
				position: 'absolute',
				top: rect.top,
				left: rect.left,
				width: rect.width,
				height: rect.height,
				zIndex: 1,
				pointerEvents: 'none',
			} }
			className={ [
				'bp-lc-col-zone',
				isOver ? 'is-over' : '',
				isEmpty ? 'is-empty' : '',
			]
				.filter( Boolean )
				.join( ' ' ) }
		>
			{ isEmpty && (
				// A button, not a div: dragging is not the only way in, and an
				// empty column offers no other route to the element list. The
				// zone itself is pointer-events:none so hotspots underneath stay
				// clickable, so this re-enables them on the hint alone.
				<button
					type="button"
					className="bp-lc-col-zone__hint"
					onClick={ onRequestElements }
					title={ __( 'Browse elements', 'better-payment' ) }
				>
					<svg
						width="20"
						height="20"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="1.5"
						strokeLinecap="round"
						strokeLinejoin="round"
					>
						<path d="M12 5v14M5 12h14" />
					</svg>
					<span>
						{ __( 'Drop elements here', 'better-payment' ) }
					</span>
				</button>
			) }
		</div>
	);
}

// ── LiveCanvas ────────────────────────────────────────────────────────────────

export default function LiveCanvas( {
	state,
	active = true,
	campaignId,
	selectedElementId,
	highlightedElementIds,
	dispatch,
	dropTargetElementId,
	onRequestElements,
	onRequestAI,
	sidebarTab,
	proEnabled = false,
	onRequestUpgrade,
} ) {
	const [ previewHtml, setPreviewHtml ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ iframeHeight, setIframeHeight ] = useState( 300 );
	const [ elementRects, setElementRects ] = useState( {} );
	const [ columnRects, setColumnRects ] = useState( {} );

	const iframeRef = useRef( null );
	const debounceRef = useRef( null );
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
				path: 'better-payment/v1/campaigns/preview',
				method: 'POST',
				data: {
					campaign_id: campaignId || 0,
					layout: state.layout,
					meta: state.meta,
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
				top: r.top - PAD,
				left: r.left - PAD,
				width: r.width + PAD * 2,
				height: r.height + PAD * 2,
			};
		} );
		setElementRects( elRects );

		const colRects = {};
		doc.querySelectorAll( '[data-bp-column-id]' ).forEach( ( el ) => {
			const r = el.getBoundingClientRect();
			colRects[ el.dataset.bpColumnId ] = {
				top: r.top,
				left: r.left,
				width: r.width,
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

	// Re-measure when the editor tab becomes visible again. The canvas is now kept
	// mounted across top-nav switches (App toggles its `display`, never unmounts) —
	// but while hidden the iframe has no layout, so any measurement that ran then
	// (e.g. a preview refresh from a meta change on the Settings tab) recorded zeroed
	// rects, and the unchanged srcDoc means onLoad won't fire on return. A rAF measure
	// on the hidden→visible transition restores the hotspots. Same trick as onLoad.
	const wasActive = useRef( active );
	useEffect( () => {
		if ( active && ! wasActive.current ) {
			requestAnimationFrame( measurePositions );
		}
		wasActive.current = active;
	}, [ active, measurePositions ] );

	// ── Empty state ───────────────────────────────────────────────────────────
	//
	// A campaign with no columns has NO DROP TARGETS — `ColumnDropZone` is
	// rendered per column, so with zero columns there is nowhere for a dragged
	// element to land. This used to read "Drag elements from the right panel, or
	// choose a template", which put the impossible action first: a user who tried
	// it got no drop indicator, no error, and no clue why. The copy now names only
	// the two things that actually work from here — pick a layout, or describe the
	// campaign to the AI — and mentions dragging as what comes *after* a layout
	// exists, so the dead end reads as a next step instead of a broken feature.
	//
	// If drop-on-empty is ever implemented (auto-creating a single column), this
	// copy has to change back.
	if ( columns.length === 0 ) {
		// An AI-assisted campaign (started via "Campaign With AI" or the "Create
		// using AI Assistant" template card) is empty *by design* until the first
		// prompt — AI_TEMPLATE ships `columns: []`. Sending that user to the
		// template picker offers them the choice they already made, so here the +
		// opens the AI panel instead. It matters when the sidebar is parked on
		// Elements or Customize, where the assistant is off-screen and the + is
		// the most obvious thing to click.
		const isAiCampaign = state.meta?.bpc_template_key === AI_TEMPLATE.key;
		const canFocusAI = isAiCampaign && typeof onRequestAI === 'function';

		return (
			<div className="bp-cb-canvas bp-cb-canvas--empty">
				<div className="bp-cb-canvas-placeholder">
					<button
						className="bp-cb-canvas-placeholder__add"
						title={
							canFocusAI
								? __(
										'Open the AI Assistant',
										'better-payment'
								  )
								: __(
										'Choose a layout or template to start',
										'better-payment'
								  )
						}
						onClick={ () =>
							canFocusAI
								? onRequestAI()
								: dispatch( { type: 'OPEN_TEMPLATE_MODAL' } )
						}
					>
						<svg
							width="32"
							height="32"
							viewBox="0 0 32 32"
							fill="none"
						>
							<circle
								cx="16"
								cy="16"
								r="15"
								stroke="#bbb"
								strokeWidth="1.5"
							/>
							<path
								d="M16 10v12M10 16h12"
								stroke="#bbb"
								strokeWidth="2"
								strokeLinecap="round"
							/>
						</svg>
					</button>
					<p className="bp-cb-canvas-placeholder__title">
						{ canFocusAI
							? __(
									'Create Your Campaign with AI',
									'better-payment'
							  )
							: __( 'Start your campaign', 'better-payment' ) }
					</p>
					<p>
						{ canFocusAI
							? // On an AI campaign the copy tracks the sidebar: while
							  // the user is on the Elements tab (before any layout
							  // exists) point them at the drag-to-build path; on the
							  // AI tab keep the "describe it to AI" prompt.
							  sidebarTab === 'fields'
								? __(
										'Once your layout is ready, customize it by dragging elements onto the canvas.',
										'better-payment'
								  )
								: __(
										'Describe your campaign & let AI build one for you. Click on ‘+’ to get started.',
										'better-payment'
								  )
							: __(
									'Describe it to the AI Assistant on the right and it will build one for you — or click + to pick a layout.',
									'better-payment'
							  ) }
					</p>
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
			{ /* Shade — direct child of bp-lc-wrap so position:absolute;inset:0 covers
                the full canvas area (flex slot), not just the iframe content height */ }
			{ loading && <div className="bp-lc-shade" /> }

			{ /* Outer shell — iframe + overlay share the same coordinate space */ }
			<div
				className="bp-lc-inner"
				style={ { height: iframeHeight || 'auto' } }
			>
				{ /* Live preview iframe — pointer-events disabled so overlay intercepts all input */ }
				<iframe
					ref={ iframeRef }
					srcDoc={ previewHtml }
					className="bp-lc-iframe"
					style={ { height: iframeHeight } }
					onLoad={ measurePositions }
					title={ __( 'Campaign Preview', 'better-payment' ) }
					sandbox="allow-same-origin allow-scripts"
				/>

				{ /* Transparent interaction overlay */ }
				<div
					className="bp-lc-overlay"
					style={ { height: iframeHeight } }
				>
					{ columns.map( ( column ) => (
						<SortableContext
							key={ column.id }
							items={ column.elements.map( ( el ) => el.id ) }
						>
							{ /* Column drop zone for panel-drag targets */ }
							<ColumnDropZone
								column={ column }
								rect={ columnRects[ column.id ] }
								onRequestElements={ onRequestElements }
							/>

							{ /* Per-element hotspots */ }
							{ column.elements.map( ( element, index ) => (
								<ElementHotspot
									key={ element.id }
									element={ element }
									columnId={ column.id }
									index={ index }
									totalElements={ column.elements.length }
									rect={ elementRects[ element.id ] }
									isSelected={
										element.id === selectedElementId
									}
									isDropTarget={
										element.id === dropTargetElementId
									}
									isHighlighted={ (
										highlightedElementIds || []
									).includes( element.id ) }
									isProLocked={ isElementLocked(
										getElement( element.type ),
										proEnabled
									) }
									dispatch={ dispatch }
									onRequestUpgrade={ onRequestUpgrade }
								/>
							) ) }
						</SortableContext>
					) ) }
				</div>
			</div>
		</div>
	);
}
