/**
 * The campaign's currency, as the builder reads it.
 *
 * A campaign keeps the currency it was created in (`bpc_currency`, stamped by
 * Campaign\CampaignCurrency on the server). A campaign created before 2.5.0 has none
 * (`''`) and follows the site currency, `window.betterPaymentCampaignData.globalCurrency`.
 *
 * The open campaign's currency is set once per render by App.js
 * (`setActiveCampaignCurrency()`), before any child renders, so components and helpers
 * with no access to the store's meta (the amount editors, the AI suggestion labels) read
 * the same code through `getCampaignCurrencySymbol()`.
 */

const data = () => window?.betterPaymentCampaignData ?? {};

let activeCurrency = '';

/**
 * Site currency (Settings → General), USD when unset.
 *
 * @return {string} ISO code.
 */
export function getSiteCurrency() {
	const code = String( data().globalCurrency ?? '' )
		.trim()
		.toUpperCase();
	return code || 'USD';
}

/**
 * A campaign's currency from its meta: its own code, or the site's when it has none.
 *
 * @param {Object} meta Campaign meta.
 * @return {string} ISO code.
 */
export function resolveCampaignCurrency( meta ) {
	const code = String( meta?.bpc_currency ?? '' )
		.trim()
		.toUpperCase();
	return /^[A-Z]{3}$/.test( code ) ? code : getSiteCurrency();
}

/**
 * Remember the open campaign's currency (App.js, every render).
 *
 * @param {Object} meta Campaign meta.
 */
export function setActiveCampaignCurrency( meta ) {
	// The campaign's own code only ('' when it has none), so the site fallback is
	// read when asked, not frozen here.
	const code = String( meta?.bpc_currency ?? '' )
		.trim()
		.toUpperCase();
	activeCurrency = /^[A-Z]{3}$/.test( code ) ? code : '';
}

/**
 * The open campaign's currency.
 *
 * @return {string} ISO code.
 */
export function getCampaignCurrency() {
	return activeCurrency || getSiteCurrency();
}

/**
 * The symbol for a currency code; `$` when Intl cannot name it.
 *
 * @param {string} currency ISO code.
 * @return {string} Symbol.
 */
export function currencySymbol( currency ) {
	try {
		return (
			new Intl.NumberFormat( 'en-US', { style: 'currency', currency } )
				.formatToParts( 0 )
				.find( ( p ) => p.type === 'currency' )?.value || '$'
		);
	} catch {
		return '$';
	}
}

/**
 * The open campaign's currency symbol.
 *
 * @return {string} Symbol.
 */
export function getCampaignCurrencySymbol() {
	return currencySymbol( getCampaignCurrency() );
}

/**
 * Settings → Currency's choices as select options, sorted by code.
 *
 * @return {Array<{value: string, label: string}>} Options.
 */
export function currencyOptions() {
	const list = data().currencies ?? {};
	return Object.keys( list )
		.sort()
		.map( ( code ) => ( {
			value: code,
			label: `${ code } — ${ list[ code ] }`,
		} ) );
}

/**
 * Enabled gateways that cannot charge in a currency.
 *
 * Mirrors Campaign\CampaignCurrency::unsupported_gateways(): PayPal refuses the codes in
 * its `unsupported` list, Paystack takes only its `supported` list, Stripe takes all.
 *
 * @param {string} currency ISO code.
 * @param {Object} gateways `betterPaymentCampaignData.gatewayCurrencies`.
 * @return {string[]} Gateway slugs (`paypal`, `paystack`).
 */
export function unsupportedGateways(
	currency,
	gateways = data().gatewayCurrencies
) {
	const code = String( currency ?? '' ).toUpperCase();
	const out = [];
	if (
		gateways?.paypal?.enabled &&
		( gateways.paypal.unsupported ?? [] ).includes( code )
	) {
		out.push( 'paypal' );
	}
	if (
		gateways?.paystack?.enabled &&
		! ( gateways.paystack.supported ?? [] ).includes( code )
	) {
		out.push( 'paystack' );
	}
	return out;
}
