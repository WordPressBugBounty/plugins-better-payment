import {
	useState,
	useEffect,
	useMemo,
	useRef,
	useCallback,
	createPortal,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDraggable } from '@dnd-kit/core';
import { getAllElements, getElement } from '../fields/FieldRegistry';
import SuggestedAmountsEditor from './SuggestedAmountsEditor';
import RepeaterControl from './RepeaterControl';
import { openWpMedia } from '../utils/media';
import { getGlobalCurrencySymbol } from '../utils/currency';
import { isElementLocked, lockedElementFeatures } from '../utils/proEnabled';
import {
	backgroundFieldState,
	resolveThemeBackground,
	themeClassForKey,
} from '../utils/themeBackground';
import ColorPicker from './ColorPicker';
import ProCrownIcon from './ProCrownIcon';
import AIAssistantPanel from '../ai/AIAssistantPanel';
import AiIcon from '../ai/AiIcon';
import ElementAIActions from '../ai/ElementAIActions';
import { aiGetConfig } from '../ai/aiClient';
import { isAiUsable, readAiConfigSeed } from '../utils/aiConfig';

const CUSTOM_ICONS = {
	progress_bar: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<rect
				x="2"
				y="10"
				width="20"
				height="4"
				rx="2"
				fill="currentColor"
				fillOpacity="0.22"
			/>
			<rect
				x="2"
				y="10"
				width="12"
				height="4"
				rx="2"
				fill="currentColor"
			/>
			<circle cx="14" cy="12" r="3" fill="currentColor" />
		</svg>
	),
	donation_form: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				d="M12 9c-.5-1.3-1.8-2.2-3.2-2.2C6.9 6.8 5.4 8.3 5.4 10.1c0 2.3 2.3 4.4 6.6 7.1 4.3-2.7 6.6-4.8 6.6-7.1 0-1.8-1.5-3.3-3.4-3.3C13.8 6.8 12.5 7.7 12 9z"
				fill="currentColor"
			/>
			<rect
				x="6"
				y="18.5"
				width="12"
				height="3"
				rx="1.5"
				stroke="currentColor"
				strokeWidth="1.4"
				fill="none"
			/>
			<line
				x1="9.5"
				y1="18.5"
				x2="9.5"
				y2="17"
				stroke="currentColor"
				strokeWidth="1.3"
				strokeLinecap="round"
			/>
			<line
				x1="12"
				y1="18.5"
				x2="12"
				y2="16.5"
				stroke="currentColor"
				strokeWidth="1.3"
				strokeLinecap="round"
			/>
			<line
				x1="14.5"
				y1="18.5"
				x2="14.5"
				y2="17"
				stroke="currentColor"
				strokeWidth="1.3"
				strokeLinecap="round"
			/>
		</svg>
	),
};

/**
 * Right sidebar — three tabs:
 *   "AI"        — the assistant panel (the default; see ai/AIAssistantPanel)
 *   "Elements"  — 2-col grid of draggable element cards
 *   "Customize" — schema-driven settings for the selected element or column
 *
 * The active tab is **controlled by App** (`activeTab` / `onTabChange`) rather
 * than held locally, because the canvas opens it too: an empty column's "+" hint
 * switches here to Elements, which is the only place to pick an element up from.
 */
