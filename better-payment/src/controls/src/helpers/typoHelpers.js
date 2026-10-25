import { hasVal } from "./hasVal";
import { googleFonts } from "../controls/typography-control-v2/fontPicker/googleFonts";

// function to generate typography attributes for multiple typography control based on the array of prefix
export const generateTypographyAttributes = (prefixArray, defaults = {}) => {
    if (typeof prefixArray === 'string') {
        prefixArray = [prefixArray]
    }
    const typoAttrs = prefixArray.reduce((total, current) => {
        const result = {
            [`${current}FontFamily`]: {
                type: "string",
            },
            [`${current}FontSource`]: {
                type: "string",
            },
            [`${current}SizeUnit`]: {
                type: "string",
                default: defaults?.fontSizeUnit || "px",
            },
            [`${current}FontSize`]: {
                type: "number",
                ...(defaults?.fontSize && { default: defaults?.fontSize })
            },
            [`${current}FontWeight`]: {
                type: "string",
                ...(defaults?.fontWeight && { default: defaults?.fontWeight })
            },
            [`${current}FontStyle`]: {
                type: "string",
                ...(defaults?.fontStyle && { default: defaults?.fontStyle })
            },
            [`${current}TextTransform`]: {
                type: "string",
                ...(defaults?.textTransform && { default: defaults?.textTransform })
            },
            [`${current}TextDecoration`]: {
                type: "string",
                ...(defaults?.textDecoration && { default: defaults?.textDecoration })
            },
            [`${current}LetterSpacingUnit`]: {
                type: "string",
                default: defaults?.letterSpacingUnit || "px",
            },
            [`${current}LetterSpacing`]: {
                type: "number",
                ...(defaults?.LetterSpacing && { default: defaults?.LetterSpacing })
            },
            [`${current}LineHeightUnit`]: {
                type: "string",
                default: defaults?.lineHeightUnit || "em",
            },
            [`${current}LineHeight`]: {
                type: "number",
                ...(defaults?.lineHeight && { default: defaults?.lineHeight })
            },

            [`TAB${current}SizeUnit`]: {
                type: "string",
                default: "px",
            },
            [`TAB${current}FontSize`]: {
                type: "number",
            },
            [`TAB${current}LetterSpacingUnit`]: {
                type: "string",
                default: "px",
            },
            [`TAB${current}LetterSpacing`]: {
                type: "number",
            },
            [`TAB${current}LineHeightUnit`]: {
                type: "string",
                default: "em",
            },
            [`TAB${current}LineHeight`]: {
                type: "number",
            },

            [`MOB${current}SizeUnit`]: {
                type: "string",
                default: "px",
            },
            [`MOB${current}FontSize`]: {
                type: "number",
            },
            [`MOB${current}LetterSpacingUnit`]: {
                type: "string",
                default: "px",
            },
            [`MOB${current}LetterSpacing`]: {
                type: "number",
            },
            [`MOB${current}LineHeightUnit`]: {
                type: "string",
                default: "em",
            },
            [`MOB${current}LineHeight`]: {
                type: "number",
            },
        };

        return {
            ...total,
            ...result,
        };
    }, {});

    return typoAttrs;
};

