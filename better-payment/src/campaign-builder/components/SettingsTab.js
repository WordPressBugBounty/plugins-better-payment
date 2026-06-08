import { __ } from '@wordpress/i18n';
import { getGlobalCurrencySymbol } from '../utils/currency';

/**
 * Settings tab — full-width campaign settings view.
 *
 * Two collapsible sections matching the PRD:
 *   7.1 General Settings
 *   7.2 Donation Options
 */
export default function SettingsTab( { meta, dispatch } ) {
    const update = ( patch ) => dispatch( { type: 'UPDATE_META', payload: patch } );
    const currencySymbol = getGlobalCurrencySymbol();

    return (
        <div className="bp-settings-tab">

            {/* 7.1 General Settings */}
            <div className="bp-settings-section">
                <div className="bp-settings-section__header">
                    <h3>{ __( 'General Settings', 'better-payment' ) }</h3>
                </div>
                <div className="bp-settings-section__body">

                    <div className="bp-settings-row">
                        <div className="bp-settings-col bp-settings-col--full">
                            <label>{ __( 'Campaign Goal Amount', 'better-payment' ) }</label>
                            <div className="bp-settings-prefix-input">
                                <span className="bp-settings-prefix">{ currencySymbol }</span>
                                <input
                                    type="number" min="0" step="1"
                                    value={ meta.bpc_goal_amount }
                                    onChange={ ( e ) => update( { bpc_goal_amount: e.target.value } ) }
                                    placeholder="0"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="bp-settings-row">
                        <div className="bp-settings-col bp-settings-col--full">
                            <label>{ __( 'Ending Date', 'better-payment' ) }</label>
                            <input
                                type="date"
                                value={ meta.bpc_end_date }
                                onChange={ ( e ) => update( { bpc_end_date: e.target.value } ) }
                                onClick={ ( e ) => e.target.showPicker?.() }
                            />
                        </div>
                    </div>

                    <div className="bp-settings-row">
                        <div className="bp-settings-col bp-settings-col--full">
                            <label>{ __( 'Custom CSS Class', 'better-payment' ) }</label>
                            <input
                                type="text"
                                value={ meta.bpc_css_class || '' }
                                onChange={ ( e ) => update( { bpc_css_class: e.target.value } ) }
                                placeholder="my-campaign-class"
                            />
                        </div>
                    </div>

                </div>
            </div>

            {/* 7.2 Donation Options */}
            <div className="bp-settings-section">
                <div className="bp-settings-section__header">
                    <h3>{ __( 'Donation Options', 'better-payment' ) }</h3>
                </div>
                <div className="bp-settings-section__body">

                    <div className="bp-settings-row">
                        <div className="bp-settings-col bp-settings-col--full">
                            <label>{ __( 'Minimum Donation Amount', 'better-payment' ) }</label>
                            <div className="bp-settings-prefix-input">
                                <span className="bp-settings-prefix">{ currencySymbol }</span>
                                <input
                                    type="number" min="0" step="1"
                                    value={ meta.bpc_minimum_amount || '' }
                                    onChange={ ( e ) => update( { bpc_minimum_amount: e.target.value } ) }
                                    placeholder="0"
                                />
                            </div>
                            <p className="bp-settings-hint bp-settings-hint--italic">
                                { __( 'Leave empty to allow no restrictions on how small the donation can be.', 'better-payment' ) }
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    );
}
