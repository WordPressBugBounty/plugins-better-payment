/**
 * The campaign page's mobile "Donate" bar.
 *
 * RendererService prints one `.bp-campaign-askbar` per page — a link to an
 * anchor placed just before the campaign's ask (its first Donate Amount picker,
 * or its Donate Button). A theme decides whether it shows at all (the `-v3`
 * themes do, on a phone, where the ask sits below the whole story). This keeps it
 * out of the way:
 *
 * - hidden while the ask itself is on screen — a bar pointing at what the
 *   visitor is already looking at is noise, and it would cover the button;
 * - hidden while the campaign is off screen — a campaign embedded in a longer
 *   page must not float a Donate bar over the rest of that page;
 * - a tap scrolls to the ask and moves focus there, so keyboard and screen
 *   reader users land where sighted users do.
 *
 * Without JavaScript (or IntersectionObserver) the bar stays a plain jump link.
 */

/**
 * Whether the bar should be hidden.
 *
 * @param {Object}  state
 * @param {boolean} state.campaignVisible Any part of the campaign is on screen.
 * @param {boolean} state.askVisible      Any part of the ask is on screen.
 * @return {boolean} True to hide.
 */
export function askBarHidden( { campaignVisible, askVisible } ) {
	return ! campaignVisible || askVisible;
}

/**
 * The elements that make up the ask: the picker the anchor sits before, and the
 * campaign's Donate Button. Either one on screen means the ask is.
 *
 * @param {Element} anchor   The `.bp-campaign-ask-anchor`.
 * @param {Element} campaign Its `.bp-campaign` wrapper.
 * @return {Element[]} Elements to watch.
 */
export function askRegion( anchor, campaign ) {
	return [
		anchor.nextElementSibling,
		campaign?.querySelector( '.bp-campaign-donate-wrap' ),
	].filter( ( el, i, all ) => el && all.indexOf( el ) === i );
}

/**
 * The first control in the ask, to move focus to after the jump.
 *
 * @param {Element[]} region askRegion() result.
 * @return {?Element} An amount radio, the custom amount, or the Donate link.
 */
function firstControl( region ) {
	for ( const el of region ) {
		const control = el.querySelector(
			'input.bp-option-amount, .campaign-custom-amount, .bp-donate_btn'
		);
		if ( control ) return control;
	}
	return null;
}

/**
 * Wire every Donate bar in a document.
 *
 * @param {Document} doc Document to search.
 * @param {Window}   win Its window.
 * @return {number} How many bars were wired.
 */
export function initAskBars( doc = document, win = window ) {
	let wired = 0;

	doc.querySelectorAll( '.bp-campaign-askbar[data-bp-ask]' ).forEach(
		( bar ) => {
			const anchor = doc.getElementById( bar.dataset.bpAsk );
			const campaign = bar.closest( '.bp-campaign' );
			if ( ! anchor || ! campaign ) {
				// Nothing to jump to: a dead link is worse than no bar.
				bar.remove();
				return;
			}

			const region = askRegion( anchor, campaign );
			const reduceMotion = win.matchMedia?.(
				'(prefers-reduced-motion: reduce)'
			)?.matches;

			bar.querySelector( 'a' )?.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				anchor.scrollIntoView( {
					behavior: reduceMotion ? 'auto' : 'smooth',
					block: 'start',
				} );
				firstControl( region )?.focus( { preventScroll: true } );
			} );

			if ( typeof win.IntersectionObserver !== 'function' ) {
				wired++;
				return;
			}

			const visible = new Set();
			const sync = () => {
				const hidden = askBarHidden( {
					campaignVisible: visible.has( campaign ),
					askVisible: region.some( ( el ) => visible.has( el ) ),
				} );
				bar.classList.toggle( 'is-hidden', hidden );
				// Hidden means out of the tab order and the accessibility tree too.
				bar.toggleAttribute( 'inert', hidden );
			};

			const observer = new win.IntersectionObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						visible.add( entry.target );
					} else {
						visible.delete( entry.target );
					}
				} );
				sync();
			} );

			[ campaign, ...region ].forEach( ( el ) => observer.observe( el ) );
			wired++;
		}
	);

	return wired;
}
