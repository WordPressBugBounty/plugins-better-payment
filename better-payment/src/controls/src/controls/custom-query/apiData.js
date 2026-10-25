import apiFetch from "@wordpress/api-fetch";
import { select } from "@wordpress/data";
const rootURL = EssentialBlocksLocalize?.rest_rootURL;
apiFetch.use(apiFetch.createRootURLMiddleware(rootURL));

import { ebJsonStringCheck } from "../../helpers";

// Cache for taxonomy data to improve performance
const taxonomyCache = new Map();
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

// Helper function to get cached data
const getCachedData = (key) => {
    const cached = taxonomyCache.get(key);
    if (cached && (Date.now() - cached.timestamp) < CACHE_DURATION) {
        return cached.data;
    }
    return null;
};

// Helper function to set cached data
const setCachedData = (key, data) => {
    taxonomyCache.set(key, {
        data: data,
        timestamp: Date.now()
    });
};

//Get Posts
export const getPosts = (query) => {
    const queryString = generateQueryString(query);
    const namespace = query.rest_namespace ? query.rest_namespace : "wp/v2";
    let postData = [];
    return apiFetch({
        path: `/${namespace}/${query.rest_base ? query.rest_base : "posts"
            }?${queryString}&_embed`,
    }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterPostData = {};
                    Object.keys(item).forEach((key) => {
                        filterPostData[key] = item[key];
                    });
                    postData = [...postData, filterPostData];
                });
            return postData;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Author Data
export const getAuthor = (searchString = false) => {
    let authorData = [];
    let queryString = "per_page=5";
    if (searchString && searchString.length > 2) {
        queryString = `search=${searchString}&per_page=10`
    }
    return apiFetch({ path: `/wp/v2/users?${queryString}` }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterauthorData = {};
                    let includesVal = ["id", "link", "name", "slug"];
                    Object.keys(item).forEach((key) => {
                        if (includesVal.includes(key)) {
                            filterauthorData[key] = item[key];
                        }
                    });
                    authorData = [...authorData, filterauthorData];
                });
            return authorData;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Taxonomies By Post Type - Optimized Version with Caching
export const getTaxonomiesByPostType = (query) => {
    const currentPostType = query.source ? query.source : "post";
    const cacheKey = `taxonomies_${currentPostType}`;

    // Check cache first
    const cachedData = getCachedData(cacheKey);
    if (cachedData) {
        return Promise.resolve(cachedData);
    }

    // Method 1: Try to get taxonomies directly for the post type
    return apiFetch({
        path: `/wp/v2/types/${currentPostType}?context=edit`,
    }).then(
        (postTypeData) => {

            // Extract taxonomies from post type data
            const postTypeTaxonomies = postTypeData.taxonomies || [];

            if (postTypeTaxonomies.length === 0) {
                return [];
            }

            // Get detailed info for each taxonomy
            const taxonomyPromises = postTypeTaxonomies.map(taxonomySlug => {
                return apiFetch({
                    path: `/wp/v2/taxonomies/${taxonomySlug}`,
                }).then(taxonomyData => {
                    return {
                        name: taxonomyData.name,
                        slug: taxonomyData.slug,
                        rest_base: taxonomyData.rest_base,
                        rest_namespace: taxonomyData.rest_namespace || "wp/v2",
                        types: taxonomyData.types || [currentPostType],
                    };
                }).catch(error => {
                    console.warn(`Failed to load taxonomy ${taxonomySlug}:`, error);
                    return null;
                });
            });

            return Promise.all(taxonomyPromises).then(results => {
                // Filter out failed requests
                const validTaxonomies = results.filter(taxonomy => taxonomy !== null);

                // Cache the successful result
                setCachedData(cacheKey, validTaxonomies);

                return validTaxonomies;
            });
        },
        (error) => {
            console.warn(`Failed to get post type data for ${currentPostType}, falling back to legacy method:`, error);

            // Fallback: Use the old method but with better filtering
            return getTaxonomiesByPostTypeFallback(query);
        }
    );
};

