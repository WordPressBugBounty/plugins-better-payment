import { __ } from '@wordpress/i18n';
import { getGlobalCurrencySymbol } from '../utils/currency';
import ThemeColors from './ThemeColors';

/**
 * Settings tab — full-width campaign settings view.
 *
 * Sections:
 *   General Settings — goal, end date, CSS class
 *   Appearance       — the campaign background (it was the Customize panel's
 *                      Advanced tab until that tab became per-element settings)
 */
export default function SettingsTab( { meta, dispatch } ) {
	const update = ( patch ) =>
		dispatch( { type: 'UPDATE_META', payload: patch } );
	const currencySymbol = getGlobalCurrencySymbol();

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
