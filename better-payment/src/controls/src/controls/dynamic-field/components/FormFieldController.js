/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";
import {
    Button,
    BaseControl,
    ButtonGroup,
} from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";
import { OPTIONS_TYPE } from "./constants";
import SortableControl from "../../sortable-control";

export function DynamicFormFieldControl(props) {
    const {
        type,
        options,
        dynamicOptions,
        dynamicValue,
        dynamicOptionType,
        dynamicValueLoader,
        setAttributes
    } = props;

    const [optionType, setOptionType] = useState("normal");

    const onOptionAdd = () => {
        if (!options || typeof options !== "object") {
            return;
        }

        const count = options.length + 1;
        const newOptions = [
            ...options,
            {
                name: `Option ${count}`,
                value: `option_${count}`,
            },
        ];

        setAttributes({ options: newOptions });
    };

    return (
        <>
            <div className="eb-dynamic-select-control">
                <BaseControl>
                    <ButtonGroup>
                        {OPTIONS_TYPE.map(
                            (item, key) => (
                                <Button
                                    key={key}
                                    // isLarge
                                    isPrimary={
                                        dynamicOptionType ===
                                        item.value
                                    }
                                    isSecondary={
                                        dynamicOptionType !==
                                        item.value
                                    }
                                    onClick={() =>
                                        setAttributes({ dynamicOptionType: item.value })
                                    }
                                >
                                    {item.label}
                                </Button>
                            )
                        )}
                    </ButtonGroup>
                </BaseControl>
                {dynamicOptionType === 'normal' && (
                    <>
                        {/* <PanelRow>Manage Options</PanelRow> */}
                        <BaseControl
                            label={__(
                                "Manage Options",
                                "essential-blocks"
                            )}
                            id="eb-advance-heading-alignment"
                        >

                        </BaseControl>
                        <SortableControl
                            valueEditable={false}
                            options={options}
                            setAttributes={setAttributes}
                        />
                        <Button
                            className="eb-add-options-button"
                            label={__(
                                "Add New Option",
                                "essential-blocks"
                            )}
                            icon="plus-alt"
                            onClick={onOptionAdd}
                        >
                            <span className="eb-add-options-button-label">
                                {__(
                                    "Add New Option",
                                    "essential-blocks"
                                )}
                            </span>
                        </Button>
                    </>
                )}
                {dynamicOptionType === 'dynamic' && (
                    applyFilters(
                        "essential_blocks_dynamic_select_control",
                        "",
                        options,
                        dynamicOptions,
                        dynamicValue,
                        dynamicValueLoader,
                        setAttributes
                    )
                )}
            </div>
        </>
    );
}
