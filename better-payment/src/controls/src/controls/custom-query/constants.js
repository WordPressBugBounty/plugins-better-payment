/**
 * WordPress Core Dependencies
*/
import { __ } from "@wordpress/i18n";
import { Dashicon } from "@wordpress/components";

export const ORDER_BY = [
    { label: __("Date", "essential-blocks"), value: "date" },
    { label: __("Modified Date", "essential-blocks"), value: "modified" },
    { label: __("Title", "essential-blocks"), value: "title" },
    { label: __("ID", "essential-blocks"), value: "id" },
    { label: __("Parent", "essential-blocks"), value: "parent" },
    // { label: __("Relevant Posts (Pro)", "essential-blocks"), value: "relevance", isPro: true },
    { label: __("Random Order (Pro)", "essential-blocks"), value: "rand", isPro: true },
    { label: __("Menu Order (Pro)", "essential-blocks"), value: "menu_order", isPro: true },
    { label: __("Comment Count (Pro)", "essential-blocks"), value: "comment_count", isPro: true },
];

export const ORDER = [
    { label: __("Desc", "essential-blocks"), value: "desc" },
    { label: __("Asc", "essential-blocks"), value: "asc" },
];

export const MORE_POSTS_TYPE = [
    { label: __("Load More Button"), value: "1" },
    { label: __("Pagination"), value: "2" },
];

export const TEXT_ALIGN = [
    { label: __(<Dashicon icon={"editor-alignleft"} />), value: "left" },
    { label: __(<Dashicon icon={"editor-aligncenter"} />), value: "center" },
    { label: __(<Dashicon icon={"editor-alignright"} />), value: "right" }
];

export const INCLUDE_EXCLUDE = [
    { label: __("Include", "essential-blocks"), value: "include" },
    { label: __("Exclude", "essential-blocks"), value: "exclude" }
];
