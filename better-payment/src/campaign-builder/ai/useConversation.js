/**
 * Conversation state + actions for the AI Assistant panel.
 *
 * Owns the message list, the in-flight/progress/error flags, and the provider
 * config. Talks to the AI REST routes via aiClient, and hands validated
 * operations back to the caller through `onOperations` (the panel applies them
 * via dispatch, so this hook stays free of reducer concerns).
 *
 * Edit/generate operations are applied immediately; analysis operations are held
 * as *suggestions* the user accepts one by one.
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	aiChat,
	aiGenerate,
	aiAnalyze,
	aiGetConfig,
	aiGetConversation,
	aiSaveConversation,
	aiClearConversation,
} from './aiClient';
import { readAiConfigSeed } from '../utils/aiConfig';
import { operationName } from './operationExecutor';

let messageSeq = 0;
function nextId() {
	messageSeq += 1;
	return `m_${ messageSeq }`;
}

// The AI enabled/configured flags are seeded synchronously from the builder
// payload (CPT::ai_config_seed) via readAiConfigSeed(), so the "AI is disabled" /
// "add an API key" notice paints on first render instead of waiting for the async
// /ai/config fetch. Only those two flags are seeded; the fetch fills the rest
// (providers, operations) once it resolves. The sidebar AI tab reads the same
// seed (RightSidebar) so the tab and the panel can never disagree at first paint.

// Staged progress labels shown while a request is in flight (a lightweight,
// robust "streaming" feel without server-sent events).
const GENERATE_STAGES = [
	__( 'Understanding your campaign…', 'better-payment' ),
	__( 'Choosing a layout…', 'better-payment' ),
	__( 'Writing the content…', 'better-payment' ),
	__( 'Putting it together…', 'better-payment' ),
];
const EDIT_STAGES = [
	__( 'Reading the campaign…', 'better-payment' ),
	__( 'Making the changes…', 'better-payment' ),
];
const ANALYZE_STAGES = [
	__( 'Reviewing your campaign…', 'better-payment' ),
	__( 'Finding improvements…', 'better-payment' ),
];

/**
 * The assistant message's ops + text after (possibly failed) image generation.
 *
 * Pure, so the "don't claim an image that failed" decision is unit-testable
 * without mounting the hook. When every requested image failed, a "Generated an
 * image" checkmark would lie — so the `generate_image` op is dropped from the
 * checklist and the text says what actually happened. On success (or when there
 * were no image ops) the message is unchanged.
 *
 * @param {Object}  args
 * @param {Array}   args.ops          Operations returned by the model.
 * @param {string}  args.text         The model's natural-language reply.
 * @param {boolean} args.apply        Whether ops are applied (edit/generate) vs. held (analyze).
 * @param {number}  args.imagesOk     Images generated successfully.
 * @param {number}  args.imagesFailed Images that failed to generate.
 * @return {{operations: Array, content: string}} Message fields.
 */
export function resolveAppliedMessage( {
	ops = [],
	text = '',
	apply = true,
	imagesOk = 0,
	imagesFailed = 0,
} ) {
	let operations = apply ? ops : [];
	let content = text;

	if ( imagesFailed > 0 && imagesOk === 0 ) {
		operations = operations.filter(
			( op ) => operationName( op ) !== 'generate_image'
		);
		content = __(
			'I applied the changes, but the image could not be generated. Check the AI image provider in Settings → AI and try again.',
			'better-payment'
		);
	}

	return { operations, content };
}

