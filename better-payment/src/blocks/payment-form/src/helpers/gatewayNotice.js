/**
 * Why the Payment Form block has nothing to render, when it has nothing to render.
 *
 * A form with no payment method enabled used to render as **literally nothing**
 * in the editor: each layout began with `if (!paypalEnabled && !stripeEnabled &&
 * !paystackEnabled) return null`, so inserting the block on a site that had not
 * set up a gateway yet produced an empty block with no hint that anything was
 * wrong, no way to tell it apart from a broken block, and nothing naming the
 * setting that fixes it.
 *
 * There are two different reasons for it and they need two different answers,
 * which is why this is a state rather than one boolean:
 *
 * - `global` — no gateway is enabled in Better Payment's own settings. Every
 *   newly inserted block seeds its toggles from there (`mapSettingsToAttributes`),
 *   so the block is off because the site is. The fix is in Settings.
 * - `block` — the site has a gateway, but all three of THIS block's toggles are
 *   off. Sending that user to Settings would be wrong: the gateway they need is
 *   already enabled there, and the switch that is off is in the block inspector.
 *
 * Both are decided from the block's own attributes plus the settings blob the
 * editor is given, so this module stays pure and testable.
 */

/**
 * The three gateways, and where each one's state is read from.
 *
 * `label` is a brand name, deliberately not translated.
 */
export const PAYMENT_GATEWAYS = [
	{
		slug: 'paypal',
		attribute: 'paypalEnabled',
		settingsKey: 'better_payment_settings_general_general_paypal',
		label: 'PayPal',
	},
	{
		slug: 'stripe',
		attribute: 'stripeEnabled',
		settingsKey: 'better_payment_settings_general_general_stripe',
		label: 'Stripe',
	},
	{
		slug: 'paystack',
		attribute: 'paystackEnabled',
		settingsKey: 'better_payment_settings_general_general_paystack',
		label: 'Paystack',
	},
];

/**
 * The gateways this block is set to offer.
 *
 * @param {Object} attributes Block attributes.
 * @return {Array<Object>} The matching entries of `PAYMENT_GATEWAYS`.
 */
export function blockGateways( attributes ) {
	const attrs = attributes || {};
	return PAYMENT_GATEWAYS.filter(
		( gateway ) => !! attrs[ gateway.attribute ]
	);
}

/**
 * The gateways the site has enabled in Better Payment's settings.
 *
 * Settings are stored as `'yes'` / `'no'` strings, so this is an equality test,
 * not a truthiness one — `'no'` is a non-empty string.
 *
 * @param {Object} settings `betterPaymentBlockData.betterPaymentSettings`.
 * @return {Array<Object>} The matching entries of `PAYMENT_GATEWAYS`.
 */
export function siteGateways( settings ) {
	const values = settings || {};
	return PAYMENT_GATEWAYS.filter(
		( gateway ) => values[ gateway.settingsKey ] === 'yes'
	);
}

/**
 * Whether the block can render a form, and if not, why.
 *
 * @param {Object} args            Arguments.
 * @param {Object} args.attributes Block attributes.
 * @param {Object} args.settings   The editor's settings blob.
 * @return {null|{reason: string, siteGateways: Array<Object>}} `null` when the
 *         block has a gateway to render; otherwise the reason it has none.
 */
export function gatewayNoticeState( { attributes, settings } = {} ) {
	if ( blockGateways( attributes ).length > 0 ) {
		return null;
	}

	const available = siteGateways( settings );

	return {
		// No settings blob at all reads as `global`: a brand-new install that has
		// not saved settings yet is exactly the case this notice exists for, and
		// pointing the user at the block's own toggles would be a dead end when
		// the credentials behind them are missing too.
		reason: available.length > 0 ? 'block' : 'global',
		siteGateways: available,
	};
}

/**
 * The attributes that switch this block onto the site's enabled gateways.
 *
 * Behind the `block` notice's "Turn on …" button. A block copies the site's
 * gateway switches into its own attributes once, when it is inserted
 * (`mapSettingsToAttributes`), so a block inserted while the editor saw no
 * gateway — or before any was enabled — keeps all three `false` after the site
 * enables one. This puts it back on exactly the gateways the site offers,
 * leaving the rest as they are.
 *
 * @param {Array<Object>} gateways Entries of `PAYMENT_GATEWAYS`, usually
 *                                 `gatewayNoticeState().siteGateways`.
 * @return {Object} `{ paypalEnabled: true, … }` for each gateway given.
 */
export function siteGatewayAttributes( gateways ) {
	return ( gateways || [] ).reduce(
		( attributes, gateway ) => ( {
			...attributes,
			[ gateway.attribute ]: true,
		} ),
		{}
	);
}
