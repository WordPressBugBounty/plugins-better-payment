/**
 * The AI enabled/configured flags for first paint.
 *
 * `CPT::ai_config_seed()` prints these inline into the builder payload
 * (`window.betterPaymentCampaignData.aiConfig`), so anything that reflects the
 * AI feature's on/off state — the sidebar AI tab's "off" indicator
 * (`RightSidebar`) and the assistant panel's disabled / "add an API key" notice
 * (`useConversation`) — can render correctly on the FIRST paint instead of
 * showing the enabled state and then correcting itself once the async
 * `/ai/config` fetch resolves (the visible flicker this replaces).
 *
 * `enabled` is the Settings toggle; `configured` is whether the active provider
 * has an API key. Both must be true for the feature to work, which is why the tab
 * treats `! enabled || ! configured` as "off".
 *
 * Returns `null` when the seed is absent so each caller can pick its own
 * unknown-state default (the tab assumes "on" to avoid a false dot; the panel
 * keeps its notice hidden until the fetch answers).
 *
 * @return {{enabled: boolean, configured: boolean}|null} Seeded flags, or null.
 */
export function readAiConfigSeed() {
	const seed = window.betterPaymentCampaignData?.aiConfig;
	if ( ! seed || typeof seed !== 'object' ) return null;
	return {
		enabled: seed.enabled === true,
		configured: seed.configured === true,
	};
}

/**
 * Whether the AI feature can actually be used: the Settings toggle is on AND the
 * active provider has an API key. Builder surfaces that send AI requests — the
 * sidebar tab's "off" dot and the per-element AI Quick Edit row — read this, so
 * no surface offers an action the server refuses (`ai_disabled`) or the provider
 * cannot answer (no key).
 *
 * @param {{enabled: boolean, configured: boolean}|null} flags AI flags.
 * @return {boolean} True only when both flags are strictly true.
 */
export function isAiUsable( flags ) {
	return flags?.enabled === true && flags?.configured === true;
}
