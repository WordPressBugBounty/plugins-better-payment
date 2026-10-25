/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    SelectControl,
    RangeControl,
    DateTimePicker,
    BaseControl,
    Flex,
    FlexItem,
    TextControl,
    Notice,
    __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { useState, useCallback, memo } from "@wordpress/element";

/**
 * Date Query Builder Component
 * 
 * Provides an interface for building date-based queries with various options
 * including relative dates, specific date ranges, and date comparisons.
 * 
 * @param {Object} props Component props
 * @param {Object} props.dateQuery Current date query object
 * @param {Function} props.onChange Callback when date query changes
 * @param {boolean} props.initialOpen Whether panel should be initially open
 */
function DateQueryBuilder({ dateQuery = {}, onChange, initialOpen = false }) {
    const [dateQueryType, setDateQueryType] = useState(dateQuery.type || 'none');

    // Date query types
    const dateQueryTypes = [
        { label: __("None", "essential-blocks"), value: "none" },
        { label: __("Last X Days", "essential-blocks"), value: "last_days" },
        { label: __("Date Range", "essential-blocks"), value: "date_range" },
        { label: __("This Week", "essential-blocks"), value: "this_week" },
        { label: __("This Month", "essential-blocks"), value: "this_month" },
        { label: __("This Year", "essential-blocks"), value: "this_year" },
        { label: __("Custom", "essential-blocks"), value: "custom" },
    ];

    // Date comparison operators
    const dateCompareOptions = [
        { label: __("Equal to", "essential-blocks"), value: "=" },
        { label: __("Not equal to", "essential-blocks"), value: "!=" },
        { label: __("Greater than", "essential-blocks"), value: ">" },
        { label: __("Greater than or equal", "essential-blocks"), value: ">=" },
        { label: __("Less than", "essential-blocks"), value: "<" },
        { label: __("Less than or equal", "essential-blocks"), value: "<=" },
        { label: __("Between", "essential-blocks"), value: "BETWEEN" },
        { label: __("Not between", "essential-blocks"), value: "NOT BETWEEN" },
        { label: __("In", "essential-blocks"), value: "IN" },
        { label: __("Not in", "essential-blocks"), value: "NOT IN" },
    ];

    // Date column options
    const dateColumnOptions = [
        { label: __("Post Date", "essential-blocks"), value: "post_date" },
        { label: __("Post Modified", "essential-blocks"), value: "post_modified" },
        { label: __("Post Date GMT", "essential-blocks"), value: "post_date_gmt" },
        { label: __("Post Modified GMT", "essential-blocks"), value: "post_modified_gmt" },
    ];

    // Handle date query type change
    const handleDateQueryTypeChange = useCallback((type) => {
        setDateQueryType(type);
        
        let newDateQuery = { type };
        
        switch (type) {
            case 'none':
                newDateQuery = {};
                break;
            case 'last_days':
                newDateQuery = {
                    type,
                    days: dateQuery.days || 7,
                };
                break;
            case 'date_range':
                newDateQuery = {
                    type,
                    after: dateQuery.after || '',
                    before: dateQuery.before || '',
                };
                break;
            case 'this_week':
            case 'this_month':
            case 'this_year':
                newDateQuery = { type };
                break;
            case 'custom':
                newDateQuery = {
                    type,
                    year: dateQuery.year || '',
                    month: dateQuery.month || '',
                    day: dateQuery.day || '',
                    hour: dateQuery.hour || '',
                    minute: dateQuery.minute || '',
                    second: dateQuery.second || '',
                    compare: dateQuery.compare || '=',
                    column: dateQuery.column || 'post_date',
                };
                break;
        }
        
        onChange(newDateQuery);
    }, [dateQuery, onChange]);

    // Update date query field
    const updateDateQuery = useCallback((field, value) => {
        const updatedQuery = {
            ...dateQuery,
            [field]: value,
        };
        onChange(updatedQuery);
    }, [dateQuery, onChange]);

    return (
        <PanelBody
            title={__("Date Query", "essential-blocks")}
            initialOpen={initialOpen}
            className="eb-date-query-builder"
        >
            <p className="eb-date-query-description">
                {__("Filter posts based on their publication or modification dates.", "essential-blocks")}
            </p>
            <Notice status="info" isDismissible={false}>
                {__("Note: Date filters may not fully reflect in the editor preview because the REST API has limited support for complex date queries. The final filtering is correctly applied by PHP on the frontend.", "essential-blocks")}
            </Notice>

            {/* Date Query Type */}
            <SelectControl
                label={__("Date Filter Type", "essential-blocks")}
                value={dateQueryType}
                options={dateQueryTypes}
                onChange={handleDateQueryTypeChange}
                help={__("Choose how to filter posts by date.", "essential-blocks")}
            />

            <Spacer marginY={3} />

            {/* Last X Days */}
            {dateQueryType === 'last_days' && (
                <RangeControl
                    label={__("Number of Days", "essential-blocks")}
                    value={dateQuery.days || 7}
                    onChange={(value) => updateDateQuery('days', value)}
                    min={1}
                    max={365}
                    help={__("Show posts from the last X days.", "essential-blocks")}
                />
            )}

            {/* Date Range */}
            {dateQueryType === 'date_range' && (
                <>
                    <BaseControl
                        label={__("Start Date", "essential-blocks")}
                        help={__("Posts published after this date will be included.", "essential-blocks")}
                    >
                        <DateTimePicker
                            currentDate={dateQuery.after || null}
                            onChange={(date) => updateDateQuery('after', date)}
                            is12Hour={true}
                        />
                    </BaseControl>

                    <Spacer marginY={3} />

                    <BaseControl
                        label={__("End Date", "essential-blocks")}
                        help={__("Posts published before this date will be included.", "essential-blocks")}
                    >
                        <DateTimePicker
                            currentDate={dateQuery.before || null}
                            onChange={(date) => updateDateQuery('before', date)}
                            is12Hour={true}
                        />
                    </BaseControl>
                </>
            )}

            {/* Predefined Periods Info */}
            {(dateQueryType === 'this_week' || dateQueryType === 'this_month' || dateQueryType === 'this_year') && (
                <div className="eb-date-query-info">
                    <p>
                        {dateQueryType === 'this_week' && __("Shows posts from the current week (Monday to Sunday).", "essential-blocks")}
                        {dateQueryType === 'this_month' && __("Shows posts from the current month.", "essential-blocks")}
                        {dateQueryType === 'this_year' && __("Shows posts from the current year.", "essential-blocks")}
                    </p>
                </div>
            )}

            {/* Custom Date Query */}
            {dateQueryType === 'custom' && (
                <>
                    <SelectControl
                        label={__("Date Column", "essential-blocks")}
                        value={dateQuery.column || 'post_date'}
                        options={dateColumnOptions}
                        onChange={(value) => updateDateQuery('column', value)}
                        help={__("Which date field to query against.", "essential-blocks")}
                    />

                    <Spacer marginY={3} />

                    <SelectControl
                        label={__("Comparison", "essential-blocks")}
                        value={dateQuery.compare || '='}
                        options={dateCompareOptions}
                        onChange={(value) => updateDateQuery('compare', value)}
                    />

                    <Spacer marginY={3} />

                    {/* Year, Month, Day inputs */}
                    <Flex>
                        <FlexItem>
                            <TextControl
                                label={__("Year", "essential-blocks")}
                                type="number"
                                placeholder="YYYY"
                                value={dateQuery.year ?? ''}
                                min={1970}
                                max={new Date().getFullYear() + 10}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('year', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                        <FlexItem>
                            <TextControl
                                label={__("Month", "essential-blocks")}
                                type="number"
                                placeholder="MM"
                                value={dateQuery.month ?? ''}
                                min={1}
                                max={12}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('month', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                        <FlexItem>
                            <TextControl
                                label={__("Day", "essential-blocks")}
                                type="number"
                                placeholder="DD"
                                value={dateQuery.day ?? ''}
                                min={1}
                                max={31}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('day', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                    </Flex>

                    <Spacer marginY={3} />

                    {/* Hour, Minute, Second inputs */}
                    <Flex>
                        <FlexItem>
                            <TextControl
                                label={__("Hour", "essential-blocks")}
                                type="number"
                                placeholder="HH"
                                value={dateQuery.hour ?? ''}
                                min={0}
                                max={23}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('hour', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                        <FlexItem>
                            <TextControl
                                label={__("Minute", "essential-blocks")}
                                type="number"
                                placeholder="MM"
                                value={dateQuery.minute ?? ''}
                                min={0}
                                max={59}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('minute', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                        <FlexItem>
                            <TextControl
                                label={__("Second", "essential-blocks")}
                                type="number"
                                placeholder="SS"
                                value={dateQuery.second ?? ''}
                                min={0}
                                max={59}
                                step={1}
                                onChange={(val) => {
                                    const v = val === '' ? '' : parseInt(val, 10);
                                    updateDateQuery('second', Number.isFinite(v) ? v : '');
                                }}
                            />
                        </FlexItem>
                    </Flex>

                    <div className="eb-date-query-custom-help">
                        <p>
                            {__("Leave fields empty to ignore them in the query. For example, setting only year=2023 will show all posts from 2023.", "essential-blocks")}
                        </p>
                    </div>
                </>
            )}
        </PanelBody>
    );
}

export default memo(DateQueryBuilder);
