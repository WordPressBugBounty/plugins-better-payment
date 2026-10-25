import { __ } from "@wordpress/i18n";
import { Dashicon } from "@wordpress/components";
import { URLInput, RichText } from "@wordpress/block-editor";
import { applyFilters } from "@wordpress/hooks";
import { GeneralTab } from "./general";
import { StyleTab } from "./style-tab.js";
import { Style } from "./style-js.js";
import { addAttributes } from "./attributes";
import {
    useBlockSetAttributes,
    useBlockAttributes,
    EBDisplayIconEdit,
    EBDisplayIconSave,
    sanitizeURL,
} from "../../index";
import { camelCase, upperFirst } from "lodash";
import DynamicInputValueHandler from "../../controls/dynamic-field/DynamicInputValueHandler";

export const EBButton = ({
    isSelected,
    buttonAttrProps,
    urlInput,
    className,
    tagName,
    attrPrefix,
    type,
    disable = false,
    loadingIcon = false,
    btnWrapperClassName = '',
    isDynamic = false
}) => {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

    const getAttrName = (attrKey) => {
        const finalKey = buttonAttrProps && buttonAttrProps[attrKey] ? buttonAttrProps[attrKey] : attrKey;

        if (buttonAttrProps && buttonAttrProps[attrKey]) {
            return finalKey;
        }

        return attrPrefix ? `${attrPrefix}${upperFirst(camelCase(finalKey))}` : finalKey;
    };
    const attrValue = (attrKey) => attributes[getAttrName(attrKey)];

    // Generate liquid glass classes
    const liquidGlassClasses = attrValue("btnLiquidGlass")?.enable
        ? `eb_liquid_glass-${attrValue("btnLiquidGlass").effect} eb_liquid_glass_shadow-${attrValue("btnLiquidGlass").shadowEffect}`
        : '';

    return (
        <>
            <div className={`eb-button-inner-wrapper ${btnWrapperClassName}`}>
                {type == 'button' && (
                    <button
                        className={`eb-button-loadmore${attrValue("hoverEffect") ? ` ${attrValue("hoverEffect")}` : ""
                            } ${className?.length > 0 ? className : ""} ${liquidGlassClasses}`}
                        disabled={disable}
                    >
                        {applyFilters("eb_liquid_glass_effect_pro_content", "", attributes, "btnLiquidGlass")}
                        {attrValue("addIcon") && attrValue("iconPosition") === "left" ? (
                            <EBDisplayIconEdit
                                icon={attrValue("icon")}
                                className={`eb-button-icon eb-button-icon-left hvr-icon`}
                            />
                        ) : (
                            ""
                        )}
                        {attrValue("buttonText")}
                        {attrValue("addIcon") && attrValue("iconPosition") === "right" ? (
                            <EBDisplayIconEdit
                                icon={attrValue("icon")}
                                className={`eb-button-icon eb-button-icon-left hvr-icon`}
                            />
                        ) : (
                            ""
                        )}

                        {loadingIcon && (
                            <img className="eb-install-loader" src={`${EssentialBlocksLocalize.eb_plugins_url}/assets/images/loading.svg`} />
                        )}
                    </button>
                )}

                {type !== 'button' && (
                    <>
                        <a
                            className={`eb-button-anchor${attrValue("hoverEffect") ? ` ${attrValue("hoverEffect")}` : ""
                                } ${className?.length > 0 ? className : ""} ${liquidGlassClasses}`}
                        >
                            {applyFilters("eb_liquid_glass_effect_pro_content", "", attributes, "btnLiquidGlass")}
                            {attrValue("addIcon") && attrValue("iconPosition") === "left" ? (
                                <EBDisplayIconEdit
                                    icon={attrValue("icon")}
                                    className={`eb-button-icon eb-button-icon-left hvr-icon`}
                                />
                            ) : (
                                ""
                            )}
                            <DynamicInputValueHandler
                                tagName={tagName}
                                placeholder={__("Add Text..", "essential-blocks")}
                                className={`eb-button-text${className?.length > 0 ? ` ${className}-text` : ""
                                    }`}
                                value={attrValue("buttonText")}
                                onChange={(newText) =>
                                    setAttributes({ [getAttrName("buttonText")]: newText })
                                }
                                allowedFormats={["core/bold", "core/italic", "core/strikethrough"]}
                            />
                            {attrValue("addIcon") && attrValue("iconPosition") === "right" ? (
                                <EBDisplayIconEdit
                                    icon={attrValue("icon")}
                                    className={`eb-button-icon eb-button-icon-left hvr-icon`}
                                />
                            ) : (
                                ""
                            )}
                        </a>
                    </>
                )}
            </div>
            {urlInput && isSelected && !isDynamic && (
                <div className="eb-button-link">
                    <Dashicon icon="admin-links" />
                    <URLInput
                        value={attrValue("buttonURL")}
                        onChange={(newURL) =>
                            setAttributes({ [getAttrName("buttonURL")]: newURL })
                        }
                    />
                </div>
            )}
        </>
    );
};

