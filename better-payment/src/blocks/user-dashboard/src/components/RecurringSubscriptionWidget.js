import { applyFilters } from '@wordpress/hooks';

export default function RecurringSubscriptionWidget( {
	proEnabled,
	proAssets,
	subscriptions,
	title,
	viewAllLabel,
	noItemsLabel,
} ) {
	if ( ! proEnabled ) {
		return (
			<a
				className="width-100"
				target="_blank"
				href="https://wpdeveloper.com/in/upgrade-better-payment-pro"
				rel="noopener noreferrer"
			>
				<img
					width="100%"
					src={ proAssets.recurringSubscriptionBanner }
					alt="recurring-subscription-pro-banner"
				/>
			</a>
		);
	}

	// The Pro panel comes from Pro (filter `better_payment_user_dashboard_block_recurring_subscriptions`); a Pro that registers none renders nothing.
	const Panel = applyFilters(
		'better_payment_user_dashboard_block_recurring_subscriptions',
		null
	);

	if ( ! Panel ) {
		return null;
	}

	return (
		<Panel
			subscriptions={ subscriptions }
			title={ title }
			viewAllLabel={ viewAllLabel }
			noItemsLabel={ noItemsLabel }
		/>
	);
}
