/**
 * Essential Blocks Sanitization Utilities
 * Comprehensive XSS prevention and data sanitization for frontend and backend
 */

/**
 * Essential Blocks Sanitization Object
 * Provides comprehensive XSS prevention and data sanitization utilities
 */
export const EBSanitization = {
    /**
     * Sanitize any string input to prevent XSS attacks
     * @param {any} input - The input to sanitize
     * @param {string} type - The type of sanitization ('url', 'attribute', 'text', 'html')
     * @returns {string|null} - Sanitized string or null if invalid
     */
    sanitize(input, type = 'text') {
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
                return this.sanitizeURL(str);
            case 'attribute':
                return this.sanitizeAttribute(str);
            case 'html':
                return this.sanitizeHTML(str);
            case 'text':
            default:
                return this.sanitizeText(str);
        }
    },

    /**
     * Sanitize URL to prevent XSS attacks
     * @param {string} url - The URL to sanitize
     * @returns {string|null} - Sanitized URL or null if invalid
     */
    sanitizeURL(url) {
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
    },

    /**
     * Sanitize HTML attribute values
     * @param {string} value - The attribute value to sanitize
     * @returns {string} - Sanitized attribute value
     */
    sanitizeAttribute(value) {
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
    },

    /**
     * Sanitize text content
     * @param {string} text - The text to sanitize
     * @returns {string} - Sanitized text
     */
    sanitizeText(text) {
        if (!text || typeof text !== 'string') {
            return '';
        }

        return text
            .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '') // Remove script tags
            .replace(/<[^>]*>/g, '') // Remove HTML tags
            .replace(/javascript:/gi, '') // Remove javascript protocol
            .replace(/on\w+=/gi, '') // Remove event handlers
            .trim();
    },

    /**
     * Sanitize HTML content (more permissive than text)
     * @param {string} html - The HTML to sanitize
     * @returns {string} - Sanitized HTML
     */
    sanitizeHTML(html) {
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
    },

    /**
     * Validate target attribute values
     * @param {string} target - The target attribute value
     * @returns {string} - Valid target value
     */
    sanitizeTarget(target) {
        if (!target || typeof target !== 'string') {
            return '_self';
        }
        
        const allowedTargets = ['_self', '_blank', '_parent', '_top'];
        const cleanTarget = this.sanitizeAttribute(target);
        
        return allowedTargets.includes(cleanTarget) ? cleanTarget : '_self';
    },

    /**
     * Safely get and sanitize data attribute
     * @param {Element} element - The DOM element
     * @param {string} attribute - The data attribute name (without 'data-')
     * @param {string} type - The sanitization type
     * @returns {string|null} - Sanitized attribute value
     */
    getDataAttribute(element, attribute, type = 'attribute') {
        if (!element || !attribute) {
            return null;
        }

        const rawValue = element.getAttribute(`data-${attribute}`);
        return this.sanitize(rawValue, type);
    },

    /**
     * Safely set data attribute with sanitization
     * @param {Element} element - The DOM element
     * @param {string} attribute - The data attribute name (without 'data-')
     * @param {string} value - The value to set
     * @param {string} type - The sanitization type
     */
    setDataAttribute(element, attribute, value, type = 'attribute') {
        if (!element || !attribute) {
            return;
        }

        const sanitizedValue = this.sanitize(value, type);
        if (sanitizedValue !== null) {
            element.setAttribute(`data-${attribute}`, sanitizedValue);
        }
    },

    /**
     * Sanitize CSS values to prevent CSS injection
     * @param {string} cssValue - The CSS value to sanitize
     * @returns {string} - Sanitized CSS value
     */
    sanitizeCSS(cssValue) {
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
    },

    /**
     * Log security warnings
     * @param {string} message - The warning message
     * @param {any} data - Additional data to log
     */
    logSecurityWarning(message, data = null) {
        console.warn(`[Essential Blocks Security] ${message}`, data || '');
    },

    /**
     * Validate and sanitize form input
     * @param {string} input - The form input to validate
     * @param {string} type - The input type ('email', 'url', 'text', 'number')
     * @returns {string|null} - Sanitized input or null if invalid
     */
    sanitizeFormInput(input, type = 'text') {
        if (!input || typeof input !== 'string') {
            return null;
        }

        const cleanInput = this.sanitizeText(input);

        switch (type) {
            case 'email':
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(cleanInput) ? cleanInput : null;
            case 'url':
                return this.sanitizeURL(cleanInput);
            case 'number':
                const num = parseFloat(cleanInput);
                return !isNaN(num) ? num.toString() : null;
            case 'text':
            default:
                return cleanInput;
        }
    },

    /**
     * Batch sanitize an object of values
     * @param {Object} data - Object containing key-value pairs to sanitize
     * @param {Object} rules - Object containing sanitization rules for each key
     * @returns {Object} - Sanitized data object
     */
    sanitizeObject(data, rules = {}) {
        if (!data || typeof data !== 'object') {
            return {};
        }

        const sanitized = {};
        
        for (const [key, value] of Object.entries(data)) {
            const rule = rules[key] || 'text';
            sanitized[key] = this.sanitize(value, rule);
        }

        return sanitized;
    }
};

// Export individual functions for convenience
export const {
    sanitize,
    sanitizeURL,
    sanitizeAttribute,
    sanitizeText,
    sanitizeHTML,
    sanitizeTarget,
    getDataAttribute,
    setDataAttribute,
    sanitizeCSS,
    logSecurityWarning,
    sanitizeFormInput,
    sanitizeObject
} = EBSanitization;
