/**
 * The seasonal offer pointer beside Better Payment's admin menu item (Classes\OfferPointer).
 *
 * Opens a WordPress admin pointer with the campaign's title, one line of copy, a button to the
 * campaign page and a Dismiss link. Closing it with Dismiss records the dismissal for this user;
 * following the button leaves it open.
 *
 * The pointer stays beside the menu item while the page scrolls: WordPress places a pointer once,
 * in page coordinates, but the admin menu stays in view, so the pointer is kept in viewport
 * coordinates and moved whenever the menu link moves.
 */
import {
	isTargetVisible,
	pointerOffset,
	pointerOptions,
	pointerPosition,
} from './_offer-pointer';

( ( $ ) => {
	const data = window.betterPaymentOfferPointer;

	if ( ! data || ! $?.fn?.pointer ) {
		return;
	}

	$( () => {
		const $target = $( data.target );

		// A filtered menu has no item to point at: show nothing.
		if ( ! $target.length ) {
			return;
		}

		let open = true;

		$target
			.pointer(
				pointerOptions( $, data, () => {
					open = false;
					$.post( data.ajaxUrl, {
						action: data.action,
						nonce: data.nonce,
					} );
				} )
			)
			.pointer( 'open' );

		const target = $target[ 0 ];
		const widget = $target.pointer( 'widget' )[ 0 ];

		if ( ! widget ) {
			return;
		}

		// WordPress has just placed it beside the link: remember that gap, then keep it.
		const offset = pointerOffset(
			target.getBoundingClientRect(),
			widget.getBoundingClientRect()
		);

		const place = () => {
			if ( ! open ) {
				return;
			}

			const rect = target.getBoundingClientRect();

			// No link on screen (the off-canvas menu on a narrow window): nothing to point at.
			if ( ! isTargetVisible( rect ) ) {
				widget.style.display = 'none';
				return;
			}

			const { top, left } = pointerPosition( rect, offset );
			widget.style.position = 'fixed';
			widget.style.top = `${ top }px`;
			widget.style.left = `${ left }px`;
			widget.style.display = '';
		};

		let frame = 0;
		const schedule = () => {
			if ( frame ) {
				return;
			}
			frame = window.requestAnimationFrame( () => {
				frame = 0;
				place();
			} );
		};

		place();
		window.addEventListener( 'scroll', schedule, { passive: true } );
		window.addEventListener( 'resize', schedule );
		// Folding the menu, or opening it on a narrow window, moves the link without a scroll.
		$( document ).on( 'wp-collapse-menu wp-menu-state-set', schedule );
		$( '#wp-admin-bar-menu-toggle' ).on( 'click', () =>
			window.setTimeout( schedule, 50 )
		);
	} );
} )( window.jQuery );
