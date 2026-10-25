# EBTextControl Component

A flexible wrapper component for WordPress TextControl that provides additional functionality and customization options.

## Features

- **Design Flexibility**: Easy to modify design with wrapper elements and custom styling
- **Enhanced onChange**: Additional onChange handler with enhanced data
- **Prefix/Suffix Support**: Add elements before or after the input
- **Validation**: Built-in validation support with custom validators
- **AI Content Generation**: Built-in AI content generation with OpenAI integration
- **Security Filtering**: Built-in XSS protection and content sanitization
- **Real-Time URL Validation**: Live URL validation as user types with helpful feedback messages
- **URL Sanitization**: Automatic URL sanitization for security
- **Field Type Control**: Prop-controlled field types ('text' or 'url') with appropriate validation
- **Accessibility**: Maintains all WordPress TextControl accessibility features
- **Responsive**: Mobile-friendly responsive design

## Basic Usage

```jsx
import { EBTextControl } from '@essential-blocks/controls';

// Simple usage (same as TextControl)
<EBTextControl
    label="Enter your name"
    value={name}
    onChange={(value) => setName(value)}
    placeholder="Your name here..."
/>
```

## Advanced Usage Examples

### With AI Content Generation

```jsx
<EBTextControl
    label="Product Title"
    value={title}
    onChange={(value) => setTitle(value)}
    enableAi={true}
    aiContentType="text"
    placeholder="Enter product title or use AI to generate..."
/>
```

### With Prefix and Suffix

```jsx
<EBTextControl
    label="Website URL"
    value={url}
    onChange={(value) => setUrl(value)}
    prefix={<span>https://</span>}
    suffix={<span>.com</span>}
    placeholder="example"
/>
```

### With Validation

```jsx
const emailValidator = (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return {
        isValid: emailRegex.test(value),
        message: emailRegex.test(value) ? '' : 'Please enter a valid email address'
    };
};

<EBTextControl
    label="Email Address"
    value={email}
    onChange={(value) => setEmail(value)}
    validator={emailValidator}
    showValidation={true}
    type="email"
/>
```

### URL Field with Real-Time Validation

```jsx
<EBTextControl
    label="Website URL"
    value={websiteUrl}
    onChange={(value) => setWebsiteUrl(value)}
    fieldType="url"
    showValidation={true}
    placeholder="https://example.com"
    help="Real-time validation will guide you as you type"
/>
```

### URL Field with Custom Validation

```jsx
// Custom validator for social media URLs
const validateSocialUrl = (url) => {
    if (!url) return { isValid: true, message: '' };

    const socialDomains = ['facebook.com', 'twitter.com', 'instagram.com'];
    const hasSocialDomain = socialDomains.some(domain => url.includes(domain));

    if (!hasSocialDomain) {
        return {
            isValid: false,
            message: 'Please enter a social media URL'
        };
    }

    return { isValid: true, message: '' };
};

<EBTextControl
    label="Social Media Profile"
    value={socialUrl}
    onChange={(value) => setSocialUrl(value)}
    fieldType="url"
    validator={validateSocialUrl}
    placeholder="https://facebook.com/yourpage"
/>
```

### Text Field with Security Filtering

```jsx
<EBTextControl
    label="User Content"
    value={userContent}
    onChange={(value) => setUserContent(value)}
    enableSecurity={true}
    showValidation={true}
    placeholder="Enter content (XSS protection enabled)"
/>
```

### Prop-Controlled Field Types

```jsx
// Text field (default)
<EBTextControl
    label="Text Input"
    value={textValue}
    onChange={setTextValue}
    fieldType="text"
    showValidation={true}
/>

// URL field with automatic validation
<EBTextControl
    label="URL Input"
    value={urlValue}
    onChange={setUrlValue}
    fieldType="url"
    showValidation={true}
/>
```

### With Enhanced onChange