// Fallback method - more efficient than the old approach
const getTaxonomiesByPostTypeFallback = (query) => {
    const currentPostType = query.source ? query.source : "post";
    const cacheKey = `taxonomies_fallback_${currentPostType}`;

    // Check cache first
    const cachedData = getCachedData(cacheKey);
    if (cachedData) {
        return Promise.resolve(cachedData);
    }

    // Use the type parameter to filter server-side if possible
    return apiFetch({
        path: `/wp/v2/taxonomies?type=${currentPostType}&per_page=-1`,
    }).then(
        (result) => {

            let taxonomies = [];
            if (result != null && Object.keys(result).length > 0) {
                Object.keys(result).forEach((item) => {
                    const taxonomy = result[item];

                    // Only include if types array includes current post type
                    if (taxonomy.types &&
                        Array.isArray(taxonomy.types) &&
                        taxonomy.types.includes(currentPostType)) {

                        taxonomies.push({
                            name: taxonomy.name,
                            slug: taxonomy.slug,
                            rest_base: taxonomy.rest_base,
                            rest_namespace: taxonomy.rest_namespace || "wp/v2",
                            types: taxonomy.types,
                        });
                    }
                });
            }

            // Cache the successful result
            setCachedData(cacheKey, taxonomies);

            return taxonomies;
        },
        (error) => {
            console.error("Fallback method also failed:", error);
            return [];
        }
    );
};

