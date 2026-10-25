import { hasVal } from "./hasVal";

// function to generate New Dimensions-Control's attributes for multiple Dimensions control based on the array of values(prefixs)
export const generateDimensionsAttributes = (controlName, defaults = {}) => {
    const {
        top,
        right,
        bottom,
        left,
        isLinked = true,
        disableLeftRight = false,
        defaultUnit = "px"
    } = defaults;

    const desktopTop = hasVal(top)
        ? {
            [`${controlName}Top`]: {
                type: "string",
                default: `${top}`,
            },
        }
        : {
            [`${controlName}Top`]: {
                type: "string",
            },
        };

    const desktopRight = hasVal(right)
        ? {
            [`${controlName}Right`]: {
                type: "string",
                default: `${right}`,
            },
        }
        : {
            [`${controlName}Right`]: {
                type: "string",
            },
        };

    const desktopBottom = hasVal(bottom)
        ? {
            [`${controlName}Bottom`]: {
                type: "string",
                default: `${bottom}`,
            },
        }
        : {
            [`${controlName}Bottom`]: {
                type: "string",
            },
        };

    const desktopLeft = hasVal(left)
        ? {
            [`${controlName}Left`]: {
                type: "string",
                default: `${left}`,
            },
        }
        : {
            [`${controlName}Left`]: {
                type: "string",
            },
        };

    const objsAfterCaringForDisableLeftRightProp = disableLeftRight
        ? {
            ...desktopTop,
            ...desktopBottom,

            [`TAB${controlName}Top`]: {
                type: "string",
            },
            [`TAB${controlName}Bottom`]: {
                type: "string",
            },

            [`MOB${controlName}Top`]: {
                type: "string",
            },
            [`MOB${controlName}Bottom`]: {
                type: "string",
            },
        }
        : {
            ...desktopTop,
            ...desktopRight,
            ...desktopBottom,
            ...desktopLeft,

            [`TAB${controlName}Top`]: {
                type: "string",
            },
            [`TAB${controlName}Right`]: {
                type: "string",
            },
            [`TAB${controlName}Bottom`]: {
                type: "string",
            },
            [`TAB${controlName}Left`]: {
                type: "string",
            },

            [`MOB${controlName}Top`]: {
                type: "string",
            },
            [`MOB${controlName}Right`]: {
                type: "string",
            },
            [`MOB${controlName}Bottom`]: {
                type: "string",
            },
            [`MOB${controlName}Left`]: {
                type: "string",
            },
        };

    return {
        [`${controlName}isLinked`]: {
            type: "boolean",
            default: isLinked,
        },
        [`${controlName}Unit`]: {
            type: "string",
            default: defaultUnit,
        },
        [`TAB${controlName}Unit`]: {
            type: "string",
            default: defaultUnit,
        },
        [`MOB${controlName}Unit`]: {
            type: "string",
            default: defaultUnit,
        },
        ...objsAfterCaringForDisableLeftRightProp,
    };
};

