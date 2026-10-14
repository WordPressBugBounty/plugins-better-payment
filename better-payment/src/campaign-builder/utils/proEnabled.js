/**
 * Is Better Payment Pro active?
 *
 * Reads `window.betterPaymentCampaignData.proEnabled`, which PHP sets from
 * `apply_filters( 'better_payment/pro_enabled', false )`.
 *
 * ## Why this is a function and not `=== true`
 *
 * It used to be `window?.betterPaymentCampaignData?.proEnabled === true`, inlined
 * in two files. The payload was sent with `wp_localize_script()`, which casts every
 * scalar to a string on the way out — so a `true` from PHP arrived as the string
 * `"1"`, `"1" === true` is false, and **every install behaved as if Pro were
 * inactive**: crowns on the palette, the whole settings panel disabled, the upgrade
 * banner shown to paying customers. Nothing in PHP was wrong; the boolean died in
 * transport, and the strict check turned a lossy channel into a silent product bug.
 *
 * PHP now sends real JSON (`wp_add_inline_script` + `wp_json_encode`), so `true`
 * really is `true`. This stays tolerant anyway: it is a value crossing the PHP→JS
 * boundary, where WordPress has more than one way to hand it over, and the cost of
 * accepting `"1"` is one comparison against the cost of silently re-shipping that
 * bug the next time someone reaches for `wp_localize_script`.
 *
 * Deliberately **not** a bare truthy check — `"0"` and `""` are the string forms of
 * `false` and must stay falsy, which `!! "0"` gets wrong.
 *
 * @param {*} raw Raw value as it arrived from PHP.
 * @return {boolean} True only for an affirmative value.
 */
export function parseProEnabled( raw ) {
	if ( typeof raw === 'string' ) {
		return raw === '1' || raw === 'true';
	}

	return raw === true || raw === 1;
}

/**
 * Read the live Pro entitlement off the localized builder payload.
 *
 * @return {boolean} True when Pro is active.
 */
export function isProEnabled() {
	return parseProEnabled( window?.betterPaymentCampaignData?.proEnabled );
}

/**
 * Is this element type locked — a Pro element on a site without Pro?
 *
 * The one definition of "locked", shared by the three places that gate on it: the
 * Elements palette card, the Customize panel's upgrade banner, and the canvas lock
 * overlay. They agreed by coincidence when each spelled the expression
 * out itself; a fourth surface would have been the one to drift, and a surface that
 * disagrees either sells Pro to someone who owns it or hands Pro away for free.
 *
 * `def` is the element *definition* from the registry (carrying the `pro` flag),
 * not a placed element instance. The flag always rides the schema — it is a
 * description of the element, never an entitlement. Entitlement is `proEnabled`.
 *
 * @param {Object|undefined}  def        Element definition from `getElement()`.
 * @param {boolean}           proEnabled Whether Pro is active.
 * @return {boolean} True when the element should render its locked state.
 */
export function isElementLocked( def, proEnabled ) {
	return !! def?.pro && ! proEnabled;
}

/**
 * The upgrade feature list for a locked element's Customize-panel banner.
 *
 * On a free install Lite registers only a lock stub for each Pro element
 * (PHP `ProElementStubs`): no `settingsSchema`, and a short translated
 * `proFeatures` list saying what Pro adds. The list crosses the PHP→JS boundary,
 * so anything that is not a non-empty string is dropped rather than rendered.
 * A definition without the key (a third-party `pro` element, or Pro's full
 * definition) yields `[]`, and the banner shows no list.
 *
 * @param {Object|undefined} def Element definition from `getElement()`.
 * @return {string[]} Feature lines, possibly empty.
 */
export function lockedElementFeatures( def ) {
	const features = def?.proFeatures;

	if ( ! Array.isArray( features ) ) {
		return [];
	}

	return features.filter(
		( feature ) => typeof feature === 'string' && feature.trim() !== ''
	);
}

/**
 * Is this campaign template locked — a Pro template on a site without Pro?
 *
 * The template-picker counterpart to {@link isElementLocked}, and kept beside it
 * for the same reason: the crown badge, the hover CTA, the click handler and the
 * lightbox footer all have to agree about one card, and four inline
 * `tpl.pro && ! proEnabled` expressions are four chances to disagree. A surface
 * that disagrees either sells Pro to someone who already bought it or lets a free
 * user apply a Pro design.
 *
 * `pro` is set on the card by PHP's `ProTemplateCatalog`, which only contributes
 * cards while Pro is inactive — so in practice `proEnabled` is already false
 * whenever `tpl.pro` is true. It is still checked, because entitlement is read
 * live everywhere else in the builder and a flag that arrived with stored data
 * must never be the thing that decides it.
 *
 * @param {Object|undefined} tpl        Template card from `betterPaymentCampaignData.templates`.
 * @param {boolean}          proEnabled Whether Pro is active.
 * @return {boolean} True when the card should render its locked state.
 */
export function isTemplateLocked( tpl, proEnabled ) {
	return !! tpl?.pro && ! proEnabled;
}
