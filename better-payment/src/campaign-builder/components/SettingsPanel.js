import { __ } from '@wordpress/i18n';
import { getElement } from '../fields/FieldRegistry';

/**
 * Right panel — campaign-level settings when nothing is selected,
 * or schema-driven element settings when an element is selected.
 *
 * Element settings controls are generated from the PHP settingsSchema
 * (localized via betterPaymentCampaignData.elements), so adding a new
 * control to PHP automatically adds it here without touching this file.
 */
export default function SettingsPanel( { meta, layout, selectedElementId, selectedColumnId, dispatch } ) {

    let selectedElement = null;
    if ( selectedElementId && layout?.columns ) {
        for ( const col of layout.columns ) {
            const found = col.elements.find( ( el ) => el.id === selectedElementId );
            if ( found ) { selectedElement = found; break; }
        }
    }

    if ( selectedElement ) {
        const def = getElement( selectedElement.type );
        return (
            <div className="bp-cb-settings-panel">
                <div className="bp-cb-settings-panel__header">
                    <span className={ `dashicons dashicons-${ def?.icon || 'block-default' }` } />
                    <h3 className="bp-cb-panel-heading">
                        { def?.label || __( 'Element Settings', 'better-payment' ) }
                    </h3>
                </div>
                <ElementSettings
                    element={ selectedElement }
                    columnId={ selectedColumnId }
                    schema={ def?.settingsSchema || [] }
                    meta={ meta }
                    dispatch={ dispatch }
                />
            </div>
        );
    }

    return (
        <div className="bp-cb-settings-panel">
            <h3 className="bp-cb-panel-heading">{ __( 'Campaign Settings', 'better-payment' ) }</h3>
            <CampaignMetaSettings meta={ meta } dispatch={ dispatch } />
        </div>
    );
}

// ── Schema-driven element settings ────────────────────────────────────────────

function ElementSettings( { element, columnId, schema, meta, dispatch } ) {
    const updateElement = ( patch ) =>
        dispatch( { type: 'UPDATE_ELEMENT_SETTINGS', elementId: element.id, settings: patch } );
    const updateMeta = ( patch ) =>
        dispatch( { type: 'UPDATE_META', payload: patch } );
    const s = element.settings || {};

    if ( schema.length === 0 ) {
        return <p className="bp-cb-hint">{ __( 'No settings for this element.', 'better-payment' ) }</p>;
    }

    return (
        <div className="bp-cb-settings-form">
            { schema.map( ( control ) => {
                const isMeta = !! control.metaKey;
                const value  = isMeta ? ( meta?.[ control.metaKey ] ?? '' ) : s[ control.key ];
                const onChange = isMeta
                    ? ( val ) => updateMeta( { [ control.metaKey ]: val } )
                    : ( val ) => updateElement( { [ control.key ]: val } );
                return (
                    <SettingsControl
                        key={ control.key }
                        control={ control }
                        value={ value }
                        onChange={ onChange }
                    />
                );
            } ) }
        </div>
    );
}

