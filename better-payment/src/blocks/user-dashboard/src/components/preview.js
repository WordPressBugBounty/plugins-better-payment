/**
 * Dashboard Preview Component
 * Renders the actual user dashboard matching layout-1.php structure
 *
 * @package Better_Payment
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import AnalyticsReport from './AnalyticsReport';
import RecurringSubscriptionWidget from './RecurringSubscriptionWidget';
import SplitSubscriptionWidget from './SplitSubscriptionWidget';
import SubscriptionsTab from './SubscriptionsTab';

const DashboardSVGIcons = {
    dashboard: () => (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clipPath="url(#clip0_400_549)">
                <path d="M4 5C4 4.73478 4.10536 4.48043 4.29289 4.29289C4.48043 4.10536 4.73478 4 5 4H9C9.26522 4 9.51957 4.10536 9.70711 4.29289C9.89464 4.48043 10 4.73478 10 5V9C10 9.26522 9.89464 9.51957 9.70711 9.70711C9.51957 9.89464 9.26522 10 9 10H5C4.73478 10 4.48043 9.89464 4.29289 9.70711C4.10536 9.51957 4 9.26522 4 9V5Z" stroke="#48506D" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M4 15C4 14.7348 4.10536 14.4804 4.29289 14.2929C4.48043 14.1054 4.73478 14 5 14H9C9.26522 14 9.51957 14.1054 9.70711 14.2929C9.89464 14.4804 10 14.7348 10 15V19C10 19.2652 9.89464 19.5196 9.70711 19.7071C9.51957 19.8946 9.26522 20 9 20H5C4.73478 20 4.48043 19.8946 4.29289 19.7071C4.10536 19.5196 4 19.2652 4 19V15Z" stroke="#48506D" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M14 15C14 14.7348 14.1054 14.4804 14.2929 14.2929C14.4804 14.1054 14.7348 14 15 14H19C19.2652 14 19.5196 14.1054 19.7071 14.2929C19.8946 14.4804 20 14.7348 20 15V19C20 19.2652 19.8946 19.5196 19.7071 19.7071C19.5196 19.8946 19.2652 20 19 20H15C14.7348 20 14.4804 19.8946 14.2929 19.7071C14.1054 19.5196 14 19.2652 14 19V15Z" stroke="#48506D" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M14 7H20" stroke="#48506D" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M17 4V10" stroke="#48506D" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </g>
            <defs>
                <clipPath id="clip0_400_549">
                    <rect width="24" height="24" fill="white" />
                </clipPath>
            </defs>
        </svg>
    ),
    transactions: () => (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fillRule="evenodd" clipRule="evenodd" d="M7.09878 1.24992C7.14683 1.24994 7.19559 1.24996 7.24508 1.24996H16.7551C16.8045 1.24996 16.8533 1.24994 16.9014 1.24992C17.9181 1.24947 18.6178 1.24917 19.2072 1.45422C20.3201 1.84149 21.1842 2.73714 21.5547 3.86546L20.8421 4.09942L21.5547 3.86546C21.7507 4.46258 21.7505 5.17242 21.7501 6.22642C21.7501 6.27359 21.7501 6.32145 21.7501 6.37002V20.3742C21.7501 21.8394 20.0231 22.7117 18.8857 21.6709C18.8062 21.5981 18.694 21.5981 18.6145 21.6709L18.1314 22.1129C17.2032 22.9623 15.7969 22.9623 14.8688 22.1129C14.5138 21.7881 13.9864 21.7881 13.6314 22.1129C12.7032 22.9623 11.2969 22.9623 10.3688 22.1129C10.0138 21.7881 9.48637 21.7881 9.13138 22.1129C8.20319 22.9623 6.79694 22.9623 5.86875 22.1129L5.38566 21.6709C5.30618 21.5981 5.19395 21.5981 5.11448 21.6709C3.97705 22.7117 2.25007 21.8394 2.25007 20.3742V6.37002C2.25007 6.32145 2.25005 6.2736 2.25003 6.22643C2.24965 5.17242 2.24939 4.46259 2.44545 3.86546C2.81591 2.73714 3.68002 1.84149 4.79298 1.45422C5.3823 1.24917 6.08203 1.24947 7.09878 1.24992ZM7.24508 2.74996C6.024 2.74996 5.6034 2.76045 5.28593 2.87091C4.62655 3.10035 4.09919 3.63716 3.8706 4.33338C3.75951 4.67171 3.75007 5.11784 3.75007 6.37002V20.3742C3.75007 20.4932 3.80999 20.566 3.88517 20.6007C3.92434 20.6189 3.96264 20.6235 3.99456 20.6193C4.0227 20.6155 4.05911 20.6034 4.10185 20.5643C4.75453 19.967 5.74561 19.967 6.39828 20.5643L6.88138 21.0063C7.23637 21.3312 7.76377 21.3312 8.11875 21.0063C9.04694 20.157 10.4532 20.157 11.3814 21.0063C11.7364 21.3312 12.2638 21.3312 12.6188 21.0063C13.5469 20.157 14.9532 20.157 15.8814 21.0063C16.2364 21.3312 16.7638 21.3312 17.1188 21.0063L17.6019 20.5643C18.2545 19.967 19.2456 19.967 19.8983 20.5643C19.941 20.6034 19.9774 20.6155 20.0056 20.6193C20.0375 20.6235 20.0758 20.6189 20.115 20.6007C20.1901 20.566 20.2501 20.4932 20.2501 20.3742V6.37002C20.2501 5.11784 20.2406 4.67171 20.1295 4.33338C19.9009 3.63716 19.3736 3.10035 18.7142 2.87091C18.3967 2.76045 17.9761 2.74996 16.7551 2.74996H7.24508ZM6.25007 7.49996C6.25007 7.08575 6.58585 6.74996 7.00007 6.74996H7.50007C7.91428 6.74996 8.25007 7.08575 8.25007 7.49996C8.25007 7.91417 7.91428 8.24996 7.50007 8.24996H7.00007C6.58585 8.24996 6.25007 7.91417 6.25007 7.49996ZM9.75007 7.49996C9.75007 7.08575 10.0859 6.74996 10.5001 6.74996H17.0001C17.4143 6.74996 17.7501 7.08575 17.7501 7.49996C17.7501 7.91417 17.4143 8.24996 17.0001 8.24996H10.5001C10.0859 8.24996 9.75007 7.91417 9.75007 7.49996ZM6.25007 11C6.25007 10.5857 6.58585 10.25 7.00007 10.25H7.50007C7.91428 10.25 8.25007 10.5857 8.25007 11C8.25007 11.4142 7.91428 11.75 7.50007 11.75H7.00007C6.58585 11.75 6.25007 11.4142 6.25007 11ZM9.75007 11C9.75007 10.5857 10.0859 10.25 10.5001 10.25H17.0001C17.4143 10.25 17.7501 10.5857 17.7501 11C17.7501 11.4142 17.4143 11.75 17.0001 11.75H10.5001C10.0859 11.75 9.75007 11.4142 9.75007 11ZM6.25007 14.5C6.25007 14.0857 6.58585 13.75 7.00007 13.75H7.50007C7.91428 13.75 8.25007 14.0857 8.25007 14.5C8.25007 14.9142 7.91428 15.25 7.50007 15.25H7.00007C6.58585 15.25 6.25007 14.9142 6.25007 14.5ZM9.75007 14.5C9.75007 14.0857 10.0859 13.75 10.5001 13.75H17.0001C17.4143 13.75 17.7501 14.0857 17.7501 14.5C17.7501 14.9142 17.4143 15.25 17.0001 15.25H10.5001C10.0859 15.25 9.75007 14.9142 9.75007 14.5Z" fill="#667085" />
        </svg>
    ),
    subscriptions: () => (
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clipPath="url(#clip0_400_558)">
                <path d="M3 8V12.172C3.00011 12.7024 3.2109 13.211 3.586 13.586L9.296 19.296C9.74795 19.7479 10.3609 20.0017 11 20.0017C11.6391 20.0017 12.252 19.7479 12.704 19.296L16.296 15.704C16.7479 15.252 17.0017 14.6391 17.0017 14C17.0017 13.3609 16.7479 12.748 16.296 12.296L10.586 6.586C10.211 6.2109 9.70239 6.00011 9.172 6H5C4.46957 6 3.96086 6.21071 3.58579 6.58579C3.21071 6.96086 3 7.46957 3 8Z" stroke="#2B2748" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M18 19L19.592 17.408C20.4958 16.5041 21.0035 15.2782 21.0035 14C21.0035 12.7218 20.4958 11.4959 19.592 10.592L15 6" stroke="#2B2748" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M6.99828 10H6.98828" stroke="#2B2748" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </g>
            <defs>
                <clipPath id="clip0_400_558">
                    <rect width="24" height="24" fill="white" />
                </clipPath>
            </defs>
        </svg>
    ),
};

/**
 * Map transaction status to display label and background color.
 * Mirrors PHP Helper::get_type_by_transaction_status($status, 'v2') and get_color_by_transaction_status($status, 'v2')
 */
