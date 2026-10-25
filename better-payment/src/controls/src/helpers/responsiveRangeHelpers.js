// function to generate responsive range controller attributes for multiple range control based on the array of prefix
export const generateResponsiveRangeAttributes = (
    controlName,
    defaults = {}
) => {
    const {
        defaultRange,
        noUnits,
        defaultUnit = "px",
        defaultRangeTAB,
        defaultRangeMOB,
        defaultIsAuto = false // Add default value
    } = defaults;

    // Range attributes
    const desktop = defaultRange
        ? {
            [`${controlName}Range`]: {
                type: "number",
                default: defaultRange,
            },
        }
        : {
            [`${controlName}Range`]: {
                type: "number",
            },
        };
    const tab = defaultRange
        ? {
            [`TAB${controlName}Range`]: {
                type: "number",
                default: defaultRangeTAB,
            },
        }
        : {
            [`TAB${controlName}Range`]: {
                type: "number",
            },
        };
    const mobile = defaultRange
        ? {
            [`MOB${controlName}Range`]: {
                type: "number",
                default: defaultRangeMOB,
            },
        }
        : {
            [`MOB${controlName}Range`]: {
                type: "number",
            },
        };

    const units =
        noUnits === true
            ? {}
            : {
                [`${controlName}Unit`]: {
                    type: "string",
                    default: defaultUnit,
                },
                [`TAB${controlName}Unit`]: {
                    type: "string",
                    default: "px",
                },
                [`MOB${controlName}Unit`]: {
                    type: "string",
                    default: "px",
                },
            };

    // Add the auto attribute
    const autoAttribute = {
        [`${controlName}IsAuto`]: {
            type: "boolean",
            default: defaultIsAuto,
        },
    };

    return {
        ...desktop,
        ...tab,
        ...mobile,
        ...units,
        ...autoAttribute, // Include the auto attribute
    };
};

// function to generate responsive range control styles for an element based on it's prefix
export const generateResponsiveRangeStyles = ({
    controlName,
    property,
    attributes,
    customUnit,
    noUnits,
}) => {
    let desktopSizeUnit;
    let TABsizeUnit;
    let MOBsizeUnit;

    if (noUnits) {
        desktopSizeUnit = TABsizeUnit = MOBsizeUnit = "";
    } else if (!customUnit) {
        desktopSizeUnit = attributes[`${controlName}Unit`];
        TABsizeUnit = attributes[`TAB${controlName}Unit`];
        MOBsizeUnit = attributes[`MOB${controlName}Unit`];
    } else {
        desktopSizeUnit = TABsizeUnit = MOBsizeUnit = customUnit;
    }

    // Units that should be capped at 100 for range values
    // Extend this list as needed (e.g., "%", "em", "vm").
    const cappedUnits = new Set(["%", "em", "vm"]);
    const capAt100IfNeeded = (value, unit) =>
        cappedUnits.has(unit) && value > 100 ? 100 : value;

    // Determine the effective unit that will be used in output
    const effectiveDesktopUnit = customUnit || desktopSizeUnit;
    const effectiveTABUnit = customUnit || TABsizeUnit;
    const effectiveMOBUnit = customUnit || MOBsizeUnit;


    const {
        [`${controlName}Range`]: desktopRange,
        [`TAB${controlName}Range`]: TABrange,
        [`MOB${controlName}Range`]: MOBrange,
        [`${controlName}IsAuto`]: isAuto,
    } = attributes;

    const propertyName = property === null ? "" : property + ":";
    const endValue = property === null ? "" : ";";

    // If auto is enabled, set auto for all devices
    if (isAuto) {
        return {
            rangeStylesDesktop: propertyName + 'auto',
            rangeStylesTab: propertyName + 'auto',
            rangeStylesMobile: propertyName + 'auto',
        };
    }

    const rangeStylesDesktop =
        desktopRange || desktopRange === 0
            ? propertyName +
            capAt100IfNeeded(desktopRange, effectiveDesktopUnit) +
            effectiveDesktopUnit + endValue
            : "";
    const rangeStylesTab =
        TABrange || TABrange === 0
            ? propertyName +
            capAt100IfNeeded(TABrange, effectiveTABUnit) +
            effectiveTABUnit + endValue
            : "";
    const rangeStylesMobile =
        MOBrange || MOBrange === 0
            ? propertyName +
            capAt100IfNeeded(MOBrange, effectiveMOBUnit) +
            effectiveMOBUnit + endValue
            : "";

    return {
        rangeStylesDesktop,
        rangeStylesTab,
        rangeStylesMobile,
    };
};
