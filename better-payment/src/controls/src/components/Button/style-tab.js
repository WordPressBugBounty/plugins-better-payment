/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    BaseControl,
    SelectControl,
    RangeControl,
    __experimentalDivider as Divider,
} from "@wordpress/components";

/**
 * Internal depencencies
 */
import {
    HOVER_EFFECT,
} from "./constants";

import {
    InspectorPanel,
    TypographyDropdown,
    ColorControl,
    BackgroundControl,
    BorderShadowControl,
    ResponsiveDimensionsControl,
    LiquidGlassEffectControl
} from "../..";
import { ToggleControl } from "@wordpress/components";
import { useBlockSetAttributes, useBlockAttributes } from "../../index";
import {
    camelCase,
    upperFirst,
} from 'lodash'

export const StyleTab = (props) => {
    const {
        label,
        buttonAttrProps,
        attrPrefix = "",
        hasTypography = true,
        typography = "text",
        hasBackground = true,
        background = "btnBack",
        hasBorder = true,
        border = "btnBrdShw",
        hasPadding = true,
        padding = "btnPadding",
        hasHoverEffect = true,
        hasLiquidGlass = true,
        liquidGlassAttr = "btnLiquidGlass",
        liquidGlassShadowAttr = "btnliquidGlassShadowEffectBorder"
    } = props;

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

    return (
        <>
            <InspectorPanel.PanelBody
                title={label ? label : __("Styles", "essential-blocks")}
                initialOpen={true}
            >
                <>
                    {hasTypography && (
                        <>
                            <TypographyDropdown
                                baseLabel={__("Typography", "essential-blocks")}
                                typographyPrefixConstant={typography}
                            />
                        </>
                    )}

                    <ColorControl
                        label={__("Text Color", "essential-blocks")}
                        color={attrValue('textColor')}
                        attributeName={getAttrName('textColor')}
                    />
                    <ColorControl
                        label={__("Text Hover Color", "essential-blocks")}
                        color={attrValue('hoverTextColor')}
                        attributeName={getAttrName('hoverTextColor')}
                    />
                    {hasBackground && (
                        <>
                            <Divider />
                            <BaseControl>
                                <h3 className="eb-control-title">
                                    {__("Background", "essential-blocks")}
                                </h3>
                            </BaseControl>
                            <BackgroundControl
                                controlName={background}
                                noOverlay={true}
                                noMainBgi={true}
                            />
                        </>
                    )}
                    {hasBorder && (
                        <>
                            <Divider />
                            <BaseControl>
                                <h3 className="eb-control-title">
                                    {__("Border", "essential-blocks")}
                                </h3>
                            </BaseControl>
                            <BorderShadowControl controlName={border} />
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
                    {hasHoverEffect && (
                        <>
                            <SelectControl
                                label={__("Hover Effect", "essential-blocks")}
                                value={attrValue('hoverEffect')}
                                options={HOVER_EFFECT}
                                onChange={(newHoverEffect) =>
                                    setAttributes({
                                        [getAttrName('hoverEffect')]: newHoverEffect,
                                    })
                                }
                            />
                            {attrValue('hoverEffect') && (
                                <RangeControl
                                    label={__("Hover Transition", "essential-blocks")}
                                    value={attrValue('hoverTransition')}
                                    onChange={(hoverTransition) =>
                                        setAttributes({
                                            [getAttrName('hoverTransition')]: hoverTransition,
                                        })
                                    }
                                    step={0.01}
                                    min={0}
                                    max={5}
                                />
                            )}
                        </>
                    )}
                </>
            </InspectorPanel.PanelBody>
            {hasLiquidGlass && attrValue(liquidGlassAttr).enable && (
                <InspectorPanel.PanelBody
                    title={__("Button Liquid Glass", "essential-blocks")}
                    initialOpen={false}
                >
                    <LiquidGlassEffectControl
                        attributeName={getAttrName(liquidGlassAttr)}
                        shadowAttributeName={getAttrName(liquidGlassShadowAttr)}
                    />
                </InspectorPanel.PanelBody>
            )}
        </>
    );
};
