const Paystack = ( { data, onDataChange } ) => { 
    return (
        <div className="bp-payment__content">
            <div className="bp-payment__field live__mode">
                <div className="bp-payment__field-details">
                    <h3 className="bp-payment__field-title">Live Mode</h3>
                    <p className="bp-payment__field-description">
                        Live mode allows you to process real transactions. You will need Paystack credentials to accept real payments.
                    </p>
                </div>
                <label className="bp-toggle">
                    <input type="checkbox" checked={data?.better_payment_settings_payment_paystack_live_mode === 'yes'} onChange={(e) => onDataChange({ ...data, better_payment_settings_payment_paystack_live_mode: e.target.checked ? 'yes' : 'no' })} />
                    <span className="bp-toggle__slider"></span>
                </label>
            </div>
            {data?.better_payment_settings_payment_paystack_live_mode === 'no' ? (
                <>
                    <div className="bp-payment__field">
                        <label className="bp-label">
                            Test Public Key
                        </label>
                        {/* <p className="bp-payment__field-description">
                            This test Public Key is required to make payments via Paystack.
                        </p> */}
                        <div className="bp-input-wrapper">
                            <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.1309 9.8816H12.892C11.7126 9.8816 10.7563 8.92525 10.7563 7.74509C10.7563 6.56572 11.7126 5.60938 12.892 5.60938H16.1055" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path d="M13.2566 7.69761H13.009" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path d="M4.82675 4.43979H8.21008" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.86587C0.75 2.52857 2.6873 0.75 8.50079 0.75C14.3135 0.75 16.2508 2.52857 16.2508 7.86587C16.2508 13.2024 14.3135 14.9817 8.50079 14.9817C2.6873 14.9817 0.75 13.2024 0.75 7.86587Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                            <input type="text" className="bp-input" placeholder="Public Key" value={data?.better_payment_settings_payment_paystack_test_public} onChange={(e) => onDataChange({ ...data, better_payment_settings_payment_paystack_test_public: e.target.value })} />
                        </div>
                        <p className="bp-payment__field-info">
                            This test Public Key is required to make payments via Paystack. For more help, visit <a href="https://support.paystack.com/en/articles/2123458" className="bp-link" target="_blank">Developer Guide</a> or <a href="//betterpayment.co/docs/set-up-paystack-account-with-better-payment/" className="bp-link" target="_blank">See Documentation.</a>
                        </p>
                    </div>

                    <div className="bp-payment__field">
                        <label className="bp-label">
                            Test Secret Key
                        </label>
                        {/* <p className="bp-payment__field-description">
                            This test Secret Key is required to make payments via Paystack.
                        </p> */}
                        <div className="bp-input-wrapper">
                            <svg width="16" height="18" viewBox="0 0 16 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.83336 6.58594C8.9841 6.58594 9.9167 7.51854 9.9167 8.66927C9.9167 9.82 8.9841 10.7526 7.83336 10.7526C6.68263 10.7526 5.75003 9.82 5.75003 8.66927C5.75003 7.51854 6.68263 6.58594 7.83336 6.58594Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M14.64 4.70854V4.70854C14.0704 3.71499 12.8094 3.37503 11.8238 3.94877C10.9665 4.44678 9.89509 3.82365 9.89509 2.82681C9.89509 1.68016 8.9716 0.75 7.83317 0.75V0.75C6.69474 0.75 5.77125 1.68016 5.77125 2.82681C5.77125 3.82365 4.69984 4.44678 3.84336 3.94877C2.85694 3.37503 1.59593 3.71499 1.0263 4.70854C0.457498 5.70208 0.795022 6.97221 1.78144 7.54512C2.63792 8.04395 2.63792 9.28938 1.78144 9.78821C0.795022 10.3619 0.457498 11.6321 1.0263 12.6248C1.59593 13.6183 2.85694 13.9583 3.84254 13.3854H3.84336C4.69984 12.8866 5.77125 13.5097 5.77125 14.5065V14.5065C5.77125 15.6532 6.69474 16.5833 7.83317 16.5833V16.5833C8.9716 16.5833 9.89509 15.6532 9.89509 14.5065V14.5065C9.89509 13.5097 10.9665 12.8866 11.8238 13.3854C12.8094 13.9583 14.0704 13.6183 14.64 12.6248C15.2097 11.6321 14.8713 10.3619 13.8857 9.78821H13.8849C13.0284 9.28938 13.0284 8.04395 13.8857 7.54512C14.8713 6.97221 15.2097 5.70208 14.64 4.70854Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                            <input type="password" className="bp-input" placeholder="Secret Key" value={data?.better_payment_settings_payment_paystack_test_secret} onChange={(e) => onDataChange({ ...data, better_payment_settings_payment_paystack_test_secret: e.target.value })} />
                        </div>
                        <p className="bp-payment__field-info">
                            This test Secret Key is required to make payments via Paystack. For more help, visit <a href="https://support.paystack.com/en/articles/2123458" className="bp-link" target="_blank">Developer Guide</a> or <a href="//betterpayment.co/docs/set-up-paystack-account-with-better-payment/" className="bp-link" target="_blank">See Documentation.</a>
                        </p>
                    </div>
                </>
                ) : (
                <>
                    <div className="bp-payment__field">
                        <label className="bp-label">
                            Live Public Key
                        </label>
                        {/* <p className="bp-payment__field-description">
                            This live Public Key is required to make payments via Paystack.
                        </p> */}
                        <div className="bp-input-wrapper">
                            <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.1309 9.8816H12.892C11.7126 9.8816 10.7563 8.92525 10.7563 7.74509C10.7563 6.56572 11.7126 5.60938 12.892 5.60938H16.1055" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path d="M13.2566 7.69761H13.009" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path d="M4.82675 4.43979H8.21008" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.86587C0.75 2.52857 2.6873 0.75 8.50079 0.75C14.3135 0.75 16.2508 2.52857 16.2508 7.86587C16.2508 13.2024 14.3135 14.9817 8.50079 14.9817C2.6873 14.9817 0.75 13.2024 0.75 7.86587Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                            <input type="text" className="bp-input" placeholder="Public Key" value={data?.better_payment_settings_payment_paystack_live_public} onChange={(e) => onDataChange({ ...data, better_payment_settings_payment_paystack_live_public: e.target.value })} />
                        </div>
                        <p className="bp-payment__field-info">
                            This live Public Key is required to make payments via Paystack. For more help, visit <a href="https://support.paystack.com/en/articles/2123458" className="bp-link" target="_blank">Developer Guide</a> or <a href="//betterpayment.co/docs/set-up-paystack-account-with-better-payment/" className="bp-link" target="_blank">See Documentation.</a>
                        </p>
                    </div>

                    <div className="bp-payment__field">
                        <label className="bp-label">
                            Live Secret Key
                        </label>
                        {/* <p className="bp-payment__field-description">
                            This live Secret Key is required to make payments via Paystack.
                        </p> */}
                        <div className="bp-input-wrapper">
                            <svg width="16" height="18" viewBox="0 0 16 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.83336 6.58594C8.9841 6.58594 9.9167 7.51854 9.9167 8.66927C9.9167 9.82 8.9841 10.7526 7.83336 10.7526C6.68263 10.7526 5.75003 9.82 5.75003 8.66927C5.75003 7.51854 6.68263 6.58594 7.83336 6.58594Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M14.64 4.70854V4.70854C14.0704 3.71499 12.8094 3.37503 11.8238 3.94877C10.9665 4.44678 9.89509 3.82365 9.89509 2.82681C9.89509 1.68016 8.9716 0.75 7.83317 0.75V0.75C6.69474 0.75 5.77125 1.68016 5.77125 2.82681C5.77125 3.82365 4.69984 4.44678 3.84336 3.94877C2.85694 3.37503 1.59593 3.71499 1.0263 4.70854C0.457498 5.70208 0.795022 6.97221 1.78144 7.54512C2.63792 8.04395 2.63792 9.28938 1.78144 9.78821C0.795022 10.3619 0.457498 11.6321 1.0263 12.6248C1.59593 13.6183 2.85694 13.9583 3.84254 13.3854H3.84336C4.69984 12.8866 5.77125 13.5097 5.77125 14.5065V14.5065C5.77125 15.6532 6.69474 16.5833 7.83317 16.5833V16.5833C8.9716 16.5833 9.89509 15.6532 9.89509 14.5065V14.5065C9.89509 13.5097 10.9665 12.8866 11.8238 13.3854C12.8094 13.9583 14.0704 13.6183 14.64 12.6248C15.2097 11.6321 14.8713 10.3619 13.8857 9.78821H13.8849C13.0284 9.28938 13.0284 8.04395 13.8857 7.54512C14.8713 6.97221 15.2097 5.70208 14.64 4.70854Z" stroke="#899AB2" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                            <input type="password" className="bp-input" placeholder="Secret Key" value={data?.better_payment_settings_payment_paystack_live_secret} onChange={(e) => onDataChange({ ...data, better_payment_settings_payment_paystack_live_secret: e.target.value })} />
                        </div>
                        <p className="bp-payment__field-info">
                            This live Secret Key is required to make payments via Paystack. For more help, visit <a href="https://support.paystack.com/en/articles/2123458" className="bp-link" target="_blank">Developer Guide</a> or <a href="//betterpayment.co/docs/set-up-paystack-account-with-better-payment/" className="bp-link" target="_blank">See Documentation.</a>
                        </p>
                    </div>
                </>
            )}
        </div>
    );
};

export default Paystack;