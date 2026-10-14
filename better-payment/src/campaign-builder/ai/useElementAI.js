/**
 * Inline, per-element AI actions (Rewrite / Shorten / Improve / More urgent, and
 * Generate image for photos). Runs a scoped AI edit on a single element and
 * applies the returned operations through the reducer — reusing the same
 * operations pipeline as the chat panel, just targeted at one element id.
 */

import { useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { aiChat, aiGenerateImage } from './aiClient';
import { buildContext } from './contextSelector';
import { operationName } from './operationExecutor';
import { textFieldLabels } from './elementCapabilities';

const ACTION_PROMPTS = {
	rewrite: ( label ) =>
		`Rewrite the ${ label } with fresh, compelling, specific fundraising copy.`,
	shorten: ( label ) =>
		`Make the ${ label } shorter and punchier while keeping its meaning.`,
	improve: ( label ) =>
		`Improve the ${ label } for stronger emotional impact and clearer donor motivation.`,
	urgent: ( label ) =>
		`Rewrite the ${ label } to feel more urgent and time-sensitive.`,
};

/**
 * Operations a single-element turn is allowed to apply.
 *
 * Mirrors `OperationValidator::SCOPED_OPERATIONS` — the server already drops
 * anything else, and this is the client half of the same rule. Duplicated
 * deliberately: the server guarantee is the one that matters, but a stale or
 * proxied response should not be able to rewrite the page either, and filtering
 * here keeps the reducer from ever seeing an out-of-scope action.
 */
const SCOPED_OPERATIONS = [ 'update_block', 'replace_image' ];

/**
 * Keep only the operations that change the element the user actually selected.
 *
 * Exported for tests: this is the guard that stops "shorten this headline" from
 * coming back as a `set_layout` that rebuilds the page, or an `update_meta` that
 * moves the fundraising goal. Before it existed the target was conveyed to the
 * model as nothing but a sentence in the prompt, and whatever came back was
 * applied verbatim.
 *
 * @param {Array}  operations Validated operations from the server.
 * @param {string} elementId  The selected element's id.
 * @return {Array} Operations that target only that element.
 */
export function scopeOperations( operations, elementId ) {
	if ( ! elementId ) return [];
	return ( operations ?? [] ).filter(
		( op ) =>
			SCOPED_OPERATIONS.includes( operationName( op ) ) &&
			op?.element_id === elementId
	);
}

/**
 * Name the widget and the fields the request should land in.
 *
 * "Rewrite the Progress Bar" on its own leaves the model to pick a field, and a
 * progress bar's `goal_label` is a prefix the renderer appends an amount to — the
 * documented way that widget ends up reading "Our Goal: $5,000 €5,000". Naming
 * the writable fields keeps a generic action pointed at the copy the user can see.
 *
 * @param {Object} element     The selected element.
 * @param {string} actionKey   Action id.
 * @param {string} label       Human label for the widget.
 * @return {string} The instruction sent to the model.
 */
export function buildActionMessage( element, actionKey, label ) {
	const name = label || element.type;
	const build = ACTION_PROMPTS[ actionKey ] ?? ACTION_PROMPTS.improve;
	const fields = textFieldLabels( element.type );

	const focus = fields.length
		? ` Change only this widget's text fields (${ fields.join(
				', '
		  ) }) — leave its other settings alone.`
		: '';

	return `${ build( name ) }${ focus }`;
}

export function useElementAI( { campaignId, state, dispatch } ) {
	const [ busyKey, setBusyKey ] = useState( null ); // `${elementId}:${action}` while in flight
	const stateRef = useRef( state );
	stateRef.current = state;
	const mounted = useRef( true );

	useEffect( () => {
		mounted.current = true;
		return () => {
			mounted.current = false;
		};
	}, [] );

	const runAction = useCallback(
		async ( element, actionKey, label ) => {
			if ( busyKey || ! element ) return;
			setBusyKey( `${ element.id }:${ actionKey }` );
			try {
				const context = buildContext( stateRef.current );
				const message = buildActionMessage( element, actionKey, label );
				const res = await aiChat( {
					campaignId,
					message,
					context,
					history: [],
					targetElementId: element.id,
				} );
				const ops = scopeOperations( res?.operations, element.id );
				if ( ops.length ) {
					dispatch( {
						type: 'AI_APPLY_OPERATIONS',
						operations: ops,
					} );
				}
			} catch ( err ) {
				// Swallow — the element simply stays unchanged.
			} finally {
				if ( mounted.current ) setBusyKey( null );
			}
		},
		[ busyKey, campaignId, dispatch ]
	);

	const generateImage = useCallback(
		async ( element ) => {
			if ( busyKey || ! element ) return;
			setBusyKey( `${ element.id }:image` );
			try {
				const meta = stateRef.current?.meta || {};
				const prompt = meta.title
					? `A warm, high-quality hero photo for a fundraising campaign: ${ meta.title }`
					: 'A warm, high-quality fundraising campaign hero photo';
				const img = await aiGenerateImage( { campaignId, prompt } );
				dispatch( {
					type: 'AI_APPLY_OPERATIONS',
					operations: [
						{
							op: 'replace_image',
							element_id: element.id,
							src: img.url,
							src_id: img.id,
							src_sizes: img.sizes,
							alt: img.alt,
						},
					],
				} );
			} catch ( err ) {
				// Swallow.
			} finally {
				if ( mounted.current ) setBusyKey( null );
			}
		},
		[ busyKey, campaignId, dispatch ]
	);

	return { busyKey, runAction, generateImage };
}
