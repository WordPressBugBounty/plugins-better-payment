import { Button, ButtonGroup, BaseControl, Tooltip } from "@wordpress/components";
import { useBlockAttributes, useBlockSetAttributes, useDeviceType } from "../../hooks";
import { WithResponsiveOptions } from "../../components";
export default function ResponsiveAlignControl({ baseLabel, controlName, options, className = '' }) {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const resOption = useDeviceType()

    const alignmentKeys = {
        Desktop: controlName,
        Tablet: `TAB${controlName}`,
        Mobile: `MOB${controlName}`,
    };

    const handleClick = (key, value) => {
        setAttributes({ [key]: value });
    };

    const renderButtons = (currentAlignKey) => (
        <WithResponsiveOptions label={baseLabel} hasReset={true} attributeName={controlName}>
            <BaseControl>
                <ButtonGroup>
                    {options.map((item, index) => (
                        <Tooltip key={index} text={item.value}>
                            <Button
                                isPrimary={attributes[currentAlignKey] === item.value}
                                isSecondary={attributes[currentAlignKey] !== item.value}
                                onClick={() => handleClick(currentAlignKey, item.value)}
                            >
                                {item.label}
                            </Button>
                        </Tooltip>
                    ))}
                </ButtonGroup>
            </BaseControl>
        </WithResponsiveOptions>
    );

    const wrapperClassName = className
        ? `responsiveAlignControlWrapper ${className}`
        : 'responsiveAlignControlWrapper';

    return (
        <div className={wrapperClassName}>
            {resOption in alignmentKeys && renderButtons(alignmentKeys[resOption])}
        </div>
    );
}
