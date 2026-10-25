import { __ } from "@wordpress/i18n";
import { camelCase, upperFirst } from "lodash";

import {
  generateBorderShadowAttributes,
  generateDimensionsAttributes,
  generateResponsiveRangeAttributes,
  generateTypographyAttributes,
  generateResponsiveAlignAttributes,
} from "../../index";

import {
  IMAGE_BORDER,
  IMAGE_WIDTH,
  IMAGE_HEIGHT,
  IMAGE_SIZE,
  IMAGE_RADIUS,
  IMAGE_PADDING,
  IMAGE_MARGIN,
  CAPTION_MARGIN,
  CAPTION_PADDING,
  CAPTION_TYPOGRAPHY,
  CAPTION_WIDTH,
  TEXT_ALIGNMENT,
  IMAGE_ALIGNMENT,
} from "./constants";

const imgAttributes = {
  imageUrl: {
    type: "string",
  },
  imageAlt: {
    type: "string",
  },
  imageId: {
    type: "number",
  },
  imageCaption: {
    type: "string",
  },
  imgObjectFit: {
    type: "string",
    default: "",
  },
  imgObjectPosition: {
    type: "string",
    default: "center center",
  },
  imageSize: {
    type: "string",
    default: "",
  },
  imageBlur: {
    type: "number",
    default: 0,
  },
  imageBrightness: {
    type: "number",
    default: 1,
  },
  imageContrast: {
    type: "number",
    default: 1,
  },
  imageGrayscale: {
    type: "number",
    default: 0,
  },
  imageHueRotate: {
    type: "number",
    default: 0,
  },
  imageInvert: {
    type: "number",
    default: 0,
  },
  imageOpacity: {
    type: "number",
    default: 1,
  },
  imageSaturate: {
    type: "number",
    default: 1,
  },
  imageSepia: {
    type: "number",
    default: 0,
  },
  figureTag: {
    type: "boolean",
    default: false,
  },
  imageAutoHeight: {
    type: "boolean",
    default: true,
  },
  displayCaption: {
    type: "boolean",
    default: false,
  },
  captionStyle: {
    type: "string",
    default: "caption-style-1",
  },
  captionColor: {
    type: "string",
    default: "#101828",
  },
  captionBGColor: {
    type: "string",
    default: "#f9fafb",
  },
  textAlign: {
    type: "string",
    default: "center",
  },
  horizontalAlign: {
    type: "string",
    default: "center",
  },
  verticalAlign: {
    type: "string",
    default: "bottom",
  },
  verticalAlignCap2: {
    type: "string",
    default: "bottom",
  },
  stylePreset: {
    type: "string",
    default: "rounded",
  },
  complexStyle: {
    type: "boolean",
    default: false,
  },
  enableLink: {
    type: "boolean",
    default: false,
  },
  imageLink: {
    type: "string",
    default: "",
  },
  openInNewTab: {
    type: "boolean",
    default: false,
  },
  captionColor: {
    type: "string",
    default: "var(--eb-global-primary-color)",
  },
  captionBGColor: {
    type: "string",
    default: "var(--eb-global-background-color)",
  },
  // hoverEffect: {
  //     type: "string",
  //     default: "no-effect",
  // },
};

const applyAttrName = (attributes, attrName = "") => {
  if (!attrName) {
    return attributes;
  }

  return Object.keys(attributes).reduce((newAttributes, key) => {
    const prefixedKey = `${attrName}${upperFirst(camelCase(key))}`;
    newAttributes[prefixedKey] = { ...attributes[key] };
    return newAttributes;
  }, {});
};

export const addAttributes = (props) => {
  const {
    attrObject = {},
    attrName = "",
    hasBorder = true,
    border = { key: IMAGE_BORDER },
    hasPadding = true,
    padding = {
      key: IMAGE_PADDING,
    },
    hasMargin = true,
    margin = {
      key: IMAGE_MARGIN,
    },
    hasWidth = true,
    width = {
      key: IMAGE_WIDTH,
    },
    hasHeight = true,
    height = {
      key: IMAGE_HEIGHT,
    },
    useImageSize = false,
    imageSize = {
      key: IMAGE_SIZE,
    },
    hasRadius = false,
    radius = {
      key: IMAGE_RADIUS,
    },
    useImageAlign = false,
    imageAlign = {
      key: IMAGE_ALIGNMENT,
      default: {
        defaultAlign: "center",
        defaultTabAlign: "center",
        defaultMobileAlign: "center",
      },
    },
  } = props;
  let renamedObject = {};

  for (const [oldKey, newKey] of Object.entries(attrObject)) {
    if (imgAttributes.hasOwnProperty(oldKey)) {
      renamedObject[newKey] = imgAttributes[oldKey];
    }
  }

  let remainingAttributes = {};
  for (const [key, value] of Object.entries(imgAttributes)) {
    if (!attrObject.hasOwnProperty(key)) {
      remainingAttributes[key] = value;
    }
  }

  if (attrName.length > 0) {
    remainingAttributes = applyAttrName(remainingAttributes, attrName);
  }

  let borderObject = {};
  if (hasBorder) {
    borderObject = generateBorderShadowAttributes(border?.key, border?.default);
  }

  let paddingObject = {};
  if (hasPadding) {
    paddingObject = generateDimensionsAttributes(
      padding?.key,
      padding?.default
    );
  }

  let marginObject = {};
  if (hasMargin) {
    marginObject = generateDimensionsAttributes(margin?.key, margin?.default);
  }

  let fixedWidth = {};
  if (hasWidth) {
    fixedWidth = generateResponsiveRangeAttributes(width?.key, width?.default);
  }

  let fixedHeight = {};
  if (hasHeight) {
    fixedHeight = generateResponsiveRangeAttributes(
      height?.key,
      height?.default
    );
  }

  let fixedImageSize = {};
  if (useImageSize) {
    fixedImageSize = generateResponsiveRangeAttributes(
      imageSize?.key,
      imageSize?.default
    );
  }

  let fixedRadius = {};
  if (hasRadius) {
    fixedRadius = generateResponsiveRangeAttributes(
      radius?.key,
      radius?.default
    );
  }

  let imageAlignObject = {};
  if (useImageAlign) {
    imageAlignObject = generateResponsiveAlignAttributes(
      imageAlign?.key,
      imageAlign?.default
    );
  }

  let captionObject = {};
  captionObject = {
    ...generateDimensionsAttributes(CAPTION_MARGIN),
    ...generateDimensionsAttributes(CAPTION_PADDING),
    ...generateTypographyAttributes(CAPTION_TYPOGRAPHY, {
      fontSize: 13,
    }),
    ...generateResponsiveRangeAttributes(CAPTION_WIDTH),
    ...generateResponsiveAlignAttributes(TEXT_ALIGNMENT, {
      defaultAlign: "center",
      defaultTabAlign: "center",
      defaultMobileAlign: "center",
    }),
  };

  renamedObject = {
    ...renamedObject,
    ...remainingAttributes,
    ...borderObject,
    ...paddingObject,
    ...marginObject,
    ...fixedWidth,
    ...fixedHeight,
    ...fixedImageSize,
    ...fixedRadius,
    ...imageAlignObject,
    ...captionObject,
  };

  return renamedObject;
};