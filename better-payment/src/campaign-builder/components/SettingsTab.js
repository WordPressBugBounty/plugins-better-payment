import { __, sprintf } from '@wordpress/i18n';
import {
	currencyOptions,
	getCampaignCurrencySymbol,
	resolveCampaignCurrency,
	unsupportedGateways,
} from '../utils/currency';
import ThemeColors from './ThemeColors';
import { settingsClosure } from '../utils/lifecycle';

/**
 * Settings tab — full-width campaign settings view.
 *
 * Sections:
 *   General Settings — currency, goal, end date, CSS class
 *   Donations        — Status, close at goal, and what visitors read once the
 *                      campaign stops taking donations (Campaign\CampaignLifecycle).
 *   Appearance       — the campaign background (it was the Customize panel's
 *                      Advanced tab until that tab became per-element settings)
 */
const GATEWAY_NAMES = { paypal: 'PayPal', paystack: 'Paystack' };

export default function SettingsTab( {
	meta,
	dispatch,
	currencyLocked = false,
} ) {
	const update = ( patch ) =>
		dispatch( { type: 'UPDATE_META', payload: patch } );
	const currencySymbol = getCampaignCurrencySymbol();
	const currency = resolveCampaignCurrency( meta );
	const unsupported = unsupportedGateways( currency );
	const closure = settingsClosure( meta );
	const isClosed =
		meta?.bpc_status === 'closed' || meta?.bpc_status === 'ended';

	return (
		<div className="bp-settings-tab">
			{ /* 7.1 General Settings */ }
			<div className="bp-settings-section">
				<div className="bp-settings-section__header">
					<h3>{ __( 'General Settings', 'better-payment' ) }</h3>
				</div>
				<div className="bp-settings-section__body">
					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label htmlFor="bp-settings-currency">
								{ __( 'Currency', 'better-payment' ) }
							</label>
							<select
								id="bp-settings-currency"
								value={ currency }
								disabled={ currencyLocked }
								onChange={ ( e ) =>
									update( { bpc_currency: e.target.value } )
								}
							>
								{ currencyOptions().map( ( option ) => (
									<option
										key={ option.value }
										value={ option.value }
									>
										{ option.label }
									</option>
								) ) }
							</select>
							<p className="bp-settings-hint">
								{ currencyLocked
									? __(
											'This campaign has donations, so its currency can no longer change.',
											'better-payment'
									  )
									: __(
											'The goal and every donation are in this currency. Changing the site currency later does not change it. You can change it until the first donation.',
											'better-payment'
									  ) }
							</p>
							{ unsupported.length > 0 && (
								<p
									className="bp-settings-currency-notice"
									role="status"
								>
									{ sprintf(
										/* translators: 1: gateway names, 2: currency code. */
										__(
											'%1$s cannot take payments in %2$s, so donors cannot pay through it. Choose another currency or another gateway.',
											'better-payment'
										),
										unsupported
											.map(
												( slug ) =>
													GATEWAY_NAMES[ slug ] ??
													slug
											)
											.join( ', ' ),
										currency
									) }
								</p>
							) }
						</div>
					</div>

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label>
								{ __(
									'Campaign Goal Amount',
									'better-payment'
								) }
							</label>
							<div className="bp-settings-prefix-input">
								<span className="bp-settings-prefix">
									{ currencySymbol }
								</span>
								<input
									type="number"
									min="0"
									step="1"
									value={ meta.bpc_goal_amount }
									onChange={ ( e ) =>
										update( {
											bpc_goal_amount: e.target.value,
										} )
									}
									placeholder="0"
								/>
							</div>
						</div>
					</div>

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label>
								{ __( 'Ending Date', 'better-payment' ) }
							</label>
							<input
								type="date"
								value={ meta.bpc_end_date }
								onChange={ ( e ) =>
									update( { bpc_end_date: e.target.value } )
								}
								onClick={ ( e ) => e.target.showPicker?.() }
							/>
							<p className="bp-settings-hint">
								{ __(
									'Donations stop after this day ends (site time). Leave empty for no end date.',
									'better-payment'
								) }
							</p>
						</div>
					</div>

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label>
								{ __( 'Custom CSS Class', 'better-payment' ) }
							</label>
							<input
								type="text"
								value={ meta.bpc_css_class || '' }
								onChange={ ( e ) =>
									update( { bpc_css_class: e.target.value } )
								}
								placeholder="my-campaign-class"
							/>
						</div>
					</div>
				</div>
			</div>

			<div className="bp-settings-section">
				<div className="bp-settings-section__header">
					<h3>{ __( 'Donations', 'better-payment' ) }</h3>
				</div>
				<div className="bp-settings-section__body">
					{ closure && (
						<p
							className="bp-settings-lifecycle-notice"
							role="status"
						>
							{ closure === 'closed_manual'
								? __(
										'This campaign is closed: visitors see the closed message instead of the Donate Button, and payments are refused.',
										'better-payment'
								  )
								: __(
										'The end date has passed: visitors see the closed message instead of the Donate Button, and payments are refused. Move the end date to reopen it.',
										'better-payment'
								  ) }
						</p>
					) }

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label htmlFor="bp-settings-status">
								{ __( 'Status', 'better-payment' ) }
							</label>
							<select
								id="bp-settings-status"
								value={ isClosed ? 'closed' : 'active' }
								onChange={ ( e ) =>
									update( { bpc_status: e.target.value } )
								}
							>
								<option value="active">
									{ __(
										'Active — taking donations',
										'better-payment'
									) }
								</option>
								<option value="closed">
									{ __(
										'Closed — not taking donations',
										'better-payment'
									) }
								</option>
							</select>
						</div>
					</div>

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label
								className="bp-settings-toggle-label"
								htmlFor="bp-settings-close-on-goal"
							>
								<input
									id="bp-settings-close-on-goal"
									type="checkbox"
									checked={
										!! Number(
											meta?.bpc_close_on_goal ?? 0
										)
									}
									onChange={ ( e ) =>
										update( {
											bpc_close_on_goal: e.target.checked
												? 1
												: 0,
										} )
									}
								/>
								<span>
									{ __(
										'Stop taking donations when the goal is reached',
										'better-payment'
									) }
								</span>
							</label>
							<p className="bp-settings-hint">
								{ Number( meta?.bpc_goal_amount ) > 0
									? __(
											'Off: donations keep coming in after the goal.',
											'better-payment'
									  )
									: __(
											'Needs a goal amount above.',
											'better-payment'
									  ) }
							</p>
						</div>
					</div>

					<div className="bp-settings-row">
						<div className="bp-settings-col bp-settings-col--full">
							<label htmlFor="bp-settings-closed-message">
								{ __(
									'Message when not taking donations',
									'better-payment'
								) }
							</label>
							<textarea
								id="bp-settings-closed-message"
								rows={ 2 }
								value={ meta?.bpc_closed_message ?? '' }
								onChange={ ( e ) =>
									update( {
										bpc_closed_message: e.target.value,
									} )
								}
								placeholder={ __(
									'This campaign has ended and is no longer taking donations.',
									'better-payment'
								) }
							/>
							<p className="bp-settings-hint">
								{ __(
									'Shown in place of the Donate Button once the campaign ends or is closed. Leave empty for a default that names the reason.',
									'better-payment'
								) }
							</p>
						</div>
					</div>
				</div>
			</div>

			<div className="bp-settings-section bp-settings-section--appearance">
				<div className="bp-settings-section__header">
					<h3>{ __( 'Appearance', 'better-payment' ) }</h3>
				</div>
				<div className="bp-settings-section__body">
					<ThemeColors meta={ meta } dispatch={ dispatch } />
				</div>
			</div>
		</div>
	);
}
