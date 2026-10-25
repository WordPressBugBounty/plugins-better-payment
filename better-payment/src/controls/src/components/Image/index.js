/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { RichText } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { camelCase, upperFirst } from "lodash";

/**
 * Internal dependencies
 */
import { GeneralTab } from "./general";
import { StyleTab } from "./style-tab.js";
import { Style } from "./style-js.js";
import { addAttributes } from "./attributes";
import {
  useBlockAttributes,
  useBlockSetAttributes,
  sanitizeURL,
  DynamicImageHandler,
  DynamicInputValueHandler,
  EBMediaPlaceholder,
} from "../../index";

// import './imageComponent.scss';

export const ImageComponent = ({
  isSelected,
  imageAttrProps,
  className,
  attrPrefix,
  hasStyle = true,
}) => {
  const attributes = useBlockAttributes();
  const setAttributes = useBlockSetAttributes();

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
  const DynamicTag = attrValue("figureTag") ? "figure" : "div";
  const captionClass = `
    eb-img-${attrValue("captionStyle")}
    eb-caption-horizontal-${attrValue("horizontalAlign")}
    eb-caption-vertical-${attrValue("verticalAlign")}
    ${attrValue("verticalAlignCap2")}
  `
    .replace(/\s+/g, " ")
    .trim();

  return (
    <>
      <DynamicTag className={`eb-image-wrapper`}>
        {attrValue("displayCaption") &&
        attrValue("captionStyle") === "caption-style-1" ? (
          <div
            className={`eb-image-wrapper-inner${
              className?.length > 0 ? " " + className : ""
            }${attrValue("displayCaption") ? " " + captionClass : ""}${
              hasStyle ? ` eb-img-style-${attrValue("stylePreset")}` : ""
            }`}
          >
            <DynamicImageHandler
              src={attrValue("imageUrl")}
              alt={attrValue("imageAlt")}
            />
            {attrValue("displayCaption") && (
              <>
                <DynamicInputValueHandler
                  value={attrValue("imageCaption")}
                  tagName={"figcaption"}
                  className="eb-image-caption-wrap"
                  allowedFormats={[
                    "core/bold",
                    "core/italic",
                    "core/link",
                    "core/strikethrough",
                    "core/underline",
                    "core/text-color",
                  ]}
                  onChange={(value) =>
                    setAttributes({
                      [getAttrName("imageCaption")]: value,
                    })
                  }
                  readOnly={true}
                />
              </>
            )}
          </div>
        ) : (
          <>
            <div
              className={`eb-image-wrapper-inner${
                className?.length > 0 ? " " + className : ""
              }${attrValue("displayCaption") ? " " + captionClass : ""}${
                hasStyle ? ` eb-img-style-${attrValue("stylePreset")}` : ""
              }`}
            >
              <DynamicImageHandler
                src={attrValue("imageUrl")}
                alt={attrValue("imageAlt")}
              />
            </div>
            {attrValue("displayCaption") &&
              attrValue("captionStyle") === "caption-style-2" && (
                <>
                  <DynamicInputValueHandler
                    value={attrValue("imageCaption")}
                    tagName={"figcaption"}
                    className="eb-image-caption-wrap"
                    allowedFormats={[
                      "core/bold",
                      "core/italic",
                      "core/link",
                      "core/strikethrough",
                      "core/underline",
                      "core/text-color",
                    ]}
                    onChange={(value) =>
                      setAttributes({
                        [getAttrName("imageCaption")]: value,
                      })
                    }
                    readOnly={true}
                  />
                </>
              )}
          </>
        )}
      </DynamicTag>
    </>
  );
};

