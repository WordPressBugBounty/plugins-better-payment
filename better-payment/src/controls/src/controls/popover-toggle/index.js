import { BaseControl, Dropdown, Button } from "@wordpress/components";
import {
    useBlockAttributes,
    useBlockSetAttributes,
    useBlockDefaultAttributes,
    useDeviceType
} from "../../hooks";
import { memo } from "@wordpress/element";

import { TypographyIcon } from '../../extras/icons'

const PopoverToggle = ({
    label,
    showReset = false,
    onReset,
    children
}) => {
    return (
        <BaseControl label={label}
            className="eb-popover-toggle-base">
            <Dropdown
                className="eb-popover-toggle-dropdown"
                contentClassName="eb-popover-content-classname"
                placement="bottom right"
                renderToggle={({ isOpen, onToggle }) => (
                    <>
                        {showReset && (
                            <Button
                                onClick={onReset}
                                icon={'image-rotate'}
                            ></Button>
                        )}
                        <Button
                            onClick={onToggle}
                        ><TypographyIcon /></Button>
                    </>
                )}
                renderContent={() => (
                    <div className="eb-panel-control">{children}</div>
                )}
            />
        </BaseControl>
    )
};

export default memo(PopoverToggle);
