/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
/**
 * Internal depencencies
 */
import {
  IMAGE_BORDER,
  IMAGE_PADDING,
  IMAGE_MARGIN,
  IMAGE_WIDTH,
  IMAGE_SIZE,
  IMAGE_RADIUS,
  IMAGE_HEIGHT,
  CAPTION_MARGIN,
  CAPTION_PADDING,
  CAPTION_TYPOGRAPHY,
  CAPTION_WIDTH,
  TEXT_ALIGNMENT,
  IMAGE_ALIGN,
  IMAGE_ALIGNMENT,
} from "./constants";
import { camelCase, upperFirst } from "lodash";

import {
  generateTypographyStyles,
  generateResponsiveRangeStyles,
  useBlockAttributes,
  generateDimensionsControlStyles,
  generateBackgroundControlStyles,
  generateBorderShadowStyles,
  generateResponsiveAlignStyles,
} from "../../index";

export const Style = ({
  blockId,
  wrapperClass,
  imageAttrProps,
  attrPrefix = "",
  className = "",
  hasFilter = true,
  width = IMAGE_WIDTH,
  height = IMAGE_HEIGHT,
  useImageSize = false,
  imageSize = IMAGE_SIZE,
  radius = IMAGE_RADIUS,
  border = IMAGE_BORDER,
  padding = IMAGE_PADDING,
  margin = IMAGE_MARGIN,
  useImageAlign = false,
  imageAlign = IMAGE_ALIGNMENT,
  imageAlignProperty = "justify-content",
}) => {
  const attributes = useBlockAttributes();

  const getAttrName = (attrKey) => {
    const finalKey =
      imageAttrProps && imageAttrProps[attrKey]
        ? imageAttrProps[attrKey]
        : attrKey;

    if (imageAttrProps && imageAttrProps[attrKey]) {
      return finalKey;
    }

    return attrPrefix
      ? `${attrPrefix}${upperFirst(camelCase(finalKey))}`
      : finalKey;
  };

  const attrValue = (attrKey) => attributes[getAttrName(attrKey)];

  if (blockId === "" || wrapperClass === "") {
    return;
  }

  const customClass = className?.length > 0 ? "." + className : "";
  // Default values in case of undefined
  const filterBlur =
    attrValue("imageBlur") !== undefined ? attrValue("imageBlur") : 0;
  const filterBrightness =
    attrValue("imageBrightness") !== undefined
      ? attrValue("imageBrightness")
      : 1;
  const filterContrast =
    attrValue("imageContrast") !== undefined ? attrValue("imageContrast") : 1;
  const filterGrayscale =
    attrValue("imageGrayscale") !== undefined ? attrValue("imageGrayscale") : 0;
  const filterHueRotate =
    attrValue("imageHueRotate") !== undefined ? attrValue("imageHueRotate") : 0;
  const filterInvert =
    attrValue("imageInvert") !== undefined ? attrValue("imageInvert") : 0;
  const filterOpacity =
    attrValue("imageOpacity") !== undefined ? attrValue("imageOpacity") : 1;
  const filterSaturate =
    attrValue("imageSaturate") !== undefined ? attrValue("imageSaturate") : 1;
  const filterSepia =
    attrValue("imageSepia") !== undefined ? attrValue("imageSepia") : 0;

  const filterStyle = `
  blur(${filterBlur}px)
  brightness(${filterBrightness})
  contrast(${filterContrast})
  grayscale(${filterGrayscale})
  hue-rotate(${filterHueRotate}deg)
  invert(${filterInvert})
  opacity(${filterOpacity})
  saturate(${filterSaturate})
  sepia(${filterSepia})
`.trim();

  const {
    rangeStylesDesktop: imgWidthDesktop,
    rangeStylesTab: imgWidthTab,
    rangeStylesMobile: imgWidthMobile,
  } = generateResponsiveRangeStyles({
    controlName: width,
    property: "width",
    attributes,
  });

  const {
    rangeStylesDesktop: imgHeightDesktop,
    rangeStylesTab: imgHeightTab,
    rangeStylesMobile: imgHeightMobile,
  } = generateResponsiveRangeStyles({
    controlName: height,
    property: "height",
    attributes,
  });

  const {
    rangeStylesDesktop: imgSizeWidthDesktop,
    rangeStylesTab: imgSizeWidthTab,
    rangeStylesMobile: imgSizeWidthMobile,
  } = generateResponsiveRangeStyles({
    controlName: imageSize,
    property: "width",
    attributes,
  });

  const {
    rangeStylesDesktop: imgSizeHeightDesktop,
    rangeStylesTab: imgSizeHeightTab,
    rangeStylesMobile: imgSizeHeightMobile,
  } = generateResponsiveRangeStyles({
    controlName: imageSize,
    property: "height",
    attributes,
  });

  const {
    rangeStylesDesktop: imgRadiusDesktop,
    rangeStylesTab: imgRadiusTab,
    rangeStylesMobile: imgRadiusMobile,
  } = generateResponsiveRangeStyles({
    controlName: radius,
    property: "border-radius",
    attributes,
  });

  const {
    dimensionStylesDesktop: imgPaddingDesktop,
    dimensionStylesTab: imgPaddingTab,
    dimensionStylesMobile: imgPaddingMobile,
  } = generateDimensionsControlStyles({
    controlName: padding,
    styleFor: "padding",
    attributes,
  });

  const {
    dimensionStylesDesktop: imgMarginDesktop,
    dimensionStylesTab: imgMarginTab,
    dimensionStylesMobile: imgMarginMobile,
  } = generateDimensionsControlStyles({
    controlName: margin,
    styleFor: "margin",
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

  /* Caption Margin */
  const {
    dimensionStylesDesktop: captionMarginDesktop,
    dimensionStylesTab: captionMarginTab,
    dimensionStylesMobile: captionMarginMobile,
  } = generateDimensionsControlStyles({
    controlName: CAPTION_MARGIN,
    styleFor: "margin",
    attributes,
  });

  /* Caption Padding */
  const {
    dimensionStylesDesktop: captionPaddingDesktop,
    dimensionStylesTab: captionPaddingTab,
    dimensionStylesMobile: captionPaddingMobile,
  } = generateDimensionsControlStyles({
    controlName: CAPTION_PADDING,
    styleFor: "padding",
    attributes,
  });

  // Caption Typography
  const {
    typoStylesDesktop: captionTypographyDesktop,
    typoStylesTab: captionTypographyTab,
    typoStylesMobile: captionTypographyMobile,
  } = generateTypographyStyles({
    attributes,
    prefixConstant: CAPTION_TYPOGRAPHY,
  });

  const {
    rangeStylesDesktop: captionWidthDesktop,
    rangeStylesTab: captionWidthTab,
    rangeStylesMobile: captionWidthMobile,
  } = generateResponsiveRangeStyles({
    controlName: CAPTION_WIDTH,
    property: "width",
    attributes,
  });

  const {
    alignStylesDesktop: textAlignDesktop,
    alignStylesTab: textAlignTab,
    alignStylesMobile: textAlignMobile,
  } = generateResponsiveAlignStyles({
    controlName: TEXT_ALIGNMENT,
    property: "text-align",
    attributes,
  });

  const {
    alignStylesDesktop: imageAlignDesktop,
    alignStylesTab: imageAlignTab,
    alignStylesMobile: imageAlignMobile,
  } = generateResponsiveAlignStyles({
    controlName: imageAlign,
    property: imageAlignProperty,
    attributes,
  });

  const desktopStyles = `
        ${
          useImageAlign
            ? `
          .${wrapperClass}.${blockId} .eb-image-wrapper {
              ${imageAlignDesktop}
          }
          `
            : ""
        }
        .${wrapperClass}.${blockId} .eb-image-wrapper .eb-image-wrapper-inner${customClass} {
            ${imgPaddingDesktop}
            ${imgMarginDesktop}
            ${!useImageSize ? imgWidthDesktop : ""}
            ${useImageSize ? imgSizeWidthDesktop : ""}
            ${useImageSize ? imgSizeHeightDesktop : ""}
            ${!attrValue("complexStyle") ? bdShadowStyesDesktop : ""}
            ${!attrValue("complexStyle") ? imgRadiusDesktop : ""}
            ${
              !useImageSize && attrValue("imageAutoHeight")
                ? `height:auto;`
                : imgHeightDesktop
            }
            transition: ${bdShadowTransitionStyle};
        }
	
        .${wrapperClass}.${blockId} .eb-image-wrapper .eb-image-wrapper-inner${customClass} img {
            ${
              attrValue("imgObjectFit")
                ? `object-fit: ${attrValue("imgObjectFit")};`
                : ""
            }
            ${
              attrValue("imgObjectFit")
                ? `object-position: ${attrValue("imgObjectPosition")};`
                : ""
            }
            ${hasFilter ? `filter: ${filterStyle};` : ""}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper:hover .eb-image-wrapper-inner${customClass} img {
            ${bdShadowStylesHoverDesktop}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper figcaption {
            color: ${attrValue("captionColor")};
            ${textAlignDesktop}
            ${captionMarginDesktop}
            ${captionPaddingDesktop}
            ${captionTypographyDesktop}
            ${captionWidthDesktop}
        }
        .${wrapperClass}.${blockId} .eb-image-wrapper .eb-image-wrapper-inner.eb-img-caption-style-1 figcaption {
          background: ${attrValue("captionBGColor")};
        }
`;

  const tabStyles = `
          ${
            useImageAlign
              ? `
          .${wrapperClass}.${blockId} .eb-image-wrapper {
              ${imageAlignTab}
          }
          `
              : ""
          }
        .${wrapperClass}.${blockId} .eb-image-wrapper .eb-image-wrapper-inner${customClass} {
            ${imgPaddingTab}
            ${imgMarginTab}
            ${!useImageSize ? imgWidthTab : ""}
            ${useImageSize ? imgSizeWidthTab : ""}
            ${useImageSize ? imgSizeHeightTab : ""}
            ${!useImageSize && attrValue("imageAutoHeight") ? "" : imgHeightTab}
            ${!attrValue("complexStyle") ? bdShadowStyesTab : ""}
            ${!attrValue("complexStyle") ? imgRadiusTab : ""}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper:hover .eb-image-wrapper-inner${customClass} img {
            ${bdShadowStylesHoverTab}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper figcaption {
            ${textAlignTab}
            ${captionMarginTab}
            ${captionPaddingTab}
            ${captionTypographyTab}
            ${captionWidthTab}
        }
`;

  const mobileStyles = `
          ${
            useImageAlign
              ? `
          .${wrapperClass}.${blockId} .eb-image-wrapper {
              ${imageAlignMobile}
          }
          `
              : ""
          }
        .${wrapperClass}.${blockId} .eb-image-wrapper .eb-image-wrapper-inner${customClass} {
            ${imgPaddingMobile}
            ${imgMarginMobile}
            ${!useImageSize ? imgWidthMobile : ""}
            ${useImageSize ? imgSizeWidthMobile : ""}
            ${useImageSize ? imgSizeHeightMobile : ""}
            ${
              !useImageSize && attrValue("imageAutoHeight")
                ? ""
                : imgHeightMobile
            }
            ${!attrValue("complexStyle") ? bdShadowStyesMobile : ""}
            ${!attrValue("complexStyle") ? imgRadiusMobile : ""}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper:hover .eb-image-wrapper-inner${customClass} img {
            ${bdShadowStylesHoverMobile}
        }

        .${wrapperClass}.${blockId} .eb-image-wrapper figcaption {
            ${textAlignMobile}
            ${captionMarginMobile}
            ${captionPaddingMobile}
            ${captionTypographyMobile}
            ${captionWidthMobile}
        }
`;

  return {
    imgDesktopStyle: desktopStyles,
    imgTabStyle: tabStyles,
    imgMobileStyle: mobileStyles,
  };
};