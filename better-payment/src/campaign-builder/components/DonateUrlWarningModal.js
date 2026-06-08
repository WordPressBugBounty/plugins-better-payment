import { __ } from '@wordpress/i18n';

/**
 * Non-blocking warning shown when the user publishes a campaign that has a
 * donation_form element with no URL configured and no payment page set in the
 * Advanced tab (bpc_form_page_id === 0).
 *
 * The user can dismiss and fix the URL, or proceed to publish anyway.
 */
export default function DonateUrlWarningModal( { onProceed, onCancel } ) {
    return (
        <div className="bp-name-modal-overlay" onClick={ onCancel }>
            <div
                className="bp-title-required-modal"
                onClick={ ( e ) => e.stopPropagation() }
                style={ { maxWidth: '400px' } }
            >
                <div className="bp-title-required-modal__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="12" fill="#ecc30b" />
                        <text
                            x="12" y="17"
                            textAnchor="middle"
                            fill="#1a1a2e"
                            fontSize="14"
                            fontWeight="bold"
                            fontFamily="serif"
                        >!</text>
                    </svg>
                </div>

                <h3 className="bp-title-required-modal__heading">
                    { __( 'Donate button has no URL', 'better-payment' ) }
                </h3>

                <p className="bp-title-required-modal__body">
                    { __(
                        'Your Donate button isn\’t set up yet. Add a payment page URL in the Donate Button settings so visitors can make donations.','better-payment'
                    ) }
                </p>

                <div style={ { display: 'flex', gap: '10px', justifyContent: 'center', marginTop: '4px' } }>
                    <button
                        className="bp-title-required-modal__ok"
                        style={ { background: '#6b63f6', minWidth: '120px', whiteSpace: 'nowrap' } }
                        onClick={ onCancel }
                    >
                        { __( 'Fix it', 'better-payment' ) }
                    </button>
                    <button
                        className="bp-title-required-modal__ok"
                        style={ { background: '#8a8fa6', minWidth: '140px', whiteSpace: 'nowrap' } }
                        onClick={ onProceed }
                    >
                        { __( 'Publish anyway', 'better-payment' ) }
                    </button>
                </div>
            </div>
        </div>
    );
}
