/**
 * Essential Blocks Frontend Sanitization Utilities
 * Comprehensive XSS prevention and data sanitization for frontend JavaScript
 * Optimized for frontend usage with minimal dependencies
 */

/**
 * Sanitize any string input to prevent XSS attacks
 * @param {any} input - The input to sanitize
 * @param {string} type - The type of sanitization ('url', 'attribute', 'text', 'html')
 * @returns {string|null} - Sanitized string or null if invalid
 */
export const sanitize = (input, type = 'text') => {
    if (input === null || input === undefined) {
        return null;
    }

    // Convert to string and trim
    const str = String(input).trim();

    if (!str) {
        return type === 'url' ? null : '';
    }

    switch (type) {
        case 'url':
            return sanitizeURL(str);
        case 'attribute':
            return sanitizeAttribute(str);
        case 'html':
            return sanitizeHTML(str);
        case 'text':
        default:
            return sanitizeText(str);
    }
};

/**
 * Sanitize URL to prevent XSS attacks
 * @param {string} url - The URL to sanitize
 * @returns {string|null} - Sanitized URL or null if invalid
 */
export const sanitizeURL = (url) => {
    if (!url || typeof url !== 'string') {
        return null;
    }

    // Remove dangerous protocols and scripts
    const cleanUrl = url
        .replace(/javascript:/gi, '')
        .replace(/data:/gi, '')
        .replace(/vbscript:/gi, '')
        .replace(/on\w+=/gi, '') // Remove event handlers
        .replace(/<script/gi, '')
        .replace(/<\/script>/gi, '');

    // Allow relative URLs and anchors
    if (cleanUrl.startsWith('/') || cleanUrl.startsWith('#')) {
        return cleanUrl;
    }

    try {
        const urlObject = new URL(cleanUrl);

        // Only allow safe protocols
        const allowedProtocols = [
            'http:', 'https:', 'ftp:', 'ftps:', 'mailto:', 'news:', 'irc:', 'irc6:',
            'ircs:', 'gopher:', 'nntp:', 'feed:', 'telnet:', 'mms:', 'rtsp:',
            'sms:', 'svn:', 'tel:', 'fax:', 'xmpp:', 'webcal:', 'urn:'
        ];

        if (!allowedProtocols.includes(urlObject.protocol)) {
            return null;
        }

        return urlObject.toString();
    } catch (error) {
        return null;
    }
};

/**
 * Sanitize HTML attribute values
 * @param {string} value - The attribute value to sanitize
 * @returns {string} - Sanitized attribute value
 */
export const sanitizeAttribute = (value) => {
    if (!value || typeof value !== 'string') {
        return '';
    }

    return value
        .replace(/[<>'"]/g, '') // Remove HTML characters
        .replace(/javascript:/gi, '') // Remove javascript protocol
        .replace(/on\w+=/gi, '') // Remove event handlers
        .replace(/data:/gi, '') // Remove data protocol
        .replace(/vbscript:/gi, '') // Remove vbscript protocol
        .trim();
};

/**
 * Sanitize text content
 * @param {string} text - The text to sanitize
 * @returns {string} - Sanitized text
 */
export const sanitizeText = (text) => {
    if (!text || typeof text !== 'string') {
        return '';
    }

    return text
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '') // Remove script tags
        .replace(/<[^>]*>/g, '') // Remove HTML tags
        .replace(/javascript:/gi, '') // Remove javascript protocol
        .replace(/on\w+=/gi, '') // Remove event handlers
        .trim();
};

/**
 * Sanitize HTML content (more permissive than text)
 * @param {string} html - The HTML to sanitize
 * @returns {string} - Sanitized HTML
 */
export const sanitizeHTML = (html) => {
    if (!html || typeof html !== 'string') {
        return '';
    }

    // Remove dangerous elements and attributes
    return html
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '') // Remove script tags
        .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '') // Remove iframe tags
        .replace(/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi, '') // Remove object tags
        .replace(/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/gi, '') // Remove embed tags
        .replace(/on\w+\s*=\s*["'][^"']*["']/gi, '') // Remove event handlers
        .replace(/javascript:/gi, '') // Remove javascript protocol
        .replace(/vbscript:/gi, '') // Remove vbscript protocol
        .trim();
};

/**
 * Validate target attribute values
 * @param {string} target - The target attribute value
 * @returns {string} - Valid target value
 */
