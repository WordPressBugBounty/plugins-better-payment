import { __ } from '@wordpress/i18n';

/**
 * Crown marker for Pro-locked items.
 *
 * Replaces the old "PRO" text badge on the Elements palette cards. It is sized
 * in CSS (`.bp-cb-pro-crown`), deliberately smaller than the card's own 20px
 * element icon, so the lock reads as a marker on the widget rather than
 * competing with the widget's identity.
 *
 * `aria-hidden` + the visually-hidden label keep the meaning for screen readers,
 * which a bare decorative glyph would drop — the card's `title` covers the
 * pointer case only.
 *
 * Pass `decorative` where the crown sits inside text that already says it, such as
 * the canvas overlay's "Get PRO to Unlock" button. Announcing "Pro feature Get PRO
 * to Unlock" is not more accessible than the button label on its own. Default is
 * `false` so the palette card — where the glyph is the *only* signal — keeps it.
 *
 * @param {Object}  props
 * @param {boolean} props.decorative Suppress the screen-reader label.
 */
export default function ProCrownIcon( { decorative = false } ) {
	return (
		<span className="bp-cb-pro-crown">
			<svg
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
				aria-hidden="true"
				focusable="false"
			>
				<path
					d="M3 8.2a1.2 1.2 0 1 1 1.86 1l1.9 2.66a1 1 0 0 0 1.6.04l2.86-3.6a1.2 1.2 0 1 1 1.56 0l2.86 3.6a1 1 0 0 0 1.6-.04l1.9-2.66A1.2 1.2 0 1 1 21 8.2c0 .5-.3.93-.74 1.11l-1.2 7.06a1 1 0 0 1-.99.83H5.93a1 1 0 0 1-.99-.83l-1.2-7.06A1.2 1.2 0 0 1 3 8.2z"
					fill="currentColor"
				/>
				<rect
					x="5.6"
					y="18.6"
					width="12.8"
					height="1.9"
					rx="0.95"
					fill="currentColor"
				/>
			</svg>
			{ ! decorative && (
				<span className="screen-reader-text">
					{ __( 'Pro feature', 'better-payment' ) }
				</span>
			) }
		</span>
	);
}
