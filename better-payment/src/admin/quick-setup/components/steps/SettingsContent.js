/**
 * SettingsContent Component
 *
 * Payment gateway and feature toggles configuration
 */

import { useState, useRef, useEffect } from '@wordpress/element';

export default function SettingsContent( {
	data,
	currencies,
	onDataChange,
	onNext,
} ) {
	const [ isCurrencyDropdownOpen, setIsCurrencyDropdownOpen ] =
		useState( false );
	const currencyDropdownRef = useRef( null );

	// Handle click outside to close dropdown
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				currencyDropdownRef.current &&
				! currencyDropdownRef.current.contains( event.target )
			) {
				setIsCurrencyDropdownOpen( false );
			}
		};

		if ( isCurrencyDropdownOpen ) {
			document.addEventListener( 'mousedown', handleClickOutside );
			return () =>
				document.removeEventListener( 'mousedown', handleClickOutside );
		}
	}, [ isCurrencyDropdownOpen ] );

	const handleCurrencySelect = ( currency ) => {
		onDataChange( {
			...data,
			better_payment_settings_general_general_currency: currency,
		} );
		setIsCurrencyDropdownOpen( false );
	};

	return (
		<div className="bp-wizard__content bp-wizard__content--settings">
			<div className="bp-settings">
				<div className="bp-settings__grid">
					<div className="bp-setting-card">
						<div className="bp-setting-card__details">
							<h3 className="bp-setting-card__title">PayPal</h3>
							<p className="bp-setting-card__description">
								Enable PayPal if you want to offer payment
								options through PayPal.
								<a
									href="//betterpayment.co/docs/set-up-paypal-account-with-better-payment/"
									target="_blank"
									className="bp-link"
								>
									See documentation.
								</a>
							</p>
						</div>
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_paypal"
								value={
									data?.better_payment_settings_general_general_paypal
								}
								checked={
									data.better_payment_settings_general_general_paypal ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_paypal:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card">
						<div className="bp-setting-card__details">
							<h3 className="bp-setting-card__title">Stripe</h3>
							<p className="bp-setting-card__description">
								Enable Stripe if you want to offer payment
								options through Stripe.
								<a
									href="//betterpayment.co/docs/set-up-stripe-account-with-better-payment/"
									target="_blank"
									className="bp-link"
								>
									See documentation.
								</a>
							</p>
						</div>
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_stripe"
								value={
									data?.better_payment_settings_general_general_stripe
								}
								checked={
									data.better_payment_settings_general_general_stripe ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_stripe:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card">
						<div className="bp-setting-card__details">
							<h3 className="bp-setting-card__title">Paystack</h3>
							<p className="bp-setting-card__description">
								Enable Paystack if you want to offer payment
								options through Paystack.
								<a
									href="//betterpayment.co/docs/set-up-paystack-account-with-better-payment/"
									target="_blank"
									className="bp-link"
								>
									See documentation.
								</a>
							</p>
						</div>
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_paystack"
								value={
									data?.better_payment_settings_general_general_paystack
								}
								checked={
									data.better_payment_settings_general_general_paystack ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_paystack:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card">
						<div className="bp-setting-card__details">
							<div className="bp-setting-card__title-wrap">
								<h3 className="bp-setting-card__title">
									Subscriber Dashboard
								</h3>
							</div>
							<p className="bp-setting-card__description">
								Enable User Dashboard to provide personalized
								subscriber transaction previews.
								<a
									href="//betterpayment.co/docs/user-dashboard-using-better-payment/"
									target="_blank"
									className="bp-link"
								>
									See documentation.
								</a>
							</p>
						</div>
						{ /* The User Dashboard is a free feature (default on, switchable in Settings),
                            so its toggle works for everyone. It used to be disabled with a Pro crown
                            on free installs, which read as a free feature locked behind Pro
                            (WordPress.org Guideline 5, issue #147). */ }
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_user_dashboard"
								value={
									data?.better_payment_settings_general_general_user_dashboard
								}
								checked={
									data.better_payment_settings_general_general_user_dashboard ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_user_dashboard:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card">
						<div className="bp-setting-card__header">
							<h3 className="bp-setting-card__title">
								Fundraising Campaign
							</h3>
							<p className="bp-setting-card__description">
								Enable Fundraising Campaign to run donations,
								crowdfunding or any kind of campaign.
								<a
									href="//betterpayment.co/docs/configure-fundraising-campaign-in-better-payment/"
									target="_blank"
									className="bp-link"
								>
									See documentation.
								</a>
							</p>
						</div>
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_fundraising_campaign"
								value={
									data?.better_payment_settings_general_general_fundraising_campaign
								}
								checked={
									data.better_payment_settings_general_general_fundraising_campaign ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_fundraising_campaign:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card">
						<div className="bp-setting-card__details">
							<h3 className="bp-setting-card__title">
								Email Notifications
							</h3>
							<p className="bp-setting-card__description">
								Turn on email notifications to get real-time
								updates on status of each payment transaction
								for both owner & customer.
							</p>
						</div>
						<label className="bp-toggle">
							<input
								type="checkbox"
								name="better_payment_settings_general_general_email"
								value={
									data?.better_payment_settings_general_general_email
								}
								checked={
									data.better_payment_settings_general_general_email ===
									'yes'
								}
								onChange={ ( e ) =>
									onDataChange( {
										...data,
										better_payment_settings_general_general_email:
											e.target.checked ? 'yes' : 'no',
									} )
								}
							/>
							<span className="bp-toggle__slider"></span>
						</label>
					</div>

					<div className="bp-setting-card bp-setting-card--full">
						<div className="bp-setting-card__info">
							<h3 className="bp-setting-card__title">Currency</h3>
							<p className="bp-setting-card__description">
								Select the default currency for each
								transaction. You can also overwrite this setting
								from each form control.
							</p>
						</div>
						<div
							ref={ currencyDropdownRef }
							className="bp-currency-dropdown"
							onClick={ () =>
								setIsCurrencyDropdownOpen(
									! isCurrencyDropdownOpen
								)
							}
						>
							<button
								className="bp-currency-dropdown__trigger"
								type="button"
							>
								<span className="bp-currency-dropdown__value">
									{ data?.better_payment_settings_general_general_currency ||
										'USD' }
								</span>
								<span className="bp-currency-dropdown__icon">
									<svg
										width="12"
										height="8"
										viewBox="0 0 12 8"
										fill="none"
										xmlns="http://www.w3.org/2000/svg"
										className={
											isCurrencyDropdownOpen
												? 'bp-currency-dropdown-icon-open'
												: ''
										}
									>
										<path
											d="M1 1.5L6 6.5L11 1.5"
											stroke="#6B7280"
											strokeWidth="1.5"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</svg>
								</span>
							</button>

							{ isCurrencyDropdownOpen && (
								<ul className="bp-currency-dropdown__list">
									{ Object.keys( currencies ).map(
										( currency ) => (
											<li
												key={ currency }
												className={ `bp-currency-dropdown__item ${
													( data?.better_payment_settings_general_general_currency ||
														'USD' ) === currency
														? 'bp-currency-dropdown__item--active'
														: ''
												}` }
												onClick={ () =>
													handleCurrencySelect(
														currency
													)
												}
												role="option"
												aria-selected={
													( data?.better_payment_settings_general_general_currency ||
														'USD' ) === currency
												}
											>
												{ currency }
											</li>
										)
									) }
								</ul>
							) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}
