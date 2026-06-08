import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const CUSTOM_ICONS = {
    progress_bar: (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="10" width="20" height="4" rx="2" fill="currentColor" fillOpacity="0.22"/>
            <rect x="2" y="10" width="12" height="4" rx="2" fill="currentColor"/>
            <circle cx="14" cy="12" r="3" fill="currentColor"/>
        </svg>
    ),
    donation_form: (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 9c-.5-1.3-1.8-2.2-3.2-2.2C6.9 6.8 5.4 8.3 5.4 10.1c0 2.3 2.3 4.4 6.6 7.1 4.3-2.7 6.6-4.8 6.6-7.1 0-1.8-1.5-3.3-3.4-3.3C13.8 6.8 12.5 7.7 12 9z" fill="currentColor"/>
            <rect x="6" y="18.5" width="12" height="3" rx="1.5" stroke="currentColor" strokeWidth="1.4" fill="none"/>
            <line x1="9.5" y1="18.5" x2="9.5" y2="17" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
            <line x1="12"  y1="18.5" x2="12"  y2="16.5" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
            <line x1="14.5" y1="18.5" x2="14.5" y2="17" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
        </svg>
    ),
};
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { getElement } from '../fields/FieldRegistry';
import { openWpMedia } from '../utils/media';
import { getGlobalCurrencySymbol } from '../utils/currency';

// Mirror of RendererService::scale_inline_font_sizes() — bumps every explicit
// px font-size by +2px so editor preview matches the 16px-base frontend.
function scaleInlineFontSizes( html ) {
    if ( ! html ) return html;
    return html.replace( /\bfont-size\s*:\s*(\d+(?:\.\d+)?)px/gi, ( _, size ) => {
        return `font-size: ${ parseFloat( size ) + 2 }px`;
    } );
}

/**
 * A single element card on the canvas — sortable via @dnd-kit/sortable.
 * Elements that have a live preview render their content directly;
 * all others fall back to the generic icon + label row.
 */
