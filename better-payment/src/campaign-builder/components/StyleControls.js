import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ColorPicker from './ColorPicker';
import Popover from './Popover';
import {
	SIDES,
	DEFAULT_SHADOW,
	dimensionsValue,
	editDimension,
	isCssColor,
	readDimensions,
	readShadow,
} from '../utils/styleControls';

/**
 * The builder's style control widgets: colour, four-sided dimensions, box
 * shadow and Normal/Hover state tabs. Drawn by RightSidebar's SettingsControl
 * for the matching schema `type`; the values they write are compiled to CSS by
 * `Campaign\Styles\ElementStyleCompiler`.
 *
 * Every control can be cleared back to "unset", and unset is the only state in
 * which the template theme styles the element — so a cleared control writes
 * '' rather than a colour or a zero.
 *
 * Dimensions and box shadow draw their own label row (label left, unit / link
 * or switch right) and take the panel's full width: inside a collapsible
 * section every other control sits beside a 44% label, which left four number
 * fields about 10px each (RightSidebar's STACKED_TYPES).
 */

const SIDE_LABELS = {
	top: __( 'Top', 'better-payment' ),
	right: __( 'Right', 'better-payment' ),
	bottom: __( 'Bottom', 'better-payment' ),
	left: __( 'Left', 'better-payment' ),
};

/**
 * Swatch + typed value + clear. The swatch opens the picker in a popover —
 * the sidebar column is too narrow to hold it in place.
 *
 * The text field keeps the `ctrl-{key}` id the native colour input had, so a
 * typed hex still works and E2E specs can fill it.
 *
 * @param {Object}   props
 * @param {string}   props.id       Input id.
 * @param {string}   props.value    Stored colour ('' = unset).
 * @param {Function} props.onChange Receives the colour, or '' when cleared.
 * @param {boolean}  props.alpha    Offer an opacity slider.
 * @param {string}   props.fallback Colour the picker opens on while unset.
 * @param {string}   props.label    Name for the picker dialog.
 */
export function ColorControl( {
	id,
	value,
	onChange,
	alpha = false,
	fallback = '#6B63F6',
	label = '',
} ) {
	const [ open, setOpen ] = useState( false );
	const [ text, setText ] = useState( value ?? '' );
	const swatchRef = useRef( null );
	const isSet = isCssColor( value );

	useEffect( () => setText( value ?? '' ), [ value ] );

	return (
		<div className="bp-cb-color">
			<button
				ref={ swatchRef }
				type="button"
				className={ `bp-cb-color__swatch${ isSet ? '' : ' is-unset' }${
					open ? ' is-active' : ''
				}` }
				aria-label={
					label
						? `${ __(
								'Pick a colour',
								'better-payment'
						  ) }: ${ label }`
						: __( 'Pick a colour', 'better-payment' )
				}
				aria-haspopup="dialog"
				aria-expanded={ open }
				onClick={ () => setOpen( ( v ) => ! v ) }
			>
				{ isSet && (
					<span
						className="bp-cb-color__fill"
						style={ { background: value } }
					/>
				) }
			</button>
			<div className="bp-cb-color__field">
				<input
					id={ id }
					type="text"
					className="bp-cb-color__input"
					value={ text }
					placeholder={ __( 'Default', 'better-payment' ) }
					spellCheck={ false }
					onChange={ ( e ) => {
						const next = e.target.value;
						setText( next );
						if ( next.trim() === '' ) onChange( '' );
						else if ( isCssColor( next ) ) onChange( next.trim() );
					} }
					onBlur={ () => setText( value ?? '' ) }
				/>
				{ isSet && (
					<button
						type="button"
						className="bp-cb-color__clear"
						aria-label={ __( 'Clear colour', 'better-payment' ) }
						title={ __( 'Clear', 'better-payment' ) }
						onClick={ () => {
							onChange( '' );
							setOpen( false );
						} }
					>
						<svg
							width="10"
							height="10"
							viewBox="0 0 10 10"
							aria-hidden="true"
						>
							<path
								d="M2 2l6 6M8 2L2 8"
								stroke="currentColor"
								strokeWidth="1.6"
								strokeLinecap="round"
							/>
						</svg>
					</button>
				) }
			</div>
			{ open && (
				<Popover
					anchorRef={ swatchRef }
					label={ label || __( 'Colour', 'better-payment' ) }
					onClose={ () => setOpen( false ) }
				>
					<ColorPicker
						value={ isSet ? value : fallback }
						defaultValue={ fallback }
						alpha={ alpha }
						onChange={ onChange }
						onReset={ () => {
							onChange( '' );
							setOpen( false );
						} }
						resetLabel={ __( 'Clear', 'better-payment' ) }
						onClose={ () => setOpen( false ) }
					/>
				</Popover>
			) }
		</div>
	);
}

