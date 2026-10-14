import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { transactionsAPI } from './../utils/api';
import {
	formateName,
	paymentStatus,
	emailAddress,
	canMarkTransactionComplete,
	formatDateTime,
} from './../utils/helper';
import GetProCard from '../components/Transactions/Details/GetProCard.js';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import FooterCards from '../components/settings/FooterCards.js';
import Stripe from '../svg/Stripe';
import Paypal from '../svg/Paypal';
import Paystack from '../svg/Paystack';

/**
 * A detail row's value: the real content once loaded, a shimmer while the fetch
 * is in flight.
 *
 * `loading` and `error` were tracked in this component from the start but never
 * read in the JSX, so the page mounted with `transaction` still `null` and every
 * optional-chained value resolved to empty — a full page of blank rows that
 * filled in field-by-field across two independent requests, and a permanently
 * blank page (no message) whenever one failed.
 *
 * Only the values shimmer. The card chrome, its heading, its icon and the key
 * labels are fixed text with nothing to wait for, so they stay real and paint on
 * the first frame — the same split as the dashboard's Hero and Pro's Analytics
 * card.
 *
 * `wp-skeleton_cell` (14px, no bottom margin) is deliberate rather than `_text`:
 * the key label beside it is 16px, so the key drives the row height and the
 * shimmer cannot change it. A taller `_text` bar (12px + 10px margin) would make
 * the row grow when the real value replaced it.
 */
const DetailValue = ( { loading, width = 'wp-skeleton_w-60', children } ) => {
	if ( loading ) {
		return (
			<div className={ `wp-skeleton wp-skeleton_cell ${ width }` }></div>
		);
	}

	return children;
};

