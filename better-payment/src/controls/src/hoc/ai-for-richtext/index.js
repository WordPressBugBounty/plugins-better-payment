/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { registerFormatType, store as richTextStore } from '@wordpress/rich-text';
import { RichTextToolbarButton, BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { select, useSelect } from '@wordpress/data';


/**
 * Internal dependencies
 */
import AIContentPopover from '../../components/AI/AIContentPopover';
import AiIcon from '../../icons/ai-icon';

/**
 * AI Toolbar Button Component
 */
const AIRichTextButton = ({ onChange, value }) => {
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const aiPopoverRef = useRef(null);

    const getFormatType = useSelect((select) =>
        select(richTextStore).getFormatType,
        []
    );

    // Get current block name to determine content type
    const currentBlockName = useSelect((select) => {
        const { getSelectedBlock } = select('core/block-editor');
        const selectedBlock = getSelectedBlock();
        return selectedBlock ? selectedBlock.name : '';
    }, []);

    /**
     * Determine content type based on block name
     */
    const getContentType = () => {
        if (!currentBlockName) return 'paragraph';

        const blockName = currentBlockName.toLowerCase();
        const headingKeywords = ['heading', 'title', 'head', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        return headingKeywords.some(keyword => blockName.includes(keyword)) ? 'text' : 'paragraph';
    };

    /**
     * Handle AI button click
     */
    const handleAiButtonClick = () => {
        setIsPopoverVisible(!isPopoverVisible);
    };

    /**
     * Handle AI content generation
     */
    const handleContentGenerated = (generatedContent) => {
        if (!generatedContent) return;

        // Replace the entire RichText content with AI generated content
        const newValue = {
            ...value,
            formats: Array(generatedContent.length),
            replacements: Array(generatedContent.length),
            text: generatedContent
        };

        onChange(newValue);
        setIsPopoverVisible(false);
    };

    /**
     * Handle popover close
     */
    const handlePopoverClose = () => {
        setIsPopoverVisible(false);
    };

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={<AiIcon />}
                        title={__('Write with AI', 'essential-blocks')}
                        onClick={handleAiButtonClick}
                        isActive={isPopoverVisible}
                        className="eb-hoc-ai-button eb-richtext-ai-button"
                        ref={aiPopoverRef}
                    />
                </ToolbarGroup>
            </BlockControls>
            {isPopoverVisible && (
                <AIContentPopover
                    isVisible={isPopoverVisible}
                    onClose={handlePopoverClose}
                    anchor={aiPopoverRef.current}
                    onContentGenerated={handleContentGenerated}
                    contentType={getContentType()}
                    currentValue={value.text || ''}
                    contentFor="writeRichtext"
                />
            )}
        </>
    );
};


/**
 * Register AI format type for all RichText toolbars
 *
 * This registers a global AI button that appears in every RichText toolbar
 * across all blocks in the editor.
 */
const registerAIForRichText = () => {
    if (window.EssentialBlocksLocalize?.enableWriteAIRichtext !== "1") return;

    const getFormatType = select('core/rich-text').getFormatType;

    if (getFormatType && !getFormatType('essential-blocks/ai-content')) {
        registerFormatType('essential-blocks/ai-content', {
            title: __('AI Content Generator', 'essential-blocks'),
            tagName: 'div', // We don't actually apply any formatting, just use the button
            className: 'eb-richtext-ai-button',
            edit: AIRichTextButton
        });
    }
};

registerAIForRichText();