/**
 * The label row a stacked control draws itself: label left, actions right.
 *
 * @param {Object} props
 * @param {string} props.htmlFor  Input the label names.
 * @param {string} props.label    Label text.
 * @param {*}      props.children Actions.
 */
export function ControlHeader( { htmlFor, label, children } ) {
	return (
		<div className="bp-cb-control-head">
			<label className="bp-cb-field-label" htmlFor={ htmlFor }>
				{ label }
			</label>
			{ children && (
				<div className="bp-cb-control-head__actions">{ children }</div>
			) }
		</div>
	);
}

/**
 * Four sides with a link toggle and a unit switch.
 *
 * ```
 * PADDING                      px  %  em
 * [  8 ][  8 ][  8 ][  8 ]  (🔗)
 *  Top  Right Bottom Left
 * ```
 *
 * @param {Object}   props
 * @param {string}   props.id       Id prefix (the Top field's id).
 * @param {string}   props.label    Label.
 * @param {*}        props.value    Stored dimensions.
 * @param {Function} props.onChange Receives the value, or '' when every side is blank.
 * @param {string[]} props.units    Allowed units.
 * @param {number}   props.min      Lower bound (omitted = negatives allowed).
 */
export function DimensionsControl( {
	id,
	label,
	value,
	onChange,
	units,
	min,
} ) {
	const allowed = units?.length ? units : [ 'px' ];
	const dims = readDimensions( value, allowed );

	const write = ( patch ) =>
		onChange( dimensionsValue( { ...dims, ...patch }, allowed ) );

	return (
		<div className="bp-cb-dims">
			<ControlHeader htmlFor={ id } label={ label }>
				{ allowed.length > 1 && (
					<div
						className="bp-cb-units"
						role="radiogroup"
						aria-label={ __( 'Unit', 'better-payment' ) }
					>
						{ allowed.map( ( unit ) => (
							<button
								key={ unit }
								type="button"
								role="radio"
								aria-checked={ dims.unit === unit }
								className={ `bp-cb-units__btn${
									dims.unit === unit ? ' is-active' : ''
								}` }
								onClick={ () => write( { unit } ) }
							>
								{ unit }
							</button>
						) ) }
					</div>
				) }
			</ControlHeader>
			<div className="bp-cb-dims__row">
				<div className="bp-cb-dims__fields">
					{ SIDES.map( ( side ) => (
						<label key={ side } className="bp-cb-num-field">
							<input
								id={ side === 'top' ? id : `${ id }-${ side }` }
								type="number"
								min={ min }
								value={ dims[ side ] }
								placeholder="–"
								onChange={ ( e ) =>
									onChange(
										editDimension(
											dims,
											side,
											e.target.value,
											allowed
										)
									)
								}
							/>
							<span>{ SIDE_LABELS[ side ] }</span>
						</label>
					) ) }
				</div>
				<button
					type="button"
					className={ `bp-cb-dims__link${
						dims.linked ? ' is-active' : ''
					}` }
					aria-pressed={ dims.linked }
					aria-label={
						dims.linked
							? __( 'Unlink values', 'better-payment' )
							: __( 'Link values together', 'better-payment' )
					}
					title={
						dims.linked
							? __( 'Unlink values', 'better-payment' )
							: __( 'Link values together', 'better-payment' )
					}
					onClick={ () => write( { linked: ! dims.linked } ) }
				>
					<svg
						width="14"
						height="14"
						viewBox="0 0 20 20"
						fill="none"
						aria-hidden="true"
					>
						<path
							d="M8.5 11.5l3-3M7 9.5l-1.8 1.8a2.5 2.5 0 003.5 3.5L10.5 13M13 10.5l1.8-1.8a2.5 2.5 0 00-3.5-3.5L9.5 7"
							stroke="currentColor"
							strokeWidth="1.6"
							strokeLinecap="round"
						/>
					</svg>
				</button>
			</div>
		</div>
	);
}

