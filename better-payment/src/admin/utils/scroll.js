import { useEffect, useRef } from '@wordpress/element';

/**
 * Scroll the admin screen back to the top.
 *
 * The React admin is a single page: switching tabs or opening a details view
 * swaps the content without a page load, so the browser keeps the old scroll
 * position and the new screen opens at whatever depth the previous one was
 * left at. Call this wherever a new screen replaces the current one.
 *
 * @param {Window} [win] Window, injectable for tests.
 */
export function scrollAdminToTop( win = window ) {
	win.scrollTo?.( { top: 0, left: 0, behavior: 'auto' } );
}

/**
 * Remembers a list's scroll position across a details view, for one return.
 *
 * `remember()` stores where the list was scrolled to. `requestRestore()` marks
 * that the list is coming back. `takeRestore()` hands the stored position over
 * once and then forgets it, so a later re-render cannot jump the page again.
 *
 * @param {Window} [win] Window, injectable for tests.
 * @return {{remember: Function, requestRestore: Function, takeRestore: Function}} Memory.
 */
export function createScrollMemory( win = window ) {
	let saved = null;
	let pending = false;

	return {
		remember() {
			saved = win.scrollY ?? win.pageYOffset ?? 0;
			pending = false;
		},
		requestRestore() {
			pending = saved !== null;
		},
		takeRestore() {
			if ( ! pending ) {
				return null;
			}
			const top = saved;
			pending = false;
			saved = null;
			return top;
		},
	};
}

/**
 * Returns a list to the scroll position it had before a details view opened.
 *
 * The details view replaces the list and opens at the top, so the list would
 * otherwise come back at the top too. Call `remember()` before showing the
 * details and `requestRestore()` when closing them. The scroll waits until
 * `ready` is true, so a list that refetches is restored only after its rows
 * are back, not onto a shorter skeleton.
 *
 * @param {Object}  args
 * @param {boolean} args.showingList True while the list (not the details) renders.
 * @param {boolean} args.ready       True once the list has its rows.
 * @return {{remember: Function, requestRestore: Function}} Handlers.
 */
export function useListScrollRestore( { showingList, ready } ) {
	const memory = useRef( null );
	if ( ! memory.current ) {
		memory.current = createScrollMemory();
	}

	useEffect( () => {
		if ( ! showingList || ! ready ) {
			return;
		}

		const top = memory.current.takeRestore();
		if ( top === null ) {
			return;
		}

		// Wait one frame so the list has been laid out at its full height.
		const frame = window.requestAnimationFrame( () =>
			window.scrollTo( { top, left: 0, behavior: 'auto' } )
		);
		return () => window.cancelAnimationFrame( frame );
	}, [ showingList, ready ] );

	return {
		remember: () => memory.current.remember(),
		requestRestore: () => memory.current.requestRestore(),
	};
}
