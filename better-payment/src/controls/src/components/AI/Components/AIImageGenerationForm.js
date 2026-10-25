/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Button, TextareaControl } from '@wordpress/components';
import { useRef } from '@wordpress/element';

import AiIcon from '../../../icons/ai-icon';

/**
 * AIImageGenerationForm - Component for AI image generation form
 *
 * @param {Object} props - Component props
 * @param {string} props.aiPrompt - AI prompt text
 * @param {Function} props.handlePromptChange - Function to handle prompt changes
 * @param {string} props.aiModel - Selected AI model
 * @param {Function} props.handleModelChange - Function to handle model changes
 * @param {number} props.aiImageCount - Number of images to generate
 * @param {Function} props.setAiImageCount - Function to set image count
 * @param {string} props.imageCategory - Selected image category
 * @param {Function} props.handleCategoryChange - Function to handle category changes
 * @param {string} props.imageType - Selected image type
 * @param {Function} props.setImageType - Function to set image type
 * @param {string} props.aiImageSize - Selected image size
 * @param {Function} props.setAiImageSize - Function to set image size
 * @param {string} props.aiImageQuality - Selected image quality
 * @param {Function} props.setAiImageQuality - Function to set image quality
 * @param {string} props.aiImageStyle - Selected image style
 * @param {Function} props.setAiImageStyle - Function to set image style
 * @param {string} props.aiOutputFormat - Selected output format
 * @param {Function} props.setAiOutputFormat - Function to set output format
 * @param {string} props.aiOutputCompression - Selected output compression
 * @param {Function} props.setAiOutputCompression - Function to set output compression
 * @param {string} props.aiBackground - Selected background setting
 * @param {Function} props.setAiBackground - Function to set background
 * @param {boolean} props.hasGenerated - Whether images have been generated
 * @param {boolean} props.isGenerating - Whether generation is in progress
 * @param {boolean} props.isSaving - Whether saving is in progress
 * @param {boolean} props.aiError - Whether there is an error
 * @param {Function} props.handleGenerateAgain - Function to handle generate again
 * @param {Function} props.generateAIImage - Function to generate AI image
 * @param {Function} props.getAvailableModels - Function to get available models
 * @param {Function} props.getAvailableImageCounts - Function to get available image counts
 * @param {Function} props.getImageCategories - Function to get image categories
 * @param {Function} props.getAvailableSizes - Function to get available sizes
 * @param {Function} props.isControlVisible - Function to check if control is visible
 * @param {Function} props.getModelForSize - Function to get model for size
 */
