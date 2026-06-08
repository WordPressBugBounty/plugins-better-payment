/**
 * Returns the currency symbol for the globally configured plugin currency.
 * Reads from window.betterPaymentCampaignData.globalCurrency (localized by CPT.php).
 * Falls back to '$' if unavailable or Intl is unsupported.
 */
export function getGlobalCurrencySymbol() {
    const currency = window?.betterPaymentCampaignData?.globalCurrency || 'USD';
    try {
        return new Intl.NumberFormat( 'en-US', { style: 'currency', currency } )
            .formatToParts( 0 )
            .find( ( p ) => p.type === 'currency' )?.value || '$';
    } catch {
        return '$';
    }
}
