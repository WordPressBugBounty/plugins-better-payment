/**
 * Schema Processor Utility for AI Content Processing
 * 
 * This utility handles extraction and validation of content attributes
 * from Essential Blocks for AI processing while preserving block structure.
 */

/**
 * Attributes that should be excluded from AI processing
 * These are technical/styling attributes that shouldn't be modified by AI
 */
const EXCLUDED_ATTRIBUTES = [
    // Block identification and metadata
    'blockId',
    'blockRoot', 
    'blockMeta',
    'uniqueId',
    'classHook',
    'parentBlockId',
    
    // Responsive and layout settings
    'resOption',
    'cover',
    'preset',
    
    // Styling and design attributes
    'blockStyle',
    'blockStyleId', 
    'blockStyleName',
    
    // Border and shadow attributes (pattern matching)
    /.*BdShadow.*/,
    /.*Border.*/,
    /.*Margin.*/,
    /.*Padding.*/,
    /.*Background.*/,
    /.*Bg.*/,
    
    // Typography attributes (pattern matching)
    /.*Typography.*/,
    /.*FontSize.*/,
    /.*FontWeight.*/,
    /.*LineHeight.*/,
    /.*LetterSpacing.*/,
    
    // Color attributes (pattern matching)
    /.*Color$/,
    /.*ColorHover$/,
    /.*ColorActive$/,
    
    // Responsive attributes (pattern matching)
    /.*Desktop$/,
    /.*Tablet$/,
    /.*Mobile$/,
    
    // Animation and transition attributes
    /.*Animation.*/,
    /.*Transition.*/,
    /.*Duration.*/,
    
    // Advanced styling
    /.*Transform.*/,
    /.*Filter.*/,
    /.*Shadow.*/,
    /.*Gradient.*/,
    
    // Layout and positioning
    /.*Position.*/,
    /.*ZIndex.*/,
    /.*Overflow.*/,
    /.*Display.*/,
    /.*Flex.*/,
    /.*Grid.*/,
    /.*Align(?!$)/, // Exclude alignment attributes except 'align' itself
    
    // Size and dimension attributes
    /.*Width$/,
    /.*Height$/,
    /.*Size$/,
    /.*Radius.*/,
    /.*Gap.*/,
    /.*Space.*/,
    /.*Spacing.*/,
    
    // Advanced features
    /.*Schema.*/,
    /.*SEO.*/,
    /.*Custom.*/,
    /.*Advanced.*/
];

/**
 * Content attributes that should be included in AI processing
 * These are the primary content attributes that AI should modify
 */
const CONTENT_ATTRIBUTES = [
    // Text content
    'text',
    'content',
    'title',
    'subtitle',
    'heading',
    'description',
    'caption',
    'label',
    'placeholder',
    'buttonText',
    'linkText',
    
    // Media attributes
    'alt',
    'imageAlt',
    'imageCaption',
    
    // URL and link attributes
    'url',
    'href',
    'link',
    'buttonURL',
    'linkURL',
    
    // List and array content
    'items',
    'options',
    'choices',
    'tabs',
    'accordions',
    
    // Form fields
    'value',
    'defaultValue',
    'helpText',
    
    // Basic alignment (content-related)
    'align',
    'textAlign',
    'contentAlign',
    
    // Inner blocks (for container blocks)
    'innerBlocks'
];

/**
 * Check if an attribute key should be excluded from AI processing
 * @param {string} key - Attribute key to check
 * @returns {boolean} True if should be excluded
 */
const shouldExcludeAttribute = (key) => {
    // Check exact matches first
    if (EXCLUDED_ATTRIBUTES.includes(key)) {
        return true;
    }
    
    // Check pattern matches
    return EXCLUDED_ATTRIBUTES.some(pattern => {
        if (pattern instanceof RegExp) {
            return pattern.test(key);
        }
        return false;
    });
};

/**
 * Check if an attribute key is a content attribute
 * @param {string} key - Attribute key to check
 * @returns {boolean} True if it's a content attribute
 */
