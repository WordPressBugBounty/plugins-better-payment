# LiquidGlassEffectControl Component

A simple toggle control for enabling/disabling liquid glass effects in Essential Blocks.

## Features

- **Simple Toggle**: Provides a boolean toggle for enabling/disabling liquid glass effects
- **Help Text**: Shows guidance to users about configuring effects in the Advanced tab
- **Lightweight**: Minimal implementation focused on the enable/disable functionality

## Basic Usage

```jsx
import { LiquidGlassEffectControl } from '@essential-blocks/controls';

// In your inspector component
<LiquidGlassEffectControl attributeName="iconLiquidGlass" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `attributeName` | string | `'iconLiquidGlass'` | Name of the boolean attribute to control |

## Required Block Attributes

Your block must have a boolean attribute for the toggle:

```javascript
iconLiquidGlass: {
    type: "boolean",
    default: false,
}
```

Note: The full liquid glass functionality (effects, colors, filters) should be configured in the Advanced tab using the standard `liquidGlass` object attribute.

## Usage Notes

- This control only provides the enable/disable toggle functionality
- Full liquid glass configuration (effects, colors, filters) should be done in the Advanced tab
- The control shows helpful text directing users to the Advanced tab for more options
- This is designed for blocks that need a simple liquid glass toggle in their specific settings panel
