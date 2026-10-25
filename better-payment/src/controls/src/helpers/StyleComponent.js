/**
 * WordPress dependencies
 */
import { useEffect, useState } from "@wordpress/element";
import { doAction, applyFilters } from "@wordpress/hooks";
import { useSelect } from '@wordpress/data';

import { softMinifyCssStrings } from "../helpers/miniHelperFuncs"

export const StyleComponent = (props) => {
    const {
        attributes,
        setAttributes,
        desktopAllStyles,
        tabAllStyles,
        mobileAllStyles,
        editorDesktopStyles,
        editorTabStyles,
        editorMobileStyles,
        blockName,
        ...otherProps
    } = props;

    const {
        blockMeta,
    } = attributes

    const [styleObject, setStyleObject] = useState();
    const [didMount, setDidMount] = useState(false);

    // Set All Style in "styleObject"
    useEffect(() => {
        const styles = {
            desktop: desktopAllStyles,
            tab: tabAllStyles,
            mobile: mobileAllStyles,
            editorDesktop: editorDesktopStyles,
            editorTab: editorTabStyles,
            editorMobile: editorMobileStyles
        }
        setStyleObject(applyFilters(`eb-style-object_${blockName.replace(/\//g, '_')}`, styles, attributes, setAttributes))
        setDidMount(true)
    }, [
        attributes,
        desktopAllStyles,
        tabAllStyles,
        mobileAllStyles,
        editorDesktopStyles,
        editorTabStyles,
        editorMobileStyles
    ]);

    // Set All Style in "blockMeta" Attribute
    useEffect(() => {
        if (styleObject) {
            const dbStyles = {
                desktop: softMinifyCssStrings(styleObject.desktop),
                tab: softMinifyCssStrings(styleObject.tab),
                mobile: softMinifyCssStrings(styleObject.mobile)
            }
            if (JSON.stringify(blockMeta) !== JSON.stringify(dbStyles)) {
                setAttributes({ blockMeta: dbStyles });
            }
        }
    }, [styleObject])

    return (
        <style>
            {didMount && `
				${softMinifyCssStrings(styleObject.editorDesktop ? styleObject.editorDesktop : styleObject.desktop)}

				@media all and (max-width: ${EssentialBlocksLocalize?.responsiveBreakpoints?.tablet}px) {
					/* tabcssStart */
					${softMinifyCssStrings(styleObject.editorTab ? styleObject.editorTab : styleObject.tab)}
					/* tabcssEnd */
				}

				@media all and (max-width: ${EssentialBlocksLocalize?.responsiveBreakpoints?.mobile}px) {
					/* mobcssStart */
					${softMinifyCssStrings(styleObject.editorMobile ? styleObject.editorMobile : styleObject.mobile)}
					/* mobcssEnd */
				}
			`}
        </style>
    )
}