export default function ElementCard( { element, columnId, index, totalElements, meta, campaignId, isSelected, dispatch, isDropTarget } ) {
    const def = getElement( element.type );

    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable( {
        id: element.id,
        data: { source: 'canvas', columnId, element },
    } );

    const style = {
        transform: CSS.Transform.toString( transform ),
        transition,
        opacity: isDragging ? 0.4 : 1,
    };

    const handleClick = ( e ) => {
        e.stopPropagation();
        if ( isSelected ) {
            dispatch( { type: 'DESELECT_ELEMENT' } );
        } else {
            dispatch( { type: 'SELECT_ELEMENT', elementId: element.id, columnId } );
        }
    };

    const handleEdit = ( e ) => {
        e.stopPropagation();
        dispatch( { type: 'SELECT_ELEMENT', elementId: element.id, columnId } );
    };

    const handleDelete = ( e ) => {
        e.stopPropagation();
        dispatch( { type: 'REMOVE_ELEMENT', columnId, elementId: element.id } );
    };

    const handleMoveUp = ( e ) => {
        e.stopPropagation();
        dispatch( { type: 'REORDER_ELEMENTS', columnId, oldIndex: index, newIndex: index - 1 } );
    };

    const handleMoveDown = ( e ) => {
        e.stopPropagation();
        dispatch( { type: 'REORDER_ELEMENTS', columnId, oldIndex: index, newIndex: index + 1 } );
    };

    const isFirst = index === 0;
    const isLast  = index === totalElements - 1;

    const preview = getElementPreview( element, meta, dispatch, campaignId, columnId );

    return (
        <div
            ref={ setNodeRef }
            style={ style }
            className={ `bp-element-card${ isSelected ? ' is-selected' : '' }${ preview ? ' bp-element-card--preview' : '' }${ isDropTarget ? ' is-drop-target' : '' }` }
            onClick={ handleClick }
            { ...attributes }
        >
            { /* Floating hover toolbar */ }
            <div className="bp-element-card__toolbar">
                <button
                    type="button"
                    className="bp-element-card__toolbar-btn bp-element-card__toolbar-btn--drag"
                    title={ __( 'Drag to reorder', 'better-payment' ) }
                    { ...listeners }
                >
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <path d="M6 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-4 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" fill="currentColor"/>
                    </svg>
                </button>

                { ! isFirst && (
                    <button
                        type="button"
                        className="bp-element-card__toolbar-btn"
                        title={ __( 'Move up', 'better-payment' ) }
                        onClick={ handleMoveUp }
                    >
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M8 12V4M4 8l4-4 4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </button>
                ) }

                { ! isLast && (
                    <button
                        type="button"
                        className="bp-element-card__toolbar-btn"
                        title={ __( 'Move down', 'better-payment' ) }
                        onClick={ handleMoveDown }
                    >
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M8 4v8m4-4-4 4-4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </button>
                ) }

                <button
                    type="button"
                    className="bp-element-card__toolbar-btn"
                    title={ __( 'Edit element', 'better-payment' ) }
                    onClick={ handleEdit }
                >
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <path d="M11.5 2.5l2 2-7 7H4.5v-2l7-7z" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </button>

                <button
                    type="button"
                    className="bp-element-card__toolbar-btn bp-element-card__toolbar-btn--delete"
                    title={ __( 'Delete', 'better-payment' ) }
                    onClick={ handleDelete }
                >
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <path d="M2 4h12M5.333 4V2.667C5.333 2.313 5.473 1.974 5.724 1.724 5.974 1.473 6.313 1.333 6.667 1.333H9.333C9.687 1.333 10.026 1.473 10.276 1.724 10.527 1.974 10.667 2.313 10.667 2.667V4M12.667 4 12 13.333C12 13.687 11.86 14.026 11.609 14.276 11.359 14.527 11.02 14.667 10.667 14.667H5.333C4.98 14.667 4.641 14.527 4.391 14.276 4.14 14.026 4 13.687 4 13.333L3.333 4" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </button>
            </div>

            { /* Non-preview fallback body */ }
            { ! preview && (
                <div className="bp-element-card__body">
                    { CUSTOM_ICONS[ element.type ]
                        ? <span className="bp-cb-field-card__svg-icon">{ CUSTOM_ICONS[ element.type ] }</span>
                        : <span className={ `dashicons dashicons-${ def?.icon || 'block-default' }` } />
                    }
                    <span className="bp-element-card__label">{ def?.label || element.type }</span>
                </div>
            ) }

            { /* Live preview body */ }
            { preview && (
                <div className="bp-element-card__preview">
                    { preview }
                </div>
            ) }
        </div>
    );
}

// ── Per-element live previews ─────────────────────────────────────────────────

function getElementPreview( element, meta, dispatch, campaignId, columnId ) {
    switch ( element.type ) {
        case 'campaign_title':
            return <CampaignTitlePreview element={ element } meta={ meta } />;
        case 'campaign_description':
            return <CampaignDescriptionPreview element={ element } />;
        case 'photo':
            return <PhotoPreview element={ element } columnId={ columnId } dispatch={ dispatch } />;
        case 'social_links':
            return <SocialLinksPreview element={ element } />;
        case 'social_sharing':
            return <SocialSharingPreview element={ element } />;
        case 'progress_bar':
            return <ProgressBarPreview element={ element } meta={ meta } campaignId={ campaignId } />;
        case 'donate_amount':
            return <DonateAmountPreview element={ element } meta={ meta } dispatch={ dispatch } />;
        case 'donation_form':
            return <DonationFormPreview element={ element } meta={ meta } />;
        case 'campaign_summary':
            return <CampaignSummaryPreview element={ element } meta={ meta } campaignId={ campaignId } />;
        case 'organizer':
            return <OrganizerPreview element={ element } />;
        default:
            return null;
    }
}

// ── Campaign Summary preview ──────────────────────────────────────────────────

