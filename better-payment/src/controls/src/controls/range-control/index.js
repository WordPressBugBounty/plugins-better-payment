import { RangeControl, PanelRow } from "@wordpress/components";
import UnitControl from "../unit-control";
import {
    useBlockAttributes,
    useBlockSetAttributes,
    useBlockDefaultAttributes,
    useDeviceType
} from "../../hooks";
import { WithResponsiveOptions } from "../../components";
import { memo, useState } from "@wordpress/element";

/**
 * Helper function for split range value and unit
 * @param {*} input
 * @param {*} option
 * @returns mixed
 */
const splitValue = (input, option = 'value') => {
    if (!input) return undefined
    const strInput = input.toString();

    const match = strInput.match(/^(-?\d*\.?\d+)([a-zA-Z%]*)$/); // Regex to split number and text
    if (match) {
        const value = parseFloat(match[1]);
        const unit = match[2];

        if (option === 'value') return value; // Return only the value
        if (option === 'unit') return unit;  // Return only the unit
        return [value, unit];                // Default: Return both
    }
    return null; // Return null if no match
}

const defaultUnits = [
    { label: "px", value: "px" },
    { label: "em", value: "em" },
    { label: "%", value: "%" },
];

const RangeController = ({
    label,
    value,
    attrName,
    defaultValue = undefined,
    defaultUnit = 'px',
    units,
    min,
    max,
    step,
    noUnits = false,
    onChange,
    allowReset = true,
    help
}) => {

    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const defaultAttributes = useBlockDefaultAttributes();

    const [selectedUnit, setselectedUnit] = useState(defaultUnit)

    //On Rnage Chnage
    const handleChange = (data) => {
        let updatedValue = data
        if (noUnits === false) {
            const currentUnitValue = splitValue(value, 'unit') || 'px'
            updatedValue = data + currentUnitValue
        }
        if (onChange && typeof onChange === 'function') {
            onChange(updatedValue)
        }
        else if (attributes && attrName !== undefined) {
            setAttributes({ [attributeKey]: updatedValue });
        }
    };

    //On Unit Chnage
    const handleUnitChange = (data) => {
        let currentValue = splitValue(value, 'value')
        if (!currentValue && data !== 'px') {
            currentValue = '0'
        }
        if (currentValue) {
            const updatedValue = currentValue + data
            if (onChange && typeof onChange === 'function') {
                onChange(updatedValue)
            }
            else if (attributes && attrName !== undefined) {
                setAttributes({ [attributeKey]: updatedValue });
            }
        }
    };

    const onReset = () => {
        if (onChange && typeof onChange === 'function') {
            onChange(defaultValue)
        }
        else if (attributes && attrName !== undefined) {
            const defaultRange = defaultAttributes[attributeKey]?.default;
            setAttributes({ [attributeKey]: defaultRange });
        }
    }

    //Get range value
    const getValue = () => {
        let data = value
        if (attributes && attrName !== undefined) {
            data = attributes[attrName]
        }
        return splitValue(data, 'value')
    };

    //Get unit value
    const getUnitValue = () => {
        let data = value
        if (attributes && attrName !== undefined) {
            data = attributes[attrName]
        }
        return splitValue(data, 'unit') || selectedUnit
    }

    return (
        <div className="range-controller-wrapper">
            {!noUnits && (
                <UnitControl
                    selectedUnit={getUnitValue()}
                    unitTypes={units || defaultUnits}
                    onClick={handleUnitChange}
                />
            )}

            <div className="range-controller-input-wrapper">
                <div className="range-control-inputs">
                    <RangeControl
                        label={label}
                        value={getValue()}
                        onChange={handleChange}
                        step={step}
                        min={min}
                        max={max}
                    />
                </div>

                {allowReset && (
                    <div className="range-reset">
                        <button className="eb-range-reset-button" onClick={onReset}>
                            <span className="dashicon dashicons dashicons-image-rotate"></span>
                        </button>
                    </div>
                )}
            </div>
            {help && (
                <PanelRow className="eb-help-text">{help}</PanelRow>
            )}
        </div>
    );
};

export default memo(RangeController);
