/**
 * Browser-level guard for unsaved builder changes.
 *
 * The Exit button already asks before discarding edits (UnsavedChangesModal),
 * but closing the tab, reloading, or following any other link left the builder
 * with no warning at all. A `beforeunload` handler is the only hook the browser
 * gives for those, and it can only show the browser's own generic prompt.
 */

/**
 * Build a `beforeunload` listener that asks before leaving while `shouldWarn()`
 * is true.
 *
 * `shouldWarn` is read at unload time (not captured), so the listener can be
 * registered once and still see the latest dirty state — and so a deliberate
 * navigation (Exit, Save & Exit) can switch it off synchronously before setting
 * `window.location`, without waiting for React to re-render.
 *
 * @param {Function} shouldWarn Returns true when leaving would lose changes.
 * @return {Function} The event listener.
 */
export function createBeforeUnloadHandler( shouldWarn ) {
	return ( event ) => {
		if ( ! shouldWarn() ) return undefined;
		// Both forms: preventDefault() is the standard, returnValue is what
		// Chromium/Safari still require to show the prompt.
		event.preventDefault();
		event.returnValue = '';
		return '';
	};
}
