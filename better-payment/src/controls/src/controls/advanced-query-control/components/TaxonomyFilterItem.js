/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";
import { __experimentalSpacer as Spacer } from "@wordpress/components";
import { memo, useMemo, useCallback } from "@wordpress/element";

/**
 * External Dependencies
 */
import AsyncSelect from "react-select/async";

/**
 * Internal Dependencies
 */
import { ButtonGroupControl } from "../../button-group-control";
import { INCLUDE_EXCLUDE } from "../../custom-query/constants";
import { getTaxonomyCategories } from "../../custom-query/apiData";

/**
 * Optimized Taxonomy Filter Item Component
 * 
 * This component is memoized to prevent unnecessary re-renders
 * and includes performance optimizations for large taxonomy lists.
 * 
 * @param {Object} props Component props
 * @param {string} props.taxonomySlug Taxonomy slug
 * @param {Object} props.taxonomy Taxonomy data
 * @param {Object} props.taxonomySelectTypes Select types state
 * @param {Object} props.taxonomyFilters Current filters
 * @param {Function} props.onSelectTypeChange Handler for select type change
 * @param {Function} props.onFilterChange Handler for filter change
 */
function TaxonomyFilterItem({
    taxonomySlug,
    taxonomy,
    taxonomySelectTypes,
    taxonomyFilters,
    onSelectTypeChange,
    onFilterChange,
}) {
    // Memoize current select type to prevent recalculation
    const currentSelectType = useMemo(() => {
        return taxonomySelectTypes[taxonomySlug] || 'include';
    }, [taxonomySelectTypes, taxonomySlug]);

    // Memoize current taxonomy object to prevent recalculation
    const currentTaxonomyObj = useMemo(() => {
        return taxonomyFilters[taxonomySlug] || {};
    }, [taxonomyFilters, taxonomySlug]);

    // Memoize current values with safe JSON parsing
    const currentValues = useMemo(() => {
        try {
            if (currentSelectType === 'include') {
                return currentTaxonomyObj.value ? JSON.parse(currentTaxonomyObj.value) : [];
            } else {
                return currentTaxonomyObj.exclude ? JSON.parse(currentTaxonomyObj.exclude) : [];
            }
        } catch (error) {
            console.warn(`Error parsing taxonomy values for ${taxonomySlug}:`, error);
            return [];
        }
    }, [currentSelectType, currentTaxonomyObj, taxonomySlug]);

    // Memoize default options to prevent recreation
    const defaultOptions = useMemo(() => {
        return taxonomy.terms || [];
    }, [taxonomy.terms]);

    // Memoize placeholder text
    const placeholder = useMemo(() => {
        return __(`Search ${taxonomy.name}...`, "essential-blocks");
    }, [taxonomy.name]);

    // Optimized select type change handler
    const handleSelectTypeChange = useCallback((value) => {
        onSelectTypeChange(taxonomySlug, value);
    }, [onSelectTypeChange, taxonomySlug]);

    // Optimized filter change handler
    const handleFilterChange = useCallback((selected) => {
        onFilterChange(
            taxonomySlug, 
            selected, 
            currentSelectType
        );
    }, [onFilterChange, taxonomySlug, currentSelectType]);

    // Optimized load options function with caching
    const loadOptions = useCallback(async (searchValue) => {
        // Return default options for empty search
        if (!searchValue || searchValue.length < 2) {
            return defaultOptions;
        }

        try {
            // Use the cached API function
            const searchResults = await getTaxonomyCategories(taxonomy.rest_base, searchValue);
            return searchResults || [];
        } catch (error) {
            console.warn(`Error loading taxonomy options for ${taxonomySlug}:`, error);
            return defaultOptions;
        }
    }, [defaultOptions, taxonomy.rest_base, taxonomySlug]);

    return (
        <div className="eb-taxonomy-filter">
            <ButtonGroupControl
                label={taxonomy.name}
                attrName={'taxonomySelectType'}
                options={INCLUDE_EXCLUDE}
                currentValue={currentSelectType}
                onChange={handleSelectTypeChange}
            />

            <Spacer marginY={2} />

            {/* Optimized Taxonomy Terms Selection */}
            <AsyncSelect
                cacheOptions={true}
                value={currentValues}
                defaultOptions={defaultOptions.slice(0, 50)} // Limit initial options for performance
                placeholder={placeholder}
                onChange={handleFilterChange}
                isMulti={true}
                loadOptions={loadOptions}
                // Performance optimizations
                menuPortalTarget={document.body}
                menuPosition="fixed"
                maxMenuHeight={200}
                // Reduce re-renders
                blurInputOnSelect={false}
                closeMenuOnSelect={false}
                // Improve search performance
                filterOption={null} // Let server handle filtering
                // Debounce search input
                debounceTimeout={300}
                // Limit options for performance
                pageSize={20}
                // Styling optimizations
                styles={{
                    menuPortal: (base) => ({
                        ...base,
                        zIndex: 9999
                    }),
                    menu: (base) => ({
                        ...base,
                        maxHeight: 200,
                        overflowY: 'auto'
                    }),
                    option: (base) => ({
                        ...base,
                        fontSize: '14px',
                        padding: '8px 12px'
                    }),
                    // Optimize multi-value container
                    multiValue: (base) => ({
                        ...base,
                        fontSize: '12px'
                    }),
                    // Optimize control
                    control: (base) => ({
                        ...base,
                        minHeight: '36px'
                    })
                }}
                // Additional performance props
                components={{
                    // Custom option component for better performance
                    Option: ({ children, ...props }) => (
                        <div {...props.innerProps} style={{
                            padding: '8px 12px',
                            fontSize: '14px',
                            backgroundColor: props.isSelected ? '#0073aa' : props.isFocused ? '#f0f0f0' : 'white',
                            color: props.isSelected ? 'white' : 'black',
                            cursor: 'pointer'
                        }}>
                            {children}
                        </div>
                    )
                }}
            />
            
            <Spacer marginY={3} />
        </div>
    );
}

// Export memoized component to prevent unnecessary re-renders
export default memo(TaxonomyFilterItem, (prevProps, nextProps) => {
    // Custom comparison function for better performance
    return (
        prevProps.taxonomySlug === nextProps.taxonomySlug &&
        prevProps.taxonomy === nextProps.taxonomy &&
        prevProps.taxonomySelectTypes[prevProps.taxonomySlug] === nextProps.taxonomySelectTypes[nextProps.taxonomySlug] &&
        JSON.stringify(prevProps.taxonomyFilters[prevProps.taxonomySlug]) === JSON.stringify(nextProps.taxonomyFilters[nextProps.taxonomySlug])
    );
});