const ALIGN_OPTIONS = [
    {
        value: 'left',
        title: 'Align left',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="2" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="2" y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
    {
        value: 'center',
        title: 'Align center',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2"   y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="4.5" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="3"   y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
    {
        value: 'right',
        title: 'Align right',
        icon: (
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="4"  width="16" height="2" rx="1" fill="currentColor"/>
                <rect x="7" y="9"  width="11" height="2" rx="1" fill="currentColor"/>
                <rect x="4" y="14" width="14" height="2" rx="1" fill="currentColor"/>
            </svg>
        ),
    },
];

function SettingsControl( { control, value, onChange } ) {
    const { key, label, type, placeholder, min, max, rows, options } = control;
    const resolvedPlaceholder = placeholder || '';

    return (
        <div className="bp-cb-control">
            { type !== 'toggle' && (
                <label className="bp-cb-field-label" htmlFor={ `ctrl-${ key }` }>
                    { label }
                </label>
            ) }

            { type === 'align' && (
                <div className="bp-cb-align-group">
                    { ALIGN_OPTIONS.map( ( opt ) => (
                        <button
                            key={ opt.value }
                            type="button"
                            title={ opt.title }
                            className={ `bp-cb-align-btn${ ( value || 'left' ) === opt.value ? ' is-active' : '' }` }
                            onClick={ () => onChange( opt.value ) }
                        >
                            { opt.icon }
                        </button>
                    ) ) }
                </div>
            ) }

            { type === 'text' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="text"
                    value={ value ?? '' }
                    placeholder={ resolvedPlaceholder }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }

            { type === 'number' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="number"
                    value={ value ?? '' }
                    min={ min }
                    max={ max }
                    onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) }
                />
            ) }

            { type === 'color' && (
                <input
                    id={ `ctrl-${ key }` }
                    type="color"
                    value={ value || '#6b63f6' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }

            { type === 'toggle' && (
                <label className="bp-cb-toggle-label" htmlFor={ `ctrl-${ key }` }>
                    <input
                        id={ `ctrl-${ key }` }
                        type="checkbox"
                        checked={ !! value }
                        onChange={ ( e ) => onChange( e.target.checked ) }
                    />
                    <span>{ label }</span>
                </label>
            ) }

            { type === 'select' && (
                <select
                    id={ `ctrl-${ key }` }
                    value={ value ?? '' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                >
                    { ( options || [] ).map( ( opt ) => (
                        <option key={ opt.value } value={ opt.value }>{ opt.label }</option>
                    ) ) }
                </select>
            ) }

            { type === 'textarea' && (
                <textarea
                    id={ `ctrl-${ key }` }
                    rows={ rows || 4 }
                    value={ value ?? '' }
                    placeholder={ placeholder || '' }
                    onChange={ ( e ) => onChange( e.target.value ) }
                />
            ) }
        </div>
    );
}

// ── Campaign meta settings (campaign-level, shown when nothing selected) ──────

function CampaignMetaSettings( { meta, dispatch } ) {
    const update = ( patch ) => dispatch( { type: 'UPDATE_META', payload: patch } );

    return (
        <div className="bp-cb-settings-form">

            <label className="bp-cb-field-label">{ __( 'Goal Amount', 'better-payment' ) }</label>
            <input
                type="number" min="0" step="1"
                value={ meta.bpc_goal_amount }
                onChange={ ( e ) => update( { bpc_goal_amount: e.target.value } ) }
            />

            <label className="bp-cb-field-label">{ __( 'End Date', 'better-payment' ) }</label>
            <input
                type="date"
                value={ meta.bpc_end_date }
                onChange={ ( e ) => update( { bpc_end_date: e.target.value } ) }
                onClick={ ( e ) => e.target.showPicker?.() }
            />

            <label className="bp-cb-field-label">{ __( 'Donation Page', 'better-payment' ) }</label>
            <input
                type="number" min="0"
                placeholder={ __( 'Page ID', 'better-payment' ) }
                value={ meta.bpc_form_page_id || '' }
                onChange={ ( e ) => update( { bpc_form_page_id: parseInt( e.target.value, 10 ) || 0 } ) }
            />

            <label className="bp-cb-field-label">{ __( 'Primary Color', 'better-payment' ) }</label>
            <input
                type="color"
                value={ meta.bpc_color_primary || '#6b63f6' }
                onChange={ ( e ) => update( { bpc_color_primary: e.target.value } ) }
            />

            <label className="bp-cb-field-label">{ __( 'Status', 'better-payment' ) }</label>
            <select value={ meta.bpc_status } onChange={ ( e ) => update( { bpc_status: e.target.value } ) }>
                <option value="active">{ __( 'Active', 'better-payment' ) }</option>
                <option value="ended">{ __( 'Ended', 'better-payment' ) }</option>
            </select>
        </div>
    );
}
