import { __ } from '@wordpress/i18n';

export default function SplitSubscriptionWidget({ proEnabled, proAssets, subscriptions, title, viewAllLabel, noItemsLabel }) {
    if (!proEnabled) {
        return (
            <a className="width-100" target="_blank" href="https://wpdeveloper.com/in/upgrade-better-payment-pro" rel="noopener noreferrer">
                <img width="100%" src={proAssets.splitSubscriptionBanner} alt="split-subscription-pro-banner" />
            </a>
        );
    }

    // Compute subscription counts for CSS doughnut - Split has only 2 states (no cancelled)
    const activeCount    = subscriptions.filter(s => {
        const st = s.subscription_status;
        return st === 'active' || st === 'complete';
    }).length;
    const inactiveCount  = subscriptions.length - activeCount;

    const sum = activeCount + inactiveCount;
    const unit_percentage = sum > 0 ? 100 / sum : 1;
    const activePercent   = activeCount > 0 && sum > 0 ? unit_percentage * activeCount : 100;

    const displayRows = subscriptions.slice(0, 10);

    return (
        <div className="bp-subscription_box">
            <div className="bp-subscription_box-th flex justify-between items-center flex-wrap">
                <div className="flex gap-2 items-center">
                    <span className="line-height-0">
                        <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.0002 11.7499C12.1952 11.7499 12.3827 11.6749 12.5327 11.5324L16.2827 7.78237C16.5752 7.48987 16.5752 7.01737 16.2827 6.72487C15.9902 6.43237 15.5177 6.43237 15.2252 6.72487L11.4752 10.4749C11.1827 10.7674 11.1827 11.2399 11.4752 11.5324C11.6252 11.6824 11.8127 11.7499 12.0077 11.7499H12.0002Z" fill="#475467" />
                            <path d="M15.7656 10.2499C15.3531 10.2499 15.0156 10.5874 15.0156 10.9999C15.0156 11.4124 15.3531 11.7499 15.7656 11.7499C16.1781 11.7499 16.5156 11.4124 16.5156 10.9999C16.5156 10.5874 16.1781 10.2499 15.7656 10.2499Z" fill="#475467" />
                            <path d="M12.0156 7.99988C12.4281 7.99988 12.7656 7.66238 12.7656 7.24988C12.7656 6.83738 12.4281 6.49988 12.0156 6.49988H12.0081C11.5956 6.49988 11.2656 6.83738 11.2656 7.24988C11.2656 7.66238 11.6031 7.99988 12.0156 7.99988Z" fill="#475467" />
                            <path d="M18.375 2.74988H5.625C4.1775 2.74988 3 3.92738 3 5.37488V15.4999C3 16.3249 3.675 16.9999 4.5 16.9999H6.75V21.4999C6.75 21.7549 6.885 21.9949 7.095 22.1299C7.3125 22.2649 7.5825 22.2874 7.815 22.1749L9.09 21.5749L10.365 22.1749C10.5675 22.2724 10.8 22.2724 11.0025 22.1749L12.2775 21.5749L13.5525 22.1749C13.755 22.2724 13.9875 22.2724 14.19 22.1749L15.465 21.5749L16.74 22.1749C16.8375 22.2199 16.95 22.2499 17.0625 22.2499C17.175 22.2499 17.28 22.2274 17.385 22.1749L18.66 21.5749L19.935 22.1749C20.1675 22.2874 20.4375 22.2649 20.655 22.1299C20.8725 21.9949 21 21.7549 21 21.4999V5.37488C21 3.92738 19.8225 2.74988 18.375 2.74988ZM4.5 5.37488C4.5 4.75238 5.0025 4.24988 5.625 4.24988C6.2475 4.24988 6.75 4.75238 6.75 5.37488V15.4999H4.5V5.37488ZM19.5 20.3149L19.305 20.2249C18.8925 20.0299 18.4275 20.0299 18.0225 20.2249L17.07 20.6749L16.125 20.2249C15.7125 20.0299 15.2475 20.0299 14.8425 20.2249L13.89 20.6749L12.9375 20.2249C12.525 20.0299 12.06 20.0299 11.655 20.2249L10.7025 20.6749L9.75 20.2249C9.3375 20.0299 8.8725 20.0299 8.4675 20.2249L8.265 20.3224V5.37488C8.265 4.96988 8.1675 4.59488 8.0025 4.24988H18.39C19.0125 4.24988 19.515 4.75238 19.515 5.37488V20.3149H19.5Z" fill="#475467" />
                            <path d="M18 14.7499C18 14.3374 17.6625 13.9999 17.25 13.9999H10.5C10.0875 13.9999 9.75 14.3374 9.75 14.7499C9.75 15.1624 10.0875 15.4999 10.5 15.4999H17.25C17.6625 15.4999 18 15.1624 18 14.7499Z" fill="#475467" />
                            <path d="M14.25 16.9999H10.5C10.0875 16.9999 9.75 17.3374 9.75 17.7499C9.75 18.1624 10.0875 18.4999 10.5 18.4999H14.25C14.6625 18.4999 15 18.1624 15 17.7499C15 17.3374 14.6625 16.9999 14.25 16.9999Z" fill="#475467" />
                            <path d="M17.2656 16.9999C16.8531 16.9999 16.5156 17.3374 16.5156 17.7499C16.5156 18.1624 16.8531 18.4999 17.2656 18.4999C17.6781 18.4999 18.0156 18.1624 18.0156 17.7499C18.0156 17.3374 17.6781 16.9999 17.2656 16.9999Z" fill="#475467" />
                        </svg>
                    </span>
                    <h3 className="bp-subscription_title">{title}</h3>
                </div>
                <a href="#" className="bp-view_all-btn">{viewAllLabel || __('View All', 'better-payment')}</a>
            </div>

            <div className="bp-subscripthon_doughnut-box flex">
                <div className="bp-multi-graph bp-margin">
                    <h2 className="bp-total_count">{subscriptions.length}</h2>
                    <p className="bp-total_title">{__('Total recurring subscription:', 'better-payment')}</p>
                    {/* For split subscriptions, no cancelled bar - only inactive (yellow) and active (purple) */}
                    {/* Inactive bar (yellow) at 100% */}
                    <div className="bp-graph active-inactive-sum" style={{ '--percentage': 100, '--fill': '#FFCF66' }} />
                    {/* Active bar (purple) */}
                    <div className="bp-graph active-sum" style={{ '--percentage': activePercent, '--fill': '#8673FF' }} />
                    <div className="bp-doughnut_info">
                        <div className="bp-dougnut_color-info flex items-center"><div className="color-B" />{__('Active subscription:', 'better-payment')} {activeCount}</div>
                        <div className="bp-dougnut_color-info flex items-center"><div className="color-Y" />{__('Inactive subscription:', 'better-payment')} {inactiveCount}</div>
                    </div>
                </div>
            </div>

            <div className="bp-subscription_product bp--table-wrapper">
                <div className="bp-subscription_product-th flex items-center min-w_600">
                    <div className="w-325 bp-subscription_th"><h4>{__('Product Name', 'better-payment')}</h4></div>
                    <div className="w-214 bp-subscription_th"><h4>{__('Next Payment', 'better-payment')}</h4></div>
                    <div className="w-130 bp-subscription_th"><h4>{__('Status', 'better-payment')}</h4></div>
                    <div className="w-214 bp-subscription_th"><h4>{__('Amount', 'better-payment')}</h4></div>
                </div>

                {displayRows.length > 0 ? displayRows.map((sub, i) => {
                    const subStatus       = sub.subscription_status || '';
                    const isActive        = subStatus === 'active' || subStatus === 'complete';
                    const nextPaymentDate = sub.subscription_current_period_end ? new Date(sub.subscription_current_period_end * 1000).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
                    const productName     = sub.subscription_product_name || sub.subscription_plan_id || '—';
                    const interval        = sub.subscription_interval || '';
                    return (
                        <div key={i} className="bp-subscription_product-td better-payment-user-dashboard-table-body bp--table-body flex items-center p-0 min-w_600 border-bottom_active">
                            <div className="w-325 bp-subscription_td"><div className="flex items-center"><div className="bp-icon" /><div className="bp-plan_info"><h4>{productName}</h4></div></div></div>
                            <div className="w-214 bp-subscription_td td"><p>{nextPaymentDate}</p></div>
                            <div className="w-130 bp-subscription_td"><button className={`${subStatus || 'inactive'} ${isActive ? 'active' : 'inactive'}`}>{isActive ? __('Active', 'better-payment') : __('Inactive', 'better-payment')}</button></div>
                            <div className="w-214 bp-subscription_td"><div className="flex bp-subscription_product-price"><span>{sub.currency} {parseFloat(sub.amount || 0)}</span>{interval && <span>/{interval}</span>}</div></div>
                        </div>
                    );
                }) : (
                    <div className="flex justify-center m-5"><p className="bp-no_subscription-text">{noItemsLabel || __('No records found!', 'better-payment')}</p></div>
                )}
            </div>
        </div>
    );
}
