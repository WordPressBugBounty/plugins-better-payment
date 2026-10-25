/**
 * The campaign page's minimum-donation check (fundraising-campaign.js).
 *
 * The check reads the amount the donor picked on the page, so it only applies to
 * a campaign copy that HAS an amount picker (the Donate Amount widget). Without
 * one the amount is entered later — in the popup's Amount field (Pro) or on the
 * form page — where the minimum is shown and enforced, and the server holds it
 * (PaymentRequestGuard). Checking the page there found 0 below the minimum on
 * every click, so such a campaign could never take a donation.
 */

/**
 * Whether this campaign copy offers an amount on the page.
 *
 * Scoped to the one `.bp-campaign` root: a campaign can be on a page twice.
 *
 * @param {Element|null|undefined} campaign The `.bp-campaign` root.
 * @return {boolean} True when the donor can pick or type an amount here.
 */
export function hasAmountPicker( campaign ) {
	return Boolean(
		campaign?.querySelector?.(
			'.bp-option-amount, .campaign-custom-amount'
		)
	);
}

/**
 * Whether the minimum stops this Donate click.
 *
 * @param {Object}  args
 * @param {number}  args.minimum   The campaign minimum (0 or less = none).
 * @param {number}  args.amount    The amount picked on the page (0 = none).
 * @param {boolean} args.hasPicker Whether the page offers an amount at all.
 * @return {boolean} True when the click must be stopped.
 */
export function blocksDonation( { minimum, amount, hasPicker } ) {
	if ( ! hasPicker || ! ( minimum > 0 ) ) {
		return false;
	}

	return ! ( amount >= minimum );
}
