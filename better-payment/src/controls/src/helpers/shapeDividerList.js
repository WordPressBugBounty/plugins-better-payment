import { applyFilters } from "@wordpress/hooks";
import { StyleOne } from "../shape-divider-svg/style-1";
import { StyleTwo } from "../shape-divider-svg/style-2";
import { StyleThree } from "../shape-divider-svg/style-3";
import { StyleFour } from "../shape-divider-svg/style-4";

export const ShapeDividerList = () => {
    return applyFilters("essential_blocks_shape_divider_list", {
        style_1: {
            component: StyleOne,
        },
        style_2: {
            component: StyleTwo,
        },
        style_3: {
            component: StyleThree,
        },
        style_4: {
            component: StyleFour,
        },
    });
};
