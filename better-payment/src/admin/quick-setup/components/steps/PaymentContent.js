/**
 * PaymentContent Component
 *
 * Payment gateway credentials configuration
 */

import { useState } from '@wordpress/element';
import { PAYMENT_GATEWAYS } from '../../utils/constants';
import PayPal from './payment-methods/PayPal';
import Stripe from './payment-methods/Stripe';
import Paystack from './payment-methods/Paystack';

const PaymentContent = ({ data, onDataChange }) => {
    const [activeTab, setActiveTab] = useState(PAYMENT_GATEWAYS.PAYPAL);

    const handlePaymentMethodChange = (method) => {
        setActiveTab(method);
    };

    const renderPaymentMethod = () => {
        switch (activeTab) {
            case PAYMENT_GATEWAYS.PAYPAL:
                return <PayPal data={data} onDataChange={onDataChange} />;
            case PAYMENT_GATEWAYS.STRIPE:
                return <Stripe data={data} onDataChange={onDataChange} />;
            case PAYMENT_GATEWAYS.PAYSTACK:
                return <Paystack data={data} onDataChange={onDataChange} />;
            default:
                return null;
        }
    };

    return (
        <div className="bp-wizard__content bp-wizard__content--payment">
            <div className="bp-payment">
                <div className="bp-payment__sidebar">
                    <button className={`bp-payment-tab ${activeTab === PAYMENT_GATEWAYS.PAYPAL ? 'bp-payment-tab--active' : ''}`} onClick={() => handlePaymentMethodChange(PAYMENT_GATEWAYS.PAYPAL)}>
                        <svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7.91667 0C10.5108 0 12.3408 1.4875 12.8025 3.80417C14.24 4.79167 15 6.43083 15 8.33333C15 10.7775 12.71 12.82 10.1808 12.9133L10 12.9167H8.53833L8.15 15.25C8.0776 15.6076 7.88972 15.9316 7.61524 16.1721C7.34076 16.4125 6.9949 16.5561 6.63083 16.5808L6.5 16.5833H4.26667C4.07925 16.587 3.89341 16.5484 3.72291 16.4705C3.5524 16.3926 3.4016 16.2774 3.28167 16.1333C3.07257 15.8852 2.96853 15.5653 2.99167 15.2417L3.02667 15H1.66667C0.83 15 0.0716667 14.28 0.00500003 13.4525L0 13.3333L0.00833333 13.2158L1.66917 1.5875L1.67167 1.5475C1.73167 0.8025 2.35083 0.145833 3.08417 0.0208334L3.21417 0.00416676L3.33333 0H7.91667ZM12.76 6.125L12.74 6.1975C12.1517 8.2 10.18 9.89417 8.11083 9.995L7.91667 10H5.83083V10.0417L5.81 10.1942L4.81 14.3608C4.80542 14.3805 4.80014 14.3999 4.79417 14.4192L4.72083 14.9167H6.51583L7.01167 11.9467C7.04111 11.7691 7.12724 11.6059 7.25717 11.4813C7.3871 11.3568 7.55386 11.2777 7.7325 11.2558L7.83333 11.25H10C11.7275 11.25 13.3333 9.85833 13.3333 8.33333C13.3333 7.48167 13.1367 6.73 12.76 6.125Z" fill="#0070E0"/>
                        </svg>
                        PayPal
                    </button>
                    <button className={`bp-payment-tab ${activeTab === PAYMENT_GATEWAYS.STRIPE ? 'bp-payment-tab--active' : ''}`} onClick={() => handlePaymentMethodChange(PAYMENT_GATEWAYS.STRIPE)}>
                        <svg width="13" height="17" viewBox="0 0 13 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.25 0C8.29167 0 10.0358 0.366667 11.19 0.913333C11.3485 0.988469 11.4793 1.11173 11.5637 1.26555C11.6481 1.41937 11.6818 1.59591 11.66 1.77L11.2433 5.10333C11.227 5.2348 11.1795 5.36046 11.1048 5.46991C11.0302 5.57935 10.9305 5.66942 10.8141 5.73264C10.6976 5.79586 10.5678 5.83041 10.4354 5.83343C10.3029 5.83645 10.1717 5.80785 10.0525 5.75C9.18 5.32583 7.87083 5.06417 6.57917 5.06417C6.46472 5.06472 6.3725 5.07194 6.3025 5.08583L6.32583 5.10583L6.38417 5.145C6.64583 5.3175 7.0775 5.50917 7.7775 5.76667C10.9642 6.8925 12.5 8.46667 12.5 11.25C12.5 13.025 11.8683 14.36 10.5875 15.3533C9.51 16.2058 7.73583 16.6667 5.83333 16.6667C3.83333 16.6667 1.9 16.13 0.428333 15.3117C0.283147 15.2309 0.165426 15.1085 0.0902903 14.9603C0.0151542 14.8121 -0.0139671 14.6449 0.00666658 14.48L0.423333 11.1467C0.440405 11.0101 0.491043 10.8799 0.570723 10.7676C0.650403 10.6554 0.756644 10.5646 0.879954 10.5035C1.00326 10.4423 1.1398 10.4126 1.27738 10.4171C1.41495 10.4216 1.54927 10.4601 1.66833 10.5292C2.745 11.1542 4.70167 11.6667 5.83333 11.6667C6.15833 11.6667 6.42 11.5442 6.55833 11.4208L6.5875 11.3917L6.5125 11.3625C6.37333 11.3125 5.19333 10.9517 4.71833 10.7842C1.69667 9.6975 0 7.77667 0 5C0 3.4275 0.735 2.03083 1.975 1.01917C2.99167 0.196667 4.0725 0 6.25 0Z" fill="#8F9AB0"/>
                        </svg>
                        Stripe
                    </button>
                    <button className={`bp-payment-tab ${activeTab === PAYMENT_GATEWAYS.PAYSTACK ? 'bp-payment-tab--active' : ''}`} onClick={() => handlePaymentMethodChange(PAYMENT_GATEWAYS.PAYSTACK)}>
                        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.5462 0H0.851649C0.391759 0 0 0.393422 0 0.872369V2.42895C0 2.90789 0.391759 3.30132 0.851649 3.30132H14.5462C15.0231 3.30132 15.3978 2.90789 15.4149 2.42895V0.889473C15.4149 0.39342 15.0231 0 14.5462 0ZM14.5462 8.63816H0.851649C0.63022 8.63816 0.408792 8.72369 0.255495 8.89474C0.0851649 9.06579 0 9.27105 0 9.51052V11.0671C0 11.5461 0.391759 11.9395 0.851649 11.9395H14.5462C15.0231 11.9395 15.3978 11.5632 15.4149 11.0671V9.51052C15.3978 9.01447 15.0231 8.63816 14.5462 8.63816ZM8.56759 12.9487H0.851649C0.63022 12.9487 0.408792 13.0342 0.255495 13.2053C0.102198 13.3763 0 13.5816 0 13.8211V15.3776C0 15.8566 0.391759 16.25 0.851649 16.25H8.55056C9.02748 16.25 9.40221 15.8566 9.40221 15.3947V13.8382C9.41924 13.325 9.04451 12.9316 8.56759 12.9487ZM15.4149 4.31052H0.851649C0.63022 4.31052 0.408792 4.39605 0.255495 4.5671C0.102198 4.73816 0 4.94342 0 5.18289V6.73947C0 7.21842 0.391759 7.61184 0.851649 7.61184H15.3978C15.8747 7.61184 16.2495 7.21842 16.2495 6.73947V5.18289C16.2665 4.70395 15.8747 4.32763 15.4149 4.31052Z" fill="#8F9AB0"/>
                        </svg>
                        Paystack
                    </button>
                </div>
                {renderPaymentMethod()}
            </div>
        </div>
    );
};

export default PaymentContent;