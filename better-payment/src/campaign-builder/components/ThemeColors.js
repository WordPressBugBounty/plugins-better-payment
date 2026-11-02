import {
	useState,
	useEffect,
	useMemo,
	useRef,
	useCallback,
	createPortal,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	backgroundFieldState,
	resolveThemeBackground,
	themeClassForKey,
} from '../utils/themeBackground';
import ColorPicker from './ColorPicker';

// ── Theme Colors ──────────────────────────────────────────────────────────────
//
// Lives on the Settings tab. It was the Customize panel's Advanced tab until that
// tab became the per-element Advanced settings (spacing, background, border).

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

export default function ThemeColors( { meta, dispatch } ) {
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
