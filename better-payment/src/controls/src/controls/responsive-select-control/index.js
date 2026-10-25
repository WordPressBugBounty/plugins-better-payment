import { SelectControl } from "@wordpress/components";
import { memo } from "@wordpress/element";
import {useBlockAttributes, useBlockSetAttributes, useDeviceType} from "../../hooks";
import {WithResponsiveOptions} from "../../components";

const ResponsiveSelectController = ({
    baseLabel,
    controlName,
    options,
}) => {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const resOption = useDeviceType();
    const getAttributeName = (prefix) => `${prefix}${controlName}Option`;
    const attrNames = {
        Desktop: getAttributeName(""),
        Tablet: getAttributeName("TAB"),
        Mobile: getAttributeName("MOB"),
    };

    const firstOption = options[0].value;

    const getAttributeValue = (prefix, fallback) =>
        attributes[getAttributeName(prefix)] || fallback;

    const values = {
        Desktop: getAttributeValue("", firstOption),
        Tablet: getAttributeValue("TAB", getAttributeValue("", firstOption)),
        Mobile: getAttributeValue("MOB", getAttributeValue("TAB", getAttributeValue("", firstOption))),
    };

    const renderSelectControl = (device) => (
        <div className={`responsiveSelectControl ${attributes[attrNames[device]] ? "" : "jhapsha"}`}>
            <WithResponsiveOptions
                label={baseLabel}
                hasReset={true}
                attributeName={getAttributeName('')}
            >
                <SelectControl
                    value={values[device]}
                    options={options}
                    onChange={(value) =>
                        setAttributes({
                            [attrNames[device]]: value,
                        })
                    }
                />
            </WithResponsiveOptions>
        </div>
    );
    return resOption in attrNames && renderSelectControl(resOption);
};
export default memo(ResponsiveSelectController);
