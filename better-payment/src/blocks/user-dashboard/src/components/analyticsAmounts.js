/**
 * The User Dashboard block preview's amount cards.
 *
 * Amounts are never added across currencies. The server sends display strings per bucket
 * (`userAnalytics.formatted`, built by `Classes\CurrencyTotals::format_analytics()`, the same
 * strings the widget prints): `primary` is the headline in the primary currency and `others` lists
 * every other currency ('' when none).
 *
 * A payload without `formatted` (an older Better Payment) falls back to the old single figure.
 *
 * @param {Object} analytics `betterPaymentBlockData.userAnalytics`.
 * @param {string} bucket    `total` | `completed` | `incomplete` | `refunded`.
 * @return {{primary: string, others: string}} Display strings.
 */
export const amountText = ( analytics, bucket ) => {
	const formatted = analytics?.formatted?.[ bucket ];

	if ( typeof formatted?.primary === 'string' ) {
		return {
			primary: formatted.primary,
			others:
				typeof formatted?.others === 'string' ? formatted.others : '',
		};
	}

	const amount = parseFloat(
		analytics?.[ `${ bucket }_transactions_amount` ] ?? 0
	);

	return {
		primary: `$${ ( Number.isFinite( amount ) ? amount : 0 ).toFixed(
			2
		) }`,
		others: '',
	};
};

/** The amount cards the preview prints, in card order. */
export const AMOUNT_BUCKETS = [
	'total',
	'completed',
	'incomplete',
	'refunded',
];

/**
 * Every amount card's display strings, built once.
 *
 * The preview prints four cards and reads `primary` and `others` from each. It used to call
 * amountText() up to three times per card on every render; it now calls this once and reads
 * `amounts.total.primary`, `amounts.total.others`, and so on.
 *
 * @param {Object} analytics `betterPaymentBlockData.userAnalytics`.
 * @return {Object<string, {primary: string, others: string}>} Bucket => display strings.
 */
export const amountTexts = ( analytics ) =>
	Object.fromEntries(
		AMOUNT_BUCKETS.map( ( bucket ) => [
			bucket,
			amountText( analytics, bucket ),
		] )
	);
