/**
 * The status badge a campaign gets on the Campaigns screen.
 *
 * The server decides whether a published campaign still takes donations
 * (`Campaign\CampaignLifecycle`, sent as `lifecycle` on each campaign): its end
 * date (inclusive, in the site's timezone), a reached goal with "Close when the
 * goal is reached" on, or Status set to Closed. The browser used to compare the
 * end date itself with `new Date( endDate )`, which parses a bare date as UTC
 * midnight — a day early in every timezone west of UTC — and knew nothing about
 * the goal or the Status setting.
 *
 * @param {string} postStatus WordPress post status.
 * @param {string} lifecycle  `accepting` | `ended_date` | `ended_goal` | `closed_manual`.
 * @param {string} endDate    `bpc_end_date`, used only when no lifecycle was sent (an older server).
 * @return {string} `trash` | `draft` | `active` | `finished` | `closed`.
 */
export const campaignStatus = ( postStatus, lifecycle, endDate ) => {
	if ( postStatus === 'trash' ) return 'trash';
	if ( postStatus === 'draft' ) return 'draft';

	if ( lifecycle ) {
		if ( lifecycle === 'accepting' ) return 'active';
		return lifecycle === 'closed_manual' ? 'closed' : 'finished';
	}

	if ( ! endDate ) return 'active';
	const end = new Date( `${ endDate }T23:59:59` );
	return ! isNaN( end.getTime() ) && end < new Date() ? 'finished' : 'active';
};
