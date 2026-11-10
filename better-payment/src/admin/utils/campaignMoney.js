/**
 * Money a campaign raised in currencies other than the site's.
 *
 * `stats.total_raised` is the site currency's sum only; donations taken in another
 * currency (the site currency was changed after they came in) sit in
 * `stats.raised_by_currency` and are listed beside it, never added to it. Same
 * rule as PHP's CampaignStats::other_currencies().
 *
 * @param {Object} stats Campaign stats from the REST API.
 * @return {string} "ZAR 15.00 + EUR 2.50", or '' when there is none.
 */
export const otherCurrencyAmounts = ( stats ) => {
	const site = String( stats?.currency ?? '' ).toUpperCase();
	const byCurrency = stats?.raised_by_currency ?? {};

	return Object.entries( byCurrency )
		.filter(
			( [ code, amount ] ) =>
				code.toUpperCase() !== site && parseFloat( amount ) > 0
		)
		.map(
			( [ code, amount ] ) =>
				`${ code } ${ parseFloat( amount ).toLocaleString( 'en-US', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2,
				} ) }`
		)
		.join( ' + ' );
};
