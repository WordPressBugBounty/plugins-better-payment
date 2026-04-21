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
import { __ } from "@wordpress/i18n";
import {
    typoPrefix_sidebar_title,
    typoPrefix_sidebar_sub_title,
    typoPrefix_sidebar_amount_text,
    typoPrefix_sidebar_amount_summary,
    typoPrefix_form_fields,
    typoPrefix_amount_fields,
    typoPrefix_form_button
} from "./typographyConstant";
import {
    generateBackgroundAttributes,
    generateDimensionsAttributes,
    generateBorderShadowAttributes,
    generateTypographyAttributes,
    generateResponsiveRangeAttributes,
} from "../../../../controls/src";
/**
 * Block attributes for the Payment Form block.
 *
 * These attributes define the data structure for the block
 * and are used by both the editor and the save/render components.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

const attributes = {
    blockRoot: {
        type: "string",
        default: "better_payment",
    }, 
    // blockMeta is for keeping all the styles :arrow_down:
    blockMeta: {
        type: "object",
    },
    align: {
        type: 'string',
    },
    initialSettingsApplied: {
        type: 'boolean',
        default: false
    },
    // Global Settings Attributes
    paypalEnabled: {
        type: 'boolean',
        default: true
    },
    paypalBusinessEmail: {
        type: 'string'
    },
    paypalLiveMode: {
        type: 'boolean'
    },
    stripeEnabled: {
        type: 'boolean',
        default: true
    },
    stripeLiveMode: {
        type: 'boolean'
    },
    stripeTestPublicKey: {
        type: 'string'
    },
    stripeTestSecretKey: {
        type: 'string'
    },
    stripeLivePublicKey: {
        type: 'string'
    },
    stripeLiveSecretKey: {
        type: 'string'
    },
    paystackEnabled: {
        type: 'boolean'
    },
    paystackLiveMode: {
        type: 'boolean'
    },
    paystackTestPublicKey: {
        type: 'string'
    },
    paystackTestSecretKey: {
        type: 'string'
    },
    paystackLivePublicKey: {
        type: 'string'
    },
    paystackLiveSecretKey: {
        type: 'string'
    },
    currency: {
        type: 'string'
    },
    emailNotificationEnabled: {
        type: 'boolean',
        default: true
    },

    // Block Specific Attributes
    blockId: {
        type: 'string'
    },
    paymentSource: {
        type: 'string',
        default: 'manual'
    },
    showSidebar: {
        type: 'boolean',
        default: true
    },
    formName: {
        type: 'string',
        default: 'Better Payment'
    },
    formLayout: {
        type: 'string',
        default: 'layout-1'
    },
    iconColor: {
        type: 'string'
    },
    currencyAlign: {
        type: 'string',
        default: 'left'
    },
    showAmountList: {
        type: 'boolean',
        default: false
    },
    formFields: {
        type: 'array',
        default: [
            {
                label: 'First Name',
                placeholder: 'First Name',
                type: 'text',
                icon: 'bp-icon bp-user',
                primaryFieldType: 'primary_first_name',
                required: false,
                show: true,
                displayInline: false,
                fieldWidth: ''
            },
            {
                label: 'Last Name',
                placeholder: 'Last Name',
                type: 'text',
                icon: 'bp-icon bp-user',
                primaryFieldType: 'primary_last_name',
                required: false,
                show: true,
                displayInline: false,
                fieldWidth: ''
            },
            {
                label: 'Email',
                placeholder: 'Email Address',
                type: 'email',
                icon: 'bp-icon bp-envelope',
                primaryFieldType: 'primary_email',
                required: true,
                show: true,
                displayInline: false,
                fieldWidth: ''
            },
            {
                label: 'Amount',
                placeholder: 'Payment Amount',
                type: 'number',
                icon: 'bp-icon bp-logo-2',
                primaryFieldType: 'primary_payment_amount',
                required: true,
                show: true,
                displayInline: false,
                fieldWidth: ''
            }
        ]
    },
    amountList: {
        type: 'array',
        default: [
            {
                label: '5'
            },
            {
                label: '10'
            },
            {
                label: '15'
            },
            {
                label: '20'
            }
        ]
    },
    transactionTitle: {
        type: 'string',
        default: 'Transaction Details'
    },
    transactionSubTitle: {
        type: 'string',
        default: 'Total payment of your product in the following:'
    },
    amountText: {
        type: 'string',
        default: 'Amount:'
    },
    paypalButtonText: {
        type: 'string',
        default: ''
    },
    stripeButtonText: {
        type: 'string',
        default: ''
    },
    paystackButtonText: {
        type: 'string',
        default: ''
    },
    errorHeading: {
        type: 'string',
        default: 'Payment Failed'
    },
    errorSubHeading: {
        type: 'string',
        default: 'Your payment has failed. Please check your payment details'
    },
    transactionIDText: {
        type: 'string',
        default: 'Transaction ID:'
    },
    showDetailsButton: {
        type: 'boolean',
        default: false
    },
    detailsButtonText: {
        type: 'string',
        default: 'View Details'
    },
    detailsButtonUrl: {
        type: 'string',
        default: ''
    },
    errorIcon: {
        type: 'string',
        default: ''
    },
    errorRedirectUrl: {
        type: 'string',
        default: ''
    },
    successIcon: {
        type: 'string',
        default: ''
    },
    successHeading: {
        type: 'string',
        default: 'Payment Successful'
    },
    successSubHeading: {
        type: 'string',
        default: 'Thank you for your payment. Your transaction was successful.'
    },
    transactionID: {
        type: 'string',
        default: 'Transaction ID:'
    },
    thanksMessage: {
        type: 'string',
        default: 'Thank you for your payment.'
    },
    amountMessage: {
        type: 'string',
        default: 'Amount'
    },
    currencyMessage: {
        type: 'string',
        default: 'Currency'
    },
    paymentMethodMessage: {
        type: 'string',
        default: 'Payment Method'
    },
    paymentType: {
        type: 'string',
        default: 'Payment Type'
    },
    merchatDetails: {
        type: 'string',
        default: 'Merchant Details'
    },
    paidAmount: {
        type: 'string',
        default: 'Paid Amount'
    },
    purchaseDetails: {
        type: 'string',
        default: 'Purchase Details'
    },
    printButtonText: {
        type: 'string',
        default: 'Print'
    },
    viewDetailsButtonText: {
        type: 'string',
        default: 'View Details'
    },
    userDashboardUrl: {
        type: 'string',
        default: ''
    },
    customRedirectUrl: {
        type: 'string',
        default: ''
    },
    emailIcon: {
        type: 'string',
        default: 'bp-icon bp-envelope'
    },
    woocommerceProductId: {
        type: 'string',
        default: ''
    },
    fluentcartProductId: {
        type: 'string',
        default: ''
    },
    productName: {
        type: 'string',
        default: ''
    },
    productPrice: {
        type: 'string',
        default: ''
    },
    productPermalink: {
        type: 'string',
        default: ''
    },
    transactionDetailsProductTitle: {
        type: 'string',
        default: 'Title:'
    },
    emailSettingsOption: {
        type: 'string',
        default: 'admin'
    },
    emailTabs: {
        type: 'array',
        default: [
            {
                label: 'Admin',
                value: 'admin',
                active: true
            },
            {
                label: 'Customer',
                value: 'customer',
                active: false
            }
        ]
    },
    adminEmail: {
        type: 'string',
        default: ''
    },
    adminSubject: {
        type: 'string',
        default: ''
    },
    adminMessage: {
        type: 'string',
        default: ''
    },
    adminShowHeaderText: {
        type: 'boolean',
        default: true
    },
    adminShowFromSection: {
        type: 'boolean',
        default: true
    },
    adminShowToSection: {
        type: 'boolean',
        default: true
    },
    adminShowTransactionSummary: {
        type: 'boolean',
        default: true
    },
    adminShowFooterText: {
        type: 'boolean',
        default: true
    },
    adminFromEmail: {
        type: 'string',
        default: ''
    },
    adminFromName: {
        type: 'string',
        default: ''
    },
    adminReplyTo: {
        type: 'string',
        default: ''
    },
    adminCc: {
        type: 'string',
        default: ''
    },
    adminBcc: {
        type: 'string',
        default: ''
    },
    adminSendAs: {
        type: 'string',
        default: 'html'
    },
    customerSubject: {
        type: 'string',
        default: ''
    },
    customerMessage: {
        type: 'string',
        default: ''
    },
    customerPDFAttachment: {
        type: 'boolean',
        default: false
    },
    customerFileAttachment: {
        type: 'string',
        default: ''
    },
    customerFromEmail: {
        type: 'string',
        default: ''
    },
    customerFromName: {
        type: 'string',
        default: ''
    },
    customerReplyTo: {
        type: 'string',
        default: ''
    },
    customerCc: {
        type: 'string',
        default: ''
    },
    customerBcc: {
        type: 'string',
        default: ''
    },
    customerSendAs: {
        type: 'string',
        default: 'html'
    },
    stripeDefaultPriceId: {
        type: 'string',
        default: ''
    },
    
    sidebarTextColor: {
        type: 'string',
        default: ''
    },
    sidebarSubTextColor: {
        type: 'string',
        default: ''
    },
    sidebarAmountTextColor: {
        type: 'string',
        default: ''
    },
    sidebarAmountSummaryColor: {
        type: 'string',
        default: ''
    },
    sidebarIconColor: {
        type: 'string',
        default: ''
    },
    paymentMethodStatuses: {
        type: 'array',
        default: [
            {
                label: 'Active',
                value: 'active',
                active: true
            },
            {
                label: 'Inactive',
                value: 'inactive',
                active: false
            }
        ]
    },
    paymentMethodStatus: {
        type: 'string',
        default: 'active'
    },
    formFieldsBgColor: {
        type: 'string',
        default: ''
    },
    formFieldsColor: {
        type: 'string',
        default: ''
    },
    formFieldsIconColor: {
        type: 'string',
        default: ''
    },
    formFieldsPlaceholderColor: {
        type: 'string',
        default: ''
    },
    amountStatuses: {
        type: 'array',
        default: [
            {
                label: 'Normal',
                value: 'normal',
                active: true
            },
            {
                label: 'Selected',
                value: 'selected',
                active: false
            }
        ]
    },
    amountStatus: {
        type: 'string',
        default: 'normal'
    },
    amountFieldsBgColor: {
        type: 'string',
        default: ''
    },
    amountFieldsColor: {
        type: 'string',
        default: ''
    },
    amountFieldsSelectedBgColor: {
        type: 'string',
        default: ''
    },
    amountFieldsSelectedColor: {
        type: 'string',
        default: ''
    },
    formButtonBackground: {
        type: 'string',
        default: ''
    },
    formButtonColor: {
        type: 'string',
        default: ''
    },
    formButtonStatuses: {
        type: 'array',
        default: [
            {
                label: 'Normal',
                value: 'normal',
                active: true
            },
            {
                label: 'Hover',
                value: 'hover',
                active: false
            }
        ]
    },
    formButtonStatus: {
        type: 'string',
        default: 'normal'
    },
    formButtonHoverBackground: {
        type: 'string',
        default: ''
    },
    formButtonHoverColor: {
        type: 'string',
        default: ''
    },
    formButtonHoverBorderColor: {
        type: 'string',
        default: ''
    },
    ...generateBackgroundAttributes(SIDEBAR_BACKGROUND, {
        defaultFillColor: '',
    }),
    ...generateDimensionsAttributes(SIDEBAR_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_PADDING),
    ...generateBorderShadowAttributes(SIDEBAR_BORDER),
    ...generateDimensionsAttributes(SIDEBAR_TITLE_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_TITLE_PADDING),
    ...generateDimensionsAttributes(SIDEBAR_SUBTITLE_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_SUBTITLE_PADDING),
    ...generateDimensionsAttributes(SIDEBAR_AMOUNT_TEXT_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_AMOUNT_TEXT_PADDING),
    ...generateDimensionsAttributes(SIDEBAR_AMOUNT_SUMMARY_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_AMOUNT_SUMMARY_PADDING),
    ...generateTypographyAttributes(typoPrefix_sidebar_title),
    ...generateTypographyAttributes(typoPrefix_sidebar_sub_title),
    ...generateTypographyAttributes(typoPrefix_sidebar_amount_text),
    ...generateTypographyAttributes(typoPrefix_sidebar_amount_summary),
    ...generateDimensionsAttributes(SIDEBAR_ICON_MARGIN),
    ...generateDimensionsAttributes(SIDEBAR_ICON_PADDING),
    ...generateBackgroundAttributes(FORM_CONTAINER_BACKGROUND, {
        defaultFillColor: '',
    }),
    ...generateDimensionsAttributes(FORM_CONTAINER_MARGIN),
    ...generateDimensionsAttributes(FORM_CONTAINER_PADDING),
    ...generateBorderShadowAttributes(FORM_CONTAINER_BORDER),
    ...generateDimensionsAttributes(FORM_FIELDS_PADDING),
    ...generateBorderShadowAttributes(FORM_FIELDS_BORDER),
    ...generateBorderShadowAttributes(PAYMENT_METHOD_BORDER),
    ...generateBorderShadowAttributes(PAYMENT_METHOD_INACTIVE_BORDER),
    ...generateTypographyAttributes(typoPrefix_form_fields),
    ...generateBorderShadowAttributes(AMOUNT_FIELDS_BORDER),
    ...generateTypographyAttributes(typoPrefix_amount_fields),
    ...generateDimensionsAttributes(FORM_BUTTON_PADDING),
    ...generateBorderShadowAttributes(FORM_BUTTON_BORDER),
    ...generateTypographyAttributes(typoPrefix_form_button),
    ...generateResponsiveRangeAttributes(FORM_BUTTON_MARGIN_TOP, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(AMOUNT_FIELDS_WIDTH, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(AMOUNT_FIELDS_HEIGHT, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(AMOUNT_FIELDS_SPACING, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(SIDEBAR_ICON_SIZE, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_SPACING, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_TEXT_INDENT, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_INPUT_WIDTH, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_INPUT_HEIGHT, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_BUTTON_WIDTH, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_ICON_SIZE, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_ICON_WIDTH, {
        defaultRange: '',
    }),
    ...generateResponsiveRangeAttributes(FORM_FIELDS_ICON_HEIGHT, {
        defaultRange: '',
    })
};

export default attributes;