function getBpStatusInfo( status ) {
	const s = status || '';
	if ( [ 'paid', 'Completed', 'completed', 'success' ].includes( s ) ) {
		return { label: 'Completed', color: '#0ECA86' };
	}
	if ( s === 'refunded' ) {
		return { label: 'Refunded', color: '#FF0202' };
	}
	// pending, Pending, unpaid, incomplete, Incomplete, empty → Incomplete
	return { label: 'Incomplete', color: '#FFDA15' };
}

/**
 * Format date to match WordPress site date-only format: 'F j, Y'
 * Example: "April 9, 2026"
 */
function formatBpDateOnly( dateStr ) {
	if ( ! dateStr ) return 'N/A';
	const d = new Date( dateStr );
	const month = d.toLocaleString( 'en-US', { month: 'long' } );
	const day   = d.getDate();
	const year  = d.getFullYear();
	return `${ month } ${ day }, ${ year }`;
}

/**
 * Format date to match WordPress site date/time format: 'F j, Y g:i a'
 * Example: "April 9, 2026 2:25 pm"
 */
function formatBpDate( dateStr ) {
	if ( ! dateStr ) return 'N/A';
	const d = new Date( dateStr );
	const month = d.toLocaleString( 'en-US', { month: 'long' } );
	const day   = d.getDate();
	const year  = d.getFullYear();
	let hours   = d.getHours();
	const mins  = String( d.getMinutes() ).padStart( 2, '0' );
	const ampm  = hours >= 12 ? 'pm' : 'am';
	hours = hours % 12 || 12;
	return `${ month } ${ day }, ${ year } ${ hours }:${ mins } ${ ampm }`;
}

