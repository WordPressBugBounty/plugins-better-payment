/**
 * Example usage of GeneralTab component with custom onSelect callback and value prop
 * This demonstrates how to use the enhanced GeneralTab component
 */

import { useState } from '@wordpress/element';
import { GeneralTab } from './general';

// Example 1: Using GeneralTab with default attribute-based behavior (existing functionality)
const DefaultUsageExample = () => {
    return (
        <GeneralTab
            imageAttrProps={{
                imageId: 'myImageId',
                imageUrl: 'myImageUrl',
                imageAlt: 'myImageAlt',
                imageCaption: 'myImageCaption'
            }}
            attrPrefix="hero"
            label="Hero Image Settings"
        />
    );
};

// Example 2: Using GeneralTab with custom onSelect callback (new functionality)
const CustomOnSelectExample = () => {
    const handleCustomImageSelect = (media) => {
        // Custom logic for handling selected media

        // You can perform any custom operations here:
        // - Save to custom state
        // - Process the image data
        // - Call external APIs
        // - Update multiple attributes at once
        // - Apply custom transformations

        // Example: Custom processing
        const processedImageData = {
            id: media.id,
            url: media.url,
            alt: media.alt || `Custom alt text for ${media.filename}`,
            caption: media.caption || 'Auto-generated caption',
            customField: 'some custom value'
        };

        // Your custom logic here...
        // setCustomState(processedImageData);
        // callExternalAPI(processedImageData);
        // etc.
    };

    return (
        <GeneralTab
            label="Custom Image Handler"
            onSelect={handleCustomImageSelect}
        // Note: When onSelect is provided, imageAttrProps and attrPrefix
        // are not used for media selection, but other component features
        // like styles, captions, etc. still work normally
        />
    );
};

// Example 3: Using GeneralTab with both custom onSelect and value props (full control)
const FullControlExample = () => {
    const [selectedImageId, setSelectedImageId] = useState(null);
    const [imageData, setImageData] = useState(null);

    const handleCustomImageSelectWithValue = (media) => {
        // Custom logic with full control over both selection and value
        console.log('Selected media with full control:', media);

        // Update your custom state
        setSelectedImageId(media.id);
        setImageData({
            id: media.id,
            url: media.url,
            alt: media.alt,
            caption: media.caption,
            customProcessedData: 'processed by custom handler'
        });

        // You have complete control over what happens with the selected media
        // The MediaUpload component will use the selectedImageId for its value
    };

    return (
        <GeneralTab
            label="Full Control Image Settings"
            onSelect={handleCustomImageSelectWithValue}
            value={selectedImageId} // Custom value takes precedence
        // When both onSelect and value are provided:
        // - onSelect handles the selection callback
        // - value controls what MediaUpload considers "selected"
        />
    );
};

// Example 4: Conditional usage - switch between default and custom behavior
const ConditionalUsageExample = ({ useCustomHandler, customHandler }) => {
    return (
        <GeneralTab
            imageAttrProps={{
                imageId: 'conditionalImageId',
                imageUrl: 'conditionalImageUrl',
                imageAlt: 'conditionalImageAlt'
            }}
            attrPrefix="conditional"
            label="Conditional Image Settings"
            // Only pass onSelect when custom handling is needed
            onSelect={useCustomHandler ? customHandler : undefined}
        />
    );
};

// Example 5: External state management with custom value
const ExternalStateExample = () => {
    const [externalImageId, setExternalImageId] = useState(123); // From external state

    const handleExternalImageSelect = (media) => {
        // Update external state management system
        // dispatch({ type: 'UPDATE_IMAGE', payload: media });
        setExternalImageId(media.id);
    };

    return (
        <GeneralTab
            label="External State Management"
            onSelect={handleExternalImageSelect}
            value={externalImageId}
        // This allows integration with Redux, Zustand, or any external state system
        />
    );
};

export {
    DefaultUsageExample,
    CustomOnSelectExample,
    FullControlExample,
    ConditionalUsageExample,
    ExternalStateExample
};