```jsx
<EBTextControl
    label="Search Query"
    value={searchQuery}
    onChange={(value) => setSearchQuery(value)}
    onChangeEnhanced={(data) => {
        console.log('Enhanced data:', data);
        // data contains: { value, previousValue, isValid, timestamp }

        // Trigger search after user stops typing
        if (data.value.length > 2) {
            debouncedSearch(data.value);
        }
    }}
    placeholder="Search..."
/>
```

### With Custom Styling

```jsx
<EBTextControl
    label="Custom Styled Input"
    value={customValue}
    onChange={(value) => setCustomValue(value)}
    wrapperClassName="my-custom-wrapper"
    className="my-custom-input"
    wrapperStyle={{
        border: '2px solid #007cba',
        borderRadius: '8px',
        padding: '4px'
    }}
/>
```

### Complex Example with All Features

```jsx
const priceValidator = (value) => {
    const price = parseFloat(value);
    if (isNaN(price)) {
        return { isValid: false, message: 'Please enter a valid number' };
    }
    if (price < 0) {
        return { isValid: false, message: 'Price cannot be negative' };
    }
    if (price > 10000) {
        return { isValid: false, message: 'Price cannot exceed $10,000' };
    }
    return { isValid: true, message: '' };
};

<EBTextControl
    label="Product Price"
    value={price}
    onChange={(value) => setPrice(value)}
    onChangeEnhanced={(data) => {
        if (data.isValid) {
            updatePriceInDatabase(data.value);
        }
    }}
    prefix={<span>$</span>}
    suffix={<span>USD</span>}
    validator={priceValidator}
    showValidation={true}
    type="number"
    placeholder="0.00"
    help="Enter the product price in USD"
    wrapperClassName="price-input-wrapper"
/>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | - | Label for the text control |
| `value` | string | `''` | Current value |
| `onChange` | function | - | Standard change handler |
| `placeholder` | string | - | Placeholder text |
| `help` | string | - | Help text |
| `className` | string | `''` | Additional CSS classes for input |
| `wrapperClassName` | string | `''` | CSS classes for the wrapper |
| `prefix` | ReactNode | - | Element to show before the input |
| `suffix` | ReactNode | - | Element to show after the input |
| `onChangeEnhanced` | function | - | Enhanced change handler with additional data |
| `validator` | function | - | Validation function |
| `showValidation` | boolean | `false` | Whether to show validation messages |
| `wrapperStyle` | object | `{}` | Inline styles for wrapper |
| `disabled` | boolean | `false` | Whether the control is disabled |
| `type` | string | `'text'` | Input type |
| `enableAi` | boolean | `true` | Whether to show AI functionality |
| `aiContentType` | string | `'text'` | Type of AI content ('text' or 'paragraph') |
| `fieldType` | string | `'text'` | Field type ('text' or 'url') |
| `enableSecurity` | boolean | `false` | Whether to enable security filtering (auto-enabled for URL fields) |

## Validator Function

The validator function can return:

1. **Boolean**: `true` for valid, `false` for invalid
2. **Object**: `{ isValid: boolean, message: string }`

```jsx
// Simple boolean validator
const simpleValidator = (value) => value.length > 3;

