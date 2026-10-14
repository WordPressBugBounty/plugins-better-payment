/**
 * What a campaign template's theme paints behind the page.
 *
 * The Advanced tab's Background control edits `bpc_color_background`, which the
 * renderer treats as an OVERRIDE: empty meta means "the theme decides", and only
 * a non-empty value emits `background-color: … !important` on the wrapper
 * (`RendererService::generate_color_style()`). The control did not model that. It
 * declared a flat `#ffffff` default, so every campaign — including the dark
 * `bp-v2-nightsky` design and Pro's four — reported a white background it did not
 * have, and the picker's Reset button wrote that white in as a real override,
 * repainting the themed page white with no way back.
 *
 * The theme's own page colour is the `--bp-v2-page` custom property declared on
 * `.bp-campaign.{theme_class}` in the campaign-display stylesheet (free themes)
 * and in Pro's `campaign-themes.scss` (Pro's four). Both sheets are loaded on the
 * builder page — `CPT::enqueue_builder_assets()` enqueues the first and fires
 * `better_payment/campaign/enqueue_styles` with `'builder'` for the second — so
 * the value is read back out of the CSS with an off-screen probe element rather
 * than duplicated in JS or PHP, where it would drift from the stylesheet that
 * actually renders the page. That also means Pro's themes resolve with no
 * Pro-specific code here, and a free install probing a Pro theme class gets the
 * neutral `:where()` fallback tokens Lite ships — in both cases exactly what the
 * campaign renders.
 */

export const THEME_PAGE_TOKEN = '--bp-v2-page';

/**
 * Cache of `theme_class => page colour`.
 *
 * Only non-empty results are cached: a probe that runs before the campaign
 * stylesheet has loaded reads nothing, and caching that would pin the control to
 * "no theme colour" for the rest of the session.
 */
const cache = new Map();

/**
 * The theme class a campaign renders under, for the template it was built from.
 *
 * Resolved against the whole template registry (`templateThemes`, localized from
 * `TemplateManager::theme_classes()`), so a campaign on a retired design still
 * reports its theme. A template with no theme class — the blank layouts, the two
 * pre-2.3.2 designs — has none, and there is nothing to report.
 *
 * @param {string} templateKey `bpc_template_key` as stored on the campaign.
 * @param {Object} themes      `template key => theme_class`.
 * @return {string} The theme class, or `''`.
 */
export function themeClassForKey( templateKey, themes ) {
	if ( ! templateKey || ! themes ) return '';
	const themeClass = themes[ templateKey ];
	return typeof themeClass === 'string' ? themeClass.trim() : '';
}

/**
 * Normalise a CSS colour to a `#rrggbb` string.
 *
 * The tokens are authored as hex today, so the fast path is a regex. Anything
 * else (a named colour, `rgb()`) is resolved by letting the browser parse it as a
 * `color`, because the swatch and the colour picker both need hex.
 *
 * @param {string}   value CSS colour, as authored.
 * @param {Document} doc   Document to parse against.
 * @return {string} `#rrggbb`, or `''` when the value is not a usable colour.
 */
