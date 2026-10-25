/**
 * Example usage of withAIForRichText HOC
 *
 * This file demonstrates how to use the withAIForRichText Higher-Order Component
 * to add AI functionality to blocks that use RichText components.
 */

import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import withAIForRichText from '.';

/**
 * Example 1: Basic usage with default settings
 *
 * This will add AI functionality to generate paragraph content
 * and overwrite existing content by default.
 */
const BasicRichTextExample = withAIForRichText()(({ attributes, setAttributes }) => {
    const { text } = attributes;

    return (
        <div className="example-richtext-wrapper">
            <RichText
                tagName="p"
                value={text}
                onChange={(newText) => setAttributes({ text: newText })}
                placeholder={__('Type your content here or use AI to generate...', 'essential-blocks')}
            />
        </div>
    );
});

/**
 * Example 2: Custom configuration for title/heading content
 *
 * This example shows how to configure the HOC for different content types
 * and target different attributes.
 */
const TitleWithAIExample = withAIForRichText({
    contentType: 'text',           // Generate shorter text content
    overwriteContent: true,        // Replace existing content
    targetAttribute: 'title',      // Target the 'title' attribute instead of 'text'
    enableAi: true                 // Explicitly enable AI (default is true)
})(({ attributes, setAttributes }) => {
    const { title } = attributes;

    return (
        <div className="example-title-wrapper">
            <RichText
                tagName="h2"
                value={title}
                onChange={(newTitle) => setAttributes({ title: newTitle })}
                placeholder={__('Enter title or generate with AI...', 'essential-blocks')}
            />
        </div>
    );
});

/**
 * Example 3: Append mode for description content
 *
 * This example shows how to append AI-generated content to existing content
 * instead of overwriting it.
 */
const DescriptionWithAIExample = withAIForRichText({
    contentType: 'paragraph',      // Generate paragraph content
    overwriteContent: false,       // Append to existing content
    targetAttribute: 'description' // Target the 'description' attribute
})(({ attributes, setAttributes }) => {
    const { description } = attributes;

    return (
        <div className="example-description-wrapper">
            <RichText
                tagName="div"
                value={description}
                onChange={(newDescription) => setAttributes({ description: newDescription })}
                placeholder={__('Enter description or add AI content...', 'essential-blocks')}
                multiline="p"
            />
        </div>
    );
});

/**
 * Example 4: Complete block edit component with multiple RichText fields
 *
 * This shows how to use multiple HOCs for different RichText fields
 * in the same block.
 */
const CompleteBlockExample = ({ attributes, setAttributes, isSelected }) => {
    const { title, subtitle, content } = attributes;

    // Create HOC-wrapped components for different fields
    const TitleWithAI = withAIForRichText({
        contentType: 'text',
        targetAttribute: 'title'
    })(({ attributes, setAttributes }) => (
        <RichText
            tagName="h2"
            className="block-title"
            value={attributes.title}
            onChange={(newTitle) => setAttributes({ title: newTitle })}
            placeholder={__('Block title...', 'essential-blocks')}
        />
    ));

    const SubtitleWithAI = withAIForRichText({
        contentType: 'text',
        targetAttribute: 'subtitle'
    })(({ attributes, setAttributes }) => (
        <RichText
            tagName="h3"
            className="block-subtitle"
            value={attributes.subtitle}
            onChange={(newSubtitle) => setAttributes({ subtitle: newSubtitle })}
            placeholder={__('Block subtitle...', 'essential-blocks')}
        />
    ));

    const ContentWithAI = withAIForRichText({
        contentType: 'paragraph',
        targetAttribute: 'content'
    })(({ attributes, setAttributes }) => (
        <RichText
            tagName="div"
            className="block-content"
            value={attributes.content}
            onChange={(newContent) => setAttributes({ content: newContent })}
            placeholder={__('Block content...', 'essential-blocks')}
            multiline="p"
        />
    ));

    return (
        <div className="complete-block-example">
            {isSelected && (
                <>
                    <TitleWithAI attributes={attributes} setAttributes={setAttributes} />
                    <SubtitleWithAI attributes={attributes} setAttributes={setAttributes} />
                    <ContentWithAI attributes={attributes} setAttributes={setAttributes} />
                </>
            )}

            {!isSelected && (
                <div className="block-preview">
                    <RichText.Content tagName="h2" value={title} />
                    <RichText.Content tagName="h3" value={subtitle} />
                    <RichText.Content tagName="div" value={content} />
                </div>
            )}
        </div>
    );
};

/**
 * Example 5: Conditional AI enabling
 *
 * This shows how to conditionally enable/disable AI based on props or settings.
 */
const ConditionalAIExample = ({ attributes, setAttributes, enableAI = true }) => {
    const ConditionalRichText = withAIForRichText({
        enableAi: enableAI,  // Pass the conditional value
        contentType: 'paragraph',
        targetAttribute: 'text'
    })(({ attributes, setAttributes }) => (
        <RichText
            tagName="p"
            value={attributes.text}
            onChange={(newText) => setAttributes({ text: newText })}
            placeholder={
                enableAI
                    ? __('Type content or use AI...', 'essential-blocks')
                    : __('Type your content...', 'essential-blocks')
            }
        />
    ));

    return (
        <div className="conditional-ai-example">
            <ConditionalRichText attributes={attributes} setAttributes={setAttributes} />
        </div>
    );
};

// Export examples for documentation and testing
export {
    BasicRichTextExample,
    TitleWithAIExample,
    DescriptionWithAIExample,
    CompleteBlockExample,
    ConditionalAIExample
};

export default withAIForRichText;
