/**
 * `Y-m-d H:i:s` (or `Y-m-d`) → the epoch milliseconds of that wall-clock time
 * read as UTC, or null.
 *
 * Built from the parts, never `new Date( 'Y-m-d H:i:s' )`: a string without a
 * zone is parsed in the BROWSER's timezone, so the result depended on where the
 * admin happened to be sitting.
 *
 * @param {string} value - Stored datetime.
 * @return {number|null} Milliseconds, or null when unparseable.
 */
const parseWpDateTime = ( value ) => {
	const parts =
		/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/.exec(
			String( value ?? '' ).trim()
		);

	if ( ! parts ) return null;

	const ms = Date.UTC(
		Number( parts[ 1 ] ),
		Number( parts[ 2 ] ) - 1,
		Number( parts[ 3 ] ),
		Number( parts[ 4 ] ?? 0 ),
		Number( parts[ 5 ] ?? 0 ),
		Number( parts[ 6 ] ?? 0 )
	);

	return isNaN( ms ) ? null : ms;
};

/**
 * The site's timezone, as WordPress resolves it (`wp_timezone_string()`):
 * an IANA name (`Asia/Dhaka`) or a fixed offset (`+06:00`).
 *
 * @return {string} Timezone, 'UTC' when unknown.
 */
const siteTimezone = () =>
	( typeof window !== 'undefined' &&
		window.betterPaymentAdmin?.siteTimezone ) ||
	'UTC';

/**
 * Format an instant in a timezone that may be an IANA name or a fixed offset.
 *
 * `Intl` does not take `+06:00` in every browser, so an offset is applied by
 * hand and the result formatted as UTC. An unknown zone falls back to UTC
 * rather than throwing (a RangeError used to take the whole list down).
 *
 * @param {number} ms       - Epoch milliseconds.
 * @param {string} timezone - IANA name or `±HH:MM`.
 * @param {string} locale   - BCP 47 locale tag.
 * @param {Object} options  - Intl.DateTimeFormat options (no timeZone).
 * @return {{formatter: Intl.DateTimeFormat, date: Date}} Formatter and the Date to format.
 */
const zonedFormat = ( ms, timezone, locale, options ) => {
	const offset = /^([+-])(\d{2}):?(\d{2})$/.exec( String( timezone ?? '' ) );

	if ( offset ) {
		const minutes =
			( offset[ 1 ] === '-' ? -1 : 1 ) *
			( Number( offset[ 2 ] ) * 60 + Number( offset[ 3 ] ) );

		return {
			formatter: new Intl.DateTimeFormat( locale, {
				...options,
				timeZone: 'UTC',
			} ),
			date: new Date( ms + minutes * 60000 ),
		};
	}

	let formatter;

	try {
		formatter = new Intl.DateTimeFormat( locale, {
			...options,
			timeZone: timezone || 'UTC',
		} );
	} catch ( e ) {
		formatter = new Intl.DateTimeFormat( locale, {
			...options,
			timeZone: 'UTC',
		} );
	}

	return { formatter, date: new Date( ms ) };
};

/**
 * A transaction's `payment_date` in the site's timezone — "10/11/26, 1:05 pm".
 *
 * `payment_date` is stored in UTC (every writer uses `gmdate()`, and the PHP
 * readers convert it with `wp_date()`), so it is read as UTC and converted to
 * `timezone`. It used to be read in the browser's timezone and then converted,
 * which showed the raw UTC value to anyone whose browser and site agree — every
 * payment six hours early on a UTC+6 site.
 *
 * @param {string} dateString       - Stored UTC datetime (`Y-m-d H:i:s`).
 * @param {Object} options          - Formatting options.
 * @param {string} options.timezone - Site timezone (IANA name or `±HH:MM`).
 * @param {string} options.locale   - BCP 47 locale tag.
 * @return {string} Formatted date+time, '' for empty/unparseable input.
 */
const formatDateTime = (
	dateString,
	{ timezone = siteTimezone(), locale = 'en-US' } = {}
) => {
	if ( ! dateString ) return '';

	const ms = parseWpDateTime( dateString );

	if ( ms === null ) return '';

	const { formatter, date } = zonedFormat( ms, timezone, locale, {
		year: '2-digit',
		month: 'numeric',
		day: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} );

	return formatter.format( date )?.toLocaleLowerCase();
};

