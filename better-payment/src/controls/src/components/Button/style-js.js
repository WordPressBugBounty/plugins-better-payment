/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { applyFilters } from "@wordpress/hooks";
/**
 * Internal depencencies
 */
import {
    BUTTON_BACKGROUND,
    BUTTON_BORDER,
    BUTTON_PADDING,
    FIXED_WIDTH,
    ICON_SIZE,
    ICON_SPACE,
    typoPrefix_text,
    LIQUID_GLASS_SHADOW_BORDER,
} from "./constants";
import {
    camelCase,
    upperFirst,
} from 'lodash'

import {
    generateTypographyStyles,
    generateResponsiveRangeStyles,
    useBlockAttributes,
    generateDimensionsControlStyles,
    generateBackgroundControlStyles,
    generateBorderShadowStyles
} from "../../index"

export const Style = (
    blockId,
    wrapperClass,
    buttonAttrProps,
    attrPrefix = '',
    className = '',
    typography = typoPrefix_text,
    backgroundValue,
    border = BUTTON_BORDER,
    padding = BUTTON_PADDING,
    hasAlignment = true,
) => {
    const attributes = useBlockAttributes();

    let background = backgroundValue == '' ? BUTTON_BACKGROUND : backgroundValue;

    const getAttrName = (attrKey) => {
        const finalKey = buttonAttrProps && buttonAttrProps[attrKey] ? buttonAttrProps[attrKey] : attrKey;

        if (buttonAttrProps && buttonAttrProps[attrKey]) {
            return finalKey;
        }

        return attrPrefix ? `${attrPrefix}${upperFirst(camelCase(finalKey))}` : finalKey;
    };
    const attrValue = (attrKey) => attributes[getAttrName(attrKey)];

    if (blockId === "" || wrapperClass === "") {
        return;
    }

    const customClass = className?.length > 0 ? "." + className : "";

    // typography
    const {
        typoStylesDesktop: textTypoStylesDesktop,
        typoStylesTab: textTypoStylesTab,
        typoStylesMobile: textTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typography,
    });

    const {
        rangeStylesDesktop: buttonWidthDesktop,
        rangeStylesTab: buttonWidthTab,
        rangeStylesMobile: buttonWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: FIXED_WIDTH,
        property: "width",
        attributes,
    });

    const {
        backgroundStylesDesktop: btnBackgroundStylesDesktop,
        hoverBackgroundStylesDesktop: btnHoverBackgroundStylesDesktop,
        backgroundStylesTab: btnBackgroundStylesTab,
        hoverBackgroundStylesTab: btnHoverBackgroundStylesTab,
        backgroundStylesMobile: btnBackgroundStylesMobile,
        hoverBackgroundStylesMobile: btnHoverBackgroundStylesMobile,
        bgTransitionStyle: btnBgTransitionStyle,
    } = generateBackgroundControlStyles({
        attributes,
        controlName: background,
    });

    const {
        dimensionStylesDesktop: buttonPaddingDesktop,
        dimensionStylesTab: buttonPaddingTab,
        dimensionStylesMobile: buttonPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: padding,
        styleFor: "padding",
        attributes,
    });

    const {
        styesDesktop: bdShadowStyesDesktop,
        styesTab: bdShadowStyesTab,
        styesMobile: bdShadowStyesMobile,
        stylesHoverDesktop: bdShadowStylesHoverDesktop,
        stylesHoverTab: bdShadowStylesHoverTab,
        stylesHoverMobile: bdShadowStylesHoverMobile,
        transitionStyle: bdShadowTransitionStyle,
    } = generateBorderShadowStyles({
        controlName: border,
        attributes,
    });

    // Liquid glass shadow border styles
    const {
        styesDesktop: liquidGlassShadowStylesDesktop,
        styesTab: liquidGlassShadowStylesTab,
        styesMobile: liquidGlassShadowStylesMobile,
    } = generateBorderShadowStyles({
        controlName: LIQUID_GLASS_SHADOW_BORDER,
        attributes,
    });

    const {
        rangeStylesDesktop: iconSizeDesktop,
        rangeStylesTab: iconSizeTab,
        rangeStylesMobile: iconSizeMobile,
    } = generateResponsiveRangeStyles({
        controlName: ICON_SIZE,
        property: "font-size",
        attributes,
        customUnit: "px",
    });

    const {
        rangeStylesDesktop: svgWidthDesktop,
        rangeStylesTab: svgWidthTab,
        rangeStylesMobile: svgWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: ICON_SIZE,
        property: "width",
        attributes,
        customUnit: "px",
    });

    const {
        rangeStylesDesktop: svgHeightDesktop,
        rangeStylesTab: svgHeightTab,
        rangeStylesMobile: svgHeightMobile,
    } = generateResponsiveRangeStyles({
        controlName: ICON_SIZE,
        property: "height",
        attributes,
        customUnit: "px",
    });

    const {
        rangeStylesDesktop: iconGapLeftDesktop,
        rangeStylesTab: iconGapLeftTab,
        rangeStylesMobile: iconGapLeftMobile,
    } = generateResponsiveRangeStyles({
        controlName: ICON_SPACE,
        property: "margin-left",
        attributes,
        customUnit: "px",
    });

    // button gap right
    const {
        rangeStylesDesktop: iconGapRightDesktop,
        rangeStylesTab: iconGapRightTab,
        rangeStylesMobile: iconGapRightMobile,
    } = generateResponsiveRangeStyles({
        controlName: ICON_SPACE,
        property: "margin-right",
        attributes,
        customUnit: "px",
    });

    let buttonAlign = attrValue('buttonAlign');

    const desktopStyles = `
		${attrValue('buttonWidth') !== "auto"
            ? attrValue('buttonWidth') === "full"
                ? `
					.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
						width: 100%;
					}`
                : `.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
						${buttonWidthDesktop}
					}`
            : ""
        }

        .${wrapperClass}.${blockId} .eb-button-inner-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: ${buttonAlign};
        }

		.${wrapperClass}.${blockId} .eb-button-loadmore${customClass},
		.${wrapperClass}-${blockId} .eb-button-loadmore${customClass},
		.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
			${background && background.length > 0 ? btnBackgroundStylesDesktop : ""}
			${border && border.length > 0 ? bdShadowStyesDesktop : ""}
			${typography && typography.length > 0 ? textTypoStylesDesktop : ""}
			${padding && padding.length > 0 ? buttonPaddingDesktop : ""}
			${attrValue('textColor') ? `color: ${attrValue('textColor')};` : ""}
    transition: ${attrValue('hoverTransition') ? `all ${attrValue('hoverTransition')}s,` : ""
        } ${btnBgTransitionStyle}, ${bdShadowTransitionStyle};
}

		${attrValue('hoverEffect')
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:before, .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}:before {
				${background && background.length > 0 ? btnHoverBackgroundStylesDesktop : ''}
                ${border && border.length > 0 ? bdShadowStylesHoverDesktop ? bdShadowStylesHoverDesktop : bdShadowStyesDesktop : ''}
                ${attrValue('hoverTextColor') ? `color: ${attrValue('hoverTextColor')};` : ""}
			}
			`
            : ""
        }

		.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:hover,
        .${wrapperClass}-${blockId} .eb-button-loadmore${customClass}:hover,
        .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}:hover{
			${background && background.length > 0 ? btnHoverBackgroundStylesDesktop : ""}
			${border && border.length > 0 ? bdShadowStylesHoverDesktop : ""}
			${attrValue('hoverTextColor') ? `color: ${attrValue('hoverTextColor')};` : ""}
        }

		${attrValue('addIcon')
            ? `.${wrapperClass}.${blockId} .eb-button-icon {
					${iconSizeDesktop}
					${attrValue('iconPosition') === "left" ? iconGapRightDesktop : iconGapLeftDesktop}
				}
                .${wrapperClass}.${blockId} .eb-button-icon svg,
                .${wrapperClass}.${blockId} svg.eb-button-icon {
                    ${svgWidthDesktop}
                    ${svgHeightDesktop}
                }    
                `
            : ""
        }

        ${attrValue('btnLiquidGlass')?.enable ? `
            .${wrapperClass}.${blockId} .eb-button-anchor${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect},
            .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect} {
                ${liquidGlassShadowStylesDesktop}
            }
            .${wrapperClass}.${blockId} .eb-button-anchor${customClass}.eb_liquid_glass-${attrValue('btnLiquidGlass').effect},
            .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}.eb_liquid_glass-${attrValue('btnLiquidGlass').effect} {
                background-color: ${attrValue('btnLiquidGlass').backgroundColor};
                ${attrValue("btnLiquidGlass").effect === "effect1" ||
                attrValue("btnLiquidGlass").effect === "effect2"
                ? `backdrop-filter: blur(${attrValue("btnLiquidGlass").backdropFilter}px)${attrValue("btnLiquidGlass").effect === "effect2"
                    ? ` brightness(${attrValue("btnLiquidGlass").brightness})`
                    : ""
                };`
                : ""
            }

            }
        ` : ""}
