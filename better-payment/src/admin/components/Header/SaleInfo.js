import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dashboardAPI } from '../../utils/api';

const SaleInfo = () => {
    const [isSaleInfoDismissed, setIsSaleInfoDismissed] = useState(false);

    useEffect(() => {
        dashboardAPI.isSaleInfoDismissed().then(data => {
            setIsSaleInfoDismissed(data?.isSaleInfoDismissed);
        });
    }, []);
    
    const handleDismissSaleInfo = () => {
        dashboardAPI.dismissSaleInfo().then(data => {
            if (data?.success) {
                setIsSaleInfoDismissed(true);
            }
        });
    };
    return (
        <>
            {!isSaleInfoDismissed && (
                <div className="bp-flex align-items_center justify-content-center bp-sale_flag">
                    <div className="bp-sale_info-wrapper">
                        <p className="fx-7 bp-sale_info-text ">Unlock premium features with Holiday Special Sale: <span style={{ color: "#FFC800" }}>Grab up to 25% OFF</span> </p>
                    </div>
                    <div className="bp-sale_learnMore-wrapper">
                        <a href="https://betterpayment.co/notice" target="_blank" className="bp-font_ibm fx-6 bp-learnMore_btn">Learn More</a>
                    </div>
                    <div className='bp-flag_close-btn-wrapper'>
                        <button className='bp-flag_close-btn' onClick={handleDismissSaleInfo}>
                            <svg
                                width="16"
                                height="17"
                                viewBox="0 0 16 17"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M11.6134 14.0503L13.4509 12.2128L4.07969 2.84154L2.24219 4.67904L11.6134 14.0503ZM4.07969 14.0503L13.4509 4.67904L11.6134 2.84154L2.24219 12.2128L4.07969 14.0503Z"
                                    fill="#ffffff"
                                />
                            </svg>

                        </button>
                    </div>
                </div>
            )}
        </>
    );
};

export default SaleInfo;
