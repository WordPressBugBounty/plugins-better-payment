import { __ } from '@wordpress/i18n';

export default function TitleRequiredModal( { onClose } ) {
    return (
        <div className="bp-name-modal-overlay" onClick={ onClose }>
            <div className="bp-title-required-modal" onClick={ ( e ) => e.stopPropagation() }>
                <div className="bp-title-required-modal__icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="12" fill="#E05A2B"/>
                        <text x="12" y="17" textAnchor="middle" fill="white" fontSize="14" fontWeight="bold" fontFamily="serif">i</text>
                    </svg>
                </div>
                <h3 className="bp-title-required-modal__heading">
                    { __( 'Please enter a campaign name.', 'better-payment' ) }
                </h3>
                <p className="bp-title-required-modal__body">
                    { __( 'You need a name for your campaign before you save.', 'better-payment' ) }
                </p>
                <button className="bp-title-required-modal__ok" onClick={ onClose }>
                    { __( 'OK', 'better-payment' ) }
                </button>
            </div>
        </div>
    );
}
