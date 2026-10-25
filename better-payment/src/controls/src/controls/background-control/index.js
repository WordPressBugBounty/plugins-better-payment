/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { ToggleControl } from "@wordpress/components";

/**
 * Internal dependencies
 */
import OverlayControl from "./overlayControl";
import BgControl from "./bgControl";
import { useBlockSetAttributes, useBlockAttributes } from "../../hooks";

export default function BackgroundControl({
    controlName,
    noOverlay = false,
    noMainBgi = false,
    noOverlayBgi = false,
    noTransition = false,

    //
    forButton = false,
    onColorChange = undefined
}) {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();

    const { [`${controlName}isBgOverlay`]: isBgOverlay } = attributes;

    return (
        <>
            <BgControl
                controlName={controlName}
                noMainBgi={forButton === true ? true : noMainBgi}
                noTransition={noTransition}
                onColorChange={onColorChange}
            />

            {noOverlay === false && forButton == false && (
                <>
                    <hr />

                    <ToggleControl
                        label={__("Enable Overlay", "essential-blocks")}
                        checked={isBgOverlay}
                        onChange={() =>
                            setAttributes({
                                [`${controlName}isBgOverlay`]: !isBgOverlay,
                            })
                        }
                    />

                    {isBgOverlay && (
                        <OverlayControl
                            controlName={controlName}
                            noOverlayBgi={noOverlayBgi}
                            noTransition={noTransition}
                        />
                    )}
                </>
            )}
        </>
    );
}