export default function DashboardPreview({ attributes }) {
    const {
        sidebarShow,
        avatarShow,
        usernameShow,
        dashboardShow,
        transactionsShow,
        subscriptionsShow,
        headerShow,
        dashboardLabel,
        transactionLabel,
        subscriptionLabel,
        refreshStatsLabel,
        noItemsLabel,
        dashboardTransactionSummaryShow,
        dashboardAnalyticsReportShow,
        dashboardRecentTransactionsShow,
        dashboardRecurringSubscriptionShow,
        dashboardSplitSubscriptionShow,
        transactionTableNameShow,
        transactionTableEmailAddressShow,
        transactionTableAmountShow,
        transactionTablePaymentTypeShow,
        transactionTableTransactionIdShow,
        transactionTableSourceShow,
        transactionTableStatusShow,
        transactionTableDateShow,
        dashboardTotalAmountLabel,
        dashboardCompletedAmountLabel,
        dashboardIncompleteAmountLabel,
        dashboardRefundedAmountLabel,
        dashboardViewAllLabel,
        dashboardAnalyticsReportsLabel,
        dashboardRecentTransactionsLabel,
        transactionTableNameLabel,
        transactionTableEmailAddressLabel,
        transactionTableAmountLabel,
        transactionTablePaymentTypeLabel,
        transactionTableTransactionIdLabel,
        transactionTableSourceLabel,
        transactionTableStatusLabel,
        transactionTableDateLabel,
    } = attributes;

    const blockData = window.betterPaymentBlockData || {};
    const currentUser = blockData.currentUser || { user_email: '', user_login: '', user_avatar_url: '' };
    const analytics = blockData.userAnalytics || {};
    const assetUrl = blockData.assetUrl || blockData.assetsUrl || '';
    const proAssets = blockData.proAssets || {};
    const proEnabled = blockData.proEnabled === true;
    const analyticsChartData = blockData.analyticsChartData || null;
    const restUrl  = blockData.restUrl  || '';
    const restNonce = blockData.restNonce || '';
    const initMeta = blockData.userTransactionsMeta || { total: 0, pages: 1, perPage: 20 };

    // Paginated transaction state — initialized from block data (page 1)
    const [txRows, setTxRows] = useState(blockData.userTransactions || []);
    const [txPage, setTxPage] = useState(1);
    const [txMeta, setTxMeta] = useState(initMeta);
    const [txLoading, setTxLoading] = useState(false);
    const txMounted = useRef(false);

    const fetchTxPage = useCallback((page) => {
        if ( ! restUrl ) return;
        setTxLoading(true);
        fetch(`${restUrl}?tab=transactions&page=${page}&per_page=${txMeta.perPage || 20}`, {
            headers: { 'X-WP-Nonce': restNonce },
        })
        .then(r => r.json())
        .then(data => {
            setTxRows(data.transactions || []);
            setTxPage(data.page || page);
            setTxMeta({ total: data.total, pages: data.pages, perPage: data.per_page });
        })
        .finally(() => setTxLoading(false));
    }, [restUrl, restNonce, txMeta.perPage]);

    useEffect(() => {
        if ( ! txMounted.current ) {
            txMounted.current = true;
            return;
        }
        fetchTxPage(txPage);
    }, [txPage]); // eslint-disable-line react-hooks/exhaustive-deps

    // Paginated subscription state — initialized from block data (page 1)
    const initSubMeta = blockData.userSubscriptionsMeta || { total: 0, pages: 1, perPage: 20 };
    const [subRows, setSubRows] = useState(blockData.userSubscriptions || []);
    const [subPage, setSubPage] = useState(1);
    const [subMeta, setSubMeta] = useState(initSubMeta);
    const subMounted = useRef(false);

    const fetchSubPage = useCallback((page) => {
        if ( ! restUrl ) return;
        fetch(`${restUrl}?tab=subscriptions&page=${page}&per_page=${subMeta.perPage || 20}`, {
            headers: { 'X-WP-Nonce': restNonce },
        })
        .then(r => r.json())
        .then(data => {
            setSubRows(data.transactions || []);
            setSubPage(data.page || page);
            setSubMeta({ total: data.total, pages: data.pages, perPage: data.per_page });
        });
    }, [restUrl, restNonce, subMeta.perPage]);

    useEffect(() => {
        if ( ! subMounted.current ) {
            subMounted.current = true;
            return;
        }
        fetchSubPage(subPage);
    }, [subPage]); // eslint-disable-line react-hooks/exhaustive-deps

    // Subscription data for the Dashboard tab summary widgets (uses first page from block data)
    const allSubscriptions       = blockData.userSubscriptions || [];
    const recurringSubscriptions = allSubscriptions.filter(tx => !parseInt(tx.is_payment_split_payment || 0, 10));
    const splitSubscriptions     = allSubscriptions.filter(tx =>  !!parseInt(tx.is_payment_split_payment || 0, 10));

    const [activeTab, setActiveTab] = useState(
        dashboardShow ? 'dashboard' : transactionsShow ? 'transactions' : subscriptionsShow ? 'subscriptions' : 'dashboard'
    );

    return (
        <div className="better-payment">
            <div className="better-payment-user-dashboard-container bp--section-wrapper flex gap-6 min-h-full">
                <div className="bp-overlay"></div>

                {/* Sidebar */}
                {sidebarShow && (
                    <div className="better-payment-user-dashboard-sidebar user-dashboard-sidebar bp--sidebar-wrapper bp-hidden-xs">
                        <div className="bp--author">
                            {avatarShow && (
                                <div>
                                    <img
                                        src={currentUser.user_avatar_url || currentUser.avatar || 'https://www.gravatar.com/avatar/?d=mm'}
                                        alt={currentUser.user_login || currentUser.login || 'user'}
                                        style={{ width: '32px', height: '32px', minWidth: '32px', borderRadius: '50%', objectFit: 'cover', flexShrink: 0, display: 'block' }}
                                    />
                                </div>
                            )}
                            {usernameShow && (
                                <h5 className="user-name">
                                    {currentUser.user_login || currentUser.login || 'Admin'}
                                </h5>
                            )}
                        </div>

                        <div className="bp--sidebar-nav-list">
                            {dashboardShow && (
                                <div
                                    className={`bp--sidebar-nav dashboard-tab ${activeTab === 'dashboard' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('dashboard')}
                                >
                                    <span className="bp--nav-icon">
                                        <DashboardSVGIcons.dashboard />
                                    </span>
                                    <span className="bp--nav-text">{dashboardLabel || __('Dashboard', 'better-payment')}</span>
                                </div>
                            )}

                            {transactionsShow && (
                                <div
                                    className={`bp--sidebar-nav transactions-tab ${!dashboardShow && activeTab === 'transactions' ? 'active' : activeTab === 'transactions' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('transactions')}
                                >
                                    <span className="bp--nav-icon">
                                        <DashboardSVGIcons.transactions />
                                    </span>
                                    <span className="bp--nav-text">{transactionLabel || __('Transactions', 'better-payment')}</span>
                                </div>
                            )}

                            {subscriptionsShow && (
                                <div
                                    className={`bp--sidebar-nav subscriptions-tab ${(!dashboardShow && !transactionsShow) || activeTab === 'subscriptions' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('subscriptions')}
                                >
                                    <span className="bp--nav-icon">
                                        <DashboardSVGIcons.subscriptions />
                                    </span>
                                    <span className="bp--nav-text">{subscriptionLabel || __('Subscriptions', 'better-payment')}</span>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Main Content */}
                <div className="bp--db-main-wrapper">
                    {/* Dashboard Tab */}
                    {dashboardShow && activeTab === 'dashboard' && (
                        <div className="bp--tab-conetnt-wrapper dashboard-tab-wrapper">
                            {headerShow && (
                                <div className="better-payment-user-dashboard-header bp--db-header bp-dashboard-header flex items-center justify-center">
                                    {sidebarShow && (
                                        <div className="bp-dashboard-hamburger padding-0 bp-visible-xs">
                                            <svg fill="none" viewBox="0 0 24 24" width="25" xmlns="http://www.w3.org/2000/svg">
                                                <path clipRule="evenodd" d="m3 8c0-.55228.44772-1 1-1h16c.5523 0 1 .44772 1 1s-.4477 1-1 1h-16c-.55228 0-1-.44772-1-1zm0 4c0-.5523.44772-1 1-1h16c.5523 0 1 .4477 1 1s-.4477 1-1 1h-16c-.55228 0-1-.4477-1-1zm0 4c0-.5523.44772-1 1-1h8c.5523 0 1 .4477 1 1s-.4477 1-1 1h-8c-.55228 0-1-.4477-1-1z" fill="rgb(0,0,0)" fillRule="evenodd" />
                                            </svg>
                                        </div>
                                    )}
                                    <h2>{dashboardLabel || __('Dashboard', 'better-payment')}</h2>
                                    <button className="primary-btn bp-hidden-xs">
                                        <a href="#" onClick={(e) => { e.preventDefault(); window.location.reload(); }}>
                                            {refreshStatsLabel || __('Refresh Stats', 'better-payment')}
                                        </a>
                                    </button>
                                </div>
                            )}

                            <div className="bp-dashboard_wrapper">
                                {/* Transaction Summary */}
                                {dashboardTransactionSummaryShow && (
                                    <div className="bp-amount_wrapper">
                                        <div className="bp-row">
                                            <div className="bp-col_4 bp-col">
                                                <div className="bp-amount">
                                                    <h4 className="mb-0 pb-0">
                                                        {dashboardTotalAmountLabel || __('Total Amount', 'better-payment')}
                                                    </h4>
                                                    <div className="bp-amount_price mb-0 pb-0">
                                                        <span>${parseFloat(analytics.total_transactions_amount || 0).toFixed(2)}</span>
                                                    </div>
                                                    <div className="bp-transaction">
                                                        <span className="bp-transaction_title">No of Transaction:</span>
                                                        <span className="bp-transaction_number"> {analytics.total_transactions_count || 0}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bp-col_4 bp-col">
                                                <div className="bp-amount">
                                                    <h4 className="mb-0 pb-0">
                                                        {dashboardCompletedAmountLabel || __('Completed Amount', 'better-payment')}
                                                    </h4>
                                                    <div className="bp-amount_price mb-0 pb-0">
                                                        <span>${parseFloat(analytics.completed_transactions_amount || 0).toFixed(2)}</span>
                                                    </div>
                                                    <div className="bp-transaction">
                                                        <span className="bp-transaction_title">No of Transaction:</span>
                                                        <span className="bp-transaction_number"> {analytics.completed_transactions_count || 0}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bp-col_4 bp-col">
                                                <div className="bp-amount">
                                                    <h4 className="mb-0 pb-0">
                                                        {dashboardIncompleteAmountLabel || __('Incomplete Amount', 'better-payment')}
                                                    </h4>
                                                    <div className="bp-amount_price mb-0 pb-0">
                                                        <span>${parseFloat(analytics.incomplete_transactions_amount || 0).toFixed(2)}</span>
                                                    </div>
                                                    <div className="bp-transaction">
                                                        <span className="bp-transaction_title">No of Transaction:</span>
                                                        <span className="bp-transaction_number"> {analytics.incomplete_transactions_count || 0}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="bp-col_4 bp-col">
                                                <div className="bp-amount">
                                                    <h4 className="mb-0 pb-0">
                                                        {dashboardRefundedAmountLabel || __('Refunded Amount', 'better-payment')}
                                                    </h4>
                                                    <div className="bp-amount_price mb-0 pb-0">
                                                        <span>${parseFloat(analytics.refunded_transactions_amount || 0).toFixed(2)}</span>
                                                    </div>
                                                    <div className="bp-transaction">
                                                        <span className="bp-transaction_title">No of Transaction:</span>
                                                        <span className="bp-transaction_number"> {analytics.refunded_transactions_count || 0}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Analytics chart (75%) + Recent Transactions (25%) — siblings in one row, matching template-dashboard-tab.php */}
                                {(dashboardAnalyticsReportShow || dashboardRecentTransactionsShow) && (
                                    <div className="bp-analytics_chart-wrapper">
                                        <div className="bp-row">
                                            {dashboardAnalyticsReportShow && (
                                                <div className="bp-col_10 bp-col">
                                                    <AnalyticsReport
                                                        proEnabled={proEnabled}
                                                        proAssets={proAssets}
                                                        analyticsChartData={analyticsChartData}
                                                        bp_settings={attributes}
                                                    />
                                                </div>
                                            )}

                                            {dashboardRecentTransactionsShow && (
                                                <div className="bp-col_4 bp-col">
                                                    <div className="bp-recent_box">
                                                        <div className="bp-recent_header flex justify-between items-center">
                                                            <div className="flex gap-2 items-center">
                                                                <span>
                                                                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M13.9586 2.16663H6.03361C5.06788 2.16663 4.58502 2.16663 4.19558 2.30213C3.45711 2.55909 2.87733 3.15595 2.62772 3.91618C2.49609 4.31708 2.49609 4.81417 2.49609 5.80834V17.4785C2.49609 18.1937 3.31692 18.5731 3.83617 18.098C4.14124 17.8188 4.60095 17.8188 4.90601 18.098L5.30859 18.4664C5.84325 18.9556 6.64894 18.9556 7.18359 18.4664C7.71825 17.9771 8.52394 17.9771 9.05859 18.4664C9.59325 18.9556 10.3989 18.9556 10.9336 18.4664C11.4682 17.9771 12.2739 17.9771 12.8086 18.4664C13.3432 18.9556 14.1489 18.9556 14.6836 18.4664L15.0862 18.098C15.3912 17.8188 15.8509 17.8188 16.156 18.098C16.6753 18.5731 17.4961 18.1937 17.4961 17.4785V5.80834C17.4961 4.81417 17.4961 4.31708 17.3645 3.91618C17.1149 3.15595 16.5351 2.55909 15.7966 2.30213C15.4072 2.16663 14.9243 2.16663 13.9586 2.16663Z" stroke="#475467" strokeWidth="1.25" />
                                                                        <path d="M8.74609 9.66663L14.1628 9.66663" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                        <path d="M5.8291 9.66663H6.24577" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                        <path d="M5.8291 6.75H6.24577" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                        <path d="M5.8291 12.5834H6.24577" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                        <path d="M8.74609 6.75H14.1628" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                        <path d="M8.74609 12.5834H14.1628" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" />
                                                                    </svg>
                                                                </span>
                                                                <h3 className="bp-recent_header-title">
                                                                    {dashboardRecentTransactionsLabel || __('Recent Transactions', 'better-payment')}
                                                                </h3>
                                                            </div>
                                                            <a href="#" className="bp-view_all-btn is-hidden">
                                                                {dashboardViewAllLabel || __('View All', 'better-payment')}
                                                            </a>
                                                        </div>

                                                        <div className="bp-recent_body">
                                                            <div className="bp-th flex items-center">
                                                                <div className="w-195">
                                                                    <h5>Transaction</h5>
                                                                </div>
                                                                <div className="w-80">
                                                                    <h5>Amount</h5>
                                                                </div>
                                                            </div>

                                                            <div className="bp-product_scroll">
                                                                {txRows && txRows.length > 0 ? (
                                                                    txRows.slice(0, 5).map((txn, index) => (
                                                                        <div key={index} className="bp-td flex items-center">
                                                                            <div className="td-product flex items-start w-195 flex-col">
                                                                                <div className="td-product_logo">
                                                                                    <img
                                                                                        src={txn.source === 'paypal' ? assetUrl + '/img/paypal.png' : assetUrl + '/img/' + txn.source + '.svg'}
                                                                                        title={txn.source.toUpperCase()}
                                                                                        alt={txn.source}
                                                                                    />
                                                                                </div>
                                                                                <div className="td-product_info">
                                                                                    <h4 className="td-product_name">
                                                                                        {formatBpDateOnly( txn.payment_date )}
                                                                                    </h4>
                                                                                </div>
                                                                            </div>
                                                                            <div className="td-product_price w-80">
                                                                                <span>{txn.currency} {parseFloat(txn.amount).toFixed(2)}</span>
                                                                            </div>
                                                                        </div>
                                                                    ))
                                                                ) : (
                                                                    <div>
                                                                        <p className="bp-no_subscription-text">{noItemsLabel || __('No records found!', 'better-payment')}</p>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Subscriptions Section (Pro only) — both recurring and split in one wrapper, matching template-dashboard-tab.php */}
                                {(dashboardRecurringSubscriptionShow || dashboardSplitSubscriptionShow) && (
                                    <div className="bp-subscription_wrapper">
                                        <div className="bp-row">
                                            {dashboardRecurringSubscriptionShow && (
                                                <div className="bp-col_7 bp-col">
                                                    <RecurringSubscriptionWidget
                                                        proEnabled={proEnabled}
                                                        proAssets={proAssets}
                                                        subscriptions={recurringSubscriptions}
                                                        title={attributes.dashboardRecurringSubscriptionsLabel || 'Recurring Subscriptions'}
                                                        viewAllLabel={attributes.dashboardViewAllLabel}
                                                        noItemsLabel={attributes.noItemsLabel}
                                                    />
                                                </div>
                                            )}

                                            {dashboardSplitSubscriptionShow && (
                                                <div className="bp-col_7 bp-col">
                                                    <SplitSubscriptionWidget
                                                        proEnabled={proEnabled}
                                                        proAssets={proAssets}
                                                        subscriptions={splitSubscriptions}
                                                        title={attributes.dashboardSplitSubscriptionsLabel || 'Split Subscriptions'}
                                                        viewAllLabel={attributes.dashboardViewAllLabel}
                                                        noItemsLabel={attributes.noItemsLabel}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Transactions Tab */}
                    {transactionsShow && activeTab === 'transactions' && (
                        <div className="bp--tab-conetnt-wrapper transactions-tab-wrapper">
                            {headerShow && (
                                <div className="better-payment-user-dashboard-header bp--db-header bp-dashboard-header flex items-center justify-center">
                                    {sidebarShow && (
                                        <div className="bp-dashboard-hamburger padding-0 bp-visible-xs">
                                            <svg fill="none" viewBox="0 0 24 24" width="25" xmlns="http://www.w3.org/2000/svg">
                                                <path clipRule="evenodd" d="m3 8c0-.55228.44772-1 1-1h16c.5523 0 1 .44772 1 1s-.4477 1-1 1h-16c-.55228 0-1-.44772-1-1zm0 4c0-.5523.44772-1 1-1h16c.5523 0 1 .4477 1 1s-.4477 1-1 1h-16c-.55228 0-1-.4477-1-1zm0 4c0-.5523.44772-1 1-1h8c.5523 0 1 .4477 1 1s-.4477 1-1 1h-8c-.55228 0-1-.4477-1-1z" fill="rgb(0,0,0)" fillRule="evenodd" />
                                            </svg>
                                        </div>
                                    )}
                                    <h2>{transactionLabel || __('Transactions', 'better-payment')}</h2>
                                </div>
                            )}

                            <div className="bp--body-content">
                                <div className="bp--table-main-wrapper">
                                    <div className="bp--table-wrapper transaction better-payment-user-dashboard-table">
                                        <div className="better-payment-user-dashboard-table-header bp--table-header bp-min_width-1300 flex justify-between gap-1">
                                            {transactionTableNameShow && (
                                                <div className="th user-name details">
                                                    <h5>{transactionTableNameLabel || __('Name', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableEmailAddressShow && (
                                                <div className="th details email">
                                                    <h5>{transactionTableEmailAddressLabel || __('Email Address', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableAmountShow && (
                                                <div className="th details amount">
                                                    <h5>{transactionTableAmountLabel || __('Amount', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTablePaymentTypeShow && (
                                                <div className="th details">
                                                    <h5>{transactionTablePaymentTypeLabel || __('Payment Type', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableTransactionIdShow && (
                                                <div className="th details transaction">
                                                    <h5>{transactionTableTransactionIdLabel || __('Transaction ID', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableSourceShow && (
                                                <div className="th details flex justify-center">
                                                    <h5>{transactionTableSourceLabel || __('Source', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableStatusShow && (
                                                <div className="th details flex justify-center">
                                                    <h5>{transactionTableStatusLabel || __('Status', 'better-payment')}</h5>
                                                </div>
                                            )}
                                            {transactionTableDateShow && (
                                                <div className="th details flex justify-center">
                                                    <h5>{transactionTableDateLabel || __('Date', 'better-payment')}</h5>
                                                </div>
                                            )}
                                        </div>

                                        <div style={{ position: 'relative', opacity: txLoading ? 0.5 : 1 }}>
                                        {txRows && txRows.length > 0 ? (
                                            txRows.map((txn, index) => {
                                                // customer_name and customer_email are pre-computed by PHP (BlockManager)
                                                // and by the REST API (UserAPI) — use them directly.
                                                const customerName  = txn.customer_name  || '';
                                                const customerEmail = txn.customer_email || '';
                                                const paymentType   = txn.is_subscription || (() => {
                                                    const ffi = txn.form_fields_info || {};
                                                    return ffi.subscription_id ? 'Subscription' : 'One Time';
                                                })();
                                                const { label: statusLabel, color: statusColor } = getBpStatusInfo( txn.status );

                                                return (
                                                    <div key={index} className="better-payment-user-dashboard-table-body bp--table-body bp-min_width-1300 flex items-center justify-between gap-1">
                                                        {transactionTableNameShow && (
                                                            <div className="td details user-name flex items-center gap-3">
                                                                <p>{customerName || 'N/A'}</p>
                                                            </div>
                                                        )}
                                                        {transactionTableEmailAddressShow && (
                                                            <div className="td details email flex items-center gap-3">
                                                                <p>
                                                                    <span id={`bp_email_copy_clipboard_input_${index + 1}`}>
                                                                        {customerEmail || 'N/A'}
                                                                    </span>
                                                                    <span
                                                                        id={`bp_email_copy_clipboard_${index + 1}`}
                                                                        className="bp-icon bp-copy-square bp-email-copy-clipboard"
                                                                        title="Copy"
                                                                        data-bp_txn_counter={index + 1}
                                                                    />
                                                                </p>
                                                            </div>
                                                        )}
                                                        {transactionTableAmountShow && (
                                                            <div className="td details amount flex items-center gap-3">
                                                                <p>{txn.currency} {parseFloat(txn.amount).toFixed(2)}</p>
                                                            </div>
                                                        )}
                                                        {transactionTablePaymentTypeShow && (
                                                            <div className="td details flex items-center gap-3">
                                                                <p>{paymentType}</p>
                                                            </div>
                                                        )}
                                                        {transactionTableTransactionIdShow && (
                                                            <div className="td details transaction flex items-center gap-3">
                                                                {txn.transaction_id ? (
                                                                    <p>
                                                                        <span id={`bp_copy_clipboard_input_${index + 1}`}>
                                                                            {txn.transaction_id}
                                                                        </span>
                                                                        <span
                                                                            id={`bp_copy_clipboard_${index + 1}`}
                                                                            className="bp-icon bp-copy-square bp-copy-clipboard"
                                                                            title="Copy"
                                                                            data-bp_txn_counter={index + 1}
                                                                        />
                                                                    </p>
                                                                ) : null}
                                                            </div>
                                                        )}
                                                        {transactionTableSourceShow && (
                                                            <div className="td details flex justify-center gap-3">
                                                                <img
                                                                    className="source--img"
                                                                    src={txn.source === 'paypal' ? assetUrl + '/img/paypal.png' : assetUrl + '/img/' + txn.source + '.svg'}
                                                                    title={txn.source ? txn.source.toUpperCase() : ''}
                                                                    alt={txn.source || ''}
                                                                />
                                                            </div>
                                                        )}
                                                        {transactionTableStatusShow && (
                                                            <div className="td details flex justify-center gap-3">
                                                                <p data-id={txn.id}>
                                                                    <span style={{ color: '#fff', padding: '7px 15px', borderRadius: '20px', background: statusColor }}>
                                                                        {txn.status_label || statusLabel}
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        )}
                                                        {transactionTableDateShow && (
                                                            <div className="td details flex justify-center gap-3">
                                                                <p>{formatBpDate( txn.payment_date )}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        ) : (
                                            <div className="flex justify-center m-5">
                                                <p className="bp-no_subscription-text">{noItemsLabel || __('No records found!', 'better-payment')}</p>
                                            </div>
                                        )}
                                        </div>

                                        {txMeta.pages > 1 && (
                                            <div className="bp--pagination pagination">
                                                <ul>
                                                    <li>
                                                        <a
                                                            href="#"
                                                            className={`pagination-previous bp--prev${txPage <= 1 ? ' disabled' : ''}`}
                                                            aria-disabled={txPage <= 1}
                                                            onClick={(e) => { e.preventDefault(); if (txPage > 1) setTxPage(p => p - 1); }}
                                                        >
                                                            <i className="bp-icon bp-caret-left"></i>
                                                        </a>
                                                    </li>
                                                    {(() => {
                                                        const total = txMeta.pages;
                                                        const items = [];
                                                        const rendered = {};
                                                        let ellipsisLeft = false;
                                                        let ellipsisRight = false;

                                                        for (let p = 1; p <= total; p++) {
                                                            const inWindow = p === 1 || p === total || (p >= txPage - 2 && p <= txPage + 2);
                                                            if (total <= 7 || inWindow) {
                                                                const isActive = p === txPage;
                                                                items.push(
                                                                    <li key={p}>
                                                                        <a href="#" className={`pagination-link bp--page-num${isActive ? ' is-current active' : ''}`} aria-label={`Page ${p}`}
                                                                            onClick={(e) => { e.preventDefault(); if (!isActive) setTxPage(p); }}> {p} </a>
                                                                    </li>
                                                                );
                                                                rendered[p] = true;
                                                            } else {
                                                                const inLeftGap  = p < txPage - 2 && !ellipsisLeft;
                                                                const inRightGap = p > txPage + 2 && !ellipsisRight;
                                                                if (inLeftGap) {
                                                                    const mid = Math.max(1, Math.round((2 + (txPage - 3)) / 2));
                                                                    items.push(<li key={`el-l`}><a href="#" className="pagination-link bp--page-num bp--ellipsis" data-page={mid} title={`Jump to page ${mid}`} onClick={(e) => { e.preventDefault(); setTxPage(mid); }}>...</a></li>);
                                                                    ellipsisLeft = true;
                                                                } else if (inRightGap) {
                                                                    const mid = Math.min(total, Math.round(((txPage + 3) + (total - 1)) / 2));
                                                                    items.push(<li key={`el-r`}><a href="#" className="pagination-link bp--page-num bp--ellipsis" data-page={mid} title={`Jump to page ${mid}`} onClick={(e) => { e.preventDefault(); setTxPage(mid); }}>...</a></li>);
                                                                    ellipsisRight = true;
                                                                }
                                                            }
                                                        }
                                                        return items;
                                                    })()}
                                                    <li>
                                                        <a
                                                            href="#"
                                                            className={`pagination-next bp--next${txPage >= txMeta.pages ? ' disabled' : ''}`}
                                                            aria-disabled={txPage >= txMeta.pages}
                                                            onClick={(e) => { e.preventDefault(); if (txPage < txMeta.pages) setTxPage(p => p + 1); }}
                                                        >
                                                            <i className="bp-icon bp-caret-right"></i>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Subscriptions Tab (Pro only) */}
                    {subscriptionsShow && activeTab === 'subscriptions' && (
                        <SubscriptionsTab
                            proEnabled={proEnabled}
                            proAssets={proAssets}
                            subscriptions={subRows}
                            subPage={subPage}
                            subMeta={subMeta}
                            setSubPage={setSubPage}
                            attributes={attributes}
                            noItemsLabel={attributes.noItemsLabel}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