export const sanitizeTarget = (target) => {
    if (!target || typeof target !== 'string') {
        return '_self';
    }

    const allowedTargets = ['_self', '_blank', '_parent', '_top'];
    const cleanTarget = sanitizeAttribute(target);

    return allowedTargets.includes(cleanTarget) ? cleanTarget : '_self';
};

/**
 * Safely get and sanitize data attribute
 * @param {Element} element - The DOM element
 * @param {string} attribute - The data attribute name (without 'data-')
 * @param {string} type - The sanitization type
 * @returns {string|null} - Sanitized attribute value
 */
export const getDataAttribute = (element, attribute, type = 'attribute') => {
    if (!element || !attribute) {
        return null;
    }

    const rawValue = element.getAttribute(`data-${attribute}`);
    return sanitize(rawValue, type);
};

/**
 * Safely set data attribute with sanitization
 * @param {Element} element - The DOM element
 * @param {string} attribute - The data attribute name (without 'data-')
 * @param {string} value - The value to set
 * @param {string} type - The sanitization type
 */
export const setDataAttribute = (element, attribute, value, type = 'attribute') => {
    if (!element || !attribute) {
        return;
    }

    const sanitizedValue = sanitize(value, type);
    if (sanitizedValue !== null) {
        element.setAttribute(`data-${attribute}`, sanitizedValue);
    }
};

/**
 * Sanitize CSS values to prevent CSS injection
 * @param {string} cssValue - The CSS value to sanitize
 * @returns {string} - Sanitized CSS value
 */
export const sanitizeCSS = (cssValue) => {
    if (!cssValue || typeof cssValue !== 'string') {
        return '';
    }

    return cssValue
        .replace(/javascript:/gi, '')
        .replace(/expression\s*\(/gi, '')
        .replace(/@import/gi, '')
        .replace(/behavior\s*:/gi, '')
        .replace(/binding\s*:/gi, '')
        .replace(/<[^>]*>/g, '') // Remove HTML tags
        .trim();
};

/**
 * Log security warnings
 * @param {string} message - The warning message
 * @param {any} data - Additional data to log
 */
export const logSecurityWarning = (message, data = null) => {
    console.warn(`[Essential Blocks Security] ${message}`, data || '');
};

/**
 * Validate and sanitize form input
 * @param {string} input - The form input to validate
 * @param {string} type - The input type ('email', 'url', 'text', 'number')
 * @returns {string|null} - Sanitized input or null if invalid
 */
export const sanitizeFormInput = (input, type = 'text') => {
    if (!input || typeof input !== 'string') {
        return null;
    }

    const cleanInput = sanitizeText(input);

    switch (type) {
        case 'email':
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(cleanInput) ? cleanInput : null;
        case 'url':
            return sanitizeURL(cleanInput);
        case 'number':
            const num = parseFloat(cleanInput);
            return !isNaN(num) ? num.toString() : null;
        case 'text':
        default:
            return cleanInput;
    }
};

/**
 * Batch sanitize an object of values
 * @param {Object} data - Object containing key-value pairs to sanitize
 * @param {Object} rules - Object containing sanitization rules for each key
 * @returns {Object} - Sanitized data object
 */
export const sanitizeObject = (data, rules = {}) => {
    if (!data || typeof data !== 'object') {
        return {};
    }

    const sanitized = {};

    for (const [key, value] of Object.entries(data)) {
        const rule = rules[key] || 'text';
        sanitized[key] = sanitize(value, rule);
    }

    return sanitized;
};

/**
 * Sanitize icon values to prevent XSS attacks (Enhanced version of existing function)
 * @param {string} iconValue - The icon value to sanitize
 * @returns {string} - The sanitized icon value
 */
export const sanitizeIconValue = (iconValue) => {
    if (!iconValue) {
        return '';
    }

    // For Font Awesome icons, only allow valid format (fa-* classes)
    if (iconValue.includes('fa-')) {
        // Validate that it only contains allowed characters for Font Awesome classes
        const faRegex = /^(fa[srb]?)\s+(fa-[a-z0-9-]+)(\s+fa-[a-z0-9-]+)*$/;
        return faRegex.test(iconValue) ? iconValue : '';
    }

    // For Dashicons, only allow valid format (dashicons-* classes)
    if (iconValue.includes('dashicons-')) {
        const dashiconRegex = /^dashicons-[a-z0-9-]+$/;
        return dashiconRegex.test(iconValue) ? iconValue : '';
    }

    // If it's neither Font Awesome nor Dashicon, return empty string
    return '';
};