//
// function to generate dimensions-controls styles for an element based on it's controlName(prefix)
export const generateDimensionsControlStyles = ({
    controlName,
    styleFor,
    attributes,
    disableLeftRight = false,
}) => {
    const {
        [`${controlName}isLinked`]: isLinked,

        [`${controlName}Unit`]: dimensionUnit,
        [`${controlName}Top`]: dimensionTop,
        [`${controlName}Right`]: dimensionRight,
        [`${controlName}Bottom`]: dimensionBottom,
        [`${controlName}Left`]: dimensionLeft,

        [`TAB${controlName}Unit`]: TABdimensionUnit,
        [`TAB${controlName}Top`]: TABdimensionTop,
        [`TAB${controlName}Right`]: TABdimensionRight,
        [`TAB${controlName}Bottom`]: TABdimensionBottom,
        [`TAB${controlName}Left`]: TABdimensionLeft,

        [`MOB${controlName}Unit`]: MOBdimensionUnit,
        [`MOB${controlName}Top`]: MOBdimensionTop,
        [`MOB${controlName}Right`]: MOBdimensionRight,
        [`MOB${controlName}Bottom`]: MOBdimensionBottom,
        [`MOB${controlName}Left`]: MOBdimensionLeft,
    } = attributes;

    let dimensionStylesDesktop = " ";
    let dimensionStylesTab = " ";
    let dimensionStylesMobile = " ";

    let topProperty = `${styleFor && styleFor.trim() !== '' ? `${styleFor}-top` : 'top'}`
    let rightProperty = `${styleFor && styleFor.trim() !== '' ? `${styleFor}-right` : 'right'}`
    let bottomProperty = `${styleFor && styleFor.trim() !== '' ? `${styleFor}-bottom` : 'bottom'}`
    let leftProperty = `${styleFor && styleFor.trim() !== '' ? `${styleFor}-left` : 'left'}`

    if (isLinked === true && disableLeftRight === false) {
        if (styleFor === "border") {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `border-width: ${dimensionTop}${dimensionUnit}; `
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-width: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-width: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else if (styleFor === "border-radius") {
            dimensionStylesDesktop = `
                ${dimensionTop
                    ? `border-radius: ${dimensionTop}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-radius: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-radius: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `${styleFor}: ${dimensionTop}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `${styleFor}: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `${styleFor}: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }

            `;
        }
    } else if (isLinked === false && disableLeftRight === false) {
        if (styleFor === "border") {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `border-top-width: ${dimensionTop}${dimensionUnit};`
                    : " "
                }
            ${dimensionRight
                    ? `border-right-width: ${dimensionRight}${dimensionUnit};`
                    : " "
                }
            ${dimensionLeft
                    ? `border-left-width: ${dimensionLeft}${dimensionUnit};`
                    : " "
                }
            ${dimensionBottom
                    ? `border-bottom-width: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-width: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionRight
                    ? `border-right-width: ${TABdimensionRight}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionLeft
                    ? `border-left-width: ${TABdimensionLeft}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionBottom
                    ? `border-bottom-width: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-width: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionRight
                    ? `border-right-width: ${MOBdimensionRight}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionLeft
                    ? `border-left-width: ${MOBdimensionLeft}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `border-bottom-width: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else if (styleFor === "border-radius") {
            dimensionStylesDesktop = `
                ${dimensionTop
                    ? `border-top-left-radius: ${dimensionTop}${dimensionUnit};`
                    : " "
                }
                ${dimensionRight
                    ? `border-top-right-radius: ${dimensionRight}${dimensionUnit};`
                    : " "
                }
                ${dimensionLeft
                    ? `border-bottom-left-radius: ${dimensionLeft}${dimensionUnit};`
                    : " "
                }
                ${dimensionBottom
                    ? `border-bottom-right-radius: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-left-radius: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionRight
                    ? `border-top-right-radius: ${TABdimensionRight}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionLeft
                    ? `border-bottom-left-radius: ${TABdimensionLeft}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionBottom
                    ? `border-bottom-right-radius: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-left-radius: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionRight
                    ? `border-top-right-radius: ${MOBdimensionRight}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionLeft
                    ? `border-bottom-left-radius: ${MOBdimensionLeft}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `border-bottom-right-radius: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `${topProperty}: ${dimensionTop}${dimensionUnit};`
                    : " "
                }
            ${dimensionRight
                    ? `${rightProperty}: ${dimensionRight}${dimensionUnit};`
                    : " "
                }
            ${dimensionLeft
                    ? `${leftProperty}: ${dimensionLeft}${dimensionUnit};`
                    : " "
                }
            ${dimensionBottom
                    ? `${bottomProperty}: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `${topProperty}: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionRight
                    ? `${rightProperty}: ${TABdimensionRight}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionLeft
                    ? `${leftProperty}: ${TABdimensionLeft}${TABdimensionUnit}; `
                    : " "
                }
                ${TABdimensionBottom
                    ? `${bottomProperty}: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `${topProperty}: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionRight
                    ? `${rightProperty}: ${MOBdimensionRight}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionLeft
                    ? `${leftProperty}: ${MOBdimensionLeft}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `${bottomProperty}: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        }
    } else if (isLinked === true && disableLeftRight === true) {
        if (styleFor === "border") {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `border-top-width: ${dimensionTop}${dimensionUnit}; `
                    : " "
                }
            ${dimensionBottom
                    ? `border-bottom-width: ${dimensionBottom}${dimensionUnit}; `
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-width: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionBottom
                    ? `border-bottom-width: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-width: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `border-bottom-width: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else if (styleFor === "border-radius") {
            dimensionStylesDesktop = `
                ${dimensionTop
                    ? `border-top-left-radius: ${dimensionTop}${dimensionUnit};`
                    : " "
                }

                ${dimensionBottom
                    ? `border-bottom-right-radius: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-left-radius: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }

                ${TABdimensionBottom
                    ? `border-bottom-right-radius: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-left-radius: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }

                ${MOBdimensionBottom
                    ? `border-bottom-right-radius: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `${topProperty}: ${dimensionTop}${dimensionUnit};`
                    : " "
                }

            ${dimensionBottom
                    ? `${bottomProperty}: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `${topProperty}: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }

                ${TABdimensionBottom
                    ? `${bottomProperty}: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `${topProperty}: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }

                ${MOBdimensionBottom
                    ? `${bottomProperty}: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        }
    } else if (isLinked === false && disableLeftRight === true) {
        if (styleFor === "border") {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `border-top-width: ${pdimensionTop}${dimensionUnit};`
                    : " "
                }
            ${dimensionBottom
                    ? `border-bottom-width: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-width: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionBottom
                    ? `border-bottom-width: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-width: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `border-bottom-width: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else if (styleFor === "border-radius") {
            dimensionStylesDesktop = `
                ${dimensionTop
                    ? `border-top-left-radius: ${dimensionTop}${dimensionUnit};`
                    : " "
                }
                ${dimensionBottom
                    ? `border-bottom-right-radius: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `border-top-left-radius: ${TABdimensionTop}${TABdimensionUnit};`
                    : " "
                }
                ${TABdimensionBottom
                    ? `border-bottom-right-radius: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `border-top-left-radius: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `border-bottom-right-radius: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        } else {
            dimensionStylesDesktop = `
            ${dimensionTop
                    ? `${topProperty}: ${dimensionTop}${dimensionUnit};`
                    : " "
                }
            ${dimensionBottom
                    ? `${bottomProperty}: ${dimensionBottom}${dimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesTab = `
                ${TABdimensionTop
                    ? `${topProperty}: ${TABdimensionTop}${TABdimensionUnit}; `
                    : " "
                }
                ${TABdimensionBottom
                    ? `${bottomProperty}: ${TABdimensionBottom}${TABdimensionUnit};`
                    : " "
                }

            `;

            dimensionStylesMobile = `
                ${MOBdimensionTop
                    ? `${topProperty}: ${MOBdimensionTop}${MOBdimensionUnit};`
                    : " "
                }
                ${MOBdimensionBottom
                    ? `${bottomProperty}: ${MOBdimensionBottom}${MOBdimensionUnit};`
                    : " "
                }

            `;
        }
    }

    return {
        dimensionStylesDesktop,
        dimensionStylesTab,
        dimensionStylesMobile,
    };
};