ImageComponent.Upload = (props) => {
  const { imageAttrProps, attrPrefix, labels, icon, accept, allowedTypes, multiple } = props;
  const attributes = useBlockAttributes();
  const setAttributes = useBlockSetAttributes();

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
    (!attrValue("imageUrl") || attrValue("imageUrl") === "") && (
      <EBMediaPlaceholder
        onSelect={(image) => {
          setAttributes({
            [getAttrName("imageUrl")]: image?.url,
            [getAttrName("imageId")]: image?.id,
            [getAttrName("imageAlt")]: image?.alt,
            [getAttrName("imageCaption")]: image?.caption,
          });
        }}
        accept={accept ||"image/*"}
        allowedTypes={allowedTypes || ["image"]}
        labels={labels}
        icon={icon}
        multiple={multiple}
      />
    )
  );
};

ImageComponent.Content = (props) => {
  const {
    attributes,
    className,
    imageAttrProps,
    attrPrefix = "",
    hasStyle = true,
  } = props;

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
  // const attrValue = (attrKey) => attributes[getAttrName(attrKey)];
  const attrValue = (attrKey) => {
    const attrName = getAttrName(attrKey);
    return attributes && attrName in attributes ? attributes[attrName] : "";
  };

  const DynamicTag = attrValue("figureTag") ? "figure" : "div";
  const captionClass = `
  eb-img-${attrValue("captionStyle")}
  eb-caption-horizontal-${attrValue("horizontalAlign")}
  eb-caption-vertical-${attrValue("verticalAlign")}
  ${attrValue("verticalAlignCap2")}
`
    .replace(/\s+/g, " ")
    .trim();

  return (
    <>
      {attrValue("imageUrl") && (
        <DynamicTag className={`eb-image-wrapper`}>
          {attrValue("displayCaption") &&
          attrValue("captionStyle") === "caption-style-1" ? (
            <div
              className={`eb-image-wrapper-inner${
                className?.length > 0 ? " " + className : ""
              }${attrValue("displayCaption") ? " " + captionClass : ""} ${
                hasStyle ? ` eb-img-style-${attrValue("stylePreset")}` : ""
              }`}
            >
              {attrValue("enableLink") && (
                <a
                  className={"eb-image-link"}
                  href={sanitizeURL(attrValue("imageLink"))}
                  target={attrValue("openInNewTab") ? "_blank" : "_self"}
                  rel={attrValue("openInNewTab") ? "noopener" : undefined}
                ></a>
              )}
              <img src={attrValue("imageUrl")} alt={attrValue("imageAlt")} />
              {attrValue("displayCaption") && (
                <RichText.Content
                  tagName="figcaption"
                  className="eb-image-caption-wrap"
                  value={attrValue("imageCaption")}
                />
              )}
            </div>
          ) : (
            <>
              <div
                className={`eb-image-wrapper-inner${
                  className?.length > 0 ? " " + className : ""
                }${attrValue("displayCaption") ? " " + captionClass : ""}${
                  hasStyle ? ` eb-img-style-${attrValue("stylePreset")}` : ""
                }`}
              >
                <img src={attrValue("imageUrl")} alt={attrValue("imageAlt")} />
              </div>
              {attrValue("enableLink") && (
                <a
                  className={"eb-image-link"}
                  href={sanitizeURL(attrValue("imageLink"))}
                  target={attrValue("openInNewTab") ? "_blank" : "_self"}
                  rel={attrValue("openInNewTab") ? "noopener" : undefined}
                ></a>
              )}
              {attrValue("displayCaption") &&
                attrValue("captionStyle") === "caption-style-2" && (
                  <RichText.Content
                    tagName="figcaption"
                    className="eb-image-caption-wrap"
                    value={attrValue("imageCaption")}
                  />
                )}
            </>
          )}
        </DynamicTag>
      )}
    </>
  );
};

// Attach sub-components and utilities to main component
ImageComponent.addAttributes = addAttributes;
ImageComponent.GeneralTab = GeneralTab;
ImageComponent.StyleTab = StyleTab;
ImageComponent.Style = Style;

// Export all components and utilities
export { GeneralTab, StyleTab, Style, addAttributes };
export default ImageComponent;
