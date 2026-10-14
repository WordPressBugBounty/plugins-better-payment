/**
 * Calendar popover for the Smart Prompt Wizard's end-date question.
 *
 * Hand-rolled rather than pulled from `@wordpress/components`: the campaign
 * builder imports none of that package, and its enqueue
 * (`CPT::enqueue_builder_assets`) hardcodes a minimal script dependency list with
 * no generated `.asset.php`, so a `DatePicker` would mean adding `wp-components`
 * plus its stylesheet to the page for one field. Every other builder control is
 * hand-rolled too — this matches.
 *
 * Dates are handled as plain `YYYY-MM-DD` strings and built with the LOCAL date
 * parts, never `toISOString()` (which formats in UTC and can shift the calendar
 * day either side of midnight). Because the format is zero-padded and fixed-width,
 * plain string comparison is also correct date ordering — that is what the `min`
 * check relies on.
 */

import {
	useState,
	useEffect,
	useLayoutEffect,
	useRef,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/** Weekday initials, Sunday-first; reordered by the site's start_of_week. */
const WEEKDAYS = [
	__( 'Su', 'better-payment' ),
	__( 'Mo', 'better-payment' ),
	__( 'Tu', 'better-payment' ),
	__( 'We', 'better-payment' ),
	__( 'Th', 'better-payment' ),
	__( 'Fr', 'better-payment' ),
	__( 'Sa', 'better-payment' ),
];

/**
 * Format a Date as a local `YYYY-MM-DD` string.
 *
 * Deliberately not `toISOString().slice(0,10)`: that converts to UTC first, so
 * for anyone east or west of Greenwich it can report the wrong calendar day.
 *
 * @param {Date} date The date to format.
 * @return {string} The date as YYYY-MM-DD.
 */
export function toIsoDate( date ) {
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	return `${ date.getFullYear() }-${ month }-${ day }`;
}

/** Today as a local `YYYY-MM-DD` string. @return {string} Today. */
export function todayIso() {
	return toIsoDate( new Date() );
}

/**
 * The weekday the site starts its weeks on (0 = Sunday), from Settings → General.
 *
 * Localized by `CPT::enqueue_builder_assets`. It arrives as a **string** — even
 * though PHP casts it to int — because `wp_localize_script()` stringifies every
 * scalar it is handed. The `Number()` here is therefore load-bearing, not
 * defensive (see `tests/Integration/CampaignBuilderLocalizeTest.php`).
 *
 * `??` not `||`: 0 (Sunday) is a real value that `||` would discard.
 *
 * @return {number} A weekday index, 0-6.
 */
export function getStartOfWeek() {
	const value = Number( window?.betterPaymentCampaignData?.startOfWeek ?? 0 );
	return Number.isInteger( value ) && value >= 0 && value <= 6 ? value : 0;
}

/**
 * Lay a month out as weeks of day numbers, padded with nulls to full weeks.
 *
 * @param {number} year        Full year.
 * @param {number} month       Month index, 0-11.
 * @param {number} startOfWeek Weekday the grid starts on (0 = Sunday).
 * @return {Array<Array<number|null>>} Weeks of seven cells.
 */
export function buildMonthGrid( year, month, startOfWeek = 0 ) {
	const daysInMonth = new Date( year, month + 1, 0 ).getDate();
	const firstDayOfWeek = new Date( year, month, 1 ).getDay();
	const leading = ( firstDayOfWeek - startOfWeek + 7 ) % 7;

	const cells = new Array( leading ).fill( null );
	for ( let day = 1; day <= daysInMonth; day++ ) {
		cells.push( day );
	}
	while ( cells.length % 7 !== 0 ) {
		cells.push( null );
	}

	const weeks = [];
	for ( let i = 0; i < cells.length; i += 7 ) {
		weeks.push( cells.slice( i, i + 7 ) );
	}
	return weeks;
}

/**
 * Render a `YYYY-MM-DD` string for display, in the viewer's locale.
 *
 * Parsed into local date parts rather than handed to `new Date( iso )`, which
 * would read it as UTC midnight and render as the previous day west of Greenwich.
 *
 * @param {string} iso A YYYY-MM-DD date.
 * @return {string} A localized date, or '' when `iso` is not a valid date string.
 */
export function formatDisplayDate( iso ) {
	if ( ! /^\d{4}-\d{2}-\d{2}$/.test( iso ?? '' ) ) {
		return '';
	}
	const [ year, month, day ] = iso.split( '-' ).map( Number );
	// Noon, so no timezone or DST shift can move it off this calendar day.
	return new Date( year, month - 1, day, 12 ).toLocaleDateString( undefined, {
		day: 'numeric',
		month: 'short',
		year: 'numeric',
	} );
}

export default function DatePopover( {
	value,
	min,
	startOfWeek = 0,
	onSelect,
	onClose,
} ) {
	const ref = useRef( null );

	// Open on the selected month when there is one, else on the min/today month.
	const [ view, setView ] = useState( () => {
		const anchor = value || min || todayIso();
		const [ year, month ] = anchor.split( '-' ).map( Number );
		return { year, month: month - 1 };
	} );

	useEffect( () => {
		const onDown = ( event ) => {
			if ( ! ref.current?.contains( event.target ) ) {
				onClose();
			}
		};
		const onKey = ( event ) => {
			if ( 'Escape' === event.key ) {
				event.stopPropagation();
				onClose();
			}
		};
		document.addEventListener( 'mousedown', onDown );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDown );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ onClose ] );

	// The calendar can still overflow a narrow sidebar even anchored to a
	// full-width select's left edge, and the scroll container clips it. Measure
	// once, before paint, and hang it off the right edge instead when it would
	// spill.
	const [ flipped, setFlipped ] = useState( false );
	useLayoutEffect( () => {
		const el = ref.current;
		const scroller = el?.closest( '.bp-cb-aiw__questions' );
		if ( ! el || ! scroller ) {
			return;
		}
		if (
			el.getBoundingClientRect().right >
			scroller.getBoundingClientRect().right
		) {
			setFlipped( true );
		}
	}, [] );

	// The panel scrolls, and an absolutely positioned popover is clipped by it.
	// Opening the calendar is only useful if it stays in view.
	useEffect( () => {
		ref.current?.scrollIntoView( { block: 'nearest' } );
	}, [] );

	const weeks = useMemo(
		() => buildMonthGrid( view.year, view.month, startOfWeek ),
		[ view.year, view.month, startOfWeek ]
	);

	const weekdays = useMemo(
		() => [
			...WEEKDAYS.slice( startOfWeek ),
			...WEEKDAYS.slice( 0, startOfWeek ),
		],
		[ startOfWeek ]
	);

	const monthLabel = new Date( view.year, view.month, 1 ).toLocaleDateString(
		undefined,
		{ month: 'long', year: 'numeric' }
	);

	const shiftMonth = ( delta ) =>
		setView( ( prev ) => {
			const next = new Date( prev.year, prev.month + delta, 1 );
			return { year: next.getFullYear(), month: next.getMonth() };
		} );

	return (
		<div
			className={ `bp-cb-aiw__datepop${ flipped ? ' is-flipped' : '' }` }
			ref={ ref }
			role="dialog"
			aria-modal="false"
			aria-label={ __( 'Choose an end date', 'better-payment' ) }
		>
			<div className="bp-cb-aiw__datepop-head">
				<button
					type="button"
					className="bp-cb-aiw__datepop-nav"
					onClick={ () => shiftMonth( -1 ) }
					aria-label={ __( 'Previous month', 'better-payment' ) }
				>
					‹
				</button>
				<span className="bp-cb-aiw__datepop-month">{ monthLabel }</span>
				<button
					type="button"
					className="bp-cb-aiw__datepop-nav"
					onClick={ () => shiftMonth( 1 ) }
					aria-label={ __( 'Next month', 'better-payment' ) }
				>
					›
				</button>
			</div>

			<table className="bp-cb-aiw__datepop-grid">
				<thead>
					<tr>
						{ weekdays.map( ( day ) => (
							<th key={ day } scope="col">
								{ day }
							</th>
						) ) }
					</tr>
				</thead>
				<tbody>
					{ weeks.map( ( week, weekIndex ) => (
						<tr key={ weekIndex }>
							{ week.map( ( day, dayIndex ) => {
								if ( ! day ) {
									return <td key={ dayIndex } />;
								}
								const iso = toIsoDate(
									new Date( view.year, view.month, day )
								);
								// Fixed-width zero-padded dates compare correctly as strings.
								const isDisabled = !! min && iso < min;
								const isSelected = iso === value;
								return (
									<td key={ dayIndex }>
										<button
											type="button"
											className={ `bp-cb-aiw__datepop-day${
												isSelected ? ' is-selected' : ''
											}` }
											disabled={ isDisabled }
											aria-current={
												isSelected ? 'date' : undefined
											}
											onClick={ () => onSelect( iso ) }
										>
											{ day }
										</button>
									</td>
								);
							} ) }
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}
