/**
 * Navigation Component
 *
 * Displays Back and Next buttons for wizard navigation
 */

const Navigation = ({ onBack, onNext, onFinish, currentStep, isTrackingOptedIn, isPro }) => {
    // Determine which button handler to use
    const handlePrimaryButtonClick = () => {
        if (currentStep === 3) {
            // On Features step (step 3): Call handleFinish to submit wizard data
            onFinish();
        } else {
            // On other steps: Call handleNext to advance to next step
            onNext();
        }
    };

    return (
        <div className="bp-wizard__navigation">
            {/* Hide back button if user opted in and is on Settings step (step 1) */}
            {!(isTrackingOptedIn && currentStep === 1) && (
                <button
                    className="bp-btn bp-btn--secondary"
                    onClick={onBack}
                    type="button"
                >
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.625 14.25L6.375 9L11.625 3.75" stroke="#6B59EE" strokeWidth="1.5" strokeLinecap="square"/>
                    </svg>
                    Back
                </button>
            )}
            <button
                className="bp-btn bp-btn--primary"
                onClick={handlePrimaryButtonClick}
                type="button"
            >
                {currentStep === 3  ? ( isPro === '1' ? 'Finish' : 'Finish Without PRO') : 'Next'}
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.375 3.75L11.625 9L6.375 14.25" stroke="#F7F8FA" strokeWidth="1.5" strokeLinecap="square"/>
                </svg>
            </button>
        </div>
    );
};

export default Navigation;
