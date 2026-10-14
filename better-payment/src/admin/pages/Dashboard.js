import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ChangeLog from '../components/dashboard/ChangeLog';
import UpgradePro from '../components/dashboard/UpgradePro';
import Hero from '../components/dashboard/Hero';
import RecentTransactions from '../components/dashboard/RecentTransactions';
import DashboardAnalytics from '../components/dashboard/DashboardAnalytics';
import { applyFilters } from '@wordpress/hooks';
import FooterCards from '../components/settings/FooterCards';

/**
 * The Dashboard fetches nothing itself — its two data-driven children (Hero and
 * RecentTransactions) own their requests and render their own skeletons while
 * those are in flight. So this renders its shell immediately: the page chrome,
 * headings, and the static cards (ChangeLog / UpgradePro / FooterCards) have no
 * data to wait for and must never be held back by one.
 *
 * There used to be a page-level `loading` flag here, but it was bound to nothing
 * — a hardcoded `setTimeout(…, 200)` that blanked the whole layout behind a
 * spinner, then unblocked and let the children start fetching. That produced
 * spinner → layout pop-in → skeleton → content on every visit. Do not reintroduce
 * a page-level loading gate unless this component actually fetches something.
 */
const Dashboard = ( { onPageChange } ) => {
	// need to change page title
	useEffect( () => {
		document.title = __( 'Dashboard - Better Payment', 'better-payment' );
	}, [] );

	return (
		<div className="tab-content bp-tab_active" id="dashboard">
			<Hero />
			<div className="section  bp-recent_transactions-section">
				<div className="bp-container">
					<div className="bp-row justify-content-center-active_992">
						<div className="bp-col bp-custome_col-9 bp-col_100-active_600">
							<RecentTransactions onPageChange={ onPageChange } />
							<DashboardAnalytics onPageChange={ onPageChange } />
						</div>
						<div className="bp-col bp-custome_col-3  bp-col_100-active_600 bp-row_active-992">
							{ applyFilters(
								'better_payment_dashboard_license_card',
								null,
								onPageChange
							) }
							<ChangeLog />
							{ applyFilters(
								'better_payment_dashboard_upgrade_pro',
								<UpgradePro />
							) }
						</div>
					</div>
					<FooterCards />
				</div>
			</div>
		</div>
	);
};

export default Dashboard;
