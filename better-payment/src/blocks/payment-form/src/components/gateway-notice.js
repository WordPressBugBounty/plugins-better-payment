/**
 * Editor notice for a Payment Form block with no payment method to show.
 *
 * Replaces what used to be an empty block (`return null` in each layout). It is
 * editor-only: on the frontend a form with no gateway renders nothing at all for
 * visitors, and the same explanation for anyone who can change the setting
 * (`BlockManager::render_payment_form_frontend()`).
 */

import { __, sprintf } from '@wordpress/i18n';
import { Placeholder, Button } from '@wordpress/components';
import BlockIcon from './icon';
import { siteGatewayAttributes } from '../helpers/gatewayNotice';

/**
 * The copy for one notice state.
 *
 * Kept separate from the markup so the wording is unit-testable, and so the two
 * reasons cannot drift into one generic sentence — which is the bug this whole
 * component exists to fix, one level up.
 *
 * @param {Object}  state             Result of `gatewayNoticeState()`.
 * @param {boolean} canManageSettings Whether this user may open the settings.
 * @return {{title: string, body: string, cta: string, fix: string}} The notice
 *         copy. `cta` links to Settings and `fix` switches the block's own
 *         toggles on; each is `''` when there is no such button to show.
 */
export function noticeCopy( state, canManageSettings ) {
	if ( state?.reason === 'block' ) {
		const names = ( state.siteGateways || [] )
			.map( ( gateway ) => gateway.label )
			.join( ', ' );

		return {
			title: __( 'No payment method selected', 'better-payment' ),
			body: names
				? sprintf(
						/* translators: %s: comma-separated list of gateway names, e.g. "Stripe, PayPal". */
						__(
							'This form has every payment method switched off. Turn on %s under Payment Settings in the block sidebar.',
							'better-payment'
						),
						names
				  )
				: __(
						'This form has every payment method switched off. Turn one on under Payment Settings in the block sidebar.',
						'better-payment'
				  ),
			// The fix is in the inspector, not in Settings — offering a link out
			// of the editor here would send the user away from the switch they
			// actually need.
			cta: '',
			// …or one click here. A block keeps the gateway switches it copied
			// when it was inserted, so one inserted before the site had a
			// gateway stays off after it gets one; this switches it onto the
			// gateways the site has. Any author can use it: it only changes
			// this block.
			fix: names
				? sprintf(
						/* translators: %s: comma-separated list of gateway names, e.g. "Stripe, PayPal". */
						__( 'Turn on %s', 'better-payment' ),
						names
				  )
				: '',
		};
	}

	return {
		title: __( 'No payment gateway enabled', 'better-payment' ),
		body: canManageSettings
			? __(
					'Enable at least one payment gateway — PayPal, Stripe or Paystack — in Better Payment settings, then reload this page. Until then this form has nothing to show.',
					'better-payment'
			  )
			: __(
					'This form needs a payment gateway — PayPal, Stripe or Paystack — enabled in Better Payment settings. Ask a site administrator to set one up.',
					'better-payment'
			  ),
		cta: canManageSettings
			? __( 'Open Better Payment settings', 'better-payment' )
			: '',
		fix: '',
	};
}

/**
 * The notice itself.
 *
 * @param {Object}   props               Props.
 * @param {Object}   props.state         Result of `gatewayNoticeState()`.
 * @param {Function} props.setAttributes The block's `setAttributes`.
 * @return {JSX.Element} The placeholder shown in place of the form.
 */
export default function GatewayNotice( { state, setAttributes } ) {
	const data = window?.betterPaymentBlockData ?? {};
	const canManageSettings = data.canManageSettings === true;
	const settingsUrl = data.settingsUrl || '';
	const { title, body, cta, fix } = noticeCopy( state, canManageSettings );

	return (
		<Placeholder
			className="better-payment-block__gateway-notice"
			icon={ BlockIcon }
			label={ title }
			instructions={ body }
		>
			{ cta && settingsUrl && (
				<Button variant="primary" href={ settingsUrl } target="_blank">
					{ cta }
				</Button>
			) }
			{ fix && setAttributes && (
				<Button
					variant="primary"
					onClick={ () =>
						setAttributes(
							siteGatewayAttributes( state?.siteGateways )
						)
					}
				>
					{ fix }
				</Button>
			) }
		</Placeholder>
	);
}
