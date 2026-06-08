import { __ } from '@wordpress/i18n';
import { getField } from '../fields/FieldRegistry';

/**
 * A single field card in the canvas.
 * Shows field label + drag handle + delete button.
 * Clicking selects the field (shows its settings in the right panel).
 */
export default function FieldItem( { field, index, isSelected, dispatch, onDropAbove } ) {
    const def = getField( field.type );

    const handleDragStart = ( e ) => {
        e.dataTransfer.setData( 'bp_field_id', field.id );
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = ( e ) => {
        e.preventDefault();
        e.stopPropagation();
    };

    return (
        <div
            className={ `bp-cb-field-item-canvas${ isSelected ? ' is-selected' : '' }` }
            onClick={ () => dispatch( { type: isSelected ? 'DESELECT_FIELD' : 'SELECT_FIELD', fieldId: field.id } ) }
        >
            {/* Drop zone above this item */ }
            <div
                className="bp-cb-drop-zone"
                onDragOver={ handleDragOver }
                onDrop={ onDropAbove }
            />

            <div
                className="bp-cb-field-inner"
                draggable
                onDragStart={ handleDragStart }
            >
                <span className="bp-cb-drag-handle dashicons dashicons-move" title={ __( 'Drag to reorder', 'better-payment' ) } />
                <span className={ `dashicons dashicons-${ def?.icon || 'block-default' }` } />
                <span className="bp-cb-field-label">{ def?.label || field.type }</span>

                <button
                    className="bp-cb-field-delete"
                    title={ __( 'Remove field', 'better-payment' ) }
                    onClick={ ( e ) => {
                        e.stopPropagation();
                        dispatch( { type: 'REMOVE_FIELD', fieldId: field.id } );
                    } }
                >
                    ✕
                </button>
            </div>
        </div>
    );
}
