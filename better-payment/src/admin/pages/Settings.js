import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { toast } from 'react-toastify';
import paypalLogo from './../images/bp-paypal.svg';
import stripeLogo from './../images/st.svg';
import paystackLogo from './../images/pay.svg';
import FooterCards from '../components/settings/FooterCards';
import General from '../svg/General';
import Admin from '../svg/Admin';
import Customer from '../svg/Customer';
import { settingsAPI } from '../utils/api';
import {
	readSavedSettings,
	rememberSavedSettings,
} from '../utils/savedSettings';
import { PAYPAL_REFUND_CREDENTIALS_SETTINGS_FILTER } from '../utils/proHandoff';
import { roleOptions } from '../utils/roleOptions';

const Settings = () => {
	// Seeded from the last SAVED settings, not only the page-load blob: the admin
	// switches tabs without a reload, so this page remounts on every visit. See
	// utils/savedSettings.js.
	const [ settings, setSettings ] = useState( readSavedSettings );
	const [ savedSettings, setSavedSettings ] = useState( readSavedSettings );
	// `?? {}` because the currency <select> below maps over this unguarded: the
	// global is optional-chained here, so a missing one made `Object.keys(undefined)`
	// throw and took the whole page down rather than rendering an empty list.
	const currencies = window.betterPaymentAdmin?.currencies ?? {};
	const [ saving, setSaving ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'general' );
	const [ adminAdditional, setAdminAdditional ] = useState( false );
	const [ customerAdditional, setCustomerAdditional ] = useState( false );

	const gatewayTabs = [
		{
			id: 'paypal',
			label: __( 'PayPal', 'better-payment' ),
			logo: paypalLogo,
		},
		{
			id: 'stripe',
			label: __( 'Stripe', 'better-payment' ),
			logo: stripeLogo,
		},
		{
			id: 'paystack',
			label: __( 'Paystack', 'better-payment' ),
			logo: paystackLogo,
		},
	];

	const emailTabs = [
		{
			id: 'admin',
			label: __( 'Admin', 'better-payment' ),
			logo: <Admin />,
		},
		{
			id: 'customer',
			label: __( 'Customer', 'better-payment' ),
			logo: <Customer />,
		},
	];

	/*
	 * This page fetches nothing. `settings` and `currencies` are localised by PHP
	 * into `window.betterPaymentAdmin` and are already in memory at the first
	 * render (see the useState seeds above), so there is nothing to wait for and
	 * nothing to place a skeleton over.
	 *
	 * There used to be a page-level `loading` gate here, bound to no request at
	 * all — a hardcoded `setTimeout(…, 200)` that early-returned a full-page
	 * `.bp-page-loading` spinner over data the page already had, then cleared on a
	 * timer. It was 200ms of withheld content on every visit. Do not reintroduce a
	 * gate — or a skeleton — unless this component actually starts fetching.
	 */
	useEffect( () => {
		document.title = __( 'Settings - Better Payment', 'better-payment' );
	}, [] );

	// Deep-link support: `&section=ai` (or any valid sub-tab id) lands the user
	// directly on that section instead of the default General one. The AI
	// assistant panel's "Enable it in AI settings" link uses this. Guard against
	// an unknown value so a bad param falls back to General rather than showing a
	// blank panel.
	useEffect( () => {
		const section = new URLSearchParams( window.location.search ).get(
			'section'
		);
		const valid = [
			'general',
			...gatewayTabs.map( ( t ) => t.id ),
			...emailTabs.map( ( t ) => t.id ),
			'subscription',
			'ai',
		];
		if ( section && valid.includes( section ) ) {
			setActiveTab( section );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const handleSettingChange = ( key, value ) => {
		setSettings( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};

	const handleSaveSettings = async () => {
		setSaving( true );

		const response = await settingsAPI.updateSettings( settings );
		if ( response.success ) {
			setSavedSettings( settings );
			rememberSavedSettings( settings );
			toast.success(
				__( 'Settings saved successfully.', 'better-payment' )
			);
		} else {
			toast.error( __( 'Error saving settings.', 'better-payment' ) );
		}
		setSaving( false );
	};

	// The PayPal refund credentials (client ID + secret) are Pro's: only Pro's refunds
	// read them. Pro fills the slot, and a free install renders no fields.
	const renderPayPalRefundCredentials = ( mode ) =>
		applyFilters( PAYPAL_REFUND_CREDENTIALS_SETTINGS_FILTER, null, {
			mode,
			settings,
			onChange: handleSettingChange,
		} );
	const paypalLiveRefundCredentials = renderPayPalRefundCredentials( 'live' );
	const paypalTestRefundCredentials = renderPayPalRefundCredentials( 'test' );

	return (
		<div className="tab-content bp-tab_active" id="setting">
			<div className="bp-container">
				<div className="border-radius_12 bp-setting_wrapper">
					<div className="bp-setting_wrapper-header">
						<div className="bp-row padding-24 align-items_center">
							<div className="bp-col bp-col_6">
								<h3 className="bp-font_ibm fx-2 bp-setting_header">
									{ __( 'Settings', 'better-payment' ) }
								</h3>
							</div>
							<div className="bp-col bp-col_6">
								<div className="bp-flex justify-content_flex-end">
									{ saving && (
										<div className="bp-spinner bp-spinner_small"></div>
									) }
									<button
										className="bp-btn bp-font_ibm fx-4 bp-save_changes-btn"
										onClick={ ( e ) => {
											e.preventDefault();
											handleSaveSettings();
										} }
									>
										{ __(
											' Save Changes',
											'better-payment'
										) }
									</button>
								</div>
							</div>
						</div>
					</div>
					<div className="bp-setting_wrapper-body">
						<div className="bp-row">
							<div className="bp-col bp-custome_col-2">
								<ul className="bp-setting_features-list">
									<li
										className={ `bp-flex align-items_center margin-bottom_20 bp-setting_features-list_item ${
											activeTab === 'general'
												? 'bp-tab_active'
												: ''
										}` }
										data-tab="general"
										onClick={ () =>
											setActiveTab( 'general' )
										}
									>
										<span className="bp-setting_features-logo">
											<General />
										</span>
										<span className="bp-font_ibm bp-setting_features-name">
											{ __(
												'General',
												'better-payment'
											) }
										</span>
									</li>
									<li className="margin-bottom_20">
										<span className="bp-font_ibm fx-6 feature-type">
											{ __(
												'PAYMENT',
												'better-payment'
											) }
										</span>
										<ul>
											{ gatewayTabs.map( ( tab ) => (
												<li
													className={ `bp-flex align-items_center bp-setting_features-list_item ${
														activeTab === tab.id
															? 'bp-tab_active'
															: ''
													}` }
													key={ tab.id }
													data-tab={ tab.id }
													onClick={ () =>
														setActiveTab( tab.id )
													}
												>
													<span className="bp-setting_features-logo">
														<img src={ tab.logo } />
													</span>
													<span className="bp-font_ibm bp-setting_features-name">
														{ tab.label }
													</span>
												</li>
											) ) }
										</ul>
									</li>
									<li className="margin-bottom_20">
										<span className="bp-font_ibm fx-6 feature-type">
											{ __( 'EMAIL', 'better-payment' ) }
										</span>
										<ul>
											{ emailTabs.map( ( tab ) => (
												<li
													className={ `bp-flex align-items_center bp-setting_features-list_item ${
														activeTab === tab.id
															? 'bp-tab_active'
															: ''
													}` }
													key={ tab.id }
													data-tab={ tab.id }
													onClick={ () =>
														setActiveTab( tab.id )
													}
												>
													<span className="bp-setting_features-logo">
														{ tab.logo }
													</span>
													<span className="bp-font_ibm bp-setting_features-name">
														{ tab.label }
													</span>
												</li>
											) ) }
										</ul>
									</li>
									<li className="margin-bottom_20">
										<span className="bp-font_ibm fx-6 feature-type">
											{ __(
												'E-COMMERCE',
												'better-payment'
											) }
										</span>
										<ul>
											<li
												className={ `bp-flex align-items_center bp-setting_features-list_item ${
													activeTab === 'subscription'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="subscription"
												onClick={ () =>
													setActiveTab(
														'subscription'
													)
												}
											>
												<span className="bp-setting_features-logo">
													<svg
														width="20"
														height="20"
														viewBox="0 0 24 24"
														fill="none"
														stroke="#6b63f6"
														strokeWidth="2"
														strokeLinecap="round"
														strokeLinejoin="round"
														aria-hidden="true"
														focusable="false"
													>
														<path d="M17 2l4 4-4 4" />
														<path d="M3 11v-1a4 4 0 0 1 4-4h14" />
														<path d="M7 22l-4-4 4-4" />
														<path d="M21 13v1a4 4 0 0 1-4 4H3" />
													</svg>
												</span>
												<span className="bp-font_ibm bp-setting_features-name">
													{ __(
														'Subscription',
														'better-payment'
													) }
												</span>
											</li>
										</ul>
									</li>
									<li className="margin-bottom_20">
										<span className="bp-font_ibm fx-6 feature-type">
											{ __( 'AI', 'better-payment' ) }
										</span>
										<ul>
											<li
												className={ `bp-flex align-items_center bp-setting_features-list_item ${
													activeTab === 'ai'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="ai"
												onClick={ () =>
													setActiveTab( 'ai' )
												}
											>
												<span className="bp-setting_features-logo">
													<svg
														width="20"
														height="20"
														viewBox="0 0 24 24"
														fill="#6b63f6"
														aria-hidden="true"
														focusable="false"
													>
														<path d="M11 2.25a.6.6 0 0 1 .57.41l1.34 3.9a3.5 3.5 0 0 0 2.18 2.18l3.9 1.34a.6.6 0 0 1 0 1.14l-3.9 1.34a3.5 3.5 0 0 0-2.18 2.18l-1.34 3.9a.6.6 0 0 1-1.14 0l-1.34-3.9a3.5 3.5 0 0 0-2.18-2.18l-3.9-1.34a.6.6 0 0 1 0-1.14l3.9-1.34a3.5 3.5 0 0 0 2.18-2.18l1.34-3.9A.6.6 0 0 1 11 2.25Z" />
														<path d="M18.5 13.5a.45.45 0 0 1 .43.31l.53 1.53a1.8 1.8 0 0 0 1.12 1.12l1.53.53a.45.45 0 0 1 0 .86l-1.53.53a1.8 1.8 0 0 0-1.12 1.12l-.53 1.53a.45.45 0 0 1-.86 0l-.53-1.53a1.8 1.8 0 0 0-1.12-1.12l-1.53-.53a.45.45 0 0 1 0-.86l1.53-.53a1.8 1.8 0 0 0 1.12-1.12l.53-1.53a.45.45 0 0 1 .43-.31Z" />
													</svg>
												</span>
												<span className="bp-font_ibm bp-setting_features-name">
													{ __(
														'AI Assistant',
														'better-payment'
													) }
												</span>
											</li>
										</ul>
									</li>
								</ul>
							</div>
							<div className="bp-col bp-custome_col-10">
								<div className="bp-setting_features-list_content">
									<div
										className={ `setting-tab-content ${
											activeTab === 'general'
												? 'bp-tab_active'
												: ''
										}` }
										id="general"
									>
										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ ' ' }
													{ __(
														'Currency',
														'better-payment'
													) }{ ' ' }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Select a default currency for transactions from 30+ options. You can update this setting later using the Payment Form in Elementor & Gutenberg.',
														'better-payment'
													) }{ ' ' }
													&nbsp;
													<a
														href={
															'//betterpayment.co/docs/set-up-multiple-currencies-in-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<select
													name="currency"
													className="bp-font_ibm fx-4"
													id="currency"
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_general_currency',
															e.target.value
														)
													}
													value={
														settings?.better_payment_settings_general_general_currency ||
														''
													}
												>
													<option value="" disabled>
														{ __(
															'Select Currency',
															'better-payment'
														) }
													</option>
													{ Object.keys(
														currencies
													).map( ( key ) => (
														<option
															key={ key }
															value={ key }
														>
															{ key }
														</option>
													) ) }
												</select>
											</div>
										</div>

										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'User Dashboard',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable this widget and let your customers preview transactions, subscriptions, take action, unsubscribe, and more.',
														'better-payment'
													) }{ ' ' }
													&nbsp;
													<a
														href={
															'//betterpayment.co/docs/user-dashboard-using-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_user_dashboard ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_user_dashboard ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_user_dashboard ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_user_dashboard',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'Email Notification',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable email notification to provide transaction alerts and customize the appearance. It notifies the website admin and the customer.',
														'better-payment'
													) }{ ' ' }
													&nbsp;
													<a
														href={
															'//betterpayment.co/docs/email-notifications-in-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_email ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_email ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_email ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_email',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'Fundraising Campaign',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable this widget to create fundraising campaigns seamlessly in Elementor builder. Design the campaign according to your needs.',
														'better-payment'
													) }{ ' ' }
													&nbsp;
													<a
														href={
															'//betterpayment.co/docs/configure-fundraising-campaign-in-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_fundraising_campaign ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_fundraising_campaign ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_fundraising_campaign ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_fundraising_campaign',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'paypal'
												? 'bp-tab_active'
												: ''
										}` }
										id="paypal"
									>
										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'PayPal',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable PayPal if you want your customers to make transactions using PayPal. For configuration, ',
														'better-payment'
													) }
													<a
														href={
															'//betterpayment.co/docs/set-up-paypal-account-with-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_paypal ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_paypal ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_paypal ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_paypal',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<ul className="margin-bottom_24 bp-mode_tab">
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_paypal_live_mode ===
													'yes'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="liveMood"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_paypal_live_mode',
														'yes'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Live Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_paypal_live_mode ===
													'yes' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_paypal_live_mode ===
													'no'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="testMood"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_paypal_live_mode',
														'no'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Test Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_paypal_live_mode ===
													'no' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
										</ul>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_paypal_live_mode ===
												'yes'
													? 'bp-tab_active'
													: ''
											}` }
											id="liveMood"
										>
											<form action="#">
												<div
													className={ `${
														paypalLiveRefundCredentials
															? 'margin-bottom_20 '
															: ''
													}bp-form_content` }
												>
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Business Email',
															'better-payment'
														) }
													</label>
													<input
														type="email"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Email Address"
														value={
															settings?.better_payment_settings_payment_paypal_email
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paypal_email',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'Your PayPal account email address to accept payments via PayPal.',
															'better-payment'
														) }
													</label>
												</div>
												{ paypalLiveRefundCredentials }
											</form>
										</div>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_paypal_live_mode ===
												'no'
													? 'bp-tab_active'
													: ''
											}` }
											id="testMood"
										>
											<form action="#">
												<div
													className={ `${
														paypalTestRefundCredentials
															? 'margin-bottom_20 '
															: ''
													}bp-form_content` }
												>
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Business Email',
															'better-payment'
														) }
													</label>
													<input
														type="email"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Email Address"
														value={
															settings?.better_payment_settings_payment_paypal_email
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paypal_email',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'To accept payments via PayPal, enter your PayPal account email address.',
															'better-payment'
														) }
													</label>
												</div>
												{ paypalTestRefundCredentials }
											</form>
										</div>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'stripe'
												? 'bp-tab_active'
												: ''
										}` }
										id="stripe"
									>
										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'Stripe',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable Stripe if you want your customers to make transactions using Stripe. For configuration, ',
														'better-payment'
													) }
													<a
														href={
															'//betterpayment.co/docs/set-up-stripe-account-with-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_stripe ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_stripe ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_stripe ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_stripe',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<ul className="margin-bottom_24 bp-mode_tab">
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_stripe_live_mode ===
													'yes'
														? 'bp-tab_active'
														: ''
												}` }
												id="liveMode"
												data-tab="liveMoodStripe"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_stripe_live_mode',
														'yes'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Live Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_stripe_live_mode ===
													'yes' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_stripe_live_mode ===
													'no'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="testMoodStripe"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_stripe_live_mode',
														'no'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Test Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_stripe_live_mode ===
													'no' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
										</ul>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_stripe_live_mode ===
												'yes'
													? 'bp-tab_active'
													: ''
											}` }
											id="liveMoodStripe"
										>
											<form action="#">
												<div className=" margin-bottom_20 bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Live Public Key',
															'better-payment'
														) }
													</label>
													<input
														type="text"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Public Key"
														value={
															settings?.better_payment_settings_payment_stripe_live_public
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_stripe_live_public',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'A Stripe live Public Key is required to make payments via Stripe. For more help, visit ',
															'better-payment'
														) }
														<a
															href={
																'https://docs.stripe.com/keys'
															}
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href={
																'//betterpayment.co/docs/set-up-stripe-account-with-better-payment/'
															}
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
													</label>
												</div>

												<div className="bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Live Secret Key',
															'better-payment'
														) }
													</label>
													<input
														type="password"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Secret Key"
														value={
															settings?.better_payment_settings_payment_stripe_live_secret
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_stripe_live_secret',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'A Stripe live Secret Key is required to make payments via Stripe. For more help, visit ',
															'better-payment'
														) }
														<a
															href={
																'https://docs.stripe.com/keys'
															}
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href={
																'//betterpayment.co/docs/set-up-stripe-account-with-better-payment/'
															}
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
													</label>
												</div>
											</form>
										</div>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_stripe_live_mode ===
												'no'
													? 'bp-tab_active'
													: ''
											}` }
											id="testMoodStripe"
										>
											<form action="#">
												<div className=" margin-bottom_20 bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Test Public Key',
															'better-payment'
														) }
													</label>
													<input
														type="text"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Public Key"
														value={
															settings?.better_payment_settings_payment_stripe_test_public
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_stripe_test_public',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'A Stripe test Public Key is required to make payments via Stripe. For more help, visit ',
															'better-payment'
														) }
														<a
															href="https://docs.stripe.com/keys"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="//betterpayment.co/docs/set-up-stripe-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>

												<div className="bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Test Secret Key',
															'better-payment'
														) }
													</label>
													<input
														type="password"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Secret Key"
														value={
															settings?.better_payment_settings_payment_stripe_test_secret
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_stripe_test_secret',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'Stripe test Secret Key is required to make payments via Stripe. For more help, visit ',
															'better-payment'
														) }
														<a
															href="https://docs.stripe.com/keys"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="//betterpayment.co/docs/set-up-stripe-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>
											</form>
										</div>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'paystack'
												? 'bp-tab_active'
												: ''
										}` }
										id="paystack"
									>
										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'Paystack',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Enable Paystack to offer your customers flexible payment options and allow payments via Paystack on every payment form. For configuration, ',
														'better-payment'
													) }
													<a
														href={
															'//betterpayment.co/docs/set-up-paystack-account-with-better-payment/'
														}
														target="_blank"
														className="email-notification_link"
													>
														{ __(
															'See Documentation',
															'better-payment'
														) }
													</a>
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_general_general_paystack ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_general_general_paystack ===
														'yes'
															? 'Enabled'
															: 'Disabled' }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_general_general_paystack ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_general_paystack',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<ul className="margin-bottom_24 bp-mode_tab">
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_paystack_live_mode ===
													'yes'
														? 'bp-tab_active'
														: ''
												}` }
												id="liveMode"
												data-tab="liveMoodPaystack"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_paystack_live_mode',
														'yes'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Live Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_paystack_live_mode ===
													'yes' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
											<li
												className={ `bp-font_ibm fx-4 bp-mode_tab-item ${
													settings?.better_payment_settings_payment_paystack_live_mode ===
													'no'
														? 'bp-tab_active'
														: ''
												}` }
												data-tab="testMoodPaystack"
												onClick={ () =>
													handleSettingChange(
														'better_payment_settings_payment_paystack_live_mode',
														'no'
													)
												}
											>
												<span>
													{ ' ' }
													{ __(
														'Test Mode',
														'better-payment'
													) }
												</span>
												{ savedSettings?.better_payment_settings_payment_paystack_live_mode ===
													'no' && (
													<span className="bp-save-and-active-tab"></span>
												) }
											</li>
										</ul>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_paystack_live_mode ===
												'yes'
													? 'bp-tab_active'
													: ''
											}` }
											id="liveMoodPaystack"
										>
											<form action="#">
												<div className=" margin-bottom_20 bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Live Public Key',
															'better-payment'
														) }
													</label>
													<input
														type="text"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Public Key"
														value={
															settings?.better_payment_settings_payment_paystack_live_public
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paystack_live_public',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'This live Public Key is required to make payments via Paystack. For more help, visit ',
															'better-payment'
														) }{ ' ' }
														&nbsp;
														<a
															href="https://support.paystack.com/en/articles/2123458"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="https://betterpayment.co/docs/set-up-paystack-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>

												<div className="bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Live Secret Key',
															'better-payment'
														) }
													</label>
													<input
														type="password"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Secret Key"
														value={
															settings?.better_payment_settings_payment_paystack_live_secret
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paystack_live_secret',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'This live Public Key is required to make payments via Paystack. For more help, visit ',
															'better-payment'
														) }{ ' ' }
														&nbsp;
														<a
															href="https://support.paystack.com/en/articles/2123458"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="https://betterpayment.co/docs/set-up-paystack-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>
											</form>
										</div>

										<div
											className={ `bp-mode_tabs-content ${
												settings?.better_payment_settings_payment_paystack_live_mode ===
												'no'
													? 'bp-tab_active'
													: ''
											}` }
											id="testMoodPaystack"
										>
											<form action="#">
												<div className=" margin-bottom_20 bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Test Public Key',
															'better-payment'
														) }
													</label>
													<input
														type="text"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Public Key"
														value={
															settings?.better_payment_settings_payment_paystack_test_public
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paystack_test_public',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'This test Public Key is required to make payments via Paystack. For more help, visit ',
															'better-payment'
														) }
														<a
															href="https://support.paystack.com/en/articles/2123458"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="https://betterpayment.co/docs/set-up-paystack-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>

												<div className="bp-form_content">
													<label className="bp-font_ibm  bp-content_header">
														{ __(
															'Test Secret Key',
															'better-payment'
														) }
													</label>
													<input
														type="password"
														className=" bp-font_ibm fx-4 bp-form_control"
														placeholder="Secret Key"
														value={
															settings?.better_payment_settings_payment_paystack_test_secret
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_payment_paystack_test_secret',
																e.target.value
															)
														}
													/>
													<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
														{ __(
															'This test Public Key is required to make payments via Paystack. For more help, visit ',
															'better-payment'
														) }
														<a
															href="https://support.paystack.com/en/articles/2123458"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'Developer Guide',
																'better-payment'
															) }
														</a>
														&nbsp;or&nbsp;
														<a
															href="https://betterpayment.co/docs/set-up-paystack-account-with-better-payment/"
															target="_blank"
															className="email-notification_link"
														>
															{ __(
																'See Documentation',
																'better-payment'
															) }
														</a>
														.
													</label>
												</div>
											</form>
										</div>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'admin'
												? 'bp-tab_active'
												: ''
										}` }
										id="admin"
									>
										<form action="#">
											<div className=" margin-bottom_20 bp-form_content">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'To',
														'better-payment'
													) }
												</label>
												<input
													type="email"
													className=" bp-font_ibm fx-4 bp-form_control"
													placeholder="Email Address"
													value={
														settings?.better_payment_settings_general_email_to
													}
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_email_to',
															e.target.value
														)
													}
												/>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Enter the website admin email address here. This email will be used to send an email notification for each transaction.',
														'better-payment'
													) }
												</label>
											</div>
											<div className=" margin-bottom_20 bp-form_content">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'Subject',
														'better-payment'
													) }
												</label>
												<input
													type="email"
													className=" bp-font_ibm fx-4 bp-form_control"
													placeholder="Subject"
													value={
														settings?.better_payment_settings_general_email_subject
													}
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_email_subject',
															e.target.value
														)
													}
												/>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Email subject for the admin email notification.',
														'better-payment'
													) }
												</label>
											</div>

											<div className="bp-form_content margin-bottom_20">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'Message',
														'better-payment'
													) }
												</label>
												<textarea
													type="text"
													className=" bp-font_ibm fx-4 bp-form_control bp-message_box"
													placeholder="Your message"
													value={
														settings?.better_payment_settings_general_email_message
													}
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_email_message',
															e.target.value
														)
													}
												></textarea>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Write email body for the admin email notification.',
														'better-payment'
													) }
												</label>
											</div>

											<button
												className="bp-btn bp-font_ibm margin-bottom_20 fx-4 form-submit_btn"
												onClick={ ( e ) => {
													e.preventDefault();
													setAdminAdditional(
														! adminAdditional
													);
												} }
											>
												<span>
													{ __(
														'Additional Headers',
														'better-payment'
													) }
													&nbsp;
												</span>
												<span>
													<svg
														width="18"
														height="18"
														viewBox="0 0 18 18"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															d="M9 3.75V14.25"
															strokeLinecap="round"
															strokeLinejoin="round"
														></path>
														<path
															d="M3.74902 9H14.249"
															strokeLinecap="round"
															strokeLinejoin="round"
														></path>
													</svg>
												</span>
											</button>

											{ adminAdditional && (
												<>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'From Name',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Enter Name"
															value={
																settings?.better_payment_settings_general_email_from_name
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_from_name',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'From name that will be used in the email headers.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'From Email',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_from_email
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_from_email',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as From Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Reply-To',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_reply_to
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_reply_to',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Reply-To Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Cc',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_cc
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_cc',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Cc Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Bcc',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_bcc
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_bcc',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Bcc Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Send As',
																'better-payment'
															) }
														</label>
														<div className="margin-bottom_8 bp-down-arrow-select-wrapper">
															<select
																className=" bp-font_ibm fx-4 margin-0 bp-form_control bp-down-arrow"
																value={
																	settings?.better_payment_settings_general_email_send_as
																}
																onChange={ (
																	e
																) =>
																	handleSettingChange(
																		'better_payment_settings_general_email_send_as',
																		e.target
																			.value
																	)
																}
															>
																<option value="html">
																	{ __(
																		'Html',
																		'better-payment'
																	) }
																</option>
																<option value="plain">
																	{ __(
																		'Plain',
																		'better-payment'
																	) }
																</option>
															</select>
														</div>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Html helps to send html markup in the email body. Select plain if you just want plain text in the email body.',
																'better-payment'
															) }
														</label>
													</div>
												</>
											) }
										</form>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'customer'
												? 'bp-tab_active'
												: ''
										}` }
										id="customer"
									>
										<form action="#">
											<div className=" margin-bottom_20 bp-form_content">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'To',
														'better-payment'
													) }
												</label>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Customer email addresses will be auto fetched from payment form and it will be used to send email notifications for each transaction.',
														'better-payment'
													) }
												</label>
											</div>
											<div className=" margin-bottom_20 bp-form_content">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'Subject',
														'better-payment'
													) }
												</label>
												<input
													type="email"
													className=" bp-font_ibm fx-4 bp-form_control"
													placeholder="Subject"
													value={
														settings?.better_payment_settings_general_email_customer_subject
													}
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_email_customer_subject',
															e.target.value
														)
													}
												/>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Enter email subject for the customer (who makes payments) email notification.',
														'better-payment'
													) }
												</label>
											</div>

											<div className="bp-form_content margin-bottom_20">
												<label className="bp-font_ibm  bp-content_header">
													{ __(
														'Message',
														'better-payment'
													) }
												</label>
												<textarea
													type="text"
													className=" bp-font_ibm fx-4 bp-form_control bp-message_box"
													placeholder="Your message"
													value={
														settings?.better_payment_settings_general_email_customer_message
													}
													onChange={ ( e ) =>
														handleSettingChange(
															'better_payment_settings_general_email_customer_message',
															e.target.value
														)
													}
												></textarea>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Write email body for the customer email notification.',
														'better-payment'
													) }
												</label>
											</div>

											<button
												className="bp-btn bp-font_ibm  margin-bottom_20 fx-4 form-submit_btn"
												onClick={ ( e ) => {
													e.preventDefault();
													setCustomerAdditional(
														! customerAdditional
													);
												} }
											>
												<span>
													{ __(
														'Additional Headers',
														'better-payment'
													) }
													&nbsp;
												</span>
												<span>
													<svg
														width="18"
														height="18"
														viewBox="0 0 18 18"
														fill="none"
														xmlns="http://www.w3.org/2000/svg"
													>
														<path
															d="M9 3.75V14.25"
															strokeLinecap="round"
															strokeLinejoin="round"
														></path>
														<path
															d="M3.74902 9H14.249"
															strokeLinecap="round"
															strokeLinejoin="round"
														></path>
													</svg>
												</span>
											</button>
											{ customerAdditional && (
												<>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'From Name',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Enter Name"
															value={
																settings?.better_payment_settings_general_email_customer_from_name
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_customer_from_name',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'From name that will be used in the email headers.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'From Email',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_customer_from_email
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_customer_from_email',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as From Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Reply-To',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_customer_reply_to
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_customer_reply_to',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Reply-To Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Cc',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_customer_cc
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_customer_cc',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Cc Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Bcc',
																'better-payment'
															) }
														</label>
														<input
															type="email"
															className=" bp-font_ibm fx-4 bp-form_control"
															placeholder="Email Address"
															value={
																settings?.better_payment_settings_general_email_customer_bcc
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_general_email_customer_bcc',
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Email address that will be displayed in the email header as Bcc Email.',
																'better-payment'
															) }
														</label>
													</div>
													<div className=" margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm  bp-content_header">
															{ __(
																'Send As',
																'better-payment'
															) }
														</label>
														<div className="margin-bottom_8 bp-down-arrow-select-wrapper">
															<select
																className=" bp-font_ibm fx-4 margin-0 bp-form_control bp-down-arrow"
																value={
																	settings?.better_payment_settings_general_email_send_as_customer
																}
																onChange={ (
																	e
																) =>
																	handleSettingChange(
																		'better_payment_settings_general_email_send_as_customer',
																		e.target
																			.value
																	)
																}
															>
																<option value="html">
																	{ __(
																		'Html',
																		'better-payment'
																	) }
																</option>
																<option value="plain">
																	{ __(
																		'Plain',
																		'better-payment'
																	) }
																</option>
															</select>
														</div>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Html helps to send html markup in the email body. Select plain if you just want plain text in the email body.',
																'better-payment'
															) }
														</label>
													</div>
												</>
											) }
										</form>
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'subscription'
												? 'bp-tab_active'
												: ''
										}` }
										id="subscription"
									>
										{ ( () => {
											const userRoles =
												window.betterPaymentAdmin
													?.userRoles ?? {};
											// `customer` is WooCommerce's role, so
											// it is only the default where it
											// exists (DB::default_inactive_role()).
											const defaultInactiveRole =
												window.betterPaymentAdmin
													?.defaultInactiveRole ??
												'subscriber';
											// Keep a saved role visible even if
											// it no longer exists on the site
											// (e.g. one a membership plugin
											// removed), so the select doesn't
											// silently show the wrong value. The
											// old 'customer' default on a site
											// without WooCommerce never gets here:
											// DB::get_settings() resolves it. utils/roleOptions.js
											// humanises that synthetic label —
											// it used to be the raw slug.
											const withCurrentRole = (
												current
											) =>
												roleOptions(
													userRoles,
													current
												);
											const toggleRow = (
												key,
												title,
												description
											) => (
												<div
													className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features"
													key={ key }
												>
													<div className="bp-text_wrapper">
														<h4 className="bp-font_ibm bp-content_header">
															{ title }
														</h4>
														<p className="bp-font_ibm fx-5 bp-content_sub-header">
															{ description }
														</p>
													</div>
													<div className="bp-content_wrapper">
														<form
															action="#"
															className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
																settings?.[
																	key
																] === 'yes'
																	? 'enabled_switch'
																	: ''
															}` }
														>
															<label className="bp-font_ibm fx-4 bp-content_status">
																{ settings?.[
																	key
																] === 'yes'
																	? __(
																			'Enabled',
																			'better-payment'
																	  )
																	: __(
																			'Disabled',
																			'better-payment'
																	  ) }
															</label>
															<label className="bp-toggle_switch">
																<input
																	type="checkbox"
																	className="bp-toggle_switch-checkbox"
																	checked={
																		settings?.[
																			key
																		] ===
																		'yes'
																	}
																	onChange={ (
																		e
																	) =>
																		handleSettingChange(
																			key,
																			e
																				.target
																				.checked
																				? 'yes'
																				: 'no'
																		)
																	}
																/>
																<span className="bp-slider bp-round"></span>
															</label>
														</form>
													</div>
												</div>
											);
											// Inline row, same design as the
											// General tab's Currency setting:
											// title + description on the left,
											// the select on the right.
											const selectRow = (
												key,
												title,
												description,
												options,
												fallback
											) => (
												<div
													className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features"
													key={ key }
												>
													<div className="bp-text_wrapper">
														<h4 className="bp-font_ibm bp-content_header">
															{ title }
														</h4>
														<p className="bp-font_ibm fx-5 bp-content_sub-header">
															{ description }
														</p>
													</div>
													<div className="bp-content_wrapper">
														<select
															className="bp-font_ibm fx-4"
															value={
																settings?.[
																	key
																] || fallback
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	key,
																	e.target
																		.value
																)
															}
														>
															{ Object.entries(
																options
															).map(
																( [
																	value,
																	label,
																] ) => (
																	<option
																		key={
																			value
																		}
																		value={
																			value
																		}
																	>
																		{
																			label
																		}
																	</option>
																)
															) }
														</select>
													</div>
												</div>
											);
											return (
												<>
													{ selectRow(
														'better_payment_settings_ecommerce_subscription_renewal_process',
														__(
															'Renewal Mode',
															'better-payment'
														),
														__(
															'How subscriptions renew after they expire — charged automatically, or renewed manually by the customer.',
															'better-payment'
														),
														{
															auto: __(
																'Automatic',
																'better-payment'
															),
															manual: __(
																'Manual',
																'better-payment'
															),
														},
														'auto'
													) }
													{ toggleRow(
														'better_payment_settings_ecommerce_subscription_stripe_auto_renew',
														__(
															'Automatic Stripe Charging',
															'better-payment'
														),
														__(
															'Allow Stripe to charge subscription renewals automatically.',
															'better-payment'
														)
													) }
													{ toggleRow(
														'better_payment_settings_ecommerce_subscription_auto_renewal_toggle',
														__(
															'Customer Auto-Renew Control',
															'better-payment'
														),
														__(
															'Let customers switch their own renewals on or off from the My Account subscription view.',
															'better-payment'
														)
													) }
													{ toggleRow(
														'better_payment_settings_ecommerce_subscription_myaccount_tab',
														__(
															'My Account Subscriptions Tab',
															'better-payment'
														),
														__(
															'Show a Subscriptions tab on the WooCommerce My Account page where customers view and manage their subscriptions.',
															'better-payment'
														)
													) }
													{ selectRow(
														'better_payment_settings_ecommerce_subscription_active_role',
														__(
															'Role While Subscribed',
															'better-payment'
														),
														__(
															'Role assigned to a customer while their subscription is active.',
															'better-payment'
														),
														withCurrentRole(
															settings?.better_payment_settings_ecommerce_subscription_active_role ||
																'subscriber'
														),
														'subscriber'
													) }
													{ selectRow(
														'better_payment_settings_ecommerce_subscription_inactive_role',
														__(
															'Role After Subscription Ends',
															'better-payment'
														),
														__(
															'Role the customer falls back to once the subscription is cancelled or completed.',
															'better-payment'
														),
														withCurrentRole(
															settings?.better_payment_settings_ecommerce_subscription_inactive_role ||
																defaultInactiveRole
														),
														defaultInactiveRole
													) }
												</>
											);
										} )() }
									</div>

									<div
										className={ `setting-tab-content ${
											activeTab === 'ai'
												? 'bp-tab_active'
												: ''
										}` }
										id="ai"
									>
										<div className="bp-flex justify-content-space_between align-items_center margin-bottom_20 bp-general_features">
											<div className="bp-text_wrapper">
												<h4 className="bp-font_ibm bp-content_header">
													{ __(
														'AI Assistant',
														'better-payment'
													) }
												</h4>
												<p className="bp-font_ibm fx-5 bp-content_sub-header">
													{ __(
														'Generate and refine fundraising campaigns with AI directly in the Campaign Builder. Choose your AI provider and enter your API key to get started.',
														'better-payment'
													) }
												</p>
											</div>
											<div className="bp-content_wrapper">
												<form
													action="#"
													className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
														settings?.better_payment_settings_ai_enabled ===
														'yes'
															? 'enabled_switch'
															: ''
													}` }
												>
													<label className="bp-font_ibm fx-4 bp-content_status">
														{ settings?.better_payment_settings_ai_enabled ===
														'yes'
															? __(
																	'Enabled',
																	'better-payment'
															  )
															: __(
																	'Disabled',
																	'better-payment'
															  ) }
													</label>
													<label className="bp-toggle_switch">
														<input
															type="checkbox"
															className="bp-toggle_switch-checkbox"
															checked={
																settings?.better_payment_settings_ai_enabled ===
																'yes'
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	'better_payment_settings_ai_enabled',
																	e.target
																		.checked
																		? 'yes'
																		: 'no'
																)
															}
														/>
														<span className="bp-slider bp-round"></span>
													</label>
												</form>
											</div>
										</div>

										<form action="#">
											<div className="margin-bottom_20 bp-form_content">
												<label className="bp-font_ibm bp-content_header">
													{ __(
														'AI Provider',
														'better-payment'
													) }
												</label>
												<div className="margin-bottom_8 bp-down-arrow-select-wrapper">
													<select
														className="bp-font_ibm fx-4 margin-0 bp-form_control bp-down-arrow"
														value={
															settings?.better_payment_settings_ai_provider ||
															'openai'
														}
														onChange={ ( e ) =>
															handleSettingChange(
																'better_payment_settings_ai_provider',
																e.target.value
															)
														}
													>
														<option value="openai">
															{ __(
																'OpenAI',
																'better-payment'
															) }
														</option>
														<option value="claude">
															{ __(
																'Claude (Anthropic)',
																'better-payment'
															) }
														</option>
														<option value="gemini">
															{ __(
																'Google Gemini',
																'better-payment'
															) }
														</option>
														<option value="openrouter">
															{ __(
																'OpenRouter',
																'better-payment'
															) }
														</option>
													</select>
												</div>
												<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
													{ __(
														'Choose the AI service used to generate campaign content.',
														'better-payment'
													) }
												</label>
											</div>

											{ ( () => {
												const activeProvider =
													settings?.better_payment_settings_ai_provider ||
													'openai';
												const keyFields = {
													openai: {
														label: __(
															'OpenAI API Key',
															'better-payment'
														),
														name: __(
															'OpenAI',
															'better-payment'
														),
														placeholder: __(
															'Enter your OpenAI API key',
															'better-payment'
														),
														docUrl: 'https://platform.openai.com/api-keys',
													},
													claude: {
														label: __(
															'Claude API Key',
															'better-payment'
														),
														name: __(
															'Anthropic',
															'better-payment'
														),
														placeholder: __(
															'Enter your Claude API key',
															'better-payment'
														),
														docUrl: 'https://console.anthropic.com/settings/keys',
													},
													gemini: {
														label: __(
															'Gemini API Key',
															'better-payment'
														),
														name: __(
															'Google AI Studio',
															'better-payment'
														),
														placeholder: __(
															'Enter your Gemini API key',
															'better-payment'
														),
														docUrl: 'https://aistudio.google.com/app/apikey',
													},
													openrouter: {
														label: __(
															'OpenRouter API Key',
															'better-payment'
														),
														name: __(
															'OpenRouter',
															'better-payment'
														),
														placeholder: __(
															'Enter your OpenRouter API key',
															'better-payment'
														),
														docUrl: 'https://openrouter.ai/keys',
													},
												};
												const field =
													keyFields[
														activeProvider
													] || keyFields.openai;
												const key = `better_payment_settings_ai_api_key_${ activeProvider }`;
												return (
													<div className="margin-bottom_20 bp-form_content">
														<label className="bp-font_ibm bp-content_header">
															{ field.label }
														</label>
														<input
															type="password"
															autoComplete="off"
															className="bp-font_ibm fx-4 bp-form_control"
															placeholder={
																field.placeholder
															}
															value={
																settings?.[
																	key
																] || ''
															}
															onChange={ ( e ) =>
																handleSettingChange(
																	key,
																	e.target
																		.value
																)
															}
														/>
														<label className="bp-font_ibm fx-6 bp-form_control-info bp-content_sub-header">
															{ __(
																'Enter your',
																'better-payment'
															) }{ ' ' }
															{ field.label }{ ' ' }
															{ __(
																'to connect the AI Assistant. Need an API key?',
																'better-payment'
															) }{ ' ' }
															{ __(
																'Get one from',
																'better-payment'
															) }{ ' ' }
															<a
																href={
																	field.docUrl
																}
																target="_blank"
																rel="noopener noreferrer"
																className="email-notification_link"
															>
																{ field.name }
															</a>
															.
														</label>
													</div>
												);
											} )() }
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<FooterCards />
			</div>
		</div>
	);
};

export default Settings;
