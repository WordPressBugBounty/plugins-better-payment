import { useState, useEffect, useRef, createPortal } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { campaignsAPI } from '../utils/api';
import DeleteConfirm from './DeleteConfirm';
import FooterCards from '../components/settings/FooterCards';
import FilterDropdown from '../components/FilterDropdown';
import Pagination from 'rc-pagination';
import locale from '../utils/locale/en-US';
import illustration from '../images/illustration.webp';
import illustrationdark from '../images/illustrationdark.webp';
import AiIcon from '../../campaign-builder/ai/AiIcon';

// Rows the list requests, and therefore rows the skeleton draws. One constant so
// the placeholder can't promise more rows than the request can return.
const PER_PAGE = 20;
const SKELETON_ROWS = PER_PAGE;

// The table's column classes, in source order, matching the real header row. The
// skeleton reuses them so every shimmer cell lands under its own column and the
// table fills in place instead of the whole block redrawing. `_col-actions` is
// appended only outside trash, exactly as the header does it.
const TABLE_COLUMNS = [
	'bp-campaigns_col-check',
	'bp-campaigns_col-title',
	'bp-campaigns_col-donations',
	'bp-campaigns_col-status',
	'bp-campaigns_col-datecreated',
	'bp-campaigns_col-enddate',
];

const SortButtons = ( { field, orderBy, order, onSort } ) => (
	<div className="line-height_0 bp-flex bp-up-down-btns-wrapper">
		<button
			className="line-height_0 bp-table-up-btn"
			onClick={ () => onSort( field ) }
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
						orderBy === field && order === 'ASC'
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
			onClick={ () => onSort( field ) }
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
						orderBy === field && order === 'DESC'
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
);

const IconView = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M1 8C1 8 3.5 3 8 3C12.5 3 15 8 15 8C15 8 12.5 13 8 13C3.5 13 1 8 1 8Z"
			stroke="currentColor"
			strokeWidth="1.2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<circle cx="8" cy="8" r="2" stroke="currentColor" strokeWidth="1.2" />
	</svg>
);

const IconEdit = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M11.333 2.00004C11.5081 1.82494 11.716 1.68605 11.9447 1.59129C12.1735 1.49653 12.4187 1.44775 12.6664 1.44775C12.914 1.44775 13.1592 1.49653 13.388 1.59129C13.6167 1.68605 13.8246 1.82494 13.9997 2.00004C14.1748 2.17514 14.3137 2.383 14.4084 2.61178C14.5032 2.84055 14.552 3.08575 14.552 3.33337C14.552 3.58099 14.5032 3.82619 14.4084 4.05497C14.3137 4.28374 14.1748 4.4916 13.9997 4.6667L5.1664 13.5L1.33307 14.6667L2.4997 10.8334L11.333 2.00004Z"
			stroke="currentColor"
			strokeWidth="1.2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const IconDelete = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			d="M2 4H14M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724C5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724C10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4L12 13.333C12 13.687 11.86 14.026 11.609 14.276C11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276C4.14 14.026 4 13.687 4 13.333L3.333 4"
			stroke="currentColor"
			strokeWidth="1.2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const getStatus = ( postStatus, endDate ) => {
	if ( postStatus === 'trash' ) return 'trash';
	if ( postStatus === 'draft' ) return 'draft';
	if ( ! endDate ) return 'active';
	const end = new Date( endDate );
	return end < new Date() ? 'finished' : 'active';
};

const formatEndDate = ( endDate ) => {
	if ( ! endDate ) return '—';
	const d = new Date( endDate );
	if ( isNaN( d.getTime() ) ) return '—';
	return d.toLocaleDateString( 'en-US', {
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	} );
};

const formatPostDate = ( dateStr ) => {
	if ( ! dateStr ) return '—';
	const d = new Date( dateStr );
	if ( isNaN( d.getTime() ) ) return '—';
	return d.toLocaleDateString( 'en-US', {
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	} );
};