//
// function to generate typography styles for an element based on it's prefix
export const generateTypographyStyles = ({
    prefixConstant,
    defaultFontSize,
    attributes,
}) => {
    const {
        [`${prefixConstant}FontSource`]: fontSource,
        [`${prefixConstant}FontFamily`]: fontFamily,
        [`${prefixConstant}FontWeight`]: fontWeight,
        [`${prefixConstant}FontStyle`]: fontStyle,
        [`${prefixConstant}TextTransform`]: textTransform,
        [`${prefixConstant}TextDecoration`]: textDecoration,
        [`${prefixConstant}FontSize`]: fontSize = defaultFontSize,
        [`${prefixConstant}SizeUnit`]: sizeUnit,
        [`${prefixConstant}LetterSpacing`]: letterSpacing,
        [`${prefixConstant}LetterSpacingUnit`]: letterSpacingUnit,
        [`${prefixConstant}LineHeight`]: lineHeight,
        [`${prefixConstant}LineHeightUnit`]: lineHeightUnit,

        [`TAB${prefixConstant}SizeUnit`]: TABsizeUnit,
        [`TAB${prefixConstant}LetterSpacingUnit`]: TABletterSpacingUnit,
        [`TAB${prefixConstant}LineHeightUnit`]: TABlineHeightUnit,
        [`TAB${prefixConstant}FontSize`]: TABfontSize,
        [`TAB${prefixConstant}LetterSpacing`]: TABletterSpacing,
        [`TAB${prefixConstant}LineHeight`]: TABlineHeight,

        [`MOB${prefixConstant}SizeUnit`]: MOBsizeUnit,
        [`MOB${prefixConstant}LetterSpacingUnit`]: MOBletterSpacingUnit,
        [`MOB${prefixConstant}LineHeightUnit`]: MOBlineHeightUnit,
        [`MOB${prefixConstant}FontSize`]: MOBfontSize,
        [`MOB${prefixConstant}LetterSpacing`]: MOBletterSpacing,
        [`MOB${prefixConstant}LineHeight`]: MOBlineHeight,
    } = attributes;

    const googleFontFamily = (fontFamily) => {
        if (fontFamily && fontFamily !== "Default") {
            const fontFamilyKey = (fontFamily || "").replace(/\s+/g, "-");

            let googleFontCategory = googleFonts[fontFamilyKey]
                ? googleFonts[fontFamilyKey].category
                : '';

            let FontFamily;
            if ("handwriting" === googleFontCategory || "display" === googleFontCategory) {
                FontFamily = '"' + fontFamily + '", cursive';
            } else {
                const cat = (googleFontCategory || "").toString().trim();
                FontFamily = '"' + fontFamily + '"' + (cat ? ', ' + cat : '');
            }

            return FontFamily;
        }
        return "";
    };

    //Retrieve typography global key for global fonts || return false if custom typo
    const getGlobalSource = (fontSource) => {
        if (!fontSource) {
            return false;
        }
        const parts = fontSource.split(':');
        if (parts.length === 2 && parts[0].trim() === 'global') {
            return parts[1].trim();
        } else if (parts.length === 1 && parts[0].trim() === 'global') {
            return 'global';
        } else {
            return false;
        }
    }

    const source = getGlobalSource(fontSource)
    if (source !== false && source !== 'custom') {
        if (source === 'global') { //If Source=global, no css need to generate, global css is handled from global controls
            return {
                typoStylesDesktop: '',
                typoStylesTab: '',
                typoStylesMobile: ''
            }
        }
        return { //if source=custom global fonts
            typoStylesDesktop: `
            font-family: var(--${source}-font-family);
            font-size: var(--${source}-font-size);
            font-weight: var(--${source}-font-weight);
            line-height: var(--${source}-line-height);
            font-style: var(--${source}-font-style);
            text-decoration: var(--${source}-text-decoration);
            text-transform: var(--${source}-text-transform);
            letter-spacing: var(--${source}-letter-spacing);
            `,
            typoStylesTab: '',
            typoStylesMobile: '',
        }
    }

    const typoStylesDesktop = `
              ${fontFamily
            ? `font-family: ${googleFontFamily(fontFamily)};`
            : " "
        }
              ${hasVal(fontSize) ? `font-size: ${fontSize}${sizeUnit};` : " "}
              ${hasVal(lineHeight)
            ? `line-height: ${lineHeight}${lineHeightUnit};`
            : " "
        }
              ${fontWeight ? `font-weight: ${fontWeight};` : " "}
              ${fontStyle ? `font-style: ${fontStyle};` : " "}
              ${textDecoration ? `text-decoration: ${textDecoration};` : " "}
              ${textTransform ? `text-transform: ${textTransform};` : " "}
              ${hasVal(letterSpacing)
            ? `letter-spacing: ${letterSpacing}${letterSpacingUnit};`
            : " "
        }
          `;

    const typoStylesTab = `
              ${hasVal(TABfontSize)
            ? `font-size: ${TABfontSize}${TABsizeUnit};`
            : " "
        }
              ${hasVal(TABlineHeight)
            ? `line-height: ${TABlineHeight}${TABlineHeightUnit};`
            : " "
        }
              ${hasVal(TABletterSpacing)
            ? `letter-spacing: ${TABletterSpacing}${TABletterSpacingUnit};`
            : " "
        }
          `;

    const typoStylesMobile = `
              ${hasVal(MOBfontSize)
            ? `font-size: ${MOBfontSize}${MOBsizeUnit};`
            : " "
        }
              ${hasVal(MOBlineHeight)
            ? `line-height: ${MOBlineHeight}${MOBlineHeightUnit};`
            : " "
        }
              ${hasVal(MOBletterSpacing)
            ? `letter-spacing: ${MOBletterSpacing}${MOBletterSpacingUnit};`
            : " "
        }
          `;

    return {
        typoStylesDesktop,
        typoStylesTab,
        typoStylesMobile,
    };
};
