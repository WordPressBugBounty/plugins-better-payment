import {
	useRef,
	useEffect,
	useLayoutEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	DateRangePicker,
	defaultStaticRanges,
	createStaticRanges,
} from 'react-date-range';
import {
	allTimeStart as allTimeStartDate,
	dateRangeLabel,
	filterFromSelection,
	isAllTimeSelection,
	selectionFromFilter,
} from './../utils/dateRange';
import { horizontalPopupOffset, contentBounds } from './../utils/popupPosition';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';

/**
 * DateRangeFilter Component
 *
 * The Transactions and Subscriptions lists' date filter (2.5.1: the look of Pro's Analytics date
 * range — brand-colour selection, divided presets, typed dates, Reset + Apply — plus **All Time**).
 * A list with no date filter shows every row, so All Time is the empty filter ('' / ''), which is
 * also what the trigger shows by default. The calendar shows it, like Analytics, as the list's
 * span: its oldest day (`allTimeStart`) to today, selected, both dates in the inputs.
 *
 * @param {string} dateFrom - Start date (YYYY-MM-DD format)
 * @param {string} dateTo - End date (YYYY-MM-DD format)
 * @param {function} onChange - Callback when Apply button is clicked
 * @param {boolean} isOpen - Whether the dropdown is currently open
 * @param {function} onToggle - Callback to toggle dropdown open/closed
 * @param {function} onClose - Callback to close the dropdown
 * @param {string} allTimeStart - The list's oldest day (`YYYY-MM-DD`, its count route's
 *                                `first_date`): where All Time starts; '' = today.
 */
