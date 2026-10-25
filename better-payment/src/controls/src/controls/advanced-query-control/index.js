/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    SelectControl,
    TextControl,
    ToggleControl,
    RangeControl,
    BaseControl,
    Flex,
    FlexItem,
    __experimentalSpacer as Spacer,
} from "@wordpress/components";

import { useEffect, useState, useRef, memo, useMemo, useCallback } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { applyFilters } from "@wordpress/hooks";

/**
 * External Dependencies
 */
import AsyncSelect from "react-select/async";

/**
 * Internal Dependencies
 */
import { ORDER_BY, ORDER } from "./constants"
import {
    getAuthor,
    getLatest5Posts,
    getPostsBySearchString,
    getTaxonomiesByPostType,
    getTaxonomyCategories,
} from "../custom-query/apiData";
import ProSelectControl from "../pro-select-control";

// Import sub-components
import MetaQueryBuilder from "./components/MetaQueryBuilder";
import DateQueryBuilder from "./components/DateQueryBuilder";
import TaxonomyFilterItem from "./components/TaxonomyFilterItem";

/**
 * Advanced Query Control Component
 *
 * A comprehensive, reusable query building component for Essential Blocks.
 * Provides advanced query capabilities including dynamic post types, taxonomies,
 * meta queries, date queries, and performance optimizations.
 *
 * @param {Object} props Component props
 * @param {Object} props.attributes Block attributes containing query data
 * @param {Function} props.setAttributes Function to update block attributes
 * @param {boolean} props.initialOpen Whether panel should be initially open
 * @param {Function} props.onQueryChange Callback when query changes (queryData, results)
 * @param {Function} props.onQueryLoading Callback when query loading state changes
 * @param {Function} props.onQueryError Callback when query error occurs
 * @param {boolean} props.showAdvancedOptions Whether to show advanced options
 * @param {boolean} props.showMetaQuery Whether to show meta query builder
 * @param {boolean} props.showDateQuery Whether to show date query builder
 * @param {string} props.panelTitle Custom panel title
 * @param {Object} props.queryConfig Configuration for query behavior
 */
