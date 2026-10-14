/**
 * Native `<select>` question for the Smart Prompt Wizard.
 *
 * Used for questions with a longer answer list (cause, tone, audience, end date)
 * where a grid of tiles (see ChoiceGroup) would run several rows deep. A native
 * select needs no custom keyboard handling or ARIA roving-tabindex plumbing —
 * the browser already provides all of that — and it gives every optional
 * question a built-in "no preference" affordance for free: the first option is
 * always the blank placeholder, so re-opening the dropdown and picking it again
 * clears the answer. That replaces the separate **Clear** button ChoiceGroup
 * needs for the same job.
 *
 * `children` render inside the field's own positioning context (below the
 * select, `position: relative`), so an addon can float against it — the end
 * date's calendar (see DatePopover) opens this way once "Pick a date" is chosen.
 */

import { __ } from '@wordpress/i18n';

export default function SelectField( {
	id,
	label,
	options,
	value,
	onChange,
	placeholder = __( 'Select an option', 'better-payment' ),
	disabled = false,
	children,
} ) {
	return (
		<div className="bp-cb-aiw__q">
			<label className="bp-cb-aiw__label" htmlFor={ id }>
				{ label }
			</label>
			<div className="bp-cb-aiw__select">
				<select
					id={ id }
					className="bp-cb-aiw__select-control"
					value={ value }
					onChange={ ( e ) => onChange( e.target.value ) }
					disabled={ disabled }
				>
					<option value="">{ placeholder }</option>
					{ options.map( ( option ) => (
						<option key={ option.value } value={ option.value }>
							{ option.label }
						</option>
					) ) }
				</select>
				{ children }
			</div>
		</div>
	);
}
