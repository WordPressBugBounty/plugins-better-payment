/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    SelectControl
} from "@wordpress/components";
import { select } from "@wordpress/data";
import {
    useBlockAttributes,
    useBlockSetAttributes
} from "../index";

/**
 * Return Global Image Size Array
*/
export const ebImageSizes = () => {
    let imageAllSizes = [];
    const sizes = select('core/block-editor').getSettings().imageSizes;
    if (typeof sizes === 'object' && sizes.length > 0) {
        imageAllSizes = [{
            label: "Default",
            value: ""
        }];
        sizes.map((item, index) => {
            imageAllSizes.push({
                label: item.name,
                value: item.slug
            })
        });
    }

    return imageAllSizes;
}

/**
 * Return Image Select component
*/
export const EbImageSizeSelector = (props) => {
    const { attrName, label = 'Thumbnail Image Size' } = props;

    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const imageAllSizes = ebImageSizes();

    return (
        <SelectControl
            label={__(label, "essential-blocks")}
            value={attributes[attrName]}
            options={imageAllSizes}
            onChange={(val) =>
                setAttributes({
                    [attrName]: val
                })
            }
        />
    )
}