/**
 * A switch in the label row turns the shadow on; then X / Y / Blur / Spread,
 * the colour and Inset. Off writes '' (unset).
 *
 * @param {Object}   props
 * @param {string}   props.id       Id prefix (the switch's id).
 * @param {string}   props.label    Label.
 * @param {*}        props.value    Stored shadow.
 * @param {Function} props.onChange Receives the shadow, or ''.
 */
export function BoxShadowControl( { id, label, value, onChange } ) {
	const shadow = readShadow( value );
	const on = shadow !== null;
	const set = ( patch ) =>
		onChange( { ...( shadow ?? DEFAULT_SHADOW ), ...patch } );

	const fields = [
		[ 'x', __( 'X', 'better-payment' ), -200, 200 ],
		[ 'y', __( 'Y', 'better-payment' ), -200, 200 ],
		[ 'blur', __( 'Blur', 'better-payment' ), 0, 300 ],
		[ 'spread', __( 'Spread', 'better-payment' ), -200, 200 ],
	];

	return (
		<div className="bp-cb-shadow">
			<ControlHeader htmlFor={ id } label={ label }>
				{ /* A <label>, so the track itself toggles — the input is
				     visually hidden and a bare <span> track ignored clicks. */ }
				<label className="bp-cb-switch-wrap" htmlFor={ id }>
					<input
						id={ id }
						type="checkbox"
						checked={ on }
						onChange={ ( e ) =>
							onChange(
								e.target.checked ? { ...DEFAULT_SHADOW } : ''
							)
						}
					/>
					<span className="bp-cb-switch-track" />
				</label>
			</ControlHeader>
			{ on && (
				<div className="bp-cb-shadow__body">
					<div className="bp-cb-dims__fields">
						{ fields.map( ( [ key, fieldLabel, lo, hi ] ) => (
							<label key={ key } className="bp-cb-num-field">
								<input
									type="number"
									min={ lo }
									max={ hi }
									value={ shadow[ key ] }
									onChange={ ( e ) =>
										set( {
											[ key ]:
												e.target.value === ''
													? 0
													: Number( e.target.value ),
										} )
									}
								/>
								<span>{ fieldLabel }</span>
							</label>
						) ) }
					</div>
					<div className="bp-cb-shadow__row">
						<ColorControl
							id={ `${ id }-color` }
							label={ label }
							value={ shadow.color }
							alpha
							fallback="#000000"
							onChange={ ( color ) =>
								// Clearing the colour turns the shadow off: a
								// shadow without a colour prints nothing.
								color ? set( { color } ) : onChange( '' )
							}
						/>
						<label className="bp-cb-toggle-label bp-cb-shadow__inset">
							<input
								type="checkbox"
								checked={ !! shadow.inset }
								onChange={ ( e ) =>
									set( { inset: e.target.checked } )
								}
							/>
							<span>{ __( 'Inset', 'better-payment' ) }</span>
						</label>
					</div>
				</div>
			) }
		</div>
	);
}

/**
 * Normal / Hover (/ any) state tabs. `renderChildren( children )` draws a tab's
 * controls with the panel's own control renderer.
 *
 * @param {Object}   props
 * @param {Array}    props.tabs           [ { key, label, children } ].
 * @param {Function} props.renderChildren Draws an array of controls.
 */
export function StateTabs( { tabs, renderChildren } ) {
	const [ active, setActive ] = useState( tabs?.[ 0 ]?.key );
	const current = tabs?.find( ( t ) => t.key === active ) ?? tabs?.[ 0 ];
	if ( ! current ) return null;

	return (
		<div className="bp-cb-states">
			<div className="bp-cb-states__tabs" role="tablist">
				{ tabs.map( ( tab ) => (
					<button
						key={ tab.key }
						type="button"
						role="tab"
						aria-selected={ tab.key === current.key }
						className={ `bp-cb-states__tab${
							tab.key === current.key ? ' is-active' : ''
						}` }
						onClick={ () => setActive( tab.key ) }
					>
						{ tab.label }
					</button>
				) ) }
			</div>
			<div className="bp-cb-states__body" role="tabpanel">
				{ renderChildren( current.children ?? [] ) }
			</div>
		</div>
	);
}
