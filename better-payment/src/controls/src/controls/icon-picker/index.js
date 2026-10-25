import { IconPicker, DisplayIcon } from "wordpress-icon-picker";
import { __ } from "@wordpress/i18n";
import {
  fetchSvgAsHTML,
  getIconType,
  isInlineSvgMarkup,
} from "../../helpers/index";
import { sanitizeHTML } from "../../helpers/EBSanitization";
import "./style.scss";
import { memo, useRef, useEffect, useState } from "@wordpress/element";
import { useBlockSetAttributes } from "../../hooks";
import { BaseControl, Button, Modal } from "@wordpress/components";
import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";

const enableFontawesome =
  EssentialBlocksLocalize?.fontAwesome == "true" ? true : false;

export const EBIconPicker = memo((props) => {
  const containerRef = useRef(null);
  const setAttributes = useBlockSetAttributes();
  const [svgHtml, setSvgHtml] = useState(null);
  const [showWarningModal, setShowWarningModal] = useState(false);
  useEffect(() => {
    let mounted = true;
    const isSvgUrl =
      typeof props.value === "string" && /\.svg(\?|#|$)/i.test(props.value);
    if (isSvgUrl) {
      fetchSvgAsHTML(props.value)
        .then((html) => {
          if (mounted) setSvgHtml(html ? sanitizeHTML(html) : null);
        })
        .catch(() => mounted && setSvgHtml(null));
    } else {
      setSvgHtml(null);
    }
    return () => {
      mounted = false;
    };
  }, [props.value]);

  // propagate changes to attribute or external handler
  const targetAttr = props.attributeName || "icon";
  let onChange = (value) => setAttributes({ [targetAttr]: value });
  if (props.onChange) {
    onChange = (value) => props.onChange(value);
  }

  const defaultValue =
    !enableFontawesome && getIconType(props.value) == "fontawesome"
      ? ""
      : props.value;
  const _props = {
    ...props,
    disableFontAwesome: !enableFontawesome,
    value: defaultValue,
  };

  // Clear the value of the IconPicker
  const handleClear = () => {
    onChange("");
  };

  // Open the underlying IconPicker popover by simulating a click on its trigger (#wipIcon)
  const openLibrary = () => {
    const host = containerRef.current;
    const trigger = host?.querySelector?.("#wipIcon");

    if (trigger) {
      trigger.click();

      // After opening the popup, click the first wip-icon-tab to activate it
      setTimeout(() => {
        const popup = document.querySelector(".wip-iconpicker-popup");
        if (popup) {
          const secondTab = popup.querySelectorAll(".wip-icon-tab");
          if (secondTab[1]) {
            secondTab[1].click();
          }
        }
      }, 100); // Small delay to ensure popup is rendered
    }
  };

  // Handle Upload SVG button click
  const handleUploadSVGClick = (openMediaUpload) => {
    const isUnfilteredFileEnabled =
      EssentialBlocksLocalize?.unfilteredFile === "true";

    // Show warning if unfiltered files are disabled OR if user is not admin
    if (!isUnfilteredFileEnabled || !isAdminUser) {
      setShowWarningModal(true);
      return;
    }

    openMediaUpload();
  };

  // Check if current user is admin
  const isAdminUser = EssentialBlocksLocalize?.is_admin_user === "true";

  if (_props.disableDashicon && _props.disableFontAwesome) return null;
  return (
    <BaseControl className="eb-icon-picker-base" label={props.title || "Icon"}>
      <div className="ebip__wrap" ref={containerRef}>
        <div
          className={`ebip__preview${
            isInlineSvgMarkup(svgHtml) ? " ebip__preview--svg" : ""
          }`}
        >
          {/* Keep IconPicker in DOM so its popover works; style it to fill the preview */}
          {isInlineSvgMarkup(svgHtml) ? (
            <>
              <div
                className="ebip__preview__svg_wrap"
                dangerouslySetInnerHTML={{ __html: sanitizeHTML(svgHtml) }}
              />
              <IconPicker {..._props} onChange={onChange} />
            </>
          ) : (
            <IconPicker {..._props} onChange={onChange} />
          )}

          {props.hasReset !== false && (
            <Button
              className="ebip__clear"
              icon="trash"
              onClick={(e) => {
                e.stopPropagation();
                handleClear();
              }}
              disabled={!props.value}
              label="Clear"
            />
          )}
        </div>

        <div className="ebip__actions">
          <Button className="ebip__action" onClick={openLibrary}>
            Icon Library
          </Button>
          {props.enableSvgUpload !== false && (
            <>
              <span className="ebip__divider" aria-hidden="true" />
              <MediaUploadCheck>
                <MediaUpload
                  onSelect={(media) => {
                    const isSvg =
                      media?.mime === "image/svg+xml" ||
                      (media?.type === "image" &&
                        /\.svg$/i.test(media?.url || ""));
                    if (isSvg) {
                      onChange(media?.url || "");
                    }
                  }}
                  allowedTypes={["image/svg+xml"]}
                  title={"Select SVG"}
                  multiple={false}
                  gallery={false}
                  value={undefined}
                  render={({ open }) => (
                    <Button
                      className="ebip__action"
                      onClick={() => handleUploadSVGClick(open)}
                      // disabled={typeof props.onSvgSelect !== "function"}
                    >
                      Upload SVG
                    </Button>
                  )}
                />
              </MediaUploadCheck>
            </>
          )}
        </div>

        {showWarningModal && (
          <Modal
            title={
              <div
                style={{ display: "flex", alignItems: "center", gap: "10px" }}
              >
                {isAdminUser
                  ? __("SVG Upload Disabled", "essential-blocks")
                  : __("Upload Restricted", "essential-blocks")}
              </div>
            }
            onRequestClose={() => setShowWarningModal(false)}
            className="eb-svg-warning-modal"
            style={{ maxWidth: "500px" }}
          >
            <div style={{ padding: "20px 0" }}>
              {isAdminUser ? (
                // Admin user message
                <>
                  <p style={{ marginBottom: "15px", lineHeight: "1.6" }}>
                    {__(
                      "SVG file uploads are currently disabled for security reasons. To enable SVG uploads, please go to Essential Blocks Settings > Options and enable 'Unfiltered File Uploads'.",
                      "essential-blocks"
                    )}
                  </p>
                  <p
                    style={{
                      marginBottom: "20px",
                      lineHeight: "1.6",
                      color: "#d63638",
                      fontWeight: "500",
                    }}
                  >
                    {__(
                      "Warning: This feature should only be enabled if you understand the security risks involved.",
                      "essential-blocks"
                    )}
                  </p>
                  <div
                    style={{
                      display: "flex",
                      gap: "10px",
                      justifyContent: "flex-end",
                    }}
                  >
                    <Button
                      variant="tertiary"
                      onClick={() => {
                        window.open(
                          "https://essential-blocks.com/docs/unfiltered-file-uploads-in-wordpress/",
                          "_blank"
                        );
                      }}
                    >
                      {__("Learn More", "essential-blocks")}
                    </Button>
                    <Button
                      variant="primary"
                      onClick={() => setShowWarningModal(false)}
                      style={{
                        backgroundColor: "#cebeff",
                        color: "#551ef7",
                        borderColor: "#cebeff",
                      }}
                    >
                      {__("Got it", "essential-blocks")}
                    </Button>
                  </div>
                </>
              ) : (
                // Non-admin user message
                <>
                  <p style={{ marginBottom: "15px", lineHeight: "1.6" }}>
                    {__(
                      "Sorry, you can't upload that file yet.",
                      "essential-blocks"
                    )}
                  </p>
                  <p style={{ marginBottom: "15px", lineHeight: "1.6" }}>
                    {__(
                      "This is because JSON files may pose a security risk.",
                      "essential-blocks"
                    )}
                  </p>
                  <p
                    style={{
                      marginBottom: "20px",
                      lineHeight: "1.6",
                      fontWeight: "500",
                    }}
                  >
                    {__("Contact with administrator", "essential-blocks")}
                  </p>
                  <div
                    style={{
                      display: "flex",
                      gap: "10px",
                      justifyContent: "flex-end",
                    }}
                  >
                    <Button
                      variant="primary"
                      onClick={() => setShowWarningModal(false)}
                      style={{
                        backgroundColor: "#cebeff",
                        color: "#551ef7",
                        borderColor: "#cebeff",
                      }}
                    >
                      {__("Got it", "essential-blocks")}
                    </Button>
                  </div>
                </>
              )}
            </div>
          </Modal>
        )}
      </div>
    </BaseControl>
  );
});

export const EBDisplayIconEdit = (props) => {
  const { icon, className } = props;
  const [svgHtml, setSvgHtml] = useState(null);
  const iconClass = className ? className : "";

  useEffect(() => {
    let mounted = true;
    const isSvgUrl = typeof icon === "string" && /\.svg(\?|#|$)/i.test(icon);
    if (isSvgUrl) {
      fetchSvgAsHTML(icon)
        .then((html) => {
          if (mounted) setSvgHtml(html ? sanitizeHTML(html) : null);
        })
        .catch(() => mounted && setSvgHtml(null));
    } else {
      setSvgHtml(null);
    }
    return () => {
      mounted = false;
    };
  }, [icon]);

  if (!icon) {
    return null;
  }

  if (isInlineSvgMarkup(icon)) {
    return (
      <span
        className={iconClass}
        dangerouslySetInnerHTML={{ __html: sanitizeHTML(icon) }}
      />
    );
  }

  if (svgHtml) {
    return (
      <span
        className={iconClass}
        dangerouslySetInnerHTML={{ __html: svgHtml }}
      />
    );
  }
  return <DisplayIcon {...props} icon={icon} />;
};

export const EBDisplayIconSave = (props) => {
  const { icon, className } = props;
  const iconClass = className ? className : "";

  if (!icon) {
    return null;
  }

  const isSvgUrl = typeof icon === "string" && /\.svg(\?|#|$)/i.test(icon);
  if (isSvgUrl) {
    return (
      <span
        className="eb-display-icon-svg"
        data-svg-url={icon}
        data-class-name={iconClass}
      />
    );
  }
  return <DisplayIcon {...props} icon={icon} />;
};

export const EBDisplayIcon = (props) => {
  if (!props.icon) {
    return;
  }
  return <DisplayIcon {...props} />;
};
