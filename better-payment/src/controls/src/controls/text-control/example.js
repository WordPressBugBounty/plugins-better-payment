/**
 * Example usage of EBTextControl component
 * This file demonstrates various ways to use the EBTextControl
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import EBTextControl from './index';

const EBTextControlExample = () => {
    const [basicValue, setBasicValue] = useState('');
    const [urlValue, setUrlValue] = useState('');
    const [emailValue, setEmailValue] = useState('');
    const [priceValue, setPriceValue] = useState('');
    const [searchValue, setSearchValue] = useState('');
    const [aiTitleValue, setAiTitleValue] = useState('');
    const [aiHeadlineValue, setAiHeadlineValue] = useState('');
    const [secureTextValue, setSecureTextValue] = useState('');

    // Email validator
    const emailValidator = (value) => {
        if (!value) return { isValid: true, message: '' };

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return {
            isValid: emailRegex.test(value),
            message: emailRegex.test(value) ? '' : __('Please enter a valid email address', 'essential-blocks')
        };
    };

    // Price validator
    const priceValidator = (value) => {
        if (!value) return { isValid: true, message: '' };

        const price = parseFloat(value);
        if (isNaN(price)) {
            return {
                isValid: false,
                message: __('Please enter a valid number', 'essential-blocks')
            };
        }
        if (price < 0) {
            return {
                isValid: false,
                message: __('Price cannot be negative', 'essential-blocks')
            };
        }
        if (price > 10000) {
            return {
                isValid: false,
                message: __('Price cannot exceed $10,000', 'essential-blocks')
            };
        }
        return { isValid: true, message: '' };
    };

    // Enhanced change handler for search
    const handleSearchChange = (data) => {

        // Example: Trigger search after user stops typing
        if (data.value.length > 2) {
            console.log('Would trigger search for:', data.value);
        }
    };

    return (
        <div style={{ padding: '20px', maxWidth: '600px' }}>
            <h2>{__('EBTextControl Examples', 'essential-blocks')}</h2>

            {/* Basic Usage */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('1. Basic Usage', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Basic Text Input', 'essential-blocks')}
                    value={basicValue}
                    onChange={setBasicValue}
                    placeholder={__('Enter some text...', 'essential-blocks')}
                    help={__('This is a basic text input with AI support enabled.', 'essential-blocks')}
                />
                <p><strong>Value:</strong> {basicValue}</p>
            </div>

            {/* AI Content Generation */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('2. AI Content Generation', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Product Title (AI-Powered)', 'essential-blocks')}
                    value={aiTitleValue}
                    onChange={setAiTitleValue}
                    enableAi={true}
                    aiContentType="text"
                    placeholder={__('Enter product title or click AI icon to generate...', 'essential-blocks')}
                    help={__('Click the AI icon to generate creative product titles.', 'essential-blocks')}
                />
                <p><strong>Generated Title:</strong> {aiTitleValue}</p>
            </div>

            {/* AI Headlines */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('3. AI Headlines', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Marketing Headline', 'essential-blocks')}
                    value={aiHeadlineValue}
                    onChange={setAiHeadlineValue}
                    enableAi={true}
                    aiContentType="text"
                    placeholder={__('Generate catchy headlines with AI...', 'essential-blocks')}
                    help={__('Perfect for creating engaging marketing headlines.', 'essential-blocks')}
                />
                <p><strong>Headline:</strong> {aiHeadlineValue}</p>
            </div>

            {/* With Prefix and Suffix */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('4. With Prefix and Suffix', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Website URL', 'essential-blocks')}
                    value={urlValue}
                    onChange={setUrlValue}
                    prefix={<span>https://</span>}
                    suffix={<span>.com</span>}
                    placeholder={__('example', 'essential-blocks')}
                    help={__('Enter your website domain name.', 'essential-blocks')}
                />
                <p><strong>Full URL:</strong> https://{urlValue}.com</p>
            </div>

            {/* With Validation */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('5. With Email Validation', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Email Address', 'essential-blocks')}
                    value={emailValue}
                    onChange={setEmailValue}
                    validator={emailValidator}
                    showValidation={true}
                    type="email"
                    placeholder={__('user@example.com', 'essential-blocks')}
                    help={__('Enter a valid email address.', 'essential-blocks')}
                />
                <p><strong>Valid:</strong> {emailValidator(emailValue).isValid ? 'Yes' : 'No'}</p>
            </div>

            {/* Complex Example */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('6. Price Input with Validation', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Product Price', 'essential-blocks')}
                    value={priceValue}
                    onChange={setPriceValue}
                    prefix={<span>$</span>}
                    suffix={<span>USD</span>}
                    validator={priceValidator}
                    showValidation={true}
                    type="number"
                    placeholder="0.00"
                    help={__('Enter the product price in USD (max $10,000).', 'essential-blocks')}
                    wrapperClassName="price-input-example"
                />
                <p><strong>Price:</strong> ${priceValue} USD</p>
            </div>

            {/* Enhanced onChange */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('7. With Enhanced onChange', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Search Query', 'essential-blocks')}
                    value={searchValue}
                    onChange={setSearchValue}
                    onChangeEnhanced={handleSearchChange}
                    placeholder={__('Search...', 'essential-blocks')}
                    help={__('Check the console for enhanced change data.', 'essential-blocks')}
                    prefix={<span>🔍</span>}
                />
                <p><strong>Search:</strong> {searchValue}</p>
            </div>

            {/* Without AI */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('8. Without AI Features', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Simple Text Input', 'essential-blocks')}
                    value={basicValue}
                    onChange={setBasicValue}
                    enableAi={false}
                    placeholder={__('No AI features here...', 'essential-blocks')}
                    help={__('This input has AI features disabled.', 'essential-blocks')}
                />
            </div>

            {/* Custom Styling */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('9. Custom Styling', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Custom Styled Input', 'essential-blocks')}
                    value={basicValue}
                    onChange={setBasicValue}
                    wrapperClassName="custom-wrapper-example"
                    className="custom-input-example"
                    wrapperStyle={{
                        border: '2px solid #007cba',
                        borderRadius: '8px',
                        padding: '8px',
                        backgroundColor: '#f8f9fa'
                    }}
                    placeholder={__('Custom styled input...', 'essential-blocks')}
                    help={__('This input has custom wrapper styling.', 'essential-blocks')}
                />
            </div>

            {/* URL Field with Real-time Validation */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('10. URL Field with Real-time Validation', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Website URL', 'essential-blocks')}
                    value={urlValue}
                    onChange={setUrlValue}
                    fieldType="url"
                    placeholder={__('https://example.com', 'essential-blocks')}
                    help={__('Real-time URL validation: validates on every keystroke when URL looks complete.', 'essential-blocks')}
                    onChangeEnhanced={(data) => {
                        console.log('Real-time validation:', {
                            value: data.value,
                            isValid: data.isValid,
                            message: data.validationMessage,
                            fieldType: data.fieldType
                        });
                    }}
                />
                <p><strong>URL:</strong> {urlValue}</p>
                <p style={{ fontSize: '12px', color: '#666' }}>
                    {__('Check console for real-time validation feedback on every keystroke!', 'essential-blocks')}
                </p>
            </div>

            {/* Security Filtering */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('11. Text with Security Filtering', 'essential-blocks')}</h3>
                <EBTextControl
                    label={__('Secure Text Input', 'essential-blocks')}
                    value={secureTextValue}
                    onChange={setSecureTextValue}
                    enableSecurity={true}
                    showValidation={true}
                    placeholder={__('Try entering script tags or javascript...', 'essential-blocks')}
                    help={__('This input filters out potentially dangerous content like script tags.', 'essential-blocks')}
                />
                <p><strong>Filtered Value:</strong> {secureTextValue}</p>
            </div>

            {/* Prop-Controlled Field Types */}
            <div style={{ marginBottom: '30px' }}>
                <h3>{__('12. Prop-Controlled Field Types', 'essential-blocks')}</h3>
                <div style={{ marginBottom: '15px' }}>
                    <h4>{__('Text Field (Default)', 'essential-blocks')}</h4>
                    <EBTextControl
                        label={__('Text Field', 'essential-blocks')}
                        value={basicValue}
                        onChange={setBasicValue}
                        fieldType="text"
                        showValidation={true}
                        placeholder={__('Enter text...', 'essential-blocks')}
                        help={__('fieldType="text" - Standard text input', 'essential-blocks')}
                    />
                </div>
                <div>
                    <h4>{__('URL Field', 'essential-blocks')}</h4>
                    <EBTextControl
                        label={__('URL Field', 'essential-blocks')}
                        value={urlValue}
                        onChange={setUrlValue}
                        fieldType="url"
                        showValidation={true}
                        placeholder={__('https://example.com', 'essential-blocks')}
                        help={__('fieldType="url" - Automatic URL validation and security', 'essential-blocks')}
                    />
                </div>
            </div>
        </div>
    );
};

export default EBTextControlExample;
