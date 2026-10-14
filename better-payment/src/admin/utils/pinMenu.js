/**
 * Keep WordPress's admin-menu pinning in step with the React admin's height.
 *
 * wp-admin/js/common.js measures `#wpwrap` once, on DOM ready, and again only on
 * a window resize or a `wp-pin-menu` event — never on scroll. On our screens the
 * React root is still empty at DOM ready, so the page measures shorter than the
 * menu and common.js unpins it. When the menu is also taller than the window
 * (WooCommerce and similar plugins add enough items for that), the stale
 * measurement is never corrected: the menu scrolls away with the page and leaves
 * an empty sidebar. Without those plugins the menu fits the window, takes the
 * `sticky-menu` path instead, and the bug does not show.
 *
 * Re-announcing `wp-pin-menu` whenever `#wpwrap` changes height makes common.js
 * re-measure, exactly as core does after its own layout changes (postboxes).
 *
 * @param {Object} [env]     Injectable globals, for tests.
 * @param {Window} [env.win] Window.
 * @param {Document} [env.doc] Document.
 * @return {Function} Stops watching.
 */
export function watchAdminMenuPin( { win = window, doc = document } = {} ) {
	const wrap = doc.getElementById( 'wpwrap' );
	const $ = win.jQuery;

	if (
		! wrap ||
		typeof $ !== 'function' ||
		typeof win.ResizeObserver !== 'function'
	) {
		return () => {};
	}

	let frame = 0;
	let lastHeight = -1;

	const observer = new win.ResizeObserver( () => {
		if ( frame ) {
			return;
		}

		frame = win.requestAnimationFrame( () => {
			frame = 0;

			const height = wrap.offsetHeight;
			if ( height === lastHeight ) {
				return;
			}

			lastHeight = height;
			$( doc ).trigger( 'wp-pin-menu' );
		} );
	} );

	observer.observe( wrap );

	return () => {
		observer.disconnect();
		if ( frame ) {
			win.cancelAnimationFrame( frame );
		}
	};
}
