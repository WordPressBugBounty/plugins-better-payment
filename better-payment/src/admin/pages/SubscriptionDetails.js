import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { subscriptionsAPI } from './../utils/api';
import {
	formatDateCompact,
	ecommerceBillingLabel,
	ecommerceSubscriptionStatus,
	ecommerceRenewalsLabel,
} from './../utils/helper';
import DeleteConfirm from './DeleteConfirm';
import FooterCards from '../components/settings/FooterCards.js';

/*
 * A detail row's value: the real content once loaded, a shimmer while the
 * fetch is in flight — same contract as TransactionDetails.js's DetailValue
 * (only values shimmer; card chrome, headings and key labels paint on the
 * first frame).
 */
const DetailValue = ( { loading, width = 'wp-skeleton_w-60', children } ) => {
	if ( loading ) {
		return (
			<div className={ `wp-skeleton wp-skeleton_cell ${ width }` }></div>
		);
	}

	return children;
};

/*
 * WooCommerce order status => existing `.tag` class (green/amber/red), so the
 * Related Orders list needs no new status styles. Anything unknown renders
 * amber rather than unstyled.
 */
const wooOrderTagClass = ( status ) => {
	const normalized = String( status ?? '' ).toLowerCase();

	if ( [ 'completed', 'processing' ].includes( normalized ) ) {
		return 'completed';
	}
	if (
		[ 'cancelled', 'refunded', 'failed', 'trash' ].includes( normalized )
	) {
		return 'refunded';
	}

	return 'incomplete';
};

/*
 * One address card of the Billing & Shipping section: the heading in the
 * first column, the plain-text address lines from the API (never markup)
 * in the second. The first line is the recipient name and gets its own
 * emphasized line; the remaining lines are joined into one comma-separated
 * paragraph, with the phone on its own line below. An empty address
 * renders an em dash so the card still reads as deliberately empty.
 */
const AddressBlock = ( { heading, lines = [], phone = '' } ) => {
	const [ name, ...rest ] = lines;

	return (
		<div className="bp-address_block">
			<h4 className="bp-font_ibm bp-address_block-heading">
				{ heading }
			</h4>
			<div className="bp-address_block-lines">
				{ lines.length > 0 ? (
					<>
						<p className="bp-information_value bp-address_block-name">
							{ name }
						</p>
						{ rest.length > 0 && (
							<p className="bp-information_value">
								{ rest.join( ', ' ) }
							</p>
						) }
					</>
				) : (
					<p className="bp-information_value">—</p>
				) }
				{ phone && (
					<p className="bp-information_value bp-address_block-phone">
						{ phone }
					</p>
				) }
			</div>
		</div>
	);
};

