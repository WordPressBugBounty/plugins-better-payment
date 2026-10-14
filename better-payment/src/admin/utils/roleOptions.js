/**
 * Option maps for the E-commerce > Subscription role selects.
 *
 * PHP localises the site's roles as a `role_key => display name` map in
 * `window.betterPaymentAdmin.userRoles` (`ReactAdmin::get_user_roles_list()`),
 * already translated through `translate_user_role()`. A saved role that no
 * longer exists on the site — say, one a membership plugin removed — is absent
 * from that map, so the select has to synthesise its own option or it would
 * silently display (and re-save) the wrong role.
 *
 * `customer` without WooCommerce is NOT such a case any more. It was the old
 * default of Role After Subscription Ends on every site, so a stored
 * `customer` the site lacks was never the admin's choice;
 * `DB::get_settings()` resolves it to `subscriber` before it reaches here
 * (`DB::resolve_legacy_inactive_role()`). Only a role the admin picked can be
 * shown as unavailable.
 *
 * That synthesised option used to carry the raw slug as its label, so the
 * select read `customer` in lower case beside real labels like `Customer` and
 * `Shop manager`, and nothing said the role was missing. The label is humanised
 * here instead — never with CSS `text-transform`, which cannot reach the
 * underscore in `shop_manager` and would also re-case WordPress's own labels
 * ("Shop manager" → "Shop Manager").
 */

import { __, sprintf } from '@wordpress/i18n';

/**
 * Turn a role slug into a readable label.
 *
 * Sentence case, like WordPress's own role names ("Shop manager", not "Shop
 * Manager"): separators become spaces and only the first word is capitalised.
 *
 * @param {string} slug The role key, e.g. `shop_manager`.
 * @return {string} A readable label, e.g. `Shop manager`.
 */
export function humanizeRoleSlug( slug ) {
	const words = String( slug ?? '' )
		.replace( /[_-]+/g, ' ' )
		.trim();

	if ( ! words ) {
		return '';
	}

	return words.charAt( 0 ).toUpperCase() + words.slice( 1 );
}

/**
 * The role options for a select, keeping a saved role that the site has lost.
 *
 * @param {Object} userRoles The localised `role_key => label` map.
 * @param {string} current   The currently saved role key.
 * @return {Object} `role_key => label`, with `current` always present.
 */
export function roleOptions( userRoles, current ) {
	const roles = userRoles || {};

	if ( ! current || roles[ current ] ) {
		return roles;
	}

	return {
		...roles,
		// Named as unavailable because the role is genuinely gone: saving it
		// assigns a role no user can hold. Says so in the one place the admin
		// looks, rather than leaving a bare slug to be read as a typo.
		[ current ]: sprintf(
			/* translators: %s: user role name. */
			__( '%s (unavailable)', 'better-payment' ),
			humanizeRoleSlug( current )
		),
	};
}
