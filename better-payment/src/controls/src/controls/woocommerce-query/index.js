/**
 * External Dependencies
 */
import { __ } from "@wordpress/i18n";
import { PanelBody, PanelRow, TextControl, SelectControl } from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";
import Select2 from "react-select";
import AsyncSelect from "react-select/async";

/**
 * Internal Dependencies
 */
import { ORDER_BY, ORDER, INCLUDE_EXCLUDE } from "./constants";
import { getProducts, getProductBySearch, getProductCategory, getProductTag } from "./apiData";
import ProSelectControl from "../pro-select-control"
import { ButtonGroupControl } from "../button-group-control";

export default function WoocommerceQuery(props) {
    const { queryData, setQueryResults, setAttributes, isEdit } = props;
    const [query, setQuery] = useState({});
    const [didMount, setDidMount] = useState(false);
    const [categoryOptions, setCategoryOptions] = useState([]);
    const [tagOptions, setTagOptions] = useState([]);
    const [productDataOptions, setProductDataOptions] = useState([]);
    const [excludeProducts, setExcludeProducts] = useState([]);
    const [includeProductsQuery, setIncludeProductsQuery] = useState([]);
    const [excludeProductsQuery, setExcludeProductsQuery] = useState([]);
    const [disableEdit, setDisableEdit] = useState(false);
    const [productCategoryType, setProductCategoryType] = useState('include');
    const [productTagType, setProductTagType] = useState('include');

    useEffect(() => {
        setDidMount(true);

        //If Exixsting query data exists in Attributes, set existing data to current query data
        if (
            typeof queryData != "undefined" &&
            Object.keys(queryData).length > 0
        ) {
            setQuery({
                ...queryData,
                query_type: "custom_query",
                source: "products",
                rest_base: "products",
                rest_namespace: "wc/v3",
            });
        } else {
            setQuery({
                source: "products",
                rest_base: "products",
                rest_namespace: "wc/v3",
                query_type: "custom_query",
                product: JSON.stringify([
                    {
                        label: __("Current Product", "essential-blocks"),
                        value: "current_product",
                    },
                ]),
                exclude_ids: [],
                category: "",
                tag: "",
                excludeCategory: "",
                excludeTag: "",
                per_page: "4",
                offset: "0",
                orderby: JSON.stringify([
                    {
                        label: __("Date", "essential-blocks"),
                        value: "date",
                    },
                ]),
                order: JSON.stringify([
                    {
                        label: __("Descending", "essential-blocks"),
                        value: "desc",
                    },
                ]),
            });
        }

        return () => {
            setDidMount(false);
        };
    }, []);

    useEffect(() => {
        //Get Post data on query change and set to Attributes
        if (didMount) {
            setDisableEdit(true);
            getProducts(query).then((posts) => {
                setAttributes({ queryData: query });
                setQueryResults(posts);
                setDisableEdit(false);
            });

            // If Component use in editor, don't run further
            if (isEdit) {
                return;
            }
        }
    }, [query]);

    const updateQuery = (selected, property, json, makeArray) => {
        const updateQuery = { ...query };
        updateQuery[property] =
            json && makeArray
                ? JSON.stringify([selected])
                : json
                    ? JSON.stringify(selected)
                    : selected;
        setQuery(updateQuery);
    };

    const updateTaxonomyQuery = (selected, property, secondProperty, json, makeArray) => {
        const updateQuery = { ...query };
        updateQuery[secondProperty] = "";
        updateQuery[property] =
            json && makeArray
                ? JSON.stringify([selected])
                : json
                    ? JSON.stringify(selected)
                    : selected;
        setQuery(updateQuery);
    }

    const updateIntegerQuery = (number, property) => {
        const updateQuery = { ...query };
        updateQuery[property] = number < 0 ? 0 : number;
        setQuery(updateQuery);
    };

    //Get Product Categories data and Set Product Categories Options Data
    const loadCategories = (value) => {
        if (typeof value === 'string' && value.length < 3) {
            return []
        }

        return getProductCategory(value).then((categoryData) => {
            let categoryDataForOptions = [];
            categoryData.length > 0 &&
                categoryData.forEach((item) => {
                    let filterCategoryData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "name") {
                            filterCategoryData.label = item[key];
                        }
                        if (key === "id") {
                            filterCategoryData.value = item[key];
                        }
                    });
                    categoryDataForOptions.push(filterCategoryData);
                });

            if (value.type === 'focus') {
                setCategoryOptions(categoryDataForOptions);
            }
            return categoryDataForOptions;
        });
    }

    const loadTags = (value) => {
        if (typeof value === 'string' && value.length < 3) {
            return []
        }

        return getProductTag(value).then((tagData) => {
            let tagDataForOptions = [];
            tagData.length > 0 &&
                tagData.forEach((item) => {
                    let filterTagData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "name") {
                            filterTagData.label = item[key];
                        }
                        if (key === "id") {
                            filterTagData.value = item[key];
                        }
                    });
                    tagDataForOptions.push(filterTagData);
                });

            if (value.type === 'focus') {
                setTagOptions(tagDataForOptions);
            }
            return tagDataForOptions;
        });
    }

    const loadProductBySearch = (value, keyState) => {

        if (typeof value === 'string' && value.length < 3) {
            return []
        }

        return getProductBySearch(value).then((productData) => {
            let productDataForOptions = [];
            productData.length > 0 &&
                productData.forEach((item) => {
                    let filterProductData = {};
                    Object.keys(item).forEach((key) => {
                        if (key === "name") {
                            filterProductData.label = item[key].replace(/<\/?[^>]+(>|$)/g, "");
                        }
                        if (key === "id") {
                            filterProductData.value = item[key];
                        }
                    });
                    productDataForOptions.push(filterProductData);
                });

            if (value.type === 'focus') {
                if ('product' === keyState) {
                    let newObject = {
                        "value": "current",
                        "label": "Current Product"
                    };

                    productDataForOptions.unshift(newObject);
                    setProductDataOptions(productDataForOptions);
                } else if ('exclude_products' === keyState) {
                    setExcludeProducts(productDataForOptions)
                } else if ('exclude' === keyState) {
                    setExcludeProductsQuery(productDataForOptions)
                } else if ('include' === keyState) {
                    setIncludeProductsQuery(productDataForOptions)
                }

            }
            return productDataForOptions;
        });
    }

    if (isEdit) {
        return null;
    }

    return (
        <PanelBody title={props.title} initialOpen={props.initialOpen}>
            {typeof query != "undefined" && didMount && (
                <div className="eb-control-item-wrapper">
                    <SelectControl
                        label={__("Query Type", "essential-blocks")}
                        value={query?.query_type}
                        options={[
                            { label: "Custom Query", value: 'custom_query' },
                            { label: 'Related Products', value: 'related_products' }
                        ]}
                        onChange={(selected) => updateQuery(selected, "query_type", false, false)}
                    />
                    {query?.query_type === 'related_products' && (
                        <>
                            <PanelRow>
                                {__("Select Product", "essential-blocks")}
                            </PanelRow>
                            <AsyncSelect
                                value={
                                    typeof query.product != "undefined" &&
                                        query.product.length > 0
                                        ? JSON.parse(query.product)
                                        : ""
                                }
                                defaultOptions={productDataOptions}
                                placeholder={__("Search Product", "essential-blocks")}
                                loadOptions={(value) => loadProductBySearch(value, "product")}
                                onChange={(selected) => updateQuery(selected, "product", true)}
                                isDisabled={disableEdit}
                                onFocus={(value) => loadProductBySearch(value, "product")}
                            />
                            {typeof query.product != "undefined" &&
                                query.product.length > 0 && JSON.parse(query?.product).value === 'current' && (
                                    <PanelRow>
                                        <div className="eb-instruction">
                                            {__('When using "Current Product" the block relies on the product context of the live site, which may not be accurately reflected in the editor or templates.', 'essential-blocks')}
                                        </div>
                                    </PanelRow>
                                )}

                            <div className="eb-control-item-wrapper">
                                <PanelRow>
                                    {__("Exclude Products", "essential-blocks")}
                                </PanelRow>
                                <AsyncSelect
                                    cacheOptions
                                    value={
                                        typeof query.exclude_products != "undefined" &&
                                            query.exclude_products.length > 0
                                            ? JSON.parse(query.exclude_products)
                                            : ""
                                    }
                                    defaultOptions={excludeProducts}
                                    placeholder={__("Search Products", "essential-blocks")}
                                    loadOptions={(value) => loadProductBySearch(value, "exclude_products")}
                                    onChange={(selected) => updateQuery(selected, "exclude_products", true)}
                                    isMulti="true"
                                    isDisabled={disableEdit}
                                    onFocus={(value) => loadProductBySearch(value, "exclude_products")}
                                />
                            </div>
                        </>
                    )}
                    {query?.query_type === "custom_query" && (
                        <>
                            <PanelRow>
                                {__("Order By", "essential-blocks")}
                            </PanelRow>
                            <ProSelectControl
                                name="select-order-by"
                                value={
                                    typeof query.orderby != "undefined" &&
                                        query.orderby.length > 0
                                        ? JSON.parse(query.orderby)
                                        : ""
                                }
                                onChange={(selected) =>
                                    updateQuery(selected, "orderby", true, true)
                                }
                                options={ORDER_BY}
                                isMulti={false}
                                selectType={"select2"}
                            />
                            <div className="eb-control-item-wrapper">
                                <PanelRow>{__("Order", "essential-blocks")}</PanelRow>
                                <Select2
                                    name="select-order"
                                    value={
                                        typeof query.order != "undefined" &&
                                            query.order.length > 0
                                            ? JSON.parse(query.order)
                                            : ""
                                    }
                                    onChange={(selected) =>
                                        updateQuery(selected, "order", true, true)
                                    }
                                    options={ORDER}
                                    isMulti={false}
                                />
                            </div>
                        </>
                    )}
                    <TextControl
                        label={__("Products Per Page", "essential-blocks")}
                        type={"number"}
                        value={query.per_page}
                        onChange={(selected) =>
                            updateIntegerQuery(selected, "per_page")
                        }
                    />
                    {query?.query_type === "custom_query" && (
                        <>
                            <TextControl
                                label={__("Offset", "essential-blocks")}
                                type={"number"}
                                value={query.offset}
                                onChange={(selected) =>
                                    updateIntegerQuery(selected, "offset")
                                }
                            />

                            <div className="eb-control-item-wrapper">
                                <PanelRow>
                                    {__("Include Products", "essential-blocks")}
                                </PanelRow>
                                <AsyncSelect
                                    cacheOptions
                                    value={
                                        typeof query.include != "undefined" &&
                                            query.include.length > 0
                                            ? JSON.parse(query.include)
                                            : ""
                                    }
                                    defaultOptions={includeProductsQuery}
                                    placeholder={__("Search Products", "essential-blocks")}
                                    loadOptions={(value) => loadProductBySearch(value, "include")}
                                    onChange={(selected) =>
                                        updateQuery(selected, "include", true)
                                    }
                                    isMulti="true"
                                    isDisabled={disableEdit}
                                    onFocus={(value) => loadProductBySearch(value, "include")}
                                />
                            </div>
                            <div className="eb-control-item-wrapper">
                                <PanelRow>
                                    {__("Exclude Products", "essential-blocks")}
                                </PanelRow>
                                <AsyncSelect
                                    cacheOptions
                                    value={
                                        typeof query.exclude != "undefined" &&
                                            query.exclude.length > 0
                                            ? JSON.parse(query.exclude)
                                            : ""
                                    }
                                    defaultOptions={excludeProductsQuery}
                                    placeholder={__("Search Products", "essential-blocks")}
                                    loadOptions={(value) => loadProductBySearch(value, "exclude")}
                                    onChange={(selected) =>
                                        updateQuery(selected, "exclude", true)
                                    }
                                    isMulti="true"
                                    isDisabled={disableEdit}
                                    onFocus={(value) => loadProductBySearch(value, "exclude")}
                                />
                            </div>

                            <ButtonGroupControl
                                label={__("Product Categories", "essential-blocks")}
                                attrName={'productCategoryType'}
                                options={INCLUDE_EXCLUDE}
                                currentValue={productCategoryType}
                                onChange={(value) => {
                                    setProductCategoryType(value);
                                }}
                            />
                            {'include' === productCategoryType && (
                                <>
                                    <div className="eb-control-item-wrapper">
                                        <PanelRow>
                                            {__("Include Categories", "essential-blocks")}
                                        </PanelRow>
                                        <AsyncSelect
                                            cacheOptions
                                            value={
                                                typeof query.category != "undefined" &&
                                                    query.category.length > 0
                                                    ? JSON.parse(query.category)
                                                    : ""
                                            }
                                            defaultOptions={categoryOptions}
                                            placeholder={__("Search Product Categories", "essential-blocks")}
                                            loadOptions={loadCategories}
                                            onChange={(selected) => {
                                                updateTaxonomyQuery(selected, "category", 'excludeCategory', true);
                                            }}
                                            isMulti="true"
                                            isDisabled={disableEdit}
                                            onFocus={loadCategories}
                                        />
                                    </div>
                                </>
                            )}
                            {'exclude' === productCategoryType && (
                                <>
                                    <div className="eb-control-item-wrapper">
                                        <PanelRow>
                                            {__("Exclude Categories", "essential-blocks")}
                                        </PanelRow>
                                        <AsyncSelect
                                            cacheOptions
                                            value={
                                                typeof query.excludeCategory != "undefined" &&
                                                    query.excludeCategory.length > 0
                                                    ? JSON.parse(query.excludeCategory)
                                                    : ""
                                            }
                                            defaultOptions={categoryOptions}
                                            placeholder={__("Search Product Categories", "essential-blocks")}
                                            loadOptions={loadCategories}
                                            onChange={(selected) => {
                                                updateTaxonomyQuery(selected, "excludeCategory", "category", true)
                                            }}
                                            isMulti="true"
                                            isDisabled={disableEdit}
                                            onFocus={loadCategories}
                                        />
                                    </div>
                                </>
                            )}
                            <ButtonGroupControl
                                label={__("Product Tags", "essential-blocks")}
                                attrName={'productTagType'}
                                options={INCLUDE_EXCLUDE}
                                currentValue={productTagType}
                                onChange={(value) =>
                                    setProductTagType(value)
                                }
                            />
                            {'include' === productTagType && (
                                <>
                                    <div className="eb-control-item-wrapper">
                                        <PanelRow>
                                            {__("Include Tags", "essential-blocks")}
                                        </PanelRow>
                                        <AsyncSelect
                                            cacheOptions
                                            value={
                                                typeof query.tag != "undefined" &&
                                                    query.tag.length > 0
                                                    ? JSON.parse(query.tag)
                                                    : ""
                                            }
                                            defaultOptions={tagOptions}
                                            placeholder={__("Search Product Tags", "essential-blocks")}
                                            loadOptions={loadTags}
                                            onChange={(selected) => updateTaxonomyQuery(selected, "tag", "excludeTag", true)}
                                            isMulti="true"
                                            isDisabled={disableEdit}
                                            onFocus={loadTags}
                                        />
                                    </div>
                                </>
                            )}
                            {'exclude' === productTagType && (
                                <>
                                    <div className="eb-control-item-wrapper">
                                        <PanelRow>
                                            {__("Exclude Tags", "essential-blocks")}
                                        </PanelRow>
                                        <AsyncSelect
                                            cacheOptions
                                            value={
                                                typeof query.excludeTag != "undefined" &&
                                                    query.excludeTag.length > 0
                                                    ? JSON.parse(query.excludeTag)
                                                    : ""
                                            }
                                            defaultOptions={tagOptions}
                                            placeholder={__("Search Product Tags", "essential-blocks")}
                                            loadOptions={loadTags}
                                            onChange={(selected) => updateTaxonomyQuery(selected, "excludeTag", "tag", true)}
                                            isMulti="true"
                                            isDisabled={disableEdit}
                                            onFocus={loadTags}
                                        />
                                    </div>
                                </>
                            )}
                        </>
                    )}
                </div>
            )}
        </PanelBody>
    );
}
