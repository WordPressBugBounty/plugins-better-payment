import HeaderCrown from '../../svg/HeaderCrown';
import { __ } from '@wordpress/i18n';

const UpgradePro = () => {
    const features = [
        {
            title: __( 'Recurring Subscriptions', 'better-payment' )
        },
        {
            title: __( 'Subscriber Dashboard', 'better-payment' )
        },
        {
            title: __( 'Split Payments', 'better-payment' )
        },
        {
            title: __( 'In-Depth Analytics', 'better-payment' )
        },
        {
            title: __( 'Fundraising Campaigns', 'better-payment' )
        },
        {
            title: __( 'Premium Invoice', 'better-payment' )
        },
        {
            title: __( 'Unique Form Layouts', 'better-payment' )
        }
    ];
    return (
        <div className="bp-col_active-992 bp-custome_col-6_active-992">
            <div className="margin-bottom_20 padding-20 bp-pro_card">
                <div className="bp-pro_logo">
                    <HeaderCrown />
                </div>
                <h4 className="bp-font_ibm fx-3 card-header">{ __( 'Get Exclusive Features', 'better-payment' ) }</h4>
                <p className="bp-font_ibm fx-5 card-sub_header">
                    { __( 'Add premium functionality & provide users with the smoothest experience.', 'better-payment' ) }
                </p>
                <div className="feature-list">
                    <ul className="bp-pro_feature-list">
                        {features.map((feature, index) => {
                            return (
                                <li className="bp-flex align-items_center bp-pro_feature-list_item" key={index}>
                                    <span className="bp-flex align-items_center  feature-active_logo">
                                        <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.33398 6.98059L6.01017 8.90059L10.2007 4.10059" stroke="#FF9208" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"></path>
                                        </svg>
                                    </span>
                                    <span className="bp-font_ibm fx-4 feature-list_title">{feature.title}</span>
                                </li>
                            );
                        })}
                    </ul>
                    <p className="bp-font_ibm fx-4 more-btn">{ __( 'and more...', 'better-payment' ) }</p>
                </div>
                <a href={'https://betterpayment.co/#pricing-plan'} target="_blank" className="bp-font_inter fx-4 bp-go_to-pro_btn">
                    <span>
                        <svg width="18" height="13" viewBox="0 0 18 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.59597 9.29956C1.2538 7.07544 0.911629 4.85137 0.56946 2.62724C0.493579 2.13421 1.05456 1.79795 1.4536 2.09725C2.51965 2.89679 3.58564 3.69627 4.65169 4.49581C5.00269 4.75906 5.50275 4.67339 5.74609 4.30833L8.40857 0.31458C8.68993 -0.107464 9.31003 -0.107464 9.5914 0.31458L12.2539 4.30833C12.4972 4.67339 12.9973 4.759 13.3483 4.49581C14.4143 3.69627 15.4803 2.89679 16.5464 2.09725C16.9454 1.79795 17.5064 2.13421 17.4306 2.62724C17.0884 4.85137 16.7462 7.07544 16.4041 9.29956H1.59597Z" fill="white"></path>
                            <path d="M15.6298 13.0012H2.36578C1.93941 13.0012 1.59375 12.6555 1.59375 12.2291V10.5332H16.4018V12.2291C16.4018 12.6555 16.0561 13.0012 15.6298 13.0012Z" fill="white"></path>
                        </svg>
                    </span>
                    &nbsp;{ __( 'Upgrade to Pro', 'better-payment' ) }
                </a>
            </div>
        </div>
    );
};

export default UpgradePro;