function CampaignSummaryPreview( { element, meta, campaignId } ) {
    const s           = element.settings || {};
    const headline    = s.headline     || '';
    const showRaised  = s.show_raised  !== false;
    const showDonors  = s.show_donors  !== false;
    const showPercent = s.show_percent !== false;
    const showDays    = s.show_days    !== false;

    const [ stats, setStats ] = useState( null );

    useEffect( () => {
        if ( ! campaignId ) return;
        apiFetch( { path: `better-payment/v1/campaigns/${ campaignId }/stats` } )
            .then( ( data ) => setStats( data ) )
            .catch( () => {} );
    }, [ campaignId ] );

    const raised  = stats ? parseFloat( stats.total_raised ) : 0;
    const donors  = stats ? stats.donor_count : 0;
    const goal    = parseFloat( meta?.bpc_goal_amount ) || 0;
    const days    = meta?.bpc_end_date
        ? Math.max( 0, Math.ceil( ( new Date( meta.bpc_end_date ) - new Date() ) / 86400000 ) )
        : null;
    const percent = goal > 0 ? Math.min( 100, Math.round( ( raised / goal ) * 1000 ) / 10 ) : 0;

    const currencySymbol = getGlobalCurrencySymbol();

    const raisedFormatted = `${ currencySymbol }${ Number( raised ).toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ) }`;

    return (
        <div className="bp-element-preview__summary">
            { headline && (
                <p className="bp-element-preview__summary-headline">{ headline }</p>
            ) }
            <div className="bp-element-preview__summary-grid">
                { showRaised && (
                    <div className="bp-element-preview__summary-item">
                        <strong>{ raisedFormatted }</strong>
                        <span>{ __( 'Raised', 'better-payment' ) }</span>
                    </div>
                ) }
                { showDonors && (
                    <div className="bp-element-preview__summary-item">
                        <strong>{ donors }</strong>
                        <span>{ __( 'Donors', 'better-payment' ) }</span>
                    </div>
                ) }
                { showPercent && goal > 0 && (
                    <div className="bp-element-preview__summary-item">
                        <strong>{ percent }%</strong>
                        <span>{ __( 'Funded', 'better-payment' ) }</span>
                    </div>
                ) }
                { showDays && days !== null && (
                    <div className="bp-element-preview__summary-item">
                        <strong>{ days }</strong>
                        <span>{ __( 'Days Left', 'better-payment' ) }</span>
                    </div>
                ) }
            </div>
        </div>
    );
}

// ── Campaign Description preview ─────────────────────────────────────────────

function CampaignDescriptionPreview( { element } ) {
    const s        = element.settings || {};
    const headline = s.headline || '';
    const content  = s.content  || '';
    const width    = s.width ?? 100;
    const align    = s.align || 'left';

    const wrapStyle = {
        width: `${ width }%`,
        textAlign: align,
        margin: align === 'center' ? '0 auto' : align === 'right' ? '0 0 0 auto' : '0',
    };

    if ( ! headline && ! content ) {
        return (
            <div className="bp-element-preview__description-empty">
                <span className="dashicons dashicons-editor-paragraph" />
                <span>{ __( 'Campaign Description', 'better-payment' ) }</span>
            </div>
        );
    }

    return (
        <div className="bp-element-preview__description" style={ wrapStyle }>
            { headline && (
                <p className="bp-element-preview__description-headline">{ headline }</p>
            ) }
            { content && (
                <div
                    className="bp-element-preview__description-content"
                    dangerouslySetInnerHTML={ { __html: scaleInlineFontSizes( content ) } }
                />
            ) }
        </div>
    );
}

// ── Progress Bar preview ──────────────────────────────────────────────────────

