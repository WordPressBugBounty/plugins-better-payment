import { useRef, useEffect, useState } from '@wordpress/element';
import { DateRangePicker } from 'react-date-range';
import { toIsoDate, parseIsoDate } from './../utils/helper';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';

/**
 * DateRangeFilter Component
 *
 * A date range filter with calendar picker
 *
 * @param {string} dateFrom - Start date (YYYY-MM-DD format)
 * @param {string} dateTo - End date (YYYY-MM-DD format)
 * @param {function} onChange - Callback when Apply button is clicked
 * @param {boolean} isOpen - Whether the dropdown is currently open
 * @param {function} onToggle - Callback to toggle dropdown open/closed
 * @param {function} onClose - Callback to close the dropdown
 */
const DateRangeFilter = ( {
	dateFrom,
	dateTo,
	onChange,
	isOpen,
	onToggle,
	onClose,
} ) => {
	const dropdownRef = useRef( null );
	const popupRef = useRef( null );
	// Both ends are parsed from LOCAL calendar parts, never `new Date('Y-m-d')`
	// (parsed as UTC midnight, so the calendar opens on the previous day west
	// of Greenwich).
	const selectionFrom = ( from, to ) => ( {
		startDate: parseIsoDate( from ) ?? new Date(),
		endDate: parseIsoDate( to ) ?? new Date(),
		key: 'selection',
	} );

	const [ state, setState ] = useState( [
		selectionFrom( dateFrom, dateTo ),
	] );

	// The calendar follows the filter it displays. Without this the picker
	// keeps its last selection after the list's Reset button clears the range,
	// so reopening it shows a range that is no longer applied.
	useEffect( () => {
		setState( [ selectionFrom( dateFrom, dateTo ) ] );
	}, [ dateFrom, dateTo ] );

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
		setState( [ item.selection ] );
	};

	const handleApply = () => {
		// Local calendar parts, NOT toISOString(): that converts to UTC first,
		// so a user in UTC+6 picking 16 Aug sent 15 Aug to the server and the
		// list came back for the wrong day.
		onChange(
			toIsoDate( state[ 0 ].startDate ),
			toIsoDate( state[ 0 ].endDate )
		);
		onClose();
	};

	const displayLabel =
		dateFrom && dateTo ? `${ dateFrom } to ${ dateTo }` : 'Date Range';

	// Check if filter has non-default values
	const hasValue = dateFrom && dateTo;
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
					className="bp-date-range-dropdown"
					style={ {
						position: 'absolute',
						top: '100%',
						right: 0,
						zIndex: 1000,
						marginTop: '8px',
						backgroundColor: '#fff',
						borderRadius: '8px',
						boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
						padding: '16px',
					} }
					onClick={ ( e ) => e.stopPropagation() }
				>
					<DateRangePicker
						onChange={ handleDateChange }
						ranges={ state }
						months={ 1 }
					/>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'flex-end',
							gap: '8px',
							marginTop: '16px',
							paddingTop: '16px',
							borderTop: '1px solid #E5E7EB',
						} }
					>
						<button
							type="button"
							onClick={ handleApply }
							className="bp-font_ibm fx-4"
							style={ {
								padding: '8px 16px',
								backgroundColor: '#6B59EE',
								border: 'none',
								borderRadius: '6px',
								cursor: 'pointer',
								color: '#fff',
								fontWeight: '500',
							} }
						>
							Apply
						</button>
					</div>
				</div>
			) }
		</div>
	);
};

export default DateRangeFilter;
