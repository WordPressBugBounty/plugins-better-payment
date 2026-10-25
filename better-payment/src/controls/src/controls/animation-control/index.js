/**
 * External Dependencies
*/

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    SelectControl,
    RangeControl,
} from "@wordpress/components";

import {
    ANIMATION_STYLES,
} from "./constants";
import {memo} from "@wordpress/element";
import {useBlockSetAttributes} from "../../hooks";

const AnimationControls = ({animationData}) => {
    const setAttributes = useBlockSetAttributes();
    const changeAnimation = (name, value) => {
        setAttributes({
            animationData: {
                ...animationData,
                [name]: value,
            }
        });
    }

    return (
        <>
            {animationData && (
                <PanelBody title={__("Animation", "essential-blocks")} initialOpen={false}>
                    <SelectControl
                        label={__("Select Animation", "essential-blocks")}
                        value={animationData.style}
                        options={ANIMATION_STYLES}
                        onChange={(style) => changeAnimation("style", style)}
                        id={"eb-animation-style"}
                    />
                    <RangeControl
                        label={__("Animation Speed", "essential-blocks")}
                        value={animationData.speed}
                        onChange={(style) => changeAnimation("speed", style)}
                        min={0}
                        max={5000}
                        allowReset={true}
                    />
                    <RangeControl
                        label={__("Animation Delay", "essential-blocks")}
                        value={animationData.delay}
                        onChange={(style) => changeAnimation("delay", style)}
                        min={0}
                        max={5000}
                        allowReset={true}
                    />
                </PanelBody>
            )}

        </>
    )
}

export default memo(AnimationControls);
