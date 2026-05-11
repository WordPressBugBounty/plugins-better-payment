/**
 * Block attributes schema for User Dashboard.
 *
 * @package Better_Payment
 */

import {
    generateBackgroundAttributes,
    generateDimensionsAttributes,
    generateBorderShadowAttributes,
    generateTypographyAttributes,
    generateResponsiveRangeAttributes,
} from "@better-payment/controls";
import {
    CONTAINER_BACKGROUND,
    CONTAINER_MARGIN,
    CONTAINER_PADDING,
    CONTAINER_BORDER_RADIUS,
    SIDEBAR_BG,
    SIDEBAR_MARGIN,
    SIDEBAR_PADDING,
    SIDEBAR_BORDER,
    SIDEBAR_AVATAR_SIZE,
    SIDEBAR_ICON_SIZE,
    HEADER_BG,
    HEADER_MARGIN,
    HEADER_PADDING,
    HEADER_BORDER,
    TABLE_BG,
    TABLE_MARGIN,
    TABLE_PADDING,
    TABLE_BORDER,
    TABLE_HEADER_BG,
    TABLE_HEADER_MARGIN,
    TABLE_HEADER_PADDING,
    TABLE_HEADER_BORDER,
    TABLE_BODY_BG,
    TABLE_BODY_MARGIN,
    TABLE_BODY_PADDING,
    TABLE_BODY_BORDER,
} from "./constants";
import {
    typoPrefix_sidebar_avatar,
    typoPrefix_sidebar_nav,
    typoPrefix_header_title,
    typoPrefix_table_header,
    typoPrefix_table_body,
} from "./typographyConstant";

/**
 * Define block attributes.
 */
