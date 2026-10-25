/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, TextareaControl, SelectControl, Dashicon, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';

import ColorControl from '../../../controls/color-control';
import AiIcon from '../../../icons/ai-icon';

/**
 * AIImageEditingControls - Component for image editing controls
 *
 * @param {Object} props - Component props
 * @param {string|null} props.selectedEditingOption - Currently selected editing option
 * @param {Function} props.setSelectedEditingOption - Function to set selected editing option
 * @param {string} props.editingPrompt - Editing prompt text
 * @param {Function} props.setEditingPrompt - Function to set editing prompt
 * @param {number} props.editingImageCount - Number of images to generate for editing
 * @param {Function} props.setEditingImageCount - Function to set editing image count
 * @param {string} props.backgroundDescription - Background description for replace background
 * @param {Function} props.setBackgroundDescription - Function to set background description
 * @param {string} props.isolateBackgroundColor - Background color for isolate object
 * @param {Function} props.setIsolateBackgroundColor - Function to set isolate background color
 * @param {Function} props.handleEditingGeneration - Function to handle editing generation
 * @param {boolean} props.isGenerating - Whether generation is in progress
 * @param {boolean} props.isSaving - Whether saving is in progress
 * @param {boolean} props.hasGeneratedImage - Whether there is a newly generated image
 * @param {Function} props.onDiscardGeneratedImage - Function to handle discarding generated image
 * @param {Function} props.onKeepGeneratedImage - Function to handle keeping generated image
 */
