/**
 * External Dependencies
 */
import Editor from "@monaco-editor/react";
/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Suspense, useState } from "@wordpress/element";
import {
    SelectControl,
    ToggleControl,
    RangeControl,
    PanelRow,
    __experimentalDivider as Divider,
} from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";

/**
 * Internal Dependencies
 */
import AnimationControls from "../../controls/animation-control";
import {
    ResponsiveDimensionsControl,
    BackgroundControl,
    BorderShadowControl,
    useBlockAttributes,
    InspectorPanel,
    useBlockSetAttributes,
    ProSelectControl,
} from "../../index";
import LayoutOptions from "./layout-options";

import IaIcon from "./assets/ia-icon";
import TnIcon from "./assets/tn-icon";
import CdIcon from "./assets/cd-icon";
import Crown from "./assets/crown";
import ProBadge from "./assets/ProBadge";
import ColorControl from "../../controls/color-control";
import LiquidGlassEffectControl from "../../controls/liquid-glass-effect-control";

const AdvancedControls = (props) => {
    const {
        hasMargin = true,
        marginPrefix = "",
        hasPadding = true,
        paddingPrefix = "",
        hasBackground = true,
        backgroundPrefix = "",
        hasBorder = true,
        borderPrefix = "",
        disableBackgroundOverlay = false,
        disableBackgroundMainImage = false,
        disableBackgrondOverlayImage = false,
        disableBackgroundTransition = false,
        disableBorder = false,
        disableShadow = false,
        disableShdowHover = false,
        disableBorderRadius = false,
        hasInteractiveAnimation = true,
        hasAnimation = true,
        hasLiquidGlass = true,
    } = props;

    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

    const {
        customCss,
        hideOnDesktop,
        hideOnTab,
        hideOnMobile,
        resOption,
        liquidGlass,
    } = attributes;

    const [errors, setErrors] = useState([]);
    const updateCss = (value) => {
        setAttributes({ customCss: value });
    };

    const handleEditorValidation = (markers) => {
        const currentErrors = markers.filter(
            (marker) => marker.severity === monaco.MarkerSeverity.Error
        );
        setErrors(currentErrors.reverse());
        currentErrors.length === 0
            ? setAttributes({ isCustomCssError: false })
            : setAttributes({ isCustomCssError: true });
    };

    const defaultCss = `
        eb_selector {
            /*position: relative;*/
        }
        @media (max-width: 767px) {
            eb_selector {
                /*display: block;*/
            }
        }
    `;

    const IaUpgradeProContent = () => {
        return (
            <div className="eb_ie">
                <IaIcon />
                <h3>
                    <a
                        target="_blank"
                        href="https://essential-blocks.com/demo/interactive-animation/"
                    >
                        Interactive Animation
                        <span className="dashicons dashicons-external"></span>
                    </a>
                </h3>
                <p>Supercharge your website animation with interactive animation.</p>
                <a
                    className="eb_upgrade_button"
                    target="_blank"
                    href={EssentialBlocksLocalize?.upgrade_pro_url}
                >
                    <Crown />
                    Upgrade to PRO
                </a>
            </div>
        );
    };

    const CdUpgradeProContent = () => {
        return (
            <div className="eb_ie">
                <CdIcon />
                <h3>
                    <a
                        target="_blank"
                        href="https://essential-blocks.com/demo/conditional-display/"
                    >
                        Conditional Display
                        <span className="dashicons dashicons-external"></span>
                    </a>
                </h3>
                <p>Supercharge your website with Conditional Display.</p>
                <a
                    className="eb_upgrade_button"
                    target="_blank"
                    href={EssentialBlocksLocalize?.upgrade_pro_url}
                >
                    <Crown />
                    Upgrade to PRO
                </a>
            </div>
        );
    };

    const TransformAnimationUpgradePro = () => {
        return (
            <div className="eb_ie">
                <TnIcon />
                <h3>
                    <a
                        target="_blank"
                        href="https://essential-blocks.com/demo/transform-animation/"
                    >
                        {__("Transform Animation", "essential-blocks")}
                        <span className="dashicons dashicons-external"></span>
                    </a>
                </h3>
                <p>{__("Supercharge your website animation with transform animation.", "essential-blocks")}</p>
                <a
                    className="eb_upgrade_button"
                    target="_blank"
                    href={EssentialBlocksLocalize?.upgrade_pro_url}
                >
                    <Crown />
                    {__("Upgrade to PRO", "essential-blocks")}
                </a>
            </div>
        );
    };

    return (
        <>
            {applyFilters("eb_advanced_controls_top", "")}
            <LayoutOptions
                attributes={attributes}
                setAttributes={setAttributes}
                resOption={resOption}
            />
            {/*
            * Transform Animation
            */}
            <InspectorPanel.PanelBody
                title={__("Transform", "essential-blocks")}
                initialOpen={false}
                className={"eb-transform-animation eb-group-control-panelbody"}
                {...(EssentialBlocksLocalize?.is_pro_active === "false" && {
                    icon: <ProBadge />,
                })}
            >
                {applyFilters(
                    "eb_advanced_controls_transform_animation",
                    <TransformAnimationUpgradePro />,
                    props
                )}
            </InspectorPanel.PanelBody>
            {(hasMargin || hasPadding) && (
                <InspectorPanel.PanelBody
                    title={__("Margin & Padding", "essential-blocks")}
                    initialOpen={true}
                >
                    {hasMargin && (
                        <ResponsiveDimensionsControl
                            controlName={marginPrefix}
                            baseLabel="Margin"
                        />
                    )}
                    {hasPadding && (
                        <ResponsiveDimensionsControl
                            controlName={paddingPrefix}
                            baseLabel="Padding"
                        />
                    )}
                </InspectorPanel.PanelBody>
            )}

            {hasBackground && (
                <InspectorPanel.PanelBody
                    title={__("Background", "essential-blocks")}
                    initialOpen={false}
                >
                    <BackgroundControl
                        controlName={backgroundPrefix}
                        noOverlay={disableBackgroundOverlay}
                        noMainBgi={disableBackgroundMainImage}
                        noOverlayBgi={disableBackgrondOverlayImage}
                        noTransition={disableBackgroundTransition}
                    />
                </InspectorPanel.PanelBody>
            )}
            {hasBorder && (
                <InspectorPanel.PanelBody
                    title={__("Border & Shadow", "essential-blocks")}
                    initialOpen={false}
                >
                    <BorderShadowControl
                        controlName={borderPrefix}
                        noBorder={disableBorder}
                        noShadow={disableShadow}
                        noShdowHover={disableShdowHover}
                        noBorderRadius={disableBorderRadius}
                    />
                </InspectorPanel.PanelBody>
            )}

            {hasLiquidGlass && (
                <InspectorPanel.PanelBody
                    title={__("Liquid Glass Effect", "essential-blocks")}
                    initialOpen={false}
                >
                    <ToggleControl
                        label={__("Enable Liquid Glass Effect", "essential-blocks")}
                        checked={liquidGlass.enable}
                        onChange={() =>
                            setAttributes({
                                liquidGlass: { ...liquidGlass, enable: !liquidGlass.enable },
                            })
                        }
                    />

                    {liquidGlass.enable && (
                        <>
                            <LiquidGlassEffectControl
                                attributeName="liquidGlass"
                                shadowAttributeName="liquidGlassShadowEffectBorder"
                            />
                        </>
                    )}
                </InspectorPanel.PanelBody>
            )}

            <InspectorPanel.PanelBody
                title={__("Responsive Control", "essential-blocks")}
                initialOpen={false}
            >
                <ToggleControl
                    label={__("Hide on Desktop", "essential-blocks")}
                    checked={hideOnDesktop}
                    onChange={() => setAttributes({ hideOnDesktop: !hideOnDesktop })}
                />
                <ToggleControl
                    label={__("Hide on Tab", "essential-blocks")}
                    checked={hideOnTab}
                    onChange={() => setAttributes({ hideOnTab: !hideOnTab })}
                />
                <ToggleControl
                    label={__("Hide on Mobile", "essential-blocks")}
                    checked={hideOnMobile}
                    onChange={() => setAttributes({ hideOnMobile: !hideOnMobile })}
                />
            </InspectorPanel.PanelBody>

            {hasAnimation && (
                <AnimationControls
                    setAttributes={setAttributes}
                    animationData={attributes.animationData}
                />
            )}

            {hasInteractiveAnimation && (
                <InspectorPanel.PanelBody
                    title={__("Interactive Animation", "essential-blocks")}
                    initialOpen={false}
                    className={"eb-interactive-animation eb-group-control-panelbody"}
                    {...(EssentialBlocksLocalize?.is_pro_active === "false" && {
                        icon: <ProBadge />,
                    })}
                >
                    {applyFilters(
                        "eb_advanced_controls_interactive_animation",
                        <IaUpgradeProContent />,
                        props
                    )}
                </InspectorPanel.PanelBody>
            )}

            <InspectorPanel.PanelBody
                title={__("Conditional Display", "essential-blocks")}
                initialOpen={false}
                className={"eb-condition-display eb-group-control-panelbody"}
                {...(EssentialBlocksLocalize?.is_pro_active === "false" && {
                    icon: <ProBadge />,
                })}
            >
                {applyFilters(
                    "eb_advanced_controls_conditional_display",
                    <CdUpgradeProContent />,
                    props
                )}
            </InspectorPanel.PanelBody>

            {applyFilters("eb_advanced_controls_after_animation", "", props)}

            <InspectorPanel.PanelBody
                title={__("Custom CSS", "essential-blocks")}
                initialOpen={false}
            >
                <Suspense fallback={"Loading..."}>
                    <Editor
                        height="250px"
                        defaultLanguage="css"
                        defaultValue={customCss || defaultCss.trim()}
                        onChange={updateCss}
                        options={{
                            minimap: {
                                enabled: false,
                            },
                            scrollbar: { vertical: "hidden" },
                            overviewRulerLanes: 0,
                            overviewRulerBorder: false,
                            quickSuggestions: true,
                        }}
                        onValidate={handleEditorValidation}
                    />
                </Suspense>
                <p className="eb-help-text">
                    {__(
                        'add "eb_selector" prefix to target block elements',
                        "essential-blocks"
                    )}
                </p>
                {errors.length > 0 && (
                    <div className="eb-css-error-messages-wrapper">
                        {errors.map((err, idx) => (
                            <div key={idx} className="eb-css-error-item">
                                <span className="eb-error-tag">
                                    <span className="dashicons dashicons-no-alt"></span>Error on
                                    line {err.startLineNumber}:
                                </span>{" "}
                                <span className="eb-error-text">{err.message}</span>
                            </div>
                        ))}
                    </div>
                )}
            </InspectorPanel.PanelBody>
            {applyFilters("eb_advanced_controls_bottom", "")}
        </>
    );
};

export default AdvancedControls;
