/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import EBTextareaControl from './index';

/**
 * Example usage of EBTextareaControl component
 */
const EBTextareaControlExample = () => {
    // Basic usage state
    const [basicValue, setBasicValue] = useState('');

    // Advanced usage state
    const [advancedValue, setAdvancedValue] = useState('');

    // Validation example state
    const [validatedValue, setValidatedValue] = useState('');

    // AI content example state
    const [aiContentValue, setAiContentValue] = useState('');

    /**
     * Content validator - requires minimum 50 characters
     */
    const contentValidator = (value) => {
        const minLength = 50;
        return {
            isValid: value.length >= minLength,
            message: value.length >= minLength
                ? ''
                : __(`Content must be at least ${minLength} characters long`, 'essential-blocks')
        };
    };

    /**
     * Enhanced onChange handler example
     */
    const handleEnhancedChange = (data) => {
        console.log('Enhanced change data:', data);
        // data contains: { value, previousValue, isValid, timestamp }

        // Example: Auto-save after user stops typing
        if (data.value.length > 100) {
            console.log('Auto-saving content...');
        }
    };

    return (
        <div style={{ padding: '20px', maxWidth: '800px' }}>
            <h2>{__('EBTextareaControl Examples', 'essential-blocks')}</h2>

            {/* Basic Usage */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('1. Basic Usage', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Basic Textarea', 'essential-blocks')}
                    value={basicValue}
                    onChange={setBasicValue}
                    placeholder={__('Enter your content here...', 'essential-blocks')}
                    help={__('This is a basic textarea control with AI support enabled.', 'essential-blocks')}
                    rows={6}
                />
                <p><strong>{__('Current value:', 'essential-blocks')}</strong> {basicValue}</p>
                <p><strong>{__('Character count:', 'essential-blocks')}</strong> {basicValue.length}</p>
            </div>

            {/* AI Content Generation */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('2. AI Content Generation', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('AI-Powered Content', 'essential-blocks')}
                    value={aiContentValue}
                    onChange={setAiContentValue}
                    placeholder={__('Click the AI icon to generate content...', 'essential-blocks')}
                    enableAi={true}
                    aiContentType="paragraph"
                    rows={10}
                    help={__('Use the AI icon to generate content automatically. Try prompts like "Write a product description for..." or "Create a blog post about...".', 'essential-blocks')}
                />
                <p><strong>{__('Generated content length:', 'essential-blocks')}</strong> {aiContentValue.length} {__('characters', 'essential-blocks')}</p>
            </div>

            {/* Advanced Usage with Validation */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('3. With Validation', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Validated Content', 'essential-blocks')}
                    value={validatedValue}
                    onChange={setValidatedValue}
                    placeholder={__('Write at least 50 characters...', 'essential-blocks')}
                    validator={contentValidator}
                    showValidation={true}
                    rows={8}
                    wrapperStyle={{
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        padding: '16px'
                    }}
                />
                <p><strong>{__('Character count:', 'essential-blocks')}</strong> {validatedValue.length}</p>
                <p><strong>{__('Valid:', 'essential-blocks')}</strong> {contentValidator(validatedValue).isValid ? __('Yes', 'essential-blocks') : __('No', 'essential-blocks')}</p>
            </div>

            {/* Enhanced onChange Example */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('4. Enhanced onChange Handler', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Advanced Textarea', 'essential-blocks')}
                    value={advancedValue}
                    onChange={setAdvancedValue}
                    onChangeEnhanced={handleEnhancedChange}
                    placeholder={__('Type here to see enhanced change data in console...', 'essential-blocks')}
                    rows={6}
                    help={__('Check the browser console to see enhanced change data.', 'essential-blocks')}
                />
            </div>

            {/* With Prefix and Suffix */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('5. With Prefix and Suffix', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Code Block', 'essential-blocks')}
                    value={advancedValue}
                    onChange={setAdvancedValue}
                    placeholder={__('Enter your code here...', 'essential-blocks')}
                    prefix={<span style={{ color: '#666', fontSize: '12px' }}>```</span>}
                    suffix={<span style={{ color: '#666', fontSize: '12px' }}>```</span>}
                    rows={8}
                    className="code-textarea"
                />
            </div>

            {/* Without AI */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('6. Without AI Features', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Simple Textarea', 'essential-blocks')}
                    value={basicValue}
                    onChange={setBasicValue}
                    placeholder={__('No AI features here...', 'essential-blocks')}
                    enableAi={false}
                    rows={5}
                    help={__('This textarea has AI features disabled.', 'essential-blocks')}
                />
            </div>

            {/* Disabled State */}
            <div style={{ marginBottom: '40px' }}>
                <h3>{__('7. Disabled State', 'essential-blocks')}</h3>
                <EBTextareaControl
                    label={__('Disabled Textarea', 'essential-blocks')}
                    value={__('This textarea is disabled and cannot be edited', 'essential-blocks')}
                    onChange={() => { }}
                    disabled={true}
                    rows={4}
                    help={__('This textarea is in disabled state.', 'essential-blocks')}
                />
            </div>
        </div>
    );
};

export default EBTextareaControlExample;
