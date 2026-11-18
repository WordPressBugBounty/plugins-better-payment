/**
 * The options handed to `$.fn.pointer()` for the offer pointer.
 *
 * Kept apart from the entry so it can be tested: the copy arrives as plain text and every node is
 * built with jQuery's `.text()` / `.attr()`, never by joining strings into HTML.
 */

/** Added to the pointer so its own rules can reach it. */
export const POINTER_CLASS = 'wp-pointer better-payment-offer-pointer';

/**
 * The pointer's heading and copy, as an HTML string of escaped text.
 *
 * @param {Function} $    jQuery.
 * @param {Object}   data `window.betterPaymentOfferPointer`.
 * @return {string} HTML.
 */
export const pointerContent = ( $, data ) =>
	$( '<div>' )
		.append(
			$( '<h3>' ).text( data?.title ?? '' ),
			$( '<p>' ).text( data?.text ?? '' )
		)
		.html();

/**
 * The action row: the campaign button, then Dismiss.
 *
 * WordPress wraps what `buttons` returns in `.wp-pointer-buttons` with `.wrap()`, which wraps each
 * element of a set on its own, so this returns one element holding both.
 *
 * @param {Function} $       jQuery.
 * @param {Object}   data    `window.betterPaymentOfferPointer`.
 * @param {Function} onClose Closes the pointer.
 * @return {Object} jQuery element.
 */
export const pointerButtons = ( $, data, onClose ) =>
	$( '<div class="bp-offer-pointer__actions">' ).append(
		$( '<a class="button button-primary">' )
			.attr( {
				href: data?.url ?? '#',
				target: '_blank',
				rel: 'noopener noreferrer',
			} )
			.text( data?.button ?? '' ),
		$( '<a class="close" href="#" role="button">' )
			.text( data?.dismiss ?? '' )
			.on( 'click', ( event ) => {
				event.preventDefault();
				onClose();
			} )
	);

/**
 * @param {Function} $         jQuery.
 * @param {Object}   data      `window.betterPaymentOfferPointer`.
 * @param {Function} onDismiss Records the dismissal.
 * @return {Object} Options for `$.fn.pointer()`.
 */
export const pointerOptions = ( $, data, onDismiss ) => ( {
	content: pointerContent( $, data ),
	pointerClass: POINTER_CLASS,
	position: {
		edge: data?.edge === 'right' ? 'right' : 'left',
		align: 'center',
	},
	buttons: ( event, handoff ) =>
		pointerButtons( $, data, () => handoff.element.pointer( 'close' ) ),
	// WordPress calls this whenever the pointer closes; Dismiss is the only way to close it.
	close: onDismiss,
} );

/**
 * Where the pointer sits relative to the menu link it points at, read once after WordPress has
 * placed it: the gap from the link's top-left corner to the pointer's.
 *
 * @param {{top: number, left: number}} targetRect  The link's viewport box.
 * @param {{top: number, left: number}} pointerRect The pointer's viewport box.
 * @return {{top: number, left: number}} Offset.
 */
export const pointerOffset = ( targetRect, pointerRect ) => ( {
	top: ( pointerRect?.top ?? 0 ) - ( targetRect?.top ?? 0 ),
	left: ( pointerRect?.left ?? 0 ) - ( targetRect?.left ?? 0 ),
} );

/**
 * The pointer's viewport position for where the menu link is now.
 *
 * The admin menu stays in view while the page scrolls (it is fixed, or pinned to an edge when it
 * is taller than the window), and WordPress places a pointer once, in page coordinates, so the
 * pointer used to scroll away from the item it points at. Placed in viewport coordinates from
 * the link's current box, it stays beside the link wherever the menu goes.
 *
 * @param {{top: number, left: number}} targetRect The link's viewport box.
 * @param {{top: number, left: number}} offset     From pointerOffset().
 * @return {{top: number, left: number}} Position for `position: fixed`, in whole pixels.
 */
export const pointerPosition = ( targetRect, offset ) => ( {
	top: Math.round( ( targetRect?.top ?? 0 ) + ( offset?.top ?? 0 ) ),
	left: Math.round( ( targetRect?.left ?? 0 ) + ( offset?.left ?? 0 ) ),
} );

/**
 * Whether the menu link is on screen to be pointed at. Under 783px the admin menu is off canvas
 * until it is opened, and a folded or hidden item has no box.
 *
 * @param {{width: number, height: number, right: number}} targetRect The link's viewport box.
 * @return {boolean} True when the link has a box inside the window's left edge.
 */
export const isTargetVisible = ( targetRect ) =>
	( targetRect?.width ?? 0 ) > 0 &&
	( targetRect?.height ?? 0 ) > 0 &&
	( targetRect?.right ?? 0 ) > 0;