const attributes = {
    // Block alignment
    align: {
        type: 'string',
        default: 'full',
    },

    // Block ID
    blockId: {
        type: 'string',
        default: '',
    },

    // Layout & Display
    dashboardLayout: {
        type: 'string',
        default: 'layout-1',
    },

    // Sidebar settings
    sidebarShow: {
        type: 'boolean',
        default: true,
    },

    avatarShow: {
        type: 'boolean',
        default: true,
    },

    usernameShow: {
        type: 'boolean',
        default: true,
    },

    // Tab visibility
    dashboardShow: {
        type: 'boolean',
        default: true,
    },

    transactionsShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionsShow: {
        type: 'boolean',
        default: true,
    },

    headerShow: {
        type: 'boolean',
        default: true,
    },

    // Tab labels
    dashboardLabel: {
        type: 'string',
        default: 'Dashboard',
    },

    transactionLabel: {
        type: 'string',
        default: 'Transactions',
    },

    subscriptionLabel: {
        type: 'string',
        default: 'Subscriptions',
    },

    refreshStatsLabel: {
        type: 'string',
        default: 'Refresh Stats',
    },

    noItemsLabel: {
        type: 'string',
        default: 'No records found!',
    },

    // Dashboard section visibility
    dashboardTransactionSummaryShow: {
        type: 'boolean',
        default: true,
    },

    dashboardAnalyticsReportShow: {
        type: 'boolean',
        default: true,
    },

    dashboardRecentTransactionsShow: {
        type: 'boolean',
        default: true,
    },

    dashboardRecurringSubscriptionShow: {
        type: 'boolean',
        default: true,
    },

    dashboardSplitSubscriptionShow: {
        type: 'boolean',
        default: true,
    },

    // Dashboard section labels
    dashboardTotalAmountLabel: {
        type: 'string',
        default: 'Total Amount',
    },

    dashboardCompletedAmountLabel: {
        type: 'string',
        default: 'Completed Amount',
    },

    dashboardIncompleteAmountLabel: {
        type: 'string',
        default: 'Incomplete Amount',
    },

    dashboardRefundedAmountLabel: {
        type: 'string',
        default: 'Refunded Amount',
    },

    dashboardViewAllLabel: {
        type: 'string',
        default: 'View All',
    },

    dashboardAnalyticsReportsLabel: {
        type: 'string',
        default: 'Analytics Reports',
    },

    dashboardRecentTransactionsLabel: {
        type: 'string',
        default: 'Recent Transactions',
    },

    dashboardRecurringSubscriptionsLabel: {
        type: 'string',
        default: 'Recurring Subscriptions',
    },

    dashboardSplitSubscriptionsLabel: {
        type: 'string',
        default: 'Split Subscriptions',
    },

    // Transaction table visibility
    transactionTableNameShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableEmailAddressShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableAmountShow: {
        type: 'boolean',
        default: true,
    },

    transactionTablePaymentTypeShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableTransactionIdShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableSourceShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableStatusShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableDateShow: {
        type: 'boolean',
        default: true,
    },

    transactionTableActionShow: {
        type: 'boolean',
        default: true,
    },

    // Transaction table labels
    transactionTableNameLabel: {
        type: 'string',
        default: 'Name',
    },

    transactionTableEmailAddressLabel: {
        type: 'string',
        default: 'Email Address',
    },

    transactionTableAmountLabel: {
        type: 'string',
        default: 'Amount',
    },

    transactionTablePaymentTypeLabel: {
        type: 'string',
        default: 'Payment Type',
    },

    transactionTableTransactionIdLabel: {
        type: 'string',
        default: 'Transaction ID',
    },

    transactionTableSourceLabel: {
        type: 'string',
        default: 'Source',
    },

    transactionTableStatusLabel: {
        type: 'string',
        default: 'Status',
    },

    transactionTableDateLabel: {
        type: 'string',
        default: 'Date',
    },

    transactionTableActionLabel: {
        type: 'string',
        default: 'Action',
    },

    // Subscription table column visibility (pro feature)
    subscriptionTableSubscriptionIdShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTablePlanIdShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTableStatusShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTableAmountShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTableCreatedDateShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTableCurrentPeriodShow: {
        type: 'boolean',
        default: true,
    },

    subscriptionTableActionShow: {
        type: 'boolean',
        default: true,
    },

    // Subscription table column labels (pro feature)
    subscriptionTableSubscriptionIdLabel: {
        type: 'string',
        default: 'Subscription ID',
    },

    subscriptionTablePlanIdLabel: {
        type: 'string',
        default: 'Product Name',
    },

    subscriptionTableStatusLabel: {
        type: 'string',
        default: 'Status',
    },

    subscriptionTableAmountLabel: {
        type: 'string',
        default: 'Amount',
    },

    subscriptionTableCreatedDateLabel: {
        type: 'string',
        default: 'Payment Date',
    },

    subscriptionTableCurrentPeriodLabel: {
        type: 'string',
        default: 'Renewal Date',
    },

    subscriptionTableActionLabel: {
        type: 'string',
        default: 'Action',
    },

    // Subscription table status/action labels
    subscriptionTableStatusActiveLabel: {
        type: 'string',
        default: 'Active',
    },

    subscriptionTableStatusInactiveLabel: {
        type: 'string',
        default: 'Inactive',
    },

    subscriptionTableActionCancelLabel: {
        type: 'string',
        default: 'Cancel',
    },

    // Block metadata for dynamic CSS generation
    blockMeta: {
        type: 'object',
    },

    // Container style attributes
    ...generateBackgroundAttributes(CONTAINER_BACKGROUND, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(CONTAINER_MARGIN),
    ...generateDimensionsAttributes(CONTAINER_PADDING),
    ...generateBorderShadowAttributes(CONTAINER_BORDER_RADIUS),

    // Sidebar color attributes (plain strings — not background control)
    sidebarAvatarTextColor:      { type: 'string', default: '' },
    sidebarAvatarHoverTextColor: { type: 'string', default: '' },
    sidebarNavTextColor:         { type: 'string', default: '' },
    sidebarNavHoverTextColor:    { type: 'string', default: '' },
    sidebarIconColor:            { type: 'string', default: '' },

    // Sidebar style attributes
    ...generateBackgroundAttributes(SIDEBAR_BG, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(SIDEBAR_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_PADDING),
    ...generateBorderShadowAttributes(SIDEBAR_BORDER),
    ...generateResponsiveRangeAttributes(SIDEBAR_AVATAR_SIZE, { defaultRange: '' }),
    ...generateResponsiveRangeAttributes(SIDEBAR_ICON_SIZE,   { defaultRange: '' }),
    ...generateTypographyAttributes(typoPrefix_sidebar_avatar),
    ...generateTypographyAttributes(typoPrefix_sidebar_nav),

    // Header color attributes
    headerTextColor:      { type: 'string', default: '' },
    headerHoverTextColor: { type: 'string', default: '' },

    // Header style attributes
    ...generateBackgroundAttributes(HEADER_BG, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(HEADER_MARGIN),
    ...generateDimensionsAttributes(HEADER_PADDING),
    ...generateBorderShadowAttributes(HEADER_BORDER),
    ...generateTypographyAttributes(typoPrefix_header_title),

    // Header button colors
    activeButtonTextColor:       { type: 'string', default: '' },
    activeButtonBgColor:         { type: 'string', default: '' },
    activeButtonHoverTextColor:  { type: 'string', default: '' },
    activeButtonHoverBgColor:    { type: 'string', default: '' },
    inactiveButtonTextColor:     { type: 'string', default: '' },
    inactiveButtonBgColor:       { type: 'string', default: '' },
    inactiveButtonHoverTextColor: { type: 'string', default: '' },
    inactiveButtonHoverBgColor:  { type: 'string', default: '' },
    cancelButtonTextColor:       { type: 'string', default: '' },
    cancelButtonBgColor:         { type: 'string', default: '' },
    cancelButtonHoverTextColor:  { type: 'string', default: '' },
    cancelButtonHoverBgColor:    { type: 'string', default: '' },

    // Table color attributes
    tableHeaderTextColor:      { type: 'string', default: '' },
    tableHeaderHoverTextColor: { type: 'string', default: '' },
    tableBodyTextColor:        { type: 'string', default: '' },
    tableBodyHoverTextColor:   { type: 'string', default: '' },

    // Table style attributes
    ...generateBackgroundAttributes(TABLE_BG, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(TABLE_MARGIN),
    ...generateDimensionsAttributes(TABLE_PADDING),
    ...generateBorderShadowAttributes(TABLE_BORDER),

    ...generateBackgroundAttributes(TABLE_HEADER_BG, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(TABLE_HEADER_MARGIN),
    ...generateDimensionsAttributes(TABLE_HEADER_PADDING),
    ...generateBorderShadowAttributes(TABLE_HEADER_BORDER),
    ...generateTypographyAttributes(typoPrefix_table_header),

    ...generateBackgroundAttributes(TABLE_BODY_BG, { defaultFillColor: '' }),
    ...generateDimensionsAttributes(TABLE_BODY_MARGIN),
    ...generateDimensionsAttributes(TABLE_BODY_PADDING),
    ...generateBorderShadowAttributes(TABLE_BODY_BORDER),
    ...generateTypographyAttributes(typoPrefix_table_body),
};

export default attributes;
