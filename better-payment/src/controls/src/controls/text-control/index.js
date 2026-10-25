/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { memo, useState, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AIContentPopover from '../../components/AI/AIContentPopover';
import AiIcon from '../../icons/ai-icon';
import { sanitizeURL } from '../../helpers';

/**
 * EBTextControl - A flexible wrapper component for WordPress TextControl
 *
 * This component wraps the standard TextControl and provides additional functionality:
 * - Custom styling and wrapper elements
 * - Enhanced onChange handling
 * - Optional prefix/suffix elements
 * - Validation support
 * - Easy design modifications
 *
 * @param {Object} props - Component props
 * @param {string} props.label - Label for the text control
 * @param {string} props.value - Current value
 * @param {Function} props.onChange - Change handler
 * @param {string} props.placeholder - Placeholder text
 * @param {string} props.help - Help text
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.wrapperClassName - CSS classes for the wrapper
 * @param {React.ReactNode} props.prefix - Element to show before the input
 * @param {React.ReactNode} props.suffix - Element to show after the input
 * @param {Function} props.onChangeEnhanced - Enhanced change handler with additional data
 * @param {Function} props.validator - Validation function
 * @param {boolean} props.showValidation - Whether to show validation messages
 * @param {Object} props.wrapperStyle - Inline styles for wrapper
 * @param {boolean} props.disabled - Whether the control is disabled
 * @param {string} props.type - Input type (text, email, password, etc.)
 * @param {boolean} props.enableAi - Whether to show AI functionality
 * @param {string} props.aiContentType - Type of AI content to generate ('text' or 'paragraph')
 * @param {string} props.fieldType - Field type ('text' or 'url'), defaults to 'text'
 * @param {boolean} props.enableSecurity - Whether to enable security filtering (auto-enabled for URL fields)
 * @param {Object} props.otherProps - All other props passed to TextControl
 */
const EBTextControl = memo(({
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
    type = 'text',
    enableAi = true,
    aiContentType = 'text',
    fieldType = 'text',
    enableSecurity = false,
    ...otherProps
}) => {
    // State for popover visibility
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const aiIconRef = useRef(null);

    // State for real-time validation
    const [realTimeValidation, setRealTimeValidation] = useState({ isValid: true, message: '' });
    const [isTyping, setIsTyping] = useState(false);
    const [debouncedValue, setDebouncedValue] = useState(value);
    // Manage a local value to keep caret stable while typing
    const [internalValue, setInternalValue] = useState(value);
    const [isFocused, setIsFocused] = useState(false);

    // Determine if security filtering should be enabled
    const shouldEnableSecurity = enableSecurity || fieldType === 'url';

    /**
     * Validate the current value
     * @param {string} valueToValidate - Value to validate
     * @returns {Object} Validation result
     */
    const validateValue = (valueToValidate) => {
        // First check URL validation for URL fields
        if (fieldType === 'url') {
            const urlValidation = validateURL(valueToValidate);
            if (!urlValidation.isValid) {
                return urlValidation;
            }
        }

        // Then check custom validator if provided
        if (!validator) {
            return { isValid: true, message: '' };
        }

        const result = validator(valueToValidate);

        // Handle different validator return types
        if (typeof result === 'boolean') {
            return {
                isValid: result,
                message: result ? '' : __('Invalid input', 'essential-blocks')
            };
        }

        if (typeof result === 'object') {
            return result;
        }

        return { isValid: true, message: '' };
    };

    /**
     * Perform real-time validation as user types
     * @param {string} inputValue - The input value to validate
     */
    const performRealTimeValidation = (inputValue) => {
        if (fieldType === 'url') {
            const urlValidation = validateURL(inputValue);
            setRealTimeValidation(urlValidation);
            setIsTyping(false);
        } else if (showValidation && validator) {
            const validationResult = validateValue(inputValue);
            setRealTimeValidation(validationResult);
            setIsTyping(false);
        } else {
            setRealTimeValidation({ isValid: true, message: '' });
            setIsTyping(false);
        }
    };

    // Effect to debounce value changes for validation
    useEffect(() => {
        const timer = setTimeout(() => {
            // Prefer internalValue while focused to avoid parent-driven jumps
            setDebouncedValue(isFocused ? internalValue : value);
        }, 300); // 300ms debounce

        return () => clearTimeout(timer);
    }, [value, internalValue, isFocused]);

    // Effect to validate when debounced value changes
    useEffect(() => {
        if (fieldType === 'url' || showValidation) {
            performRealTimeValidation(debouncedValue);
        }
    }, [debouncedValue, fieldType, showValidation]);

    /**
     * Check if a URL looks complete enough to be sanitized
     * @param {string} url - The URL to check
     * @returns {boolean} - Whether the URL looks complete
     */
    const isUrlComplete = (url) => {
        // Allow relative URLs and anchors immediately
        if (url.startsWith('/') || url.startsWith('#')) {
            return true;
        }

        // For protocol URLs, check if they have a complete domain part
        if (url.startsWith('http://') || url.startsWith('https://')) {
            const domainPart = url.substring(url.indexOf('://') + 3);
            // Must have domain.tld format and look like a complete URL
            if (domainPart.includes('.')) {
                const parts = domainPart.split('/')[0].split('.'); // Get domain part before any path
                const tld = parts[parts.length - 1];
                // Only consider complete if:
                // 1. Has at least 2 parts (domain.tld)
                // 2. TLD is at least 3 characters OR it's followed by a path/query
                // 3. Doesn't end with a dot
                return parts.length >= 2 &&
                    !parts[parts.length - 1].includes('.') &&
                    (tld.length >= 3 || domainPart.includes('/') || domainPart.includes('?') || domainPart.includes('#'));
            }
            return false;
        }

        if (url.startsWith('ftp://') || url.startsWith('ftps://')) {
            const domainPart = url.substring(url.indexOf('://') + 3);
            if (domainPart.includes('.')) {
                const parts = domainPart.split('/')[0].split('.');
                return parts.length >= 2 && parts[parts.length - 1].length >= 2 && !parts[parts.length - 1].includes('.');
            }
            return false;
        }

        if (url.startsWith('mailto:')) {
            return url.includes('@') && url.includes('.', url.indexOf('@'));
        }

        if (url.startsWith('tel:')) {
            return url.length > 4; // Has some number after tel:
        }

        // For URLs without protocol, check if they look like a complete domain
        if (url.includes('.')) {
            const parts = url.split('.');
            const tld = parts[parts.length - 1];
            // Must have at least domain.tld format and look complete
            // Only consider complete if TLD is 3+ characters (com, org, net) or it's a known 2-char TLD with context
            return parts.length >= 2 &&
                tld.length >= 3 &&
                !url.endsWith('.') &&
                parts.every(part => part.length > 0); // No empty parts
        }

        return false;
    };

    /**
     * Check if input contains potentially dangerous content
     * @param {string} input - The input to check
     * @returns {boolean} - Whether input contains dangerous content
     */
    const containsDangerousContent = (input) => {
        const dangerousPatterns = [
            /javascript:/gi,
            /data:/gi,
            /vbscript:/gi,
            /<script/gi,
            /on\w+\s*=/gi, // Event handlers like onclick=
            /<iframe/gi,
            /<object/gi,
            /<embed/gi
        ];

        return dangerousPatterns.some(pattern => pattern.test(input));
    };

    /**
     * Security filter for text input
     * @param {string} inputValue - The input value to filter
     * @returns {string} - The filtered value
     */
    const securityFilter = (inputValue) => {
        if (!shouldEnableSecurity) {
            return inputValue;
        }

        // Handle empty values - don't sanitize empty strings to avoid '#' replacement
        if (!inputValue || inputValue.trim() === '') {
            return inputValue;
        }

        // For URL fields
        if (fieldType === 'url') {
            // First, check for dangerous content and remove it immediately
            if (containsDangerousContent(inputValue)) {
                // Remove dangerous patterns but preserve the rest
                let cleaned = inputValue
                    .replace(/javascript:/gi, '')
                    .replace(/data:/gi, '')
                    .replace(/vbscript:/gi, '')
                    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                    .replace(/on\w+\s*=/gi, '')
                    .replace(/<iframe\b[^>]*>/gi, '')
                    .replace(/<object\b[^>]*>/gi, '')
                    .replace(/<embed\b[^>]*>/gi, '');

                // If after cleaning it's empty or just whitespace, return empty
                if (!cleaned.trim()) {
                    return '';
                }

                inputValue = cleaned;
            }

            // Preserve user input while typing to avoid caret jumps.
            // Do NOT normalize/sanitize to canonical form here; only strip dangerous patterns above.
            // We'll sanitize/normalize onBlur instead.
            return inputValue;
        }

        // For text fields with security enabled, basic XSS prevention
        return inputValue
            .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '') // Remove script tags
            .replace(/javascript:/gi, '') // Remove javascript: protocol
            .replace(/on\w+\s*=/gi, ''); // Remove event handlers
    };

    /**
     * URL validation function with real-time feedback
     * @param {string} url - The URL to validate
     * @returns {Object} - Validation result
     */
    const validateURL = (url) => {
        if (!url || url.trim() === '') {
            return { isValid: true, message: '' };
        }

        // Check for dangerous content first
        if (containsDangerousContent(url)) {
            return {
                isValid: false,
                message: __('URL contains potentially dangerous content.', 'essential-blocks')
            };
        }

        try {
            // Allow relative URLs and anchors
            if (url.startsWith('/') || url.startsWith('#')) {
                return { isValid: true, message: '' };
            }

            // Provide real-time feedback for incomplete URLs
            if (!isUrlComplete(url)) {
                // Give helpful hints for common URL patterns
                if (url.startsWith('http') && !url.includes('://')) {
                    return {
                        isValid: true,
                        message: __('Continue typing... (e.g., http://example.com)', 'essential-blocks')
                    };
                }
                if (url.startsWith('https') && !url.includes('://')) {
                    return {
                        isValid: true,
                        message: __('Continue typing... (e.g., https://example.com)', 'essential-blocks')
                    };
                }
                if (url.includes('://') && !url.includes('.')) {
                    return {
                        isValid: true,
                        message: __('Add domain extension (e.g., .com, .org)', 'essential-blocks')
                    };
                }
                if (url.includes('.') && url.split('.').length >= 2) {
                    const parts = url.split('.');
                    const tld = parts[parts.length - 1];
                    if (tld.length < 2) {
                        return {
                            isValid: true,
                            message: __('Complete the domain extension', 'essential-blocks')
                        };
                    }
                }
                return { isValid: true, message: '' };
            }

            // Check for valid URL format
            const urlObject = new URL(url);

            // Check if protocol is allowed
            const allowedProtocols = ['http:', 'https:', 'ftp:', 'ftps:', 'mailto:', 'tel:'];
            if (!allowedProtocols.includes(urlObject.protocol)) {
                return {
                    isValid: false,
                    message: __('Invalid URL protocol. Only HTTP, HTTPS, FTP, FTPS, mailto, and tel are allowed.', 'essential-blocks')
                };
            }

            return {
                isValid: true,
                message: __('Valid URL', 'essential-blocks')
            };
        } catch (error) {
            // Only show error if URL looks like it should be complete
            if (isUrlComplete(url)) {
                return {
                    isValid: false,
                    message: __('Please enter a valid URL format.', 'essential-blocks')
                };
            }
            // Otherwise, allow continued typing
            return { isValid: true, message: '' };
        }
    };

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
     * @param {string} newValue - The new input value
     */
    const handleChange = (newValue) => {
        // Set typing state to true to indicate user is actively typing
        setIsTyping(true);

        // Apply security filtering
        const filteredValue = securityFilter(newValue);

        // Update local state to keep caret stable while typing
        setInternalValue(filteredValue);

        // For dangerous content, provide immediate feedback
        if (fieldType === 'url' && containsDangerousContent(newValue)) {
            setRealTimeValidation({
                isValid: false,
                message: __('URL contains potentially dangerous content.', 'essential-blocks')
            });
        } else {
            // For other validation, it will be handled by the debounced effect
            // Reset typing state after a short delay
            setTimeout(() => setIsTyping(false), 100);
        }

        // Avoid unnecessary parent updates that can cause caret jumps.
        // Only propagate when the effective value actually changed.
        const hasChanged = filteredValue !== value;

        if (hasChanged && onChange) {
            // Only sync to parent when not focused; otherwise do it on blur
            onChange(filteredValue);
        }

        // Call enhanced onChange with additional data if provided
        if (onChangeEnhanced) {
            // Get real-time validation for the filtered value
            const validationResult = fieldType === 'url' || showValidation ? validateValue(filteredValue) : { isValid: true, message: '' };
            const enhancedData = {
                value: filteredValue,
                previousValue: value,
                isValid: validator ? validator(filteredValue) : validationResult.isValid,
                validationMessage: validationResult.message,
                fieldType: fieldType,
                timestamp: Date.now(),
                focused: isFocused
            };
            onChangeEnhanced(enhancedData);
        }
    };

    // Use real-time validation state for immediate feedback
    const validation = realTimeValidation;

    // Combine CSS classes
    const wrapperClasses = [
        'eb-text-control-wrapper',
        wrapperClassName,
        disabled ? 'eb-text-control-wrapper--disabled' : '',
        !validation.isValid ? 'eb-text-control-wrapper--invalid' : '',
        validation.isValid && validation.message ? 'eb-text-control-wrapper--valid' : '',
        prefix ? 'eb-text-control-wrapper--has-prefix' : '',
        suffix ? 'eb-text-control-wrapper--has-suffix' : '',
        fieldType === 'url' ? 'eb-text-control-wrapper--url' : '',
        shouldEnableSecurity ? 'eb-text-control-wrapper--secure' : '',
        isTyping ? 'eb-text-control-wrapper--typing' : ''
    ].filter(Boolean).join(' ');

    const inputClasses = [
        'eb-text-control-input',
        className
    ].filter(Boolean).join(' ');

    const showAiButton = enableAi && window.EssentialBlocksLocalize?.enableWriteAIInputField === "1";

    return (
        <div
            className={wrapperClasses}
            style={wrapperStyle}
        >
            <div className="eb-text-control-input-wrapper">
                {fieldType !== 'url' && showAiButton && (
                    <>
                        <span
                            className="eb-text-control-ai"
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
                    <div className="eb-text-control-prefix">
                        {prefix}
                    </div>
                )}

                <TextControl
                    label={label}
                    value={isFocused ? internalValue : value}
                    onChange={handleChange}
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => {
                        setIsFocused(false);
                        // Sync latest local value to parent on blur
                        if (onChange) {
                            const finalValue = fieldType === 'url' ? securityFilter(internalValue) : internalValue;
                            if (finalValue !== value) onChange(finalValue);
                        }
                    }}
                    placeholder={placeholder}
                    help={help}
                    className={inputClasses}
                    disabled={disabled}
                    type={fieldType === 'url' ? 'url' : type}
                    {...otherProps}
                />

                {suffix && (
                    <div className="eb-text-control-suffix">
                        {suffix}
                    </div>
                )}
            </div>

            {/* Show validation messages for URL fields or when showValidation is true */}
            {(fieldType === 'url' || showValidation) && validation.message && (
                <div className={`eb-text-control-validation-message ${validation.isValid ? 'eb-text-control-validation-message--success' : 'eb-text-control-validation-message--error'
                    }`}>
                    {validation.message}
                </div>
            )}
        </div>
    );
});

// Set display name for debugging
EBTextControl.displayName = 'EBTextControl';

export default EBTextControl;
