import {
	useReducer,
	useEffect,
	useCallback,
	useState,
	useRef,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import {
	DndContext,
	DragOverlay,
	PointerSensor,
	useSensor,
	useSensors,
	closestCenter,
} from '@dnd-kit/core';

import { reducer, initialState } from './store/campaignStore';
import { getElement, getDefaultSettings } from './fields/FieldRegistry';
import Toolbar from './components/Toolbar';
import LiveCanvas from './components/LiveCanvas';
import RightSidebar from './components/RightSidebar';
import TemplatesTab, { AI_TEMPLATE } from './components/TemplatesTab';
import CampaignNameModal from './components/CampaignNameModal';
import TitleRequiredModal from './components/TitleRequiredModal';
import DonateUrlWarningModal from './components/DonateUrlWarningModal';
import UnsavedChangesModal from './components/UnsavedChangesModal';
import SettingsTab from './components/SettingsTab';
import UpgradeModal from './components/UpgradeModal';
import { isProEnabled } from './utils/proEnabled';

// Read once at module scope — entitlement cannot change inside a page load.
// Do NOT re-inline this as `proEnabled === true`; see utils/proEnabled.js for the
// bug that idiom shipped.
const PRO_ENABLED = isProEnabled();

export default function App( {
	campaignId,
	restUrl,
	nonce,
	adminUrl,
	campaignsUrl,
	start,
} ) {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	// `start=ai` (from the "Campaign With AI" button) opens a new campaign straight
	// into the editor with the AI Assistant ready, skipping the template picker.
	const startWithAI = ! campaignId && start === 'ai';

	// Mutable campaign ID — starts from prop but updated after first save of a new campaign.
	const [ currentCampaignId, setCurrentCampaignId ] = useState(
		campaignId || 0
	);

	// UI state
	const [ activeTab, setActiveTab ] = useState(
		campaignId || startWithAI ? 'editor' : 'templates'
	);
	// Once the editor has been shown, keep its surface MOUNTED and just toggle its
	// visibility — switching top-nav tabs must not unmount LiveCanvas/RightSidebar.
	// Unmounting threw away LiveCanvas's local preview state (previewHtml, rects),
	// so every return to Editor re-fetched /campaigns/preview and reloaded the
	// iframe from scratch (the visible "loading" flash). Latching keeps a brand-new
	// campaign that opens on Templates from mounting the editor before it's visited.
	// Same rule the AI panel already follows across sidebar tabs — hide, never
	// unmount (see CLAUDE.md → AIAssistantPanel).
	const [ editorMounted, setEditorMounted ] = useState( () =>
		Boolean( campaignId || startWithAI )
	);
	// Which right-sidebar panel is showing (AI / Elements / Customize). Owned here
	// rather than inside RightSidebar because the canvas drives it too: the empty
	// column's "+" hint opens the Elements tab, which is the only place to get an
	// element from. Passing the raw useState setter keeps its identity stable —
	// RightSidebar's select-an-element effect depends on that (see its comment).
	const [ sidebarTab, setSidebarTab ] = useState( 'ai' );
	// Bumped whenever the canvas empty-state "+" opens the AI panel, so the panel
	// can move the caret into its composer. A counter (not a boolean) so every
	// click re-triggers the focus effect even if the tab is already 'ai'.
	const [ aiFocusSignal, setAiFocusSignal ] = useState( 0 );
	const [ isFullscreen, setIsFullscreen ] = useState( false );
	const [ nameModalOpen, setNameModalOpen ] = useState( false );
	const [ activeId, setActiveId ] = useState( null );
	const [ activeDragData, setActiveDragData ] = useState( null );
	const [ dropTargetElementId, setDropTargetElementId ] = useState( null );
	const [ previewModal, setPreviewModal ] = useState( {
		open: false,
		html: '',
		loading: false,
	} );
	const [ titleWarningOpen, setTitleWarningOpen ] = useState( false );
	const [ donateUrlWarning, setDonateUrlWarning ] = useState( {
		open: false,
		pendingStatus: null,
	} );
	const [ savingAs, setSavingAs ] = useState( null );
	const [ unsavedModalOpen, setUnsavedModalOpen ] = useState( false );
	const [ proModal, setProModal ] = useState( { open: false, type: '' } );

	// Configure apiFetch nonce synchronously during render (not in useEffect) so child
	// component effects can make authenticated requests on first mount.
	const nonceConfigured = useRef( false );
	if ( nonce && ! nonceConfigured.current ) {
		apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
		nonceConfigured.current = true;
	}

	// Load existing campaign
	useEffect( () => {
		if ( ! campaignId ) {
			if ( startWithAI ) {
				// Commit an empty AI campaign (ungates the editor/save controls) and
				// land in the editor, where the AI tab shows the Smart Prompt Wizard.
				dispatch( { type: 'APPLY_TEMPLATE', template: AI_TEMPLATE } );
			} else {
				dispatch( { type: 'OPEN_TEMPLATE_MODAL' } );
			}
			return;
		}
		dispatch( { type: 'SET_INITIALIZING', value: true } );
		apiFetch( { path: `better-payment/v1/campaigns/${ campaignId }` } )
			.then( ( data ) => {
				dispatch( {
					type: 'LOAD_CAMPAIGN',
					meta: { title: data.title || '', ...( data.meta || {} ) },
					layout: data.meta?.bpc_fields_layout || null,
					postStatus: data.post_status || 'draft',
					viewUrl: data.view_url || '',
				} );
				if ( ! data.meta?.bpc_template_key ) {
					setActiveTab( 'templates' );
				}
			} )
			.catch( () => {
				dispatch( { type: 'SET_INITIALIZING', value: false } );
				dispatch( { type: 'OPEN_TEMPLATE_MODAL' } );
			} );
	}, [ campaignId ] );

	// Clear the AI "just changed" highlight after a moment so the flash is brief.
	useEffect( () => {
		if ( ! state.highlightedElementIds?.length ) return undefined;
		const t = setTimeout(
			() => dispatch( { type: 'CLEAR_AI_HIGHLIGHT' } ),
			2600
		);
		return () => clearTimeout( t );
	}, [ state.highlightedElementIds ] );

	// Fullscreen toggle — hide WP admin chrome
	const handleFullscreen = useCallback( () => {
		const next = ! isFullscreen;
		setIsFullscreen( next );
		document.body.classList.toggle( 'bp-fullscreen', next );
		document.documentElement.classList.toggle( 'bp-fullscreen', next );
	}, [ isFullscreen ] );

	// An empty column's "+" hint asks for the Elements tab — the only place an
	// element can be picked up from. Stable identity so the canvas doesn't
	// re-render on every parent render.
	const showElementsTab = useCallback( () => setSidebarTab( 'fields' ), [] );

	// The canvas drives the sidebar too: an AI campaign's empty-state "+" focuses
	// the assistant. Stable identity, same reason as showElementsTab — RightSidebar
	// leaves the tab setter out of an effect's deps, so an inline arrow here would
	// re-fire it every render. Bumping aiFocusSignal alongside the tab switch tells
	// the panel to move the caret into its composer (see AIAssistantPanel).
	const showAITab = useCallback( () => {
		setSidebarTab( 'ai' );
		setAiFocusSignal( ( n ) => n + 1 );
	}, [] );

	// Shared by every upgrade CTA: the locked palette card, the Customize panel's
	// banner, and the canvas lock overlay. Stable identity so passing it down
	// cannot retrigger a child's effects.
	const requestUpgrade = useCallback(
		( type ) => setProModal( { open: true, type } ),
		[]
	);

	// Tag <body> with the active tab so the WP admin backstop (body/#wpwrap)
	// can match each tab's surface: white for the editor (white canvas + panel),
	// grey for templates/settings. This prevents the builder's background from
	// seaming against WordPress's own background in the area below the frame.
	useEffect( () => {
		document.body.classList.toggle(
			'bp-cb-tab-editor',
			activeTab === 'editor'
		);
		return () => document.body.classList.remove( 'bp-cb-tab-editor' );
	}, [ activeTab ] );

	// Latch the editor surface as mounted the first time it's shown, so it stays
	// mounted (visibility-toggled) thereafter — see the editorMounted note above.
	useEffect( () => {
		if ( activeTab === 'editor' ) setEditorMounted( true );
	}, [ activeTab ] );

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Returns true when the layout contains a donation_form element with no URL
	 * and the campaign has no payment page configured (bpc_form_page_id === 0).
	 */
	const hasMissingDonateUrl = useCallback( ( layout, meta ) => {
		if ( meta?.bpc_form_page_id ) return false;
		const columns = layout?.columns ?? [];
		return columns.some( ( col ) =>
			( col.elements ?? [] ).some(
				( el ) => el.type === 'donation_form' && ! el.settings?.url
			)
		);
	}, [] );

	// ── Save ──────────────────────────────────────────────────────────────────

	const handleSave = useCallback(
		async ( postStatus, { skipUrlCheck = false } = {} ) => {
			if ( ! state.meta.title?.trim() ) {
				setTitleWarningOpen( true );
				return false;
			}

			// Warn on publish if donate button has no URL and no payment page is set.
			// Skipped when saving-and-exiting so the exit flow is never blocked by this.
			if (
				! skipUrlCheck &&
				postStatus === 'publish' &&
				hasMissingDonateUrl( state.layout, state.meta )
			) {
				setDonateUrlWarning( {
					open: true,
					pendingStatus: postStatus,
				} );
				return false;
			}

			setSavingAs( postStatus );
			dispatch( { type: 'SET_SAVING', isSaving: true } );

			const body = {
				title: state.meta.title,
				post_status: postStatus,
				...state.meta,
				bpc_fields_layout: JSON.stringify( state.layout ),
			};

			try {
				let data;
				if ( currentCampaignId ) {
					data = await apiFetch( {
						path: `better-payment/v1/campaigns/${ currentCampaignId }`,
						method: 'POST',
						data: body,
					} );
				} else {
					data = await apiFetch( {
						path: 'better-payment/v1/campaigns',
						method: 'POST',
						data: body,
					} );
					if ( data?.id ) {
						setCurrentCampaignId( data.id );
						history.pushState(
							null,
							'',
							`${ adminUrl }admin.php?page=bp-campaign-builder&campaign_id=${ data.id }`
						);
					}
				}
				dispatch( {
					type: 'SAVE_SUCCESS',
					postStatus: data?.post_status || postStatus,
					viewUrl: data?.view_url || '',
				} );
				setSavingAs( null );
				toast.success(
					postStatus === 'publish'
						? __(
								'Campaign published successfully.',
								'better-payment'
						  )
						: __( 'Campaign saved as draft.', 'better-payment' ),
					{ autoClose: 3000 }
				);
				return true;
			} catch ( err ) {
				dispatch( {
					type: 'SAVE_ERROR',
					error: err?.message || 'Save failed',
				} );
				setSavingAs( null );
				toast.error(
					__( 'Save failed. Please try again.', 'better-payment' ),
					{ autoClose: 4000 }
				);
				return false;
			}
		},
		[ currentCampaignId, state, adminUrl ]
	);

	// ── Preview ───────────────────────────────────────────────────────────────

	const handlePreview = useCallback( async () => {
		if ( ! currentCampaignId ) {
			window.alert(
				__(
					'Please save the campaign first to preview it.',
					'better-payment'
				)
			);
			return;
		}
		setPreviewModal( { open: true, html: '', loading: true } );
		try {
			// Use build_preview_document() so the preview includes the campaign title,
			// correct CSS, and reflects current unsaved meta changes.
			const res = await apiFetch( {
				path: 'better-payment/v1/campaigns/preview',
				method: 'POST',
				data: {
					campaign_id: currentCampaignId,
					layout: state.layout,
					meta: state.meta,
				},
			} );
			setPreviewModal( {
				open: true,
				html: res.html || '',
				loading: false,
			} );
		} catch {
			setPreviewModal( { open: false, html: '', loading: false } );
		}
	}, [ currentCampaignId, state.layout, state.meta ] );

	const handleExit = () => {
		if ( state.isDirty ) {
			setUnsavedModalOpen( true );
			return;
		}
		window.location.href = campaignsUrl;
	};

	const handleCopyShortcode = () => {
		const code = `[bp_campaign id="${ currentCampaignId }"]`;
		if ( ! navigator.clipboard ) {
			return Promise.reject();
		}
		return navigator.clipboard.writeText( code );
	};

	const handleNameSave = ( newName ) => {
		dispatch( { type: 'UPDATE_META', payload: { title: newName } } );
	};

	// ── DnD sensors ──────────────────────────────────────────────────────────

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 6 } } )
	);

	const handleDragStart = ( { active } ) => {
		setActiveId( active.id );
		setActiveDragData( active.data.current );
	};

	const handleDragOver = ( { over } ) => {
		if ( ! activeDragData || activeDragData.source !== 'panel' ) return;
		setDropTargetElementId(
			over?.data?.current?.source === 'canvas' ? over.id : null
		);
	};

	const handleDragEnd = ( { active, over } ) => {
		setActiveId( null );
		setActiveDragData( null );
		setDropTargetElementId( null );

		if ( ! over ) return;

		const activeData = active.data.current;
		const overData = over.data.current;

		if ( activeData?.source === 'panel' ) {
			// A Pro element dropped by a free user IS added — with its default
			// settings, a mock preview on the canvas and its full control set
			// shown disabled in the sidebar. Refusing the drop (as this used to)
			// meant the only way to ever see a Pro element was to have had Pro,
			// so the palette advertised three widgets a free user could never
			// look at.
			//
			// A drop deliberately does NOT open the upgrade modal. Interrupting a
			// drag the moment it lands throws a dialog over the thing the user
			// just placed, before they can see it — the modal is an upsell, and an
			// upsell that hides the product it is selling does not work. Clicking a
			// locked palette card still opens it (that click has no other meaning),
			// as does the CTA on the locked-element banner in the sidebar.
			//
			// Nothing here grants function: the renderer drops these elements
			// from the public frontend entirely, and MetaBox::enforce_pro_entitlement()
			// pins their settings to the defaults on save.
			const targetColumnId =
				overData?.columnId || over.id.replace( /^col-/, '' );
			if ( ! targetColumnId ) return;

			let insertAt;
			if ( overData?.source === 'canvas' ) {
				const col = state.layout.columns.find(
					( c ) => c.id === targetColumnId
				);
				if ( col ) {
					const idx = col.elements.findIndex(
						( el ) => el.id === over.id
					);
					if ( idx !== -1 ) insertAt = idx;
				}
			}

			dispatch( {
				type: 'ADD_ELEMENT',
				columnId: targetColumnId,
				elementType: activeData.elementType,
				defaultSettings:
					activeData.defaultSettings ||
					getDefaultSettings( activeData.elementType ),
				insertAt,
			} );
			return;
		}

		if ( activeData?.source === 'canvas' ) {
			const fromColumnId = activeData.columnId;
			const toColumnId =
				overData?.columnId || over.id.replace( /^col-/, '' );

			if ( ! toColumnId ) return;

			if ( fromColumnId === toColumnId ) {
				const col = state.layout.columns.find(
					( c ) => c.id === fromColumnId
				);
				if ( ! col ) return;
				const oldIndex = col.elements.findIndex(
					( el ) => el.id === active.id
				);
				const newIndex = col.elements.findIndex(
					( el ) => el.id === over.id
				);
				if (
					oldIndex === -1 ||
					newIndex === -1 ||
					oldIndex === newIndex
				)
					return;
				dispatch( {
					type: 'REORDER_ELEMENTS',
					columnId: fromColumnId,
					oldIndex,
					newIndex,
				} );
			} else {
				const toCol = state.layout.columns.find(
					( c ) => c.id === toColumnId
				);
				const insertAt = toCol ? toCol.elements.length : 0;
				dispatch( {
					type: 'MOVE_ELEMENT',
					fromColumnId,
					toColumnId,
					elementId: active.id,
					insertAt,
				} );
			}
		}
	};

	const activeDef =
		activeDragData?.source === 'panel'
			? getElement( activeDragData?.elementType )
			: null;
	const activeElDef =
		activeDragData?.source === 'canvas'
			? getElement( activeDragData?.element?.type )
			: null;
	const dragLabel = activeDef?.label || activeElDef?.label || '';
	const dragIcon = activeDef?.icon || activeElDef?.icon || 'block-default';

	return (
		<DndContext
			sensors={ sensors }
			collisionDetection={ closestCenter }
			onDragStart={ handleDragStart }
			onDragOver={ handleDragOver }
			onDragEnd={ handleDragEnd }
		>
			<div
				className={ `bp-campaign-builder bp-campaign-builder--${ activeTab }` }
			>
				<Toolbar
					meta={ state.meta }
					isDirty={ state.isDirty }
					isSaving={ state.isSaving }
					savingAs={ savingAs }
					activeTab={ activeTab }
					isFullscreen={ isFullscreen }
					templateChosen={ state.templateChosen }
					onTabChange={ setActiveTab }
					onMetaChange={ ( patch ) =>
						dispatch( { type: 'UPDATE_META', payload: patch } )
					}
					onOpenNameModal={ () => setNameModalOpen( true ) }
					onCopyShortcode={ handleCopyShortcode }
					onPreview={ handlePreview }
					onSave={ handleSave }
					onFullscreen={ handleFullscreen }
					onExit={ handleExit }
					campaignId={ currentCampaignId }
					postStatus={ state.postStatus }
					viewUrl={ state.viewUrl }
				/>

				{ state.saveError && (
					<div className="bp-cb-error-bar">
						{ __( 'Save failed: ', 'better-payment' ) }
						{ state.saveError }
					</div>
				) }

				{ activeTab === 'templates' && (
					<TemplatesTab
						dispatch={ dispatch }
						appliedTemplateKey={ state.appliedTemplateKey }
						onTemplateApplied={ ( template ) => {
							setActiveTab( 'editor' );
							// Land the user on the right sidebar tab for what
							// they just picked: the AI assistant for an AI
							// campaign, otherwise the Elements palette so they
							// can start building on the blank/prebuilt layout.
							if ( template?.key === AI_TEMPLATE.key ) {
								showAITab();
							} else {
								showElementsTab();
							}
						} }
					/>
				) }

				{ editorMounted && (
					<div
						className={ `bp-cb-body${
							activeTab === 'editor' ? '' : ' bp-cb-body--hidden'
						}` }
					>
						<div className="bp-cb-canvas-area">
							{ state.isInitializing ? (
								<div className="bp-cb-canvas-initializing">
									<span className="dashicons dashicons-update bp-cb-canvas-initializing__spin" />
								</div>
							) : (
								<LiveCanvas
									state={ state }
									active={ activeTab === 'editor' }
									campaignId={ currentCampaignId }
									selectedElementId={
										state.selectedElementId
									}
									highlightedElementIds={
										state.highlightedElementIds
									}
									dispatch={ dispatch }
									dropTargetElementId={ dropTargetElementId }
									onRequestElements={ showElementsTab }
									onRequestAI={ showAITab }
									sidebarTab={ sidebarTab }
									proEnabled={ PRO_ENABLED }
									onRequestUpgrade={ requestUpgrade }
								/>
							) }
						</div>

						<RightSidebar
							state={ state }
							meta={ state.meta }
							layout={ state.layout }
							activeTab={ sidebarTab }
							onTabChange={ setSidebarTab }
							selectedElementId={ state.selectedElementId }
							selectedColumnId={ state.selectedColumnId }
							dispatch={ dispatch }
							campaignId={ currentCampaignId }
							adminUrl={ adminUrl }
							proEnabled={ PRO_ENABLED }
							onRequestUpgrade={ requestUpgrade }
							aiFocusSignal={ aiFocusSignal }
						/>
					</div>
				) }

				{ activeTab === 'settings' && (
					<div className="bp-cb-settings-view">
						<SettingsTab
							meta={ state.meta }
							dispatch={ dispatch }
						/>
					</div>
				) }

				{ nameModalOpen && (
					<CampaignNameModal
						currentName={ state.meta.title }
						onSave={ handleNameSave }
						onClose={ () => setNameModalOpen( false ) }
					/>
				) }

				{ titleWarningOpen && (
					<TitleRequiredModal
						onClose={ () => setTitleWarningOpen( false ) }
					/>
				) }

				{ donateUrlWarning.open && (
					<DonateUrlWarningModal
						postStatus={ state.postStatus }
						onCancel={ () =>
							setDonateUrlWarning( {
								open: false,
								pendingStatus: null,
							} )
						}
						onProceed={ () => {
							const status = donateUrlWarning.pendingStatus;
							setDonateUrlWarning( {
								open: false,
								pendingStatus: null,
							} );
							// Skip the URL check this time and save directly.
							setSavingAs( status );
							dispatch( { type: 'SET_SAVING', isSaving: true } );
							const body = {
								title: state.meta.title,
								post_status: status,
								...state.meta,
								bpc_fields_layout: JSON.stringify(
									state.layout
								),
							};
							( currentCampaignId
								? apiFetch( {
										path: `better-payment/v1/campaigns/${ currentCampaignId }`,
										method: 'POST',
										data: body,
								  } )
								: apiFetch( {
										path: 'better-payment/v1/campaigns',
										method: 'POST',
										data: body,
								  } )
							)
								.then( ( data ) => {
									if ( ! currentCampaignId && data?.id ) {
										setCurrentCampaignId( data.id );
										history.pushState(
											null,
											'',
											`${ adminUrl }admin.php?page=bp-campaign-builder&campaign_id=${ data.id }`
										);
									}
									dispatch( {
										type: 'SAVE_SUCCESS',
										postStatus: data?.post_status || status,
										viewUrl: data?.view_url || '',
									} );
									setSavingAs( null );
									toast.success(
										__(
											'Campaign published successfully.',
											'better-payment'
										),
										{ autoClose: 3000 }
									);
								} )
								.catch( ( err ) => {
									dispatch( {
										type: 'SAVE_ERROR',
										error: err?.message || 'Save failed',
									} );
									setSavingAs( null );
									toast.error(
										__(
											'Save failed. Please try again.',
											'better-payment'
										),
										{ autoClose: 4000 }
									);
								} );
						} }
					/>
				) }

				{ previewModal.open && (
					<PreviewModal
						html={ previewModal.html }
						loading={ previewModal.loading }
						onClose={ () =>
							setPreviewModal( {
								open: false,
								html: '',
								loading: false,
							} )
						}
					/>
				) }

				{ proModal.open && (
					<UpgradeModal
						type={ proModal.type }
						onClose={ () =>
							setProModal( { open: false, type: '' } )
						}
					/>
				) }

				{ unsavedModalOpen && (
					<UnsavedChangesModal
						onSaveAndExit={ async () => {
							setUnsavedModalOpen( false );
							const saved = await handleSave(
								state.postStatus || 'draft',
								{ skipUrlCheck: true }
							);
							if ( saved ) {
								window.location.href = campaignsUrl;
							}
						} }
						onCancel={ () => setUnsavedModalOpen( false ) }
					/>
				) }
			</div>

			<DragOverlay>
				{ activeId ? (
					<div className="bp-drag-overlay">
						<span
							className={ `dashicons dashicons-${ dragIcon }` }
						/>
						<span>{ dragLabel }</span>
					</div>
				) : null }
			</DragOverlay>

			<ToastContainer
				position="bottom-right"
				autoClose={ 3000 }
				hideProgressBar={ false }
				newestOnTop={ false }
				closeOnClick
				pauseOnHover
			/>
		</DndContext>
	);
}