function ProgressBarPreview( { element, meta, campaignId } ) {
    const s            = element.settings || {};
    const headline     = s.headline    || '';
    const showDonated  = s.show_donated !== false;
    const showGoal     = s.show_goal    !== false;
    const donateLabel  = s.donate_label || 'Donated:';
    const goalLabel    = s.goal_label   || 'Goal:';
    const width        = s.width ?? 100;
    const align        = s.align || 'left';
    const primary      = meta?.bpc_color_primary || '#6b63f6';

    const [ stats, setStats ] = useState( null );

    useEffect( () => {
        if ( ! campaignId ) return;
        apiFetch( { path: `better-payment/v1/campaigns/${ campaignId }/stats` } )
            .then( ( data ) => setStats( data ) )
            .catch( () => {} );
    }, [ campaignId ] );

    const raised   = stats ? stats.total_raised : 0;
    const goal     = parseFloat( meta?.bpc_goal_amount ) || 0;
    const progress = goal > 0 ? Math.min( 100, ( raised / goal ) * 100 ) : 0;

    const wrapStyle = {
        width: `${ width }%`,
        margin: align === 'center' ? '0 auto' : align === 'right' ? '0 0 0 auto' : '0',
    };

    const roundAmounts   = s.round_amounts || false;
    const currencySymbol = getGlobalCurrencySymbol();

    // Display progress: ceiling integer when round_amounts, else 1 decimal from API.
    const rawRatio       = goal > 0 ? Math.min( 100, ( raised / goal ) * 100 ) : 0;
    const displayProgress = roundAmounts
        ? Math.ceil( rawRatio )
        : progress; // 1-decimal float from CampaignStats

    const goalFormatted = goal > 0
        ? ( currencySymbol + ( roundAmounts ? Math.ceil( goal ).toLocaleString() : Number( goal ).toFixed( 2 ) ) )
        : __( 'No goal set', 'better-payment' );

    return (
        <div className="bp-element-preview__progress" style={ wrapStyle }>
            { headline && (
                <p className="bp-element-preview__progress-headline">{ headline }</p>
            ) }
            <div className="bp-element-preview__progress-track">
                <div
                    className="bp-element-preview__progress-fill"
                    style={ { width: `${ progress }%`, backgroundColor: primary } }
                />
            </div>
            { ( showDonated || showGoal ) && (
                <div className="bp-element-preview__progress-labels">
                    { showDonated && (
                        <span className="bp-element-preview__progress-donated">
                            { donateLabel } { displayProgress }%
                        </span>
                    ) }
                    { showGoal && (
                        <span className="bp-element-preview__progress-goal">
                            { goalLabel } { goalFormatted }
                        </span>
                    ) }
                </div>
            ) }
        </div>
    );
}

// ── Photo preview ─────────────────────────────────────────────────────────────

function PhotoPreview( { element, columnId, dispatch } ) {
    const s     = element.settings || {};

    const handleImageClick = useCallback( ( e ) => {
        e.stopPropagation();
        openWpMedia( ( { url, id, sizes } ) => {
            dispatch( {
                type: 'UPDATE_ELEMENT_SETTINGS',
                elementId: element.id,
                settings: { src: url, src_id: id, src_sizes: sizes },
            } );
        } );
    }, [ element.id, dispatch ] );
    const src   = s.src || '';
    const alt   = s.alt || '';
    const width = s.width ?? 100;
    const align = s.align || 'center';
    const size  = s.size || 'full';
    const sizes = s.src_sizes || {};
    const srcId = s.src_id || 0;

    // Keyed by attachment ID — fetched once per ID, cached for all size changes.
    const [ fetchedSizes, setFetchedSizes ] = useState( {} );

    useEffect( () => {
        if ( ! srcId ) {
            setFetchedSizes( {} );
            return;
        }
        let cancelled = false;
        apiFetch( { path: `/wp/v2/media/${ srcId }` } )
            .then( ( data ) => {
                if ( cancelled ) return;
                const raw = data?.media_details?.sizes || {};
                const mapped = {};
                Object.keys( raw ).forEach( ( k ) => {
                    mapped[ k ] = raw[ k ].source_url;
                } );
                setFetchedSizes( mapped );
            } )
            .catch( () => {} );
        return () => { cancelled = true; };
    }, [ srcId ] );

    // Priority: src_sizes (from media modal) → fetched from REST API → original src.
    const resolvedUrl = size !== 'full'
        ? ( sizes[ size ]?.url || fetchedSizes[ size ] || src )
        : src;

    const wrapStyle = { textAlign: align };
    const imgStyle  = {
        width: 'auto',
        maxWidth: `${ width }%`,
        height: 'auto',
        display: 'block',
        borderRadius: '4px',
        margin: align === 'center' ? '0 auto' : align === 'right' ? '0 0 0 auto' : '0',
    };

    if ( ! src ) {
        return (
            <div className="bp-element-preview__photo-empty" onClick={ handleImageClick } style={ { cursor: 'pointer' } }>
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#bbb">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
            </div>
        );
    }

    return (
        <div className="bp-element-preview__photo" style={ wrapStyle }>
            <img
                src={ resolvedUrl }
                alt={ alt }
                style={ { ...imgStyle, cursor: 'pointer' } }
                onClick={ handleImageClick }
            />
        </div>
    );
}

