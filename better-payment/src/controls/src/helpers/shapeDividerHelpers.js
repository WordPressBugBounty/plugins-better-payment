import { generateResponsiveRangeAttributes, generateResponsiveRangeStyles } from "./responsiveRangeHelpers";

// function to generate responsive range controller attributes for multiple range control based on the array of prefix
export const generateShapeDividerAttributes = (controlName, defaults = {}) => {
  const { dividerType, defaultColor } = defaults;
  const desktop = {
    [`${controlName}Type`]: {
      type: "string",
      default: dividerType ? dividerType : "",
    },
    [`${controlName}Color`]: {
      type: "string",
      default: defaultColor ? defaultColor : "",
    },
    [`${controlName}Flip`]: {
      type: "boolean",
      default: false,
    },
    [`${controlName}Invert`]: {
      type: "boolean",
      default: false,
    },
    [`${controlName}BringFront`]: {
      type: "boolean",
      default: false,
    },
  };

  return {
    ...desktop,
    ...generateResponsiveRangeAttributes(`${controlName}Width`, {
      defaultRange: 100,
      noUnits: true,
    }),
    ...generateResponsiveRangeAttributes(`${controlName}Height`, {
      noUnits: true,
    }),
  };
};

// function to generate responsive range control styles for an element based on it's prefix
export const generateShapeDividerStyles = ({ controlName, position, attributes }) => {
  const {
    [`${controlName}Color`]: dividerColor,
    [`${controlName}Flip`]: dividerFlip,
    [`${controlName}BringFront`]: dividerBringFront,
    blockId,
  } = attributes;

  const {
    rangeStylesDesktop: shapeWidthDesktop,
    rangeStylesTab: shapeWidthTab,
    rangeStylesMobile: shapeWidthMobile,
  } = generateResponsiveRangeStyles({
    controlName: `${controlName}Width`,
    property: "",
    attributes,
    customUnit: "px",
  });

  const {
    rangeStylesDesktop: shapeHeightDesktop,
    rangeStylesTab: shapeHeightTab,
    rangeStylesMobile: shapeHeightMobile,
  } = generateResponsiveRangeStyles({
    controlName: `${controlName}Height`,
    property: "height",
    attributes,
    customUnit: "px",
  });

  const desktopShapeStyle = `
    ${
      position == "top"
        ? `.eb-parent-${blockId} .eb-shape-divider-top .eb-shape-divider-fill {
            ${dividerColor ? `fill: ${dividerColor};` : ""}
        }

         .eb-parent-${blockId} .eb-shape-divider-top svg {
            ${shapeWidthDesktop.length != 0 ? `width: calc(${shapeWidthDesktop.replace(/\D/g, "")}% + 1.3px);` : ""}
            ${shapeHeightDesktop.length != 0 ? shapeHeightDesktop : ""}
            ${dividerFlip ? "transform: translateX(-50%) rotateY(180deg);" : ""}
        }
    ${
      dividerBringFront
        ? `
             .eb-parent-${blockId} .eb-shape-divider-top {
                z-index: 2 !important;
                pointer-events: none;
             }
        `
        : ""
    }`
        : ""
    }

    ${
      position == "bottom"
        ? `
     .eb-parent-${blockId} .eb-shape-divider-bottom .eb-shape-divider-fill {
        ${dividerColor ? `fill: ${dividerColor};` : ""}
    }
    .eb-parent-${blockId} .eb-shape-divider-bottom svg {
        ${shapeWidthDesktop.length != 0 ? `width: calc(${shapeWidthDesktop.replace(/\D/g, "")}% + 1.3px);` : ""}
        ${shapeHeightDesktop.length != 0 ? shapeHeightDesktop : ""}
        ${dividerFlip ? "transform: translateX(-50%) rotateY(180deg);" : ""}
    }
    ${
      dividerBringFront
        ? `
            .eb-parent-${blockId} .eb-shape-divider-bottom {
            z-index: 2 !important;
            pointer-events: none;
            }
    `
        : ""
    }
    `
        : ""
    }
    `;

  const tabShapeStyle = `
    ${
      position == "top"
        ? `.eb-parent-${blockId} .eb-shape-divider-top svg {
            ${shapeWidthTab.length != 0 ? `width: calc(${shapeWidthTab.replace(/\D/g, "")}% + 1.3px);` : ""}
            ${shapeHeightTab.length != 0 ? shapeHeightTab : ""}
        }`
        : ""
    }

    ${
      position == "bottom"
        ? `.eb-parent-${blockId} .eb-shape-divider-bottom svg {
            ${shapeWidthTab.length != 0 ? `width: calc(${shapeWidthTab.replace(/\D/g, "")}% + 1.3px);` : ""}
            ${shapeHeightTab.length != 0 ? shapeHeightTab : ""}
        }`
        : ""
    }

    `;

  const mobShapeStyle = `
    ${
      position == "top"
        ? `.eb-parent-${blockId} .eb-shape-divider-top svg {
            ${shapeWidthMobile.length != 0 ? `width: calc(${shapeWidthMobile.replace(/\D/g, "")}% + 1.3px);` : ""}
            ${shapeHeightMobile.length != 0 ? shapeHeightMobile : ""}
        }`
        : ""
    }

    ${
      position == "bottom"
        ? `.eb-parent-${blockId} .eb-shape-divider-bottom svg {
            ${shapeWidthMobile.length != 0 ? `width: calc(${shapeWidthMobile.replace(/\D/g, "")}% + 1.3px);` : ""}
            ${shapeHeightMobile.length != 0 ? shapeHeightMobile : ""}
        }`
        : ""
    }
    `;

  return {
    desktopShapeStyle,
    tabShapeStyle,
    mobShapeStyle,
  };
};