export default function RightSidebar( {
	state,
	meta,
	layout,
	activeTab,
	onTabChange,
	selectedElementId,
	selectedColumnId,
	dispatch,
	proEnabled = false,
	onRequestUpgrade,
	campaignId,
	adminUrl,
	aiFocusSignal = 0,
} ) {
	const setActiveTab = onTabChange;
	// The tab's own "off" indicator. Seeded SYNCHRONOUSLY from the builder payload
	// (readAiConfigSeed → CPT::ai_config_seed) so the red dot + dimmed tab paint on
	// the first render, exactly like the panel's notice. It used to default to
	// `enabled: true` and flip only after the async /ai/config fetch resolved,
	// which flashed an enabled tab for a beat before correcting to "off". Missing
	// seed defaults to on (no false dot before we know). AIAssistantPanel still
	// fetches its own full config for the notice/disabled-input logic that actually
	// blocks requests; this is only the tab affordance.
	const [ aiFlags, setAiFlags ] = useState(
		() => readAiConfigSeed() || { enabled: true, configured: true }
	);

	useEffect( () => {
		let mounted = true;
		aiGetConfig()
			.then( ( cfg ) => {
				if ( mounted && cfg ) {
					setAiFlags( {
						enabled: cfg.enabled !== false,
						configured: cfg.configured !== false,
					} );
				}
			} )
			.catch( () => {} );
		return () => {
			mounted = false;
		};
	}, [] );

	// "Off" = the feature can't be used: either the toggle is off OR the active
	// provider has no API key. Mirrors the panel's own `blocked` (aiDisabled ||
	// notConfigured) so the tab and the panel never disagree, and the tooltip
	// names the specific fix — "enable it" vs. "add a key".
	const aiBlocked = ! isAiUsable( aiFlags );
	const aiTooltip = ! aiFlags.enabled
		? __(
				'AI Assistant is disabled — enable it in Settings → AI Assistant',
				'better-payment'
		  )
		: __(
				'AI Assistant needs an API key — add one in Settings → AI Assistant',
				'better-payment'
		  );

	// Selecting an element on the canvas jumps to its settings.
	//
	// `setActiveTab` is deliberately NOT a dependency: it is the parent's raw
	// useState setter, whose identity is stable. Passing an inline arrow from
	// App instead would re-run this on every render and pin the panel to
	// Customize for as long as an element is selected — the user could never
	// reach the Elements tab, including via the canvas "+" hint.
	useEffect( () => {
		if ( selectedElementId ) {
			setActiveTab( 'customization' );
		}
	}, [ selectedElementId ] );

	return (
		<div className="bp-cb-right-sidebar">
			<div className="bp-cb-right-sidebar__tabs">
				<button
					className={ `bp-cb-sidebar-tab${
						activeTab === 'ai' ? ' is-active' : ''
					}${ aiBlocked ? ' bp-cb-sidebar-tab--ai-off' : '' }` }
					onClick={ () => setActiveTab( 'ai' ) }
					title={ aiBlocked ? aiTooltip : undefined }
				>
					<AiIcon
						size={ 15 }
						className="bp-cb-sidebar-tab__ai-icon"
					/>
					{ __( 'AI', 'better-payment' ) }
					{ aiBlocked && (
						<span className="bp-cb-sidebar-tab__ai-off-dot" />
					) }
				</button>
				<button
					className={ `bp-cb-sidebar-tab${
						activeTab === 'fields' ? ' is-active' : ''
					}` }
					onClick={ () => setActiveTab( 'fields' ) }
				>
					{ __( 'Elements', 'better-payment' ) }
				</button>
				<button
					className={ `bp-cb-sidebar-tab${
						activeTab === 'customization' ? ' is-active' : ''
					}` }
					onClick={ () => setActiveTab( 'customization' ) }
				>
					{ __( 'Customize', 'better-payment' ) }
				</button>
			</div>

			{ /* The AI panel stays MOUNTED across sidebar-tab switches — hidden,
			     not unmounted — so the conversation (including the very first
			     "create campaign" prompt, which lives only in the panel's local
			     state until the campaign is saved) survives moving to Elements /
			     Customize and back. Unmounting it here used to throw that state
			     away. It manages its own scroll + pinned composer, so it fills the
			     sidebar column directly rather than the padded, scrolling body. */ }
			<div
				className="bp-cb-ai-host"
				style={ { display: activeTab === 'ai' ? 'flex' : 'none' } }
				aria-hidden={ activeTab !== 'ai' }
			>
				<AIAssistantPanel
					embedded
					state={ state }
					dispatch={ dispatch }
					campaignId={ campaignId }
					adminUrl={ adminUrl }
					focusSignal={ aiFocusSignal }
				/>
			</div>

			{ activeTab !== 'ai' && (
				<div className="bp-cb-right-sidebar__body">
					{ activeTab === 'fields' ? (
						<FieldsGrid
							proEnabled={ proEnabled }
							onRequestUpgrade={ onRequestUpgrade }
						/>
					) : (
						<CustomizationPanel
							state={ state }
							campaignId={ campaignId }
							meta={ meta }
							layout={ layout }
							selectedElementId={ selectedElementId }
							selectedColumnId={ selectedColumnId }
							dispatch={ dispatch }
							proEnabled={ proEnabled }
							onRequestUpgrade={ onRequestUpgrade }
							aiUsable={ ! aiBlocked }
							aiLockedReason={ aiTooltip }
						/>
					) }
				</div>
			) }
		</div>
	);
}

// ── Input Fields tab ───────────────────────────────────────────────────────────

function FieldsGrid( { proEnabled = false, onRequestUpgrade } ) {
	const elements = getAllElements();

	return (
		<div className="bp-cb-fields-grid">
			{ elements.map( ( el ) => (
				<DraggableFieldCard
					key={ el.type }
					element={ el }
					locked={ isElementLocked( el, proEnabled ) }
					onRequestUpgrade={ onRequestUpgrade }
				/>
			) ) }
		</div>
	);
}

function DraggableFieldCard( { element, locked = false, onRequestUpgrade } ) {
	const { attributes, listeners, setNodeRef, isDragging } = useDraggable( {
		id: `panel-${ element.type }`,
		data: {
			source: 'panel',
			elementType: element.type,
			defaultSettings: element.defaultSettings,
		},
	} );

	const handleClick = () => {
		if ( locked && typeof onRequestUpgrade === 'function' ) {
			onRequestUpgrade( element.type );
		}
	};

	return (
		<div
			ref={ setNodeRef }
			className={ `bp-cb-field-card${ isDragging ? ' is-dragging' : '' }${
				locked ? ' is-pro-locked' : ''
			}` }
			title={
				locked
					? `${ element.label } — ${ __(
							'Pro feature',
							'better-payment'
					  ) }`
					: element.label
			}
			onClick={ handleClick }
			{ ...listeners }
			{ ...attributes }
		>
			{ locked && <ProCrownIcon /> }
			{ CUSTOM_ICONS[ element.type ] ? (
				<span className="bp-cb-field-card__svg-icon">
					{ CUSTOM_ICONS[ element.type ] }
				</span>
			) : (
				<span
					className={ `dashicons dashicons-${ element.icon }` }
				></span>
			) }
			<span className="bp-cb-field-card__label">{ element.label }</span>
		</div>
	);
}

// ── Input Customization tab ────────────────────────────────────────────────────

