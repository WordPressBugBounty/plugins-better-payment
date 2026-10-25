import { __ } from '@wordpress/i18n';
import ProCrownIcon from './ProCrownIcon';

/**
 * "How donors pay" on a free install: the page link (what the button does) and
 * the popup Payment Form, which is Pro's, behind an upgrade prompt.
 *
 * Free UI only — the popup form itself ships in Pro, whose builder script draws
 * the real control in this one's place. Shown only while Pro is NOT active, so a
 * Pro owner on an older Pro is never sold what they already own (they see
 * nothing here until Pro is updated).
 *
 * @param {Object}   props
 * @param {Function} props.onRequestUpgrade Opens the upgrade modal.
 */
export default function DonationModeLock( { onRequestUpgrade } ) {
	// A group, not a radiogroup: the Pro option is a button that opens the
	// upgrade prompt, and a radiogroup may only hold radios.
	return (
		<div
			className="bp-cb-mode-lock"
			role="group"
			aria-label={ __( 'How donors pay', 'better-payment' ) }
		>
			<label className="bp-cb-mode-lock__option is-active">
				<input type="radio" checked readOnly name="bp-cb-mode-lock" />
				{ __( 'Link to a Page', 'better-payment' ) }
			</label>
			<button
				type="button"
				className="bp-cb-mode-lock__option bp-cb-mode-lock__option--pro"
				onClick={ () => onRequestUpgrade?.( 'popup_form' ) }
			>
				{ __( 'Payment Form', 'better-payment' ) }
				<ProCrownIcon />
			</button>
		</div>
	);
}
