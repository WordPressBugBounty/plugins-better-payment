import {
	useState,
	useMemo,
	useEffect,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import AiIcon from '../ai/AiIcon';
import ProCrownIcon from './ProCrownIcon';
import UpgradeModal from './UpgradeModal';
import { getPreviewImageUrl } from '../utils/templatePreview';
import { getCampaignCategories } from '../utils/campaignCategories';
import { isProEnabled, isTemplateLocked } from '../utils/proEnabled';

// ── Blank-layout SVG wireframes ───────────────────────────────────────────────
const BLANK_ICONS = {
	'blank-1col': (
		<svg width="120" height="90" viewBox="0 0 120 90" fill="none">
			<rect
				x="8"
				y="8"
				width="104"
				height="74"
				rx="4"
				fill="#f5f5f5"
				stroke="#e5e7eb"
				strokeWidth="1.5"
			/>
			<rect
				x="16"
				y="16"
				width="88"
				height="10"
				rx="3"
				fill="#f59e0b"
				opacity="0.5"
			/>
			<rect x="16" y="32" width="88" height="6" rx="2" fill="#d1d5db" />
			<rect x="16" y="42" width="70" height="6" rx="2" fill="#d1d5db" />
			<rect x="16" y="52" width="80" height="6" rx="2" fill="#d1d5db" />
			<rect
				x="16"
				y="66"
				width="30"
				height="8"
				rx="2"
				fill="#f59e0b"
				opacity="0.7"
			/>
		</svg>
	),
	'blank-2col': (
		<svg width="120" height="90" viewBox="0 0 120 90" fill="none">
			<rect
				x="8"
				y="8"
				width="104"
				height="74"
				rx="4"
				fill="#f5f5f5"
				stroke="#e5e7eb"
				strokeWidth="1.5"
			/>
			<rect
				x="16"
				y="16"
				width="58"
				height="10"
				rx="3"
				fill="#f59e0b"
				opacity="0.5"
			/>
			<rect x="16" y="32" width="58" height="6" rx="2" fill="#d1d5db" />
			<rect x="16" y="42" width="46" height="6" rx="2" fill="#d1d5db" />
			<rect x="16" y="52" width="58" height="6" rx="2" fill="#d1d5db" />
			<rect
				x="16"
				y="66"
				width="24"
				height="8"
				rx="2"
				fill="#f59e0b"
				opacity="0.7"
			/>
			<rect
				x="82"
				y="16"
				width="22"
				height="58"
				rx="3"
				fill="#f59e0b"
				opacity="0.12"
				stroke="#f59e0b"
				strokeWidth="1.2"
			/>
			<rect x="86" y="22" width="14" height="4" rx="2" fill="#d1d5db" />
			<rect x="86" y="30" width="10" height="4" rx="2" fill="#d1d5db" />
			<rect x="86" y="38" width="12" height="4" rx="2" fill="#d1d5db" />
		</svg>
	),
	// "Custom" (blank-3col) — a full-width row on top, two equal columns below.
	'blank-3col': (
		<svg width="120" height="90" viewBox="0 0 120 90" fill="none">
			<rect
				x="8"
				y="8"
				width="104"
				height="74"
				rx="4"
				fill="#f5f5f5"
				stroke="#e5e7eb"
				strokeWidth="1.5"
			/>
			<rect
				x="16"
				y="16"
				width="88"
				height="26"
				rx="3"
				fill="#f59e0b"
				opacity="0.12"
				stroke="#f59e0b"
				strokeWidth="1.2"
			/>
			<rect
				x="20"
				y="22"
				width="50"
				height="5"
				rx="2"
				fill="#f59e0b"
				opacity="0.5"
			/>
			<rect x="20" y="31" width="72" height="4" rx="2" fill="#d1d5db" />
			<rect
				x="16"
				y="48"
				width="42"
				height="26"
				rx="3"
				fill="#f59e0b"
				opacity="0.12"
				stroke="#f59e0b"
				strokeWidth="1.2"
			/>
			<rect x="20" y="54" width="30" height="4" rx="2" fill="#d1d5db" />
			<rect x="20" y="62" width="24" height="4" rx="2" fill="#d1d5db" />
			<rect
				x="62"
				y="48"
				width="42"
				height="26"
				rx="3"
				fill="#f59e0b"
				opacity="0.12"
				stroke="#f59e0b"
				strokeWidth="1.2"
			/>
			<rect x="66" y="54" width="30" height="4" rx="2" fill="#d1d5db" />
			<rect x="66" y="62" width="24" height="4" rx="2" fill="#d1d5db" />
		</svg>
	),
};

/**
 * The viewport the live preview is RENDERED at, regardless of the modal's size.
 *
 * The preview document carries `width=device-width`, so the frontend's own media
 * queries fire on the iframe's pixel width and the layout is whatever that width
 * produces. Sizing the frame to the modal therefore previewed a DIFFERENT page
 * from the one the editor and the live site show — and the modal is itself
 * `max-width: 95vw`, so the design also changed with the reviewer's screen.
 *
 * The concrete symptom was a summary tile: a `campaign_summary` in a 50% column
 * is ~88px wide at the modal's 1080px and ~122px at a desktop width, and "Days
 * Left" — the longest label — wrapped to two lines in the preview while sitting
 * on one everywhere else. Four templates showed it (child-healthcare-v2,
 * education-fund-v2, tree-plantation-v2 and Pro's Clean Water).
 *
 * 1280 because every offered template's summary row reaches its final widths by
 * ~1240 and does not move above it, so this is the settled desktop rendering
 * rather than a number that merely clears today's longest label.
 *
 * This is the same class of bug as the 780px -> 1080px widening before it (a
 * 2-column template previewed as the mobile stack); rendering at a FIXED width
 * and scaling to fit retires the class instead of moving the threshold again.
 */
export const PREVIEW_VIEWPORT_WIDTH = 1280;

/**
 * Geometry for the live preview iframe: render wide, then scale to fit the box.
 *
 * Pure so it can be tested without mounting the modal. Never upscales — a box at
 * or above the viewport renders 1:1, which is the behaviour that existed before
 * — and never returns a zero/NaN scale for an unmeasured box (0 would collapse
 * the frame; the caller applies the result as a CSS transform).
 *
 * @param {number} boxWidth  Width of the lightbox body, in CSS pixels.
 * @param {number} boxHeight Height of the lightbox body, in CSS pixels.
 * @param {number} viewport  Width to render the document at.
 * @return {{width: number, height: number, scale: number}} Frame geometry.
 */
export function previewFrameGeometry(
	boxWidth,
	boxHeight,
	viewport = PREVIEW_VIEWPORT_WIDTH
) {
	if ( ! ( boxWidth > 0 ) || ! ( viewport > 0 ) ) {
		return {
			width: viewport,
			height: boxHeight > 0 ? boxHeight : 0,
			scale: 1,
		};
	}

	const width = Math.max( viewport, boxWidth );
	const scale = boxWidth / width;

	// The frame scrolls internally, so it has to be TALLER than the box by the
	// same factor it is being shrunk by — otherwise scaling it also shortens the
	// visible page and leaves a band of modal background under it.
	return {
		width,
		height: boxHeight > 0 ? boxHeight / scale : 0,
		scale,
	};
}

/**
 * Which skeleton shape a template's layout preset gets.
 *
 * Exported for the same reason `isControlVisible` is: it is the one decision in
 * the placeholder worth pinning, and it is pure. An unrecognised preset stacks —
 * a third-party template registering its own layout gets a plausible placeholder
 * rather than none.
 *
 * @param {string} layout Template layout preset.
 * @return {string} 'two-column' | 'split' | 'stack'
 */
export function skeletonShape( layout ) {
	if ( layout === '2-column' ) return 'two-column';
	if ( layout === 'split' ) return 'split';
	return 'stack';
}

/**
 * How the Preview lightbox shows a template: a live document or its screenshot.
 *
 * A locked Pro card (a free install) previews as its screenshot only. Lite ships
 * no copy of Pro's layouts, so there is nothing to render and the server would
 * answer 404 — and asking anyway opened the modal at live width over a skeleton,
 * only to snap back to the screenshot when the request failed. Deciding up front
 * means no request, no skeleton and no resize. With Pro active the card is not
 * locked and previews live like any other template. A live preview whose request
 * failed falls back to the same screenshot.
 *
 * @param {Object|undefined} tpl        Template card, or undefined while closed.
 * @param {boolean}          proEnabled Whether Pro is active.
 * @param {boolean}          failed     Whether this template's document request failed.
 * @return {?string} 'live' | 'screenshot', or null when no template is open.
 */
export function lightboxPreviewMode( tpl, proEnabled, failed ) {
	if ( ! tpl ) return null;
	if ( isTemplateLocked( tpl, proEnabled ) || failed ) return 'screenshot';
	return 'live';
}

/**
 * Placeholder shown while a template preview loads.
 *
 * It mirrors the template's OWN layout rather than showing one generic block, so
 * the wait tells the user something: the shape they are about to see. The three
 * presets are the same three the picker sells (`2-column`, `split`, everything
 * else stacks), and the pieces are in campaign order — masthead, figures, the
 * ask, the story — because that is what will replace them.
 *
 * @param {Object} props        Component props.
 * @param {string} props.layout Template layout preset.
 * @return {JSX.Element} Skeleton markup.
 */
function TemplatePreviewSkeleton( { layout } ) {
	const bar = ( key, className ) => (
		<span key={ key } className={ `bp-tpl-skel__bar ${ className }` } />
	);

	const story = (
		<>
			{ bar( 'title', 'bp-tpl-skel__bar--title' ) }
			{ bar( 'lede1', 'bp-tpl-skel__bar--text' ) }
			{ bar( 'lede2', 'bp-tpl-skel__bar--text' ) }
			{ bar( 'lede3', 'bp-tpl-skel__bar--text-short' ) }
			<span className="bp-tpl-skel__media" />
			{ bar( 'body1', 'bp-tpl-skel__bar--text' ) }
			{ bar( 'body2', 'bp-tpl-skel__bar--text-short' ) }
		</>
	);

	const ask = (
		<>
			{ bar( 'progress', 'bp-tpl-skel__bar--progress' ) }
			<span className="bp-tpl-skel__tiles">
				<span className="bp-tpl-skel__tile" />
				<span className="bp-tpl-skel__tile" />
				<span className="bp-tpl-skel__tile" />
			</span>
			<span className="bp-tpl-skel__chips">
				<span className="bp-tpl-skel__chip" />
				<span className="bp-tpl-skel__chip" />
				<span className="bp-tpl-skel__chip" />
				<span className="bp-tpl-skel__chip" />
			</span>
			{ bar( 'cta', 'bp-tpl-skel__bar--cta' ) }
		</>
	);

	const kind = skeletonShape( layout );

	let shape;
	if ( kind === 'two-column' ) {
		shape = (
			<div className="bp-tpl-skel__row">
				<div className="bp-tpl-skel__col bp-tpl-skel__col--wide">
					{ story }
				</div>
				<div className="bp-tpl-skel__col bp-tpl-skel__col--narrow">
					{ ask }
				</div>
			</div>
		);
	} else if ( kind === 'split' ) {
		shape = (
			<>
				<div className="bp-tpl-skel__band">
					{ bar( 'title', 'bp-tpl-skel__bar--title' ) }
					{ bar( 'lede', 'bp-tpl-skel__bar--text' ) }
				</div>
				<div className="bp-tpl-skel__row">
					<div className="bp-tpl-skel__col">
						<span className="bp-tpl-skel__media" />
						{ bar( 'body1', 'bp-tpl-skel__bar--text' ) }
						{ bar( 'body2', 'bp-tpl-skel__bar--text-short' ) }
					</div>
					<div className="bp-tpl-skel__col">{ ask }</div>
				</div>
			</>
		);
	} else {
		shape = (
			<div className="bp-tpl-skel__col">
				{ story }
				{ ask }
			</div>
		);
	}

	return (
		<div
			className="bp-tpl-skel"
			role="status"
			aria-live="polite"
			/* The shimmer means nothing to a screen reader, and an unlabelled
			   busy region is announced as an empty one. */
			aria-label={ __( 'Loading template preview…', 'better-payment' ) }
		>
			{ shape }
		</div>
	);
}

// Synthetic "template" for the Create-using-AI card. Applying it commits an empty
// campaign (no columns) so the editor/save controls ungate, marks the card Active,
// and — because it has no columns — leaves the layout free for the AI to choose.
// Exported so the builder can apply it directly when opened with `start=ai`.
export const AI_TEMPLATE = {
	key: 'ai-assistant',
	category: 'blank',
	label: __( 'Create using AI Assistant', 'better-payment' ),
	layout: '1-column',
	columns: [],
};

export default function TemplatesTab( {
	dispatch,
	appliedTemplateKey,
	onTemplateApplied,
} ) {
	const data = window.betterPaymentCampaignData || {};
	const templates = data.templates || [];
	const pluginUrl = ( data.pluginUrl || '' ).replace( /\/$/, '' );
	const restUrl = data.restUrl || '';
	const nonce = data.nonce || '';

	// The sidebar's categories come from the shared registry, not a list kept
	// here: a hardcoded copy meant a template registered with a new tag (by Pro
	// or a third party) counted toward "All Templates" but had no sidebar entry.
	const categories = useMemo(
		() =>
			getCampaignCategories().map( ( c ) => ( {
				key: c.slug,
				label: c.label,
			} ) ),
		[]
	);

	const blankTemplates = templates.filter( ( t ) => t.category === 'blank' );
	const prebuiltTemplates = templates.filter(
		( t ) => t.category === 'prebuilt'
	);

	const [ search, setSearch ] = useState( '' );
	const [ activeCategory, setActiveCategory ] = useState( 'all' );
	const [ lightboxKey, setLightboxKey ] = useState( null );
	const [ confirmTemplate, setConfirmTemplate ] = useState( null ); // template pending confirmation
	const [ failedThumbs, setFailedThumbs ] = useState( () => new Set() ); // keys whose preview image 404'd
	const [ upgradeTemplate, setUpgradeTemplate ] = useState( null ); // locked template whose upsell is open

	// Read live, like every other Pro gate in the builder — never inferred from
	// what a template carries. A card is locked only when PHP marked it `pro`
	// (ProTemplateCatalog, which contributes cards only on a free install).
	const proEnabled = isProEnabled();

	const handleSelect = ( template ) => {
		// A locked Pro card has no `columns` to apply — the only thing it can do
		// is sell Pro. Returning here keeps the confirmation modal (which warns
		// about losing the current layout) out of a flow that changes nothing.
		if ( isTemplateLocked( template, proEnabled ) ) {
			setUpgradeTemplate( template );
			return;
		}

		// If a template is already applied and the user is switching to a different one,
		// show a confirmation modal before overwriting the current layout.
		if ( appliedTemplateKey && template.key !== appliedTemplateKey ) {
			setConfirmTemplate( template );
			return;
		}
		applyTemplate( template );
	};

	const applyTemplate = ( template ) => {
		dispatch( { type: 'APPLY_TEMPLATE', template } );
		onTemplateApplied( template );
	};

	const filteredPrebuilt = prebuiltTemplates.filter( ( tpl ) => {
		const matchesCat =
			activeCategory === 'all' ||
			( tpl.tags || [] ).includes( activeCategory );
		const matchesSearch =
			! search ||
			tpl.label.toLowerCase().includes( search.toLowerCase() ) ||
			( tpl.description || '' )
				.toLowerCase()
				.includes( search.toLowerCase() );
		return matchesCat && matchesSearch;
	} );

	const filteredBlank = blankTemplates.filter( ( tpl ) => {
		if ( activeCategory !== 'all' ) return false;
		return (
			! search || tpl.label.toLowerCase().includes( search.toLowerCase() )
		);
	} );

	const getCategoryCount = ( key ) =>
		prebuiltTemplates.filter( ( t ) => ( t.tags || [] ).includes( key ) )
			.length;

	const currentLabel = appliedTemplateKey
		? templates.find( ( t ) => t.key === appliedTemplateKey )?.label || ''
		: null;

	// Resolve a thumbnail, falling back to the solid preview_color block once an
	// image has failed to load (a template may declare a preview_image whose file
	// isn't present yet — a broken-image icon is worse than the colour block).
	const thumbUrl = ( tpl ) =>
		failedThumbs.has( tpl.key ) ? '' : getPreviewImageUrl( tpl, pluginUrl );

	const markThumbFailed = ( key ) =>
		setFailedThumbs( ( prev ) => {
			if ( prev.has( key ) ) return prev;
			const next = new Set( prev );
			next.add( key );
			return next;
		} );

	const lightboxTpl = lightboxKey
		? templates.find( ( t ) => t.key === lightboxKey )
		: null;

	// ── Live preview document for the lightbox ────────────────────────────────
	// The card grid keeps its static screenshot: fifteen live iframes on mount is
	// what made this picker slow enough to be replaced by screenshots in the first
	// place. The Preview lightbox renders the real template instead, so it scrolls
	// the WHOLE design — a screenshot is a fixed crop of the top of the page and
	// silently drifts from the layout the moment a template is redesigned.
	const [ previewDocs, setPreviewDocs ] = useState( {} ); // key -> HTML document
	const [ previewFailed, setPreviewFailed ] = useState( () => new Set() );

	const markPreviewFailed = ( key ) =>
		setPreviewFailed( ( prev ) => {
			if ( prev.has( key ) ) return prev;
			const next = new Set( prev );
			next.add( key );
			return next;
		} );

	useEffect( () => {
		if ( ! lightboxKey || ! restUrl ) return undefined;
		if ( previewDocs[ lightboxKey ] || previewFailed.has( lightboxKey ) ) {
			return undefined;
		}

		// A locked Pro card is never fetched: Lite ships no copy of Pro's layouts,
		// so there is nothing to render and its screenshot is the whole preview
		// (see lightboxPreviewMode). With Pro active the card is not locked and
		// renders live like any other template.
		if (
			lightboxPreviewMode( lightboxTpl, proEnabled, false ) !== 'live'
		) {
			return undefined;
		}

		let cancelled = false;

		fetch( `${ restUrl }templates/${ lightboxKey }/preview?document=1`, {
			headers: { 'X-WP-Nonce': nonce },
		} )
			.then( ( r ) => ( r.ok ? r.json() : Promise.reject( r.status ) ) )
			.then( ( d ) => {
				if ( cancelled ) return;
				if ( d?.html ) {
					setPreviewDocs( ( prev ) => ( {
						...prev,
						[ lightboxKey ]: d.html,
					} ) );
				} else {
					markPreviewFailed( lightboxKey );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) markPreviewFailed( lightboxKey );
			} );

		return () => {
			cancelled = true;
		};
	}, [ lightboxKey ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// The iframe is "ready" only once it has fired onLoad. Swapping the skeleton
	// out the moment the HTML arrives leaves a second blank beat while the frame
	// fetches the campaign stylesheet and the hero image — a shorter wait made to
	// feel longer, because the user watches the placeholder vanish into nothing.
	const [ previewFrames, setPreviewFrames ] = useState( () => new Set() );

	const markFrameReady = ( key ) =>
		setPreviewFrames( ( prev ) => {
			if ( prev.has( key ) ) return prev;
			const next = new Set( prev );
			next.add( key );
			return next;
		} );

	// Reopening the lightbox remounts the iframe, so its ready flag has to go with
	// it — otherwise a template previewed once comes back as a blank white frame
	// for the beat before it repaints, which is worse than the placeholder.
	useEffect( () => {
		if ( ! lightboxKey ) return;
		setPreviewFrames( ( prev ) => {
			if ( ! prev.has( lightboxKey ) ) return prev;
			const next = new Set( prev );
			next.delete( lightboxKey );
			return next;
		} );
	}, [ lightboxKey ] );

	const lightboxDoc = lightboxTpl ? previewDocs[ lightboxTpl.key ] : '';
	const lightboxDocFailed = lightboxTpl
		? previewFailed.has( lightboxTpl.key )
		: false;
	const lightboxFrameReady = lightboxTpl
		? previewFrames.has( lightboxTpl.key )
		: false;

	// Geometry is decided the moment the lightbox opens, not when the HTML lands.
	// Keying it on `lightboxDoc` meant the modal opened at the screenshot's 780px
	// and snapped to 1080px x 90vh a beat later — the layout jumping under a user
	// who is trying to look at something.
	// A locked Pro card is decided here too: it opens straight at the screenshot.
	const lightboxIsLive =
		lightboxPreviewMode( lightboxTpl, proEnabled, lightboxDocFailed ) ===
		'live';

	// The frame renders at PREVIEW_VIEWPORT_WIDTH and is scaled down to whatever
	// the body actually is, so the preview shows the template's DESKTOP layout on
	// every screen instead of re-laying it out for the modal (see the constant).
	const lightboxBodyRef = useRef( null );
	const [ frameBox, setFrameBox ] = useState( { width: 0, height: 0 } );

	// Layout effect, not effect: the frame is positioned before the browser
	// paints, so an unscaled first frame never flashes behind the placeholder.
	useLayoutEffect( () => {
		const node = lightboxBodyRef.current;
		if ( ! lightboxIsLive || ! node ) return undefined;

		const measure = () => {
			const { width, height } = node.getBoundingClientRect();
			setFrameBox( ( prev ) =>
				prev.width === width && prev.height === height
					? prev
					: { width, height }
			);
		};

		measure();

		// The modal is `max-width: 95vw` / `height: 90vh`, so it resizes with the
		// window while open. ResizeObserver is not in the jsdom used by Jest and
		// is not worth a polyfill for a browser-only affordance.
		if ( typeof window.ResizeObserver !== 'function' ) return undefined;

		const observer = new window.ResizeObserver( measure );
		observer.observe( node );
		return () => observer.disconnect();
	}, [ lightboxIsLive, lightboxKey ] );

	const frameGeometry = previewFrameGeometry(
		frameBox.width,
		frameBox.height
	);

	return (
		<div className="bp-templates-tab">
			{ /* Page header */ }
			<div className="bp-templates-tab__page-header">
				<h2>{ __( 'Select A Template', 'better-payment' ) }</h2>
				{ currentLabel && (
					<p>
						{ __(
							'You are currently using the',
							'better-payment'
						) }{ ' ' }
						<strong>"{ currentLabel }"</strong>{ ' ' }
						{ __(
							'template. Changing a template now might result in losing fields and data. Proceed carefully.',
							'better-payment'
						) }
					</p>
				) }
			</div>

			<div className="bp-templates-tab__body">
				{ /* Sidebar */ }
				<div className="bp-templates-tab__sidebar">
					<div className="bp-templates-tab__search">
						<svg
							width="14"
							height="14"
							viewBox="0 0 14 14"
							fill="none"
						>
							<circle
								cx="6"
								cy="6"
								r="4.5"
								stroke="#8a8fa6"
								strokeWidth="1.3"
							/>
							<path
								d="M9.5 9.5L12.5 12.5"
								stroke="#8a8fa6"
								strokeWidth="1.3"
								strokeLinecap="round"
							/>
						</svg>
						<input
							type="text"
							placeholder={ __(
								'Search Templates',
								'better-payment'
							) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
						/>
					</div>

					<button
						className={ `bp-templates-tab__all-cat${
							activeCategory === 'all' ? ' is-active' : ''
						}` }
						onClick={ () => setActiveCategory( 'all' ) }
					>
						<span>{ __( 'All Templates', 'better-payment' ) }</span>
						<span className="bp-templates-tab__cat-count">
							{ templates.length }
						</span>
					</button>

					<hr className="bp-templates-tab__divider" />

					<div className="bp-templates-tab__cats">
						{ categories.map( ( cat ) => {
							const count = getCategoryCount( cat.key );
							if ( count === 0 ) return null;
							return (
								<button
									key={ cat.key }
									className={ `bp-templates-tab__cat${
										activeCategory === cat.key
											? ' is-active'
											: ''
									}` }
									onClick={ () =>
										setActiveCategory( cat.key )
									}
								>
									<span>{ cat.label }</span>
									<span className="bp-templates-tab__cat-num">
										{ count }
									</span>
								</button>
							);
						} ) }
					</div>

					<div className="bp-templates-tab__sidebar-cta">
						<strong>
							{ __(
								'Don’t See The Template You’re Looking For?',
								'better-payment'
							) }
						</strong>
						<p>
							<a
								href="https://wpdeveloper.com/support/"
								target="_blank"
							>
								{ __( 'Share', 'better-payment' ) }
							</a>{ ' ' }
							{ __(
								'your suggestions for future additions.',
								'better-payment'
							) }
						</p>
					</div>
				</div>

				{ /* Main grid */ }
				<div className="bp-templates-tab__main">
					{ filteredBlank.length > 0 && (
						<div className="bp-templates-tab__section">
							{ activeCategory === 'all' && ! search && (
								<p className="bp-templates-tab__section-heading">
									{ __(
										'Start with a blank template',
										'better-payment'
									) }
								</p>
							) }
							<div className="bp-templates-tab__grid bp-templates-tab__grid--blank">
								{ /* Create using AI Assistant — commits an empty campaign
                                    (so the editor/save controls ungate) and jumps into the
                                    editor with the AI Assistant ready to build the page. */ }
								{ ( () => {
									const aiActive =
										appliedTemplateKey === AI_TEMPLATE.key;
									return (
										<button
											className={ `bp-tpl-card bp-tpl-card--ai${
												aiActive ? ' is-active' : ''
											}` }
											onClick={ () =>
												aiActive
													? onTemplateApplied(
															AI_TEMPLATE
													  )
													: handleSelect(
															AI_TEMPLATE
													  )
											}
										>
											{ aiActive && (
												<span className="bp-tpl-card__badge">
													{ __(
														'Active',
														'better-payment'
													) }
												</span>
											) }
											<div className="bp-tpl-card__thumb bp-tpl-card__thumb--ai">
												{ ! aiActive && (
													<span className="bp-tpl-card__ai-pill">
														<AiIcon size={ 11 } />
														{ __(
															'AI-powered',
															'better-payment'
														) }
													</span>
												) }
												<span
													className="bp-tpl-card__ai-orb"
													aria-hidden="true"
												>
													<AiIcon size={ 30 } />
												</span>
												{ ! aiActive && (
													<span className="bp-tpl-card__ai-cta">
														<AiIcon size={ 14 } />
														{ __(
															'Start with AI',
															'better-payment'
														) }
													</span>
												) }
											</div>
											<div className="bp-tpl-card__info">
												<strong>
													{ __(
														'Create using AI Assistant',
														'better-payment'
													) }
												</strong>
												<span>
													{ __(
														'Describe your campaign — AI builds the page for you.',
														'better-payment'
													) }
												</span>
											</div>
										</button>
									);
								} )() }
								{ filteredBlank.map( ( tpl ) => {
									const isActive =
										tpl.key === appliedTemplateKey;
									return (
										<button
											key={ tpl.key }
											className={ `bp-tpl-card${
												isActive ? ' is-active' : ''
											}` }
											onClick={ () =>
												! isActive &&
												handleSelect( tpl )
											}
										>
											{ isActive && (
												<span className="bp-tpl-card__badge">
													{ __(
														'Active',
														'better-payment'
													) }
												</span>
											) }
											<div className="bp-tpl-card__thumb bp-tpl-card__thumb--blank">
												{ BLANK_ICONS[ tpl.key ] || (
													<span className="dashicons dashicons-layout" />
												) }
												{ ! isActive && (
													<div className="bp-tpl-card__hover">
														<span className="bp-tpl-card__hover-btn bp-tpl-card__hover-btn--primary">
															{ __(
																'Use Template',
																'better-payment'
															) }
														</span>
													</div>
												) }
											</div>
											<div className="bp-tpl-card__info">
												<strong>{ tpl.label }</strong>
												{ tpl.description && (
													<span>
														{ tpl.description }
													</span>
												) }
											</div>
										</button>
									);
								} ) }
							</div>
						</div>
					) }

					{ filteredPrebuilt.length > 0 && (
						<div className="bp-templates-tab__section">
							{ activeCategory === 'all' && ! search && (
								<p className="bp-templates-tab__section-heading">
									{ __(
										'Or choose a pre-built template and launch your campaign in minutes!',
										'better-payment'
									) }
								</p>
							) }
							<div className="bp-templates-tab__grid">
								{ filteredPrebuilt.map( ( tpl ) => {
									const isActive =
										tpl.key === appliedTemplateKey;
									const locked = isTemplateLocked(
										tpl,
										proEnabled
									);
									const imgUrl = thumbUrl( tpl );
									return (
										<div
											key={ tpl.key }
											className={ `bp-tpl-card${
												isActive ? ' is-active' : ''
											}${ locked ? ' is-locked' : '' }` }
										>
											{ isActive && (
												<span className="bp-tpl-card__badge">
													{ __(
														'Active',
														'better-payment'
													) }
												</span>
											) }
											{ /* The palette marks a locked widget with a bare crown
											    because the glyph sits next to a 20px element icon it
											    must not outweigh. Here there is no icon to outweigh —
											    a 12px crown alone would vanish on a 190px screenshot —
											    so the crown labels a "PRO" pill, the same wording the
											    upgrade modal's own pill uses. */ }
											{ locked && (
												<span className="bp-tpl-card__pro-badge">
													<ProCrownIcon decorative />
													{ __(
														'PRO',
														'better-payment'
													) }
												</span>
											) }
											<div className="bp-tpl-card__thumb">
												{ imgUrl ? (
													<img
														src={ imgUrl }
														alt={ tpl.label }
														className="bp-tpl-card__screenshot"
														draggable={ false }
														onError={ () =>
															markThumbFailed(
																tpl.key
															)
														}
													/>
												) : (
													<div
														className="bp-tpl-card__thumb-placeholder"
														style={ {
															background:
																tpl.preview_color ||
																'#6b63f6',
														} }
													/>
												) }
												{ ! isActive && (
													<div className="bp-tpl-card__hover">
														<button
															className="bp-tpl-card__hover-btn bp-tpl-card__hover-btn--primary"
															onClick={ () =>
																handleSelect(
																	tpl
																)
															}
														>
															{ locked && (
																<ProCrownIcon
																	decorative
																/>
															) }
															{ locked
																? __(
																		'Upgrade to Pro',
																		'better-payment'
																  )
																: __(
																		'Use Template',
																		'better-payment'
																  ) }
														</button>
														{ /* Preview stays available while locked — seeing
														    the design is the whole point of showing the
														    card, and it is what the upsell is selling. */ }
														<button
															className="bp-tpl-card__hover-btn"
															onClick={ ( e ) => {
																e.stopPropagation();
																setLightboxKey(
																	tpl.key
																);
															} }
														>
															{ __(
																'Preview',
																'better-payment'
															) }
														</button>
													</div>
												) }
											</div>
											<div className="bp-tpl-card__info">
												<strong>{ tpl.label }</strong>
												{ tpl.description && (
													<span>
														{ tpl.description }
													</span>
												) }
											</div>
										</div>
									);
								} ) }
							</div>
						</div>
					) }

					{ filteredBlank.length === 0 &&
						filteredPrebuilt.length === 0 && (
							<div className="bp-templates-tab__empty">
								<p>
									{ __(
										'No templates match your search.',
										'better-payment'
									) }
								</p>
							</div>
						) }
				</div>
			</div>

			{ /* Lightbox — live document, or the screenshot (locked Pro card / failed request) */ }
			{ lightboxTpl && (
				<div
					className="bp-template-lightbox-overlay"
					onClick={ () => setLightboxKey( null ) }
				>
					<div
						className={ `bp-template-lightbox${
							lightboxIsLive ? ' bp-template-lightbox--live' : ''
						}` }
						onClick={ ( e ) => e.stopPropagation() }
					>
						{ /* Minimal close — absolute top-right, no background */ }
						<button
							className="bp-template-lightbox__close-btn"
							onClick={ () => setLightboxKey( null ) }
							aria-label={ __( 'Close', 'better-payment' ) }
						>
							<svg
								width="18"
								height="18"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
							>
								<line x1="18" y1="6" x2="6" y2="18" />
								<line x1="6" y1="6" x2="18" y2="18" />
							</svg>
						</button>

						{ /* Header — large title + description, no border */ }
						<div className="bp-template-lightbox__header">
							<h2 className="bp-template-lightbox__title">
								{ lightboxTpl.label }
							</h2>
							{ lightboxTpl.description && (
								<p className="bp-template-lightbox__desc">
									{ lightboxTpl.description }
								</p>
							) }
						</div>

						{ /* Preview area. The live document is the real template
						    rendered by the same code the frontend uses, so the
						    iframe scrolls the design end to end. It falls back to
						    the static screenshot for a locked Pro card (no layout
						    to render) or if the request fails. */ }
						<div
							ref={ lightboxBodyRef }
							className={ `bp-template-lightbox__body${
								lightboxIsLive
									? ' bp-template-lightbox__body--live'
									: ''
							}` }
						>
							{ lightboxIsLive && lightboxDoc && (
								<iframe
									className={ `bp-template-lightbox__frame${
										lightboxFrameReady ? ' is-ready' : ''
									}` }
									srcDoc={ lightboxDoc }
									title={ __(
										'Template preview',
										'better-payment'
									) }
									/* Rendered wide, scaled to fit — the layout
									   must come from the viewport width, not from
									   how big the reviewer's modal happens to be. */
									style={ {
										width: `${ frameGeometry.width }px`,
										height: `${ frameGeometry.height }px`,
										transform: `scale(${ frameGeometry.scale })`,
									} }
									onLoad={ () =>
										markFrameReady( lightboxTpl.key )
									}
									/* allow-same-origin + allow-scripts matches the
									   builder's other preview iframes: the document
									   is the campaign as it renders live, and a
									   nested embed with no script access falls back
									   to its "enable JavaScript" page. */
									sandbox="allow-same-origin allow-scripts"
								/>
							) }
							{ lightboxIsLive && ! lightboxFrameReady && (
								<TemplatePreviewSkeleton
									layout={ lightboxTpl.layout }
								/>
							) }
							{ ! lightboxIsLive &&
								( thumbUrl( lightboxTpl ) ? (
									<img
										src={ thumbUrl( lightboxTpl ) }
										alt={ lightboxTpl.label }
										className="bp-template-lightbox__img"
										onError={ () =>
											markThumbFailed( lightboxTpl.key )
										}
									/>
								) : (
									<div className="bp-template-lightbox__no-preview">
										{ __(
											'No preview available.',
											'better-payment'
										) }
									</div>
								) ) }
						</div>

						{ /* Full-width footer button. On a locked Pro template the
						    same click routes through handleSelect into the upgrade
						    modal instead of applying anything — one gate, not a
						    second copy of the predicate. */ }
						<button
							className={ `bp-template-lightbox__use-btn${
								isTemplateLocked( lightboxTpl, proEnabled )
									? ' is-locked'
									: ''
							}` }
							onClick={ () => {
								handleSelect( lightboxTpl );
								setLightboxKey( null );
							} }
						>
							{ isTemplateLocked( lightboxTpl, proEnabled ) ? (
								<>
									<ProCrownIcon decorative />
									{ __(
										'Upgrade to Pro to Use This Template',
										'better-payment'
									) }
								</>
							) : (
								__( 'Use This Template', 'better-payment' )
							) }
						</button>
					</div>
				</div>
			) }

			{ /* Locked Pro template upsell — carries the pricing-page CTA */ }
			{ upgradeTemplate && (
				<UpgradeModal
					template={ upgradeTemplate }
					onClose={ () => setUpgradeTemplate( null ) }
				/>
			) }

			{ /* Template change confirmation modal */ }
			{ confirmTemplate && (
				<div
					className="bp-template-confirm-overlay"
					onClick={ () => setConfirmTemplate( null ) }
				>
					<div
						className="bp-template-confirm"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<div className="bp-template-confirm__icon">
							<svg
								width="32"
								height="32"
								viewBox="0 0 24 24"
								fill="none"
								stroke="#f59e0b"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							>
								<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
								<line x1="12" y1="9" x2="12" y2="13" />
								<line x1="12" y1="17" x2="12.01" y2="17" />
							</svg>
						</div>
						<h3 className="bp-template-confirm__heading">
							{ __( 'Switch Template?', 'better-payment' ) }
						</h3>
						<p className="bp-template-confirm__body">
							{ __( 'Switching to', 'better-payment' ) }{ ' ' }
							<strong>"{ confirmTemplate.label }"</strong>{ ' ' }
							{ __(
								'will replace your current layout and all its elements. This cannot be undone.',
								'better-payment'
							) }
						</p>
						<div className="bp-template-confirm__actions">
							<button
								className="bp-template-confirm__btn bp-template-confirm__btn--cancel"
								onClick={ () => setConfirmTemplate( null ) }
							>
								{ __( 'Cancel', 'better-payment' ) }
							</button>
							<button
								className="bp-template-confirm__btn bp-template-confirm__btn--confirm"
								onClick={ () => {
									applyTemplate( confirmTemplate );
									setConfirmTemplate( null );
								} }
							>
								{ __(
									'Yes, Switch Template',
									'better-payment'
								) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
}
