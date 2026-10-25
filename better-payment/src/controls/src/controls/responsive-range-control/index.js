import { RangeControl, Button } from "@wordpress/components";
import UnitControl from "../unit-control";
import {
    useBlockAttributes,
    useBlockSetAttributes,
    useBlockDefaultAttributes,
    useDeviceType
} from "../../hooks";
import { WithResponsiveOptions } from "../../components";
import { memo, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import "./style.scss";

const ResponsiveRangeController = ({
    baseLabel,
    controlName,
    units,
    min,
    max,
    step,
    noUnits = false,
    allowAuto = false // Enable/disable auto option
}) => {
    const setAttributes = useBlockSetAttributes();
    const resOption = useDeviceType();
    const [showSettings, setShowSettings] = useState(true);

    const {
        desktopRange,
        TABrange,
        MOBrange,
        desktopSizeUnit,
        TABsizeUnit,
        MOBsizeUnit,
        isAuto // Single auto attribute for all devices
    } = useBlockAttributes(attributes => {
        if (attributes) {
            return {
                desktopRange: attributes[`${controlName}Range`],
                TABrange: attributes[`TAB${controlName}Range`],
                MOBrange: attributes[`MOB${controlName}Range`],
                desktopSizeUnit: attributes[`${controlName}Unit`],
                TABsizeUnit: attributes[`TAB${controlName}Unit`],
                MOBsizeUnit: attributes[`MOB${controlName}Unit`],
                isAuto: attributes[`${controlName}IsAuto`]
            };
        }
    });

    const defaultUnits = [
        { label: "px", value: "px" },
        { label: "em", value: "em" },
        { label: "%", value: "%" },
    ];

    // Set auto mode
    const setAuto = () => {
        setAttributes({ [`${controlName}IsAuto`]: true });
        setShowSettings(false);
    };

    // Show custom settings and disable auto
    const showCustomSettings = () => {
        setAttributes({ [`${controlName}IsAuto`]: false });
        setShowSettings(true);
    };

    const handleChange = (value) => {
        const attributeKey = `${resOption === "Tablet" ? "TAB" : resOption === "Mobile" ? "MOB" : ""}${controlName}Range`;
        setAttributes({ [attributeKey]: value });
    };

    const getValue = () => ({
        Desktop: desktopRange,
        Tablet: TABrange,
        Mobile: MOBrange,
    }[resOption]);

    const getStep = () => {
        const deviceUnit = {
            Desktop: desktopSizeUnit,
            Tablet: TABsizeUnit,
            Mobile: MOBsizeUnit,
        }[resOption];

        return deviceUnit === 'em' ? 0.1 : step || 1;
    };

    const getMax = () => {
        if (noUnits) {
            return max;
        }
        const deviceUnit = {
            Desktop: desktopSizeUnit,
            Tablet: TABsizeUnit,
            Mobile: MOBsizeUnit,
        }[resOption];

        return deviceUnit !== 'px' ? 100 : max || 100;
    };

    const getMin = () => {
        if (noUnits) {
            return min;
        }
        const deviceUnit = {
            Desktop: desktopSizeUnit,
            Tablet: TABsizeUnit,
            Mobile: MOBsizeUnit,
        }[resOption];

        return deviceUnit !== 'px' ? 0 : min || 0;
    };

    const getSelectedUnit = () => ({
        Desktop: desktopSizeUnit,
        Tablet: TABsizeUnit,
        Mobile: MOBsizeUnit,
    }[resOption]);

    const handleUnitChange = (value) => {
        const attributeKey = `${resOption === "Tablet" ? "TAB" : resOption === "Mobile" ? "MOB" : ""}${controlName}Unit`;
        const rangeAttributeKey = `${resOption === "Tablet" ? "TAB" : resOption === "Mobile" ? "MOB" : ""}${controlName}Range`;

        // Get current range value
        const currentValue = getValue();

        // Calculate new min and max based on new unit
        const newMax = value !== 'px' ? 100 : max || 100;
        const newMin = value !== 'px' ? 0 : min || 0;

        // Clamp the current value to new min/max if needed
        if (currentValue > newMax) {
            setAttributes({
                [attributeKey]: value,
                [rangeAttributeKey]: newMax
            });
        } else if (currentValue < newMin) {
            setAttributes({
                [attributeKey]: value,
                [rangeAttributeKey]: newMin
            });
        } else {
            setAttributes({ [attributeKey]: value });
        }
    };

    return (
        <div className="responsiveRangeControllerWrapper">
            <div className="eb-range-header">

                {allowAuto && (
                    <>
                        <span className="eb-range-title">{baseLabel}</span>
                        <div className="eb-range-auto-controls">
                            <Button
                                className={`eb-auto-button ${isAuto ? 'is-active' : ''}`}
                                onClick={setAuto}
                            >
                                {__("Auto", "essential-blocks")}
                            </Button>
                            <Button
                                className="eb-settings-button"
                                onClick={showCustomSettings}
                            >
                                <span className="dashicons dashicons-admin-generic"></span>
                            </Button>
                        </div>
                    </>
                )}
            </div>

            {(!allowAuto || !isAuto) && showSettings && (
                <>
                    <div className="eb-range-control-body">
                        <div className="eb-range-control-wrapper">
                            {!noUnits && (
                                <UnitControl
                                    selectedUnit={getSelectedUnit()}
                                    unitTypes={units || defaultUnits}
                                    onClick={handleUnitChange}
                                />
                            )}
                            <WithResponsiveOptions
                                hasUnits={!noUnits}
                                label={baseLabel}
                                attributeName={`${controlName}Range`}
                                key={`${controlName}Range`}
                                unitAttributeName={`${controlName}Unit`}
                                hasReset={true}
                            >
                                <RangeControl
                                    value={getValue()}
                                    onChange={handleChange}
                                    step={getStep()}
                                    min={getMin()}
                                    max={getMax()}
                                    key={resOption}
                                />
                            </WithResponsiveOptions>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};

export default memo(ResponsiveRangeController);