export function normalizeColor( value, doc = document ) {
	const raw = String( value ?? '' ).trim();
	if ( ! raw ) return '';

	if ( /^#[0-9a-f]{6}$/i.test( raw ) ) return raw.toLowerCase();

	if ( /^#[0-9a-f]{3}$/i.test( raw ) ) {
		const [ r, g, b ] = raw.slice( 1 ).split( '' );
		return `#${ r }${ r }${ g }${ g }${ b }${ b }`.toLowerCase();
	}

	const rgb = raw.match( /^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/i );
	if ( rgb ) {
		const hex = rgb
			.slice( 1, 4 )
			.map( ( part ) => {
				const n = Math.max( 0, Math.min( 255, Math.round( +part ) ) );
				return n.toString( 16 ).padStart( 2, '0' );
			} )
			.join( '' );
		return `#${ hex }`;
	}

	// Named colours and any other syntax: let the engine resolve it. `color` has
	// to be read back COMPUTED, not off the inline style — an inline style
	// serialises a keyword back as the keyword, so `red` would round-trip
	// unchanged and get us no further.
	const host = doc?.body || doc?.documentElement;
	if ( ! host || ! doc?.defaultView ) return '';

	const probe = doc.createElement( 'div' );
	probe.setAttribute(
		'style',
		`position:absolute;left:-9999px;top:0;visibility:hidden;color:${ raw }`
	);
	host.appendChild( probe );

	let computed = '';
	try {
		computed = doc.defaultView.getComputedStyle( probe ).color || '';
	} catch ( e ) {
		computed = '';
	} finally {
		probe.remove();
	}

	const resolved = computed.trim();
	// Guard the recursion: only re-enter for a syntax the fast paths handle.
	return /^(#|rgba?\()/i.test( resolved ) ? normalizeColor( resolved ) : '';
}

/**
 * Read a custom property off an off-screen `.bp-campaign.{theme_class}` probe.
 *
 * Exported for the tests, which cannot rely on jsdom resolving a custom property
 * through the cascade.
 *
 * @param {string}   themeClass Theme class to probe.
 * @param {Document} doc        Document whose stylesheets are consulted.
 * @return {string} The raw token value, or `''`.
 */
export function readThemeToken( themeClass, doc = document ) {
	const host = doc?.body || doc?.documentElement;
	if ( ! themeClass || ! host || ! doc.defaultView ) return '';

	const probe = doc.createElement( 'div' );
	probe.className = `bp-campaign ${ themeClass }`;
	// Out of flow and out of sight: the probe exists only to be read. Not
	// `display:none` — a detached or undisplayed element resolves no computed
	// value in some engines.
	probe.setAttribute(
		'style',
		'position:absolute;left:-9999px;top:0;width:0;height:0;overflow:hidden;visibility:hidden;pointer-events:none'
	);
	probe.setAttribute( 'aria-hidden', 'true' );
	host.appendChild( probe );

	let value = '';
	try {
		value = doc.defaultView
			.getComputedStyle( probe )
			.getPropertyValue( THEME_PAGE_TOKEN );
	} catch ( e ) {
		value = '';
	} finally {
		probe.remove();
	}

	return String( value || '' ).trim();
}

/**
 * The page colour a theme class paints, as `#rrggbb`.
 *
 * @param {string}   themeClass Theme class, e.g. `bp-v2-nightsky`.
 * @param {Document} doc        Document whose stylesheets are consulted.
 * @return {string} `#rrggbb`, or `''` when the theme declares none.
 */
export function resolveThemeBackground( themeClass, doc = document ) {
	if ( ! themeClass ) return '';
	if ( cache.has( themeClass ) ) return cache.get( themeClass );

	const hex = normalizeColor( readThemeToken( themeClass, doc ), doc );
	if ( hex ) cache.set( themeClass, hex );

	return hex;
}

/**
 * Forget the probed colours — for tests, and for a stylesheet that arrives late.
 *
 * @return {void}
 */
export function clearThemeBackgroundCache() {
	cache.clear();
}

/**
 * What the Background row shows, given the campaign's meta and its theme.
 *
 * Three states, and the middle one is the one that was missing:
 *
 * - an override is set → that colour, editable, clearable;
 * - no override, the theme paints something → the theme's colour, flagged as
 *   inherited so Reset can clear rather than freeze it;
 * - no override and no theme colour (a blank layout, a pre-`-v2` design) → no
 *   colour at all, because the campaign really does inherit the site's own
 *   background. Claiming `#ffffff` here is the same lie in a quieter place.
 *
 * @param {Object} args                 Arguments.
 * @param {string} args.override        `bpc_color_background` as stored.
 * @param {string} args.themeBackground The theme's page colour, or `''`.
 * @return {{hex: string, isThemeDefault: boolean, hasColor: boolean, display: string}}
 *         `hex` for the picker and swatch, `display` for the text input.
 */
export function backgroundFieldState( { override, themeBackground } = {} ) {
	const set = normalizeColor( override );

	if ( set ) {
		return {
			hex: set,
			isThemeDefault: false,
			hasColor: true,
			display: set.toUpperCase(),
		};
	}

	const theme = normalizeColor( themeBackground );

	return {
		hex: theme,
		isThemeDefault: true,
		hasColor: !! theme,
		display: theme ? theme.toUpperCase() : '',
	};
}