const SubscriptionDetails = ( { subscriptionId, source, onBack } ) => {
	const [ detail, setDetail ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	// The status action in flight ('cancel' | 'reactivate' | null) — both
	// buttons disable while either runs.
	const [ actioning, setActioning ] = useState( null );
	const [ isCancelModalOpen, setIsCancelModalOpen ] = useState( false );

	const fetchDetail = useCallback( async () => {
		try {
			setError( null );
			const response = await subscriptionsAPI.getSubscription(
				subscriptionId,
				source
			);
			setDetail( response );
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Failed to fetch subscription data', 'better-payment' )
			);
		} finally {
			setLoading( false );
		}
	}, [ subscriptionId, source ] );

	useEffect( () => {
		setLoading( true );
		fetchDetail();
	}, [ fetchDetail ] );

	const handleBackClick = () => {
		if ( onBack ) {
			onBack();
		}
	};

	const handleCopyEmail = ( email, e ) => {
		window.navigator.clipboard.writeText( email );
		e.target.classList.add( 'copyed-text' );
		setTimeout( () => {
			e.target.classList.remove( 'copyed-text' );
		}, 1000 );
	};

	const runStatusAction = async ( action ) => {
		if ( actioning ) {
			return;
		}

		try {
			setActioning( action );
			const response = await subscriptionsAPI.updateSubscriptionStatus(
				subscriptionId,
				source,
				action
			);
			toast.success(
				response?.message ||
					__( 'Subscription updated successfully.', 'better-payment' )
			);
			await fetchDetail();
		} catch ( err ) {
			toast.error(
				err?.message ||
					__( 'Failed to update the subscription.', 'better-payment' )
			);
		} finally {
			setActioning( null );
			setIsCancelModalOpen( false );
		}
	};

	const orDash = ( value ) => value || '—';
	const dateLabel = ( value ) => formatDateCompact( value ) || '—';
	const moneyLabel = ( amount, currency ) => {
		if ( amount === null || amount === undefined ) {
			return '—';
		}
		return `${ currency ?? '' } ${ Number( amount ).toFixed( 2 ) }`.trim();
	};

	const statusInfo = ecommerceSubscriptionStatus( detail?.status );
	// An un-hydrated subscription (integration inactive, order deleted) has
	// no status; it reads as data-only and offers no actions.
	const isHydrated = Boolean( detail?.status );
	const availableActions = detail?.available_actions ?? [];
	const orders = detail?.orders ?? [];

	return (
		<>
			<div
				className="section  bp-transaction_details-page"
				id="subscriptionDetails"
			>
				<div className="bp-container">
					<div className="bp-flex align-items_center justify-content-space_between">
						<div className="bp-flex align-items_center bp-back_btn-wrapper">
							<button
								className="line-height_0 bp-transaction_details-back_btn"
								onClick={ handleBackClick }
							>
								<svg
									width="16"
									height="16"
									viewBox="0 0 16 16"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<g clipPath="url(#clip0_bp_subd_back)">
										<path
											d="M3.33203 8H12.6654"
											stroke="#394357"
											strokeWidth="1.5"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
										<path
											d="M3.33203 8L7.33203 12"
											stroke="#394357"
											strokeWidth="1.5"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
										<path
											d="M3.33203 8L7.33203 4"
											stroke="#394357"
											strokeWidth="1.5"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</g>
									<defs>
										<clipPath id="clip0_bp_subd_back">
											<rect
												width="16"
												height="16"
												fill="white"
											/>
										</clipPath>
									</defs>
								</svg>
							</button>
							<h2 className="fx-1 bp-font_ibm bp-back_btn-title">
								{ __(
									'Back to Subscriptions',
									'better-payment'
								) }
							</h2>
						</div>
					</div>

					{ error && ! loading ? (
						<div className="bp-transaction_details-wrapper">
							<div className="bp-data_table-no_data-wrapper">
								<div className="bp-data_table-no-data_img-wrapper">
									<h3 className="bp-font_ibm no-transaction-header">
										{ __(
											'Could not load this subscription',
											'better-payment'
										) }
									</h3>
									<p className="bp-font_ibm fx-6 no-transaction-sub-header">
										{ error }
									</p>
								</div>
							</div>
						</div>
					) : (
						<div className="bp-transaction_details-wrapper">
							<div className="bp-flex bp-transaction_details-body">
								<div className="bp-transaction_details-left_content">
									{ /* Customer Information */ }
									<div className="bp-information_wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M10 1.667a4.167 4.167 0 1 0 0 8.333 4.167 4.167 0 0 0 0-8.333Zm0 1.25a2.917 2.917 0 1 1 0 5.833 2.917 2.917 0 0 1 0-5.833Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M10 10.833c-2.679 0-5.049.67-6.522 1.674-1.123.766-1.811 1.739-1.811 2.76v1.17c0 .363.144.712.401.969.257.256.605.4.968.4H16.964c.363 0 .711-.144.968-.4.257-.257.401-.606.401-.969v-1.17c0-1.021-.688-1.994-1.811-2.76-1.473-1.004-3.843-1.674-6.522-1.674Zm0 1.25c2.395 0 4.524.607 5.818 1.489.737.502 1.265 1.092 1.265 1.695v1.17a.12.12 0 0 1-.035.084.12.12 0 0 1-.084.035H3.036a.12.12 0 0 1-.084-.035.12.12 0 0 1-.035-.084v-1.17c0-.603.528-1.193 1.265-1.695 1.294-.882 3.423-1.489 5.818-1.489Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Customer Information',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Name:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ orDash(
																	detail?.customer_name
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Email:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-75"
														>
															<div className="bp-flex align-items_center">
																<p className="bp-information_value">
																	{ orDash(
																		detail?.customer_email
																	) }
																</p>
																{ detail?.customer_email && (
																	<button
																		className="bp-copy_btn bp-transaction_data-copy_btn"
																		onClick={ (
																			e
																		) =>
																			handleCopyEmail(
																				detail?.customer_email,
																				e
																			)
																		}
																		type="button"
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
																) }
															</div>
														</DetailValue>
													</div>
												</li>
											</ul>
										</div>
									</div>

									{ /* Related Orders */ }
									<div className="bp-information_wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M5 1.667c-1.036 0-1.875.84-1.875 1.875v12.916c0 1.036.84 1.875 1.875 1.875h10c1.035 0 1.875-.84 1.875-1.875V6.25c0-.166-.066-.325-.183-.442l-3.958-3.958a.625.625 0 0 0-.442-.183H5Zm-.625 1.875c0-.345.28-.625.625-.625h6.042v3.125c0 .345.28.625.625.625h3.125v9.791c0 .346-.28.625-.625.625H5a.625.625 0 0 1-.625-.625V3.542Z"
															fill="#394357"
														/>
														<path
															d="M6.875 10.625h6.25a.625.625 0 1 0 0-1.25h-6.25a.625.625 0 1 0 0 1.25ZM6.875 13.75h6.25a.625.625 0 1 0 0-1.25h-6.25a.625.625 0 1 0 0 1.25Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Related Orders',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											{ loading ? (
												<div className="bp-related_orders">
													{ Array.from(
														{ length: 3 },
														( _, row ) => (
															<div
																className="bp-related_orders-row"
																key={ row }
															>
																{ Array.from(
																	{
																		length: 5,
																	},
																	(
																		__unused,
																		col
																	) => (
																		<div
																			className="wp-skeleton wp-skeleton_cell"
																			key={
																				col
																			}
																		></div>
																	)
																) }
															</div>
														)
													) }
												</div>
											) : (
												<div className="bp-related_orders">
													<div className="bp-related_orders-row bp-related_orders-head">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Order',
																'better-payment'
															) }
														</h4>
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Type',
																'better-payment'
															) }
														</h4>
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Date',
																'better-payment'
															) }
														</h4>
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Status',
																'better-payment'
															) }
														</h4>
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Total',
																'better-payment'
															) }
														</h4>
													</div>
													{ orders.map( ( order ) => (
														<div
															className="bp-related_orders-row"
															key={ `${ order.order_id }-${ order.type }` }
														>
															<p className="bp-information_value">
																{ order.edit_url ? (
																	<a
																		href={
																			order.edit_url
																		}
																		target="_blank"
																		rel="noreferrer"
																	>
																		#
																		{ order.order_number ||
																			order.order_id }
																	</a>
																) : (
																	`#${ order.order_id }`
																) }
															</p>
															<p className="bp-information_value">
																{ orDash(
																	order.type_label
																) }
															</p>
															<p className="bp-information_value">
																{ dateLabel(
																	order.date
																) }
															</p>
															<p className="bp-information_value">
																{ order.status ? (
																	<span
																		className={ `fx-4 bp-font_ibm tag ${ wooOrderTagClass(
																			order.status
																		) }` }
																	>
																		{ order.status_label ||
																			order.status }
																	</span>
																) : (
																	'—'
																) }
															</p>
															<p className="bp-information_value">
																{ moneyLabel(
																	order.total,
																	order.currency
																) }
															</p>
														</div>
													) ) }
													{ orders.length === 0 && (
														<p className="bp-font_ibm fx-5 bp-related_orders-empty">
															{ __(
																'No related orders recorded.',
																'better-payment'
															) }
														</p>
													) }
												</div>
											) }
										</div>
									</div>
								</div>

								<div className="bp-transaction_details-right_content payment-gateway-wrapper">
									{ /* Subscription Information */ }
									<div className="bp-information_wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															d="M10 1.667a8.333 8.333 0 1 0 0 16.666 8.333 8.333 0 0 0 0-16.666Zm0 1.25a7.083 7.083 0 1 1 0 14.166 7.083 7.083 0 0 1 0-14.166Z"
															fill="#394357"
														/>
														<path
															d="M9.375 5.417v4.841l3.108 3.109.884-.884-2.742-2.742V5.417h-1.25Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Subscription Information',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Subscription ID:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																#
																{ detail?.subscription_id ??
																	subscriptionId }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Product:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
														>
															<p className="bp-information_value">
																{ orDash(
																	detail?.product_name
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Amount:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ moneyLabel(
																	detail?.amount,
																	detail?.currency
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Billing:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ ecommerceBillingLabel(
																	detail?.interval,
																	detail?.period
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Status:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-25"
														>
															{ isHydrated ? (
																<span
																	className={ `fx-4 bp-font_ibm tag ${ statusInfo.tagClass }` }
																>
																	{
																		statusInfo.label
																	}
																</span>
															) : (
																<p className="bp-information_value">
																	—
																</p>
															) }
														</DetailValue>
													</div>
												</li>
												{ /* Who ended a cancelled subscription — the actor's
												    name plus a Customer/Admin tag. Conditional like
												    the payment-date rows: cancellations recorded
												    before the actor stamp existed have no answer,
												    so no row rather than a guess. */ }
												{ ! loading &&
													detail?.cancelled_by_type && (
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	{ __(
																		'Cancelled By:',
																		'better-payment'
																	) }
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<DetailValue
																	loading={
																		loading
																	}
																	width="wp-skeleton_w-40"
																>
																	<p className="bp-information_value bp-cancelled_by">
																		{ detail?.cancelled_by_name && (
																			<span className="bp-cancelled_by-name">
																				{
																					detail.cancelled_by_name
																				}
																			</span>
																		) }
																		<span
																			className={ `fx-4 bp-font_ibm tag bp-cancelled_by-tag bp-cancelled_by-tag--${ detail.cancelled_by_type }` }
																		>
																			{ detail.cancelled_by_type ===
																			'admin'
																				? __(
																						'Admin',
																						'better-payment'
																				  )
																				: __(
																						'Customer',
																						'better-payment'
																				  ) }
																		</span>
																	</p>
																</DetailValue>
															</div>
														</li>
													) }
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Started:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ dateLabel(
																	detail?.start_date
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												{ /* Both payment-date rows are conditional — the API
												    sends '' when there is nothing to report (no next
												    charge on a cancelled/terminal subscription; no
												    last payment until a renewal has settled), and an
												    absent row reads better than an em dash. */ }
												{ ! loading &&
													detail?.last_payment && (
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	{ __(
																		'Last Payment:',
																		'better-payment'
																	) }
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<DetailValue
																	loading={
																		loading
																	}
																	width="wp-skeleton_w-40"
																>
																	<p className="bp-information_value">
																		{ dateLabel(
																			detail?.last_payment
																		) }
																	</p>
																</DetailValue>
															</div>
														</li>
													) }
												{ ! loading &&
													detail?.next_payment && (
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	{ __(
																		'Next Payment:',
																		'better-payment'
																	) }
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<DetailValue
																	loading={
																		loading
																	}
																	width="wp-skeleton_w-40"
																>
																	<p className="bp-information_value">
																		{ dateLabel(
																			detail?.next_payment
																		) }
																	</p>
																</DetailValue>
															</div>
														</li>
													) }
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Renewals:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-25"
														>
															<p className="bp-information_value">
																{ isHydrated
																	? ecommerceRenewalsLabel(
																			detail?.renewal_count
																	  )
																	: '—' }
															</p>
														</DetailValue>
													</div>
												</li>
												{ /* Only meaningful once hydrated — '' means the
												    integration could not report it. */ }
												{ ! loading &&
													detail?.auto_renew && (
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	{ __(
																		'Auto Renewal:',
																		'better-payment'
																	) }
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className="bp-information_value">
																	{ detail.auto_renew ===
																	'no'
																		? __(
																				'Off',
																				'better-payment'
																		  )
																		: __(
																				'On',
																				'better-payment'
																		  ) }
																</p>
															</div>
														</li>
													) }
											</ul>
										</div>
									</div>

									{ /* Source */ }
									<div className="bp-information_wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper border-none margin-0">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M3.333 3.333c-.92 0-1.666.747-1.666 1.667v10c0 .92.746 1.667 1.666 1.667h13.334c.92 0 1.666-.747 1.666-1.667V5c0-.92-.746-1.667-1.666-1.667H3.333Zm-.416 1.667c0-.23.186-.417.416-.417h13.334c.23 0 .416.187.416.417v1.667H2.917V5Zm0 2.917V15c0 .23.186.417.416.417h13.334c.23 0 .416-.187.416-.417V7.917H2.917Z"
															fill="#394357"
														/>
														<path
															d="M4.583 12.083c0-.345.28-.625.625-.625h3.334a.625.625 0 1 1 0 1.25H5.208a.625.625 0 0 1-.625-.625Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Source',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="payment-gateway_wrapper bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															{ __(
																'Platform:',
																'better-payment'
															) }
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ orDash(
																	detail?.source_label
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												{ ! loading &&
													detail?.order_edit_url && (
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	{ __(
																		'Order:',
																		'better-payment'
																	) }
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className="bp-information_value">
																	<a
																		href={
																			detail.order_edit_url
																		}
																		target="_blank"
																		rel="noreferrer"
																	>
																		{ sprintf(
																			/* translators: %d: parent (subscription) order id */
																			__(
																				'View order #%d',
																				'better-payment'
																			),
																			detail?.subscription_id ??
																				subscriptionId
																		) }
																	</a>
																</p>
															</div>
														</li>
													) }
											</ul>
										</div>
									</div>

									{ /* Subscription Action — the ONE editable thing here.
									    Everything else on this page is read-only. */ }
									<div className="bp-information_wrapper bp-subscription_action-wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper border-none margin-0">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M10 6.042a3.958 3.958 0 1 0 0 7.916 3.958 3.958 0 0 0 0-7.916Zm0 1.25a2.708 2.708 0 1 1 0 5.416 2.708 2.708 0 0 1 0-5.416Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M8.958 1.667a.625.625 0 0 0-.625.625v1.323a6.68 6.68 0 0 0-1.44.598l-.936-.937a.625.625 0 0 0-.884 0L3.276 5.073a.625.625 0 0 0 0 .884l.937.936a6.68 6.68 0 0 0-.598 1.44H2.292a.625.625 0 0 0-.625.625v2.084c0 .345.28.625.625.625h1.323c.145.507.347.99.598 1.44l-.937.936a.625.625 0 0 0 0 .884l1.797 1.797c.244.244.64.244.884 0l.936-.937c.45.251.933.453 1.44.598v1.323c0 .345.28.625.625.625h2.084c.345 0 .625-.28.625-.625v-1.323a6.68 6.68 0 0 0 1.44-.598l.936.937c.244.244.64.244.884 0l1.797-1.797a.625.625 0 0 0 0-.884l-.937-.936c.251-.45.453-.933.598-1.44h1.323c.345 0 .625-.28.625-.625V8.958a.625.625 0 0 0-.625-.625h-1.323a6.68 6.68 0 0 0-.598-1.44l.937-.936a.625.625 0 0 0 0-.884l-1.797-1.797a.625.625 0 0 0-.884 0l-.936.937a6.68 6.68 0 0 0-1.44-.598V2.292a.625.625 0 0 0-.625-.625H8.958Zm.625 2.5v-1.25h.834v1.25c0 .296.207.551.497.612.657.138 1.271.396 1.816.752.248.162.575.128.784-.081l.885-.885.589.589-.885.885a.625.625 0 0 0-.081.784c.356.545.614 1.159.752 1.816.06.29.316.497.612.497h1.25v.834h-1.25a.625.625 0 0 0-.612.497 5.43 5.43 0 0 1-.752 1.816.625.625 0 0 0 .081.784l.885.885-.589.589-.885-.885a.625.625 0 0 0-.784-.081 5.43 5.43 0 0 1-1.816.752.625.625 0 0 0-.497.612v1.25h-.834v-1.25a.625.625 0 0 0-.497-.612 5.43 5.43 0 0 1-1.816-.752.625.625 0 0 0-.784.081l-.885.885-.589-.589.885-.885a.625.625 0 0 0 .081-.784 5.43 5.43 0 0 1-.752-1.816.625.625 0 0 0-.612-.497h-1.25v-.834h1.25c.296 0 .551-.207.612-.497.138-.657.396-1.271.752-1.816a.625.625 0 0 0-.081-.784l-.885-.885.589-.589.885.885c.209.209.536.243.784.081a5.43 5.43 0 0 1 1.816-.752.625.625 0 0 0 .497-.612Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Subscription Action',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											{ loading ? (
												<div className="bp-subscription_action-body">
													<div className="wp-skeleton wp-skeleton_btn"></div>
												</div>
											) : (
												<div className="bp-subscription_action-body">
													{ availableActions.length >
													0 ? (
														<>
															<p className="bp-font_ibm fx-5 bp-subscription_action-hint">
																{ __(
																	'Status changes take effect immediately.',
																	'better-payment'
																) }
															</p>
															<div className="bp-flex align-items_center bp-subscription_action-buttons">
																{ availableActions.includes(
																	'reactivate'
																) && (
																	<button
																		className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-subscription_action-reactivate_btn"
																		onClick={ () =>
																			runStatusAction(
																				'reactivate'
																			)
																		}
																		disabled={
																			!! actioning
																		}
																		type="button"
																	>
																		{ actioning ===
																		'reactivate'
																			? __(
																					'Reactivating…',
																					'better-payment'
																			  )
																			: __(
																					'Reactivate Subscription',
																					'better-payment'
																			  ) }
																	</button>
																) }
																{ availableActions.includes(
																	'cancel'
																) && (
																	<button
																		className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-subscription_action-cancel_btn"
																		onClick={ () =>
																			setIsCancelModalOpen(
																				true
																			)
																		}
																		disabled={
																			!! actioning
																		}
																		type="button"
																	>
																		{ actioning ===
																		'cancel'
																			? __(
																					'Cancelling…',
																					'better-payment'
																			  )
																			: __(
																					'Cancel Subscription',
																					'better-payment'
																			  ) }
																	</button>
																) }
															</div>
														</>
													) : (
														<p className="bp-font_ibm fx-5 bp-subscription_action-hint">
															{ isHydrated
																? __(
																		'No actions are available for this subscription’s current status.',
																		'better-payment'
																  )
																: __(
																		'This subscription’s integration is not available, so it cannot be managed from here.',
																		'better-payment'
																  ) }
														</p>
													) }
												</div>
											) }
										</div>
									</div>

									{ /* Billing & Shipping — from the parent (subscription) order. */ }
									<div className="bp-information_wrapper bp-billing_shipping-wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper border-none margin-0">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M10 1.667a6.25 6.25 0 0 0-6.25 6.25c0 2.31 1.32 4.518 2.73 6.188a20.8 20.8 0 0 0 2.926 2.87c.18.142.395.192.594.192.199 0 .414-.05.594-.192a20.8 20.8 0 0 0 2.926-2.87c1.41-1.67 2.73-3.878 2.73-6.188A6.25 6.25 0 0 0 10 1.667Zm-5 6.25a5 5 0 1 1 10 0c0 1.856-1.086 3.786-2.436 5.383A19.55 19.55 0 0 1 10 15.845a19.55 19.55 0 0 1-2.564-2.545C6.086 11.703 5 9.773 5 7.917Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M10 5.417a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Zm-1.25 2.5a1.25 1.25 0 1 1 2.5 0 1.25 1.25 0 0 1-2.5 0Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													{ __(
														'Billing & Shipping',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											{ loading ? (
												<div className="bp-billing_shipping-body">
													<div className="wp-skeleton wp-skeleton_cell wp-skeleton_w-75"></div>
													<div className="wp-skeleton wp-skeleton_cell wp-skeleton_w-60"></div>
													<div className="wp-skeleton wp-skeleton_cell wp-skeleton_w-40"></div>
												</div>
											) : (
												<div className="bp-billing_shipping-body">
													<AddressBlock
														heading={ __(
															'Billing Address',
															'better-payment'
														) }
														lines={
															detail?.billing_address ??
															[]
														}
														phone={
															detail?.billing_phone ??
															''
														}
													/>
													<AddressBlock
														heading={ __(
															'Shipping Address',
															'better-payment'
														) }
														lines={
															detail?.shipping_address ??
															[]
														}
													/>
												</div>
											) }
										</div>
									</div>
								</div>
							</div>
						</div>
					) }
					<FooterCards />
				</div>
			</div>

			<DeleteConfirm
				isOpen={ isCancelModalOpen }
				onClose={ () => setIsCancelModalOpen( false ) }
				onConfirm={ () => runStatusAction( 'cancel' ) }
				isDeleting={ actioning === 'cancel' }
				title={ __( 'Cancel this subscription?', 'better-payment' ) }
				message={ __(
					'No further renewals will be charged. You can reactivate it later from this page.',
					'better-payment'
				) }
				confirmLabel={ __( 'Yes, Cancel It', 'better-payment' ) }
				busyLabel={ __( 'Cancelling…', 'better-payment' ) }
			/>
		</>
	);
};

export default SubscriptionDetails;
