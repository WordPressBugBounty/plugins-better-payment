import { useEffect, useState } from '@wordpress/element';
import { dashboardAPI } from './../../utils/api';
import bpPaypalLogo from './../../images/bp-paypal.svg';
import stripeLogo from './../../images/st.svg';
import paystackLogo from './../../images/pay.svg';
import {
	formatDateTime,
	formateName,
	paymentStatus,
	emailAddress,
	siteTimezone,
} from './../../utils/helper';
import { __ } from '@wordpress/i18n';
import illustration from './../../images/illustration.webp';
import illustrationdark from './../../images/illustrationdark.webp';

// How many transactions the dashboard shows — and therefore how many rows the
// skeleton draws. One constant so the placeholder can't promise more rows than
// the request can return.
const PER_PAGE = 5;
const SKELETON_ROWS = PER_PAGE;

const RecentTransactions = ( { onPageChange } ) => {
	const betterPaymentAdmin = window.betterPaymentAdmin || {};
	const [ transactions, setTransactions ] = useState();
	const [ loading, setLoading ] = useState( true );
	// payment_date is UTC; it is shown in the site's timezone (offset sites included).
	const timezone = siteTimezone();
	const tableHeaders = [
		{ label: __( 'Name', 'better-payment' ), width: '146' },
		{ label: __( 'Email', 'better-payment' ), width: '228' },
		{ label: __( 'Amount', 'better-payment' ), width: '116' },
		{ label: __( 'Source', 'better-payment' ), width: '122' },
		{ label: __( 'Status', 'better-payment' ), width: '124' },
		{ label: __( 'Date', 'better-payment' ), width: '164' },
	];

	useEffect( () => {
		dashboardAPI
			.getTransactions( { per_page: PER_PAGE } )
			.then( ( data ) => {
				setTransactions( data?.transactions || [] );
				setLoading( false );
			} );
	}, [] );

	// Handle view transaction - navigate to Transactions page with transaction details
	const handleViewTransaction = ( transactionId ) => {
		const url = new URL( window.location );
		url.searchParams.set( 'tab', 'transactions' );
		url.searchParams.set( 'action', 'view' );
		url.searchParams.set( 'id', transactionId );
		window.history.pushState( {}, '', url );
		onPageChange( 'transactions' );
	};

	return (
		<div className="border-radius_12 margin-bottom_20 bp-recent_transactions-wrapper">
			<div className="bp-row align-items_center justify-content-space_between bp-table_header-box">
				<div className="bp-col bp-col_6">
					<h4 className="bp-font_inter fx-2 bp-table_header">
						{ __( 'Recent Transactions', 'better-payment' ) }
					</h4>
				</div>
				{ ! loading && transactions?.length > 0 && (
					<div className="bp-col bp-col_6 bp-flex justify-content_flex-end">
						<a
							href={ `${ betterPaymentAdmin.adminUrl }&tab=transactions` }
							className="bp-font_ibm fx-6 bp-btn_2 bp-view-transactions_btn"
							onClick={ ( e ) => {
								e.preventDefault();
								onPageChange( 'transactions' );
							} }
						>
							{ __( 'View All Transactions', 'better-payment' ) }
						</a>
					</div>
				) }
			</div>

			{ loading && ! transactions ? (
				<div
					className="bp-data_table-wrapper"
					role="status"
					aria-busy="true"
					aria-label={ __(
						'Loading transactions…',
						'better-payment'
					) }
				>
					{ /* The real header row and the real per-column widths, so the
                        table fills in place instead of the whole block redrawing.
                        SKELETON_ROWS matches the per_page above — promising more
                        rows than can arrive is its own layout shift. */ }
					<div className="bp-flex bp-table_header-row">
						{ tableHeaders.map( ( header, index ) => (
							<div
								className={ `bp-width_${ header.width }` }
								key={ index }
							>
								<h5 className="bp-font_inter fx-5 bp-dataTable_header">
									{ header.label }
								</h5>
							</div>
						) ) }
					</div>
					{ Array.from( { length: SKELETON_ROWS }, ( _, row ) => (
						<div
							className="bp-flex bp-table_data-row align-items_center"
							key={ row }
						>
							{ tableHeaders.map( ( header, col ) => (
								<div
									className={ `bp-width_${ header.width }` }
									key={ col }
								>
									<div className="wp-skeleton wp-skeleton_cell"></div>
								</div>
							) ) }
						</div>
					) ) }
				</div>
			) : ! loading && transactions?.length > 0 ? (
				<div className="bp-data_table-wrapper ">
					<div className="bp-flex bp-table_header-row ">
						{ tableHeaders.map( ( header, index ) => (
							<div
								className={ `bp-width_${ header.width }` }
								key={ index }
							>
								<h5 className="bp-font_inter fx-5 bp-dataTable_header ">
									{ header.label }
								</h5>
							</div>
						) ) }
					</div>
					{ transactions?.map( ( transaction, index ) => (
						<div
							className="bp-flex bp-table_data-row align-items_center"
							key={ index }
						>
							<div className="bp-width_146">
								<p
									className="bp-font_inter fx-5 table-data_value bp-transaction-name-link"
									onClick={ ( e ) => {
										e.preventDefault();
										handleViewTransaction(
											transaction?.id
										);
									} }
								>
									{ formateName(
										transaction?.form_fields_info
									) }
								</p>
							</div>
							<div className="bp-width_228">
								<div className="bp-flex align-items_center bp-transaction_email-wrapper">
									<p className="bp-font_inter fx-5 table-data_value">
										{ emailAddress(
											transaction?.form_fields_info
										) || transaction?.email }
									</p>
									{ ( emailAddress(
										transaction?.form_fields_info
									) ||
										transaction?.email ) && (
										<span className="line-height_0 bp-copy_btn-icon">
											<button
												className="bp-transaction_data-copy_btn "
												onClick={ ( e ) => {
													navigator.clipboard.writeText(
														emailAddress(
															transaction?.form_fields_info
														) || transaction?.email
													);
													e.target.classList.add(
														'copyed-text'
													);
													setTimeout( () => {
														e.target.classList.remove(
															'copyed-text'
														);
													}, 1000 );
												} }
											>
												<svg
													width="20"
													height="20"
													viewBox="0 0 20 20"
													fill="none"
													xmlns="http://www.w3.org/2000/svg"
												>
													<path
														d="M15.0002 15.601C16.3257 15.601 17.4002 14.5265 17.4002 13.201V8.40098C17.4002 5.38399 17.4002 3.87549 16.463 2.93823C15.5257 2.00098 14.0172 2.00098 11.0002 2.00098H7.80024C6.47476 2.00098 5.40024 3.07549 5.40024 4.40098M10.2002 18.001H7.80024C5.5375 18.001 4.40613 18.001 3.70319 17.298C3.00024 16.5951 3.00024 15.4637 3.00024 13.201V9.20098C3.00024 6.93823 3.00024 5.80686 3.70319 5.10392C4.40613 4.40098 5.5375 4.40098 7.80024 4.40098H10.2002C12.463 4.40098 13.5944 4.40098 14.2973 5.10392C15.0002 5.80686 15.0002 6.93824 15.0002 9.20098V13.201C15.0002 15.4637 15.0002 16.5951 14.2973 17.298C13.5944 18.001 12.463 18.001 10.2002 18.001Z"
														strokeWidth="1.152"
													/>
												</svg>
											</button>
										</span>
									) }
								</div>
							</div>
							<div className="bp-width_116">
								<p className="bp-font_inter fx-5 table-data_value">
									{ transaction?.currency +
										' ' +
										transaction?.amount }
								</p>
							</div>
							<div className="bp-width_122">
								<div className="bp-flex align-items_center">
									<div className="pay-type_logo">
										{ transaction?.source?.toLowerCase() ===
										'paypal' ? (
											<img src={ bpPaypalLogo } />
										) : transaction?.source?.toLowerCase() ===
										  'stripe' ? (
											<img src={ stripeLogo } />
										) : transaction?.source?.toLowerCase() ===
										  'paystack' ? (
											<img src={ paystackLogo } />
										) : null }
									</div>
									<div className="pay-type_name">
										{ transaction?.source?.toLowerCase() ===
										'paypal' ? (
											<p className="bp-font_ibm fx-5">
												Paypal
											</p>
										) : transaction?.source?.toLowerCase() ===
										  'stripe' ? (
											<p className="bp-font_ibm fx-5">
												Stripe
											</p>
										) : transaction?.source?.toLowerCase() ===
										  'paystack' ? (
											<p className="bp-font_ibm fx-5">
												Paystack
											</p>
										) : null }
									</div>
								</div>
							</div>
							<div className="bp-width_124">
								<span
									className={ `bp-font_inter fx-6 tag ${
										transaction?.status
											? paymentStatus(
													transaction?.status,
													transaction?.status_type
											  )?.toLowerCase()
											: 'incomplete'
									}` }
								>
									{ transaction?.status
										? paymentStatus(
												transaction?.status,
												transaction?.status_type
										  )
										: 'Incomplete' }
								</span>
							</div>
							<div className="bp-width_164">
								<p className="bp-font_inter fx-5 table-data_value">
									{ formatDateTime(
										transaction?.payment_date,
										timezone
									) }
								</p>
							</div>
						</div>
					) ) }
				</div>
			) : (
				<div className="bp-data_table-no_data-wrapper">
					<div className="bp-data_table-no-data_img-wrapper">
						<img
							src={ illustration }
							alt=""
							className="light-img"
						/>
						<img
							src={ illustrationdark }
							alt=""
							className="dark-img"
						/>
						<h3 className="bp-font_ibm no-transaction-header">
							No Transactions
						</h3>
						<p className="bp-font_ibm fx-6 no-transaction-sub-header">
							Use{ ' ' }
							<a
								href={ `//betterpayment.co/docs/configure-form-settings-in-better-payment/` }
								target="_blank"
							>
								Payment Form
							</a>{ ' ' }
							or{ ' ' }
							<a
								href={ `//betterpayment.co/docs/configure-fundraising-campaign-in-better-payment/` }
								target="_blank"
							>
								Fundraising Campaign
							</a>{ ' ' }
							widget to collect payments.
						</p>
					</div>
				</div>
			) }
		</div>
	);
};

export default RecentTransactions;
