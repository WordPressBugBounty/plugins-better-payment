/**
 * Style component for User Dashboard block.
 *
 * Generates responsive, per-instance CSS for Container, Sidebar, Header and Table sections.
 * CSS is scoped to the block via blockId and persisted to blockMeta for frontend delivery.
 *
 * @package Better_Payment
 */

import {
    CONTAINER_BACKGROUND, CONTAINER_MARGIN, CONTAINER_PADDING, CONTAINER_BORDER_RADIUS,
    SIDEBAR_BG, SIDEBAR_MARGIN, SIDEBAR_PADDING, SIDEBAR_BORDER, SIDEBAR_AVATAR_SIZE, SIDEBAR_ICON_SIZE,
    HEADER_BG, HEADER_MARGIN, HEADER_PADDING, HEADER_BORDER,
    TABLE_BG, TABLE_MARGIN, TABLE_PADDING, TABLE_BORDER,
    TABLE_HEADER_BG, TABLE_HEADER_MARGIN, TABLE_HEADER_PADDING, TABLE_HEADER_BORDER,
    TABLE_BODY_BG, TABLE_BODY_MARGIN, TABLE_BODY_PADDING, TABLE_BODY_BORDER,
} from "./constants";

import {
    typoPrefix_sidebar_avatar,
    typoPrefix_sidebar_nav,
    typoPrefix_header_title,
    typoPrefix_table_header,
    typoPrefix_table_body,
} from "./typographyConstant";

import {
    softMinifyCssStrings,
    generateDimensionsControlStyles,
    generateBorderShadowStyles,
    StyleComponent,
    useBlockAttributes,
    generateBackgroundControlStyles,
    generateTypographyStyles,
    generateResponsiveRangeStyles,
} from "@better-payment/controls";

