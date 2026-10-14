/**
 * QuickSetupWizard Component
 *
 * Main container component that orchestrates the multi-step wizard flow.
 * Manages state, navigation, and data submission.
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WIZARD_STEPS, buildWizardSteps } from '../utils/constants';
import ProgressBar from './ProgressBar';
import Navigation from './Navigation';
import StartContent from './steps/StartContent';
import SettingsContent from './steps/SettingsContent';
import PaymentContent from './steps/PaymentContent';
import FeaturesContent from './steps/FeaturesContent';
import FinishContent from './steps/FinishContent';
import FinishPreLoader from './steps/FinishPreLoader';

const QuickSetupWizard = () => {
	const betterPaymentObjWizard = window?.betterPaymentObjWizard || {};

	// Total number of wizard steps (0-3: Start, Settings, Payment, Features)
	// FinishContent is NOT a step - it's a success screen displayed after AJAX completes
	const TOTAL_STEPS = WIZARD_STEPS.length;

	// Determine if user has already opted in to tracking
	const hasOptedIn = betterPaymentObjWizard?.is_tracking === '1';

	// Initialize currentStep based on tracking opt-in status
	// If user has already opted in (is_tracking === 1), start at Settings step (1)
	// Otherwise, start at Start step (0)
	const [ currentStep, setCurrentStep ] = useState( hasOptedIn ? 1 : 0 );

	// The progress indicator is DERIVED from currentStep, never stored beside
	// it. The two used to be separate state updated by hand in handleNext /
	// handleBack, and any path that moved one without the other left the bar
	// showing the wrong step (or no active step at all).
	const stepsData = useMemo(
		() => buildWizardSteps( currentStep ),
		[ currentStep ]
	);

	const [ errors, setErrors ] = useState( {} );
	const [ settingsData, setSettingsData ] = useState(
		window?.betterPaymentObjWizard?.settings || {}
	);
	const [ currencies, setCurrencies ] = useState(
		betterPaymentObjWizard?.currencies || {}
	);

	// Track if user has opted in to tracking (prevents back navigation to Start step)
	const [ isTrackingOptedIn, setIsTrackingOptedIn ] = useState( hasOptedIn );

	// Track if wizard is finished (for displaying FinishContent success screen)
	const [ isFinished, setIsFinished ] = useState( false );
	const [ finishLoading, setFinishLoading ] = useState( false );

	useEffect( () => {
		document.title = __(
			'Quick Setup Wizard - Better Payment',
			'better-payment'
		);
	}, [] );

	const handleNext = () => {
		// Move to next step if not at the last step (step 4 is the last step)
		if ( currentStep < TOTAL_STEPS - 1 ) {
			setCurrentStep( currentStep + 1 );
		}
	};

	const handleBack = () => {
		// Prevent going back to Start step (step 0) if user has opted in to tracking
		if ( currentStep === 1 && isTrackingOptedIn ) {
			return;
		}

		if ( currentStep > 0 ) {
			setCurrentStep( currentStep - 1 );
		}
	};

	const handleFinish = async () => {
		try {
			setFinishLoading( true );

			const formData = Object.keys( settingsData ).map( ( key ) => ( {
				name: key,
				value: settingsData[ key ],
			} ) );

			const response = await fetch( betterPaymentObjWizard.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'save_setup_wizard_data',
					nonce: betterPaymentObjWizard.admin_nonce,
					form_data: JSON.stringify( formData ),
				} ),
			} );

			const result = await response.json();

			if ( result.success ) {
				setIsFinished( true );
				setFinishLoading( false );

				// Redirect after 2 seconds (for demo purposes)
				setTimeout( () => {
					if ( result.data?.redirect_url ) {
						window.location.href = result.data.redirect_url;
					}
				}, 2000 );
			} else {
				setFinishLoading( false );
				setErrors( {
					submit: result.data || 'Failed to save settings',
				} );
			}
		} catch ( error ) {
			setFinishLoading( false );
			setErrors( { submit: error.message || 'An error occurred' } );
		}
	};

	const renderContent = () => {
		switch ( currentStep ) {
			case 0:
				return (
					<StartContent
						onNext={ handleNext }
						onTrackingOptIn={ setIsTrackingOptedIn }
					/>
				);
			case 1:
				return (
					<SettingsContent
						data={ settingsData }
						currencies={ currencies }
						onDataChange={ ( data ) => setSettingsData( data ) }
						onNext={ handleNext }
					/>
				);
			case 2:
				return (
					<PaymentContent
						data={ settingsData }
						onDataChange={ ( data ) => setSettingsData( data ) }
						onNext={ handleNext }
					/>
				);
			case 3:
				return (
					<FeaturesContent
						data={ settingsData }
						onDataChange={ ( data ) => setSettingsData( data ) }
						onNext={ handleNext }
						isPro={ betterPaymentObjWizard?.is_pro }
					/>
				);
			default:
				return null;
		}
	};

	return (
		<div className="bp-wizard bp-wizard-reset">
			<ProgressBar steps={ stepsData } />
			{ renderContent() }
			{ currentStep > 0 && currentStep < TOTAL_STEPS && (
				<Navigation
					onBack={ handleBack }
					onNext={ handleNext }
					onFinish={ handleFinish }
					currentStep={ currentStep }
					isTrackingOptedIn={ isTrackingOptedIn }
					isPro={ betterPaymentObjWizard?.is_pro }
				/>
			) }
			{ errors?.submit && (
				<div className="bp-wizard__error">
					<p>{ errors?.submit }</p>
				</div>
			) }
			{ finishLoading && <FinishPreLoader /> }
			{ isFinished && <FinishContent /> }
		</div>
	);
};

export default QuickSetupWizard;
