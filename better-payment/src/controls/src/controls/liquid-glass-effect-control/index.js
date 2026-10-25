import { __ } from "@wordpress/i18n";
import {
    ToggleControl,
    RangeControl,
    PanelRow,
    SelectControl,
    __experimentalDivider as Divider,
} from "@wordpress/components";
import { memo } from "@wordpress/element";
import { applyFilters } from "@wordpress/hooks";

/**
 * Internal dependencies
 */
import { ColorControl, ProSelectControl, BorderShadowControl } from "../../index";
import { useBlockAttributes, useBlockSetAttributes } from "../../hooks";

/**
 * LiquidGlassEffectControl Component
 *
 * Provides a simple toggle control for enabling/disabling liquid glass effects.
 * Works with custom boolean attributes for simplified implementations.
 *
 * @param {Object} props - Component props
 * @param {string} props.attributeName - Name of the boolean attribute to control
 */
const LiquidGlassEffectControl = ({
    attributeName,
    shadowAttributeName
}) => {

    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

    const updateAttribute = (name, value) => {
        setAttributes({
            [attributeName]: {
                ...attributes[attributeName],
                [name]: value,
            }
        });
    }

    const handleEffect = (value) => {
        updateAttribute('effect', value);

        applyFilters("eb_liquid_glass_effect_change", value, attributes, setAttributes, attributeName);

        switch (value) {
            case "effect1":
                setAttributes({
                    [attributeName]: {
                        ...attributes[attributeName],
                        effect: "effect1",
                        backgroundColor: "#FFFFFF1F",
                        backdropFilter: 24,
                        brightness: 1,
                    },

                    [shadowAttributeName + "Rds_Bottom"]: "24",
                    [shadowAttributeName + "Rds_Left"]: "24",
                    [shadowAttributeName + "Rds_Right"]: "24",
                    [shadowAttributeName + "Rds_Top"]: "24",
                    [shadowAttributeName + "Rds_Unit"]: "px",
                    [shadowAttributeName + "Rds_isLinked"]: true,

                })
                break;
            case "effect2":
                setAttributes({
                    [attributeName]: {
                        ...attributes[attributeName],
                        effect: "effect2",
                        backgroundColor: "#753B3B1F",
                        backdropFilter: 20,
                        brightness: 1,
                    }
                })
                break;
        }
    }

    const handleShadowEffect = (value) => {
        updateAttribute('shadowEffect', value);

        switch (value) {
            case "effect1":
                setAttributes({
                    [shadowAttributeName + "Rds_Bottom"]: "24",
                    [shadowAttributeName + "Rds_Left"]: "24",
                    [shadowAttributeName + "Rds_Right"]: "24",
                    [shadowAttributeName + "Rds_Top"]: "24",
                    [shadowAttributeName + "Rds_Unit"]: "px",
                    [shadowAttributeName + "Rds_isLinked"]: true,
                })
                break;
            case "effect2":
                setAttributes({
                    [shadowAttributeName + "Rds_Bottom"]: "16",
                    [shadowAttributeName + "Rds_Left"]: "16",
                    [shadowAttributeName + "Rds_Right"]: "16",
                    [shadowAttributeName + "Rds_Top"]: "16",
                    [shadowAttributeName + "Rds_Unit"]: "px",
                    [shadowAttributeName + "Rds_isLinked"]: true,
                })
                break;
            case "effect3":
                setAttributes({
                    [shadowAttributeName + "Rds_Bottom"]: "8",
                    [shadowAttributeName + "Rds_Left"]: "8",
                    [shadowAttributeName + "Rds_Right"]: "8",
                    [shadowAttributeName + "Rds_Top"]: "8",
                    [shadowAttributeName + "Rds_Unit"]: "px",
                    [shadowAttributeName + "Rds_isLinked"]: true,
                })
                break;
            case "effect4":
                setAttributes({
                    [shadowAttributeName + "Rds_Bottom"]: "24",
                    [shadowAttributeName + "Rds_Left"]: "24",
                    [shadowAttributeName + "Rds_Right"]: "24",
                    [shadowAttributeName + "Rds_Top"]: "24",
                    [shadowAttributeName + "Rds_Unit"]: "px",
                    [shadowAttributeName + "Rds_isLinked"]: true,
                })
                break;
        }
    }

    return (
        <>
            {attributes[attributeName].enable && (
                <>
                    <PanelRow>
                        <p className="eb-liquid-glass-effect-helper">
                            <strong>{__("Recommended:", "essential-blocks")}</strong>{" "}
                            {__(
                                "Use Liquid Glass inside a wrapper with a fixed background image, set your block as an inner block, make the block background transparent, and adjust/reset margin or padding if needed for a smoother effect.",
                                "essential-blocks",
                            )}
                        </p>
                    </PanelRow>

                    <ProSelectControl
                        label={__("Liquid Glass Presets", "essential-blocks")}
                        value={attributes[attributeName].effect}
                        options={[
                            { label: __("Heavy Frost", "essential-blocks"), value: "effect1" },
                            { label: __("Soft Mist", "essential-blocks"), value: "effect2" },
                            { label: __("Light Frost (Pro)", "essential-blocks"), value: "effect3", isPro: true },
                            { label: __("Grain Frost (Pro)", "essential-blocks"), value: "effect4", isPro: true },
                            { label: __("Fine Frost (Pro)", "essential-blocks"), value: "effect5", isPro: true },
                        ]}
                        onChange={handleEffect}
                    />

                    <ColorControl
                        label={__("Background Color", "essential-blocks")}
                        color={attributes[attributeName].backgroundColor}
                        onChange={(value) => updateAttribute('backgroundColor', value)}
                    />

                    <RangeControl
                        label={__("Backdrop Filter", "essential-blocks")}
                        value={attributes[attributeName].backdropFilter}
                        onChange={(value) => updateAttribute('backdropFilter', value)}
                        min={0}
                        max={50}
                        step={1}
                    />

                    {attributes[attributeName].effect === "effect2" && (
                        <RangeControl
                            label={__("Brightness", "essential-blocks")}
                            value={attributes[attributeName].brightness}
                            onChange={(value) => updateAttribute('brightness', value)}
                            min={0}
                            max={5}
                            step={0.1}
                        />
                    )}

                    {applyFilters("eb_liquid_glass_effect_pro_control", "", attributes, setAttributes, attributeName)}

                    <SelectControl
                        label={__("Shadow Effects", "essential-blocks")}
                        value={attributes[attributeName].shadowEffect}
                        options={[
                            { label: __("Effect 1", "essential-blocks"), value: "effect1" },
                            { label: __("Effect 2", "essential-blocks"), value: "effect2" },
                            { label: __("Effect 3", "essential-blocks"), value: "effect3" },
                            { label: __("Effect 4", "essential-blocks"), value: "effect4" },
                        ]}
                        onChange={(value) => handleShadowEffect(value)}
                    />

                    <Divider />
                    <PanelRow>
                        <h3 className="eb-control-title">{__("Shadow Settings", "essential-blocks")}</h3>
                    </PanelRow>
                    <BorderShadowControl controlName={shadowAttributeName} noBdrHover noShdowHover />
                </>
            )}
        </>
    );
};

export default memo(LiquidGlassEffectControl);
