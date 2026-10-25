# EBTextareaControl Component

A flexible wrapper component for WordPress TextareaControl that provides additional functionality and customization options.

## Features

- **Design Flexibility**: Easy to modify design with wrapper elements and custom styling
- **Enhanced onChange**: Additional onChange handler with enhanced data
- **Prefix/Suffix Support**: Add elements before or after the textarea
- **Validation**: Built-in validation support with custom validators
- **AI Content Generation**: Built-in AI content generation with OpenAI integration
- **Accessibility**: Maintains all WordPress TextareaControl accessibility features
- **Responsive**: Mobile-friendly responsive design

## Basic Usage

```jsx
import { EBTextareaControl } from '@essential-blocks/controls';

// Simple usage (same as TextareaControl)
<EBTextareaControl
    label="Enter your description"
    value={description}
    onChange={(value) => setDescription(value)}
    placeholder="Your description here..."
    rows={6}
/>
```

## Advanced Usage Examples

### With AI Content Generation

```jsx
<EBTextareaControl
    label="Product Description"
    value={productDescription}
    onChange={(value) => setProductDescription(value)}
    enableAi={true}
    aiContentType="paragraph"
    placeholder="Describe your product..."
    rows={8}
/>
```

### With Prefix and Suffix

```jsx
<EBTextareaControl
    label="Code Block"
    value={code}
    onChange={(value) => setCode(value)}
    prefix={<span>```</span>}
    suffix={<span>```</span>}
    placeholder="Enter your code here..."
    rows={10}
/>
```

### With Validation

```jsx
const contentValidator = (value) => {
    const minLength = 50;
    return {
        isValid: value.length >= minLength,
        message: value.length >= minLength ? '' : `Content must be at least ${minLength} characters long`
    };
};

<EBTextareaControl
    label="Article Content"
    value={content}
    onChange={(value) => setContent(value)}
    validator={contentValidator}
    showValidation={true}
    rows={12}
/>
```

### With Enhanced onChange

```jsx
<EBTextareaControl
    label="Blog Post"
    value={blogPost}
    onChange={(value) => setBlogPost(value)}
    onChangeEnhanced={(data) => {
        console.log('Enhanced data:', data);
        // data contains: { value, previousValue, isValid, timestamp }

        // Auto-save after user stops typing
        if (data.value.length > 100) {
            debouncedAutoSave(data.value);
        }
    }}
    placeholder="Write your blog post..."
    rows={15}
/>
```

### With Custom Styling

```jsx
<EBTextareaControl
    label="Custom Styled Textarea"
    value={customContent}
    onChange={(value) => setCustomContent(value)}
    className="my-custom-textarea"
    wrapperClassName="my-custom-wrapper"
    wrapperStyle={{
        border: '2px solid #0073aa',
        borderRadius: '8px',
        padding: '16px'
    }}
    rows={8}
/>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string` | - | Label for the textarea control |
| `value` | `string` | `''` | Current value of the textarea |
| `onChange` | `function` | - | Function called when value changes |
| `placeholder` | `string` | - | Placeholder text |
| `help` | `string` | - | Help text displayed below the textarea |
| `className` | `string` | `''` | Additional CSS classes for the textarea |
| `wrapperClassName` | `string` | `''` | CSS classes for the wrapper element |
| `prefix` | `ReactNode` | - | Element to display before the textarea |
| `suffix` | `ReactNode` | - | Element to display after the textarea |
| `onChangeEnhanced` | `function` | - | Enhanced change handler with additional data |
| `validator` | `function` | - | Validation function |
| `showValidation` | `boolean` | `false` | Whether to show validation messages |
| `wrapperStyle` | `object` | `{}` | Inline styles for the wrapper |
| `disabled` | `boolean` | `false` | Whether the textarea is disabled |
| `rows` | `number` | `4` | Number of rows for the textarea |
| `enableAi` | `boolean` | `true` | Whether to show AI functionality |
| `aiContentType` | `string` | `'paragraph'` | Type of AI content ('text' or 'paragraph') |

## AI Content Generation

The component includes built-in AI content generation powered by OpenAI. Users can:

- Click the AI icon to open the content generation popover
- Enter prompts to generate relevant content
- Choose from suggested prompts based on content type
- Select content length (Short, Medium, Large, Big) and tone (Friendly, Professional, Bold, Quirky, Urgent)
- Generate content that automatically fills the textarea
- Cursor automatically focuses at the end of generated content for immediate editing

### AI Configuration

To use AI features, ensure:
1. OpenAI API key is configured in Essential Blocks settings
2. `enableAi` prop is set to `true` (default)
3. Appropriate `aiContentType` is set ('paragraph' for longer content, 'text' for shorter content)

## Styling

The component uses CSS classes that can be customized:

- `.eb-textarea-control-wrapper` - Main wrapper
- `.eb-textarea-control-input-wrapper` - Input container
- `.eb-textarea-control-input` - Textarea element
- `.eb-textarea-control-ai` - AI icon
- `.eb-textarea-control-prefix` - Prefix container
- `.eb-textarea-control-suffix` - Suffix container
- `.eb-textarea-control-validation-message` - Validation message

## Validation

The validator function can return:

```jsx
// Boolean (simple validation)
const validator = (value) => value.length > 10;

// Object (detailed validation)
const validator = (value) => ({
    isValid: value.length > 10,
    message: 'Content must be longer than 10 characters'
});
```

## Enhanced onChange Data

The `onChangeEnhanced` callback receives an object with:

```jsx
{
    value: 'current value',
    previousValue: 'previous value',
    isValid: true, // validation result
    timestamp: 1234567890 // when change occurred
}
```
