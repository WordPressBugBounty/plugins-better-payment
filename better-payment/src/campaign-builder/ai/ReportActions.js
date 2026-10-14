/**
 * The action list that closes an analysis report.
 *
 * The report's findings are prose — they say the Donate Button asks weakly, not
 * which field to open. This is the checklist under them: **which fields need to
 * improve**, grouped by the widget (or the campaign settings) they belong to,
 * each row naming the field and carrying its own Apply.
 *
 * Grouping and ordering live in `groupSuggestions()` so they can be tested
 * without mounting React; this file is the markup over that shape.
 */

import { __, sprintf, _n } from '@wordpress/i18n';
import { groupSuggestions } from './operationExecutor';

export default function ReportActions( {
	suggestions,
	state,
	sending,
	onApply,
	onApplyAll,
} ) {
	const groups = groupSuggestions( suggestions, state );
	if ( ! groups.length ) return null;

	const pending = ( suggestions || [] ).filter( ( s ) => ! s.applied ).length;

	return (
		<div className="bp-cb-ai__actions">
			<div className="bp-cb-ai__actions-head">
				<p className="bp-cb-ai__actions-title">
					{ __( 'Fields to Improve', 'better-payment' ) }
				</p>
				{ /* One pending row needs no "apply all" — the row's own button
				     already is that, and two buttons doing one thing reads as a
				     choice the user has to make. */ }
				{ pending > 1 && (
					<button
						type="button"
						className="bp-cb-ai__actions-all"
						onClick={ onApplyAll }
						disabled={ sending }
					>
						{ sprintf(
							/* translators: %d: number of outstanding suggestions. */
							_n(
								'Apply all (%d)',
								'Apply all (%d)',
								pending,
								'better-payment'
							),
							pending
						) }
					</button>
				) }
			</div>

			{ groups.map( ( group ) => (
				<div className="bp-cb-ai__actions-group" key={ group.key }>
					<p className="bp-cb-ai__actions-group-label">
						{ group.label }
					</p>
					<ul className="bp-cb-ai__fixes">
						{ group.items.map( ( item ) => (
							<li key={ item.index }>
								<span className="bp-cb-ai__fix-label">
									<span className="bp-cb-ai__fix-title">
										{ item.label }
									</span>
									{ item.detail && (
										<span className="bp-cb-ai__fix-detail">
											{ item.detail }
										</span>
									) }
								</span>
								{ item.applied ? (
									<span className="bp-cb-ai__fix-done">
										<span className="dashicons dashicons-yes" />
										{ __( 'Applied', 'better-payment' ) }
									</span>
								) : (
									<button
										type="button"
										className="bp-cb-ai__fix-apply"
										onClick={ () => onApply( item.index ) }
										disabled={ sending }
									>
										{ __( 'Apply', 'better-payment' ) }
									</button>
								) }
							</li>
						) ) }
					</ul>
				</div>
			) ) }
		</div>
	);
}