function PreviewModal( { html, loading, onClose } ) {
	return (
		<div className="bp-preview-modal-overlay" onClick={ onClose }>
			<div
				className="bp-preview-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				{ /* Header bar — close button lives here, never overlaps iframe */ }
				<div className="bp-preview-modal__header">
					<span className="bp-preview-modal__title">
						{ __( 'Campaign Preview', 'better-payment' ) }
					</span>
					<button
						className="bp-preview-modal__close"
						onClick={ onClose }
						aria-label={ __( 'Close preview', 'better-payment' ) }
					>
						<svg
							width="16"
							height="16"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2.5"
							strokeLinecap="round"
						>
							<line x1="18" y1="6" x2="6" y2="18" />
							<line x1="6" y1="6" x2="18" y2="18" />
						</svg>
					</button>
				</div>

				<div className="bp-preview-modal__body">
					{ loading ? (
						<div className="bp-preview-loading">
							<span className="dashicons dashicons-update spin"></span>
							{ __( 'Generating preview…', 'better-payment' ) }
						</div>
					) : (
						// allow-scripts is required, not optional: the preview is
						// the campaign as it renders live, and embedded players
						// (YouTube/Vimeo) plus payment buttons are JavaScript.
						// Without it the YouTube player can't boot and falls back
						// to its <noscript> "enable JavaScript" error. Matches the
						// live canvas iframe (LiveCanvas.js), which uses the same
						// pair for the same trusted, same-origin admin document.
						<iframe
							srcDoc={ html }
							className="bp-preview-modal__iframe"
							title={ __( 'Campaign Preview', 'better-payment' ) }
							sandbox="allow-same-origin allow-scripts"
						/>
					) }
				</div>
			</div>
		</div>
	);
}
