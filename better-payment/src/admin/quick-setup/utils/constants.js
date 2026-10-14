/**
 * Quick Setup Wizard Constants
 *
 * Centralized constants for the wizard configuration
 */

import CompleteIcon from '../svg/CompleteIcon';
import Start from '../svg/Start';
import SettingsActive from '../svg/SettingsActive';
import Settings from '../svg/Settings';
import Payment from '../svg/Payment';
import PaymentActive from '../svg/PaymentActive';
import Features from '../svg/Features';
import FeaturesActive from '../svg/FeaturesActive';

/**
 * The three states a progress step can be in.
 *
 * They are mutually exclusive by construction — see `buildWizardSteps()`. The
 * old shape carried two independent booleans (`active` + `completed`) that the
 * wizard maintained by hand on every Next/Back, which allowed the impossible
 * `active && completed` pair. `ProgressBar` rendered no icon at all for that
 * pair, so a desync showed up as a step with a label and an empty icon slot.
 */
export const STEP_STATUS = {
	UPCOMING: 'upcoming',
	ACTIVE: 'active',
	COMPLETED: 'completed',
};

/**
 * Static step definitions. Deliberately carries NO state — the status of each
 * step is derived from `currentStep` by `buildWizardSteps()`.
 */
export const WIZARD_STEPS = [
	{
		id: 'start',
		label: 'Start',
		icon: <Start />,
		activeIcon: <Start />,
		completedIcon: <CompleteIcon />,
	},
	{
		id: 'settings',
		label: 'Settings',
		icon: <Settings />,
		activeIcon: <SettingsActive />,
		completedIcon: <CompleteIcon />,
	},
	{
		id: 'payment',
		label: 'Payment',
		icon: <Payment />,
		activeIcon: <PaymentActive />,
		completedIcon: <CompleteIcon />,
	},
	{
		id: 'features',
		label: 'Features',
		icon: <Features />,
		activeIcon: <FeaturesActive />,
		completedIcon: <CompleteIcon />,
	},
];

/**
 * Derive every step's status from the step the user is on.
 *
 * `currentStep` is the single source of truth. Keeping a parallel `stepsData`
 * state in sync by hand is what made the indicator drift: `handleNext` on the
 * last step marked it completed while the guard below it refused to advance,
 * leaving the bar with NO active step and the last screen still on show.
 * Deriving makes that unrepresentable.
 *
 * The index is clamped, so an out-of-range `currentStep` still yields exactly
 * one active step rather than an indicator with nothing lit.
 *
 * @param {number} currentStep Zero-based index of the step being shown.
 * @param {Array}  steps       Step definitions; defaults to `WIZARD_STEPS`.
 * @return {Array} Steps with a `status` of upcoming / active / completed.
 */
export const buildWizardSteps = ( currentStep, steps = WIZARD_STEPS ) => {
	const index = Math.min(
		Math.max( Number.isInteger( currentStep ) ? currentStep : 0, 0 ),
		steps.length - 1
	);

	return steps.map( ( step, i ) => {
		let status = STEP_STATUS.UPCOMING;

		if ( i < index ) {
			status = STEP_STATUS.COMPLETED;
		} else if ( i === index ) {
			status = STEP_STATUS.ACTIVE;
		}

		return { ...step, status };
	} );
};

export const PAYMENT_GATEWAYS = {
	PAYPAL: 'paypal',
	STRIPE: 'stripe',
	PAYSTACK: 'paystack',
};

export const FEATURE_TOGGLES = [
	{ key: 'dashboard', label: 'Subscriber Dashboard' },
	{ key: 'fundraising', label: 'Fundraising Campaign' },
	{ key: 'emailNotifications', label: 'Email Notifications' },
];

export const CURRENCIES = [
	{ id: 'USD', label: 'USD' },
	{ id: 'EUR', label: 'EUR' },
	{ id: 'GBP', label: 'GBP' },
	{ id: 'BDT', label: 'BDT' },
	{ id: 'INR', label: 'INR' },
	{ id: 'AUD', label: 'AUD' },
	{ id: 'CAD', label: 'CAD' },
	{ id: 'JPY', label: 'JPY' },
];

export const PAYMENT_GATEWAY_FIELDS = {
	paypal: [
		{
			name: 'email',
			label: 'Business Email',
			type: 'email',
			required: true,
		},
	],
	stripe: [
		{ name: 'clientId', label: 'Public Key', type: 'text', required: true },
		{
			name: 'secret',
			label: 'Secret Key',
			type: 'password',
			required: true,
		},
	],
	paystack: [
		{ name: 'clientId', label: 'Public Key', type: 'text', required: true },
		{
			name: 'secret',
			label: 'Secret Key',
			type: 'password',
			required: true,
		},
	],
};
