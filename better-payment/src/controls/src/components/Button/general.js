/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    Button,
    ButtonGroup,
    BaseControl,
    ToggleControl,
} from "@wordpress/components";
import { useState } from "@wordpress/element";

/**
 * Internal depencencies
 */
import {
    CONTENT_POSITION,
    BUTTON_WIDTH,
    FIXED_WIDTH,
    ICON_POSITION,
    ICON_SIZE,
    ICON_SPACE,
} from "./constants";
import { camelCase, upperFirst } from "lodash";

import {
    InspectorPanel,
    ResponsiveRangeController,
    EBIconPicker,
} from "../../";
import { DynamicInputControl } from "../../controls/dynamic-field";
import { useBlockSetAttributes, useBlockAttributes } from "../../index";

export const GeneralTab = (props) => {
    const {
        buttonAttrProps,
        attrPrefix = "",
        hasIcon = true,
        hasAlignment = true,
        hasWidth = true,
        label,
        type,
        isDynamic = false,
        liquidGlassAttr = "btnLiquidGlass",
        hasLiquidGlass = true
    } = props;

    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

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
    const attrValue = (attrKey) => attributes[getAttrName(attrKey)];

    const handleURLChange = (url) => {
        setAttributes({ [getAttrName("buttonURL")]: url });
    };

    return (
        <>
            <InspectorPanel.PanelBody
                title={label ? label : __("Button Settings", "essential-blocks")}
                initialOpen={false}
            >
                <DynamicInputControl
                    label={__("Button Text", "essential-blocks")}
                    attrName={getAttrName("buttonText")}
                    inputValue={attrValue("buttonText")}
                    setAttributes={setAttributes}
                    onChange={(text) =>
                        setAttributes({ [getAttrName("buttonText")]: text })
                    }
                />
                {type !== "button" && !isDynamic && (
                    <>
                        <DynamicInputControl
                            label={__("URL", "essential-blocks")}
                            attrName={getAttrName("buttonURL")}
                            inputValue={attrValue("buttonURL")}
                            setAttributes={setAttributes}
                            onChange={handleURLChange}
                            enableAi={false}
                            fieldType="url"
                            showValidation={true}
                            enableSecurity={true}
                        />
                        {attrValue("buttonURL") && !isDynamic && (
                            <>
                                <ToggleControl
                                    label={__("Open in New Tab", "essential-blocks")}
                                    checked={attrValue("newWindow")}
                                    onChange={() =>
                                        setAttributes({
                                            [getAttrName("newWindow")]: !attrValue("newWindow"),
                                        })
                                    }
                                />
                                <ToggleControl
                                    label={__("Add nofollow", "essential-blocks")}
                                    checked={attrValue("addNofollow")}
                                    onChange={() =>
                                        setAttributes({
                                            [getAttrName("addNofollow")]: !attrValue("addNofollow"),
                                        })
                                    }
                                />
                                <ToggleControl
                                    label={__("Add Download", "essential-blocks")}
                                    checked={attrValue("addDownload")}
                                    onChange={() =>
                                        setAttributes({
                                            [getAttrName("addDownload")]: !attrValue("addDownload"),
                                        })
                                    }
                                />
                            </>
                        )}
                    </>
                )}

                {hasAlignment && (
                    <>
                        <BaseControl label={__("Alignment", "essential-blocks")}>
                            <ButtonGroup id="eb-button-group-alignment">
                                {CONTENT_POSITION.map((item, index) => (
                                    <Button
                                        key={index}
                                        isPrimary={attrValue("buttonAlign") === item.value}
                                        isSecondary={attrValue("buttonAlign") !== item.value}
                                        onClick={() =>
                                            setAttributes({
                                                [getAttrName("buttonAlign")]: item.value,
                                            })
                                        }
                                    >
                                        {item.label}
                                    </Button>
                                ))}
                            </ButtonGroup>
                        </BaseControl>
                    </>
                )}
                {hasWidth && (
                    <>
                        <BaseControl label={__("Width", "essential-blocks")}>
                            <ButtonGroup id="eb-button-group-alignment">
                                {BUTTON_WIDTH.map((item, index) => (
                                    <Button
                                        key={index}
                                        isPrimary={attrValue("buttonWidth") === item.value}
                                        isSecondary={attrValue("buttonWidth") !== item.value}
                                        onClick={() =>
                                            setAttributes({
                                                [getAttrName("buttonWidth")]: item.value,
                                            })
                                        }
                                    >
                                        {item.label}
                                    </Button>
                                ))}
                            </ButtonGroup>
                        </BaseControl>
                        {attrValue("buttonWidth") === "fixed" && (
                            <ResponsiveRangeController
                                baseLabel={__("Fixed Width", "essential-blocks")}
                                controlName={FIXED_WIDTH}
                                min={100}
                                max={900}
                                step={1}
                            />
                        )}
                    </>
                )}
                {hasIcon && (
                    <>
                        <InspectorPanel.PanelBody
                            title={__("Icon", "essential-blocks")}
                            initialOpen={false}
                        >
                            <ToggleControl
                                label={__("Add icon", "essential-blocks")}
                                checked={attrValue("addIcon")}
                                onChange={() =>
                                    setAttributes({
                                        [getAttrName("addIcon")]: !attrValue("addIcon"),
                                    })
                                }
                            />
                            {attrValue("addIcon") && (
                                <>
                                    <EBIconPicker
                                        value={attrValue("icon")}
                                        attributeName={getAttrName("icon")}
                                    />
                                    <BaseControl label={__("Icon Postion", "essential-blocks")}>
                                        <ButtonGroup id="eb-button-group-alignment">
                                            {ICON_POSITION.map((item, index) => (
                                                <Button
                                                    key={index}
                                                    isPrimary={attrValue("iconPosition") === item.value}
                                                    isSecondary={attrValue("iconPosition") !== item.value}
                                                    onClick={() =>
                                                        setAttributes({
                                                            [getAttrName("iconPosition")]: item.value,
                                                        })
                                                    }
                                                >
                                                    {item.label}
                                                </Button>
                                            ))}
                                        </ButtonGroup>
                                    </BaseControl>
                                    <ResponsiveRangeController
                                        baseLabel={__("Size", "essential-blocks")}
                                        controlName={ICON_SIZE}
                                        noUnits={true}
                                    />
                                    <ResponsiveRangeController
                                        baseLabel={__("Gap", "essential-blocks")}
                                        controlName={ICON_SPACE}
                                        noUnits={true}
                                    />
                                </>
                            )}
                        </InspectorPanel.PanelBody>
                    </>
                )}

                {hasLiquidGlass && (
                    <>
                        <ToggleControl
                            label={__("Enable Liquid Glass Effect", "essential-blocks")}
                            checked={attrValue(liquidGlassAttr).enable}
                            onChange={(enable) =>
                                setAttributes({
                                    [getAttrName(liquidGlassAttr)]: {
                                        ...attrValue(liquidGlassAttr),
                                        enable: enable,
                                    },
                                })
                            }
                        />
                    </>
                )}
            </InspectorPanel.PanelBody>
        </>
    );
};