const isContentAttribute = (key) => {
    return CONTENT_ATTRIBUTES.some(pattern => {
        if (typeof pattern === 'string') {
            return key === pattern || key.toLowerCase().includes(pattern.toLowerCase());
        }
        return false;
    });
};

/**
 * Extract content attributes from block attributes for AI processing
 * @param {Object} attributes - Block attributes object
 * @param {Object} options - Processing options
 * @returns {Object} Filtered attributes containing only content
 */
export const extractContentAttributes = (attributes, options = {}) => {
    const {
        includeInnerBlocks = true,
        preserveStructure = true,
        customContentKeys = []
    } = options;
    
    if (!attributes || typeof attributes !== 'object') {
        return {};
    }
    
    const contentAttributes = {};
    
    // Add custom content keys to the list
    const allContentKeys = [...CONTENT_ATTRIBUTES, ...customContentKeys];
    
    Object.keys(attributes).forEach(key => {
        const value = attributes[key];
        
        // Skip excluded attributes
        if (shouldExcludeAttribute(key)) {
            return;
        }
        
        // Include content attributes
        if (isContentAttribute(key)) {
            contentAttributes[key] = value;
            return;
        }
        
        // Handle inner blocks specially
        if (key === 'innerBlocks' && includeInnerBlocks && Array.isArray(value)) {
            contentAttributes[key] = value.map(block => {
                if (block && block.attributes) {
                    return {
                        ...block,
                        attributes: extractContentAttributes(block.attributes, options)
                    };
                }
                return block;
            });
            return;
        }
        
        // Include any attribute that contains text content (heuristic)
        if (typeof value === 'string' && value.trim().length > 0) {
            // Check if it looks like content (not a technical value)
            if (!key.match(/^(id|class|style|data-|aria-)/i) && 
                !key.match(/(color|size|width|height|margin|padding)$/i)) {
                contentAttributes[key] = value;
            }
        }
        
        // Include arrays that might contain content
        if (Array.isArray(value) && value.length > 0) {
            // Check if array contains objects with content
            const hasContent = value.some(item => 
                typeof item === 'object' && item !== null &&
                Object.keys(item).some(itemKey => isContentAttribute(itemKey))
            );
            
            if (hasContent) {
                contentAttributes[key] = value;
            }
        }
        
        // Include objects that might contain content
        if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
            const nestedContent = extractContentAttributes(value, options);
            if (Object.keys(nestedContent).length > 0) {
                contentAttributes[key] = nestedContent;
            }
        }
    });
    
    return contentAttributes;
};

/**
 * Validate that rewritten content maintains the same structure as original
 * @param {Object} originalContent - Original content structure
 * @param {Object} rewrittenContent - AI-generated content
 * @returns {Object} Validation result with isValid and errors
 */
export const validateContentStructure = (originalContent, rewrittenContent) => {
    const errors = [];
    
    if (!originalContent || !rewrittenContent) {
        return {
            isValid: false,
            errors: ['Original or rewritten content is missing']
        };
    }
    
    // Check if both are objects
    if (typeof originalContent !== 'object' || typeof rewrittenContent !== 'object') {
        return {
            isValid: false,
            errors: ['Content must be objects']
        };
    }
    
    // Validate structure recursively
    const validateStructureRecursive = (original, rewritten, path = '') => {
        Object.keys(original).forEach(key => {
            const currentPath = path ? `${path}.${key}` : key;
            
            if (!(key in rewritten)) {
                errors.push(`Missing key: ${currentPath}`);
                return;
            }
            
            const originalValue = original[key];
            const rewrittenValue = rewritten[key];
            
            // Check type consistency for non-content attributes
            if (Array.isArray(originalValue) !== Array.isArray(rewrittenValue)) {
                errors.push(`Type mismatch at ${currentPath}: expected ${Array.isArray(originalValue) ? 'array' : 'non-array'}`);
                return;
            }
            
            // Recursively validate nested objects
            if (typeof originalValue === 'object' && originalValue !== null &&
                typeof rewrittenValue === 'object' && rewrittenValue !== null &&
                !Array.isArray(originalValue)) {
                validateStructureRecursive(originalValue, rewrittenValue, currentPath);
            }
            
            // Validate array structures
            if (Array.isArray(originalValue) && Array.isArray(rewrittenValue)) {
                if (key === 'innerBlocks') {
                    // For innerBlocks, validate each block structure
                    originalValue.forEach((block, index) => {
                        if (rewrittenValue[index] && block.attributes && rewrittenValue[index].attributes) {
                            validateStructureRecursive(
                                block.attributes, 
                                rewrittenValue[index].attributes, 
                                `${currentPath}[${index}].attributes`
                            );
                        }
                    });
                }
            }
        });
    };
    
    validateStructureRecursive(originalContent, rewrittenContent);
    
    return {
        isValid: errors.length === 0,
        errors
    };
};

