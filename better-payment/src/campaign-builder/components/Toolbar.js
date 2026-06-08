import { __ } from '@wordpress/i18n';

/**
 * Top navigation bar — styled after Fluent Forms' builder header.
 *
 * Layout (left → right):
 *   Back  |  Name  |  [Editor] [Settings]  |  Shortcode  Preview  Publish  Draft  Fullscreen
 */
export default function Toolbar( {
    meta,
    isDirty,
    isSaving,
    savingAs,
    activeTab,
    isFullscreen,
    templateChosen,
    onTabChange,
    onMetaChange,
    onOpenNameModal,
    onPreview,
    onSave,
    onFullscreen,
    onExit,
    campaignId,
    postStatus,
    viewUrl,
} ) {
    return (
        <div className="bp-cb-topnav">

            {/* Left: Back + Name */}
            <div className="bp-cb-topnav__left">
                <button className="bp-cb-topnav__back" onClick={ onExit } title={ __( 'Back to Campaigns', 'better-payment' ) }>
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M10 12L6 8l4-4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                </button>

                <button className="bp-cb-topnav__name" onClick={ onOpenNameModal } title={ __( 'Edit campaign name', 'better-payment' ) }>
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style={ { flexShrink: 0 } }>
                        <path d="M9.5 1.5l2 2-7 7H2.5v-2l7-7z" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                    { meta.title
                        ? <span>{ meta.title }</span>
                        : <span className="bp-cb-topnav__name-placeholder">{ __( 'Campaign Name', 'better-payment' ) }</span>
                    }
                </button>
            </div>

            {/* Center: Tabs */}
            <div className="bp-cb-topnav__tabs">
                <button
                    className={ `bp-cb-tab-btn${ activeTab === 'templates' ? ' is-active' : '' }` }
                    onClick={ () => onTabChange( 'templates' ) }
                >
                    { __( 'Templates', 'better-payment' ) }
                </button>
                <button
                    className={ `bp-cb-tab-btn${ activeTab === 'editor' ? ' is-active' : '' }${ ! templateChosen ? ' is-disabled' : '' }` }
                    onClick={ () => templateChosen && onTabChange( 'editor' ) }
                    disabled={ ! templateChosen }
                    title={ ! templateChosen ? __( 'Choose a template first', 'better-payment' ) : '' }
                >
                    { __( 'Editor', 'better-payment' ) }
                </button>
                <button
                    className={ `bp-cb-tab-btn${ activeTab === 'settings' ? ' is-active' : '' }${ ! templateChosen ? ' is-disabled' : '' }` }
                    onClick={ () => templateChosen && onTabChange( 'settings' ) }
                    disabled={ ! templateChosen }
                    title={ ! templateChosen ? __( 'Choose a template first', 'better-payment' ) : '' }
                >
                    { __( 'Settings', 'better-payment' ) }
                </button>
            </div>

            {/* Right: Actions */}
            <div className="bp-cb-topnav__right">
{ postStatus === 'publish' && viewUrl ? (
                    <a
                        className="bp-cb-topnav__btn bp-cb-topnav__btn--outline"
                        href={ viewUrl }
                        target="_blank"
                        rel="noreferrer"
                        title={ __( 'View published campaign', 'better-payment' ) }
                    >
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M6 2H2.5A1.5 1.5 0 001 3.5v8A1.5 1.5 0 002.5 13h8A1.5 1.5 0 0012 11.5V8" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
                            <path d="M8 1h5v5M13 1L6.5 7.5" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                        { __( 'View Live', 'better-payment' ) }
                    </a>
                ) : (
                    <button
                        className="bp-cb-topnav__btn bp-cb-topnav__btn--outline bp-cb-topnav__btn--disabled"
                        disabled
                        title={ __( 'Publish the campaign to view it live', 'better-payment' ) }
                    >
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M6 2H2.5A1.5 1.5 0 001 3.5v8A1.5 1.5 0 002.5 13h8A1.5 1.5 0 0012 11.5V8" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
                            <path d="M8 1h5v5M13 1L6.5 7.5" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                        { __( 'View Live', 'better-payment' ) }
                    </button>
                ) }

                <button
                    className={ `bp-cb-topnav__btn bp-cb-topnav__btn--outline${ ! templateChosen ? ' bp-cb-topnav__btn--disabled' : '' }` }
                    onClick={ onPreview }
                    disabled={ ! templateChosen }
                    title={ ! templateChosen ? __( 'Choose a template first', 'better-payment' ) : '' }
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <ellipse cx="7" cy="7" rx="6" ry="4" stroke="currentColor" strokeWidth="1.3"/>
                        <circle cx="7" cy="7" r="1.5" fill="currentColor"/>
                    </svg>
                    { __( 'Preview', 'better-payment' ) }
                </button>

                <button
                    className={ `bp-cb-topnav__btn bp-cb-topnav__btn--primary${ ! templateChosen ? ' bp-cb-topnav__btn--disabled' : '' }` }
                    onClick={ () => onSave( 'publish' ) }
                    disabled={ isSaving || ! templateChosen }
                    title={ ! templateChosen ? __( 'Choose a template first', 'better-payment' ) : '' }
                >
                    { savingAs === 'publish' ? (
                        __( 'Saving…', 'better-payment' )
                    ) : (
                        <>
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                <path d="M2.5 7l3 3 6-6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                            { __( 'Publish', 'better-payment' ) }
                        </>
                    ) }
                </button>

                <button
                    className="bp-cb-topnav__btn bp-cb-topnav__btn--outline"
                    onClick={ () => onSave( 'draft' ) }
                    disabled={ isSaving }
                >
                    { savingAs === 'draft' ? __( 'Saving…', 'better-payment' ) : __( 'Draft', 'better-payment' ) }
                </button>

                <button
                    className={ `bp-cb-topnav__icon-btn${ isFullscreen ? ' is-active' : '' }` }
                    onClick={ onFullscreen }
                    title={ isFullscreen ? __( 'Exit fullscreen', 'better-payment' ) : __( 'Fullscreen', 'better-payment' ) }
                >
                    { isFullscreen ? (
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path d="M10 5h3V2M5 10H2v3M10 10l3 3M5 5L2 2" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
                        </svg>
                    ) : (
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path d="M10 2h3v3M2 10v3h3M10 13h3v-3M2 5V2h3" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round"/>
                        </svg>
                    ) }
                </button>
            </div>
        </div>
    );
}
