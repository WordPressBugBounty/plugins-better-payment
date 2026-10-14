import { applyFilters } from '@wordpress/hooks';

export default function AnalyticsReport( {
	proEnabled,
	proAssets,
	analyticsChartData,
	bp_settings,
} ) {
	if ( ! proEnabled ) {
		return (
			<a
				className="bp-analytics_reports"
				target="_blank"
				href="https://wpdeveloper.com/in/upgrade-better-payment-pro"
				rel="noopener noreferrer"
			>
				<img
					width="100%"
					src={ proAssets.analyticsReportsBanner }
					alt="analytics-reports-pro-banner"
				/>
			</a>
		);
	}

	// The Pro panel comes from Pro (filter `better_payment_user_dashboard_block_analytics_report`); a Pro that registers none renders nothing.
	const Panel = applyFilters(
		'better_payment_user_dashboard_block_analytics_report',
		null
	);

	if ( ! Panel ) {
		return null;
	}

	return (
		<Panel
			analyticsChartData={ analyticsChartData }
			bp_settings={ bp_settings }
		/>
	);
}