/**
 * Merge rewritten content back into original attributes while preserving structure
 * @param {Object} originalAttributes - Original block attributes
 * @param {Object} rewrittenContent - AI-generated content
 * @returns {Object} Merged attributes
 */
export const mergeRewrittenContent = (originalAttributes, rewrittenContent) => {
    if (!originalAttributes || !rewrittenContent) {
        return originalAttributes;
    }
    
    // Validate structure first
    const validation = validateContentStructure(
        extractContentAttributes(originalAttributes),
        rewrittenContent
    );
    
    if (!validation.isValid) {
        console.warn('Content structure validation failed:', validation.errors);
        // Return original attributes if validation fails
        return originalAttributes;
    }
    
    // Deep merge while preserving original structure
    const mergeRecursive = (original, rewritten) => {
        const result = { ...original };
        
        Object.keys(rewritten).forEach(key => {
            if (key in original) {
                const originalValue = original[key];
                const rewrittenValue = rewritten[key];
                
                if (typeof originalValue === 'object' && originalValue !== null &&
                    typeof rewrittenValue === 'object' && rewrittenValue !== null &&
                    !Array.isArray(originalValue) && !Array.isArray(rewrittenValue)) {
                    result[key] = mergeRecursive(originalValue, rewrittenValue);
                } else {
                    result[key] = rewrittenValue;
                }
            }
        });
        
        return result;
    };
    
    return mergeRecursive(originalAttributes, rewrittenContent);
};

/**
 * Get block-specific content extraction rules
 * @param {string} blockName - Block name (e.g., 'essential-blocks/button')
 * @returns {Object} Block-specific extraction options
 */
export const getBlockContentRules = (blockName) => {
    const blockType = blockName.replace('essential-blocks/', '');
    
    const blockRules = {
        'button': {
            customContentKeys: ['buttonText', 'buttonURL'],
            preserveStructure: true
        },
        'advanced-heading': {
            customContentKeys: ['title', 'subtitle', 'tagName'],
            preserveStructure: true
        },
        'text': {
            customContentKeys: ['text', 'tagName'],
            preserveStructure: true
        },
        'accordion': {
            customContentKeys: ['title'],
            includeInnerBlocks: true,
            preserveStructure: true
        },
        'accordion-item': {
            customContentKeys: ['title'],
            includeInnerBlocks: true,
            preserveStructure: true
        },
        'call-to-action': {
            customContentKeys: ['title', 'description', 'buttonText', 'buttonURL'],
            preserveStructure: true
        },
        'testimonial': {
            customContentKeys: ['name', 'designation', 'description', 'testimonial'],
            preserveStructure: true
        },
        'info-box': {
            customContentKeys: ['title', 'description', 'buttonText', 'buttonURL'],
            preserveStructure: true
        }
    };
    
    return blockRules[blockType] || {
        customContentKeys: [],
        includeInnerBlocks: true,
        preserveStructure: true
    };
};
