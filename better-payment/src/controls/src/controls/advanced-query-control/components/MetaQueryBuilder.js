/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    TextControl,
    SelectControl,
    Button,
    BaseControl,
    Flex,
    FlexItem,
    Notice,
    __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useCallback, memo } from "@wordpress/element";
import { plus, trash } from "@wordpress/icons";

/**
 * Meta Query Builder Component
 * 
 * Provides an interface for building complex meta queries with multiple conditions.
 * Supports various operators and meta value types.
 * 
 * @param {Object} props Component props
 * @param {Array} props.metaQuery Current meta query array
 * @param {Function} props.onChange Callback when meta query changes
 * @param {boolean} props.initialOpen Whether panel should be initially open
 */
function MetaQueryBuilder({ metaQuery = [], onChange, initialOpen = false }) {
    // Meta query operators
    const META_OPERATORS = [
        { label: __("Equal to", "essential-blocks"), value: "=" },
        { label: __("Not equal to", "essential-blocks"), value: "!=" },
        { label: __("Greater than", "essential-blocks"), value: ">" },
        { label: __("Greater than or equal", "essential-blocks"), value: ">=" },
        { label: __("Less than", "essential-blocks"), value: "<" },
        { label: __("Less than or equal", "essential-blocks"), value: "<=" },
        { label: __("Like", "essential-blocks"), value: "LIKE" },
        { label: __("Not like", "essential-blocks"), value: "NOT LIKE" },
        { label: __("In", "essential-blocks"), value: "IN" },
        { label: __("Not in", "essential-blocks"), value: "NOT IN" },
        { label: __("Between", "essential-blocks"), value: "BETWEEN" },
        { label: __("Not between", "essential-blocks"), value: "NOT BETWEEN" },
        { label: __("Exists", "essential-blocks"), value: "EXISTS" },
        { label: __("Not exists", "essential-blocks"), value: "NOT EXISTS" },
    ];

    // Meta value types
    const metaTypes = [
        { label: __("Text", "essential-blocks"), value: "CHAR" },
        { label: __("Number", "essential-blocks"), value: "NUMERIC" },
        { label: __("Date", "essential-blocks"), value: "DATE" },
        { label: __("DateTime", "essential-blocks"), value: "DATETIME" },
        { label: __("Time", "essential-blocks"), value: "TIME" },
        { label: __("Binary", "essential-blocks"), value: "BINARY" },
    ];

    // Add new meta query condition
    const addMetaCondition = useCallback(() => {
        const newCondition = {
            key: '',
            value: '',
            operator: '=',
            type: 'CHAR',
        };
        
        const updatedQuery = [...metaQuery, newCondition];
        onChange(updatedQuery);
    }, [metaQuery, onChange]);

    // Remove meta query condition
    const removeMetaCondition = useCallback((index) => {
        const updatedQuery = metaQuery.filter((_, i) => i !== index);
        onChange(updatedQuery);
    }, [metaQuery, onChange]);

    // Update meta query condition
    const updateMetaCondition = useCallback((index, field, value) => {
        const updatedQuery = [...metaQuery];
        updatedQuery[index] = {
            ...updatedQuery[index],
            [field]: value,
        };
        onChange(updatedQuery);
    }, [metaQuery, onChange]);

    // Get help text for meta value input based on operator and type
    const getMetaValueHelp = (operator, type) => {
        if (operator === 'BETWEEN' || operator === 'NOT BETWEEN') {
            return __("Enter two values separated by comma (e.g., 10,20)", "essential-blocks");
        }
        
        if (operator === 'IN' || operator === 'NOT IN') {
            return __("Enter multiple values separated by comma (e.g., value1,value2,value3)", "essential-blocks");
        }
        
        if (operator === 'LIKE' || operator === 'NOT LIKE') {
            return __("Use % as wildcard (e.g., %search% for contains)", "essential-blocks");
        }
        
        if (type === 'DATE') {
            return __("Enter date in YYYY-MM-DD format", "essential-blocks");
        }
        
        if (type === 'DATETIME') {
            return __("Enter datetime in YYYY-MM-DD HH:MM:SS format", "essential-blocks");
        }
        
        if (type === 'TIME') {
            return __("Enter time in HH:MM:SS format", "essential-blocks");
        }
        
        if (type === 'NUMERIC') {
            return __("Enter a numeric value", "essential-blocks");
        }
        
        return __("Enter the value to compare against", "essential-blocks");
    };

    return (
        <PanelBody
            title={__("Meta Query", "essential-blocks")}
            initialOpen={initialOpen}
            className="eb-meta-query-builder"
        >
            <p className="eb-meta-query-description">
                {__("Filter posts based on custom field values. Add multiple conditions to create complex queries.", "essential-blocks")}
            </p>
            <Notice status="info" isDismissible={false}>
                {__("Note: Meta queries may not fully reflect in the editor preview because the REST API has limited support for meta_query. The final filtering is correctly applied by PHP on the frontend.", "essential-blocks")}
            </Notice>

            {metaQuery.length === 0 && (
                <div className="eb-meta-query-empty">
                    <p>{__("No meta query conditions added yet.", "essential-blocks")}</p>
                </div>
            )}

            {/* Meta Query Conditions */}
            {metaQuery.map((condition, index) => (
                <div key={index} className="eb-meta-condition">
                    <BaseControl
                        label={__(`Meta Condition ${index + 1}`, "essential-blocks")}
                        className="eb-meta-condition-control"
                    >
                        {/* Meta Key */}
                        <TextControl
                            label={__("Meta Key", "essential-blocks")}
                            value={condition.key || ''}
                            onChange={(value) => updateMetaCondition(index, 'key', value)}
                            placeholder={__("Enter meta key...", "essential-blocks")}
                            help={__("The custom field key to query.", "essential-blocks")}
                        />

                        <Spacer marginY={2} />

                        {/* Operator and Type */}
                        <Flex>
                            <FlexItem>
                                <SelectControl
                                    label={__("Operator", "essential-blocks")}
                                    value={condition.operator || '='}
                                    options={META_OPERATORS}
                                    onChange={(value) => updateMetaCondition(index, 'operator', value)}
                                />
                            </FlexItem>
                            <FlexItem>
                                <SelectControl
                                    label={__("Type", "essential-blocks")}
                                    value={condition.type || 'CHAR'}
                                    options={metaTypes}
                                    onChange={(value) => updateMetaCondition(index, 'type', value)}
                                />
                            </FlexItem>
                        </Flex>

                        <Spacer marginY={2} />

                        {/* Meta Value */}
                        {condition.operator !== 'EXISTS' && condition.operator !== 'NOT EXISTS' && (
                            <>
                                <TextControl
                                    label={__("Meta Value", "essential-blocks")}
                                    value={condition.value || ''}
                                    onChange={(value) => updateMetaCondition(index, 'value', value)}
                                    placeholder={__("Enter meta value...", "essential-blocks")}
                                    help={getMetaValueHelp(condition.operator, condition.type)}
                                    type={condition.type === 'NUMERIC' ? 'number' : 'text'}
                                />
                                <Spacer marginY={2} />
                            </>
                        )}

                        {/* Remove Condition Button */}
                        <Button
                            icon={trash}
                            label={__("Remove Condition", "essential-blocks")}
                            onClick={() => removeMetaCondition(index)}
                            isDestructive
                            variant="secondary"
                            size="small"
                        >
                            {__("Remove", "essential-blocks")}
                        </Button>
                    </BaseControl>

                    {index < metaQuery.length - 1 && (
                        <div className="eb-meta-condition-separator">
                            <span>{__("AND", "essential-blocks")}</span>
                        </div>
                    )}

                    <Spacer marginY={3} />
                </div>
            ))}

            {/* Add Condition Button */}
            <Button
                icon={plus}
                onClick={addMetaCondition}
                variant="primary"
                className="eb-add-meta-condition"
            >
                {__("Add Meta Condition", "essential-blocks")}
            </Button>

            {metaQuery.length > 1 && (
                <>
                    <Spacer marginY={3} />
                    <div className="eb-meta-query-relation">
                        <BaseControl
                            label={__("Relation", "essential-blocks")}
                            help={__("How multiple meta conditions should be combined.", "essential-blocks")}
                        >
                            <p className="eb-meta-relation-note">
                                {__("Currently only AND relation is supported. All conditions must be true.", "essential-blocks")}
                            </p>
                        </BaseControl>
                    </div>
                </>
            )}
        </PanelBody>
    );
}

export default memo(MetaQueryBuilder);
