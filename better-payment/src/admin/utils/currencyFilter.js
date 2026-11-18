/**
 * The Transactions screen's currency filter options.
 *
 * Offers the currencies the site has actually taken payments in
 * (`betterPaymentAdmin.currenciesInUse`, from `DB::currencies_in_use()`), so the list is short
 * and every choice matches something. Falls back to every code Better Payment knows
 * (`betterPaymentAdmin.currencies`) when that list is missing (an older payload) or empty.
 *
 * @param {string[]|undefined}             inUse Codes stored on transactions.
 * @param {Object<string, string>|string[]} all   Every known code.
 * @return {{label: string, value: string}[]} Options, "All" first.
 */
export const currencyFilterOptions = ( inUse, all ) => {
	const used = Array.isArray( inUse )
		? inUse.filter( ( code ) => typeof code === 'string' && code !== '' )
		: [];
	const codes = used.length > 0 ? used : Object.values( all ?? {} );

	return [
		{ label: 'All', value: 'all' },
		...[ ...new Set( codes ) ].map( ( code ) => ( {
			label: code,
			value: code,
		} ) ),
	];
};
