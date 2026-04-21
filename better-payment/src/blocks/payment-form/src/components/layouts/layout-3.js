import { useState } from '@wordpress/element';
import { getCurrencySymbol, normalizeIconClass, titleToSnake } from '../../helpers/helpers';

const Layout3 = ({ attributes, setAttributes, serialized, pageId }) => {
    const {
        formFields,
        showSidebar,
        currencyAlign,
        showAmountList,
        amountList,
        transactionTitle,
        transactionSubTitle,
        amountText,
        paypalButtonText,
        stripeButtonText,
        paystackButtonText,
        paypalEnabled,
        stripeEnabled,
        paystackEnabled,
        currency,
        paymentSource,
        blockId,
        productName,
        productPrice,
        productPermalink,
        transactionDetailsProductTitle,
    } = attributes;

    // --- Computed layout variables (mirrors layout-vars.php) ---
    const currencySymbol = currency ? getCurrencySymbol(currency) : '';
    const currencyAlignment = currencyAlign || 'left';
    const currencyLeft = currencyAlignment === 'left' ? currencySymbol : '';
    const currencyRight = currencyAlignment === 'right' ? currencySymbol : '';

    const isPaymentTypeWoocommerce = paymentSource === 'woocommerce';
    const isPaymentTypeFluentcart = paymentSource === 'fluentcart';
    const isPaymentTypeStripe = paymentSource === 'stripe';
    const layoutPutAmountFieldHideShow = (isPaymentTypeWoocommerce || isPaymentTypeFluentcart || isPaymentTypeStripe) ? 'is-hidden' : '';
    const layoutDynamicPaymentHideShow = (isPaymentTypeWoocommerce || isPaymentTypeFluentcart || isPaymentTypeStripe) ? '' : 'is-hidden';
    // Show product title only for WooCommerce and FluentCart (not Stripe)
    const showProductTitle = isPaymentTypeWoocommerce || isPaymentTypeFluentcart;

    const enabledCount = [paypalEnabled, stripeEnabled, paystackEnabled].filter(Boolean).length;

    const assetsUrl = window?.betterPaymentBlockData?.assetsUrl || '';

    // Return null if all payment methods are disabled (mirrors Elementor widget behavior)
    if (!paypalEnabled && !stripeEnabled && !paystackEnabled) {
        return null;
    }

    // Track amount entered in the payment amount input field
    const [enteredAmount, setEnteredAmount] = useState('');

    // Track selected payment method for dynamic button display
    const getDefaultPaymentMethod = () => {
        if (paypalEnabled) return 'paypal';
        if (stripeEnabled) return 'stripe';
        if (paystackEnabled) return 'paystack';
        return 'paypal';
    };
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(getDefaultPaymentMethod());

    // Track selected amount from amount list
    const [selectedAmountIndex, setSelectedAmountIndex] = useState(-1);

    // Determine display amount: product price takes precedence when available
    const hasProductPrice = (isPaymentTypeWoocommerce || isPaymentTypeFluentcart || isPaymentTypeStripe) && productPrice !== '';
    const displayAmount = hasProductPrice ? parseFloat(productPrice) : (enteredAmount || '');

    // Handle amount list item click
    const handleAmountSelect = (amountValue, index) => {
        setSelectedAmountIndex(index);
        setEnteredAmount(String(amountValue));
    };

    // Handle payment method change
    const handlePaymentMethodChange = (method) => {
        setSelectedPaymentMethod(method);
    };

    // --- Render helper: icon for a form field ---
    const renderFieldIcon = (item, isPaymentAmountField) => {
        const icon = normalizeIconClass(item.icon || '');
        const isImage = icon.startsWith('http://') || icon.startsWith('https://') || icon.startsWith('/');

        // For payment amount field, always show currency symbol
        if (isPaymentAmountField) {
            return (
                <span className="icon is-medium is-left">
                    <span className="icon is-medium is-left">
                        <span className="bp-currency-symbol">{currencySymbol}</span>
                    </span>
                </span>
            );
        }

        return (
            <span className="icon is-medium is-left">
                {isImage ? (
                    <img src={icon} alt="Icon" width="20" />
                ) : (
                    <i className={icon}></i>
                )}
            </span>
        );
    };

    // --- Render amount list ---
    const renderAmountList = () => {
        if (!showAmountList || !amountList || amountList.length === 0) {
            return null;
        }
        return (
            <>
                {amountList.map((amountItem, idx) => {
                    const amountValue = parseFloat(amountItem.label) || 0;
                    const uniqueId = `bp_payment_amount_${blockId}_${idx}`;
                    return (
                        <div key={idx} className="bp-form__group pt-5">
                            <input
                                type="radio"
                                value={amountValue}
                                id={uniqueId}
                                className="bp-form__control bp-form_pay-radio"
                                name="primary_payment_amount_radio"
                                checked={selectedAmountIndex === idx}
                                onChange={() => handleAmountSelect(amountValue, idx)}
                            />
                            <label htmlFor={uniqueId}>{currencyLeft}{amountValue}{currencyRight}</label>
                        </div>
                    );
                })}
            </>
        );
    };

    return (
        <div className={`better-payment ${blockId ? 'bp-' + blockId : ''}`}>
            <div className="better-payment--wrapper">
                <div className="better-payment--container">
                    <form
                        name={`better-payment-form-${blockId}`}
                        data-better-payment={serialized}
                        className="better-payment-form"
                        id={`better-payment-form-${blockId}`}
                        method="post"
                    >
                        <input type="hidden" name="better_payment_page_id" value={pageId} />
                        <input type="hidden" name="better_payment_widget_id" value={blockId} />

                        <div className="payment-form-layout-3 payment-form-layout">
                            <div className="tile is-ancestor m-0">

                                {/* Sidebar comes FIRST (left) in layout-3 */}
                                {showSidebar && (
                                    <div className="tile is-parent dynamic-amount-section">
                                        <div className="tile is-child">
                                            <div className="dynamic-amount-section-inner has-text-white p-6">
                                                <div className="pt-3 pb-3">
                                                    <p className="bp-dynamic-amount-section-icon-wrap">
                                                        <i className="bp-icon bp-logo-2 is-size-1-fix dynamic-amount-section-icon bp-dynamic-amount-section-icon"></i>
                                                    </p>
                                                    <h4 className="is-size-5-fix pt-5 dynamic-amount-section-title bp-dynamic-amount-section-title">
                                                        {transactionTitle}
                                                    </h4>
                                                    <p className="is-size-6-fix pt-3 dynamic-amount-section-subtitle bp-dynamic-amount-section-sub-title">
                                                        {transactionSubTitle}
                                                    </p>
                                                    <p className="is-size-6-fix pt-2 dynamic-amount-section-subtitle bp-dynamic-amount-section-amount">
                                                        {showProductTitle && (
                                                            <>
                                                                {transactionDetailsProductTitle}{' '}
                                                                <a href={productPermalink || '#'} style={{ textDecoration: 'none', pointerEvents: 'none' }}>
                                                                    {productName}
                                                                </a>
                                                                <br />
                                                            </>
                                                        )}
                                                        {amountText}{' '}
                                                        <span className="bp-transaction-details-amount-text">{displayAmount}</span>
                                                    </p>
                                                    <h4 className="is-size-2-fix pt-5 dynamic-amount-section-amount bp-dynamic-amount-section-amount-summary">
                                                        {currencyLeft}
                                                        <span className="bp-transaction-details-amount-text">{displayAmount}</span>
                                                        {currencyRight}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Form section comes SECOND (right) in layout-3 */}
                                <div className={`tile is-parent has-background-white-fix form-content-section ${showSidebar ? 'is-8' : 'is-12'}`}>
                                    <div className="tile is-child">
                                        <div className="form-content-section-inner p-6">
                                            <div className="pt-3 pb-3">
                                                <div className="form-content-section-fields">
                                                    {/* Payment method radio buttons — always image cards with columns class */}
                                                    <div className="field-payment_method">
                                                        <div className="control bp-radio-box columns">
                                                            {paypalEnabled && (
                                                                <label className={`radio mb-5 payment-method-checkbox column has-text-centered payment-method-checkbox-paypal ${selectedPaymentMethod === 'paypal' ? 'active' : ''}`}>
                                                                    <input type="radio" name="payment_method" className="layout-payment-method-input layout-payment-method-paypal" checked={selectedPaymentMethod === 'paypal'} onChange={() => handlePaymentMethodChange('paypal')} />
                                                                    <img src={`${assetsUrl}/img/paypal.png`} alt="PayPal" />
                                                                </label>
                                                            )}

                                                            {stripeEnabled && (
                                                                <label className={`radio mb-5 payment-method-checkbox payment-method-checkbox-stripe column has-text-centered ${selectedPaymentMethod === 'stripe' ? 'active' : ''}`}>
                                                                    <input type="radio" name="payment_method" className="layout-payment-method-input layout-payment-method-stripe" checked={selectedPaymentMethod === 'stripe'} onChange={() => handlePaymentMethodChange('stripe')} />
                                                                    <img src={`${assetsUrl}/img/stripe.svg`} alt="Stripe" />
                                                                </label>
                                                            )}

                                                            {paystackEnabled && (
                                                                <label className={`radio mb-5 payment-method-checkbox payment-method-checkbox-paystack column has-text-centered ${selectedPaymentMethod === 'paystack' ? 'active' : ''}`}>
                                                                    <input type="radio" name="payment_method" className="layout-payment-method-input layout-payment-method-paystack" checked={selectedPaymentMethod === 'paystack'} onChange={() => handlePaymentMethodChange('paystack')} />
                                                                    <img src={`${assetsUrl}/img/paystack.svg`} alt="Paystack" />
                                                                </label>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Form fields */}
                                                    {formFields.map((item, index) => {
                                                        const { displayInline, fieldWidth, label, placeholder, primaryFieldType, required, show, type } = item;

                                                        const isPaymentAmountField = primaryFieldType === 'primary_payment_amount';
                                                        const isPrimaryEmailField = primaryFieldType === 'primary_email';
                                                        const isPrimaryField = [
                                                            'primary_first_name', 'primary_last_name', 'primary_email',
                                                            'primary_payment_amount', 'primary_coupon_code', 'primary_reference_number'
                                                        ].includes(primaryFieldType);

                                                        const fieldName = isPrimaryField ? primaryFieldType : titleToSnake(label);
                                                        const isRequired = required || isPaymentAmountField || isPrimaryEmailField;
                                                        const requiredClass = isRequired ? ' required' : '';
                                                        const requiredPlaceholder = isRequired ? ' *' : '';
                                                        const visibleClass = show ? '' : ' is-hidden';

                                                        let fieldType = type || 'text';
                                                        if (isPaymentAmountField) fieldType = 'number';
                                                        if (isPrimaryEmailField) fieldType = 'email';

                                                        const fieldPlaceholder = (placeholder || '') + requiredPlaceholder;
                                                        const paymentAmountFieldClass = isPaymentAmountField ? 'bp-custom-payment-amount' : '';
                                                        const inlineClass = displayInline ? ' field-display-inline' : '';
                                                        const inlineStyle = displayInline && fieldWidth ? { width: `${fieldWidth}%` } : {};

                                                        return (
                                                            <div key={index}>
                                                                {isPaymentAmountField && (
                                                                    <div className={`bp-payment-amount-wrap ${layoutPutAmountFieldHideShow}`}>
                                                                        {renderAmountList()}
                                                                    </div>
                                                                )}

                                                                <div
                                                                    className={`better-payment-field-advanced-layout field-${fieldName} pt-5 elementor-repeater-item-${index}${inlineClass} ${paymentAmountFieldClass}${visibleClass}${isPaymentAmountField ? ' ' + layoutPutAmountFieldHideShow : ''}`}
                                                                    style={inlineStyle}
                                                                >
                                                                    <div className="control has-icons-left">
                                                                        <input
                                                                            className={`input is-medium${requiredClass}${visibleClass}`}
                                                                            type={fieldType}
                                                                            placeholder={fieldPlaceholder}
                                                                            name={fieldName}
                                                                            {...(isRequired ? { required: 'required' } : {})}
                                                                            {...(isPaymentAmountField ? { step: 'any', min: '1' } : {})}
                                                                            {...(isPaymentAmountField ? {
                                                                                value: hasProductPrice ? String(parseFloat(productPrice)) : enteredAmount,
                                                                                onChange: (e) => setEnteredAmount(e.target.value),
                                                                                ...(hasProductPrice ? { readOnly: true } : {}),
                                                                            } : {})}
                                                                        />
                                                                        {renderFieldIcon(item, isPaymentAmountField)}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}

                                                    <div className="pb-5"></div>

                                                    {/* Payment buttons */}
                                                    <div className="field-process_payment_button pt-3">
                                                        <div className="control">
                                                            <div className="payment__option">
                                                                {paypalEnabled && (
                                                                    <button type="button" className="button is-medium is-fullwidth better-payment-paypal-bt bp-payment-btn" style={{ display: selectedPaymentMethod === 'paypal' ? 'block' : 'none' }}>
                                                                        {paypalButtonText || 'Proceed to Payment'}
                                                                    </button>
                                                                )}
                                                                {stripeEnabled && (
                                                                    <button type="button" className="button is-medium is-fullwidth better-payment-stripe-bt bp-payment-btn" style={{ display: selectedPaymentMethod === 'stripe' ? 'block' : 'none' }}>
                                                                        {stripeButtonText || 'Proceed to Payment'}
                                                                    </button>
                                                                )}
                                                                {paystackEnabled && (
                                                                    <button type="button" className="button is-medium is-fullwidth better-payment-paystack-bt bp-payment-btn" style={{ display: selectedPaymentMethod === 'paystack' ? 'block' : 'none' }}>
                                                                        {paystackButtonText || 'Proceed to Payment'}
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default Layout3;
