import FooterCards from '../components/settings/FooterCards';
import bpGotoProLight from './../images/analytics-page-go-pro-light.webp';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';


const AnalyticsPageFree = ({ theme }) => {
    return (
        <>
            {
                theme === 'light' ?
                    <a href="https://wpdeveloper.com/in/upgrade-better-payment-pro" target="_blank">
                        <img className="analytics-page-go-pro" src={bpGotoProLight} alt="Go to Pro" />
                    </a> :
                    <a href="https://wpdeveloper.com/in/upgrade-better-payment-pro" target="_blank">
                        <img className="analytics-page-go-pro" src={bpGotoProLight} alt="Go to Pro" />
                    </a>
            }
        </>
    );
};

const Analytics = ( props ) => {
    useEffect(() => {
        document.title = __('Analytics - Better Payment', 'better-payment');
    }, []);

    // need to load data from browser local storage for theme
    const theme = localStorage.getItem('isBPlightMode') !== 'false' ? 'light' : 'dark';
    
    return (
        <>
            <div className="tab-content bp-tab_active" id="analytics">
                <div className="section  bp-analytics_section">
                    <div className="bp-container">
                        {applyFilters('better_payment_analytics_page', <AnalyticsPageFree theme={theme} />, props)}
                        <FooterCards />
                    </div>
                </div>
            </div>
        </>
    );
};

export default Analytics;