function CustomizationPanel( {
	state,
	campaignId,
	meta,
	layout,
	selectedElementId,
	selectedColumnId,
	dispatch,
	proEnabled = false,
	onRequestUpgrade,
	aiUsable = false,
	aiLockedReason = '',
} ) {
	const [ innerTab, setInnerTab ] = useState( 'general' );

	let selectedElement = null;
	if ( selectedElementId && layout?.columns ) {
		for ( const col of layout.columns ) {
			const found = col.elements.find(
				( el ) => el.id === selectedElementId
			);
			if ( found ) {
				selectedElement = found;
				break;
			}
		}
	}

	const def = selectedElement ? getElement( selectedElement.type ) : null;
	// A Pro element on a site without Pro. Lite registers only a lock stub for it
	// (no settingsSchema, a `proFeatures` list), so the panel shows the upgrade
	// banner instead of a control list. With Pro active, Pro's full definition
	// replaces the stub and the element's real controls render below.
	const isProLocked = isElementLocked( def, proEnabled );
	const proFeatures = isProLocked ? lockedElementFeatures( def ) : [];

	return (
		<div className="bp-cb-element-settings">
			{ /* General / Advanced tabs — always visible */ }
			<div className="bp-cb-inner-tabs">
				<button
					className={ `bp-cb-inner-tab${
						innerTab === 'general' ? ' is-active' : ''
					}` }
					onClick={ () => setInnerTab( 'general' ) }
				>
					{ __( 'General', 'better-payment' ) }
				</button>
				<button
					className={ `bp-cb-inner-tab${
						innerTab === 'advanced' ? ' is-active' : ''
					}` }
					onClick={ () => setInnerTab( 'advanced' ) }
				>
					{ __( 'Advanced', 'better-payment' ) }
				</button>
			</div>

			{ innerTab === 'general' &&
				( selectedElement ? (
					<>
						<div className="bp-cb-element-settings__header">
							<span
								className={ `dashicons dashicons-${
									def?.icon || 'block-default'
								}` }
							/>
							<span className="bp-cb-element-settings__title">
								{ def?.label ||
									__( 'Element Settings', 'better-payment' ) }
							</span>
						</div>

						{ /* Free-only. `isProLocked` is `def.pro && ! proEnabled`,
							     so with Pro active this whole banner is gone and a
							     Pro element's panel is indistinguishable from a free
							     one's — which is the point: an entitled user should
							     never be sold what they already own.

							     The banner replaces the control list. Pro owns the
							     element's schema, so a free site has no controls to
							     show; the short feature list from the lock stub says
							     what Pro adds instead. A downgraded campaign's saved
							     settings are untouched (MetaBox restores them on
							     save) and still drive the canvas mock.

							     No "Remove element" here. It sat directly under the
							     upgrade CTA, which made "delete my work" the second
							     option in a sales pitch. Deletion belongs to the
							     canvas — the element's floating toolbar has the
							     trash action, same as every other element. */ }
						{ isProLocked && (
							<div className="bp-cb-pro-locked-notice">
								<span className="bp-cb-pro-locked-notice__badge">
									{ __( 'PRO', 'better-payment' ) }
								</span>
								<p className="bp-cb-pro-locked-notice__text">
									{ __(
										'This is a Better Payment Pro element. Preview only - activate Pro to configure it and display it on your live campaign.',
										'better-payment'
									) }
								</p>
								{ proFeatures.length > 0 && (
									<ul className="bp-cb-pro-locked-notice__features">
										{ proFeatures.map( ( feature ) => (
											<li key={ feature }>{ feature }</li>
										) ) }
									</ul>
								) }
								<button
									type="button"
									className="bp-cb-pro-locked-notice__cta"
									onClick={ () =>
										typeof onRequestUpgrade ===
											'function' &&
										onRequestUpgrade( selectedElement.type )
									}
								>
									{ __( 'Upgrade to Pro', 'better-payment' ) }
								</button>
							</div>
						) }

						{ /* AI edits an element by writing its settings, so it
							     has to follow the same lock. While AI is unusable
							     (toggle off or no API key) the row is shown as UI
							     only — buttons disabled, with a note naming the fix —
							     since any request would only fail. */ }
						{ ! isProLocked && (
							<ElementAIActions
								element={ selectedElement }
								elementLabel={ def?.label }
								campaignId={ campaignId }
								state={ state }
								dispatch={ dispatch }
								aiUsable={ aiUsable }
								lockedReason={ aiLockedReason }
							/>
						) }

						{ selectedElement.type === 'progress_bar' && (
							<p className="bp-cb-widget-note">
								{ __(
									'Note: The progress bar is always shown. When no campaign goal is set, the goal amount displays as 0 and the donated percentage stays at 0%.',
									'better-payment'
								) }
							</p>
						) }

						{ ( selectedElement.type === 'social_links' ||
							selectedElement.type === 'social_sharing' ) && (
							<p className="bp-cb-social-info">
								{ __(
									"Don't see a social network here that you use and would like added?",
									'better-payment'
								) }{ ' ' }
								<a
									href="https://wpdeveloper.com/support"
									target="_blank"
									rel="noreferrer"
								>
									{ __( 'Let us know!', 'better-payment' ) }
								</a>
							</p>
						) }

						{ ! isProLocked && (
							<ElementSettings
								element={ selectedElement }
								schema={ def?.settingsSchema ?? [] }
								meta={ meta }
								dispatch={ dispatch }
							/>
						) }
					</>
				) : (
					<div className="bp-cb-customization-empty">
						<svg
							width="40"
							height="40"
							viewBox="0 0 40 40"
							fill="none"
						>
							<circle
								cx="20"
								cy="20"
								r="18"
								stroke="#ddd"
								strokeWidth="2"
							/>
							<path
								d="M14 20h12M20 14v12"
								stroke="#ccc"
								strokeWidth="2"
								strokeLinecap="round"
							/>
						</svg>
						<p>
							{ __(
								'Click an element on the canvas to customize it.',
								'better-payment'
							) }
						</p>
					</div>
				) ) }

			{ innerTab === 'advanced' && (
				<ThemeColors meta={ meta } dispatch={ dispatch } />
			) }
		</div>
	);
}

