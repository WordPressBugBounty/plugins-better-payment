/**
 * Prompt templates for the free-form ("write your own") campaign composer.
 *
 * Each template is a *title* the user browses by, a one-line `description` shown
 * beneath it (so the list reads as a richer, scannable menu rather than a bare
 * column of names), an `icon` (dashicon slug) for quick visual identification,
 * plus a rich, detailed `prompt` that seeds the editable box once its title is
 * picked. Only title + description + icon show in the list — the prompt appears
 * after selection, in an editable textarea the user can tweak or refine with AI
 * before generating (mirrors the wizard's brief-review step, see BriefReview).
 *
 * Each prompt names a concrete example subject (e.g. "Disaster Relief") rather
 * than a `[bracketed]` placeholder — the editable box is where the user swaps in
 * their own cause, or asks the AI to refine the whole prompt, before creating the
 * campaign.
 *
 * Pure data + a tiny lookup, so it is trivially unit-testable (see
 * tests/JS/aiPromptTemplates.test.js) and holds the copy in one place.
 */

import { __ } from '@wordpress/i18n';

export const PROMPT_TEMPLATES = [
	{
		id: 'general-fundraising',
		title: __( 'General Fundraising Campaign', 'better-payment' ),
		description: __(
			'An all-purpose page for any cause',
			'better-payment'
		),
		icon: 'heart',
		prompt: __(
			'Create a modern fundraising campaign for Disaster Relief with a compelling hero section, emotional headline, mission statement, donation goal, progress tracker, beneficiary stories, impact statistics, FAQs, donor testimonials, and a strong call-to-action. Use compassionate, trust-building language that encourages one-time and recurring donations.',
			'better-payment'
		),
	},
	{
		id: 'nonprofit-donation-landing',
		title: __( 'Nonprofit Donation Landing Page', 'better-payment' ),
		description: __(
			'Trust-first landing page for an organization',
			'better-payment'
		),
		icon: 'building',
		prompt: __(
			'Build a professional nonprofit donation landing page for Clean Water Initiative. Include an engaging hero banner, clear mission overview, donation form, campaign milestones, impact metrics, volunteer opportunities, supporter testimonials, FAQs, and multiple donation call-to-action sections. The design should inspire trust, transparency, and generosity.',
			'better-payment'
		),
	},
	{
		id: 'charity-campaign',
		title: __( 'Charity Campaign', 'better-payment' ),
		description: __(
			'Problem, impact, and beneficiary stories',
			'better-payment'
		),
		icon: 'groups',
		prompt: __(
			'Generate a charity fundraising campaign for Homelessness Support that explains the problem, highlights how donations make a measurable impact, introduces the beneficiaries, showcases success stories, includes campaign progress, donor recognition, frequently asked questions, and persuasive donation calls-to-action. Maintain an empathetic and hopeful tone throughout.',
			'better-payment'
		),
	},
	{
		id: 'emergency-relief',
		title: __( 'Emergency Relief Campaign', 'better-payment' ),
		description: __(
			'Urgency-driven appeal for a crisis',
			'better-payment'
		),
		icon: 'sos',
		prompt: __(
			'Create an emergency fundraising campaign for Earthquake Recovery with an urgency-focused hero section, emergency appeal, donation progress tracker, immediate needs list, real-world impact statistics, volunteer opportunities, recovery updates, FAQs, and multiple donation buttons. Use compassionate messaging that motivates immediate action while building credibility and trust.',
			'better-payment'
		),
	},
	{
		id: 'community-fundraising',
		title: __( 'Community Fundraising Campaign', 'better-payment' ),
		description: __(
			'Local project with community participation',
			'better-payment'
		),
		icon: 'admin-multisite',
		prompt: __(
			'Design a community fundraising campaign supporting Community Health Clinic. Include a welcoming hero section, campaign overview, fundraising goal, community impact statistics, volunteer registration, sponsor showcase, event timeline, testimonials from local supporters, and donation call-to-action. The content should emphasize collaboration, local impact, and community participation.',
			'better-payment'
		),
	},
	{
		id: 'crowdfunding',
		title: __( 'Crowdfunding Campaign', 'better-payment' ),
		description: __(
			'Story-led project with reward tiers',
			'better-payment'
		),
		icon: 'megaphone',
		prompt: __(
			'Create a crowdfunding campaign for Art Exhibition featuring a compelling story, campaign objective, funding goal, milestone tracker, project timeline, reward tiers (if applicable), updates section, supporter testimonials, FAQs, and clear calls-to-action encouraging people to contribute and share the campaign with others.',
			'better-payment'
		),
	},
	{
		id: 'mission-driven',
		title: __( 'Mission-Driven Campaign', 'better-payment' ),
		description: __(
			'Long-term vision and program impact',
			'better-payment'
		),
		icon: 'flag',
		prompt: __(
			"Build a mission-driven fundraising page for Animal Rescue Organization that communicates the organization's purpose, long-term vision, ongoing programs, measurable impact, donor success stories, financial transparency, frequently asked questions, recurring donation options, and volunteer opportunities. Write persuasive content that builds credibility and encourages long-term supporter engagement.",
			'better-payment'
		),
	},
	{
		id: 'healthcare',
		title: __( 'Healthcare Fundraising Campaign', 'better-payment' ),
		description: __(
			'Patient story with treatment goals',
			'better-payment'
		),
		icon: 'plus-alt',
		prompt: __(
			'Generate a healthcare fundraising campaign for Cancer Care Foundation with an emotional hero section, patient or beneficiary story, treatment goals, fundraising progress, healthcare impact, doctor or caregiver testimonials, FAQs, and multiple donation opportunities. Use compassionate language that inspires empathy while maintaining professionalism.',
			'better-payment'
		),
	},
	{
		id: 'education',
		title: __( 'Education Campaign', 'better-payment' ),
		description: __(
			'Scholarships and student success stories',
			'better-payment'
		),
		icon: 'welcome-learn-more',
		prompt: __(
			"Create an education fundraising campaign supporting Children's Education. Include a motivating headline, campaign mission, student success stories, scholarship or program details, fundraising milestones, educational impact statistics, testimonials, FAQs, and donation calls-to-action. Emphasize how every contribution helps create better opportunities for learners.",
			'better-payment'
		),
	},
	{
		id: 'environmental',
		title: __( 'Environmental Campaign', 'better-payment' ),
		description: __(
			'Sustainability goals with measurable impact',
			'better-payment'
		),
		icon: 'palmtree',
		prompt: __(
			'Build an environmental fundraising campaign for Climate Action that highlights the environmental challenge, campaign objectives, measurable sustainability goals, project milestones, volunteer opportunities, environmental impact metrics, success stories, FAQs, and donation options. Use optimistic, action-oriented messaging that encourages supporters to make a lasting impact.',
			'better-payment'
		),
	},
	{
		id: 'animal-welfare',
		title: __( 'Animal Welfare Campaign', 'better-payment' ),
		description: __(
			'Rescue stories and adoption highlights',
			'better-payment'
		),
		icon: 'pets',
		prompt: __(
			'Generate an animal rescue fundraising campaign for Animal Rescue Organization featuring rescue stories, adoption success highlights, campaign goals, donation progress, volunteer opportunities, shelter statistics, FAQs, and strong calls-to-action. Write warm, compassionate copy that connects emotionally with animal lovers.',
			'better-payment'
		),
	},
	{
		id: 'membership-club',
		title: __( 'Membership or Club Campaign', 'better-payment' ),
		description: __(
			'Member benefits and exclusive experiences',
			'better-payment'
		),
		icon: 'awards',
		prompt: __(
			'Create a premium membership campaign for Freelancers Association showcasing membership benefits, exclusive experiences, upcoming events, facilities, member testimonials, pricing or contribution options, FAQs, and compelling calls-to-action encouraging users to join the community. Use aspirational and professional messaging that highlights exclusivity and long-term value.',
			'better-payment'
		),
	},
];

/**
 * Look up a template by id. Returns the first template as a safe fallback so the
 * picker always has something selected (there is no "nothing chosen" state).
 *
 * @param {string} id Template id.
 * @return {Object} The matching template, or the first one.
 */
export function findTemplate( id ) {
	return (
		PROMPT_TEMPLATES.find( ( t ) => t.id === id ) || PROMPT_TEMPLATES[ 0 ]
	);
}
