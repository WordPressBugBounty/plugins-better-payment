/**
 * The admin lists' date-range filter (Transactions, Subscriptions), 2.5.1.
 *
 * A list with no date filter shows every row, so "All Time" is the empty filter: both ends ''.
 * The picker still shows it as the list's span, oldest day to today. These helpers turn the filter (two `YYYY-MM-DD` strings) into the picker's selection and back,
 * and word the trigger the way Pro's Analytics date range does ("Oct 28, 2026 - Nov 11, 2026").
 */
import { toIsoDate, parseIsoDate } from './helper';

const MONTHS = [
	'Jan',
	'Feb',
	'Mar',
	'Apr',
	'May',
	'Jun',
	'Jul',
	'Aug',
	'Sep',
	'Oct',
	'Nov',
	'Dec',
];

/**
 * A local date as "Oct 28, 2026".
 *
 * @param {Date} date Date.
 * @return {string} Label.
 */
const shortDate = ( date ) =>
	`${ MONTHS[ date.getMonth() ] } ${ String( date.getDate() ).padStart(
		2,
		'0'
	) }, ${ date.getFullYear() }`;

/**
 * Whether a filter is All Time: no usable start or end.
 *
 * @param {string} dateFrom `YYYY-MM-DD` or ''.
 * @param {string} dateTo   `YYYY-MM-DD` or ''.
 * @return {boolean} All Time.
 */
export const isAllTimeFilter = ( dateFrom, dateTo ) =>
	! parseIsoDate( dateFrom ) || ! parseIsoDate( dateTo );

/**
 * The trigger's text: "Oct 28, 2026 - Nov 11, 2026", one date for a single day, or the All Time
 * label for no filter.
 *
 * @param {string} dateFrom     `YYYY-MM-DD` or ''.
 * @param {string} dateTo       `YYYY-MM-DD` or ''.
 * @param {string} allTimeLabel The translated "All Time".
 * @return {string} Label.
 */
export const dateRangeLabel = ( dateFrom, dateTo, allTimeLabel ) => {
	if ( isAllTimeFilter( dateFrom, dateTo ) ) {
		return allTimeLabel;
	}

	const from = shortDate( parseIsoDate( dateFrom ) );
	const to = shortDate( parseIsoDate( dateTo ) );

	return from === to ? from : `${ from } - ${ to }`;
};

/**
 * Where All Time starts: the list's oldest day (`first_date` from its count route, `YYYY-MM-DD`)
 * as a local date, or today when there is none, it is malformed, or it lies in the future.
 *
 * @param {*}    firstDate The list's `first_date`.
 * @param {Date} today     Today, at local midnight.
 * @return {Date} Start.
 */
export const allTimeStart = ( firstDate, today ) => {
	const date = parseIsoDate( firstDate );

	return date && date <= today ? date : today;
};

/**
 * The picker's selection for a filter. All Time shows the list's whole span, from its oldest day
 * to today, like Pro's Analytics date range: selected on the calendar, both dates in the inputs.
 *
 * @param {string} dateFrom  `YYYY-MM-DD` or ''.
 * @param {string} dateTo    `YYYY-MM-DD` or ''.
 * @param {Date}   allStart  allTimeStart().
 * @param {Date}   today     Today, at local midnight.
 * @return {{startDate: Date, endDate: Date, key: string, allTime: boolean}} Selection.
 */
export const selectionFromFilter = ( dateFrom, dateTo, allStart, today ) =>
	isAllTimeFilter( dateFrom, dateTo )
		? {
				startDate: allStart,
				endDate: today,
				key: 'selection',
				allTime: true,
		  }
		: {
				startDate: parseIsoDate( dateFrom ),
				endDate: parseIsoDate( dateTo ),
				key: 'selection',
				allTime: false,
		  };

/**
 * Whether a picked selection is still All Time: the All Time preset sets the flag, and it holds
 * only while the dates are still its span (a picked range keeps the previous one's fields).
 *
 * @param {Object} selection Picker selection.
 * @param {Date}   allStart  allTimeStart().
 * @param {Date}   today     Today, at local midnight.
 * @return {boolean} All Time.
 */
export const isAllTimeSelection = ( selection, allStart, today ) =>
	selection?.allTime === true &&
	toIsoDate( selection?.startDate ) === toIsoDate( allStart ) &&
	toIsoDate( selection?.endDate ) === toIsoDate( today );

/**
 * The filter a selection applies: both ends as local `YYYY-MM-DD`, or '' / '' for All Time (an
 * open or half-picked range included: a list filters on both ends or not at all).
 *
 * @param {Object} selection Picker selection.
 * @return {{dateFrom: string, dateTo: string}} Filter.
 */
export const filterFromSelection = ( selection ) => {
	const dateFrom = toIsoDate( selection?.startDate );
	const dateTo = toIsoDate( selection?.endDate );

	return selection?.allTime === true || dateFrom === '' || dateTo === ''
		? { dateFrom: '', dateTo: '' }
		: { dateFrom, dateTo };
};
