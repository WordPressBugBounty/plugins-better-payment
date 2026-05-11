import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function AnalyticsReport({ proEnabled, proAssets, analyticsChartData, bp_settings }) {
    const canvasRef = useRef(null);
    const chartRef = useRef(null);

    useEffect(() => {
        if (!proEnabled || !analyticsChartData || !canvasRef.current) return;
        if (typeof window.Chart === 'undefined') return;

        // Destroy existing chart instance if any
        if (chartRef.current) {
            chartRef.current.destroy();
            chartRef.current = null;
        }

        const labels = analyticsChartData.payment_date_period_index || [];
        const datasets = [];

        // If show_all_four_stats is true, show all datasets regardless of individual flags
        const showAllFourStats = analyticsChartData.show_all_four_stats == 1;

        // Total Transaction Dataset
        if (showAllFourStats || analyticsChartData.all_transactions_show == 1) {
            datasets.push({
                label: __('Total Transaction', 'better-payment'),
                data: analyticsChartData.all_transactions || [],
                borderColor: '#735EF8',
                backgroundColor: '#735EF8',
                tension: 0,
                fill: false,
            });
        }

        // Completed Transaction Dataset
        if (showAllFourStats || analyticsChartData.completed_transactions_show == 1) {
            datasets.push({
                label: __('Completed Transaction', 'better-payment'),
                data: analyticsChartData.completed_transactions || [],
                borderColor: '#0ECA86',
                backgroundColor: '#0ECA86',
                tension: 0,
                fill: false,
            });
        }

        // Incomplete Transaction Dataset
        if (showAllFourStats || analyticsChartData.incomplete_transactions_show == 1) {
            datasets.push({
                label: __('Incomplete Transaction', 'better-payment'),
                data: analyticsChartData.incomplete_transactions || [],
                borderColor: '#FFDA15',
                backgroundColor: '#FFDA15',
                tension: 0,
                fill: false,
            });
        }

        // Refund Transaction Dataset
        if (showAllFourStats || analyticsChartData.refund_transactions_show == 1) {
            datasets.push({
                label: __('Refunded Transaction', 'better-payment'),
                data: analyticsChartData.refund_transactions || [],
                borderColor: '#FF0202',
                backgroundColor: '#FF0202',
                tension: 0,
                fill: false,
            });
        }

        const config = {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets,
            },
            options: {
                maintainAspectRatio: false,
                scaleShowHorizontalLines: true,
                scaleShowVerticalLines: false,
                bezierCurveTension: 0.3,
                responsive: true,
                spanGaps: false,
                tooltips: {
                    mode: 'nearest',
                    position: 'nearest',
                    intersect: false,
                },
                hover: {
                    position: 'nearest',
                    intersect: false,
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function (value) {
                                return '$' + value;
                            },
                        },
                    },
                },
            },
        };

        chartRef.current = new window.Chart(canvasRef.current, config);

        return () => {
            if (chartRef.current) {
                chartRef.current.destroy();
                chartRef.current = null;
            }
        };
    }, [proEnabled, analyticsChartData]);

    if (!proEnabled) {
        return (
            <a className="bp-analytics_reports" target="_blank" href="https://wpdeveloper.com/in/upgrade-better-payment-pro" rel="noopener noreferrer">
                <img width="100%" src={proAssets.analyticsReportsBanner} alt="analytics-reports-pro-banner" />
            </a>
        );
    }

    return (
        <div className="bp-analytics_box">
            <div className="better-payment-analytics">
                <div className="bp-chart_header flex justify-between items-center flex-wrap">
                    <div className="flex gap-4">
                        <div className="icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 21.9999H21" stroke="#475467" strokeWidth="1.25" strokeLinecap="round" strokeLinejoin="round" />
                                <path d="M3 11C3 10.0572 3 9.58579 3.29289 9.29289C3.58579 9 4.05719 9 5 9C5.94281 9 6.41421 9 6.70711 9.29289C7 9.58579 7 10.0572 7 11V17C7 17.9428 7 18.4142 6.70711 18.7071C6.41421 19 5.94281 19 5 19C4.05719 19 3.58579 19 3.29289 18.7071C3 18.4142 3 17.9428 3 17V11Z" stroke="#475467" strokeWidth="1.25" />
                                <path d="M9.99805 7C9.99805 6.05719 9.99805 5.58579 10.2909 5.29289C10.5838 5 11.0552 5 11.998 5C12.9409 5 13.4123 5 13.7052 5.29289C13.998 5.58579 13.998 6.05719 13.998 7V17C13.998 17.9428 13.998 18.4142 13.7052 18.7071C13.4123 19 12.9409 19 11.998 19C11.0552 19 10.5838 19 10.2909 18.7071C9.99805 18.4142 9.99805 17.9428 9.99805 17V7Z" stroke="#475467" strokeWidth="1.25" />
                                <path d="M16.9971 4C16.9971 3.05719 16.9971 2.58579 17.29 2.29289C17.5829 2 18.0543 2 18.9971 2C19.9399 2 20.4113 2 20.7042 2.29289C20.9971 2.58579 20.9971 3.05719 20.9971 4V17C20.9971 17.9428 20.9971 18.4142 20.7042 18.7071C20.4113 19 19.9399 19 18.9971 19C18.0543 19 17.5829 19 17.29 18.7071C16.9971 18.4142 16.9971 17.9428 16.9971 17V4Z" stroke="#475467" strokeWidth="1.25" />
                            </svg>
                        </div>
                        <div className="name">
                            <h3>{bp_settings?.dashboardAnalyticsReportsLabel || __('Analytics Reports', 'better-payment')}</h3>
                        </div>
                    </div>
                </div>
                <div className="analytics-chart-wrap" style={{ height: '300px' }}>
                    <div className="template-analytics-content" style={{ height: '100%' }}>
                        <canvas ref={canvasRef} id="bpEditorChart" />
                    </div>
                </div>
            </div>
        </div>
    );
}
