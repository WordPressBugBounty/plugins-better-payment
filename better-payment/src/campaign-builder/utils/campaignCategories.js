/**
 * The campaign category taxonomy, as localized by PHP.
 *
 * One list, read by both the template picker's sidebar (TemplatesTab) and the AI
 * Smart Prompt Wizard's "What are you raising funds for?" tiles. Each previously
 * hardcoded its own — which is exactly how the picker ended up offering five
 * categories while the wizard offered a different ten. Anything that needs the
 * category list reads it from here.
 *
 * Source: CategoryRegistry::for_client(), via `betterPaymentCampaignData`.
 * See includes/Campaign/Templates/CategoryRegistry.php.
 */

/**
 * Every campaign category.
 *
 * @return {Array<{slug: string, label: string, brief: string, photo: string}>}
 *         The categories, in the order PHP declared them. Empty when the global
 *         is absent (the script wasn't localized) — callers render nothing
 *         rather than falling back to a stale copy that could drift again.
 */
export function getCampaignCategories() {
	return window.betterPaymentCampaignData?.categories ?? [];
}
