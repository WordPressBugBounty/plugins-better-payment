/**
 * Inspector controls for the Payment Form block.
 *
 * This component renders the block settings in the sidebar
 * when the block is selected in the editor.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

import { __ } from "@wordpress/i18n";
import {
  MediaUpload,
  InspectorControls,
} from "@wordpress/block-editor";
import { useState, useEffect, useRef } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import {
  PanelRow,
  TextControl,
  TextareaControl,
  SelectControl,
  ToggleControl,
  RangeControl,
  ComboboxControl,
  __experimentalDivider as Divider,
  ButtonGroup,
  Button,
  BaseControl,
} from "@wordpress/components";

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
  SIDEBAR_MARGIN,
  SIDEBAR_PADDING,
  SIDEBAR_BORDER,
  SIDEBAR_BACKGROUND,
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
  WRAPPER_MARGIN,
} from "./constants";

/**
 * Inspector component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set block attributes.
 * @returns {JSX.Element} The inspector component.
 */
const Inspector = ({ attributes, setAttributes }) => {
  const {
    formLayout,
    paymentSource,
    currencyAlign,
    align,
    paypalEnabled,
    paypalBusinessEmail,
    paypalLiveMode,
    stripeEnabled,
    stripeLiveMode,
    paystackEnabled,
    paystackLiveMode,
    emailNotificationEnabled,
    showSidebar,
    currency,
    formName,
    showAmountList,
    formFields,
    amountList,
    transactionTitle,
    transactionSubTitle,
    amountText,
    paypalButtonText,
    stripeButtonText,
    paystackButtonText,
    errorHeading,
    errorSubHeading,
    transactionIDText,
    showDetailsButton,
    detailsButtonText,
    detailsButtonUrl,
    errorIcon,
    errorRedirectUrl,
    successHeading,
    successIcon,
    successSubHeading,
    transactionID,
    thanksMessage,
    amountMessage,
    currencyMessage,
    paymentMethodMessage,
    paymentType,
    merchatDetails,
    paidAmount,
    purchaseDetails,
    printButtonText,
    viewDetailsButtonText,
    userDashboardUrl,
    customRedirectUrl,
    emailIcon,
    woocommerceProductId,
    fluentcartProductId,
    productName,
    emailSettingsOption,
    emailTabs,
    adminEmail,
    adminSubject,
    adminMessage,
    adminShowHeaderText,
    adminShowFromSection,
    adminShowToSection,
    adminShowTransactionSummary,
    adminShowFooterText,
    adminFromEmail,
    adminFromName,
    adminReplyTo,
    adminCc,
    adminBcc,
    adminSendAs,
    customerSubject,
    customerMessage,
    customerPDFAttachment,
    customerFileAttachment,
    customerFromEmail,
    customerFromName,
    customerReplyTo,
    customerCc,
    customerBcc,
    customerSendAs,
    stripeDefaultPriceId,
    sidebarTextColor,
    sidebarSubTextColor,
    sidebarAmountTextColor,
    sidebarAmountSummaryColor,
    sidebarIconColor,
    formFieldsBgColor,
    formFieldsColor,
    formFieldsIconColor,
    formFieldsPlaceholderColor,
    paymentMethodStatuses,
    paymentMethodStatus,
    amountStatuses,
    amountStatus,
    amountFieldsBgColor,
    amountFieldsColor,
    amountFieldsSelectedBgColor,
    amountFieldsSelectedColor,
    formButtonStatuses,
    formButtonStatus,
    formButtonBackground,
    formButtonColor,
    formButtonHoverBackground,
    formButtonHoverColor,
    formButtonHoverBorderColor,
  } = attributes;

  // Available form layouts
  const layoutOptions = [
    { label: __("Layout 1", "better-payment"), value: "layout-1" },
    { label: __("Layout 2", "better-payment"), value: "layout-2" },
    { label: __("Layout 3", "better-payment"), value: "layout-3" },
  ];

  const paymentSourceOptions = [
    { label: __("Manual", "better-payment"), value: "manual" },
    { label: __("WooCommerce", "better-payment"), value: "woocommerce" },
    { label: __("FluentCart", "better-payment"), value: "fluentcart" },
    { label: __("Stripe Product", "better-payment"), value: "stripe" },
  ];

  const currencies = betterPaymentBlockData?.currencies || [];
  const currencyOptions = Object.keys(currencies).map((currencyCode) => ({
    label: `${currencyCode}`,
    value: currencyCode,
  }));

  // Product search state for WooCommerce
  const [wooProductOptions, setWooProductOptions] = useState(() => {
    if (woocommerceProductId && productName) {
      return [{ value: woocommerceProductId, label: productName }];
    }
    return [];
  });

  // Product search state for FluentCart
  const [fcProductOptions, setFcProductOptions] = useState(() => {
    if (fluentcartProductId && productName) {
      return [{ value: fluentcartProductId, label: productName }];
    }
    return [];
  });

  const wooSearchTimeout = useRef(null);
  const fcSearchTimeout = useRef(null);

  // Load initial WooCommerce products on mount
  useEffect(() => {
    if (paymentSource === "woocommerce") {
      apiFetch({ path: "/wc/v3/products?per_page=10" })
        .then((result) => {
          const options = (result || []).map((item) => ({
            value: String(item.id),
            label: item.name ? item.name.replace(/<\/?[^>]+(>|$)/g, "") : "",
          }));
          // Keep current selection in options
          if (woocommerceProductId && productName) {
            const exists = options.find(
              (o) => o.value === woocommerceProductId,
            );
            if (!exists) {
              options.unshift({
                value: woocommerceProductId,
                label: productName,
              });
            }
          }
          setWooProductOptions(options);
        })
        .catch(() => {});
    }
  }, [paymentSource]);

  // Load initial FluentCart products on mount
  useEffect(() => {
    if (paymentSource === "fluentcart") {
      apiFetch({ path: "/better-payment/v1/fluentcart-products" })
        .then((result) => {
          const options = result || [];
          // Keep current selection in options
          if (fluentcartProductId && productName) {
            const exists = options.find((o) => o.value === fluentcartProductId);
            if (!exists) {
              options.unshift({
                value: fluentcartProductId,
                label: productName,
              });
            }
          }
          setFcProductOptions(options);
        })
        .catch(() => {});
    }
  }, [paymentSource]);

  // Set default values for email notification settings from global settings
  useEffect(() => {
    const globalSettings = betterPaymentBlockData?.betterPaymentSettings || {};
    const siteAdminEmail = betterPaymentBlockData?.adminEmail || "";
    const siteName = betterPaymentBlockData?.siteName || "";
    const siteDomain = betterPaymentBlockData?.siteDomain || "example.com";
    const defaultFromEmail = `wordpress@${siteDomain}`;
    const defaultSubject = siteName
      ? `Better Payment transaction on ${siteName}`
      : "Better Payment transaction";

    const defaultsToSet = {};

    // Admin email defaults
    if (!adminEmail) {
      defaultsToSet.adminEmail =
        globalSettings?.better_payment_settings_general_email_to ||
        siteAdminEmail;
    }
    if (!adminSubject) {
      defaultsToSet.adminSubject =
        globalSettings?.better_payment_settings_general_email_subject ||
        defaultSubject;
    }
    if (!adminMessage) {
      defaultsToSet.adminMessage =
        globalSettings?.better_payment_settings_general_email_message_admin ||
        "";
    }
    if (!adminFromEmail) {
      defaultsToSet.adminFromEmail =
        globalSettings?.better_payment_settings_general_email_from_email ||
        defaultFromEmail;
    }
    if (!adminFromName) {
      defaultsToSet.adminFromName =
        globalSettings?.better_payment_settings_general_email_from_name ||
        siteName;
    }
    if (!adminReplyTo) {
      defaultsToSet.adminReplyTo =
        globalSettings?.better_payment_settings_general_email_reply_to ||
        defaultFromEmail;
    }
    if (!adminCc) {
      defaultsToSet.adminCc =
        globalSettings?.better_payment_settings_general_email_cc || "";
    }
    if (!adminBcc) {
      defaultsToSet.adminBcc =
        globalSettings?.better_payment_settings_general_email_bcc || "";
    }
    if (!adminSendAs) {
      defaultsToSet.adminSendAs =
        globalSettings?.better_payment_settings_general_email_send_as || "html";
    }

    // Customer email defaults
    if (!customerSubject) {
      defaultsToSet.customerSubject =
        globalSettings?.better_payment_settings_general_email_subject_customer ||
        defaultSubject;
    }
    if (!customerMessage) {
      defaultsToSet.customerMessage =
        globalSettings?.better_payment_settings_general_email_message_customer ||
        "";
    }
    if (!customerFromEmail) {
      defaultsToSet.customerFromEmail =
        globalSettings?.better_payment_settings_general_email_from_email_customer ||
        defaultFromEmail;
    }
    if (!customerFromName) {
      defaultsToSet.customerFromName =
        globalSettings?.better_payment_settings_general_email_from_name_customer ||
        siteName;
    }
    if (!customerReplyTo) {
      defaultsToSet.customerReplyTo =
        globalSettings?.better_payment_settings_general_email_reply_to_customer ||
        defaultFromEmail;
    }
    if (!customerCc) {
      defaultsToSet.customerCc =
        globalSettings?.better_payment_settings_general_email_cc_customer || "";
    }
    if (!customerBcc) {
      defaultsToSet.customerBcc =
        globalSettings?.better_payment_settings_general_email_bcc_customer ||
        "";
    }
    if (!customerSendAs) {
      defaultsToSet.customerSendAs =
        globalSettings?.better_payment_settings_general_email_send_as_customer ||
        "html";
    }

    // Only call setAttributes if there are defaults to set
    if (Object.keys(defaultsToSet).length > 0) {
      setAttributes(defaultsToSet);
    }
  }, []); // Run once on mount

  // WooCommerce product search handler
  const onWooFilterValueChange = (inputValue) => {
    clearTimeout(wooSearchTimeout.current);
    if (!inputValue || inputValue.length < 3) {
      return;
    }
    wooSearchTimeout.current = setTimeout(() => {
      apiFetch({
        path: `/wc/v3/products?search=${encodeURIComponent(
          inputValue,
        )}&per_page=10`,
      })
        .then((result) => {
          const options = (result || []).map((item) => ({
            value: String(item.id),
            label: item.name ? item.name.replace(/<\/?[^>]+(>|$)/g, "") : "",
          }));
          // Keep current selection in options
          if (woocommerceProductId && productName) {
            const exists = options.find(
              (o) => o.value === woocommerceProductId,
            );
            if (!exists) {
              options.unshift({
                value: woocommerceProductId,
                label: productName,
              });
            }
          }
          setWooProductOptions(options);
        })
        .catch(() => {});
    }, 300);
  };

  // FluentCart product search handler
  const onFcFilterValueChange = (inputValue) => {
    clearTimeout(fcSearchTimeout.current);
    if (!inputValue || inputValue.length < 3) {
      return;
    }
    fcSearchTimeout.current = setTimeout(() => {
      apiFetch({
        path: `/better-payment/v1/fluentcart-products?search=${encodeURIComponent(
          inputValue,
        )}`,
      })
        .then((result) => {
          const options = result || [];
          // Keep current selection in options
          if (fluentcartProductId && productName) {
            const exists = options.find((o) => o.value === fluentcartProductId);
            if (!exists) {
              options.unshift({
                value: fluentcartProductId,
                label: productName,
              });
            }
          }
          setFcProductOptions(options);
        })
        .catch(() => {});
    }, 300);
  };

  const onFormFieldAdd = () => {
    const newFormFields = [
      ...formFields,
      {
        label: "Field Name",
        placeholder: "Field Name",
        type: "text",
        icon: "bp-icon bp-user",
        primaryFieldType: "primary_none",
        required: false,
        show: true,
        displayInline: false,
        fieldWidth: "",
      },
    ];

    setAttributes({ formFields: newFormFields });
  };

  const getFormFieldComponents = () => {
    const onFormFieldChange = (key, value, position) => {
      const newFormField = { ...formFields[position] };
      const newFormFields = [...formFields];
      newFormFields[position] = newFormField;

      if (Array.isArray(key)) {
        key.map((item, index) => {
          newFormFields[position][item] = value[index];
        });
      } else {
        newFormFields[position][key] = value;
      }

      setAttributes({ formFields: newFormFields });
    };

    return formFields.map((each, i) => (
      <div key={i}>
        <EBTextControl
          label={each.label}
          value={each.label}
          onChange={(value) => onFormFieldChange("label", value, i)}
          enableAi={false}
        />
        <EBTextControl
          label={__("Placeholder", "better-payment")}
          value={each.placeholder}
          onChange={(value) => onFormFieldChange("placeholder", value, i)}
          enableAi={false}
        />
        <SelectControl
          label={__("Field Type", "better-payment")}
          value={each.primaryFieldType}
          options={[
            { label: __("Text", "better-payment"), value: "text" },
            { label: __("Email", "better-payment"), value: "email" },
            { label: __("Number", "better-payment"), value: "number" },
          ]}
          onChange={(value) => onFormFieldChange("primaryFieldType", value, i)}
        />
        <SelectControl
          label={__("Primary Field Type", "better-payment")}
          value={each.primaryFieldType}
          options={[
            { label: __("None", "better-payment"), value: "primary_none" },
            {
              label: __("First Name", "better-payment"),
              value: "primary_first_name",
            },
            {
              label: __("Last Name", "better-payment"),
              value: "primary_last_name",
            },
            { label: __("Email", "better-payment"), value: "primary_email" },
            {
              label: __("Payment Amount", "better-payment"),
              value: "primary_payment_amount",
            },
            {
              label: __("Coupon Code", "better-payment"),
              value: "primary_coupon_code",
            },
            {
              label: __("Reference Number", "better-payment"),
              value: "primary_reference_number",
            },
          ]}
          onChange={(value) => onFormFieldChange("primaryFieldType", value, i)}
          help={__(
            "If this is a primary field (first name, last name, email etc), then please select one.",
            "better-payment",
          )}
        />
        <EBIconPicker
          value={each.icon}
          onChange={(icon) => onFormFieldChange("icon", icon, i)}
        />
        <ToggleControl
          label={__("Required", "better-payment")}
          checked={each.required}
          onChange={(value) => onFormFieldChange("required", value, i)}
        />
        <ToggleControl
          label={__("Show", "better-payment")}
          checked={each.show}
          onChange={(value) => onFormFieldChange("show", value, i)}
        />
        <ToggleControl
          label={__("Display Inline?", "better-payment")}
          checked={each.displayInline}
          onChange={(value) => onFormFieldChange("displayInline", value, i)}
        />
        {each.displayInline && (
          <RangeControl
            label={__("Field Width", "better-payment")}
            value={each.fieldWidth}
            min={0}
            max={100}
            step={1}
            onChange={(value) => onFormFieldChange("fieldWidth", value, i)}
            help={__(
              "Set the width of the field in percentage.",
              "better-payment",
            )}
          />
        )}
      </div>
    ));
  };

  const onAmountListAdd = () => {
    const newAmountList = [
      ...amountList,
      {
        label: "",
      },
    ];

    setAttributes({ amountList: newAmountList });
  };

  const getAmountListComponents = () => {
    const onAmountListChange = (key, value, position) => {
      const newAmountList = { ...amountList[position] };
      const newAmountListItems = [...amountList];
      newAmountListItems[position] = newAmountList;

      if (Array.isArray(key)) {
        key.map((item, index) => {
          newAmountListItems[position][item] = value[index];
        });
      } else {
        newAmountListItems[position][key] = value;
      }

      setAttributes({ amountList: newAmountListItems });
    };

    return amountList.map((each, i) => (
      <div key={i}>
        <EBTextControl
          label={__("Amount", "better-payment")}
          value={each.label}
          onChange={(label) => onAmountListChange("label", label, i)}
          enableAi={false}
        />
      </div>
    ));
  };

  return (
    <InspectorPanel hideTabs={["advanced"]}>
      <InspectorPanel.General>
        <InspectorPanel.PanelBody
          title={__("Payment Settings", "better-payment")}
          initialOpen={true}
        >
          <SelectControl
            label={__("Payment Layout", "better-payment")}
            value={formLayout}
            options={layoutOptions}
            onChange={(newSource) => setAttributes({ formLayout: newSource })}
          />
          <SelectControl
            label={__("Payment Source", "better-payment")}
            value={paymentSource}
            options={paymentSourceOptions}
            onChange={(newSource) =>
              setAttributes({ paymentSource: newSource })
            }
          />
          {paymentSource === "woocommerce" && (
            <ComboboxControl
              label={__("Select WooCommerce Product", "better-payment")}
              value={woocommerceProductId || null}
              options={wooProductOptions}
              onChange={(value) => {
                setAttributes({ woocommerceProductId: value || "" });
              }}
              onFilterValueChange={onWooFilterValueChange}
              help={__(
                "Type at least 3 characters to search products",
                "better-payment",
              )}
            />
          )}
          {paymentSource === "fluentcart" && (
            <ComboboxControl
              label={__("Select FluentCart Product", "better-payment")}
              value={fluentcartProductId || null}
              options={fcProductOptions}
              onChange={(value) => {
                setAttributes({ fluentcartProductId: value || "" });
              }}
              onFilterValueChange={onFcFilterValueChange}
              help={__(
                "Type at least 3 characters to search products",
                "better-payment",
              )}
            />
          )}
          {paymentSource === "stripe" && (
            <EBTextControl
              label={__("Default Price ID", "better-payment")}
              value={stripeDefaultPriceId}
              onChange={(value) =>
                setAttributes({ stripeDefaultPriceId: value })
              }
              enableAi={false}
              help={__(
                "Create a product from Stripe dashboard and get the (default) price id. For coupons, make sure they are global or product independent.",
                "better-payment",
              )}
            />
          )}
          <ToggleControl
            label={__("Enable PayPal", "better-payment")}
            checked={paypalEnabled}
            onChange={(newVal) => setAttributes({ paypalEnabled: newVal })}
          />
          <ToggleControl
            label={__("Enable Stripe", "better-payment")}
            checked={stripeEnabled}
            onChange={(newVal) => setAttributes({ stripeEnabled: newVal })}
          />
          <ToggleControl
            label={__("Enable Paystack", "better-payment")}
            checked={paystackEnabled}
            onChange={(newVal) => setAttributes({ paystackEnabled: newVal })}
          />
          <ToggleControl
            label={__("Enable Email Notification", "better-payment")}
            checked={emailNotificationEnabled}
            onChange={(newVal) =>
              setAttributes({ emailNotificationEnabled: newVal })
            }
          />
          <ToggleControl
            label={__("Show Sidebar", "better-payment")}
            checked={showSidebar}
            onChange={(newVal) => setAttributes({ showSidebar: newVal })}
          />
          <SelectControl
            label={__("Currency", "better-payment")}
            value={currency}
            options={currencyOptions}
            onChange={(newVal) => setAttributes({ currency: newVal })}
          />
          <ButtonGroup label={__("Currency Alignment", "better-payment")}>
            <Button
              isPrimary={currencyAlign === "left"}
              onClick={() => setAttributes({ currencyAlign: "left" })}
            >
              {__("Left", "better-payment")}
            </Button>
            <Button
              isPrimary={currencyAlign === "right"}
              onClick={() => setAttributes({ currencyAlign: "right" })}
            >
              {__("Right", "better-payment")}
            </Button>
          </ButtonGroup>
        </InspectorPanel.PanelBody>

        {(paypalEnabled || stripeEnabled || paystackEnabled) && (
          <InspectorPanel.PanelBody
            title={__("Form Settings", "better-payment")}
            initialOpen={false}
          >
            <TextControl
              label={__("Form Name", "better-payment")}
              value={formName}
              onChange={(newName) => setAttributes({ formName: newName })}
            />
            <SortControl
              items={formFields}
              labelKey={"label"}
              onSortEnd={(formFields) =>
                setAttributes({ formFields: formFields })
              }
              onDeleteItem={(index) => {
                setAttributes({
                  formFields: attributes.formFields.filter(
                    (each, i) => i !== index,
                  ),
                });
              }}
              hasSettings={true}
              settingsComponents={getFormFieldComponents()}
              hasAddButton={true}
              onAddItem={onFormFieldAdd}
              addButtonText={__("Add Item", "better-payment")}
            />
            {paymentSource === "manual" && (
              <>
                <ToggleControl
                  label={__("Show Amount List", "better-payment")}
                  checked={showAmountList}
                  onChange={(newVal) =>
                    setAttributes({ showAmountList: newVal })
                  }
                />
                {showAmountList && (
                  <SortControl
                    items={amountList}
                    labelKey={"label"}
                    onSortEnd={(amountList) =>
                      setAttributes({ amountList: amountList })
                    }
                    onDeleteItem={(index) => {
                      setAttributes({
                        amountList: attributes.amountList.filter(
                          (each, i) => i !== index,
                        ),
                      });
                    }}
                    hasSettings={true}
                    settingsComponents={getAmountListComponents()}
                    hasAddButton={true}
                    onAddItem={onAmountListAdd}
                    addButtonText={__("Add Item", "better-payment")}
                  />
                )}
              </>
            )}
            <Divider />
            <PanelRow>
              <strong>Transaction Details</strong>
            </PanelRow>
            <TextControl
              label={__("Heading Text", "better-payment")}
              value={transactionTitle}
              onChange={(newTitle) =>
                setAttributes({ transactionTitle: newTitle })
              }
            />
            <TextControl
              label={__("Sub Heading Text", "better-payment")}
              value={transactionSubTitle}
              onChange={(newTitle) =>
                setAttributes({ transactionSubTitle: newTitle })
              }
            />
            <TextControl
              label={__("Amount Text", "better-payment")}
              value={amountText}
              onChange={(newTitle) => setAttributes({ amountText: newTitle })}
            />
            <Divider />
            <PanelRow>
              <strong>Form Custom Text</strong>
            </PanelRow>
            {paypalEnabled && (
              <TextControl
                label={__("PayPal Button Text", "better-payment")}
                value={paypalButtonText}
                onChange={(newTitle) =>
                  setAttributes({ paypalButtonText: newTitle })
                }
              />
            )}
            {stripeEnabled && (
              <TextControl
                label={__("Stripe Button Text", "better-payment")}
                value={stripeButtonText}
                onChange={(newTitle) =>
                  setAttributes({ stripeButtonText: newTitle })
                }
              />
            )}
            {paystackEnabled && (
              <TextControl
                label={__("Paystack Button Text", "better-payment")}
                value={paystackButtonText}
                onChange={(newTitle) =>
                  setAttributes({ paystackButtonText: newTitle })
                }
              />
            )}
          </InspectorPanel.PanelBody>
        )}

        {paypalEnabled && (
          <InspectorPanel.PanelBody
            title={__("PayPal Settings", "better-payment")}
            initialOpen={false}
          >
            <TextControl
              label={__("PayPal Business Email", "better-payment")}
              value={paypalBusinessEmail}
              onChange={(newEmail) =>
                setAttributes({ paypalBusinessEmail: newEmail })
              }
            />
            <ToggleControl
              label={__("PayPal Live Mode", "better-payment")}
              checked={paypalLiveMode}
              onChange={(newVal) => setAttributes({ paypalLiveMode: newVal })}
            />
          </InspectorPanel.PanelBody>
        )}

        {stripeEnabled && (
          <InspectorPanel.PanelBody
            title={__("Stripe Settings", "better-payment")}
            initialOpen={false}
          >
            <ToggleControl
              label={__("Stripe Live Mode", "better-payment")}
              checked={stripeLiveMode}
              onChange={(newVal) => setAttributes({ stripeLiveMode: newVal })}
            />
          </InspectorPanel.PanelBody>
        )}

        {paystackEnabled && (
          <InspectorPanel.PanelBody
            title={__("Paystack Settings", "better-payment")}
            initialOpen={false}
          >
            <ToggleControl
              label={__("Paystack Live Mode", "better-payment")}
              checked={paystackLiveMode}
              onChange={(newVal) => setAttributes({ paystackLiveMode: newVal })}
            />
          </InspectorPanel.PanelBody>
        )}

        {emailNotificationEnabled && (
          <InspectorPanel.PanelBody
            title={__("Email Settings", "better-payment")}
            initialOpen={false}
          >
            <div id="email-icon-picker-wrapper">
              <EBIconPicker
                value={emailIcon}
                onChange={(icon) => setAttributes({ emailIcon: icon })}
              />
            </div>
            <BaseControl id="eb-accordion-image-icon">
              <ButtonGroup>
                {(emailTabs || []).map(({ label, value }, index) => (
                  <Button
                    key={index}
                    isPrimary={value === emailSettingsOption}
                    onClick={() =>
                      setAttributes({ emailSettingsOption: value })
                    }
                  >
                    {label}
                  </Button>
                ))}
              </ButtonGroup>
            </BaseControl>
            {emailSettingsOption === "admin" && (
              <>
                <TextControl
                  label={__("To", "better-payment")}
                  value={adminEmail}
                  onChange={(newEmail) =>
                    setAttributes({ adminEmail: newEmail })
                  }
                />
                <TextControl
                  label={__("Subject", "better-payment")}
                  value={adminSubject}
                  onChange={(newSubject) =>
                    setAttributes({ adminSubject: newSubject })
                  }
                />
                <TextareaControl
                  label={__("Message", "better-payment")}
                  value={adminMessage}
                  onChange={(newMessage) =>
                    setAttributes({ adminMessage: newMessage })
                  }
                />
                <ToggleControl
                  label={__("Show Header Text", "better-payment")}
                  checked={adminShowHeaderText}
                  onChange={(newVal) =>
                    setAttributes({ adminShowHeaderText: newVal })
                  }
                />
                <ToggleControl
                  label={__("Show From Section", "better-payment")}
                  checked={adminShowFromSection}
                  onChange={(newVal) =>
                    setAttributes({ adminShowFromSection: newVal })
                  }
                />
                <ToggleControl
                  label={__("Show To Section", "better-payment")}
                  checked={adminShowToSection}
                  onChange={(newVal) =>
                    setAttributes({ adminShowToSection: newVal })
                  }
                />
                <ToggleControl
                  label={__("Show Transaction Summary", "better-payment")}
                  checked={adminShowTransactionSummary}
                  onChange={(newVal) =>
                    setAttributes({ adminShowTransactionSummary: newVal })
                  }
                />
                <ToggleControl
                  label={__("Show Footer Text", "better-payment")}
                  checked={adminShowFooterText}
                  onChange={(newVal) =>
                    setAttributes({ adminShowFooterText: newVal })
                  }
                />
                <Divider />
                <TextControl
                  label={__("From Email", "better-payment")}
                  value={adminFromEmail}
                  onChange={(newEmail) =>
                    setAttributes({ adminFromEmail: newEmail })
                  }
                />
                <TextControl
                  label={__("From Name", "better-payment")}
                  value={adminFromName}
                  onChange={(newName) =>
                    setAttributes({ adminFromName: newName })
                  }
                />
                <TextControl
                  label={__("Reply To", "better-payment")}
                  value={adminReplyTo}
                  onChange={(newReplyTo) =>
                    setAttributes({ adminReplyTo: newReplyTo })
                  }
                />
                <TextControl
                  label={__("Cc", "better-payment")}
                  value={adminCc}
                  onChange={(newCc) => setAttributes({ adminCc: newCc })}
                />
                <TextControl
                  label={__("Bcc", "better-payment")}
                  value={adminBcc}
                  onChange={(newBcc) => setAttributes({ adminBcc: newBcc })}
                />
                <SelectControl
                  label={__("Send As", "better-payment")}
                  value={adminSendAs}
                  options={[
                    { label: __("HTML", "better-payment"), value: "html" },
                    { label: __("Plain", "better-payment"), value: "plain" },
                  ]}
                  onChange={(newVal) => setAttributes({ adminSendAs: newVal })}
                />
              </>
            )}

            {emailSettingsOption === "customer" && (
              <>
                <TextControl
                  label={__("Subject", "better-payment")}
                  value={customerSubject}
                  onChange={(newSubject) =>
                    setAttributes({ customerSubject: newSubject })
                  }
                />
                <TextareaControl
                  label={__("Message", "better-payment")}
                  value={customerMessage}
                  onChange={(newMessage) =>
                    setAttributes({ customerMessage: newMessage })
                  }
                />
                <ToggleControl
                  label={__("PDF Attachment?", "better-payment")}
                  checked={customerPDFAttachment}
                  onChange={(newVal) =>
                    setAttributes({ customerPDFAttachment: newVal })
                  }
                />
                {customerPDFAttachment && (
                  <>
                    <p>PDF Attachment</p>
                    <MediaUpload
                      onSelect={(media) =>
                        setAttributes({ customerPDFAttachment: media.url })
                      }
                      type="application/pdf"
                      value={customerPDFAttachment}
                      help={__("Allowed file types: pdf.", "better-payment")}
                      render={({ open }) => (
                        <Button
                          onClick={open}
                          isPrimary={!!customerPDFAttachment}
                        >
                          {customerPDFAttachment
                            ? "Change File"
                            : "Upload File"}
                        </Button>
                      )}
                    />
                  </>
                )}
                {/* image attachment control */}
                {!customerPDFAttachment && (
                  <>
                    <p>Image Attachment</p>
                    <MediaUpload
                      onSelect={(media) =>
                        setAttributes({ customerFileAttachment: media.url })
                      }
                      type="image"
                      value={customerFileAttachment}
                      help={__(
                        "Allowed file types: jpg, jpeg, png.",
                        "better-payment",
                      )}
                      render={({ open }) => (
                        <Button
                          onClick={open}
                          isPrimary={!!customerFileAttachment}
                        >
                          {customerFileAttachment
                            ? "Change File"
                            : "Upload File"}
                        </Button>
                      )}
                    />
                  </>
                )}
                <Divider />
                <TextControl
                  label={__("From Email", "better-payment")}
                  value={customerFromEmail}
                  onChange={(newEmail) =>
                    setAttributes({ customerFromEmail: newEmail })
                  }
                />
                <TextControl
                  label={__("From Name", "better-payment")}
                  value={customerFromName}
                  onChange={(newName) =>
                    setAttributes({ customerFromName: newName })
                  }
                />
                <TextControl
                  label={__("Reply To", "better-payment")}
                  value={customerReplyTo}
                  onChange={(newReplyTo) =>
                    setAttributes({ customerReplyTo: newReplyTo })
                  }
                />
                <TextControl
                  label={__("Cc", "better-payment")}
                  value={customerCc}
                  onChange={(newCc) => setAttributes({ customerCc: newCc })}
                />
                <TextControl
                  label={__("Bcc", "better-payment")}
                  value={customerBcc}
                  onChange={(newBcc) => setAttributes({ customerBcc: newBcc })}
                />
                <SelectControl
                  label={__("Send As", "better-payment")}
                  value={customerSendAs}
                  options={[
                    { label: __("HTML", "better-payment"), value: "html" },
                    { label: __("Plain", "better-payment"), value: "plain" },
                  ]}
                  onChange={(newVal) =>
                    setAttributes({ customerSendAs: newVal })
                  }
                />
              </>
            )}
          </InspectorPanel.PanelBody>
        )}

        <InspectorPanel.PanelBody
          title={__("Success Message", "better-payment")}
          initialOpen={false}
        >
          <div id="success-icon-picker-wrapper">
            <EBIconPicker
              value={successIcon}
              onChange={(icon) => setAttributes({ successIcon: icon })}
            />
          </div>
          <Divider />
          <PanelRow>
            <strong>Content</strong>
          </PanelRow>
          <TextControl
            label={__("Heading", "better-payment")}
            value={successHeading}
            onChange={(newTitle) => setAttributes({ successHeading: newTitle })}
            help={__(
              "Use shortcode like [currency_symbol], [amount], [store_name] to customize your message. eg: You paid [currency_symbol][amount] to [store_name] for your order.",
              "better-payment",
            )}
          />
          <TextControl
            label={__("Sub Heading", "better-payment")}
            value={successSubHeading}
            onChange={(newTitle) =>
              setAttributes({ successSubHeading: newTitle })
            }
            help={__(
              "Payment confirmation email will be sent to [customer_email]",
              "better-payment",
            )}
          />
          <TextControl
            label={__("Transaction ID", "better-payment")}
            value={transactionID}
            onChange={(newTitle) => setAttributes({ transactionID: newTitle })}
          />
          <TextControl
            label={__("Thanks You", "better-payment")}
            value={thanksMessage}
            onChange={(newTitle) => setAttributes({ thanksMessage: newTitle })}
          />
          <TextControl
            label={__("Amount", "better-payment")}
            value={amountMessage}
            onChange={(newTitle) => setAttributes({ amountMessage: newTitle })}
          />
          <TextControl
            label={__("Currency", "better-payment")}
            value={currencyMessage}
            onChange={(newTitle) =>
              setAttributes({ currencyMessage: newTitle })
            }
          />
          <TextControl
            label={__("Payment Method", "better-payment")}
            value={paymentMethodMessage}
            onChange={(newTitle) =>
              setAttributes({ paymentMethodMessage: newTitle })
            }
          />
          <TextControl
            label={__("Payment Type", "better-payment")}
            value={paymentType}
            onChange={(newTitle) => setAttributes({ paymentType: newTitle })}
          />
          <TextControl
            label={__("Merchant Details", "better-payment")}
            value={merchatDetails}
            onChange={(newTitle) => setAttributes({ merchatDetails: newTitle })}
          />
          <TextControl
            label={__("Paid Amount", "better-payment")}
            value={paidAmount}
            onChange={(newTitle) => setAttributes({ paidAmount: newTitle })}
          />
          <TextControl
            label={__("Purchase Details", "better-payment")}
            value={purchaseDetails}
            onChange={(newTitle) =>
              setAttributes({ purchaseDetails: newTitle })
            }
          />
          <TextControl
            label={__("Print", "better-payment")}
            value={printButtonText}
            onChange={(newTitle) =>
              setAttributes({ printButtonText: newTitle })
            }
          />
          <TextControl
            label={__("View Details", "better-payment")}
            value={viewDetailsButtonText}
            onChange={(newTitle) =>
              setAttributes({ viewDetailsButtonText: newTitle })
            }
          />
          <EBTextControl
            label={__("User Dashboard URL", "better-payment")}
            fieldType="url"
            value={userDashboardUrl}
            onChange={(value) => setAttributes({ userDashboardUrl: value })}
            placeholder="eg. https://example.com/custom-page/"
            help={__(
              "Please enter the page url where User Dashboard widget is used.",
              "better-payment",
            )}
            showValidation={true}
            enableSecurity={true}
          />
          <EBTextControl
            label={__("Custom Redirect URL", "better-payment")}
            fieldType="url"
            value={customRedirectUrl}
            onChange={(value) => setAttributes({ customRedirectUrl: value })}
            placeholder="eg. https://example.com/custom-page/"
            help={__(
              "Please note that only your current domain is allowed here to keep your site secure.",
              "better-payment",
            )}
            showValidation={true}
            enableSecurity={true}
          />
        </InspectorPanel.PanelBody>

        <InspectorPanel.PanelBody
          title={__("Error Message", "better-payment")}
          initialOpen={false}
        >
          <div id="error-icon-picker-wrapper">
            <EBIconPicker
              value={errorIcon}
              onChange={(icon) => setAttributes({ errorIcon: icon })}
            />
          </div>
          <Divider />
          <PanelRow>
            <strong>Content</strong>
          </PanelRow>
          <TextControl
            label={__("Heading Message Text", "better-payment")}
            value={errorHeading}
            onChange={(newTitle) => setAttributes({ errorHeading: newTitle })}
          />
          <TextControl
            label={__("Sub Heading Message Text", "better-payment")}
            value={errorSubHeading}
            onChange={(newTitle) =>
              setAttributes({ errorSubHeading: newTitle })
            }
          />
          <TextControl
            label={__("Transaction ID Text", "better-payment")}
            value={transactionIDText}
            onChange={(newTitle) =>
              setAttributes({ transactionIDText: newTitle })
            }
          />
          <ToggleControl
            label={__("Show Details Button", "better-payment")}
            checked={showDetailsButton}
            onChange={(newVal) => setAttributes({ showDetailsButton: newVal })}
          />
          {showDetailsButton && (
            <>
              <TextControl
                label={__("Details Button Text", "better-payment")}
                value={detailsButtonText}
                onChange={(newTitle) =>
                  setAttributes({ detailsButtonText: newTitle })
                }
              />
              <EBTextControl
                label={__("User Dashboard URL", "better-payment")}
                fieldType="url"
                value={detailsButtonUrl}
                onChange={(newUrl) =>
                  setAttributes({ detailsButtonUrl: newUrl })
                }
                placeholder="eg. https://example.com/custom-page/"
                help={__(
                  "Please enter the page url where User Dashboard widget is used.",
                  "better-payment",
                )}
                showValidation={true}
                enableSecurity={true}
              />
            </>
          )}
          <EBTextControl
            label={__("Custom Redirect URL", "better-payment")}
            fieldType="url"
            value={errorRedirectUrl}
            onChange={(value) => setAttributes({ errorRedirectUrl: value })}
            placeholder="eg. https://example.com/custom-page/"
            help={__(
              "Please note that only your current domain is allowed here to keep your site secure.",
              "better-payment",
            )}
            showValidation={true}
            enableSecurity={true}
          />
        </InspectorPanel.PanelBody>
      </InspectorPanel.General>
      
      <InspectorPanel.Style>
        <>
          <InspectorPanel.PanelBody
            title={__("Form Sidebar Style", "better-payment")}
            initialOpen={true}
          >
            <BackgroundControl 
              label={__("Background", "better-payment")}
              noOverlay
              // noMainBgi
              noOverlayBgi
              controlName={SIDEBAR_BACKGROUND} 
            />
            <Divider />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <Divider />
            <BorderShadowControl 
              label={__("Border & Shadow", "better-payment")}
              controlName={SIDEBAR_BORDER} 
            />
          </InspectorPanel.PanelBody>

          <InspectorPanel.PanelBody
            title={__("Sidebar Text Style", "better-payment")}
            initialOpen={false}
          >
            <PanelRow>
              <strong>Title Text</strong>
            </PanelRow>
            <ColorControl
              label={__("Color", "better-payment")}
              color={sidebarTextColor}
              onChange={(color) => setAttributes({ sidebarTextColor: color })}
              attributeName={"sidebarTextColor"}
            />
            <TypographyDropdown
              baseLabel="Typography"
              typographyPrefixConstant={typoPrefix_sidebar_title}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_TITLE_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_TITLE_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <Divider />
            <PanelRow>
              <strong>Sub-Title Text</strong>
            </PanelRow>
            <ColorControl
              label={__("Color", "better-payment")}
              color={sidebarSubTextColor}
              onChange={(color) => setAttributes({ sidebarSubTextColor: color })}
              attributeName={"sidebarSubTextColor"}
            />
            <TypographyDropdown
              baseLabel="Typography"
              typographyPrefixConstant={typoPrefix_sidebar_sub_title}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_SUBTITLE_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_SUBTITLE_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <Divider />
            <PanelRow>
              <strong>Amount Text</strong>
            </PanelRow>
            <ColorControl
              label={__("Color", "better-payment")}
              color={sidebarAmountTextColor}
              onChange={(color) => setAttributes({ sidebarAmountTextColor: color })}
              attributeName={"sidebarAmountTextColor"}
            />
            <TypographyDropdown
              baseLabel="Typography"
              typographyPrefixConstant={typoPrefix_sidebar_amount_text}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_AMOUNT_TEXT_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_AMOUNT_TEXT_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <Divider />
            <PanelRow>
              <strong>Amount Summary</strong>
            </PanelRow>
            <ColorControl
              label={__("Color", "better-payment")}
              color={sidebarAmountSummaryColor}
              onChange={(color) => setAttributes({ sidebarAmountSummaryColor: color })}
              attributeName={"sidebarAmountSummaryColor"}
            />
            <TypographyDropdown
              baseLabel="Typography"
              typographyPrefixConstant={typoPrefix_sidebar_amount_summary}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_AMOUNT_SUMMARY_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={SIDEBAR_AMOUNT_SUMMARY_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            {formLayout !== 'layout-1' && (
              <>
                <Divider />
                <PanelRow>
                  <strong>Icon</strong>
                </PanelRow>
                <ColorControl
                  label={__("Color", "better-payment")}
                  color={sidebarIconColor}
                  onChange={(color) => setAttributes({ sidebarIconColor: color })}
                  attributeName={"sidebarIconColor"}
                />
                <ResponsiveRangeController
                  baseLabel={__("Size", "better-payment")}
                  controlName={SIDEBAR_ICON_SIZE}
                  min={10}
                  max={100}
                  step={1}
                />
                <ResponsiveDimensionsControl
                  controlName={SIDEBAR_ICON_MARGIN}
                  baseLabel={__("Margin", "better-payment")}
                />
                <ResponsiveDimensionsControl
                  controlName={SIDEBAR_ICON_PADDING}
                  baseLabel={__("Padding", "better-payment")}
                />
              </>
            )}
          </InspectorPanel.PanelBody>

          <InspectorPanel.PanelBody
            title={__("Form Container Style", "better-payment")}
            initialOpen={false}
          >
            <BackgroundControl
              controlName={FORM_CONTAINER_BACKGROUND}
              noOverlay
            />
            <ResponsiveDimensionsControl
              controlName={FORM_CONTAINER_MARGIN}
              baseLabel={__("Margin", "better-payment")}
            />
            <ResponsiveDimensionsControl
              controlName={FORM_CONTAINER_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <BorderShadowControl
              label={__("Border & Shadow", "better-payment")}
              controlName={FORM_CONTAINER_BORDER}
            />
          </InspectorPanel.PanelBody>

          <InspectorPanel.PanelBody
            title={__("Form Fields Style", "better-payment")}
            initialOpen={false}
          >
            <ColorControl
              label={__("Background Color", "better-payment")}
              color={formFieldsBgColor}
              onChange={(color) => setAttributes({ formFieldsBgColor: color })}
              attributeName={"formFieldsBgColor"}
            />
            <ColorControl
              label={__("Text Color", "better-payment")}
              color={formFieldsColor}
              onChange={(color) => setAttributes({ formFieldsColor: color })}
              attributeName={"formFieldsColor"}
            />
            <ColorControl
              label={__("Placeholder Color", "better-payment")}
              color={formFieldsPlaceholderColor}
              onChange={(color) => setAttributes({ formFieldsPlaceholderColor: color })}
              attributeName={"formFieldsPlaceholderColor"}
            />
            <ResponsiveRangeController
              baseLabel={__("Spacing", "better-payment")}
              controlName={FORM_FIELDS_SPACING}
              min={0}
              max={100000}
              step={1}
            />
            <ResponsiveDimensionsControl
              controlName={FORM_FIELDS_PADDING}
              baseLabel={__("Padding", "better-payment")}
            />
            <ResponsiveRangeController
              baseLabel={__("Text Indent", "better-payment")}
              controlName={FORM_FIELDS_TEXT_INDENT}
              min={0}
              max={100000}
              step={1}
            />
            <ResponsiveRangeController
              baseLabel={__("Input Width", "better-payment")}
              controlName={FORM_FIELDS_INPUT_WIDTH}
              min={0}
              max={1000000}
              step={1}
              unitType={["px", "%"]}
              help={__("Set width for all input fields. Not applicable if the field is set to display inline.", "better-payment")}
            />
            <ResponsiveRangeController
              baseLabel={__("Input Height", "better-payment")}
              controlName={FORM_FIELDS_INPUT_HEIGHT}
              min={0}
              max={1000000}
              step={1}
              unitType={["px", "em", "%"]}
            />
            <BorderShadowControl
              label={__("Border & Shadow", "better-payment")}
              controlName={FORM_FIELDS_BORDER}
            />
            <TypographyDropdown
              baseLabel="Typography"
              typographyPrefixConstant={typoPrefix_form_fields}
            />
            <Divider />
            <PanelRow>
              <strong>Payment Method</strong>
            </PanelRow>
            <BaseControl id="eb-accordion-image-icon">
              <ButtonGroup>
                {( paymentMethodStatuses || []).map(({ label, value, active }, index) => (
                  <Button
                    key={index}
                    isPrimary={value === paymentMethodStatus && active}
                    onClick={() =>
                      setAttributes({ paymentMethodStatus: value })
                    }
                  >
                    {label}
                  </Button>
                ))}
              </ButtonGroup>
            </BaseControl>
            {(paymentMethodStatus === 'active') && (
              <>
                <BorderShadowControl
                  label={__("Border & Shadow", "better-payment")}
                  controlName={PAYMENT_METHOD_BORDER}
                />
              </>
            )}
            {(paymentMethodStatus === 'inactive') && (
              <>
                <BorderShadowControl
                  label={__("Border & Shadow", "better-payment")}
                  controlName={PAYMENT_METHOD_INACTIVE_BORDER}
                />
              </>
            )}
            <Divider />
            <PanelRow>
              <strong>Input Icon</strong>
            </PanelRow>
            <ColorControl
              label={__("Color", "better-payment")}
              color={formFieldsIconColor}
              onChange={(color) => setAttributes({ formFieldsIconColor: color })}
              attributeName={"formFieldsIconColor"}
            />
            <ResponsiveRangeController
              baseLabel={__("Size", "better-payment")}
              controlName={FORM_FIELDS_ICON_SIZE}
              min={0}
              max={100}
              step={1}
            />
            <ResponsiveRangeController
              baseLabel={__("Width", "better-payment")}
              controlName={FORM_FIELDS_ICON_WIDTH}
              min={0}
              max={100}
              step={1}
            />
            <ResponsiveRangeController
              baseLabel={__("Height", "better-payment")}
              controlName={FORM_FIELDS_ICON_HEIGHT}
              min={0}
              max={100}
              step={1}
            />
          </InspectorPanel.PanelBody>

          <InspectorPanel.PanelBody
            title={__("Amount Fields Style", "better-payment")}
            initialOpen={false}
          >
            <ResponsiveRangeController
              baseLabel={__("Input Width", "better-payment")}
              controlName={AMOUNT_FIELDS_WIDTH}
              min={50}
              max={100000}
              step={1}
            />
            <ResponsiveRangeController
              baseLabel={__("Input Height", "better-payment")}
              controlName={AMOUNT_FIELDS_HEIGHT}
              min={0}
              max={100000}
              step={1}
            />
            <ResponsiveRangeController
              baseLabel={__("Spacing", "better-payment")}
              controlName={AMOUNT_FIELDS_SPACING}
              min={0}
              max={100000}
              step={1}
            />
            <BaseControl id="eb-amount-status">
              <ButtonGroup id="eb-amount-status">
                {( amountStatuses || []).map(({ label, value, active }, index) => (
                  <Button
                    key={index}
                    isPrimary={value === amountStatus && active}
                    onClick={() =>
                      setAttributes({ amountStatus: value })
                    }
                  >
                    {label}
                  </Button>
                ))}
              </ButtonGroup>
            </BaseControl>
            {(amountStatus === 'normal') && (
              <>
                <ColorControl
                  label={__("Background Color", "better-payment")}
                  color={amountFieldsBgColor}
                  onChange={(color) => setAttributes({ amountFieldsBgColor: color })}
                  attributeName={"amountFieldsBgColor"}
                />
                <ColorControl
                  label={__("Text Color", "better-payment")}
                  color={amountFieldsColor}
                  onChange={(color) => setAttributes({ amountFieldsColor: color })}
                  attributeName={"amountFieldsColor"}
                />
              </>
            )}
            {(amountStatus === 'selected') && (
              <>
                <ColorControl
                  label={__("Background Color", "better-payment")}
                  color={amountFieldsSelectedBgColor}
                  onChange={(color) => setAttributes({ amountFieldsSelectedBgColor: color })}
                  attributeName={"amountFieldsSelectedBgColor"}
                />
                <ColorControl
                  label={__("Text Color", "better-payment")}
                  color={amountFieldsSelectedColor}
                  onChange={(color) => setAttributes({ amountFieldsSelectedColor: color })}
                  attributeName={"amountFieldsSelectedColor"}
                />
              </>
            )}
            <Divider />
            <BorderShadowControl
              label={__("Border & Shadow", "better-payment")}
              controlName={AMOUNT_FIELDS_BORDER}
            />
            <TypographyDropdown
              baseLabel={__("Typography", "better-payment")}
              typographyPrefixConstant={typoPrefix_amount_fields}
            />
          </InspectorPanel.PanelBody>

          <InspectorPanel.PanelBody
            title={__("Form Button Style", "better-payment")}
            initialOpen={false}
          >
            <ResponsiveRangeController
              baseLabel={__("Width", "better-payment")}
              controlName={FORM_BUTTON_WIDTH}
              min={0}
              max={10000}
              step={1}
              unitType={["px", "%"]}
            />
            <BaseControl id="eb-form-button-status">
              <ButtonGroup id="eb-form-button-status">
                {( formButtonStatuses || []).map(({ label, value, active }, index) => (
                  <Button
                    key={index}
                    isPrimary={value === formButtonStatus && active}
                    onClick={() =>
                      setAttributes({ formButtonStatus: value })
                    }
                  >
                    {label}
                  </Button>
                ))}
              </ButtonGroup>
            </BaseControl>
            {(formButtonStatus === 'normal') && (
              <>
                <ColorControl
                  label={__("Background Color", "better-payment")}
                  color={formButtonBackground}
                  onChange={(color) => setAttributes({ formButtonBackground: color })}
                  attributeName={"formButtonBackground"}
                />
                <ColorControl
                  label={__("Text Color", "better-payment")}
                  color={formButtonColor}
                  onChange={(color) => setAttributes({ formButtonColor: color })}
                  attributeName={"formButtonColor"}
                />
                <BorderShadowControl
                  label={__("Border & Shadow", "better-payment")}
                  controlName={FORM_BUTTON_BORDER}
                />
                <ResponsiveDimensionsControl
                  controlName={FORM_BUTTON_PADDING}
                  baseLabel={__("Padding", "better-payment")}
                />
                <ResponsiveRangeController
                  baseLabel={__("Margin Top", "better-payment")}
                  controlName={FORM_BUTTON_MARGIN_TOP}
                  min={0}
                  max={10000}
                  step={1}
                />
                <TypographyDropdown
                  baseLabel={__("Typography", "better-payment")}
                  typographyPrefixConstant={typoPrefix_form_button}
                />
              </>
            )}
            {(formButtonStatus === 'hover') && (
              <>
                <ColorControl
                  label={__("Background Color", "better-payment")}
                  color={formButtonHoverBackground}
                  onChange={(color) => setAttributes({ formButtonHoverBackground: color })}
                  attributeName={"formButtonHoverBackground"}
                />
                <ColorControl
                  label={__("Text Color", "better-payment")}
                  color={formButtonHoverColor}
                  onChange={(color) => setAttributes({ formButtonHoverColor: color })}
                  attributeName={"formButtonHoverColor"}
                />
                <ColorControl
                  label={__("Border Color", "better-payment")}
                  color={formButtonHoverBorderColor}
                  onChange={(color) => setAttributes({ formButtonHoverBorderColor: color })}
                  attributeName={"formButtonHoverBorderColor"}
                />
              </>
            )}
          </InspectorPanel.PanelBody>
        </>
      </InspectorPanel.Style>
    </InspectorPanel>
  );
};

export default Inspector;
