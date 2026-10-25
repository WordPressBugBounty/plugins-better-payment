import { __ } from "@wordpress/i18n";
import { ToggleControl } from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";

import ProSelectControl from "../pro-select-control";
import ColorControl from "../color-control";
import ResponsiveRangeController from "../responsive-range-control";
import { ShapeDividerList } from "../../helpers/shapeDividerList";
import { useBlockAttributes, useBlockSetAttributes } from "../../hooks";
const ShapeDividerControl = ({ position, controlName }) => {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

    const dividerPosition =
        position == undefined || position != "" ? "top" : "bottom";

    const {
        [`${controlName}Type`]: dividerType = "style_1",
        [`${controlName}Color`]: dividerColor,
        [`${controlName}Flip`]: dividerFlip,
        [`${controlName}Invert`]: dividerInvert,
        [`${controlName}BringFront`]: dividerBringFront,
    } = attributes;

    const TYPES = [
        { label: __("None", "essential-blocks"), value: "" },
        {
            label: __("Ocean Wave", "essential-blocks"),
            value: "style_1",
        },
        {
            label: __("Asymmetric Triangle [Left]", "essential-blocks"),
            value: "style_2",
        },
        {
            label: __("Abstract Paintbrush", "essential-blocks"),
            value: "style_3",
        },
        {
            label: __("Asymmetric Curve (Inverted)", "essential-blocks"),
            value: "style_4",
        },
        {
            label: __("Cloud (Pro)", "essential-blocks"),
            value: "style_1_pro",
            isPro: true,
        },
        {
            label: __("Mountain (Pro)", "essential-blocks"),
            value: "style_2_pro",
            isPro: true,
        },
        {
            label: __("Fire (Pro)", "essential-blocks"),
            value: "style_3_pro",
            isPro: true,
        },
        {
            label: __("Sports (Pro)", "essential-blocks"),
            value: "style_4_pro",
            isPro: true,
        },
        {
            label: __("Travel (Pro)", "essential-blocks"),
            value: "style_5_pro",
            isPro: true,
        },
    ];
    const shapeLists = ShapeDividerList();
    const isNegative = shapeLists[dividerType]?.negative;

    return (
        <>
            {dividerPosition == "top" && (
                <>
                    <ProSelectControl
                        label={__("Type", "essential-blocks")}
                        value={dividerType}
                        options={applyFilters("eb_shape_divider_types", TYPES)}
                        onChange={(selected) =>
                            setAttributes({
                                [`${controlName}Type`]: selected,
                            })
                        }
                    />
                    {dividerType !== "" ? (
                        <>
                            <ColorControl
                                label={__("Color", "essential-blocks")}
                                color={dividerColor}
                                onChange={(color) =>
                                    setAttributes({
                                        [`${controlName}Color`]: color,
                                    })
                                }
                            />
                            <ResponsiveRangeController
                                baseLabel={__("Width", "essential-blocks")}
                                controlName={`${controlName}Width`}
                                noUnits
                                min={100}
                                max={300}
                                step={1}
                            />
                            <ResponsiveRangeController
                                baseLabel={__("Height", "essential-blocks")}
                                controlName={`${controlName}Height`}
                                noUnits={true}
                                min={0}
                                max={500}
                                step={1}
                            />
                            <ToggleControl
                                label={__("Flip", "essential-blocks")}
                                checked={dividerFlip}
                                onChange={() =>
                                    setAttributes({
                                        [`${controlName}Flip`]: !dividerFlip,
                                    })
                                }
                            />
                            {isNegative && (
                                <ToggleControl
                                    label={__("Invert", "essential-blocks")}
                                    checked={dividerInvert}
                                    onChange={() =>
                                        setAttributes({
                                            [`${controlName}Invert`]: !dividerInvert,
                                        })
                                    }
                                />
                            )}
                            <ToggleControl
                                label={__("Bring to Front", "essential-blocks")}
                                checked={dividerBringFront}
                                onChange={() =>
                                    setAttributes({
                                        [`${controlName}BringFront`]: !dividerBringFront,
                                    })
                                }
                            />
                        </>
                    ) : (
                        ""
                    )}
                </>
            )}
        </>
    );
};

export default ShapeDividerControl;
