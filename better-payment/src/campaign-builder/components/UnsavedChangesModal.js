import { __ } from '@wordpress/i18n';

export default function UnsavedChangesModal( { onSaveAndExit, onCancel } ) {
    return (
        <div className="bp-unsaved-overlay" onClick={ onCancel }>
            <div className="bp-unsaved-modal" onClick={ ( e ) => e.stopPropagation() }>

                <div className="bp-unsaved-modal__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                        <path d="M12 9v4M12 17h.01" stroke="#1a1a2e" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </div>

                <h3 className="bp-unsaved-modal__title">
                    { __( 'You have unsaved changes', 'better-payment' ) }
                </h3>

                <p className="bp-unsaved-modal__body">
                    { __( 'Your campaign has unsaved changes. Save your changes before leaving, or cancel to continue editing.', 'better-payment' ) }
                </p>

                <div className="bp-unsaved-modal__actions">
                    <button className="bp-unsaved-modal__btn bp-unsaved-modal__btn--stay" onClick={ onSaveAndExit }>
                        { __( 'Save & Exit', 'better-payment' ) }
                    </button>
                    <button className="bp-unsaved-modal__btn bp-unsaved-modal__btn--leave" onClick={ onCancel }>
                        { __( 'Cancel', 'better-payment' ) }
                    </button>
                </div>

            </div>
        </div>
    );
}
