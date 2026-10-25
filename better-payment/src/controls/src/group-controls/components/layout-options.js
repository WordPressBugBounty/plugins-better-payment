import { __ } from "@wordpress/i18n";
import { SelectControl, RangeControl } from "@wordpress/components";
import ResponsiveTextController from "../../controls/responsive-text-control";
import { InspectorPanel } from "../../index";
import { applyFilters } from "@wordpress/hooks";

export default function LayoutOptions(props) {
    const { attributes, setAttributes, resOption } = props;
    const objAttributes = attributes;
    const resRequiredProps = {
        attributes: attributes,
        setAttributes: setAttributes,
        resOption: resOption,
        objAttributes: objAttributes,
    };
    const { wrapperOverflow } = attributes;

    return (
        <>
            <InspectorPanel.PanelBody
                title={__("Visibility Options", "essential-blocks")}
                initialOpen={false}
            >
                <ResponsiveTextController
                    controlName="zIndex"
                    baseLabel={__("Z-Index", "essential-blocks")}
                    useUnits={false}
                    isNumber="true"
                />

                <SelectControl
                    label={__("Overflow", "essential-blocks")}
                    value={wrapperOverflow}
                    options={[
                        { label: "Default", value: "" },
                        { label: "Auto", value: "auto" },
                        { label: "Hidden", value: "hidden" },
                        { label: "Visible", value: "visible" },
                        { label: "Scroll", value: "scroll" },
                    ]}
                    onChange={(newWrapperOverflow) =>
                        setAttributes({ wrapperOverflow: newWrapperOverflow })
                    }
                />
                {EssentialBlocksLocalize?.is_pro_active === "false" ? (
                    <>
                        <div className="eb-pro-control-wrapper">
                            <RangeControl
                                label={
                                    <>
                                        {__("Opacity", "essential-blocks")}
                                        <span
                                            className="dashicons dashicons-lock eb-pro-title-icon"
                                            style={{ marginLeft: 6 }}
                                        />
                                    </>
                                }
                                value={1}
                                min={0}
                                max={1}
                                step={0.1}
                                onChange={() => { }}
                                disabled
                            />
                            <SelectControl
                                label={
                                    <>
                                        {__("Position", "essential-blocks")}
                                        <span
                                            className="dashicons dashicons-lock eb-pro-title-icon"
                                            style={{ marginLeft: 6 }}
                                        />
                                    </>
                                }
                                value=""
                                options={[
                                    { label: __("Default", "essential-blocks"), value: "" },
                                    {
                                        label: __("Relative", "essential-blocks"),
                                        value: "relative",
                                    },
                                    {
                                        label: __("Absolute", "essential-blocks"),
                                        value: "absolute",
                                    },
                                    { label: __("Fixed", "essential-blocks"), value: "fixed" },
                                    { label: __("Sticky", "essential-blocks"), value: "sticky" },
                                ]}
                                onChange={() => { }}
                                disabled
                            />
                        </div>
                        <div className="eb-is-pro-message">
                            <span className="dashicons dashicons-lock"></span>
                            <span>
                                <a
                                    href={EssentialBlocksLocalize?.upgrade_pro_url}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    {__("Upgrade to pro", "essential-blocks")}
                                </a>{" "}
                                {__("for this premium feature!", "essential-blocks")}
                            </span>
                        </div>
                    </>
                ) : (
                    applyFilters("eb_group_controls_layout", "", resRequiredProps)
                )}
            </InspectorPanel.PanelBody>
        </>
    );
}