/**
 * A `Date` as `YYYY-MM-DD` built from its LOCAL calendar parts.
 *
 * Never `toISOString().split('T')[0]` — that converts to UTC first, so local
 * midnight anywhere east of Greenwich falls on the previous UTC day and the
 * picked day is sent to the server off by one. (Same rule the campaign
 * builder's date popover follows.)
 *
 * @param {Date} date - Date object.
 * @return {string} `YYYY-MM-DD`, or '' for a missing/invalid date.
 */
const toIsoDate = ( date ) => {
	if ( ! ( date instanceof Date ) || isNaN( date.getTime() ) ) return '';

	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );

	return `${ date.getFullYear() }-${ month }-${ day }`;
};

/**
 * `YYYY-MM-DD` back to a local-midnight `Date` — the inverse of toIsoDate().
 *
 * `new Date( '2026-08-16' )` is parsed as UTC midnight by spec, which renders
 * as the 15th for anyone west of Greenwich, so a stored filter value would
 * reopen the calendar on the wrong day. Passing the parts separately builds
 * the date in local time.
 *
 * @param {string} value - `YYYY-MM-DD`.
 * @return {Date|null} Local-midnight Date, or null when unparseable.
 */
const parseIsoDate = ( value ) => {
	const parts = /^(\d{4})-(\d{2})-(\d{2})$/.exec( String( value ?? '' ) );

	if ( ! parts ) return null;

	const date = new Date(
		Number( parts[ 1 ] ),
		Number( parts[ 2 ] ) - 1,
		Number( parts[ 3 ] )
	);

	return isNaN( date.getTime() ) ? null : date;
};

/**
 * Compact date + time label — "Sept 13, 2026, 2:20 pm".
 *
 * The month is the long name truncated to 4 characters ("Sept", "Augu",
 * "July") — a deliberate product choice for the Subscriptions tab, not a
 * locale abbreviation ("Sep").
 *
 * The input is already SITE time (the subscription rows are built with
 * `wp_date()` / `WC_DateTime::date()`), so it is shown as the wall-clock time
 * it is, with no conversion — whatever the browser's timezone. Unlike
 * `formatDateTime()`, whose input is UTC.
 *
 * @param {string} dateString - Site-time datetime ("2026-09-13 14:20:00").
 * @param {Object} options    - Formatting options.
 * @param {string} options.locale - BCP 47 locale tag. (`timezone` is accepted
 *                                for compatibility and ignored: the value is
 *                                already in it.)
 * @return {string} Formatted date+time, '' for empty/unparseable input.
 */
const formatDateCompact = ( dateString, { locale = 'en-US' } = {} ) => {
	if ( ! dateString ) return '';

	const ms = parseWpDateTime( dateString );

	if ( ms === null ) return '';

	const parts = new Intl.DateTimeFormat( locale, {
		timeZone: 'UTC',
		year: 'numeric',
		month: 'long',
		day: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} ).formatToParts( new Date( ms ) );

	const get = ( type ) =>
		parts.find( ( part ) => part.type === type )?.value ?? '';

	const month = get( 'month' ).slice( 0, 4 );
	const dayPeriod = get( 'dayPeriod' ).toLowerCase();

	return `${ month } ${ get( 'day' ) }, ${ get( 'year' ) }, ${ get(
		'hour'
	) }:${ get( 'minute' ) } ${ dayPeriod }`.trim();
};

const formateName = ( data ) => {
	if ( ! data ) {
		return '';
	}
	let customerName = '';

	if ( data?.primary_first_name ) {
		customerName += data.primary_first_name + ' ';
	}
	if ( data?.primary_last_name ) {
		customerName += data.primary_last_name;
	}

	// Legacy support
	if ( customerName === '' ) {
		if ( data?.first_name ) {
			customerName += data.first_name + ' ';
		}
		if ( data?.last_name ) {
			customerName += data.last_name;
		}
	}

	return customerName;
};

const emailAddress = ( data ) => {
	if ( ! data ) {
		return '';
	}
	let email = '';

	if ( data?.primary_email ) {
		email = data.primary_email;
	}

	// Legacy support
	if ( email === '' ) {
		if ( data?.email ) {
			email = data.email;
		}
	}

	return email;
};

