import { __ } from '@wordpress/i18n';

/**
 * Per-element upgrade content shown when a free user tries to add a Pro-only
 * element (Donors Wall, FAQ, Video). Copy is written to convert — it names the
 * concrete benefit of each widget, not just "this is Pro".
 */
const PRO_CONTENT = {
	donors_wall: {
		label: __( 'Donors Wall', 'better-payment' ),
		tagline: __(
			'Turn real donations into social proof.',
			'better-payment'
		),
		description: __(
			'Automatically showcase the people backing your campaign. Nothing builds trust — and inspires new donations — like a live wall of real supporters.',
			'better-payment'
		),
		benefits: [
			__(
				'Pulls real donors straight from your campaign payments',
				'better-payment'
			),
			__(
				'Donor avatars, names, and amounts — no manual entry',
				'better-payment'
			),
			__(
				'Vertical or horizontal layouts with full styling control',
				'better-payment'
			),
			__(
				'Proven to boost trust and conversion rates',
				'better-payment'
			),
		],
	},
	faq: {
		label: __( 'FAQ', 'better-payment' ),
		tagline: __(
			'Answer objections before they cost you a donation.',
			'better-payment'
		),
		description: __(
			'Add a clean, collapsible FAQ so donors get instant answers about how their money is used and whether payments are secure — the moments that make or break a gift.',
			'better-payment'
		),
		benefits: [
			__( 'Unlimited question & answer items', 'better-payment' ),
			__( 'Collapsible accordion or open-list styles', 'better-payment' ),
			__( 'Reduce support requests and hesitation', 'better-payment' ),
			__( 'Drag to reorder, fully customizable', 'better-payment' ),
		],
	},
	video: {
		label: __( 'Video', 'better-payment' ),
		tagline: __(
			'Tell your story in the format that converts best.',
			'better-payment'
		),
		description: __(
			'Embed a compelling campaign video to connect emotionally with supporters. Campaigns with video consistently raise more than those without.',
			'better-payment'
		),
		benefits: [
			__( 'Embed YouTube, Vimeo, or self-hosted MP4', 'better-payment' ),
			__(
				'Fully responsive with 16:9, 4:3, and 1:1 ratios',
				'better-payment'
			),
			__( 'Autoplay and player controls', 'better-payment' ),
			__( 'Increase engagement and time on page', 'better-payment' ),
		],
	},
};

const GENERIC = {
	label: __( 'Premium Element', 'better-payment' ),
	tagline: __( 'Unlock more ways to grow your campaign.', 'better-payment' ),
	description: __(
		'This element is part of Better Payment Pro.',
		'better-payment'
	),
	benefits: [],
};

/**
 * Copy for a locked Pro *template* card.
 *
 * The template's own label and description carry the pitch — they are the same
 * strings the card shows, so the modal continues the thing the user clicked
 * rather than restating a generic "this is Pro". The benefits below describe what
 * every Pro template has in common (the three Pro elements it is built from),
 * which is the actual reason the design cannot be handed over for free.
 *
 * @param {Object} tpl Template card from `betterPaymentCampaignData.templates`.
 * @return {Object} Content in the same shape as PRO_CONTENT's entries.
 */
const templateContent = ( tpl ) => ( {
	label: tpl?.label || __( 'Premium Template', 'better-payment' ),
	tagline: __(
		'A ready-made campaign design, included with Better Payment Pro.',
		'better-payment'
	),
	description:
		tpl?.description ||
		__(
			'This pre-built campaign layout is part of Better Payment Pro.',
			'better-payment'
		),
	benefits: [
		__(
			'Launch a complete, designed campaign page in one click',
			'better-payment'
		),
		__(
			'Built with the Pro elements — Donors Wall, FAQ and Video',
			'better-payment'
		),
		__(
			'Hand-tuned typography, colours and layout per template',
			'better-payment'
		),
		__(
			'Four premium templates, plus every future Pro addition',
			'better-payment'
		),
	],
} );

const CrownIcon = () => (
	<svg
		width="28"
		height="28"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M3 7l4 4 5-6 5 6 4-4v10.5c0 .8-.7 1.5-1.5 1.5h-15c-.8 0-1.5-.7-1.5-1.5V7z"
			fill="currentColor"
		/>
	</svg>
);

const CheckIcon = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 20 20"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M5 10.5l3 3 7-7.5"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

/**
 * @param {Object}           props
 * @param {string}           props.type     Element type key (donors_wall|faq|video). Ignored when `template` is given.
 * @param {Object|undefined} props.template Locked Pro template card — switches the modal to template copy.
 * @param {Function}         props.onClose  Close handler.
 */
export default function UpgradeModal( { type, template, onClose } ) {
	// `template` wins over `type` so a caller never has to invent a fake element
	// type for a template — the two upsells share the shell and nothing else.
	const content = template
		? templateContent( template )
		: PRO_CONTENT[ type ] || GENERIC;
	const upgradeUrl =
		window?.betterPaymentCampaignData?.upgradeUrl ||
		'https://wpdeveloper.com/in/upgrade-better-payment-pro';

	return (
		<div className="bp-cb-upgrade-overlay" onClick={ onClose }>
			<div
				className="bp-cb-upgrade-modal"
				onClick={ ( e ) => e.stopPropagation() }
				role="dialog"
				aria-modal="true"
			>
				<button
					className="bp-cb-upgrade-close"
					onClick={ onClose }
					aria-label={ __( 'Close', 'better-payment' ) }
				>
					✕
				</button>

				<div className="bp-cb-upgrade-header">
					<span className="bp-cb-upgrade-crown">
						<CrownIcon />
					</span>
					<span className="bp-cb-upgrade-pill">
						{ __( 'PRO', 'better-payment' ) }
					</span>
				</div>

				<h2 className="bp-cb-upgrade-title">
					{ /* translators: %s: element name */ }
					{ content.label }
				</h2>
				<p className="bp-cb-upgrade-tagline">{ content.tagline }</p>
				<p className="bp-cb-upgrade-desc">{ content.description }</p>

				{ content.benefits.length > 0 && (
					<ul className="bp-cb-upgrade-benefits">
						{ content.benefits.map( ( benefit, i ) => (
							<li key={ i }>
								<span className="bp-cb-upgrade-check">
									<CheckIcon />
								</span>
								<span>{ benefit }</span>
							</li>
						) ) }
					</ul>
				) }

				<div className="bp-cb-upgrade-actions">
					<a
						className="bp-cb-upgrade-cta"
						href={ upgradeUrl }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Upgrade to Pro', 'better-payment' ) }
					</a>
					<button
						type="button"
						className="bp-cb-upgrade-later"
						onClick={ onClose }
					>
						{ __( 'Maybe later', 'better-payment' ) }
					</button>
				</div>

				<p className="bp-cb-upgrade-footnote">
					{ template
						? __(
								'Unlock all four premium campaign templates, plus the Donors Wall, FAQ and Video elements.',
								'better-payment'
						  )
						: __(
								'Unlock Donors Wall, FAQ, Video and more premium campaign elements.',
								'better-payment'
						  ) }
				</p>
			</div>
		</div>
	);
}
