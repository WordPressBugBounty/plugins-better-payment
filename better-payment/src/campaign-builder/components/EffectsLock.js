import { __ } from '@wordpress/i18n';
import ProCrownIcon from './ProCrownIcon';

/**
 * The locked "Effects" row at the end of a free install's Advanced tab.
 *
 * Free upsell only — Lite has no gradient, hover effect, transition or custom
 * CSS code. Pro adds those controls to every element's schema (its own
 * `Campaign\StyleEffects`), and RightSidebar draws this only while
 * `proEnabled` is false, so a Pro user never sees it.
 *
 * @param {Object}   props
 * @param {Function} props.onRequestUpgrade Opens the upgrade modal for a type.
 */
export default function EffectsLock( { onRequestUpgrade } ) {
	return (
		<button
			type="button"
			className="bp-cb-effects-lock"
			onClick={ () =>
				typeof onRequestUpgrade === 'function' &&
				onRequestUpgrade( 'style_effects' )
			}
		>
			<span className="bp-cb-effects-lock__title">
				{ __( 'Effects', 'better-payment' ) }
				<ProCrownIcon />
			</span>
			<span className="bp-cb-effects-lock__text">
				{ __(
					'Gradients, hover effects, transitions and custom CSS.',
					'better-payment'
				) }
			</span>
		</button>
	);
}