// ── Theme Colors (Advanced tab) ────────────────────────────────────────────────

// Campaign background colour. The per-button colour lives on the Donate Button
// widget now (its "Button Color" control), not here.
//
// There is no flat default: an unset `bpc_color_background` means the template's
// theme paints the page, and the theme's own colour is read out of the loaded
// campaign CSS (utils/themeBackground.js). A hardcoded `#ffffff` here is what
// made every campaign report a white background it did not have, and made Reset
// write that white in as a real override.
const COLOR_FIELDS = [
	{
		key: 'bpc_color_background',
		label: __( 'Background', 'better-payment' ),
	},
];

const PICKER_W = 270;
const PICKER_H_EST = 320; // estimated picker height after size reduction

function ThemeColors( { meta, dispatch } ) {
	const [ openKey, setOpenKey ] = useState( null );
	const [ pickerPos, setPickerPos ] = useState( {
		top: 0,
		left: 0,
		caretLeft: 0,
		caretAtBottom: true,
	} );
	const swatchRefs = useRef( {} );
	const controlRefs = useRef( {} );
	const pickerRef = useRef( null );

	const openPickerFor = useCallback(
		( key ) => {
			if ( openKey === key ) {
				setOpenKey( null );
				return;
			}

			const el = swatchRefs.current[ key ];
			if ( el ) {
				const rect = el.getBoundingClientRect();

				// Horizontal: center picker over swatch, clamp to viewport.
				let left = rect.left + rect.width / 2 - PICKER_W / 2;
				left = Math.max(
					8,
					Math.min( left, window.innerWidth - PICKER_W - 8 )
				);
				const caretLeft = rect.left + rect.width / 2 - left;

				// Vertical: prefer above the swatch, fall back to below.
				const spaceAbove = rect.top - 10;
				const caretAtBottom = spaceAbove >= PICKER_H_EST;
				let top = caretAtBottom
					? rect.top - PICKER_H_EST - 10
					: rect.bottom + 10;

				// Clamp so the picker never leaves the viewport.
				top = Math.max(
					8,
					Math.min( top, window.innerHeight - PICKER_H_EST - 8 )
				);

				setPickerPos( { top, left, caretLeft, caretAtBottom } );
			}
			setOpenKey( key );
		},
		[ openKey ]
	);

	// Close picker when clicking outside both the popup and the color control.
	// The check covers the whole control (swatch + text input), not just the
	// swatch — otherwise a mousedown on the input reads as "outside" and closes
	// the popup, which the input's onClick then reopens (so it never toggles
	// shut). Anchoring on the control wrapper leaves onClick as the sole toggle.
	useEffect( () => {
		if ( ! openKey ) return;
		const handler = ( e ) => {
			const inPicker = pickerRef.current?.contains( e.target );
			const inControl = Object.values( controlRefs.current ).some(
				( el ) => el?.contains( e.target )
			);
			if ( ! inPicker && ! inControl ) setOpenKey( null );
		};
		document.addEventListener( 'mousedown', handler );
		return () => document.removeEventListener( 'mousedown', handler );
	}, [ openKey ] );

	const update = ( key, val ) =>
		dispatch( { type: 'UPDATE_META', payload: { [ key ]: val } } );

	// The theme's own page colour, probed out of the campaign CSS that is loaded
	// on this page — Lite's for the free themes, Pro's for its four. Recomputed
	// only when the campaign's template changes; the probe itself is cached per
	// theme class.
	const themeBackground = useMemo( () => {
		const themes = window.betterPaymentCampaignData?.templateThemes ?? {};
		return resolveThemeBackground(
			themeClassForKey( meta?.bpc_template_key, themes )
		);
	}, [ meta?.bpc_template_key ] );

	const activeField = COLOR_FIELDS.find( ( f ) => f.key === openKey );

	return (
		<div className="bp-theme-colors">
			<p className="bp-theme-colors__title">
				{ __( 'Theme Colors', 'better-payment' ) }
			</p>

			{ COLOR_FIELDS.map( ( { key, label } ) => {
				const field = backgroundFieldState( {
					override: meta[ key ],
					themeBackground,
				} );
				// A campaign whose theme paints nothing (a blank layout, a
				// pre-`-v2` design) inherits the site's background, so the row
				// says so instead of naming a colour nothing applies.
				const display =
					field.display || __( 'Theme default', 'better-payment' );
				return (
					<div key={ key } className="bp-theme-color-row">
						<span className="bp-theme-color-row__label">
							{ label }
						</span>
						<div
							className="bp-theme-color-row__control"
							ref={ ( el ) => {
								controlRefs.current[ key ] = el;
							} }
						>
							<button
								ref={ ( el ) => {
									swatchRefs.current[ key ] = el;
								} }
								type="button"
								className={ `bp-theme-color-row__swatch${
									openKey === key ? ' is-active' : ''
								}${ field.hasColor ? '' : ' is-unset' }` }
								style={
									field.hasColor
										? { background: field.hex }
										: undefined
								}
								aria-label={ label }
								title={
									field.isThemeDefault
										? __(
												'Set by the template theme — not overridden.',
												'better-payment'
										  )
										: undefined
								}
								onClick={ () => openPickerFor( key ) }
							/>
							<input
								type="text"
								className={ `bp-theme-color-row__input${
									field.isThemeDefault ? ' is-inherited' : ''
								}` }
								value={ display }
								readOnly
								onClick={ () => openPickerFor( key ) }
							/>
						</div>
					</div>
				);
			} ) }

			{ openKey &&
				activeField &&
				createPortal(
					<div
						ref={ pickerRef }
						className="bp-color-picker-popup"
						style={ {
							position: 'fixed',
							top: pickerPos.top,
							left: pickerPos.left,
							width: PICKER_W,
							zIndex: 999999,
						} }
					>
						<ColorPicker
							value={ meta[ openKey ] || themeBackground }
							defaultValue={ themeBackground || '#ffffff' }
							onChange={ ( hex ) => update( openKey, hex ) }
							// Clears the override instead of writing the
							// theme's colour in as one: the theme owns the
							// background again, gradient wash included, and a
							// later template change follows the new theme.
							onReset={ () => update( openKey, '' ) }
							resetLabel={ __(
								'Theme default',
								'better-payment'
							) }
							onClose={ () => setOpenKey( null ) }
						/>
						<div
							className={ `bp-color-picker-popup__caret${
								pickerPos.caretAtBottom ? '' : ' is-top'
							}` }
							style={ { left: pickerPos.caretLeft } }
						/>
					</div>,
					document.body
				) }
		</div>
	);
}

