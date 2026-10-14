import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	DndContext,
	PointerSensor,
	closestCenter,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	verticalListSortingStrategy,
	useSortable,
	arrayMove,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

/**
 * Generic repeater control for the Campaign Builder settings panel.
 *
 * Renders a drag-to-reorder list of collapsible items; each item is a small
 * group of sub-fields defined by the element schema. The value is an array of
 * plain objects keyed by the sub-field keys (e.g. [ { question, answer }, ... ]).
 * A `_id` is kept on each item for stable React keys / drag ids and collapse
 * tracking — the PHP renderer reads only the declared field keys and ignores it.
 *
 * Schema shape (registered by add-ons via `better_payment/campaign_elements`):
 *   {
 *     key: 'items',
 *     type: 'repeater',
 *     label: 'FAQ Items',
 *     fields: [
 *       { key: 'question', type: 'text',     label: 'Question', placeholder: '...' },
 *       { key: 'answer',   type: 'textarea', label: 'Answer',   rows: 3 },
 *     ],
 *     itemLabel: 'Item',        // used in the collapsed row title fallback
 *     titleField: 'question',   // which sub-field to show as the row title
 *     addLabel: 'Add Item',
 *     max: 20,                  // optional cap
 *   }
 */

function DragHandle( { listeners, attributes } ) {
	return (
		<span
			className="bp-rp-drag"
			{ ...listeners }
			{ ...attributes }
			title={ __( 'Drag to reorder', 'better-payment' ) }
		>
			⠿
		</span>
	);
}

function SubField( { field, value, onChange } ) {
	// A switch/toggle sub-field carries a boolean, not text — render it as the
	// same pill toggle used by top-level `switch` controls and skip the label
	// above (the toggle carries its own inline label).
	if ( field.type === 'switch' || field.type === 'toggle' ) {
		return (
			<div className="bp-rp-field bp-rp-field--switch">
				<label className="bp-cb-switch-label">
					<span className="bp-cb-switch-wrap">
						<input
							type="checkbox"
							checked={ !! value }
							onChange={ ( e ) => onChange( e.target.checked ) }
						/>
						<span className="bp-cb-switch-track" />
					</span>
					{ field.label && <span>{ field.label }</span> }
				</label>
			</div>
		);
	}

	const commonProps = {
		value: value ?? '',
		placeholder: field.placeholder || '',
		onChange: ( e ) => onChange( e.target.value ),
	};

	return (
		<div className="bp-rp-field">
			{ field.label && (
				<label className="bp-cb-field-label">{ field.label }</label>
			) }
			{ field.type === 'textarea' ? (
				<textarea rows={ field.rows || 3 } { ...commonProps } />
			) : (
				<input type="text" { ...commonProps } />
			) }
		</div>
	);
}

function SortableRepeaterRow( {
	item,
	index,
	fields,
	titleField,
	itemLabel,
	isOpen,
	onToggle,
	onUpdate,
	onDelete,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: item._id } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.4 : 1,
		zIndex: isDragging ? 1 : 'auto',
	};

	const rowTitle =
		( titleField && item[ titleField ] ) ||
		`${ itemLabel || __( 'Item', 'better-payment' ) } ${ index + 1 }`;

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ `bp-rp-row${ isOpen ? ' is-open' : '' }` }
		>
			<div className="bp-rp-row__head">
				<DragHandle listeners={ listeners } attributes={ attributes } />
				<button
					type="button"
					className="bp-rp-row__toggle"
					aria-expanded={ isOpen }
					onClick={ () => onToggle( item._id ) }
				>
					<span className="bp-rp-row__title">{ rowTitle }</span>
					<svg
						className="bp-rp-row__chevron"
						width="14"
						height="14"
						viewBox="0 0 20 20"
						fill="none"
						aria-hidden="true"
					>
						<path
							d="M6 8l4 4 4-4"
							stroke="currentColor"
							strokeWidth="1.6"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</button>
				<button
					type="button"
					className="bp-rp-delete"
					onClick={ () => onDelete( item._id ) }
					title={ __( 'Remove', 'better-payment' ) }
				>
					✕
				</button>
			</div>
			{ isOpen && (
				<div className="bp-rp-row__body">
					{ fields.map( ( field ) => (
						<SubField
							key={ field.key }
							field={ field }
							value={ item[ field.key ] }
							onChange={ ( val ) =>
								onUpdate( item._id, { [ field.key ]: val } )
							}
						/>
					) ) }
				</div>
			) }
		</div>
	);
}

