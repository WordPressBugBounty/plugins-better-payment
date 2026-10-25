import { useCallback } from "@wordpress/element";
import { useBlockDefaultAttributes, useBlockSetAttributes, useDeviceType } from "../hooks";
import { useDispatch } from "@wordpress/data";

export const WithResponsiveOptions = ({
    children,
    label,
    attributeName = '',
    unitAttributeName = '',
    hasUnits = false,
    hasReset = false
}) => {
    const resOption = useDeviceType();
    const defaultAttributes = useBlockDefaultAttributes();
    const setAttributes = useBlockSetAttributes();
    const dispatch = useDispatch();

    const onReset = useCallback(() => {
        const defaultRange = defaultAttributes[attributeName]?.default;
        const defaultUnit = defaultAttributes[unitAttributeName]?.default || "px";

        let attributesToUpdate = {};
        if (!hasUnits) {
            attributesToUpdate = {
                "Desktop": { [attributeName]: defaultRange },
                "Tablet": { [`TAB${attributeName}`]: defaultRange },
                "Mobile": { [`MOB${attributeName}`]: defaultRange }
            };
        } else {
            // Always reset both range and unit to defaults for current device
            const rangeAttr = resOption === "Desktop" ? attributeName : `${resOption === "Tablet" ? "TAB" : "MOB"}${attributeName}`;
            const unitAttr = resOption === "Desktop" ? unitAttributeName : `${resOption === "Tablet" ? "TAB" : "MOB"}${unitAttributeName}`;

            attributesToUpdate = {
                [rangeAttr]: defaultRange,
                [unitAttr]: defaultUnit
            };
        }

        const attributes = hasUnits ? attributesToUpdate : attributesToUpdate[resOption];
        if (attributes) {
            setAttributes({ ...attributes });
        }

    }, [resOption, attributeName, hasUnits, setAttributes, unitAttributeName, defaultAttributes]);

    const handleResponsiveOptionChange = (option) => {
        dispatch('core/editor').setDeviceType(option);
    };

    return (
        <div className={`responsive-btn-wrapper`}>
            <div className="responsive-btn">
                <span className="responsive-btn-label components-base-control__label">
                    {label}
                </span>
                <span
                    onClick={() => handleResponsiveOptionChange('Desktop')}
                    className={`typoResButton dashicons dashicons-desktop ${resOption === "Desktop" ? "active" : " "
                        }`}
                ></span>
                <span
                    onClick={() => handleResponsiveOptionChange('Tablet')}
                    className={`typoResButton dashicons dashicons-tablet ${resOption === "Tablet" ? "active" : " "
                        }`}
                ></span>
                <span
                    onClick={() => handleResponsiveOptionChange('Mobile')}
                    className={`typoResButton dashicons dashicons-smartphone ${resOption === "Mobile" ? "active" : " "
                        }`}
                ></span>
            </div>
            <div className="eb-component-wrapper">
                {children}
                {
                    hasReset && (
                        <button className="eb-range-reset-button" onClick={onReset}>
                            <span className="dashicon dashicons dashicons-image-rotate"></span>
                        </button>
                    )
                }
            </div>
        </div>
    );
}
