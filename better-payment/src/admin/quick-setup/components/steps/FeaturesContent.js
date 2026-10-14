/**
 * FeaturesContent Component
 *
 * Feature enablement configuration
 */

import { FEATURE_TOGGLES } from '../../utils/constants';
import featuresPageBg from '@quick-setup-images/features-page-bg.png';

export default function FeaturesContent({ data, onDataChange, isPro }) {
    const handleToggle = (key) => {
        onDataChange({
            ...data,
            [key]: !data[key]
        });
    };

    return (
        <div className="bp-wizard__content bp-wizard__content--features">
            <div className="bp-features">
                <div className="bp-features__section">
                    <div className="bp-features__details">
                        <div className="bp-features__content">
                            <h2 className="bp-features__title">Everything You Need to Get Paid Smarter At A Glance</h2>
                            <p className="bp-features__description">
                                From collecting 1-click payments to running subscription or fundraising campaigns, you can do it all with Better Payment. Moreover, you are getting transaction details, analytics, and so on.
                            </p>
                        </div>

                        <div className="bp-features__grid">
                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="12" viewBox="0 0 20 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6480_19809)">
                                        <path d="M16.8631 3.38705C16.5371 3.42455 16.2651 3.62411 16.1251 3.90201L16.1224 3.90737C15.9224 4.1846 15.7724 4.51272 15.6924 4.86696L15.6891 4.88505C15.6524 5.05714 15.6318 5.25402 15.6318 5.45625C15.6318 5.46629 15.6318 5.47567 15.6318 5.48571V5.48438C15.6358 5.75424 15.6891 6.01071 15.7844 6.24576L15.7791 6.2317C15.9018 6.55112 16.0658 6.72455 16.2618 6.7654C16.2831 6.76741 16.3084 6.76875 16.3338 6.76875C16.5684 6.76875 16.7791 6.66563 16.9231 6.5029L16.9238 6.50223C17.2458 6.18482 17.4684 5.76696 17.5431 5.29955L17.5444 5.2875C17.5798 5.10603 17.6011 4.89576 17.6024 4.68147V4.68013C17.5991 4.41027 17.5451 4.15379 17.4498 3.91875L17.4551 3.93281C17.3318 3.61339 17.1684 3.44062 16.9718 3.39978C16.9391 3.39308 16.9018 3.38839 16.8631 3.38772V3.38705ZM11.7378 3.38705C11.4118 3.42455 11.1398 3.62411 10.9998 3.90201L10.9971 3.90737C10.7971 4.1846 10.6471 4.51272 10.5671 4.86696L10.5638 4.88505C10.5271 5.05513 10.5058 5.25067 10.5058 5.45089C10.5058 5.46228 10.5058 5.47433 10.5058 5.48571V5.48371C10.5098 5.75357 10.5631 6.01005 10.6584 6.24509L10.6531 6.23103C10.7758 6.55045 10.9391 6.72388 11.1358 6.76473C11.1571 6.76674 11.1824 6.76808 11.2078 6.76808C11.4424 6.76808 11.6531 6.66496 11.7971 6.50223L11.7978 6.50156C12.1198 6.18415 12.3424 5.76629 12.4171 5.29888L12.4184 5.28683C12.4551 5.11607 12.4764 4.9192 12.4764 4.71763C12.4764 4.70424 12.4764 4.69085 12.4764 4.67746V4.67946C12.4731 4.4096 12.4198 4.15313 12.3251 3.91808L12.3298 3.93214C12.2064 3.61272 12.0431 3.43996 11.8464 3.39911C11.8138 3.39241 11.7764 3.38772 11.7378 3.38772V3.38705ZM16.8251 2.01027C16.9824 2.01161 17.1344 2.02969 17.2811 2.06317L17.2671 2.06049C17.8344 2.16897 18.3038 2.52121 18.5704 3.00201L18.5751 3.01138C18.8224 3.44799 18.9684 3.97098 18.9684 4.52746C18.9684 4.56295 18.9678 4.59844 18.9664 4.63393V4.62857C18.9664 4.64062 18.9664 4.65402 18.9664 4.66808C18.9664 5.5125 18.7291 6.30134 18.3184 6.97098L18.3291 6.95223C17.9724 7.67478 17.2498 8.16696 16.4111 8.18371H16.4091C16.2518 8.18237 16.0998 8.16429 15.9531 8.13147L15.9678 8.13415C15.3998 8.02567 14.9304 7.67277 14.6644 7.1913L14.6598 7.18192C14.4118 6.73929 14.2664 6.2096 14.2664 5.64643C14.2664 5.61496 14.2671 5.58281 14.2678 5.55134V5.55603C14.2678 5.5433 14.2678 5.52857 14.2678 5.51317C14.2678 4.6721 14.5051 3.88728 14.9158 3.22165L14.9051 3.2404C15.2658 2.52054 15.9864 2.03036 16.8231 2.00893H16.8258L16.8251 2.01027ZM11.7004 2.01027C11.8578 2.01161 12.0098 2.02969 12.1564 2.06317L12.1424 2.06049C12.7091 2.17098 13.1778 2.52254 13.4458 3.00201L13.4504 3.01138C13.6978 3.44799 13.8438 3.97031 13.8438 4.52746C13.8438 4.56295 13.8431 4.59844 13.8418 4.63393V4.62857C13.8418 4.64062 13.8418 4.65402 13.8418 4.66808C13.8418 5.5125 13.6044 6.30134 13.1931 6.97098L13.2038 6.95223C12.8471 7.67478 12.1244 8.16696 11.2858 8.18371H11.2838C11.1264 8.18237 10.9744 8.16429 10.8278 8.13147L10.8424 8.13415C10.2744 8.02567 9.80511 7.67277 9.53911 7.1913L9.53444 7.18192C9.28644 6.73929 9.14044 6.2096 9.14044 5.64643C9.14044 5.61496 9.14111 5.58281 9.14177 5.55134V5.55603C9.14177 5.5433 9.14177 5.52857 9.14177 5.51384C9.14177 4.67277 9.37911 3.88795 9.79044 3.22232L9.77977 3.24107C10.1404 2.52121 10.8611 2.03103 11.6978 2.0096H11.7004V2.01027ZM8.58911 1.12098C8.75977 1.12433 8.91511 1.18728 9.03577 1.28906L9.03444 1.28839C9.17844 1.39821 9.27377 1.56496 9.28777 1.75513V1.75714C9.28911 1.77455 9.28977 1.79397 9.28977 1.81406C9.28977 1.93728 9.25844 2.05312 9.20377 2.15357L9.20577 2.14955C8.81444 3.01406 8.53444 4.01652 8.41777 5.06853L8.41377 5.11339C8.26377 5.9183 8.17777 6.84442 8.17777 7.79063C8.17777 7.94933 8.18044 8.10804 8.18511 8.2654L8.18444 8.24197C8.18777 8.27277 8.18977 8.30893 8.18977 8.34509C8.18977 8.50513 8.15111 8.6558 8.08377 8.78839L8.08644 8.78304C8.00444 8.94844 7.84377 9.06362 7.65511 9.07835H7.65311C7.65044 9.07835 7.64711 9.07835 7.64377 9.07835C7.38777 9.07835 7.15911 8.96116 7.00844 8.77701L7.00711 8.77567C6.15777 7.81339 5.52911 6.63482 5.21177 5.33371L5.20044 5.27813C4.80133 6.0625 4.40889 6.85045 4.02311 7.64196C3.54177 8.57076 3.13244 9.04621 2.78911 9.07098C2.56844 9.08772 2.38111 8.89888 2.21644 8.50447C1.63711 6.7279 1.16444 4.6192 0.886441 2.45022L0.867775 2.2721C0.861775 2.23661 0.858441 2.19576 0.858441 2.15424C0.858441 1.99219 0.911108 1.84152 1.00044 1.72031L0.999108 1.72232C1.11977 1.5683 1.30511 1.4692 1.51377 1.46786C1.54844 1.46049 1.58777 1.45647 1.62777 1.45647C1.95777 1.45647 2.22511 1.725 2.22511 2.05647C2.22511 2.05714 2.22511 2.05781 2.22511 2.05848C2.47844 3.77478 2.75644 5.22857 3.05044 6.41853L4.84044 2.99598C4.94111 2.73482 5.17111 2.54531 5.44911 2.50379L5.45311 2.50313C5.81244 2.47835 6.03311 2.70804 6.12311 3.19286C6.31577 4.26228 6.58444 5.20513 6.93577 6.10647L6.89977 6.00134C6.98577 4.34933 7.37444 2.80848 8.01111 1.40625L7.97911 1.48527C8.07844 1.27835 8.28044 1.13571 8.51777 1.12433H8.51911C8.54222 1.1221 8.56555 1.12098 8.58911 1.12098ZM1.85777 0.0241071C0.833108 0.0247767 0.00244141 0.859152 0.00244141 1.88839C0.00244141 1.89107 0.00244141 1.89375 0.00244141 1.89643V8.13616C0.00244141 9.16942 0.836441 10.0085 1.86577 10.0085H9.57511L13.0984 11.9792L12.2971 10.0085H18.1338C18.1338 10.0085 18.1344 10.0085 18.1351 10.0085C19.1638 10.0085 19.9978 9.17076 19.9978 8.1375C19.9978 8.13683 19.9978 8.13683 19.9978 8.13616V1.89576C19.9978 1.89509 19.9978 1.89509 19.9978 1.89442C19.9978 0.861161 19.1638 0.0234375 18.1351 0.0234375C18.1344 0.0234375 18.1344 0.0234375 18.1338 0.0234375L1.85777 0.0241071Z" fill="#6E58F7"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6480_19809">
                                        <rect width="20" height="12" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Woo Integrations</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6613_2418)">
                                        <path d="M10 15.8346H5C4.33696 15.8346 3.70107 15.5712 3.23223 15.1024C2.76339 14.6336 2.5 13.9977 2.5 13.3346V6.66797C2.5 6.00493 2.76339 5.36904 3.23223 4.9002C3.70107 4.43136 4.33696 4.16797 5 4.16797H15C15.663 4.16797 16.2989 4.43136 16.7678 4.9002C17.2366 5.36904 17.5 6.00493 17.5 6.66797V10.418" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M2.5 8.33203H17.5" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M13.3335 15.832H18.3335" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M15.8335 13.332L18.3335 15.832L15.8335 18.332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M5.83838 12.5H5.84255" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M9.1665 12.5H10.8332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6613_2418">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Subscriber Dashboard</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6613_17099)">
                                        <path d="M2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H15.8333C16.2754 2.5 16.6993 2.67559 17.0118 2.98816C17.3244 3.30072 17.5 3.72464 17.5 4.16667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333Z" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M9.16797 10.832H10.8346" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M7.5 8.33203H12.5" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M8.33203 5.83203H11.6654" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6613_17099">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Ready Payment Forms</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6613_19814)">
                                        <path d="M2.5 2.5V17.5H17.5" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M16.6665 15V17.5" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M13.3335 13.332V17.4987" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M10 10.832V17.4987" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M6.6665 13.332V17.4987" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M2.5 9.16667C7.5 9.16667 6.66667 5 10 5C13.3333 5 12.5 9.16667 17.5 9.16667" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6613_19814">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Detailed Analytics</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6480_19818)">
                                        <path d="M11.4516 10.7419C11.4516 10.7491 11.4571 10.7455 11.4425 10.7298C11.4265 10.7126 11.3857 10.6824 11.3065 10.6472C11.1417 10.574 10.8737 10.5161 10.5565 10.5161H9.44355C8.87569 10.5161 8.29601 10.4166 7.83468 10.2117C7.37287 10.0064 7 9.67472 7 9.25806C7 8.84141 7.37287 8.50969 7.83468 8.30444C8.29601 8.09948 8.87569 8 9.44355 8H12.2258C12.6534 8 13 8.23108 13 8.51613C13 8.80118 12.6534 9.03226 12.2258 9.03226H9.44355C9.12632 9.03226 8.85832 9.09017 8.69355 9.16331C8.61431 9.19852 8.57355 9.22871 8.55746 9.24597C8.54285 9.26164 8.54839 9.26526 8.54839 9.25806C8.54839 9.25087 8.54285 9.25448 8.55746 9.27016C8.57355 9.28742 8.61431 9.3176 8.69355 9.35282C8.85832 9.42596 9.12632 9.48387 9.44355 9.48387H10.5565C11.1243 9.48387 11.704 9.58336 12.1653 9.78831C12.6271 9.99356 13 10.3253 13 10.7419C13 11.1586 12.6271 11.4903 12.1653 11.6956C11.704 11.9005 11.1243 12 10.5565 12H7.77419C7.34662 12 7 11.7689 7 11.4839C7 11.1988 7.34662 10.9677 7.77419 10.9677H10.5565C10.8737 10.9677 11.1417 10.9098 11.3065 10.8367C11.3857 10.8015 11.4265 10.7713 11.4425 10.754C11.4571 10.7384 11.4516 10.7347 11.4516 10.7419Z" fill="#6E58F7"/>
                                        <path d="M9 13.4074V12.5556C9 12.2283 9.44772 11.963 10 11.963C10.5523 11.963 11 12.2283 11 12.5556V13.4074C11 13.7347 10.5523 14 10 14C9.44772 14 9 13.7347 9 13.4074ZM9 7.44444V6.59259C9 6.26531 9.44772 6 10 6C10.5523 6 11 6.26531 11 6.59259V7.44444C11 7.77172 10.5523 8.03704 10 8.03704C9.44772 8.03704 9 7.77172 9 7.44444Z" fill="#6E58F7"/>
                                        <g clipPath="url(#clip1_6480_19818)">
                                        <path d="M16.6654 8.16854C16.4616 6.70205 15.7813 5.34325 14.7292 4.30145C13.6772 3.25965 12.3118 2.59264 10.8434 2.40316C9.37502 2.21369 7.88505 2.51227 6.60303 3.25291C5.32101 3.99355 4.31807 5.13515 3.7487 6.50187M3.33203 3.16854V6.50187H6.66536" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M3.33203 11.832C3.53583 13.2985 4.21614 14.6573 5.26817 15.6991C6.32019 16.7409 7.68557 17.4079 9.15397 17.5974C10.6224 17.7869 12.1123 17.4883 13.3944 16.7477C14.6764 16.007 15.6793 14.8654 16.2487 13.4987M16.6654 16.832V13.4987H13.332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6480_19818">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        <clipPath id="clip1_6480_19818">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Recurring Payment</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6613_19415)">
                                        <path d="M11.6665 2.5V5.83333C11.6665 6.05435 11.7543 6.26631 11.9106 6.42259C12.0669 6.57887 12.2788 6.66667 12.4998 6.66667H15.8332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M15.8334 10V15.8333C15.8729 16.1478 15.8105 16.4667 15.6555 16.7431C15.5005 17.0196 15.261 17.2392 14.9721 17.3696C14.6833 17.5001 14.3602 17.5346 14.0503 17.468C13.7404 17.4015 13.46 17.2375 13.2501 17C13.1215 16.8355 12.9571 16.7024 12.7694 16.6109C12.5817 16.5193 12.3756 16.4718 12.1668 16.4718C11.958 16.4718 11.7519 16.5193 11.5642 16.6109C11.3764 16.7024 11.2121 16.8355 11.0834 17C10.9548 17.1645 10.7905 17.2976 10.6027 17.3891C10.415 17.4807 10.2089 17.5282 10.0001 17.5282C9.79128 17.5282 9.5852 17.4807 9.39749 17.3891C9.20978 17.2976 9.04539 17.1645 8.91678 17C8.78818 16.8355 8.62378 16.7024 8.43608 16.6109C8.24837 16.5193 8.04228 16.4718 7.83345 16.4718C7.62462 16.4718 7.41853 16.5193 7.23082 16.6109C7.04311 16.7024 6.87872 16.8355 6.75012 17C6.54023 17.2375 6.25979 17.4015 5.9499 17.468C5.64 17.5346 5.31695 17.5001 5.02809 17.3696C4.73923 17.2392 4.49975 17.0196 4.34475 16.7431C4.18975 16.4667 4.12738 16.1478 4.16678 15.8333V4.16667C4.16678 3.72464 4.34238 3.30072 4.65494 2.98816C4.9675 2.67559 5.39142 2.5 5.83345 2.5H11.6668L15.8334 6.66667V10.2083" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6613_19415">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Transaction Invoices</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clipPath="url(#clip0_6613_19832)">
                                        <path d="M10 15.8346H5C4.33696 15.8346 3.70107 15.5712 3.23223 15.1024C2.76339 14.6336 2.5 13.9977 2.5 13.3346V6.66797C2.5 6.00493 2.76339 5.36904 3.23223 4.9002C3.70107 4.43136 4.33696 4.16797 5 4.16797H15C15.663 4.16797 16.2989 4.43136 16.7678 4.9002C17.2366 5.36904 17.5 6.00493 17.5 6.66797V10.418" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M2.5 8.33203H17.5" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M13.3335 15.832H18.3335" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M15.8335 13.332L18.3335 15.832L15.8335 18.332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M5.83838 12.5H5.84255" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M9.1665 12.5H10.8332" stroke="#6E58F7" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_6613_19832">
                                        <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">Split Payment</span>
                            </div>

                            <div className="bp-features__item">
                                <div className="bp-features__item-icon">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.04803 15H1L2.26302 12.304C2.6338 11.5125 3.4787 11 4.41292 11H14L13.3479 12.3921C12.6063 13.9751 10.9165 15 9.04803 15Z" fill="#00009F"/>
                                        <path d="M15.5253 9H4L4.66398 7.60794C5.419 6.02488 7.13937 5 9.04164 5H19L17.7142 7.69603C17.3366 8.48754 16.4765 9 15.5253 9Z" fill="#00009F"/>
                                    </svg>
                                </div>
                                <span className="bp-features__item-label">FluentCart Integration</span>
                            </div>
                        </div>

                        <div className='bp-features__all'>
                            {isPro !== '1' && (
                                <a href="//betterpayment.co/#pricing-plan" target="_blank" className="bp-btn bp-btn--upgrade">
                                    <svg width="17" height="13" viewBox="0 0 17 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.03347 9.30151C0.691298 7.07739 0.349129 4.85332 0.00695999 2.6292C-0.0689213 2.13616 0.49206 1.7999 0.891097 2.09921C1.95715 2.89875 3.02314 3.69823 4.08919 4.49776C4.44019 4.76101 4.94025 4.67535 5.18359 4.31028L7.84607 0.316533C8.12743 -0.105511 8.74753 -0.105511 9.0289 0.316533L11.6914 4.31028C11.9347 4.67535 12.4348 4.76096 12.7858 4.49776C13.8518 3.69823 14.9178 2.89875 15.9839 2.09921C16.3829 1.7999 16.9439 2.13616 16.8681 2.6292C16.5259 4.85332 16.1837 7.07739 15.8416 9.30151H1.03347Z" fill="white"/>
                                    <path d="M15.0673 13.0031H1.80328C1.37691 13.0031 1.03125 12.6575 1.03125 12.2311V10.5352H15.8393V12.2311C15.8393 12.6575 15.4936 13.0031 15.0673 13.0031Z" fill="white"/>
                                </svg>
                                Upgrade To PRO
                            </a>)}
                            
                            <a href="//betterpayment.co/" target="_blank" className="bp-link bp-link--arrow">
                                Explore All Features
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clipPath="url(#clip0_6480_19935)">
                                    <path d="M3.33447 8H12.6678" stroke="#6B59EE" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                    <path d="M8.66553 12L12.6655 8" stroke="#6B59EE" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                    <path d="M8.66553 4L12.6655 8" stroke="#6B59EE" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_6480_19935">
                                    <rect width="16" height="16" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <img src={featuresPageBg} alt="img" />
                </div>
            </div>
        </div>
    );
}
