/**
 * Smart Prompt Wizard.
 *
 * Instead of asking the user to write a long prompt, this collects a few quick
 * answers (cause, location, target, tone, audience, deadline) and assembles a
 * rich, specific brief. On submit it hands that brief up via `onGenerate` — which
 * no longer generates immediately: the panel routes it to a brief-review step
 * (see BriefReview) where the user can edit or AI-refine it before approving.
 * Shown in place of the free-text composer while the campaign is empty; the user
 * can switch to writing their own prompt at any time.
 *
 * Every question takes exactly one answer. Layout and the amount presets render
 * as a real radio group (see ChoiceGroup) — a handful of short answers that read
 * well as a row of tiles. Cause, tone, audience, and the end date instead render
 * as a native `<select>` (see SelectField): the cause/tone/audience lists run
 * long enough that a tile grid would sprawl several rows deep, and a select gets
 * roving-tabindex/arrow-key handling and a built-in "no preference" affordance
 * (the blank placeholder option) for free.
 *
 * The optional questions start with nothing selected — leaving one blank means the
 * model is never told about a preference the user did not express. Re-opening a
 * select and picking its blank placeholder clears the answer; that is SelectField's
 * equivalent of ChoiceGroup's **Clear** button (which the tile groups here still
 * need, since a tile can't be un-picked by clicking it again).
 *
 * Tone and Audience label that blank placeholder **"Let AI decide"**: they are pure
 * creative hints, so an unset value is not a gap to leave empty — it is an explicit
 * hand-off, and the prompt rules (Prompt/Templates/rules.php → "unspecified creative
 * preferences") tell the model to pick a tone/audience that fits the campaign rather
 * than fall back to a bland default. That is safe precisely because neither is a
 * guarded meta value. The **End date** keeps "No preference" on purpose: an unset end
 * date must STAY empty (UserFieldGuard/DateGuard + EndDateGuardTest) — the AI must
 * never invent a deadline — and "No deadline" is its explicit no-end-date option.
 *
 * Only Layout keeps an explicit "Let AI decide" *option* (a real value, `__ai__`),
 * because there it is a generation preference owned by the panel (`activeLayoutKey`),
 * not the absence of one — see docs/features/ai/README.md.
 *
 * "What are you raising funds for?" offers every campaign category we ship
 * prebuilt templates for (from the shared registry — see utils/campaignCategories),
 * plus the extra causes below. Picking a category sends its slug on to
 * generation, which dresses the campaign in that category's existing artwork;
 * anything else leaves the slug empty and lets the server infer one from the
 * brief, falling back to the default hero image. See docs/features/ai/image-generation.md.
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getGlobalCurrencySymbol } from '../utils/currency';
import { getCampaignCategories } from '../utils/campaignCategories';
import ChoiceGroup from './ChoiceGroup';
import SelectField from './SelectField';
import DatePopover, {
	toIsoDate,
	todayIso,
	formatDisplayDate,
	getStartOfWeek,
} from './DatePopover';

/** The unanswered state for an optional question. */
const ANY = '';

/** The end-date answer that reveals the date picker. */
const CUSTOM_DEADLINE = 'custom';

/**
 * Build `{ value, label }` options from plain answer labels.
 *
 * @param {string[]} labels Answer labels, used as both value and display text.
 * @return {Array<{value: string, label: string}>} Options for ChoiceGroup.
 */
const toOptions = ( labels ) =>
	labels.map( ( label ) => ( { value: label, label } ) );

/**
 * Causes offered *in addition to* the campaign categories.
 *
 * The category tiles come from the shared registry (every category we ship
 * prebuilt templates for, so the wizard and the template picker always offer the
 * same set). These are the narrower, more evocative answers that have no
 * category of their own — kept because they make for a better brief than the
 * broad category name does.
 *
 * They carry **no category slug on purpose.** Where one of these does belong to
 * a category, the server infers it from the brief text: "animal rescue" is a
 * `charity` keyword, "disaster relief" an `environmental` one. That keeps the
 * cause→category mapping in exactly one place (CategoryRegistry's keywords)
 * instead of duplicating it here, where it would drift. Ones that match nothing
 * — Memorial, Creative project — are the "new category" case and get the
 * default hero image.
 *
 * `brief` is untranslated: it becomes prompt text for an English-instructed
 * model, so a translated label would yield a mixed-language sentence.
 */
