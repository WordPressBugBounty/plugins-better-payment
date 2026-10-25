import { hasVal } from "./hasVal";

// function to generate New Dimensions-Control's attributes for multiple Dimensions control based on the array of values(prefixs)
export const generateTextControllerAttributes = (controlName, defaults = {}) => {
    const {
        value,
        defaultUnit = "px",
        isNumber
    } = defaults;

    const desktopValue = hasVal(value)
        ? {
            [`${controlName}Value`]: {
                type: "string",
                default: `${value}`,
            },
        }
        : {
            [`${controlName}Value`]: {
                type: "string",
            },
        };

    const objsAfterCaringForDesktopProp = {
        ...desktopValue,
        [`TAB${controlName}Value`]: {
            type: "string",
        },
        [`MOB${controlName}Value`]: {
            type: "string",
        },
    };

    return {
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
        ...objsAfterCaringForDesktopProp,
    };
};

//
// function to generate dimensions-controls styles for an element based on it's controlName(prefix)
export const generateTextControllerStyles = ({
    controlName,
    styleFor,
    useUnits,
    attributes,
}) => {
    const {
        [`${controlName}Unit`]: dimensionUnit,
        [`${controlName}Value`]: dimensionValue,

        [`TAB${controlName}Unit`]: TABdimensionUnit,
        [`TAB${controlName}Value`]: TABdimensionValue,

        [`MOB${controlName}Unit`]: MOBdimensionUnit,
        [`MOB${controlName}Value`]: MOBdimensionValue,
    } = attributes;

    let textControlStylesDesktop = " ";
    let textControlStylesTab = " ";
    let textControlStylesMobile = " ";


    textControlStylesDesktop = `
            ${dimensionValue
            ? useUnits ? `${styleFor}: ${parseFloat(
                dimensionValue
            )}${dimensionUnit};` : `${styleFor}: ${parseFloat(
                dimensionValue
            )};` : ""
        }
`;

    textControlStylesTab = `
            ${TABdimensionValue
            ? useUnits ? `${styleFor}: ${parseFloat(
                TABdimensionValue
            )}${TABdimensionUnit};` : `${styleFor}: ${parseFloat(
                TABdimensionValue
            )};` : ""
        }
`;

    textControlStylesMobile = `
            ${MOBdimensionValue
            ? useUnits ? `${styleFor}: ${parseFloat(
                MOBdimensionValue
            )}${MOBdimensionUnit};` : `${styleFor}: ${parseFloat(
                MOBdimensionValue
            )};` : ""
        }
`;

    return {
        textControlStylesDesktop,
        textControlStylesTab,
        textControlStylesMobile,
    };
};
