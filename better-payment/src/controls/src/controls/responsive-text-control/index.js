import UnitControl from "../unit-control";
import { WithResponsiveOptions } from "../../components";
import { useBlockAttributes, useBlockSetAttributes, useDeviceType } from "../../hooks";
const ResponsiveTextController = ({
    controlName,
    baseLabel,
    useUnits,
    isNumber
}) => {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const resOption = useDeviceType();
    const prefixes = {
        Desktop: "",
        Tablet: "TAB",
        Mobile: "MOB"
    };

    const getAttributeValue = (prefix, suffix) => attributes[`${prefix}${controlName}${suffix}`];

    const dimensionValues = {
        Desktop: {
            unit: getAttributeValue(prefixes.Desktop, "Unit"),
            value: getAttributeValue(prefixes.Desktop, "Value"),
        },
        Tablet: {
            unit: getAttributeValue(prefixes.Tablet, "Unit"),
            value: getAttributeValue(prefixes.Tablet, "Value"),
        },
        Mobile: {
            unit: getAttributeValue(prefixes.Mobile, "Unit"),
            value: getAttributeValue(prefixes.Mobile, "Value"),
        },
    };

    const UNITS = [
        { label: "px", value: "px" },
        { label: "em", value: "em" },
        { label: "%", value: "%" },
    ];

    const neededProps = {
        resOption,
        baseLabel,
        setAttributes,
        controlName,
        isNumber
    };

    const renderControls = (device) => (
        <>
            {useUnits && (
                <UnitControl
                    selectedUnit={dimensionValues[device].unit}
                    unitTypes={UNITS}
                    onClick={(unit) =>
                        setAttributes({ [`${prefixes[device]}${controlName}Unit`]: unit })
                    }
                />
            )}
            <div className="eb-responsive-text-controler dimention-container">
                <WithResponsiveOptions
                    label={baseLabel}
                    hasReset={true}
                    hasUnits={true}
                    attributeName={`${controlName}Value`}
                    unitAttributeName={`${controlName}Unit`}
                >
                    <div className={`input-container`}>
                        <div className="text-input-wrapper">
                            <input
                                type={isNumber ? "number" : "text"}
                                name="value"
                                value={dimensionValues[device].value || ''}
                                onChange={(event) =>
                                    setAttributes({ [`${prefixes[device]}${controlName}Value`]: event.target.value })
                                }
                            />
                        </div>
                    </div>
                </WithResponsiveOptions>
            </div>
        </>
    );
    return (
        <>
            {renderControls(resOption)}
        </>
    );
};
export default ResponsiveTextController;