const AIImageEditingControls = ({
    selectedEditingOption,
    setSelectedEditingOption,
    editingPrompt,
    setEditingPrompt,
    editingImageCount,
    setEditingImageCount,
    backgroundDescription,
    setBackgroundDescription,
    isolateBackgroundColor,
    setIsolateBackgroundColor,
    handleEditingGeneration,
    isGenerating,
    isSaving,
    hasGeneratedImage,
    onDiscardGeneratedImage,
    onKeepGeneratedImage
}) => {
    // State for confirmation dialog
    const [showConfirmationDialog, setShowConfirmationDialog] = useState(false);

    /**
     * Handle back to options button click
     */
    const handleBackToOptions = () => {
        // If there's a generated image, show confirmation dialog
        if (hasGeneratedImage) {
            setShowConfirmationDialog(true);
        } else {
            // No generated image, just go back
            setSelectedEditingOption(null);
        }
    };

    /**
     * Handle discard generated image
     */
    const handleDiscardImage = () => {
        setShowConfirmationDialog(false);
        onDiscardGeneratedImage();
        setSelectedEditingOption(null);
    };

    /**
     * Handle keep generated image
     */
    const handleKeepImage = () => {
        setShowConfirmationDialog(false);
        onKeepGeneratedImage();
        setSelectedEditingOption(null);
    };

    /**
     * Handle cancel confirmation dialog
     */
    const handleCancelConfirmation = () => {
        setShowConfirmationDialog(false);
    };

    return (
        <div className="eb-ai-editing-controls">
            {/* Show main options grid or specific option controls */}
            {selectedEditingOption === null ? (
                /* Main Options Grid */
                <div className="eb-ai-editing-options-grid">
                    <div className="eb-ai-editing-option-card" onClick={() => setSelectedEditingOption('variations')}>
                        <div className="eb-ai-option-icon">
                            <Dashicon icon="images-alt2" />
                        </div>
                        <h4 className="eb-ai-option-title">{__('Variations', 'essential-blocks')}</h4>
                        <p className="eb-ai-option-description">{__('Create different versions of your image', 'essential-blocks')}</p>
                    </div>

                    <div className="eb-ai-editing-option-card" onClick={() => setSelectedEditingOption('remove-background')}>
                        <div className="eb-ai-option-icon">
                            <Dashicon icon="format-image" />
                        </div>
                        <h4 className="eb-ai-option-title">{__('Remove Background', 'essential-blocks')}</h4>
                        <p className="eb-ai-option-description">{__('Make background transparent', 'essential-blocks')}</p>
                    </div>

                    <div className="eb-ai-editing-option-card" onClick={() => setSelectedEditingOption('replace-background')}>
                        <div className="eb-ai-option-icon">
                            <Dashicon icon="admin-customizer" />
                        </div>
                        <h4 className="eb-ai-option-title">{__('Replace Background', 'essential-blocks')}</h4>
                        <p className="eb-ai-option-description">{__('Change to a new background scene', 'essential-blocks')}</p>
                    </div>

                    <div className="eb-ai-editing-option-card" onClick={() => setSelectedEditingOption('isolate-object')}>
                        <div className="eb-ai-option-icon">
                            <Dashicon icon="admin-appearance" />
                        </div>
                        <h4 className="eb-ai-option-title">{__('Isolate Object', 'essential-blocks')}</h4>
                        <p className="eb-ai-option-description">{__('Professional product-style image', 'essential-blocks')}</p>
                    </div>
                </div>
            ) : (
                /* Specific Option Controls */
                <div className="eb-ai-editing-option-controls">
                    {/* Back Button */}
                    <div className="eb-ai-editing-back-header">
                        <Button
                            variant="tertiary"
                            onClick={handleBackToOptions}
                            className="eb-ai-editing-back-button"
                        >
                            <Dashicon icon="arrow-left-alt2" />
                            {__('Back to Options', 'essential-blocks')}
                        </Button>
                    </div>

                    {/* Render specific option controls based on selectedEditingOption */}
                    {selectedEditingOption === 'variations' && (
                        <div className="eb-ai-editing-specific-controls">
                            <h4 className="eb-ai-editing-option-title">{__('Create Variations', 'essential-blocks')}</h4>
                            <TextareaControl
                                label={__('Additional prompt instructions (optional)', 'essential-blocks')}
                                value={editingPrompt}
                                onChange={setEditingPrompt}
                                placeholder={__('Add specific instructions for variations...', 'essential-blocks')}
                                rows={3}
                            />
                            <SelectControl
                                label={__('Number of variations', 'essential-blocks')}
                                value={editingImageCount}
                                onChange={setEditingImageCount}
                                options={Array.from({ length: 4 }, (_, i) => ({
                                    label: __(String(i + 1), 'essential-blocks'),
                                    value: i + 1
                                }))}
                            />
                            <Button
                                variant="primary"
                                onClick={() => handleEditingGeneration('variations')}
                                disabled={isGenerating || isSaving}
                                className="eb-ai-action-button eb-ai-generate-button"
                            >
                                <AiIcon />
                                {isGenerating ? (
                                    <>
                                        {__('Generating...', 'essential-blocks')}
                                    </>
                                ) : (
                                    <>
                                        {__('Generate Variations', 'essential-blocks')}
                                    </>
                                )}
                            </Button>
                        </div>
                    )}

                    {selectedEditingOption === 'remove-background' && (
                        <div className="eb-ai-editing-specific-controls">
                            <h4 className="eb-ai-editing-option-title">{__('Remove Background', 'essential-blocks')}</h4>
                            <div className="eb-ai-editing-prompt-preview">
                                <p><strong>{__('Generated prompt:', 'essential-blocks')}</strong></p>
                                <p className="eb-ai-prompt-text">{__('Remove background from this image, make it transparent', 'essential-blocks')}</p>
                            </div>
                            <Button
                                variant="primary"
                                onClick={() => handleEditingGeneration('remove-background')}
                                disabled={isGenerating || isSaving}
                                className="eb-ai-action-button eb-ai-generate-button"
                            >
                                <AiIcon />
                                {isGenerating ? (
                                    <>
                                        {__('Generating...', 'essential-blocks')}
                                    </>
                                ) : (
                                    <>
                                        {__('Generate', 'essential-blocks')}
                                    </>
                                )}
                            </Button>
                        </div>
                    )}

                    {selectedEditingOption === 'replace-background' && (
                        <div className="eb-ai-editing-specific-controls">
                            <h4 className="eb-ai-editing-option-title">{__('Replace Background', 'essential-blocks')}</h4>
                            <TextareaControl
                                label={__('Describe your desired background', 'essential-blocks')}
                                value={backgroundDescription}
                                onChange={setBackgroundDescription}
                                placeholder={__('e.g., tropical beach at sunset, modern office space, mountain landscape...', 'essential-blocks')}
                                rows={3}
                            />
                            <Button
                                variant="primary"
                                onClick={() => handleEditingGeneration('replace-background')}
                                disabled={isGenerating || isSaving || !backgroundDescription.trim()}
                                className="eb-ai-action-button eb-ai-generate-button"
                            >
                                <AiIcon />
                                {isGenerating ? (
                                    <>
                                        {__('Generating...', 'essential-blocks')}
                                    </>
                                ) : (
                                    <>
                                        {__('Generate', 'essential-blocks')}
                                    </>
                                )}
                            </Button>
                        </div>
                    )}

                    {selectedEditingOption === 'isolate-object' && (
                        <div className="eb-ai-editing-specific-controls">
                            <h4 className="eb-ai-editing-option-title">{__('Isolate Object', 'essential-blocks')}</h4>
                            <div className="eb-ai-color-picker-wrapper">
                                <ColorControl
                                    label={__(
                                        "Background Color",
                                        "essential-blocks"
                                    )}
                                    color={isolateBackgroundColor}
                                    isGradient={false}
                                    onChange={(value) => setIsolateBackgroundColor(value)}
                                />
                                <span className="eb-ai-color-value">{isolateBackgroundColor}</span>
                            </div>
                            <Button
                                variant="primary"
                                onClick={() => handleEditingGeneration('isolate-object')}
                                disabled={isGenerating || isSaving}
                                className="eb-ai-action-button eb-ai-generate-button"
                            >
                                <AiIcon />
                                {isGenerating ? (
                                    <>
                                        {__('Generating...', 'essential-blocks')}
                                    </>
                                ) : (
                                    <>
                                        {__('Generate', 'essential-blocks')}
                                    </>
                                )}
                            </Button>
                        </div>
                    )}
                </div>
            )}

            {/* Confirmation Dialog */}
            {showConfirmationDialog && (
                <Modal
                    title={__('Your AI Image Is Ready!', 'essential-blocks')}
                    onRequestClose={handleCancelConfirmation}
                    className="eb-ai-image-confirmation-dialog"
                    size="small"
                >
                    <div className="eb-ai-confirmation-content">
                        <p>{__('An AI image has been generated. Would you like to keep it or discard it?', 'essential-blocks')}</p>

                        <div className="eb-ai-confirmation-actions">
                            <Button
                                variant="secondary"
                                onClick={handleDiscardImage}
                                className="eb-ai-discard-button"
                            >
                                {__('Discard', 'essential-blocks')}
                            </Button>

                            <Button
                                variant="primary"
                                onClick={handleKeepImage}
                                className="eb-ai-keep-button"
                            >
                                {__('Keep', 'essential-blocks')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
};

export default AIImageEditingControls;
