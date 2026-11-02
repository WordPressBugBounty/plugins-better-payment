/**
 * Re-run `onChange` whenever the preview document's layout moves after load.
 *
 * The canvas measures element boxes on the iframe's `load` event, but the page
 * keeps moving after that: the campaign fonts use `font-display: swap`, so the
 * text is first laid out in the fallback face and re-wraps when the real face
 * arrives, and an image that finishes late grows its element. Each shift pushes
 * every element below it down, and the hotspots stayed where the elements were
 * at `load` — a selection outline cut through the middle of the next widget.
 *
 * Watches, in the preview's own window:
 *   - a ResizeObserver on the body, every column and every element (a box that
 *     changes size is what moves the boxes after it);
 *   - `document.fonts` (`ready` and `loadingdone`), for a swap that re-wraps
 *     text without changing any observed height;
 *   - `load` on every image not yet complete.
 *
 * Calls are batched to one per animation frame. Returns a cleanup function.
 *
 * @param {Document} doc      The preview iframe's document.
 * @param {Function} onChange Called (at most once per frame) after a shift.
 * @return {Function} Stops watching.
 */
export function watchPreviewLayout( doc, onChange ) {
	const win = doc?.defaultView;
	if ( ! win ) {
		return () => {};
	}

	let frame = 0;
	let stopped = false;
	const schedule = () => {
		if ( stopped || frame ) return;
		frame = win.requestAnimationFrame( () => {
			frame = 0;
			if ( ! stopped ) onChange();
		} );
	};

	const cleanups = [];

	const Observer = win.ResizeObserver;
	if ( Observer ) {
		// The first callback fires for every target on observe(); the load
		// handler has just measured, so skip that initial round.
		let primed = false;
		const observer = new Observer( () => {
			if ( primed ) schedule();
		} );
		[
			doc.body,
			...doc.querySelectorAll(
				'[data-bp-column-id], [data-bp-element-id]'
			),
		]
			.filter( Boolean )
			.forEach( ( node ) => observer.observe( node ) );
		win.requestAnimationFrame( () => {
			primed = true;
		} );
		cleanups.push( () => observer.disconnect() );
	}

	const fonts = doc.fonts;
	if ( fonts ) {
		fonts.ready?.then( schedule );
		if ( fonts.addEventListener ) {
			fonts.addEventListener( 'loadingdone', schedule );
			cleanups.push( () =>
				fonts.removeEventListener( 'loadingdone', schedule )
			);
		}
	}

	doc.querySelectorAll( 'img' ).forEach( ( img ) => {
		if ( img.complete ) return;
		img.addEventListener( 'load', schedule );
		img.addEventListener( 'error', schedule );
		cleanups.push( () => {
			img.removeEventListener( 'load', schedule );
			img.removeEventListener( 'error', schedule );
		} );
	} );

	return () => {
		stopped = true;
		if ( frame ) win.cancelAnimationFrame( frame );
		cleanups.forEach( ( fn ) => fn() );
	};
}
