import bpGotoProLight from './../../images/analytics-overview-pro-light.webp';
import { applyFilters } from '@wordpress/hooks';

const AnalyticsFree = ({ theme }) => {
    return (
        <>
            {
                theme === 'light' ?
                    <a href="https://wpdeveloper.com/in/upgrade-better-payment-pro" target="_blank">
                        <img className="analytics-overview-go-pro" src={bpGotoProLight} alt="Go to Pro" />
                    </a> :
                    <a href="https://wpdeveloper.com/in/upgrade-better-payment-pro" target="_blank">
                        <img className="analytics-overview-go-pro" src={bpGotoProLight} alt="Go to Pro" />
                    </a>
            }
        </>
    );
};

const DashboardAnalytics = ( props ) => {
    const theme = localStorage.getItem('isBPlightMode') !== 'false' ? 'light' : 'dark';
    return (
        <>
            {
                applyFilters('better_payment_dashboard_analytics', <AnalyticsFree theme={theme} />, props)
            }
        </>
    );
};

export default DashboardAnalytics;
