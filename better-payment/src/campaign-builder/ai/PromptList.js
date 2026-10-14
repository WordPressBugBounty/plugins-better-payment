/**
 * Suggested prompts — every one visible, in as little height as possible.
 *
 * Two earlier shapes were wrong in opposite directions. As **chips** they
 * wrapped over four rows of pill buttons, gaining a row with every suggestion
 * added and pushing the conversation out of a 340px sidebar. As a **carousel**
 * the height was fixed but only one prompt was legible at a time, which is the
 * opposite of what a suggestion list is for: you pick by scanning, and a deck
 * you have to page through to read is slower than typing the request yourself.
 *
 * So: one compact line per prompt, all of them on screen at once. A row is a
 * single line — prompts are short imperative sentences, and letting them wrap
 * would put the height back on a variable it should not be on. Anything longer
 * than the rail ellipsizes, with the full text in `title`.
 *
 * There is no pure logic here to unit-test; the list is a map over its props.
 */

import { __ } from '@wordpress/i18n';

export default function PromptList( { items, onSelect, onClose, disabled } ) {
	if ( ! items.length ) return null;

	return (
		<section
			className="bp-cb-ai__prompts"
			aria-label={ __( 'Suggested prompts', 'better-payment' ) }
		>
			<div className="bp-cb-ai__prompts-head">
				<span className="bp-cb-ai__prompts-title">
					{ __( 'Try One of These', 'better-payment' ) }
				</span>
				<button
					type="button"
					className="bp-cb-ai__prompts-close"
					onClick={ onClose }
					aria-label={ __(
						'Hide suggested prompts',
						'better-payment'
					) }
				>
					<span className="dashicons dashicons-no-alt" />
				</button>
			</div>

			<ul className="bp-cb-ai__prompts-list">
				{ items.map( ( item ) => (
					<li key={ item }>
						<button
							type="button"
							className="bp-cb-ai__prompt-row"
							onClick={ () => onSelect( item ) }
							disabled={ disabled }
							// The row clips at one line; the tooltip is how a
							// long prompt stays readable without a taller list.
							title={ item }
						>
							<span className="dashicons dashicons-arrow-right-alt2" />
							<span className="bp-cb-ai__prompt-row-text">
								{ item }
							</span>
						</button>
					</li>
				) ) }
			</ul>
		</section>
	);
}
