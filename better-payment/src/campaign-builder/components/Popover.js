import {
	createPortal,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { popoverPosition } from '../utils/popoverPosition';

/**
 * A floating panel anchored to a control — the colour picker's home.
 *
 * Rendered into `document.body`, so the sidebar's narrow column, its
 * `overflow: auto` and a collapsible section's `overflow: hidden` cannot clip
 * it. Inside the builder sidebar it opens beside the sidebar, over the canvas,
 * level with the anchor, so the controls stay visible; elsewhere (or with no
 * room) below the anchor, or above. Always inside the viewport
 * (`popoverPosition()`), and it follows the anchor while the sidebar scrolls. Closes on Escape (focus returns to the anchor) and on a mouse-down
 * outside both the panel and the anchor — the anchor itself toggles.
 *
 * @param {Object}   props
 * @param {Object}   props.anchorRef Ref to the element it hangs off.
 * @param {Function} props.onClose   Called to close it.
 * @param {string}   props.label     Accessible name of the dialog.
 * @param {number}   props.width     Panel width in px.
 * @param {*}        props.children  Content.
 */
export default function Popover( {
	anchorRef,
	onClose,
	label,
	width = 264,
	children,
} ) {
	const panelRef = useRef( null );
	const [ pos, setPos ] = useState( null );

	// Measure after paint, then on every scroll (capture: the sidebar scrolls,
	// not the window) and resize, once per frame.
	useLayoutEffect( () => {
		let frame = 0;
		const place = () => {
			cancelAnimationFrame( frame );
			frame = requestAnimationFrame( () => {
				const anchor = anchorRef.current?.getBoundingClientRect();
				const panel = panelRef.current;
				if ( ! anchor || ! panel ) return;
				const sidebar = anchorRef.current
					?.closest?.( '.bp-cb-right-sidebar' )
					?.getBoundingClientRect();
				setPos(
					popoverPosition( {
						anchor,
						side: sidebar ? sidebar.left : null,
						width,
						height: panel.offsetHeight,
						viewport: {
							width: window.innerWidth,
							height: window.innerHeight,
						},
					} )
				);
			} );
		};
		place();
		window.addEventListener( 'scroll', place, true );
		window.addEventListener( 'resize', place );
		return () => {
			cancelAnimationFrame( frame );
			window.removeEventListener( 'scroll', place, true );
			window.removeEventListener( 'resize', place );
		};
	}, [ anchorRef, width ] );

	useEffect( () => {
		const onDown = ( e ) => {
			if (
				panelRef.current?.contains( e.target ) ||
				anchorRef.current?.contains( e.target )
			) {
				return;
			}
			onClose();
		};
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				e.stopPropagation();
				onClose();
				anchorRef.current?.focus?.();
			}
		};
		document.addEventListener( 'mousedown', onDown );
		document.addEventListener( 'keydown', onKey, true );
		return () => {
			document.removeEventListener( 'mousedown', onDown );
			document.removeEventListener( 'keydown', onKey, true );
		};
	}, [ anchorRef, onClose ] );

	return createPortal(
		<div
			ref={ panelRef }
			className={ `bp-cb-popover${
				pos?.placement ? ` is-${ pos.placement }` : ''
			}` }
			role="dialog"
			aria-label={ label }
			style={ {
				position: 'fixed',
				width,
				top: pos?.top ?? -9999,
				left: pos?.left ?? -9999,
				visibility: pos ? 'visible' : 'hidden',
				zIndex: 999999,
			} }
		>
			{ children }
		</div>,
		document.body
	);
}
