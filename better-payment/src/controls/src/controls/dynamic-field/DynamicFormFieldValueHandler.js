
/**
 * WordPress dependencies
 */
import { useEffect, useState } from "@wordpress/element";
import { applyFilters } from "@wordpress/hooks";

export default function DynamicFormFieldValueHandler(props) {
    const {
        type,
        fieldName,
        defaultValue,
        options,
        dynamicValue,
        dynamicOptions,
        dynamicOptionType,
        dynamicValueLoader,
        setAttributes
    } = props
    const [content, setContent] = useState("")

    useEffect(() => {
        applyFilters(
            'essential_blocks_dynamic_select_value_handle',
            content,
            setContent,
            type,
            fieldName,
            defaultValue,
            options,
            dynamicValue,
            dynamicOptions,
            dynamicOptionType,
            dynamicValueLoader,
            setAttributes
        );

        var output = '';
        if (type === 'select') {
            const optionsMarkup = options.map((option, index) => (
                `<option key=${index} value=${option.value} ${option.value === defaultValue ? 'selected' : ''}>${option.name}</option>`
            )).join('');
            output = `<select
            id="${fieldName}"
            name="${fieldName}"
            class="eb-field-input"
            value="${defaultValue}"
          >
          ${optionsMarkup}
          </select>`;
        } else if (type === 'radio') {
            output = options.map((option, index) => (
                `<div class="eb-radio-inputarea"><label for="${option.value}"><input id="${option.value}" name="${fieldName}" value="${option.value}" type="radio">${option.name}</label></div>`
            )).join('');
        } else if (type === 'checkbox') {
            output = options.map((option, index) => (
                `<div class="eb-checkbox-inputarea"><label for="${option.value}"><input id="${option.value}" name="${fieldName}" value="${option.value}" type="checkbox">${option.name}</label></div>`
            )).join('');
        }

        setContent(<div dangerouslySetInnerHTML={{ __html: output }} />)
    }, [dynamicOptionType, dynamicValue, options])
    return content;
}