//Get Categories By Post Taxonomies - Optimized with Caching
export const getTaxonomyCategories = (taxonomyName, searchString = false) => {
    let taxonomies = [];
    let queryString = "per_page=5";
    if (searchString && searchString.length >= 2) {
        queryString = `search=${searchString}&per_page=10`
    }

    // Create cache key
    const cacheKey = `taxonomy_terms_${taxonomyName}_${queryString}`;

    // Check cache first (only for non-search queries to avoid stale search results)
    if (!searchString) {
        const cachedData = getCachedData(cacheKey);
        if (cachedData) {
            return Promise.resolve(cachedData);
        }
    }

    return apiFetch({ path: `/wp/v2/${taxonomyName}?${queryString}` }).then(
        (result) => {
            result != null &&
                Object.keys(result).length > 0 &&
                Object.keys(result).forEach((item) => {
                    let filterTaxonomoies = {};
                    Object.keys(result[item]).forEach((key) => {
                        if (key === "name") {
                            filterTaxonomoies.label = result[item][key];
                        }
                        if (key === "id") {
                            filterTaxonomoies.value = result[item][key];
                        }
                    });
                    taxonomies.push(filterTaxonomoies);
                });

            // Cache the result (only for non-search queries)
            if (!searchString) {
                setCachedData(cacheKey, taxonomies);
            }

            return taxonomies;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Posts for Include or Exclude
export const getLatest5Posts = (query) => {
    const namespace = query.rest_namespace ? query.rest_namespace : "wp/v2";
    let data = [];
    return apiFetch({ path: `/${namespace}/${query.rest_base ? query.rest_base : "posts"}/?per_page=5` }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterData = {};
                    let includesVal = ["id", "title"];
                    Object.keys(item).forEach((key) => {
                        if (includesVal.includes(key)) {
                            filterData[key] = item[key];
                        }
                    });
                    data = [...data, filterData];
                });
            return data;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Posts for Include or Exclude
export const getPostsBySearchString = (search, query) => {
    let postsData = [];
    const namespace = query.rest_namespace ? query.rest_namespace : "wp/v2";
    return apiFetch({ path: `/${namespace}/search/?search=${search}&type=post&subtype=${query.source ? query.source : "post"}` }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterPostsData = {};
                    let includesVal = ["id", "title"];
                    Object.keys(item).forEach((key) => {
                        if (includesVal.includes(key)) {
                            filterPostsData[key] = item[key];
                        }
                    });
                    postsData = [...postsData, filterPostsData];
                });
            return postsData;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Total Number of Posts
export const getNumberOfPosts = (query) => {
    const queryString = generateQueryString(query);
    const namespace = query.rest_namespace ? query.rest_namespace : "wp/v2";
    return apiFetch({
        path: `/${namespace}/${query.rest_base ? query.rest_base : "posts"
            }?${queryString}`,
        parse: false,
    }).then(
        (result) => {
            return result.headers.get("X-WP-Total");
        },
        (error) => {
            console.log("error", error);
        }
    );
};

const generateQueryString = (query) => {
    let queryStringObj = {};
    Object.keys(query).forEach((key) => {
        const includeKeys = [
            "source",
            "sourceIndex",
            "author",
            "taxonomies",
            "per_page",
            "offset",
            "orderby",
            "order",
            "include",
            "exclude",
            "category",
            "tag",
            "exclude_current",
            "ignore_sticky_posts",
        ];
        if (includeKeys.includes(key)) {
            if (key === "taxonomies") {
                const taxonomies = query[key];
                if (
                    typeof taxonomies === "object" &&
                    Object.keys(taxonomies).length > 0
                ) {
                    Object.keys(taxonomies).map((item) => {
                        if (ebJsonStringCheck(taxonomies[item].value)) {
                            const valueObj = JSON.parse(taxonomies[item].value);
                            if (valueObj.length > 0) {
                                const valueArr = [];
                                typeof valueObj === "object" &&
                                    Object.keys(valueObj).map((val) => {
                                        valueArr.push(valueObj[val].value);
                                    });
                                queryStringObj[
                                    taxonomies[item].rest_base
                                ] = valueArr.join(",");
                            }
                        }
                        if( ebJsonStringCheck(taxonomies[item].exclude) ) {
                            const valueObj = JSON.parse(taxonomies[item].exclude);
                            if (valueObj.length > 0) {
                                const valueArr = [];
                                typeof valueObj === "object" &&
                                    Object.keys(valueObj).map((val) => {
                                        valueArr.push(valueObj[val].value);
                                    });
                                queryStringObj[taxonomies[item].rest_base + "_exclude"] = valueArr.join(",");
                            }
                        }
                    });
                }
            }
            // Handle different data types properly
            if (query[key] !== undefined && query[key] !== null && query[key] !== "") {
                // For numeric values (like per_page, offset, etc.)
                if (typeof query[key] === "number") {
                    queryStringObj[key] = query[key];
                }
                // For string values that might be JSON or plain strings
                else if (typeof query[key] === "string") {
                    if (ebJsonStringCheck(query[key])) {
                        let data = JSON.parse(query[key]);
                        let arg = [];
                        if (data.length > 0) {
                            data.forEach((eachData) => {
                                if (typeof eachData === "object") {
                                    arg.push(eachData.value);
                                }
                            });
                            queryStringObj[key] = arg.join(",");
                        }
                    } else {
                        // Plain string value
                        if (query[key] !== "0" && query[key].length > 0) {
                            queryStringObj[key] = query[key];
                        }
                    }
                }
                // For boolean values
                else if (typeof query[key] === "boolean") {
                    queryStringObj[key] = query[key];
                }
                // For arrays (handle directly)
                else if (Array.isArray(query[key]) && query[key].length > 0) {
                    queryStringObj[key] = query[key].join(",");
                }
            }
            if (key === "exclude_current" && query.exclude_current) {
                const currentPostId = select("core/editor")?.getCurrentPostId();
                if (typeof currentPostId === "number") {
                    if (queryStringObj.exclude) {
                        let update = queryStringObj.exclude.split(',')
                        if (update.indexOf(currentPostId.toString()) === -1) {
                            update.push(currentPostId.toString())
                        }
                        queryStringObj.exclude = update.join(',');
                    }
                    else {
                        queryStringObj.exclude = currentPostId.toString();
                    }
                }
            }

            if (key === "ignore_sticky_posts" && query.ignore_sticky_posts) {
                queryStringObj.sticky = !query.ignore_sticky_posts;
            }
        }
    });

    //Create Query String
    let queryString = Object.keys(queryStringObj)
        .map((key) => key + "=" + queryStringObj[key])
        .join("&");

    return queryString;
};


/**
 * Currently Unused Funcs
 */

//Get Categories
export const getCategory = () => {
    let categoryData = [];
    return apiFetch({ path: `/wp/v2/categories?per_page=-1` }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterCategoryData = {};
                    let includesVal = ["id", "link", "name", "slug", "parent"];
                    Object.keys(item).forEach((key) => {
                        if (includesVal.includes(key)) {
                            filterCategoryData[key] = item[key];
                        }
                    });
                    categoryData = [...categoryData, filterCategoryData];
                });
            return categoryData;
        },
        (error) => {
            console.log("error", error);
        }
    );
};

//Get Tags
export const getTag = () => {
    let tagsData = [];
    return apiFetch({ path: `/wp/v2/tags?per_page=-1` }).then(
        (result) => {
            result != null &&
                result.length > 0 &&
                result.forEach((item) => {
                    let filterTagsData = {};
                    let includesVal = ["id", "link", "name", "slug"];
                    Object.keys(item).forEach((key) => {
                        if (includesVal.includes(key)) {
                            filterTagsData[key] = item[key];
                        }
                    });
                    tagsData = [...tagsData, filterTagsData];
                });
            return tagsData;
        },
        (error) => {
            console.log("error", error);
        }
    );
};