export default function Style(props) {
    const { setAttributes, name } = props;
    const attributes = useBlockAttributes();
    const {
        blockId,
        sidebarAvatarTextColor, sidebarAvatarHoverTextColor,
        sidebarNavTextColor, sidebarNavHoverTextColor,
        sidebarIconColor,
        headerTextColor, headerHoverTextColor,
        activeButtonTextColor, activeButtonBgColor, activeButtonHoverTextColor, activeButtonHoverBgColor,
        inactiveButtonTextColor, inactiveButtonBgColor, inactiveButtonHoverTextColor, inactiveButtonHoverBgColor,
        cancelButtonTextColor, cancelButtonBgColor, cancelButtonHoverTextColor, cancelButtonHoverBgColor,
        tableHeaderTextColor, tableHeaderHoverTextColor,
        tableBodyTextColor, tableBodyHoverTextColor,
    } = attributes;

    // ── Container ──────────────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: containerBgDesktop,
        hoverBackgroundStylesDesktop: containerHoverBgDesktop,
        backgroundStylesTab: containerBgTab,
        hoverBackgroundStylesTab: containerHoverBgTab,
        backgroundStylesMobile: containerBgMobile,
        hoverBackgroundStylesMobile: containerHoverBgMobile,
        bgTransitionStyle: containerBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: CONTAINER_BACKGROUND });

    const { dimensionStylesDesktop: containerMarginDesktop, dimensionStylesTab: containerMarginTab, dimensionStylesMobile: containerMarginMobile }
        = generateDimensionsControlStyles({ controlName: CONTAINER_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: containerPaddingDesktop, dimensionStylesTab: containerPaddingTab, dimensionStylesMobile: containerPaddingMobile }
        = generateDimensionsControlStyles({ controlName: CONTAINER_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: containerBorderDesktop, styesTab: containerBorderTab, styesMobile: containerBorderMobile, stylesHoverDesktop: containerBorderHoverDesktop, stylesHoverTab: containerBorderHoverTab, stylesHoverMobile: containerBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: CONTAINER_BORDER_RADIUS, attributes });

    // ── Sidebar ────────────────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: sidebarBgDesktop,
        hoverBackgroundStylesDesktop: sidebarHoverBgDesktop,
        backgroundStylesTab: sidebarBgTab,
        hoverBackgroundStylesTab: sidebarHoverBgTab,
        backgroundStylesMobile: sidebarBgMobile,
        hoverBackgroundStylesMobile: sidebarHoverBgMobile,
        bgTransitionStyle: sidebarBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: SIDEBAR_BG });

    const { dimensionStylesDesktop: sidebarMarginDesktop, dimensionStylesTab: sidebarMarginTab, dimensionStylesMobile: sidebarMarginMobile }
        = generateDimensionsControlStyles({ controlName: SIDEBAR_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: sidebarPaddingDesktop, dimensionStylesTab: sidebarPaddingTab, dimensionStylesMobile: sidebarPaddingMobile }
        = generateDimensionsControlStyles({ controlName: SIDEBAR_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: sidebarBorderDesktop, styesTab: sidebarBorderTab, styesMobile: sidebarBorderMobile, stylesHoverDesktop: sidebarBorderHoverDesktop, stylesHoverTab: sidebarBorderHoverTab, stylesHoverMobile: sidebarBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: SIDEBAR_BORDER, attributes });

    // Avatar image size — property: null returns just "Npx" so we embed in width/height manually
    const { rangeStylesDesktop: avatarSizeDesktop, rangeStylesTab: avatarSizeTab, rangeStylesMobile: avatarSizeMobile }
        = generateResponsiveRangeStyles({ controlName: SIDEBAR_AVATAR_SIZE, property: null, attributes });

    // Sidebar icon size
    const { rangeStylesDesktop: iconSizeDesktop, rangeStylesTab: iconSizeTab, rangeStylesMobile: iconSizeMobile }
        = generateResponsiveRangeStyles({ controlName: SIDEBAR_ICON_SIZE, property: null, attributes });

    const { typoStylesDesktop: avatarTypoDesktop, typoStylesTab: avatarTypoTab, typoStylesMobile: avatarTypoMobile }
        = generateTypographyStyles({ attributes, prefixConstant: typoPrefix_sidebar_avatar });

    const { typoStylesDesktop: navTypoDesktop, typoStylesTab: navTypoTab, typoStylesMobile: navTypoMobile }
        = generateTypographyStyles({ attributes, prefixConstant: typoPrefix_sidebar_nav });

    // ── Header ─────────────────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: headerBgDesktop,
        hoverBackgroundStylesDesktop: headerHoverBgDesktop,
        backgroundStylesTab: headerBgTab,
        hoverBackgroundStylesTab: headerHoverBgTab,
        backgroundStylesMobile: headerBgMobile,
        hoverBackgroundStylesMobile: headerHoverBgMobile,
        bgTransitionStyle: headerBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: HEADER_BG });

    const { dimensionStylesDesktop: headerMarginDesktop, dimensionStylesTab: headerMarginTab, dimensionStylesMobile: headerMarginMobile }
        = generateDimensionsControlStyles({ controlName: HEADER_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: headerPaddingDesktop, dimensionStylesTab: headerPaddingTab, dimensionStylesMobile: headerPaddingMobile }
        = generateDimensionsControlStyles({ controlName: HEADER_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: headerBorderDesktop, styesTab: headerBorderTab, styesMobile: headerBorderMobile, stylesHoverDesktop: headerBorderHoverDesktop, stylesHoverTab: headerBorderHoverTab, stylesHoverMobile: headerBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: HEADER_BORDER, attributes });

    const { typoStylesDesktop: headerTitleTypoDesktop, typoStylesTab: headerTitleTypoTab, typoStylesMobile: headerTitleTypoMobile }
        = generateTypographyStyles({ attributes, prefixConstant: typoPrefix_header_title });

    // ── Table container ────────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: tableBgDesktop,
        hoverBackgroundStylesDesktop: tableHoverBgDesktop,
        backgroundStylesTab: tableBgTab,
        hoverBackgroundStylesTab: tableHoverBgTab,
        backgroundStylesMobile: tableBgMobile,
        hoverBackgroundStylesMobile: tableHoverBgMobile,
        bgTransitionStyle: tableBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: TABLE_BG });

    const { dimensionStylesDesktop: tableMarginDesktop, dimensionStylesTab: tableMarginTab, dimensionStylesMobile: tableMarginMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: tablePaddingDesktop, dimensionStylesTab: tablePaddingTab, dimensionStylesMobile: tablePaddingMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: tableBorderDesktop, styesTab: tableBorderTab, styesMobile: tableBorderMobile, stylesHoverDesktop: tableBorderHoverDesktop, stylesHoverTab: tableBorderHoverTab, stylesHoverMobile: tableBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: TABLE_BORDER, attributes });

    // ── Table header row ───────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: tableHeaderBgDesktop,
        hoverBackgroundStylesDesktop: tableHeaderHoverBgDesktop,
        backgroundStylesTab: tableHeaderBgTab,
        hoverBackgroundStylesTab: tableHeaderHoverBgTab,
        backgroundStylesMobile: tableHeaderBgMobile,
        hoverBackgroundStylesMobile: tableHeaderHoverBgMobile,
        bgTransitionStyle: tableHeaderBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: TABLE_HEADER_BG });

    const { dimensionStylesDesktop: tableHeaderMarginDesktop, dimensionStylesTab: tableHeaderMarginTab, dimensionStylesMobile: tableHeaderMarginMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_HEADER_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: tableHeaderPaddingDesktop, dimensionStylesTab: tableHeaderPaddingTab, dimensionStylesMobile: tableHeaderPaddingMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_HEADER_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: tableHeaderBorderDesktop, styesTab: tableHeaderBorderTab, styesMobile: tableHeaderBorderMobile, stylesHoverDesktop: tableHeaderBorderHoverDesktop, stylesHoverTab: tableHeaderBorderHoverTab, stylesHoverMobile: tableHeaderBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: TABLE_HEADER_BORDER, attributes });

    const { typoStylesDesktop: tableHeaderTypoDesktop, typoStylesTab: tableHeaderTypoTab, typoStylesMobile: tableHeaderTypoMobile }
        = generateTypographyStyles({ attributes, prefixConstant: typoPrefix_table_header });

    // ── Table body rows ────────────────────────────────────────────────────
    const {
        backgroundStylesDesktop: tableBodyBgDesktop,
        hoverBackgroundStylesDesktop: tableBodyHoverBgDesktop,
        backgroundStylesTab: tableBodyBgTab,
        hoverBackgroundStylesTab: tableBodyHoverBgTab,
        backgroundStylesMobile: tableBodyBgMobile,
        hoverBackgroundStylesMobile: tableBodyHoverBgMobile,
        bgTransitionStyle: tableBodyBgTransition,
    } = generateBackgroundControlStyles({ attributes, controlName: TABLE_BODY_BG });

    const { dimensionStylesDesktop: tableBodyMarginDesktop, dimensionStylesTab: tableBodyMarginTab, dimensionStylesMobile: tableBodyMarginMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_BODY_MARGIN, styleFor: "margin", attributes });

    const { dimensionStylesDesktop: tableBodyPaddingDesktop, dimensionStylesTab: tableBodyPaddingTab, dimensionStylesMobile: tableBodyPaddingMobile }
        = generateDimensionsControlStyles({ controlName: TABLE_BODY_PADDING, styleFor: "padding", attributes });

    const { styesDesktop: tableBodyBorderDesktop, styesTab: tableBodyBorderTab, styesMobile: tableBodyBorderMobile, stylesHoverDesktop: tableBodyBorderHoverDesktop, stylesHoverTab: tableBodyBorderHoverTab, stylesHoverMobile: tableBodyBorderHoverMobile }
        = generateBorderShadowStyles({ controlName: TABLE_BODY_BORDER, attributes });

    const { typoStylesDesktop: tableBodyTypoDesktop, typoStylesTab: tableBodyTypoTab, typoStylesMobile: tableBodyTypoMobile }
        = generateTypographyStyles({ attributes, prefixConstant: typoPrefix_table_body });

    // ── CSS Strings ────────────────────────────────────────────────────────
    const desktopStyles = `
        .better-payment-user-dashboard {
            ${containerBgDesktop}
            ${containerMarginDesktop}
            ${containerPaddingDesktop}
            ${containerBorderDesktop}
            ${containerBgTransition}
        }
        .better-payment-user-dashboard:hover {
            ${containerHoverBgDesktop}
            ${containerBorderHoverDesktop}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar {
            ${sidebarBgDesktop}
            ${sidebarMarginDesktop}
            ${sidebarPaddingDesktop}
            ${sidebarBorderDesktop}
            ${sidebarBgTransition}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar:hover {
            ${sidebarHoverBgDesktop}
            ${sidebarBorderHoverDesktop}
        }
        .better-payment-user-dashboard .bp--author img {
            ${avatarSizeDesktop ? `width: ${avatarSizeDesktop}; height: ${avatarSizeDesktop};` : ''}
        }
        .better-payment-user-dashboard .bp--author .user-name {
            color: ${sidebarAvatarTextColor};
            ${avatarTypoDesktop}
        }
        .better-payment-user-dashboard .bp--author .user-name:hover {
            color: ${sidebarAvatarHoverTextColor};
        }
        .better-payment-user-dashboard .bp--sidebar-nav .bp--nav-text {
            ${navTypoDesktop}
        }
        .better-payment-user-dashboard .bp--sidebar-nav.active .bp--nav-text {
            color: ${sidebarNavTextColor};
        }
        .better-payment-user-dashboard .bp--sidebar-nav.active .bp--nav-text:hover {
            color: ${sidebarNavHoverTextColor};
        }
        .better-payment-user-dashboard .bp--nav-icon svg {
            ${iconSizeDesktop ? `width: ${iconSizeDesktop}; height: ${iconSizeDesktop};` : ''}
        }
        .better-payment-user-dashboard .bp--sidebar-nav.active .bp--nav-icon svg path {
            ${sidebarIconColor ? `stroke: ${sidebarIconColor}; fill: ${sidebarIconColor};` : ''}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-header {
            ${headerBgDesktop}
            ${headerMarginDesktop}
            ${headerPaddingDesktop}
            ${headerBorderDesktop}
            ${headerBgTransition}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header:hover {
            ${headerHoverBgDesktop}
            ${headerBorderHoverDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header h2 {
            color: ${headerTextColor};
            ${headerTitleTypoDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header h2:hover {
            color: ${headerHoverTextColor};
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table {
            ${tableBgDesktop}
            ${tableMarginDesktop}
            ${tablePaddingDesktop}
            ${tableBorderDesktop}
            ${tableBgTransition}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table:hover {
            ${tableHoverBgDesktop}
            ${tableBorderHoverDesktop}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-header {
            ${tableHeaderBgDesktop}
            ${tableHeaderMarginDesktop}
            ${tableHeaderPaddingDesktop}
            ${tableHeaderBorderDesktop}
            ${tableHeaderBgTransition}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header:hover {
            ${tableHeaderHoverBgDesktop}
            ${tableHeaderBorderHoverDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header .th h5 {
            color: ${tableHeaderTextColor};
            ${tableHeaderTypoDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header .th h5:hover {
            color: ${tableHeaderHoverTextColor};
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-body {
            ${tableBodyBgDesktop}
            ${tableBodyMarginDesktop}
            ${tableBodyPaddingDesktop}
            ${tableBodyBorderDesktop}
            ${tableBodyBgTransition}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body:hover {
            ${tableBodyHoverBgDesktop}
            ${tableBodyBorderHoverDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body .td p {
            color: ${tableBodyTextColor};
            ${tableBodyTypoDesktop}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body .td p:hover {
            color: ${tableBodyHoverTextColor};
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table button.active {
            ${activeButtonTextColor ? `color: ${activeButtonTextColor};` : ''}
            ${activeButtonBgColor ? `background-color: ${activeButtonBgColor};` : ''}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table button.active:hover {
            ${activeButtonHoverTextColor ? `color: ${activeButtonHoverTextColor};` : ''}
            ${activeButtonHoverBgColor ? `background-color: ${activeButtonHoverBgColor};` : ''}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table button.inactive {
            ${inactiveButtonTextColor ? `color: ${inactiveButtonTextColor};` : ''}
            ${inactiveButtonBgColor ? `background-color: ${inactiveButtonBgColor};` : ''}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table button.inactive:hover {
            ${inactiveButtonHoverTextColor ? `color: ${inactiveButtonHoverTextColor};` : ''}
            ${inactiveButtonHoverBgColor ? `background-color: ${inactiveButtonHoverBgColor};` : ''}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table button.cancel {
            ${cancelButtonTextColor ? `color: ${cancelButtonTextColor};` : ''}
            ${cancelButtonBgColor ? `background-color: ${cancelButtonBgColor};` : ''}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table button.cancel:hover {
            ${cancelButtonHoverTextColor ? `color: ${cancelButtonHoverTextColor};` : ''}
            ${cancelButtonHoverBgColor ? `background-color: ${cancelButtonHoverBgColor};` : ''}
        }
    `;

    const tabStyles = `
        .better-payment-user-dashboard {
            ${containerBgTab}
            ${containerMarginTab}
            ${containerPaddingTab}
            ${containerBorderTab}
        }
        .better-payment-user-dashboard:hover {
            ${containerHoverBgTab}
            ${containerBorderHoverTab}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar {
            ${sidebarBgTab}
            ${sidebarMarginTab}
            ${sidebarPaddingTab}
            ${sidebarBorderTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar:hover {
            ${sidebarHoverBgTab}
            ${sidebarBorderHoverTab}
        }
        .better-payment-user-dashboard .bp--author img {
            ${avatarSizeTab ? `width: ${avatarSizeTab}; height: ${avatarSizeTab};` : ''}
        }
        .better-payment-user-dashboard .bp--author .user-name {
            ${avatarTypoTab}
        }
        .better-payment-user-dashboard .bp--sidebar-nav .bp--nav-text {
            ${navTypoTab}
        }
        .better-payment-user-dashboard .bp--nav-icon svg {
            ${iconSizeTab ? `width: ${iconSizeTab}; height: ${iconSizeTab};` : ''}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-header {
            ${headerBgTab}
            ${headerMarginTab}
            ${headerPaddingTab}
            ${headerBorderTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header:hover {
            ${headerHoverBgTab}
            ${headerBorderHoverTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header h2 {
            ${headerTitleTypoTab}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table {
            ${tableBgTab}
            ${tableMarginTab}
            ${tablePaddingTab}
            ${tableBorderTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table:hover {
            ${tableHoverBgTab}
            ${tableBorderHoverTab}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-header {
            ${tableHeaderBgTab}
            ${tableHeaderMarginTab}
            ${tableHeaderPaddingTab}
            ${tableHeaderBorderTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header:hover {
            ${tableHeaderHoverBgTab}
            ${tableHeaderBorderHoverTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header .th h5 {
            ${tableHeaderTypoTab}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-body {
            ${tableBodyBgTab}
            ${tableBodyMarginTab}
            ${tableBodyPaddingTab}
            ${tableBodyBorderTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body:hover {
            ${tableBodyHoverBgTab}
            ${tableBodyBorderHoverTab}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body .td p {
            ${tableBodyTypoTab}
        }
    `;

    const mobileStyles = `
        .better-payment-user-dashboard {
            ${containerBgMobile}
            ${containerMarginMobile}
            ${containerPaddingMobile}
            ${containerBorderMobile}
        }
        .better-payment-user-dashboard:hover {
            ${containerHoverBgMobile}
            ${containerBorderHoverMobile}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar {
            ${sidebarBgMobile}
            ${sidebarMarginMobile}
            ${sidebarPaddingMobile}
            ${sidebarBorderMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-sidebar:hover {
            ${sidebarHoverBgMobile}
            ${sidebarBorderHoverMobile}
        }
        .better-payment-user-dashboard .bp--author img {
            ${avatarSizeMobile ? `width: ${avatarSizeMobile}; height: ${avatarSizeMobile};` : ''}
        }
        .better-payment-user-dashboard .bp--author .user-name {
            ${avatarTypoMobile}
        }
        .better-payment-user-dashboard .bp--sidebar-nav .bp--nav-text {
            ${navTypoMobile}
        }
        .better-payment-user-dashboard .bp--nav-icon svg {
            ${iconSizeMobile ? `width: ${iconSizeMobile}; height: ${iconSizeMobile};` : ''}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-header {
            ${headerBgMobile}
            ${headerMarginMobile}
            ${headerPaddingMobile}
            ${headerBorderMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header:hover {
            ${headerHoverBgMobile}
            ${headerBorderHoverMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-header h2 {
            ${headerTitleTypoMobile}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table {
            ${tableBgMobile}
            ${tableMarginMobile}
            ${tablePaddingMobile}
            ${tableBorderMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table:hover {
            ${tableHoverBgMobile}
            ${tableBorderHoverMobile}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-header {
            ${tableHeaderBgMobile}
            ${tableHeaderMarginMobile}
            ${tableHeaderPaddingMobile}
            ${tableHeaderBorderMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header:hover {
            ${tableHeaderHoverBgMobile}
            ${tableHeaderBorderHoverMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-header .th h5 {
            ${tableHeaderTypoMobile}
        }

        .better-payment-user-dashboard .better-payment-user-dashboard-table-body {
            ${tableBodyBgMobile}
            ${tableBodyMarginMobile}
            ${tableBodyPaddingMobile}
            ${tableBodyBorderMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body:hover {
            ${tableBodyHoverBgMobile}
            ${tableBodyBorderHoverMobile}
        }
        .better-payment-user-dashboard .better-payment-user-dashboard-table-body .td p {
            ${tableBodyTypoMobile}
        }
    `;

    // Scope all rules to this block instance.
    // Negative lookahead (?![-\w]) matches only the root class name and not
    // longer class names that share the same prefix (e.g. -sidebar, -header, -table).
    const scopeCSS = (css) => blockId
        ? css.replace(/\.better-payment-user-dashboard(?![-\w])/g, `.better-payment-user-dashboard.bp-${blockId}`)
        : css;

    return (
        <StyleComponent
            attributes={attributes}
            setAttributes={setAttributes}
            desktopAllStyles={softMinifyCssStrings(scopeCSS(desktopStyles))}
            tabAllStyles={softMinifyCssStrings(scopeCSS(tabStyles))}
            mobileAllStyles={softMinifyCssStrings(scopeCSS(mobileStyles))}
            blockName={name}
        />
    );
}
