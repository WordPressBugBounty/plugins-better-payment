/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    PanelRow,
    SelectControl,
    TextControl,
    ToggleControl
} from "@wordpress/components";
import { useEffect, useState, useRef, memo, useMemo } from "@wordpress/element";
import { select, useSelect, withSelect } from "@wordpress/data";
import { applyFilters } from "@wordpress/hooks";

/**
 * External Dependencies
 */
import AsyncSelect from "react-select/async";

/**
 * Internal Dependencies
 */
import { ORDER_BY, ORDER, INCLUDE_EXCLUDE } from "./constants";
import {
    getPosts,
    getAuthor,
    getLatest5Posts,
    getPostsBySearchString,
    getTaxonomiesByPostType,
    getTaxonomyCategories,
} from "./apiData";
import ProSelectControl from "../pro-select-control"
import { ButtonGroupControl } from "../button-group-control";

function Inspector(props) {
    const { attributes, setAttributes, setQueryResults, initialOpen, isEdit } = props;
    const { queryData, postTerms } = attributes;

    const didMount = useRef(false)
    const [authorOptions, setAuthorOptions] = useState([]); //Author Data for Select2 Format
    const [postOptions, setPostOptions] = useState([]); //Include/Exclude post Data for Select2 Format
    const [query, setQuery] = useState({});
    const [disableEdit, setDisableEdit] = useState(false);
    const [postTypes, setPostTypes] = useState({});
    const [taxonomyValues, setTaxonomyValues] = useState({});
    const [taxonomySelectType, setTaxonomySelectType] = useState({});

    const filterablePostTypes = useSelect((select) => {
        const postTypes = select("core").getPostTypes({ per_page: -1 });

        const defaultValue = [{ label: "Post", value: "posts" }];

        if (!postTypes || postTypes.length === 0 || postTypes === null) {
            return defaultValue;
        }

        if (typeof postTypes === "object" || postTypes.length > 0) {
            const filteredValues = [];
            postTypes.map((item, index) => {
                if (item.slug === "attachment") {
                    return;
                }
                if (item.viewable) {
                    filteredValues.push({
                        label: item.name,
                        value: parseInt(filteredValues.length),
                        post_type: item.slug,
                        rest_base: item.rest_base,
                        rest_namespace: item.rest_namespace,
                    });
                }
            });
            return filteredValues;
        }

        return postTypes;
    }, []);

    useEffect(() => {
        setPostTypes(filterablePostTypes);
    }, [filterablePostTypes]);

    useEffect(() => {
        //If Exixsting query data exists in Attributes, set existing data to current query data
        if (typeof queryData != "undefined" && Object.keys(queryData).length > 0) {
            const queryDataObj = { ...queryData };
            if (queryDataObj.source === "posts") {
                queryDataObj.source = "post";
                queryDataObj.rest_base = "posts";
                queryDataObj.rest_namespace = "wp/v2";
            }
            if (queryDataObj.categories) {
                queryDataObj.taxonomies = {
                    ...queryDataObj.taxonomies,
                    category: {
                        name: "Categories",
                        rest_base: "categories",
                        rest_namespace: "wp/v2",
                        value: queryDataObj.categories,
                    },
                };
            }
            if (queryDataObj.tags) {
                queryDataObj.taxonomies = {
                    ...queryDataObj.taxonomies,
                    post_tag: {
                        name: "Tags",
                        rest_base: "tags",
                        rest_namespace: "wp/v2",
                        value: queryDataObj.tags,
                    },
                };
            }
            if (queryDataObj.exclude_password_protected === undefined) {
                queryDataObj.exclude_password_protected = false
            }
            delete queryDataObj.categories;
            delete queryDataObj.tags;
            setQuery({ ...queryDataObj });
        } else {
            setQuery({
                source: "post",
                sourceIndex: 0,
                rest_base: "posts",
                rest_namespace: "wp/v2",
                author: "",
                taxonomies: {},
                per_page: "6",
                offset: "0",
                orderby: "date",
                order: "desc",
                include: "",
                exclude: "",
                exclude_current: false,
                ignore_sticky_posts: false,
                exclude_password_protected: false,
            });
        }

        didMount.current = true

        return () => {
            didMount.current = false
        };
    }, []);

    useEffect(() => {
        if (didMount.current && typeof queryData === 'object' && Object.keys(queryData).length > 0 && JSON.stringify(queryData) !== JSON.stringify(query)) {
            setQuery(queryData)
        }
    }, [queryData])

    useEffect(() => {
        //Get Post data on query change and set to Attributes
        if (didMount.current && typeof query === 'object' && Object.keys(query).length > 0) {
            setDisableEdit(true);
            getPosts(query).then((posts) => {
                setAttributes({ queryData: query });
                setQueryResults(posts);
                setDisableEdit(false);
            });
        }
    }, [query]);

    useEffect(() => {
        if (isEdit) { //If Component use in editor, don't run further
            return;
        }

        //Get Taxonomies
        getTaxonomiesByPostType(query).then((response) => {
            const taxonomyValObj = {};
            let termsAttr = {};
            response.length > 0 && response.map((item, index) => {
                getTaxonomyCategories(item.rest_base).then((catResponse) => {
                    taxonomyValObj[item.slug] = {
                        name: item.name,
                        rest_base: item.rest_base,
                        rest_namespace: item.rest_namespace,
                        slug: item.slug,
                        values: catResponse,
                    };
                });

                termsAttr[item.slug] = {
                    label: item.name,
                    value: item.slug,
                };
            });

            //if "postTerms" attribute exists, set value on change post type
            if (postTerms) {
                setAttributes({
                    postTerms: { ...termsAttr },
                });
            }
            setTaxonomyValues(taxonomyValObj);
        });

        //Get Posts data and Set Tags Options Data
        getLatest5Posts(query).then((postData) => {
            let postDataForOptions = [];
            postData.length > 0 &&
                postData.forEach((item) => {
                    let filterPostData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "title") {
                            filterPostData.label = item[key].rendered;
                        }
                        if (key === "id") {
                            filterPostData.value = item[key];
                        }
                    });
                    postDataForOptions.push(filterPostData);
                });
            setPostOptions(postDataForOptions);
        });
    }, [query?.source]);

    //Function for update query data on Source Select
    const setSource = (value) => {
        let updatedQueryData = { ...query };
        updatedQueryData.source = filterablePostTypes[value].post_type
            ? filterablePostTypes[value].post_type
            : "post";
        updatedQueryData.rest_base = filterablePostTypes[value].rest_base
            ? filterablePostTypes[value].rest_base
            : "posts";
        updatedQueryData.rest_namespace = filterablePostTypes[value]
            .rest_namespace
            ? filterablePostTypes[value].rest_namespace
            : "wp/v2";
        updatedQueryData.sourceIndex = filterablePostTypes[value].value
            ? filterablePostTypes[value].value
            : 0;
        updatedQueryData.taxonomies = {};
        setQuery(updatedQueryData);
    };

    //Function for update query data on Taxonoy Select
    const setTaxonomyValuesInQuery = (taxonomyName, values) => {
        let updatedQueryData = { ...query };
        updatedQueryData.taxonomies = {
            ...updatedQueryData.taxonomies,
            [taxonomyName]: {
                ...taxonomyValues[taxonomyName],
                value: JSON.stringify(values),
            },
        };
        delete updatedQueryData.taxonomies[taxonomyName].values; //Remove all values
        setQuery(updatedQueryData);
    };

    //Function for update query data on exclude Taxonoy Select
    const setExcludeTaxonomyValuesInQuery = (taxonomyName, values) => {
        let updatedQueryData = { ...query };
        updatedQueryData.taxonomies = {
            ...updatedQueryData?.taxonomies,
            [taxonomyName]: {
                ...taxonomyValues[taxonomyName],
                exclude: JSON.stringify(values),
            },
        };
        delete updatedQueryData.taxonomies[taxonomyName].values; //Remove all values
        setQuery(updatedQueryData);
    }

    /**
     * Function for set Query data by given key and value
     * @param {string} key
     * @param {*} value
     * @param {boolean} isJson if value is object, set the value as json stringify
     */
    const setQueryData = (key, value, isJson = true) => {
        let updatedQueryData = { ...query };
        updatedQueryData[key] = isJson ? JSON.stringify(value) : value;
        setQuery(updatedQueryData);
    }

    //Function for Load User by Search
    const loadTaxonomyValues = (restbase, value) => {
        if (value.length < 2) {
            return []
        }
        return getTaxonomyCategories(restbase, value).then((userData) => {
            return userData
        });
    };

    //Function for Load User by Search
    const LoadAuthors = (value) => {
        if (typeof value === 'string' && value.length < 3) {
            return []
        }
        return getAuthor(value).then((userData) => {
            let userDataForOptions = [];
            userData.length > 0 &&
                userData.forEach((item) => {
                    let filteruserData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "name") {
                            filteruserData.label = item[key];
                        }
                        if (key === "id") {
                            filteruserData.value = item[key];
                        }
                    });
                    userDataForOptions.push(filteruserData);
                });

            if (value.type === 'focus') {
                setAuthorOptions(userDataForOptions);
            }
            return userDataForOptions
        });
    };

    //Function for Load Posts by Search
    const LoadIncludePosts = (value) => {
        return getPostsBySearchString(value, query).then((postData) => {
            let postDataForOptions = [];
            postData.length > 0 &&
                postData.forEach((item) => {
                    let filterPostData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "title") {
                            filterPostData.label = item[key];
                        }
                        if (key === "id") {
                            filterPostData.value = item[key];
                        }
                    });
                    postDataForOptions.push(filterPostData);
                });
            return postDataForOptions;
        });
    };

    const handleSelectTypeChange = (taxoSlug, value) => {
        setTaxonomySelectType(prevTypes => ({
            ...prevTypes,
            [taxoSlug]: value
        }));
    };

 

    if (isEdit) {
        return null
    }

    return (
        <PanelBody
            title={__("Query", "essential-blocks")}
            initialOpen={initialOpen}
        >
            {didMount.current && typeof query === "object" && Object.keys(query).length > 0 && (
                <>
                    <SelectControl
                        label={__("Source", "essential-blocks")}
                        value={query.sourceIndex}
                        options={postTypes}
                        onChange={(selected) => setSource(selected)}
                        disabled={disableEdit}
                    />

                    <div className="eb-control-item-wrapper">
                        <PanelRow>Author</PanelRow>
                        <AsyncSelect
                            cacheOptions
                            value={
                                query?.author.length > 0
                                    ? JSON.parse(query.author)
                                    : ""
                            }
                            defaultOptions={authorOptions}
                            placeholder={"Search Author"}
                            loadOptions={LoadAuthors}
                            onChange={(selected) => setQueryData('author', selected)}
                            isMulti="true"
                            isDisabled={disableEdit}
                            onFocus={LoadAuthors}
                        />
                    </div>
                    {Object.keys(taxonomyValues).length > 0 && Object.keys(taxonomyValues).map((taxonomies,index) => {
                        const taxoSlug = taxonomyValues[taxonomies].slug;
                        const taxoName = taxonomyValues[taxonomies].name;

                        const queryTaxonomyObj =
                        query.taxonomies && query.taxonomies[taxoSlug]
                            ? query.taxonomies[taxoSlug]
                            : {};
                        const queryTaxonomyValue = queryTaxonomyObj.value
                            ? JSON.parse(queryTaxonomyObj.value)
                            : "";
                        const excludeQueryTaxonomyValue = queryTaxonomyObj?.exclude
                            ? JSON.parse(queryTaxonomyObj?.exclude)
                            : "";
                        const currentTaxonomySelectType = taxonomySelectType[taxoSlug] || 'include';
                        return (
                            <>
                                <ButtonGroupControl
                                    label={taxoName}
                                    attrName={'taxonomySelectType'}
                                    options={INCLUDE_EXCLUDE}
                                    currentValue={currentTaxonomySelectType}
                                    onChange={(value) => {
                                        handleSelectTypeChange(taxoSlug, value)
                                    }}
                                />
                                {currentTaxonomySelectType === 'include' && (
                                    <div className="eb-control-item-wrapper" key={index}>
                                        <PanelRow>Include {taxoName}</PanelRow>
                                        <AsyncSelect
                                            cacheOptions
                                            name="select-categories"
                                            value={queryTaxonomyValue}
                                            defaultOptions={taxonomyValues[taxonomies].values}
                                            placeholder={`Search for ${taxoName}`}
                                            onChange={(selected) =>
                                                setTaxonomyValuesInQuery(
                                                    taxoSlug,
                                                    selected
                                                )
                                            }
                                            isMulti="true"
                                            isDisabled={disableEdit}
                                            loadOptions={(search) => loadTaxonomyValues(taxonomyValues[taxonomies].rest_base, search)}
                                        />
                                    </div>
                                    
                                )}
                                {currentTaxonomySelectType === 'exclude' && (
                                <div className="eb-control-item-wrapper">
                                    <PanelRow>Exclude {taxoName}</PanelRow>
                                    <AsyncSelect
                                        cacheOptions
                                        name="exclude-categories"
                                        value={excludeQueryTaxonomyValue}
                                        defaultOptions={taxonomyValues[taxonomies].values}
                                        placeholder={`Search for ${taxoName}`}
                                        onChange={(selected) =>
                                            setExcludeTaxonomyValuesInQuery(
                                                taxoSlug,
                                                selected
                                            )
                                        }
                                        isMulti="true"
                                        isDisabled={disableEdit}
                                        loadOptions={(search) => loadTaxonomyValues(taxonomyValues[taxonomies].rest_base, search)}
                                    />
                                </div>
                                )}
                            </>
                        )
                    })}
                    <div className="eb-control-item-wrapper">
                        <PanelRow>Include</PanelRow>
                        <AsyncSelect
                            cacheOptions
                            value={
                                query.include.length > 0
                                    ? JSON.parse(query.include)
                                    : ""
                            }
                            defaultOptions={postOptions}
                            placeholder={`Search for ${query.source ? query.source : "Posts"}`}
                            loadOptions={LoadIncludePosts}
                            onChange={(selected) => setQueryData('include', selected)}
                            isMulti="true"
                            isDisabled={disableEdit}
                        />
                    </div>

                    <div className="eb-control-item-wrapper">
                        <PanelRow> Exclude</PanelRow>
                        <AsyncSelect
                            cacheOptions
                            value={
                                query.exclude.length > 0
                                    ? JSON.parse(query.exclude)
                                    : ""
                            }
                            defaultOptions={postOptions}
                            placeholder={`Search for ${query.source ? query.source : "Posts"}`}
                            loadOptions={LoadIncludePosts}
                            onChange={(selected) => setQueryData('exclude', selected)}
                            isMulti="true"
                            isDisabled={disableEdit}
                        />
                    </div>

                    <TextControl
                        label="Posts Per Page"
                        type={"number"}
                        value={query.per_page}
                        onChange={(selected) => setQueryData('per_page', selected, false)}
                        disabled={disableEdit}
                    />

                    <TextControl
                        label="Offset"
                        type={"number"}
                        value={query.offset}
                        onChange={(selected) => setQueryData('offset', selected, false)}
                        disabled={disableEdit}
                    />

                    <ProSelectControl
                        label={__("Order By", "essential-blocks")}
                        value={query.orderby}
                        options={applyFilters("eb_custom_query_orderby_filter", ORDER_BY)}
                        onChange={(selected) => setQueryData('orderby', selected, false)}
                        disabled={disableEdit}
                    />

                    <SelectControl
                        label={__("Order", "essential-blocks")}
                        value={query.order}
                        options={ORDER}
                        onChange={(selected) => setQueryData('order', selected, false)}
                        disabled={disableEdit}
                    />
                    <ToggleControl
                        label={__("Exclude Current Post?")}
                        checked={query.exclude_current}
                        onChange={() => setQueryData('exclude_current', !query.exclude_current, false)}
                    />
                    <ToggleControl
                        label={__("Disable Sticky Post?")}
                        checked={query.ignore_sticky_posts}
                        onChange={() => setQueryData('ignore_sticky_posts', !query.ignore_sticky_posts, false)}
                    />
                    <ToggleControl
                        label={__("Exclude Password Protected Posts?")}
                        checked={query.exclude_password_protected}
                        onChange={() => setQueryData('exclude_password_protected', !query.exclude_password_protected, false)}
                    />
                </>
            )}
        </PanelBody>
    );
}

export default memo(Inspector)