const TransactionDetails = ( { transactionId, onBack } ) => {
	const handleBackClick = () => {
		if ( onBack ) {
			onBack();
		}
	};

	const [ transaction, setTransaction ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ isMarkingComplete, setIsMarkingComplete ] = useState( false );
	const [ refererData, setRefererData ] = useState( null );
	// Starts true: the effect below fires on mount, so a `false` seed rendered one
	// frame of empty "Referrer" rows before the shimmer appeared — a flash of the
	// very blank state the placeholder exists to prevent.
	const [ loadingReferer, setLoadingReferer ] = useState( true );

	useEffect( () => {
		const fetchTransaction = async () => {
			try {
				setLoading( true );
				setError( null );
				const response = await apiFetch( {
					path: `/better-payment/v1/transactions/${ transactionId }`,
					method: 'GET',
				} );
				setTransaction( response );
			} catch ( err ) {
				setError( err.message || 'Failed to fetch transaction data' );
			} finally {
				setLoading( false );
			}
		};
		fetchTransaction();
	}, [ transactionId ] );

	useEffect( () => {
		const fetchRefererData = async () => {
			if ( ! transactionId ) {
				// Nothing to wait for — without this the shimmer never resolves.
				setLoadingReferer( false );
				return;
			}

			try {
				setLoadingReferer( true );
				const response =
					await transactionsAPI.getTransactionReferer(
						transactionId
					);
				if ( response.success && response.data ) {
					setRefererData( response.data );
				}
			} catch ( err ) {
				// Silently fail - referer data is optional
				console.error( 'Failed to fetch referer data:', err );
			} finally {
				setLoadingReferer( false );
			}
		};
		fetchRefererData();
	}, [ transactionId ] );

	const handleMarkAsCompleted = async ( e ) => {
		e.preventDefault();

		if ( isMarkingComplete ) return;

		try {
			setIsMarkingComplete( true );
			const response =
				await transactionsAPI.markTransactionComplete( transactionId );

			if ( response.success ) {
				// Show success message
				toast.success(
					__( 'Transaction marked as completed', 'better-payment' )
				);

				// Refresh transaction data
				const updatedTransaction = await apiFetch( {
					path: `/better-payment/v1/transactions/${ transactionId }`,
					method: 'GET',
				} );
				setTransaction( updatedTransaction );
			}
		} catch ( err ) {
			toast.error(
				err.message ||
					__(
						'Failed to mark transaction as completed',
						'better-payment'
					)
			);
		} finally {
			setIsMarkingComplete( false );
		}
	};

	// Handle copy to clipboard for email
	const handleCopyEmail = ( email, e ) => {
		navigator.clipboard.writeText( email );
		e.target.classList.add( 'copyed-text' );
		setTimeout( () => {
			e.target.classList.remove( 'copyed-text' );
		}, 1000 );
	};

	// Handle copy to clipboard for transaction ID
	const handleCopyTransactionId = ( transactionId, e ) => {
		navigator.clipboard.writeText( transactionId );
		e.target.classList.add( 'copyed-text' );
		setTimeout( () => {
			e.target.classList.remove( 'copyed-text' );
		}, 1000 );
	};

	// Refetch the transaction after a panel a filter rendered here changed it. Passed
	// as the third argument of `better_payment_transaction_details_get_pro_card`: Pro's
	// card calls it (whatever it names the argument) and shows its own notice.
	const refreshTransaction = async () => {
		try {
			const updatedTransaction = await apiFetch( {
				path: `/better-payment/v1/transactions/${ transactionId }`,
				method: 'GET',
			} );
			setTransaction( updatedTransaction );
		} catch ( err ) {
			console.error( 'Failed to refresh the transaction:', err );
		}
	};

	// The server's status_type when the response has it (an unconfirmed pre-2.4.0 free
	// checkout reads Incomplete), else the raw-status map.
	const statusLabel = paymentStatus(
		transaction?.status,
		transaction?.status_type
	);

	return (
		<>
			<div
				className="section  bp-transaction_details-page"
				id="transactionDetails"
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
									<g clipPath="url(#clip0_4672_15786)">
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
										<clipPath id="clip0_4672_15786">
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
								Back to Transactions
							</h2>
						</div>
						{ /* Held back until the transaction lands: Pro swaps these for real
                            receipt/refund actions bound to it, and an action offered before
                            its subject exists is one the user can click into nothing. The
                            placeholders keep the row's height so nothing jumps. */ }
						{ loading ? (
							<div className="bp-flex align-items_center gap-8 bp-back_btn-wrapper">
								<div className="wp-skeleton wp-skeleton_btn"></div>
								<div className="wp-skeleton wp-skeleton_btn"></div>
							</div>
						) : (
							applyFilters(
								'better_payment_admin_transaction_details_header_actions',
								<>
									<div className="bp-flex align-items_center gap-8 bp-back_btn-wrapper bp-receipt-btn-deactivate">
										<button className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-view_receipt-btn">
											View Receipt
										</button>
										<button className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-send_receipt-btn">
											Send Receipt
										</button>
										<button className="bp-print_btn">
											<svg
												width="20"
												height="20"
												viewBox="0 0 20 20"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
											>
												<g clipPath="url(#clip0_5459_23151)">
													<path
														d="M14.1667 14.1667H15.8333C16.2754 14.1667 16.6993 13.9911 17.0118 13.6785C17.3244 13.366 17.5 12.942 17.5 12.5V9.16667C17.5 8.72464 17.3244 8.30072 17.0118 7.98816C16.6993 7.6756 16.2754 7.5 15.8333 7.5H4.16667C3.72464 7.5 3.30072 7.6756 2.98816 7.98816C2.67559 8.30072 2.5 8.72464 2.5 9.16667V12.5C2.5 12.942 2.67559 13.366 2.98816 13.6785C3.30072 13.9911 3.72464 14.1667 4.16667 14.1667H5.83333"
														stroke="#6B59EE"
														strokeWidth="1.3"
														strokeLinecap="round"
														strokeLinejoin="round"
													/>
													<path
														d="M14.1663 7.5V4.16667C14.1663 3.72464 13.9907 3.30072 13.6782 2.98816C13.3656 2.67559 12.9417 2.5 12.4997 2.5H7.49967C7.05765 2.5 6.63372 2.67559 6.32116 2.98816C6.0086 3.30072 5.83301 3.72464 5.83301 4.16667V7.5"
														stroke="#6B59EE"
														strokeWidth="1.3"
														strokeLinecap="round"
														strokeLinejoin="round"
													/>
													<path
														d="M5.83301 12.4987C5.83301 12.0567 6.0086 11.6327 6.32116 11.3202C6.63372 11.0076 7.05765 10.832 7.49967 10.832H12.4997C12.9417 10.832 13.3656 11.0076 13.6782 11.3202C13.9907 11.6327 14.1663 12.0567 14.1663 12.4987V15.832C14.1663 16.2741 13.9907 16.698 13.6782 17.0105C13.3656 17.3231 12.9417 17.4987 12.4997 17.4987H7.49967C7.05765 17.4987 6.63372 17.3231 6.32116 17.0105C6.0086 16.698 5.83301 16.2741 5.83301 15.832V12.4987Z"
														stroke="#6B59EE"
														strokeWidth="1.3"
														strokeLinecap="round"
														strokeLinejoin="round"
													/>
												</g>
												<defs>
													<clipPath id="clip0_5459_23151">
														<rect
															width="20"
															height="20"
															fill="white"
														/>
													</clipPath>
												</defs>
											</svg>
										</button>
									</div>
								</>,
								transaction,
								refererData
							)
						) }
					</div>

					{ /* A failed fetch used to leave every row blank with no message at
                        all — `error` was set and then never rendered. */ }
					{ error && ! loading ? (
						<div className="bp-transaction_details-wrapper">
							<div className="bp-data_table-no_data-wrapper">
								<div className="bp-data_table-no-data_img-wrapper">
									<h3 className="bp-font_ibm no-transaction-header">
										{ __(
											'Could not load this transaction',
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
															d="M9.58464 16.8849H2.45964C2.41547 16.8849 2.37297 16.8666 2.34214 16.8358C2.31047 16.8041 2.29297 16.7624 2.29297 16.7183C2.29297 16.7174 2.29297 15.5099 2.29297 15.5099C2.29297 14.8174 2.78714 14.2074 3.5488 13.6891C4.9088 12.7608 7.10964 12.1758 9.58464 12.1758C9.92964 12.1758 10.2096 11.8958 10.2096 11.5508C10.2096 11.2066 9.92964 10.9258 9.58464 10.9258C6.8163 10.9258 4.36547 11.6183 2.8438 12.6558C1.68297 13.4474 1.04297 14.4533 1.04297 15.5091V16.7183C1.04297 17.0933 1.19214 17.4541 1.45797 17.7199C1.7238 17.9849 2.0838 18.1349 2.45964 18.1349H9.58464C9.92964 18.1341 10.2096 17.8541 10.2096 17.5091C10.2096 17.1649 9.92964 16.8841 9.58464 16.8849Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M9.58333 1.04102C7.05333 1.04102 5 3.09435 5 5.62435C5 8.15435 7.05333 10.2077 9.58333 10.2077C12.1133 10.2077 14.1667 8.15435 14.1667 5.62435C14.1667 3.09435 12.1133 1.04102 9.58333 1.04102ZM9.58333 2.29102C11.4233 2.29102 12.9167 3.78435 12.9167 5.62435C12.9167 7.46435 11.4233 8.95768 9.58333 8.95768C7.74333 8.95768 6.25 7.46435 6.25 5.62435C6.25 3.78435 7.74333 2.29102 9.58333 2.29102Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M13.9609 14.582V16.6654C13.9609 17.0104 14.2409 17.2904 14.5859 17.2904C14.9309 17.2904 15.2109 17.0104 15.2109 16.6654V14.582C15.2109 14.237 14.9309 13.957 14.5859 13.957C14.2409 13.957 13.9609 14.237 13.9609 14.582Z"
															fill="#394357"
														/>
														<path
															d="M14.5833 13.5417C15.0436 13.5417 15.4167 13.1686 15.4167 12.7083C15.4167 12.2481 15.0436 11.875 14.5833 11.875C14.1231 11.875 13.75 12.2481 13.75 12.7083C13.75 13.1686 14.1231 13.5417 14.5833 13.5417Z"
															fill="#394357"
														/>
														<path
															fillRule="evenodd"
															clipRule="evenodd"
															d="M14.5859 10.207C12.1709 10.207 10.2109 12.167 10.2109 14.582C10.2109 16.997 12.1709 18.957 14.5859 18.957C17.0009 18.957 18.9609 16.997 18.9609 14.582C18.9609 12.167 17.0009 10.207 14.5859 10.207ZM14.5859 11.457C16.3109 11.457 17.7109 12.857 17.7109 14.582C17.7109 16.307 16.3109 17.707 14.5859 17.707C12.8609 17.707 11.4609 16.307 11.4609 14.582C11.4609 12.857 12.8609 11.457 14.5859 11.457Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													Basic Information
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Name:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{ formateName(
																	transaction?.form_fields_info
																) }
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Email:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-75"
														>
															<div className="bp-flex align-items_center">
																<p className="bp-information_value">
																	{ emailAddress(
																		transaction?.form_fields_info
																	) ||
																		transaction?.email }
																</p>
																{ ( emailAddress(
																	transaction?.form_fields_info
																) ||
																	transaction?.email ) && (
																	<button
																		className="bp-copy_btn bp-transaction_data-copy_btn"
																		onClick={ (
																			e
																		) =>
																			handleCopyEmail(
																				emailAddress(
																					transaction?.form_fields_info
																				) ||
																					transaction?.email,
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

									{ applyFilters(
										'better_payment_transaction_details_subscription_info',
										null,
										transaction
									) }

									<div className="bp-information_wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon personal_information-dark_icon">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<g clipPath="url(#clip0_5230_21418)">
															<path
																d="M2.5 10C2.5 10.9849 2.69399 11.9602 3.0709 12.8701C3.44781 13.7801 4.00026 14.6069 4.6967 15.3033C5.39314 15.9997 6.21993 16.5522 7.12987 16.9291C8.03982 17.306 9.01509 17.5 10 17.5C10.9849 17.5 11.9602 17.306 12.8701 16.9291C13.7801 16.5522 14.6069 15.9997 15.3033 15.3033C15.9997 14.6069 16.5522 13.7801 16.9291 12.8701C17.306 11.9602 17.5 10.9849 17.5 10C17.5 8.01088 16.7098 6.10322 15.3033 4.6967C13.8968 3.29018 11.9891 2.5 10 2.5C8.01088 2.5 6.10322 3.29018 4.6967 4.6967C3.29018 6.10322 2.5 8.01088 2.5 10Z"
																stroke="#394357"
																strokeWidth="1.5"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
															<path
																d="M10 7.5H10.0083"
																stroke="#394357"
																strokeWidth="2"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
															<path
																d="M9.16663 10H9.99996V13.3333H10.8333"
																stroke="#394357"
																strokeWidth="2"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
														</g>
														<defs>
															<clipPath id="clip0_5230_21418">
																<rect
																	width="20"
																	height="20"
																	fill="white"
																/>
															</clipPath>
														</defs>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													Additional Information
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Referrer Page
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														{ /* Referrer rows come from their own request, so they
                                                        shimmer on `loadingReferer`, not `loading` — the two
                                                        resolve independently and each fills in on its own. */ }
														<DetailValue
															loading={
																loadingReferer
															}
															width="wp-skeleton_w-75"
														>
															<p className="bp-information_value">
																<a
																	href={
																		refererData?.page_link
																	}
																	target="_blank"
																>
																	{
																		refererData?.page_title
																	}
																</a>
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Referrer Widget:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={
																loadingReferer
															}
															width="wp-skeleton_w-40"
														>
															<p className="bp-information_value">
																{
																	refererData?.widget_name
																}
															</p>
														</DetailValue>
													</div>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<div className="bp-transaction_details-right_content payment-gateway-wrapper">
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
														<g clipPath="url(#clip0_4527_17901)">
															<path
																d="M13.2 13.826C12.8819 13.826 12.624 14.0838 12.624 14.402C12.624 14.7201 12.8819 14.978 13.2 14.978V14.402V13.826ZM18 14.402L18.4073 14.8092C18.5153 14.7012 18.576 14.5547 18.576 14.402C18.576 14.2492 18.5153 14.1027 18.4073 13.9947L18 14.402ZM15.9927 15.5947C15.7678 15.8196 15.7678 16.1843 15.9927 16.4092C16.2176 16.6342 16.5824 16.6342 16.8073 16.4092L16.4 16.002L15.9927 15.5947ZM16.8073 12.3947C16.5824 12.1697 16.2176 12.1697 15.9927 12.3947C15.7678 12.6196 15.7678 12.9843 15.9927 13.2092L16.4 12.802L16.8073 12.3947ZM11.6 17.378C11.9181 17.378 12.176 17.1201 12.176 16.802C12.176 16.4838 11.9181 16.226 11.6 16.226V16.802V17.378ZM17.424 10.402C17.424 10.7201 17.6819 10.978 18 10.978C18.3181 10.978 18.576 10.7201 18.576 10.402H18H17.424ZM8.4 14.178C8.71812 14.178 8.976 13.9201 8.976 13.602C8.976 13.2838 8.71812 13.026 8.4 13.026V13.602V14.178ZM5.2 13.026C4.88188 13.026 4.624 13.2838 4.624 13.602C4.624 13.9201 4.88188 14.178 5.2 14.178V13.602V13.026ZM10.8 14.178C11.1181 14.178 11.376 13.9201 11.376 13.602C11.376 13.2838 11.1181 13.026 10.8 13.026V13.602V14.178ZM10.4 13.026C10.0819 13.026 9.824 13.2838 9.824 13.602C9.824 13.9201 10.0819 14.178 10.4 14.178V13.602V13.026ZM2 8.22595C1.68188 8.22595 1.424 8.48384 1.424 8.80195C1.424 9.12007 1.68188 9.37795 2 9.37795V8.80195V8.22595ZM18 9.37795C18.3181 9.37795 18.576 9.12007 18.576 8.80195C18.576 8.48384 18.3181 8.22595 18 8.22595V8.80195V9.37795ZM13.2 14.402V14.978H18V14.402V13.826H13.2V14.402ZM18 14.402L17.5927 13.9947L15.9927 15.5947L16.4 16.002L16.8073 16.4092L18.4073 14.8092L18 14.402ZM18 14.402L18.4073 13.9947L16.8073 12.3947L16.4 12.802L15.9927 13.2092L17.5927 14.8092L18 14.402ZM8.4 4.00195V4.57795H11.6V4.00195V3.42595H8.4V4.00195ZM11.6 16.802V16.226H8.4V16.802V17.378H11.6V16.802ZM8.4 16.802V16.226C6.87522 16.226 5.78958 16.2247 4.96548 16.1139C4.15795 16.0054 3.68832 15.8012 3.34455 15.4574L2.93726 15.8647L2.52996 16.272C3.12345 16.8655 3.8767 17.1299 4.81198 17.2557C5.73069 17.3792 6.90779 17.378 8.4 17.378V16.802ZM2 10.402H1.424C1.424 11.8942 1.42278 13.0713 1.54629 13.99C1.67204 14.9252 1.93648 15.6785 2.52996 16.272L2.93726 15.8647L3.34455 15.4574C3.00078 15.1136 2.79659 14.644 2.68802 13.8365C2.57722 13.0124 2.576 11.9267 2.576 10.402H2ZM11.6 4.00195V4.57795C13.1248 4.57795 14.2104 4.57918 15.0345 4.68997C15.8421 4.79854 16.3117 5.00273 16.6554 5.3465L17.0627 4.93921L17.47 4.53192C16.8765 3.93843 16.1233 3.67399 15.188 3.54825C14.2693 3.42473 13.0922 3.42595 11.6 3.42595V4.00195ZM18 10.402H18.576C18.576 8.90974 18.5772 7.73264 18.4537 6.81393C18.328 5.87866 18.0635 5.12541 17.47 4.53192L17.0627 4.93921L16.6554 5.3465C16.9992 5.69028 17.2034 6.1599 17.312 6.96743C17.4228 7.79154 17.424 8.87717 17.424 10.402H18ZM8.4 4.00195V3.42595C6.90779 3.42595 5.73069 3.42473 4.81198 3.54825C3.8767 3.67399 3.12345 3.93843 2.52996 4.53192L2.93726 4.93921L3.34455 5.3465C3.68832 5.00273 4.15795 4.79854 4.96548 4.68997C5.78958 4.57918 6.87522 4.57795 8.4 4.57795V4.00195ZM2 10.402H2.576C2.576 8.87717 2.57722 7.79154 2.68802 6.96743C2.79659 6.1599 3.00078 5.69028 3.34455 5.3465L2.93726 4.93921L2.52996 4.53192C1.93648 5.12541 1.67204 5.87866 1.54629 6.81393C1.42278 7.73264 1.424 8.90974 1.424 10.402H2ZM8.4 13.602V13.026H5.2V13.602V14.178H8.4V13.602ZM10.8 13.602V13.026H10.4V13.602V14.178H10.8V13.602ZM2 8.80195V9.37795H18V8.80195V8.22595H2V8.80195Z"
																fill="#394357"
															/>
														</g>
														<defs>
															<clipPath id="clip0_4527_17901">
																<rect
																	width="20"
																	height="20"
																	fill="white"
																/>
															</clipPath>
														</defs>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													Payment Geteway
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="payment-gateway_wrapper bp-information_list">
												{ /* Transaction ID is the one row here that is gated on the data
                                                itself, so unlike its neighbours it renders nothing at all
                                                in flight. This stands in for it; the two can never both
                                                show, since `transaction` is null exactly while loading. */ }
												{ loading && (
													<li className="bp-row align-items_center bp-information_item">
														<div className=" bp-col_4 bp-information_key-wrapper">
															<h4 className="bp-font_ibm bp-information_key">
																Transaction ID:
															</h4>
														</div>
														<div className=" bp-col_8 bp-information_value-wrapper">
															<div className="wp-skeleton wp-skeleton_cell wp-skeleton_w-75"></div>
														</div>
													</li>
												) }
												{ /* <li className='bp-row align-items_center bp-information_item'>
                                                <div className=" bp-col_4 bp-information_key-wrapper">
                                                    <h4 className="bp-font_ibm bp-information_key">Payment Method: </h4>
                                                </div>
                                                <div className=" bp-col_8 bp-information_value-wrapper">
                                                    <p className="bp-information_value">Yes</p>
                                                </div>
                                            </li> */ }
												{ transaction?.transaction_id && (
													<li className="bp-row align-items_center bp-information_item">
														<div className=" bp-col_4 bp-information_key-wrapper">
															<h4 className="bp-font_ibm bp-information_key">
																Transaction ID:
															</h4>
														</div>
														<div className=" bp-col_8 bp-information_value-wrapper">
															<div className="bp-flex align-items_center">
																<p className="bp-information_value">
																	{
																		transaction?.transaction_id
																	}
																</p>
																<button
																	className=" bp-transaction_data-copy_btn bp-copy_btn"
																	onClick={ (
																		e
																	) =>
																		handleCopyTransactionId(
																			transaction?.transaction_id,
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
															</div>
														</div>
													</li>
												) }
												{ transaction?.form_fields_info
													?.is_coupon_applied ? (
													<>
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	Subtotal:
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className="bp-information_value">
																	{ transaction
																		?.form_fields_info
																		?.paid_amount_diff +
																		transaction
																			?.form_fields_info
																			?.exact_paid_amount }{ ' ' }
																	{
																		transaction?.currency
																	}
																</p>
															</div>
														</li>

														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	Discount:
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className="bp-information_value">
																	{
																		transaction
																			?.form_fields_info
																			?.paid_amount_diff
																	}{ ' ' }
																	{
																		transaction?.currency
																	}
																</p>
															</div>
														</li>
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	Coupon Code:
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className=" bp-information_value">
																	{
																		transaction
																			?.form_fields_info
																			?.coupon_code
																	}
																</p>
															</div>
														</li>
													</>
												) : null }
												{ transaction?.form_fields_info
													?.amount_quantity &&
												! transaction?.form_fields_info
													?.is_woo_layout ? (
													<>
														<li className="bp-row align-items_center bp-information_item">
															<div className=" bp-col_4 bp-information_key-wrapper">
																<h4 className="bp-font_ibm bp-information_key">
																	Single
																	Amount:
																</h4>
															</div>
															<div className=" bp-col_8 bp-information_value-wrapper">
																<p className="bp-font_ibm bp-information_value bp-total_amount-hilight ">
																	{
																		transaction
																			?.form_fields_info
																			?.exact_paid_amount
																	}{ ' ' }
																	{
																		transaction?.currency
																	}
																</p>
															</div>
														</li>
													</>
												) : (
													<li className="bp-row align-items_center bp-information_item">
														<div className=" bp-col_4 bp-information_key-wrapper">
															<h4 className="bp-font_ibm bp-information_key">
																Total Amount:
															</h4>
														</div>
														<div className=" bp-col_8 bp-information_value-wrapper">
															<DetailValue
																loading={
																	loading
																}
																width="wp-skeleton_w-40"
															>
																<p className="bp-font_ibm bp-information_value bp-total_amount-hilight ">
																	{
																		transaction?.amount
																	}{ ' ' }
																	{
																		transaction?.currency
																	}
																</p>
															</DetailValue>
														</div>
													</li>
												) }
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Source:{ ' ' }
														</h4>
													</div>
													<div className=" bp-col_8 line-height_0 bp-information_value-wrapper ">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<div className="bp-information_source-logo">
																{ transaction?.source?.toLowerCase() ===
																'paypal' ? (
																	<Paypal />
																) : transaction?.source?.toLowerCase() ===
																  'stripe' ? (
																	<Stripe />
																) : transaction?.source?.toLowerCase() ===
																  'paystack' ? (
																	<Paystack />
																) : null }
															</div>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Status:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														{ /* Also stops the `bp-undefined-tag` class the status badge
                                                        rendered while `transaction` was still null. */ }
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-40"
														>
															<div className="bp-flex gap-8 align-items_center bp-status-wrapper ">
																<span
																	className={ `bp-information_value bp-${ statusLabel?.toLowerCase() }-tag` }
																>
																	{
																		statusLabel
																	}
																</span>
																{ canMarkTransactionComplete(
																	transaction
																) && (
																	<a
																		href="#"
																		className="bp-information_value bp-mark-tag"
																		onClick={
																			handleMarkAsCompleted
																		}
																		disabled={
																			isMarkingComplete
																		}
																	>
																		{ isMarkingComplete
																			? 'Marking...'
																			: 'Mark as Completed' }
																	</a>
																) }
																{ /* <span className="bp-information_value bp-mark-tag"> Mark As Complete </span> */ }
															</div>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Order ID:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-60"
														>
															<p className="bp-information_value">
																{
																	transaction?.order_id
																}
															</p>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Payment Date:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-60"
														>
															<p className="bp-information_value">
																{ formatDateTime(
																	transaction?.payment_date
																) ||
																	transaction?.payment_date }
															</p>
														</DetailValue>
													</div>
												</li>
												{ transaction?.campaign_id && (
													<li className="bp-row align-items_center bp-information_item">
														<div className=" bp-col_4 bp-information_key-wrapper">
															<h4 className="bp-font_ibm bp-information_key">
																Campaign ID:
															</h4>
														</div>
														<div className=" bp-col_8 bp-information_value-wrapper">
															<p className="bp-information_value">
																{
																	transaction?.campaign_id
																}
															</p>
														</div>
													</li>
												) }
											</ul>
										</div>
									</div>

									<div className="bp-information_wrapper email-activity-wrapper">
										<div className="margin-bottom_24 bp-information_header-wrapper ">
											<div className="bp-flex align-items_center ">
												<span className="line-height_0 bp-personal_information-icon  ">
													<svg
														width="20"
														height="20"
														viewBox="0 0 20 20"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															d="M18.2422 2.96875H1.75781C0.790547 2.96875 0 3.75582 0 4.72656V15.2734C0 16.2445 0.791055 17.0312 1.75781 17.0312H18.2422C19.2095 17.0312 20 16.2442 20 15.2734V4.72656C20 3.75559 19.2091 2.96875 18.2422 2.96875ZM17.9723 4.14062C17.4039 4.71082 10.7283 11.4082 10.4541 11.6834C10.225 11.9131 9.77512 11.9133 9.54594 11.6834L2.02773 4.14062H17.9723ZM1.17188 15.058V4.94199L6.21348 10L1.17188 15.058ZM2.02773 15.8594L7.04078 10.83L8.71598 12.5107C9.40246 13.1994 10.5978 13.1991 11.2841 12.5107L12.9593 10.83L17.9723 15.8594H2.02773ZM18.8281 15.058L13.7865 10L18.8281 4.94199V15.058Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-personal_information-title">
													Email Activity
												</h4>
											</div>
										</div>
										<div className="bp-information_list-wrppaer">
											<ul className="bp-information_list">
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Email Sent To:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-75"
														>
															<div className="bp-flex align-items_center">
																<p className="bp-information_value">
																	{ emailAddress(
																		transaction?.form_fields_info
																	) ||
																		transaction?.email }
																</p>
															</div>
														</DetailValue>
													</div>
												</li>
												<li className="bp-row align-items_center bp-information_item">
													<div className=" bp-col_4 bp-information_key-wrapper">
														<h4 className="bp-font_ibm bp-information_key">
															Sent Time:
														</h4>
													</div>
													<div className=" bp-col_8 bp-information_value-wrapper">
														<DetailValue
															loading={ loading }
															width="wp-skeleton_w-60"
														>
															<p className="bp-information_value">
																{ formatDateTime(
																	transaction?.payment_date
																) ||
																	transaction?.payment_date }
															</p>
														</DetailValue>
													</div>
												</li>
											</ul>
										</div>
									</div>

									{ applyFilters(
										'better_payment_transaction_details_get_pro_card',
										<GetProCard />,
										transaction,
										refreshTransaction
									) }
								</div>
							</div>
						</div>
					) }
					<FooterCards />
				</div>
			</div>
		</>
	);
};

export default TransactionDetails;
