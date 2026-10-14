import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { subscriptionsAPI } from './../utils/api';
import {
	formatDateCompact,
	ecommerceSubscriptionStatus,
	ecommerceStatusOptions,
} from './../utils/helper';
import FilterDropdown from './../components/FilterDropdown';
import DateRangeFilter from './../components/DateRangeFilter';
import { TransactionsSearchBar } from './TransactionsSearchBar';
import Pagination from 'rc-pagination';
import FooterCards from '../components/settings/FooterCards';
import SubscriptionDetails from './SubscriptionDetails';
import DeleteConfirm from './DeleteConfirm';

import illustration from './../images/illustration.webp';
import illustrationdark from './../images/illustrationdark.webp';
import locale from './../utils/locale/en-US';
import { scrollAdminToTop, useListScrollRestore } from '../utils/scroll';

// Rows the list requests, and therefore rows the skeleton draws — same contract
// as the Transactions tab (see Transactions.js).
const PER_PAGE = 20;
const SKELETON_ROWS = PER_PAGE;

// The table's column widths, in source order, matching the `bp-width_*` classes
// on the real header row below. The skeleton reuses them so every shimmer cell
// lands under its own column. Keep in sync with the header `<li>` AND with the
// `#subscriptions … min-width` rule in styles/admin.scss.
//
// ONLY the width utilities defined app-wide in utilities/_base.scss may be
// used here: 96, 114, 116, 156, 200, 214, 226. The others that appear in the
// codebase (122, 124, 146, 164, 228) live inside a #transactions-nested scope
// in _common.scss and are UNDEFINED on this page — a cell using one silently
// auto-sizes to its own text, so header and data columns drift apart. That
// exact bug shipped here once.
// Widths are flex BASES, not hard widths: `#subscriptions` allows the cells
// to shrink uniformly (with single-line ellipsis) when the viewport is
// narrower than their sum, so the table never scrolls horizontally.
const TABLE_COLUMN_WIDTHS = [
	'156',
	'200',
	'116',
	'156',
	'114',
	'200',
	'200',
	'96',
];

// The Status dropdown's "no filter" value. FilterDropdown renders an option
// labelled 'All' as the inactive state (the trigger shows the filter's name),
// and the server reads 'all' as "no status filter".
const DEFAULT_STATUS = 'all';

// The dropdown's options come from the same map that renders the status tags in
// the table (utils/helper.js) — never a second hardcoded list here. That map is
// the wider set: it also labels statuses the filter deliberately does not offer
// (see `nonFilterableStatuses`).
const STATUS_OPTIONS = ecommerceStatusOptions();

