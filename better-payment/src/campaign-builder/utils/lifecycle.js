/**
 * Builder-side reading of a campaign's lifecycle settings, for the Settings tab's hints.
 *
 * The rule itself is the server's (`Campaign\CampaignLifecycle`): it closes the
 * Donate Button and refuses payments. This only tells the author, while editing,
 * which of their settings will stop donations — so it mirrors the server's order
 * (Status → end date → goal) and its inclusive end date.
 */

/**
 * Whether an end date (Y-m-d) is already over: the whole day counts, so a campaign
 * ending today still takes donations. Uses the browser's clock and timezone — a hint,
 * not the rule.
 *
 * @param {string} endDate `bpc_end_date`.
 * @param {Date}   now     Current time.
 * @return {boolean} True once the end date's last second has passed.
 */
export const endDateHasPassed = ( endDate, now = new Date() ) => {
	const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(
		String( endDate ?? '' ).trim()
	);
	if ( ! match ) {
		return false;
	}
	const end = new Date(
		Number( match[ 1 ] ),
		Number( match[ 2 ] ) - 1,
		Number( match[ 3 ] ),
		23,
		59,
		59
	);

	return now > end;
};

/**
 * Why the campaign will not take donations, judged from its settings alone
 * (the goal rule needs the raised total, which only the server knows).
 *
 * @param {Object} meta Builder meta.
 * @param {Date}   now  Current time.
 * @return {''|'closed_manual'|'ended_date'} '' when nothing in the settings stops donations.
 */
export const settingsClosure = ( meta, now = new Date() ) => {
	const status = String( meta?.bpc_status ?? '' ).toLowerCase();
	if ( status === 'closed' || status === 'ended' ) {
		return 'closed_manual';
	}

	return endDateHasPassed( meta?.bpc_end_date, now ) ? 'ended_date' : '';
};
