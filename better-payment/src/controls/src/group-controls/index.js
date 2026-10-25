/**
 * WordPress Dependencies
 */
import { __ } from "@wordpress/i18n";
import { useEffect, Fragment } from "@wordpress/element";
import { createHigherOrderComponent } from "@wordpress/compose";
import { addFilter, applyFilters } from "@wordpress/hooks";

/**
 * Internal Dependencies
 */
import { softMinifyCssStrings } from "../helpers";
import classNames from "classnames";

import {
    generateTextControllerAttributes,
    generateTextControllerStyles,
} from "../helpers/responsiveTextControllerHelpers";
import { getEditorRoot } from "../helpers"
import { generateBorderShadowAttributes } from "../helpers/borderShadowHelpers";
import { generateBorderShadowStyles } from "../helpers/borderShadowHelpers";

/**
 * Add custom attribute for Essential Block
 *
 * @param {Object} settings Settings for the block.
 *
 * @return {Object} settings Modified settings.
 */
function addAttributes(settings) {
    if (typeof settings.attributes === "undefined") {
        return settings;
    }
    if (
        settings.attributes.blockRoot &&
        settings.attributes.blockRoot.default == "essential_block"
    ) {
        settings.attributes = {
            ...settings.attributes,
            classHook: {
                type: "string",
                default: "",
            },
            showBlockContent: {
                type: "boolean",
                default: true
            },
            commonStyles: {
                type: "object",
            },
            hideOnDesktop: {
                type: "boolean",
                default: false,
            },
            hideOnTab: {
                type: "boolean",
                default: false,
            },
            hideOnMobile: {
                type: "boolean",
                default: false,
            },
            isCustomCssError: {
                type: "boolean",
                default: false,
            },
            customCss: {
                type: "string",
            },
            animationData: {
                type: "object",
                default: {
                    style: "none",
                    speed: 1000,
                    delay: 0,
                },
            },
            wrapperOverflow: {
                type: "string",
                default: '',
            },
            liquidGlass: {
                type: "object",
                default: {
                    enable: false,
                    effect: "effect1",
                    shadowEffect: "effect1",
                    backgroundColor: "#FFFFFF1F",
                    backdropFilter: 24,
                    brightness: 1,
                },
            },
            ...generateTextControllerAttributes('zIndex'),
            ...generateBorderShadowAttributes('liquidGlassShadowEffectBorder', {
                defaultBdrColor: "#FFFFFF1F",
                defaultBdrStyle: "solid",
                bdrDefaults: {
                    top: 1,
                    bottom: 1,
                    right: 1,
                    left: 1,
                },
                rdsDefaults: {
                    top: 24,
                    bottom: 24,
                    right: 24,
                    left: 24,
                },
            }),
            ...applyFilters("eb_group_controls_attributes", "", settings)
        };
    }

    return settings;
}

/**
 * Add controls and generate styles on Advanced Block Panel.
 *
 * @param {function} BlockEdit Block edit component.
 *
 * @return {function} BlockEdit Modified block edit component.
 */
const withAdvancedControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes, isSelected, name, clientId } = props;

        const {
            blockRoot,
            blockId,
            commonStyles,
            hideOnDesktop,
            hideOnTab,
            hideOnMobile,
            isCustomCssError,
            classHook,
            customCss,
            animationData,
            wrapperOverflow,
            liquidGlass,
            liquidGlassShadowEffectBorder,
        } = attributes;

        /**
         * On change AnimationData Attribute
         */
        useEffect(() => {
            const classes = classHook && classHook.split(" ").filter((className) => !className.includes("eb___")).join(" ");
            if (typeof animationData === "object") {
                if (animationData.style === "none") {
                    const removeAnimateClass = classes.replace("eb_animation", "");
                    setAttributes({
                        classHook: removeDuplicateClasses(removeAnimateClass),
                    });
                }
                else {
                    const addClasses = classNames(classes, "eb_animation", "eb___animated", animationData.style);
                    setAttributes({
                        classHook: removeDuplicateClasses(addClasses),
                    });
                }
                setTimeout(() => {
                    const editorRoot = getEditorRoot()
                    if (editorRoot) {
                        const selector = '.eb-parent-' + blockId
                        const element = editorRoot.querySelector(selector)
                        if (element) {
                            let toRemoveClasses = [];
                            let toAddClasses = [];
                            element.classList.forEach((classname) => {
                                if (classname.includes("eb___")) {
                                    toRemoveClasses.push(classname);
                                    toAddClasses.push(classname.replace("eb___", "eb__"));
                                }
                            });
                            element.classList.add(...toAddClasses);
                            element.classList.remove(...toRemoveClasses);
                        }
                    }
                }, 200)
            }

        }, [animationData]);

        //Generate Styles for Animation
        const animationAllStyles =
            animationData && animationData.style != "none"
                ? `
            /* Css for Animation */
            body:not(.wp-admin) .eb-parent-${blockId}.eb_animation {
                visibility: hidden;
                opacity: 0;
                transition: ease-in ${animationData.speed}ms;
        }
            body:not(.wp-admin) .eb-parent-${blockId}.eb_animation.eb__animated {
            visibility: visible;
            opacity: 1;
        }
            .eb-parent-${blockId}.eb_animation.eb__animated {
                animation-duration: ${animationData.speed}ms;
        animation-delay: ${animationData.delay}ms;
    }
    `
                : "";

        //Remove Duplicate words Function
        const removeDuplicateClasses = (string) => {
            if (typeof string !== "string") {
                return string;
            }
            if (string.length === 0) {
                return string;
            }

            return string
                .split(" ")
                .filter(function (item, pos, self) {
                    return self.indexOf(item) == pos;
                })
                .join(" ");
        };

        // Set All Style in "blockMeta" Attribute
        if (blockRoot === "essential_block") {
            //Responsive Control CSS
            const responsiveStyleDesktop = `
                .eb-parent-${blockId} {
                    display: ${hideOnDesktop ? "none" : "block"};
                }
            `;
            const responsiveStyleDesktopEditor = `
                .editor-styles-wrapper .eb-parent-${blockId} {
                    display: block;
                }
                ${hideOnDesktop ? `
                .editor-styles-wrapper .eb-parent-${blockId} {
                    filter: saturate(0);
                }
                .editor-styles-wrapper .eb-parent-${blockId}::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: repeating-linear-gradient(125deg, rgba(0, 0, 0, .05), rgba(0, 0, 0, .05) 1px, transparent 2px, transparent 9px);
                    border: 1px solid rgba(0, 0, 0, .02);
                    z-index: 9999;
                }
                `: `
                    .editor-styles-wrapper .eb-parent-${blockId} {
                        filter: unset;
                    }
                    .editor-styles-wrapper .eb-parent-${blockId}::before {
                        content: none;
                    }
                `}
            `;

            const responsiveStyleTabEditor = `
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                    display: block;
                }
                ${hideOnTab ? `
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                    filter: saturate(0);
                }
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId}::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: repeating-linear-gradient(125deg, rgba(0, 0, 0, .05), rgba(0, 0, 0, .05) 1px, transparent 2px, transparent 9px);
                    border: 1px solid rgba(0, 0, 0, .02);
                    z-index: 9999;
                }
                `: `
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                        filter: none;
                    }
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId}::before {
                    content: none;
                }
                `}
            `;
            const responsiveStyleTab = `
                .eb-parent-${blockId} {
                    display: ${hideOnTab ? "none" : "block"};
                }
            `;

            const responsiveStyleMobile = `
                .eb-parent-${blockId} {
                    display: ${hideOnMobile ? "none" : "block"};
                }
            `;
            const responsiveStyleMobileEditor = `
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                    display: block;
                }
                ${hideOnMobile ? `
                .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                    filter: saturate(0);
                }
                    .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId}::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: repeating-linear-gradient(125deg, rgba(0, 0, 0, .05), rgba(0, 0, 0, .05) 1px, transparent 2px, transparent 9px);
                    border: 1px solid rgba(0, 0, 0, .02);
                    z-index: 9999;
                }
                `: `
                    .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId} {
                        filter: none;
                    }
                    .editor-styles-wrapper.wp-embed-responsive .eb-parent-${blockId}::before {
                        content: none;
                    }
                `}
            `;

            // Layout options css
            const {
                textControlStylesDesktop: zIndexDesktop,
                textControlStylesTab: zIndexTab,
                textControlStylesMobile: zIndexMobile,
            } = generateTextControllerStyles({
                controlName: 'zIndex',
                styleFor: "z-index",
                attributes,
            });

            let layoutDesktopStyle = `
                .root-${blockId} {
                    ${zIndexDesktop}
                    ${wrapperOverflow && wrapperOverflow.length > 0 ? `overflow: ${wrapperOverflow}` : ''};
                    position: relative;
                }
            `;

            let layoutTabStyle = `
                .root-${blockId} {
                    ${zIndexTab}
                }
            `;

            let layoutMobileStyle = `
                .root-${blockId} {
                    ${zIndexMobile}
                }
            `;

            const {
                styesDesktop: liquidGlassShadowEffectBorderStylesDesktop,
                styesTab: liquidGlassShadowEffectBorderStylesTab,
                styesMobile: liquidGlassShadowEffectBorderStylesMobile,
            } = generateBorderShadowStyles({
                controlName: 'liquidGlassShadowEffectBorder',
                attributes,
            });



            let liquidGlassStyles = `
                .root-${blockId}.eb_liquid_glass-effect1{
                    background-color: ${liquidGlass.backgroundColor};
                    backdrop-filter: blur(${liquidGlass.backdropFilter}px);
                }
                .root-${blockId}.eb_liquid_glass-effect2{
                    background-color: ${liquidGlass.backgroundColor};
                    backdrop-filter: blur(${liquidGlass.backdropFilter}px) brightness(${liquidGlass.brightness});
                }
                .root-${blockId}.eb_liquid_glass_shadow-${liquidGlass.shadowEffect}{
                    ${liquidGlassShadowEffectBorderStylesDesktop}
                }

                .root-${blockId}.eb_liquid_glass-${liquidGlass.effect} > .eb-parent-wrapper > div {
                    background: transparent;
                }
            `;

            // all css styles for large screen width (desktop/laptop) in strings ⬇
            let desktopAllStyles = softMinifyCssStrings(`
                ${responsiveStyleDesktop}
                ${animationAllStyles}
                ${layoutDesktopStyle}
                ${liquidGlassStyles}
            `);
            let desktopAllStylesEditor = softMinifyCssStrings(`
                ${responsiveStyleDesktopEditor}
                ${animationAllStyles}
                ${layoutDesktopStyle}
                ${liquidGlassStyles}
            `);

            // all css styles for Tab in strings ⬇
            let tabAllStyles = softMinifyCssStrings(`
                ${responsiveStyleTab}
                ${layoutTabStyle}
            `);
            let tabAllStylesEditor = softMinifyCssStrings(`
                ${responsiveStyleTabEditor}
                ${layoutTabStyle}
            `);

            // all css styles for Mobile in strings ⬇
            let mobileAllStyles = softMinifyCssStrings(`
                ${responsiveStyleMobile}
                ${layoutMobileStyle}
                `);
            let mobileAllStylesEditor = softMinifyCssStrings(`
                ${responsiveStyleMobileEditor}
                ${layoutMobileStyle}
                `);

            let styleObject = {
                desktop: desktopAllStyles,
                tab: tabAllStyles,
                mobile: mobileAllStyles,
            };

            let styleObjectEditor = {
                desktop: desktopAllStylesEditor,
                tab: tabAllStylesEditor,
                mobile: mobileAllStylesEditor,
            };
            styleObject = applyFilters("eb_common_style_object", styleObject, attributes);
            styleObjectEditor = applyFilters("eb_common_style_object_editor", styleObjectEditor, attributes);

            // Set All Style in "blockMeta" Attribute
            useEffect(() => {
                if (
                    JSON.stringify(commonStyles) != JSON.stringify(styleObject)
                ) {
                    setAttributes({
                        commonStyles: styleObject,
                    });
                }
            }, [attributes]);

            // let _attributes = { ...attributes };

            /**
             * Set Block Defaults when new block inserted
             */
            // const isBlockJustInserted = select("core/block-editor").wasBlockJustInserted(clientId);
            // const blockDefaults = select("essential-blocks")?.getBlockDefaults();

            // useEffect(() => {
            //     const modifyBlockKey = blockKey.startsWith('pro_') ? blockKey.replace('pro_', '') : blockKey;

            //     console.log('Global Control UseEffect', attributes, attributes.socialDetails)

            //     if (
            //         isBlockJustInserted &&
            //         blockDefaults &&
            //         blockRoot === "essential_block" &&
            //         name.toLowerCase().includes("essential-blocks/") &&
            //         eb_conditional_localize?.editor_type !== 'edit-widgets'
            //     ) {
            //         if (blockDefaults?.[modifyBlockKey]) {
            //             _attributes = {
            //                 ..._attributes,
            //                 ...blockDefaults[modifyBlockKey],
            //                 ...(!blockId && { showBlockContent: false })
            //             };
            //             setAttributes({ ..._attributes });
            //         }
            //     }
            // }, []);

            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <style>
                        {`
                            ${styleObjectEditor?.desktop}
                            @media all and (max-width: 1024px) {
                                /* tabcssStart */
                                ${styleObjectEditor?.tab}
                                /* tabcssEnd */

                            }
                            @media all and(max - width: 767px) {
                                /* mobcssStart */
                                ${styleObjectEditor?.mobile}
                                /* mobcssEnd */

                            }
                            /* Custom CSS*/
                            ${!isCustomCssError ? customCss?.replace(/eb_selector/g, "." + blockId) : ''}

                        `}
                    </style>
                </Fragment>
            );
        } else {
            return <BlockEdit {...props} />;
        }
    };
}, "withAdvancedControls");

/**
 * Add Attributes Filter
 */
addFilter("blocks.registerBlockType", "essential-blocks/global", addAttributes);

/**
 * Filter for modification of Edit Function
 */
addFilter("editor.BlockEdit", "essential-blocks/global", withAdvancedControls);

