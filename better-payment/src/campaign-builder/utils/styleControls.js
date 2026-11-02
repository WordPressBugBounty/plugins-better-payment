/**
 * Pure helpers behind the builder's style controls (Style / Advanced tabs).
 *
 * The PHP side is `Campaign\Styles\StyleControls` (the control groups) and
 * `ElementStyleCompiler` (values → scoped CSS). Kept free of React so Jest can
 * pin them.
 */

/** The sidebar tabs a schema control can belong to. */
export const SETTINGS_TABS = [ 'content', 'style', 'advanced' ];

/**
 * Which sidebar tab draws a control. No `tab` means Content, so every schema
 * written before the tabs existed keeps its controls where they were.
 *
 * @param {Object} control Schema control.
 * @return {string} 'content' | 'style' | 'advanced'.
 */
export function controlTab( control ) {
	return SETTINGS_TABS.includes( control?.tab ) ? control.tab : 'content';
}

/**
 * The top-level schema controls a tab draws.
 *
 * @param {Array}  schema settingsSchema.
 * @param {string} tab    'content' | 'style' | 'advanced'.
 * @return {Array} Controls.
 */
export function controlsForTab( schema, tab ) {
	return ( schema ?? [] ).filter(
		( control ) => controlTab( control ) === tab
	);
}

/**
 * Every leaf control of a schema, descending into sections and state tabs.
 *
 * @param {Array} controls settingsSchema (or children).
 * @return {Array} Leaf controls.
 */
export function leafControls( controls ) {
	const out = [];
	( controls ?? [] ).forEach( ( control ) => {
		if ( ! control || typeof control !== 'object' ) return;
		if ( control.type === 'tabs' ) {
			( control.tabs ?? [] ).forEach( ( tab ) =>
				out.push( ...leafControls( tab?.children ) )
			);
			return;
		}
		if ( Array.isArray( control.children ) && control.children.length ) {
			out.push( ...leafControls( control.children ) );
			return;
		}
		if ( [ 'section', 'section_label', 'note' ].includes( control.type ) ) {
			return;
		}
		out.push( control );
	} );
	return out;
}

/**
 * Setting keys the AI never sees (controls marked `ai: false`, or inside a
 * container marked so). Mirrors `StyleControls::ai_hidden_keys()`.
 *
 * @param {Array} controls settingsSchema.
 * @return {string[]} Keys.
 */
export function aiHiddenKeys( controls ) {
	const keys = [];
	( controls ?? [] ).forEach( ( control ) => {
		if ( ! control || typeof control !== 'object' ) return;
		if ( control.ai === false ) {
			leafControls( [ control ] ).forEach(
				( leaf ) => leaf.key && keys.push( leaf.key )
			);
			return;
		}
		if ( control.type === 'tabs' ) {
			( control.tabs ?? [] ).forEach( ( tab ) =>
				keys.push( ...aiHiddenKeys( tab?.children ) )
			);
		} else if ( Array.isArray( control.children ) ) {
			keys.push( ...aiHiddenKeys( control.children ) );
		}
	} );
	return Array.from( new Set( keys ) );
}

/**
 * Whether a stored key belongs to a hidden control: the key itself, or an
 * add-on's `{key}_{suffix}` variant beside it (Pro's per-device values).
 *
 * @param {string}   key    Stored setting key.
 * @param {string[]} hidden From aiHiddenKeys().
 * @return {boolean} True for a style key.
 */
export function isHiddenKey( key, hidden ) {
	return hidden.some( ( h ) => key === h || key.startsWith( `${ h }_` ) );
}

/**
 * Carry the style settings of the elements an AI `set_layout` replaces onto
 * their replacements.
 *
 * The model never sees style keys (CampaignSchema::without_style_settings()),
 * so a whole-page `set_layout` cannot echo them back. Without this, "shorten the
 * title" would silently wipe every colour, border and spacing the author set.
 * An element is "the same" when its id survived the replacement; the model's
 * own style keys (if it sent any) never win over the stored ones.
 *
 * @param {Array}    prevColumns Columns before the replacement.
 * @param {Array}    nextColumns Columns after it (ids already normalized).
 * @param {Function} schemaOf    type => settingsSchema.
 * @return {Array} nextColumns with style settings restored.
 */
