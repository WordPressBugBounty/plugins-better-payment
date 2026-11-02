/**
 * Where a floating panel goes, given its anchor (Popover.js).
 *
 * With a `side` edge (the builder sidebar's left x), the panel opens **beside**
 * the sidebar, over the canvas, top-aligned with the anchor — so it never
 * covers the controls being edited. That needs `side - gap - width >= 8`;
 * otherwise (a narrow window) it falls back to below/above the anchor.
 *
 * Below the anchor when it fits, otherwise above, otherwise whichever side has
 * more room — clamped so it never leaves the viewport. Horizontally its right
 * edge lines up with the anchor's (the builder sidebar is on the right, so the
 * panel opens toward the canvas), clamped to an 8px margin.
 *
 * @param {Object} args
 * @param {Object} args.anchor   DOMRect-like { top, bottom, left, right }.
 * @param {number} args.width    Panel width.
 * @param {number} args.height   Panel height.
 * @param {Object} args.viewport { width, height }.
 * @param {number} args.gap      Space between anchor and panel.
 * @param {number} args.side     Left x of the sidebar the anchor sits in, or null.
 * @return {{top: number, left: number, placement: string}} Position.
 */
export function popoverPosition( {
	anchor,
	width,
	height,
	viewport,
	gap = 6,
	side = null,
} ) {
	const margin = 8;
	const clampTop = ( t ) =>
		Math.max( margin, Math.min( t, viewport.height - height - margin ) );

	if ( typeof side === 'number' && side - gap - width >= margin ) {
		return {
			top: Math.round( clampTop( anchor.top ) ),
			left: Math.round( side - gap - width ),
			placement: 'side',
		};
	}

	const below = viewport.height - anchor.bottom - gap - margin;
	const above = anchor.top - gap - margin;

	let placement = 'bottom';
	if ( height > below && ( height <= above || above > below ) ) {
		placement = 'top';
	}

	let top =
		placement === 'bottom'
			? anchor.bottom + gap
			: anchor.top - gap - height;
	top = Math.max(
		margin,
		Math.min( top, viewport.height - height - margin )
	);

	let left = anchor.right - width;
	left = Math.max(
		margin,
		Math.min( left, viewport.width - width - margin )
	);

	return { top: Math.round( top ), left: Math.round( left ), placement };
}
