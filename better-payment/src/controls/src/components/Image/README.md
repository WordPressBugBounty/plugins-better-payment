# ImageComponent Documentation

The `ImageComponent` is a comprehensive, reusable image component for Essential Blocks that provides image display, upload functionality, styling options, and caption support with responsive controls.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Component Structure](#component-structure)
- [Props](#props)
- [Attributes](#attributes)
- [Sub-components](#sub-components)
- [Examples](#examples)
- [Styling](#styling)
- [Advanced Usage](#advanced-usage)

## Overview

The ImageComponent consists of three main parts:
- **Main Component**: Renders the image with styling and captions
- **Upload Component**: Handles image upload functionality
- **Content Component**: Renders the final output for frontend

## Installation

```javascript
import { ImageComponent } from "../../controls/src/components/Image";
// or
import ImageComponent from "../../controls/src/components/Image";
```

## Basic Usage

### 1. Adding Image Attributes to Block

```javascript
import { ImageComponent } from "../../controls/src/components/Image";

// Add image attributes to your block
const attributes = {
  ...ImageComponent.addAttributes({
    attrName: "hero", // Optional prefix for attribute names
    hasBorder: true,
    hasPadding: true,
    hasMargin: true,
    hasWidth: true,
    hasHeight: true,
  }),
  // ... other block attributes
};
```

### 2. Using in Block Edit Component

```javascript
import { ImageComponent } from "../../controls/src/components/Image";

const Edit = ({ attributes, setAttributes, isSelected }) => {
  return (
    <div>
      {/* Image Upload Component */}
      <ImageComponent.Upload
        imageAttrProps={{}}
        attrPrefix="hero"
        labels={{
          title: "Upload Hero Image",
          instructions: "Select an image for the hero section"
        }}
      />
      
      {/* Main Image Component */}
      <ImageComponent
        isSelected={isSelected}
        imageAttrProps={{}}
        className="custom-image-class"
        attrPrefix="hero"
        hasStyle={true}
      />
    </div>
  );
};
```

### 3. Using in Block Save Component

```javascript
const Save = ({ attributes }) => {
  return (
    <div>
      <ImageComponent.Content
        attributes={attributes}
        className="custom-image-class"
        imageAttrProps={{}}
        attrPrefix="hero"
        hasStyle={true}
      />
    </div>
  );
};
```

### 4. Adding Inspector Controls

```javascript
import { InspectorControls } from "@wordpress/block-editor";

const Edit = ({ attributes, setAttributes }) => {
  return (
    <>
      <InspectorControls>
        <ImageComponent.GeneralTab
          imageAttrProps={{}}
          attrPrefix="hero"
          label="Hero Image Settings"
          hasTag={true}
          hasCaption={true}
          hasStyle={true}
          useImageAlign={false}
        />
        <ImageComponent.StyleTab
          imageAttrProps={{}}
          attrPrefix="hero"
          hasBackground={true}
          hasBorder={true}
          hasRadius={true}
        />
      </InspectorControls>
      {/* ... rest of component */}
    </>
  );
};
```

### 5. Adding Styles

```javascript
import { ImageComponent } from "../../controls/src/components/Image";

const Style = ({ attributes, setAttributes, name }) => {
  return (
    <ImageComponent.Style
      attributes={attributes}
      setAttributes={setAttributes}
      name={name}
      imageAttrProps={{}}
      attrPrefix="hero"
    />
  );
};
```

## Component Structure

```
ImageComponent/
├── index.js          # Main component file
├── attributes.js     # Attribute definitions
├── constants.js      # Constants and options
├── general.js        # General tab controls
├── style-tab.js      # Style tab controls
├── style-js.js       # Style generation
└── imageComponent.scss # Component styles
```

## Props

### Main ImageComponent Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `isSelected` | boolean | - | Whether the block is currently selected |
| `imageAttrProps` | object | `{}` | Custom attribute name mappings |
| `className` | string | `""` | Additional CSS classes |
| `attrPrefix` | string | `""` | Prefix for attribute names |
| `hasStyle` | boolean | `true` | Whether to apply style presets |

### Upload Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `imageAttrProps` | object | `{}` | Custom attribute name mappings |
| `attrPrefix` | string | `""` | Prefix for attribute names |
| `labels` | object | `{}` | Custom labels for upload interface |
| `icon` | ReactNode | - | Custom icon for upload button |

### Content Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `attributes` | object | - | Block attributes |
| `className` | string | `""` | Additional CSS classes |
| `imageAttrProps` | object | `{}` | Custom attribute name mappings |
| `attrPrefix` | string | `""` | Prefix for attribute names |
| `hasStyle` | boolean | `true` | Whether to apply style presets |

### GeneralTab Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `imageAttrProps` | object | `{}` | Custom attribute name mappings |
| `attrPrefix` | string | `""` | Prefix for attribute names |
| `label` | string | `"Image Settings"` | Panel title |
| `hasTag` | boolean | `true` | Show figure tag option |
| `hasCaption` | boolean | `true` | Show caption options |
| `hasStyle` | boolean | `true` | Show style presets |
| `useImageAlign` | boolean | `false` | Show image alignment controls |
| `resOption` | object | - | Responsive options |

## Attributes

The component automatically generates the following attributes when using `addAttributes()`:

### Core Image Attributes
- `imageUrl` (string): Image URL
- `imageId` (number): WordPress media ID
- `imageAlt` (string): Alt text
- `imageCaption` (string): Image caption
- `imageSize` (string): WordPress image size

### Display Options
- `figureTag` (boolean): Use figure tag instead of div
- `displayCaption` (boolean): Show/hide caption
- `captionStyle` (string): Caption style variant
- `stylePreset` (string): Image style preset

### Link Options
- `enableLink` (boolean): Enable image linking
- `imageLink` (string): Link URL
- `openInNewTab` (boolean): Open link in new tab

### Image Filters
- `imageBlur` (number): Blur filter
- `imageBrightness` (number): Brightness filter
- `imageContrast` (number): Contrast filter
- `imageGrayscale` (number): Grayscale filter
- `imageHueRotate` (number): Hue rotation
- `imageInvert` (number): Invert filter
- `imageOpacity` (number): Opacity
- `imageSaturate` (number): Saturation
- `imageSepia` (number): Sepia filter

### Object Fit & Position
- `imgObjectFit` (string): CSS object-fit property
- `imgObjectPosition` (string): CSS object-position property

### Responsive Attributes
When enabled, the component also generates responsive attributes for:
- Border and shadow
- Padding and margin
- Width and height
- Border radius
- Typography (for captions)
- Alignment

## Sub-components

### ImageComponent.Upload
Renders the image upload interface when no image is selected.

### ImageComponent.Content
Renders the final image output for the frontend/save function.

### ImageComponent.GeneralTab
Provides inspector controls for general image settings.

### ImageComponent.StyleTab
Provides inspector controls for styling options.

### ImageComponent.Style
Generates CSS styles based on attributes.

### ImageComponent.addAttributes
Utility function to add image attributes to block registration.

## Examples

### Example 1: Simple Image Block

```javascript
import { ImageComponent } from "../../controls/src/components/Image";

const attributes = {
  ...ImageComponent.addAttributes({}),
};

const Edit = ({ attributes, setAttributes, isSelected }) => (
  <>
    <ImageComponent.Upload />
    <ImageComponent isSelected={isSelected} />
  </>
);

const Save = ({ attributes }) => (
  <ImageComponent.Content attributes={attributes} />
);
```

### Example 2: Hero Image with Custom Prefix

```javascript
const attributes = {
  ...ImageComponent.addAttributes({
    attrName: "hero",
    hasBorder: true,
    hasRadius: true,
  }),
};

const Edit = ({ attributes, setAttributes, isSelected }) => (
  <>
    <ImageComponent.Upload attrPrefix="hero" />
    <ImageComponent 
      isSelected={isSelected} 
      attrPrefix="hero"
      className="hero-image"
    />
  </>
);
```

### Example 3: Multiple Images with Different Configurations

```javascript
const attributes = {
  // Featured image
  ...ImageComponent.addAttributes({
    attrName: "featured",
    hasBorder: true,
    hasRadius: true,
  }),
  // Thumbnail image
  ...ImageComponent.addAttributes({
    attrName: "thumb",
    hasBorder: false,
    hasRadius: false,
    hasWidth: true,
    hasHeight: true,
  }),
};
```

### Example 4: Custom Attribute Mapping

```javascript
const imageAttrProps = {
  imageUrl: "customImageUrl",
  imageId: "customImageId",
  imageAlt: "customImageAlt",
};

<ImageComponent 
  imageAttrProps={imageAttrProps}
  isSelected={isSelected}
/>
```

## Styling

The component generates CSS classes that can be styled:

```scss
.eb-image-wrapper {
  // Main wrapper styles
  
  .eb-image-wrapper-inner {
    // Inner wrapper styles
    
    &.eb-img-style-rounded {
      // Rounded style preset
    }
    
    &.eb-img-style-circle {
      // Circle style preset
    }
    
    &.eb-img-style-square {
      // Square style preset
    }
  }
  
  .eb-image-caption-wrap {
    // Caption styles
    
    &.eb-img-caption-style-1 {
      // Caption style 1
    }
    
    &.eb-img-caption-style-2 {
      // Caption style 2
    }
  }
}
```

## Advanced Usage

### Custom Style Presets

You can extend the available style presets by modifying the `STYLES` constant:

```javascript
// In constants.js
export const STYLES = [
  { label: __("Rounded", "essential-blocks"), value: "rounded" },
  { label: __("Square", "essential-blocks"), value: "square" },
  { label: __("Circle", "essential-blocks"), value: "circle" },
  { label: __("Custom", "essential-blocks"), value: "custom" },
];
```

### Dynamic Content Support

The component supports dynamic content when Essential Blocks Pro is active:

```javascript
// Dynamic image URL
<DynamicInputControl
  attrName="imageUrl"
  inputValue={imageUrl}
  setAttributes={setAttributes}
  isImageField={true}
  type="image"
/>
```

### Responsive Controls

Enable responsive controls for better mobile experience:

```javascript
<ImageComponent.GeneralTab
  useImageAlign={true}
  resOption={resOption}
/>
```

This documentation covers the complete usage of the ImageComponent. For more specific implementation details, refer to the individual component files in the Image directory.
