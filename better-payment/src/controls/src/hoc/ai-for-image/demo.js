/**
 * Demo file for AI Image HOC
 * 
 * This file demonstrates the AI Image button functionality
 * and can be used for testing purposes.
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Demo component showing AI Image button behavior
 */
const AIImageDemo = () => {
    const [demoImage, setDemoImage] = useState(null);
    const [isGenerating, setIsGenerating] = useState(false);

    /**
     * Simulate AI image generation
     */
    const simulateImageGeneration = () => {
        setIsGenerating(true);
        
        // Simulate API call delay
        setTimeout(() => {
            const mockImageData = {
                id: Math.floor(Math.random() * 1000),
                url: 'https://via.placeholder.com/400x300?text=AI+Generated+Image',
                alt: 'AI Generated demo image',
                caption: 'This is a demo AI generated image',
                title: 'Demo AI Image'
            };
            
            setDemoImage(mockImageData);
            setIsGenerating(false);
        }, 2000);
    };

    return (
        <div style={{ padding: '20px', border: '1px solid #ddd', margin: '20px' }}>
            <h3>{__('AI Image Generation Demo', 'essential-blocks')}</h3>
            
            <p>{__('This demo shows how the AI Image button works:', 'essential-blocks')}</p>
            
            <ol>
                <li>{__('Select an image block (essential-blocks/advanced-image or core/image)', 'essential-blocks')}</li>
                <li>{__('Look for the AI icon in the block toolbar', 'essential-blocks')}</li>
                <li>{__('Click the AI button to open the image generation popup', 'essential-blocks')}</li>
                <li>{__('Enter a prompt and generate an image', 'essential-blocks')}</li>
                <li>{__('Keep the image to add it to your block', 'essential-blocks')}</li>
            </ol>

            <div style={{ marginTop: '20px' }}>
                <button 
                    onClick={simulateImageGeneration}
                    disabled={isGenerating}
                    style={{
                        padding: '10px 20px',
                        backgroundColor: '#5632d1',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: isGenerating ? 'not-allowed' : 'pointer'
                    }}
                >
                    {isGenerating 
                        ? __('Generating...', 'essential-blocks')
                        : __('Demo: Generate AI Image', 'essential-blocks')
                    }
                </button>
            </div>

            {demoImage && (
                <div style={{ marginTop: '20px' }}>
                    <h4>{__('Generated Image:', 'essential-blocks')}</h4>
                    <img 
                        src={demoImage.url} 
                        alt={demoImage.alt}
                        style={{ maxWidth: '100%', height: 'auto' }}
                    />
                    <p><strong>{__('ID:', 'essential-blocks')}</strong> {demoImage.id}</p>
                    <p><strong>{__('Alt:', 'essential-blocks')}</strong> {demoImage.alt}</p>
                    <p><strong>{__('Caption:', 'essential-blocks')}</strong> {demoImage.caption}</p>
                </div>
            )}

            <div style={{ marginTop: '20px', padding: '10px', backgroundColor: '#f0f0f0' }}>
                <h4>{__('Technical Details:', 'essential-blocks')}</h4>
                <ul>
                    <li><strong>{__('Supported Blocks:', 'essential-blocks')}</strong> essential-blocks/advanced-image, core/image</li>
                    <li><strong>{__('Trigger:', 'essential-blocks')}</strong> {__('Block selection + AI enabled', 'essential-blocks')}</li>
                    <li><strong>{__('Component:', 'essential-blocks')}</strong> AIImagePopover</li>
                    <li><strong>{__('Filter:', 'essential-blocks')}</strong> editor.BlockEdit</li>
                </ul>
            </div>
        </div>
    );
};

export default AIImageDemo;
