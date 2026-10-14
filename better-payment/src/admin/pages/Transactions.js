import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { transactionsAPI } from './../utils/api';
import bpPaypalLogo from './../images/bp-paypal.svg';
import stripeLogo from './../images/st.svg';
import paystackLogo from './../images/pay.svg';
import {
	formatDateTime,
	formateName,
	paymentStatus,
	emailAddress,
	siteTimezone,
} from './../utils/helper';
import { TransactionsSearchBar } from './TransactionsSearchBar';
import ImportFile from './ImportFile';
import DeleteConfirm from './DeleteConfirm';
import FilterDropdown from './../components/FilterDropdown';
import DateRangeFilter from './../components/DateRangeFilter';
import Pagination from 'rc-pagination';
import TransactionDetails from './TransactionDetails';
import FooterCards from '../components/settings/FooterCards';

import illustration from './../images/illustration.webp';
import illustrationdark from './../images/illustrationdark.webp';
import locale from './../utils/locale/en-US';
import { scrollAdminToTop, useListScrollRestore } from '../utils/scroll';

// Rows the list requests, and therefore rows the skeleton draws. One constant so
// the placeholder can't promise more rows than the request can return.
const PER_PAGE = 20;
const SKELETON_ROWS = PER_PAGE;

// The table's column widths, in source order, matching the `bp-width_*` classes
// on the real header row below. The skeleton reuses them so every shimmer cell
// lands under its own column and the table fills in place instead of the whole
// block redrawing. Keep in sync with the header `<li>`.
const TABLE_COLUMN_WIDTHS = [
	'156',
	'226',
	'96',
	'116',
	'116',
	'214',
	'114',
	'200',
];

