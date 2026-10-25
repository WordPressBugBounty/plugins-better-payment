/**
 * SVG Icon Support Helper Functions
 */

/**
 * Check if a value is an SVG URL
 * @param {string} value - The value to check
 * @returns {boolean} - True if the value is an SVG URL
 */
export const isSvgIconValue = (value) => {
    if (!value || typeof value !== "string") return false;
    try {
        const hasSvgExt = /\.svg(\?|#|$)/i.test(value);
        return hasSvgExt;
    } catch (e) {
        return false;
    }
};

/**
 * Check if a string contains inline SVG markup
 * @param {string} value - The value to check
 * @returns {boolean} - True if the value contains inline SVG markup
 */
export const isInlineSvgMarkup = (value) => {
    return typeof value === "string" && /<svg[\s\S]*<\/svg>/i.test(value);
};

/**
 * Fetch SVG file and return sanitized inline SVG markup
 * @param {string} svgUrl - The SVG URL to fetch
 * @returns {Promise<string>} - The sanitized SVG markup
 */
export const fetchSvgAsHTML = async (svgUrl) => {
    if (!isSvgIconValue(svgUrl)) return "";
    try {
        const res = await fetch(svgUrl, { credentials: "omit" });
        if (!res.ok) return "";
        const raw = await res.text();
        // Ensure we only inline the <svg ...>...</svg> fragment
        const match = raw.match(/<svg[\s\S]*?<\/svg>/i);
        const svgOnly = match ? match[0] : raw;

        // Use the global sanitizeHTML function if available
        if (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeHTML) {
            return window.eb_frontend.sanitizeHTML(svgOnly);
        }
        return svgOnly;
    } catch (e) {
        return "";
    }
};

/**
 * Function to load SVG icons from URLs
 * @param {HTMLElement} container - The container element to search for SVG icons
 */
export const loadSvgIcons = async (container) => {
    const svgElements = container.querySelectorAll('.eb-svg-icon[data-svg-url]');

    for (const element of svgElements) {
        const svgUrl = element.getAttribute('data-svg-url');
        if (svgUrl) {
            try {
                const svgContent = await fetchSvgAsHTML(svgUrl);
                if (svgContent) {
                    element.innerHTML = svgContent;
                    element.removeAttribute('data-svg-url'); // Clean up
                }
            } catch (error) {
                console.warn('Failed to load SVG icon:', svgUrl, error);
            }
        }
    }
};

export const EBGetIconType = (value) => {
    if (value.includes('fa-')) {
        return 'fontawesome';
    }
    return 'dashicon';
}

export const EBRenderIcon = (iconType, className, icon) => {
    if (iconType === 'dashicon') {
        // Render Dashicon
        return '<span class="dashicon dashicons ' + icon + ' ' + className + '"></span>';
    } else if (iconType === 'fontawesome') {
        // Render FontAwesome icon
        return '<i class="' + icon + ' ' + className + '"></i>';
    }

    // Handle other icon types or return an error message if needed.
    return 'Invalid icon type';
}

export const EBGetIconClass = (value) => {
    if (!value) {
        return ''
    }
    if (!value.includes("fa-")) {
        return "dashicon dashicons " + value;
    }

    return value;
};

/**
 * Enhanced icon rendering with SVG support
 * @param {string} iconValue - The icon value
 * @param {string} className - Additional CSS class name
 * @returns {string} - The rendered icon HTML string
 */
export const EBRenderIconWithSVG = (iconValue, className = "") => {
    if (!iconValue) return "";

    // Get sanitization functions
    const sanitizeHTML = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeHTML)
        ? window.eb_frontend.sanitizeHTML
        : (html) => html;
    const sanitizeIconValue = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeIconValue)
        ? window.eb_frontend.sanitizeIconValue
        : (icon) => {
            // Basic fallback sanitization for icon values
            if (!icon || typeof icon !== 'string') return '';
            // Remove potentially dangerous characters
            return icon.replace(/[<>'"]/g, '');
        };

    // Handle inline SVG markup
    if (isInlineSvgMarkup(iconValue)) {
        return '<span class="' + className + '">' + sanitizeHTML(iconValue) + '</span>';
    }

    // Handle SVG URLs - return a placeholder that will be replaced with actual SVG
    if (isSvgIconValue(iconValue)) {
        return '<span class="eb-svg-icon ' + className + '" data-svg-url="' + iconValue + '"></span>';
    }

    // Handle FontAwesome and Dashicon icons using existing functions
    const iconType = EBGetIconType(iconValue);
    const sanitizedIconValue = sanitizeIconValue(iconValue);
    const sanitizedClassName = className.replace(/[<>'"]/g, '');

    // Use EBRenderIcon for proper dashicon and fontawesome rendering
    return EBRenderIcon(iconType, sanitizedClassName, sanitizedIconValue);
};

/**
 * Generate arrow HTML with SVG support for different slider versions
 * @param {string} iconValue - The icon value
 * @param {string} direction - The arrow direction ('prev' or 'next')
 * @returns {string} - The generated HTML string
 */
export const generateArrowHTML = (iconValue, direction) => {
    // Get sanitization functions
    const sanitizeHTML = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeHTML)
        ? window.eb_frontend.sanitizeHTML
        : (html) => html;
    const sanitizeIconValue = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeIconValue)
        ? window.eb_frontend.sanitizeIconValue
        : (icon) => {
            // Basic fallback sanitization for icon values
            if (!icon || typeof icon !== 'string') return '';
            // Remove potentially dangerous characters
            return icon.replace(/[<>'"]/g, '');
        };

    if (isInlineSvgMarkup(iconValue)) {
        return '<div class="slick-' + direction + '"><span>' + sanitizeHTML(iconValue) + '</span></div>';
    } else if (isSvgIconValue(iconValue)) {
        return '<div class="slick-' + direction + '"><span class="eb-svg-icon" data-svg-url="' + iconValue + '"></span></div>';
    } else {
        // Handle FontAwesome and Dashicon icons using existing functions
        const iconType = EBGetIconType(iconValue);
        const sanitizedIconValue = sanitizeIconValue(iconValue);
        const sanitizedDirection = direction.replace(/[<>'"]/g, '');

        // Use EBRenderIcon for proper dashicon and fontawesome rendering
        const iconHTML = EBRenderIcon(iconType, '', sanitizedIconValue);
        return '<div class="slick-' + sanitizedDirection + '">' + iconHTML + '</div>';
    }
};
