/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextareaControl } from '@wordpress/components';
import { memo, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AIContentPopover from '../../components/AI/AIContentPopover';
import AiIcon from '../../icons/ai-icon';

/**
 * EBTextareaControl - A flexible wrapper component for WordPress TextareaControl
 *
 * This component wraps the standard TextareaControl and provides additional functionality:
 * - Custom styling and wrapper elements
 * - Enhanced onChange handling
 * - Optional prefix/suffix elements
 * - Validation support
 * - Easy design modifications
 * - AI content generation support
 *
 * @param {Object} props - Component props
 * @param {string} props.label - Label for the textarea control
 * @param {string} props.value - Current value
 * @param {Function} props.onChange - Change handler
 * @param {string} props.placeholder - Placeholder text
 * @param {string} props.help - Help text
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.wrapperClassName - CSS classes for the wrapper
 * @param {React.ReactNode} props.prefix - Element to show before the textarea
 * @param {React.ReactNode} props.suffix - Element to show after the textarea
 * @param {Function} props.onChangeEnhanced - Enhanced change handler with additional data
 * @param {Function} props.validator - Validation function
 * @param {boolean} props.showValidation - Whether to show validation messages
 * @param {Object} props.wrapperStyle - Inline styles for wrapper
 * @param {boolean} props.disabled - Whether the control is disabled
 * @param {number} props.rows - Number of rows for the textarea
 * @param {boolean} props.enableAi - Whether to show AI functionality
 * @param {string} props.aiContentType - Type of AI content to generate ('text' or 'paragraph')
 * @param {Object} props.otherProps - All other props passed to TextareaControl
 */
const EBTextareaControl = memo(({
    label,
    value = '',
    attrName,
    onChange,
    placeholder,
    help,
    className = '',
    wrapperClassName = '',
    prefix,
    suffix,
    onChangeEnhanced,
    validator,
    showValidation = false,
    wrapperStyle = {},
    disabled = false,
    rows = 4,
    enableAi = true,
    aiContentType = 'paragraph',
    ...otherProps
}) => {
    // State for popover visibility
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const aiIconRef = useRef(null);

    /**
     * Handle AI icon click to toggle popover
     */
    const handleAiIconClick = () => {
        setIsPopoverVisible(!isPopoverVisible);
    };

    /**
     * Handle AI content generation
     */
    const handleAIContentGenerated = (generatedContent) => {
        handleChange(generatedContent);
    };

    /**
     * Handle change event with enhanced functionality
     * @param {string} newValue - The new textarea value
     */
    const handleChange = (newValue) => {
        // Call the standard onChange if provided
        if (onChange) {
            onChange(newValue);
        }

        // Call enhanced onChange with additional data if provided
        if (onChangeEnhanced) {
            const enhancedData = {
                value: newValue,
                previousValue: value,
                isValid: validator ? validator(newValue) : true,
                timestamp: Date.now()
            };
            onChangeEnhanced(enhancedData);
        }
    };

    /**
     * Validate the current value
     * @param {string} val - Value to validate
     * @returns {Object} Validation result with isValid and message
     */
    const validateValue = (val) => {
        if (!validator) {
            return { isValid: true, message: '' };
        }

        const result = validator(val);

        // Handle different validator return types
        if (typeof result === 'boolean') {
            return { isValid: result, message: result ? '' : __('Invalid input', 'essential-blocks') };
        }

        if (typeof result === 'object' && result !== null) {
            return {
                isValid: result.isValid || false,
                message: result.message || ''
            };
        }

        return { isValid: true, message: '' };
    };

    const validation = showValidation ? validateValue(value) : { isValid: true, message: '' };

    // Combine CSS classes
    const wrapperClasses = [
        'eb-textarea-control-wrapper',
        wrapperClassName,
        disabled ? 'eb-textarea-control-wrapper--disabled' : '',
        !validation.isValid ? 'eb-textarea-control-wrapper--invalid' : '',
        prefix ? 'eb-textarea-control-wrapper--has-prefix' : '',
        suffix ? 'eb-textarea-control-wrapper--has-suffix' : ''
    ].filter(Boolean).join(' ');

    const textareaClasses = [
        'eb-textarea-control-input',
        className
    ].filter(Boolean).join(' ');

    const showAiButton = enableAi && window.EssentialBlocksLocalize?.enableWriteAIInputField === "1";

    return (
        <div
            className={wrapperClasses}
            style={wrapperStyle}
        >
            <div className="eb-textarea-control-input-wrapper">
                {showAiButton && (
                    <>
                        <span
                            className="eb-textarea-control-ai"
                            ref={aiIconRef}
                            onClick={handleAiIconClick}
                            style={{ cursor: 'pointer' }}
                        >
                            <AiIcon />
                        </span>
                        <AIContentPopover
                            isVisible={isPopoverVisible}
                            onClose={() => setIsPopoverVisible(false)}
                            anchor={aiIconRef.current}
                            onContentGenerated={handleAIContentGenerated}
                            contentType={aiContentType}
                            currentValue={value}
                        />
                    </>
                )}

                {prefix && (
                    <div className="eb-textarea-control-prefix">
                        {prefix}
                    </div>
                )}

                <TextareaControl
                    label={label}
                    value={value}
                    onChange={handleChange}
                    placeholder={placeholder}
                    help={!validation.isValid ? validation.message : help}
                    className={textareaClasses}
                    disabled={disabled}
                    rows={rows}
                    {...otherProps}
                />

                {suffix && (
                    <div className="eb-textarea-control-suffix">
                        {suffix}
                    </div>
                )}
            </div>

            {showValidation && !validation.isValid && (
                <div className="eb-textarea-control-validation-message">
                    {validation.message}
                </div>
            )}
        </div>
    );
});

// Set display name for debugging
EBTextareaControl.displayName = 'EBTextareaControl';

export default EBTextareaControl;