// ── Social Links preview ──────────────────────────────────────────────────────

const SOCIAL_ICONS = {
    twitter: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
        </svg>
    ),
    facebook: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
    ),
    linkedin: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
        </svg>
    ),
    instagram: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162S8.597 18.163 12 18.163s6.162-2.759 6.162-6.162S15.403 5.838 12 5.838zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
        </svg>
    ),
    tiktok: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
        </svg>
    ),
    pinterest: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
        </svg>
    ),
    youtube: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
        </svg>
    ),
    threads: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.028-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.964-.065-1.19.408-2.285 1.33-3.082.88-.76 2.119-1.207 3.583-1.291a13.853 13.853 0 013.271.165c-.07-.75-.273-1.318-.614-1.716-.434-.507-1.119-.768-2.036-.777h-.045c-.67 0-1.938.181-2.669 1.4l-1.812-.755c.97-1.868 2.836-2.677 4.484-2.677h.06c3.233.03 5.164 2.01 5.34 5.49.208 3.394-1.112 5.49-3.317 6.548-.384.186-.785.34-1.197.461C16.418 23.61 14.397 24 12.186 24z"/>
        </svg>
    ),
    bluesky: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 01-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.204-.659-.299-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8z"/>
        </svg>
    ),
    mastodon: (
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 00.023-.043v-1.809a.052.052 0 00-.066-.051c-1.517.363-3.072.546-4.632.546-2.685 0-3.463-1.284-3.674-1.818a5.593 5.593 0 01-.319-1.433.053.053 0 01.066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.124-.01 1.554-.043 3.19-.167 4.72-.498.038-.009.075-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.005-.109.033-1.25.033-1.36 0-.43.138-3.032-.019-4.643zm-3.22 9.214h-2.058V9.47c0-1.063-.447-1.601-1.35-1.601-1 0-1.5.647-1.5 1.923v2.786h-2.048V9.792c0-1.276-.5-1.923-1.5-1.923-.903 0-1.35.538-1.35 1.601v5.04H7.884V9.32c0-1.062.27-1.907.81-2.534.558-.627 1.287-.948 2.192-.948 1.047 0 1.84.402 2.363 1.206l.509.855.51-.855c.523-.804 1.316-1.206 2.363-1.206.904 0 1.633.32 2.192.948.54.627.925 1.472.925 2.534v5.19z"/>
        </svg>
    ),
};

const SOCIAL_ORDER = [ 'twitter', 'facebook', 'linkedin', 'instagram', 'tiktok', 'pinterest', 'youtube', 'threads', 'bluesky', 'mastodon' ];
const SOCIAL_SHARING_ORDER = [ 'twitter', 'facebook', 'linkedin', 'pinterest', 'mastodon', 'threads', 'bluesky' ];

