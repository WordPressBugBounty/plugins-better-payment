import { __ } from "@wordpress/i18n";

export const ORDER_BY = [
    { label: __("Date", "essential-blocks"), value: "date" },
    { label: __("Modified Date", "essential-blocks"), value: "modified" },
    { label: __("Title", "essential-blocks"), value: "title" },
    { label: __("ID", "essential-blocks"), value: "id" },
    { label: __("Parent", "essential-blocks"), value: "parent" },
    { label: __("Random Order", "essential-blocks"), value: "rand" },
    { label: __("Menu Order", "essential-blocks"), value: "menu_order" },
    { label: __("Comment Count", "essential-blocks"), value: "comment_count" },
];

export const ORDER = [
    { label: __("Desc", "essential-blocks"), value: "desc" },
    { label: __("Asc", "essential-blocks"), value: "asc" },
];