const EXTRA_CAUSES = [
	{
		slug: 'animal-rescue',
		label: __( 'Animal rescue', 'better-payment' ),
		brief: 'animal rescue',
	},
	{
		slug: 'disaster-relief',
		label: __( 'Disaster relief', 'better-payment' ),
		brief: 'disaster relief',
	},
	{
		slug: 'emergency',
		label: __( 'Emergency', 'better-payment' ),
		brief: 'emergency',
	},
	{
		slug: 'community',
		label: __( 'Community', 'better-payment' ),
		brief: 'community',
	},
	{
		slug: 'nonprofit',
		label: __( 'Nonprofit', 'better-payment' ),
		brief: 'nonprofit',
	},
	{
		slug: 'sports-team',
		label: __( 'Sports team', 'better-payment' ),
		brief: 'sports team',
	},
	{
		slug: 'memorial',
		label: __( 'Memorial', 'better-payment' ),
		brief: 'memorial',
	},
	{
		slug: 'creative-project',
		label: __( 'Creative project', 'better-payment' ),
		brief: 'creative project',
	},
];

/**
 * Every answer to "What are you raising funds for?" — the campaign categories
 * first, then the extra causes.
 *
 * Values are namespaced (`cat:` / `cause:`) so a category slug and an extra
 * cause can never collide on the same value, which would break the radio group
 * (two options rendering as checked) if a category were ever registered under a
 * name an extra cause already uses.
 *
 * @return {Array<{value: string, label: string, brief: string, category: string}>}
 *         Answers; `category` is the slug to send, or '' to let the server infer.
 */
export function buildCauses() {
	return [
		...getCampaignCategories().map( ( c ) => ( {
			value: `cat:${ c.slug }`,
			label: c.label,
			brief: c.brief,
			category: c.slug,
		} ) ),
		...EXTRA_CAUSES.map( ( c ) => ( {
			value: `cause:${ c.slug }`,
			label: c.label,
			brief: c.brief,
			category: '',
		} ) ),
	];
}

/**
 * The correct indefinite article for a word, so the brief reads "for an
 * environmental cause" rather than "for a environmental cause".
 *
 * @param {string} word The word the article precedes.
 * @return {string} 'a' or 'an'.
 */
const article = ( word ) => ( /^[aeiou]/i.test( word ) ? 'an' : 'a' );

const TONES = [
	__( 'Emotional', 'better-payment' ),
	__( 'Urgent', 'better-payment' ),
	__( 'Hopeful', 'better-payment' ),
	__( 'Warm', 'better-payment' ),
	__( 'Professional', 'better-payment' ),
	__( 'Inspiring', 'better-payment' ),
];

const AUDIENCES = [
	__( 'Friends & family', 'better-payment' ),
	__( 'Local community', 'better-payment' ),
	__( 'General public', 'better-payment' ),
	__( 'Businesses', 'better-payment' ),
];

const AMOUNT_PRESETS = [ 500, 1000, 5000, 10000, 25000, 50000 ];

const DEADLINES = [
	{ value: '30', label: __( '30 days', 'better-payment' ), days: 30 },
	{ value: '60', label: __( '60 days', 'better-payment' ), days: 60 },
	{ value: '90', label: __( '90 days', 'better-payment' ), days: 90 },
	{ value: 'none', label: __( 'No deadline', 'better-payment' ), days: null },
	{
		value: CUSTOM_DEADLINE,
		label: __( 'Pick a date', 'better-payment' ),
		days: undefined,
	},
];

/**
 * Format a target amount with thousands separators (no decimals).
 *
 * @param {string|number} value Raw amount; non-digits are stripped.
 * @return {string} The formatted amount, or '' when there are no digits.
 */
function formatAmount( value ) {
	const n = parseInt( String( value ).replace( /[^\d]/g, '' ), 10 );
	return Number.isFinite( n ) ? n.toLocaleString() : '';
}

/**
 * Local `YYYY-MM-DD` date `days` from today.
 *
 * Formatted from the local date parts via `toIsoDate`, not `toISOString()`: the
 * latter converts to UTC first, so the shortcut could land a day either side of
 * the intended one depending on the viewer's offset.
 *
 * @param {number} days Days to add to today.
 * @return {string} The resulting date as YYYY-MM-DD.
 */
function isoInDays( days ) {
	const d = new Date();
	d.setDate( d.getDate() + days );
	return toIsoDate( d );
}

/**
 * Map the chosen end-date answer onto the `deadline*` fields `buildBrief` reads.
 *
 * The three shapes that matter: unanswered (say nothing about an end date), "No
 * deadline" (say so explicitly), and a real date — whether picked from the
 * calendar or derived from a "30 days"-style shortcut. "Pick a date" with no date
 * chosen yet resolves to no date rather than to today.
 *
 * @param {string} deadlineKey A `DEADLINES` value, or `ANY` when unanswered.
 * @param {string} customDate  The calendar date (YYYY-MM-DD), when one is picked.
 * @return {{deadlineChosen: boolean, deadlineDays: (number|null|undefined), deadlineDate: string}}
 *         The deadline fields for `buildBrief`.
 */
