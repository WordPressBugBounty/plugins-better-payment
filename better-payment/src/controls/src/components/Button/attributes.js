import { __ } from "@wordpress/i18n";
import {
    camelCase,
    upperFirst,
} from 'lodash'

import {
    generateBackgroundAttributes,
    generateTypographyAttributes,
    generateBorderShadowAttributes,
    generateDimensionsAttributes,
    generateResponsiveRangeAttributes
} from "../../index"

import {
    BUTTON_BACKGROUND,
    BUTTON_BORDER,
    BUTTON_PADDING,
    FIXED_WIDTH,
    ICON_SIZE,
    ICON_SPACE,
    typoPrefix_text,
    LIQUID_GLASS_SHADOW_BORDER
} from "./constants"

const buttonAttributes = {
    buttonText: {
        type: "string",
        default: __("Click Me!", "essential-blocks"),
    },
    buttonURL: {
        type: "string",
        default: "",
    },
    newWindow: {
        type: "boolean",
        default: false,
    },
    addNofollow: {
        type: "boolean",
        default: false,
    },
    addDownload: {
        type: "boolean",
        default: false,
    },
    buttonAlign: {
        type: "string",
        default: "center",
    },
    buttonWidth: {
        type: "string",
        default: "auto",
    },
    addIcon: {
        type: "boolean",
        default: false,
    },
    icon: {
        type: "string",
        default: "fas fa-chevron-right",
    },
    iconPosition: {
        type: "string",
        default: "left",
    },
    iconSize: {
        type: "string",
    },
    iconSpace: {
        type: "string",
        default: "5px",
    },
    hoverEffect: {
        type: "string",
    },
    hoverTransition: {
        type: "number",
        default: 0.3,
    },
    textColor: {
        type: "string",
        default: "var(--eb-global-button-text-color)",
    },
    hoverTextColor: {
        type: "string",
        default: "var(--eb-global-button-text-color)",
    },
    btnLiquidGlass: {
        type: "object",
        default: {
            enable: false,
            effect: "effect1",
            shadowEffect: "effect1",
            backgroundColor: "#FFFFFF1F",
            backdropFilter: 24,
            brightness: 1,
        },
    },
}

const applyAttrName = (attributes, attrName = "") => {
    if (!attrName) {
        return attributes
    }

    return Object.keys(attributes).reduce((newAttributes, key) => {
        const prefixedKey = `${attrName}${upperFirst(camelCase(key))}`;
        newAttributes[prefixedKey] = { ...attributes[key] };
        return newAttributes;
    }, {});

}

export const addAttributes = (
    attrObject = {},
    attrName = '',
    hasTypography = true,
    typography = { key: typoPrefix_text },
    hasBackground = true,
    backgroundValue,
    hasBorder = true,
    border = { key: BUTTON_BORDER },
    hasPadding = true,
    padding = {
        key: BUTTON_PADDING, default: {
            top: 15,
            right: 30,
            bottom: 15,
            left: 30,
            isLinked: false,
        }
    },
    hasWidth = true,
    hasIcon = true,
    hasLiquidGlass = true
) => {
    let renamedObject = {};

    let background = (backgroundValue !== undefined && backgroundValue !== '') ? backgroundValue : {
        key: BUTTON_BACKGROUND,
        default: {
            noOverlay: true,
            noMainBgi: true,
            defaultFillColor: "var(--eb-global-button-background-color)",
            defaultHovFillColor: "var(--eb-global-tertiary-color)",
        }
    };

    for (const [oldKey, newKey] of Object.entries(attrObject)) {
        if (buttonAttributes.hasOwnProperty(oldKey)) {
            renamedObject[newKey] = buttonAttributes[oldKey];
        }
    }

    let remainingAttributes = {};
    for (const [key, value] of Object.entries(buttonAttributes)) {
        if (!attrObject.hasOwnProperty(key)) {
            remainingAttributes[key] = value;
        }
    }

    if (attrName.length > 0) {
        remainingAttributes = applyAttrName(remainingAttributes, attrName);
    }

    let typographyObject = {};
    if (hasTypography) {
        typographyObject = generateTypographyAttributes(typography?.key, typography.default);
    }

    let backgroundObject = {};
    if (hasBackground) {
        backgroundObject = generateBackgroundAttributes(background?.key, background.default);
    }

    let borderObject = {};
    if (hasBorder) {
        borderObject = generateBorderShadowAttributes(border?.key, border.default);
    }

    let paddingObject = {};
    if (hasPadding) {
        paddingObject = generateDimensionsAttributes(padding?.key, padding.default);
    }

    let fixedWidth = {};
    if (hasWidth) {
        fixedWidth = generateResponsiveRangeAttributes(FIXED_WIDTH)
    }

    let iconSize = {};
    let iconGap = {};
    if (hasIcon) {
        iconSize = generateResponsiveRangeAttributes(ICON_SIZE, {
            noUnits: true,
            defaultRange: 20
        });
        iconGap = generateResponsiveRangeAttributes(ICON_SPACE, {
            defaultRange: 8,
            noUnits: true,
        });
    }

    let liquidGlassShadowBorder = {};
    if (hasLiquidGlass) {
        liquidGlassShadowBorder = generateBorderShadowAttributes(LIQUID_GLASS_SHADOW_BORDER, {
            defaultBdrColor: "#FFFFFF1F",
            defaultBdrStyle: "solid",
            bdrDefaults: {
                top: 1,
                bottom: 1,
                right: 1,
                left: 1,
            },
            rdsDefaults: {
                top: 24,
                bottom: 24,
                right: 24,
                left: 24,
            },
        });
    }

    renamedObject = {
        ...renamedObject,
        ...remainingAttributes,
        ...typographyObject,
        ...backgroundObject,
        ...borderObject,
        ...paddingObject,
        ...fixedWidth,
        ...iconSize,
        ...iconGap,
        ...liquidGlassShadowBorder
    };

    return renamedObject;
};
