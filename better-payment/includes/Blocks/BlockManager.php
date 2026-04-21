<?php
/**
 * Block Manager for Better Payment Gutenberg blocks.
 *
 * Handles registration, enqueuing, and management of all Gutenberg blocks.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

namespace Better_Payment\Lite\Blocks;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Handler;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block Manager class.
 *
 * @since 1.0.0
 */
class BlockManager {
    use TraitsHelper;

    /**
     * Instance of this class.
     *
     * @var BlockManager|null
     */
    private static $instance = null;

    /**
     * List of available blocks.
     *
     * @var array
     */
    private $blocks = array(
        'payment-form' => array(
            'name'        => 'better-payment/payment-form',
            'path'        => 'payment-form',
            'has_styles'  => true,
            'has_scripts' => true,
        ),
    );

    /**
     * Get the singleton instance.
     *
     * @return BlockManager
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
    }

    /**
     * Render callback for the payment form block frontend.
     *
     * This method contains all the rendering logic including layout template loading,
     * settings mapping, widget proxy object creation, and security measures.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block default content.
     * @param WP_Block $block      Block instance.
     * @return string The rendered HTML output.
     */
    public function render_payment_form_frontend( $attributes, $content = '', $block = null ) {
        // Get block attributes with defaults.
        $form_layout = isset( $attributes['formLayout'] ) ? sanitize_text_field( $attributes['formLayout'] ) : 'layout-1';

        // Validate layout - only allow layout-1, layout-2, layout-3.
        $allowed_layouts = array( 'layout-1', 'layout-2', 'layout-3' );
        if ( ! in_array( $form_layout, $allowed_layouts, true ) ) {
            $form_layout = 'layout-1';
        }

        // Check if layout file exists, fallback to layout-1 if not.
        $template_file = BETTER_PAYMENT_ADMIN_VIEWS_PATH . '/elementor/layouts-block/' . $form_layout . '.php';
        if ( ! file_exists( $template_file ) ) {
            $template_file = BETTER_PAYMENT_ADMIN_VIEWS_PATH . '/elementor/layouts-block/layout-1.php';
            if ( ! file_exists( $template_file ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Better Payment: Layout template not found at ' . $template_file );
                }
                return '<div class="better-payment-block better-payment-block--error">' .
                       esc_html__( 'Payment form template not found.', 'better-payment' ) .
                       '</div>';
            }
        }

        // Get global settings from database.
        $global_settings = DB::get_settings();

        // Generate a unique ID for this block instance.
        // Use a deterministic ID based on block attributes to ensure consistency across page loads.
        // This is critical for payment callback matching.
        $block_id = isset( $attributes['blockId'] ) && ! empty( $attributes['blockId'] )
            ? sanitize_text_field( $attributes['blockId'] )
            : 'bp-block-' . md5( get_the_ID() . wp_json_encode( $attributes ) );

        // Build the $settings array that the layout files expect.
        // This maps block attributes to the Elementor widget settings format.
        $settings = $this->build_block_settings( $attributes, $global_settings, $form_layout );

// Check if at least one payment gateway is enabled.
        $has_payment_gateway = 'yes' === $settings['better_payment_form_paypal_enable'] ||
                               'yes' === $settings['better_payment_form_stripe_enable'] ||
                               'yes' === $settings['better_payment_form_paystack_enable'];

        // Build wrapper attributes using WordPress's block wrapper API.
        // get_block_wrapper_attributes() reads supports.align from the block context
        // (set automatically by WP_Block before invoking this render callback) and adds
        // alignwide / alignfull to the class list — no manual $attributes['align'] needed.
        $wrapper_class      = 'better-payment-block bp-form-' . esc_attr( $form_layout );
        $wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_class ) );

        if ( ! $has_payment_gateway ) {
            return '<div ' . $wrapper_attributes . '>' .
                   '<div class="better-payment-block__notice">' .
                   '<p>' . esc_html__( 'Please configure at least one payment gateway (PayPal, Stripe, or Paystack) in Better Payment settings.', 'better-payment' ) . '</p>' .
                   '</div></div>';
        }

        // Match Elementor flow:
        // - Handle response before rendering form.
        // - On success: render success notice only.
        // - On error: render error notice and continue rendering the form.
        ob_start();
        $payment_response = Handler::manage_response( $settings, $block_id );
        $manage_response_output = ob_get_clean();

        if ( $payment_response ) {
            return '<div ' . $wrapper_attributes . '>' . $manage_response_output . '</div>';
        }

        // Fire action hook for extensibility (matching Elementor widget behavior).
        do_action( 'better_payment/elementor/editor/manage_response_webhook', null, $settings );

        // Store settings in a transient so the AJAX handler can retrieve them.
        $transient_key = 'bp_block_settings_' . get_the_ID() . '_' . $block_id;
        set_transient( $transient_key, $settings, HOUR_IN_SECONDS );

        // Create widget proxy object for layout templates.
        $widgetObj = $this->create_widget_proxy( $block_id, $settings );

        // Prepare extraDatas for the layout.
        $extraDatas = array(
            'action'    => esc_url( admin_url( 'admin-post.php' ) ),
            'block_id'  => $block_id,
            'setting_meta' => wp_json_encode(
                array(
                    'page_id'   => get_the_ID(),
                    'widget_id' => $block_id,
                    'source'    => 'gutenberg',
                )
            ),
        );

        // Enqueue the necessary scripts and styles.
        wp_enqueue_style( 'better-payment-el' );
        wp_enqueue_style( 'bp-icon-front' );
        wp_enqueue_style( 'better-payment-style' );
        wp_enqueue_style( 'better-payment-common-style' );
        wp_enqueue_style( 'better-payment-admin-style' );
        $this->enqueue_font_awesome();
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'better-payment-common-script' );
        wp_enqueue_script( 'better-payment' );

        // Use output buffering to capture the layout output.
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is a safe WordPress core function. ?>>
            <?php echo $manage_response_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php
            // Create a closure to include the template with isolated scope.
            $render_layout = function ( $template_file, $settings, $widgetObj, $extraDatas ) {
                include $template_file;
            };

            // Bind the closure to the widget proxy so $this works in the template.
            $bound_render = \Closure::bind( $render_layout, $widgetObj, get_class( $widgetObj ) );
            $bound_render( $template_file, $settings, $widgetObj, $extraDatas );
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Build the settings array for layout templates.
     *
     * Maps block attributes to the Elementor widget settings format expected by layout templates.
     *
     * @param array  $attributes      Block attributes.
     * @param array  $global_settings Global plugin settings from database.
     * @param string $form_layout     The selected form layout.
     * @return array Settings array for layout templates.
     */
    public function build_block_settings( $attributes, $global_settings, $form_layout ) {
        // Convert block formFields array to Elementor format.
        $form_fields = $this->convert_form_fields( $attributes );

        // Convert block amountList array to Elementor format.
        $amount_list = $this->convert_amount_list( $attributes );

        // Get block attribute values with fallbacks to global settings.
        $paypal_enabled = isset( $attributes['paypalEnabled'] )
            ? ( $attributes['paypalEnabled'] ? 'yes' : '' )
            : ( ! empty( $global_settings['better_payment_settings_general_general_paypal'] ) && 'yes' === $global_settings['better_payment_settings_general_general_paypal'] ? 'yes' : '' );

        // Get PayPal business email from block attribute, fall back to global settings.
        $paypal_business_email = isset( $attributes['paypalBusinessEmail'] ) && ! empty( $attributes['paypalBusinessEmail'] )
            ? sanitize_email( $attributes['paypalBusinessEmail'] )
            : ( ! empty( $global_settings['better_payment_settings_payment_paypal_email'] ) ? $global_settings['better_payment_settings_payment_paypal_email'] : '' );

        $stripe_enabled = isset( $attributes['stripeEnabled'] )
            ? ( $attributes['stripeEnabled'] ? 'yes' : '' )
            : ( ! empty( $global_settings['better_payment_settings_general_general_stripe'] ) && 'yes' === $global_settings['better_payment_settings_general_general_stripe'] ? 'yes' : '' );

        $paystack_enabled = isset( $attributes['paystackEnabled'] )
            ? ( $attributes['paystackEnabled'] ? 'yes' : '' )
            : ( ! empty( $global_settings['better_payment_settings_general_general_paystack'] ) && 'yes' === $global_settings['better_payment_settings_general_general_paystack'] ? 'yes' : '' );

        // Get currency from block attribute or global settings.
        $currency = isset( $attributes['currency'] ) && ! empty( $attributes['currency'] )
            ? sanitize_text_field( $attributes['currency'] )
            : ( ! empty( $global_settings['better_payment_settings_general_general_currency'] ) ? $global_settings['better_payment_settings_general_general_currency'] : 'USD' );

        // Get sidebar show setting from block attribute.
        $sidebar_show = isset( $attributes['showSidebar'] )
            ? ( $attributes['showSidebar'] ? 'yes' : '' )
            : 'yes';

        // Get show amount list from block attribute.
        $show_amount_list = isset( $attributes['showAmountList'] )
            ? ( $attributes['showAmountList'] ? 'yes' : '' )
            : '';

        // Get currency alignment from block attribute.
        $currency_alignment = isset( $attributes['currencyAlign'] ) && ! empty( $attributes['currencyAlign'] )
            ? sanitize_text_field( $attributes['currencyAlign'] )
            : 'left';

        // Get payment source from block attribute.
        $payment_source = isset( $attributes['paymentSource'] ) && ! empty( $attributes['paymentSource'] )
            ? sanitize_text_field( $attributes['paymentSource'] )
            : '';

        // Get transaction details from block attributes.
        $transaction_title = isset( $attributes['transactionTitle'] ) && ! empty( $attributes['transactionTitle'] )
            ? sanitize_text_field( $attributes['transactionTitle'] )
            : __( 'Transaction Details', 'better-payment' );

        $transaction_sub_title = isset( $attributes['transactionSubTitle'] ) && ! empty( $attributes['transactionSubTitle'] )
            ? sanitize_text_field( $attributes['transactionSubTitle'] )
            : __( 'Total payment of your product in the following:', 'better-payment' );

        $amount_text = isset( $attributes['amountText'] ) && ! empty( $attributes['amountText'] )
            ? sanitize_text_field( $attributes['amountText'] )
            : __( 'Amount:', 'better-payment' );

        $product_title = isset( $attributes['transactionDetailsProductTitle'] ) && ! empty( $attributes['transactionDetailsProductTitle'] )
            ? sanitize_text_field( $attributes['transactionDetailsProductTitle'] )
            : __( 'Title:', 'better-payment' );

        // Get button text from block attributes.
        $paypal_button_text = isset( $attributes['paypalButtonText'] ) && ! empty( $attributes['paypalButtonText'] )
            ? sanitize_text_field( $attributes['paypalButtonText'] )
            : '';

        $stripe_button_text = isset( $attributes['stripeButtonText'] ) && ! empty( $attributes['stripeButtonText'] )
            ? sanitize_text_field( $attributes['stripeButtonText'] )
            : '';

        $paystack_button_text = isset( $attributes['paystackButtonText'] ) && ! empty( $attributes['paystackButtonText'] )
            ? sanitize_text_field( $attributes['paystackButtonText'] )
            : '';

        // Get product IDs from block attributes.
        $woocommerce_product_id = isset( $attributes['woocommerceProductId'] ) && ! empty( $attributes['woocommerceProductId'] )
            ? intval( $attributes['woocommerceProductId'] )
            : 0;

        $fluentcart_product_id = isset( $attributes['fluentcartProductId'] ) && ! empty( $attributes['fluentcartProductId'] )
            ? intval( $attributes['fluentcartProductId'] )
            : 0;

        // Get Stripe Price ID from block attributes.
        // Block attribute: stripeDefaultPriceId (string)
        // Used when payment source is 'stripe' (Stripe Product).
        $stripe_price_id = isset( $attributes['stripeDefaultPriceId'] ) && ! empty( $attributes['stripeDefaultPriceId'] )
            ? sanitize_text_field( $attributes['stripeDefaultPriceId'] )
            : '';

        // Validate Stripe Price ID format (should start with 'price_').
        if ( ! empty( $stripe_price_id ) && strpos( $stripe_price_id, 'price_' ) !== 0 ) {
            // Invalid format - log warning in debug mode and reset to empty.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Better Payment: Invalid Stripe Price ID format (should start with "price_"): ' . esc_html( $stripe_price_id ) );
            }
            $stripe_price_id = '';
        }

        // Get email notification setting from block attributes.
        $email_notification_enabled = isset( $attributes['emailNotificationEnabled'] ) && $attributes['emailNotificationEnabled']
            ? 'yes'
            : '';

        // Get site domain for default email addresses.
        $site_url_parsed = wp_parse_url( get_site_url() );
        $site_domain     = ! empty( $site_url_parsed['host'] ) ? esc_html( $site_url_parsed['host'] ) : 'example.com';
        $default_from_email = 'wordpress@' . $site_domain;
        $default_email_subject = sprintf( __( 'Better Payment transaction on %s', 'better-payment' ), esc_html( get_option( 'blogname' ) ) );

        // Admin email settings from block attributes with fallback to global settings.
        // Block attribute: adminEmail (string)
        $email_to = isset( $attributes['adminEmail'] ) && ! empty( $attributes['adminEmail'] )
            ? $this->sanitize_email_list( $attributes['adminEmail'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_to'] ) ? sanitize_email( $global_settings['better_payment_settings_general_email_to'] ) : sanitize_email( get_option( 'admin_email' ) ) );

        // Block attribute: adminSubject (string)
        $email_subject = isset( $attributes['adminSubject'] ) && ! empty( $attributes['adminSubject'] )
            ? sanitize_text_field( $attributes['adminSubject'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_subject'] ) ? sanitize_text_field( $global_settings['better_payment_settings_general_email_subject'] ) : $default_email_subject );

        // Block attribute: adminMessage (string)
        $email_content = isset( $attributes['adminMessage'] ) && ! empty( $attributes['adminMessage'] )
            ? wp_kses_post( $attributes['adminMessage'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_message_admin'] ) ? wp_kses_post( $global_settings['better_payment_settings_general_email_message_admin'] ) : '' );

        // Block attribute: adminFromEmail (string)
        $email_from = isset( $attributes['adminFromEmail'] ) && ! empty( $attributes['adminFromEmail'] )
            ? sanitize_email( $attributes['adminFromEmail'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_from_email'] ) ? sanitize_email( $global_settings['better_payment_settings_general_email_from_email'] ) : $default_from_email );

        // Block attribute: adminFromName (string)
        $email_from_name = isset( $attributes['adminFromName'] ) && ! empty( $attributes['adminFromName'] )
            ? sanitize_text_field( $attributes['adminFromName'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_from_name'] ) ? sanitize_text_field( $global_settings['better_payment_settings_general_email_from_name'] ) : esc_html( get_bloginfo( 'name' ) ) );

        // Block attribute: adminReplyTo (string)
        $email_reply_to = isset( $attributes['adminReplyTo'] ) && ! empty( $attributes['adminReplyTo'] )
            ? sanitize_email( $attributes['adminReplyTo'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_reply_to'] ) ? sanitize_email( $global_settings['better_payment_settings_general_email_reply_to'] ) : $default_from_email );

        // Block attribute: adminCc (string)
        $email_cc = isset( $attributes['adminCc'] ) && ! empty( $attributes['adminCc'] )
            ? $this->sanitize_email_list( $attributes['adminCc'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_cc'] ) ? $this->sanitize_email_list( $global_settings['better_payment_settings_general_email_cc'] ) : '' );

        // Block attribute: adminBcc (string)
        $email_bcc = isset( $attributes['adminBcc'] ) && ! empty( $attributes['adminBcc'] )
            ? $this->sanitize_email_list( $attributes['adminBcc'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_bcc'] ) ? $this->sanitize_email_list( $global_settings['better_payment_settings_general_email_bcc'] ) : '' );

        // Block attribute: adminSendAs (string: 'html' or 'plain')
        $allowed_content_types = array( 'html', 'plain' );
        $email_content_type = isset( $attributes['adminSendAs'] ) && in_array( $attributes['adminSendAs'], $allowed_content_types, true )
            ? $attributes['adminSendAs']
            : ( ! empty( $global_settings['better_payment_settings_general_email_send_as'] ) && in_array( $global_settings['better_payment_settings_general_email_send_as'], $allowed_content_types, true ) ? $global_settings['better_payment_settings_general_email_send_as'] : 'html' );

        // Admin email display toggles (boolean attributes converted to 'yes'/'' for Elementor compatibility).
        // Block attribute: adminShowHeaderText (boolean, default: true)
        $email_content_heading = isset( $attributes['adminShowHeaderText'] ) ? ( $attributes['adminShowHeaderText'] ? 'yes' : '' ) : 'yes';
        // Block attribute: adminShowFromSection (boolean, default: true)
        $email_content_from_section = isset( $attributes['adminShowFromSection'] ) ? ( $attributes['adminShowFromSection'] ? 'yes' : '' ) : 'yes';
        // Block attribute: adminShowToSection (boolean, default: true)
        $email_content_to_section = isset( $attributes['adminShowToSection'] ) ? ( $attributes['adminShowToSection'] ? 'yes' : '' ) : 'yes';
        // Block attribute: adminShowTransactionSummary (boolean, default: true)
        $email_content_transaction_summary = isset( $attributes['adminShowTransactionSummary'] ) ? ( $attributes['adminShowTransactionSummary'] ? 'yes' : '' ) : 'yes';
        // Block attribute: adminShowFooterText (boolean, default: true)
        $email_content_footer_text = isset( $attributes['adminShowFooterText'] ) ? ( $attributes['adminShowFooterText'] ? 'yes' : '' ) : 'yes';

        // Customer email settings from block attributes with fallback to global settings.
        // Block attribute: customerSubject (string)
        $email_subject_customer = isset( $attributes['customerSubject'] ) && ! empty( $attributes['customerSubject'] )
            ? sanitize_text_field( $attributes['customerSubject'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_subject_customer'] ) ? sanitize_text_field( $global_settings['better_payment_settings_general_email_subject_customer'] ) : $default_email_subject );

        // Block attribute: customerMessage (string)
        $email_content_customer = isset( $attributes['customerMessage'] ) && ! empty( $attributes['customerMessage'] )
            ? wp_kses_post( $attributes['customerMessage'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_message_customer'] ) ? wp_kses_post( $global_settings['better_payment_settings_general_email_message_customer'] ) : '' );

        // Block attribute: customerFromEmail (string)
        $email_from_customer = isset( $attributes['customerFromEmail'] ) && ! empty( $attributes['customerFromEmail'] )
            ? sanitize_email( $attributes['customerFromEmail'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_from_email_customer'] ) ? sanitize_email( $global_settings['better_payment_settings_general_email_from_email_customer'] ) : $default_from_email );

        // Block attribute: customerFromName (string)
        $email_from_name_customer = isset( $attributes['customerFromName'] ) && ! empty( $attributes['customerFromName'] )
            ? sanitize_text_field( $attributes['customerFromName'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_from_name_customer'] ) ? sanitize_text_field( $global_settings['better_payment_settings_general_email_from_name_customer'] ) : esc_html( get_bloginfo( 'name' ) ) );

        // Block attribute: customerReplyTo (string)
        $email_reply_to_customer = isset( $attributes['customerReplyTo'] ) && ! empty( $attributes['customerReplyTo'] )
            ? sanitize_email( $attributes['customerReplyTo'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_reply_to_customer'] ) ? sanitize_email( $global_settings['better_payment_settings_general_email_reply_to_customer'] ) : $default_from_email );

        // Block attribute: customerCc (string)
        $email_cc_customer = isset( $attributes['customerCc'] ) && ! empty( $attributes['customerCc'] )
            ? $this->sanitize_email_list( $attributes['customerCc'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_cc_customer'] ) ? $this->sanitize_email_list( $global_settings['better_payment_settings_general_email_cc_customer'] ) : '' );

        // Block attribute: customerBcc (string)
        $email_bcc_customer = isset( $attributes['customerBcc'] ) && ! empty( $attributes['customerBcc'] )
            ? $this->sanitize_email_list( $attributes['customerBcc'] )
            : ( ! empty( $global_settings['better_payment_settings_general_email_bcc_customer'] ) ? $this->sanitize_email_list( $global_settings['better_payment_settings_general_email_bcc_customer'] ) : '' );

        // Block attribute: customerSendAs (string: 'html' or 'plain')
        $email_content_type_customer = isset( $attributes['customerSendAs'] ) && in_array( $attributes['customerSendAs'], $allowed_content_types, true )
            ? $attributes['customerSendAs']
            : ( ! empty( $global_settings['better_payment_settings_general_email_send_as_customer'] ) && in_array( $global_settings['better_payment_settings_general_email_send_as_customer'], $allowed_content_types, true ) ? $global_settings['better_payment_settings_general_email_send_as_customer'] : 'html' );

        // Customer email attachment settings.
        // Block attribute: customerPDFAttachment (boolean, default: false)
        $email_attachment_pdf_show = isset( $attributes['customerPDFAttachment'] ) && $attributes['customerPDFAttachment'] ? 'yes' : '';
        // Block attribute: customerFileAttachment (string - URL)
        $email_attachment = isset( $attributes['customerFileAttachment'] ) && ! empty( $attributes['customerFileAttachment'] )
            ? array( 'url' => esc_url( $attributes['customerFileAttachment'] ) )
            : array();

        // Get success message settings from block attributes.
        $success_icon = isset( $attributes['successIcon'] ) && ! empty( $attributes['successIcon'] )
            ? sanitize_text_field( $attributes['successIcon'] )
            : '';

        $success_heading = isset( $attributes['successHeading'] ) && ! empty( $attributes['successHeading'] )
            ? sanitize_text_field( $attributes['successHeading'] )
            : '';

        $success_sub_heading = isset( $attributes['successSubHeading'] ) && ! empty( $attributes['successSubHeading'] )
            ? sanitize_text_field( $attributes['successSubHeading'] )
            : '';

        $transaction_id_text = isset( $attributes['transactionID'] ) && ! empty( $attributes['transactionID'] )
            ? sanitize_text_field( $attributes['transactionID'] )
            : __( 'Transaction ID:', 'better-payment' );

        $thanks_message = isset( $attributes['thanksMessage'] ) && ! empty( $attributes['thanksMessage'] )
            ? sanitize_text_field( $attributes['thanksMessage'] )
            : __( 'Thank you for your payment.', 'better-payment' );

        $amount_message = isset( $attributes['amountMessage'] ) && ! empty( $attributes['amountMessage'] )
            ? sanitize_text_field( $attributes['amountMessage'] )
            : __( 'Amount', 'better-payment' );

        $currency_message = isset( $attributes['currencyMessage'] ) && ! empty( $attributes['currencyMessage'] )
            ? sanitize_text_field( $attributes['currencyMessage'] )
            : __( 'Currency', 'better-payment' );

        $payment_method_message = isset( $attributes['paymentMethodMessage'] ) && ! empty( $attributes['paymentMethodMessage'] )
            ? sanitize_text_field( $attributes['paymentMethodMessage'] )
            : __( 'Payment Method', 'better-payment' );

        $payment_type_message = isset( $attributes['paymentType'] ) && ! empty( $attributes['paymentType'] )
            ? sanitize_text_field( $attributes['paymentType'] )
            : __( 'Payment Type', 'better-payment' );

        $merchant_details_text = isset( $attributes['merchatDetails'] ) && ! empty( $attributes['merchatDetails'] )
            ? sanitize_text_field( $attributes['merchatDetails'] )
            : __( 'Merchant Details', 'better-payment' );

        $paid_amount_text = isset( $attributes['paidAmount'] ) && ! empty( $attributes['paidAmount'] )
            ? sanitize_text_field( $attributes['paidAmount'] )
            : __( 'Paid Amount', 'better-payment' );

        $purchase_details_text = isset( $attributes['purchaseDetails'] ) && ! empty( $attributes['purchaseDetails'] )
            ? sanitize_text_field( $attributes['purchaseDetails'] )
            : __( 'Purchase Details', 'better-payment' );

        $print_btn_text = isset( $attributes['printButtonText'] ) && ! empty( $attributes['printButtonText'] )
            ? sanitize_text_field( $attributes['printButtonText'] )
            : __( 'Print', 'better-payment' );

        $view_details_btn_text = isset( $attributes['viewDetailsButtonText'] ) && ! empty( $attributes['viewDetailsButtonText'] )
            ? sanitize_text_field( $attributes['viewDetailsButtonText'] )
            : __( 'View Details', 'better-payment' );

        $user_dashboard_url = isset( $attributes['userDashboardUrl'] ) && ! empty( $attributes['userDashboardUrl'] )
            ? esc_url( $attributes['userDashboardUrl'] )
            : '';

        $custom_redirect_url = isset( $attributes['customRedirectUrl'] ) && ! empty( $attributes['customRedirectUrl'] )
            ? esc_url( $attributes['customRedirectUrl'] )
            : '';

        // Get error message settings from block attributes.
        $error_icon = isset( $attributes['errorIcon'] ) && ! empty( $attributes['errorIcon'] )
            ? sanitize_text_field( $attributes['errorIcon'] )
            : '';

        // Email icon (block control) maps to Elementor email logo (image URL) when uploaded.
        $email_icon = isset( $attributes['emailIcon'] ) && ! empty( $attributes['emailIcon'] )
            ? sanitize_text_field( $attributes['emailIcon'] )
            : 'bp-icon bp-envelope';

        $error_heading = isset( $attributes['errorHeading'] ) && ! empty( $attributes['errorHeading'] )
            ? sanitize_text_field( $attributes['errorHeading'] )
            : __( 'Payment Failed', 'better-payment' );

        $error_sub_heading = isset( $attributes['errorSubHeading'] ) && ! empty( $attributes['errorSubHeading'] )
            ? sanitize_text_field( $attributes['errorSubHeading'] )
            : __( 'Your payment has failed. Please check your payment details', 'better-payment' );

        $error_transaction_id_text = isset( $attributes['transactionIDText'] ) && ! empty( $attributes['transactionIDText'] )
            ? sanitize_text_field( $attributes['transactionIDText'] )
            : __( 'Transaction ID', 'better-payment' );

        $error_show_details_button = isset( $attributes['showDetailsButton'] ) && $attributes['showDetailsButton'] ? 'yes' : '';

        $error_details_button_text = isset( $attributes['detailsButtonText'] ) && ! empty( $attributes['detailsButtonText'] )
            ? sanitize_text_field( $attributes['detailsButtonText'] )
            : __( 'View Details', 'better-payment' );

        $error_details_button_url = isset( $attributes['detailsButtonUrl'] ) && ! empty( $attributes['detailsButtonUrl'] )
            ? esc_url( $attributes['detailsButtonUrl'] )
            : '';

        $error_redirect_url = isset( $attributes['errorRedirectUrl'] ) && ! empty( $attributes['errorRedirectUrl'] )
            ? esc_url( $attributes['errorRedirectUrl'] )
            : '';

        return array(
            // Layout settings.
            'better_payment_form_layout' => $form_layout,

            // Form title - used as item/product name in PayPal and Stripe checkout.
            // Block attribute is 'formName' (not 'formTitle') — default is 'Better Payment'.
            'better_payment_form_title' => isset( $attributes['formName'] ) && ! empty( $attributes['formName'] )
                ? sanitize_text_field( $attributes['formName'] )
                : '',

            // Payment gateway settings - prioritize block attributes over global settings.
            'better_payment_form_paypal_enable'   => $paypal_enabled,
            'better_payment_form_stripe_enable'   => $stripe_enabled,
            'better_payment_form_paystack_enable' => $paystack_enabled,

            // Stripe API keys - resolve based on live mode (matches Elementor behavior).
            // Handler::stripe_payment_success() uses better_payment_stripe_secret_key directly.
            'better_payment_stripe_live_mode'       => ! empty( $global_settings['better_payment_settings_payment_stripe_live_mode'] ) && 'yes' === $global_settings['better_payment_settings_payment_stripe_live_mode'] ? 'yes' : '',
            'better_payment_stripe_public_key'      => $this->resolve_stripe_key( $global_settings, 'public' ),
            'better_payment_stripe_secret_key'      => $this->resolve_stripe_key( $global_settings, 'secret' ),
            'better_payment_stripe_public_key_live' => ! empty( $global_settings['better_payment_settings_payment_stripe_live_public'] ) ? $global_settings['better_payment_settings_payment_stripe_live_public'] : '',
            'better_payment_stripe_secret_key_live' => ! empty( $global_settings['better_payment_settings_payment_stripe_live_secret'] ) ? $global_settings['better_payment_settings_payment_stripe_live_secret'] : '',

            // PayPal API keys - prioritize block attributes over global settings.
            'better_payment_paypal_live_mode'      => ! empty( $global_settings['better_payment_settings_payment_paypal_live_mode'] ) && 'yes' === $global_settings['better_payment_settings_payment_paypal_live_mode'] ? 'yes' : '',
            'better_payment_paypal_business_email' => $paypal_business_email,
            'better_payment_paypal_button_type'    => '_xclick', // Default PayPal button type for Buy Now functionality.

            // Paystack API keys - resolve based on live mode (matches Elementor behavior).
            'better_payment_paystack_live_mode'       => ! empty( $global_settings['better_payment_settings_payment_paystack_live_mode'] ) && 'yes' === $global_settings['better_payment_settings_payment_paystack_live_mode'] ? 'yes' : '',
            'better_payment_paystack_public_key'      => $this->resolve_paystack_key( $global_settings, 'public' ),
            'better_payment_paystack_secret_key'      => $this->resolve_paystack_key( $global_settings, 'secret' ),
            'better_payment_paystack_public_key_live' => ! empty( $global_settings['better_payment_settings_payment_paystack_live_public'] ) ? $global_settings['better_payment_settings_payment_paystack_live_public'] : '',
            'better_payment_paystack_secret_key_live' => ! empty( $global_settings['better_payment_settings_payment_paystack_live_secret'] ) ? $global_settings['better_payment_settings_payment_paystack_live_secret'] : '',

            // Currency settings from block attributes with fallback to global settings.
            'better_payment_form_currency'           => $currency,
            'better_payment_form_currency_alignment' => $currency_alignment,

            // Payment source settings from block attributes.
            'better_payment_form_payment_source'                  => $payment_source,
            'better_payment_form_payment_type'                    => '',
            'better_payment_form_woocommerce_product_id'          => $woocommerce_product_id,
            'better_payment_form_fluentcart_product_id'           => $fluentcart_product_id,
            'better_payment_form_payment_source_stripe_price_id'  => $stripe_price_id,

            // Sidebar settings from block attributes.
            'better_payment_form_sidebar_show'                      => $sidebar_show,
            'better_payment_form_transaction_details_heading'       => $transaction_title,
            'better_payment_form_transaction_details_sub_heading'   => $transaction_sub_title,
            'better_payment_form_transaction_details_product_title' => $product_title,
            'better_payment_form_transaction_details_amount_text'   => $amount_text,

            // Amount list settings from block attributes.
            'better_payment_show_amount_list' => $show_amount_list,
            'better_payment_amount'           => $amount_list,

            // Form fields from block attributes (converted to Elementor format).
            'better_payment_form_fields' => $form_fields,

            // Button text settings from block attributes.
            'better_payment_form_form_buttons_paypal_button_text'   => $paypal_button_text,
            'better_payment_form_form_buttons_stripe_button_text'   => $stripe_button_text,
            'better_payment_form_form_buttons_paystack_button_text' => $paystack_button_text,

            // Placeholder settings.
            'better_payment_placeholder_switch' => 'yes',

            // Email notification settings.
            'better_payment_form_email_enable' => $email_notification_enabled,

            // Admin email settings.
            'better_payment_email_to'                          => $email_to,
            'better_payment_email_subject'                     => $email_subject,
            'better_payment_email_content'                     => $email_content,
            'better_payment_email_from'                        => $email_from,
            'better_payment_email_from_name'                   => $email_from_name,
            'better_payment_email_reply_to'                    => $email_reply_to,
            'better_payment_email_cc'                          => $email_cc,
            'better_payment_email_bcc'                         => $email_bcc,
            'better_payment_email_content_type'                => $email_content_type,
            'better_payment_email_content_heading'             => $email_content_heading,
            'better_payment_email_content_from_section'        => $email_content_from_section,
            'better_payment_email_content_to_section'          => $email_content_to_section,
            'better_payment_email_content_transaction_summary' => $email_content_transaction_summary,
            'better_payment_email_content_footer_text'         => $email_content_footer_text,

            // Customer email settings.
            'better_payment_email_subject_customer'         => $email_subject_customer,
            'better_payment_email_content_customer'         => $email_content_customer,
            'better_payment_email_from_customer'            => $email_from_customer,
            'better_payment_email_from_name_customer'       => $email_from_name_customer,
            'better_payment_email_reply_to_customer'        => $email_reply_to_customer,
            'better_payment_email_cc_customer'              => $email_cc_customer,
            'better_payment_email_bcc_customer'             => $email_bcc_customer,
            'better_payment_email_content_type_customer'    => $email_content_type_customer,
            'better_payment_form_email_attachment_pdf_show' => $email_attachment_pdf_show,
            'better_payment_form_email_attachment'          => $email_attachment,
            'better_payment_form_email_logo'                => $this->build_email_logo_setting( $email_icon, 'bp-icon bp-envelope' ),

            // Success message settings from block attributes.
            'better_payment_form_success_message_icon'      => $this->build_icon_control_setting( $success_icon ),
            'better_payment_form_success_message_heading'           => $success_heading,
            'better_payment_form_success_message_sub_heading'       => $success_sub_heading,
            'better_payment_form_success_message_transaction'       => $transaction_id_text,
            'better_payment_form_success_message_thanks'            => $thanks_message,
            'better_payment_form_success_message_amount_text'       => $amount_message,
            'better_payment_form_success_message_currency_text'     => $currency_message,
            'better_payment_form_success_message_pay_method_text'   => $payment_method_message,
            'better_payment_form_success_message_pay_type_text'     => $payment_type_message,
            'better_payment_form_success_message_merchant_details_text' => $merchant_details_text,
            'better_payment_form_success_message_paid_amount_text'  => $paid_amount_text,
            'better_payment_form_success_message_purchase_details_text' => $purchase_details_text,
            'better_payment_form_success_message_print_btn_text'    => $print_btn_text,
            'better_payment_form_success_message_view_details_btn_text' => $view_details_btn_text,
            'better_payment_form_success_page_view_details_url'     => array(
                'url' => $user_dashboard_url,
            ),
            'better_payment_form_success_page_url' => array(
                'url' => $custom_redirect_url,
            ),

            // Error message settings from block attributes.
            'better_payment_form_error_message_icon' => $this->build_icon_control_setting( $error_icon ),
            'better_payment_form_error_message_heading' => $error_heading,
            'better_payment_form_error_message_sub_heading' => $error_sub_heading,
            'better_payment_form_error_message_transaction_id_text' => $error_transaction_id_text,
            'better_payment_form_error_details_button_switch' => $error_show_details_button,
            'better_payment_form_error_details_button_text' => $error_details_button_text,
            'better_payment_form_error_details_page_url' => array(
                'url' => $error_details_button_url,
            ),
            'better_payment_form_error_page_url'        => array(
                'url' => $error_redirect_url,
            ),
            // Flag to identify this form is from Gutenberg block (not Elementor)
            'better_payment_form_source' => 'block',
        );
    }

    /**
     * Resolve the correct Stripe key based on live mode setting.
     *
     * This matches Elementor's behavior where the key is resolved before being stored
     * in better_payment_stripe_public_key/better_payment_stripe_secret_key.
     * Handler::stripe_payment_success() uses better_payment_stripe_secret_key directly.
     *
     * @param array  $global_settings Global settings array.
     * @param string $key_type        Either 'public' or 'secret'.
     * @return string The resolved key.
     */
    private function resolve_stripe_key( $global_settings, $key_type ) {
        $is_live_mode = ! empty( $global_settings['better_payment_settings_payment_stripe_live_mode'] )
            && 'yes' === $global_settings['better_payment_settings_payment_stripe_live_mode'];

        if ( 'public' === $key_type ) {
            return $is_live_mode
                ? ( ! empty( $global_settings['better_payment_settings_payment_stripe_live_public'] ) ? $global_settings['better_payment_settings_payment_stripe_live_public'] : '' )
                : ( ! empty( $global_settings['better_payment_settings_payment_stripe_test_public'] ) ? $global_settings['better_payment_settings_payment_stripe_test_public'] : '' );
        } else {
            return $is_live_mode
                ? ( ! empty( $global_settings['better_payment_settings_payment_stripe_live_secret'] ) ? $global_settings['better_payment_settings_payment_stripe_live_secret'] : '' )
                : ( ! empty( $global_settings['better_payment_settings_payment_stripe_test_secret'] ) ? $global_settings['better_payment_settings_payment_stripe_test_secret'] : '' );
        }
    }

    /**
     * Resolve the correct Paystack key based on live mode setting.
     *
     * This matches Elementor's behavior where the key is resolved before being stored
     * in better_payment_paystack_public_key/better_payment_paystack_secret_key.
     *
     * @param array  $global_settings Global settings array.
     * @param string $key_type        Either 'public' or 'secret'.
     * @return string The resolved key.
     */
    private function resolve_paystack_key( $global_settings, $key_type ) {
        $is_live_mode = ! empty( $global_settings['better_payment_settings_payment_paystack_live_mode'] )
            && 'yes' === $global_settings['better_payment_settings_payment_paystack_live_mode'];

        if ( 'public' === $key_type ) {
            return $is_live_mode
                ? ( ! empty( $global_settings['better_payment_settings_payment_paystack_live_public'] ) ? $global_settings['better_payment_settings_payment_paystack_live_public'] : '' )
                : ( ! empty( $global_settings['better_payment_settings_payment_paystack_test_public'] ) ? $global_settings['better_payment_settings_payment_paystack_test_public'] : '' );
        } else {
            return $is_live_mode
                ? ( ! empty( $global_settings['better_payment_settings_payment_paystack_live_secret'] ) ? $global_settings['better_payment_settings_payment_paystack_live_secret'] : '' )
                : ( ! empty( $global_settings['better_payment_settings_payment_paystack_test_secret'] ) ? $global_settings['better_payment_settings_payment_paystack_test_secret'] : '' );
        }
    }

    /**
     * Sanitize a comma-separated list of email addresses.
     *
     * This method validates and sanitizes email addresses to prevent
     * email header injection attacks and ensure only valid emails are used.
     *
     * @param string $email_list Comma-separated list of email addresses.
     * @return string Sanitized comma-separated list of valid email addresses.
     */
    private function sanitize_email_list( $email_list ) {
        if ( empty( $email_list ) ) {
            return '';
        }

        // Split by comma and sanitize each email.
        $emails = array_map( 'trim', explode( ',', $email_list ) );
        $valid_emails = array();

        foreach ( $emails as $email ) {
            $sanitized = sanitize_email( $email );
            // Only include valid email addresses.
            if ( ! empty( $sanitized ) && is_email( $sanitized ) ) {
                $valid_emails[] = $sanitized;
            }
        }

        return implode( ', ', $valid_emails );
    }

    /**
     * Convert block formFields array to Elementor widget format.
     *
     * @param array $attributes Block attributes.
     * @return array Converted form fields in Elementor format.
     */
    private function convert_form_fields( $attributes ) {
        $form_fields = array();

        if ( empty( $attributes['formFields'] ) || ! is_array( $attributes['formFields'] ) ) {
            // Return default form fields if none provided.
            return array(
                array(
                    '_id'                                => 'first_name_field',
                    'better_payment_primary_field_type'  => 'primary_first_name',
                    'better_payment_field_name_heading'  => __( 'First Name', 'better-payment' ),
                    'better_payment_field_name_placeholder' => __( 'First Name', 'better-payment' ),
                    'better_payment_field_type'          => 'text',
                    'better_payment_field_icon'          => array( 'value' => 'bp-icon bp-user', 'library' => '' ),
                    'better_payment_field_name_required' => '',
                    'better_payment_field_name_show'     => 'yes',
                ),
                array(
                    '_id'                                => 'last_name_field',
                    'better_payment_primary_field_type'  => 'primary_last_name',
                    'better_payment_field_name_heading'  => __( 'Last Name', 'better-payment' ),
                    'better_payment_field_name_placeholder' => __( 'Last Name', 'better-payment' ),
                    'better_payment_field_type'          => 'text',
                    'better_payment_field_icon'          => array( 'value' => 'bp-icon bp-user', 'library' => '' ),
                    'better_payment_field_name_required' => '',
                    'better_payment_field_name_show'     => 'yes',
                ),
                array(
                    '_id'                                => 'email_field',
                    'better_payment_primary_field_type'  => 'primary_email',
                    'better_payment_field_name_heading'  => __( 'Email', 'better-payment' ),
                    'better_payment_field_name_placeholder' => __( 'Email Address', 'better-payment' ),
                    'better_payment_field_type'          => 'email',
                    'better_payment_field_icon'          => array( 'value' => 'bp-icon bp-envelope', 'library' => '' ),
                    'better_payment_field_name_required' => 'yes',
                    'better_payment_field_name_show'     => 'yes',
                ),
                array(
                    '_id'                                       => 'amount_field',
                    'better_payment_primary_field_type'         => 'primary_payment_amount',
                    'better_payment_field_name_heading'         => __( 'Amount', 'better-payment' ),
                    'better_payment_field_name_placeholder'     => __( 'Payment Amount', 'better-payment' ),
                    'better_payment_field_type'                 => 'number',
                    'better_payment_field_icon'                 => array( 'value' => 'bp-icon bp-logo-2', 'library' => '' ),
                    'better_payment_field_name_required'        => 'yes',
                    'better_payment_field_name_show'            => 'yes',
                    'better_payment_field_name_min'             => 1,
                    'better_payment_field_name_max'             => '',
                    'better_payment_field_name_default'         => '',
                    'better_payment_field_name_default_fixed'   => '',
                    'better_payment_field_name_default_dynamic_enable' => '',
                ),
            );
        }

        foreach ( $attributes['formFields'] as $index => $field ) {
            $raw_icon_value = isset( $field['icon'] ) ? sanitize_text_field( $field['icon'] ) : 'bp-icon bp-user';
            $icon_setting   = $this->build_icon_control_setting( $raw_icon_value, 'bp-icon bp-user' );

            $converted_field = array(
                '_id'                                => 'field_' . $index,
                'better_payment_primary_field_type'  => isset( $field['primaryFieldType'] ) ? sanitize_text_field( $field['primaryFieldType'] ) : '',
                'better_payment_field_name_heading'  => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
                'better_payment_field_name_placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
                'better_payment_field_type'          => isset( $field['type'] ) ? sanitize_text_field( $field['type'] ) : 'text',
                'better_payment_field_icon'          => array(
                    'value'   => $icon_setting['value'],
                    'library' => $icon_setting['library'],
                ),
                'better_payment_field_name_required' => isset( $field['required'] ) && $field['required'] ? 'yes' : '',
                'better_payment_field_name_show'     => isset( $field['show'] ) && $field['show'] ? 'yes' : '',
                'better_payment_field_name_display_inline' => isset( $field['displayInline'] ) && $field['displayInline'] ? 'inline-block' : '',
            );

            // Add amount field specific settings.
            if ( isset( $field['primaryFieldType'] ) && 'primary_payment_amount' === $field['primaryFieldType'] ) {
                $converted_field['better_payment_field_name_min']     = isset( $field['min'] ) ? intval( $field['min'] ) : 1;
                $converted_field['better_payment_field_name_max']     = isset( $field['max'] ) ? intval( $field['max'] ) : '';
                $converted_field['better_payment_field_name_default'] = isset( $field['default'] ) ? intval( $field['default'] ) : '';
                $converted_field['better_payment_field_name_default_fixed'] = isset( $field['defaultFixed'] ) && $field['defaultFixed'] ? 'yes' : '';
                $converted_field['better_payment_field_name_default_dynamic_enable'] = isset( $field['defaultDynamicEnable'] ) && $field['defaultDynamicEnable'] ? 'yes' : '';
            }

            $form_fields[] = $converted_field;
        }

        return $form_fields;
    }

    /**
     * Convert block amountList array to Elementor widget format.
     *
     * @param array $attributes Block attributes.
     * @return array Converted amount list in Elementor format.
     */
    private function convert_amount_list( $attributes ) {
        $amount_list = array();

        if ( empty( $attributes['amountList'] ) || ! is_array( $attributes['amountList'] ) ) {
            return array();
        }

        foreach ( $attributes['amountList'] as $index => $item ) {
            if ( isset( $item['label'] ) && '' !== $item['label'] ) {
                $amount_list[] = array(
                    '_id'                       => 'amount_' . $index,
                    'better_payment_amount_val' => sanitize_text_field( $item['label'] ),
                );
            }
        }

        return $amount_list;
    }

    /**
     * Normalize icon classes for rendering compatibility.
     *
     * Dashicons require the base `dashicons` class plus the icon class.
     *
     * @param string $icon Icon class string.
     * @return string
     */
    private function normalize_icon_class( $icon ) {
        $icon = trim( (string) $icon );
        if ( '' === $icon ) {
            return $icon;
        }

        if ( false !== strpos( $icon, 'dashicons-' ) && false === strpos( $icon, 'dashicons ' ) ) {
            $icon = 'dashicons ' . $icon;
        }

        return $icon;
    }

    /**
     * Convert block icon value to Elementor ICONS control format.
     *
     * Keeps default state empty-library so existing default notice icons are shown.
     *
     * @param string $icon_value    Icon class or uploaded icon URL.
     * @param string $default_value Default icon class for this control.
     * @return array
     */
    private function build_icon_control_setting( $icon_value, $default_value = '' ) {
        $icon_value = trim( (string) $icon_value );

        // Only return empty library when icon_value is truly empty (no selection)
        // Don't treat icons matching the default as empty - they should render as custom icons
        if ( '' === $icon_value ) {
            return array(
                'value'   => '',
                'library' => '',
            );
        }

        // Check if the value is an image URL (SVG, PNG, JPG, etc.)
        if ( $this->is_icon_url( $icon_value ) ) {
            return array(
                'value'   => array(
                    'url' => esc_url_raw( $icon_value ),
                ),
                'library' => 'svg',
            );
        }

        // For all other non-empty values (custom CSS classes, FontAwesome, etc.)
        // Detect the library type and return it so Handler can render correctly
        return array(
            'value'   => $this->normalize_icon_class( $icon_value ),
            'library' => $this->detect_icon_library( $icon_value ),
        );
    }

    /**
     * Build email logo setting from block email icon value.
     *
     * Email template expects a media-like structure with a `url` key.
     *
     * @param string $icon_value    Icon class or uploaded icon URL.
     * @param string $default_value Default icon class for this control.
     * @return array
     */
    private function build_email_logo_setting( $icon_value, $default_value = '' ) {
        $icon_value = trim( (string) $icon_value );

        if ( '' === $icon_value || ( '' !== $default_value && $icon_value === $default_value ) ) {
            return array();
        }

        if ( $this->is_icon_url( $icon_value ) ) {
            return array(
                'url' => esc_url_raw( $icon_value ),
            );
        }

        return array();
    }

    /**
     * Detect Elementor-style icon library key from icon class string.
     *
     * @param string $icon_value Icon class.
     * @return string
     */
    private function detect_icon_library( $icon_value ) {
        $icon_value = (string) $icon_value;

        // Check for FontAwesome icons (has 'fa-' or 'fa ' prefix)
        if ( false !== strpos( $icon_value, 'fa-' ) || false !== strpos( $icon_value, 'fa ' ) ) {
            // Check for specific FontAwesome variants (fas, far, fab, fal, fad)
            if ( preg_match( '/\bfab\b/', $icon_value ) ) {
                return 'fab';  // FontAwesome Brands
            }
            if ( preg_match( '/\bfar\b/', $icon_value ) ) {
                return 'far';  // FontAwesome Regular
            }
            if ( preg_match( '/\bfal\b/', $icon_value ) ) {
                return 'fal';  // FontAwesome Light
            }
            if ( preg_match( '/\bfad\b/', $icon_value ) ) {
                return 'fad';  // FontAwesome Duotone
            }

            // Default to FontAwesome Solid
            return 'fas';
        }

        // Check for Dashicons
        if ( false !== strpos( $icon_value, 'dashicon' ) ) {
            return 'dashicons';
        }

        // Any other CSS class (custom icons like 'bp-icon bp-check-circle')
        return 'custom';
    }

    /**
     * Check whether an icon value is a URL-like image path.
     *
     * @param string $icon_value Icon value.
     * @return bool
     */
    private function is_icon_url( $icon_value ) {
        $icon_value = trim( (string) $icon_value );

        if ( '' === $icon_value ) {
            return false;
        }

        return 0 === strpos( $icon_value, 'http://' )
            || 0 === strpos( $icon_value, 'https://' )
            || 0 === strpos( $icon_value, '/' )
            || 0 === strpos( $icon_value, 'data:image/' );
    }

    /**
     * Create a widget proxy object for layout templates.
     *
     * This provides the methods that layout files expect from $widgetObj and $this.
     *
     * @param string $block_id The unique block ID.
     * @param array  $settings The settings array.
     * @return object Widget proxy object with required methods.
     */
    private function create_widget_proxy( $block_id, $settings ) {
        return new class( $block_id, $settings ) {
            /**
             * Block ID.
             *
             * @var string
             */
            private $id;

            /**
             * Settings array.
             *
             * @var array
             */
            private $settings;

            /**
             * Constructor.
             *
             * @param string $id       Block ID.
             * @param array  $settings Settings array.
             */
            public function __construct( $id, $settings ) {
                $this->id       = $id;
                $this->settings = $settings;
            }

            /**
             * Get the block ID.
             *
             * @return string Block ID.
             */
            public function get_id() {
                return $this->id;
            }

            /**
             * Get default payment amount text.
             *
             * @param array $settings Settings array.
             * @return string Default amount text.
             */
            public function render_attribute_default_text( $settings ) {
                $render_attribute_default_text = '';

                $items = ! empty( $settings['better_payment_form_fields'] ) ? $settings['better_payment_form_fields'] : array();

                foreach ( $items as $item ) {
                    // Check for primary_payment_amount field type.
                    if ( ! empty( $item['better_payment_primary_field_type'] ) && 'primary_payment_amount' === $item['better_payment_primary_field_type'] ) {
                        $render_attribute_default_text = ! empty( $item['better_payment_field_name_default'] )
                            ? $item['better_payment_field_name_default']
                            : '';
                        break;
                    }
                }

                return $render_attribute_default_text;
            }

            /**
             * Render amount selection element.
             *
             * @param array $settings Settings array.
             * @param array $args     Additional arguments.
             * @return void
             */
            public function render_amount_element( $settings, $args = array() ) {
                if ( empty( $settings['better_payment_amount'] ) ) {
                    return;
                }

                $items = $settings['better_payment_amount'];

                // Get currency symbol and alignment for display.
                $bp_helper_obj          = new \Better_Payment\Lite\Classes\Helper();
                $currency               = ! empty( $settings['better_payment_form_currency'] ) ? $settings['better_payment_form_currency'] : 'USD';
                $currency_symbol        = $bp_helper_obj->get_currency_symbol( esc_html( $currency ) );
                $currency_alignment     = ! empty( $settings['better_payment_form_currency_alignment'] ) ? $settings['better_payment_form_currency_alignment'] : 'left';
                $currency_left          = 'left' === $currency_alignment ? $currency_symbol : '';
                $currency_right         = 'right' === $currency_alignment ? $currency_symbol : '';

                foreach ( $items as $item ) :
                    if ( ! empty( $item['better_payment_amount_val'] ) ) :
                        $uid   = uniqid();
                        $value = floatval( $item['better_payment_amount_val'] );
                        ?>
                        <div class="bp-form__group pt-5">
                            <input type="radio" value="<?php echo esc_attr( $value ); ?>"
                                id="bp_payment_amount-<?php echo esc_attr( $uid ); ?>"
                                class="bp-form__control bp-form_pay-radio"
                                name="primary_payment_amount_radio">
                            <label for="bp_payment_amount-<?php echo esc_attr( $uid ); ?>"><?php printf( '%s%s%s', esc_html( $currency_left ), esc_html( $value ), esc_html( $currency_right ) ); ?></label>
                        </div>
                        <?php
                    endif;
                endforeach;
            }

            /**
             * Render campaign ID hidden field.
             *
             * @param array $settings Settings array.
             * @return string Empty string for blocks.
             */
            public function render_campaign_id_hidden_field( $settings ) {
                return '';
            }
        };
    }

    /**
     * Enqueue editor assets (controls library).
     *
     * @return void
     */
    public function enqueue_editor_assets() {
        // Only load on block editor pages
        $screen = get_current_screen();
        if ( ! $screen || ! $screen->is_block_editor() ) {
            return;
        }

        global $pagenow;
        $editor_type = false;
        if ( $pagenow === 'post-new.php' || $pagenow === 'post.php' ) {
            $editor_type = 'edit-post';
        } elseif ( $pagenow === 'site-editor.php' || ( $pagenow === 'themes.php' && isset( $_GET[ 'page' ] ) && sanitize_text_field( wp_unslash( $_GET[ 'page' ] ) ) === 'gutenberg-edit-site' ) ) {
            $editor_type = 'edit-site';
        } elseif ( $pagenow === 'widgets.php' ) {
            $editor_type = 'edit-widgets';
        }

        // Define EssentialBlocksLocalize before controls load (controls access it at module level)
        $localize_data = array(
            'ajaxurl'               => admin_url( 'admin-ajax.php' ),
            'ajax_url'              => admin_url( 'admin-ajax.php' ),
            'nonce'                 => wp_create_nonce( 'better_payment_block_nonce' ),
            'admin_nonce'           => wp_create_nonce( 'better_payment_admin_nonce' ),
            'rest_rootURL'          => esc_url_raw( rest_url() ),
            'is_pro_active'         => 'false',
            'eb_plugins_url'        => BETTER_PAYMENT_URL . '/',
            'image_url'             => BETTER_PAYMENT_ASSETS . '/img',
            'fontAwesome'           => 'true',
            'googleFont'            => 'true',
            'quickToolbar'          => 'false',
            'enableGenerateImage'   => '0',
            'enableWriteAIInputField' => '0',
            'enableWriteAIRichtext' => '0',
            'enableRewriteAIContent' => '0',
            'hasOpenAiApiKey'       => '0',
            'all_blocks_default'   => array(),
            'all_blocks'           => array(),
            'quick_toolbar_blocks' => array(),
            'responsiveBreakpoints' => array(
                'tablet' => 1024,
                'mobile' => 767,
            ),
        );

        // Add inline script to wp-blocks (which loads early) to define the global variable
        wp_add_inline_script(
            'wp-blocks',
            'window.EssentialBlocksLocalize = window.EssentialBlocksLocalize || ' . wp_json_encode( $localize_data ) . ';',
            'before'
        );

        // Enqueue controls library (must be available for blocks that depend on it)
        wp_enqueue_script( 'better-payment-controls' );
        wp_enqueue_style( 'better-payment-controls-style' );

        // Enqueue necessary CSS files for form styles in editor (used in render.php)
        wp_enqueue_style( 'better-payment-el' );
        wp_enqueue_style( 'bp-icon-front' );
        wp_enqueue_style( 'better-payment-style' );
        wp_enqueue_style( 'better-payment-common-style' );
        wp_enqueue_style( 'better-payment-admin-style' );
        $this->enqueue_font_awesome();
        wp_enqueue_style( 'dashicons' );

        // Get site domain for email defaults
        $site_url_parsed = wp_parse_url( get_site_url() );
        $site_domain     = ! empty( $site_url_parsed['host'] ) ? esc_html( $site_url_parsed['host'] ) : 'example.com';

        // Localize script data for blocks
        wp_localize_script( 'better-payment-payment-form-editor', 'betterPaymentBlockData', array(
            'ajaxurl'               => admin_url( 'admin-ajax.php' ),
            'nonce'                 => wp_create_nonce( 'better_payment_block_nonce' ),
            'betterPaymentSettings' => get_option( 'better_payment_settings' ),
            'currencies'            => $this->get_currency_list(),
            'assetsUrl'             => BETTER_PAYMENT_ASSETS,
            'adminEmail'            => get_option( 'admin_email' ),
            'siteName'              => get_bloginfo( 'name' ),
            'siteDomain'            => $site_domain,
        ) );
        wp_localize_script('better-payment-payment-form-editor', 'eb_conditional_localize',
            $editor_type !== false ? [
                'editor_type' => $editor_type
            ] : []
		);
    }

    /**
     * Get the current editor type.
     *
     * @return string
     */
    private function get_editor_type() {
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
                return 'edit-post';
            }
        }
        return 'edit-post';
    }

    /**
     * Register the Better Payment block category.
     *
     * @param array                   $categories Block categories.
     * @param WP_Block_Editor_Context $context    Block editor context.
     * @return array Modified block categories.
     */
    public function register_block_category( $categories, $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        // Check if our category already exists
        foreach ( $categories as $category ) {
            if ( 'better-payment' === $category['slug'] ) {
                return $categories;
            }
        }

        // Add our category at the beginning
        array_unshift(
            $categories,
            array(
                'slug'  => 'better-payment',
                'title' => __( 'Better Payment', 'better-payment' ),
                'icon'  => 'money-alt',
            )
        );

        return $categories;
    }

    /**
     * Register all blocks.
     *
     * @return void
     */
    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        foreach ( $this->blocks as $block_key => $block_config ) {
            if ( ! $this->is_block_registered( $block_config['name'] ) ) {
                $this->register_single_block( $block_key, $block_config );
            }
        }
    }

    /**
     * Register a single block.
     *
     * @param string $block_key    Block key.
     * @param array  $block_config Block configuration.
     * @return void
     */
    private function register_single_block( $block_key, $block_config ) {
        // Use assets/blocks path for block.json since src/ is excluded from production builds.
        // The block.json is copied to assets/blocks/ during the build process.
        $block_json_path = BETTER_PAYMENT_PATH . '/assets/blocks/blocks/' . $block_config['path'] . '/block.json';

        // Fallback to src/blocks for development environment (assets may not be built yet)
        if ( ! file_exists( $block_json_path ) ) {
            $block_json_path = BETTER_PAYMENT_PATH . '/src/blocks/' . $block_config['path'] . '/block.json';
        }

        // Check if block.json exists
        if ( ! file_exists( $block_json_path ) ) {
            return;
        }

        // Get asset file for dependencies
        $asset_file = $this->get_asset_file( $block_config['path'] );

        // Add controls as a dependency
        $dependencies = array_merge( $asset_file['dependencies'], array( 'better-payment-controls' ) );

        // Register editor script
        $editor_script_handle = 'better-payment-' . $block_key . '-editor';

        if ( file_exists( BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_config['path'] . '/index.min.js' ) ) {
            wp_register_script(
                $editor_script_handle,
                BETTER_PAYMENT_ASSETS . '/blocks/' . $block_config['path'] . '/index.min.js',
                $dependencies,
                $asset_file['version'],
                true
            );
        } elseif ( file_exists( BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_config['path'] . '/index.js' ) ) {
            wp_register_script(
                $editor_script_handle,
                BETTER_PAYMENT_ASSETS . '/blocks/' . $block_config['path'] . '/index.js',
                $dependencies,
                $asset_file['version'],
                true
            );
        }

        // Register frontend/editor style
        $style_handle = 'better-payment-' . $block_key . '-style';
        $style_dependencies = array(
            'better-payment-controls-style',
            'better-payment-el',
            'bp-icon-front',
            'better-payment-style',
            'better-payment-common-style',
            'better-payment-admin-style',
            'dashicons',
        );
        if ( ! $this->is_font_awesome_provided() ) {
            $style_dependencies[] = 'bp-font-awesome';
        }
        if ( file_exists( BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_config['path'] . '/style.min.css' ) ) {
            wp_register_style(
                $style_handle,
                BETTER_PAYMENT_ASSETS . '/blocks/' . $block_config['path'] . '/style.min.css',
                $style_dependencies,
                $asset_file['version']
            );
        } elseif ( file_exists( BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_config['path'] . '/style.css' ) ) {
            wp_register_style(
                $style_handle,
                BETTER_PAYMENT_ASSETS . '/blocks/' . $block_config['path'] . '/style.css',
                $style_dependencies,
                $asset_file['version']
            );
        }

        // Build block registration args
        $block_args = array(
            'editor_script' => $editor_script_handle,
            'style'         => $style_handle,
        );

        // Add render callback for payment-form block
        if ( 'payment-form' === $block_key ) {
            $block_args['render_callback'] = array( $this, 'render_payment_form_frontend' );
        }

        // Register block with WordPress
        $result = register_block_type( $block_json_path, $block_args );

        if ( false === $result ) {
            // Log error in debug mode
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Better Payment: Failed to register block ' . $block_config['name'] );
            }
        }
    }

    /**
     * Get asset file with dependencies and version.
     *
     * @param string $block_path Block path.
     * @return array Asset file data.
     */
    private function get_asset_file( $block_path ) {
        $asset_file_path = BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_path . '/index.min.asset.php';

        if ( ! file_exists( $asset_file_path ) ) {
            $asset_file_path = BETTER_PAYMENT_PATH . '/assets/blocks/' . $block_path . '/index.asset.php';
        }

        if ( file_exists( $asset_file_path ) ) {
            return include $asset_file_path;
        }

        return array(
            'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            'version'      => BETTER_PAYMENT_VERSION,
        );
    }


    /**
     * Get the blocks directory path.
     *
     * @return string
     */
    public function get_blocks_path() {
        return BETTER_PAYMENT_PATH . '/src/blocks/';
    }

    /**
     * Get the blocks assets URL.
     *
     * @return string
     */
    public function get_blocks_url() {
        return BETTER_PAYMENT_ASSETS . '/blocks/';
    }

    /**
     * Check if a block is registered.
     *
     * @param string $block_name Full block name (e.g., 'better-payment/payment-form').
     * @return bool
     */
    public function is_block_registered( $block_name ) {
        return \WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
    }

    /**
     * Check if Font Awesome is already provided by another plugin (e.g. Elementor).
     *
     * @return bool
     */
    private function is_font_awesome_provided() {
        $handles = [
            'font-awesome-5-all',
            'font-awesome-6-all',
            'elementor-icons-fa-solid',
            'elementor-icons-fa-brands',
            'elementor-icons-fa-regular',
        ];

        foreach ( $handles as $handle ) {
            if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue Font Awesome only if not already provided by another plugin.
     *
     * @return void
     */
    private function enqueue_font_awesome() {
        if ( ! $this->is_font_awesome_provided() ) {
            wp_enqueue_style( 'bp-font-awesome' );
        }
    }
}
