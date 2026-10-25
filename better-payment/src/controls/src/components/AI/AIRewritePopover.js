/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Popover, Button, TextareaControl } from '@wordpress/components';
import { useState, useRef } from '@wordpress/element';
import { Dashicon } from '@wordpress/components';
import { BlockPreview } from "@wordpress/block-editor";
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

import EBLogo from '../../icons/eb-logo';
import AiIcon from '../../icons/ai-icon';
import GeneratingIcon from '../../icons/generating-icon';
import { extractContentAttributes, validateContentStructure, mergeRewrittenContent } from '../../utils/schema-processor';
import essentialBlocksSchema from './blocks-schema';

/**
 * AIRewritePopover - A component for AI content rewriting
 *
 * @param {Object} props - Component props
 * @param {boolean} props.isVisible - Whether the popover is visible
 * @param {Function} props.onClose - Function to close the popover
 * @param {Object} props.anchor - Anchor element for the popover
 * @param {Function} props.onContentRewritten - Callback when content is rewritten
 * @param {Object} props.currentBlock - Current block object with attributes and innerBlocks
 * @param {string} props.contentFor - Content type identifier
 */
const AIRewritePopover = ({
    isVisible,
    onClose,
    anchor,
    // onContentRewritten,
    currentBlock = {},
    contentFor = 'writePageContent'
}) => {
    // WordPress block editor dispatch functions
    const { replaceBlock, updateBlockAttributes } = useDispatch('core/block-editor');

    // AI rewrite states
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiTone, setAiTone] = useState('friendly');
    const [isGenerating, setIsGenerating] = useState(false);
    const [aiError, setAiError] = useState(false);
    const [rewrittenContent, setRewrittenContent] = useState(null);
    const [rewrittenBlockObj, setRewrittenBlockObj] = useState({});
    const [showRewrittenContent, setShowRewrittenContent] = useState(false);

    // Ref for TextareaControl to focus
    const textareaRef = useRef(null);

    /**
     * Suggested prompts for rewriting content
     */
    const getSuggestedPrompts = () => {
        return [
            __('Make this content more engaging and compelling', 'essential-blocks'),
            __('Simplify this content for better readability', 'essential-blocks'),
            __('Make this content more professional and polished', 'essential-blocks'),
            __('Rewrite this content to be more persuasive', 'essential-blocks'),
            __('Make this content shorter and more concise', 'essential-blocks'),
            __('Expand this content with more details', 'essential-blocks'),
            __('Rewrite this content for a younger audience', 'essential-blocks'),
            __('Make this content more action-oriented', 'essential-blocks')
        ];
    };

    /**
     * Get tone instructions
     */
    const getToneInstructions = (tone) => {
        switch (tone) {
            case 'friendly':
                return __('friendly and approachable', 'essential-blocks');
            case 'professional':
                return __('professional and polished', 'essential-blocks');
            case 'bold':
                return __('bold and confident', 'essential-blocks');
            case 'quirky':
                return __('quirky and creative', 'essential-blocks');
            case 'urgent':
                return __('urgent and compelling', 'essential-blocks');
            default:
                return __('friendly and approachable', 'essential-blocks');
        }
    };

    /**
     * Create schema reference for AI prompt following Design AI pattern
     */
    const createSchemaReference = () => {
        const schemaInfo = {};

        Object.keys(essentialBlocksSchema).forEach(blockName => {
            const blockSchema = essentialBlocksSchema[blockName];
            const contentAttributes = {};

            Object.keys(blockSchema).forEach(attrName => {
                const attrInfo = blockSchema[attrName];
                if (attrInfo.isContent) {
                    contentAttributes[attrName] = attrInfo.type;
                }
            });

            schemaInfo[blockName] = {
                contentAttributes
            };
        });

        return JSON.stringify(schemaInfo, null, 2);
    };

    /**
     * Create rewrite prompt following Design AI pattern
     */
    const createRewritePrompt = (userPrompt, toneInstructions, contentToRewrite, isRegeneration) => {
        const schemaReference = createSchemaReference();

        let prompt = `You are an expert content rewriter using Essential Blocks for WordPress. Rewrite the provided content based on the user's instructions while maintaining the exact same structure and format.

USER REQUEST: "${userPrompt}"
CONTENT TONE: ${toneInstructions}
${isRegeneration ? 'REGENERATION: Please provide a different version than the previous rewrite.' : ''}

CURRENT CONTENT TO REWRITE:
${JSON.stringify(contentToRewrite, null, 2)}

INSTRUCTIONS:
1. Analyze the user's rewrite request and modify only the values within each attributes object.
2. Apply the "${toneInstructions}" tone to all text content
3. Maintain the exact same JSON structure as provided
4. Only modify text values, never change block structure, attribute names
5. For nested structures (innerBlocks), process each block recursively
6. Return ONLY valid JSON - no explanations, no markdown, no comments
7. Ensure all strings are properly quoted with double quotes
8. Do not use trailing commas in JSON objects or arrays
9. Escape any quotes within string values properly

RESPONSE FORMAT:
Return the content in the exact same structure as provided, with only the content attribute values modified according to the rewrite instructions.`;

        return prompt;
    };

    /**
     * Extract content attributes from block using schema-based filtering
     * Recursively processes innerBlocks and filters attributes based on isContent: true in schema
     */
    const extractContentFromBlock = (block) => {
        if (!block || !block.name) {
            return {};
        }

        const blockSchema = essentialBlocksSchema[block.name];
        const contentAttributes = {};

        // If block is not in schema, return empty object but maintain structure
        if (!blockSchema) {
            console.warn(`Block ${block.name} not found in schema, using empty placeholder`);
            return {
                name: block.name,
                attributes: {},
                innerBlocks: block.innerBlocks ? block.innerBlocks.map(extractContentFromBlock) : []
            };
        }

        // Filter attributes based on schema
        if (block.attributes && blockSchema) {
            Object.keys(block.attributes).forEach(attrName => {
                const attrSchema = blockSchema[attrName];
                if (attrSchema && attrSchema.isContent === true) {
                    contentAttributes[attrName] = block.attributes[attrName];
                }
            });
        }

        // Process innerBlocks recursively
        const processedInnerBlocks = block.innerBlocks ?
            block.innerBlocks.map(extractContentFromBlock) : [];

        return {
            name: block.name,
            attributes: contentAttributes,
            innerBlocks: processedInnerBlocks
        };
    };


    /**
     * Update block data by merging rewritten content into the current block
     * Handles nested innerBlocks recursively and preserves existing attributes
     */
    function updateBlockData(currentBlock, rewrittenContent) {
        // Create a deep copy of the current block to avoid mutations
        const updatedBlock = JSON.parse(JSON.stringify(currentBlock));

        // Helper function to merge attributes
        const mergeAttributes = (target, source) => {
            if (!source || typeof source !== 'object') return target;

            return {
                ...target,
                ...source
            };
        };

        // Helper function to find matching inner block by name and index
        const findMatchingInnerBlock = (targetBlocks, sourceBlock, sourceIndex) => {
            // First try to find by exact index and name match
            if (targetBlocks[sourceIndex] && targetBlocks[sourceIndex].name === sourceBlock.name) {
                return sourceIndex;
            }

            // If not found by index, try to find by name (first occurrence)
            for (let i = 0; i < targetBlocks.length; i++) {
                if (targetBlocks[i].name === sourceBlock.name) {
                    return i;
                }
            }

            return -1;
        };

        // Merge attributes for the main block
        if (rewrittenContent.attributes) {
            updatedBlock.attributes = mergeAttributes(updatedBlock.attributes || {}, rewrittenContent.attributes);
        }

        // Handle innerBlocks recursively
        if (rewrittenContent.innerBlocks && Array.isArray(rewrittenContent.innerBlocks)) {
            // Ensure updatedBlock has innerBlocks array
            if (!updatedBlock.innerBlocks) {
                updatedBlock.innerBlocks = [];
            }

            // Process each rewritten inner block
            rewrittenContent.innerBlocks.forEach((rewrittenInnerBlock, sourceIndex) => {
                const targetIndex = findMatchingInnerBlock(updatedBlock.innerBlocks, rewrittenInnerBlock, sourceIndex);

                if (targetIndex !== -1) {
                    // Update existing inner block recursively
                    updatedBlock.innerBlocks[targetIndex] = updateBlockData(
                        updatedBlock.innerBlocks[targetIndex],
                        rewrittenInnerBlock
                    );
                } else {
                    // If no matching block found, add the new block (this handles cases where structure changes)
                    updatedBlock.innerBlocks.push(rewrittenInnerBlock);
                }
            });
        }

        return updatedBlock;
    }

    /**
     * Generate AI rewritten content
     */
    const generateAIRewrite = (event, isRegeneration = false) => {
        // Handle event properly to prevent Firefox popover closing
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!aiPrompt.trim()) {
            alert(__("Please enter a rewrite prompt", "essential-blocks"));
            return;
        }

        setIsGenerating(true);
        setAiError(false);

        // Extract content attributes from the current block using schema
        const contentToRewrite = extractContentFromBlock(currentBlock);

        // Build the full prompt following Design AI pattern
        const toneInstructions = getToneInstructions(aiTone);
        const fullPrompt = createRewritePrompt(aiPrompt, toneInstructions, contentToRewrite, isRegeneration);

        // Prepare the data for AJAX request
        const formData = new FormData();
        formData.append('action', 'start_ai_job');
        formData.append('admin_nonce', window.EssentialBlocksLocalize?.admin_nonce || '');
        formData.append('job_type', 'rewrite');
        formData.append('prompt', fullPrompt);
        formData.append('current_content', JSON.stringify(contentToRewrite));
        formData.append('content_for', contentFor);

        console.log('fullPrompt: ', fullPrompt);

        // Start the AI job
        fetch(window.EssentialBlocksLocalize?.ajax_url || '', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.job_id) {
                    // Start polling for job completion
                    pollJobStatus(data.data.job_id);
                } else {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("Error starting AI job:", data);
                    alert(__("Error starting rewrite process. Please try again.", "essential-blocks"));
                }
            })
            .catch(error => {
                setIsGenerating(false);
                setAiError(true);
                console.error("AJAX Error:", error);
                alert(__("Error connecting to the server. Please try again.", "essential-blocks"));
            });
    };

    /**
     * Poll job status until completion
     */
    const pollJobStatus = (jobId) => {
        const poll = () => {
            const formData = new FormData();
            formData.append('action', 'check_ai_job_status');
            formData.append('admin_nonce', window.EssentialBlocksLocalize?.admin_nonce || '');
            formData.append('job_id', jobId);

            fetch(window.EssentialBlocksLocalize?.ajax_url || '', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Polling Response:', data);
                    if (data.success) {
                        const jobData = data.data;

                        if (jobData.status === 'completed') {
                            setIsGenerating(false);

                            // Handle the response content
                            let rewrittenBlockContent = jobData.result.content;
                            if (typeof rewrittenBlockContent === 'string') {
                                try {
                                    rewrittenBlockContent = JSON.parse(rewrittenBlockContent);
                                } catch (e) {
                                    console.error('Failed to parse rewritten content:', e);
                                    setAiError(true);
                                    alert(__("Error parsing rewritten content. Please try again.", "essential-blocks"));
                                    return;
                                }
                            }

                            // Store the rewritten content and show preview
                            console.log('Parsed Response:', currentBlock, rewrittenBlockContent);

                            const mergeGeneratedAttributes = updateBlockData(currentBlock, rewrittenBlockContent);
                            console.log('Merged Generated Attributes:', mergeGeneratedAttributes);

                            setRewrittenBlockObj({ ...mergeGeneratedAttributes });
                            setShowRewrittenContent(true);

                        } else if (jobData.status === 'failed') {
                            setIsGenerating(false);
                            setAiError(true);
                            console.error("AI job failed:", jobData.error_message);
                            alert(__("Rewrite failed: ", "essential-blocks") + (jobData.error_message || __("Unknown error", "essential-blocks")));
                        } else if (jobData.status === 'expired') {
                            setIsGenerating(false);
                            setAiError(true);
                            alert(__("Rewrite request timed out. Please try again.", "essential-blocks"));
                        } else {
                            // Job is still pending or processing, continue polling
                            setTimeout(poll, 3000);
                        }
                    } else {
                        setIsGenerating(false);
                        setAiError(true);
                        console.error("Error checking job status:", data);
                        alert(__("Error checking rewrite status. Please try again.", "essential-blocks"));
                    }
                })
                .catch((error) => {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("AJAX Error during polling:", error);
                    alert(__("Error connecting to the server. Please try again.", "essential-blocks"));
                });
        };

        // Start polling
        poll();
    };

    /**
     * Handle initial content rewrite
     */
    const handleRewriteNow = (event) => {
        generateAIRewrite(event, false); // Pass event and false for initial rewrite
    };

    /**
     * Helper function to recursively create blocks from nested structure
     */
    const createBlockFromData = (blockData) => {
        if (!blockData || !blockData.name) {
            return null;
        }

        const innerBlocks = blockData.innerBlocks
            ? blockData.innerBlocks.map(createBlockFromData).filter(Boolean)
            : [];

        return createBlock(
            blockData.name,
            blockData.attributes || {},
            innerBlocks
        );
    };

    /**
     * Handle keeping the rewritten content
     */
    const handleKeepContent = () => {
        if (!rewrittenBlockObj || Object.keys(rewrittenBlockObj).length === 0) {
            setAiError(true);
            alert(__("No rewritten content available. Please try again.", "essential-blocks"));
            return;
        }

        try {
            // Check if we're dealing with a single block or multiple blocks
            if (rewrittenBlockObj.name && rewrittenBlockObj.attributes) {
                // Single block replacement - update the current block with new attributes and innerBlocks
                const updatedAttributes = {
                    ...currentBlock.attributes,
                    ...rewrittenBlockObj.attributes
                };

                // If there are innerBlocks, we need to replace the entire block to update the structure
                if (rewrittenBlockObj.innerBlocks && rewrittenBlockObj.innerBlocks.length > 0) {
                    // Create a new block with the updated content using helper function
                    const newBlock = createBlockFromData({
                        name: rewrittenBlockObj.name,
                        attributes: updatedAttributes,
                        innerBlocks: rewrittenBlockObj.innerBlocks
                    });

                    if (newBlock) {
                        // Replace the current block with the new block structure
                        replaceBlock(currentBlock.clientId, newBlock);
                    } else {
                        throw new Error('Failed to create new block from rewritten data');
                    }
                } else {
                    // No innerBlocks, just update attributes
                    updateBlockAttributes(currentBlock.clientId, updatedAttributes);
                }
            } else {
                // Handle case where rewrittenBlockObj might be an array or different structure
                console.error('Unexpected rewritten block structure:', rewrittenBlockObj);
                setAiError(true);
                alert(__("Error applying rewritten content. Unexpected content structure.", "essential-blocks"));
                return;
            }

            // Close the popover after successful update
            onClose();

            // Optional: Show success message
            console.log('Successfully applied rewritten content to block');

        } catch (error) {
            console.error('Error applying rewritten content:', error);
            setAiError(true);
            alert(__("Error applying rewritten content. Please try again.", "essential-blocks"));
        }
    };

    /**
     * Handle regenerating the content
     */
    const handleRegenerateContent = (event) => {
        setShowRewrittenContent(false);
        generateAIRewrite(event, true); // Pass event and true for regeneration
    };

    /**
     * Handle closing the popover
     */
    const handleClose = () => {
        // Reset states when closing
        setAiPrompt('');
        setAiTone('friendly');
        setIsGenerating(false);
        setAiError(false);
        setRewrittenContent(null);
        setShowRewrittenContent(false);
        onClose();
    };

    /**
     * Handle suggested prompt click
     */
    const handleSuggestedPromptClick = (prompt) => {
        setAiPrompt(prompt);
        // Focus the textarea after setting the prompt
        setTimeout(() => {
            if (textareaRef.current) {
                textareaRef.current.focus();
            }
        }, 100);
    };

    if (!isVisible) {
        return null;
    }

    return (
        <Popover
            anchor={anchor}
            onClose={handleClose}
            placement="bottom-start"
            className="eb-controls-ai-popover eb-controls-rewrite-ai-popover"
        >
            <div className="eb-ai-popover-content">
                <div className="eb-ai-header">
                    <div className="eb-ai-logo">
                        <EBLogo />
                    </div>
                    <div className="eb-ai-header-content">
                        <h4 style={{ margin: '0 0 8px 0', fontSize: '16px', fontWeight: '600' }}>
                            {__('Rewrite with AI', 'essential-blocks')}
                        </h4>
                        <p style={{ margin: '0 0 16px 0', fontSize: '13px', color: '#666' }}>
                            Rewrite and improve your existing content with the power of AI
                        </p>
                    </div>
                    <Button
                        variant="custom"
                        className="eb-ai-close-button"
                        onClick={handleClose}
                    >
                        <Dashicon icon="no-alt" />
                    </Button>
                </div>

                {/* Suggested Prompts */}
                <div className="eb-ai-suggested-prompts">
                    <label className="eb-ai-label">
                        {__('Suggested Prompts', 'essential-blocks')}
                    </label>
                    <div className="eb-ai-prompt-buttons">
                        {getSuggestedPrompts().map((prompt, index) => (
                            <Button
                                key={index}
                                variant="secondary"
                                size="small"
                                className="eb-ai-prompt-button"
                                onClick={() => handleSuggestedPromptClick(prompt)}
                            >
                                {prompt}
                            </Button>
                        ))}
                    </div>
                </div>

                {/* Prompt Input */}
                <div className="eb-ai-prompt-section">
                    <TextareaControl
                        label={__('Rewrite Instructions', 'essential-blocks')}
                        value={aiPrompt}
                        onChange={setAiPrompt}
                        placeholder={__('Describe how you want to rewrite the content...', 'essential-blocks')}
                        rows={3}
                        ref={textareaRef}
                        className="eb-ai-prompt-textarea"
                    />
                </div>

                {/* Settings */}
                <div className="eb-ai-settings">
                    <SelectControl
                        label={__('Tone', 'essential-blocks')}
                        value={aiTone}
                        options={[
                            { label: __('Friendly', 'essential-blocks'), value: 'friendly' },
                            { label: __('Professional', 'essential-blocks'), value: 'professional' },
                            { label: __('Bold', 'essential-blocks'), value: 'bold' },
                            { label: __('Quirky', 'essential-blocks'), value: 'quirky' },
                            { label: __('Urgent', 'essential-blocks'), value: 'urgent' }
                        ]}
                        onChange={setAiTone}
                    />
                </div>

                {/* Loading State Display */}
                {isGenerating && (
                    <div className="eb-ai-generated-content">
                        <label className="eb-ai-label">
                            {__('Rewriting Content...', 'essential-blocks')}
                        </label>

                        <div className="eb-ai-generating">
                            <GeneratingIcon />
                            <span>{__('AI is rewriting your content...', 'essential-blocks')}</span>
                        </div>
                    </div>
                )}

                {/* Rewritten Content Preview */}
                {showRewrittenContent && rewrittenBlockObj && Object.keys(rewrittenBlockObj).length > 0 && (
                    <div className="eb-ai-rewritten-content">
                        <label className="eb-ai-label">
                            {__('Rewritten Content Preview', 'essential-blocks')}
                        </label>

                        <div className="eb-ai-content-preview">
                            <div className="eb-ai-content-display">
                                <BlockPreview
                                    blocks={{ ...rewrittenBlockObj }}
                                    viewportWidth={1200}
                                    minHeight={200}
                                    additionalStyles={[
                                        {
                                            css: `
                                                .block-editor-block-preview__content {
                                                    transform: scale(0.8);
                                                    transform-origin: top left;
                                                    width: 125%;
                                                    height: 125%;
                                                    overflow: hidden;
                                                }
                                            `
                                        }
                                    ]}
                                />
                            </div>
                        </div>

                        <div className="eb-ai-preview-actions">
                            <Button
                                variant="primary"
                                onClick={handleKeepContent}
                                className="eb-ai-keep-button"
                            >
                                <AiIcon />
                                {__('Keep This Content', 'essential-blocks')}
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={handleRegenerateContent}
                                className="eb-ai-regenerate-button"
                            >
                                {/* <GeneratingIcon /> */}
                                {__('Regenerate', 'essential-blocks')}
                            </Button>
                        </div>
                    </div>
                )}

                {/* Action Buttons - Only show when not showing rewritten content */}
                {!showRewrittenContent && (
                    <div className="eb-ai-actions">
                        <Button
                            variant="primary"
                            onClick={handleRewriteNow}
                            disabled={isGenerating || !aiPrompt.trim()}
                            className="eb-ai-generate-button"
                        >
                            {isGenerating ? (
                                <>
                                    <GeneratingIcon />
                                    {__('Rewriting...', 'essential-blocks')}
                                </>
                            ) : (
                                <>
                                    <AiIcon />
                                    {__('Rewrite Now', 'essential-blocks')}
                                </>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </Popover>
    );
};

export default AIRewritePopover;
