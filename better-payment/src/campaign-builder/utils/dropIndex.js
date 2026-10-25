/**
 * Where a drag-and-drop lands inside the target column.
 *
 * dnd-kit reports the thing under the pointer as `over`. On the live canvas that
 * is either an element hotspot (`useSortable`, id = the element id, data
 * `{ source: 'canvas', columnId, element }`) or a column drop zone
 * (`useDroppable`, id `col-{id}`, data `{ columnId }`). Dropping on an element
 * means "put it here", so the new position is that element's index; dropping on
 * the column itself (its empty area) means "at the end".
 *
 * Shared by palette drops (ADD_ELEMENT) and cross-column moves (MOVE_ELEMENT) —
 * the move used to ignore the hovered element and always append, so an element
 * dragged onto the top of another column landed at its bottom.
 *
 * @param {Array}  columns  Layout columns.
 * @param {string} columnId Target column id.
 * @param {Object} over     dnd-kit `over` ({ id, data: { current } }).
 * @return {number} Index to insert at (the column's length when appending).
 */
export function dropInsertIndex( columns, columnId, over ) {
	const col = ( columns ?? [] ).find( ( c ) => c.id === columnId );
	const length = col?.elements?.length ?? 0;
	if ( ! col || over?.data?.current?.source !== 'canvas' ) return length;
	const idx = col.elements.findIndex( ( el ) => el.id === over.id );
	return idx === -1 ? length : idx;
}