function AdvancedQueryControl(props) {
    const {
        attributes,
        setAttributes,
        initialOpen = true,
        onQueryChange = () => { },
        onQueryLoading = () => { },
        onQueryError = () => { },
        showAdvancedOptions = true,
        showMetaQuery = true,
        showDateQuery = true,
        panelTitle = __("Advanced Query", "essential-blocks"),
        queryConfig = {}
    } = props;

    // Extract query-related attributes with fallbacks
    const {
        // Basic query attributes
        queryData = {},
        postType = attributes.postType || queryData.source || "post",
        postsPerPage = attributes.postsPerPage || queryData.per_page || 6,
        orderBy = attributes.orderBy || queryData.orderby || "date",
        order = attributes.order || queryData.order || "desc",
        includeIds = attributes.includeIds || queryData.include || "",
        excludeIds = attributes.excludeIds || queryData.exclude || "",
        authorIds = attributes.authorIds || queryData.author || "",

        // Advanced attributes
        taxonomyFilters = attributes.taxonomyFilters || queryData.taxonomies || {},
        metaQuery = attributes.metaQuery || [],
        dateQuery = attributes.dateQuery || {},

        // Options
        stickyPosts = attributes.stickyPosts || queryData.sticky || false,
        excludeCurrentPost = attributes.excludeCurrentPost || queryData.exclude_current || false,
        ignoreStickyPosts = attributes.ignoreStickyPosts || queryData.ignore_sticky_posts || false,
        excludePasswordProtected = attributes.excludePasswordProtected || queryData.exclude_password_protected || false,
    } = attributes;

    // Component state
    const didMount = useRef(false);
    const [isTaxonomyLoading, setIsTaxonomyLoading] = useState(false);
    const [isQueryLoading, setIsQueryLoading] = useState(false);
    const [authorOptions, setAuthorOptions] = useState([]);
    const [postOptions, setPostOptions] = useState([]);
    const [taxonomyData, setTaxonomyData] = useState({});
    const [taxonomySelectTypes, setTaxonomySelectTypes] = useState({});

    // Get available post types dynamically
    const availablePostTypes = useSelect((select) => {
        const postTypes = select("core").getPostTypes({ per_page: -1 });

        if (!postTypes || postTypes.length === 0) {
            return [{ label: __("Post", "essential-blocks"), value: "post" }];
        }

        return postTypes
            .filter(type => type.viewable && type.slug !== "attachment")
            .map(type => ({
                label: type.name,
                value: type.slug,
                rest_base: type.rest_base,
                rest_namespace: type.rest_namespace || "wp/v2",
            }));
    }, []);

    // Memoized current post type data
    const currentPostTypeData = useMemo(() => {
        return availablePostTypes.find(type => type.value === postType) || availablePostTypes[0];
    }, [availablePostTypes, postType]);

    // Initialize component
    useEffect(() => {
        didMount.current = true;
        return () => {
            didMount.current = false;
        };
    }, []);

    // Build query object for compatibility with existing API
    const buildQueryObject = useCallback(() => {
        // Use saved postType from attributes as the primary source
        const sourcePostType = postType;

        // Try to get rest_base from currentPostTypeData, but fallback intelligently
        let restBase = currentPostTypeData?.rest_base;
        let restNamespace = currentPostTypeData?.rest_namespace || "wp/v2";

        // If currentPostTypeData is not available yet (during initial load),
        // use intelligent fallbacks based on common WordPress post types
        if (!restBase) {
            switch (sourcePostType) {
                case 'post':
                    restBase = 'posts';
                    break;
                case 'page':
                    restBase = 'pages';
                    break;
                case 'attachment':
                    restBase = 'media';
                    break;
                default:
                    // For custom post types, try to use the post type slug as rest_base
                    // This works for most custom post types that follow WordPress conventions
                    restBase = sourcePostType + 's'; // e.g., 'product' -> 'products'
                    break;
            }
        }

        const queryObject = {
            source: sourcePostType,
            rest_base: restBase,
            rest_namespace: restNamespace,
            per_page: postsPerPage,
            orderby: orderBy,
            order: order,
            include: includeIds,
            exclude: excludeIds,
            author: authorIds,
            taxonomies: taxonomyFilters,
            sticky: stickyPosts,
            exclude_current: excludeCurrentPost,
            ignore_sticky_posts: ignoreStickyPosts,
            exclude_password_protected: excludePasswordProtected,
        };

        return queryObject;
    }, [currentPostTypeData?.rest_base, currentPostTypeData?.rest_namespace, postType, postsPerPage, orderBy, order, includeIds, excludeIds, authorIds, taxonomyFilters, stickyPosts, excludeCurrentPost, ignoreStickyPosts, excludePasswordProtected]);
    // Track the last loaded post type to prevent unnecessary reloads
    const lastLoadedPostType = useRef(null);

    // Load taxonomies when post type changes
    useEffect(() => {
        if (!didMount.current || !postType) return;

        // Skip if we've already loaded data for this post type
        if (lastLoadedPostType.current === postType) {
            return;
        }

        lastLoadedPostType.current = postType;
        setIsTaxonomyLoading(true);

        // Clear existing taxonomy data when post type changes
        setTaxonomyData({});
        setTaxonomySelectTypes({});

        // Create a minimal query object for taxonomy loading
        const queryObj = {
            source: postType,
            rest_base: currentPostTypeData?.rest_base || (postType === 'post' ? 'posts' : postType + 's'),
            rest_namespace: currentPostTypeData?.rest_namespace || "wp/v2",
        };

        // Load taxonomies specifically for the selected post type
        getTaxonomiesByPostType(queryObj).then((taxonomies) => {
            // Check if component is still mounted and post type hasn't changed
            if (!didMount.current || lastLoadedPostType.current !== postType) {
                return;
            }

            const taxonomyDataObj = {};
            let loadedCount = 0;
            const totalTaxonomies = taxonomies ? taxonomies.length : 0;

            if (totalTaxonomies === 0) {
                setIsTaxonomyLoading(false);
                return;
            }

            // Load terms for each taxonomy (already filtered by API)
            taxonomies.forEach((taxonomy) => {
                getTaxonomyCategories(taxonomy.rest_base).then((terms) => {
                    // Check if component is still mounted and post type hasn't changed
                    if (!didMount.current || lastLoadedPostType.current !== postType) {
                        return;
                    }

                    taxonomyDataObj[taxonomy.slug] = {
                        name: taxonomy.name,
                        rest_base: taxonomy.rest_base,
                        rest_namespace: taxonomy.rest_namespace,
                        slug: taxonomy.slug,
                        types: taxonomy.types || [],
                        terms: terms || [],
                    };

                    setTaxonomyData(prev => ({
                        ...prev,
                        [taxonomy.slug]: taxonomyDataObj[taxonomy.slug]
                    }));

                    loadedCount++;
                    if (loadedCount >= totalTaxonomies) {
                        setIsTaxonomyLoading(false);
                    }
                }).catch((error) => {
                    console.error(`Error loading terms for taxonomy ${taxonomy.slug}:`, error);
                    loadedCount++;
                    if (loadedCount >= totalTaxonomies && didMount.current) {
                        setIsTaxonomyLoading(false);
                    }
                });
            });
        }).catch((error) => {
            console.error('Error loading taxonomies:', error);
            if (didMount.current) {
                setIsTaxonomyLoading(false);
            }
        });

        // Load sample posts for include/exclude options
        getLatest5Posts(queryObj).then((posts) => {
            // Check if component is still mounted and post type hasn't changed
            if (!didMount.current || lastLoadedPostType.current !== postType) {
                return;
            }

            const postOptionsData = posts.map(post => ({
                label: post.title?.rendered || "",
                value: post.id,
            }));
            setPostOptions(postOptionsData);
        }).catch((error) => {
            console.error('Error loading posts:', error);
        });

    }, [postType, currentPostTypeData?.rest_base, currentPostTypeData?.rest_namespace]);

    // Memoize the complete query data to prevent unnecessary re-executions
    const completeQueryData = useMemo(() => {
        const baseQuery = buildQueryObject();

        // Add meta and date queries if enabled
        if (showMetaQuery && metaQuery.length > 0) {
            baseQuery.metaQuery = metaQuery;
        }

        if (showDateQuery && dateQuery.type && dateQuery.type !== 'none') {
            baseQuery.dateQuery = dateQuery;
        }

        return baseQuery;
    }, [buildQueryObject, showMetaQuery, metaQuery, showDateQuery, dateQuery]);

    // Create a stable reference for the query execution
    const lastQueryRef = useRef(null);

    // Execute query and fetch results when query parameters change
    useEffect(() => {
        if (!didMount.current) return;

        // Create a string representation of the query to compare
        const queryString = JSON.stringify(completeQueryData);

        // Skip execution if query hasn't actually changed
        if (lastQueryRef.current === queryString) {
            return;
        }

        lastQueryRef.current = queryString;

        // Check if auto-execution is enabled (default: true)
        const autoExecute = queryConfig.autoExecute !== false;

        if (autoExecute) {
            // Set loading state
            setIsQueryLoading(true);
            onQueryLoading(true);

            // Execute query and fetch results
            import('../custom-query/apiData').then(({ getPosts }) => {
                getPosts(completeQueryData).then((posts) => {
                    // Only update if component is still mounted and query hasn't changed
                    if (didMount.current && lastQueryRef.current === queryString) {
                        // Store query results in block attributes
                        setAttributes({
                            queryData: completeQueryData,
                            queryResults: posts
                        });

                        // Call the onQueryChange callback with both query and results
                        onQueryChange(completeQueryData, posts);

                        setIsQueryLoading(false);
                        onQueryLoading(false);
                    }
                }).catch((error) => {
                    if (didMount.current && lastQueryRef.current === queryString) {
                        console.error('Error executing query:', error);
                        setIsQueryLoading(false);
                        onQueryLoading(false);
                        onQueryError(error);

                        // Still call onQueryChange with query data even if fetch fails
                        onQueryChange(completeQueryData, []);
                    }
                });
            });
        } else {
            // Just call onQueryChange with query data, no execution
            onQueryChange(completeQueryData);
        }
    }, [completeQueryData, onQueryChange, onQueryLoading, onQueryError, queryConfig.autoExecute]);

    // Optimized author loading function
    const loadAuthors = useCallback(async (searchValue) => {
        if (typeof searchValue === 'string' && searchValue.length < 3) {
            return [];
        }

        try {
            const authors = await getAuthor(searchValue);
            const authorOptionsData = authors.map(author => ({
                label: author.name,
                value: author.id,
            }));

            if (searchValue?.type === 'focus') {
                setAuthorOptions(authorOptionsData);
            }

            return authorOptionsData;
        } catch (error) {
            console.error('Error loading authors:', error);
            return [];
        }
    }, []);

    // Optimized post loading function
    const loadPosts = useCallback(async (searchValue) => {
        if (!searchValue || searchValue.length < 2) {
            return postOptions;
        }

        try {
            // Create a minimal query object for post search
            const queryObj = {
                source: postType,
                rest_base: currentPostTypeData?.rest_base || (postType === 'post' ? 'posts' : postType + 's'),
                rest_namespace: currentPostTypeData?.rest_namespace || "wp/v2",
            };
            const posts = await getPostsBySearchString(searchValue, queryObj);
            return posts.map(post => ({
                label: post.title,
                value: post.id,
            }));
        } catch (error) {
            console.error('Error loading posts:', error);
            return [];
        }
    }, [postType, currentPostTypeData?.rest_base, currentPostTypeData?.rest_namespace, postOptions]);

    // Handle post type change
    const handlePostTypeChange = useCallback((newPostType) => {
        // Clear existing taxonomy data immediately for better UX
        setTaxonomyData({});
        setTaxonomySelectTypes({});

        // Reset the last loaded post type to force reload
        lastLoadedPostType.current = null;

        // Create a minimal query data update
        const updatedQueryData = {
            ...queryData,
            source: newPostType,
            taxonomies: {}, // Reset taxonomies when post type changes
        };

        setAttributes({
            queryData: updatedQueryData,
            postType: newPostType,
            taxonomyFilters: {}, // Clear taxonomy filters when post type changes
        });
    }, [queryData]);

    // Handle taxonomy filter change - Optimized (immutable + consistent selectType handling)
    const handleTaxonomyFilterChange = useCallback((taxonomySlug, values, selectType = 'include') => {
        // Normalize current and new values as strings for comparison
        const currentTaxonomyObj = taxonomyFilters[taxonomySlug] || {};
        const newValuesString = JSON.stringify(values);

        const currentValuesString = selectType === 'exclude'
            ? currentTaxonomyObj.exclude
            : currentTaxonomyObj.value;

        // Early return if values haven't changed
        if (currentValuesString === newValuesString) {
            return;
        }

        // Build next entry immutably
        const baseEntry = (currentTaxonomyObj && Object.keys(currentTaxonomyObj).length)
            ? { ...currentTaxonomyObj }
            : {
                name: taxonomyData[taxonomySlug]?.name || taxonomySlug,
                rest_base: taxonomyData[taxonomySlug]?.rest_base || taxonomySlug,
                rest_namespace: taxonomyData[taxonomySlug]?.rest_namespace || 'wp/v2',
            };

        const nextEntry = selectType === 'exclude'
            ? { ...baseEntry, exclude: newValuesString }
            : { ...baseEntry, value: newValuesString };

        // Build updatedFilters immutably
        let updatedFilters = { ...taxonomyFilters, [taxonomySlug]: nextEntry };

        // Optionally remove the taxonomy if both include and exclude are empty/absent
        if (!nextEntry.value && !nextEntry.exclude) {
            const { [taxonomySlug]: _removed, ...rest } = updatedFilters;
            updatedFilters = rest;
        }

        // Update both new and legacy formats
        const updatedQueryData = {
            ...queryData,
            taxonomies: updatedFilters,
        };

        setAttributes({
            taxonomyFilters: updatedFilters,
            queryData: updatedQueryData,
        });
    }, [taxonomyFilters, taxonomyData, queryData, setAttributes]);

    // Handle taxonomy select type change (include/exclude) - Optimized
    const handleTaxonomySelectTypeChange = useCallback((taxonomySlug, selectType) => {
        setTaxonomySelectTypes(prev => {
            // Only update if value actually changed
            if (prev[taxonomySlug] === selectType) {
                return prev;
            }
            return {
                ...prev,
                [taxonomySlug]: selectType
            };
        });
    }, []);

    // Update query data helper
    const updateQueryData = useCallback((key, value, isJson = false) => {
        const updatedQueryData = {
            ...queryData,
            [key]: isJson ? JSON.stringify(value) : value,
        };

        // Map queryData keys to direct attribute names for consistency
        const keyMapping = {
            'per_page': 'postsPerPage',
            'source': 'postType',
            'orderby': 'orderBy',
            'order': 'order',
            'include': 'includeIds',
            'exclude': 'excludeIds',
            'author': 'authorIds',
            'taxonomies': 'taxonomyFilters',
            'sticky': 'stickyPosts',
            'exclude_current': 'excludeCurrentPost',
            'ignore_sticky_posts': 'ignoreStickyPosts',
            'exclude_password_protected': 'excludePasswordProtected',
        };

        const directAttributeKey = keyMapping[key] || key;

        setAttributes({
            queryData: updatedQueryData,
            [directAttributeKey]: value,
        });
    }, [queryData]);

    return (
        <PanelBody
            title={panelTitle}
            initialOpen={initialOpen}
            className="eb-advanced-query-control"
        >
            {/* Post Type Selection */}
            <SelectControl
                label={__("Post Type", "essential-blocks")}
                value={postType}
                options={availablePostTypes}
                onChange={handlePostTypeChange}
                help={__("Select the post type to query. Taxonomies will update automatically.", "essential-blocks")}
            />

            <Spacer marginY={3} />

            {/* Posts Per Page */}
            <RangeControl
                label={__("Posts Per Page", "essential-blocks")}
                value={postsPerPage}
                onChange={(value) => updateQueryData('per_page', value)}
                min={1}
                max={50}
                help={__("Number of posts to display per page.", "essential-blocks")}
            />

            <Spacer marginY={3} />

            {/* Order Controls */}
            <Flex>
                <FlexItem style={{ flex: 1 }}>
                    <ProSelectControl
                        label={__("Order By", "essential-blocks")}
                        value={orderBy}
                        options={applyFilters("eb_advanced_query_orderby_options", ORDER_BY)}
                        onChange={(value) => updateQueryData('orderby', value)}
                    />
                </FlexItem>
                <FlexItem style={{ flex: 1 }}>
                    <SelectControl
                        label={__("Order", "essential-blocks")}
                        value={order}
                        options={ORDER}
                        onChange={(value) => updateQueryData('order', value)}
                    />
                </FlexItem>
            </Flex>

            <Spacer marginY={3} />

            {/* Author Selection */}
            <BaseControl
                label={__("Authors", "essential-blocks")}
                help={__("Select specific authors to include in the query.", "essential-blocks")}
            >
                <AsyncSelect
                    cacheOptions
                    value={authorIds ? (typeof authorIds === 'string' ? JSON.parse(authorIds || '[]') : authorIds) : []}
                    defaultOptions={authorOptions}
                    placeholder={__("Search for authors...", "essential-blocks")}
                    loadOptions={loadAuthors}
                    onChange={(selected) => updateQueryData('author', selected, true)}
                    isMulti={true}
                    onFocus={loadAuthors}
                />
            </BaseControl>

            <Spacer marginY={3} />

            {/* Taxonomy Filters Section */}
            {/* Only shows taxonomies that are specifically associated with the selected post type */}
            {/* For example: Posts show Categories & Tags, Movies show Genres, Products show Product Categories */}



            {/* Loading indicator for taxonomies */}
            {isTaxonomyLoading && (
                <div className="eb-query-builder-loading">
                    {__("Loading taxonomies...", "essential-blocks")}
                </div>
            )}

            {/* No taxonomies message */}
            {!isTaxonomyLoading && Object.keys(taxonomyData).length === 0 && (
                <div className="eb-no-taxonomies-message" style={{
                    padding: '12px',
                    backgroundColor: '#f8f9fa',
                    border: '1px solid #e9ecef',
                    borderRadius: '4px',
                    margin: '8px 0'
                }}>
                    <p style={{
                        fontStyle: 'italic',
                        color: '#6c757d',
                        margin: '0 0 8px 0',
                        fontSize: '14px'
                    }}>
                        {__(`No taxonomies registered for "${currentPostTypeData?.label || postType}" post type.`, "essential-blocks")}
                    </p>
                    <p style={{
                        fontSize: '12px',
                        color: '#868e96',
                        margin: '0'
                    }}>
                        {__('Taxonomies like categories, tags, or custom taxonomies need to be registered for this post type to appear here.', "essential-blocks")}
                    </p>
                </div>
            )}

            {/* Dynamic Taxonomy Filters - Optimized for Performance */}
            {Object.keys(taxonomyData).length > 0 && Object.entries(taxonomyData).map(([taxonomySlug, taxonomy]) => (
                <TaxonomyFilterItem
                    key={taxonomySlug}
                    taxonomySlug={taxonomySlug}
                    taxonomy={taxonomy}
                    taxonomySelectTypes={taxonomySelectTypes}
                    taxonomyFilters={taxonomyFilters}
                    onSelectTypeChange={handleTaxonomySelectTypeChange}
                    onFilterChange={handleTaxonomyFilterChange}
                />
            ))}

            {/* Include/Exclude Specific Posts */}
            <BaseControl
                label={__("Include Specific Posts", "essential-blocks")}
                help={__("Select specific posts to include in the query.", "essential-blocks")}
            >
                <AsyncSelect
                    cacheOptions
                    value={(() => {
                        if (!includeIds) return [];
                        if (Array.isArray(includeIds)) return includeIds;
                        try {
                            const parsed = JSON.parse(includeIds);
                            return Array.isArray(parsed) ? parsed : [];
                        } catch (e) {
                            // Fallback for legacy comma-separated IDs
                            return includeIds
                                .split(',')
                                .map((id) => id.trim())
                                .filter(Boolean)
                                .map((id) => {
                                    const match = postOptions.find((p) => String(p.value) === id);
                                    return { value: parseInt(id, 10), label: match ? match.label : `Post ${id}` };
                                });
                        }
                    })()}
                    defaultOptions={postOptions}
                    placeholder={__("Search for posts to include...", "essential-blocks")}
                    loadOptions={loadPosts}
                    getOptionLabel={(opt) => (typeof opt?.label === 'string' ? opt.label : (opt?.label?.rendered ?? String(opt?.label ?? '')))}
                    getOptionValue={(opt) => String(opt?.value ?? '')}
                    onChange={(selected) => {
                        const jsonStr = JSON.stringify(selected || []);
                        setAttributes({
                            queryData: { ...queryData, include: jsonStr },
                            includeIds: jsonStr,
                        });
                    }}
                    isMulti={true}
                />
            </BaseControl>

            <Spacer marginY={3} />

            <BaseControl
                label={__("Exclude Specific Posts", "essential-blocks")}
                help={__("Select specific posts to exclude from the query.", "essential-blocks")}
            >
                <AsyncSelect
                    cacheOptions
                    value={(() => {
                        if (!excludeIds) return [];
                        if (Array.isArray(excludeIds)) return excludeIds;
                        try {
                            const parsed = JSON.parse(excludeIds);
                            return Array.isArray(parsed) ? parsed : [];
                        } catch (e) {
                            // Fallback for legacy comma-separated IDs
                            return excludeIds
                                .split(',')
                                .map((id) => id.trim())
                                .filter(Boolean)
                                .map((id) => {
                                    const match = postOptions.find((p) => String(p.value) === id);
                                    return { value: parseInt(id, 10), label: match ? match.label : `Post ${id}` };
                                });
                        }
                    })()}
                    defaultOptions={postOptions}
                    placeholder={__("Search for posts to exclude...", "essential-blocks")}
                    loadOptions={loadPosts}
                    getOptionLabel={(opt) => (typeof opt?.label === 'string' ? opt.label : (opt?.label?.rendered ?? String(opt?.label ?? '')))}
                    getOptionValue={(opt) => String(opt?.value ?? '')}
                    onChange={(selected) => {
                        const jsonStr = JSON.stringify(selected || []);
                        setAttributes({
                            queryData: { ...queryData, exclude: jsonStr },
                            excludeIds: jsonStr,
                        });
                    }}
                    isMulti={true}
                />
            </BaseControl>

            {/* Advanced Options */}
            {showAdvancedOptions && (
                <>
                    <Spacer marginY={3} />

                    <PanelBody
                        title={__("Advanced Options", "essential-blocks")}
                        initialOpen={false}
                        className="eb-query-advanced-options"
                    >
                        <ToggleControl
                            label={__("Exclude Current Post", "essential-blocks")}
                            checked={excludeCurrentPost}
                            onChange={(value) => updateQueryData('exclude_current', value)}
                            help={__("Exclude the current post from the query results. Note: In the editor, the current post ID is not available, so this only takes effect in Preview and on the front end.", "essential-blocks")}
                        />

                        <ToggleControl
                            label={__("Ignore Sticky Posts", "essential-blocks")}
                            checked={ignoreStickyPosts}
                            onChange={(value) => updateQueryData('ignore_sticky_posts', value)}
                            help={__("Ignore sticky posts and treat them as regular posts.", "essential-blocks")}
                        />

                        <ToggleControl
                            label={__("Exclude Password Protected", "essential-blocks")}
                            checked={excludePasswordProtected}
                            onChange={(value) => updateQueryData('exclude_password_protected', value)}
                            help={__("Exclude password protected posts from the query.", "essential-blocks")}
                        />
                    </PanelBody>
                </>
            )}

            {/* Meta Query Builder */}
            {showMetaQuery && (
                <MetaQueryBuilder
                    metaQuery={metaQuery}
                    onChange={(newMetaQuery) => setAttributes({ metaQuery: newMetaQuery })}
                    initialOpen={false}
                />
            )}

            {/* Date Query Builder */}
            {showDateQuery && (
                <DateQueryBuilder
                    dateQuery={dateQuery}
                    onChange={(newDateQuery) => setAttributes({ dateQuery: newDateQuery })}
                    initialOpen={false}
                />
            )}
        </PanelBody>
    );
}

export default memo(AdvancedQueryControl);
