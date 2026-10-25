/**
 * WordPress dependencies
 */
 import { __ } from "@wordpress/i18n";
 
 //Animation Style Values
export const ANIMATION_STYLES = [
    { label: __("None", "essential-blocks"), value: "none" },
    { label: __("FadeIn", "essential-blocks"), value: "eb___fadeIn" },
    { label: __("FadeInDown", "essential-blocks"), value: "eb___fadeInDown" },
    { label: __("FadeInLeft", "essential-blocks"), value: "eb___fadeInLeft" },
    { label: __("FadeInRight", "essential-blocks"), value: "eb___fadeInRight" },
    { label: __("FadeInUp", "essential-blocks"), value: "eb___fadeInUp" },

    { label: __("SlideInUp", "essential-blocks"), value: "eb___slideInUp" },
    { label: __("SlideInDown", "essential-blocks"), value: "eb___slideInDown" },
    { label: __("SlideInLeft", "essential-blocks"), value: "eb___slideInLeft" },
    { label: __("SlideInRight", "essential-blocks"), value: "eb___slideInRight" },

    // { label: __("SlideOutUp", "essential-blocks"), value: "eb___slideOutUp" },
    // { label: __("SlideOutDown", "essential-blocks"), value: "eb___slideOutDown" },
    // { label: __("SlideOutLeft", "essential-blocks"), value: "eb___slideOutLeft" },
    // { label: __("SlideOutRight", "essential-blocks"), value: "eb___slideOutRight" },

    { label: __("ZoomIn", "essential-blocks"), value: "eb___zoomIn" },
    { label: __("ZoomInDown", "essential-blocks"), value: "eb___zoomInDown" },
    { label: __("ZoomInLeft", "essential-blocks"), value: "eb___zoomInLeft" },
    { label: __("ZoomInRight", "essential-blocks"), value: "eb___zoomInRight" },
    { label: __("ZoomInUp", "essential-blocks"), value: "eb___zoomInUp" },

    // { label: __("ZoomOut", "essential-blocks"), value: "eb___zoomOut" },
    // { label: __("ZoomOutDown", "essential-blocks"), value: "eb___zoomOutDown" },
    // { label: __("ZoomOutLeft", "essential-blocks"), value: "eb___zoomOutLeft" },
    // { label: __("ZoomOutUp", "essential-blocks"), value: "eb___zoomOutUp" },

    // { label: __("FadeOut", "essential-blocks"), value: "eb___fadeOut" },
    // { label: __("FadeOutDown", "essential-blocks"), value: "eb___fadeOutDown" },
    // { label: __("FadeOutLeft", "essential-blocks"), value: "eb___fadeOutLeft" },
    // { label: __("FadeOutRight", "essential-blocks"), value: "eb___fadeOutRight" },
    // { label: __("FadeOutUp", "essential-blocks"), value: "eb___fadeOutUp" },

    // { label: __("FadeInDownBig", "essential-blocks"), value: "eb___fadeInDownBig" },
    // { label: __("FadeInLeftBig", "essential-blocks"), value: "eb___fadeInLeftBig" },
    // { label: __("FadeInRightBig", "essential-blocks"), value: "eb___fadeInRightBig" },
    // { label: __("FadeInUpBig", "essential-blocks"), value: "eb___fadeInUpBig" },
    // { label: __("FadeOutDownBig", "essential-blocks"), value: "eb___fadeOutDownBig" },
    // { label: __("FadeOutLeftBig", "essential-blocks"), value: "eb___fadeOutLeftBig" },
    // { label: __("FadeOutRightBig", "essential-blocks"), value: "eb___fadeOutRightBig" },
    // { label: __("FadeOutUpBig", "essential-blocks"), value: "eb___fadeOutUpBig" },


    { label: __("Bounce", "essential-blocks"), value: "eb___bounce" },
    { label: __("Flash", "essential-blocks"), value: "eb___flash" },
    { label: __("Pulse", "essential-blocks"), value: "eb___pulse" },
    { label: __("RubberBand", "essential-blocks"), value: "eb___rubberBand" },
    // { label: __("Shake", "essential-blocks"), value: "eb___shake" },
    { label: __("Swing", "essential-blocks"), value: "eb___swing" },
    { label: __("Tada", "essential-blocks"), value: "eb___tada" },
    { label: __("Wobble", "essential-blocks"), value: "eb___wobble" },
    { label: __("Jello", "essential-blocks"), value: "eb___jello" },

    { label: __("BounceIn", "essential-blocks"), value: "eb___bounceIn" },
    { label: __("BounceInDown", "essential-blocks"), value: "eb___bounceInDown" },
    { label: __("BounceInUp", "essential-blocks"), value: "eb___bounceInUp" },

    // { label: __("BounceOut", "essential-blocks"), value: "eb___bounceOut" },
    // { label: __("BounceOutDown", "essential-blocks"), value: "eb___bounceOutDown" },
    // { label: __("BounceOutLeft", "essential-blocks"), value: "eb___bounceOutLeft" },
    // { label: __("BounceOutRight", "essential-blocks"), value: "eb___bounceOutRight" },
    // { label: __("BounceOutUp", "essential-blocks"), value: "eb___bounceOutUp" },

    { label: __("Flip", "essential-blocks"), value: "eb___flip" },
    { label: __("FlipInX", "essential-blocks"), value: "eb___flipInX" },
    { label: __("FlipInY", "essential-blocks"), value: "eb___flipInY" },
    // { label: __("FlipOutX", "essential-blocks"), value: "eb___flipOutX" },
    // { label: __("FlipOutY", "essential-blocks"), value: "eb___flipOutY" },

    // { label: __("LightSpeedIn", "essential-blocks"), value: "eb___lightSpeedIn" },
    // { label: __("LightSpeedOut", "essential-blocks"), value: "eb___lightSpeedOut" },

    { label: __("RotateIn", "essential-blocks"), value: "eb___rotateIn" },
    // { label: __("RotateInDownLeft", "essential-blocks"), value: "eb___rotateInDownLeft" },
    // { label: __("RotateInDownRight", "essential-blocks"), value: "eb___rotateInDownRight" },
    // { label: __("RotateInUpLeft", "essential-blocks"), value: "eb___rotateInUpLeft" },
    // { label: __("RotateInUpRight", "essential-blocks"), value: "eb___rotateInUpRight" },

    // { label: __("RotateOut", "essential-blocks"), value: "eb___rotateOut" },
    // { label: __("RotateOutDownLeft", "essential-blocks"), value: "eb___rotateOutDownLeft" },
    // { label: __("RotateOutDownRight", "essential-blocks"), value: "eb___rotateOutDownRight" },
    // { label: __("RotateOutUpLeft", "essential-blocks"), value: "eb___rotateOutUpLeft" },
    // { label: __("RotateOutUpRight", "essential-blocks"), value: "eb___rotateOutUpRight" },

    // { label: __("Hinge", "essential-blocks"), value: "eb___hinge" },
    // { label: __("RollIn", "essential-blocks"), value: "eb___rollIn" },
    // { label: __("RollOut", "essential-blocks"), value: "eb___rollOut" },
];
