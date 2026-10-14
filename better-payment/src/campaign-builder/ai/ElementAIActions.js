/**
 * Inline AI quick-edit row shown at the top of the Customize panel for the
 * selected element. Text elements get Rewrite / Shorten / Improve / More urgent;
 * photo elements get Generate image. Built on useElementAI.
 */

import { __ } from '@wordpress/i18n';
import { useElementAI } from './useElementAI';
import { hasEditableCopy } from './elementCapabilities';
import { isAiUsable, readAiConfigSeed } from '../utils/aiConfig';
import AiIcon from './AiIcon';

const TEXT_ACTIONS = [
	{ key: 'rewrite', label: __( 'Rewrite', 'better-payment' ) },
	{ key: 'shorten', label: __( 'Shorten', 'better-payment' ) },
	{ key: 'improve', label: __( 'Improve', 'better-payment' ) },
	{ key: 'urgent', label: __( 'More Urgent', 'better-payment' ) },
];

export default function ElementAIActions( {
	element,
	elementLabel,
	campaignId,
	state,
	dispatch,
	aiUsable = true,
	lockedReason = '',
} ) {
	const { busyKey, runAction, generateImage } = useElementAI( {
		campaignId,
		state,
		dispatch,
	} );

	if ( ! element ) return null;

	// While AI is unusable (toggle off or no API key) the row is still SHOWN, so
	// the feature stays discoverable, but every button is disabled and its click
	// handler is a no-op — a live button would only answer "Working…" and then
	// fail (`ai_disabled` from the REST guard, or a provider with no key). Two
	// inputs on the one predicate: the caller's `aiUsable` prop, and the
	// synchronous seed, so a caller that forgets the prop still cannot send a
	// request and the first paint is already correct.
	const locked =
		! aiUsable ||
		! isAiUsable(
			readAiConfigSeed() ?? { enabled: true, configured: true }
		);

	const isPhoto = element.type === 'photo';
	const busy = !! busyKey;
	const disabled = locked || busy;

	// Which actions a widget gets is derived from its own schema, not a hand-kept
	// denylist — a widget made of live figures and toggles has nothing to
	// "shorten", and offering it invites the model to invent prose for a field
	// that holds a number. See elementCapabilities.js.
	if ( ! isPhoto && ! hasEditableCopy( element.type ) ) {
		return null;
	}

	return (
		<div
			className={ `bp-cb-el-ai${ locked ? ' bp-cb-el-ai--locked' : '' }` }
			title={ locked && lockedReason ? lockedReason : undefined }
		>
			<span className="bp-cb-el-ai__label">
				<AiIcon size={ 13 } />
				{ __( 'AI Quick Edit', 'better-payment' ) }
			</span>
			<div className="bp-cb-el-ai__actions">
				{ isPhoto ? (
					<button
						type="button"
						className="bp-cb-el-ai__btn"
						onClick={ () => ! locked && generateImage( element ) }
						disabled={ disabled }
					>
						{ busyKey === `${ element.id }:image`
							? __( 'Generating…', 'better-payment' )
							: __( 'Generate Image', 'better-payment' ) }
					</button>
				) : (
					TEXT_ACTIONS.map( ( a ) => (
						<button
							key={ a.key }
							type="button"
							className="bp-cb-el-ai__btn"
							onClick={ () =>
								! locked &&
								runAction( element, a.key, elementLabel )
							}
							disabled={ disabled }
						>
							{ busyKey === `${ element.id }:${ a.key }`
								? __( 'Working…', 'better-payment' )
								: a.label }
						</button>
					) )
				) }
			</div>
			{ locked && lockedReason && (
				<p className="bp-cb-el-ai__note">{ lockedReason }</p>
			) }
		</div>
	);
}
