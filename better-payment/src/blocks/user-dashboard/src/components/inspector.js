/**
 * Inspector controls for User Dashboard block.
 *
 * @package Better_Payment
 */

import { __ } from '@wordpress/i18n';
import { SelectControl, ToggleControl, PanelBody, TextControl, PanelRow, __experimentalDivider as Divider } from '@wordpress/components';
import {
    CONTAINER_BACKGROUND, CONTAINER_BORDER_RADIUS, CONTAINER_MARGIN, CONTAINER_PADDING,
    SIDEBAR_BG, SIDEBAR_MARGIN, SIDEBAR_PADDING, SIDEBAR_BORDER, SIDEBAR_AVATAR_SIZE, SIDEBAR_ICON_SIZE,
    HEADER_BG, HEADER_MARGIN, HEADER_PADDING, HEADER_BORDER,
    TABLE_BG, TABLE_MARGIN, TABLE_PADDING, TABLE_BORDER,
    TABLE_HEADER_BG, TABLE_HEADER_MARGIN, TABLE_HEADER_PADDING, TABLE_HEADER_BORDER,
    TABLE_BODY_BG, TABLE_BODY_MARGIN, TABLE_BODY_PADDING, TABLE_BODY_BORDER,
    LAYOUTS,
} from './constants';
import {
    typoPrefix_sidebar_avatar,
    typoPrefix_sidebar_nav,
    typoPrefix_header_title,
    typoPrefix_table_header,
    typoPrefix_table_body,
} from './typographyConstant';
import {
  ColorControl,
  BackgroundControl,
  BorderShadowControl,
  AdvancedControls,
  InspectorPanel,
  SortControl,
  EBIconPicker,
  EBTextControl,
  ResponsiveDimensionsControl,
  TypographyDropdown,
  ResponsiveRangeController,
} from "@better-payment/controls";

