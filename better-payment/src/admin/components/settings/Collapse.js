/**
 * Smooth show/hide for a settings block.
 *
 * Height cannot transition to `auto`, so the wrapper is a one-row grid whose row
 * animates between `0fr` and `1fr` — the content keeps its natural height and
 * nothing is measured in JS. Opacity and a small lift ride along.
 *
 * The children stay mounted until the closing transition has finished, then
 * unmount, so a hidden block still costs nothing (and a closed MCP panel fires
 * none of its requests). An initially open block renders open with no animation:
 * a page load is not a reveal.
 */
import { useEffect, useState } from '@wordpress/element';

/** Must match `$bp-collapse-duration` in admin.scss. */
export const COLLAPSE_DURATION = 320;

const Collapse = ( { open, children, className = '' } ) => {
	const [ mounted, setMounted ] = useState( open );
	const [ expanded, setExpanded ] = useState( open );

	useEffect( () => {
		if ( open ) {
			setMounted( true );

			// Two frames: the first paints the collapsed state, the second
			// starts the transition from it.
			let inner = 0;
			const outer = window.requestAnimationFrame( () => {
				inner = window.requestAnimationFrame( () =>
					setExpanded( true )
				);
			} );

			return () => {
				window.cancelAnimationFrame( outer );
				window.cancelAnimationFrame( inner );
			};
		}

		setExpanded( false );

		// A timer, not `transitionend`: under prefers-reduced-motion there is no
		// transition, and the event would never come.
		const timer = window.setTimeout(
			() => setMounted( false ),
			COLLAPSE_DURATION
		);

		return () => window.clearTimeout( timer );
	}, [ open ] );

	if ( ! mounted ) {
		return null;
	}

	return (
		<div
			className={ `bp-collapse${
				expanded ? ' is-open' : ''
			} ${ className }` }
			aria-hidden={ ! expanded }
		>
			<div className="bp-collapse__inner">{ children }</div>
		</div>
	);
};

export default Collapse;
