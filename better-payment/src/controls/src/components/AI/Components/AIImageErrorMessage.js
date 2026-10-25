/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

/**
 * AIImageErrorMessage - Component for displaying AI image generation errors
 *
 * @param {Object} props - Component props
 * @param {boolean} props.aiError - Whether there is an error
 * @param {string} props.aiErrorMessage - The error message to display
 */
const AIImageErrorMessage = ({ aiError, aiErrorMessage }) => {
    if (!aiError || !aiErrorMessage) {
        return null;
    }

    return (
        <div className="eb-ai-error-message">
            <div className="eb-ai-error-content">
                <Dashicon icon="warning" />
                <div className="eb-ai-error-text">
                    {aiErrorMessage.includes('Your organization must be verified to use the model `gpt-image-1`') ? (
                        <div>
                            <p><strong>{__('Organization Verification Required', 'essential-blocks')}</strong></p>
                            <p>{aiErrorMessage}</p>
                            <p>
                                <a
                                    href="https://platform.openai.com/settings/organization/general"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="eb-ai-error-link"
                                >
                                    {__('Verify Your Organization →', 'essential-blocks')}
                                </a>
                            </p>
                        </div>
                    ) : (
                        <p>{aiErrorMessage}</p>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AIImageErrorMessage;
