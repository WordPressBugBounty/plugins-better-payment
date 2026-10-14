/**
 * StartContent Component
 *
 * Welcome screen for the Quick Setup wizard
 */

import heroImage from '@quick-setup-images/hero.png';
import { useState } from '@wordpress/element';
import { sendTrackingOptIn } from '../../utils/tracking';

const StartContent = ( { onNext, onTrackingOptIn } ) => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => setIsModalOpen( true );
	const closeModal = () => setIsModalOpen( false );

	/**
	 * Handle Skip button click
	 *
	 * Skipping declines usage tracking, so it sends nothing and records
	 * nothing. It only moves to the next step. The user can still return to the
	 * Start step and choose "Proceed To Next Step" later.
	 */
	const handleSkip = () => {
		onNext();
	};

	/**
	 * Handle Proceed to Next Step button click
	 * Triggers AJAX to save is_tracking as 'yes'
	 * Sets isTrackingOptedIn to true (prevents back navigation)
	 * User should NEVER return to Start step
	 */
	const handleProceedToNextStep = () => {
		sendTrackingOptIn()
			.then( ( data ) => {
				if ( data.success ) {
					// Update parent state to indicate user has opted in
					// This prevents back navigation to Start step
					onTrackingOptIn( true );
					onNext();
				} else {
					console.error( 'Failed to save tracking data:', data.data );
				}
			} )
			.catch( ( err ) => {
				console.error( 'AJAX error:', err );
			} );
	};

	return (
		<div className="bp-wizard__content bp-wizard__content--start">
			<div className="bp-wizard__hero">
				<img src={ heroImage } alt="img" />

				<h1 className="bp-wizard__title">
					Begin Smooth Payment Experience With Better Payment
				</h1>
				<p className="bp-wizard__description">
					Thank you for choosing Better Payment for managing your
					WordPress payments. Simply follow these easy setup wizard
					steps and elevate your payment handling workflow!
				</p>

				<div className="bp-wizard__actions">
					<button
						className="bp-btn bp-btn--primary bp-btn--process"
						onClick={ handleProceedToNextStep }
					>
						Proceed To Next Step
						<svg
							width="20"
							height="20"
							viewBox="0 0 20 20"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<g clipPath="url(#clip0_6480_19667)">
								<path
									d="M4.16797 10H15.8346"
									stroke="white"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
								<path
									d="M10.832 15L15.832 10"
									stroke="white"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
								<path
									d="M10.832 5L15.832 10"
									stroke="white"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</g>
							<defs>
								<clipPath id="clip0_6480_19667">
									<rect width="20" height="20" fill="white" />
								</clipPath>
							</defs>
						</svg>
					</button>
					<span className="bp-link" onClick={ handleSkip }>
						Skip It
					</span>
				</div>

				<div className="bp-wizard__notice">
					<svg
						width="16"
						height="16"
						viewBox="0 0 16 16"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g clipPath="url(#clip0_6480_19545)">
							<path
								d="M2 8C2 8.78793 2.15519 9.56815 2.45672 10.2961C2.75825 11.0241 3.20021 11.6855 3.75736 12.2426C4.31451 12.7998 4.97595 13.2417 5.7039 13.5433C6.43185 13.8448 7.21207 14 8 14C8.78793 14 9.56815 13.8448 10.2961 13.5433C11.0241 13.2417 11.6855 12.7998 12.2426 12.2426C12.7998 11.6855 13.2417 11.0241 13.5433 10.2961C13.8448 9.56815 14 8.78793 14 8C14 6.4087 13.3679 4.88258 12.2426 3.75736C11.1174 2.63214 9.5913 2 8 2C6.4087 2 4.88258 2.63214 3.75736 3.75736C2.63214 4.88258 2 6.4087 2 8Z"
								stroke="#FEBD25"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
							<path
								d="M8 6H8.00667"
								stroke="#FEBD25"
								strokeWidth="1.5"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
							<path
								d="M7.33594 8H8.0026V10.6667H8.66927"
								stroke="#FEBD25"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</g>
						<defs>
							<clipPath id="clip0_6480_19545">
								<rect width="16" height="16" fill="white" />
							</clipPath>
						</defs>
					</svg>
					<span>
						&ldquo;Proceed To Next Step&rdquo; shares non-sensitive
						usage data with WPDeveloper. Choose &ldquo;Skip
						It&rdquo; to continue without sharing anything.&nbsp;
						<span className="bp-link" onClick={ openModal }>
							Find out what we collect?
						</span>
					</span>
				</div>
			</div>

			{ /* Modal */ }
			{ isModalOpen && (
				<div className="bp-modal-overlay" onClick={ closeModal }>
					<div
						className="bp-modal-box"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<div className="bp-modal-header">
							<h4>What Do We Collect?</h4>
							<span className="close-btn" onClick={ closeModal }>
								<svg
									width="24"
									height="24"
									viewBox="0 0 24 24"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<g clipPath="url(#clip0_6692_21867)">
										<path
											d="M18 6L6 18"
											stroke="#B9BFCA"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
										<path
											d="M6 6L18 18"
											stroke="#B9BFCA"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</g>
									<defs>
										<clipPath id="clip0_6692_21867">
											<rect
												width="24"
												height="24"
												fill="white"
											/>
										</clipPath>
									</defs>
								</svg>
							</span>
						</div>
						<div className="bp-modal-body">
							<p>
								Only if you choose &ldquo;Proceed To Next
								Step&rdquo;, Better Payment sends this to
								WPDeveloper (send.wpinsight.com): once right
								away, once a day after that, and once if you
								deactivate the plugin. It includes your site URL
								and name; WordPress, PHP and server versions;
								language, charset, text direction and whether it
								is a multisite; your active plugins and theme,
								and how many plugins are inactive; the Better
								Payment version; a site ID the service assigns;
								which Better Payment widgets, blocks, campaign
								elements, features and gateways you use; your
								email address, to send you the discount coupon;
								and, if you deactivate the plugin, the reason
								you choose.
							</p>
							<p>
								We never collect payment or transaction data,
								your customers&rsquo; details, your API keys,
								your server file paths or the names of inactive
								plugins. Skipping sends nothing. No spam, we
								promise.
							</p>
						</div>
					</div>
				</div>
			) }
		</div>
	);
};

export default StartContent;
