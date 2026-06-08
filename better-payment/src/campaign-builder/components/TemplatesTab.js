import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// ── Category definitions (below the "All Templates" divider) ──────────────────
const CATEGORIES = [
    { key: 'charity',            label: __( 'Charity', 'better-payment' ) },
    { key: 'club-organizations', label: __( 'Club / Organizations', 'better-payment' ) },
    { key: 'environmental',      label: __( 'Environmental', 'better-payment' ) },
    { key: 'medical',            label: __( 'Medical', 'better-payment' ) },
    { key: 'education',          label: __( 'Youth / Education', 'better-payment' ) },
];

// ── Blank-layout SVG wireframes ───────────────────────────────────────────────
const BLANK_ICONS = {
    'blank-1col': (
        <svg width="120" height="90" viewBox="0 0 120 90" fill="none">
            <rect x="8" y="8" width="104" height="74" rx="4" fill="#f5f5f5" stroke="#e5e7eb" strokeWidth="1.5"/>
            <rect x="16" y="16" width="88" height="10" rx="3" fill="#f59e0b" opacity="0.5"/>
            <rect x="16" y="32" width="88" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="42" width="70" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="52" width="80" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="66" width="30" height="8" rx="2" fill="#f59e0b" opacity="0.7"/>
        </svg>
    ),
    'blank-2col': (
        <svg width="120" height="90" viewBox="0 0 120 90" fill="none">
            <rect x="8" y="8" width="104" height="74" rx="4" fill="#f5f5f5" stroke="#e5e7eb" strokeWidth="1.5"/>
            <rect x="16" y="16" width="58" height="10" rx="3" fill="#f59e0b" opacity="0.5"/>
            <rect x="16" y="32" width="58" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="42" width="46" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="52" width="58" height="6" rx="2" fill="#d1d5db"/>
            <rect x="16" y="66" width="24" height="8" rx="2" fill="#f59e0b" opacity="0.7"/>
            <rect x="82" y="16" width="22" height="58" rx="3" fill="#f59e0b" opacity="0.12" stroke="#f59e0b" strokeWidth="1.2"/>
            <rect x="86" y="22" width="14" height="4" rx="2" fill="#d1d5db"/>
            <rect x="86" y="30" width="10" height="4" rx="2" fill="#d1d5db"/>
            <rect x="86" y="38" width="12" height="4" rx="2" fill="#d1d5db"/>
        </svg>
    ),
    'blank-3col': (
        <svg width="120" height="90" viewBox="0 0 120 90" fill="none">
            <rect x="8" y="8" width="104" height="74" rx="4" fill="#f5f5f5" stroke="#e5e7eb" strokeWidth="1.5"/>
            <rect x="16" y="16" width="28" height="58" rx="3" fill="#f59e0b" opacity="0.12" stroke="#f59e0b" strokeWidth="1.2"/>
            <rect x="19" y="22" width="22" height="4" rx="2" fill="#d1d5db"/>
            <rect x="19" y="30" width="16" height="4" rx="2" fill="#d1d5db"/>
            <rect x="19" y="38" width="20" height="4" rx="2" fill="#d1d5db"/>
            <rect x="50" y="16" width="20" height="58" rx="3" fill="#f59e0b" opacity="0.12" stroke="#f59e0b" strokeWidth="1.2"/>
            <rect x="53" y="22" width="14" height="4" rx="2" fill="#d1d5db"/>
            <rect x="53" y="30" width="10" height="4" rx="2" fill="#d1d5db"/>
            <rect x="53" y="38" width="12" height="4" rx="2" fill="#d1d5db"/>
            <rect x="76" y="16" width="28" height="58" rx="3" fill="#f59e0b" opacity="0.12" stroke="#f59e0b" strokeWidth="1.2"/>
            <rect x="79" y="22" width="22" height="4" rx="2" fill="#d1d5db"/>
            <rect x="79" y="30" width="16" height="4" rx="2" fill="#d1d5db"/>
            <rect x="79" y="38" width="20" height="4" rx="2" fill="#d1d5db"/>
        </svg>
    ),
};

