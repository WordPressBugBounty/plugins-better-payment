import {
    SIDEBAR_BACKGROUND,
    SIDEBAR_MARGIN,
    SIDEBAR_PADDING,
    SIDEBAR_BORDER,
    SIDEBAR_TITLE_MARGIN,
    SIDEBAR_TITLE_PADDING,
    SIDEBAR_SUBTITLE_MARGIN,
    SIDEBAR_SUBTITLE_PADDING,
    SIDEBAR_AMOUNT_TEXT_MARGIN,
    SIDEBAR_AMOUNT_TEXT_PADDING,
    SIDEBAR_AMOUNT_SUMMARY_MARGIN,
    SIDEBAR_AMOUNT_SUMMARY_PADDING,
    SIDEBAR_ICON_SIZE,
    SIDEBAR_ICON_MARGIN,
    SIDEBAR_ICON_PADDING,
    FORM_CONTAINER_BACKGROUND,
    FORM_CONTAINER_MARGIN,
    FORM_CONTAINER_PADDING,
    FORM_CONTAINER_BORDER,
    FORM_FIELDS_SPACING,
    FORM_FIELDS_PADDING,
    FORM_FIELDS_TEXT_INDENT,
    FORM_FIELDS_INPUT_WIDTH,
    FORM_FIELDS_INPUT_HEIGHT,
    FORM_FIELDS_BORDER,
    FORM_FIELDS_ICON_SIZE,
    FORM_FIELDS_ICON_WIDTH,
    FORM_FIELDS_ICON_HEIGHT,
    PAYMENT_METHOD_BORDER,
    PAYMENT_METHOD_INACTIVE_BORDER,
    AMOUNT_FIELDS_WIDTH,
    AMOUNT_FIELDS_HEIGHT,
    AMOUNT_FIELDS_SPACING,
    AMOUNT_FIELDS_BORDER,
    FORM_BUTTON_PADDING,
    FORM_BUTTON_BORDER,
    FORM_BUTTON_WIDTH,
    FORM_BUTTON_MARGIN_TOP,
} from "./constants";

import {
    typoPrefix_sidebar_title,
    typoPrefix_sidebar_sub_title,
    typoPrefix_sidebar_amount_text,
    typoPrefix_sidebar_amount_summary,
    typoPrefix_form_fields,
    typoPrefix_amount_fields,
    typoPrefix_form_button,
} from "./typographyConstant";

import {
    softMinifyCssStrings,
    generateDimensionsControlStyles,
    generateBorderShadowStyles,
    generateTypographyStyles,
    StyleComponent,
    useBlockAttributes,
    generateBackgroundControlStyles,
    generateResponsiveRangeStyles,
} from "@better-payment/controls";