// Status mappings (v1)
const transactionStatusesV1 = {
	completed: {
		// 'no_payment_required' is Stripe's status for a fully-discounted /
		// $0 Checkout Session (100% coupon or sale, free trial). The payment
		// settled — there was nothing to charge — so it belongs here, not in
		// the unknown-status fallback that renders "Incomplete".
		statuses: [ 'paid', 'Completed', 'completed', 'no_payment_required' ],
		color: '#0ECA86',
	},
	processing: {
		statuses: [ 'pending', 'Pending', null ],
		color: '#FFDA15',
	},
	refunded: {
		statuses: [ 'refunded', 'Refunded' ],
		color: '#FF0202',
	},
	unpaid: {
		statuses: [ 'unpaid', 'failed' ],
		color: '#999',
	},
	all: {
		statuses: [
			'paid',
			'Completed',
			'completed',
			'no_payment_required',
			'unpaid',
			'refunded',
			'pending',
			'Pending',
			'failed',
			null,
		],
		color: '#999',
	},
};

// Status mappings (v2)
const transactionStatusesV2 = {
	completed: {
		// See the v1 note — mirrors Helper::transaction_statuses_with_type_v2()
		// in PHP, which is also the source of the `status IN (…)` filter the
		// list query builds. The two must not drift.
		statuses: [
			'paid',
			'Completed',
			'completed',
			'success',
			'no_payment_required',
		],
		color: '#0ECA86',
	},
	incomplete: {
		statuses: [
			'pending',
			'Pending',
			'unpaid',
			'incomplete',
			'Incomplete',
			// A Stripe checkout that expired or whose delayed payment failed.
			'failed',
			null,
		],
		color: '#FFDA15',
	},
	refunded: {
		statuses: [ 'refunded', 'Refunded' ],
		color: '#FF0202',
	},
	all: {
		statuses: [
			'paid',
			'Completed',
			'completed',
			'no_payment_required',
			'unpaid',
			'refunded',
			'Refunded',
			'pending',
			'Pending',
			'success',
			'incomplete',
			'Incomplete',
			'failed',
			null,
		],
		color: '#999',
	},
};

/**
 * Get the type of a transaction status
 * @param {string|null} status  - Transaction status
 * @param {'v1'|'v2'}   version - Version of status mapping
 * @return {string} status type
 */
const getTypeByTransactionStatus = ( status = '', version = 'v1' ) => {
	const statusesMap =
		version === 'v1' ? transactionStatusesV1 : transactionStatusesV2;

	if ( ! status ) return 'incomplete';

	for ( const [ type, info ] of Object.entries( statusesMap ) ) {
		if ( info.statuses.includes( status ) ) {
			return type;
		}
	}

	return 'incomplete';
};

/**
 * The v2 bucket a transaction row is labelled under.
 *
 * Prefers the server-computed `status_type` (PHP Helper::transaction_status_type(), on
 * every AdminAPI transaction row): a raw `no_payment_required` status cannot say whether a
 * pre-2.4.0 free checkout was ever confirmed, and an unconfirmed one is in no count, so it
 * reads Incomplete. A response without the field (Pro's bundle, an older API) falls back
 * to the raw-status map above.
 *
 * @param {string|null} status     - Raw transaction status.
 * @param {string}      statusType - Server-computed bucket, when the response has one.
 * @return {string} 'completed' | 'incomplete' | 'refunded'
 */
const transactionStatusType = ( status, statusType ) => {
	if (
		typeof statusType === 'string' &&
		statusType !== 'all' &&
		Object.keys( transactionStatusesV2 ).includes( statusType )
	) {
		return statusType;
	}

	return getTypeByTransactionStatus( status, 'v2' );
};

/**
 * Status label of a transaction row ("Completed", "Incomplete", "Refunded").
 *
 * @param {string|null} status     - Raw transaction status.
 * @param {string}      statusType - Server-computed `status_type`, when the row has one.
 * @return {string} Label.
 */
const paymentStatus = ( status, statusType ) => {
	const type = transactionStatusType( status, statusType );
	return type.charAt( 0 ).toUpperCase() + type.slice( 1 );
};

/**
 * Whether the details page offers "Mark as Completed" for a row.
 *
 * Only an Incomplete row, and never a pre-2.4.0 `no_payment_required` row: it stores the
 * form's full amount although nothing was charged, so `Completed` would count money that
 * never arrived. The REST route refuses it too (AdminAPI::mark_transaction_complete()).
 *
 * @param {Object|null} transaction - Transaction row.
 * @return {boolean} Whether to offer the action.
 */
const canMarkTransactionComplete = ( transaction ) => {
	if ( ! transaction ) {
		return false;
	}

	return (
		transactionStatusType(
			transaction?.status,
			transaction?.status_type
		) === 'incomplete' && transaction?.status !== 'no_payment_required'
	);
};

