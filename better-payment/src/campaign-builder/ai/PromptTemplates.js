/**
 * Prompt Suggestions — the improved "helper prompts" for the free-form composer
 * (the "write your own" path, as opposed to the guided wizard).
 *
 * The old helper was a flat list of one-line suggestions that submitted the
 * instant you clicked one. This replaces it with a write-or-shape flow:
 *
 *  1. The prompt box starts EMPTY with a meaningful placeholder — nothing is
 *     pre-selected. The suggestions are optional starting points, so a user who
 *     already knows what they want just types straight into the box.
 *  2. A scrollable menu of suggestion *cards* — each an icon, a title and a
 *     one-line description — sits above the box. Four rows show; the fifth peeks
 *     under a light blurred overlay that lifts at the end of the list (see
 *     style.scss). The list is a quick scan of names, not a wall of prose.
 *  3. Clicking a card drops its full, detailed prompt into the editable box.
 *     The user edits it directly, or clicks "Refine with AI" to have the
 *     assistant rewrite it (POST /ai/brief → BriefWriter — the exact same
 *     text-in/text-out refiner the wizard's brief-review step uses; nothing is
 *     applied to the campaign until the user hits Create).
 *  4. "Create campaign" generates from the final prompt (free-form generate path).
 *
 * Clicking another card resets the box to that suggestion's prompt — the box
 * shows "the selected suggestion, as edited", not an accumulation.
 *
 * Data + copy live in promptTemplateData.js; this file is presentation + the
 * refine round-trip, mirroring BriefReview.
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { aiRefineBrief } from './aiClient';
import { PROMPT_TEMPLATES } from './promptTemplateData';
import AiIcon from './AiIcon';

export default function PromptTemplates( {
	onCreate,
	onClose,
	sending,
	disabled,
} ) {
	// Nothing is selected and the box is empty by default — the suggestions are
	// optional starting points, not a forced choice. The user can type their own
	// prompt straight away, or click a suggestion to seed the box.
	const [ selectedId, setSelectedId ] = useState( '' );
	const [ prompt, setPrompt ] = useState( '' );
	const [ showRefine, setShowRefine ] = useState( false );
	const [ instruction, setInstruction ] = useState( '' );
	const [ refining, setRefining ] = useState( false );
	const [ error, setError ] = useState( '' );

	// The blurred "more below" overlay must disappear once the list is scrolled
	// to the end (or doesn't overflow at all) — otherwise the final template
	// stays permanently blurred with nothing actually beneath it. `atEnd` drives
	// a class on the wrapper that fades the overlay out.
	const listRef = useRef( null );
	const [ atEnd, setAtEnd ] = useState( false );

	// The prompt textarea is tall (rows=10), so the refine panel opens below the
	// fold of the scroll region — bring it (and its Refine button) into view when
	// it appears. `block: 'end'` scrolls the nearest scrollable ancestor
	// (`.bp-cb-ptpl__scroll`) so the refine section's bottom is visible.
	const refineRef = useRef( null );
	useEffect( () => {
		if ( showRefine ) {
			refineRef.current?.scrollIntoView( {
				behavior: 'smooth',
				block: 'end',
			} );
		}
	}, [ showRefine ] );

	const updateAtEnd = useCallback( () => {
		const el = listRef.current;
		if ( ! el ) return;
		const remaining = el.scrollHeight - el.scrollTop - el.clientHeight;
		setAtEnd( remaining <= 1 );
	}, [] );

	// Measure on mount (and whenever the callback identity changes) so a list
	// that doesn't overflow starts with the overlay already hidden.
	useEffect( () => {
		updateAtEnd();
	}, [ updateAtEnd ] );

	// Either an in-flight generation (owned by the panel) or a refine call (owned
	// here) blocks the controls — kept separate so only the relevant button shows
	// its own working label.
	const busy = sending || refining;
	const hasPrompt = prompt.trim().length > 0;

	const closeRefine = () => {
		setShowRefine( false );
		setInstruction( '' );
	};

	// Clicking a card replaces the box with that suggestion's prompt. Any edits to
	// the previous one are discarded — the box is "the chosen suggestion", same as
	// re-picking would reset a form field.
	const pick = ( tpl ) => {
		setSelectedId( tpl.id );
		setPrompt( tpl.prompt );
		setError( '' );
		closeRefine();
	};

	const doRefine = async () => {
		if ( busy || disabled || ! hasPrompt ) return;
		setRefining( true );
		setError( '' );
		try {
			const res = await aiRefineBrief( {
				brief: prompt.trim(),
				instruction: instruction.trim(),
			} );
			// Replace the prompt with the improved one; keep editing or refine
			// again. `?? prompt` keeps the current text if the server somehow
			// returned nothing rather than blanking the box.
			setPrompt( res?.brief ?? prompt );
			closeRefine();
		} catch ( e ) {
			setError(
				e?.message ??
					__(
						'Could not refine the prompt. Please try again.',
						'better-payment'
					)
			);
		} finally {
			setRefining( false );
		}
	};

	const doCreate = () => {
		if ( ! hasPrompt || busy || disabled ) return;
		onCreate( prompt.trim() );
	};

	return (
		<div className="bp-cb-ptpl">
			<div className="bp-cb-ptpl__head">
				<div className="bp-cb-ptpl__intro">
					<p className="bp-cb-ptpl__intro-title">
						{ __( 'Prompt Suggestions', 'better-payment' ) }
					</p>
					<p className="bp-cb-ptpl__intro-sub">
						{ __(
							"Choose a suggested prompt or describe the campaign you'd like to create.",
							'better-payment'
						) }
					</p>
				</div>
				<button
					type="button"
					className="bp-cb-ptpl__close"
					onClick={ onClose }
					disabled={ busy }
					aria-label={ __( 'Close suggestions', 'better-payment' ) }
				>
					<span className="dashicons dashicons-no-alt" />
				</button>
			</div>

			{ /* Only this middle region scrolls; the header above and the footer
			     below stay pinned so "Create campaign" is always reachable no
			     matter how tall the prompt textarea grows. */ }
			<div className="bp-cb-ptpl__scroll">
				<div className="bp-cb-ptpl__list-label">
					<span>{ __( 'Suggested Prompts', 'better-payment' ) }</span>
					<span className="bp-cb-ptpl__count">
						{ sprintf(
							/* translators: %d: number of ready prompts. */
							_n(
								'%d Ready Prompt',
								'%d Ready Prompts',
								PROMPT_TEMPLATES.length,
								'better-payment'
							),
							PROMPT_TEMPLATES.length
						) }
					</span>
				</div>

				{ /* Four rows sit fully in view; the fifth peeks under a blurred
			     overlay that says "more below" — no scrollbar (see style.scss).
			     The overlay lives on the wrapper so it stays pinned while the
			     inner <ul> scrolls (scrollbar hidden) under it. */ }
				<div
					className={ `bp-cb-ptpl__list-wrap${
						atEnd ? ' bp-cb-ptpl__list-wrap--end' : ''
					}` }
				>
					<ul
						ref={ listRef }
						onScroll={ updateAtEnd }
						className="bp-cb-ptpl__list"
						role="listbox"
						aria-label={ __(
							'Prompt suggestions',
							'better-payment'
						) }
					>
						{ PROMPT_TEMPLATES.map( ( tpl ) => {
							const active = tpl.id === selectedId;
							return (
								<li key={ tpl.id }>
									<button
										type="button"
										className={ `bp-cb-ptpl__item${
											active
												? ' bp-cb-ptpl__item--active'
												: ''
										}` }
										onClick={ () => pick( tpl ) }
										disabled={ busy }
										role="option"
										aria-selected={ active }
										title={ tpl.title }
									>
										<span className="bp-cb-ptpl__item-icon">
											<span
												className={ `dashicons dashicons-${
													tpl.icon ?? 'heart'
												}` }
											/>
										</span>
										<span className="bp-cb-ptpl__item-body">
											<span className="bp-cb-ptpl__item-title">
												{ tpl.title }
											</span>
											{ tpl.description && (
												<span className="bp-cb-ptpl__item-desc">
													{ tpl.description }
												</span>
											) }
										</span>
										<span
											className="bp-cb-ptpl__item-check"
											aria-hidden="true"
										>
											{ active && (
												<span className="dashicons dashicons-yes" />
											) }
										</span>
									</button>
								</li>
							);
						} ) }
					</ul>
				</div>

				{ /* The editable prompt for the selected title. wp-admin styles bare
			     textareas, so border/padding are answered in style.scss (Rule 2a). */ }
				<div className="bp-cb-ptpl__editor">
					<div className="bp-cb-ptpl__editor-head">
						<span className="bp-cb-ptpl__editor-label">
							{ __( 'Describe Your Campaign', 'better-payment' ) }
						</span>
						<span className="bp-cb-ptpl__count">
							{ sprintf(
								/* translators: %d: number of characters typed. */
								_n(
									'%d character',
									'%d characters',
									prompt.length,
									'better-payment'
								),
								prompt.length
							) }
						</span>
					</div>
					<textarea
						className="bp-cb-ptpl__text"
						value={ prompt }
						onChange={ ( e ) => setPrompt( e.target.value ) }
						rows={ 10 }
						disabled={ busy }
						placeholder={ __(
							'Describe the campaign you want to build — or pick a suggestion above to get started.\n\nFor example: “Create a fundraising campaign for a local animal shelter with an emotional hero section, a $10,000 donation goal, a progress tracker, rescue stories, and a strong call-to-action.”',
							'better-payment'
						) }
						aria-label={ __( 'Campaign prompt', 'better-payment' ) }
					/>
				</div>

				{ error && <p className="bp-cb-ptpl__error">{ error }</p> }

				{ showRefine ? (
					<div className="bp-cb-ptpl__refine" ref={ refineRef }>
						<textarea
							className="bp-cb-ptpl__refine-input"
							value={ instruction }
							onChange={ ( e ) =>
								setInstruction( e.target.value )
							}
							rows={ 2 }
							placeholder={ __(
								'How should I improve it? e.g. make it more urgent, focus on families…',
								'better-payment'
							) }
							disabled={ busy }
							aria-label={ __(
								'How should I improve the prompt?',
								'better-payment'
							) }
						/>
						<div className="bp-cb-ptpl__refine-actions">
							<button
								type="button"
								className="bp-cb-ptpl__refine-cancel"
								onClick={ closeRefine }
								disabled={ refining }
							>
								{ __( 'Cancel', 'better-payment' ) }
							</button>
							<button
								type="button"
								className="bp-cb-ptpl__refine-go"
								onClick={ doRefine }
								disabled={ busy || disabled }
							>
								{ refining
									? __( 'Refining…', 'better-payment' )
									: __( 'Refine', 'better-payment' ) }
							</button>
						</div>
					</div>
				) : (
					<button
						type="button"
						className="bp-cb-ptpl__refine-toggle"
						onClick={ () => setShowRefine( true ) }
						disabled={ busy || disabled || ! hasPrompt }
					>
						<AiIcon size={ 14 } />
						{ __( 'Refine With AI', 'better-payment' ) }
					</button>
				) }
			</div>

			<div className="bp-cb-ptpl__footer">
				<button
					type="button"
					className="bp-cb-ptpl__create"
					onClick={ doCreate }
					disabled={ busy || disabled || ! hasPrompt }
				>
					{ sending
						? __( 'Creating…', 'better-payment' )
						: __( 'Generate Campaign', 'better-payment' ) }
				</button>
			</div>
		</div>
	);
}
