import { applyFilters } from '@wordpress/hooks';

export default function SubscriptionsTab( {
	proEnabled,
	proAssets,
	subscriptions,
	subPage,
	subMeta,
	setSubPage,
	attributes,
	noItemsLabel,
} ) {
	if ( ! proEnabled ) {
		return (
			<p>
				<a
					className="width-100"
					target="_blank"
					href="https://wpdeveloper.com/in/upgrade-better-payment-pro"
					rel="noopener noreferrer"
				>
					<img
						width="100%"
						src={ proAssets.subscriptionProBanner }
						alt="subscription-pro-banner"
					/>
				</a>
			</p>
		);
	}

	// The Pro panel comes from Pro (filter `better_payment_user_dashboard_block_subscriptions_table`); a Pro that registers none renders nothing.
	const Panel = applyFilters(
		'better_payment_user_dashboard_block_subscriptions_table',
		null
	);

	if ( ! Panel ) {
		return null;
	}

	return (
		<Panel
			subscriptions={ subscriptions }
			subPage={ subPage }
			subMeta={ subMeta }
			setSubPage={ setSubPage }
			attributes={ attributes }
			noItemsLabel={ noItemsLabel }
		/>
	);
}
