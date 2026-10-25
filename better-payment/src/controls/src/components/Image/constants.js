import { __ } from "@wordpress/i18n";

export const OBJECT_FIT = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("Fill", "essential-blocks"), value: "fill" },
	{ label: __("Cover", "essential-blocks"), value: "cover" },
	{ label: __("Contain", "essential-blocks"), value: "contain" },
	{ label: __("Scale Down", "essential-blocks"), value: "scale-down" },
];

export const STYLES = [
    { label: __("Rounded", "essential-blocks"), value: "rounded" },
    { label: __("Square", "essential-blocks"), value: "square" },
    { label: __("Circle", "essential-blocks"), value: "circle" },
    { label: __("Octagon", "essential-blocks"), value: "octagon" },
    { label: __("Rhombus", "essential-blocks"), value: "rhombus" },
    { label: __("Triangle", "essential-blocks"), value: "triangle" },
];

export const HOVER_EFFECT = [
    { label: __("No Effect", "essential-blocks"), value: "no-effect" },
    { label: __("Zoom In", "essential-blocks"), value: "zoom-in" },
    { label: __("Zoom Out", "essential-blocks"), value: "zoom-out" },
    { label: __("Slide", "essential-blocks"), value: "slide" },
    { label: __("Blur", "essential-blocks"), value: "blur" },
];

export const OBJECT_POSITION = [
	{ label: __("Center Center", "essential-blocks"), value: "center center" },
	{ label: __("Center Left", "essential-blocks"), value: "center left" },
	{ label: __("Center Right", "essential-blocks"), value: "center right" },
	{ label: __("Top Center", "essential-blocks"), value: "top center" },
	{ label: __("Top Left", "essential-blocks"), value: "top left" },
	{ label: __("Top Right", "essential-blocks"), value: "top right" },
	{ label: __("Bottom Center", "essential-blocks"), value: "bottom center" },
	{ label: __("Bottom Left", "essential-blocks"), value: "bottom left" },
	{ label: __("Bottom Right", "essential-blocks"), value: "bottom right" },
];

export const CAPTION_STYLES = [
    { label: __("Style 1", "essential-blocks"), value: "caption-style-1" },
    { label: __("Style 2", "essential-blocks"), value: "caption-style-2" },
];

export const TEXT_ALIGN = [
    { label: __("Left", "essential-blocks"), value: "left" },
    { label: __("Right", "essential-blocks"), value: "right" },
    { label: __("Center", "essential-blocks"), value: "center" },
    { label: __("Justify", "essential-blocks"), value: "justify" },
];

export const VERTICAL_ALIGN = [
    { label: __("Top", "essential-blocks"), value: "top" },
    { label: __("Middle", "essential-blocks"), value: "middle" },
    { label: __("Bottom", "essential-blocks"), value: "bottom" },
];

export const HORIZONTAL_ALIGN = [
    { label: __("Left", "essential-blocks"), value: "left" },
    { label: __("Center", "essential-blocks"), value: "center" },
    { label: __("Right", "essential-blocks"), value: "right" },
];

export const VERTICAL_ALIGN_CAP_2 = [
    { label: __("Top", "essential-blocks"), value: "top" },
    { label: __("Bottom", "essential-blocks"), value: "bottom" },
];

export const UNIT_TYPES = [
    { label: "px", value: "px" },
    { label: "%", value: "%" },
];

export const IMAGE_ALIGN = [
    { label: __("Left", "essential-blocks"), value: "flex-start" },
    { label: __("Center", "essential-blocks"), value: "center" },
    { label: __("Right", "essential-blocks"), value: "flex-end" },
];

// responsive range control
export const IMAGE_WIDTH = "imgWidth";
export const IMAGE_HEIGHT = "imgHeight";
export const IMAGE_SIZE = "imgSize";
export const IMAGE_RADIUS = "imgRadius";
// dimension control
export const IMAGE_PADDING = "imgPadding";
export const IMAGE_MARGIN = "imgMargin";
// border
export const IMAGE_BORDER = "imgBrdShw";
export const CAPTION_MARGIN = "captionMargin";
export const CAPTION_PADDING = "captionPadding";
export const CAPTION_TYPOGRAPHY = "captionTypo";
export const CAPTION_WIDTH = "captionWidth";
export const TEXT_ALIGNMENT = "textAlign";
export const IMAGE_ALIGNMENT = "imageAlign";