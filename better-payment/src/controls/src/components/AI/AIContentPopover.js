/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Popover, Button, TextareaControl } from '@wordpress/components';
import { useState, useRef } from '@wordpress/element';
import { Dashicon } from '@wordpress/components';

import EBLogo from '../../icons/eb-logo';
import AiIcon from '../../icons/ai-icon';
import GeneratingIcon from '../../icons/generating-icon';

/**
 * AIContentPopover - A component for AI content generation
 *
 * @param {Object} props - Component props
 * @param {boolean} props.isVisible - Whether the popover is visible
 * @param {Function} props.onClose - Function to close the popover
 * @param {Object} props.anchor - Anchor element for the popover
 * @param {Function} props.onContentGenerated - Callback when content is generated
 * @param {string} props.contentType - Type of content to generate ('text' or 'paragraph')
 * @param {string} props.currentValue - Current value of the input field
 */
const AIContentPopover = ({
    isVisible,
    onClose,
    anchor,
    onContentGenerated,
    contentType = 'text',
    currentValue = '',
    contentFor = 'writeInputFields'
}) => {

    // AI content generation states
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiContentLength, setAiContentLength] = useState(contentType === 'text' ? 'short' : 'medium');
    const [aiTone, setAiTone] = useState('informative');
    const [isGenerating, setIsGenerating] = useState(false);
    const [aiError, setAiError] = useState(false);
    const [generatedContent, setGeneratedContent] = useState('');
    const [hasGenerated, setHasGenerated] = useState(false);

    // Ref for TextareaControl to focus
    const textareaRef = useRef(null);

    /**
     * Suggested prompts for different content types
     */
    const getSuggestedPrompts = () => {
        return {
            text: [
                __('Write a catchy headline for', 'essential-blocks'),
                __('Create a title for a hero section about', 'essential-blocks'),
                __('Generate an event title for', 'essential-blocks'),
                __('Write a product name for', 'essential-blocks'),
                __('Create a short CTA (Call-to-Action) headline for', 'essential-blocks')
            ],
            paragraph: [
                __('Write a short description for', 'essential-blocks'),
                __('Create a product overview for', 'essential-blocks'),
                __('Generate a service intro for', 'essential-blocks'),
                __('Write a paragraph for a section about', 'essential-blocks'),
                __('Summarize the features of', 'essential-blocks')
            ]
        };
    };

    /**
     * Get length instructions based on length
     */
    const getLengthInstructions = (length) => {
        switch (length) {
            case 'short':
                return __('Keep it very concise, 3-8 words maximum.', 'essential-blocks');
            case 'medium':
                return __('Keep it brief, 10-20 words maximum.', 'essential-blocks');
            case 'large':
                return __('Make it descriptive, 20-40 words maximum.', 'essential-blocks');
            case 'big':
                return __('Create detailed content, 40-80 words maximum.', 'essential-blocks');
            default:
                return __('Keep it brief, 10-20 words maximum.', 'essential-blocks');
        }
    };

    /**
     * Get tone instructions
     */
    const getToneInstructions = (tone) => {
        switch (tone) {
            case 'informative':
                return __('clear and informative', 'essential-blocks');
            case 'casual':
                return __('relaxed and conversational', 'essential-blocks');
            case 'friendly':
                return __('friendly and approachable', 'essential-blocks');
            case 'professional':
                return __('professional and polished', 'essential-blocks');
            case 'inspirational':
                return __('motivating and uplifting', 'essential-blocks');
            case 'minimal':
                return __('clean and concise', 'essential-blocks');
            default:
                return __('clear and informative', 'essential-blocks');
        }
    };

    /**
     * Generate AI content
     */
    const generateAIContent = (event, isRegeneration = false) => {
        // Handle event properly to prevent Firefox popover closing
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!aiPrompt.trim()) {
            alert(__("Please enter a prompt", "essential-blocks"));
            return;
        }

        setIsGenerating(true);
        setAiError(false);

        // Build the full prompt with instructions
        const toneInstructions = getToneInstructions(aiTone);
        const lengthInstructions = getLengthInstructions(aiContentLength);

        let fullPrompt = aiPrompt;
        if (!fullPrompt.toLowerCase().includes('tone') && !fullPrompt.toLowerCase().includes('style')) {
            fullPrompt += ` Make it ${toneInstructions}.`;
        }
        fullPrompt += ` ${lengthInstructions}`;

        // Add regeneration instruction if this is a regeneration
        if (isRegeneration && generatedContent) {
            fullPrompt += ` IMPORTANT: Please generate a different version than this previous result: "${generatedContent}". Create something unique and different while following the same requirements.`;
        }

        fullPrompt += ` IMPORTANT: Provide only the text content without any HTML tags, formatting, or additional explanations. Just return the requested content directly.`;

        // Start AI job
        let data = new FormData();
        data.append("action", "start_ai_job");
        data.append("admin_nonce", window.EssentialBlocksLocalize?.admin_nonce || '');
        data.append("job_type", "content");
        data.append("prompt", fullPrompt);
        data.append("content_for", contentFor);

        fetch(window.EssentialBlocksLocalize?.ajax_url || '/wp-admin/admin-ajax.php', {
            method: "POST",
            body: data,
        })
            .then((res) => res.json())
            .then((response) => {
                if (response.success) {
                    // Start polling for job completion
                    pollJobStatus(response.data.job_id);
                } else {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("Error starting AI job:", response.data?.message);
                    alert(__("Error starting content generation. Please try again.", "essential-blocks"));
                }
            })
            .catch((error) => {
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
        const maxPollingTime = 3 * 60 * 1000; // 3 minutes
        const pollingInterval = 3000; // 3 seconds
        const startTime = Date.now();

        const poll = () => {
            // Check if we've exceeded the maximum polling time
            if (Date.now() - startTime > maxPollingTime) {
                setIsGenerating(false);
                setAiError(true);
                alert(__("Content generation timed out. Please try again.", "essential-blocks"));
                return;
            }

            // Make AJAX request to check job status
            let data = new FormData();
            data.append("action", "check_ai_job_status");
            data.append("admin_nonce", window.EssentialBlocksLocalize?.admin_nonce || '');
            data.append("job_id", jobId);

            fetch(window.EssentialBlocksLocalize?.ajax_url || '/wp-admin/admin-ajax.php', {
                method: "POST",
                body: data,
            })
                .then((res) => res.json())
                .then((response) => {
                    if (response.success) {
                        const status = response.data.status;

                        if (status === 'completed') {
                            setIsGenerating(false);

                            if (response.data.result && response.data.result.success) {
                                // Clean the generated content
                                let cleanedContent = response.data.result.content;

                                // Remove any HTML tags and clean up the content
                                cleanedContent = cleanedContent.replace(/<[^>]*>/g, '').trim();

                                // Remove surrounding quotes if present
                                cleanedContent = cleanedContent.replace(/^["']|["']$/g, '');

                                // Store the generated content and mark as generated
                                setGeneratedContent(cleanedContent);
                                setHasGenerated(true);
                            } else {
                                setAiError(true);
                                const errorMessage = response.data.result?.message || __("Unknown error occurred", "essential-blocks");
                                console.error("Error in AI generation result:", errorMessage);
                                alert(__("Error generating content. Please try again.", "essential-blocks"));
                            }
                        } else if (status === 'failed' || status === 'expired') {
                            setIsGenerating(false);
                            setAiError(true);
                            const errorMessage = response.data.error || __("Content generation failed", "essential-blocks");
                            console.error("AI job failed:", errorMessage);
                            alert(__("Error generating content. Please try again.", "essential-blocks"));
                        } else if (status === 'pending' || status === 'processing') {
                            // Continue polling
                            setTimeout(poll, pollingInterval);
                        }
                    } else {
                        setIsGenerating(false);
                        setAiError(true);
                        console.error("Error checking job status:", response.data?.message);
                        alert(__("Error checking generation status. Please try again.", "essential-blocks"));
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
     * Handle keeping the generated content
     */
    const handleKeepContent = () => {
        onContentGenerated(generatedContent);
        onClose();
    };

    /**
     * Handle generating content again
     */
    const handleGenerateAgain = (event) => {
        generateAIContent(event, true); // Pass event and true to indicate this is a regeneration
    };

    /**
     * Handle initial content generation
     */
    const handleGenerateNow = (event) => {
        generateAIContent(event, false); // Pass event and false for initial generation
    };

    /**
     * Handle popover close
     */
    const handleClose = () => {
        setAiError(false);
        setGeneratedContent('');
        setHasGenerated(false);
        onClose();
    };


    if (!isVisible) {
        return null;
    }

    return (
        <>
            <Popover
                anchor={anchor}
                onClose={() => {
                    if (!isGenerating) {
                        handleClose();
                    }
                }}
                placement="bottom-start"
                className="eb-controls-ai-popover eb-controls-write-ai-popover"
            >
                <div className="eb-ai-popover-content">
                    <div className="eb-ai-header">
                        <div className="eb-ai-logo">
                            <EBLogo />
                        </div>
                        <div className="eb-ai-header-content">
                            <h4 style={{ margin: '0 0 8px 0', fontSize: '16px', fontWeight: '600' }}>
                                {__('AI Content Generator', 'essential-blocks')}
                            </h4>
                            <p style={{ margin: '0 0 16px 0', fontSize: '13px', color: '#666' }}>
                                Generate engaging and relevant content with the power of AI
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

                    {window.EssentialBlocksLocalize?.hasOpenAiApiKey !== "1" && (
                        <div style={{
                            padding: '12px',
                            backgroundColor: '#fff3cd',
                            border: '1px solid #ffeaa7',
                            borderRadius: '4px',
                            marginBottom: '16px'
                        }}>
                            <p style={{ margin: '0', fontSize: '13px', color: '#856404' }}>
                                {__('Please add your OpenAI API Key to use this feature.', 'essential-blocks')}{' '}
                                <a
                                    href={`${EssentialBlocksLocalize?.eb_admin_url}admin.php?page=essential-blocks&tab=ai-suite`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {__('Go to Settings', 'essential-blocks')}
                                </a>
                            </p>
                        </div>
                    )}

                    <div className="eb-ai-form">
                        <TextareaControl
                            ref={textareaRef}
                            label={__('Prompt', 'essential-blocks')}
                            value={aiPrompt}
                            onChange={setAiPrompt}
                            placeholder={__('Type your idea', 'essential-blocks')}
                            rows={3}
                            style={{ marginBottom: '12px' }}
                        />

                        <div style={{ marginBottom: '12px' }}>
                            <label style={{ display: 'block', marginBottom: '4px', fontSize: '13px', fontWeight: '500' }}>
                                {__('Suggested Prompts', 'essential-blocks')}
                            </label>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
                                {getSuggestedPrompts()[contentType].map((prompt, index) => (
                                    <button
                                        key={index}
                                        type="button"
                                        onClick={() => {
                                            setAiPrompt(`${prompt} `);
                                            // Focus the textarea after setting the prompt and set cursor to end
                                            setTimeout(() => {
                                                if (textareaRef.current) {
                                                    textareaRef.current.focus();
                                                    // Set cursor position to the end of the text
                                                    const textLength = `${prompt} `.length;
                                                    textareaRef.current.setSelectionRange(textLength, textLength);
                                                }
                                            }, 0);
                                        }}
                                        style={{
                                            padding: '4px 8px',
                                            fontSize: '11px',
                                            border: '1px solid #ddd',
                                            borderRadius: '12px',
                                            backgroundColor: '#f8f9fa',
                                            cursor: 'pointer',
                                            transition: 'all 0.2s'
                                        }}
                                        onMouseOver={(e) => {
                                            e.target.style.backgroundColor = '#e9ecef';
                                            e.target.style.borderColor = '#adb5bd';
                                        }}
                                        onMouseOut={(e) => {
                                            e.target.style.backgroundColor = '#f8f9fa';
                                            e.target.style.borderColor = '#ddd';
                                        }}
                                    >
                                        {prompt}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="eb-ai-form-group">
                            <SelectControl
                                label={__('Length', 'essential-blocks')}
                                value={aiContentLength}
                                onChange={setAiContentLength}
                                options={[
                                    { label: __('Short (3-8 words)', 'essential-blocks'), value: 'short' },
                                    { label: __('Medium (10-15 words)', 'essential-blocks'), value: 'medium' },
                                    { label: __('Large (20-30 words)', 'essential-blocks'), value: 'large' },
                                    { label: __('Big (40-60 words)', 'essential-blocks'), value: 'big' }
                                ]}
                            />

                            <SelectControl
                                label={__('Tone', 'essential-blocks')}
                                value={aiTone}
                                options={[
                                    { label: __('Informative', 'essential-blocks'), value: 'informative' },
                                    { label: __('Casual', 'essential-blocks'), value: 'casual' },
                                    { label: __('Friendly', 'essential-blocks'), value: 'friendly' },
                                    { label: __('Professional', 'essential-blocks'), value: 'professional' },
                                    { label: __('Inspirational', 'essential-blocks'), value: 'inspirational' },
                                    { label: __('Minimal', 'essential-blocks'), value: 'minimal' }
                                ]}
                                onChange={setAiTone}
                            />
                        </div>

                        {/* Generated Content Display */}
                        {(hasGenerated || isGenerating) && (
                            <>
                                <label className='eb-ai-generated-content-label'>GENERATED CONTENT</label>
                                <div style={{
                                    marginBottom: '16px',
                                    padding: '12px',
                                    backgroundColor: '#ebe4ff',
                                    border: '1px solid #ef9bff',
                                    borderRadius: '4px'
                                }}>
                                    {isGenerating ? (
                                        <div style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '8px',
                                            color: '#666',
                                            fontSize: '14px'
                                        }}>
                                            <GeneratingIcon color="#af2bc8" />
                                        </div>
                                    ) : (
                                        <div style={{
                                            fontSize: '14px',
                                            lineHeight: '1.5',
                                            color: '#af2bc8',
                                            whiteSpace: 'pre-wrap'
                                        }}>
                                            {generatedContent}
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                            {hasGenerated && !isGenerating ? (
                                <>
                                    <Button
                                        variant="secondary"
                                        onClick={handleGenerateAgain}
                                        disabled={!aiPrompt.trim() || !window.EssentialBlocksLocalize?.hasOpenAiApiKey}
                                        className="eb-ai-action-button eb-ai-generate-again-button"
                                        type="button"
                                    >
                                        <AiIcon />
                                        {__('Generate Again', 'essential-blocks')}
                                    </Button>
                                    <Button
                                        variant="primary"
                                        onClick={handleKeepContent}
                                        className="eb-ai-action-button eb-ai-keep-button"
                                        style={{ backgroundColor: '#5632d1', minWidth: '120px' }}
                                        type="button"
                                    >
                                        {__('Keep', 'essential-blocks')}
                                    </Button>
                                </>
                            ) : (
                                <Button
                                    variant="primary"
                                    onClick={handleGenerateNow}
                                    disabled={isGenerating || !aiPrompt.trim() || !window.EssentialBlocksLocalize?.hasOpenAiApiKey}
                                    className={`eb-ai-action-button eb-ai-generate-button ${aiError ? 'eb-button-error' : ''}`}
                                    type="button"
                                >
                                    <AiIcon />
                                    {isGenerating ? (
                                        <>
                                            {__('Generating...', 'essential-blocks')}
                                        </>
                                    ) : (
                                        <>
                                            {aiError ? __('Try Again', 'essential-blocks') : __('Generate Now', 'essential-blocks')}
                                        </>
                                    )}
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </Popover>
        </>
    );
};

export default AIContentPopover;
