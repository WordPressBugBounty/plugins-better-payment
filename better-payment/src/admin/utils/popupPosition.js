import { useLayoutEffect } from '@wordpress/element';

/**
 * Where a popup anchored to a trigger should sit horizontally, so it stays
 * inside the content area.
 *
 * The Date Range popup is about 600px wide and was anchored `right: 0` to its
 * trigger, so at a 1280px window it grew leftwards under #adminmenuwrap and its
 * preset ranges could not be clicked. Right-aligned is still preferred; when
 * that crosses `minLeft` the popup aligns to the trigger's left edge instead,
 * and when that overflows `maxRight` it is clamped between the two.
 *
 * @param {Object} args
 * @param {number} args.triggerLeft  Trigger's viewport left.
 * @param {number} args.triggerRight Trigger's viewport right.
 * @param {number} args.popupWidth   Popup's width.
 * @param {number} args.minLeft      Leftmost allowed viewport x (the admin menu's right edge).
 * @param {number} args.maxRight     Rightmost allowed viewport x (the viewport width).
 * @param {number} [args.margin=8]   Space kept from either bound.
 * @return {number} The popup's left, relative to the trigger's left edge.
 */
export const horizontalPopupOffset = ( {
	triggerLeft,
	triggerRight,
	popupWidth,
	minLeft,
	maxRight,
	margin = 8,
} ) => {
	const low = minLeft + margin;
	const high = maxRight - margin;

	let left = triggerRight - popupWidth; // right-aligned
	if ( left < low ) {
		left = triggerLeft; // left-aligned
		if ( left + popupWidth > high ) {
			left = Math.max( low, high - popupWidth );
		}
	}

	return left - triggerLeft;
};

/**
 * The viewport bounds the admin's content area occupies: right of the wp-admin
 * menu (`#wpcontent` starts there; 0 when the menu is folded away on mobile)
 * and left of the viewport's right edge.
 *
 * @return {{minLeft: number, maxRight: number}} Bounds.
 */
export const contentBounds = () => {
	const content = document.getElementById( 'wpcontent' );
	const menu = document.getElementById( 'adminmenuwrap' );
	const menuRight =
		menu && menu.offsetParent !== null
			? menu.getBoundingClientRect().right
			: 0;
	return {
		minLeft: Math.max(
			0,
			menuRight,
			content ? content.getBoundingClientRect().left : 0
		),
		maxRight: document.documentElement.clientWidth || window.innerWidth,
	};
};

/**
 * Viewport `top` for a fixed row-action menu: below its button when it fits,
 * above it when there is no room below (a menu on the last rows of the window
 * used to open off-screen, and it closes on scroll, so it was unreachable).
 *
 * @param {Object} args
 * @param {number} args.anchorTop      Button's viewport top.
 * @param {number} args.anchorBottom   Button's viewport bottom.
 * @param {number} args.menuHeight     Menu's height.
 * @param {number} args.viewportHeight Window height.
 * @param {number} [args.gap=4]        Space between button and menu.
 * @param {number} [args.margin=8]     Space kept from the window edge.
 * @return {number} Viewport top.
 */
export const rowMenuTop = ( {
	anchorTop,
	anchorBottom,
	menuHeight,
	viewportHeight,
	gap = 4,
	margin = 8,
} ) => {
	const below = anchorBottom + gap;
	if ( below + menuHeight <= viewportHeight - margin ) {
		return below;
	}
	const above = anchorTop - gap - menuHeight;
	if ( above >= margin ) {
		return above;
	}
	// Fits neither way: the side with more room, pinned inside the window.
	return Math.max(
		margin,
		Math.min( below, viewportHeight - margin - menuHeight )
	);
};

/**
 * The position a row-action menu opens at, before it is measured.
 *
 * @param {DOMRect} rect The button's viewport rect.
 * @return {Object} `{ top, right, anchorTop, anchorBottom }`.
 */
export const rowMenuAnchor = ( rect ) => ( {
	top: rect.bottom + 4,
	right: window.innerWidth - rect.right,
	anchorTop: rect.top,
	anchorBottom: rect.bottom,
} );

/**
 * Once a row-action menu is open, measure it and flip it above its button when
 * it would run off the bottom of the window.
 *
 * @param {*}        openId      The open menu's id (null when none).
 * @param {Object}   position    State from rowMenuAnchor().
 * @param {Function} setPosition State setter.
 */
export const useRowMenuFlip = ( openId, position, setPosition ) => {
	useLayoutEffect( () => {
		if ( openId === null || openId === undefined ) {
			return;
		}
		const menu = document.querySelector(
			'.bp-data_setting-wrapper.settiong-wrapper-active'
		);
		if ( ! menu || position?.anchorBottom === undefined ) {
			return;
		}
		const top = rowMenuTop( {
			anchorTop: position.anchorTop,
			anchorBottom: position.anchorBottom,
			menuHeight: menu.offsetHeight,
			viewportHeight: window.innerHeight,
		} );
		if ( top !== position.top ) {
			setPosition( { ...position, top } );
		}
	}, [ openId, position ] );
};
