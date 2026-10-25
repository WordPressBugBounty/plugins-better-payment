/**
 * Demonstration of Global AI RichText Integration
 *
 * This file shows how the AI button automatically appears in ALL RichText toolbars
 * once the format type is registered globally.
 *
 * No HOC wrapping needed - the AI button will appear in every RichText component!
 */

import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

/**
 * Standard Notice Block - AI button automatically appears in RichText toolbars
 *
 * No special wrapping or configuration needed! The AI button will automatically
 * appear in both the title and text RichText fields.
 */
const StandardNoticeWithAI = ({ attributes, setAttributes, isSelected }) => {
    const {
        blockId,
        title,
        text,
        classHook,
    } = attributes;

    return (
        <>
            {isSelected && (
                <div style={{ marginBottom: '10px', padding: '8px', background: '#e8f5e8', borderRadius: '4px' }}>
                    <small style={{ color: '#2d5a2d' }}>
                        {__('✨ Global AI Integration - AI buttons automatically appear in ALL RichText toolbars!', 'essential-blocks')}
                    </small>
                </div>
            )}
            <div className={`eb-parent-wrapper eb-parent-${blockId} ${classHook}`}>
                <div className={`eb-notice-wrapper ${blockId}`} data-id={blockId}>
                    <div className="eb-notice-title-wrapper">
                        <RichText
                            className="eb-notice-title"
                            value={title}
                            onChange={(newTitle) => setAttributes({ title: newTitle })}
                            placeholder={__('Add Title - AI button available in toolbar...', 'essential-blocks')}
                        />
                    </div>
                    <span className="eb-notice-dismiss" />
                    <div>
                        <RichText
                            className="eb-notice-text"
                            value={text}
                            onChange={(newText) => setAttributes({ text: newText })}
                            placeholder={__('Add Text - AI button available in toolbar...', 'essential-blocks')}
                        />
                    </div>
                </div>
            </div>
        </>
    );
};

/**
 * Simple Text Block Example
 *
 * Shows how any RichText component automatically gets the AI button.
 */
const SimpleTextExample = ({ attributes, setAttributes }) => {
    const { text } = attributes;

    return (
        <div style={{ padding: '20px', border: '1px dashed #ccc', borderRadius: '4px' }}>
            <p style={{ margin: '0 0 10px 0', fontSize: '14px', color: '#666' }}>
                {__('Simple RichText with automatic AI button:', 'essential-blocks')}
            </p>
            <RichText
                tagName="p"
                value={text}
                onChange={(newText) => setAttributes({ text: newText })}
                placeholder={__('Type your content - AI button automatically available in toolbar...', 'essential-blocks')}
                style={{ minHeight: '50px', border: '1px solid #ddd', padding: '10px', borderRadius: '4px' }}
            />
        </div>
    );
};

/**
 * Multiple RichText Fields Example
 *
 * Shows how AI buttons automatically appear in multiple RichText fields.
 */
const MultipleRichTextExample = ({ attributes, setAttributes, isSelected }) => {
    const { title, subtitle, content } = attributes;

    return (
        <>
            {isSelected && (
                <div style={{ marginBottom: '10px', padding: '8px', background: '#fff3cd', borderRadius: '4px' }}>
                    <small style={{ color: '#856404' }}>
                        {__('📝 Multiple RichText Fields - Each gets its own AI button automatically!', 'essential-blocks')}
                    </small>
                </div>
            )}
            <div style={{ padding: '20px', border: '1px solid #ffc107', borderRadius: '4px' }}>
                <div style={{ marginBottom: '15px' }}>
                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
                        {__('Title:', 'essential-blocks')}
                    </label>
                    <RichText
                        tagName="h2"
                        value={title}
                        onChange={(newTitle) => setAttributes({ title: newTitle })}
                        placeholder={__('Enter title - AI button in toolbar...', 'essential-blocks')}
                        style={{ border: '1px solid #ddd', padding: '8px', borderRadius: '4px' }}
                    />
                </div>

                <div style={{ marginBottom: '15px' }}>
                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
                        {__('Subtitle:', 'essential-blocks')}
                    </label>
                    <RichText
                        tagName="h3"
                        value={subtitle}
                        onChange={(newSubtitle) => setAttributes({ subtitle: newSubtitle })}
                        placeholder={__('Enter subtitle - AI button in toolbar...', 'essential-blocks')}
                        style={{ border: '1px solid #ddd', padding: '8px', borderRadius: '4px' }}
                    />
                </div>

                <div>
                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
                        {__('Content:', 'essential-blocks')}
                    </label>
                    <RichText
                        tagName="div"
                        value={content}
                        onChange={(newContent) => setAttributes({ content: newContent })}
                        placeholder={__('Enter content - AI button in toolbar...', 'essential-blocks')}
                        multiline="p"
                        style={{ border: '1px solid #ddd', padding: '8px', borderRadius: '4px', minHeight: '100px' }}
                    />
                </div>
            </div>
        </>
    );
};

// Export all examples for testing and demonstration
export {
    StandardNoticeWithAI,
    SimpleTextExample,
    MultipleRichTextExample
};

// Default export for easy importing
export default {
    StandardNoticeWithAI,
    SimpleTextExample,
    MultipleRichTextExample
};

/**
 * Usage Instructions:
 *
 * 1. The AI format type is automatically registered when this module is imported
 * 2. AI buttons will appear in ALL RichText toolbars across the editor
 * 3. No special configuration or wrapping needed for individual components
 * 4. The AI button uses the same AIContentPopover as other Essential Blocks controls
 * 5. Generated content replaces the entire RichText content
 *
 * Requirements:
 * - OpenAI API key must be configured in Essential Blocks settings
 * - The initializeAIForRichText function is called automatically on import
 */
