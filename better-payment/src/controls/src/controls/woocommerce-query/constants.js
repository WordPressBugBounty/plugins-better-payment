/**
 * WordPress Core Dependencies
 */
import { __ } from "@wordpress/i18n";

// Order By
export const ORDER_BY = [
    { label: __("Product ID", "essential-blocks"), value: "id" },
    { label: __("Product Title", "essential-blocks"), value: "title" },
    { label: __("Product Slug", "essential-blocks"), value: "slug" },
    { label: __("Price", "essential-blocks"), value: "price" },
    { label: __("Popular", "essential-blocks"), value: "popularity" },
    { label: __("Rating", "essential-blocks"), value: "rating" },
    { label: __("Date", "essential-blocks"), value: "date" },
    { label: __("Random Order (Pro)", "essential-blocks"), value: "rand", isPro: true },
];

// Order
export const ORDER = [
    { label: __("Ascending", "essential-blocks"), value: "asc" },
    { label: __("Descending", "essential-blocks"), value: "desc" },
];

export const INCLUDE_EXCLUDE = [
    { label: __("Include", "essential-blocks"), value: "include" },
    { label: __("Exclude", "essential-blocks"), value: "exclude" }
];

