import { __, sprintf } from '@wordpress/i18n';

/**
 * Campaign filter + column helpers for the Transactions screen.
 *
 * A transaction row's `campaign_id` is either a Campaign Builder campaign (its post id,
 * `"42"`) or an Elementor Fundraising Campaign widget (`bp_campaign_{page}_{postfix}`).
 * The server describes each one (`campaign` on every row, `GET /transactions/campaigns`
 * for the filter) — see `Classes\TransactionCampaign`. These helpers stay pure so Jest
 * can cover them without mounting the page.
 */

const CAMPAIGN_ID_PATTERN = /^(\d+|bp_campaign_\d+_[\w-]+)$/;

/**
 * A `campaign_id` filter value, or '' when it is not one a row could hold.
 * Mirrors `TransactionCampaign::sanitize_filter()`.
 *
 * @param {*} value Raw value.
 * @return {string} The value, or ''.
 */
export const sanitizeCampaignFilter = ( value ) => {
	const id = String( value ?? '' ).trim();

	return id !== '' && id !== '0' && CAMPAIGN_ID_PATTERN.test( id ) ? id : '';
};

/**
 * The campaign filter the page opens with: `?campaign_id=` from the Campaigns screen's
 * "View transactions" link, or none.
 *
 * @param {string} search `window.location.search`.
 * @return {string} Campaign id, or ''.
 */
export const initialCampaignFilter = ( search = '' ) =>
	sanitizeCampaignFilter(
		new URLSearchParams( search ).get( 'campaign_id' )
	);

/**
 * FilterDropdown options: "All" first, then each campaign by the server's order.
 *
 * A campaign the URL asked for but the list does not hold (no transactions yet) still
 * gets an option, so the dropdown never shows "Campaign" while the list is filtered.
 *
 * @param {Array}  campaigns `GET /transactions/campaigns` → `campaigns`.
 * @param {string} selected  The active filter.
 * @return {Array<{label: string, value: string}>} Options.
 */
export const campaignFilterOptions = ( campaigns = [], selected = '' ) => {
	const options = [ { label: 'All', value: '' } ];

	( Array.isArray( campaigns ) ? campaigns : [] ).forEach( ( campaign ) => {
		const id = sanitizeCampaignFilter( campaign?.id );
		if ( id !== '' ) {
			options.push( { label: campaign?.title || id, value: id } );
		}
	} );

	if (
		selected !== '' &&
		! options.some( ( opt ) => opt.value === selected )
	) {
		options.push( {
			/* translators: %s: campaign id. */
			label: sprintf( __( 'Campaign #%s', 'better-payment' ), selected ),
			value: selected,
		} );
	}

	return options;
};

/**
 * What the Campaign column and the details view show for a row.
 *
 * @param {Object} transaction A transaction row (`campaign`, `campaign_id`).
 * @return {{title: string, url: string}|null} Null when the row has no campaign.
 */
export const campaignDisplay = ( transaction ) => {
	const campaign = transaction?.campaign;

	if ( campaign?.title ) {
		return { title: campaign.title, url: campaign?.edit_url ?? '' };
	}

	const id = sanitizeCampaignFilter( transaction?.campaign_id );

	return id !== ''
		? {
				/* translators: %s: campaign id. */
				title: sprintf( __( 'Campaign #%s', 'better-payment' ), id ),
				url: '',
		  }
		: null;
};

/**
 * The Transactions screen filtered to one campaign — the Campaigns screen links here.
 *
 * @param {string}     adminUrl Any wp-admin URL (its directory is kept).
 * @param {string|int} id       Campaign id.
 * @return {string} URL.
 */
export const transactionsUrlForCampaign = ( adminUrl, id ) => {
	const base = String( adminUrl || '/wp-admin/' )
		.split( '?' )[ 0 ]
		.replace( /[^/]*$/, '' );
	const campaign = sanitizeCampaignFilter( id );

	return `${ base }admin.php?page=better-payment-admin&tab=transactions${
		campaign ? `&campaign_id=${ encodeURIComponent( campaign ) }` : ''
	}`;
};
