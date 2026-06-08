import { __ } from '@wordpress/i18n';
import { useDraggable } from '@dnd-kit/core';
import { getAllElements } from '../fields/FieldRegistry';

/**
 * Left sidebar — lists all element types available to drag into the canvas.
 * Each item is a @dnd-kit Draggable (not sortable — the list is fixed).
 */
export default function FieldsPanel() {
    const elements = getAllElements();

    return (
        <div className="bp-cb-fields-panel">
            <h3 className="bp-cb-panel-heading">{ __( 'Elements', 'better-payment' ) }</h3>
            <p className="bp-cb-panel-hint">{ __( 'Drag or click to add to a column', 'better-payment' ) }</p>
            <ul className="bp-cb-field-list">
                { elements.map( ( el ) => (
                    <DraggableElement key={ el.type } element={ el } />
                ) ) }
            </ul>
        </div>
    );
}

function DraggableElement( { element } ) {
    const { attributes, listeners, setNodeRef, isDragging } = useDraggable( {
        id: `panel-${ element.type }`,
        data: {
            source: 'panel',
            elementType: element.type,
            defaultSettings: element.defaultSettings,
        },
    } );

    return (
        <li
            ref={ setNodeRef }
            className={ `bp-cb-field-item${ isDragging ? ' is-dragging' : '' }` }
            title={ element.label }
            { ...listeners }
            { ...attributes }
        >
            <span className={ `dashicons dashicons-${ element.icon }` }></span>
            <span className="bp-cb-field-label">{ element.label }</span>
        </li>
    );
}