export function useConversation( { campaignId, getContext, onOperations } ) {
	const [ messages, setMessages ] = useState( [] );
	const [ sending, setSending ] = useState( false );
	const [ progress, setProgress ] = useState( '' );
	const [ error, setError ] = useState( null );
	const [ config, setConfig ] = useState( readAiConfigSeed );
	const mounted = useRef( true );
	const progressRef = useRef( null );

	useEffect( () => {
		mounted.current = true;
		return () => {
			mounted.current = false;
			if ( progressRef.current ) clearInterval( progressRef.current );
		};
	}, [] );

	// Load provider config once.
	useEffect( () => {
		aiGetConfig()
			.then( ( cfg ) => {
				if ( mounted.current ) setConfig( cfg );
			} )
			.catch( () => {
				if ( mounted.current )
					setConfig( { configured: false, providers: [] } );
			} );
	}, [] );

	// Load stored history for a saved campaign (not animated).
	useEffect( () => {
		if ( ! campaignId ) return;
		aiGetConversation( campaignId )
			.then( ( res ) => {
				if ( ! mounted.current || ! res?.messages?.length ) return;
				setMessages(
					res.messages.map( ( m ) => ( {
						id: nextId(),
						role: m.role,
						content: m.content,
						operations: [],
						suggestions: [],
						stream: false,
					} ) )
				);
			} )
			.catch( () => {} );
	}, [ campaignId ] );

	// Persist the whole conversation to the campaign whenever it settles, so it
	// survives ANY remount — the sidebar tabs, the top Templates/Editor/Settings
	// nav, and a full page reload. This is the durable half of the retention fix:
	// the first "create campaign" turn is produced while the campaign is still
	// unsaved (id 0), so /ai/generate cannot append it server-side; here we write
	// it the moment the campaign has been saved and an id appears.
	//
	//   • Skip while `sending` — wait until the assistant reply has been added, so
	//     we never persist a half-turn (user prompt with no answer yet). Because of
	//     this the list is already complete when we write, so no debounce/timer is
	//     needed (a timer's cleanup could also cancel the write on a fast unmount).
	//   • Skip an empty list — deleting stored history is `clear()`'s job; writing
	//     [] here would wipe real history during the brief window on mount before
	//     the stored history has loaded.
	//   • Replace (not append) so a re-sync of the same list is idempotent, and the
	//     fire-and-forget call never sets state, so it cannot loop.
	useEffect( () => {
		if ( ! campaignId || campaignId <= 0 ) return;
		if ( sending || ! messages.length ) return;
		aiSaveConversation(
			campaignId,
			messages.map( ( m ) => ( { role: m.role, content: m.content } ) )
		).catch( () => {} );
	}, [ campaignId, messages, sending ] );

	const pushUser = useCallback( ( text ) => {
		setMessages( ( prev ) => [
			...prev,
			{ id: nextId(), role: 'user', content: text },
		] );
	}, [] );

	const startProgress = useCallback( ( stages ) => {
		if ( progressRef.current ) clearInterval( progressRef.current );
		if ( ! stages || ! stages.length ) {
			setProgress( '' );
			return;
		}
		let i = 0;
		setProgress( stages[ 0 ] );
		progressRef.current = setInterval( () => {
			i = Math.min( i + 1, stages.length - 1 );
			if ( mounted.current ) setProgress( stages[ i ] );
		}, 1400 );
	}, [] );

	const stopProgress = useCallback( () => {
		if ( progressRef.current ) {
			clearInterval( progressRef.current );
			progressRef.current = null;
		}
		setProgress( '' );
	}, [] );

	const handleResult = useCallback(
		async ( res, apply ) => {
			const ops = res?.operations || [];
			const text = res?.assistant_message || '';

			// Image ops need a network round-trip to create the attachment. Apply
			// the batch and WAIT for it before posting the assistant's confirmation
			// — otherwise "Done — I applied the changes" and a "Generated an image"
			// checkmark appear while the image is still rendering, claiming a result
			// that isn't there yet. Synchronous ops (the layout) still apply
			// instantly inside onOperations, so the canvas updates right away; only
			// the confirmation waits, and the panel shows a "Generating image…"
			// state meanwhile.
			let applied = { imagesOk: 0, imagesFailed: 0 };
			if ( apply && ops.length && typeof onOperations === 'function' ) {
				const hasImage = ops.some(
					( op ) => operationName( op ) === 'generate_image'
				);
				if ( hasImage ) {
					startProgress( [
						__( 'Generating image…', 'better-payment' ),
					] );
				}
				applied = ( await onOperations( ops ) ) || applied;
			}

			if ( ! mounted.current ) return;

			// Don't claim an image that failed: resolveAppliedMessage drops the
			// generate_image op from the "done" checklist and rewrites the text when
			// every requested image failed.
			const { operations: shownOps, content } = resolveAppliedMessage( {
				ops,
				text,
				apply,
				imagesOk: applied.imagesOk,
				imagesFailed: applied.imagesFailed,
			} );

			setMessages( ( prev ) => [
				...prev,
				{
					id: nextId(),
					role: 'assistant',
					content,
					// Applied edits show as a "done" checklist; analysis ops are held as
					// suggestions the user accepts individually.
					operations: shownOps,
					suggestions: apply
						? []
						: ops.map( ( op ) => ( { op, applied: false } ) ),
					stream: true,
				},
			] );
		},
		[ onOperations, startProgress ]
	);

	const run = useCallback(
		async ( fn, { apply = true, stages = [] } = {} ) => {
			setSending( true );
			setError( null );
			startProgress( stages );
			try {
				const res = await fn();
				// Await: handleResult now waits for async image ops, and `sending`
				// must stay true (progress visible, composer disabled) until they
				// finish — not just until the REST call returns.
				if ( mounted.current ) await handleResult( res, apply );
				return res;
			} catch ( err ) {
				const msg =
					err?.message ||
					__(
						'The AI request failed. Please try again.',
						'better-payment'
					);
				if ( mounted.current ) {
					setError( msg );
					setMessages( ( prev ) => [
						...prev,
						{
							id: nextId(),
							role: 'assistant',
							content: msg,
							operations: [],
							suggestions: [],
							stream: false,
						},
					] );
				}
				return null;
			} finally {
				if ( mounted.current ) {
					setSending( false );
					stopProgress();
				}
			}
		},
		[ handleResult, startProgress, stopProgress ]
	);

	const send = useCallback(
		( text ) => {
			const trimmed = ( text || '' ).trim();
			if ( ! trimmed || sending ) return;
			pushUser( trimmed );
			const history = messages.map( ( m ) => ( {
				role: m.role,
				content: m.content,
			} ) );
			return run(
				() =>
					aiChat( {
						campaignId,
						message: trimmed,
						context: getContext(),
						history,
					} ),
				{ stages: EDIT_STAGES }
			);
		},
		[ sending, messages, campaignId, getContext, pushUser, run ]
	);

	// `category` is the campaign category the wizard's user explicitly picked, if
	// any — it selects the artwork generated photo elements get. Empty for a
	// free-form prompt, which leaves the server to infer one from the brief.
	//
	// `fields` are the campaign fields the user filled in themselves (goal amount,
	// end date), which the server enforces over whatever the model returns. `{}`
	// on the free-form path enforces nothing — there is no field UI there to
	// contradict, only prose.
	const generate = useCallback(
		( brief, category = '', fields = {} ) => {
			const trimmed = ( brief || '' ).trim();
			if ( ! trimmed || sending ) return;
			pushUser( trimmed );
			return run(
				() =>
					aiGenerate( {
						campaignId,
						message: trimmed,
						context: getContext(),
						category,
						fields,
					} ),
				{ stages: GENERATE_STAGES }
			);
		},
		[ sending, campaignId, getContext, pushUser, run ]
	);

	// Rebuild the whole campaign with a fresh layout. Sending an EMPTY layout in
	// the context is what makes this a redesign: `selected_structure()` returns
	// null, so the server does not treat the current columns as a user-chosen
	// structure to preserve, and the model composes a new one from the brief.
	// (It used to mean "let the server pick a different blueprint" — the server
	// no longer picks structures at all.)
	const regenerate = useCallback( () => {
		if ( sending ) return;
		const ctx = getContext();
		const title = ctx?.meta?.title;
		const brief = title
			? `Redesign this campaign ("${ title }") with a fresh layout and improved, compelling copy.`
			: 'Redesign this campaign with a fresh layout and improved, compelling copy.';
		pushUser( __( 'Regenerate with a fresh design', 'better-payment' ) );
		const freshContext = {
			layout: { layout: '1-column', columns: [] },
			meta: ctx?.meta || {},
		};
		// Redesigning the page must not silently acquire a goal or an end date the
		// campaign never had. Pin both to their current values — empty stays
		// empty, and anything already set survives the rebuild verbatim.
		const fields = {
			bpc_goal_amount: String( ctx?.meta?.bpc_goal_amount ?? '' ),
			bpc_end_date: String( ctx?.meta?.bpc_end_date ?? '' ),
		};
		return run(
			() =>
				aiGenerate( {
					campaignId,
					message: brief,
					context: freshContext,
					fields,
				} ),
			{ stages: GENERATE_STAGES }
		);
	}, [ sending, campaignId, getContext, pushUser, run ] );

	// Analysis: suggestions are NOT applied automatically — held for the user.
	const analyze = useCallback( () => {
		if ( sending ) return;
		pushUser(
			__(
				'Analyze this campaign and suggest improvements.',
				'better-payment'
			)
		);
		return run( () => aiAnalyze( { campaignId, context: getContext() } ), {
			apply: false,
			stages: ANALYZE_STAGES,
		} );
	}, [ sending, campaignId, getContext, pushUser, run ] );

	// Accept a single analysis suggestion.
	const applySuggestion = useCallback(
		( messageId, index ) => {
			setMessages( ( prev ) =>
				prev.map( ( m ) => {
					if ( m.id !== messageId ) return m;
					const suggestion = m.suggestions?.[ index ];
					if ( ! suggestion || suggestion.applied ) return m;
					if ( typeof onOperations === 'function' )
						onOperations( [ suggestion.op ] );
					const suggestions = m.suggestions.map( ( s, i ) =>
						i === index ? { ...s, applied: true } : s
					);
					return { ...m, suggestions };
				} )
			);
		},
		[ onOperations ]
	);

	// Accept every outstanding suggestion in one go.
	//
	// One `onOperations` call, not a loop over `applySuggestion` — the panel's
	// handler dispatches AI_APPLY_OPERATIONS, which snapshots for undo, so N
	// calls would bury the pre-analysis state under N undo steps for what the
	// user experienced as a single action.
	const applyAllSuggestions = useCallback(
		( messageId ) => {
			setMessages( ( prev ) =>
				prev.map( ( m ) => {
					if ( m.id !== messageId ) return m;
					const pending = ( m.suggestions || [] ).filter(
						( s ) => ! s.applied
					);
					if ( ! pending.length ) return m;
					if ( typeof onOperations === 'function' ) {
						onOperations( pending.map( ( s ) => s.op ) );
					}
					return {
						...m,
						suggestions: m.suggestions.map( ( s ) => ( {
							...s,
							applied: true,
						} ) ),
					};
				} )
			);
		},
		[ onOperations ]
	);

	const clear = useCallback( () => {
		setMessages( [] );
		setError( null );
		if ( campaignId ) aiClearConversation( campaignId ).catch( () => {} );
	}, [ campaignId ] );

	return {
		messages,
		sending,
		progress,
		error,
		config,
		send,
		generate,
		regenerate,
		analyze,
		applySuggestion,
		applyAllSuggestions,
		clear,
	};
}