export function resolveDeadline( deadlineKey, customDate ) {
	const deadline = DEADLINES.find( ( d ) => d.value === deadlineKey );

	let deadlineDate = '';
	if ( CUSTOM_DEADLINE === deadlineKey ) {
		deadlineDate = customDate || '';
	} else if ( deadline?.days ) {
		deadlineDate = isoInDays( deadline.days );
	}

	return {
		deadlineChosen: ANY !== deadlineKey,
		deadlineDays: deadline?.days,
		deadlineDate,
	};
}

/**
 * The campaign fields the user answered themselves, keyed by campaign meta key.
 *
 * Sent alongside the brief so the server can hold the model to them. Both keys
 * are ALWAYS present, even when blank — that is the whole point. The brief omits
 * an unanswered goal or deadline entirely, but "the brief is silent" and "the
 * user was asked and chose to leave it empty" are different claims, and only the
 * second one can be enforced. A key present with `''` tells the server to keep
 * that field empty no matter what the model returns; a missing key would instead
 * let the model's invented value through (the free-form prompt path).
 *
 * "No deadline" and "unanswered" both resolve to an empty `bpc_end_date` here —
 * neither produces a date, and the difference between them is already carried in
 * the brief's wording.
 *
 * @see includes/AI/Services/UserFieldGuard.php
 *
 * @param {Object} a              Answers: `amount` and the resolved `deadlineDate`.
 * @param {string} a.amount       Digits-only target amount, '' when unanswered.
 * @param {string} a.deadlineDate Resolved end date (YYYY-MM-DD), '' when none.
 * @return {{bpc_goal_amount: string, bpc_end_date: string}} Fields for the server.
 */
export function buildUserFields( a ) {
	return {
		bpc_goal_amount: ( a?.amount ?? '' ) === '' ? '' : String( a.amount ),
		bpc_end_date: a?.deadlineDate ?? '',
	};
}

/**
 * Assemble the answers into a rich natural-language brief for the model.
 *
 * Unanswered questions are simply omitted, so the model is never told about a
 * preference the user did not express.
 *
 * @param {Object} a              The wizard's answers (cause, details, location,
 *                                amount, tone, audience, deadline*).
 * @param {string} currencySymbol Symbol to prefix the fundraising goal with.
 * @return {string} The assembled brief.
 */
export function buildBrief( a, currencySymbol ) {
	const parts = [];

	const tone = a.tone ? a.tone.toLowerCase() : '';
	const cause = a.cause ? a.cause.toLowerCase() : '';
	let opening = tone
		? `Create a ${ tone } fundraising campaign`
		: 'Create a fundraising campaign';
	if ( cause ) opening += ` for ${ article( cause ) } ${ cause } cause`;
	const detail = ( a.details || '' ).trim();
	if ( detail ) opening += `: ${ detail }`;
	parts.push( opening + '.' );

	if ( ( a.location || '' ).trim() ) {
		parts.push( `Location: ${ a.location.trim() }.` );
	}
	if ( a.amount ) {
		parts.push(
			`Fundraising goal: ${ currencySymbol }${ formatAmount(
				a.amount
			) }.`
		);
	}
	if ( a.audience ) {
		parts.push( `Primary audience: ${ a.audience }.` );
	}
	if ( a.deadlineDate ) {
		parts.push( `The campaign should end on ${ a.deadlineDate }.` );
	} else if ( a.deadlineChosen && null === a.deadlineDays ) {
		parts.push( 'No fixed end date.' );
	}

	parts.push(
		'Write compelling, specific copy tailored to this cause and audience.'
	);
	return parts.join( ' ' );
}

