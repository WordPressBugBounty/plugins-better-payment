import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Modal for editing the campaign name inline.
 * Opens when user clicks the name in the top nav bar.
 */
export default function CampaignNameModal( { currentName, onSave, onClose } ) {
    const [ name, setName ] = useState( currentName || '' );
    const inputRef = useRef( null );

    useEffect( () => {
        inputRef.current?.focus();
        inputRef.current?.select();
    }, [] );

    const handleSubmit = ( e ) => {
        e.preventDefault();
        const trimmed = name.trim();
        if ( trimmed ) {
            onSave( trimmed );
        }
        onClose();
    };

    const handleKeyDown = ( e ) => {
        if ( e.key === 'Escape' ) onClose();
    };

    return (
        <div className="bp-name-modal-overlay" onClick={ onClose }>
            <div className="bp-name-modal" onClick={ ( e ) => e.stopPropagation() }>
                <h3 className="bp-name-modal__title">{ __( 'Edit Campaign Name', 'better-payment' ) }</h3>
                <form onSubmit={ handleSubmit }>
                    <input
                        ref={ inputRef }
                        className="bp-name-modal__input"
                        type="text"
                        value={ name }
                        onChange={ ( e ) => setName( e.target.value ) }
                        onKeyDown={ handleKeyDown }
                        placeholder={ __( 'Campaign name…', 'better-payment' ) }
                        maxLength={ 200 }
                    />
                    <div className="bp-name-modal__actions">
                        <button type="button" className="bp-cb-btn bp-cb-btn--ghost-dark" onClick={ onClose }>
                            { __( 'Cancel', 'better-payment' ) }
                        </button>
                        <button type="submit" className="bp-cb-btn bp-cb-btn--blue" disabled={ ! name.trim() }>
                            { __( 'Update', 'better-payment' ) }
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