const AIImageGenerationForm = ({
    aiPrompt,
    handlePromptChange,
    aiModel,
    handleModelChange,
    aiImageCount,
    setAiImageCount,
    imageCategory,
    handleCategoryChange,
    imageType,
    setImageType,
    aiImageSize,
    setAiImageSize,
    aiImageQuality,
    setAiImageQuality,
    aiImageStyle,
    setAiImageStyle,
    aiOutputFormat,
    setAiOutputFormat,
    aiOutputCompression,
    setAiOutputCompression,
    aiBackground,
    setAiBackground,
    hasGenerated,
    isGenerating,
    isSaving,
    aiError,
    handleGenerateAgain,
    generateAIImage,
    getAvailableModels,
    getAvailableImageCounts,
    getImageCategories,
    getAvailableSizes,
    isControlVisible,
    getModelForSize
}) => {
    // Ref for TextareaControl to focus
    const textareaRef = useRef(null);

    return (
        <div className="eb-ai-form">
            <TextareaControl
                ref={textareaRef}
                label={__('Prompt', 'essential-blocks')}
                value={aiPrompt}
                onChange={handlePromptChange}
                placeholder={__('Describe the image you want to generate...', 'essential-blocks')}
                rows={3}
                className="eb-ai-prompt-textarea"
            />

            {/* Manual Model Selection and Image Count Controls */}
            <div className="eb-ai-form-group">
                <SelectControl
                    label={__('AI Model', 'essential-blocks')}
                    value={aiModel}
                    onChange={handleModelChange}
                    options={getAvailableModels()}
                />

                <SelectControl
                    label={__('Number of Images', 'essential-blocks')}
                    value={aiImageCount}
                    onChange={setAiImageCount}
                    options={getAvailableImageCounts()}
                />
            </div>

            {/* Image Categorization Controls */}
            <div className="eb-ai-form-group">
                <SelectControl
                    label={__('Image Category', 'essential-blocks')}
                    value={imageCategory}
                    onChange={handleCategoryChange}
                    options={Object.keys(getImageCategories()).map(category => ({
                        label: __(category, 'essential-blocks'),
                        value: category
                    }))}
                />

                <SelectControl
                    label={__('Type', 'essential-blocks')}
                    value={imageType}
                    onChange={setImageType}
                    options={(getImageCategories()[imageCategory] || []).map(type => ({
                        label: __(type, 'essential-blocks'),
                        value: type
                    }))}
                />
            </div>

            <div className="eb-ai-form-group">
                <SelectControl
                    label={__('Image Size', 'essential-blocks')}
                    value={aiImageSize}
                    onChange={setAiImageSize}
                    options={getAvailableSizes()}
                />

                {isControlVisible('imageQuality') && (() => {
                    const selectedModel = aiModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : aiModel;

                    if (selectedModel === 'gpt-image-1') {
                        return (
                            <SelectControl
                                label={__('Image Quality', 'essential-blocks')}
                                value={aiImageQuality}
                                options={[
                                    { label: __('Auto', 'essential-blocks'), value: 'auto' },
                                    { label: __('High', 'essential-blocks'), value: 'high' },
                                    { label: __('Medium', 'essential-blocks'), value: 'medium' },
                                    { label: __('Low', 'essential-blocks'), value: 'low' }
                                ]}
                                onChange={setAiImageQuality}
                            />
                        );
                    } else if (selectedModel === 'dall-e-3') {
                        return (
                            <SelectControl
                                label={__('Image Quality', 'essential-blocks')}
                                value={aiImageQuality}
                                options={[
                                    { label: __('Auto', 'essential-blocks'), value: 'auto' },
                                    { label: __('Standard', 'essential-blocks'), value: 'standard' },
                                    { label: __('HD', 'essential-blocks'), value: 'hd' }
                                ]}
                                onChange={setAiImageQuality}
                            />
                        );
                    }
                    return null;
                })()}

                {isControlVisible('imageStyle') && (
                    <SelectControl
                        label={__('Image Style', 'essential-blocks')}
                        value={aiImageStyle}
                        onChange={setAiImageStyle}
                        options={[
                            { label: __('None', 'essential-blocks'), value: 'none' },
                            { label: __('Vivid', 'essential-blocks'), value: 'vivid' },
                            { label: __('Natural', 'essential-blocks'), value: 'natural' }
                        ]}
                    />
                )}

                {isControlVisible('outputFormat') && (
                    <SelectControl
                        label={__('Output Format', 'essential-blocks')}
                        value={aiOutputFormat}
                        onChange={setAiOutputFormat}
                        options={[
                            { label: __('Auto', 'essential-blocks'), value: 'auto' },
                            { label: __('PNG', 'essential-blocks'), value: 'png' },
                            { label: __('JPEG', 'essential-blocks'), value: 'jpeg' },
                            { label: __('WEBP', 'essential-blocks'), value: 'webp' }
                        ]}
                    />
                )}

                {isControlVisible('outputCompression') && (
                    <SelectControl
                        label={__('Output Compression', 'essential-blocks')}
                        value={aiOutputCompression}
                        onChange={setAiOutputCompression}
                        options={[
                            { label: __('High', 'essential-blocks'), value: 'high' },
                            { label: __('Medium', 'essential-blocks'), value: 'medium' },
                            { label: __('Low', 'essential-blocks'), value: 'low' }
                        ]}
                    />
                )}

                {isControlVisible('background') && (
                    <SelectControl
                        label={__('Background', 'essential-blocks')}
                        value={aiBackground}
                        onChange={setAiBackground}
                        options={[
                            { label: __('Auto (Default)', 'essential-blocks'), value: 'auto' },
                            { label: __('Transparent', 'essential-blocks'), value: 'transparent' },
                            { label: __('Opaque', 'essential-blocks'), value: 'opaque' }
                        ]}
                    />
                )}
            </div>

            <div className="eb-ai-action-buttons-wrapper">
                {hasGenerated && !isGenerating && !isSaving ? (
                    <Button
                        variant="secondary"
                        onClick={handleGenerateAgain}
                        disabled={!aiPrompt.trim() || !window.EssentialBlocksLocalize?.hasOpenAiApiKey}
                        className="eb-ai-action-button eb-ai-generate-again-button"
                    >
                        <AiIcon />
                        {__('Generate Again', 'essential-blocks')}
                    </Button>
                ) : (
                    <Button
                        variant="primary"
                        onClick={generateAIImage}
                        disabled={isGenerating || isSaving || !aiPrompt.trim() || !window.EssentialBlocksLocalize?.hasOpenAiApiKey}
                        className={`eb-ai-action-button eb-ai-generate-button ${aiError ? 'eb-button-error' : ''}`}
                    >
                        <AiIcon />
                        {isGenerating ? (
                            <>
                                {__('Generating images...', 'essential-blocks')}
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
    );
};

export default AIImageGenerationForm;
