/**
 * AI Assistant panel — a conversational editing surface for the Campaign Builder.
 *
 * Mounts as a collapsible left rail in the editor tab. It reads the current
 * builder state, sends natural-language requests to the AI REST layer, and
 * applies the returned operations through the reducer (AI_APPLY_OPERATIONS) —
 * the exact same state path a human's edits take. It never manipulates the
 * canvas, DOM, or element HTML directly.
 */

import {
	useState,
	useRef,
	useCallback,
	useEffect,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useConversation } from './useConversation';
import { buildContext } from './contextSelector';
import { describeOperation, operationName } from './operationExecutor';
import { parseReport } from './reportFormat';
import ReportActions from './ReportActions';
import PromptList from './PromptList';
import { aiGenerateImage } from './aiClient';
import PromptWizard from './PromptWizard';
import PromptTemplates from './PromptTemplates';
import BriefReview from './BriefReview';
import AiIcon from './AiIcon';
import PromptBulbIcon from './PromptBulbIcon';

// Quick edit suggestions for an existing campaign (the non-empty state). The
// empty state no longer uses a flat suggestion list — it opens the richer
// prompt-template picker instead (see PromptTemplates / the "Prompt templates"
// chip below).
const EDIT_SUGGESTIONS = [
	__( 'Make the story more emotional', 'better-payment' ),
	__( 'Increase urgency in the copy', 'better-payment' ),
	__( 'Improve the call to action', 'better-payment' ),
];

function hasElements( state ) {
	return ( state?.layout?.columns || [] ).some(
		( c ) => ( c.elements || [] ).length > 0
	);
}

/** Bold / plain runs of one parsed line. */
function Runs( { runs } ) {
	return runs.map( ( run, i ) =>
		run.bold ? <strong key={ i }>{ run.text }</strong> : run.text
	);
}

/**
 * Render an assistant reply as a small document.
 *
 * Analysis replies are reports — a verdict plus `- **Widget** — finding` lines —
 * and printing them raw showed the user the Markdown rather than the writing.
 * Everything here is text nodes; nothing is set as HTML.
 */
function ReportBody( { text } ) {
	const blocks = parseReport( text );

	// No structure to speak of (the common one-line reply) — render as plain
	// text so the bubble's `white-space: pre-wrap` keeps the author's line breaks.
	if ( blocks.length === 1 && blocks[ 0 ].type === 'para' ) {
		return <Runs runs={ blocks[ 0 ].runs } />;
	}

	return blocks.map( ( block, i ) => {
		if ( block.type === 'heading' ) {
			return (
				<p className="bp-cb-ai__report-heading" key={ i }>
					<Runs runs={ block.runs } />
				</p>
			);
		}
		if ( block.type === 'list' ) {
			const Tag = block.ordered ? 'ol' : 'ul';
			return (
				<Tag className="bp-cb-ai__report-list" key={ i }>
					{ block.items.map( ( runs, j ) => (
						<li key={ j }>
							<Runs runs={ runs } />
						</li>
					) ) }
				</Tag>
			);
		}
		return (
			<p className="bp-cb-ai__report-para" key={ i }>
				<Runs runs={ block.runs } />
			</p>
		);
	} );
}

/**
 * Reveals text with a lightweight typewriter effect the first time it mounts
 * (for freshly-received replies); shows it in full otherwise (loaded history).
 *
 * The reveal drives the *source text*, and formatting is re-derived from the
 * partial string on every tick — so a report types out already formatted instead
 * of showing its Markdown and reflowing once at the end.
 */
function TypewriterText( { text, animate } ) {
	const [ shown, setShown ] = useState( animate ? '' : text );

	useEffect( () => {
		if ( ! animate ) {
			setShown( text );
			return undefined;
		}
		let i = 0;
		const step = Math.max( 1, Math.round( text.length / 120 ) );
		setShown( '' );
		const timer = setInterval( () => {
			i = Math.min( i + step, text.length );
			setShown( text.slice( 0, i ) );
			if ( i >= text.length ) clearInterval( timer );
		}, 16 );
		return () => clearInterval( timer );
	}, [ text, animate ] );

	return <ReportBody text={ shown } />;
}

