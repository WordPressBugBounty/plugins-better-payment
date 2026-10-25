/**
 * Example usage of AI for Image HOC
 *
 * This file demonstrates how the AI Image button is automatically
 * added to image blocks when this HOC is imported.
 */

// Import the AI for Image HOC
import './index.js';

/**
 * Example: Custom Image Block with AI Support
 *
 * When you create a custom image block, the AI button will automatically
 * be added to the toolbar if the block name matches the supported blocks.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Example custom image block registration
// The AI button will automatically appear for this block if the name matches
registerBlockType('example/custom-image', {
    title: __('Custom Image with AI', 'essential-blocks'),
    icon: 'format-image',
    category: 'media',

    attributes: {
        imageId: {
            type: 'number',
        },
        imageUrl: {
            type: 'string',
        },
        imageAlt: {
            type: 'string',
        },
        imageCaption: {
            type: 'string',
        },
    },

    edit: ({ attributes, setAttributes, isSelected }) => {
        // The AI button will be automatically added to the toolbar
        // when this block is selected, thanks to the HOC

        return (
            <div>
                {attributes.imageUrl ? (
                    <img
                        src={attributes.imageUrl}
                        alt={attributes.imageAlt}
                    />
                ) : (
                    <div>No image selected</div>
                )}
            </div>
        );
    },

    save: ({ attributes }) => {
        return attributes.imageUrl ? (
            <img
                src={attributes.imageUrl}
                alt={attributes.imageAlt}
            />
        ) : null;
    },
});

/**
 * Example: Extending existing blocks
 *
 * The HOC automatically works with:
 * - essential-blocks/advanced-image
 * - core/image
 *
 * No additional setup needed for these blocks.
 */

/**
 * Example: Checking if AI is enabled
 *
 * You can check if the AI feature is enabled in your own components:
 */
const isAIEnabled = window.EssentialBlocksLocalize?.enableGenerateImage === "1";

if (isAIEnabled) {
    console.log('AI Image generation is enabled');
} else {
    console.log('AI Image generation is disabled');
}
