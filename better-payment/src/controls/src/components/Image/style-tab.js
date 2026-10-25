/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  BaseControl,
  SelectControl,
  RangeControl,
  ToggleControl,
  PanelRow,
  __experimentalDivider as Divider,
} from "@wordpress/components";

/**
 * Internal depencencies
 */
import {
  IMAGE_WIDTH,
  IMAGE_HEIGHT,
  IMAGE_SIZE,
  IMAGE_RADIUS,
  IMAGE_BORDER,
  IMAGE_PADDING,
  IMAGE_MARGIN,
  OBJECT_POSITION,
  OBJECT_FIT,
  TEXT_ALIGN,
  VERTICAL_ALIGN,
  VERTICAL_ALIGN_CAP_2,
  HORIZONTAL_ALIGN,
  CAPTION_MARGIN,
  CAPTION_PADDING,
  CAPTION_TYPOGRAPHY,
  CAPTION_WIDTH,
  UNIT_TYPES,
  TEXT_ALIGNMENT,
} from "./constants";

import {
  InspectorPanel,
  BorderShadowControl,
  ResponsiveDimensionsControl,
  ResponsiveRangeController,
  ColorControl,
  TypographyDropdown,
  ButtonGroupControl,
  ResponsiveAlignControl,
} from "../..";
import { useBlockSetAttributes, useBlockAttributes } from "../../index";
import { camelCase, upperFirst } from "lodash";

