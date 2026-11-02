import { __ } from '@wordpress/i18n';
import ProCrownIcon from './ProCrownIcon';

/**
 * The device row above a free install's Style / Advanced controls.
 *
 * Free upsell only. Desktop is the one device a free install styles, so it is
 * shown selected; Tablet and Mobile carry a crown and open the upgrade prompt.
 * Per-device values are Pro's: Pro draws its own switcher through the
 * `better_payment.campaign_builder.style_toolbar` filter, and RightSidebar
 * draws this only while `proEnabled` is false, so a Pro user never sees it.
 *
 * @param {Object}   props
 * @param {Function} props.onRequestUpgrade Opens the upgrade modal for a type.
 */
export default function ResponsiveLock( { onRequestUpgrade } ) {
	const upgrade = () =>
		typeof onRequestUpgrade === 'function' &&
		onRequestUpgrade( 'responsive_styles' );

	return (
		<div
			className="bp-cb-devices"
			role="group"
			aria-label={ __( 'Device', 'better-payment' ) }
		>
			<button
				type="button"
				className="bp-cb-devices__btn is-active"
				aria-pressed="true"
				title={ __( 'Desktop', 'better-payment' ) }
			>
				<DeviceIcon device="desktop" />
				<span className="screen-reader-text">
					{ __( 'Desktop', 'better-payment' ) }
				</span>
			</button>
			{ [
				[ 'tablet', __( 'Tablet', 'better-payment' ) ],
				[ 'mobile', __( 'Mobile', 'better-payment' ) ],
			].map( ( [ device, label ] ) => (
				<button
					key={ device }
					type="button"
					className="bp-cb-devices__btn is-locked"
					aria-pressed="false"
					title={ `${ label } — ${ __(
						'Pro feature',
						'better-payment'
					) }` }
					onClick={ upgrade }
				>
					<DeviceIcon device={ device } />
					<span className="screen-reader-text">{ label }</span>
					<ProCrownIcon decorative />
				</button>
			) ) }
		</div>
	);
}

/**
 * Desktop / tablet / mobile glyph. Exported so an add-on's switcher can match.
 *
 * @param {Object} props
 * @param {string} props.device 'desktop' | 'tablet' | 'mobile'.
 */
export function DeviceIcon( { device } ) {
	if ( device === 'tablet' ) {
		return (
			<svg
				width="16"
				height="16"
				viewBox="0 0 20 20"
				fill="none"
				aria-hidden="true"
			>
				<rect
					x="4"
					y="2"
					width="12"
					height="16"
					rx="2"
					stroke="currentColor"
					strokeWidth="1.5"
				/>
				<circle cx="10" cy="15" r="0.9" fill="currentColor" />
			</svg>
		);
	}
	if ( device === 'mobile' ) {
		return (
			<svg
				width="16"
				height="16"
				viewBox="0 0 20 20"
				fill="none"
				aria-hidden="true"
			>
				<rect
					x="6"
					y="2"
					width="8"
					height="16"
					rx="1.8"
					stroke="currentColor"
					strokeWidth="1.5"
				/>
				<circle cx="10" cy="15.2" r="0.8" fill="currentColor" />
			</svg>
		);
	}
	return (
		<svg
			width="16"
			height="16"
			viewBox="0 0 20 20"
			fill="none"
			aria-hidden="true"
		>
			<rect
				x="2"
				y="3"
				width="16"
				height="11"
				rx="1.5"
				stroke="currentColor"
				strokeWidth="1.5"
			/>
			<path
				d="M7 17h6M10 14v3"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinecap="round"
			/>
		</svg>
	);
}
