/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Dashicon } from '@wordpress/components';

import AIImageGeneratingIcon from '../../../icons/ai-image-generating';
import AIImageSavingIcon from '../../../icons/ai-image-saving';
import SaveIcon from '../../../icons/save-icon';

/**
 * AIImageGeneratedPreview - Component for displaying generated images
 *
 * @param {Object} props - Component props
 * @param {Object} props.currentImage - Current image data
 * @param {boolean} props.isGenerating - Whether generation is in progress
 * @param {boolean} props.isSaving - Whether saving is in progress
 * @param {boolean} props.hasGenerated - Whether images have been generated
 * @param {Array} props.generatedImages - Array of generated images
 * @param {Object} props.usageInfo - Usage information
 * @param {Function} props.handleKeepImage - Function to handle keeping an image
 * @param {Function} props.setIsEditingMode - Function to set editing mode
 * @param {Function} props.setUserPreferredMode - Function to set user preferred mode
 */
const AIImageGeneratedPreview = ({
    currentImage,
    isGenerating,
    isSaving,
    hasGenerated,
    generatedImages,
    usageInfo,
    handleKeepImage,
    setIsEditingMode,
    isEditingMode,
    setUserPreferredMode
}) => {
    return (
        <div className="eb-ai-generated-images">
            {!isEditingMode && currentImage && currentImage.url && (
                <div className="eb-ai-current-image-section">
                    <button
                        className="eb-ai-switch-screen-button"
                        onClick={() => {
                            setIsEditingMode(true);
                            setUserPreferredMode('editing');
                        }}
                    >Click to Edit current image →</button>
                </div>
            )}
            {!isGenerating && generatedImages.length === 0 && (
                <>
                    <div className="eb-ai-no-images-found">
                        <img src={`${EssentialBlocksLocalize?.image_url}/image-placeholder.jpg`} className="eb-ai-gallery-image" />
                        <h4>{__('Your AI Generated Images', 'essential-blocks')}</h4>
                        <p>{__('Will appear here once generated', 'essential-blocks')}</p>
                    </div>
                </>
            )}

            {(hasGenerated || isGenerating || isSaving) && (
                <>
                    {(isGenerating || isSaving) ? (
                        <div className="eb-ai-loading-state">
                            {isSaving
                                ? <AIImageSavingIcon />
                                : <AIImageGeneratingIcon />
                            }
                        </div>
                    ) : (
                        <div className="eb-ai-generated-images-container">
                            <h4 className='eb-ai-generated-content-label'>GENERATED IMAGE{generatedImages.length > 1 ? 'S' : ''}</h4>
                            <div className="eb-ai-images-grid">
                                {generatedImages.map((image, index) => (
                                    <div key={index} className="eb-ai-image-card">
                                        <img
                                            src={image.url || (image.b64_json ? `data:image/png;base64,${image.b64_json}` : '')}
                                            alt={__('Generated image', 'essential-blocks') + ` ${index + 1}`}
                                            className="eb-ai-image-display"
                                        />
                                        <div className="eb-ai-image-actions">
                                            <Button
                                                variant="secondary"
                                                onClick={() => handleKeepImage(image, false)}
                                                disabled={isSaving}
                                                className="eb-ai-save-library-button"
                                                label='Save in Library'
                                                showTooltip={true}
                                                icon={<SaveIcon />}
                                            />
                                            <Button
                                                variant="primary"
                                                onClick={() => handleKeepImage(image, true)}
                                                disabled={isSaving}
                                                className="eb-ai-use-image-button"
                                                label='Use Image'
                                                showTooltip={true}
                                                icon={<Dashicon icon="arrow-down-alt" />}
                                            >
                                                {__('Use Image', 'essential-blocks')}
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Usage Information Display */}
                            {usageInfo && usageInfo.message && (
                                <div className="eb-ai-usage-info">
                                    <div className="eb-ai-usage-content">
                                        <Dashicon icon="info" />
                                        <span className="eb-ai-usage-message">
                                            {usageInfo.message}
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default AIImageGeneratedPreview;