/**
 * Should a schema control be shown, given the element's current settings?
 *
 * A control may declare a `condition` object of `{ settingKey: expected }`. It
 * renders only when every entry matches the element's current settings; an array
 * expected value matches if the current value is any of its members:
 *
 *     [ 'key' => 'columns', 'type' => 'select', 'condition' => [ 'layout' => 'grid' ] ]
 *
 * Without this, settings that only apply to one mode (grid columns, ticker
 * speed) sit permanently in the panel implying they do something in every mode.
 *
 * A control with no `condition` always shows, so existing schemas are unaffected.
 *
 * @param {Object} control  Schema control.
 * @param {Object} settings The element's current settings.
 * @return {boolean} True when the control should render.
 */
export function isControlVisible( control, settings = {} ) {
	const condition = control?.condition;
	if ( ! condition || typeof condition !== 'object' ) return true;

	return Object.entries( condition ).every( ( [ key, expected ] ) => {
		const actual = settings?.[ key ];
		return Array.isArray( expected )
			? expected.includes( actual )
			: actual === expected;
	} );
}

function ElementSettings( { element, schema, meta, dispatch } ) {
	// Never rendered for a Pro-locked element — CustomizationPanel shows the
	// upgrade banner instead. Not a security boundary either way: that lives
	// server-side in MetaBox::enforce_pro_entitlement(), which re-checks
	// entitlement on save.
	const updateElement = ( patch ) => {
		dispatch( {
			type: 'UPDATE_ELEMENT_SETTINGS',
			elementId: element.id,
			settings: patch,
		} );
	};
	const updateMeta = ( patch ) => {
		dispatch( { type: 'UPDATE_META', payload: patch } );
	};
	const s = element.settings || {};

	if ( schema.length === 0 ) {
		return (
			<p className="bp-cb-customization-hint">
				{ __( 'No settings for this element.', 'better-payment' ) }
			</p>
		);
	}

	// Wire a single schema control to its value + change handlers. Shared by the
	// flat top-level controls and the children of a collapsible section so both
	// resolve defaults, meta keys, and image-upload multi-changes identically.
	const renderControl = ( control ) => {
		if ( ! isControlVisible( control, s ) ) return null;
		const isMeta = !! control.metaKey;
		const rawValue = isMeta ? meta?.[ control.metaKey ] : s[ control.key ];
		const value =
			rawValue !== undefined && rawValue !== null
				? rawValue
				: control.defaultValue ?? '';
		const onChange = isMeta
			? ( val ) => updateMeta( { [ control.metaKey ]: val } )
			: ( val ) => updateElement( { [ control.key ]: val } );
		const onMultiChange =
			! isMeta && control.type === 'image_upload'
				? ( patch ) => updateElement( patch )
				: undefined;
		const elementSettings =
			! isMeta && control.type === 'image_upload' ? s : undefined;
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
	};

	return (
		<div className="bp-cb-settings-form">
			{ schema.map( ( control ) => {
				// A `section` control (e.g. Typography on the title/description
				// elements) renders its children inside a collapsible accordion.
				// Sections honour `condition` too — its children are filtered by
				// renderControl, but a section whose own condition fails should
				// not leave an empty accordion behind.
				if (
					control.type === 'section' &&
					Array.isArray( control.children )
				) {
					if ( ! isControlVisible( control, s ) ) return null;
					return (
						<CollapsibleSection
							key={ control.key }
							label={ control.label }
							defaultCollapsed={ control.collapsed !== false }
						>
							{ control.children.map( renderControl ) }
						</CollapsibleSection>
					);
				}
				return renderControl( control );
			} ) }
		</div>
	);
}

/**
 * Collapsible settings group with an accordion header + chevron. Keeps the
 * builder's current light panel theme; only adds expand/collapse behaviour.
 */
function CollapsibleSection( { label, defaultCollapsed = true, children } ) {
	const [ open, setOpen ] = useState( ! defaultCollapsed );

	return (
		<div
			className={ `bp-cb-collapsible-section${ open ? ' is-open' : '' }` }
		>
			<button
				type="button"
				className="bp-cb-collapsible-section__header"
				aria-expanded={ open }
				onClick={ () => setOpen( ( v ) => ! v ) }
			>
				<span className="bp-cb-collapsible-section__title">
					{ label }
				</span>
				<svg
					className="bp-cb-collapsible-section__chevron"
					width="16"
					height="16"
					viewBox="0 0 20 20"
					fill="none"
					aria-hidden="true"
				>
					<path
						d="M6 8l4 4 4-4"
						stroke="currentColor"
						strokeWidth="1.6"
						strokeLinecap="round"
						strokeLinejoin="round"
					/>
				</svg>
			</button>
			{ open && (
				<div className="bp-cb-collapsible-section__body">
					{ children }
				</div>
			) }
		</div>
	);
}