export default function PromptWizard( {
	onGenerate,
	onWriteOwn,
	sending,
	disabled,
	layouts = [],
	activeLayoutKey = '__ai__',
	onSelectLayout,
} ) {
	const currencySymbol = getGlobalCurrencySymbol();
	const startOfWeek = getStartOfWeek();

	const [ cause, setCause ] = useState( ANY );
	const [ details, setDetails ] = useState( '' );
	const [ location, setLocation ] = useState( '' );
	const [ amount, setAmount ] = useState( '' );
	const [ tone, setTone ] = useState( ANY );
	const [ audience, setAudience ] = useState( ANY );
	const [ deadlineKey, setDeadlineKey ] = useState( ANY );
	const [ customDate, setCustomDate ] = useState( '' );
	const [ calendarOpen, setCalendarOpen ] = useState( false );

	const layoutOptions = useMemo(
		() => [
			{ value: '__ai__', label: __( 'Let AI decide', 'better-payment' ) },
			...layouts.map( ( tpl ) => ( {
				value: tpl.key,
				label: tpl.label,
			} ) ),
		],
		[ layouts ]
	);

	const causes = useMemo( () => buildCauses(), [] );
	const causeOptions = useMemo(
		() => causes.map( ( { value, label } ) => ( { value, label } ) ),
		[ causes ]
	);
	const toneOptions = useMemo( () => toOptions( TONES ), [] );
	const audienceOptions = useMemo( () => toOptions( AUDIENCES ), [] );
	const amountOptions = useMemo(
		() =>
			AMOUNT_PRESETS.map( ( preset ) => ( {
				value: String( preset ),
				label: `${ currencySymbol }${ preset.toLocaleString() }`,
			} ) ),
		[ currencySymbol ]
	);

	// A brief needs at least a cause or a description to be meaningful.
	const canGenerate = useMemo(
		() => !! ( cause || details.trim() ) && ! sending && ! disabled,
		[ cause, details, sending, disabled ]
	);

	const submit = () => {
		if ( ! canGenerate ) return;
		// The select holds a namespaced value; the brief needs the English cause
		// text and the server needs the category slug ('' for an extra cause, so
		// it infers one from the brief instead).
		const picked = causes.find( ( c ) => c.value === cause );
		const deadline = resolveDeadline( deadlineKey, customDate );
		const brief = buildBrief(
			{
				cause: picked?.brief ?? '',
				details,
				location,
				amount,
				tone,
				audience,
				...deadline,
			},
			currencySymbol
		);
		// The brief is prose the model interprets; the fields are the same two
		// answers as data, which the server enforces. Prose alone let the model
		// fill in a goal or an end date the user never gave.
		onGenerate(
			brief,
			picked?.category ?? '',
			buildUserFields( { amount, deadlineDate: deadline.deadlineDate } )
		);
	};

	const selectLayout = ( key ) => {
		if ( ! onSelectLayout ) return;
		onSelectLayout( layouts.find( ( tpl ) => tpl.key === key ) ?? null );
	};

	// The select's own blank placeholder is how this question gets cleared — no
	// separate Clear button, unlike the tile groups. Clearing drops the picked
	// date too — leaving it behind would silently reapply it if "Pick a date"
	// were chosen again later. Any other answer closes the calendar; "Pick a
	// date" is the one that opens it.
	const chooseDeadline = ( key ) => {
		if ( ANY === key ) {
			setDeadlineKey( ANY );
			setCustomDate( '' );
			setCalendarOpen( false );
			return;
		}
		setDeadlineKey( key );
		setCalendarOpen( CUSTOM_DEADLINE === key );
	};

	// The "Pick a date" option shows the chosen date in place of its own label,
	// so the answer is readable in the closed select without reopening it.
	const deadlineOptions = useMemo(
		() =>
			DEADLINES.map( ( option ) =>
				CUSTOM_DEADLINE === option.value && customDate
					? { ...option, label: formatDisplayDate( customDate ) }
					: option
			),
		[ customDate ]
	);

	return (
		<div className="bp-cb-aiw">
			<div className="bp-cb-aiw__intro">
				<p className="bp-cb-aiw__intro-title">
					{ __( "Let's Set Up Your Campaign", 'better-payment' ) }
				</p>
				<p className="bp-cb-aiw__intro-sub">
					{ __(
						"Answer a few quick questions — AI'll write the rest.",
						'better-payment'
					) }
				</p>
			</div>

			<div className="bp-cb-aiw__questions">
				{ layouts.length > 0 && (
					<ChoiceGroup
						id="bp-cb-aiw-layout"
						label={ __( 'Campaign Layout', 'better-payment' ) }
						options={ layoutOptions }
						value={ activeLayoutKey }
						onChange={ selectLayout }
						disabled={ sending }
					/>
				) }

				<SelectField
					id="bp-cb-aiw-cause"
					label={ __( 'Category', 'better-payment' ) }
					placeholder={ __( 'Select a cause', 'better-payment' ) }
					options={ causeOptions }
					value={ cause }
					onChange={ setCause }
					disabled={ sending }
				/>

				<div className="bp-cb-aiw__q">
					<label
						className="bp-cb-aiw__label"
						htmlFor="bp-cb-aiw-details"
					>
						{ __(
							'Anything More Specific About Campaign?',
							'better-payment'
						) }
						<span className="bp-cb-aiw__optional">
							{ __( '(optional)', 'better-payment' ) }
						</span>
					</label>
					<input
						id="bp-cb-aiw-details"
						type="text"
						className="bp-cb-aiw__input"
						value={ details }
						onChange={ ( e ) => setDetails( e.target.value ) }
						placeholder={ __(
							'Share more details about your campaign',
							'better-payment'
						) }
						disabled={ sending }
					/>
				</div>

				<div className="bp-cb-aiw__q">
					<label
						className="bp-cb-aiw__label"
						htmlFor="bp-cb-aiw-location"
					>
						{ __( 'Campaign Location?', 'better-payment' ) }
						<span className="bp-cb-aiw__optional">
							{ __( '(optional)', 'better-payment' ) }
						</span>
					</label>
					<input
						id="bp-cb-aiw-location"
						type="text"
						className="bp-cb-aiw__input"
						value={ location }
						onChange={ ( e ) => setLocation( e.target.value ) }
						placeholder={ __(
							'City, region, or country',
							'better-payment'
						) }
						disabled={ sending }
					/>
				</div>

				<div className="bp-cb-aiw__q">
					<label
						className="bp-cb-aiw__label"
						htmlFor="bp-cb-aiw-amount"
					>
						{ __( 'Campaign Goal Amount?', 'better-payment' ) }
						<span className="bp-cb-aiw__optional">
							{ __( '(optional)', 'better-payment' ) }
						</span>
					</label>
					<div className="bp-cb-aiw__amount">
						<span className="bp-cb-aiw__amount-symbol">
							{ currencySymbol }
						</span>
						<input
							id="bp-cb-aiw-amount"
							type="text"
							inputMode="numeric"
							className="bp-cb-aiw__input"
							value={ amount ? formatAmount( amount ) : '' }
							onChange={ ( e ) =>
								setAmount(
									e.target.value.replace( /[^\d]/g, '' )
								)
							}
							placeholder={ __(
								'Enter an amount',
								'better-payment'
							) }
							disabled={ sending }
						/>
					</div>
					<ChoiceGroup
						id="bp-cb-aiw-amount-presets"
						ariaLabel={ __(
							'Preset target amounts',
							'better-payment'
						) }
						options={ amountOptions }
						value={ String( amount ) }
						onChange={ setAmount }
						disabled={ sending }
						minWidth={ 96 }
					/>
				</div>

				<SelectField
					id="bp-cb-aiw-tone"
					label={ __( 'Campaign Content Tone?', 'better-payment' ) }
					placeholder={ __( 'Let AI decide', 'better-payment' ) }
					options={ toneOptions }
					value={ tone }
					onChange={ setTone }
					disabled={ sending }
				/>

				<SelectField
					id="bp-cb-aiw-audience"
					label={ __(
						'Who are you asking to donate?',
						'better-payment'
					) }
					placeholder={ __( 'Let AI decide', 'better-payment' ) }
					options={ audienceOptions }
					value={ audience }
					onChange={ setAudience }
					disabled={ sending }
				/>

				<SelectField
					id="bp-cb-aiw-deadline"
					label={ __( 'Campaign End Date?', 'better-payment' ) }
					placeholder={ __( 'No preference', 'better-payment' ) }
					options={ deadlineOptions }
					value={ deadlineKey }
					onChange={ chooseDeadline }
					disabled={ sending }
				>
					{ CUSTOM_DEADLINE === deadlineKey &&
						calendarOpen &&
						! sending && (
							<DatePopover
								value={ customDate }
								min={ todayIso() }
								startOfWeek={ startOfWeek }
								onSelect={ ( iso ) => {
									setCustomDate( iso );
									setCalendarOpen( false );
								} }
								onClose={ () => setCalendarOpen( false ) }
							/>
						) }
				</SelectField>
			</div>

			<div className="bp-cb-aiw__footer">
				{ ! canGenerate && ! sending && ! disabled && (
					<p className="bp-cb-aiw__requirement">
						{ __(
							'Choose a category above or describe your campaign to continue.',
							'better-payment'
						) }
					</p>
				) }
				<button
					type="button"
					className="bp-cb-aiw__generate"
					onClick={ submit }
					disabled={ ! canGenerate }
				>
					{ sending
						? __( 'Working…', 'better-payment' )
						: __( 'Review Brief', 'better-payment' ) }
				</button>
				<button
					type="button"
					className="bp-cb-aiw__writeown"
					onClick={ onWriteOwn }
					disabled={ sending }
				>
					{ __( 'Write My Own Prompt Instead', 'better-payment' ) }
				</button>
			</div>
		</div>
	);
}