const Subscriptions = () => {
	const [ subscriptions, setSubscriptions ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	// The whole request, in one object — the fetch effect below re-runs on any
	// new identity, so a filter change, a page change and a post-mutation
	// refetch are all "hand setFilters a new object". Same shape as
	// Transactions.js. `status: 'all'` and `search_text: ''` are sent as
	// ordinary params rather than omitted; the server reads both as "no
	// filter" (Admin\SubscriptionListFilter) and keeps its fast SQL-paginated
	// path, so an unfiltered pageview costs exactly what it did before
	// filtering existed.
	const [ filters, setFilters ] = useState( {
		paged: 1,
		per_page: PER_PAGE,
		status: DEFAULT_STATUS,
		search_text: '',
		// The date range narrows the "Started" column, so the params are named
		// for it rather than borrowed from the Transactions tab's
		// payment_date_* (see Admin\SubscriptionListFilter::DATE_KEY). Empty
		// means "no bound", and either end may be open.
		start_date_from: '',
		start_date_to: '',
	} );

	// Pagination state
	const [ paginationData, setPaginationData ] = useState( {
		total: 0,
		pages: 0,
		currentPage: 1,
	} );

	// Stat-card counts (Total / Active / Cancelled) — unfiltered totals over
	// the whole relation table, fetched separately from the paged list (same
	// split as Transactions.js: the cards never change with pagination, so
	// only the numbers wait on this request).
	const [ subscriptionCount, setSubscriptionCount ] = useState( {
		all: 0,
		active: 0,
		cancelled: 0,
	} );
	const [ countLoading, setCountLoading ] = useState( true );

	// Refetched after any mutation that can change a status or remove a row:
	// delete here, cancel/reactivate on the details page (via the back
	// handler). apiRequest swallows failures into `undefined`, so a failed
	// request settles the cards at their previous values (zeros on load)
	// rather than pinning the skeleton.
	const fetchSubscriptionCount = () => {
		subscriptionsAPI
			.getSubscriptionCount()
			.then( ( data ) => {
				if ( data?.count ) {
					setSubscriptionCount( data.count );
				}
			} )
			.finally( () => {
				setCountLoading( false );
			} );
	};

	// Dropdown visibility state (per-page selector)
	const [ openDropdown, setOpenDropdown ] = useState( null );

	// Row actions menu state — same fixed-position pattern as Transactions.js:
	// the menu escapes the table's `overflow: auto` via `position: fixed`, so
	// the trigger's viewport rect is measured on open and applied inline.
	const [ openActionsMenu, setOpenActionsMenu ] = useState( null );
	const [ menuPosition, setMenuPosition ] = useState( { top: 0, right: 0 } );

	// Details view state — same swap-in-place pattern as Transactions.js's
	// viewingTransactionId, but a subscription is identified by (id, source):
	// the same numeric id can exist under two sources.
	const [ viewingSubscription, setViewingSubscription ] = useState( null );
	const listScroll = useListScrollRestore( {
		showingList: ! viewingSubscription,
		ready: ! loading,
	} );

	// Delete flow state (confirm modal + in-flight flag).
	const [ subscriptionToDelete, setSubscriptionToDelete ] = useState( null );
	const [ isDeleteModalOpen, setIsDeleteModalOpen ] = useState( false );
	const [ isDeleting, setIsDeleting ] = useState( false );

	useEffect( () => {
		document.title = __(
			'Subscriptions - Better Payment',
			'better-payment'
		);
		fetchSubscriptionCount();
	}, [] );

	useEffect( () => {
		setLoading( true );
		subscriptionsAPI
			.getSubscriptions( filters )
			.then( ( data ) => {
				setSubscriptions( data?.subscriptions || [] );
				setPaginationData( {
					total: data?.total || 0,
					pages: data?.pages || 0,
					currentPage: data?.page || 1,
				} );
			} )
			.catch( () => {
				setSubscriptions( [] );
				setPaginationData( { total: 0, pages: 0, currentPage: 1 } );
				toast.error(
					__( 'Could not load subscriptions.', 'better-payment' )
				);
			} )
			.finally( () => {
				// `finally`, not the `then` branch — a rejected request must not
				// pin the skeleton on screen (same lesson as Transactions.js).
				setLoading( false );
			} );
	}, [ filters ] );

	// Close the actions menu on any outside click.
	useEffect( () => {
		if ( openActionsMenu === null ) {
			return;
		}

		const handleOutsideClick = ( event ) => {
			const insideMenu = event.target.closest(
				'.bp-subscriptions_col-actions, .bp-transactions_list-table_header-more-btn'
			);
			if ( ! insideMenu ) {
				setOpenActionsMenu( null );
			}
		};

		document.addEventListener( 'click', handleOutsideClick );
		return () => {
			document.removeEventListener( 'click', handleOutsideClick );
		};
	}, [ openActionsMenu ] );

	// A fixed-position menu is anchored to viewport coordinates captured when
	// it opened; close it on scroll/resize instead of tracking the anchor.
	// `capture: true` because the table itself scrolls (`overflow: auto`) and
	// its scroll events do not bubble to window.
	useEffect( () => {
		if ( openActionsMenu === null ) {
			return;
		}

		const close = () => setOpenActionsMenu( null );

		window.addEventListener( 'scroll', close, true );
		window.addEventListener( 'resize', close );
		return () => {
			window.removeEventListener( 'scroll', close, true );
			window.removeEventListener( 'resize', close );
		};
	}, [ openActionsMenu ] );

	// First load draws a page-size skeleton; a refetch draws exactly the rows
	// already on screen so the swap moves nothing — same as Transactions.js.
	const skeletonRowCount =
		subscriptions?.length > 0 ? subscriptions.length : SKELETON_ROWS;

	// Any filter change also returns to page 1 — page 4 of an unfiltered list
	// is usually past the end of the filtered one, and answering a fresh
	// filter with an empty table reads as "no matches" when there are plenty.
	const handleFilterChange = ( filterName, value ) => {
		setFilters( {
			...filters,
			[ filterName ]: value,
			paged: 1,
		} );
	};

	// Debounced by TransactionsSearchBar itself (200 ms), so this fires once
	// the user stops typing rather than per keystroke.
	const handleSearch = ( searchText ) => {
		handleFilterChange( 'search_text', searchText );
	};

	const handleStatusChange = ( status ) => {
		handleFilterChange( 'status', status );
	};

	// Both ends land in one state update — handleFilterChange sets a single key
	// and two calls would race on the same stale `filters` object, applying only
	// the second.
	const handleDateRangeChange = ( dateFrom, dateTo ) => {
		setFilters( {
			...filters,
			start_date_from: dateFrom,
			start_date_to: dateTo,
			paged: 1,
		} );
	};

	// Whether the list on screen is narrowed — drives the Reset button's
	// active state and which empty state is shown. Mirrors the server's own
	// reading of these params (SubscriptionListFilter::is_filtered).
	const hasActiveFilters = () =>
		filters.status !== DEFAULT_STATUS ||
		filters.search_text !== '' ||
		filters.start_date_from !== '' ||
		filters.start_date_to !== '';

	// Resets the FILTERS only: per_page is the user's page-size preference,
	// not a filter, and silently resetting it would be a second surprise.
	const handleResetFilters = () => {
		setFilters( {
			...filters,
			status: DEFAULT_STATUS,
			search_text: '',
			start_date_from: '',
			start_date_to: '',
			paged: 1,
		} );
		setOpenDropdown( null );
	};

	// Handle per page change
	const handlePerPageChange = ( perPage ) => {
		const perPageValue = perPage === 'All' ? -1 : parseInt( perPage );
		setFilters( {
			...filters,
			per_page: perPageValue,
			paged: 1,
		} );
	};

	// Handle pagination change
	const handlePaginationChange = ( page ) => {
		setFilters( {
			...filters,
			paged: page,
		} );
	};

	// Toggle dropdown visibility
	const toggleDropdown = ( dropdownName ) => {
		setOpenDropdown( openDropdown === dropdownName ? null : dropdownName );
	};

	// Close all dropdowns
	const closeAllDropdowns = () => {
		setOpenDropdown( null );
	};

	const toggleActionsMenu = ( menuId, e ) => {
		if ( openActionsMenu === menuId ) {
			setOpenActionsMenu( null );
			return;
		}

		const rect = e.currentTarget.getBoundingClientRect();
		setMenuPosition( {
			top: rect.bottom + 4,
			right: window.innerWidth - rect.right,
		} );
		setOpenActionsMenu( menuId );
	};

	// Open the details view (URL params mirror the Transactions tab so the
	// browser back button and reloads behave the same way there and here).
	const handleViewEdit = ( subscription ) => {
		const url = new URL( window.location );
		url.searchParams.set( 'tab', 'subscriptions' );
		url.searchParams.set( 'action', 'view' );
		url.searchParams.set( 'id', subscription?.subscription_id );
		url.searchParams.set( 'source', subscription?.source );
		window.history.pushState( {}, '', url );
		listScroll.remember();
		scrollAdminToTop(); // the details view replaces the list — open it at the top
		setViewingSubscription( {
			id: subscription?.subscription_id,
			source: subscription?.source,
		} );
		setOpenActionsMenu( null );
	};

	const handleBackFromDetails = () => {
		const url = new URL( window.location );
		url.searchParams.delete( 'action' );
		url.searchParams.delete( 'id' );
		url.searchParams.delete( 'source' );
		window.history.pushState( {}, '', url );
		listScroll.requestRestore();
		setViewingSubscription( null );
		// The details page offers cancel/reactivate — refetch so the list
		// and the stat cards reflect any status change made there.
		// `loading` goes true in the same render the list returns in, so the
		// scroll restore waits for the fresh rows instead of the stale ones.
		setLoading( true );
		setFilters( ( previous ) => ( { ...previous } ) );
		fetchSubscriptionCount();
	};

	const handleDelete = ( subscription ) => {
		setSubscriptionToDelete( subscription );
		setIsDeleteModalOpen( true );
		setOpenActionsMenu( null );
	};

	const handleCloseDeleteModal = () => {
		if ( ! isDeleting ) {
			setIsDeleteModalOpen( false );
			setSubscriptionToDelete( null );
		}
	};

	const handleConfirmDelete = async () => {
		if ( ! subscriptionToDelete ) {
			return;
		}

		setIsDeleting( true );
		try {
			await subscriptionsAPI.deleteSubscription(
				subscriptionToDelete.subscription_id,
				subscriptionToDelete.source
			);

			setIsDeleteModalOpen( false );
			setSubscriptionToDelete( null );

			toast.success(
				__( 'Subscription deleted successfully!', 'better-payment' )
			);

			// Refetch by refreshing the filters object — the fetch effect
			// re-runs on any new filters identity. The stat cards refetch
			// too (one subscription just left the totals).
			setFilters( ( previous ) => ( { ...previous } ) );
			fetchSubscriptionCount();
		} catch ( error ) {
			toast.error(
				error?.message ||
					__( 'Failed to delete subscription', 'better-payment' )
			);
		} finally {
			setIsDeleting( false );
		}
	};

	// '—' placeholder for anything an un-hydrated row can't show (integration
	// deactivated, order deleted): the row still lists rather than vanishing.
	const orDash = ( value ) => value || '—';

	const amountLabel = ( subscription ) => {
		if (
			subscription?.amount === null ||
			subscription?.amount === undefined
		) {
			return '—';
		}
		return `${ subscription?.currency ?? '' } ${ Number(
			subscription.amount
		).toFixed( 2 ) }`.trim();
	};

	// "Sept 13, 2026, 2:20 pm" — 4-char month, with time.
	const dateLabel = ( value ) => formatDateCompact( value ) || '—';

	// If viewing a specific subscription, show the details component in place
	// of the whole tab — same pattern as Transactions.js.
	if ( viewingSubscription ) {
		return (
			<SubscriptionDetails
				subscriptionId={ viewingSubscription.id }
				source={ viewingSubscription.source }
				onBack={ handleBackFromDetails }
			/>
		);
	}

	return (
		<>
			<div className="tab-content bp-tab_active" id="subscriptions">
				<div className="section  bp-transactions_page">
					<div className="bp-container">
						<div className="bp-row align-items_center justify-content-space_between bp-transactions_header-section">
							<div className="bp-col bp-col_6">
								<h4 className="bp-font_inter fx-2 bp-transactions_header">
									{ __( 'Subscriptions', 'better-payment' ) }
								</h4>
							</div>
						</div>
						{ /* Stat cards — same markup as the Transactions tab's
                            three cards (Transactions.js), with the card styles
                            replicated into the #subscriptions scope in
                            admin.scss. Only the number waits on data; the
                            colour dot and label paint on the first frame. */ }
						<div className="bp-transactions_section">
							<div className="bp-row bp-transaction_section">
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--chrome bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												{ __(
													'Total Subscriptions',
													'better-payment'
												) }
											</div>
										</div>
										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{ subscriptionCount?.all }
												</h2>
											) }
										</div>
									</div>
								</div>
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--green bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												{ __(
													'Active Subscriptions',
													'better-payment'
												) }
											</div>
										</div>
										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{
														subscriptionCount?.active
													}
												</h2>
											) }
										</div>
									</div>
								</div>
								<div className="bp-col bp-col_4 bp-col_50-active_992">
									<div className="border-radius_12 bp-transaction_wrapper">
										<div className="bp-flex align-items_center margin-bottom_12">
											<div className="bp-transction_color-lavel--yellow bp-transction_color-lavel"></div>
											<div className=" bp-font_ibm bp-transction_color-name">
												{ __(
													'Cancelled Subscriptions',
													'better-payment'
												) }
											</div>
										</div>
										<div className="bp-transaction_ret margin-bottom_12">
											{ countLoading ? (
												<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-60"></div>
											) : (
												<h2 className="bp-font_ibm">
													{
														subscriptionCount?.cancelled
													}
												</h2>
											) }
										</div>
									</div>
								</div>
							</div>
						</div>
						<div className="bp-transactions_list-section">
							<div>
								<div className="bp-transactions_list-section_header">
									<div className="bp-row align-items_center">
										<div className="bp-col bp-col_4">
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
													{ __(
														'E-commerce Subscriptions',
														'better-payment'
													) }
												</h4>
											</div>
										</div>
										{ /* Filter bar — search + date range + status + reset, the same
                                            controls (and the same components) the Transactions
                                            tab uses, so the two list screens behave identically.
                                            Its CSS is #transactions-scoped in _common.scss and is
                                            replicated into this page's scope in admin.scss; a
                                            control added here without a matching rule there
                                            renders unstyled. */ }
										<div className="bp-col bp-col_8">
											<div className="bp-flex align-items_center bp-transactions_list-section_header-content">
												<TransactionsSearchBar
													onSearch={ handleSearch }
													value={
														filters.search_text
													}
													placeholder={ __(
														'Search subscriptions…',
														'better-payment'
													) }
												/>

												<DateRangeFilter
													dateFrom={
														filters.start_date_from
													}
													dateTo={
														filters.start_date_to
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
													label={ __(
														'Status',
														'better-payment'
													) }
													value={ filters.status }
													options={ STATUS_OPTIONS }
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

												{ /* Always rendered, never conditionally hidden:
                                                    a control that appears only once a filter is
                                                    set shifts the whole bar sideways the moment
                                                    the user picks a status. It dims itself
                                                    instead, and no-ops when nothing is set. */ }
												<button
													className={ `bp-flex align-items_center gap-8 bp-transactions_list-form-reset-btn${
														hasActiveFilters()
															? ' active'
															: ''
													}` }
													onClick={
														handleResetFilters
													}
													disabled={
														! hasActiveFilters()
													}
													type="button"
												>
													<span className="line-height_0">
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
														{ __(
															'Reset',
															'better-payment'
														) }
													</span>
												</button>
											</div>
										</div>
									</div>
								</div>
								{ /* Table renders while loading OR with rows so the header row
                                stays mounted and only the body fills in — the empty state below
                                is only for a settled empty result (see Transactions.js). */ }
								{ loading || subscriptions?.length > 0 ? (
									<div
										className="bp-transactions_list-table"
										role={ loading ? 'status' : undefined }
										aria-busy={
											loading ? 'true' : undefined
										}
										aria-label={
											loading
												? __(
														'Loading subscriptions…',
														'better-payment'
												  )
												: undefined
										}
									>
										<ul className="bp-transactions_list-table_wrapper">
											<li className="bp-transactions_list-table_header">
												<div className="bp-flex align-items_center ">
													<div className="bp-width_156">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Customer',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_200">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Product',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_116">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Amount',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_156">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Source',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_114">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Status',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_200">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Started',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-width_200">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Next Payment',
																'better-payment'
															) }
														</h4>
													</div>
													{ /* No visible label (user preference) — kept for
                                                    assistive tech only. */ }
													<div className="bp-width_96">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															<span className="screen-reader-text">
																{ __(
																	'Action',
																	'better-payment'
																) }
															</span>
														</h4>
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
												: subscriptions.map(
														(
															subscription,
															index
														) => {
															const statusInfo =
																ecommerceSubscriptionStatus(
																	subscription?.status
																);
															const menuId = `subscription-${
																subscription?.source
															}-${
																subscription?.subscription_id ??
																index
															}`;
															const isMenuOpen =
																openActionsMenu ===
																menuId;
															return (
																<li
																	className="bp-transactions_list-table_data"
																	key={
																		menuId
																	}
																>
																	<div className="bp-flex align-items_center ">
																		<div className="bp-width_156">
																			{ /* Opens the details view — same pattern as the
															    Transactions list's clickable name
															    (bp-transaction_name adds cursor + hover
															    colour; base colour unchanged). An
															    un-hydrated row's dash stays plain. */ }
																			{ subscription?.customer_name ? (
																				<h4
																					className="fx-4 bp-font_ibm bp-transactions_list-table_header-title"
																					title={
																						subscription.customer_name
																					}
																				>
																					<span
																						className="bp-transaction_name"
																						role="button"
																						tabIndex={
																							0
																						}
																						onClick={ () =>
																							handleViewEdit(
																								subscription
																							)
																						}
																						onKeyDown={ (
																							e
																						) => {
																							if (
																								e.key ===
																									'Enter' ||
																								e.key ===
																									' '
																							) {
																								e.preventDefault();
																								handleViewEdit(
																									subscription
																								);
																							}
																						} }
																					>
																						{
																							subscription.customer_name
																						}
																					</span>
																				</h4>
																			) : (
																				<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					—
																				</h4>
																			) }
																		</div>
																		<div className="bp-width_200">
																			{ subscription?.product_name ? (
																				<h4
																					className="fx-4 bp-font_ibm bp-transactions_list-table_header-title"
																					title={
																						subscription.product_name
																					}
																				>
																					<span
																						className="bp-transaction_name"
																						role="button"
																						tabIndex={
																							0
																						}
																						onClick={ () =>
																							handleViewEdit(
																								subscription
																							)
																						}
																						onKeyDown={ (
																							e
																						) => {
																							if (
																								e.key ===
																									'Enter' ||
																								e.key ===
																									' '
																							) {
																								e.preventDefault();
																								handleViewEdit(
																									subscription
																								);
																							}
																						} }
																					>
																						{
																							subscription.product_name
																						}
																					</span>
																				</h4>
																			) : (
																				<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																					—
																				</h4>
																			) }
																		</div>
																		<div className="bp-width_116">
																			<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																				{ amountLabel(
																					subscription
																				) }
																			</h4>
																		</div>
																		<div className="bp-width_156">
																			<h4
																				className="fx-4 bp-font_ibm bp-transactions_list-table_header-title"
																				title={
																					subscription?.source_label ||
																					undefined
																				}
																			>
																				{ orDash(
																					subscription?.source_label
																				) }
																			</h4>
																		</div>
																		<div className="bp-width_114">
																			<span
																				className={ `fx-4 bp-font_ibm  tag ${ statusInfo.tagClass }` }
																			>
																				{
																					statusInfo.label
																				}
																			</span>
																		</div>
																		<div className="bp-width_200">
																			<h4
																				className="fx-4 bp-font_ibm bp-transactions_list-table_header-title"
																				title={
																					subscription?.start_date ||
																					undefined
																				}
																			>
																				{ dateLabel(
																					subscription?.start_date
																				) }
																			</h4>
																		</div>
																		<div className="bp-width_200">
																			<h4
																				className="fx-4 bp-font_ibm bp-transactions_list-table_header-title"
																				title={
																					subscription?.next_payment ||
																					undefined
																				}
																			>
																				{ dateLabel(
																					subscription?.next_payment
																				) }
																			</h4>
																		</div>
																		<div className="bp-width_96 position-relative">
																			<button
																				className={ `bp-transactions_list-table_header-more-btn ${
																					isMenuOpen
																						? 'bp-transactions_more-btn_active'
																						: ''
																				}` }
																				onClick={ (
																					e
																				) =>
																					toggleActionsMenu(
																						menuId,
																						e
																					)
																				}
																				type="button"
																				aria-label={ __(
																					'Subscription actions',
																					'better-payment'
																				) }
																			>
																				<svg
																					width="20"
																					height="20"
																					viewBox="0 0 20 20"
																					fill="none"
																					xmlns="http://www.w3.org/2000/svg"
																				>
																					<g clipPath="url(#clip0_bp_subs_more)">
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
																						<clipPath id="clip0_bp_subs_more">
																							<rect
																								width="20"
																								height="20"
																								fill="white"
																							/>
																						</clipPath>
																					</defs>
																				</svg>
																			</button>

																			<div className="bp-subscriptions_col-actions">
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
																						<li className="bp-flex align-items_center bp-data_setting-list_item">
																							<button
																								className="bp-data_setting-item_btn"
																								onClick={ () =>
																									handleViewEdit(
																										subscription
																									)
																								}
																								type="button"
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
																									{ __(
																										'View / Edit',
																										'better-payment'
																									) }
																								</span>
																							</button>
																						</li>
																						<li className="bp-flex align-items_center bp-data_setting-list_item">
																							<button
																								className="bp-data_setting-item_btn"
																								onClick={ () =>
																									handleDelete(
																										subscription
																									)
																								}
																								type="button"
																							>
																								<span className="bp-data_setting-logo">
																									<svg
																										width="16"
																										height="17"
																										viewBox="0 0 16 17"
																										fill="none"
																										xmlns="http://www.w3.org/2000/svg"
																									>
																										<g clipPath="url(#clip0_bp_subs_delete)">
																											<path
																												d="M1.77783 4.54821H14.4209M6.51898 7.71144V12.4526M9.68037 7.71144V12.4526M2.56864 4.54821L3.35883 14.0305C3.35883 14.4497 3.52534 14.8516 3.82172 15.148C4.1181 15.4444 4.52007 15.6109 4.93922 15.6109H11.2608C11.6799 15.6109 12.0819 15.4444 12.3783 15.148C12.6746 14.8516 12.8411 14.4497 12.8411 14.0305L13.6313 4.54821M5.7291 4.54944V2.17886C5.7291 1.96929 5.81235 1.7683 5.96054 1.62011C6.10873 1.47192 6.30972 1.38867 6.51929 1.38867H9.68006C9.88963 1.38867 10.0906 1.47192 10.2388 1.62011C10.387 1.7683 10.4703 1.96929 10.4703 2.17886V4.54944"
																												strokeWidth="1.06667"
																												strokeLinecap="round"
																												strokeLinejoin="round"
																											/>
																										</g>
																										<defs>
																											<clipPath id="clip0_bp_subs_delete">
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
																							</button>
																						</li>
																					</ul>
																				</div>
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
											{ /* Two empty states, because they mean opposite
                                                things. "No subscriptions have ever been recorded"
                                                explains how they get here; a filter that matched
                                                nothing must NOT say that — the store may well be
                                                full of subscriptions, and telling the owner their
                                                integration isn't recording any sends them to
                                                debug a setup that works. */ }
											<h3 className="bp-font_ibm no-transaction-header">
												{ hasActiveFilters()
													? __(
															'No Matching Subscriptions',
															'better-payment'
													  )
													: __(
															'No Subscriptions',
															'better-payment'
													  ) }
											</h3>
											<p className="bp-font_ibm fx-6 no-transaction-sub-header">
												{ hasActiveFilters()
													? __(
															'No subscriptions match the current filters. Try a different term or date range, or reset the filters.',
															'better-payment'
													  )
													: __(
															'Subscriptions purchased through your store with the Better Payment gateway (e.g. WooCommerce subscription products) will appear here.',
															'better-payment'
													  ) }
											</p>
											{ hasActiveFilters() && (
												<button
													className="bp-font_inter fx-4 bp-subscriptions_no-data-reset"
													onClick={
														handleResetFilters
													}
													type="button"
												>
													{ __(
														'Reset Filters',
														'better-payment'
													) }
												</button>
											) }
										</div>
									</div>
								) }
							</div>
							<div className="bp-flex align-items_center justify-content-space_between bp-pagination-wrapper ">
								{ subscriptions?.length >= 10 && (
									<div className="bp-pagination-per-page">
										<FilterDropdown
											label="All"
											value={
												filters.per_page === -1
													? paginationData.total.toString()
													: filters.per_page.toString()
											}
											options={ [
												{ label: '10', value: '10' },
												{ label: '20', value: '20' },
												{ label: '50', value: '50' },
												{ label: '100', value: '100' },
												{
													label: 'All',
													value: paginationData.total.toString(),
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

			<DeleteConfirm
				isOpen={ isDeleteModalOpen }
				onClose={ handleCloseDeleteModal }
				onConfirm={ handleConfirmDelete }
				isDeleting={ isDeleting }
				message={ __(
					'The subscription will be removed from this list — and if it is still active, it will be cancelled first so no further renewals are charged. You won’t be able to revert this!',
					'better-payment'
				) }
			/>
		</>
	);
};

export default Subscriptions;
