// function to generate responsive range controller attributes for multiple range control based on the array of prefix
export const generateResponsiveSelectControlAttributes = (controlName) => {
  return {
    [`${controlName}Option`]: {
      type: "string",
    },
    [`TAB${controlName}Option`]: {
      type: "string",
    },
    [`MOB${controlName}Option`]: {
      type: "string",
    },
  };
};

// function to generate responsive range control styles for an element based on it's prefix
export const generateResponsiveSelectControlStyles = ({
  controlName,
  property,
  attributes,
}) => {
  const {
    [`${controlName}Option`]: desktopOption,
    [`TAB${controlName}Option`]: tabOption,
    [`MOB${controlName}Option`]: mobileOption,
  } = attributes;

  const selectStylesDesktop = `${
    desktopOption ? `${property}: ${desktopOption};` : ""
  }`;
  const selectStylesTab = `${tabOption ? `${property}: ${tabOption};` : ""}`;
  const selectStylesMobile = `${
    mobileOption ? `${property}: ${mobileOption};` : ""
  }`;

  return {
    selectStylesDesktop,
    selectStylesTab,
    selectStylesMobile,
  };
};
