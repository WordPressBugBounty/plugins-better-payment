/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import SpinLoader from '../../../icons/spin-loader';

/**
 * AIImageEditingPreview - Component for displaying the current image being edited
 *
 * @param {Object} props - Component props
 * @param {Object} props.currentImage - Current image data
 * @param {string} props.currentImage.url - Image URL
 * @param {string} props.currentImage.alt - Image alt text
 * @param {string} props.currentImage.title - Image title
 * @param {string} props.currentImage.caption - Image caption
 * @param {boolean} props.isGenerating - Whether generation is in progress
 */
const AIImageEditingPreview = ({ currentImage, isGenerating }) => {
    if (!currentImage || !currentImage.url) {
        return null;
    }

    return (
        <div className="eb-ai-editing-preview">
            <div className="eb-ai-editing-image-container">
                <img
                    src={currentImage.url}
                    alt={currentImage.alt || __('Current image', 'essential-blocks')}
                    className="eb-ai-editing-image-display"
                />
                {isGenerating && (
                    <div className="eb-ai-loading-state">
                        <SpinLoader />
                    </div>
                )}
            </div>
        </div>
    );
};

export default AIImageEditingPreview;