export default function TemplatesTab( {
    dispatch,
    appliedTemplateKey,
    onTemplateApplied,
} ) {
    const data      = window.betterPaymentCampaignData || {};
    const templates = data.templates || [];
    const pluginUrl = ( data.pluginUrl || '' ).replace( /\/$/, '' );

    const blankTemplates    = templates.filter( ( t ) => t.category === 'blank' );
    const prebuiltTemplates = templates.filter( ( t ) => t.category === 'prebuilt' );

    const [ search, setSearch ]               = useState( '' );
    const [ activeCategory, setActiveCategory ] = useState( 'all' );
    const [ lightboxKey, setLightboxKey ]     = useState( null );
    const [ confirmTemplate, setConfirmTemplate ] = useState( null ); // template pending confirmation

    const handleSelect = ( template ) => {
        // If a template is already applied and the user is switching to a different one,
        // show a confirmation modal before overwriting the current layout.
        if ( appliedTemplateKey && template.key !== appliedTemplateKey ) {
            setConfirmTemplate( template );
            return;
        }
        applyTemplate( template );
    };

    const applyTemplate = ( template ) => {
        dispatch( { type: 'APPLY_TEMPLATE', template } );
        onTemplateApplied();
    };

    const filteredPrebuilt = prebuiltTemplates.filter( ( tpl ) => {
        const matchesCat    = activeCategory === 'all' || ( tpl.tags || [] ).includes( activeCategory );
        const matchesSearch = ! search || tpl.label.toLowerCase().includes( search.toLowerCase() ) || ( tpl.description || '' ).toLowerCase().includes( search.toLowerCase() );
        return matchesCat && matchesSearch;
    } );

    const filteredBlank = blankTemplates.filter( ( tpl ) => {
        if ( activeCategory !== 'all' ) return false;
        return ! search || tpl.label.toLowerCase().includes( search.toLowerCase() );
    } );

    const getCategoryCount = ( key ) =>
        prebuiltTemplates.filter( ( t ) => ( t.tags || [] ).includes( key ) ).length;

    const currentLabel = appliedTemplateKey
        ? ( templates.find( ( t ) => t.key === appliedTemplateKey )?.label || '' )
        : null;

    const getPreviewImageUrl = ( tpl ) => {
        if ( ! tpl.preview_image ) return '';
        return `${ pluginUrl }/${ tpl.preview_image }`;
    };

    const lightboxTpl = lightboxKey ? templates.find( ( t ) => t.key === lightboxKey ) : null;

    return (
        <div className="bp-templates-tab">

            {/* Page header */}
            <div className="bp-templates-tab__page-header">
                <h2>{ __( 'Select A Template', 'better-payment' ) }</h2>
                { currentLabel && (
                    <p>
                        { __( 'You are currently using the', 'better-payment' ) }{ ' ' }
                        <strong>"{ currentLabel }"</strong>{ ' ' }
                        { __( 'template. Changing a template now might result in losing fields and data. Proceed carefully.', 'better-payment' ) }
                    </p>
                ) }
            </div>

            <div className="bp-templates-tab__body">

                {/* Sidebar */}
                <div className="bp-templates-tab__sidebar">

                    <div className="bp-templates-tab__search">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <circle cx="6" cy="6" r="4.5" stroke="#8a8fa6" strokeWidth="1.3"/>
                            <path d="M9.5 9.5L12.5 12.5" stroke="#8a8fa6" strokeWidth="1.3" strokeLinecap="round"/>
                        </svg>
                        <input
                            type="text"
                            placeholder={ __( 'Search Templates', 'better-payment' ) }
                            value={ search }
                            onChange={ ( e ) => setSearch( e.target.value ) }
                        />
                    </div>

                    <button
                        className={ `bp-templates-tab__all-cat${ activeCategory === 'all' ? ' is-active' : '' }` }
                        onClick={ () => setActiveCategory( 'all' ) }
                    >
                        <span>{ __( 'All Templates', 'better-payment' ) }</span>
                        <span className="bp-templates-tab__cat-count">{ templates.length }</span>
                    </button>

                    <hr className="bp-templates-tab__divider" />

                    <div className="bp-templates-tab__cats">
                        { CATEGORIES.map( ( cat ) => {
                            const count = getCategoryCount( cat.key );
                            if ( count === 0 ) return null;
                            return (
                                <button
                                    key={ cat.key }
                                    className={ `bp-templates-tab__cat${ activeCategory === cat.key ? ' is-active' : '' }` }
                                    onClick={ () => setActiveCategory( cat.key ) }
                                >
                                    <span>{ cat.label }</span>
                                    <span className="bp-templates-tab__cat-num">{ count }</span>
                                </button>
                            );
                        } ) }
                    </div>

                    <div className="bp-templates-tab__sidebar-cta">
                        <strong>{ __( "Don’t See The Template You’re Looking For?", 'better-payment' ) }</strong>
                        <p>
                            <a href="https://wpdeveloper.com/support/" target='_blank'>{ __( 'Share', 'better-payment' ) }</a>
                            { ' ' }{ __( 'your suggestions for future additions.', 'better-payment' ) }
                        </p>
                    </div>
                </div>

                {/* Main grid */}
                <div className="bp-templates-tab__main">

                    { filteredBlank.length > 0 && (
                        <div className="bp-templates-tab__section">
                            { activeCategory === 'all' && ! search && (
                                <p className="bp-templates-tab__section-heading">
                                    { __( 'Start with a blank template', 'better-payment' ) }
                                </p>
                            ) }
                            <div className="bp-templates-tab__grid">
                                { filteredBlank.map( ( tpl ) => {
                                    const isActive = tpl.key === appliedTemplateKey;
                                    return (
                                        <button
                                            key={ tpl.key }
                                            className={ `bp-tpl-card${ isActive ? ' is-active' : '' }` }
                                            onClick={ () => ! isActive && handleSelect( tpl ) }
                                        >
                                            { isActive && (
                                                <span className="bp-tpl-card__badge">{ __( 'Active', 'better-payment' ) }</span>
                                            ) }
                                            <div className="bp-tpl-card__thumb bp-tpl-card__thumb--blank">
                                                { BLANK_ICONS[ tpl.key ] || <span className="dashicons dashicons-layout" /> }
                                                { ! isActive && (
                                                    <div className="bp-tpl-card__hover">
                                                        <span className="bp-tpl-card__hover-btn bp-tpl-card__hover-btn--primary">
                                                            { __( 'Use Template', 'better-payment' ) }
                                                        </span>
                                                    </div>
                                                ) }
                                            </div>
                                            <div className="bp-tpl-card__info">
                                                <strong>{ tpl.label }</strong>
                                                { tpl.description && <span>{ tpl.description }</span> }
                                            </div>
                                        </button>
                                    );
                                } ) }
                            </div>
                        </div>
                    ) }

                    { filteredPrebuilt.length > 0 && (
                        <div className="bp-templates-tab__section">
                            { activeCategory === 'all' && ! search && (
                                <p className="bp-templates-tab__section-heading">
                                    { __( 'Or choose a pre-built template and launch your campaign in minutes!', 'better-payment' ) }
                                </p>
                            ) }
                            <div className="bp-templates-tab__grid">
                                { filteredPrebuilt.map( ( tpl ) => {
                                    const isActive  = tpl.key === appliedTemplateKey;
                                    const imgUrl    = getPreviewImageUrl( tpl );
                                    return (
                                        <div
                                            key={ tpl.key }
                                            className={ `bp-tpl-card${ isActive ? ' is-active' : '' }` }
                                        >
                                            { isActive && (
                                                <span className="bp-tpl-card__badge">{ __( 'Active', 'better-payment' ) }</span>
                                            ) }
                                            <div className="bp-tpl-card__thumb">
                                                { imgUrl ? (
                                                    <img
                                                        src={ imgUrl }
                                                        alt={ tpl.label }
                                                        className="bp-tpl-card__screenshot"
                                                        draggable={ false }
                                                    />
                                                ) : (
                                                    <div className="bp-tpl-card__thumb-placeholder" style={ { background: tpl.preview_color || '#6b63f6' } } />
                                                ) }
                                                { ! isActive && (
                                                    <div className="bp-tpl-card__hover">
                                                        <button
                                                            className="bp-tpl-card__hover-btn bp-tpl-card__hover-btn--primary"
                                                            onClick={ () => handleSelect( tpl ) }
                                                        >
                                                            { __( 'Use Template', 'better-payment' ) }
                                                        </button>
                                                        <button
                                                            className="bp-tpl-card__hover-btn"
                                                            onClick={ ( e ) => { e.stopPropagation(); setLightboxKey( tpl.key ); } }
                                                        >
                                                            { __( 'Preview', 'better-payment' ) }
                                                        </button>
                                                    </div>
                                                ) }
                                            </div>
                                            <div className="bp-tpl-card__info">
                                                <strong>{ tpl.label }</strong>
                                                { tpl.description && <span>{ tpl.description }</span> }
                                            </div>
                                        </div>
                                    );
                                } ) }
                            </div>
                        </div>
                    ) }

                    { filteredBlank.length === 0 && filteredPrebuilt.length === 0 && (
                        <div className="bp-templates-tab__empty">
                            <p>{ __( 'No templates match your search.', 'better-payment' ) }</p>
                        </div>
                    ) }
                </div>
            </div>

            {/* Lightbox — shows static screenshot */}
            { lightboxTpl && (
                <div className="bp-template-lightbox-overlay" onClick={ () => setLightboxKey( null ) }>
                    <div className="bp-template-lightbox" onClick={ ( e ) => e.stopPropagation() }>

                        {/* Minimal close — absolute top-right, no background */}
                        <button
                            className="bp-template-lightbox__close-btn"
                            onClick={ () => setLightboxKey( null ) }
                            aria-label={ __( 'Close', 'better-payment' ) }
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>

                        {/* Header — large title + description, no border */}
                        <div className="bp-template-lightbox__header">
                            <h2 className="bp-template-lightbox__title">{ lightboxTpl.label }</h2>
                            { lightboxTpl.description && (
                                <p className="bp-template-lightbox__desc">{ lightboxTpl.description }</p>
                            ) }
                        </div>

                        {/* Scrollable image area */}
                        <div className="bp-template-lightbox__body">
                            { getPreviewImageUrl( lightboxTpl ) ? (
                                <img
                                    src={ getPreviewImageUrl( lightboxTpl ) }
                                    alt={ lightboxTpl.label }
                                    className="bp-template-lightbox__img"
                                />
                            ) : (
                                <div className="bp-template-lightbox__no-preview">
                                    { __( 'No preview available.', 'better-payment' ) }
                                </div>
                            ) }
                        </div>

                        {/* Full-width footer button */}
                        <button
                            className="bp-template-lightbox__use-btn"
                            onClick={ () => { handleSelect( lightboxTpl ); setLightboxKey( null ); } }
                        >
                            { __( 'Use This Template', 'better-payment' ) }
                        </button>

                    </div>
                </div>
            ) }

            {/* Template change confirmation modal */}
            { confirmTemplate && (
                <div className="bp-template-confirm-overlay" onClick={ () => setConfirmTemplate( null ) }>
                    <div className="bp-template-confirm" onClick={ ( e ) => e.stopPropagation() }>
                        <div className="bp-template-confirm__icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 className="bp-template-confirm__heading">
                            { __( 'Switch Template?', 'better-payment' ) }
                        </h3>
                        <p className="bp-template-confirm__body">
                            { __( 'Switching to', 'better-payment' ) }{ ' ' }
                            <strong>"{ confirmTemplate.label }"</strong>{ ' ' }
                            { __( 'will replace your current layout and all its elements. This cannot be undone.', 'better-payment' ) }
                        </p>
                        <div className="bp-template-confirm__actions">
                            <button
                                className="bp-template-confirm__btn bp-template-confirm__btn--cancel"
                                onClick={ () => setConfirmTemplate( null ) }
                            >
                                { __( 'Cancel', 'better-payment' ) }
                            </button>
                            <button
                                className="bp-template-confirm__btn bp-template-confirm__btn--confirm"
                                onClick={ () => {
                                    applyTemplate( confirmTemplate );
                                    setConfirmTemplate( null );
                                } }
                            >
                                { __( 'Yes, Switch Template', 'better-payment' ) }
                            </button>
                        </div>
                    </div>
                </div>
            ) }

        </div>
    );
}