export default function AIAssistantPanel( {
	state,
	dispatch,
	campaignId,
	adminUrl,
	embedded = false,
	// Bumped by the parent each time the canvas empty-state "+" is clicked. Every
	// increment moves the caret into the composer so the user can start typing
	// their campaign description immediately — see the focus effect below.
	focusSignal = 0,
} ) {
	const [ collapsed, setCollapsed ] = useState( false );
	const [ input, setInput ] = useState( '' );
	// Default to the free-form composer, even on an empty campaign. The guided
	// Smart Prompt Wizard used to open first and it front-loaded seven questions
	// onto someone who may already know exactly what they want to type — the
	// faster path was reachable only by first dismissing the slower one.
	//
	// Nothing is lost by flipping this: the wizard is one click away the whole
	// time the campaign is empty, via the "Guided setup" chip in the quickbar
	// below the messages. Keep that chip — it is the only route back, so removing
	// it would strand the wizard entirely.
	const [ composerMode, setComposerMode ] = useState( 'freeform' );
	// The wizard no longer generates on submit — it hands its assembled brief here
	// for a review step, and generation fires only when the user approves it. Holds
	// { brief, category, fields } while composerMode === 'brief'. category + fields
	// pass through the review untouched (they are structured and server-enforced);
	// only the brief prose is edited/refined. See BriefReview.
	const [ pendingBrief, setPendingBrief ] = useState( null );
	// The wizard's optional layout choice is a generation *preference*, not an
	// immediate canvas change — so picking a layout doesn't show an empty column
	// skeleton before the AI has produced any content.
	const [ chosenLayout, setChosenLayout ] = useState( null );
	const listRef = useRef( null );
	// The free-form composer's textarea — focused on demand when the canvas "+"
	// asks the panel to open (focusSignal).
	const inputRef = useRef( null );

	// Always read the latest state when building context for a request.
	const stateRef = useRef( state );
	stateRef.current = state;
	const chosenLayoutRef = useRef( chosenLayout );
	chosenLayoutRef.current = chosenLayout;
	const getContext = useCallback( () => {
		const st = stateRef.current;
		const base = buildContext( st );
		const chosen = chosenLayoutRef.current;
		// On an empty campaign, pass the chosen layout as the target structure
		// (without touching the canvas). The AI then builds into it.
		if ( chosen && ! hasElements( st ) ) {
			return {
				...base,
				layout: { layout: chosen.layout, columns: chosen.columns },
			};
		}
		return base;
	}, [] );

	// Apply synchronous operations through the reducer; fulfil image-generation
	// operations asynchronously (they need a network round-trip to create the
	// attachment) then apply the resulting insert/replace.
	const onOperations = useCallback(
		async ( operations ) => {
			const imageOps = operations.filter(
				( op ) => operationName( op ) === 'generate_image'
			);
			const syncOps = operations.filter(
				( op ) => operationName( op ) !== 'generate_image'
			);

			if ( syncOps.length ) {
				dispatch( {
					type: 'AI_APPLY_OPERATIONS',
					operations: syncOps,
				} );
			}

			// Report how the (slow) image ops fared so the caller can wait on them
			// and confirm honestly — a "Generated an image" checkmark is only true
			// once the attachment exists.
			let imagesOk = 0;
			let imagesFailed = 0;
			for ( const op of imageOps ) {
				try {
					const img = await aiGenerateImage( {
						campaignId,
						prompt: op.prompt,
					} );
					const applied = op.element_id
						? [
								{
									op: 'replace_image',
									element_id: op.element_id,
									src: img.url,
									src_id: img.id,
									src_sizes: img.sizes,
									alt: img.alt,
								},
						  ]
						: [
								{
									op: 'insert_block',
									type: 'photo',
									column_id: op.column_id,
									settings: {
										src: img.url,
										src_id: img.id,
										src_sizes: img.sizes,
										alt: img.alt,
									},
								},
						  ];
					dispatch( {
						type: 'AI_APPLY_OPERATIONS',
						operations: applied,
					} );
					imagesOk += 1;
				} catch ( err ) {
					// Image generation failed — leave the rest of the turn applied.
					imagesFailed += 1;
				}
			}

			return { imagesOk, imagesFailed };
		},
		[ dispatch, campaignId ]
	);

	const {
		messages,
		sending,
		progress,
		config,
		send,
		generate,
		regenerate,
		analyze,
		applySuggestion,
		applyAllSuggestions,
		clear,
	} = useConversation( { campaignId, getContext, onOperations } );

	const canUndo = ( state.aiUndoStack?.length || 0 ) > 0;
	const canRedo = ( state.aiRedoStack?.length || 0 ) > 0;

	const isEmpty = ! hasElements( state );
	// Suggestions the user has already used are hidden from the quick-actions bar.
	const [ usedSuggestions, setUsedSuggestions ] = useState( [] );
	// The prompt deck is opt-in: closed until the lightbulb is clicked, so the
	// default view is the conversation rather than a menu of things to ask.
	const [ showPrompts, setShowPrompts ] = useState( false );
	const suggestions = EDIT_SUGGESTIONS.filter(
		( s ) => ! usedSuggestions.includes( s )
	);

	// Blank layouts (from the template registry) offered as an optional first
	// question in the wizard. Picking one records a preference used at generation
	// time (see getContext); the default is "Let AI decide" (null → AI chooses).
	const blankLayouts = (
		window.betterPaymentCampaignData?.templates || []
	).filter( ( t ) => t.category === 'blank' );

	const activeLayoutKey = chosenLayout ? chosenLayout.key : '__ai__';

	// A preference only — never applied to the canvas, so no empty skeleton
	// appears before the AI has generated content.
	const selectLayout = ( tpl ) => setChosenLayout( tpl || null );

	useEffect( () => {
		if ( listRef.current ) {
			listRef.current.scrollTop = listRef.current.scrollHeight;
		}
	}, [ messages, sending ] );

	const submit = ( text ) => {
		const value = ( text != null ? text : input ).trim();
		if ( ! value || sending ) return;
		setInput( '' );
		if ( isEmpty ) {
			generate( value );
		} else {
			send( value );
		}
	};

	// Using a suggestion sends it, removes it from the list, and closes the deck
	// — the reply is the thing to look at now, not the remaining prompts.
	const handleSuggestion = ( s ) => {
		setUsedSuggestions( ( prev ) =>
			prev.includes( s ) ? prev : [ ...prev, s ]
		);
		setShowPrompts( false );
		submit( s );
	};

	// Clearing the conversation also restores the full suggestion list.
	const handleClear = () => {
		setUsedSuggestions( [] );
		clear();
	};

	// The wizard hands us a fully-assembled brief, plus the campaign category the
	// user picked ('' when they chose a cause that isn't one, or none at all —
	// the server then infers one from the brief), plus the fields the user filled
	// in themselves (which the server holds the model to — see buildUserFields).
	// Rather than generate immediately, park it in the brief-review step so the
	// user can see and shape the intent first; generation fires on approval.
	const handleWizardComplete = ( brief, category = '', fields = {} ) => {
		if ( ! brief || sending ) return;
		setPendingBrief( { brief, category, fields } );
		setComposerMode( 'brief' );
	};

	// Approve the reviewed (possibly edited/refined) brief → generate, and flip to
	// the conversation view so the user sees their brief + a typing state. The
	// category and fields are the wizard's originals, carried through untouched.
	const handleBriefGenerate = ( brief ) => {
		if ( ! brief || sending || ! pendingBrief ) return;
		generate( brief, pendingBrief.category, pendingBrief.fields );
		setPendingBrief( null );
		setComposerMode( 'freeform' );
	};

	// Back to the questions. The wizard remounts fresh (its answers are local
	// state), so this is "start the questions over", not "restore what I typed".
	const handleBriefBack = () => {
		setPendingBrief( null );
		setComposerMode( 'wizard' );
	};

	// Create from a prompt template: the picker hands back the final (edited /
	// AI-refined) prompt, which is generated on the free-form path exactly as if
	// the user had typed it. Then drop back to the composer.
	const handleTemplateCreate = ( promptText ) => {
		if ( ! promptText || sending ) return;
		generate( promptText );
		setComposerMode( 'freeform' );
	};

	const showWizard = isEmpty && composerMode === 'wizard';
	const showBrief = isEmpty && composerMode === 'brief' && !! pendingBrief;
	// Empty-campaign only: the prompt-template picker replaces the old flat
	// suggestion deck for the "write your own" path.
	const showTemplates = isEmpty && composerMode === 'templates';

	// Switch to the free-form composer and move the caret into its textarea. Used
	// both by the canvas "+" (via focusSignal, below) and by the wizard's "Write my
	// own prompt instead" link. The textarea is absent in the wizard/brief/templates
	// modes, so flip to freeform first, then focus on the next frame once it has
	// mounted (optional chaining guards a stale ref if it unmounted meanwhile).
	const focusComposer = useCallback( () => {
		setComposerMode( 'freeform' );
		requestAnimationFrame( () => {
			inputRef.current?.focus();
		} );
	}, [] );

	// The canvas empty-state "+" bumps focusSignal each click so the user can start
	// describing the campaign right away. Skips the initial render (focusSignal === 0).
	useEffect( () => {
		if ( ! focusSignal ) return;
		focusComposer();
	}, [ focusSignal, focusComposer ] );

	const onKeyDown = ( e ) => {
		if ( e.key === 'Enter' && ! e.shiftKey ) {
			e.preventDefault();
			submit();
		}
	};

	const settingsUrl = `${
		adminUrl || ''
	}admin.php?page=better-payment-admin&tab=settings&section=ai`;
	// Two distinct blocked states: the admin turned the feature off entirely, or
	// left it on but never saved an API key. They need different messaging —
	// "enable it" vs. "add a key" — so keep them separate rather than folding
	// both into one generic notConfigured flag.
	const aiDisabled = !! config && config.enabled === false;
	const notConfigured =
		! aiDisabled && !! config && config.configured === false;
	const blocked = aiDisabled || notConfigured;

	// Collapse-to-rail only applies to the standalone (non-embedded) placement.
	if ( ! embedded && collapsed ) {
		return (
			<div className="bp-cb-ai bp-cb-ai--collapsed">
				<button
					type="button"
					className="bp-cb-ai__toggle"
					onClick={ () => setCollapsed( false ) }
					aria-label={ __( 'Open AI Assistant', 'better-payment' ) }
					title={ __( 'AI Assistant', 'better-payment' ) }
				>
					<AiIcon size={ 20 } />
				</button>
			</div>
		);
	}

	return (
		<div className={ `bp-cb-ai${ embedded ? ' bp-cb-ai--embedded' : '' }` }>
			<div className="bp-cb-ai__header">
				<span className="bp-cb-ai__title">
					<AiIcon size={ 16 } />
					{ __( 'AI Assistant', 'better-payment' ) }
				</span>
				<div className="bp-cb-ai__header-actions">
					{ canUndo && (
						<button
							type="button"
							className="bp-cb-ai__icon-btn"
							onClick={ () => dispatch( { type: 'UNDO_AI' } ) }
							title={ __(
								'Undo last AI change',
								'better-payment'
							) }
							aria-label={ __(
								'Undo last AI change',
								'better-payment'
							) }
						>
							<span className="dashicons dashicons-undo" />
						</button>
					) }
					{ canRedo && (
						<button
							type="button"
							className="bp-cb-ai__icon-btn"
							onClick={ () => dispatch( { type: 'REDO_AI' } ) }
							title={ __(
								'Redo last undone AI change',
								'better-payment'
							) }
							aria-label={ __(
								'Redo last undone AI change',
								'better-payment'
							) }
						>
							<span className="dashicons dashicons-redo" />
						</button>
					) }
					{ messages.length > 0 && (
						<button
							type="button"
							className="bp-cb-ai__icon-btn"
							onClick={ handleClear }
							title={ __(
								'Clear conversation',
								'better-payment'
							) }
							aria-label={ __(
								'Clear conversation',
								'better-payment'
							) }
						>
							<span className="dashicons dashicons-trash" />
						</button>
					) }
					{ ! embedded && (
						<button
							type="button"
							className="bp-cb-ai__icon-btn"
							onClick={ () => setCollapsed( true ) }
							title={ __( 'Collapse', 'better-payment' ) }
							aria-label={ __(
								'Collapse AI Assistant',
								'better-payment'
							) }
						>
							<span className="dashicons dashicons-arrow-left-alt2" />
						</button>
					) }
				</div>
			</div>

			{ aiDisabled && (
				<div className="bp-cb-ai__notice">
					{ __( 'The AI Assistant is disabled.', 'better-payment' ) }{ ' ' }
					<a href={ settingsUrl }>
						{ __( 'Enable it in AI settings', 'better-payment' ) }
					</a>
				</div>
			) }

			{ notConfigured && (
				<div className="bp-cb-ai__notice">
					{ __(
						'Add an AI provider API key to start.',
						'better-payment'
					) }{ ' ' }
					<a href={ settingsUrl }>
						{ /* "Open AI settings" parsed as "OpenAI settings" —
						     the wrong provider, on a screen that lists four. */ }
						{ __( 'API Key settings', 'better-payment' ) }
					</a>
				</div>
			) }

			{ showWizard ? (
				<PromptWizard
					onGenerate={ handleWizardComplete }
					onWriteOwn={ focusComposer }
					sending={ sending }
					disabled={ blocked }
					layouts={ blankLayouts }
					activeLayoutKey={ activeLayoutKey }
					onSelectLayout={ selectLayout }
				/>
			) : showBrief ? (
				<BriefReview
					initialBrief={ pendingBrief.brief }
					onGenerate={ handleBriefGenerate }
					onBack={ handleBriefBack }
					sending={ sending }
					disabled={ blocked }
				/>
			) : showTemplates ? (
				<PromptTemplates
					onCreate={ handleTemplateCreate }
					onClose={ () => setComposerMode( 'freeform' ) }
					sending={ sending }
					disabled={ blocked }
				/>
			) : (
				<>
					<div className="bp-cb-ai__messages" ref={ listRef }>
						{ messages.length === 0 && (
							<div className="bp-cb-ai__empty">
								<p className="bp-cb-ai__empty-title">
									{ isEmpty
										? createInterpolateElement(
												__(
													'Start with <strong>Guided Setup</strong>, explore <strong>Prompt Suggestions</strong>, or write your own prompt to generate a campaign.',
													'better-payment'
												),
												{ strong: <strong /> }
										  )
										: __(
												'Ask me to edit this campaign.',
												'better-payment'
										  ) }
								</p>
							</div>
						) }

						{ messages.map( ( m ) => {
							const isAssistant = m.role === 'assistant';
							// The bubble body — identical for both roles apart from
							// the assistant's typewriter reveal.
							const bubble = (
								<div className="bp-cb-ai__bubble">
									{ isAssistant ? (
										<TypewriterText
											text={ m.content }
											animate={ !! m.stream }
										/>
									) : (
										m.content
									) }
								</div>
							);
							return (
								<div
									key={ m.id }
									className={ `bp-cb-ai__msg bp-cb-ai__msg--${ m.role }` }
								>
									{ /* Sparkle sits to the LEFT of the assistant's
								     bubble (inline, top-aligned), marking the turn
								     as AI-authored. User turns carry no icon. */ }
									{ isAssistant ? (
										<div className="bp-cb-ai__msg-line">
											<AiIcon
												size={ 16 }
												className="bp-cb-ai__msg-icon"
											/>
											{ bubble }
										</div>
									) : (
										bubble
									) }
									{ m.operations &&
										m.operations.length > 0 && (
											<ul className="bp-cb-ai__ops">
												{ m.operations.map(
													( op, i ) => (
														<li key={ i }>
															<span className="dashicons dashicons-yes" />
															{ describeOperation(
																op,
																state
															) }
														</li>
													)
												) }
											</ul>
										) }
									{ m.suggestions &&
										m.suggestions.length > 0 && (
											<ReportActions
												suggestions={ m.suggestions }
												state={ state }
												sending={ sending }
												onApply={ ( index ) =>
													applySuggestion(
														m.id,
														index
													)
												}
												onApplyAll={ () =>
													applyAllSuggestions( m.id )
												}
											/>
										) }
								</div>
							);
						} ) }

						{ sending && (
							<div className="bp-cb-ai__msg bp-cb-ai__msg--assistant">
								<div className="bp-cb-ai__msg-line">
									<AiIcon
										size={ 16 }
										className="bp-cb-ai__msg-icon"
									/>
									{ progress ? (
										<div className="bp-cb-ai__bubble">
											<span className="bp-cb-ai__progress">
												{ progress }
											</span>
										</div>
									) : (
										<div className="bp-cb-ai__bubble bp-cb-ai__bubble--typing">
											<span></span>
											<span></span>
											<span></span>
										</div>
									) }
								</div>
							</div>
						) }
					</div>

					{ /* Quick actions above the composer. Context-aware (guided
                        setup when empty, regenerate once there's content), plus
                        one icon that opens the suggested prompts as a carousel.
                        The prompts used to sit here as chips wrapping over four
                        rows, which grew with every suggestion added and squeezed
                        the conversation out of a 340px sidebar. */ }
					<div className="bp-cb-ai__quickbar">
						{ isEmpty ? (
							<>
								<button
									type="button"
									className="bp-cb-ai__chip bp-cb-ai__chip--wizard"
									onClick={ () =>
										setComposerMode( 'wizard' )
									}
									disabled={ sending }
								>
									<AiIcon size={ 14 } />
									{ __( 'Guided Setup', 'better-payment' ) }
								</button>
								{ /* The improved helper prompts: opens the
								     template picker (titles → editable prompt →
								     refine → create). */ }
								<button
									type="button"
									className="bp-cb-ai__chip bp-cb-ai__chip--templates"
									onClick={ () =>
										setComposerMode( 'templates' )
									}
									disabled={ sending || blocked }
								>
									<PromptBulbIcon size={ 17 } />
									{ __(
										'Prompt Suggestions',
										'better-payment'
									) }
								</button>
							</>
						) : (
							<>
								<button
									type="button"
									className="bp-cb-ai__chip bp-cb-ai__chip--regen"
									onClick={ regenerate }
									disabled={ sending || blocked }
									title={ __(
										'Rebuild with a fresh layout',
										'better-payment'
									) }
								>
									<span className="dashicons dashicons-update-alt" />
									{ __( 'Regenerate', 'better-payment' ) }
								</button>
								{ /* Hidden once every suggestion has been used —
								     an icon that opens an empty deck is a dead
								     control. */ }
								{ suggestions.length > 0 && (
									<button
										type="button"
										className={ `bp-cb-ai__chip bp-cb-ai__chip--prompts${
											showPrompts
												? ' bp-cb-ai__chip--prompts-open'
												: ''
										}` }
										onClick={ () =>
											setShowPrompts( ( open ) => ! open )
										}
										disabled={ sending || blocked }
										aria-expanded={ showPrompts }
										title={ __(
											'Suggested prompts',
											'better-payment'
										) }
										aria-label={ __(
											'Suggested prompts',
											'better-payment'
										) }
									>
										<PromptBulbIcon size={ 18 } />
									</button>
								) }
							</>
						) }
					</div>

					{ ! isEmpty && showPrompts && (
						<PromptList
							items={ suggestions }
							onSelect={ handleSuggestion }
							onClose={ () => setShowPrompts( false ) }
							disabled={ sending || blocked }
						/>
					) }

					<div className="bp-cb-ai__composer">
						<textarea
							ref={ inputRef }
							className="bp-cb-ai__input"
							value={ input }
							onChange={ ( e ) => setInput( e.target.value ) }
							onKeyDown={ onKeyDown }
							placeholder={
								isEmpty
									? __(
											'e.g. A campaign to fund a school library…',
											'better-payment'
									  )
									: __(
											'Tell me what to change…',
											'better-payment'
									  )
							}
							// Taller in the empty/free-form state: it seeds a full
							// prompt (from Prompt Suggestions or the user's own
							// brief), so a 2-row box clips it. The edit state only
							// takes a short "change this" line, so it stays compact.
							rows={ isEmpty ? 6 : 2 }
							disabled={ sending || blocked }
						/>
						<div className="bp-cb-ai__composer-actions">
							{ ! isEmpty && (
								<button
									type="button"
									className="bp-cb-ai__analyze"
									onClick={ analyze }
									disabled={ sending || blocked }
								>
									<span className="dashicons dashicons-chart-bar" />
									{ __( 'Analyze', 'better-payment' ) }
								</button>
							) }
							<button
								type="button"
								className="bp-cb-ai__send"
								onClick={ () => submit() }
								disabled={
									sending || blocked || ! input.trim()
								}
							>
								{ sending
									? __( 'Working…', 'better-payment' )
									: isEmpty
									? __( 'Generate', 'better-payment' )
									: __( 'Update', 'better-payment' ) }
							</button>
						</div>
					</div>
				</>
			) }
		</div>
	);
}
