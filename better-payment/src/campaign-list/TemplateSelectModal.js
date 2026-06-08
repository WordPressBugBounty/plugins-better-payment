import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const TEMPLATE_ICONS = {
    'blank-1col': (
        <svg width="64" height="48" viewBox="0 0 64 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="40" rx="3" fill="#f0f0f1" stroke="#ddd" strokeWidth="1.5"/>
            <rect x="10" y="10" width="44" height="6" rx="2" fill="#6b63f6" opacity="0.6"/>
            <rect x="10" y="20" width="44" height="4" rx="2" fill="#ddd"/>
            <rect x="10" y="28" width="34" height="4" rx="2" fill="#ddd"/>
            <rect x="10" y="36" width="20" height="4" rx="2" fill="#ddd"/>
        </svg>
    ),
    'blank-2col': (
        <svg width="64" height="48" viewBox="0 0 64 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="40" rx="3" fill="#f0f0f1" stroke="#ddd" strokeWidth="1.5"/>
            <rect x="8" y="10" width="30" height="6" rx="2" fill="#6b63f6" opacity="0.6"/>
            <rect x="8" y="20" width="30" height="4" rx="2" fill="#ddd"/>
            <rect x="8" y="28" width="24" height="4" rx="2" fill="#ddd"/>
            <rect x="8" y="36" width="16" height="4" rx="2" fill="#ddd"/>
            <rect x="42" y="10" width="14" height="28" rx="2" fill="#6b63f6" opacity="0.15" stroke="#6b63f6" strokeWidth="1"/>
        </svg>
    ),
    'blank-3col': (
        <svg width="64" height="48" viewBox="0 0 64 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="40" rx="3" fill="#f0f0f1" stroke="#ddd" strokeWidth="1.5"/>
            <rect x="7" y="10" width="15" height="28" rx="2" fill="#6b63f6" opacity="0.15" stroke="#6b63f6" strokeWidth="1"/>
            <rect x="25" y="10" width="15" height="28" rx="2" fill="#6b63f6" opacity="0.15" stroke="#6b63f6" strokeWidth="1"/>
            <rect x="43" y="10" width="15" height="28" rx="2" fill="#6b63f6" opacity="0.15" stroke="#6b63f6" strokeWidth="1"/>
        </svg>
    ),
    'charity-basic': (
        <svg width="64" height="48" viewBox="0 0 64 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="4" width="56" height="40" rx="3" fill="#f0f0f1" stroke="#ddd" strokeWidth="1.5"/>
            <rect x="8" y="8" width="30" height="16" rx="2" fill="#6b63f6" opacity="0.3"/>
            <rect x="8" y="28" width="30" height="4" rx="2" fill="#6b63f6" opacity="0.6"/>
            <rect x="8" y="36" width="22" height="4" rx="2" fill="#ddd"/>
            <rect x="42" y="8" width="14" height="8" rx="2" fill="#ddd"/>
            <rect x="42" y="20" width="14" height="4" rx="2" fill="#ddd"/>
            <rect x="42" y="28" width="14" height="4" rx="2" fill="#ddd"/>
            <rect x="42" y="36" width="14" height="4" rx="2" fill="#6b63f6" opacity="0.7"/>
        </svg>
    ),
};

export default function TemplateSelectModal( { onClose } ) {
    const data      = window.betterPaymentCampaignData || {};
    const adminData = window.betterPaymentAdmin || {};
    const templates = data.templates || adminData.campaignTemplates || [];
    const restUrl   = data.restUrl   || adminData.apiUrl  || '';
    const nonce     = data.nonce     || adminData.nonce   || '';
    const adminUrl  = data.adminUrl  || adminData.adminBaseUrl || '';
    const [ creating, setCreating ] = useState( false );
    const [ error, setError ]       = useState( null );

    const handleSelect = async ( template ) => {
        setCreating( true );
        setError( null );

        try {
            const res = await fetch( `${ restUrl }campaigns`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify( {
                    title: template.default_title || template.label || __( 'New Campaign', 'better-payment' ),
                    post_status: 'draft',
                    bpc_fields_layout: JSON.stringify( {
                        layout:  template.layout,
                        columns: template.columns,
                    } ),
                } ),
            } );

            if ( ! res.ok ) {
                throw new Error( __( 'Could not create campaign. Please try again.', 'better-payment' ) );
            }

            const campaign = await res.json();
            window.location.href = `${ adminUrl }admin.php?page=bp-campaign-builder&campaign_id=${ campaign.id }`;
        } catch ( err ) {
            setError( err.message );
            setCreating( false );
        }
    };

    const handleOverlayClick = ( e ) => {
        if ( e.target === e.currentTarget ) {
            onClose();
        }
    };

    return (
        <div className="bp-template-modal-overlay" onClick={ handleOverlayClick }>
            <div className="bp-template-modal">
                <div className="bp-template-modal__header">
                    <h2>{ __( 'Choose a Starting Layout', 'better-payment' ) }</h2>
                    <p>{ __( 'Select a template to begin building your fundraising campaign.', 'better-payment' ) }</p>
                    <button
                        className="bp-template-modal__close"
                        onClick={ onClose }
                        aria-label={ __( 'Close', 'better-payment' ) }
                    >
                        ✕
                    </button>
                </div>

                { error && (
                    <div className="bp-template-modal__error">{ error }</div>
                ) }

                { creating ? (
                    <div className="bp-template-modal__loading">
                        <span className="spinner is-active" style={ { float: 'none', margin: '0 8px 0 0' } } />
                        { __( 'Creating campaign…', 'better-payment' ) }
                    </div>
                ) : (
                    <div className="bp-template-modal__grid">
                        { templates.map( ( tpl ) => (
                            <button
                                key={ tpl.key }
                                className="bp-template-card"
                                onClick={ () => handleSelect( tpl ) }
                            >
                                <div className="bp-template-card__thumb">
                                    { TEMPLATE_ICONS[ tpl.key ] || (
                                        <span className="dashicons dashicons-layout" />
                                    ) }
                                </div>
                                <div className="bp-template-card__info">
                                    <strong>{ tpl.label }</strong>
                                    <span>{ tpl.description }</span>
                                </div>
                            </button>
                        ) ) }
                    </div>
                ) }
            </div>
        </div>
    );
}
