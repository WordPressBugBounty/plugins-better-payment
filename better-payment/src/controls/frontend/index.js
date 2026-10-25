export { EBGetIconType } from "./iconPickerHelper";
export { EBRenderIcon } from "./iconPickerHelper";
export { EBGetIconClass } from "./iconPickerHelper";
export { EBRenderIconWithSVG } from "./iconPickerHelper";
export { generateArrowHTML } from "./iconPickerHelper";
export { loadSvgIcons } from "./iconPickerHelper";
export { isSvgIconValue } from "./iconPickerHelper";
export { isInlineSvgMarkup } from "./iconPickerHelper";
export { fetchSvgAsHTML } from "./iconPickerHelper";

export {
    sanitize,
    sanitizeIconValue,
    sanitizeObject,
    sanitizeURL,
    sanitizeAttribute,
    sanitizeText,
    sanitizeHTML,
    sanitizeTarget,
    getDataAttribute,
    setDataAttribute,
    sanitizeCSS,
    logSecurityWarning,
    sanitizeFormInput,
} from "./EBSanitization";

export const SetEqualHeightOfMultiColumnBlock = (parent) => {
    if (!parent) {
        return;
    }
    const cellMaxHeights = {};
    parent.querySelectorAll(".eb-mcpt-feature-list").forEach((column) => {
        column.querySelectorAll(".eb-mcpt-cell").forEach((cell, rowIndex) => {
            let height = cell.offsetHeight;
            if (height !== 0) {
                if (
                    cellMaxHeights[rowIndex] !== undefined &&
                    cellMaxHeights[rowIndex] < height
                ) {
                    cellMaxHeights[rowIndex] = height;
                } else if (cellMaxHeights[rowIndex] === undefined) {
                    cellMaxHeights[rowIndex] = height;
                }
            }
        });
    });
    if (Object.keys(cellMaxHeights).length > 0) {
        parent.querySelectorAll(".eb-mcpt-feature-list").forEach((column) => {
            column.querySelectorAll(".eb-mcpt-cell").forEach((cell, rowIndex) => {
                cell.style.height = cellMaxHeights[rowIndex] + "px";
            });
        });
    }
};
