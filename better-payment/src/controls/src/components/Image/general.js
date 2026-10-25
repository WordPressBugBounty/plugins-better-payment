/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  Button,
  ToggleControl,
  SelectControl,
  PanelRow,
} from "@wordpress/components";
import { useEffect, useRef, useState, memo } from "@wordpress/element";
import { MediaUpload } from "@wordpress/block-editor";
import { select } from "@wordpress/data";
import { store as coreStore } from "@wordpress/core-data";
/**
 * Internal depencencies
 */
import { camelCase, upperFirst } from "lodash";
import { InspectorPanel } from "../../";
import {
  useBlockSetAttributes,
  useBlockAttributes,
  DynamicInputControl,
  ResponsiveAlignControl,
} from "../../index";
import { getMediaSourceUrlBySize, EbImageSizeSelector } from "../../helpers";
import {
  CAPTION_STYLES,
  STYLES,
  IMAGE_ALIGN,
  IMAGE_ALIGNMENT,
} from "./constants";
import AIImagePopover from "../AI/AIImagePopover";
import AIImageIcon from "../../icons/ai-image-icon";
const dynamic_tag =
  typeof EssentialBlocksProLocalize !== "undefined"
    ? EssentialBlocksProLocalize.eb_dynamic_tags
    : null;

export const GeneralTab = (props) => {
  const {
    imageAttrProps,
    attrPrefix = "",
    label,
    hasTag = true,
    hasCaption = true,
    hasStyle = true,
    hasLink = true,
    useImageAlign = false,
    imageAlign = IMAGE_ALIGNMENT,
    imageAlignOptions = IMAGE_ALIGN,
    resOption,
    showInPanel = true,
    onSelect,
    value,
    onRemove,
    accept = "image/*",
    allowedTypes = ["image"],
    multiple = false,
  } = props;

  const attributes = useBlockAttributes();
  const setAttributes = useBlockSetAttributes();

  // State for AI popover visibility
  const [isAIPopoverVisible, setIsAIPopoverVisible] = useState(false);
  const aiButtonRef = useRef(null);

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

  const prevSizeRef = useRef(attrValue("imageSize"));

  useEffect(() => {
    if (attrValue("imageId")) {
      const { getMedia } = select(coreStore);
      const newSize = attrValue("imageSize");

      // Check if the new size is different from the previous size
      if (newSize !== prevSizeRef.current) {
        const media = getMedia(attrValue("imageId"));

        // early return if media not loaded yet
        if (!media) return;

        const imageUrl = getMediaSourceUrlBySize(media, newSize);

        setAttributes({
          [getAttrName("imageUrl")]: imageUrl,
        });

        // Update the ref with the current size
        prevSizeRef.current = newSize;
      }
    } else {
      setAttributes({
        [getAttrName("imageSize")]: "",
      });
    }
  }, [attrValue("imageSize"), attrValue("imageId")]);

  const changeStyle = (selected) => {
    setAttributes({ [getAttrName("stylePreset")]: selected });
    const complexLayouts = ["octagon", "rhombus", "triangle"];
    if (complexLayouts.includes(selected)) {
      setAttributes({
        [getAttrName("complexStyle")]: true,
      });
    } else {
      setAttributes({
        [getAttrName("complexStyle")]: false,
      });
    }
  };

  /**
   * Get the value to use for MediaUpload components
   * Uses custom value prop when both value and onSelect are provided,
   * otherwise falls back to default attribute-based value
   */
  const getMediaUploadValue = () => {
    if (value !== undefined && onSelect) {
      return value;
    }
    return attrValue("imageId");
  };

  /**
   * Get the image URL to display
   * Uses custom value prop when both value and onSelect are provided,
   * otherwise falls back to default attribute-based value
   */
  const getImageUrl = () => {
    if (typeof value === "string" && value.length > 0 && onSelect) {
      return value;
    }
    return attrValue("imageUrl");
  };

  /**
   * Handle AI button click to toggle popover
   */
  const handleAIButtonClick = () => {
    setIsAIPopoverVisible(!isAIPopoverVisible);
  };

  /**
   * Handle media selection - uses custom onSelect if provided, otherwise default attribute behavior
   */
  const handleMediaSelect = (media) => {
    if (onSelect) {
      // Use custom onSelect callback if provided
      onSelect(media);
    } else {
      // Default attribute-setting behavior
      setAttributes({
        [getAttrName("imageId")]: media.id,
        [getAttrName("imageUrl")]: getMediaSourceUrlBySize(
          media,
          attrValue("imageSize")
        ),
        [getAttrName("imageAlt")]: media?.alt,
        [getAttrName("imageCaption")]: media?.caption,
      });
    }
  };

  /**
   * Handle media selection for no-image state - uses custom onSelect if provided, otherwise default behavior
   */
  const handleMediaSelectNoImage = (media) => {
    if (onSelect) {
      // Use custom onSelect callback if provided
      onSelect(media);
    } else {
      // Default attribute-setting behavior for no-image state
      setAttributes({
        [getAttrName("imageId")]: media.id,
        [getAttrName("imageUrl")]: media.url,
        [getAttrName("imageAlt")]: media?.alt,
        [getAttrName("imageCaption")]: media?.caption,
      });
    }
  };

  /**
   * Handle AI image generation success
   */
  const handleAIImageGenerated = (imageData) => {
    if (onSelect) {
      // Use custom onSelect callback if provided
      onSelect(imageData);
    } else {
      // Default attribute-setting behavior
      setAttributes({
        [getAttrName("imageId")]: imageData.id,
        [getAttrName("imageUrl")]: imageData.url,
        [getAttrName("imageAlt")]: imageData.alt,
        [getAttrName("imageCaption")]: imageData.caption || "",
      });
    }
    setIsAIPopoverVisible(false);
  };

  return (
    <>
      {showInPanel && (
        <InspectorPanel.PanelBody
          title={label ? label : __("Image Settings", "essential-blocks")}
          initialOpen={true}
        >
          <>
            <div className="eb-image-component">
              {getImageUrl() ? (
                <div className="eb-image-preview">
                  {typeof getImageUrl() === "string" &&
                  getImageUrl().startsWith(dynamic_tag + "/", 0) ? (
                    <svg
                      width="129"
                      height="90"
                      viewBox="0 0 129 90"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <rect
                        width="129"
                        height="90"
                        rx="4.27251"
                        fill="#F5F7FA"
                      />
                      <path
                        d="M77.6599 28.7519C76.7718 27.43 74.8404 27.3922 73.9013 28.6782L55.5987 53.7423C54.728 54.9346 52.9742 55.0066 52.0088 53.8895L44.3175 44.9904C43.3305 43.8484 41.5287 43.9538 40.6815 45.2032L23.4296 70.6464C22.3962 72.1703 23.4879 74.2295 25.3291 74.2295H103.906C105.743 74.2295 106.836 72.1792 105.811 70.6545L77.6599 28.7519Z"
                        fill="#DEE2ED"
                      />
                      <path
                        d="M54.1104 28.5914L52.2118 27.1068C52.2359 26.8778 52.2565 26.6102 52.2565 26.327C52.2565 26.0439 52.2367 25.7762 52.2118 25.5473L54.1121 24.0618C54.465 23.7829 54.5614 23.288 54.3368 22.8758L52.3624 19.4598C52.1515 19.0743 51.6894 18.8763 51.2203 19.0493L48.98 19.9487C48.5506 19.6388 48.1004 19.3772 47.6348 19.1681L47.294 16.7918C47.238 16.3408 46.8456 16 46.3817 16H42.4227C41.9588 16 41.5672 16.3408 41.5121 16.7849L41.1705 19.1698C40.7195 19.3729 40.2762 19.6311 39.8278 19.9504L37.5815 19.0485C37.1606 18.8858 36.6571 19.0682 36.448 19.4521L34.4711 22.8723C34.2379 23.2665 34.3343 23.7786 34.6949 24.0644L36.5935 25.549C36.5633 25.839 36.5487 26.0912 36.5487 26.3279C36.5487 26.5646 36.5634 26.8167 36.5935 27.1077L34.6931 28.5931C34.3403 28.8728 34.2447 29.3677 34.4694 29.7791L36.4437 33.1951C36.6546 33.5798 37.1124 33.7794 37.5858 33.6056L39.8261 32.7062C40.2547 33.0152 40.7048 33.2768 41.1705 33.4868L41.5113 35.8622C41.5672 36.3149 41.9588 36.6557 42.4236 36.6557H46.3826C46.8465 36.6557 47.2389 36.3149 47.294 35.8708L47.6357 33.4868C48.0867 33.2828 48.529 33.0255 48.9783 32.7053L51.2246 33.6073C51.333 33.6494 51.4458 33.671 51.562 33.671C51.8959 33.671 52.2032 33.4885 52.3581 33.2045L54.341 29.7705C54.5614 29.3677 54.465 28.8728 54.1104 28.5914ZM44.4022 29.7705C42.5036 29.7705 40.9596 28.2265 40.9596 26.3279C40.9596 24.4293 42.5036 22.8853 44.4022 22.8853C46.3008 22.8853 47.8448 24.4293 47.8448 26.3279C47.8448 28.2265 46.3008 29.7705 44.4022 29.7705Z"
                        fill="#CBD0DD"
                      />
                    </svg>
                  ) : (
                    <img src={getImageUrl()} alt={attrValue("imageAlt")} />
                  )}

                  <div className="eb-image-component-button-wrap">
                    {EssentialBlocksLocalize?.is_pro_active === "true" && (
                      <>
                        <DynamicInputControl
                          attrName={getAttrName("imageUrl")}
                          inputValue={getImageUrl()}
                          setAttributes={setAttributes}
                          onChange={(link) =>
                            setAttributes({
                              [getAttrName("imageUrl")]: link,
                            })
                          }
                          isImageField={true}
                          imageIdAttr={getAttrName("imageId")}
                          type="image"
                        />
                      </>
                    )}

                    {/* AI Generate Image Button */}
                    {window.EssentialBlocksLocalize?.enableGenerateImage ===
                      "1" && (
                      <Button
                        ref={aiButtonRef}
                        className="components-button eb-ai-img-button"
                        label={__("Generate with AI", "essential-blocks")}
                        icon={<AIImageIcon />}
                        onClick={handleAIButtonClick}
                      />
                    )}
                    <MediaUpload
                      onSelect={handleMediaSelect}
                      value={getMediaUploadValue()}
                      accept={accept}
                      allowedTypes={allowedTypes}
                      multiple={multiple}
                      render={({ open }) => (
                        <Button
                          className="components-button eb-replace-img-button"
                          label={__("Replace Image", "essential-blocks")}
                          icon="format-image"
                          onClick={open} // Use the open function here
                        />
                      )}
                    />

                    {/* Remove Image Button */}
                    <Button
                      className="components-button eb-remove-img-button"
                      label={__("Remove Image", "essential-blocks")}
                      icon="trash"
                      onClick={() => {
                        if (onRemove) {
                          // Use custom onRemove callback if provided
                          onRemove();
                        } else {
                          // Default attribute-clearing behavior
                          setAttributes({
                            [getAttrName("imageId")]: null,
                            [getAttrName("imageUrl")]: null,
                            [getAttrName("imageAlt")]: null,
                          });
                        }
                      }}
                    />
                  </div>
                </div>
              ) : (
                <div className="eb-image-preview eb-no-image">
                  <MediaUpload
                    onSelect={handleMediaSelectNoImage}
                    value={getMediaUploadValue()}
                    accept={accept}
                    allowedTypes={allowedTypes}
                    multiple={multiple}
                    render={({ open }) =>
                      !getImageUrl() && (
                        <Button
                          className="eb-image-upload-button"
                          label={__("Upload Image", "essential-blocks")}
                          icon="format-image"
                          onClick={open}
                        />
                      )
                    }
                  />

                  <div className="eb-image-component-button-wrap">
                    {/* AI Generate Image Button */}
                    {window.EssentialBlocksLocalize?.enableGenerateImage ===
                      "1" && (
                      <Button
                        ref={aiButtonRef}
                        className="components-button eb-ai-img-button"
                        label={__("Generate with AI", "essential-blocks")}
                        icon={<AIImageIcon />}
                        onClick={handleAIButtonClick}
                      />
                    )}

                    {EssentialBlocksLocalize?.is_pro_active === "true" && (
                      <DynamicInputControl
                        attrName={getAttrName("imageUrl")}
                        inputValue={getImageUrl()}
                        setAttributes={setAttributes}
                        onChange={(link) =>
                          setAttributes({
                            [getAttrName("imageUrl")]: link,
                          })
                        }
                        isImageField={true}
                        type="image"
                      />
                    )}
                  </div>
                </div>
              )}
            </div>

            {hasStyle && (
              <>
                <SelectControl
                  label={__("Image Styles", "essential-blocks")}
                  value={attrValue("stylePreset")}
                  options={STYLES}
                  onChange={(value) => changeStyle(value)}
                />
                {attrValue("stylePreset") === "circle" && (
                  <PanelRow>
                    <em>
                      {__(
                        'Please use equal "Height" & "Width" for perfect Circle shape.',
                        "essential-blocks"
                      )}
                    </em>
                  </PanelRow>
                )}
              </>
            )}
            {hasTag && (
              <ToggleControl
                label={__("Use Figure Tag", "essential-blocks")}
                checked={attrValue("figureTag")}
                onChange={() =>
                  setAttributes({
                    [getAttrName("figureTag")]: !attrValue("figureTag"),
                  })
                }
              />
            )}
            {hasCaption && (
              <ToggleControl
                label={__("Display Caption", "essential-blocks")}
                checked={attrValue("displayCaption")}
                onChange={() =>
                  setAttributes({
                    [getAttrName("displayCaption")]:
                      !attrValue("displayCaption"),
                  })
                }
              />
            )}
            {hasCaption && attrValue("displayCaption") && (
              <>
                <DynamicInputControl
                  label={__("Caption", "essential-blocks")}
                  attrName={getAttrName("imageCaption")}
                  inputValue={attrValue("imageCaption")}
                  setAttributes={setAttributes}
                  onChange={(value) =>
                    setAttributes({
                      [getAttrName("imageCaption")]: value,
                    })
                  }
                />
                <SelectControl
                  label={__("Caption Styles", "essential-blocks")}
                  value={attrValue("captionStyle")}
                  options={CAPTION_STYLES}
                  onChange={(value) => {
                    setAttributes({
                      [getAttrName("captionStyle")]: value,
                    });
                  }}
                />
              </>
            )}
            {attrValue("imageId") && (
              <>
                <EbImageSizeSelector
                  attrName={getAttrName("imageSize")}
                  label={__("Image Size", "essential-blocks")}
                />
              </>
            )}
            {hasLink && (
              <>
                <ToggleControl
                  label={__("Enable Link?", "essential-blocks")}
                  checked={attrValue("enableLink")}
                  onChange={() =>
                    setAttributes({
                      [getAttrName("enableLink")]: !attrValue("enableLink"),
                    })
                  }
                />

                {attrValue("enableLink") && (
                  <>
                    <DynamicInputControl
                      label={__("Link", "essential-blocks")}
                      attrName={getAttrName("imageLink")}
                      inputValue={attrValue("imageLink")}
                      setAttributes={setAttributes}
                      onChange={(link) =>
                        setAttributes({
                          [getAttrName("imageLink")]: link,
                        })
                      }
                      enableAi={false}
                      fieldType="url"
                      showValidation={true}
                      enableSecurity={true}
                    />
                  </>
                )}
                {attrValue("enableLink") && (
                  <ToggleControl
                    label={__("Open in New Tab", "essential-blocks")}
                    checked={attrValue("openInNewTab")}
                    onChange={() =>
                      setAttributes({
                        [getAttrName("openInNewTab")]:
                          !attrValue("openInNewTab"),
                      })
                    }
                  />
                )}
              </>
            )}

            {useImageAlign && (
              <ResponsiveAlignControl
                baseLabel={__("Image Align", "essential-blocks")}
                controlName={imageAlign}
                options={imageAlignOptions}
                resOption={resOption}
              />
            )}
          </>
        </InspectorPanel.PanelBody>
      )}
      {!showInPanel && (
        <>
          <div className="eb-image-component">
            {getImageUrl() ? (
              <div className="eb-image-preview">
                {typeof getImageUrl() === "string" &&
                getImageUrl().startsWith(dynamic_tag + "/", 0) ? (
                  <svg
                    width="129"
                    height="90"
                    viewBox="0 0 129 90"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <rect width="129" height="90" rx="4.27251" fill="#F5F7FA" />
                    <path
                      d="M77.6599 28.7519C76.7718 27.43 74.8404 27.3922 73.9013 28.6782L55.5987 53.7423C54.728 54.9346 52.9742 55.0066 52.0088 53.8895L44.3175 44.9904C43.3305 43.8484 41.5287 43.9538 40.6815 45.2032L23.4296 70.6464C22.3962 72.1703 23.4879 74.2295 25.3291 74.2295H103.906C105.743 74.2295 106.836 72.1792 105.811 70.6545L77.6599 28.7519Z"
                      fill="#DEE2ED"
                    />
                    <path
                      d="M54.1104 28.5914L52.2118 27.1068C52.2359 26.8778 52.2565 26.6102 52.2565 26.327C52.2565 26.0439 52.2367 25.7762 52.2118 25.5473L54.1121 24.0618C54.465 23.7829 54.5614 23.288 54.3368 22.8758L52.3624 19.4598C52.1515 19.0743 51.6894 18.8763 51.2203 19.0493L48.98 19.9487C48.5506 19.6388 48.1004 19.3772 47.6348 19.1681L47.294 16.7918C47.238 16.3408 46.8456 16 46.3817 16H42.4227C41.9588 16 41.5672 16.3408 41.5121 16.7849L41.1705 19.1698C40.7195 19.3729 40.2762 19.6311 39.8278 19.9504L37.5815 19.0485C37.1606 18.8858 36.6571 19.0682 36.448 19.4521L34.4711 22.8723C34.2379 23.2665 34.3343 23.7786 34.6949 24.0644L36.5935 25.549C36.5633 25.839 36.5487 26.0912 36.5487 26.3279C36.5487 26.5646 36.5634 26.8167 36.5935 27.1077L34.6931 28.5931C34.3403 28.8728 34.2447 29.3677 34.4694 29.7791L36.4437 33.1951C36.6546 33.5798 37.1124 33.7794 37.5858 33.6056L39.8261 32.7062C40.2547 33.0152 40.7048 33.2768 41.1705 33.4868L41.5113 35.8622C41.5672 36.3149 41.9588 36.6557 42.4236 36.6557H46.3826C46.8465 36.6557 47.2389 36.3149 47.294 35.8708L47.6357 33.4868C48.0867 33.2828 48.529 33.0255 48.9783 32.7053L51.2246 33.6073C51.333 33.6494 51.4458 33.671 51.562 33.671C51.8959 33.671 52.2032 33.4885 52.3581 33.2045L54.341 29.7705C54.5614 29.3677 54.465 28.8728 54.1104 28.5914ZM44.4022 29.7705C42.5036 29.7705 40.9596 28.2265 40.9596 26.3279C40.9596 24.4293 42.5036 22.8853 44.4022 22.8853C46.3008 22.8853 47.8448 24.4293 47.8448 26.3279C47.8448 28.2265 46.3008 29.7705 44.4022 29.7705Z"
                      fill="#CBD0DD"
                    />
                  </svg>
                ) : (
                  <img src={getImageUrl()} alt={attrValue("imageAlt")} />
                )}

                <div className="eb-image-component-button-wrap">
                  {EssentialBlocksLocalize?.is_pro_active === "true" && (
                    <>
                      <DynamicInputControl
                        attrName={getAttrName("imageUrl")}
                        inputValue={getImageUrl()}
                        setAttributes={setAttributes}
                        onChange={(link) =>
                          setAttributes({
                            [getAttrName("imageUrl")]: link,
                          })
                        }
                        isImageField={true}
                        imageIdAttr={getAttrName("imageId")}
                        type="image"
                      />
                    </>
                  )}

                  {/* AI Generate Image Button */}
                  {window.EssentialBlocksLocalize?.enableGenerateImage ===
                    "1" && (
                    <Button
                      ref={aiButtonRef}
                      className="components-button eb-ai-img-button"
                      label={__("Generate with AI", "essential-blocks")}
                      icon={<AIImageIcon />}
                      onClick={handleAIButtonClick}
                    />
                  )}
                  <MediaUpload
                    onSelect={handleMediaSelect}
                    value={getMediaUploadValue()}
                    accept={accept}
                    allowedTypes={allowedTypes}
                    multiple={multiple}
                    render={({ open }) => (
                      <Button
                        className="components-button eb-replace-img-button"
                        label={__("Replace Image", "essential-blocks")}
                        icon="format-image"
                        onClick={open} // Use the open function here
                      />
                    )}
                  />

                  {/* Remove Image Button */}
                  <Button
                    className="components-button eb-remove-img-button"
                    label={__("Remove Image", "essential-blocks")}
                    icon="trash"
                    onClick={() => {
                      if (onRemove) {
                        // Use custom onRemove callback if provided
                        onRemove();
                      } else {
                        // Default attribute-clearing behavior
                        setAttributes({
                          [getAttrName("imageId")]: null,
                          [getAttrName("imageUrl")]: null,
                          [getAttrName("imageAlt")]: null,
                        });
                      }
                    }}
                  />
                </div>
              </div>
            ) : (
              <div className="eb-image-preview eb-no-image">
                <MediaUpload
                  onSelect={handleMediaSelectNoImage}
                  value={getMediaUploadValue()}
                  accept={accept}
                  allowedTypes={allowedTypes}
                  multiple={multiple}
                  render={({ open }) =>
                    !getImageUrl() && (
                      <Button
                        className="eb-image-upload-button"
                        label={__("Upload Image", "essential-blocks")}
                        icon="format-image"
                        onClick={open}
                      />
                    )
                  }
                />

                <div className="eb-image-component-button-wrap">
                  {/* AI Generate Image Button */}
                  {window.EssentialBlocksLocalize?.enableGenerateImage ===
                    "1" && (
                    <Button
                      ref={aiButtonRef}
                      className="components-button eb-ai-img-button"
                      label={__("Generate with AI", "essential-blocks")}
                      icon={<AIImageIcon />}
                      onClick={handleAIButtonClick}
                    />
                  )}

                  {EssentialBlocksLocalize?.is_pro_active === "true" && (
                    <DynamicInputControl
                      attrName={getAttrName("imageUrl")}
                      inputValue={getImageUrl()}
                      setAttributes={setAttributes}
                      onChange={(link) =>
                        setAttributes({
                          [getAttrName("imageUrl")]: link,
                        })
                      }
                      isImageField={true}
                      type="image"
                    />
                  )}
                </div>
              </div>
            )}
          </div>

          {hasStyle && (
            <>
              <SelectControl
                label={__("Image Styles", "essential-blocks")}
                value={attrValue("stylePreset")}
                options={STYLES}
                onChange={(value) => changeStyle(value)}
              />
              {attrValue("stylePreset") === "circle" && (
                <PanelRow>
                  <em>
                    {__(
                      'Please use equal "Height" & "Width" for perfect Circle shape.',
                      "essential-blocks"
                    )}
                  </em>
                </PanelRow>
              )}
            </>
          )}
          {hasTag && (
            <ToggleControl
              label={__("Use Figure Tag", "essential-blocks")}
              checked={attrValue("figureTag")}
              onChange={() =>
                setAttributes({
                  [getAttrName("figureTag")]: !attrValue("figureTag"),
                })
              }
            />
          )}
          {hasCaption && (
            <ToggleControl
              label={__("Display Caption", "essential-blocks")}
              checked={attrValue("displayCaption")}
              onChange={() =>
                setAttributes({
                  [getAttrName("displayCaption")]: !attrValue("displayCaption"),
                })
              }
            />
          )}
          {hasCaption && attrValue("displayCaption") && (
            <>
              <DynamicInputControl
                label={__("Caption", "essential-blocks")}
                attrName={getAttrName("imageCaption")}
                inputValue={attrValue("imageCaption")}
                setAttributes={setAttributes}
                onChange={(value) =>
                  setAttributes({
                    [getAttrName("imageCaption")]: value,
                  })
                }
              />
              <SelectControl
                label={__("Caption Styles", "essential-blocks")}
                value={attrValue("captionStyle")}
                options={CAPTION_STYLES}
                onChange={(value) => {
                  setAttributes({
                    [getAttrName("captionStyle")]: value,
                  });
                }}
              />
            </>
          )}
          {attrValue("imageId") && (
            <>
              <EbImageSizeSelector
                attrName={getAttrName("imageSize")}
                label={__("Image Size", "essential-blocks")}
              />
            </>
          )}
          {hasLink && (
            <>
              <ToggleControl
                label={__("Enable Link?", "essential-blocks")}
                checked={attrValue("enableLink")}
                onChange={() =>
                  setAttributes({
                    [getAttrName("enableLink")]: !attrValue("enableLink"),
                  })
                }
              />

              {attrValue("enableLink") && (
                <>
                  <DynamicInputControl
                    label={__("Link", "essential-blocks")}
                    attrName={getAttrName("imageLink")}
                    inputValue={attrValue("imageLink")}
                    setAttributes={setAttributes}
                    onChange={(link) =>
                      setAttributes({
                        [getAttrName("imageLink")]: link,
                      })
                    }
                    enableAi={false}
                    fieldType="url"
                    showValidation={true}
                    enableSecurity={true}
                  />
                </>
              )}
              {attrValue("enableLink") && (
                <ToggleControl
                  label={__("Open in New Tab", "essential-blocks")}
                  checked={attrValue("openInNewTab")}
                  onChange={() =>
                    setAttributes({
                      [getAttrName("openInNewTab")]: !attrValue("openInNewTab"),
                    })
                  }
                />
              )}
            </>
          )}

          {useImageAlign && (
            <ResponsiveAlignControl
              baseLabel={__("Image Align", "essential-blocks")}
              controlName={IMAGE_ALIGNMENT}
              options={IMAGE_ALIGN}
              resOption={resOption}
            />
          )}
        </>
      )}

      {/* AI Image Popover */}
      {window.EssentialBlocksLocalize?.enableGenerateImage === "1" && (
        <AIImagePopover
          isVisible={isAIPopoverVisible}
          onClose={() => setIsAIPopoverVisible(false)}
          anchor={aiButtonRef.current}
          onImageGenerated={handleAIImageGenerated}
        />
      )}
    </>
  );
};
