/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import AIRewritePopover from '../../components/AI/AIRewritePopover';
import RegenerateIcon from '../../icons/ai-regenerate-content-icon';
import { extractContentAttributes, getBlockContentRules } from '../../utils/schema-processor';

/**
 * Styles
 */
import './style.scss';

/**
 * AI Rewrite Toolbar Button Component
 */
const AIRewriteButton = ({ attributes, setAttributes, isSelected, name }) => {
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);
    const aiPopoverRef = useRef(null);

    // Get current block name to check if it's an Essential Block
    const currentBlockObj = useSelect((select) => {
        const { getSelectedBlock } = select('core/block-editor');
        const selectedBlock = getSelectedBlock();
        return selectedBlock || {};
    }, []);

    // Only show for Essential Blocks
    const isEssentialBlock = currentBlockObj.name && currentBlockObj.name.startsWith('essential-blocks/');

    if (!isEssentialBlock || !isSelected) {
        return null;
    }

    /**
     * Handle AI button click
     */
    const handleAiButtonClick = (event) => {
        event.preventDefault();
        event.stopPropagation();
        setIsPopoverVisible(!isPopoverVisible);
    };

    /**
     * Handle popover close
     */
    const handlePopoverClose = () => {
        setIsPopoverVisible(false);
    };

    /**
     * Handle content rewritten
     */
    const handleContentRewritten = (mergedAttributes) => {
        if (mergedAttributes && typeof mergedAttributes === 'object') {
            // The schema processor has already merged the content properly
            setAttributes(mergedAttributes);
        }
        setIsPopoverVisible(false);
    };

    /**
     * Get the full block object for AI processing
     */
    const getFullBlockObject = () => {
        // Get the current block object with all its data
        const currentBlock = {
            blockName: currentBlockObj.name,
            attributes: currentBlockObj.attributes,
            ...(currentBlockObj.innerBlocks && { innerBlocks: currentBlockObj.innerBlocks })
        };

        return currentBlock;
    };

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={<RegenerateIcon />}
                        title={__('Rewrite with AI', 'essential-blocks')}
                        onClick={handleAiButtonClick}
                        isActive={isPopoverVisible}
                        className="eb-hoc-ai-button eb-rewrite-ai-button"
                        ref={aiPopoverRef}
                    />
                </ToolbarGroup>
            </BlockControls>
            {isPopoverVisible && (
                <AIRewritePopover
                    isVisible={isPopoverVisible}
                    onClose={handlePopoverClose}
                    anchor={aiPopoverRef.current}
                    // onContentRewritten={handleContentRewritten}
                    currentBlock={currentBlockObj}
                    contentFor="writePageContent"
                />
            )}
        </>
    );
};

/**
 * Higher Order Component to add AI Rewrite button to Essential Blocks
 */
const withAIRewriteButton = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { name } = props;

        // Only apply to Essential Blocks
        const isEssentialBlock = name && name.startsWith('essential-blocks/');

        if (!isEssentialBlock) {
            return <BlockEdit {...props} />;
        }

        // Check if AI rewrite feature is enabled
        if (window.EssentialBlocksLocalize?.enableRewriteAIContent !== "1") {
            return <BlockEdit {...props} />;
        }

        return (
            <>
                <BlockEdit {...props} />
                <AIRewriteButton {...props} />
            </>
        );
    };
}, 'withAIRewriteButton');

/**
 * Register the HOC with WordPress block editor
 */
const registerAIRewriteHOC = () => {
    // Check if AI rewrite feature is enabled
    if (window.EssentialBlocksLocalize?.enableRewriteAIContent !== "1") {
        return;
    }

    // Add the filter to wrap block edit components
    addFilter(
        'editor.BlockEdit',
        'essential-blocks/ai-rewrite-button',
        withAIRewriteButton,
        999 // High priority to ensure it runs after other filters
    );
};

// Initialize the HOC
registerAIRewriteHOC();

export default withAIRewriteButton;
