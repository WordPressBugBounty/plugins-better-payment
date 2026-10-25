import { PanelRow } from "@wordpress/components";
import {
    useBlockAttributes,
    useBlockSetAttributes,
    useBlockDefaultAttributes,
    useDeviceType
} from "../../hooks";
import { memo } from "@wordpress/element";

const FlexContainer = ({
    children,
    title = false,
    gap = 10,
    columns = false,
    help = false
}) => {

    return (
        <div className="eb-flex-control-wrapper">
            {columns && typeof columns === 'number' && (
                <style>
                    {`
                .eb-flex-control-items {
                    flex-wrap: wrap;
                }
                .eb-flex-control-items > * {
                    width: calc(${100 / columns}% - ${gap / columns}px);
                }
                `}
                </style>
            )}

            {title && (
                <PanelRow className="eb-flex-control-title"> {title}</PanelRow>
            )}
            <div className="eb-flex-control-items" style={{ gap: `${gap}px` }}>
                {children}
            </div>
            {
                help && (
                    <PanelRow className="eb-flex-control-help"> {help}</PanelRow>
                )
            }
        </div >
    );
};

export default memo(FlexContainer);
