# Global AI RichText Integration

A global format type registration that automatically adds AI functionality to ALL RichText components across the editor. No HOC wrapping needed - the AI button appears in every RichText toolbar automatically!

## Features

- **Universal Integration**: AI button appears in ALL RichText toolbars automatically
- **Zero Configuration**: No wrapping or special setup needed for individual components
- **Same AI Experience**: Uses the identical AIContentPopover as EBTextControl and EBTextareaControl
- **Automatic Registration**: Format type is registered globally when the module is imported
- **Conditional Display**: Only shows when OpenAI API key is configured

## How It Works

The system registers a global format type using WordPress's `registerFormatType` function. This automatically adds an AI button to every RichText toolbar in the editor.

```jsx
// No special imports or wrapping needed!
// Just use RichText normally and the AI button will appear

const MyComponent = ({ attributes, setAttributes }) => {
    const { text } = attributes;

    return (
        <RichText
            tagName="p"
            value={text}
            onChange={(newText) => setAttributes({ text: newText })}
            placeholder="Type your content - AI button automatically available..."
        />
    );
};
```

## Automatic Registration

The AI format type is automatically registered when the Essential Blocks controls are loaded. No manual setup required!

```jsx
// The format type is registered automatically when you import Essential Blocks controls
import { initializeAIForRichText } from '@essential-blocks/controls';

// Or it's automatically initialized when the controls module loads
```

## Examples

### Standard Block with RichText

```jsx
const MyBlock = ({ attributes, setAttributes }) => {
    const { title, content } = attributes;

    return (
        <div>
            {/* AI button automatically appears in both RichText toolbars */}
            <RichText
                tagName="h2"
                value={title}
                onChange={(newTitle) => setAttributes({ title: newTitle })}
                placeholder="Enter title - AI button available in toolbar..."
            />

            <RichText
                tagName="div"
                value={content}
                onChange={(newContent) => setAttributes({ content: newContent })}
                placeholder="Enter content - AI button available in toolbar..."
                multiline="p"
            />
        </div>
    );
};
```

### Notice Block Example

```jsx
const NoticeBlock = ({ attributes, setAttributes }) => {
    const { title, text } = attributes;

    return (
        <div className="notice-block">
            <RichText
                className="notice-title"
                value={title}
                onChange={(newTitle) => setAttributes({ title: newTitle })}
                placeholder="Add Title - AI button automatically available..."
            />

            <RichText
                className="notice-text"
                value={text}
                onChange={(newText) => setAttributes({ text: newText })}
                placeholder="Add Text - AI button automatically available..."
            />
        </div>
    );
};
```

### Any Existing RichText

```jsx
// This works with ANY existing RichText component!
// No changes needed - the AI button just appears

<RichText
    tagName="p"
    value={someValue}
    onChange={someHandler}
    placeholder="AI button automatically available in toolbar..."
/>
```

## How It Works

1. **Format Registration**: A global format type is registered using `registerFormatType`
2. **Automatic Button**: The AI button appears in every RichText toolbar automatically
3. **Popover Display**: Clicking the button opens the `AIContentPopover` component
4. **Content Generation**: The popover handles AI content generation using the OpenAI API
5. **Content Replacement**: Generated content replaces the entire RichText content
6. **Cleanup**: The popover closes automatically after content generation

## Requirements

- OpenAI API key must be configured in Essential Blocks settings
- The format type is automatically registered when Essential Blocks controls load
- Works with any RichText component in any block

## Styling

The AI button uses the class `eb-richtext-ai-button` and can be styled with CSS:

```css
.eb-richtext-ai-button {
    /* Custom styles for the AI button */
}
```

## Technical Details

The implementation uses WordPress's format type system:

```jsx
registerFormatType('essential-blocks/ai-content', {
    title: 'AI Content Generator',
    tagName: 'span',
    className: null,
    edit: AIRichTextButton,
});
```

This approach ensures the AI button appears in ALL RichText toolbars across the entire editor, regardless of which block is using the RichText component.

## Benefits

- **Zero Configuration**: No setup needed for individual blocks
- **Universal Coverage**: Works with all existing and future RichText components
- **Consistent Experience**: Same AI functionality everywhere
- **Easy Maintenance**: Single registration point for all RichText AI features

## See Also

- [AIContentPopover Component](../components/AI/README.md)
- [EBTextControl](../controls/text-control/README.md)
- [EBTextareaControl](../controls/textarea-control/README.md)
