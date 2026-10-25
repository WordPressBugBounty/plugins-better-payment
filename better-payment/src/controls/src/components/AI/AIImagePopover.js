/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Modal, Button, Dashicon } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

import EBLogo from '../../icons/eb-logo';
import AIImageErrorMessage from './Components/AIImageErrorMessage';
import AIImageEditingControls from './Components/AIImageEditingControls';
import AIImageEditingPreview from './Components/AIImageEditingPreview';
import AIImageGenerationForm from './Components/AIImageGenerationForm';
import AIImageGeneratedPreview from './Components/AIImageGeneratedPreview';

/**
 * AIImagePopover - A component for AI image generation
 *
 * @param {Object} props - Component props
 * @param {boolean} props.isVisible - Whether the popover is visible
 * @param {Function} props.onClose - Function to close the popover
 * @param {Object} props.anchor - Anchor element for the popover
 * @param {Function} props.onImageGenerated - Callback when image is generated and kept
 * @param {Object} props.value - Current image data (URL, ID, or image object) to display as context
 */
const AIImagePopover = ({
    isVisible,
    onClose,
    anchor,
    onImageGenerated,
    value
}) => {
    // AI image generation states
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiModel, setAiModel] = useState('auto'); // New manual model selection
    const [aiImageCount, setAiImageCount] = useState(2); // New number of images control
    const [aiImageSize, setAiImageSize] = useState('1024x1024');
    const [aiImageQuality, setAiImageQuality] = useState('auto');
    const [aiImageStyle, setAiImageStyle] = useState('none');
    const [aiBackground, setAiBackground] = useState('auto');
    const [aiOutputFormat, setAiOutputFormat] = useState('auto');
    const [aiOutputCompression, setAiOutputCompression] = useState('standard');
    const [isGenerating, setIsGenerating] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [aiError, setAiError] = useState(false);
    const [aiErrorMessage, setAiErrorMessage] = useState('');
    const [generatedImages, setGeneratedImages] = useState([]);
    const [hasGenerated, setHasGenerated] = useState(false);
    const [usageInfo, setUsageInfo] = useState(null); // New state for usage information

    // New categorization states
    const [imageCategory, setImageCategory] = useState('Photographic');
    const [imageType, setImageType] = useState('Landscape');

    // Image editing mode states
    const [isEditingMode, setIsEditingMode] = useState(false);
    const [userPreferredMode, setUserPreferredMode] = useState(null); // null = auto-detect, 'editing' = user chose editing, 'generation' = user chose generation
    const [selectedEditingOption, setSelectedEditingOption] = useState(null); // null = show main grid, 'variations'|'remove-background'|'replace-background'|'isolate-object' = show specific option
    const [editingPrompt, setEditingPrompt] = useState('');
    const [editingImageCount, setEditingImageCount] = useState(2);
    const [backgroundDescription, setBackgroundDescription] = useState('');
    const [isolateBackgroundColor, setIsolateBackgroundColor] = useState('#ffffff');

    // Image state management for editing workflow
    const [originalImage, setOriginalImage] = useState(null); // The original image before any AI editing
    const [referenceImage, setReferenceImage] = useState(null); // The image to use as reference for AI editing (can be original or previously generated)
    const [hasNewlyGeneratedImage, setHasNewlyGeneratedImage] = useState(false); // Whether there's a newly generated image that hasn't been processed

    /**
     * Get current image data for display
     */
    const getCurrentImageData = () => {
        if (!value) return null;

        // Handle different value formats
        if (typeof value === 'string') {
            // If value is just a URL string
            return { url: value };
        }

        if (typeof value === 'object') {
            // If value is an object with image data
            return {
                id: value.id || value.imageId,
                url: value.url || value.imageUrl,
                alt: value.alt || value.imageAlt || '',
                caption: value.caption || value.imageCaption || '',
                title: value.title || value.imageTitle || ''
            };
        }

        return null;
    };

    const currentImage = getCurrentImageData();

    // Initialize original and reference images when component mounts or currentImage changes
    useEffect(() => {
        if (currentImage && currentImage.url) {
            // Set original image only if it hasn't been set yet
            if (!originalImage) {
                setOriginalImage(currentImage);
            }
            // Set reference image to current image (this will be the image used for AI editing)
            setReferenceImage(currentImage);
        }
    }, [currentImage, originalImage]);

    // Auto-switch to editing mode when current image exists, but respect user preference
    useEffect(() => {
        if (currentImage && currentImage.url && !hasGenerated) {
            // Only auto-switch to editing mode if user hasn't explicitly chosen generation mode
            if (userPreferredMode === null || userPreferredMode === 'editing') {
                setIsEditingMode(true);
            } else if (userPreferredMode === 'generation') {
                setIsEditingMode(false);
            }
        }
    }, [currentImage, hasGenerated, userPreferredMode]);

    /**
     * Get image categories and their corresponding types
     */
    const getImageCategories = () => {
        return {
            'Photographic': ['Landscape', 'Macro', 'Portrait', 'Long Exposure'],
            'Vector': ['Typographic Logo', 'Shape Logo', 'Handwritten Logo'],
            'Background': ['Gradient', 'Pattern', 'Texture', 'Solid Color'],
            'Handmade': ['Watercolor', 'Oil Painting', 'Sketch', 'Charcoal'],
            'Digital Art': ['3D Render', 'Pixel Art', 'Digital Painting', 'Abstract'],
            'Product': ['Studio Shot', 'Lifestyle', 'Flat Lay', 'Close-up'],
            '3D': ['Realistic', 'Stylized', 'Low Poly', 'Architectural']
        };
    };

    /**
     * Get prompt fragments for each type
     */
    const getPromptFragments = () => {
        return {
            // Photographic
            'Landscape': 'professional landscape photography, wide angle shot, natural lighting, high resolution, detailed scenery',
            'Macro': 'macro photography, extreme close-up, shallow depth of field, fine details, professional lighting',
            'Portrait': 'portrait photography, professional headshot, studio lighting, sharp focus, high quality',
            'Long Exposure': 'long exposure photography, motion blur effects, smooth water or clouds, dramatic lighting',

            // Vector
            'Typographic Logo': 'clean typographic logo design, modern typography, vector graphics, minimalist style, professional branding',
            'Shape Logo': 'geometric logo design, simple shapes, vector graphics, clean lines, scalable design',
            'Handwritten Logo': 'handwritten logo design, custom lettering, artistic typography, unique style, brand identity',

            // Background
            'Gradient': 'smooth gradient background, color transition, modern design, clean aesthetic',
            'Pattern': 'seamless pattern background, repeating design, decorative elements, consistent style',
            'Texture': 'textured background, surface detail, material texture, high resolution',
            'Solid Color': 'solid color background, clean and simple, uniform color, professional look',

            // Handmade
            'Watercolor': 'watercolor painting style, soft brushstrokes, flowing colors, artistic texture, hand-painted look',
            'Oil Painting': 'oil painting technique, rich colors, textured brushstrokes, classical art style',
            'Sketch': 'pencil sketch style, hand-drawn lines, artistic shading, detailed illustration',
            'Charcoal': 'charcoal drawing style, dramatic contrast, artistic shading, monochromatic tones',

            // Digital Art
            '3D Render': '3D rendered artwork, realistic lighting, detailed modeling, high quality render',
            'Pixel Art': 'pixel art style, retro gaming aesthetic, blocky design, limited color palette',
            'Digital Painting': 'digital painting artwork, artistic brushwork, vibrant colors, detailed illustration',
            'Abstract': 'abstract digital art, creative composition, unique design, artistic expression',

            // Product
            'Studio Shot': 'professional product photography, studio lighting, clean background, commercial quality',
            'Lifestyle': 'lifestyle product photography, natural setting, contextual use, authentic feel',
            'Flat Lay': 'flat lay product photography, top-down view, organized layout, clean composition',
            'Close-up': 'product close-up photography, detailed view, macro lens, fine details visible',

            // 3D
            'Realistic': 'realistic 3D rendering, photorealistic quality, detailed textures, professional lighting',
            'Stylized': 'stylized 3D art, artistic interpretation, creative design, unique aesthetic',
            'Low Poly': 'low poly 3D design, geometric shapes, minimalist style, clean edges',
            'Architectural': '3D architectural visualization, building design, structural details, professional rendering'
        };
    };

    /**
     * Get available models for manual selection
     */
    const getAvailableModels = () => {
        return [
            { label: __('Auto Detection', 'essential-blocks'), value: 'auto' },
            { label: __('DALL-E 2', 'essential-blocks'), value: 'dall-e-2' },
            { label: __('DALL-E 3', 'essential-blocks'), value: 'dall-e-3' },
            { label: __('GPT-Image-1', 'essential-blocks'), value: 'gpt-image-1' }
        ];
    };

    /**
     * Get available image count options based on selected model
     */
    const getAvailableImageCounts = () => {
        const selectedModel = aiModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : aiModel;

        // DALL-E 3 only supports 1 image per request
        if (selectedModel === 'dall-e-3') {
            return [{ label: __('1', 'essential-blocks'), value: 1 }];
        }

        // Other models support 1-10 images
        return Array.from({ length: 10 }, (_, i) => ({
            label: __(String(i + 1), 'essential-blocks'),
            value: i + 1
        }));
    };

    /**
     * Get available image sizes based on selected model
     */
    const getAvailableSizes = () => {
        const selectedModel = aiModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : aiModel;

        if (selectedModel === 'dall-e-2') {
            return [
                { label: __('256x256', 'essential-blocks'), value: '256x256' },
                { label: __('512x512', 'essential-blocks'), value: '512x512' },
                { label: __('1024x1024 (Square)', 'essential-blocks'), value: '1024x1024' }
            ];
        } else if (selectedModel === 'dall-e-3') {
            return [
                { label: __('1024x1024 (Square)', 'essential-blocks'), value: '1024x1024' },
                { label: __('1792x1024 (Wide Landscape)', 'essential-blocks'), value: '1792x1024' },
                { label: __('1024x1792 (Tall Portrait)', 'essential-blocks'), value: '1024x1792' }
            ];
        } else if (selectedModel === 'gpt-image-1') {
            return [
                { label: __('1024x1024 (Square)', 'essential-blocks'), value: '1024x1024' },
                { label: __('1536x1024 (Landscape)', 'essential-blocks'), value: '1536x1024' },
                { label: __('1024x1536 (Portrait)', 'essential-blocks'), value: '1024x1536' },
                { label: __('Auto (Default)', 'essential-blocks'), value: 'auto' }
            ];
        }

        // Auto mode - show all sizes
        return [
            // DALL-E 2 sizes
            { label: __('256x256', 'essential-blocks'), value: '256x256' },
            { label: __('512x512', 'essential-blocks'), value: '512x512' },
            // Common size (all models)
            { label: __('1024x1024 (Square)', 'essential-blocks'), value: '1024x1024' },
            // GPT-Image-1 specific sizes
            { label: __('1536x1024 (Landscape)', 'essential-blocks'), value: '1536x1024' },
            { label: __('1024x1536 (Portrait)', 'essential-blocks'), value: '1024x1536' },
            { label: __('Auto (Default)', 'essential-blocks'), value: 'auto' },
            // DALL-E 3 specific sizes
            { label: __('1792x1024 (Wide Landscape)', 'essential-blocks'), value: '1792x1024' },
            { label: __('1024x1792 (Tall Portrait)', 'essential-blocks'), value: '1024x1792' }
        ];
    };

    /**
     * Check if a control should be visible based on selected model
     */
    const isControlVisible = (controlType) => {
        const selectedModel = aiModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : aiModel;

        switch (controlType) {
            case 'imageStyle':
                return selectedModel === 'dall-e-3' || aiModel === 'auto';
            case 'outputFormat':
            case 'outputCompression':
            case 'background':
                return selectedModel === 'gpt-image-1' || aiModel === 'auto';
            case 'imageQuality':
                return selectedModel !== 'dall-e-2' || aiModel === 'auto';
            default:
                return true;
        }
    };

    /**
     * Automatically determine model based on size, background, style, and other parameters
     */
    const getModelForSize = (size, background = aiBackground, style = aiImageStyle, outputFormat = aiOutputFormat, outputCompression = aiOutputCompression) => {
        // Priority 1: If background is not 'auto', must use gpt-image-1
        if (background && background !== 'auto') {
            return 'gpt-image-1';
        }

        // Priority 2: If output_format or output_compression is not default, must use gpt-image-1
        if ((outputFormat && outputFormat !== 'png') || (outputCompression && outputCompression !== 'standard')) {
            return 'gpt-image-1';
        }

        // Priority 3: If style is 'vivid' or 'natural', must use dall-e-3
        if (style && (style === 'vivid' || style === 'natural')) {
            return 'dall-e-3';
        }

        // Priority 4: Size-based selection
        const dalleE2Sizes = ['256x256', '512x512'];
        const dalleE3Sizes = ['1792x1024', '1024x1792'];
        const gptImage1Sizes = ['1536x1024', '1024x1536', 'auto'];

        if (dalleE3Sizes.includes(size)) {
            return 'dall-e-3';
        }

        if (gptImage1Sizes.includes(size)) {
            return 'gpt-image-1';
        }

        if (dalleE2Sizes.includes(size)) {
            return 'dall-e-2';
        }

        // For 1024x1024, default to dall-e-3
        return 'dall-e-3';
    };

    /**
     * Handle AI generation for editing features
     */
    const handleEditingGeneration = (editingType) => {
        let prompt = '';
        let imageCount = 1;
        let editType = 'edit'; // Default to edit

        // Generate prompts based on editing type
        switch (editingType) {
            case 'variations':
                prompt = `Create variations of this image`;
                if (editingPrompt.trim()) {
                    prompt += ` with the following modifications: ${editingPrompt.trim()}`;
                }
                imageCount = editingImageCount;
                editType = 'variation'; // Use variation endpoint for variations
                break;

            case 'remove-background':
                prompt = 'Remove background from this image, make it transparent';
                break;

            case 'replace-background':
                if (!backgroundDescription.trim()) {
                    alert(__("Please describe your desired background", "essential-blocks"));
                    return;
                }
                prompt = `Replace the background of this image with: ${backgroundDescription.trim()}`;
                break;

            case 'isolate-object':
                prompt = `Remove the background from this image, add a solid ${isolateBackgroundColor} background, center the main object, and add a subtle drop shadow for a professional look`;
                break;

            default:
                return;
        }

        // Use the existing generateAIImage function with editing-specific parameters
        generateAIImageWithPrompt(prompt, imageCount, true, editType);
    };

    /**
     * Generate AI image with custom prompt (for editing features)
     */
    const generateAIImageWithPrompt = (customPrompt, customImageCount = 1, isEditingMode = false, editType = 'edit') => {
        if (!customPrompt.trim()) {
            alert(__("Please enter a prompt", "essential-blocks"));
            return;
        }

        setIsGenerating(true);
        setAiError(false);
        setAiErrorMessage('');

        // For editing mode, we'll use a different approach - send the current image as reference
        // and use the custom prompt for modifications
        let fullPrompt = customPrompt;

        // Determine model - use automatic selection for editing
        const selectedModel = getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression);

        // Start AI job for image generation
        let data = new FormData();
        data.append("action", "start_ai_job");
        data.append("admin_nonce", window.EssentialBlocksLocalize?.admin_nonce || '');
        data.append("job_type", "image");
        data.append("prompt", fullPrompt);
        data.append("model", selectedModel);
        data.append("size", aiImageSize);
        data.append("image_count", customImageCount);

        // Add reference image for editing operations
        if (isEditingMode && referenceImage && referenceImage.url) {
            data.append("reference_image_url", referenceImage.url);
            data.append("editing_mode", "true");
            data.append("edit_type", editType);
        }

        // Only append quality if not 'auto'
        if (aiImageQuality !== 'auto') {
            data.append("quality", aiImageQuality);
        }

        data.append("style", aiImageStyle);
        data.append("background", aiBackground);

        // Only append output_format if not 'auto'
        if (aiOutputFormat !== 'auto') {
            data.append("output_format", aiOutputFormat);
        }

        data.append("output_compression", aiOutputCompression);

        fetch(window.EssentialBlocksLocalize?.ajax_url || '/wp-admin/admin-ajax.php', {
            method: "POST",
            body: data,
        })
            .then((res) => res.json())
            .then((response) => {
                if (response.success) {
                    // Start polling for job completion
                    pollImageJobStatus(response.data.job_id);
                } else {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("Error starting AI image job:", response.data?.message);
                    const defaultMessage = __("Error starting image generation. Please try again.", "essential-blocks");
                    setAiErrorMessage(response.data?.message || defaultMessage);
                }
            })
            .catch((error) => {
                setIsGenerating(false);
                setAiError(true);
                console.error("AJAX Error:", error);
                setAiErrorMessage(__("Error connecting to the server. Please try again.", "essential-blocks"));
            });
    };

    /**
     * Generate AI image
     */
    const generateAIImage = (isRegeneration = false) => {
        if (!aiPrompt.trim()) {
            alert(__("Please enter a prompt", "essential-blocks"));
            return;
        }

        if (aiPrompt.length > 3000) {
            alert(__("Prompt must be 3000 characters or less", "essential-blocks"));
            return;
        }

        setIsGenerating(true);
        setAiError(false);
        setAiErrorMessage('');

        let fullPrompt = aiPrompt;

        // Enhance prompt with category and type fragments
        const promptFragments = getPromptFragments();
        const categoryFragment = promptFragments[imageType];
        if (categoryFragment) {
            fullPrompt = `${categoryFragment}, ${aiPrompt}`;
        }

        // Add regeneration instruction if this is a regeneration
        if (isRegeneration && generatedImages.length > 0) {
            fullPrompt += ` IMPORTANT: Please generate different images than the previous results. Create something unique and different while following the same requirements.`;
        }
        // console.log('Full Prompt: ', fullPrompt);

        // Determine model - use manual selection if not 'auto', otherwise use automatic selection
        const selectedModel = aiModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : aiModel;

        // Start AI job for image generation
        let data = new FormData();
        data.append("action", "start_ai_job");
        data.append("admin_nonce", window.EssentialBlocksLocalize?.admin_nonce || '');
        data.append("job_type", "image");
        data.append("prompt", fullPrompt);
        data.append("model", selectedModel);
        data.append("size", aiImageSize);
        data.append("image_count", aiImageCount);

        // Only append quality if not 'auto'
        if (aiImageQuality !== 'auto') {
            data.append("quality", aiImageQuality);
        }

        data.append("style", aiImageStyle);
        data.append("background", aiBackground);

        // Only append output_format if not 'auto'
        if (aiOutputFormat !== 'auto') {
            data.append("output_format", aiOutputFormat);
        }

        data.append("output_compression", aiOutputCompression);

        fetch(window.EssentialBlocksLocalize?.ajax_url || '/wp-admin/admin-ajax.php', {
            method: "POST",
            body: data,
        })
            .then((res) => res.json())
            .then((response) => {
                if (response.success) {
                    // Start polling for job completion
                    pollImageJobStatus(response.data.job_id);
                } else {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("Error starting AI image job:", response.data?.message);
                    const defaultMessage = __("Error starting image generation. Please try again.", "essential-blocks");
                    setAiErrorMessage(response.data?.message || defaultMessage);
                }
            })
            .catch((error) => {
                setIsGenerating(false);
                setAiError(true);
                console.error("AJAX Error:", error);
                setAiErrorMessage(__("Error connecting to the server. Please try again.", "essential-blocks"));
            });
    };

    /**
     * Poll image job status until completion
     */
    const pollImageJobStatus = (jobId) => {
        const maxPollingTime = 5 * 60 * 1000; // 5 minutes
        const pollingInterval = 5000; // 5 seconds (longer for image generation)
        const startTime = Date.now();

        const poll = () => {
            // Check if we've exceeded the maximum polling time
            if (Date.now() - startTime > maxPollingTime) {
                setIsGenerating(false);
                setAiError(true);
                setAiErrorMessage(__("Image generation timed out. Please try again.", "essential-blocks"));
                return;
            }

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
                                // Store the generated images (expecting array of images)
                                const images = Array.isArray(response.data.result.images) ? response.data.result.images : [response.data.result];
                                setGeneratedImages(images.map((img, index) => ({
                                    id: index,
                                    url: img.image_url || img.url,
                                    b64_json: img.image_b64 || img.b64_json,
                                    title: img.title || '',
                                    alt_tag: img.alt_tag || '',
                                    caption: img.caption || '',
                                    description: img.description || ''
                                })));

                                // Store usage information if available
                                if (response.data.result.usage) {
                                    setUsageInfo(response.data.result.usage);
                                }

                                // Clear any previous errors
                                setAiError(false);
                                setAiErrorMessage('');

                                setHasGenerated(true);
                                // Set flag for newly generated image (for editing mode confirmation)
                                if (isEditingMode) {
                                    setHasNewlyGeneratedImage(true);
                                }
                            } else {
                                setAiError(true);
                                const errorMessage = response.data.result?.message || __("Unknown error occurred", "essential-blocks");
                                console.error("Error in AI image generation result:", errorMessage);
                                if (typeof errorMessage === 'string') {
                                    setAiErrorMessage(errorMessage);
                                } else {
                                    setAiErrorMessage(__("Error generating image. Please try again.", "essential-blocks"));
                                }
                            }
                        } else if (status === 'failed' || status === 'expired') {
                            setIsGenerating(false);
                            setAiError(true);
                            const errorMessage = response.data.error || __("Image generation failed", "essential-blocks");
                            console.error("AI image job failed:", errorMessage);
                            if (typeof errorMessage === 'string') {
                                setAiErrorMessage(errorMessage);
                            } else {
                                setAiErrorMessage(__("Error generating image. Please try again.", "essential-blocks"));
                            }
                        } else if (status === 'pending' || status === 'processing') {
                            // Continue polling
                            setTimeout(poll, pollingInterval);
                        }
                    } else {
                        setIsGenerating(false);
                        setAiError(true);
                        console.error("Error checking image job status:", response.data?.message);
                        setAiErrorMessage(__("Error checking generation status. Please try again.", "essential-blocks"));
                    }
                })
                .catch((error) => {
                    setIsGenerating(false);
                    setAiError(true);
                    console.error("AJAX Error during image polling:", error);
                    setAiErrorMessage(__("Error connecting to the server. Please try again.", "essential-blocks"));
                });
        };

        // Start polling
        poll();
    };

    /**
     * Handle keeping the generated image
     */
    const handleKeepImage = (image, closePopover = true) => {
        if (!image || (!image.url && !image.b64_json)) return;

        setIsSaving(true);

        // AJAX call to download and save image to media library
        let data = new FormData();
        data.append("action", "save_ai_generated_image");
        data.append("admin_nonce", window.EssentialBlocksLocalize?.admin_nonce || '');

        // Handle both URL and base64 formats
        if (image.url) {
            data.append("image_url", image.url);
        } else if (image.b64_json) {
            data.append("image_b64", image.b64_json);
        }

        data.append("prompt", aiPrompt);
        data.append("title", image.title || '');
        data.append("alt_tag", image.alt_tag || '');
        data.append("caption", image.caption || '');
        data.append("description", image.description || '');

        fetch(window.EssentialBlocksLocalize?.ajax_url || '/wp-admin/admin-ajax.php', {
            method: "POST",
            body: data,
        })
            .then((res) => res.json())
            .then((response) => {
                setIsSaving(false);

                if (response.success) {
                    // Clean the form after successful save if closing popover
                    if (closePopover) {
                        // Pass the WordPress media data to the parent component
                        onImageGenerated({
                            id: response.data.attachment_id,
                            url: response.data.url,
                            alt: response.data.alt,
                            caption: response.data.caption || '',
                            title: response.data.title || '',
                            description: response.data.description || ''
                        });

                        setAiPrompt('');
                        setGeneratedImages([]);
                        setHasGenerated(false);
                        setAiError(false);
                    }
                } else {
                    setAiError(true);
                    const errorMessage = response.data?.message || '';
                    console.error("Error saving image:", errorMessage);
                    setAiErrorMessage(__("Error saving image to media library. Please try again.", "essential-blocks"));
                    // alert(__("Error saving image to media library. Please try again.", "essential-blocks"));
                }
            })
            .catch((error) => {
                setIsSaving(false);
                setAiError(true);
                console.error("AJAX Error:", error);
                setAiErrorMessage(__("Error connecting to the server. Please try again.", "essential-blocks"));
                // alert(__("Error connecting to the server. Please try again.", "essential-blocks"));
            });
    };

    /**
     * Handle category change and update type options
     */
    const handleCategoryChange = (newCategory) => {
        setImageCategory(newCategory);
        const categories = getImageCategories();
        const availableTypes = categories[newCategory] || [];
        if (availableTypes.length > 0) {
            setImageType(availableTypes[0]); // Set first type as default
        }
    };

    /**
     * Handle model change and adjust image count if necessary
     */
    const handleModelChange = (newModel) => {
        setAiModel(newModel);

        // If switching to DALL-E 3 (directly or through auto selection), limit to 1 image
        const selectedModel = newModel === 'auto' ? getModelForSize(aiImageSize, aiBackground, aiImageStyle, aiOutputFormat, aiOutputCompression) : newModel;
        if (selectedModel === 'dall-e-3' && aiImageCount > 1) {
            setAiImageCount(1);
        }
    };

    /**
     * Handle generating image again
     */
    const handleGenerateAgain = () => {
        generateAIImage(true); // Pass true to indicate this is a regeneration
    };

    /**
     * Handle prompt change and clear error if user starts typing
     */
    const handlePromptChange = (newPrompt) => {
        setAiPrompt(newPrompt);
        // Clear error message when user starts typing a new prompt
        if (aiError && aiErrorMessage) {
            setAiError(false);
            setAiErrorMessage('');
        }
    };

    /**
     * Handle popover close
     */
    const handleClose = () => {
        setAiError(false);
        setAiErrorMessage('');
        setGeneratedImages([]);
        setHasGenerated(false);
        setUsageInfo(null); // Reset usage information
        setAiPrompt('');
        setIsGenerating(false);
        setIsSaving(false);
        setAiModel('auto'); // Reset model selection
        setAiImageCount(2); // Reset image count
        setAiImageSize('1024x1024');
        setAiImageQuality('auto');
        setAiImageStyle('none');
        setAiBackground('auto');
        setAiOutputFormat('auto');
        setAiOutputCompression('standard');
        setImageCategory('Photographic');
        setImageType('Landscape');

        // Reset editing states
        setIsEditingMode(false);
        setUserPreferredMode(null); // Reset user preference so auto-detection works again
        setSelectedEditingOption(null);
        setEditingPrompt('');
        setEditingImageCount(2);
        setBackgroundDescription('');
        setIsolateBackgroundColor('#ffffff');

        // Reset image state management
        setOriginalImage(null);
        setReferenceImage(null);
        setHasNewlyGeneratedImage(false);

        onClose();
    };

    /**
     * Handle discarding generated image - revert to original image
     */
    const handleDiscardGeneratedImage = () => {
        if (originalImage) {
            // Revert to original image
            setReferenceImage(originalImage);
            // Clear generated images
            setGeneratedImages([]);
            setHasGenerated(false);
        }
        setHasNewlyGeneratedImage(false);
    };

    /**
     * Handle keeping generated image - use it as new reference
     */
    const handleKeepGeneratedImage = () => {
        if (generatedImages.length > 0) {
            // Use the first generated image as the new reference image
            const newReferenceImage = {
                id: generatedImages[0].id,
                url: generatedImages[0].url || (generatedImages[0].b64_json ? `data:image/png;base64,${generatedImages[0].b64_json}` : ''),
                alt: generatedImages[0].alt_tag || '',
                caption: generatedImages[0].caption || '',
                title: generatedImages[0].title || ''
            };
            setReferenceImage(newReferenceImage);
        }
        setHasNewlyGeneratedImage(false);
    };

    if (!isVisible) {
        return null;
    }

    return (
        <>
            <Modal
                title={__('AI Image Generator', 'essential-blocks')}
                icon={<EBLogo />}
                size={'large'}
                shouldCloseOnClickOutside={isGenerating || isSaving || hasGenerated ? false : true}
                onRequestClose={() => {
                    handleClose();
                }}
                className="eb-controls-ai-popover eb-controls-ai-image-popover"
            >
                {window.EssentialBlocksLocalize?.hasOpenAiApiKey !== "1" && (
                    <div className="eb-ai-api-key-warning">
                        <p>
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

                {/* Error Message Display */}
                <AIImageErrorMessage aiError={aiError} aiErrorMessage={aiErrorMessage} />

                {/* Conditional Content: Show editing interface when current image exists and editing mode is active */}
                {currentImage && currentImage.url && isEditingMode ? (
                    <div className="eb-ai-image-editing-content">
                        <div className="eb-ai-editing-header">
                            <h3>{__('Edit Image', 'essential-blocks')}</h3>
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setIsEditingMode(false);
                                    setUserPreferredMode('generation');
                                    setSelectedEditingOption(null); // Reset to main options grid when switching modes
                                }}
                                className="eb-ai-switch-screen-button"
                            >
                                <Dashicon icon="arrow-left-alt2" />
                                {__(' Go Back to Generate New', 'essential-blocks')}
                            </Button>
                        </div>


                        {/* Left Column: Editing Controls */}
                        <div className="eb-ai-editing-layout">
                            {/* Left Column: Editing Controls */}
                            <AIImageEditingControls
                                selectedEditingOption={selectedEditingOption}
                                setSelectedEditingOption={setSelectedEditingOption}
                                editingPrompt={editingPrompt}
                                setEditingPrompt={setEditingPrompt}
                                editingImageCount={editingImageCount}
                                setEditingImageCount={setEditingImageCount}
                                backgroundDescription={backgroundDescription}
                                setBackgroundDescription={setBackgroundDescription}
                                isolateBackgroundColor={isolateBackgroundColor}
                                setIsolateBackgroundColor={setIsolateBackgroundColor}
                                handleEditingGeneration={handleEditingGeneration}
                                isGenerating={isGenerating}
                                isSaving={isSaving}
                                hasGeneratedImage={hasNewlyGeneratedImage}
                                onDiscardGeneratedImage={handleDiscardGeneratedImage}
                                onKeepGeneratedImage={handleKeepGeneratedImage}
                            />

                            {/* Right Column: Image Preview */}
                            {generatedImages.length > 0 ? (
                                <AIImageGeneratedPreview
                                    currentImage={currentImage}
                                    isGenerating={isGenerating}
                                    isSaving={isSaving}
                                    hasGenerated={hasGenerated}
                                    generatedImages={generatedImages}
                                    usageInfo={usageInfo}
                                    handleKeepImage={handleKeepImage}
                                    setIsEditingMode={setIsEditingMode}
                                    setUserPreferredMode={setUserPreferredMode}
                                    isEditingMode={isEditingMode}
                                />
                            ) : (
                                <AIImageEditingPreview
                                    currentImage={currentImage}
                                    isGenerating={isGenerating}
                                />
                            )}
                        </div>
                    </div>
                ) : (
                    <div className="eb-ai-popover-content">
                        <AIImageGenerationForm
                            aiPrompt={aiPrompt}
                            handlePromptChange={handlePromptChange}
                            aiModel={aiModel}
                            handleModelChange={handleModelChange}
                            aiImageCount={aiImageCount}
                            setAiImageCount={setAiImageCount}
                            imageCategory={imageCategory}
                            handleCategoryChange={handleCategoryChange}
                            imageType={imageType}
                            setImageType={setImageType}
                            aiImageSize={aiImageSize}
                            setAiImageSize={setAiImageSize}
                            aiImageQuality={aiImageQuality}
                            setAiImageQuality={setAiImageQuality}
                            aiImageStyle={aiImageStyle}
                            setAiImageStyle={setAiImageStyle}
                            aiOutputFormat={aiOutputFormat}
                            setAiOutputFormat={setAiOutputFormat}
                            aiOutputCompression={aiOutputCompression}
                            setAiOutputCompression={setAiOutputCompression}
                            aiBackground={aiBackground}
                            setAiBackground={setAiBackground}
                            hasGenerated={hasGenerated}
                            isGenerating={isGenerating}
                            isSaving={isSaving}
                            aiError={aiError}
                            handleGenerateAgain={handleGenerateAgain}
                            generateAIImage={generateAIImage}
                            getAvailableModels={getAvailableModels}
                            getAvailableImageCounts={getAvailableImageCounts}
                            getImageCategories={getImageCategories}
                            getAvailableSizes={getAvailableSizes}
                            isControlVisible={isControlVisible}
                            getModelForSize={getModelForSize}
                        />

                        {/* Generated Images Preview */}
                        <AIImageGeneratedPreview
                            currentImage={currentImage}
                            isGenerating={isGenerating}
                            isSaving={isSaving}
                            hasGenerated={hasGenerated}
                            generatedImages={generatedImages}
                            usageInfo={usageInfo}
                            handleKeepImage={handleKeepImage}
                            setIsEditingMode={setIsEditingMode}
                            setUserPreferredMode={setUserPreferredMode}
                        />
                    </div>
                )}
            </Modal >
        </>
    );
};

export default AIImagePopover;
