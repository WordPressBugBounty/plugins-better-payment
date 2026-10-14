import { applyFilters } from '@wordpress/hooks';

/**
 * The Subscriptions controls in the block inspector: the table's column toggles
 * (`section="columns"`) and its labels (`section="labels"`).
 *
 * Subscriptions are Pro's, so a free install renders nothing here, and neither does a
 * Pro install while the Subscriptions tab is switched off. The controls come from Pro
 * (filter `better_payment_user_dashboard_block_subscriptions_inspector`); a Pro that
 * registers none renders nothing.
 *
 * @param {Object}   props
 * @param {boolean}  props.proEnabled        `betterPaymentBlockData.proEnabled`.
 * @param {boolean}  props.subscriptionsShow Whether the Subscriptions tab is on.
 * @param {string}   props.section           'columns' or 'labels'.
 * @param {Object}   props.attributes        Block attributes.
 * @param {Function} props.setAttributes     Block attribute setter.
 * @param {Object}   props.components        `PanelBody`, `ToggleControl`, `TextControl`.
 */
export default function SubscriptionsInspector( {
	proEnabled,
	subscriptionsShow,
	section,
	attributes,
	setAttributes,
	components,
} ) {
	if ( ! proEnabled || ! subscriptionsShow ) {
		return null;
	}

	const Panel = applyFilters(
		'better_payment_user_dashboard_block_subscriptions_inspector',
		null
	);

	if ( ! Panel ) {
		return null;
	}

	return (
		<Panel
			section={ section }
			attributes={ attributes }
			setAttributes={ setAttributes }
			components={ components }
		/>
	);
}