// Detailed object validator
const detailedValidator = (value) => ({
    isValid: value.length > 3,
    message: value.length > 3 ? '' : 'Must be at least 4 characters'
});
```

## Enhanced onChange Data

The `onChangeEnhanced` callback receives an object with:

- `value`: The new input value
- `previousValue`: The previous input value
- `isValid`: Boolean indicating if the value passes validation
- `timestamp`: Timestamp when the change occurred

## AI Content Generation

The component includes built-in AI content generation powered by OpenAI. Users can:

- Click the AI icon to open the content generation popover
- Enter prompts to generate relevant content
- Choose from suggested prompts based on content type
- Select content length (Short, Medium, Large, Big) and tone (Friendly, Professional, Bold, Quirky, Urgent)
- Generate content that automatically fills the input field

### AI Configuration

To use AI features, ensure:
1. OpenAI API key is configured in Essential Blocks settings
2. `enableAi` prop is set to `true` (default)
3. Appropriate `aiContentType` is set ('text' for short content like titles, 'paragraph' for longer content)

## Security Features

The component includes built-in security features to protect against XSS attacks and malicious content:

### Security Filtering

- **Automatic for URL fields**: Security filtering is automatically enabled when `fieldType="url"`
- **Manual activation**: Set `enableSecurity={true}` for text fields that need protection
- **XSS Protection**: Removes script tags, javascript: protocols, and event handlers
- **URL Sanitization**: Uses Essential Blocks' `sanitizeURL` function for URL validation

### Real-Time URL Validation

When `fieldType="url"` is set, the component provides intelligent real-time validation:

#### Smart Validation Flow
- **Empty input**: No validation message (allows user to start typing)
- **Typing "http"**: Shows helpful hint: "Continue typing... (e.g., http://example.com)"
- **Typing "https"**: Shows helpful hint: "Continue typing... (e.g., https://example.com)"
- **Has protocol but no domain**: "Add domain extension (e.g., .com, .org)"
- **Incomplete domain**: "Complete the domain extension"
- **Valid URL**: "✓ Valid URL" with green styling
- **Invalid URL**: "Please enter a valid URL format." with red styling
- **Dangerous content**: "URL contains potentially dangerous content." with immediate warning

#### Performance Features
- **Debounced Validation**: 300ms delay to avoid excessive validation during typing
- **Immediate Security Checks**: Dangerous content detected and warned about instantly
- **Smart Completion Detection**: Only validates when URL appears complete
- **Progressive Feedback**: Helpful hints guide users through URL construction

#### Visual Feedback
- **Red border/text**: Invalid URL or dangerous content
- **Green border/text**: Valid URL
- **Blue border**: User is actively typing
- **Blue left border**: URL field indicator
- **Green left border**: Security filtering enabled

#### Security Features
- **XSS Protection**: Immediate detection and removal of dangerous patterns
- **Protocol Validation**: Only allows safe protocols (http, https, ftp, ftps, mailto, tel)
- **Content Sanitization**: Filters out script tags, event handlers, and malicious content
- **Smart Sanitization**: Preserves user input during typing, applies sanitization when complete
- **Relative URL Support**: Allows relative URLs (starting with `/`) and anchors (starting with `#`)
- **Empty Value Handling**: Gracefully handles empty strings without replacing with `#`
- **User-Friendly Messages**: Shows clear error messages only when appropriate
- **Security Sanitization**: Applies sanitization without disrupting user input

### Field Type Control

- **Prop-controlled**: Field type is controlled via the `fieldType` prop ('text' or 'url')
- **Automatic validation**: Validation rules change automatically based on field type
- **Security adaptation**: Security filtering adapts to the field type
- **Developer-friendly**: Simple prop-based API for controlling field behavior

## CSS Classes

The component adds these CSS classes for styling:

- `.eb-text-control-wrapper`: Main wrapper
- `.eb-text-control-input-wrapper`: Input container
- `.eb-text-control-input`: Input element
- `.eb-text-control-ai`: AI icon
- `.eb-text-control-prefix`: Prefix element
- `.eb-text-control-suffix`: Suffix element

- `.eb-text-control-wrapper--disabled`: When disabled
- `.eb-text-control-wrapper--invalid`: When validation fails
- `.eb-text-control-wrapper--has-prefix`: When prefix is present
- `.eb-text-control-wrapper--has-suffix`: When suffix is present
- `.eb-text-control-wrapper--url`: When field type is URL
- `.eb-text-control-wrapper--secure`: When security filtering is enabled
- `.eb-text-control-validation-message`: Validation message

## Styling

Import the CSS file in your component or build process:

```css
@import '~@essential-blocks/controls/src/controls/text-control-wrapper/style.css';
```

Or include it in your build process to customize the styles.