const ALIGN_OPTIONS = [
	{
		value: 'left',
		title: 'Align left',
		icon: (
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
				<rect
					x="2"
					y="4"
					width="16"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="2"
					y="9"
					width="11"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="2"
					y="14"
					width="14"
					height="2"
					rx="1"
					fill="currentColor"
				/>
			</svg>
		),
	},
	{
		value: 'center',
		title: 'Align center',
		icon: (
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
				<rect
					x="2"
					y="4"
					width="16"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="4.5"
					y="9"
					width="11"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="3"
					y="14"
					width="14"
					height="2"
					rx="1"
					fill="currentColor"
				/>
			</svg>
		),
	},
	{
		value: 'right',
		title: 'Align right',
		icon: (
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
				<rect
					x="2"
					y="4"
					width="16"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="7"
					y="9"
					width="11"
					height="2"
					rx="1"
					fill="currentColor"
				/>
				<rect
					x="4"
					y="14"
					width="14"
					height="2"
					rx="1"
					fill="currentColor"
				/>
			</svg>
		),
	},
];

// ── SVG icons for the rich-text toolbar ──────────────────────────────────────
const TB = {
	bold: (
		<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
			<path
				d="M6 4h8a4 4 0 010 8H6zm0 8h9a4 4 0 010 8H6z"
				stroke="currentColor"
				strokeWidth="2"
				fill="none"
				strokeLinejoin="round"
			/>
		</svg>
	),
	italic: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<line x1="19" y1="4" x2="10" y2="4" />
			<line x1="14" y1="20" x2="5" y2="20" />
			<line x1="15" y1="4" x2="9" y2="20" />
		</svg>
	),
	under: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<path d="M6 4v6a6 6 0 0012 0V4" />
			<line x1="4" y1="20" x2="20" y2="20" />
		</svg>
	),
	link: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" />
			<path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" />
		</svg>
	),
	ol: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<line x1="10" y1="6" x2="21" y2="6" />
			<line x1="10" y1="12" x2="21" y2="12" />
			<line x1="10" y1="18" x2="21" y2="18" />
			<path d="M4 6h1v4" />
			<path d="M4 10h2" />
			<path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" />
		</svg>
	),
	ul: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<line x1="9" y1="6" x2="20" y2="6" />
			<line x1="9" y1="12" x2="20" y2="12" />
			<line x1="9" y1="18" x2="20" y2="18" />
			<circle cx="4" cy="6" r="1" fill="currentColor" />
			<circle cx="4" cy="12" r="1" fill="currentColor" />
			<circle cx="4" cy="18" r="1" fill="currentColor" />
		</svg>
	),
	clear: (
		<svg
			width="16"
			height="16"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
		>
			<path d="M17 7l-10 10" />
			<path d="M7 7h10v10" />
		</svg>
	),
};

const BLOCKS = [
	{ value: 'p', label: __( 'Paragraph', 'better-payment' ) },
	{ value: 'h1', label: 'H1' },
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
	{ value: 'h4', label: 'H4' },
	{ value: 'h5', label: 'H5' },
	{ value: 'h6', label: 'H6' },
];

// Block tags recognised by the rich-text editor's block-style dropdown.
const BLOCK_TAGS = BLOCKS.map( ( b ) => b.value );

const QUERY_CMDS = [
	'bold',
	'italic',
	'underline',
	'insertOrderedList',
	'insertUnorderedList',
];

