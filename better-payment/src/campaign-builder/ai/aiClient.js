/**
 * Thin apiFetch wrappers for the AI REST routes (better-payment/v1/ai/*).
 *
 * apiFetch is already configured with the wp_rest nonce in App.js, so these
 * need no extra plumbing. Each returns the parsed JSON response promise.
 */

import apiFetch from '@wordpress/api-fetch';

const base = 'better-payment/v1/ai';

/**
 * @param {Object} args                 Request arguments.
 * @param {number} args.campaignId      Campaign id, 0 for an unsaved campaign.
 * @param {string} args.message         The user instruction.
 * @param {Object} args.context         { layout, meta }.
 * @param {Array}  args.history         Prior turns.
 * @param {string} args.targetElementId Optional id of the one widget this turn may
 *                                      change. Sent by the per-element AI buttons;
 *                                      the server reads the widget's type and current
 *                                      settings back out of `context`, briefs the model
 *                                      on that widget specifically, and discards any
 *                                      operation aimed elsewhere.
 * @return {Promise<Object>} { assistant_message, operations, usage }
 */
export function aiChat( {
	campaignId = 0,
	message,
	context = {},
	history = [],
	targetElementId = '',
} ) {
	return apiFetch( {
		path: `${ base }/chat`,
		method: 'POST',
		data: {
			campaign_id: campaignId,
			message,
			context,
			history,
			target_element_id: targetElementId,
		},
	} );
}

/**
 * @param {Object} args            Request arguments.
 * @param {number} args.campaignId Campaign id, 0 for an unsaved campaign.
 * @param {string} args.message    The assembled brief.
 * @param {Object} args.context    { layout, meta }.
 * @param {string} args.category   Campaign category the user explicitly chose;
 *                                 '' lets the server infer one from the brief.
 *                                 Decides which artwork generated photos get.
 * @param {Object} args.fields     Campaign fields the user filled in themselves,
 *                                 keyed by meta key. A key present but empty
 *                                 means "they were asked and left it blank" —
 *                                 the server keeps it empty regardless of what
 *                                 the model returns. `{}` enforces nothing.
 */
export function aiGenerate( {
	campaignId = 0,
	message,
	context = {},
	category = '',
	fields = {},
} ) {
	return apiFetch( {
		path: `${ base }/generate`,
		method: 'POST',
		data: { campaign_id: campaignId, message, context, category, fields },
	} );
}

/**
 * Refine the wizard's brief before generation (text in, text out). Applies
 * nothing — returns an improved brief for the user to review/edit, then generate.
 *
 * @param {Object} args             Request arguments.
 * @param {string} args.brief       The current brief text to improve.
 * @param {string} args.instruction Optional plain-language "make it more…"; ''
 *                                  asks for a general polish.
 * @return {Promise<Object>} { brief, usage }
 */
export function aiRefineBrief( { brief, instruction = '' } ) {
	return apiFetch( {
		path: `${ base }/brief`,
		method: 'POST',
		data: { brief, instruction },
	} );
}

export function aiAnalyze( { campaignId = 0, context = {} } ) {
	return apiFetch( {
		path: `${ base }/analyze`,
		method: 'POST',
		data: { campaign_id: campaignId, context },
	} );
}

export function aiGenerateImage( { campaignId = 0, prompt } ) {
	return apiFetch( {
		path: `${ base }/image`,
		method: 'POST',
		data: { campaign_id: campaignId, prompt },
	} );
}

export function aiGetConfig() {
	return apiFetch( { path: `${ base }/config` } );
}

export function aiGetConversation( campaignId ) {
	return apiFetch( { path: `${ base }/conversations/${ campaignId }` } );
}

// Persist the full conversation for a campaign. The client owns the message list
// (the first generate turn never gets appended server-side — it happens while the
// campaign is still unsaved), so it syncs the whole list here once an id exists.
export function aiSaveConversation( campaignId, messages ) {
	return apiFetch( {
		path: `${ base }/conversations/${ campaignId }`,
		method: 'POST',
		data: { messages },
	} );
}

export function aiClearConversation( campaignId ) {
	return apiFetch( {
		path: `${ base }/conversations/${ campaignId }`,
		method: 'DELETE',
	} );
}