// E-commerce billing period => single-cycle label (interval 1) and plural.
const billingPeriods = {
	day: { single: 'Daily', plural: 'days' },
	week: { single: 'Weekly', plural: 'weeks' },
	month: { single: 'Monthly', plural: 'months' },
	year: { single: 'Yearly', plural: 'years' },
};

/**
 * Billing-cycle label for an e-commerce subscription row on the admin
 * Subscriptions tab (mirrors PHP Subscriptions::describe_schedule()).
 *
 * @param {number|string} interval - Billing interval count (e.g. 6).
 * @param {string}        period   - 'day' | 'week' | 'month' | 'year'.
 * @return {string} 'Monthly', 'Every 6 months', … or '—' when unknown —
 *                   e.g. an un-hydrated row whose integration is inactive.
 */
const ecommerceBillingLabel = ( interval, period ) => {
	const count = parseInt( interval );
	const cycle = billingPeriods[ String( period ?? '' ).toLowerCase() ];

	if ( ! cycle || ! ( count >= 1 ) ) {
		return '—';
	}

	return count === 1 ? cycle.single : `Every ${ count } ${ cycle.plural }`;
};

// E-commerce subscription lifecycle status => display label + existing `.tag`
// CSS class (completed = green, incomplete = amber, refunded = red — reused
// so the tab needs no new status styles).
const ecommerceStatuses = {
	active: { label: 'Active', tagClass: 'completed' },
	past_due: { label: 'Past Due', tagClass: 'incomplete' },
	cancelled: { label: 'Cancelled', tagClass: 'refunded' },
	completed: { label: 'Completed', tagClass: 'completed' },
};

/**
 * Display info for an e-commerce subscription status.
 *
 * @param {string} status - Raw `_bp_subscription_status` meta value.
 * @return {{label: string, tagClass: string}} Unknown/empty statuses come
 *          back as an amber 'Unknown' rather than being invisible.
 */
const ecommerceSubscriptionStatus = ( status ) => {
	return (
		ecommerceStatuses[ String( status ?? '' ).toLowerCase() ] ?? {
			label: 'Unknown',
			tagClass: 'incomplete',
		}
	);
};

// Statuses the table can render but the Status filter does not offer.
// `completed` is set by no core flow — WooCommerce\Subscriptions only ever
// writes active/past_due/cancelled (the renewal-cap feature that completed a
// subscription was removed), so the option could do nothing but return the
// "No Matching Subscriptions" empty state on every install. It stays in
// `ecommerceStatuses` above on purpose: an integration may still complete a
// subscription by writing the meta itself, and such a row must render as a
// green "Completed" tag rather than an amber "Unknown".
const nonFilterableStatuses = [ 'completed' ];

/**
 * Options for the Subscriptions tab's Status filter dropdown, derived from the
 * `ecommerceStatuses` map above so the dropdown and the status tags rendered in
 * the table can never spell a status differently ("Cancelled" vs "Canceled" is
 * exactly the kind of drift a second hardcoded list produces). The filter may
 * offer FEWER statuses than the table renders (see `nonFilterableStatuses`),
 * never more and never a different label.
 *
 * The leading 'all' entry is FilterDropdown's own convention: an option labelled
 * 'All' makes the trigger fall back to showing the filter's name ("Status") and
 * marks the control inactive. The server treats 'all' as "no status filter"
 * (Admin\SubscriptionListFilter::sanitize_status), so the request always carries
 * the control's state rather than omitting the param.
 *
 * @return {Array<{label: string, value: string}>} Dropdown options.
 */
const ecommerceStatusOptions = () => [
	{ label: 'All', value: 'all' },
	...Object.entries( ecommerceStatuses )
		.filter( ( [ value ] ) => ! nonFilterableStatuses.includes( value ) )
		.map( ( [ value, { label } ] ) => ( {
			label,
			value,
		} ) ),
];

/**
 * Renewals-so-far label for the subscription details view.
 *
 * @param {number|string} count - Renewal payments settled so far.
 * @return {string} The label; '0' for an un-hydrated/blank count.
 */
const ecommerceRenewalsLabel = ( count ) => {
	const renewals = parseInt( count );

	return `${ renewals >= 0 ? renewals : 0 }`;
};

export {
	formatDateTime,
	formatDateCompact,
	parseWpDateTime,
	siteTimezone,
	toIsoDate,
	parseIsoDate,
	formateName,
	emailAddress,
	paymentStatus,
	transactionStatusType,
	canMarkTransactionComplete,
	ecommerceBillingLabel,
	ecommerceSubscriptionStatus,
	ecommerceStatusOptions,
	ecommerceRenewalsLabel,
};
