import { __ } from '@wordpress/i18n';

/**
 * Format a Unix timestamp to match PHP's date('d M, Y', $ts) → "04 May, 2026"
 */
function formatSubDate( ts ) {
    if ( ! ts ) return '—';
    const d = new Date( parseInt( ts, 10 ) * 1000 );
    if ( isNaN( d.getTime() ) ) return '—';
    const day   = String( d.getDate() ).padStart( 2, '0' );
    const month = d.toLocaleDateString( 'en-GB', { month: 'short' } );
    const year  = d.getFullYear();
    return `${ day } ${ month }, ${ year }`;
}

export default function SubscriptionsTab({ proEnabled, proAssets, subscriptions, subPage, subMeta, setSubPage, attributes, noItemsLabel }) {
    const {
        headerShow,
        subscriptionLabel,
        subscriptionTableSubscriptionIdShow,
        subscriptionTablePlanIdShow,
        subscriptionTableStatusShow,
        subscriptionTableAmountShow,
        subscriptionTableCreatedDateShow,
        subscriptionTableCurrentPeriodShow,
        subscriptionTableActionShow,
        subscriptionTableSubscriptionIdLabel,
        subscriptionTablePlanIdLabel,
        subscriptionTableStatusLabel,
        subscriptionTableAmountLabel,
        subscriptionTableCreatedDateLabel,
        subscriptionTableCurrentPeriodLabel,
        subscriptionTableActionLabel,
        subscriptionTableStatusActiveLabel,
        subscriptionTableStatusInactiveLabel,
        subscriptionTableActionCancelLabel,
    } = attributes;

    if (!proEnabled) {
        return (
            <p>
                <a className="width-100" target="_blank" href="https://wpdeveloper.com/in/upgrade-better-payment-pro" rel="noopener noreferrer">
                    <img width="100%" src={proAssets.subscriptionProBanner} alt="subscription-pro-banner" />
                </a>
            </p>
        );
    }

    return (
        <>
            {headerShow && (
                <div className="better-payment-user-dashboard-header bp--db-header bp-dashboard-header flex items-center justify-center">
                    <h2>{subscriptionLabel || __('Subscriptions', 'better-payment')}</h2>
                </div>
            )}
            <div className="bp--body-content">
                <div className="bp--table-main-wrapper">
                    <div className="bp--table-wrapper subscription better-payment-user-dashboard-table">
                        <div className="better-payment-user-dashboard-table-header bp--table-header flex justify-between gap-3">
                            {subscriptionTableSubscriptionIdShow && (
                                <div className="th details min-w-300 max-w-300"><h5>{subscriptionTableSubscriptionIdLabel || __('Subscription ID', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTablePlanIdShow && (
                                <div className="th details min-w-300 max-w-300"><h5>{subscriptionTablePlanIdLabel || __('Product Name', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTableStatusShow && (
                                <div className="th details flex justify-center"><h5>{subscriptionTableStatusLabel || __('Status', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTableAmountShow && (
                                <div className="th details"><h5>{subscriptionTableAmountLabel || __('Amount', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTableCreatedDateShow && (
                                <div className="th details"><h5>{subscriptionTableCreatedDateLabel || __('Payment Date', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTableCurrentPeriodShow && (
                                <div className="th details"><h5>{subscriptionTableCurrentPeriodLabel || __('Renewal Date', 'better-payment')}</h5></div>
                            )}
                            {subscriptionTableActionShow && (
                                <div className="th details flex justify-center"><h5>{subscriptionTableActionLabel || __('Action', 'better-payment')}</h5></div>
                            )}
                        </div>

                        {subscriptions.length > 0 ? subscriptions.map((sub, i) => {
                            // Both BlockManager (initial load) and UserAPI (pagination) now return flat structure.
                            const subStatus   = sub.subscription_status || '';
                            const isActive    = subStatus === 'active' || subStatus === 'complete';
                            const isSplit     = parseInt( sub.is_payment_split_payment || 0, 10 ) === 1;
                            const createdDate = formatSubDate( sub.subscription_created_date );
                            const renewalDate = formatSubDate( sub.subscription_current_period_end );
                            const productName = sub.subscription_product_name || sub.subscription_plan_id || '—';
                            const amount      = parseFloat( sub.amount || 0 );
                            const interval    = sub.subscription_interval || '';
                            // Match PHP: configured active/inactive labels
                            const statusLabel = isActive
                                ? ( subscriptionTableStatusActiveLabel  || __( 'Active',   'better-payment' ) )
                                : ( subscriptionTableStatusInactiveLabel || __( 'Inactive', 'better-payment' ) );
                            // Match PHP: configured cancel label
                            const cancelLabel = subscriptionTableActionCancelLabel || __( 'Cancel', 'better-payment' );

                            return (
                                <div key={i} className="better-payment-user-dashboard-table-body bp--table-body flex items-center justify-between gap-3">
                                    {subscriptionTableSubscriptionIdShow && (
                                        <div className="td details flex items-center gap-3 min-w-300 max-w-300">
                                            <div className="bp-flexbox-container">
                                                <h5 className="bp-shortend-text">
                                                    <span>{sub.subscription_id || '—'}</span>
                                                </h5>
                                            </div>
                                        </div>
                                    )}
                                    {subscriptionTablePlanIdShow && (
                                        <div className="td details flex items-center gap-3 min-w-300 max-w-300"><p>{productName}</p></div>
                                    )}
                                    {subscriptionTableStatusShow && (
                                        <div className="td details flex justify-center bp-user-dashboard-subscription-status">
                                            <button className={`${subStatus || 'inactive'} ${isActive ? 'active' : 'inactive'}`}>{statusLabel}</button>
                                        </div>
                                    )}
                                    {subscriptionTableAmountShow && (
                                        <div className="td details">
                                            <p className="flex items-center">
                                                {sub.currency} {amount}<span className="price">{interval ? `/${interval}` : ''}</span>
                                            </p>
                                        </div>
                                    )}
                                    {subscriptionTableCreatedDateShow && (
                                        <div className="td details"><p>{createdDate}</p></div>
                                    )}
                                    {subscriptionTableCurrentPeriodShow && (
                                        <div className="td details"><p>{renewalDate}</p></div>
                                    )}
                                    {subscriptionTableActionShow && (
                                        <div className="td details flex justify-center">
                                            <button className={`cancel bp-user-dashboard-subscription-cancel ${subStatus || ''} ${(!isActive || isSplit) ? 'is-hidden' : ''}`}>
                                                {cancelLabel}
                                            </button>
                                        </div>
                                    )}
                                </div>
                            );
                        }) : (
                            <div className="flex justify-center m-5"><p className="bp-no_subscription-text">{noItemsLabel || __('No records found!', 'better-payment')}</p></div>
                        )}

                        {subMeta && subMeta.pages > 1 && (
                            <div className="bp--pagination pagination">
                                <ul>
                                    <li>
                                        <a
                                            href="#"
                                            className={`pagination-previous bp--prev${subPage <= 1 ? ' disabled' : ''}`}
                                            aria-disabled={subPage <= 1}
                                            onClick={(e) => { e.preventDefault(); if (subPage > 1) setSubPage(p => p - 1); }}
                                        >
                                            <i className="bp-icon bp-caret-left"></i>
                                        </a>
                                    </li>
                                    {(() => {
                                        const total = subMeta.pages;
                                        const items = [];
                                        let ellipsisLeft = false;
                                        let ellipsisRight = false;

                                        for (let p = 1; p <= total; p++) {
                                            const inWindow = p === 1 || p === total || (p >= subPage - 2 && p <= subPage + 2);
                                            if (total <= 7 || inWindow) {
                                                const isActive = p === subPage;
                                                items.push(
                                                    <li key={p}>
                                                        <a href="#" className={`pagination-link bp--page-num${isActive ? ' is-current active' : ''}`} aria-label={`Page ${p}`}
                                                            onClick={(e) => { e.preventDefault(); if (!isActive) setSubPage(p); }}> {p} </a>
                                                    </li>
                                                );
                                            } else {
                                                const inLeftGap  = p < subPage - 2 && !ellipsisLeft;
                                                const inRightGap = p > subPage + 2 && !ellipsisRight;
                                                if (inLeftGap) {
                                                    const mid = Math.max(1, Math.round((2 + (subPage - 3)) / 2));
                                                    items.push(<li key="el-l"><a href="#" className="pagination-link bp--page-num bp--ellipsis" onClick={(e) => { e.preventDefault(); setSubPage(mid); }}>...</a></li>);
                                                    ellipsisLeft = true;
                                                } else if (inRightGap) {
                                                    const mid = Math.min(total, Math.round(((subPage + 3) + (total - 1)) / 2));
                                                    items.push(<li key="el-r"><a href="#" className="pagination-link bp--page-num bp--ellipsis" onClick={(e) => { e.preventDefault(); setSubPage(mid); }}>...</a></li>);
                                                    ellipsisRight = true;
                                                }
                                            }
                                        }
                                        return items;
                                    })()}
                                    <li>
                                        <a
                                            href="#"
                                            className={`pagination-next bp--next${subPage >= subMeta.pages ? ' disabled' : ''}`}
                                            aria-disabled={subPage >= subMeta.pages}
                                            onClick={(e) => { e.preventDefault(); if (subPage < subMeta.pages) setSubPage(p => p + 1); }}
                                        >
                                            <i className="bp-icon bp-caret-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