function SocialLinksPreview( { element } ) {
    const s        = element.settings || {};
    const headline = s.headline || 'Follow Now';
    const align    = s.align || 'left';
    const activeIcons = SOCIAL_ORDER.filter( ( key ) => !! s[ key ] );

    return (
        <div className="bp-element-preview__social-links" style={ { textAlign: align } }>
            { headline && (
                <p className="bp-element-preview__social-headline">{ headline }</p>
            ) }
            { activeIcons.length > 0 && (
                <div className="bp-element-preview__social-icons" style={ { justifyContent: align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start' } }>
                    { activeIcons.map( ( key ) => (
                        <span key={ key } className="bp-element-preview__social-icon" title={ key }>
                            { SOCIAL_ICONS[ key ] }
                        </span>
                    ) ) }
                </div>
            ) }
        </div>
    );
}

function SocialSharingPreview( { element } ) {
    const s        = element.settings || {};
    const headline = s.headline !== undefined && s.headline !== null ? s.headline : 'Share Now';
    const align    = s.align || 'left';
    const activeIcons = SOCIAL_SHARING_ORDER.filter( ( key ) => s[ key ] !== false );

    return (
        <div className="bp-element-preview__social-links" style={ { textAlign: align } }>
            { headline && (
                <p className="bp-element-preview__social-headline">{ headline }</p>
            ) }
            { activeIcons.length > 0 && (
                <div className="bp-element-preview__social-icons" style={ { justifyContent: align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start' } }>
                    { activeIcons.map( ( key ) => (
                        <span key={ key } className="bp-element-preview__social-icon" title={ key }>
                            { SOCIAL_ICONS[ key ] }
                        </span>
                    ) ) }
                </div>
            ) }
        </div>
    );
}

// ── Campaign Title preview ────────────────────────────────────────────────────

function CampaignTitlePreview( { element, meta } ) {
    const s     = element.settings || {};
    const title = meta?.title ?? '';
    const align = s.align || 'left';

    return (
        <h2
            className="bp-element-preview__campaign-title"
            style={ { textAlign: align } }
        >
            { title }
        </h2>
    );
}

// ── Donate Amount preview ─────────────────────────────────────────────────────

function hexToRgba( hex, alpha ) {
    if ( ! hex || hex.length < 7 ) return `rgba(107,99,246,${ alpha })`;
    const r = parseInt( hex.slice( 1, 3 ), 16 );
    const g = parseInt( hex.slice( 3, 5 ), 16 );
    const b = parseInt( hex.slice( 5, 7 ), 16 );
    return `rgba(${ r },${ g },${ b },${ alpha })`;
}

function DonateAmountPreview( { element, meta, dispatch } ) {
    const s            = element.settings || {};
    const headline     = s.headline || '';
    const amounts      = Array.isArray( meta?.bpc_suggested_amounts ) ? meta.bpc_suggested_amounts : [];
    const allowCustom  = !! meta?.bpc_allow_custom_amount;
    const primary      = meta?.bpc_color_primary || '#6b63f6';

    const globalCurrencySymbol = getGlobalCurrencySymbol();

    const [ customValue, setCustomValue ] = useState( '' );

    const deselectAll = () =>
        dispatch( {
            type: 'UPDATE_META',
            payload: {
                bpc_suggested_amounts: amounts.map( ( a ) => ( { ...a, is_default: false } ) ),
            },
        } );

    const handleSelectAmount = ( id, e ) => {
        e.stopPropagation();
        setCustomValue( '' );
        dispatch( {
            type: 'UPDATE_META',
            payload: {
                bpc_suggested_amounts: amounts.map( ( a ) => ( { ...a, is_default: a.id === id } ) ),
            },
        } );
    };

    const handleCustomFocus = ( e ) => {
        e.stopPropagation();
        deselectAll();
    };

    const handleCustomChange = ( e ) => {
        setCustomValue( e.target.value );
    };

    if ( amounts.length === 0 && ! allowCustom ) {
        return (
            <div className="bp-element-preview__donate-empty">
                <span className="dashicons dashicons-money-alt" />
                <span>{ __( 'No suggested amounts yet — add some in Donation Options', 'better-payment' ) }</span>
            </div>
        );
    }

    const anySelected = amounts.some( ( a ) => a.is_default );
    const customActive = allowCustom && ! anySelected && customValue !== '';

    return (
        <div className="bp-element-preview__donate" style={ { pointerEvents: 'none' } }>
            { headline && (
                <p className="bp-element-preview__donate-headline">{ headline }</p>
            ) }

            { amounts.length > 0 && (
                <div className="bp-element-preview__donate-amounts">
                    { amounts.map( ( item ) => {
                        const selected = !! item.is_default;
                        const btnStyle = selected
                            ? {
                                borderColor:     primary,
                                color:           primary,
                                backgroundColor: hexToRgba( primary, 0.08 ),
                            }
                            : {};
                        return (
                            <button
                                key={ item.id }
                                type="button"
                                className={ 'bp-element-preview__donate-btn' + ( selected ? ' is-selected' : '' ) }
                                style={ btnStyle }
                                onClick={ ( e ) => handleSelectAmount( item.id, e ) }
                            >
                                { globalCurrencySymbol }{ item.amount }
                            </button>
                        );
                    } ) }
                </div>
            ) }

            { allowCustom && (
                <div
                    className={ 'bp-element-preview__donate-custom' + ( customActive ? ' is-active' : '' ) }
                    style={ customActive ? { borderColor: primary, boxShadow: `0 0 0 2px ${ hexToRgba( primary, 0.18 ) }` } : {} }
                    onClick={ ( e ) => e.stopPropagation() }
                >
                    <span className="bp-element-preview__donate-custom-prefix">{ globalCurrencySymbol }</span>
                    <input
                        type="number"
                        min="0"
                        step="1"
                        value={ customValue }
                        placeholder={ __( 'Enter custom amount', 'better-payment' ) }
                        onChange={ handleCustomChange }
                        onFocus={ handleCustomFocus }
                    />
                </div>
            ) }
        </div>
    );
}

// ── Donation Form (Donate Button) preview ─────────────────────────────────────

function DonationFormPreview( { element, meta } ) {
    const s           = element.settings || {};
    const label       = s.button_label || s.button_text || 'Donate Now';
    const primary     = meta?.bpc_color_primary || '#6b63f6';
    const buttonColor = s.button_color || primary;
    const width       = s.width ?? 100;
    const align       = s.align || 'center';

    const wrapStyle = {
        width:   `${ width }%`,
        margin:  align === 'center' ? '0 auto' : align === 'right' ? '0 0 0 auto' : '0',
    };

    return (
        <div className="bp-element-preview__donate-button-wrap" style={ wrapStyle }>
            <span
                className="bp-element-preview__donate-button-btn"
                style={ { backgroundColor: buttonColor } }
            >
                { label }
            </span>
        </div>
    );
}

// ── Organizer preview ─────────────────────────────────────────────────────────

function OrganizerPreview( { element } ) {
    const s          = element.settings || {};
    const roleTitle  = s.role_title  || 'Organizer';
    const description = s.description || '';
    const width      = s.width ?? 100;
    const align      = s.align || 'left';

    // Resolve display name + avatar from the localized element registry options.
    const def            = getElement( 'organizer' );
    const creatorOptions = def?.settingsSchema?.find( ( c ) => c.key === 'creator_user_id' )?.options || [];
    const creator        = creatorOptions.find( ( o ) => String( o.value ) === String( s.creator_user_id ) );
    const displayName    = creator?.label      || '';
    const avatarUrl      = creator?.avatar_url || '';

    const wrapStyle = {
        width:  `${ width }%`,
        margin: align === 'center' ? '0 auto' : align === 'right' ? '0 0 0 auto' : '0',
    };

    return (
        <div className="bp-element-preview__organizer" style={ wrapStyle }>
            <div className="bp-element-preview__organizer-avatar">
                { avatarUrl
                    ? <img src={ avatarUrl } alt={ displayName } />
                    : <span className="dashicons dashicons-admin-users" />
                }
            </div>
            <div className="bp-element-preview__organizer-info">
                { displayName && (
                    <span className="bp-element-preview__organizer-name">{ displayName }</span>
                ) }
                { roleTitle && (
                    <span className="bp-element-preview__organizer-role">{ roleTitle }</span>
                ) }
                { description && (
                    <div
                        className="bp-element-preview__organizer-desc"
                        dangerouslySetInnerHTML={ { __html: scaleInlineFontSizes( description ) } }
                    />
                ) }
            </div>
        </div>
    );
}
