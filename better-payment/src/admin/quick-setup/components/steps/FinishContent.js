/**
 * FinishContent Component
 *
 * Completion screen
 */

import finishPageBg from '@quick-setup-images/finish-page-bg.png';

export default function FinishContent() {
    return (
        <>
            <div className="bp-modal-overlay">
                <div className="bp-finish-modal__content">
                    <img src={finishPageBg} alt="img" />
                    <div className="bp-finish-content">
                        <h1 className="bp-finish-modal__title">Congratulations!</h1>
                        <p className="bp-finish-modal__description">
                            You are all set to collect and manage payments using the Better Payment plugin. Best wishes.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
