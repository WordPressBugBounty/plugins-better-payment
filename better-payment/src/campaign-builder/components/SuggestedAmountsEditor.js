import { __ } from '@wordpress/i18n';
import { getGlobalCurrencySymbol } from '../utils/currency';
import { DndContext, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy, useSortable, arrayMove } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

function DragHandle( { listeners, attributes } ) {
    return (
        <span
            className="bp-sa-drag"
            { ...listeners }
            { ...attributes }
            title={ __( 'Drag to reorder', 'better-payment' ) }
        >
            ⠿
        </span>
    );
}

const currencySymbol = getGlobalCurrencySymbol();

function SortableAmountRow( { item, onUpdate, onDelete, onSetDefault, compact } ) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable( { id: item.id } );

    const style = {
        transform: CSS.Transform.toString( transform ),
        transition,
        opacity: isDragging ? 0.4 : 1,
        zIndex: isDragging ? 1 : 'auto',
    };

    return (
        <div ref={ setNodeRef } style={ style } className="bp-sa-row">
            <DragHandle listeners={ listeners } attributes={ attributes } />

            <div className="bp-sa-cell bp-sa-cell--radio">
                <button
                    type="button"
                    className={ 'bp-sa-radio' + ( item.is_default ? ' is-selected' : '' ) }
                    onClick={ () => onSetDefault( item.id ) }
                    title={ __( 'Set as default', 'better-payment' ) }
                />
            </div>

            <div className={ 'bp-sa-cell bp-sa-cell--amount' + ( compact ? ' bp-sa-cell--amount-fill' : '' ) }>
                { compact ? (
                    <div className="bp-sa-amount-prefix-wrap">
                        <span className="bp-sa-amount-prefix">{ currencySymbol }</span>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            value={ item.amount }
                            onChange={ ( e ) => onUpdate( item.id, { amount: e.target.value } ) }
                            placeholder="0"
                        />
                    </div>
                ) : (
                    <input
                        type="number"
                        min="0"
                        step="1"
                        value={ item.amount }
                        onChange={ ( e ) => onUpdate( item.id, { amount: e.target.value } ) }
                        placeholder="0"
                    />
                ) }
            </div>

            { ! compact && (
                <div className="bp-sa-cell bp-sa-cell--desc">
                    <input
                        type="text"
                        value={ item.description }
                        onChange={ ( e ) => onUpdate( item.id, { description: e.target.value } ) }
                        placeholder={ __( 'Optional description', 'better-payment' ) }
                    />
                </div>
            ) }

            <button
                type="button"
                className="bp-sa-delete"
                onClick={ () => onDelete( item.id ) }
                title={ __( 'Remove', 'better-payment' ) }
            >
                ✕
            </button>
        </div>
    );
}

/**
 * Drag-and-drop suggested donation amounts editor.
 *
 * @param {Object[]} items    Array of { id, amount, description, is_default }
 * @param {Function} onChange Called with the updated array.
 * @param {boolean}  compact  When true: hides description column, shows $ prefix on amount.
 */
export default function SuggestedAmountsEditor( { items, onChange, compact = false } ) {
    const sensors = useSensors(
        useSensor( PointerSensor, { activationConstraint: { distance: 5 } } )
    );

    const safeItems = Array.isArray( items ) ? items : [];

    const handleDragEnd = ( { active, over } ) => {
        if ( over && active.id !== over.id ) {
            const oldIndex = safeItems.findIndex( ( i ) => i.id === active.id );
            const newIndex = safeItems.findIndex( ( i ) => i.id === over.id );
            onChange( arrayMove( safeItems, oldIndex, newIndex ) );
        }
    };

    const updateItem = ( id, patch ) =>
        onChange( safeItems.map( ( i ) => ( i.id === id ? { ...i, ...patch } : i ) ) );

    const deleteItem = ( id ) =>
        onChange( safeItems.filter( ( i ) => i.id !== id ) );

    const setDefault = ( id ) =>
        onChange( safeItems.map( ( i ) => ( { ...i, is_default: i.id === id } ) ) );

    const addItem = () =>
        onChange( [
            ...safeItems,
            { id: 'sa_' + Date.now(), amount: '', description: '', is_default: false },
        ] );

    const clearDefaults = () =>
        onChange( safeItems.map( ( i ) => ( { ...i, is_default: false } ) ) );

    return (
        <div className={ 'bp-sa-editor' + ( compact ? ' bp-sa-editor--compact' : '' ) }>
            { safeItems.length > 0 && (
                <div className="bp-sa-header">
                    <span className="bp-sa-header__drag" />
                    <span className="bp-sa-header__cell bp-sa-header__cell--radio">
                        { __( 'Default', 'better-payment' ) }
                    </span>
                    <span className={ 'bp-sa-header__cell bp-sa-header__cell--amount' + ( compact ? ' bp-sa-header__cell--amount-fill' : '' ) }>
                        { __( 'Amount', 'better-payment' ) }
                    </span>
                    { ! compact && (
                        <span className="bp-sa-header__cell bp-sa-header__cell--desc">
                            { __( 'Description (optional)', 'better-payment' ) }
                        </span>
                    ) }
                    <span className="bp-sa-header__del" />
                </div>
            ) }

            <DndContext sensors={ sensors } collisionDetection={ closestCenter } onDragEnd={ handleDragEnd }>
                <SortableContext items={ safeItems } strategy={ verticalListSortingStrategy }>
                    { safeItems.map( ( item ) => (
                        <SortableAmountRow
                            key={ item.id }
                            item={ item }
                            onUpdate={ updateItem }
                            onDelete={ deleteItem }
                            onSetDefault={ setDefault }
                            compact={ compact }
                        />
                    ) ) }
                </SortableContext>
            </DndContext>

            <div className="bp-sa-footer">
                <button type="button" className={ 'bp-sa-add' + ( compact ? ' bp-sa-add--full' : '' ) } onClick={ addItem }>
                    + { __( 'Add A Suggested Amount', 'better-payment' ) }
                </button>
                { ! compact && safeItems.length > 0 && (
                    <button type="button" className="bp-sa-clear" onClick={ clearDefaults }>
                        { __( 'Clear Defaults', 'better-payment' ) }
                    </button>
                ) }
            </div>
        </div>
    );
}
