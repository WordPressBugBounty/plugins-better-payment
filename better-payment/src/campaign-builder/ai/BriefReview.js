/**
 * Brief review — the step between the Smart Prompt Wizard and generation.
 *
 * The wizard assembles a brief from the user's answers; instead of generating
 * straight away, we show that brief here so the user can *see and shape the
 * intent before* spending a full generation. Two ways to shape it:
 *
 *  - **Edit it directly.** The brief is a plain, editable textarea — approve =
 *    click Generate, tweak = type in the box. This costs nothing (no AI call).
 *  - **Refine with AI.** Optional: describe an improvement and the assistant
 *    rewrites the brief (POST /ai/brief → BriefWriter). Loops as many times as
 *    wanted; each refine is one API call.
 *
 * What is deliberately NOT here: the fundraising goal and end date. Those are
 * structured answers owned by the wizard and enforced server-side at generation
 * (UserFieldGuard / DateGuard); letting the AI refine step rewrite them as prose
 * would risk re-introducing the invented-goal / elapsed-date bugs that guarding
 * exists to prevent. So `category` and `fields` pass through this component
 * untouched — only the narrative brief prose is editable/refinable.
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { aiRefineBrief } from './aiClient';
import AiIcon from './AiIcon';

export default function BriefReview( {
	initialBrief,
	onGenerate,
	onBack,
	sending,
	disabled,
} ) {
	const [ brief, setBrief ] = useState( initialBrief || '' );
	const [ showRefine, setShowRefine ] = useState( false );
	const [ instruction, setInstruction ] = useState( '' );
	const [ refining, setRefining ] = useState( false );
	const [ error, setError ] = useState( '' );
	const refineRef = useRef( null );

	// The brief textarea is tall (rows=8), so the refine panel opens below the
	// fold — scroll it (and its Refine button) into view when it appears.
	// `block: 'end'` scrolls whichever ancestor actually scrolls so its bottom is
	// visible, which is what the user asked for ("scroll me to the bottom").
	useEffect( () => {
		if ( showRefine ) {
			refineRef.current?.scrollIntoView( {
				behavior: 'smooth',
				block: 'end',
			} );
		}
	}, [ showRefine ] );

	// Either an in-flight generation (owned by the panel) or a refine call
	// (owned here) blocks the controls. Kept separate so only the relevant
	// button shows its own working label.
	const busy = sending || refining;
	const hasBrief = brief.trim().length > 0;

	const closeRefine = () => {
		setShowRefine( false );
		setInstruction( '' );
	};

	const doRefine = async () => {
		if ( busy || disabled || ! hasBrief ) return;
		setRefining( true );
		setError( '' );
		try {
			const res = await aiRefineBrief( {
				brief: brief.trim(),
				instruction: instruction.trim(),
			} );
			// Replace the brief with the improved one; the user can keep editing
			// it or refine again. `?? brief` keeps the current text if the server
			// somehow returned nothing rather than blanking the box.
			setBrief( res?.brief ?? brief );
			closeRefine();
		} catch ( e ) {
			setError(
				e?.message ??
					__(
						'Could not refine the brief. Please try again.',
						'better-payment'
					)
			);
		} finally {
			setRefining( false );
		}
	};

	const doGenerate = () => {
		if ( ! hasBrief || busy || disabled ) return;
		onGenerate( brief.trim() );
	};

	return (
		<div className="bp-cb-brief">
			<div className="bp-cb-brief__intro">
				<p className="bp-cb-brief__intro-title">
					{ __( 'Review Your Campaign Brief', 'better-payment' ) }
				</p>
				<p className="bp-cb-brief__intro-sub">
					{ __(
						"This is what I'll build from. Edit it directly, or ask me to refine it — your goal and end date stay as you set them.",
						'better-payment'
					) }
				</p>
			</div>

			<textarea
				className="bp-cb-brief__text"
				value={ brief }
				onChange={ ( e ) => setBrief( e.target.value ) }
				rows={ 8 }
				disabled={ busy }
				aria-label={ __( 'Campaign brief', 'better-payment' ) }
			/>

			{ error && <p className="bp-cb-brief__error">{ error }</p> }

			{ showRefine ? (
				<div className="bp-cb-brief__refine" ref={ refineRef }>
					<textarea
						className="bp-cb-brief__refine-input"
						value={ instruction }
						onChange={ ( e ) => setInstruction( e.target.value ) }
						rows={ 2 }
						placeholder={ __(
							'How should I improve it? e.g. make it more urgent, add detail about the family…',
							'better-payment'
						) }
						disabled={ busy }
						aria-label={ __(
							'How should I improve the brief?',
							'better-payment'
						) }
					/>
					<div className="bp-cb-brief__refine-actions">
						<button
							type="button"
							className="bp-cb-brief__refine-cancel"
							onClick={ closeRefine }
							disabled={ refining }
						>
							{ __( 'Cancel', 'better-payment' ) }
						</button>
						<button
							type="button"
							className="bp-cb-brief__refine-go"
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
					className="bp-cb-brief__refine-toggle"
					onClick={ () => setShowRefine( true ) }
					disabled={ busy || disabled || ! hasBrief }
				>
					<AiIcon size={ 14 } />
					{ __( 'Refine With AI', 'better-payment' ) }
				</button>
			) }

			<div className="bp-cb-brief__footer">
				<button
					type="button"
					className="bp-cb-brief__generate"
					onClick={ doGenerate }
					disabled={ busy || disabled || ! hasBrief }
				>
					{ sending
						? __( 'Generating…', 'better-payment' )
						: __( 'Generate Campaign', 'better-payment' ) }
				</button>
				<button
					type="button"
					className="bp-cb-brief__back"
					onClick={ onBack }
					disabled={ busy }
				>
					{ __( 'Back to Questions', 'better-payment' ) }
				</button>
			</div>
		</div>
	);
}