export function carryStyleSettings( prevColumns, nextColumns, schemaOf ) {
	const previous = new Map();
	( prevColumns ?? [] ).forEach( ( col ) =>
		( col?.elements ?? [] ).forEach( ( el ) => {
			if ( el?.id ) previous.set( el.id, el );
		} )
	);
	if ( ! previous.size ) return nextColumns;

	return ( nextColumns ?? [] ).map( ( col ) => ( {
		...col,
		elements: ( col?.elements ?? [] ).map( ( el ) => {
			const prev = previous.get( el?.id );
			if ( ! prev || prev.type !== el.type ) return el;

			const hidden = aiHiddenKeys( schemaOf( el.type ) ?? [] );
			if ( ! hidden.length ) return el;

			const settings = Object.fromEntries(
				Object.entries( el.settings ?? {} ).filter(
					( [ key ] ) => ! isHiddenKey( key, hidden )
				)
			);
			Object.entries( prev.settings ?? {} ).forEach( ( [ key, val ] ) => {
				if ( isHiddenKey( key, hidden ) ) settings[ key ] = val;
			} );
			return { ...el, settings };
		} ),
	} ) );
}

// ── Colour ───────────────────────────────────────────────────────────────────

const HEX = /^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i;
const RGB =
	/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*(0|1|0?\.\d+))?\s*\)$/i;

/**
 * Whether the compiler would accept a colour (ElementStyleCompiler::color()).
 *
 * @param {*} value Candidate.
 * @return {boolean} True for hex 3/4/6/8, rgb()/rgba() or `transparent`.
 */
export function isCssColor( value ) {
	if ( typeof value !== 'string' ) return false;
	const v = value.trim();
	if ( v.toLowerCase() === 'transparent' || HEX.test( v ) ) return true;
	const m = v.match( RGB );
	return !! m && [ m[ 1 ], m[ 2 ], m[ 3 ] ].every( ( c ) => +c <= 255 );
}

/**
 * Split a colour into a 6-digit hex and an opacity 0–1, for the picker.
 * Unparseable input gives `{ hex: '', alpha: 1 }`.
 *
 * @param {string} value Stored colour.
 * @return {{hex: string, alpha: number}} Parts.
 */
export function splitAlpha( value ) {
	const v = typeof value === 'string' ? value.trim() : '';
	if ( HEX.test( v ) ) {
		let h = v.slice( 1 );
		if ( h.length <= 4 ) {
			h = h
				.split( '' )
				.map( ( c ) => c + c )
				.join( '' );
		}
		const alpha =
			h.length === 8
				? Math.round( ( parseInt( h.slice( 6 ), 16 ) / 255 ) * 100 ) /
				  100
				: 1;
		return { hex: `#${ h.slice( 0, 6 ).toUpperCase() }`, alpha };
	}
	const m = v.match( RGB );
	if ( m ) {
		const hex =
			'#' +
			[ m[ 1 ], m[ 2 ], m[ 3 ] ]
				.map( ( c ) =>
					Math.min( 255, +c ).toString( 16 ).padStart( 2, '0' )
				)
				.join( '' )
				.toUpperCase();
		return { hex, alpha: m[ 4 ] !== undefined ? +m[ 4 ] : 1 };
	}
	return { hex: '', alpha: 1 };
}

/**
 * A 6-digit hex plus an opacity → `#RRGGBB` (opaque) or `#RRGGBBAA`.
 *
 * @param {string} hex   `#RRGGBB`.
 * @param {number} alpha 0–1.
 * @return {string} Colour.
 */
export function withAlpha( hex, alpha ) {
	const a = Math.max( 0, Math.min( 1, Number( alpha ) ) );
	if ( Number.isNaN( a ) || a >= 1 ) return hex.toUpperCase();
	return (
		hex.toUpperCase() +
		Math.round( a * 255 )
			.toString( 16 )
			.padStart( 2, '0' )
			.toUpperCase()
	);
}

// ── Dimensions / shadow ──────────────────────────────────────────────────────

export const SIDES = [ 'top', 'right', 'bottom', 'left' ];

