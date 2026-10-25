/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";
import { applyFilters } from "@wordpress/hooks";
import { extractDynamicStingInfo } from "../../../helpers";
import { useBlockAttributes } from "../../../hooks";

import EBTextControl from "../../text-control";
import EBTextareaControl from "../../textarea-control";

export function DynamicInputControl(props) {
    const {
        label,
        attrName,
        inputValue,
        setAttributes,
        onChange,
        placeholder,
        help,
        isTextarea,
        type,
        keyName,
        index,
        isObject,
        isImageField,
        imageIdAttr,
        enableAi = true,
        onBlur = () => null,
        fieldType,
        showValidation,
        enableSecurity,
    } = props;

    const [fieldValue, setFieldValue] = useState("");
    const [fieldPlaceholder, setFieldPlaceholder] = useState("");
    const [readOnly, setReadOnly] = useState(false);
    const [didMount, setDidMount] = useState(false);
    const attributes = useBlockAttributes();

    const dynamicTag = window?.EssentialBlocksProLocalize
        ? EssentialBlocksProLocalize.eb_dynamic_tags + "/"
        : "dynamic-tags/";

    useEffect(() => {
        setDidMount(true);
        if (inputValue && inputValue.startsWith(dynamicTag, 0)) {
            setReadOnly(true);
            setFieldPlaceholder(extractDynamicStingInfo(inputValue));
        }
    }, []);

    useEffect(() => {
        if (didMount && EssentialBlocksProLocalize) {
            if (typeof fieldValue === "string" && fieldValue.startsWith(dynamicTag, 0)) {
                setReadOnly(true);
                if (isObject) {
                    const newItem = { ...attributes[keyName][index] };
                    const newItemList = [...attributes[keyName]];
                    newItemList[index] = newItem;
                    newItemList[index][attrName] = fieldValue;
                    setAttributes({ [keyName]: newItemList });
                } else {
                    setAttributes({ [attrName]: fieldValue });
                    setAttributes({ [imageIdAttr]: null })
                }

                setFieldPlaceholder(extractDynamicStingInfo(fieldValue));
            }
            else {
                setReadOnly(false);
                setAttributes({ [attrName]: fieldValue });
            }
        }
    }, [fieldValue]);

    return (
        <div className="eb-dynamic-input-control">
            {!isImageField && !isTextarea && (
                <>
                    <EBTextControl
                        label={__(label, "essential-blocks")}
                        value={readOnly ? fieldPlaceholder : inputValue}
                        attrName={attrName}
                        placeholder={placeholder || ""}
                        onChange={(text) => (readOnly ? false : onChange(text))}
                        onBlur={(e) => onBlur(e.target.value)}
                        readOnly={readOnly}
                        help={help}
                        enableAi={enableAi}
                        fieldType={fieldType}
                        showValidation={showValidation}
                        enableSecurity={enableSecurity}
                    />
                </>
            )}
            {!isImageField && isTextarea && (
                <>
                    <EBTextareaControl
                        label={__(label, "essential-blocks")}
                        placeholder={placeholder || ""}
                        value={readOnly ? fieldPlaceholder : inputValue}
                        onChange={(text) => (readOnly ? false : onChange(text))}
                        readOnly={readOnly}
                        help={help}
                        enableAi={enableAi}
                    />
                </>
            )}
            {applyFilters("essential_blocks_dynamic_input_control", "", fieldValue, setFieldValue, inputValue, type)}
        </div>
    );
}
