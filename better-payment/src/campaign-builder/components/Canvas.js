import { __ } from '@wordpress/i18n';
import Column from './Column';

/**
 * Campaign canvas — renders the page-level column layout.
 * Empty state shows a "+" icon prompting the user to drag elements in.
 * @dnd-kit DndContext is wired in App.js (parent).
 */
export default function Canvas( { layout, meta, campaignId, selectedElementId, dispatch, activeDragSource, dropTargetElementId } ) {
    const columns = layout?.columns || [];

    if ( columns.length === 0 ) {
        return (
            <div className="bp-cb-canvas bp-cb-canvas--empty">
                <div className="bp-cb-canvas-placeholder">
                    <button
                        className="bp-cb-canvas-placeholder__add"
                        title={ __( 'Choose a template to start', 'better-payment' ) }
                        onClick={ () => dispatch( { type: 'OPEN_TEMPLATE_MODAL' } ) }
                    >
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                            <circle cx="16" cy="16" r="15" stroke="#bbb" strokeWidth="1.5"/>
                            <path d="M16 10v12M10 16h12" stroke="#bbb" strokeWidth="2" strokeLinecap="round"/>
                        </svg>
                    </button>
                    <p>{ __( 'Drag elements from the right panel, or choose a template.', 'better-payment' ) }</p>
                </div>
            </div>
        );
    }

    const layoutClass = `bp-cb-canvas bp-cb-canvas--${ layout.layout || '1-column' }`;

    return (
        <div className={ layoutClass } onClick={ () => dispatch( { type: 'DESELECT_ELEMENT' } ) }>
            <div className="bp-canvas-columns-wrap">
                { columns.map( ( column ) => (
                    <Column
                        key={ column.id }
                        column={ column }
                        meta={ meta }
                        campaignId={ campaignId }
                        selectedElementId={ selectedElementId }
                        dispatch={ dispatch }
                        isActiveDropTarget={ activeDragSource === 'panel' }
                        dropTargetElementId={ dropTargetElementId }
                    />
                ) ) }
            </div>
        </div>
    );
}