function RichTextControl( { value, onChange } ) {
	const editorRef = useRef( null );
	const containerRef = useRef( null );
	const focusedRef = useRef( false );
	const lastValueRef = useRef( value );
	const savedRangeRef = useRef( null );
	const linkInputRef = useRef( null );
	const [ activeFormats, setActiveFormats ] = useState( {} );
	const [ blockValue, setBlockValue ] = useState( 'p' );
	const [ linkPopover, setLinkPopover ] = useState( null );
	// linkPopover: null | { mode: 'insert'|'view', href: string, anchorEl: Node|null, top: number, left: number }

	// Seed editor on mount
	useEffect( () => {
		if ( editorRef.current ) {
			editorRef.current.innerHTML = value || '';
			lastValueRef.current = value || '';
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Sync external value changes when not focused (different element selected)
	useEffect( () => {
		if (
			editorRef.current &&
			! focusedRef.current &&
			value !== lastValueRef.current
		) {
			editorRef.current.innerHTML = value || '';
			lastValueRef.current = value || '';
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
			try {
				formats[ c ] = document.queryCommandState( c );
			} catch ( e ) {
				formats[ c ] = false;
			}
		} );

		// Extra check for list context via DOM traversal (more reliable cross-browser)
		const sel = window.getSelection();
		if ( sel && sel.rangeCount ) {
			let node = sel.getRangeAt( 0 ).startContainer;
			while ( node && node !== editorRef.current ) {
				const tag = node.nodeName?.toLowerCase();
				if ( tag === 'ul' ) {
					formats.insertUnorderedList = true;
					break;
				}
				if ( tag === 'ol' ) {
					formats.insertOrderedList = true;
					break;
				}
				if ( tag === 'li' ) {
					const parent = node.parentNode?.nodeName?.toLowerCase();
					if ( parent === 'ul' ) formats.insertUnorderedList = true;
					if ( parent === 'ol' ) formats.insertOrderedList = true;
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
				if ( BLOCK_TAGS.includes( tag ) ) {
					setBlockValue( tag );
					return;
				}
				node = node.parentNode;
			}
		}
		setBlockValue( 'p' );
	}, [] );

	const cmd = useCallback(
		( command, arg = null ) => {
			editorRef.current?.focus();
			document.execCommand( command, false, arg );
			updateActiveState();
		},
		[ updateActiveState ]
	);

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
			if ( node.nodeName === 'A' ) {
				anchor = node;
				break;
			}
			node = node.parentNode;
		}

		// Position below the selection, spanning the full container width (left/right set via CSS)
		const containerRect = containerRef.current?.getBoundingClientRect() ?? {
			top: 0,
		};
		const selRect = range.getBoundingClientRect();
		const top = selRect.bottom - containerRect.top + 4;

		if ( anchor ) {
			setLinkPopover( {
				mode: 'view',
				href: anchor.href,
				anchorEl: anchor,
				top,
			} );
		} else {
			setLinkPopover( {
				mode: 'insert',
				href: 'https://',
				anchorEl: null,
				top,
			} );
		}
	}, [] );

	// Restore saved selection, then apply execCommand
	const restoreAndExec = useCallback(
		( command, arg = null ) => {
			editorRef.current?.focus();
			if ( savedRangeRef.current ) {
				const sel = window.getSelection();
				sel.removeAllRanges();
				sel.addRange( savedRangeRef.current );
			}
			document.execCommand( command, false, arg );
			handleInput();
			updateActiveState();
		},
		[ handleInput, updateActiveState ]
	);

	const handleLinkSave = useCallback(
		( href ) => {
			setLinkPopover( null );
			if ( href && href !== 'https://' ) {
				restoreAndExec( 'createLink', href );
			}
		},
		[ restoreAndExec ]
	);

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
		{ id: 'bold', icon: TB.bold, title: 'Bold (Ctrl+B)' },
		{ id: 'italic', icon: TB.italic, title: 'Italic (Ctrl+I)' },
		{ id: 'underline', icon: TB.under, title: 'Underline (Ctrl+U)' },
		{ id: 'insertOrderedList', icon: TB.ol, title: 'Ordered list' },
		{ id: 'insertUnorderedList', icon: TB.ul, title: 'Unordered list' },
		{ id: 'removeFormat', icon: TB.clear, title: 'Clear formatting' },
		{ id: 'link', icon: TB.link, title: 'Insert link', isLink: true },
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
						<option key={ b.value } value={ b.value }>
							{ b.label }
						</option>
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
						className={ `bp-cb-rich-text__tool-btn${
							activeFormats[ btn.id ] ? ' is-active' : ''
						}` }
						onMouseDown={
							btn.isLink
								? openLinkPopover
								: ( e ) => {
										e.preventDefault();
										cmd( btn.id );
								  }
						}
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
					onKeyDown={ ( e ) => {
						if ( e.key === 'Escape' ) setLinkPopover( null );
					} }
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
									if ( e.key === 'Enter' ) {
										e.preventDefault();
										handleLinkSave( e.target.value );
									}
								} }
							/>
							<button
								type="button"
								className="bp-cb-rich-text__link-save"
								onMouseDown={ ( e ) => {
									e.preventDefault();
									handleLinkSave(
										linkInputRef.current?.value
									);
								} }
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
								{ linkPopover.href.length > 40
									? linkPopover.href.slice( 0, 40 ) + '…'
									: linkPopover.href }
							</a>
							<span className="bp-cb-rich-text__link-sep">|</span>
							<button
								type="button"
								className="bp-cb-rich-text__link-action"
								onMouseDown={ ( e ) => {
									e.preventDefault();
									handleLinkEdit();
								} }
							>
								{ __( 'Edit', 'better-payment' ) }
							</button>
							<span className="bp-cb-rich-text__link-sep">|</span>
							<button
								type="button"
								className="bp-cb-rich-text__link-action bp-cb-rich-text__link-action--remove"
								onMouseDown={ ( e ) => {
									e.preventDefault();
									handleLinkRemove();
								} }
							>
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
				data-placeholder={ __(
					'Write a description for your campaign…',
					'better-payment'
				) }
				onFocus={ () => {
					focusedRef.current = true;
				} }
				onBlur={ () => {
					focusedRef.current = false;
					handleInput();
				} }
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

	const srcSizes = elementSettings?.src_sizes || {};
	const size = elementSettings?.size || 'full';
	const displaySrc =
		size !== 'full' && srcSizes[ size ]?.url
			? srcSizes[ size ].url
			: value || '';
	const hasImage = !! displaySrc;

	return (
		<div className="bp-cb-image-upload">
			{ /* Elementor-style preview area */ }
			<div className="bp-cb-image-picker" onClick={ openMedia }>
				{ hasImage ? (
					<>
						<img
							src={ displaySrc }
							alt=""
							className="bp-cb-image-picker__img"
						/>
						<button
							type="button"
							className="bp-cb-image-picker__delete"
							title={ __( 'Remove image', 'better-payment' ) }
							onClick={ ( e ) => {
								e.stopPropagation();
								onMultiChange( {
									src: '',
									src_id: 0,
									src_sizes: {},
								} );
							} }
						>
							<svg
								width="14"
								height="14"
								viewBox="0 0 16 16"
								fill="none"
							>
								<path
									d="M2 4h12M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724C5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724C10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4L12 13.333C12 13.687 11.86 14.026 11.609 14.276C11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276C4.14 14.026 4 13.687 4 13.333L3.333 4"
									stroke="currentColor"
									strokeWidth="1.3"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</button>
					</>
				) : (
					<div className="bp-cb-image-picker__placeholder">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<circle
								cx="12"
								cy="12"
								r="11"
								stroke="currentColor"
								strokeWidth="1.5"
							/>
							<path
								d="M12 7v10M7 12h10"
								stroke="currentColor"
								strokeWidth="1.5"
								strokeLinecap="round"
							/>
						</svg>
					</div>
				) }

				{ /* Choose Image bar */ }
				<div
					className="bp-cb-image-picker__bar"
					onClick={ ( e ) => e.stopPropagation() }
				>
					<button
						type="button"
						className="bp-cb-image-picker__choose"
						onClick={ openMedia }
					>
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
				onChange={ ( e ) =>
					onMultiChange( {
						src: e.target.value,
						src_id: 0,
						src_sizes: {},
					} )
				}
			/>
			{ hasImage && (
				<button
					type="button"
					className="bp-cb-image-upload__btn bp-cb-image-upload__btn--clear"
					onClick={ () =>
						onMultiChange( { src: '', src_id: 0, src_sizes: {} } )
					}
				>
					{ __( 'Clear', 'better-payment' ) }
				</button>
			) }
		</div>
	);
}