export const StyleTab = (props) => {
  const {
    label,
    imageAttrProps,
    attrPrefix = "",
    hasBorder = true,
    border = IMAGE_BORDER,
    hasPadding = true,
    padding = IMAGE_PADDING,
    hasMargin = true,
    margin = IMAGE_MARGIN,
    hasWidth = true,
    width = IMAGE_WIDTH,
    hasHeight = true,
    height = IMAGE_HEIGHT,
    hasRadius = false,
    useImageSize = false,
    imageSize = IMAGE_SIZE,
    radius = IMAGE_RADIUS,
    hasFilter = true,
    hasAutoHeight = false,
  } = props;

  const attributes = useBlockAttributes();
  const setAttributes = useBlockSetAttributes();

  const {
    resOption,
  } = attributes;

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

  return (
    <>
      <InspectorPanel.PanelBody
        title={label ? label : __("Image Styles", "essential-blocks")}
        initialOpen={true}
      >
        <>
          {hasWidth && (
            <>
              <ResponsiveRangeController
                baseLabel={__("Width", "essential-blocks")}
                controlName={width}
                min={0}
                max={2500}
              />
            </>
          )}
          {hasAutoHeight && (
            <ToggleControl
              label={__("Auto Height", "essential-blocks")}
              checked={attrValue("imageAutoHeight")}
              onChange={() => {
                setAttributes({
                  [getAttrName("imageAutoHeight")]:
                    !attrValue("imageAutoHeight"),
                });
              }}
            />
          )}
          {hasHeight && !attrValue("imageAutoHeight") && (
            <>
              <ResponsiveRangeController
                baseLabel={__("Height", "essential-blocks")}
                controlName={height}
                min={0}
                max={2500}
              />
            </>
          )}
          {useImageSize && (
            <>
              <ResponsiveRangeController
                baseLabel={__("Image Size", "essential-blocks")}
                controlName={imageSize}
                min={0}
                max={2500}
              />
            </>
          )}
          <SelectControl
            label={__("Object Fit", "essential-blocks")}
            value={attrValue("imgObjectFit")}
            options={OBJECT_FIT}
            onChange={(value) =>
              setAttributes({
                [getAttrName("imgObjectFit")]: value,
              })
            }
          />

          {!attrValue("imgObjectFit") ||
            (attrValue("imgObjectFit") !== "fill" && (
              <SelectControl
                label={__("Object Position", "essential-blocks")}
                value={attrValue("imgObjectPosition")}
                options={OBJECT_POSITION}
                onChange={(value) =>
                  setAttributes({
                    [getAttrName("imgObjectPosition")]: value,
                  })
                }
              />
            ))}
          {hasRadius && (
            <>
              <ResponsiveRangeController
                baseLabel={__("Radius", "essential-blocks")}
                controlName={radius}
                min={0}
                max={500}
              />
            </>
          )}
          {hasBorder && !attrValue("complexStyle") && (
            <>
              <Divider />
              <BaseControl>
                <h3 className="eb-control-title">
                  {__("Border", "essential-blocks")}
                </h3>
              </BaseControl>
              <BorderShadowControl controlName={border} noBorderRadius />
            </>
          )}
          {hasBorder && (
            <>
              {attrValue("complexStyle") && (
                <PanelRow>
                  <em>
                    Border Style doesn't support for "{attrValue("stylePreset")}{" "}
                    style".
                  </em>
                </PanelRow>
              )}
            </>
          )}

          {hasPadding && (
            <>
              <ResponsiveDimensionsControl
                controlName={padding}
                baseLabel={__("Padding", "essential-blocks")}
              />
            </>
          )}
          {hasMargin && (
            <>
              <ResponsiveDimensionsControl
                controlName={margin}
                baseLabel={__("Margin", "essential-blocks")}
              />
            </>
          )}
          {hasFilter && (
            <InspectorPanel.PanelBody
              title={__("Image Filters", "essential-blocks")}
              initialOpen={true}
            >
              <RangeControl
                label={__("Blur", "essential-blocks")}
                value={attrValue("imageBlur")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageBlur")]: value })
                }
                step={0.1}
                max={10}
                allowReset={true}
              />
              <RangeControl
                label={__("Brightness", "essential-blocks")}
                value={attrValue("imageBrightness")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageBrightness")]: value })
                }
                step={0.1}
                max={3}
                allowReset={true}
              />
              <RangeControl
                label={__("Contrast", "essential-blocks")}
                value={attrValue("imageContrast")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageContrast")]: value })
                }
                step={0.1}
                max={3}
                allowReset={true}
              />
              <RangeControl
                label={__("Grayscale", "essential-blocks")}
                value={attrValue("imageGrayscale")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageGrayscale")]: value })
                }
                step={0.01}
                max={1}
                allowReset={true}
              />
              <RangeControl
                label={__("Hue Rotate", "essential-blocks")}
                value={attrValue("imageHueRotate")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageHueRotate")]: value })
                }
                step={1}
                max={360}
                allowReset={true}
              />
              <RangeControl
                label={__("Invert", "essential-blocks")}
                value={attrValue("imageInvert")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageInvert")]: value })
                }
                step={0.01}
                max={1}
                allowReset={true}
              />
              <RangeControl
                label={__("Opacity", "essential-blocks")}
                value={attrValue("imageOpacity")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageOpacity")]: value })
                }
                step={0.01}
                max={1}
                allowReset={true}
              />
              <RangeControl
                label={__("Saturate", "essential-blocks")}
                value={attrValue("imageSaturate")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageSaturate")]: value })
                }
                step={0.1}
                max={3}
                allowReset={true}
              />
              <RangeControl
                label={__("Sepia", "essential-blocks")}
                value={attrValue("imageSepia")}
                onChange={(value) =>
                  setAttributes({ [getAttrName("imageSepia")]: value })
                }
                step={0.01}
                max={1}
                allowReset={true}
              />
            </InspectorPanel.PanelBody>
          )}
        </>
      </InspectorPanel.PanelBody>
      {attrValue("displayCaption") && (
        <InspectorPanel.PanelBody
          title={__("Caption Styles", "essential-blocks")}
        >
          <ColorControl
            label={__("Text Color", "essential-blocks")}
            color={attrValue("captionColor")}
            attributeName={getAttrName("captionColor")}
          />
          {attrValue("captionStyle") != "caption-style-2" && (
            <ColorControl
              label={__("Background Color", "essential-blocks")}
              color={attrValue("captionBGColor")}
              attributeName={getAttrName("captionBGColor")}
              isGradient={true}
            />
          )}

          <TypographyDropdown
            baseLabel={__("Typography", "essential-blocks")}
            typographyPrefixConstant={CAPTION_TYPOGRAPHY}
          />

          <ResponsiveRangeController
            baseLabel={__("Width", "essential-blocks")}
            controlName={CAPTION_WIDTH}
            units={UNIT_TYPES}
            min={0}
            max={300}
            step={1}
          />

          <ResponsiveAlignControl
            baseLabel={__("Text Align", "essential-blocks")}
            controlName={TEXT_ALIGNMENT}
            options={TEXT_ALIGN}
            resOption={resOption}
          />

          {attrValue("captionStyle") === "caption-style-1" && (
            <>
              <ButtonGroupControl
                label={__("Horizontal Align", "essential-blocks")}
                attrName={getAttrName("horizontalAlign")}
                options={HORIZONTAL_ALIGN}
                currentValue={attrValue("horizontalAlign")}
              />

              <ButtonGroupControl
                label={__("Vertical Align", "essential-blocks")}
                attrName={getAttrName("verticalAlign")}
                options={VERTICAL_ALIGN}
                currentValue={attrValue("verticalAlign")}
              />
            </>
          )}

          {attrValue("captionStyle") === "caption-style-2" && (
            <ButtonGroupControl
              label={__("Vertical Align", "essential-blocks")}
              controlName={getAttrName("verticalAlignCap2")}
              options={VERTICAL_ALIGN_CAP_2}
              currentValue={attrValue("verticalAlignCap2")}
            />
          )}

          <ResponsiveDimensionsControl
            controlName={CAPTION_MARGIN}
            baseLabel={__("Margin", "essential-blocks")}
          />

          <ResponsiveDimensionsControl
            controlName={CAPTION_PADDING}
            baseLabel={__("Padding", "essential-blocks")}
          />
        </InspectorPanel.PanelBody>
      )}
    </>
  );
};