const DateRangeFilter = ( {
	dateFrom,
	dateTo,
	onChange,
	isOpen,
	onToggle,
	onClose,
	allTimeStart = '',
} ) => {
	const dropdownRef = useRef( null );
	const popupRef = useRef( null );
	const allTimeLabel = __( 'All Time', 'better-payment' );

	// All Time's span: the list's oldest day to today (local midnight).
	const today = new Date();
	today.setHours( 0, 0, 0, 0 );
	const allStart = allTimeStartDate( allTimeStart, today );

	// Both ends are parsed from LOCAL calendar parts (selectionFromFilter), never
	// `new Date('Y-m-d')`, which is UTC midnight and opens the calendar on the
	// previous day west of Greenwich.
	const selectionFrom = ( from, to ) =>
		selectionFromFilter( from, to, allStart, today );

	const [ state, setState ] = useState( [
		selectionFrom( dateFrom, dateTo ),
	] );

	// The picker's presets plus All Time, which clears the filter and shows the
	// list's whole span on the calendar.
	const staticRanges = [
		...defaultStaticRanges,
		...createStaticRanges( [
			{
				label: allTimeLabel,
				range: () => ( {
					startDate: allStart,
					endDate: today,
					allTime: true,
				} ),
				isSelected: ( range ) =>
					isAllTimeSelection( range, allStart, today ),
			},
		] ),
	];

	// The calendar follows the filter it displays (and All Time's start once the
	// list's oldest day arrives). Without this the picker keeps its last selection
	// after the list's Reset button clears the range, so reopening it shows a
	// range that is no longer applied.
	useEffect( () => {
		setState( [ selectionFrom( dateFrom, dateTo ) ] );
	}, [ dateFrom, dateTo, allTimeStart ] );

	// Where the popup sits, measured once it is open: right-aligned to the
	// trigger when that fits, otherwise moved right so it never slides under the
	// wp-admin menu (see horizontalPopupOffset). `null` = not measured yet.
	const [ popupLeft, setPopupLeft ] = useState( null );

	useLayoutEffect( () => {
		if ( ! isOpen ) {
			setPopupLeft( null );
			return;
		}
		const place = () => {
			const trigger = dropdownRef.current;
			const popup = popupRef.current;
			if ( ! trigger || ! popup ) {
				return;
			}
			const rect = trigger.getBoundingClientRect();
			setPopupLeft(
				horizontalPopupOffset( {
					triggerLeft: rect.left,
					triggerRight: rect.right,
					popupWidth: popup.offsetWidth,
					...contentBounds(),
				} )
			);
		};
		place();
		window.addEventListener( 'resize', place );
		return () => window.removeEventListener( 'resize', place );
	}, [ isOpen ] );

	// Handle click outside to close dropdown
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				dropdownRef.current &&
				! dropdownRef.current.contains( event.target )
			) {
				onClose();
			}
		};

		if ( isOpen ) {
			document.addEventListener( 'mousedown', handleClickOutside );
			return () =>
				document.removeEventListener( 'mousedown', handleClickOutside );
		}
	}, [ isOpen, onClose ] );

	const handleDateChange = ( item ) => {
		// A picked range keeps the previous one's fields: All Time holds only while
		// the dates are still its span.
		const { startDate, endDate } = item.selection;
		setState( [
			{
				startDate,
				endDate,
				key: 'selection',
				allTime: isAllTimeSelection( item.selection, allStart, today ),
			},
		] );
	};

	const handleApply = () => {
		// Local calendar parts (toIsoDate), NOT toISOString(): that converts to
		// UTC first, so a user in UTC+6 picking 16 Aug sent 15 Aug to the server
		// and the list came back for the wrong day. All Time sends '' / ''.
		const filter = filterFromSelection( state[ 0 ] );
		onChange( filter.dateFrom, filter.dateTo );
		onClose();
	};

	// Reset: back to All Time, the lists' default.
	const handleReset = () => {
		onChange( '', '' );
		onClose();
	};

	const displayLabel = dateRangeLabel( dateFrom, dateTo, allTimeLabel );

	// Check if filter has non-default values
	const hasValue = displayLabel !== allTimeLabel;
	const isActive = isOpen || hasValue;

	return (
		<div
			ref={ dropdownRef }
			className={ `bp-flex align-items_center gap-8 bp-transactions_list-form-select bp-date-range-filter${
				isActive ? ' active' : ''
			}` }
			style={ { position: 'relative' } }
			onClick={ onToggle }
		>
			<button
				className="bp-flex align-items_center gap-8 bp-filter-dropdown-trigger"
				type="button"
			>
				<span className="bp-font_ibm select-name">
					{ displayLabel }
				</span>
				<span className="line-height_0">
					<svg
						width="11"
						height="6"
						viewBox="0 0 11 6"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
						className={ isOpen ? 'bp-dropdown-icon-open' : '' }
					>
						<path
							d="M0.75 0.75L5.25 5.25L9.75 0.75"
							stroke="#8F9AB0"
							strokeWidth="1.5"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</span>
			</button>

			{ isOpen && (
				<div
					ref={ popupRef }
					className={ `bp-date-range-dropdown${
						state[ 0 ]?.allTime ? ' is-all-time' : ''
					}` }
					style={
						popupLeft === null
							? { right: 0 }
							: { left: popupLeft, right: 'auto' }
					}
					onClick={ ( e ) => e.stopPropagation() }
				>
					<DateRangePicker
						onChange={ handleDateChange }
						ranges={ state }
						staticRanges={ staticRanges }
						months={ 1 }
						direction="horizontal"
						moveRangeOnFirstSelection={ false }
						showSelectionPreview={ false }
						editableDateInputs={ true }
						rangeColors={ [ '#6B59EE' ] }
						// All Time spans years: open on this month, where a new
						// pick usually starts, not on the oldest row's month.
						shownDate={ state[ 0 ]?.allTime ? today : undefined }
					/>
					<div className="bp-date-range-dropdown_footer">
						<button
							type="button"
							onClick={ handleReset }
							className="bp-font_ibm fx-4 bp-date-range-dropdown_reset"
						>
							{ __( 'Reset', 'better-payment' ) }
						</button>
						<button
							type="button"
							onClick={ handleApply }
							className="bp-font_ibm fx-4 bp-date-range-dropdown_apply"
						>
							{ __( 'Apply', 'better-payment' ) }
						</button>
					</div>
				</div>
			) }
		</div>
	);
};

export default DateRangeFilter;