function SettingsControl( {
	control,
	value,
	onChange,
	onMultiChange,
	elementSettings,
} ) {
	const {
		key,
		label,
		type,
		placeholder,
		min,
		max,
		rows,
		options,
		info,
		unit,
		step,
		mediaType,
	} = control;

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
			{ type !== 'toggle' &&
				type !== 'switch' &&
				type !== 'image_upload' &&
				type !== 'rich_text' && (
					<label
						className="bp-cb-field-label"
						htmlFor={ `ctrl-${ key }` }
					>
						{ label }
					</label>
				) }

			{ type === 'image_upload' && (
				<>
					<label className="bp-cb-field-label">{ label }</label>
					<ImageUploadControl
						value={ value }
						onMultiChange={ onMultiChange }
						elementSettings={ elementSettings }
					/>
				</>
			) }

			{ type === 'rich_text' && (
				<>
					<label className="bp-cb-field-label">{ label }</label>
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
						value={ value ?? control.defaultValue ?? 100 }
						onChange={ ( e ) =>
							onChange( parseInt( e.target.value, 10 ) )
						}
					/>
					<span className="bp-cb-range-value">
						{ value ?? control.defaultValue ?? 100 }
						{ /* No implicit unit: a range is not necessarily a
						     percentage. This used to default to '%', which was
						     invisible while every range happened to be a width —
						     and rendered a column count as "4%". Declare `unit`
						     on the control when there is one. */ }
						{ unit || '' }
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
							className={ `bp-cb-align-btn${
								( value || 'left' ) === opt.value
									? ' is-active'
									: ''
							}` }
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
				<div className="bp-cb-url-control">
					<input
						id={ `ctrl-${ key }` }
						type="url"
						value={ value ?? '' }
						placeholder={ placeholder || 'https://' }
						onChange={ ( e ) => onChange( e.target.value ) }
					/>
					{ /* A control that accepts an uploaded file declares
					     `mediaType`, and gets a picker. Without it the only way to
					     use your own media is to upload it, leave the builder,
					     copy the URL, and paste it back. */ }
					{ mediaType && (
						<button
							type="button"
							className="bp-cb-url-control__media"
							onClick={ () =>
								openWpMedia( ( { url } ) => onChange( url ), {
									type: mediaType,
								} )
							}
						>
							{ __( 'Media Library', 'better-payment' ) }
						</button>
					) }
				</div>
			) }

			{ type === 'number' && (
				<input
					id={ `ctrl-${ key }` }
					type="number"
					value={
						value === '' ||
						value === null ||
						value === undefined ||
						Number.isNaN( value )
							? ''
							: value
					}
					min={ min }
					max={ max }
					placeholder={ placeholder || '' }
					onChange={ ( e ) =>
						onChange(
							e.target.value === ''
								? ''
								: parseInt( e.target.value, 10 )
						)
					}
				/>
			) }

			{ type === 'currency' && (
				<div className="bp-settings-prefix-input">
					<span className="bp-settings-prefix">
						{ getGlobalCurrencySymbol() }
					</span>
					<input
						id={ `ctrl-${ key }` }
						type="number"
						min={ min ?? 0 }
						step={ step ?? 1 }
						value={ value ?? '' }
						placeholder={ placeholder || '0' }
						onChange={ ( e ) => onChange( e.target.value ) }
					/>
				</div>
			) }

			{ type === 'color' && (
				<div className="bp-color-row">
					<input
						id={ `ctrl-${ key }` }
						type="color"
						value={ value || '#6b63f6' }
						onChange={ ( e ) => onChange( e.target.value ) }
					/>
					<span className="bp-color-value">
						{ value || '#6b63f6' }
					</span>
				</div>
			) }

			{ type === 'toggle' && (
				<label
					className="bp-cb-toggle-label"
					htmlFor={ `ctrl-${ key }` }
				>
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
				<label
					className="bp-cb-switch-label"
					htmlFor={ `ctrl-${ key }` }
				>
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
						<option key={ opt.value } value={ opt.value }>
							{ opt.label }
						</option>
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

			{ type === 'repeater' && (
				<RepeaterControl
					control={ control }
					value={ Array.isArray( value ) ? value : [] }
					onChange={ onChange }
				/>
			) }

			{ info && <p className="bp-cb-field-hint">{ info }</p> }
		</div>
	);
}