const Campaigns = () => {
	const [ campaigns, setCampaigns ] = useState( [] );
	const [ counts, setCounts ] = useState( {
		all: 0,
		publish: 0,
		draft: 0,
		trash: 0,
	} );
	const [ loading, setLoading ] = useState( true );
	// The status tabs are seeded with zeroes, so before the first response every
	// tab read "All (0)" — a real number, and a wrong one. This tracks whether the
	// counts have ever arrived, so the tabs can stay countless until they mean
	// something. It never goes back to false: on a refetch the previous counts are
	// still the best answer, and blanking them would flicker on every filter click.
	const [ countsLoaded, setCountsLoaded ] = useState( false );
	const [ pagination, setPagination ] = useState( {
		total: 0,
		pages: 0,
		page: 1,
	} );
	const [ selectedIds, setSelectedIds ] = useState( [] );
	const [ bulkAction, setBulkAction ] = useState( '' );
	const [ searchInput, setSearchInput ] = useState( '' );
	const searchDebounceRef = useRef( null );
	const [ deleteModal, setDeleteModal ] = useState( {
		open: false,
		id: null,
	} );
	const [ isDeleting, setIsDeleting ] = useState( false );

	const [ openActionMenu, setOpenActionMenu ] = useState( null );
	const [ menuPosition, setMenuPosition ] = useState( { top: 0, right: 0 } );
	const [ openDropdown, setOpenDropdown ] = useState( null );

	const [ filters, setFilters ] = useState( {
		status: 'all',
		s: '',
		orderby: 'date',
		order: 'DESC',
		per_page: PER_PAGE,
		page: 1,
	} );

	const fetchCampaigns = ( params ) => {
		setLoading( true );
		campaignsAPI
			.getCampaigns( params )
			.then( ( data ) => {
				setCampaigns( data?.campaigns || [] );
				setCounts(
					data?.counts || { all: 0, publish: 0, draft: 0, trash: 0 }
				);
				setPagination( {
					total: data?.total || 0,
					pages: data?.pages || 0,
					page: data?.page || 1,
				} );
				setCountsLoaded( true );
				setSelectedIds( [] );
			} )
			.catch( () => {
				setCampaigns( [] );
				setPagination( { total: 0, pages: 0, page: 1 } );
				toast.error(
					__( 'Could not load campaigns.', 'better-payment' )
				);
			} )
			.finally( () => {
				// `finally`, not the `then` branch — a rejected request used to leave
				// `loading` true forever, pinning the skeleton on screen with no error.
				setLoading( false );
			} );
	};

	useEffect( () => {
		document.title = __( 'Campaigns - Better Payment', 'better-payment' );
		fetchCampaigns( filters );
	}, [ filters ] );

	// Keep visible input in sync if filters.s is cleared externally.
	useEffect( () => {
		setSearchInput( filters.s );
	}, [ filters.s ] );

	useEffect( () => {
		const handleClickOutside = ( e ) => {
			if ( ! e.target.closest( '.bp-campaigns_col-actions' ) ) {
				setOpenActionMenu( null );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () =>
			document.removeEventListener( 'mousedown', handleClickOutside );
	}, [] );

	// A `position: fixed` menu is anchored to viewport coordinates captured when
	// it opened, so anything that scrolls leaves it stranded next to the wrong
	// row. Close it instead of trying to track the anchor — `capture: true` is
	// required because the list itself scrolls (`.bp-transactions_list-table` is
	// `overflow: auto`) and scroll events from it do not bubble to window.
	useEffect( () => {
		if ( openActionMenu === null ) return;

		const close = () => setOpenActionMenu( null );

		window.addEventListener( 'scroll', close, true );
		window.addEventListener( 'resize', close );
		return () => {
			window.removeEventListener( 'scroll', close, true );
			window.removeEventListener( 'resize', close );
		};
	}, [ openActionMenu ] );

	const toggleActionMenu = ( id, e ) => {
		if ( openActionMenu === id ) {
			setOpenActionMenu( null );
		} else {
			const rect = e.currentTarget.getBoundingClientRect();
			setMenuPosition( {
				top: rect.bottom + 4,
				right: window.innerWidth - rect.right,
			} );
			setOpenActionMenu( id );
		}
	};

	const toggleDropdown = ( name ) =>
		setOpenDropdown( ( prev ) => ( prev === name ? null : name ) );
	const closeAllDropdowns = () => setOpenDropdown( null );

	const handlePerPageChange = ( value ) => {
		setFilters( ( prev ) => ( {
			...prev,
			per_page: parseInt( value, 10 ),
			page: 1,
		} ) );
		closeAllDropdowns();
	};

	const handleFilterChange = ( key, value ) => {
		setFilters( ( prev ) => ( { ...prev, [ key ]: value, page: 1 } ) );
		setSelectedIds( [] );
	};

	const handleSelectAll = ( e ) => {
		setSelectedIds(
			e.target.checked ? campaigns.map( ( c ) => c.id ) : []
		);
	};

	const handleSelectOne = ( id, checked ) => {
		setSelectedIds( ( prev ) =>
			checked ? [ ...prev, id ] : prev.filter( ( i ) => i !== id )
		);
	};

	const handleBulkApply = async () => {
		if ( ! bulkAction || selectedIds.length === 0 ) return;
		if ( bulkAction === 'trash' ) {
			await Promise.all(
				selectedIds.map( ( id ) =>
					campaignsAPI.deleteCampaign( id, false )
				)
			);
			toast.success(
				__( 'Campaigns moved to trash.', 'better-payment' )
			);
		} else if ( bulkAction === 'restore' ) {
			await Promise.all(
				selectedIds.map( ( id ) => campaignsAPI.restoreCampaign( id ) )
			);
			toast.success( __( 'Campaigns restored.', 'better-payment' ) );
		} else if ( bulkAction === 'delete' ) {
			await Promise.all(
				selectedIds.map( ( id ) =>
					campaignsAPI.deleteCampaign( id, true )
				)
			);
			toast.success(
				__( 'Campaigns permanently deleted.', 'better-payment' )
			);
		}
		setBulkAction( '' );
		setFilters( ( prev ) => ( { ...prev, page: 1 } ) );
	};

	const handleSort = ( field ) => {
		setFilters( ( prev ) => ( {
			...prev,
			orderby: field,
			order:
				prev.orderby === field && prev.order === 'DESC'
					? 'ASC'
					: 'DESC',
			page: 1,
		} ) );
	};

	const handleSearchChange = ( e ) => {
		const value = e.target.value;
		setSearchInput( value );
		if ( searchDebounceRef.current )
			clearTimeout( searchDebounceRef.current );
		searchDebounceRef.current = setTimeout( () => {
			setFilters( ( prev ) => ( { ...prev, s: value, page: 1 } ) );
		}, 500 );
	};

	// betterPaymentAdmin.adminUrl is the full plugin page URL (e.g. /wp-admin/admin.php?page=…)
	// Strip the query string and filename to get just the /wp-admin/ base directory.
	const builderUrl = ( extra = '' ) => {
		const raw =
			window.betterPaymentAdmin?.adminUrl ||
			window.betterPaymentCampaignData?.adminUrl ||
			'/wp-admin/';
		const adminBase = raw.split( '?' )[ 0 ].replace( /[^/]*$/, '' );
		return `${ adminBase }admin.php?page=bp-campaign-builder${ extra }`;
	};

	const handleAddNew = () => {
		window.location.href = builderUrl();
	};

	// Open the builder straight into the editor with the AI Assistant ready
	// (mirrors the "Start with AI" option in the template picker).
	const handleAddWithAI = () => {
		window.location.href = builderUrl( '&start=ai' );
	};

	const openDeleteModal = ( id ) => setDeleteModal( { open: true, id } );
	const closeDeleteModal = () => {
		if ( ! isDeleting ) setDeleteModal( { open: false, id: null } );
	};

	const handleConfirmDelete = async () => {
		setIsDeleting( true );
		try {
			await campaignsAPI.deleteCampaign( deleteModal.id, false );
			toast.success( __( 'Campaign moved to trash.', 'better-payment' ) );
			setDeleteModal( { open: false, id: null } );
			setFilters( ( prev ) => ( { ...prev, page: 1 } ) );
		} catch {
			toast.error( __( 'Failed to delete campaign.', 'better-payment' ) );
		} finally {
			setIsDeleting( false );
		}
	};

	const statusTabs = [
		{ key: 'all', label: __( 'All', 'better-payment' ), count: counts.all },
		{
			key: 'publish',
			label: __( 'Published', 'better-payment' ),
			count: counts.publish,
		},
		{
			key: 'draft',
			label: __( 'Draft', 'better-payment' ),
			count: counts.draft,
		},
		{
			key: 'trash',
			label: __( 'Trash', 'better-payment' ),
			count: counts.trash,
		},
	];

	// How many shimmer rows the table draws. A first load has no better estimate
	// than the page size — SKELETON_ROWS matches per_page, because promising more
	// rows than the request can return is its own layout shift. On a refetch
	// (filter, sort, page) the rows already on screen are the better estimate:
	// drawing exactly that many holds the table at the height it already has, so
	// the swap moves nothing.
	const skeletonRowCount =
		campaigns.length > 0 ? campaigns.length : SKELETON_ROWS;

	// Trash hides the actions column, in the skeleton exactly as in the header.
	const skeletonColumns =
		filters.status !== 'trash'
			? [ ...TABLE_COLUMNS, 'bp-campaigns_col-actions' ]
			: TABLE_COLUMNS;

	return (
		<>
			<div className="tab-content bp-tab_active" id="campaigns">
				<div className="section bp-transactions_page">
					<div className="bp-container">
						{ /* Page header */ }
						<div className="bp-row align-items_center justify-content-space_between bp-transactions_header-section">
							<div className="bp-col bp-col_6">
								<h4 className="bp-font_inter fx-2 bp-transactions_header">
									{ __( 'Campaigns', 'better-payment' ) }
								</h4>
							</div>
							<div className="bp-col bp-col_6 bp-flex justify-content_flex-end bp-campaigns_header-actions">
								<button
									className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-export_all-btn bp-campaign_ai-btn"
									onClick={ handleAddWithAI }
									type="button"
								>
									<AiIcon size={ 16 } />
									<span>
										{ __(
											'Campaign With AI',
											'better-payment'
										) }
									</span>
								</button>
								<button
									className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-export_all-btn"
									onClick={ handleAddNew }
									type="button"
								>
									{ __(
										'Add New Campaign',
										'better-payment'
									) }
								</button>
							</div>
						</div>

						{ /* List section */ }
						<div className="bp-transactions_list-section">
							<div>
								{ /* Toolbar: status tabs + search + date filter */ }
								<div className="bp-transactions_list-section_header">
									<div className="bp-row align-items_center">
										<div className="bp-col bp-col_12">
											<div className="bp-flex align-items_center justify-content-space_between bp-campaigns_toolbar">
												{ /* Status tabs */ }
												<div className="bp-flex align-items_center bp-campaigns_status-tabs">
													{ statusTabs.map(
														( tab, i, arr ) => (
															<span
																key={ tab.key }
																className="bp-campaigns_status-tab-group"
															>
																<button
																	type="button"
																	className={ `bp-campaigns_status-tab bp-font_ibm fx-5${
																		filters.status ===
																		tab.key
																			? ' bp-campaigns_status-tab--active'
																			: ''
																	}` }
																	onClick={ () =>
																		handleFilterChange(
																			'status',
																			tab.key
																		)
																	}
																>
																	{
																		tab.label
																	}
																	{ countsLoaded
																		? ` (${ tab.count })`
																		: '' }
																</button>
																{ i <
																	arr.length -
																		1 && (
																	<span className="bp-campaigns_status-sep">
																		|
																	</span>
																) }
															</span>
														)
													) }
												</div>

												{ /* Search + date filter */ }
												<div className="bp-flex align-items_center gap-8">
													<div className="bp-campaigns_search-wrap">
														<svg
															className="bp-campaigns_search-icon"
															width="16"
															height="16"
															viewBox="0 0 18 18"
															fill="none"
															xmlns="http://www.w3.org/2000/svg"
														>
															<path
																d="M3.30469 8.25C3.30469 9.64193 3.86468 10.9769 4.85878 11.971C5.85288 12.9651 7.18782 13.5251 8.57975 13.5251C9.97168 13.5251 11.3066 12.9651 12.3007 11.971C13.2948 10.9769 13.8548 9.64193 13.8548 8.25C13.8548 6.85807 13.2948 5.52313 12.3007 4.52903C11.3066 3.53494 9.97168 2.97495 8.57975 2.97495C7.18782 2.97495 5.85288 3.53494 4.85878 4.52903C3.86468 5.52313 3.30469 6.85807 3.30469 8.25Z"
																stroke="#8F9AB0"
																strokeWidth="1.5"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
															<path
																d="M14.8594 14.25L12.3047 12"
																stroke="#8F9AB0"
																strokeWidth="1.5"
																strokeLinecap="round"
																strokeLinejoin="round"
															/>
														</svg>
														<input
															type="search"
															className="bp-font_ibm fx-5 bp-campaigns_search-input"
															placeholder={ __(
																'Search campaigns…',
																'better-payment'
															) }
															value={
																searchInput
															}
															onChange={
																handleSearchChange
															}
														/>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								{ /* Bulk actions bar */ }
								<div className="bp-flex align-items_center bp-campaigns_bulk-row">
									<select
										className="bp-font_ibm fx-5 bp-campaigns_select"
										value={ bulkAction }
										onChange={ ( e ) =>
											setBulkAction( e.target.value )
										}
									>
										<option value="">
											{ __(
												'Bulk actions',
												'better-payment'
											) }
										</option>
										{ filters.status === 'trash' ? (
											<>
												<option value="restore">
													{ __(
														'Restore',
														'better-payment'
													) }
												</option>
												<option value="delete">
													{ __(
														'Delete Permanently',
														'better-payment'
													) }
												</option>
											</>
										) : (
											<option value="trash">
												{ __(
													'Move to Trash',
													'better-payment'
												) }
											</option>
										) }
									</select>
									<button
										type="button"
										className="bp-font_ibm fx-5 bp-campaigns_apply-btn"
										onClick={ handleBulkApply }
										disabled={
											! bulkAction ||
											selectedIds.length === 0
										}
									>
										{ __( 'Apply', 'better-payment' ) }
									</button>
								</div>

								{ /* Table — renders whenever it is loading OR has rows, so the
                                     real header row, sort controls and column widths stay mounted
                                     and only the body fills in. The empty state below is for a
                                     settled empty result only, never for an in-flight one. */ }
								{ loading || campaigns.length > 0 ? (
									<div
										className="bp-transactions_list-table"
										role={ loading ? 'status' : undefined }
										aria-busy={
											loading ? 'true' : undefined
										}
										aria-label={
											loading
												? __(
														'Loading campaigns…',
														'better-payment'
												  )
												: undefined
										}
									>
										<ul className="bp-transactions_list-table_wrapper">
											{ /* Header */ }
											<li className="bp-transactions_list-table_header">
												<div className="bp-flex align-items_center">
													<div className="bp-campaigns_col-check">
														<input
															type="checkbox"
															className="bp-transactions_table-list_check"
															checked={
																campaigns.length >
																	0 &&
																selectedIds.length ===
																	campaigns.length
															}
															onChange={
																handleSelectAll
															}
															disabled={ loading }
														/>
													</div>
													<div className="bp-campaigns_col-title">
														<div className="bp-flex align-items_center gap-12">
															<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																{ __(
																	'Title',
																	'better-payment'
																) }
															</h4>
															<SortButtons
																field="title"
																orderBy={
																	filters.orderby
																}
																order={
																	filters.order
																}
																onSort={
																	handleSort
																}
															/>
														</div>
													</div>
													<div className="bp-campaigns_col-donations">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Donations',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-campaigns_col-status">
														<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
															{ __(
																'Status',
																'better-payment'
															) }
														</h4>
													</div>
													<div className="bp-campaigns_col-datecreated">
														<div className="bp-flex align-items_center gap-12">
															<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																{ __(
																	'Date Created',
																	'better-payment'
																) }
															</h4>
															<SortButtons
																field="date"
																orderBy={
																	filters.orderby
																}
																order={
																	filters.order
																}
																onSort={
																	handleSort
																}
															/>
														</div>
													</div>
													<div className="bp-campaigns_col-enddate">
														<div className="bp-flex align-items_center gap-12">
															<h4 className="fx-4 bp-font_ibm bp-transactions_list-table_header-title">
																{ __(
																	'End Date',
																	'better-payment'
																) }
															</h4>
														</div>
													</div>
													{ filters.status !==
														'trash' && (
														<div className="bp-campaigns_col-actions"></div>
													) }
												</div>
											</li>

											{ /* Rows */ }
											{ loading
												? Array.from(
														{
															length: skeletonRowCount,
														},
														( _, row ) => (
															<li
																key={ row }
																className="bp-transactions_list-table_data"
															>
																<div className="bp-flex align-items_center">
																	{ skeletonColumns.map(
																		(
																			col
																		) => (
																			<div
																				key={
																					col
																				}
																				className={
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
												: campaigns.map(
														( campaign ) => {
															const {
																id,
																title,
																edit_url,
																view_url,
																post_date,
																post_status,
																stats,
																meta,
																currency,
															} = campaign;
															const raised =
																parseFloat(
																	stats?.total_raised ||
																		0
																);
															const donors =
																parseInt(
																	stats?.donor_count ||
																		0
																);
															const cur =
																currency ||
																'USD';
															const status =
																getStatus(
																	post_status,
																	meta?.bpc_end_date
																);
															const endDate =
																formatEndDate(
																	meta?.bpc_end_date
																);
															const dateCreated =
																formatPostDate(
																	post_date
																);
															const isActionMenuOpen =
																openActionMenu ===
																id;

															return (
																<li
																	key={ id }
																	className="bp-transactions_list-table_data"
																>
																	<div className="bp-flex align-items_center">
																		{ /* Checkbox */ }
																		<div className="bp-campaigns_col-check">
																			<input
																				type="checkbox"
																				className="bp-transactions_table-list_check"
																				checked={ selectedIds.includes(
																					id
																				) }
																				onChange={ (
																					e
																				) =>
																					handleSelectOne(
																						id,
																						e
																							.target
																							.checked
																					)
																				}
																			/>
																		</div>

																		{ /* Title — links to editor */ }
																		<div className="bp-campaigns_col-title">
																			<a
																				href={
																					edit_url
																				}
																				className="bp-font_ibm fx-4 bp-campaigns_title-text bp-campaigns_title-link"
																			>
																				{ title ||
																					__(
																						'(no title)',
																						'better-payment'
																					) }
																				{ campaign.post_status ===
																					'draft' && (
																					<span className="bp-campaigns_title-draft-badge">
																						{ __(
																							' — Draft',
																							'better-payment'
																						) }
																					</span>
																				) }
																			</a>
																		</div>

																		{ /* Donations */ }
																		<div className="bp-campaigns_col-donations">
																			<p className="bp-font_ibm fx-4 bp-campaigns_donations-raised">
																				{
																					cur
																				}{ ' ' }
																				{ raised.toLocaleString(
																					'en-US',
																					{
																						minimumFractionDigits: 2,
																						maximumFractionDigits: 2,
																					}
																				) }
																			</p>
																			<p className="bp-font_ibm fx-5 bp-campaigns_donations-donors">
																				{
																					donors
																				}{ ' ' }
																				{ donors ===
																				1
																					? __(
																							'donor',
																							'better-payment'
																					  )
																					: __(
																							'donors',
																							'better-payment'
																					  ) }
																			</p>
																		</div>

																		{ /* Status */ }
																		<div className="bp-campaigns_col-status">
																			<span
																				className={ `bp-campaigns_status-badge bp-campaigns_status-badge--${ status }` }
																			>
																				{ status ===
																				'active'
																					? __(
																							'Active',
																							'better-payment'
																					  )
																					: status ===
																					  'draft'
																					? __(
																							'Draft',
																							'better-payment'
																					  )
																					: status ===
																					  'trash'
																					? __(
																							'Trash',
																							'better-payment'
																					  )
																					: __(
																							'Finished',
																							'better-payment'
																					  ) }
																			</span>
																		</div>

																		{ /* Date Created */ }
																		<div className="bp-campaigns_col-datecreated">
																			<span className="bp-font_ibm fx-4 bp-campaigns_enddate">
																				{
																					dateCreated
																				}
																			</span>
																		</div>

																		{ /* End Date */ }
																		<div className="bp-campaigns_col-enddate">
																			<span className="bp-font_ibm fx-4 bp-campaigns_enddate">
																				{
																					endDate
																				}
																			</span>
																		</div>

																		{ /* Actions — three-dot dropdown (hidden in trash view) */ }
																		{ filters.status !==
																			'trash' && (
																			<div className="bp-campaigns_col-actions position-relative">
																				<button
																					type="button"
																					className={ `bp-transactions_list-table_header-more-btn${
																						isActionMenuOpen
																							? ' bp-transactions_more-btn_active'
																							: ''
																					}` }
																					onClick={ (
																						e
																					) =>
																						toggleActionMenu(
																							id,
																							e
																						)
																					}
																				>
																					<svg
																						width="20"
																						height="20"
																						viewBox="0 0 20 20"
																						fill="none"
																						xmlns="http://www.w3.org/2000/svg"
																					>
																						<path
																							d="M9.166 10a.833.833 0 1 0 1.667 0 .833.833 0 0 0-1.667 0Z"
																							stroke="#8F9AB0"
																							strokeWidth="2"
																							strokeLinecap="round"
																							strokeLinejoin="round"
																						/>
																						<path
																							d="M9.166 15.833a.833.833 0 1 0 1.667 0 .833.833 0 0 0-1.667 0Z"
																							stroke="#8F9AB0"
																							strokeWidth="2"
																							strokeLinecap="round"
																							strokeLinejoin="round"
																						/>
																						<path
																							d="M9.166 4.167a.833.833 0 1 0 1.667 0 .833.833 0 0 0-1.667 0Z"
																							stroke="#8F9AB0"
																							strokeWidth="2"
																							strokeLinecap="round"
																							strokeLinejoin="round"
																						/>
																					</svg>
																				</button>

																				<div
																					className={ `bp-data_setting-wrapper${
																						isActionMenuOpen
																							? ' settiong-wrapper-active'
																							: ''
																					}` }
																					style={
																						isActionMenuOpen
																							? {
																									top: menuPosition.top,
																									right: menuPosition.right,
																							  }
																							: {}
																					}
																				>
																					<ul className="bp-data_setting-box">
																						{ view_url && (
																							<li
																								className="bp-flex align-items_center bp-data_setting-list_item"
																								style={ {
																									cursor: 'pointer',
																								} }
																							>
																								<a
																									href={
																										view_url
																									}
																									target="_blank"
																									rel="noreferrer"
																									className="bp-flex align-items_center bp-campaigns_dropdown-link"
																									onClick={ () =>
																										setOpenActionMenu(
																											null
																										)
																									}
																								>
																									<span className="bp-data_setting-logo">
																										<IconView />
																									</span>
																									<span className="bp-font_ibm fx-4 bp-data_setting-name">
																										{ __(
																											'View',
																											'better-payment'
																										) }
																									</span>
																								</a>
																							</li>
																						) }
																						<li
																							className="bp-flex align-items_center bp-data_setting-list_item"
																							style={ {
																								cursor: 'pointer',
																							} }
																						>
																							<a
																								href={
																									edit_url
																								}
																								className="bp-flex align-items_center bp-campaigns_dropdown-link"
																								onClick={ () =>
																									setOpenActionMenu(
																										null
																									)
																								}
																							>
																								<span className="bp-data_setting-logo">
																									<IconEdit />
																								</span>
																								<span className="bp-font_ibm fx-4 bp-data_setting-name">
																									{ __(
																										'Edit',
																										'better-payment'
																									) }
																								</span>
																							</a>
																						</li>
																						<li
																							className="bp-flex align-items_center bp-data_setting-list_item"
																							style={ {
																								cursor: 'pointer',
																							} }
																							onClick={ () => {
																								setOpenActionMenu(
																									null
																								);
																								openDeleteModal(
																									id
																								);
																							} }
																						>
																							<span className="bp-data_setting-logo">
																								<IconDelete />
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
																		) }
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
													'No Campaigns',
													'better-payment'
												) }
											</h3>
											<p className="bp-font_ibm fx-6 no-transaction-sub-header">
												{ __(
													'Create your first campaign to start collecting donations.',
													'better-payment'
												) }
											</p>
										</div>
									</div>
								) }

								{ /* Pagination */ }
								{ ( pagination.total >= 10 ||
									pagination.pages > 1 ) && (
									<div className="bp-flex align-items_center justify-content-space_between bp-pagination-wrapper">
										{ pagination.total >= 10 && (
											<div className="bp-pagination-per-page">
												<FilterDropdown
													label="20"
													value={ filters.per_page.toString() }
													options={ [
														{
															label: '10',
															value: '10',
														},
														{
															label: '20',
															value: '20',
														},
														{
															label: '50',
															value: '50',
														},
														{
															label: '100',
															value: '100',
														},
													] }
													onChange={
														handlePerPageChange
													}
													isOpen={
														openDropdown ===
														'perPage'
													}
													onToggle={ () =>
														toggleDropdown(
															'perPage'
														)
													}
													onClose={
														closeAllDropdowns
													}
												/>
											</div>
										) }
										{ pagination.pages > 1 && (
											<Pagination
												current={ pagination.page }
												total={ pagination.total }
												pageSize={ filters.per_page }
												onChange={ ( p ) =>
													setFilters( ( prev ) => ( {
														...prev,
														page: p,
													} ) )
												}
												className="bp-pagination-pages"
												locale={ locale }
											/>
										) }
									</div>
								) }
							</div>
						</div>

						<FooterCards />
					</div>
				</div>
			</div>

			<DeleteConfirm
				isOpen={ deleteModal.open }
				onClose={ closeDeleteModal }
				onConfirm={ handleConfirmDelete }
				isDeleting={ isDeleting }
			/>
		</>
	);
};

export default Campaigns;