`;


    const tabStyles = `
		${attrValue('buttonWidth') === "fixed"
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
					${buttonWidthTab}
				}`
            : ""
        }

		.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
            ${background && background.length > 0 ? btnBackgroundStylesTab : ""}
			${border && border.length > 0 ? bdShadowStyesTab : ""}
			${typography && typography.length > 0 ? textTypoStylesTab : ''}
			${padding && padding.length > 0 ? buttonPaddingTab : ''}
}

		.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:hover {
			${border && border.length > 0 ? bdShadowStylesHoverTab : ""}
}

		${attrValue('buttonWidth') === "fixed"
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
					${buttonWidthTab}
				}`
            : ""
        }

		${attrValue('addIcon')
            ? `.${wrapperClass}.${blockId} .eb-button-icon {
					${iconSizeTab}
					${attrValue('iconPosition') === "left" ? iconGapRightTab : iconGapLeftTab}
				}
                .${wrapperClass}.${blockId} .eb-button-icon svg,
                .${wrapperClass}.${blockId} svg.eb-button-icon {
                    ${svgWidthTab}
                    ${svgHeightTab}
                }     
                `
            : ""
        }

        ${attrValue('hoverEffect')
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:before {
				${background && background.length > 0 ? btnHoverBackgroundStylesTab : ""}
                ${border && border.length > 0 ? bdShadowStylesHoverTab ? bdShadowStylesHoverTab : bdShadowStyesDesktop : ""}
			}
			`
            : ""
        }

        ${attrValue('btnLiquidGlass')?.enable ? `
            .${wrapperClass}.${blockId} .eb-button-anchor${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect},
            .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect} {
                ${liquidGlassShadowStylesTab}
            }
        ` : ""}
`;

    const mobileStyles = `
        ${attrValue('buttonWidth') === "fixed"
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
                    ${buttonWidthMobile}
                }`
            : ""
        }

		.${wrapperClass}.${blockId} .eb-button-anchor${customClass} {
            ${background && background.length > 0 ? btnBackgroundStylesMobile : ""}
			${border && border.length > 0 ? bdShadowStyesMobile : ""}
			${typography && typography.length > 0 ? textTypoStylesMobile : ""}
			${padding && padding.length > 0 ? buttonPaddingMobile : ""}
}

		.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:hover {
			${border && border.length > 0 ? bdShadowStylesHoverMobile : ""}
}

		${attrValue('addIcon')
            ? `.${wrapperClass}.${blockId} .eb-button-icon {
					${iconSizeMobile}
					${attrValue('iconPosition') === "left" ? iconGapRightMobile : iconGapLeftMobile}
				}
                .${wrapperClass}.${blockId} .eb-button-icon svg,
                .${wrapperClass}.${blockId} svg.eb-button-icon {
                    ${svgWidthMobile}
                    ${svgHeightMobile}
                }     
                `
            : ""
        }

        ${attrValue('hoverEffect')
            ? `.${wrapperClass}.${blockId} .eb-button-anchor${customClass}:before {
				${background && background.length > 0 ? btnHoverBackgroundStylesMobile : ""}
                ${border && border.length > 0 ? bdShadowStylesHoverMobile ? bdShadowStylesHoverMobile : bdShadowStyesDesktop : ''}
			}
			`
            : ""
        }

        ${attrValue('btnLiquidGlass')?.enable ? `
            .${wrapperClass}.${blockId} .eb-button-anchor${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect},
            .${wrapperClass}.${blockId} .eb-button-loadmore${customClass}.eb_liquid_glass_shadow-${attrValue('btnLiquidGlass').shadowEffect} {
                ${liquidGlassShadowStylesMobile}
            }
        ` : ""}
`;


    const btnliquidProStyles = applyFilters("eb_liquid_glass_effect_pro_style", attributes, "btnLiquidGlass", `.${wrapperClass}.${blockId} .eb-button-anchor${customClass}`);
    const loadmoreliquidProStyles = applyFilters("eb_liquid_glass_effect_pro_style", attributes, "btnLiquidGlass", `.${wrapperClass}.${blockId} .eb-button-loadmore${customClass}`);

    return {
        btnDesktopStyle: desktopStyles + btnliquidProStyles + loadmoreliquidProStyles,
        btnTabStyle: tabStyles,
        btnMobileStyle: mobileStyles
    }
}
