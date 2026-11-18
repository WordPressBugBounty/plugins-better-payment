/**
 * Is Better Payment Pro active, as the admin page was told?
 *
 * Reads `window.betterPaymentAdmin.proEnabled`, which `Admin\ReactAdmin` sets from
 * `apply_filters( 'better_payment/pro_enabled', false )`. Free upsell UI in the admin (the sale
 * banner) asks this, so a paying customer is never shown an offer for what they own.
 *
 * The value is parsed, not compared with `=== true`: see `parseProEnabled()` for why.
 */
import { parseProEnabled } from '../../campaign-builder/utils/proEnabled';

/**
 * @param {Object} [data] The admin payload; defaults to `window.betterPaymentAdmin`.
 * @return {boolean} True when Pro is active.
 */
export const isProEnabled = ( data = window?.betterPaymentAdmin ) =>
	parseProEnabled( data?.proEnabled );
