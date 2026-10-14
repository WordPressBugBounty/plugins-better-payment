/**
 * Single-select option group for the Smart Prompt Wizard.
 *
 * Used for short answer lists (Layout, the amount presets) where a wrapping
 * cloud of pill buttons would be the multi-select/tag idiom — nothing about it
 * says only one answer counts, and assistive tech hears a handful of unrelated
 * buttons. This renders the same answers as a real radio group:
 * `role="radiogroup"` + `aria-checked`, a visible radio dot, roving tabindex,
 * and arrow-key navigation. Longer answer lists (cause, tone, audience, end
 * date) use SelectField — a native `<select>` — instead, both because a tile
 * grid for them would sprawl several rows deep and because a select gets a
 * built-in "no preference" affordance (the blank placeholder option) for free.
 *
 * Options auto-fit into columns, so short answers ("2 Column") pack tightly while
 * long ones ("Friends & family") wrap instead of truncating. Callers tune the
 * column floor with `minWidth`.
 *
 * `children` render inside the question, below the options.
 */

import { useRef } from '@wordpress/element';

/**
 * Resolve the index a keyboard event should move a radio group's selection to.
 *
 * Wraps at both ends, per the ARIA radio-group pattern. When nothing is selected
 * yet, forward keys enter at the first option and backward keys at the last.
 *
 * @param {number} current Index of the selected option, or -1 when none is.
 * @param {string} key     KeyboardEvent.key.
 * @param {number} count   Number of options in the group.
 * @return {number} The next index, or -1 when the key is not a navigation key.
 */
export function nextRadioIndex( current, key, count ) {
	if ( ! Number.isInteger( count ) || count < 1 ) {
		return -1;
	}

	const hasSelection =
		Number.isInteger( current ) && current >= 0 && current < count;

	switch ( key ) {
		case 'ArrowRight':
		case 'ArrowDown':
			return hasSelection ? ( current + 1 ) % count : 0;
		case 'ArrowLeft':
		case 'ArrowUp':
			return hasSelection ? ( current - 1 + count ) % count : count - 1;
		case 'Home':
			return 0;
		case 'End':
			return count - 1;
		default:
			return -1;
	}
}

export default function ChoiceGroup( {
	id,
	label,
	ariaLabel,
	options,
	value,
	onChange,
	disabled = false,
	minWidth = 118,
	children,
} ) {
	const buttonRefs = useRef( [] );
	const selectedIndex = options.findIndex(
		( option ) => option.value === value
	);

	// Bound to each radio rather than the group: the radios are what hold focus
	// (the group itself is not a tab stop), so this is where the key actually
	// lands. Putting it on the container would rely on bubbling from a child that
	// jsx-a11y then — correctly — wants made focusable.
	const handleKeyDown = ( event ) => {
		const next = nextRadioIndex( selectedIndex, event.key, options.length );
		if ( next < 0 || disabled ) {
			return;
		}
		event.preventDefault();
		onChange( options[ next ]?.value );
		buttonRefs.current[ next ]?.focus();
	};

	// Roving tabindex: exactly one option is tabbable. With nothing selected the
	// first option holds the tab stop so the group stays keyboard-reachable.
	const tabStop = selectedIndex < 0 ? 0 : selectedIndex;

	const group = (
		<div
			role="radiogroup"
			aria-labelledby={ label ? `${ id }-label` : undefined }
			aria-label={ label ? undefined : ariaLabel }
			className="bp-cb-aiw__choices"
			style={ { '--bp-choice-min': `${ minWidth }px` } }
		>
			{ options.map( ( option, index ) => {
				const selected = index === selectedIndex;
				return (
					<div
						className="bp-cb-aiw__choice-cell"
						key={ option.value }
					>
						<button
							ref={ ( el ) =>
								( buttonRefs.current[ index ] = el )
							}
							type="button"
							role="radio"
							aria-checked={ selected }
							tabIndex={ index === tabStop ? 0 : -1 }
							disabled={ disabled }
							className={ `bp-cb-aiw__choice${
								selected ? ' is-selected' : ''
							}` }
							onClick={ () => onChange( option.value ) }
							onKeyDown={ handleKeyDown }
						>
							<span
								className="bp-cb-aiw__choice-dot"
								aria-hidden="true"
							/>
							<span className="bp-cb-aiw__choice-label">
								{ option.label }
							</span>
						</button>
					</div>
				);
			} ) }
		</div>
	);

	// A `label` makes this a question in its own right, so it gets the question
	// wrapper. An `ariaLabel` instead means it is a sub-control of the question
	// around it (the amount presets under the amount field) — wrapping that in a
	// second `__q` would nest one question inside another and double its spacing.
	if ( ! label ) {
		return (
			<>
				{ group }
				{ children }
			</>
		);
	}

	return (
		<div className="bp-cb-aiw__q">
			<span className="bp-cb-aiw__label" id={ `${ id }-label` }>
				{ label }
			</span>
			{ group }
			{ children }
		</div>
	);
}
