# AI for Rewrite HOC

This Higher Order Component (HOC) adds a "Rewrite with AI" button to the block toolbar for all Essential Blocks.

## Supported Blocks

- All blocks that start with `essential-blocks/` prefix
- Automatically detects and applies to any Essential Block

## Features

- Adds a "Rewrite with AI" button to the block toolbar when an Essential Block is selected
- Shows AIRewritePopover component for AI content rewriting
- Automatically updates block attributes with rewritten content
- Only appears when the AI content generation feature is enabled (`enableWriteAIPageContent` is "1")
- Preserves block structure while rewriting content

## How it works

1. The HOC uses the `editor.BlockEdit` filter to wrap Essential Block edit components
2. When an Essential Block is selected, it adds a toolbar button with the AI icon
3. Clicking the button opens the AIRewritePopover component
4. The current block attributes are extracted and sent to the AI for rewriting
5. When content is rewritten and kept, it automatically updates the block attributes
6. Non-content attributes (like blockId, uniqueId, etc.) are preserved

## Block Attribute Processing

### Content Extraction
The HOC automatically extracts content from block attributes while excluding:
- `blockId` - Unique block identifier
- `blockRoot` - Block hierarchy information
- `blockMeta` - Block metadata
- `uniqueId` - Unique styling identifier
- `classHook` - CSS class hooks
- `cover` - Cover settings
- `preset` - Block presets
- `resOption` - Responsive options
- `parentBlockId` - Parent block reference
- `blockStyle` - Style configurations
- `blockStyleId` - Style identifiers
- `blockStyleName` - Style names

### Content Merging
When AI returns rewritten content, it's merged with existing attributes:
```javascript
setAttributes({
    ...existingAttributes,
    ...rewrittenContent
});
```

## Dependencies

- `AIRewritePopover` component from `../../components/AI/AIRewritePopover`
- `AiIcon` from `../../icons/ai-icon`
- WordPress block editor components and hooks

## Usage

This HOC is automatically registered when the file is imported. No manual setup required.

```javascript
// The HOC is automatically applied to all Essential Blocks
import './path/to/ai-for-rewrite';
```

## Conditional Loading

The AI rewrite button only appears when:
1. The block is an Essential Block (starts with `essential-blocks/`)
2. The block is currently selected
3. The AI content generation feature is enabled (`window.EssentialBlocksLocalize?.enableWriteAIPageContent === "1"`)

## Integration with Backend

The HOC integrates with the backend through:
- **Job Type**: Uses `rewrite` job type for AJAX requests
- **Content Format**: Sends current block attributes as JSON
- **Response Handling**: Processes rewritten content and updates block attributes
- **Error Handling**: Shows appropriate error messages for failed requests

## Example Usage

```javascript
// When a user clicks the "Rewrite with AI" button on an Essential Block:
// 1. Current block attributes are extracted
// 2. User provides rewrite instructions in the popup
// 3. AI processes the content and returns rewritten version
// 4. Block attributes are updated with new content
// 5. Block re-renders with rewritten content
```

## Security

- Uses WordPress nonce verification for AJAX requests
- Sanitizes all user inputs before sending to AI
- Validates AI responses before updating block attributes
- Respects user permissions for content editing

## Performance

- Only loads when AI features are enabled
- Uses efficient block selection detection
- Minimal impact on editor performance
- Lazy loads AI components only when needed