export default function Style(props) {
    const { setAttributes, name } = props;
    const attributes = useBlockAttributes();
    const {
        blockId,
        sidebarTextColor,
        sidebarSubTextColor,
        sidebarAmountTextColor,
        sidebarAmountSummaryColor,
        sidebarIconColor,
        formFieldsBgColor,
        formFieldsColor,
        formFieldsPlaceholderColor,
        formButtonBackground,
        formButtonColor,
        formButtonHoverBackground,
        formButtonHoverColor,
        formButtonHoverBorderColor,
        amountFieldsBgColor,
        amountFieldsColor,
        amountFieldsSelectedBgColor,
        amountFieldsSelectedColor,
        formFieldsIconColor,
    } = attributes;

    const {
        backgroundStylesDesktop: wrapperBackgroundStylesDesktop,
        hoverBackgroundStylesDesktop: wrapperHoverBackgroundStylesDesktop,
        backgroundStylesTab: wrapperBackgroundStylesTab,
        hoverBackgroundStylesTab: wrapperHoverBackgroundStylesTab,
        backgroundStylesMobile: wrapperBackgroundStylesMobile,
        hoverBackgroundStylesMobile: wrapperHoverBackgroundStylesMobile,
        overlayStylesDesktop: wrapperOverlayStylesDesktop,
        hoverOverlayStylesDesktop: wrapperHoverOverlayStylesDesktop,
        overlayStylesTab: wrapperOverlayStylesTab,
        hoverOverlayStylesTab: wrapperHoverOverlayStylesTab,
        overlayStylesMobile: wrapperOverlayStylesMobile,
        hoverOverlayStylesMobile: wrapperHoverOverlayStylesMobile,
        bgTransitionStyle: wrapperBgTransitionStyle,
        ovlTransitionStyle: wrapperOvlTransitionStyle,
    } = generateBackgroundControlStyles({
        attributes,
        controlName: SIDEBAR_BACKGROUND,
    });

    const {
        dimensionStylesDesktop: wrapperMarginDesktop,
        dimensionStylesTab: wrapperMarginTab,
        dimensionStylesMobile: wrapperMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_MARGIN,
        styleFor: "margin",
        attributes,
    });

    const {
        dimensionStylesDesktop: wrapperPaddingDesktop,
        dimensionStylesTab: wrapperPaddingTab,
        dimensionStylesMobile: wrapperPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_PADDING,
        styleFor: "padding",
        attributes,
    });

    const {
        styesDesktop: sidebarBorderDesktop,
        styesTab: sidebarBorderTab,
        styesMobile: sidebarBorderMobile,
    } = generateBorderShadowStyles({
        controlName: SIDEBAR_BORDER,
        attributes,
    });

    const {
        typoStylesDesktop: titleTypoStylesDesktop,
        typoStylesTab: titleTypoStylesTab,
        typoStylesMobile: titleTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_sidebar_title,
    });

    //title margin
    const {
        dimensionStylesDesktop: titleMarginDesktop,
        dimensionStylesTab: titleMarginTab,
        dimensionStylesMobile: titleMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_TITLE_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // title padding
    const {
        dimensionStylesDesktop: titlePaddingDesktop,
        dimensionStylesTab: titlePaddingTab,
        dimensionStylesMobile: titlePaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_TITLE_PADDING,
        styleFor: "padding",
        attributes,
    });

    // subtitle typography
    const {
        typoStylesDesktop: subtitleTypoStylesDesktop,
        typoStylesTab: subtitleTypoStylesTab,
        typoStylesMobile: subtitleTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_sidebar_sub_title,
    });

    // subtitle margin
    const {
        dimensionStylesDesktop: subtitleMarginDesktop,
        dimensionStylesTab: subtitleMarginTab,
        dimensionStylesMobile: subtitleMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_SUBTITLE_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // subtitle padding
    const {
        dimensionStylesDesktop: subtitlePaddingDesktop,
        dimensionStylesTab: subtitlePaddingTab,
        dimensionStylesMobile: subtitlePaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_SUBTITLE_PADDING,
        styleFor: "padding",
        attributes,
    });

    // Do same for amount text, amount summary, icon
    // amount text typography
    const {
        typoStylesDesktop: amountTextTypoStylesDesktop,
        typoStylesTab: amountTextTypoStylesTab,
        typoStylesMobile: amountTextTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_sidebar_amount_text,
    });

    // amount text margin
    const {
        dimensionStylesDesktop: amountTextMarginDesktop,
        dimensionStylesTab: amountTextMarginTab,
        dimensionStylesMobile: amountTextMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_AMOUNT_TEXT_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // amount text padding
    const {
        dimensionStylesDesktop: amountTextPaddingDesktop,
        dimensionStylesTab: amountTextPaddingTab,
        dimensionStylesMobile: amountTextPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_AMOUNT_TEXT_PADDING,
        styleFor: "padding",
        attributes,
    });

    // amount summary typography
    const {
        typoStylesDesktop: amountSummaryTypoStylesDesktop,
        typoStylesTab: amountSummaryTypoStylesTab,
        typoStylesMobile: amountSummaryTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_sidebar_amount_summary,
    });

    // amount summary margin
    const {
        dimensionStylesDesktop: amountSummaryMarginDesktop,
        dimensionStylesTab: amountSummaryMarginTab,
        dimensionStylesMobile: amountSummaryMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_AMOUNT_SUMMARY_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // amount summary padding
    const {
        dimensionStylesDesktop: amountSummaryPaddingDesktop,
        dimensionStylesTab: amountSummaryPaddingTab,
        dimensionStylesMobile: amountSummaryPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_AMOUNT_SUMMARY_PADDING,
        styleFor: "padding",
        attributes,
    });

    // icon size
    const {
        rangeStylesDesktop: iconSizeDesktop,
        rangeStylesTab: iconSizeTab,
        rangeStylesMobile: iconSizeMobile,
    } = generateResponsiveRangeStyles({
        controlName: SIDEBAR_ICON_SIZE,
        attributes,
    });

    // icon margin
    const {
        dimensionStylesDesktop: iconMarginDesktop,
        dimensionStylesTab: iconMarginTab,
        dimensionStylesMobile: iconMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_ICON_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // icon padding
    const {
        dimensionStylesDesktop: iconPaddingDesktop,
        dimensionStylesTab: iconPaddingTab,
        dimensionStylesMobile: iconPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: SIDEBAR_ICON_PADDING,
        styleFor: "padding",
        attributes,
    });

    // form container background
    const {
        backgroundStylesDesktop: formContainerBackgroundStylesDesktop,
        hoverBackgroundStylesDesktop: formContainerHoverBackgroundStylesDesktop,
        backgroundStylesTab: formContainerBackgroundStylesTab,
        hoverBackgroundStylesTab: formContainerHoverBackgroundStylesTab,
        backgroundStylesMobile: formContainerBackgroundStylesMobile,
        hoverBackgroundStylesMobile: formContainerHoverBackgroundStylesMobile,
        bgTransitionStyle: formContainerBgTransitionStyle,
    } = generateBackgroundControlStyles({
        attributes,
        controlName: FORM_CONTAINER_BACKGROUND,
    });

    // form container margin
    const {
        dimensionStylesDesktop: formContainerMarginDesktop,
        dimensionStylesTab: formContainerMarginTab,
        dimensionStylesMobile: formContainerMarginMobile,
    } = generateDimensionsControlStyles({
        controlName: FORM_CONTAINER_MARGIN,
        styleFor: "margin",
        attributes,
    });

    // form container padding
    const {
        dimensionStylesDesktop: formContainerPaddingDesktop,
        dimensionStylesTab: formContainerPaddingTab,
        dimensionStylesMobile: formContainerPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: FORM_CONTAINER_PADDING,
        styleFor: "padding",
        attributes,
    });

    // form container border
    const {
        styesDesktop: formContainerBorderDesktop,
        styesTab: formContainerBorderTab,
        styesMobile: formContainerBorderMobile,
    } = generateBorderShadowStyles({
        controlName: FORM_CONTAINER_BORDER,
        attributes,
    });

    // form fields spacing using range control
    const {
        rangeStylesDesktop: formFieldsSpacingDesktop,
        rangeStylesTab: formFieldsSpacingTab,
        rangeStylesMobile: formFieldsSpacingMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_SPACING,
        attributes,
        property: "margin-bottom",
    });

    // form fields padding
    const {
        dimensionStylesDesktop: formFieldsPaddingDesktop,
        dimensionStylesTab: formFieldsPaddingTab,
        dimensionStylesMobile: formFieldsPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: FORM_FIELDS_PADDING,
        styleFor: "padding",
        attributes,
    });

    // form fields text indent
    const {
        rangeStylesDesktop: formFieldsTextIndentDesktop,
        rangeStylesTab: formFieldsTextIndentTab,
        rangeStylesMobile: formFieldsTextIndentMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_TEXT_INDENT,
        attributes,
        property: "text-indent",
    });

    // form fields input width
    const {
        rangeStylesDesktop: formFieldsInputWidthDesktop,
        rangeStylesTab: formFieldsInputWidthTab,
        rangeStylesMobile: formFieldsInputWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_INPUT_WIDTH,
        attributes,
        property: "width",
    });

    // form fields input height
    const {
        rangeStylesDesktop: formFieldsInputHeightDesktop,
        rangeStylesTab: formFieldsInputHeightTab,
        rangeStylesMobile: formFieldsInputHeightMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_INPUT_HEIGHT,
        attributes,
        property: "height",
    });

    // form fields border
    const {
        styesDesktop: formFieldsBorderDesktop,
        styesTab: formFieldsBorderTab,
        styesMobile: formFieldsBorderMobile,
    } = generateBorderShadowStyles({
        controlName: FORM_FIELDS_BORDER,
        attributes,
    });

    // form fields typography
    const {
        typoStylesDesktop: formFieldsTypoStylesDesktop,
        typoStylesTab: formFieldsTypoStylesTab,
        typoStylesMobile: formFieldsTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_form_fields,
    });

    const {
        rangeStylesDesktop: formBtnWidthDesktop,
        rangeStylesTab: formBtnWidthTab,
        rangeStylesMobile: formBtnWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_BUTTON_WIDTH,
        customUnit: "px",
        property: "width",
        attributes,
    });

    const {
        styesDesktop: formBtnBorderStylesDesktop,
        styesTab: formBtnBorderStylesTab,
        styesMobile: formBtnBorderStylesMobile,
    } = generateBorderShadowStyles({
        controlName: FORM_BUTTON_BORDER,
        attributes,
    });

    // form button padding
    const {
        dimensionStylesDesktop: formBtnPaddingDesktop,
        dimensionStylesTab: formBtnPaddingTab,
        dimensionStylesMobile: formBtnPaddingMobile,
    } = generateDimensionsControlStyles({
        controlName: FORM_BUTTON_PADDING,
        styleFor: "padding",
        attributes,
    });
    // form button margin top
    const {
        rangeStylesDesktop: formBtnMarginTopDesktop,
        rangeStylesTab: formBtnMarginTopTab,
        rangeStylesMobile: formBtnMarginTopMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_BUTTON_MARGIN_TOP,
        customUnit: "px",
        property: "margin-top",
        attributes,
    });
    // form button typography
    const {
        typoStylesDesktop: formBtnTypoStylesDesktop,
        typoStylesTab: formBtnTypoStylesTab,
        typoStylesMobile: formBtnTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_form_button,
    });

    // Amount fields width
    const {
        rangeStylesDesktop: amountFieldsWidthDesktop,
        rangeStylesTab: amountFieldsWidthTab,
        rangeStylesMobile: amountFieldsWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: AMOUNT_FIELDS_WIDTH,
        customUnit: "px",
        property: "width",
        attributes,
    });

    // Amount fields height
    const {
        rangeStylesDesktop: amountFieldsHeightDesktop,
        rangeStylesTab: amountFieldsHeightTab,
        rangeStylesMobile: amountFieldsHeightMobile,
    } = generateResponsiveRangeStyles({
        controlName: AMOUNT_FIELDS_HEIGHT,
        customUnit: "px",
        property: "height",
        attributes,
    });

    // Amount fields spacing
    const {
        rangeStylesDesktop: amountFieldsSpacingDesktop,
        rangeStylesTab: amountFieldsSpacingTab,
        rangeStylesMobile: amountFieldsSpacingMobile,
    } = generateResponsiveRangeStyles({
        controlName: AMOUNT_FIELDS_SPACING,
        customUnit: "px",
        property: "margin-bottom",
        attributes,
    });

    // Amount fields border
    const {
        styesDesktop: amountFieldsBorderDesktop,
        styesTab: amountFieldsBorderTab,
        styesMobile: amountFieldsBorderMobile,
    } = generateBorderShadowStyles({
        controlName: AMOUNT_FIELDS_BORDER,
        attributes,
    });

    // Amount field typography
    const {
        typoStylesDesktop: amountFieldsTypoStylesDesktop,
        typoStylesTab: amountFieldsTypoStylesTab,
        typoStylesMobile: amountFieldsTypoStylesMobile,
    } = generateTypographyStyles({
        attributes,
        prefixConstant: typoPrefix_amount_fields,
    });

    // Payment method active border
    const {
        styesDesktop: paymentMethodBorderDesktop,
        styesTab: paymentMethodBorderTab,
        styesMobile: paymentMethodBorderMobile,
    } = generateBorderShadowStyles({
        controlName: PAYMENT_METHOD_BORDER,
        attributes,
    });

    // Payment method inactive border
    const {
        styesDesktop: paymentMethodInactiveBorderDesktop,
        styesTab: paymentMethodInactiveBorderTab,
        styesMobile: paymentMethodInactiveBorderMobile,
    } = generateBorderShadowStyles({
        controlName: PAYMENT_METHOD_INACTIVE_BORDER,
        attributes,
    });

    // Form fields icon width
    const {
        rangeStylesDesktop: formFieldsIconWidthDesktop,
        rangeStylesTab: formFieldsIconWidthTab,
        rangeStylesMobile: formFieldsIconWidthMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_ICON_WIDTH,
        customUnit: "px",
        property: "width",
        attributes,
    });

    // Form fields icon height
    const {
        rangeStylesDesktop: formFieldsIconHeightDesktop,
        rangeStylesTab: formFieldsIconHeightTab,
        rangeStylesMobile: formFieldsIconHeightMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_ICON_HEIGHT,
        customUnit: "px",
        property: "height",
        attributes,
    });

    // Form fields icon size
    const {
        rangeStylesDesktop: formFieldsIconSizeDesktop,
        rangeStylesTab: formFieldsIconSizeTab,
        rangeStylesMobile: formFieldsIconSizeMobile,
    } = generateResponsiveRangeStyles({
        controlName: FORM_FIELDS_ICON_SIZE,
        customUnit: "px",
        property: "font-size",
        attributes,
    });

    const desktopStyles = `
		.better-payment .payment-form-layout-1 .dynamic-amount-section,
        .better-payment .payment-form-layout-2 .dynamic-amount-section,
        .better-payment .payment-form-layout-3 .dynamic-amount-section {
			${wrapperBackgroundStylesDesktop}
			${wrapperMarginDesktop}
			${wrapperPaddingDesktop}
			${sidebarBorderDesktop}
		}
        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title {
            color: ${sidebarTextColor};
            ${titleTypoStylesDesktop}
            ${titleMarginDesktop}
            ${titlePaddingDesktop}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title {
            color: ${sidebarSubTextColor};
            ${subtitleTypoStylesDesktop}
            ${subtitleMarginDesktop}
            ${subtitlePaddingDesktop}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount {
            color: ${sidebarAmountTextColor};
            ${amountTextTypoStylesDesktop}
            ${amountTextMarginDesktop}
            ${amountTextPaddingDesktop}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary {
            color: ${sidebarAmountSummaryColor};
            ${amountSummaryTypoStylesDesktop}
            ${amountSummaryMarginDesktop}
            ${amountSummaryPaddingDesktop}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon {
            color: ${sidebarIconColor};
            ${iconSizeDesktop}
            ${iconMarginDesktop}
            ${iconPaddingDesktop}
        }

        .better-payment .payment-form-layout-1  .form-content-section,
        .better-payment .payment-form-layout-2 .form-content-section,
        .better-payment .payment-form-layout-3 .form-content-section {
            ${formContainerBackgroundStylesDesktop}
            ${formContainerMarginDesktop}
            ${formContainerPaddingDesktop}
            ${formContainerBorderDesktop}
        }

        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paypal-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-stripe-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paystack-bt {
            ${formBtnWidthDesktop}
            ${formBtnBorderStylesDesktop}
            ${formBtnPaddingDesktop}
            ${formBtnMarginTopDesktop}
            ${formBtnTypoStylesDesktop}
            color: ${formButtonColor} !important;
            background: ${formButtonBackground} !important;
        }

        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paypal-bt:hover,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-stripe-bt:hover,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paystack-bt:hover {
            color: ${formButtonHoverColor} !important;
            background: ${formButtonHoverBackground} !important;
            border-color: ${formButtonHoverBorderColor} !important;
        }

        .better-payment .payment-form-layout .bp-payment-amount-wrap label {
            ${amountFieldsWidthDesktop}
            ${amountFieldsHeightDesktop}
            ${amountFieldsSpacingDesktop}
            ${amountFieldsBorderDesktop}
            ${amountFieldsTypoStylesDesktop}
            overflow-wrap: normal !important;
        }
        .better-payment .payment-form-layout .bp-payment-amount-wrap .bp-form__group input[type=radio].bp-form__control:checked~label {
            color: ${amountFieldsSelectedColor} !important;
            background-color: ${amountFieldsSelectedBgColor} !important;
            border-color: #252aff;
        }
        .better-payment .payment-form-layout .bp-payment-amount-wrap .bp-form__group input[type=radio].bp-form__control {
            color: ${amountFieldsColor} !important;
            background-color: ${amountFieldsBgColor} !important;
        }

        .better-payment .form-content-section .form-content-section-fields input[type="text"],
        .better-payment .form-content-section .form-content-section-fields input[type="email"],
        .better-payment .form-content-section .form-content-section-fields input[type="number"] {
            ${formFieldsSpacingDesktop}
            ${formFieldsBorderDesktop}
            ${formFieldsTypoStylesDesktop}
            color: ${formFieldsColor} !important;
            background-color: ${formFieldsBgColor} !important;
            ${formFieldsInputWidthDesktop}
            ${formFieldsInputHeightDesktop}
            ${formFieldsTextIndentDesktop}
            ${formFieldsPaddingDesktop}
        }
        .better-payment .form-content-section .form-content-section-fields input::placeholder {
            color: ${formFieldsPlaceholderColor} !important;
        }

        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox.active {
            ${paymentMethodBorderDesktop}
        }
        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox {
            ${paymentMethodInactiveBorderDesktop}
        }

        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-user:before {
            color: ${formFieldsIconColor} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-user {
            ${formFieldsIconSizeDesktop} !important;
            ${formFieldsIconWidthDesktop} !important;
            ${formFieldsIconHeightDesktop} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-envelope:before {
            color: ${formFieldsIconColor} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-envelope {
            color: ${formFieldsIconColor} !important;
            ${formFieldsIconSizeDesktop} !important;
            ${formFieldsIconWidthDesktop} !important;
            ${formFieldsIconHeightDesktop} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon .bp-currency-symbol {
            color: ${formFieldsIconColor} !important;
            ${formFieldsIconSizeDesktop} !important;
            ${formFieldsIconWidthDesktop} !important;
            ${formFieldsIconHeightDesktop} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.fab {
            color: ${formFieldsIconColor} !important;
            ${formFieldsIconSizeDesktop} !important;
            ${formFieldsIconWidthDesktop} !important;
            ${formFieldsIconHeightDesktop} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.dashicons {
            color: ${formFieldsIconColor} !important;
            ${formFieldsIconSizeDesktop} !important;
            ${formFieldsIconWidthDesktop} !important;
            ${formFieldsIconHeightDesktop} !important;
        }
	`;

    const tabStyles = `
		.better-payment .payment-form-layout-1 .dynamic-amount-section,
        .better-payment .payment-form-layout-2 .dynamic-amount-section,
        .better-payment .payment-form-layout-3 .dynamic-amount-section {
			${wrapperBackgroundStylesTab}
			${wrapperMarginTab}
			${wrapperPaddingTab}
			${sidebarBorderTab}
		}
        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title {
            ${titleTypoStylesTab}
            ${titleMarginTab}
            ${titlePaddingTab}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title {
            ${subtitleTypoStylesTab}
            ${subtitleMarginTab}
            ${subtitlePaddingTab}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount {
            ${amountTextTypoStylesTab}
            ${amountTextMarginTab}
            ${amountTextPaddingTab}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary {
            ${amountSummaryTypoStylesTab}
            ${amountSummaryMarginTab}
            ${amountSummaryPaddingTab}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon {
            ${iconSizeTab}
            ${iconMarginTab}
            ${iconPaddingTab}
        }

        .better-payment .payment-form-layout-1  .form-content-section,
        .better-payment .payment-form-layout-2 .form-content-section,
        .better-payment .payment-form-layout-3 .form-content-section {
            ${formContainerBackgroundStylesTab}
            ${formContainerMarginTab}
            ${formContainerPaddingTab}
            ${formContainerBorderTab}
        }

        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paypal-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-stripe-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paystack-bt {
            ${formBtnWidthTab}
            ${formBtnBorderStylesTab}
            ${formBtnPaddingTab}
            ${formBtnMarginTopTab}
            ${formBtnTypoStylesTab}
        }

        .better-payment .payment-form-layout .bp-payment-amount-wrap label {
            ${amountFieldsWidthTab}
            ${amountFieldsHeightTab}
            ${amountFieldsSpacingTab}
            ${amountFieldsBorderTab}
            ${amountFieldsTypoStylesTab}
        }

        .better-payment .form-content-section .form-content-section-fields input[type="text"],
        .better-payment .form-content-section .form-content-section-fields input[type="email"],
        .better-payment .form-content-section .form-content-section-fields input[type="number"] {
            ${formFieldsSpacingTab}
            ${formFieldsBorderTab}
            ${formFieldsTypoStylesTab}
            ${formFieldsInputWidthTab}
            ${formFieldsInputHeightTab}
            ${formFieldsTextIndentTab}
            ${formFieldsPaddingTab}
        }

        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox.active {
            ${paymentMethodBorderTab}
        }
        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox {
            ${paymentMethodInactiveBorderTab}
        }

        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-user {
            ${formFieldsIconSizeTab} !important;
            ${formFieldsIconWidthTab} !important;
            ${formFieldsIconHeightTab} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-envelope {
            ${formFieldsIconSizeTab} !important;
            ${formFieldsIconWidthTab} !important;
            ${formFieldsIconHeightTab} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon .bp-currency-symbol {
            ${formFieldsIconSizeTab} !important;
            ${formFieldsIconWidthTab} !important;
            ${formFieldsIconHeightTab} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.fab {
            ${formFieldsIconSizeTab} !important;
            ${formFieldsIconWidthTab} !important;
            ${formFieldsIconHeightTab} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.dashicons {
            ${formFieldsIconSizeTab} !important;
            ${formFieldsIconWidthTab} !important;
            ${formFieldsIconHeightTab} !important;
        }
	`;

    const mobileStyles = `
		.better-payment .payment-form-layout-1 .dynamic-amount-section,
        .better-payment .payment-form-layout-2 .dynamic-amount-section,
        .better-payment .payment-form-layout-3 .dynamic-amount-section {
			${wrapperBackgroundStylesMobile}
			${wrapperMarginMobile}
			${wrapperPaddingMobile}
			${sidebarBorderMobile}
		}
        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-title {
            ${titleTypoStylesMobile}
            ${titleMarginMobile}
            ${titlePaddingMobile}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-sub-title {
            ${subtitleTypoStylesMobile}
            ${subtitleMarginMobile}
            ${subtitlePaddingMobile}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount {
            ${amountTextTypoStylesMobile}
            ${amountTextMarginMobile}
            ${amountTextPaddingMobile}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-amount-summary {
            ${amountSummaryTypoStylesMobile}
            ${amountSummaryMarginMobile}
            ${amountSummaryPaddingMobile}
        }

        .better-payment .payment-form-layout-1 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-2 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon,
        .better-payment .payment-form-layout-3 .dynamic-amount-section .dynamic-amount-section-inner .bp-dynamic-amount-section-icon {
            ${iconSizeMobile}
            ${iconMarginMobile}
            ${iconPaddingMobile}
        }

        .better-payment .payment-form-layout-1  .form-content-section,
        .better-payment .payment-form-layout-2 .form-content-section,
        .better-payment .payment-form-layout-3 .form-content-section {
            ${formContainerBackgroundStylesMobile}
            ${formContainerMarginMobile}
            ${formContainerPaddingMobile}
            ${formContainerBorderMobile}
        }

        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paypal-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-stripe-bt,
        .better-payment .payment-form-layout .form-content-section  .payment__option .better-payment-paystack-bt {
            ${formBtnWidthMobile}
            ${formBtnBorderStylesMobile}
            ${formBtnPaddingMobile}
            ${formBtnMarginTopMobile}
            ${formBtnTypoStylesMobile}
        }

        .better-payment .payment-form-layout .bp-payment-amount-wrap label {
            ${amountFieldsWidthMobile}
            ${amountFieldsHeightMobile}
            ${amountFieldsSpacingMobile}
            ${amountFieldsBorderMobile}
            ${amountFieldsTypoStylesMobile}
        }

        .better-payment .form-content-section .form-content-section-fields input[type="text"],
        .better-payment .form-content-section .form-content-section-fields input[type="email"],
        .better-payment .form-content-section .form-content-section-fields input[type="number"] {
            ${formFieldsSpacingMobile}
            ${formFieldsBorderMobile}
            ${formFieldsTypoStylesMobile}
            ${formFieldsInputWidthMobile}
            ${formFieldsInputHeightMobile}
            ${formFieldsTextIndentMobile}
            ${formFieldsPaddingMobile}
        }

        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox.active {
            ${paymentMethodBorderMobile}
        }
        .better-payment .payment-form-layout-3 .field-payment_method label.payment-method-checkbox {
            ${paymentMethodInactiveBorderMobile}
        }

        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-user {
            ${formFieldsIconSizeMobile} !important;
            ${formFieldsIconWidthMobile} !important;
            ${formFieldsIconHeightMobile} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left i.bp-icon.bp-envelope {
            ${formFieldsIconSizeMobile} !important;
            ${formFieldsIconWidthMobile} !important;
            ${formFieldsIconHeightMobile} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon .bp-currency-symbol {
            ${formFieldsIconSizeMobile} !important;
            ${formFieldsIconWidthMobile} !important;
            ${formFieldsIconHeightMobile} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.fab {
            ${formFieldsIconSizeMobile} !important;
            ${formFieldsIconWidthMobile} !important;
            ${formFieldsIconHeightMobile} !important;
        }
        .better-payment .form-content-section .form-content-section-fields .has-icons-left .icon i.dashicons {
            ${formFieldsIconSizeMobile} !important;
            ${formFieldsIconWidthMobile} !important;
            ${formFieldsIconHeightMobile} !important;
        }
	`;

    // const wrapperClass = 'eb-button-wrapper';
    // const {btnDesktopStyle, btnTabStyle, btnMobileStyle} = EBButton.Style(
    //     blockId,
    //     wrapperClass,
    //     {},
    //     '',
    //     '',
    //     typoPrefix_text,
    //     BUTTON_BACKGROUND,
    //     BUTTON_BORDER,
    //     BUTTON_PADDING
    // );

    // Scope all CSS to this block instance so multiple blocks on the same page
    // don't share styles. Replaces .better-payment with .better-payment.bp-<blockId>
    // (compound selector — both classes on the same root element).
    // The "bp-" prefix is required because UUIDs can start with a digit, which
    // produces an invalid CSS class selector and silently drops all rules.
    const scopeCSS = (css) => blockId
        ? css.replace(/\.better-payment /g, `.better-payment.bp-${blockId} `)
        : css;

    // all css styles for large screen width (desktop/laptop) in strings ⬇
    const desktopAllStyles = softMinifyCssStrings(scopeCSS(desktopStyles));

    // all css styles for Tab in strings ⬇
    const tabAllStyles = softMinifyCssStrings(scopeCSS(tabStyles));

    // all css styles for Mobile in strings ⬇
    const mobileAllStyles = softMinifyCssStrings(scopeCSS(mobileStyles));

    return (
        <>
            <StyleComponent
                attributes={attributes}
                setAttributes={setAttributes}
                desktopAllStyles={desktopAllStyles}
                tabAllStyles={tabAllStyles}
                mobileAllStyles={mobileAllStyles}
                blockName={name}
            />
        </>
    );
}
