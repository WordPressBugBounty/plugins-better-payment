import {
    useBlockProps,
} from "@wordpress/block-editor";
import classnames from "classnames";
import { select } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import { useDeviceType, useBlockAttributes } from "../../hooks";
import { loadGoogleFontEditor, duplicateBlockIdFix } from "../../helpers"
import { applyFilters } from "@wordpress/hooks"

const BlockPropsEdit = ({
    attributes,
    setAttributes,
    clientId,
    blockPrefix,
    rootClass = "eb-guten-block-main-parent-wrapper",
    style,
    children,
    name,
    htmlTag = "div",
    onMount = () => { }
}) => {
    const { className, blockId } = attributes;

    const blockname = name.substring(name.lastIndexOf("/") + 1);
    const blockKey = blockname.replace(/-/g, "_");

    const isBlockJustInserted = select("core/block-editor").wasBlockJustInserted(clientId);
    const blockDefaults = select("essential-blocks")?.getBlockDefaults();

    const deviceType = useDeviceType();

    // this useEffect is for creating a unique id for each block's unique className by a random unique number
    useEffect(() => {
        duplicateBlockIdFix({
            blockPrefix,
            blockId,
            setAttributes,
            clientId,
        });

        const modifyBlockKey = blockKey.startsWith('pro_') ? blockKey.replace('pro_', '') : blockKey;

        if (isBlockJustInserted && blockDefaults && name.toLowerCase().includes("essential-blocks/") && eb_conditional_localize?.editor_type !== 'edit-widgets') {
            if (blockDefaults?.[modifyBlockKey]) {
                let _attributes = {
                    ...attributes,
                    ...blockDefaults[modifyBlockKey],
                    ...(!blockId && { showBlockContent: false })
                };
                setAttributes({ ..._attributes });
            }
        }

        //Typography
        let googleFontFamily = [];
        for (var key in attributes) {
            if (/^(\w+)FontFamily/.test(key)) {
                if (attributes[key]?.length && attributes[key] !== "Default") {
                    googleFontFamily.push(
                        attributes[key] +
                        ":100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic"
                    );
                }
            }
        }
        loadGoogleFontEditor(googleFontFamily)

        //Run onMount Func end of the UseEffect
        onMount();
    }, []);

    // this useEffect is for setting the resOption attribute to desktop/tab/mobile
    useEffect(() => {
        setAttributes({
            resOption: deviceType,
        });
    }, [deviceType]);

    const blockPropsHook = applyFilters('eb_block_props_editor_hook', {})

    const blockProps = useBlockProps({
        className: classnames(className, `${rootClass} root-${blockId} ${attributes?.liquidGlass?.enable ? `eb_liquid_glass-${attributes.liquidGlass.effect} eb_liquid_glass_shadow-${attributes.liquidGlass.shadowEffect}` : ''}`),
        ...blockPropsHook
    });

    const RootTag = htmlTag;

    return (
        <RootTag {...blockProps}>
            {style}
            {applyFilters("eb_editor_before_block_content", "", attributes)}
            {/* Render the children passed to this component */}
            {children}
            {applyFilters("eb_editor_after_block_content", "", attributes)}
        </RootTag>
    );
};

const BlockPropsSave = ({ attributes, rootClass = "", children, htmlTag = "div" }) => {
    const { className, blockId } = attributes;

    // const blockPropsHook = applyFilters('eb_block_props_save_hook', {})
    const blockProps = useBlockProps.save({
        className: classnames(className, `${rootClass} root-${blockId} ${attributes?.liquidGlass?.enable ? `eb_liquid_glass-${attributes.liquidGlass.effect} eb_liquid_glass_shadow-${attributes.liquidGlass.shadowEffect}` : ''}`),
        // ...blockPropsHook
    });

    const RootTag = htmlTag;

    return (
        <RootTag
            {...blockProps}
            {...(attributes.ebGsap ? { 'data-gsap': JSON.stringify(attributes.ebGsap) } : {})}
            {...(attributes.ebFormConditionalLogic?.enable ? { 'data-form-field-condition': btoa(JSON.stringify(attributes.ebFormConditionalLogic)) } : {})}
        >
            {/* Render the children passed to this component */}
            {children}
        </RootTag>
    );
};

export const BlockProps = {
    Edit: BlockPropsEdit,
    Save: BlockPropsSave
}