/**
 * @param {Object}   control  The repeater schema control (see docblock above).
 * @param {Object[]} value    Current array of item objects.
 * @param {Function} onChange Called with the updated array.
 */
export default function RepeaterControl( { control, value, onChange } ) {
	const fields = Array.isArray( control.fields ) ? control.fields : [];
	const titleField = control.titleField || ( fields[ 0 ] && fields[ 0 ].key );
	const max = typeof control.max === 'number' ? control.max : 0;

	// Which rows are expanded, tracked by item `_id`. Default: all collapsed.
	const [ openIds, setOpenIds ] = useState( () => new Set() );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 5 } } )
	);

	// Ensure every item carries a stable `_id` for React keys / drag ids /
	// collapse tracking. The fallback id is derived from the item's position
	// (not a timestamp) so it stays stable across re-renders until an edit
	// persists it into the array — otherwise inputs would lose focus and the
	// open/closed state would reset on every keystroke.
	const safeItems = ( Array.isArray( value ) ? value : [] ).map(
		( item, index ) => {
			const it = item && typeof item === 'object' ? item : {};
			return it._id ? it : { ...it, _id: `rp_${ index }` };
		}
	);

	const toggle = ( id ) =>
		setOpenIds( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}
			return next;
		} );

	const handleDragEnd = ( { active, over } ) => {
		if ( over && active.id !== over.id ) {
			const oldIndex = safeItems.findIndex(
				( i ) => i._id === active.id
			);
			const newIndex = safeItems.findIndex( ( i ) => i._id === over.id );
			onChange( arrayMove( safeItems, oldIndex, newIndex ) );
		}
	};

	const updateItem = ( id, patch ) =>
		onChange(
			safeItems.map( ( i ) => ( i._id === id ? { ...i, ...patch } : i ) )
		);

	const deleteItem = ( id ) => {
		setOpenIds( ( prev ) => {
			const next = new Set( prev );
			next.delete( id );
			return next;
		} );
		onChange( safeItems.filter( ( i ) => i._id !== id ) );
	};

	const addItem = () => {
		if ( max > 0 && safeItems.length >= max ) {
			return;
		}
		const newId = `rp_new_${ safeItems.length }_${ Date.now() }`;
		const blank = { _id: newId };
		fields.forEach( ( f ) => {
			blank[ f.key ] =
				f.type === 'switch' || f.type === 'toggle' ? false : '';
		} );
		// Open the freshly added row so it can be filled in immediately.
		setOpenIds( ( prev ) => new Set( prev ).add( newId ) );
		onChange( [ ...safeItems, blank ] );
	};

	const atMax = max > 0 && safeItems.length >= max;

	return (
		<div className="bp-rp-editor">
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragEnd={ handleDragEnd }
			>
				<SortableContext
					items={ safeItems.map( ( i ) => i._id ) }
					strategy={ verticalListSortingStrategy }
				>
					{ safeItems.map( ( item, index ) => (
						<SortableRepeaterRow
							key={ item._id }
							item={ item }
							index={ index }
							fields={ fields }
							titleField={ titleField }
							itemLabel={ control.itemLabel }
							isOpen={ openIds.has( item._id ) }
							onToggle={ toggle }
							onUpdate={ updateItem }
							onDelete={ deleteItem }
						/>
					) ) }
				</SortableContext>
			</DndContext>

			<div className="bp-rp-footer">
				<button
					type="button"
					className="bp-rp-add"
					onClick={ addItem }
					disabled={ atMax }
				>
					+ { control.addLabel || __( 'Add Item', 'better-payment' ) }
				</button>
				{ atMax && (
					<span className="bp-rp-max-note">
						{ __( 'Maximum items reached', 'better-payment' ) }
					</span>
				) }
			</div>
		</div>
	);
}