const Transactions = () => {
	const bpAdmin = window.betterPaymentAdmin;
	const [ transactions, setTransactions ] = useState( [] );
	const [ transactionCount, setTransactionCount ] = useState( {
		all: 0,
		completed: 0,
		incomplete: 0,
	} );
	const [ exporting, setExporting ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	// The three stat cards come from their own request, so they get their own
	// flag — tying them to `loading` would blank them on every filter change,
	// and the counts are unfiltered totals that never change with the filters.
	const [ countLoading, setCountLoading ] = useState( true );
	// payment_date is UTC; it is shown in the site's timezone (offset sites included).
	const timezone = siteTimezone();
	const [ isImportModalOpen, setIsImportModalOpen ] = useState( false );
	const allCurrencies = window?.betterPaymentAdmin?.currencies || {};
	const [ currencyOptions, setCurrencyOptions ] = useState( [
		{ label: 'All', value: 'all' },
	] );

	useEffect( () => {
		// Convert currencies object to array of currency codes
		const currenciesArray = Object.values( allCurrencies );
		if ( currenciesArray?.length > 0 ) {
			const newOptions = currenciesArray.map( ( currency ) => ( {
				label: currency,
				value: currency,
			} ) );
			setCurrencyOptions( [
				{ label: 'All', value: 'all' },
				...newOptions,
			] );
		}
	}, [] );

	const [ filters, setFilters ] = useState( {
		search_text: '',
		payment_date_from: '',
		payment_date_to: '',
		source: '',
		currency: 'all',
		status: 'all',
		order_by: 'payment_date',
		order: 'DESC',
		paged: 1,
		per_page: PER_PAGE,
	} );

	// Pagination state
	const [ paginationData, setPaginationData ] = useState( {
		total: 0,
		pages: 0,
		currentPage: 1,
	} );

	// Dropdown visibility state
	const [ openDropdown, setOpenDropdown ] = useState( null );

	// Transaction row menu state
	const [ openTransactionMenu, setOpenTransactionMenu ] = useState( null );
	const [ menuPosition, setMenuPosition ] = useState( { top: 0, right: 0 } );

	// Delete confirmation modal state
	const [ isDeleteModalOpen, setIsDeleteModalOpen ] = useState( false );
	const [ transactionToDelete, setTransactionToDelete ] = useState( null );
	const [ isDeleting, setIsDeleting ] = useState( false );

	// View mode state - track if viewing transaction details
	const [ viewingTransactionId, setViewingTransactionId ] = useState( null );
	const listScroll = useListScrollRestore( {
		showingList: ! viewingTransactionId,
		ready: ! loading,
	} );

	useEffect( () => {
		document.title = __(
			'Transactions - Better Payment',
			'better-payment'
		);
		transactionsAPI
			.getTransactionCount()
			.then( ( data ) => {
				setTransactionCount(
					data?.count || { all: 0, completed: 0, incomplete: 0 }
				);
			} )
			.catch( () => {
				// Leave the seeded zeroes rather than shimmering forever.
			} )
			.finally( () => {
				setCountLoading( false );
			} );
	}, [] );

	// Check URL parameters for view action
	useEffect( () => {
		const params = new URLSearchParams( window.location.search );
		const action = params.get( 'action' );
		const id = params.get( 'id' );

		if ( action === 'view' && id ) {
			setViewingTransactionId( parseInt( id, 10 ) );
		} else {
			setViewingTransactionId( null );
		}
	}, [] );

	// Handle outside click to close transaction menu
	useEffect( () => {
		const handleOutsideClick = ( event ) => {
			// Check if click is outside any transaction menu
			const settingWrappers = document.querySelectorAll(
				'.bp-data_setting-wrapper'
			);
			const moreButtons = document.querySelectorAll(
				'.bp-transactions_list-table_header-more-btn'
			);

			let clickedOutside = true;

			settingWrappers.forEach( ( wrapper ) => {
				if ( wrapper.contains( event.target ) ) {
					clickedOutside = false;
				}
			} );

			moreButtons.forEach( ( button ) => {
				if ( button.contains( event.target ) ) {
					clickedOutside = false;
				}
			} );

			if ( clickedOutside && openTransactionMenu !== null ) {
				setOpenTransactionMenu( null );
			}
		};

		if ( openTransactionMenu !== null ) {
			document.addEventListener( 'click', handleOutsideClick );
			return () => {
				document.removeEventListener( 'click', handleOutsideClick );
			};
		}
	}, [ openTransactionMenu ] );

	// A `position: fixed` menu is anchored to viewport coordinates captured when
	// it opened, so anything that scrolls leaves it stranded next to the wrong
	// row. Close it instead of trying to track the anchor — `capture: true` is
	// required because the list itself scrolls (`.bp-transactions_list-table` is
	// `overflow: auto`) and scroll events from it do not bubble to window.
	useEffect( () => {
		if ( openTransactionMenu === null ) return;

		const close = () => setOpenTransactionMenu( null );

		window.addEventListener( 'scroll', close, true );
		window.addEventListener( 'resize', close );
		return () => {
			window.removeEventListener( 'scroll', close, true );
			window.removeEventListener( 'resize', close );
		};
	}, [ openTransactionMenu ] );

	useEffect( () => {
		setLoading( true );
		transactionsAPI
			.getTransactions( filters )
			.then( ( data ) => {
				setTransactions( data?.transactions || [] );
				// Extract pagination data from API response
				setPaginationData( {
					total: data?.total || 0,
					pages: data?.pages || 0,
					currentPage: data?.page || 1,
				} );
			} )
			.catch( () => {
				setTransactions( [] );
				setPaginationData( { total: 0, pages: 0, currentPage: 1 } );
				toast.error(
					__( 'Could not load transactions.', 'better-payment' )
				);
			} )
			.finally( () => {
				// `finally`, not the `then` branch — a rejected request used to leave
				// `loading` true forever, pinning the skeleton on screen with no error.
				setLoading( false );
			} );
	}, [ filters ] );

	// How many shimmer rows the table draws. A first load has no better estimate
	// than the page size — SKELETON_ROWS matches per_page, because promising more
	// rows than the request can return is its own layout shift. On a refetch
	// (filter, sort, page) the rows already on screen are the better estimate:
	// drawing exactly that many holds the table at the height it already has, so
	// the swap moves nothing.
	const skeletonRowCount =
		transactions?.length > 0 ? transactions.length : SKELETON_ROWS;

	// Handle filter changes
	const handleFilterChange = ( filterName, value ) => {
		setFilters( {
			...filters,
			[ filterName ]: value,
			paged: 1, // Reset to first page when filter changes
		} );
	};

	// Handle search filter
	const handleSearch = ( searchText ) => {
		handleFilterChange( 'search_text', searchText );
	};

	// Handle status filter
	const handleStatusChange = ( status ) => {
		handleFilterChange( 'status', status );
	};

	// Handle source filter
	const handleSourceChange = ( source ) => {
		handleFilterChange( 'source', source );
	};

	// Handle date range filter
	const handleDateRangeChange = ( dateFrom, dateTo ) => {
		setFilters( {
			...filters,
			payment_date_from: dateFrom,
			payment_date_to: dateTo,
			paged: 1,
		} );
	};

	// Handle sorting
	const handleSort = ( orderBy ) => {
		// If clicking the same column, toggle order direction
		if ( filters.order_by === orderBy ) {
			handleFilterChange(
				'order',
				filters.order === 'DESC' ? 'ASC' : 'DESC'
			);
		} else {
			setFilters( {
				...filters,
				order_by: orderBy,
				order: 'DESC',
				paged: 1,
			} );
		}
	};

	// Handle per page change
	const handlePerPageChange = ( perPage ) => {
		const perPageValue = perPage === 'All' ? -1 : parseInt( perPage );
		handleFilterChange( 'per_page', perPageValue );
	};

	// Handle pagination change
	const handlePaginationChange = ( page ) => {
		setFilters( {
			...filters,
			paged: page,
		} );
	};

	// Handle reset filters
	const handleResetFilters = () => {
		setFilters( {
			search_text: '',
			payment_date_from: '',
			payment_date_to: '',
			source: '',
			currency: 'all',
			status: 'all',
			order_by: 'payment_date',
			order: 'DESC',
			paged: 1,
			per_page: 20,
		} );
		setOpenDropdown( null );
	};

	// Toggle dropdown visibility
	const toggleDropdown = ( dropdownName ) => {
		setOpenDropdown( openDropdown === dropdownName ? null : dropdownName );
	};

	// Close all dropdowns
	const closeAllDropdowns = () => {
		setOpenDropdown( null );
	};

	// Toggle transaction row menu
	// The menu is `position: fixed` so it escapes `.bp-transactions_list-table`'s
	// `overflow: auto`, which used to clip it — that is why it appeared to slide
	// under the rows below. Fixed positioning is relative to the viewport, so the
	// anchor button's viewport rect has to be measured here and applied inline;
	// CSS alone cannot know where the row is.
	const toggleTransactionMenu = ( transactionId, e ) => {
		if ( openTransactionMenu === transactionId ) {
			setOpenTransactionMenu( null );
			return;
		}

		const rect = e.currentTarget.getBoundingClientRect();
		setMenuPosition( {
			top: rect.bottom + 4,
			right: window.innerWidth - rect.right,
		} );
		setOpenTransactionMenu( transactionId );
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

	// Check if any filters are active
	const hasActiveFilters = () => {
		return (
			filters.search_text !== '' ||
			filters.payment_date_from !== '' ||
			filters.payment_date_to !== '' ||
			filters.source !== '' ||
			filters.currency !== 'all' ||
			filters.status !== 'all'
		);
	};

	const handleExportAll = () => {
		setExporting( true );

		// Check if any filters are active
		const hasFilters = hasActiveFilters();

		// Make AJAX request to export endpoint
		const formData = new FormData();
		formData.append( 'action', 'better-payment-transactions-export' );
		formData.append( 'nonce', bpAdmin?.nonce );

		// If filters are active, send them to backend
		if ( hasFilters ) {
			formData.append( 'filtered', '1' );
			formData.append( 'search_text', filters.search_text );
			formData.append( 'payment_date_from', filters.payment_date_from );
			formData.append( 'payment_date_to', filters.payment_date_to );
			formData.append( 'source', filters.source );
			formData.append(
				'currency',
				filters.currency === 'all' ? '' : filters.currency
			);
			formData.append(
				'status',
				filters.status === 'all' ? '' : filters.status
			);
		}

		fetch( bpAdmin?.ajaxUrl, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.text() )
			.then( ( csvData ) => {
				// Create blob and download
				const blob = new Blob( [ '\ufeff' + csvData ], {
					type: 'text/csv;charset=utf-8;',
				} );
				const link = document.createElement( 'a' );
				const url = URL.createObjectURL( blob );
				link.setAttribute( 'href', url );
				const filename = hasFilters
					? 'better_payment_transactions_filtered.csv'
					: 'better_payment_transactions.csv';
				link.setAttribute( 'download', filename );
				link.style.visibility = 'hidden';
				document.body.appendChild( link );
				link.click();
				document.body.removeChild( link );
				setExporting( false );
			} )
			.catch( ( error ) => {
				console.error( 'Export error:', error );
				setExporting( false );
			} );
	};

	const handleImportClick = () => {
		setIsImportModalOpen( true );
	};

	const handleCloseImportModal = () => {
		setIsImportModalOpen( false );
	};

	// Handle delete button click - open confirmation modal
	const handleDeleteClick = ( transactionId ) => {
		setTransactionToDelete( transactionId );
		setIsDeleteModalOpen( true );
		setOpenTransactionMenu( null ); // Close the menu
	};

	// Handle delete confirmation
	const handleConfirmDelete = async () => {
		if ( ! transactionToDelete ) return;

		setIsDeleting( true );
		try {
			// Call REST API to delete transaction
			await transactionsAPI.deleteTransaction( transactionToDelete );

			// Close modal
			setIsDeleteModalOpen( false );
			setTransactionToDelete( null );

			// Show success message using react-toastify
			toast.success(
				__( 'Transaction deleted successfully!', 'better-payment' )
			);

			// Refetch transactions data from server
			const transactionsData =
				await transactionsAPI.getTransactions( filters );
			setTransactions( transactionsData?.transactions || [] );
			setPaginationData( {
				total: transactionsData?.total || 0,
				pages: transactionsData?.pages || 0,
				currentPage: transactionsData?.page || 1,
			} );

			// Refetch transaction count from server
			const countData = await transactionsAPI.getTransactionCount();
			setTransactionCount(
				countData?.count || { all: 0, completed: 0, incomplete: 0 }
			);
		} catch ( error ) {
			console.error( 'Delete error:', error );

			// Show error message using react-toastify
			const errorMessage =
				error.message ||
				__( 'Failed to delete transaction', 'better-payment' );
			toast.error( errorMessage );
		} finally {
			setIsDeleting( false );
		}
	};

	// Handle delete modal close
	const handleCloseDeleteModal = () => {
		if ( ! isDeleting ) {
			setIsDeleteModalOpen( false );
			setTransactionToDelete( null );
		}
	};

	// Handle view transaction
	const handleViewTransaction = ( transactionId ) => {
		const url = new URL( window.location );
		url.searchParams.set( 'tab', 'transactions' );
		url.searchParams.set( 'action', 'view' );
		url.searchParams.set( 'id', transactionId );
		window.history.pushState( {}, '', url );
		listScroll.remember();
		scrollAdminToTop(); // the details view replaces the list — open it at the top
		setViewingTransactionId( transactionId );
		setOpenTransactionMenu( null ); // Close the menu
	};

	// Handle back from transaction details
	const handleBackFromDetails = () => {
		const url = new URL( window.location );
		url.searchParams.delete( 'action' );
		url.searchParams.delete( 'id' );
		window.history.pushState( {}, '', url );
		listScroll.requestRestore();
		setViewingTransactionId( null );
	};

	const handleCurrencyChange = ( currency ) => {
		handleFilterChange( 'currency', currency );
	};

	// If viewing a specific transaction, show the details component
	if ( viewingTransactionId ) {
		return (
			<TransactionDetails
				transactionId={ viewingTransactionId }
				onBack={ handleBackFromDetails }
			/>
		);
	}

	return (
		<>
			<div className="tab-content bp-tab_active" id="transactions">
				<div className="section  bp-transactions_page">
					<div className="bp-container">
						<div className="bp-row align-items_center justify-content-space_between bp-transactions_header-section">
							<div className="bp-col bp-col_6">
								<h4 className="bp-font_inter fx-2 bp-transactions_header">
									Transactions
								</h4>
							</div>
							<div className="bp-col bp-col_6 bp-flex justify-content_flex-end">
								<div className="bp-flex">
									<button
										className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-import_data-btn"
										onClick={ handleImportClick }
									>
										{ __(
											'Import Data',
											'better-payment'
										) }
									</button>
									<button
										className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-export_all-btn"
										onClick={ handleExportAll }
										disabled={ exporting }
									>
										{ exporting
											? __(
													'Exporting...',
													'better-payment'
											  )
											: hasActiveFilters()
											? __(
													'Export Filtered Data',
													'better-payment'
											  )
											: __(
													'Export All Data',
													'better-payment'
											  ) }
									</button>
								</div>
							</div>
						</div>
						<div className="bp-transactions_section">
							<div className="bp-row bp-transaction_section">
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--chrome bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												Total Transactions
											</div>
										</div>

										{ /* Only the amount waits on data — the colour dot and the
                                            label above are static, so they paint on the first frame. */ }
										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{ transactionCount?.all }
												</h2>
											) }
										</div>

										{ /* <div className="bp-flex align-items_center">
                                            <span className="line-height_0">
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <g clipPath="url(#clip0_5507_19340)">
                                                        <path
                                                            d="M2 11.3327L6 7.33268L8.66667 9.99935L14 4.66602"
                                                            stroke="#0E9F6E"
                                                            strokeWidth="1.5"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                        />
                                                        <path
                                                            d="M9.33301 4.66602H13.9997V9.33268"
                                                            stroke="#0E9F6E"
                                                            strokeWidth="1.5"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                        />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_5507_19340">
                                                            <rect width="16" height="16" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>

                                            </span>
                                            <p className="fx-6 bp-font_ibm bp-transaction_lavel-text" >
                                                <span className="bp-transaction_lavel bp-transaction_lavel-upto_0">20%</span>
                                                <span>VS last 30 days</span>
                                            </p>
                                        </div> */ }
									</div>
								</div>
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--green bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												Completed Transactions
											</div>
										</div>

										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{
														transactionCount?.completed
													}
												</h2>
											) }
										</div>

										{ /* <div className="bp-flex align-items_center">
                                            <span className="line-height_0">
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <g clipPath="url(#clip0_5507_19340)">
                                                        <path
                                                            d="M2 11.3327L6 7.33268L8.66667 9.99935L14 4.66602"
                                                            stroke="#0E9F6E"
                                                            strokeWidth="1.5"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                        />
                                                        <path
                                                            d="M9.33301 4.66602H13.9997V9.33268"
                                                            stroke="#0E9F6E"
                                                            strokeWidth="1.5"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                        />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_5507_19340">
                                                            <rect width="16" height="16" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>

                                            </span>
                                            <p className="fx-6 bp-font_ibm bp-transaction_lavel-text" >
                                                <span className="bp-transaction_lavel bp-transaction_lavel-upto_0">20%</span>
                                                <span>VS last 30 days</span>
                                            </p>
                                        </div> */ }
									</div>
								</div>
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--yellow bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												Incomplete Transactions
											</div>
										</div>

										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{
														transactionCount?.incomplete
													}
												</h2>
											) }
										</div>

										{ /* <div className="bp-flex align-items_center">
                                            <span className="line-height_0">
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path
                                                        d="M3.19995 9.20078L5.59995 7.20078L7.59995 8.80078L10.4 6.80078L12.8 8.80078"
                                                        stroke="#6A758C"
                                                        strokeWidth="1.5"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                </svg>

                                            </span>
                                            <p className="fx-6 bp-font_ibm bp-transaction_lavel-text" >
                                                <span className="bp-transaction_lavel ">0%</span>
                                                <span>VS last 30 days</span>
                                            </p>
                                        </div> */ }
									</div>
								</div>
							</div>
						</div>
						<div className="bp-transactions_list-section">
							<div>
								<div className="bp-transactions_list-section_header">
									<div className="bp-row align-items_center">
										<div className="bp-col bp-col_2">
											<div className="bp-flex align-items_center bp-transactions_list-section_header-wrapper">
												<span className="line-height_0 bp-list_section-icon">
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
															d="M13.2975 1.79758C12.9463 1.4482 12.4706 1.25195 11.975 1.25195C10.6231 1.25195 6.9475 1.25195 5 1.25195C3.96438 1.25195 3.125 2.09133 3.125 3.12695V15.8401C3.125 16.5613 3.53875 17.2188 4.18938 17.5307C4.84 17.8426 5.61125 17.7538 6.17375 17.3019L6.48375 17.0532C6.7125 16.8701 7.0375 16.8701 7.26625 17.0532L8.8275 18.3069C9.5125 18.8569 10.4869 18.8576 11.1731 18.3088L12.7356 17.0588C12.9637 16.8763 13.2875 16.8757 13.5156 17.0582L13.8394 17.3163C14.4025 17.7657 15.1731 17.8526 15.8225 17.5401C16.4712 17.2276 16.8838 16.5707 16.8838 15.8507V6.14445C16.8838 5.6457 16.685 5.16758 16.3313 4.8157L13.2975 1.79758ZM11.25 2.50195H5C4.655 2.50195 4.375 2.78133 4.375 3.12695V15.8401C4.375 16.0807 4.51312 16.2995 4.73 16.4038C4.94687 16.5076 5.20375 16.4782 5.39125 16.3276C5.49875 16.2413 5.6025 16.1576 5.70125 16.0788C6.38687 15.5282 7.36313 15.5282 8.04875 16.0788L9.61 17.3326C9.83813 17.5157 10.1631 17.5157 10.3919 17.3332L11.9544 16.0826C12.6387 15.5351 13.6106 15.5345 14.2956 16.0807L14.6194 16.3388C14.8069 16.4888 15.0637 16.5176 15.28 16.4138C15.4962 16.3094 15.6338 16.0907 15.6338 15.8507V6.87507H13.125C12.6275 6.87507 12.1506 6.67758 11.7994 6.3257C11.4475 5.97445 11.25 5.49757 11.25 5.00007V2.50195ZM11.7131 14.7957L13.5669 12.9419C13.7456 12.7632 13.7994 12.4945 13.7025 12.2607C13.6056 12.0276 13.3775 11.8751 13.125 11.8751H6.875C6.53 11.8751 6.25 12.1551 6.25 12.5001C6.25 12.8451 6.53 13.1251 6.875 13.1251H11.6162L10.8294 13.912C10.585 14.1557 10.585 14.552 10.8294 14.7957C11.0731 15.0401 11.4694 15.0401 11.7131 14.7957ZM8.28688 7.70445L6.43312 9.5582C6.25437 9.73695 6.20063 10.0057 6.2975 10.2394C6.39438 10.4726 6.6225 10.6251 6.875 10.6251H13.125C13.47 10.6251 13.75 10.3451 13.75 10.0001C13.75 9.65507 13.47 9.37507 13.125 9.37507H8.38375L9.17063 8.5882C9.415 8.34445 9.415 7.9482 9.17063 7.70445C8.92688 7.46007 8.53063 7.46007 8.28688 7.70445ZM12.5 2.76695V5.00007C12.5 5.1657 12.5656 5.32507 12.6831 5.44195C12.8 5.55945 12.9594 5.62507 13.125 5.62507H15.3725L12.5 2.76695Z"
															fill="#394357"
														/>
													</svg>
												</span>
												<h4 className="fx-3 bp-font_ibm bp-transactions_list-section_header-title">
													All Transactions
												</h4>
											</div>
										</div>
										<div className="bp-col bp-col_10">
											<div className="bp-flex align-items_center bp-transactions_list-section_header-content">
												<TransactionsSearchBar
													onSearch={ handleSearch }
													value={
														filters.search_text
													}
												/>

												<DateRangeFilter
													dateFrom={
														filters.payment_date_from
													}
													dateTo={
														filters.payment_date_to
													}
													onChange={
														handleDateRangeChange
													}
													isOpen={
														openDropdown ===
														'dateRange'
													}
													onToggle={ () =>
														toggleDropdown(
															'dateRange'
														)
													}
													onClose={
														closeAllDropdowns
													}
												/>

												<FilterDropdown
													label="Status"
													value={ filters.status }
													options={ [
														{
															label: 'All',
															value: 'all',
														},
														{
															label: 'Completed',
															value: 'completed',
														},
														{
															label: 'Incomplete',
															value: 'incomplete',
														},
														{
															label: 'Refunded',
															value: 'refunded',
														},
													] }
													onChange={
														handleStatusChange
													}
													isOpen={
														openDropdown ===
														'status'
													}
													onToggle={ () =>
														toggleDropdown(
															'status'
														)
													}
													onClose={
														closeAllDropdowns
													}
												/>

												<FilterDropdown
													label="Source"
													value={ filters.source }
													options={ [
														{
															label: 'All',
															value: '',
														},
														{
															label: 'PayPal',
															value: 'paypal',
														},
														{
															label: 'Stripe',
															value: 'stripe',
														},
														{
															label: 'Paystack',
															value: 'paystack',
														},
													] }
													onChange={
														handleSourceChange
													}
													isOpen={
														openDropdown ===
														'source'
													}
													onToggle={ () =>
														toggleDropdown(
															'source'
														)
													}
													onClose={
														closeAllDropdowns
													}
												/>

												<FilterDropdown
													label="Currency"
													value={ filters.currency }
													options={ currencyOptions }
													onChange={
														handleCurrencyChange
													}
													isOpen={
														openDropdown ===
														'currency'
													}
													onToggle={ () =>
														toggleDropdown(
															'currency'
														)
													}
													onClose={
														closeAllDropdowns
													}
												/>

												<button
													className="bp-flex align-items_center gap-8 bp-transactions_list-form-reset-btn"
													onClick={
														handleResetFilters
													}
													type="button"
												>
													<span className="ine-height_0">
														<svg
															width="20"
															height="20"
															viewBox="0 0 20 20"
															fill="none"
															xmlns="http://www.w3.org/2000/svg"
														>
															<path
																d="M2.55005 10.8338C2.75456 12.662 3.62388 14.3513 4.99266 15.5804C6.36145 16.8095 8.13423 17.4927 9.97385 17.5C11.8135 17.5072 13.5916 16.8382 14.9701 15.6199C16.3486 14.4017 17.2312 12.7194 17.4502 10.8928C17.6692 9.06626 17.2093 7.22292 16.1578 5.71338C15.1064 4.20384 13.5368 3.13339 11.7476 2.7056C9.95837 2.27782 8.07435 2.52255 6.45381 3.39325C4.83327 4.26395 3.58927 5.69988 2.95838 7.42796"
																stroke="#6B59EE"
																strokeWidth="1.2"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
															<path
																d="M2.5 3.33398V7.50065H6.66667"
																stroke="#6B59EE"
																strokeWidth="1.2"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
														</svg>
													</span>

													<span className="bp-font_inter fx-4 reset-btn-title">
														Reset
													</span>
												</button>
											</div>
										</div>
									</div>
								</div>
								{ /* The table renders whenever it is loading OR has rows, so the
                                    real header row, sort controls and column widths stay mounted
                                    and only the body fills in. The previous
                                    `loading && transactions?.length === 0` guard meant a refilter
                                    or page change with rows already on screen showed no
                                    placeholder at all — stale rows just sat there until the swap.
                                    The empty state below is now only for a settled empty result. */ }
								{ loading || transactions?.length > 0 ? (
									<div
										className="bp-transactions_list-table"
										role={ loading ? 'status' : undefined }
										aria-busy={
											loading ? 'true' : undefined
										}
										aria-label={
											loading
												? __(
														'Loading transactions…',
														'better-payment'
												  )
												: undefined
										}
									>
										<ul className="bp-transactions_list-table_wrapper">
											<li className="bp-transactions_list-table_header">
												<div className="bp-flex align-items_center ">
													<div className="bp-width_156">
														<div className="bp-flex align-items_center ">
															{ /* <input type='checkbox' className='bp-transactions_table-list_check' /> */ }
															<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title margin-left-24">
																Name
															</h4>
														</div>
													</div>
													<div className="bp-flex align-items_center gap-12 bp-width_226">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Email
														</h4>
														<div className="line-height_0 bp-flex bp-up-down-btns-wrapper">
															<button
																className="line-height_0 bp-table-up-btn"
																onClick={ () =>
																	handleSort(
																		'email'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M8.59961 3.45703L4.59983 0.599887L0.600055 3.45703"
																		stroke={
																			filters.order_by ===
																				'email' &&
																			filters.order ===
																				'ASC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
															<button
																className="line-height_0 bp-table-down-btn"
																onClick={ () =>
																	handleSort(
																		'email'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M0.600098 0.599609L4.59988 3.45675L8.59965 0.599611"
																		stroke={
																			filters.order_by ===
																				'email' &&
																			filters.order ===
																				'DESC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
														</div>
													</div>
													<div className="bp-flex align-items_center gap-12 bp-width_96">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Amount
														</h4>
														<div className="line-height_0 bp-flex bp-up-down-btns-wrapper">
															<button
																className="line-height_0 bp-table-up-btn"
																onClick={ () =>
																	handleSort(
																		'amount'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M8.59961 3.45703L4.59983 0.599887L0.600055 3.45703"
																		stroke={
																			filters.order_by ===
																				'amount' &&
																			filters.order ===
																				'ASC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
															<button
																className="line-height_0 bp-table-down-btn"
																onClick={ () =>
																	handleSort(
																		'amount'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M0.600098 0.599609L4.59988 3.45675L8.59965 0.599611"
																		stroke={
																			filters.order_by ===
																				'amount' &&
																			filters.order ===
																				'DESC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
														</div>
													</div>
													<div className="bp-width_116">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Type
														</h4>
													</div>
													<div className="bp-width_116">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Source
														</h4>
													</div>
													<div className="bp-width_214">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Transaction ID
														</h4>
													</div>
													<div className="bp-width_114">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Status
														</h4>
													</div>
													<div className="bp-flex align-items_center gap-12 bp-width_200">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															Date
														</h4>
														<div className="line-height_0 bp-flex bp-up-down-btns-wrapper">
															<button
																className="line-height_0 bp-table-up-btn"
																onClick={ () =>
																	handleSort(
																		'payment_date'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M8.59961 3.45703L4.59983 0.599887L0.600055 3.45703"
																		stroke={
																			filters.order_by ===
																				'payment_date' &&
																			filters.order ===
																				'ASC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
															<button
																className="line-height_0 bp-table-down-btn"
																onClick={ () =>
																	handleSort(
																		'payment_date'
																	)
																}
																type="button"
															>
																<svg
																	width="10"
																	height="5"
																	viewBox="0 0 10 5"
																	fill="none"
																	xmlns="http://www.w3.org/2000/svg"
																>
																	<path
																		d="M0.600098 0.599609L4.59988 3.45675L8.59965 0.599611"
																		stroke={
																			filters.order_by ===
																				'payment_date' &&
																			filters.order ===
																				'DESC'
																				? '#6B59EE'
																				: '#B9BFCA'
																		}
																		strokeWidth="1.2"
																		strokeLinecap="round"
																		strokeLinejoin="round"
																	/>
																</svg>
															</button>
														</div>
													</div>
												</div>
											</li>
											{ loading
												? Array.from(
														{
															length: skeletonRowCount,
														},
														( _, row ) => (
															<li
																className="bp-transactions_list-table_data"
																key={ row }
															>
																<div className="bp-flex align-items_center ">
																	{ TABLE_COLUMN_WIDTHS.map(
																		(
																			width,
																			col
																		) => (
																			<div
																				className={ `bp-width_${ width }` }
																				key={
																					col
																				}
																			>
																				<div className="wp-skeleton wp-skeleton_cell"></div>
																			</div>
																		)
																	) }
																</div>
															</li>
														)
												  )
												: transactions.map(
														(
															transaction,
															index
														) => {
															const transactionId = `transaction-${ index }`;
															const isMenuOpen =
																openTransactionMenu ===
																transactionId;
															return (
																<li
																	className="bp-transactions_list-table_data"
																	key={
																		index
																	}
																>
																	<div className="bp-flex align-items_center ">
																		<div className="bp-width_156">
																			<div className="bp-flex align-items_center ">
																				{ /* <input type='checkbox' className='bp-transactions_table-list_check' /> */ }
																				{ transaction
																					?.form_fields_info
																					?.is_imported && (
																					<button className="line-height_0 wp-import-btn">
																						<svg
																							width="16"
																							height="16"
																							viewBox="0 0 16 16"
																							fill="none"
																							xmlns="http://www.w3.org/2000/svg"
																						>
																							<path
																								fillRule="evenodd"
																								clipRule="evenodd"
																								d="M2.27273 2.85714C2.27273 2.51526 2.55764 2.2381 2.90909 2.2381H5.56373C5.7325 2.2381 5.89436 2.30332 6.01371 2.41941L7.55003 3.91393C7.66935 4.03002 7.83124 4.09524 8 4.09524H13.0909C13.4424 4.09524 13.7273 4.3724 13.7273 4.71429V12.1429C13.7273 12.4848 13.4424 12.7619 13.0909 12.7619H2.90909C2.55764 12.7619 2.27273 12.4848 2.27273 12.1429V2.85714ZM2.90909 1C1.85473 1 1 1.83147 1 2.85714V12.1429C1 13.1686 1.85473 14 2.90909 14H13.0909C14.1453 14 15 13.1686 15 12.1429V4.71429C15 3.68862 14.1453 2.85714 13.0909 2.85714H8.26358L6.91366 1.54394C6.55563 1.19566 6.07005 1 5.56373 1H2.90909ZM8 5.95238C8.35146 5.95238 8.63636 6.22954 8.63636 6.57143V9.41026L9.45912 8.60989C9.70762 8.36815 10.1106 8.36815 10.3591 8.60989C10.6076 8.85163 10.6076 9.24361 10.3591 9.48535L8.44997 11.3425C8.20147 11.5842 7.79853 11.5842 7.55003 11.3425L5.64093 9.48535C5.39242 9.24361 5.39242 8.85163 5.64093 8.60989C5.88945 8.36815 6.29237 8.36815 6.54089 8.60989L7.36364 9.41026V6.57143C7.36364 6.22954 7.64854 5.95238 8 5.95238Z"
																								fill="#B9BFCA"
																							/>
																						</svg>
																					</button>
																				) }
																				{ formateName(
																					transaction?.form_fields_info
																				) && (
																					<h4
																						className="fx-4 bp-font_ibm bp-transactions_list-table_header-title bp-transaction_name"
																						onClick={ () =>
																							handleViewTransaction(
																								transaction.id
																							)
																						}
																					>
																						{ formateName(
																							transaction?.form_fields_info
																						) }
																					</h4>
																				) }
																			</div>
																		</div>
																		<div className="bp-width_226 bp-transaction_id">
																			<div className="bp-flex align-items_center ">
																				<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					{ emailAddress(
																						transaction?.form_fields_info
																					) ||
																						transaction?.email }
																				</h4>
																				{ ( emailAddress(
																					transaction?.form_fields_info
																				) ||
																					transaction?.email ) && (
																					<span className="line-height_0 bp-transaction_id-icon">
																						<button
																							className="bp-transaction_data-copy_btn"
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
																					</span>
																				) }
																			</div>
																		</div>
																		<div className="bp-width_96">
																			<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																				{ transaction?.currency +
																					' ' +
																					transaction?.amount }
																			</h4>
																		</div>
																		<div className="bp-width_116">
																			<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																				{ transaction
																					?.form_fields_info
																					?.subscription_id
																					? 'Subscription'
																					: 'One Time' }
																			</h4>
																		</div>
																		<div className="bp-width_116">
																			<div className="bp-flex align-items_center ">
																				<span className="line-height_0 bp-list_section-payment-method-icon">
																					{ transaction?.source?.toLowerCase() ===
																					'paypal' ? (
																						<img
																							src={
																								bpPaypalLogo
																							}
																						/>
																					) : transaction?.source?.toLowerCase() ===
																					  'stripe' ? (
																						<img
																							src={
																								stripeLogo
																							}
																						/>
																					) : transaction?.source?.toLowerCase() ===
																					  'paystack' ? (
																						<img
																							src={
																								paystackLogo
																							}
																						/>
																					) : null }
																				</span>
																				<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					{ transaction?.source
																						?.charAt(
																							0
																						)
																						.toUpperCase() +
																						transaction?.source?.slice(
																							1
																						) }
																				</h4>
																			</div>
																		</div>
																		<div className="bp-width_214 bp-transaction_id">
																			<div className="bp-flex align-items_center ">
																				<p className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					{
																						transaction?.transaction_id
																					}
																				</p>
																				{ transaction?.transaction_id && (
																					<span className="line-height_0 bp-transaction_id-icon">
																						<button
																							className="bp-transaction_data-copy_btn"
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
																					</span>
																				) }
																			</div>
																		</div>
																		<div className="bp-width_114">
																			<span
																				className={ `fx-4 bp-font_ibm  tag ${
																					transaction?.status
																						? paymentStatus(
																								transaction?.status,
																								transaction?.status_type
																						  )?.toLowerCase()
																						: 'incomplete'
																				}` }
																			>
																				{ paymentStatus(
																					transaction?.status,
																					transaction?.status_type
																				) }
																			</span>
																		</div>
																		<div className="bp-width_200 position-relative">
																			<div className="bp-flex align-items_center justify-content-space_between ">
																				<span className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					{ formatDateTime(
																						transaction?.payment_date,
																						{
																							timezone:
																								timezone,
																						}
																					) }
																				</span>
																				<button
																					className={ `bp-transactions_list-table_header-more-btn ${
																						isMenuOpen
																							? 'bp-transactions_more-btn_active'
																							: ''
																					}` }
																					onClick={ (
																						e
																					) =>
																						toggleTransactionMenu(
																							transactionId,
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
																						<g clipPath="url(#clip0_4527_17667)">
																							<path
																								d="M9.16602 10.0007C9.16602 10.2217 9.25381 10.4336 9.41009 10.5899C9.56637 10.7462 9.77834 10.834 9.99935 10.834C10.2204 10.834 10.4323 10.7462 10.5886 10.5899C10.7449 10.4336 10.8327 10.2217 10.8327 10.0007C10.8327 9.77964 10.7449 9.56768 10.5886 9.4114C10.4323 9.25512 10.2204 9.16732 9.99935 9.16732C9.77834 9.16732 9.56637 9.25512 9.41009 9.4114C9.25381 9.56768 9.16602 9.77964 9.16602 10.0007Z"
																								stroke="#8F9AB0"
																								strokeWidth="2"
																								strokeLinecap="round"
																								strokeLinejoin="round"
																							/>
																							<path
																								d="M9.16602 15.834C9.16602 16.055 9.25381 16.267 9.41009 16.4232C9.56637 16.5795 9.77834 16.6673 9.99935 16.6673C10.2204 16.6673 10.4323 16.5795 10.5886 16.4232C10.7449 16.267 10.8327 16.055 10.8327 15.834C10.8327 15.613 10.7449 15.401 10.5886 15.2447C10.4323 15.0884 10.2204 15.0007 9.99935 15.0007C9.77834 15.0007 9.56637 15.0884 9.41009 15.2447C9.25381 15.401 9.16602 15.613 9.16602 15.834Z"
																								stroke="#8F9AB0"
																								strokeWidth="2"
																								strokeLinecap="round"
																								strokeLinejoin="round"
																							/>
																							<path
																								d="M9.16602 4.16732C9.16602 4.38833 9.25381 4.60029 9.41009 4.75657C9.56637 4.91285 9.77834 5.00065 9.99935 5.00065C10.2204 5.00065 10.4323 4.91285 10.5886 4.75657C10.7449 4.60029 10.8327 4.38833 10.8327 4.16732C10.8327 3.9463 10.7449 3.73434 10.5886 3.57806C10.4323 3.42178 10.2204 3.33398 9.99935 3.33398C9.77834 3.33398 9.56637 3.42178 9.41009 3.57806C9.25381 3.73434 9.16602 3.9463 9.16602 4.16732Z"
																								stroke="#8F9AB0"
																								strokeWidth="2"
																								strokeLinecap="round"
																								strokeLinejoin="round"
																							/>
																						</g>
																						<defs>
																							<clipPath id="clip0_4527_17667">
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

																			<div
																				className={ `bp-data_setting-wrapper ${
																					isMenuOpen
																						? 'settiong-wrapper-active'
																						: ''
																				}` }
																				style={
																					isMenuOpen
																						? {
																								top: menuPosition.top,
																								right: menuPosition.right,
																						  }
																						: {}
																				}
																			>
																				<ul className="bp-data_setting-box">
																					{ /* <li className="bp-flex align-items_center bp-data_setting-list_item">
                                                                        <span className="bp-data_setting-logo">
                                                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                                                xmlns="http://www.w3.org/2000/svg">
                                                                                <g clipPath="url(#clip0_2871_11366)">
                                                                                    <path
                                                                                        d="M3.39846 4.73457H2.56178C2.11798 4.73457 1.69236 4.91087 1.37854 5.22468C1.06473 5.5385 0.888428 5.96412 0.888428 6.40792V13.938C0.888428 14.3818 1.06473 14.8075 1.37854 15.1213C1.69236 15.4351 2.11798 15.6114 2.56178 15.6114H10.0919C10.5357 15.6114 10.9613 15.4351 11.2751 15.1213C11.5889 14.8075 11.7652 14.3818 11.7652 13.938V13.1013M10.9286 3.06382L13.4386 5.57386M14.5977 4.38874C14.9273 4.05922 15.1124 3.61229 15.1124 3.14627C15.1124 2.68026 14.9273 2.23333 14.5977 1.90381C14.2682 1.57428 13.8213 1.38916 13.3553 1.38916C12.8892 1.38916 12.4423 1.57428 12.1128 1.90381L5.07215 8.91935V11.4294H7.58218L14.5977 4.38874Z"
                                                                                        strokeWidth="1.06667" strokeLinecap="round"
                                                                                        strokeLinejoin="round" />
                                                                                </g>
                                                                                <defs>
                                                                                    <clipPath id="clip0_2871_11366">
                                                                                        <rect width="16" height="16" fill="white"
                                                                                            transform="translate(0 0.5)" />
                                                                                    </clipPath>
                                                                                </defs>
                                                                            </svg>

                                                                        </span>
                                                                        <span className="bp-font_ibm fx-4 bp-data_setting-name">Edit</span>
                                                                    </li> */ }
																					<li
																						className="bp-flex align-items_center bp-data_setting-list_item"
																						onClick={ () =>
																							handleViewTransaction(
																								transaction.id
																							)
																						}
																						style={ {
																							cursor: 'pointer',
																						} }
																					>
																						<span className="bp-data_setting-logo">
																							<svg
																								width="16"
																								height="17"
																								viewBox="0 0 16 17"
																								fill="none"
																								xmlns="http://www.w3.org/2000/svg"
																							>
																								<path
																									d="M6.41947 8.79371C6.41947 9.21281 6.58596 9.61475 6.88232 9.91111C7.17867 10.2075 7.58061 10.374 7.99972 10.374C8.41883 10.374 8.82077 10.2075 9.11712 9.91111C9.41348 9.61475 9.57996 9.21281 9.57996 8.79371C9.57996 8.3746 9.41348 7.97266 9.11712 7.6763C8.82077 7.37995 8.41883 7.21346 7.99972 7.21346C7.58061 7.21346 7.17867 7.37995 6.88232 7.6763C6.58596 7.97266 6.41947 8.3746 6.41947 8.79371Z"
																									strokeWidth="1.13778"
																									strokeLinecap="round"
																									strokeLinejoin="round"
																								/>
																								<path
																									d="M15.1111 8.79494C13.2148 11.9554 10.8445 13.5357 8.00003 13.5357C5.15558 13.5357 2.78521 11.9554 0.888916 8.79494C2.78521 5.63445 5.15558 4.0542 8.00003 4.0542C10.8445 4.0542 13.2148 5.63445 15.1111 8.79494Z"
																									strokeWidth="1.13778"
																									strokeLinecap="round"
																									strokeLinejoin="round"
																								/>
																							</svg>
																						</span>
																						<span className="bp-font_ibm fx-4 bp-data_setting-name">
																							View
																						</span>
																					</li>
																					<li
																						className="bp-flex align-items_center bp-data_setting-list_item"
																						onClick={ () =>
																							handleDeleteClick(
																								transaction.id
																							)
																						}
																						style={ {
																							cursor: 'pointer',
																						} }
																					>
																						<span className="bp-data_setting-logo">
																							<svg
																								width="16"
																								height="17"
																								viewBox="0 0 16 17"
																								fill="none"
																								xmlns="http://www.w3.org/2000/svg"
																							>
																								<g clipPath="url(#clip0_2871_11374)">
																									<path
																										d="M1.77783 4.54821H14.4209M6.51898 7.71144V12.4526M9.68037 7.71144V12.4526M2.56864 4.54821L3.35883 14.0305C3.35883 14.4497 3.52534 14.8516 3.82172 15.148C4.1181 15.4444 4.52007 15.6109 4.93922 15.6109H11.2608C11.6799 15.6109 12.0819 15.4444 12.3783 15.148C12.6746 14.8516 12.8411 14.4497 12.8411 14.0305L13.6313 4.54821M5.7291 4.54944V2.17886C5.7291 1.96929 5.81235 1.7683 5.96054 1.62011C6.10873 1.47192 6.30972 1.38867 6.51929 1.38867H9.68006C9.88963 1.38867 10.0906 1.47192 10.2388 1.62011C10.387 1.7683 10.4703 1.96929 10.4703 2.17886V4.54944"
																										strokeWidth="1.06667"
																										strokeLinecap="round"
																										strokeLinejoin="round"
																									/>
																								</g>
																								<defs>
																									<clipPath id="clip0_2871_11374">
																										<rect
																											width="16"
																											height="16"
																											fill="white"
																											transform="translate(0 0.5)"
																										/>
																									</clipPath>
																								</defs>
																							</svg>
																						</span>
																						<span className="bp-font_ibm fx-4 bp-data_setting-name">
																							{ __(
																								'Delete',
																								'better-payment'
																							) }
																						</span>
																					</li>
																				</ul>
																			</div>
																		</div>
																	</div>
																</li>
															);
														}
												  ) }
										</ul>
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
												{ __(
													'No Transactions',
													'better-payment'
												) }
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
							<div className="bp-flex align-items_center justify-content-space_between bp-pagination-wrapper ">
								{ transactions?.length >= 10 && (
									<div className="bp-pagination-per-page">
										<FilterDropdown
											label="All"
											value={
												filters.per_page === -1
													? transactionCount.all.toString()
													: filters.per_page.toString()
											}
											options={ [
												{ label: '10', value: '10' },
												{ label: '20', value: '20' },
												{ label: '50', value: '50' },
												{ label: '100', value: '100' },
												{
													label: 'All',
													value: transactionCount.all.toString(),
												},
											] }
											onChange={ handlePerPageChange }
											isOpen={
												openDropdown === 'perPage'
											}
											onToggle={ () =>
												toggleDropdown( 'perPage' )
											}
											onClose={ closeAllDropdowns }
										/>
									</div>
								) }
								{ /* Show pagination only if there are multiple pages */ }
								{ paginationData.pages > 1 && (
									<Pagination
										current={ paginationData.currentPage }
										total={ paginationData.total }
										pageSize={
											filters.per_page === -1
												? paginationData.total
												: filters.per_page
										}
										onChange={ handlePaginationChange }
										className="bp-pagination-pages"
										locale={ locale }
									/>
								) }
							</div>
						</div>
						<FooterCards />
					</div>
				</div>
			</div>
			<>
				<ImportFile
					isOpen={ isImportModalOpen }
					onClose={ handleCloseImportModal }
				/>
				<DeleteConfirm
					isOpen={ isDeleteModalOpen }
					onClose={ handleCloseDeleteModal }
					onConfirm={ handleConfirmDelete }
					isDeleting={ isDeleting }
				/>
			</>
		</>
	);
};

export default Transactions;
