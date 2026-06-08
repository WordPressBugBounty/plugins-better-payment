import { __ } from '@wordpress/i18n';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import ElementCard from './ElementCard';

/**
 * A single droppable column in the campaign canvas.
 *
 * Accepts elements dragged from the FieldsPanel (via useDroppable) and
 * supports sortable reordering of its own elements (via SortableContext).
 */
export default function Column( { column, meta, campaignId, selectedElementId, dispatch, isActiveDropTarget, dropTargetElementId } ) {
    const { setNodeRef, isOver } = useDroppable( {
        id: `col-${ column.id }`,
        data: { columnId: column.id },
    } );

    const elementIds = column.elements.map( ( el ) => el.id );
    const isEmpty = column.elements.length === 0;
    const isHighlighted = isOver || isActiveDropTarget;

    return (
        <div
            className={ [
                'bp-canvas-column',
                isEmpty ? 'bp-canvas-column--empty' : '',
                isHighlighted ? 'bp-canvas-column--over' : '',
            ].filter( Boolean ).join( ' ' ) }
            style={ { flex: `${ parseFloat( column.width ) || 1 } 1 0`, minWidth: 0 } }
        >
            <div className="bp-canvas-column__label">
                { column.label }
            </div>

            <SortableContext items={ elementIds } strategy={ verticalListSortingStrategy }>
                <div ref={ setNodeRef } className="bp-canvas-column__drop-area">
                    { isEmpty ? (
                        <div className="bp-canvas-column__placeholder">
                            <span className="dashicons dashicons-plus-alt2"></span>
                            <p>{ __( 'Drop elements here', 'better-payment' ) }</p>
                        </div>
                    ) : (
                        column.elements.map( ( element, index ) => (
                            <ElementCard
                                key={ element.id }
                                element={ element }
                                columnId={ column.id }
                                index={ index }
                                totalElements={ column.elements.length }
                                meta={ meta }
                                campaignId={ campaignId }
                                isSelected={ element.id === selectedElementId }
                                dispatch={ dispatch }
                                isDropTarget={ element.id === dropTargetElementId }
                            />
                        ) )
                    ) }
                </div>
            </SortableContext>
        </div>
    );
}