/**
 * Normalize a stored dimensions value for the control.
 *
 * @param {*}        value Stored value.
 * @param {string[]} units Allowed units (first = default).
 * @return {{top: string, right: string, bottom: string, left: string, unit: string, linked: boolean}} Value.
 */
export function readDimensions( value, units = [ 'px' ] ) {
	const v = value && typeof value === 'object' ? value : {};
	const out = { unit: units.includes( v.unit ) ? v.unit : units[ 0 ] };
	SIDES.forEach( ( side ) => {
		out[ side ] =
			v[ side ] === undefined || v[ side ] === null
				? ''
				: String( v[ side ] );
	} );
	out.linked =
		typeof v.linked === 'boolean'
			? v.linked
			: SIDES.every( ( side ) => out[ side ] === out.top );
	return out;
}

/**
 * Apply one side's edit. A linked control writes all four sides.
 *
 * @param {Object}   current From readDimensions().
 * @param {string}   side    'top' | 'right' | 'bottom' | 'left'.
 * @param {string}   raw     Input text.
 * @param {string[]} units   Allowed units (first = default).
 * @return {Object|string} The next value (see dimensionsValue()).
 */
export function editDimension( current, side, raw, units = [ 'px' ] ) {
	const next = { ...current };
	const val = raw === '' || raw === '-' ? '' : raw;
	( current.linked ? SIDES : [ side ] ).forEach( ( s ) => {
		next[ s ] = val;
	} );
	return dimensionsValue( next, units );
}

/**
 * What a dimensions control stores: '' (unset) when every side is blank and
 * the rest is the control's default (linked, first unit); otherwise the object.
 * A blank value that is unlinked or on another unit is kept, or the choice would
 * not stick — '' reads back as linked and on the first unit. The compiler prints
 * nothing for an all-blank object either way.
 *
 * @param {Object}   dims  Sides, unit, linked.
 * @param {string[]} units Allowed units (first = default).
 * @return {Object|string} Stored value.
 */
export function dimensionsValue( dims, units = [ 'px' ] ) {
	return SIDES.every( ( s ) => dims[ s ] === '' ) &&
		dims.linked !== false &&
		dims.unit === units[ 0 ]
		? ''
		: dims;
}

/** The shadow a box-shadow control starts from when switched on. */
export const DEFAULT_SHADOW = Object.freeze( {
	x: 0,
	y: 4,
	blur: 12,
	spread: 0,
	color: '#00000026',
	inset: false,
} );

/**
 * Normalize a stored box-shadow value, or null when unset.
 *
 * @param {*} value Stored value.
 * @return {Object|null} Shadow.
 */
export function readShadow( value ) {
	if ( ! value || typeof value !== 'object' || ! isCssColor( value.color ) ) {
		return null;
	}
	return { ...DEFAULT_SHADOW, ...value };
}

// ── Number fields ────────────────────────────────────────────────────────────

/**
 * What a number field stores for its typed text. A fractional `step` keeps
 * decimals; any other control takes whole numbers, so a schema must declare a
 * fractional step or `-0.4` is cut to 0.
 *
 * @param {string} raw  The input's value.
 * @param {*}      step The control's `step`.
 * @return {number|string} The number, or '' when blank or not a number.
 */
export function parseNumberValue( raw, step ) {
	if ( raw === '' ) return '';
	const decimals = typeof step === 'number' && ! Number.isInteger( step );
	const n = decimals ? parseFloat( raw ) : parseInt( raw, 10 );
	return Number.isNaN( n ) ? '' : n;
}

// ── Sections (one open at a time) ────────────────────────────────────────────

/**
 * The section a tab opens with: its first one, so a tab with sections never
 * opens as a column of closed headers. Only one section is ever open on a tab.
 *
 * @param {Array} controls The tab's controls, in order, visible only.
 * @return {string|null} Section key.
 */
export function initialOpenSection( controls ) {
	return (
		( controls ?? [] ).find( ( c ) => c?.type === 'section' )?.key ?? null
	);
}

/**
 * Clicking a section header: opens it and closes the one that was open;
 * clicking the open one closes it.
 *
 * @param {string|null} current Open section key.
 * @param {string}      key     Clicked section key.
 * @return {string|null} The open section after the click.
 */
export function toggleSection( current, key ) {
	return current === key ? null : key;
}
