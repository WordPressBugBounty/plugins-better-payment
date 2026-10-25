/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import AIImagePopover from '../../components/AI/AIImagePopover';
import AIImageIcon from '../../icons/ai-image-icon';

/**
 * AI Image Toolbar Button Component
 */
const AIImageButton = ({ attributes, setAttributes, isSelected }) => {
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const aiPopoverRef = useRef(null);

    // Get current block name to check if it's an image block
    const currentBlockName = useSelect((select) => {
        const { getSelectedBlock } = select('core/block-editor');
        const selectedBlock = getSelectedBlock();
        return selectedBlock ? selectedBlock.name : '';
    }, []);

    // Only show for image blocks
    const isImageBlock = currentBlockName === 'essential-blocks/advanced-image' || currentBlockName === 'core/image';

    if (!isImageBlock || !isSelected) {
        return null;
    }

    /**
     * Handle AI button click
     */
    const handleAiButtonClick = () => {
        setIsPopoverVisible(!isPopoverVisible);
    };

    /**
     * Handle AI image generation
     */
    const handleImageGenerated = (imageData) => {
        if (!imageData) return;

        // Handle different block types
        if (currentBlockName === 'essential-blocks/advanced-image') {
            // For Essential Blocks advanced image
            setAttributes({
                imageId: imageData.id,
                imageUrl: imageData.url,
                imageAlt: imageData.alt || '',
                imageCaption: imageData.caption || '',
                imgSource: 'custom'
            });
        } else if (currentBlockName === 'core/image') {
            // For core image block
            setAttributes({
                id: imageData.id,
                url: imageData.url,
                alt: imageData.alt || '',
                caption: imageData.caption || '',
            });
        }

        setIsPopoverVisible(false);
    };

    /**
     * Handle popover close
     */
    const handlePopoverClose = () => {
        setIsPopoverVisible(false);
    };

    /**
     * Get current image data from block attributes
     */
    const getCurrentImageValue = () => {
        if (currentBlockName === 'essential-blocks/advanced-image') {
            // For Essential Blocks advanced image
            return {
                id: attributes.imageId,
                url: attributes.imageUrl,
                alt: attributes.imageAlt,
                caption: attributes.imageCaption,
                title: attributes.imageTitle
            };
        } else if (currentBlockName === 'core/image') {
            // For core image block
            return {
                id: attributes.id,
                url: attributes.url,
                alt: attributes.alt,
                caption: attributes.caption,
                title: attributes.title
            };
        }
        return null;
    };

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={<AIImageIcon />}
                        title={__('Generate with AI', 'essential-blocks')}
                        onClick={handleAiButtonClick}
                        isActive={isPopoverVisible}
                        className="eb-hoc-ai-button eb-image-ai-button"
                        ref={aiPopoverRef}
                    />
                </ToolbarGroup>
            </BlockControls>
            {isPopoverVisible && (
                <AIImagePopover
                    isVisible={isPopoverVisible}
                    onClose={handlePopoverClose}
                    anchor={aiPopoverRef.current}
                    onImageGenerated={handleImageGenerated}
                    value={getCurrentImageValue()}
                />
            )}
        </>
    );
};

/**
 * Higher Order Component to add AI Image button to image blocks
 */
const withAIImageButton = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name } = props;

        // Only apply to specific image blocks
        const isTargetBlock = name === 'essential-blocks/advanced-image' || name === 'core/image';

        if (!isTargetBlock) {
            return <BlockEdit {...props} />;
        }

        // Check if AI image generation feature is enabled
        if (window.EssentialBlocksLocalize?.enableGenerateImage !== "1") {
            return <BlockEdit {...props} />;
        }

        return (
            <>
                <BlockEdit {...props} />
                <AIImageButton {...props} />
            </>
        );
    };
}, 'withAIImageButton');

/**
 * Register the AI Image button filter
 */
const registerAIForImage = () => {
    // Only register if AI image generation feature is enabled
    if (window.EssentialBlocksLocalize?.enableGenerateImage !== "1") return;

    // Check if the filter is already registered to prevent duplicates
    if (window.wp?.hooks?.hasFilter?.('editor.BlockEdit', 'essential-blocks/ai-image-button')) {
        return;
    }

    addFilter(
        'editor.BlockEdit',
        'essential-blocks/ai-image-button',
        withAIImageButton
    );
};

// Initialize the AI Image button
registerAIForImage();