const Inspector = ({ attributes, setAttributes }) => {
    const {
        dashboardLayout,
        sidebarShow,
        avatarShow,
        usernameShow,
        dashboardShow,
        transactionsShow,
        subscriptionsShow,
        headerShow,
        // Dashboard subsection
        dashboardTransactionSummaryShow,
        dashboardAnalyticsReportShow,
        dashboardRecentTransactionsShow,
        dashboardRecurringSubscriptionShow,
        dashboardSplitSubscriptionShow,
        // Transactions List subsection
        transactionTableNameShow,
        transactionTableEmailAddressShow,
        transactionTableAmountShow,
        transactionTablePaymentTypeShow,
        transactionTableTransactionIdShow,
        transactionTableSourceShow,
        transactionTableStatusShow,
        transactionTableDateShow,
        // Subscription table subsection (pro)
        subscriptionTableSubscriptionIdShow,
        subscriptionTablePlanIdShow,
        subscriptionTableStatusShow,
        subscriptionTableAmountShow,
        subscriptionTableCreatedDateShow,
        subscriptionTableCurrentPeriodShow,
        subscriptionTableActionShow,
        // Content labels - general
        dashboardLabel,
        transactionLabel,
        subscriptionLabel,
        refreshStatsLabel,
        noItemsLabel,
        // Dashboard content labels
        dashboardTotalAmountLabel,
        dashboardCompletedAmountLabel,
        dashboardIncompleteAmountLabel,
        dashboardRefundedAmountLabel,
        dashboardViewAllLabel,
        dashboardAnalyticsReportsLabel,
        dashboardRecentTransactionsLabel,
        dashboardRecurringSubscriptionsLabel,
        dashboardSplitSubscriptionsLabel,
        // Transaction table labels
        transactionTableNameLabel,
        transactionTableEmailAddressLabel,
        transactionTableAmountLabel,
        transactionTablePaymentTypeLabel,
        transactionTableTransactionIdLabel,
        transactionTableSourceLabel,
        transactionTableStatusLabel,
        transactionTableDateLabel,
        // Subscription table labels (pro)
        subscriptionTableSubscriptionIdLabel,
        subscriptionTablePlanIdLabel,
        subscriptionTableStatusLabel,
        subscriptionTableAmountLabel,
        subscriptionTableCreatedDateLabel,
        subscriptionTableCurrentPeriodLabel,
        subscriptionTableActionLabel,
        subscriptionTableStatusActiveLabel,
        subscriptionTableStatusInactiveLabel,
        subscriptionTableActionCancelLabel,
        // Sidebar colors
        sidebarAvatarTextColor,
        sidebarAvatarHoverTextColor,
        sidebarNavTextColor,
        sidebarNavHoverTextColor,
        sidebarIconColor,
        // Header colors
        headerTextColor,
        headerHoverTextColor,
        // Header button colors
        activeButtonTextColor,
        activeButtonBgColor,
        activeButtonHoverTextColor,
        activeButtonHoverBgColor,
        inactiveButtonTextColor,
        inactiveButtonBgColor,
        inactiveButtonHoverTextColor,
        inactiveButtonHoverBgColor,
        cancelButtonTextColor,
        cancelButtonBgColor,
        cancelButtonHoverTextColor,
        cancelButtonHoverBgColor,
        // Table colors
        tableHeaderTextColor,
        tableHeaderHoverTextColor,
        tableBodyTextColor,
        tableBodyHoverTextColor,
    } = attributes;

    const proEnabled = window.betterPaymentBlockData?.proEnabled === true;
    const layoutOptions = [
        { label: __("Layout 1", "better-payment"), value: "layout-1" },
    ];

    return (
        <>
        <InspectorPanel hideTabs={["advanced"]}>
            <InspectorPanel.General>
                <InspectorPanel.PanelBody title={__("Layout", "better-payment")} initialOpen={true}>
                    {/* Main layout options */}
                    <SelectControl
                        label={__("Layout", "better-payment")}
                        value={dashboardLayout}
                        options={layoutOptions}
                        onChange={(newValue) => setAttributes({ dashboardLayout: newValue })}
                    />

                    <ToggleControl
                        label={__("Sidebar", "better-payment")}
                        checked={sidebarShow}
                        onChange={(newVal) => setAttributes({ sidebarShow: newVal })}
                    />

                    {sidebarShow && (
                        <>
                            <ToggleControl
                                label={__("Avatar", "better-payment")}
                                checked={avatarShow}
                                onChange={(newVal) => setAttributes({ avatarShow: newVal })}
                            />

                            <ToggleControl
                                label={__("Username", "better-payment")}
                                checked={usernameShow}
                                onChange={(newVal) => setAttributes({ usernameShow: newVal })}
                            />
                        </>
                    )}

                    <ToggleControl
                        label={__("Dashboard", "better-payment")}
                        checked={dashboardShow}
                        onChange={(newVal) => setAttributes({ dashboardShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Transactions", "better-payment")}
                        checked={transactionsShow}
                        onChange={(newVal) => setAttributes({ transactionsShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Subscriptions", "better-payment")}
                        checked={subscriptionsShow}
                        onChange={(newVal) => setAttributes({ subscriptionsShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Header", "better-payment")}
                        checked={headerShow}
                        onChange={(newVal) => setAttributes({ headerShow: newVal })}
                    />
                </InspectorPanel.PanelBody>
                <InspectorPanel.PanelBody title={__("Dashboard", "better-payment")} initialOpen={false}>
                    <ToggleControl
                        label={__("Transaction Summary", "better-payment")}
                        checked={dashboardTransactionSummaryShow}
                        onChange={(newVal) => setAttributes({ dashboardTransactionSummaryShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Analytics Report", "better-payment")}
                        checked={dashboardAnalyticsReportShow}
                        onChange={(newVal) => setAttributes({ dashboardAnalyticsReportShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Recent Transactions", "better-payment")}
                        checked={dashboardRecentTransactionsShow}
                        onChange={(newVal) => setAttributes({ dashboardRecentTransactionsShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Recurring Subscription", "better-payment")}
                        checked={dashboardRecurringSubscriptionShow}
                        onChange={(newVal) => setAttributes({ dashboardRecurringSubscriptionShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Split Subscription", "better-payment")}
                        checked={dashboardSplitSubscriptionShow}
                        onChange={(newVal) => setAttributes({ dashboardSplitSubscriptionShow: newVal })}
                    />
                </InspectorPanel.PanelBody>
                <InspectorPanel.PanelBody title={__("Transactions", "better-payment")} initialOpen={false}>
                    <ToggleControl
                        label={__("Name", "better-payment")}
                        checked={transactionTableNameShow}
                        onChange={(newVal) => setAttributes({ transactionTableNameShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Email Address", "better-payment")}
                        checked={transactionTableEmailAddressShow}
                        onChange={(newVal) => setAttributes({ transactionTableEmailAddressShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Amount", "better-payment")}
                        checked={transactionTableAmountShow}
                        onChange={(newVal) => setAttributes({ transactionTableAmountShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Payment Type", "better-payment")}
                        checked={transactionTablePaymentTypeShow}
                        onChange={(newVal) => setAttributes({ transactionTablePaymentTypeShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Transaction ID", "better-payment")}
                        checked={transactionTableTransactionIdShow}
                        onChange={(newVal) => setAttributes({ transactionTableTransactionIdShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Source", "better-payment")}
                        checked={transactionTableSourceShow}
                        onChange={(newVal) => setAttributes({ transactionTableSourceShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Status", "better-payment")}
                        checked={transactionTableStatusShow}
                        onChange={(newVal) => setAttributes({ transactionTableStatusShow: newVal })}
                    />

                    <ToggleControl
                        label={__("Date", "better-payment")}
                        checked={transactionTableDateShow}
                        onChange={(newVal) => setAttributes({ transactionTableDateShow: newVal })}
                    />
                </InspectorPanel.PanelBody>
                {/* Subscriptions List Subsection — pro only, shown only when tab is enabled */}
                {subscriptionsShow && proEnabled && (
                    <>
                        <InspectorPanel.PanelBody title={__("Subscriptions", "better-payment")} initialOpen={false}>

                        <ToggleControl
                            label={__("Subscription ID", "better-payment")}
                            checked={subscriptionTableSubscriptionIdShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableSubscriptionIdShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Product Name", "better-payment")}
                            checked={subscriptionTablePlanIdShow}
                            onChange={(newVal) => setAttributes({ subscriptionTablePlanIdShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Status", "better-payment")}
                            checked={subscriptionTableStatusShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableStatusShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Amount", "better-payment")}
                            checked={subscriptionTableAmountShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableAmountShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Payment Date", "better-payment")}
                            checked={subscriptionTableCreatedDateShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableCreatedDateShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Renewal Date", "better-payment")}
                            checked={subscriptionTableCurrentPeriodShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableCurrentPeriodShow: newVal })}
                        />

                        <ToggleControl
                            label={__("Action", "better-payment")}
                            checked={subscriptionTableActionShow}
                            onChange={(newVal) => setAttributes({ subscriptionTableActionShow: newVal })}
                        />
                        </InspectorPanel.PanelBody>
                    </>
                )}
                {/* Content Section */}
                <InspectorPanel.PanelBody title={__("Content", "better-payment")} initialOpen={false}>
                    {/* General Labels */}
                    <div style={{ fontSize: '12px', fontWeight: '600', marginBottom: '10px', color: '#000' }}>
                        {__("Label", "better-payment")}
                    </div>
                    <TextControl
                        label={__("Dashboard", "better-payment")}
                        value={dashboardLabel}
                        onChange={(newVal) => setAttributes({ dashboardLabel: newVal })}
                    />

                    <TextControl
                        label={__("Transactions", "better-payment")}
                        value={transactionLabel}
                        onChange={(newVal) => setAttributes({ transactionLabel: newVal })}
                    />

                    <TextControl
                        label={__("Subscriptions", "better-payment")}
                        value={subscriptionLabel}
                        onChange={(newVal) => setAttributes({ subscriptionLabel: newVal })}
                    />

                    <TextControl
                        label={__("Refresh Stats", "better-payment")}
                        value={refreshStatsLabel}
                        onChange={(newVal) => setAttributes({ refreshStatsLabel: newVal })}
                    />

                    <TextControl
                        label={__("No Items", "better-payment")}
                        value={noItemsLabel}
                        onChange={(newVal) => setAttributes({ noItemsLabel: newVal })}
                    />

                    {/* Dashboard Content Labels */}
                    <hr style={{ margin: '15px 0' }} />
                    <div style={{ fontSize: '12px', fontWeight: '600', marginBottom: '10px', color: '#000' }}>
                        {__("Dashboard", "better-payment")}
                    </div>

                    <TextControl
                        label={__("Total Amount", "better-payment")}
                        value={dashboardTotalAmountLabel}
                        onChange={(newVal) => setAttributes({ dashboardTotalAmountLabel: newVal })}
                    />

                    <TextControl
                        label={__("Completed Amount", "better-payment")}
                        value={dashboardCompletedAmountLabel}
                        onChange={(newVal) => setAttributes({ dashboardCompletedAmountLabel: newVal })}
                    />

                    <TextControl
                        label={__("Incomplete Amount", "better-payment")}
                        value={dashboardIncompleteAmountLabel}
                        onChange={(newVal) => setAttributes({ dashboardIncompleteAmountLabel: newVal })}
                    />

                    <TextControl
                        label={__("Refunded Amount", "better-payment")}
                        value={dashboardRefundedAmountLabel}
                        onChange={(newVal) => setAttributes({ dashboardRefundedAmountLabel: newVal })}
                    />

                    <TextControl
                        label={__("View All", "better-payment")}
                        value={dashboardViewAllLabel}
                        onChange={(newVal) => setAttributes({ dashboardViewAllLabel: newVal })}
                    />

                    <TextControl
                        label={__("Analytics Reports", "better-payment")}
                        value={dashboardAnalyticsReportsLabel}
                        onChange={(newVal) => setAttributes({ dashboardAnalyticsReportsLabel: newVal })}
                    />

                    <TextControl
                        label={__("Recent Transactions", "better-payment")}
                        value={dashboardRecentTransactionsLabel}
                        onChange={(newVal) => setAttributes({ dashboardRecentTransactionsLabel: newVal })}
                    />

                    <TextControl
                        label={__("Recurring Subscriptions", "better-payment")}
                        value={dashboardRecurringSubscriptionsLabel}
                        onChange={(newVal) => setAttributes({ dashboardRecurringSubscriptionsLabel: newVal })}
                    />

                    <TextControl
                        label={__("Split Subscriptions", "better-payment")}
                        value={dashboardSplitSubscriptionsLabel}
                        onChange={(newVal) => setAttributes({ dashboardSplitSubscriptionsLabel: newVal })}
                    />

                    {/* Transactions List Content Labels */}
                    <hr style={{ margin: '15px 0' }} />
                    <div style={{ fontSize: '12px', fontWeight: '600', marginBottom: '10px', color: '#000' }}>
                        {__("Transactions List", "better-payment")}
                    </div>

                    <TextControl
                        label={__("Name", "better-payment")}
                        value={transactionTableNameLabel}
                        onChange={(newVal) => setAttributes({ transactionTableNameLabel: newVal })}
                    />

                    <TextControl
                        label={__("Email Address", "better-payment")}
                        value={transactionTableEmailAddressLabel}
                        onChange={(newVal) => setAttributes({ transactionTableEmailAddressLabel: newVal })}
                    />

                    <TextControl
                        label={__("Amount", "better-payment")}
                        value={transactionTableAmountLabel}
                        onChange={(newVal) => setAttributes({ transactionTableAmountLabel: newVal })}
                    />

                    <TextControl
                        label={__("Payment Type", "better-payment")}
                        value={transactionTablePaymentTypeLabel}
                        onChange={(newVal) => setAttributes({ transactionTablePaymentTypeLabel: newVal })}
                    />

                    <TextControl
                        label={__("Transaction ID", "better-payment")}
                        value={transactionTableTransactionIdLabel}
                        onChange={(newVal) => setAttributes({ transactionTableTransactionIdLabel: newVal })}
                    />

                    <TextControl
                        label={__("Source", "better-payment")}
                        value={transactionTableSourceLabel}
                        onChange={(newVal) => setAttributes({ transactionTableSourceLabel: newVal })}
                    />

                    <TextControl
                        label={__("Status", "better-payment")}
                        value={transactionTableStatusLabel}
                        onChange={(newVal) => setAttributes({ transactionTableStatusLabel: newVal })}
                    />

                    <TextControl
                        label={__("Date", "better-payment")}
                        value={transactionTableDateLabel}
                        onChange={(newVal) => setAttributes({ transactionTableDateLabel: newVal })}
                    />

                    {/* Subscriptions List Content Labels — pro only */}
                    {subscriptionsShow && proEnabled && (
                        <>
                            <hr style={{ margin: '15px 0' }} />
                            <div style={{ fontSize: '12px', fontWeight: '600', marginBottom: '10px', color: '#000' }}>
                                {__("Subscriptions List", "better-payment")}
                            </div>

                            <TextControl
                                label={__("Subscription ID", "better-payment")}
                                value={subscriptionTableSubscriptionIdLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableSubscriptionIdLabel: newVal })}
                            />

                            <TextControl
                                label={__("Product Name", "better-payment")}
                                value={subscriptionTablePlanIdLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTablePlanIdLabel: newVal })}
                            />

                            <TextControl
                                label={__("Status", "better-payment")}
                                value={subscriptionTableStatusLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableStatusLabel: newVal })}
                            />

                            <TextControl
                                label={__("Amount", "better-payment")}
                                value={subscriptionTableAmountLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableAmountLabel: newVal })}
                            />

                            <TextControl
                                label={__("Payment Date", "better-payment")}
                                value={subscriptionTableCreatedDateLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableCreatedDateLabel: newVal })}
                            />

                            <TextControl
                                label={__("Renewal Date", "better-payment")}
                                value={subscriptionTableCurrentPeriodLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableCurrentPeriodLabel: newVal })}
                            />

                            <TextControl
                                label={__("Action", "better-payment")}
                                value={subscriptionTableActionLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableActionLabel: newVal })}
                            />

                            <TextControl
                                label={__("Status: Active", "better-payment")}
                                value={subscriptionTableStatusActiveLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableStatusActiveLabel: newVal })}
                            />

                            <TextControl
                                label={__("Status: Inactive", "better-payment")}
                                value={subscriptionTableStatusInactiveLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableStatusInactiveLabel: newVal })}
                            />

                            <TextControl
                                label={__("Action: Cancel", "better-payment")}
                                value={subscriptionTableActionCancelLabel}
                                onChange={(newVal) => setAttributes({ subscriptionTableActionCancelLabel: newVal })}
                            />
                        </>
                    )}
                </InspectorPanel.PanelBody>
            </InspectorPanel.General>

            <InspectorPanel.Style>
                <InspectorPanel.PanelBody title={__("Container", "better-payment")} initialOpen={true}>
                    <BackgroundControl 
                        label={__("Background", "better-payment")}
                        noOverlay
                        noMainBgi
                        noOverlayBgi
                        controlName={CONTAINER_BACKGROUND} 
                    />
                    <ResponsiveDimensionsControl
                        controlName={CONTAINER_MARGIN}
                        baseLabel={__("Margin", "better-payment")}
                    />
                    <ResponsiveDimensionsControl
                        controlName={CONTAINER_PADDING}
                        baseLabel={__("Padding", "better-payment")}
                    />
                    <BorderShadowControl 
                        label={__("Border & Shadow", "better-payment")}
                        controlName={CONTAINER_BORDER_RADIUS} 
                    />
                </InspectorPanel.PanelBody>
                <InspectorPanel.PanelBody title={__("Sidebar", "better-payment")} initialOpen={false}>
                    <BackgroundControl
                        label={__("Background", "better-payment")}
                        noOverlay noMainBgi noOverlayBgi
                        controlName={SIDEBAR_BG}
                    />
                    <ResponsiveDimensionsControl controlName={SIDEBAR_MARGIN} baseLabel={__("Margin", "better-payment")} />
                    <ResponsiveDimensionsControl controlName={SIDEBAR_PADDING} baseLabel={__("Padding", "better-payment")} />
                    <BorderShadowControl label={__("Border & Shadow", "better-payment")} controlName={SIDEBAR_BORDER} />
                    <Divider />
                    <PanelRow><strong>{__("Avatar", "better-payment")}</strong></PanelRow>
                    <ResponsiveRangeController
                        baseLabel={__("Image Size", "better-payment")}
                        controlName={SIDEBAR_AVATAR_SIZE}
                        min={15} max={200} step={1}
                    />
                    <ColorControl
                        label={__("Name Color", "better-payment")}
                        color={sidebarAvatarTextColor}
                        onChange={(color) => setAttributes({ sidebarAvatarTextColor: color })}
                        attributeName="sidebarAvatarTextColor"
                    />
                    <ColorControl
                        label={__("Name Hover Color", "better-payment")}
                        color={sidebarAvatarHoverTextColor}
                        onChange={(color) => setAttributes({ sidebarAvatarHoverTextColor: color })}
                        attributeName="sidebarAvatarHoverTextColor"
                    />
                    <TypographyDropdown baseLabel={__("Name Typography", "better-payment")} typographyPrefixConstant={typoPrefix_sidebar_avatar} />
                    <Divider />
                    <PanelRow><strong>{__("Navigation Menu", "better-payment")}</strong></PanelRow>
                    <TypographyDropdown baseLabel={__("Typography", "better-payment")} typographyPrefixConstant={typoPrefix_sidebar_nav} />
                    <ColorControl
                        label={__("Active Text Color", "better-payment")}
                        color={sidebarNavTextColor}
                        onChange={(color) => setAttributes({ sidebarNavTextColor: color })}
                        attributeName="sidebarNavTextColor"
                    />
                    <ColorControl
                        label={__("Active Text Hover Color", "better-payment")}
                        color={sidebarNavHoverTextColor}
                        onChange={(color) => setAttributes({ sidebarNavHoverTextColor: color })}
                        attributeName="sidebarNavHoverTextColor"
                    />
                    <Divider />
                    <PanelRow><strong>{__("Icon", "better-payment")}</strong></PanelRow>
                    <ResponsiveRangeController
                        baseLabel={__("Icon Size", "better-payment")}
                        controlName={SIDEBAR_ICON_SIZE}
                        min={10} max={100} step={1}
                    />
                    <ColorControl
                        label={__("Active Icon Color", "better-payment")}
                        color={sidebarIconColor}
                        onChange={(color) => setAttributes({ sidebarIconColor: color })}
                        attributeName="sidebarIconColor"
                    />
                </InspectorPanel.PanelBody>

                <InspectorPanel.PanelBody title={__("Header", "better-payment")} initialOpen={false}>
                    <BackgroundControl
                        label={__("Background", "better-payment")}
                        noOverlay noMainBgi noOverlayBgi
                        controlName={HEADER_BG}
                    />
                    <ResponsiveDimensionsControl controlName={HEADER_MARGIN} baseLabel={__("Margin", "better-payment")} />
                    <ResponsiveDimensionsControl controlName={HEADER_PADDING} baseLabel={__("Padding", "better-payment")} />
                    <BorderShadowControl label={__("Border & Shadow", "better-payment")} controlName={HEADER_BORDER} />
                    <Divider />
                    <PanelRow><strong>{__("Title", "better-payment")}</strong></PanelRow>
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={headerTextColor}
                        onChange={(color) => setAttributes({ headerTextColor: color })}
                        attributeName="headerTextColor"
                    />
                    <ColorControl
                        label={__("Text Hover Color", "better-payment")}
                        color={headerHoverTextColor}
                        onChange={(color) => setAttributes({ headerHoverTextColor: color })}
                        attributeName="headerHoverTextColor"
                    />
                    <TypographyDropdown baseLabel={__("Typography", "better-payment")} typographyPrefixConstant={typoPrefix_header_title} />
                </InspectorPanel.PanelBody>

                <InspectorPanel.PanelBody title={__("Table", "better-payment")} initialOpen={false}>
                    <PanelRow><strong>{__("Container", "better-payment")}</strong></PanelRow>
                    <BackgroundControl
                        label={__("Background", "better-payment")}
                        noOverlay noMainBgi noOverlayBgi
                        controlName={TABLE_BG}
                    />
                    <ResponsiveDimensionsControl controlName={TABLE_MARGIN} baseLabel={__("Margin", "better-payment")} />
                    <ResponsiveDimensionsControl controlName={TABLE_PADDING} baseLabel={__("Padding", "better-payment")} />
                    <BorderShadowControl label={__("Border & Shadow", "better-payment")} controlName={TABLE_BORDER} />
                    <Divider />
                    <PanelRow><strong>{__("Table Header", "better-payment")}</strong></PanelRow>
                    <BackgroundControl
                        label={__("Background", "better-payment")}
                        noOverlay noMainBgi noOverlayBgi
                        controlName={TABLE_HEADER_BG}
                    />
                    <ResponsiveDimensionsControl controlName={TABLE_HEADER_MARGIN} baseLabel={__("Margin", "better-payment")} />
                    <ResponsiveDimensionsControl controlName={TABLE_HEADER_PADDING} baseLabel={__("Padding", "better-payment")} />
                    <BorderShadowControl label={__("Border & Shadow", "better-payment")} controlName={TABLE_HEADER_BORDER} />
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={tableHeaderTextColor}
                        onChange={(color) => setAttributes({ tableHeaderTextColor: color })}
                        attributeName="tableHeaderTextColor"
                    />
                    <ColorControl
                        label={__("Text Hover Color", "better-payment")}
                        color={tableHeaderHoverTextColor}
                        onChange={(color) => setAttributes({ tableHeaderHoverTextColor: color })}
                        attributeName="tableHeaderHoverTextColor"
                    />
                    <TypographyDropdown baseLabel={__("Typography", "better-payment")} typographyPrefixConstant={typoPrefix_table_header} />
                    <Divider />
                    <PanelRow><strong>{__("Table Body", "better-payment")}</strong></PanelRow>
                    <BackgroundControl
                        label={__("Background", "better-payment")}
                        noOverlay noMainBgi noOverlayBgi
                        controlName={TABLE_BODY_BG}
                    />
                    <ResponsiveDimensionsControl controlName={TABLE_BODY_MARGIN} baseLabel={__("Margin", "better-payment")} />
                    <ResponsiveDimensionsControl controlName={TABLE_BODY_PADDING} baseLabel={__("Padding", "better-payment")} />
                    <BorderShadowControl label={__("Border & Shadow", "better-payment")} controlName={TABLE_BODY_BORDER} />
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={tableBodyTextColor}
                        onChange={(color) => setAttributes({ tableBodyTextColor: color })}
                        attributeName="tableBodyTextColor"
                    />
                    <ColorControl
                        label={__("Text Hover Color", "better-payment")}
                        color={tableBodyHoverTextColor}
                        onChange={(color) => setAttributes({ tableBodyHoverTextColor: color })}
                        attributeName="tableBodyHoverTextColor"
                    />
                    <TypographyDropdown baseLabel={__("Typography", "better-payment")} typographyPrefixConstant={typoPrefix_table_body} />
                    <Divider />
                    <PanelRow><strong>{__("Active Button", "better-payment")}</strong></PanelRow>
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={activeButtonTextColor}
                        onChange={(color) => setAttributes({ activeButtonTextColor: color })}
                        attributeName="activeButtonTextColor"
                    />
                    <ColorControl
                        label={__("Background Color", "better-payment")}
                        color={activeButtonBgColor}
                        onChange={(color) => setAttributes({ activeButtonBgColor: color })}
                        attributeName="activeButtonBgColor"
                    />
                    <ColorControl
                        label={__("Hover Text Color", "better-payment")}
                        color={activeButtonHoverTextColor}
                        onChange={(color) => setAttributes({ activeButtonHoverTextColor: color })}
                        attributeName="activeButtonHoverTextColor"
                    />
                    <ColorControl
                        label={__("Hover Background Color", "better-payment")}
                        color={activeButtonHoverBgColor}
                        onChange={(color) => setAttributes({ activeButtonHoverBgColor: color })}
                        attributeName="activeButtonHoverBgColor"
                    />
                    <Divider />
                    <PanelRow><strong>{__("Inactive Button", "better-payment")}</strong></PanelRow>
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={inactiveButtonTextColor}
                        onChange={(color) => setAttributes({ inactiveButtonTextColor: color })}
                        attributeName="inactiveButtonTextColor"
                    />
                    <ColorControl
                        label={__("Background Color", "better-payment")}
                        color={inactiveButtonBgColor}
                        onChange={(color) => setAttributes({ inactiveButtonBgColor: color })}
                        attributeName="inactiveButtonBgColor"
                    />
                    <ColorControl
                        label={__("Hover Text Color", "better-payment")}
                        color={inactiveButtonHoverTextColor}
                        onChange={(color) => setAttributes({ inactiveButtonHoverTextColor: color })}
                        attributeName="inactiveButtonHoverTextColor"
                    />
                    <ColorControl
                        label={__("Hover Background Color", "better-payment")}
                        color={inactiveButtonHoverBgColor}
                        onChange={(color) => setAttributes({ inactiveButtonHoverBgColor: color })}
                        attributeName="inactiveButtonHoverBgColor"
                    />
                    <Divider />
                    <PanelRow><strong>{__("Cancel Button", "better-payment")}</strong></PanelRow>
                    <ColorControl
                        label={__("Text Color", "better-payment")}
                        color={cancelButtonTextColor}
                        onChange={(color) => setAttributes({ cancelButtonTextColor: color })}
                        attributeName="cancelButtonTextColor"
                    />
                    <ColorControl
                        label={__("Background Color", "better-payment")}
                        color={cancelButtonBgColor}
                        onChange={(color) => setAttributes({ cancelButtonBgColor: color })}
                        attributeName="cancelButtonBgColor"
                    />
                    <ColorControl
                        label={__("Hover Text Color", "better-payment")}
                        color={cancelButtonHoverTextColor}
                        onChange={(color) => setAttributes({ cancelButtonHoverTextColor: color })}
                        attributeName="cancelButtonHoverTextColor"
                    />
                    <ColorControl
                        label={__("Hover Background Color", "better-payment")}
                        color={cancelButtonHoverBgColor}
                        onChange={(color) => setAttributes({ cancelButtonHoverBgColor: color })}
                        attributeName="cancelButtonHoverBgColor"
                    />
                </InspectorPanel.PanelBody>
            </InspectorPanel.Style>
        </InspectorPanel>
        </>
    );
};

export default Inspector;
