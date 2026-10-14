/**
 * ProgressBar Component
 *
 * Displays the wizard progress with step indicators.
 *
 * Each step renders exactly one status class (`--upcoming` / `--active` /
 * `--completed`) and exactly one icon. The separator chevron is rendered here
 * rather than carried on the step data, so "no arrow after the last step" is a
 * property of the loop instead of a field someone has to remember to omit.
 */

import { Fragment } from '@wordpress/element';
import ArrowIcon from '../svg/ArrowIcon';
import { STEP_STATUS } from '../utils/constants';

/**
 * Pick the icon for a step's status, falling back to the base icon so a step
 * can never render an empty icon slot.
 *
 * @param {Object} step   Step definition.
 * @param {string} status One of STEP_STATUS.
 * @return {*} The icon element to render.
 */
export const stepIcon = ( step, status ) => {
	if ( status === STEP_STATUS.COMPLETED ) {
		return step?.completedIcon ?? step?.icon ?? null;
	}

	if ( status === STEP_STATUS.ACTIVE ) {
		return step?.activeIcon ?? step?.icon ?? null;
	}

	return step?.icon ?? null;
};

export default function ProgressBar( { steps = [] } ) {
	return (
		<div className="bp-wizard__progress">
			{ steps?.map( ( step, index ) => {
				const status = step?.status ?? STEP_STATUS.UPCOMING;
				const icon = stepIcon( step, status );
				const isLast = index === steps.length - 1;

				return (
					<Fragment key={ step?.id ?? index }>
						<div
							className={ `bp-progress-step bp-progress-step--${ status }` }
							aria-current={
								status === STEP_STATUS.ACTIVE
									? 'step'
									: undefined
							}
						>
							{ icon && (
								<div className="bp-progress-step__icon">
									{ icon }
								</div>
							) }
							<span className="bp-progress-step__label">
								{ step?.label }
							</span>
						</div>
						{ ! isLast && (
							<div
								className={ `bp-progress-step__arrow${
									status === STEP_STATUS.COMPLETED
										? ' bp-progress-step__arrow--done'
										: ''
								}` }
							>
								<ArrowIcon />
							</div>
						) }
					</Fragment>
				);
			} ) }
		</div>
	);
}
