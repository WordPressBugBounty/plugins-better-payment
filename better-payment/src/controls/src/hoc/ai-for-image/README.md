# AI for Image HOC

This Higher Order Component (HOC) adds an AI image generation button to the block toolbar for specific image blocks.

## Supported Blocks

- `essential-blocks/advanced-image`
- `core/image`

## Features

- Adds an AI button to the block toolbar when an image block is selected
- Shows AIImagePopover component for AI image generation
- Automatically sets the generated image to the block attributes
- Only appears when the AI image generation feature is enabled (`enableGenerateImage` is "1")

## How it works

1. The HOC uses the `editor.BlockEdit` filter to wrap image block edit components
2. When an image block is selected, it adds a toolbar button with the AI icon
3. Clicking the button opens the AIImagePopover component
4. When an image is generated and kept, it automatically updates the block attributes
5. The component handles different attribute structures for Essential Blocks vs Core blocks

## Block Attribute Mapping

### Essential Blocks Advanced Image
- `imageId` - Image attachment ID
- `imageUrl` - Image URL
- `imageAlt` - Alt text
- `imageCaption` - Caption text

### Core Image Block
- `id` - Image attachment ID
- `url` - Image URL
- `alt` - Alt text
- `caption` - Caption text

## Dependencies

- `AIImagePopover` component from `../../components/AI/AIImagePopover`
- `AiIcon` from `../../icons/ai-icon`
- WordPress block editor components and hooks

## Usage

This HOC is automatically registered when the file is imported. No manual setup required.

```javascript
// The HOC is automatically applied to supported blocks
import './path/to/ai-for-image';
```

## Conditional Loading

The AI button only appears when:
1. The block is one of the supported image blocks
2. The block is currently selected
3. The AI image generation feature is enabled (`window.EssentialBlocksLocalize?.enableGenerateImage === "1"`)