EBButton.Content = (props) => {
    const {
        attributes,
        className,
        buttonAttrProps,
        attrPrefix = "",
        tagName = "",
        type,
        buttonData,
        disable = false,
        loadingIcon = false,
        btnWrapperClassName = ''
    } = props;

    const getAttrName = (attrKey) => {
        const finalKey =
            buttonAttrProps && buttonAttrProps[attrKey]
                ? buttonAttrProps[attrKey]
                : attrKey;

        if (buttonAttrProps && buttonAttrProps[attrKey]) {
            return finalKey;
        }

        return attrPrefix
            ? `${attrPrefix}${upperFirst(camelCase(finalKey))}`
            : finalKey;
    };
    // const attrValue = (attrKey) => {
    //     return getAttrName(attrKey) && attributes[getAttrName(attrKey)] ? attributes[getAttrName(attrKey)] : ''
    // };

    const attrValue = (attrKey) => {
        const attrName = getAttrName(attrKey);
        return attributes && attrName in attributes ? attributes[attrName] : '';
    };

    // Generate liquid glass classes
    const liquidGlassClasses = attrValue("btnLiquidGlass")?.enable
        ? `eb_liquid_glass-${attrValue("btnLiquidGlass").effect} eb_liquid_glass_shadow-${attrValue("btnLiquidGlass").shadowEffect}`
        : '';

    let dataAttributes;

    if (buttonData !== undefined) {
        dataAttributes = Object.keys(buttonData).reduce((acc, key) => {
            acc[`data-${key.replace(/([A-Z])/g, "-$1").toLowerCase()}`] = buttonData[key];
            return acc;
        }, {});
    }

    return (
        <>
            <div className={`eb-button-inner-wrapper ${btnWrapperClassName}`}>
                {type == 'button' && (
                    <button
                        className={`eb-button-loadmore${attrValue("hoverEffect") ? ` ${attrValue("hoverEffect")}` : ""
                            } ${className?.length > 0 ? className : ""} ${liquidGlassClasses}`}
                        {...dataAttributes}
                        disabled={disable}
                    >
                        {applyFilters("eb_liquid_glass_effect_pro_content", "", attributes, "btnLiquidGlass")}
                        {attrValue("addIcon") && attrValue("iconPosition") === "left" ? (
                            <EBDisplayIconSave
                                icon={attrValue("icon")}
                                className={"eb-button-icon eb-button-icon-left hvr-icon"}
                            />
                        ) : (
                            ""
                        )}
                        {attrValue("buttonText")}
                        {attrValue("addIcon") && attrValue("iconPosition") === "right" ? (
                            <EBDisplayIconSave
                                icon={attrValue("icon")}
                                className={"eb-button-icon eb-button-icon-right hvr-icon"}
                            />
                        ) : (
                            ""
                        )}

                        {loadingIcon && (
                            <img className="eb-install-loader" src={`${EssentialBlocksLocalize.eb_plugins_url}/assets/images/loading.svg`} />
                        )}
                    </button>
                )}
                {type !== 'button' && (
                    <a
                        className={`eb-button-anchor${attrValue("hoverEffect") ? ` ${attrValue("hoverEffect")}` : ""
                            } ${className?.length > 0 ? className : ""} ${liquidGlassClasses}`}
                        href={attrValue("buttonURL") ? sanitizeURL(attrValue("buttonURL")) : ""}
                        {...(attrValue("newWindow") && { target: "_blank" })}
                        download={attrValue("addDownload") ? true : undefined}
                        rel={attrValue("addNofollow") ? "nofollow noopener" : "noopener"}
                    >
                        {applyFilters("eb_liquid_glass_effect_pro_content", "", attributes, "btnLiquidGlass")}
                        {attrValue("addIcon") && attrValue("iconPosition") === "left" ? (
                            <EBDisplayIconSave
                                icon={attrValue("icon")}
                                className={"eb-button-icon eb-button-icon-left hvr-icon"}
                            />
                        ) : (
                            ""
                        )}
                        <RichText.Content
                            tagName={tagName}
                            value={attrValue("buttonText")}
                            className={`eb-button-text${className?.length > 0 ? ` ${className}-text` : ""
                                }`}
                        />
                        {attrValue("addIcon") && attrValue("iconPosition") === "right" ? (
                            <EBDisplayIconSave
                                icon={attrValue("icon")}
                                className={"eb-button-icon eb-button-icon-right hvr-icon"}
                            />
                        ) : (
                            ""
                        )}
                    </a>
                )}
            </div>
        </>
    );
};

EBButton.addAttributes = addAttributes;
EBButton.GeneralTab = GeneralTab;
EBButton.StyleTab = StyleTab;
EBButton.Style = Style;
