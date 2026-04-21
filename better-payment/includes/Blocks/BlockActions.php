<?php
/**
 * Block Actions for Better Payment Gutenberg blocks.
 *
 * Handles payment processing for Gutenberg block forms, separate from Elementor widget actions.
 * This class DOES NOT modify any existing Elementor methods - it provides block-specific handlers.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

namespace Better_Payment\Lite\Blocks;

use Better_Payment\Lite\Classes\Handler;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block Actions class.
 *
 * @since 1.0.0
 */
class BlockActions {
    use TraitsHelper;

    /**
     * Constructor.
     *
     * Register block-specific payment handlers with higher priority.
     * These run BEFORE the Elementor widget handlers.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_post_paypal_form_handle', array( $this, 'block_paypal_form_handle' ) );
        add_action( 'admin_post_nopriv_paypal_form_handle', array( $this, 'block_paypal_form_handle' ) );

        add_action( 'wp_ajax_better_payment_stripe_get_token', array( $this, 'block_stripe_get_token' ) );
        add_action( 'wp_ajax_nopriv_better_payment_stripe_get_token', array( $this, 'block_stripe_get_token' ) );

        add_action( 'wp_ajax_better_payment_paystack_get_token', array( $this, 'block_paystack_get_token' ) );
        add_action( 'wp_ajax_nopriv_better_payment_paystack_get_token', array( $this, 'block_paystack_get_token' ) );
    }

    /**
     * Get block settings.
     *
     * First tries to get from transient (set during page render).
     * If not found, parses the block directly from post content (works even with caching).
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID.
     * @return array|false The block settings or false if not found.
     */
    private function get_block_settings( $page_id, $widget_id ) {
        // First, try to get from transient (fastest).
        $transient_key = 'bp_block_settings_' . $page_id . '_' . $widget_id;
        $settings      = get_transient( $transient_key );

        if ( ! empty( $settings ) && is_array( $settings ) ) {
            return $settings;
        }

        // Transient not found - parse block from post content.
        // This handles cases where the page is cached.
        $settings = $this->parse_block_settings_from_post( $page_id, $widget_id );

        if ( ! empty( $settings ) && is_array( $settings ) ) {
            // Cache in transient for future requests.
            set_transient( $transient_key, $settings, HOUR_IN_SECONDS );
            return $settings;
        }

        return false;
    }

    /**
     * Parse block settings directly from post content.
     *
     * This method finds the Better Payment block in the post content and extracts its attributes.
     * Similar to how Elementor's get_elementor_widget_settings works.
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID (blockId attribute).
     * @return array|false The block settings or false if not found.
     */
    private function parse_block_settings_from_post( $page_id, $widget_id ) {
        $post = get_post( $page_id );

        if ( ! $post || empty( $post->post_content ) ) {
            return false;
        }

        // Parse blocks from post content.
        $blocks = parse_blocks( $post->post_content );

        // Find the Better Payment block with matching blockId.
        $block_attributes = $this->find_payment_block_recursive( $blocks, $widget_id );

        if ( empty( $block_attributes ) ) {
            return false;
        }

        // Get global settings.
        $global_settings = \Better_Payment\Lite\Admin\DB::get_settings();

        // Build settings using BlockManager's method.
        $form_layout = ! empty( $block_attributes['formLayout'] ) ? $block_attributes['formLayout'] : 'layout-1';
        $settings    = BlockManager::get_instance()->build_block_settings( $block_attributes, $global_settings, $form_layout );

        return $settings;
    }

    /**
     * Recursively find a Better Payment block with matching blockId.
     *
     * @param array  $blocks    Array of parsed blocks.
     * @param string $widget_id The blockId to find.
     * @return array|false Block attributes or false if not found.
     */
    private function find_payment_block_recursive( $blocks, $widget_id ) {
        foreach ( $blocks as $block ) {
            // Check if this is a Better Payment block with matching blockId.
            if ( 'better-payment/payment-form' === $block['blockName'] ) {
                $block_id = isset( $block['attrs']['blockId'] ) ? $block['attrs']['blockId'] : '';

                if ( $block_id === $widget_id ) {
                    return $block['attrs'];
                }
            }

            // Check inner blocks.
            if ( ! empty( $block['innerBlocks'] ) ) {
                $found = $this->find_payment_block_recursive( $block['innerBlocks'], $widget_id );
                if ( $found ) {
                    return $found;
                }
            }
        }

        return false;
    }

    /**
     * Check if this is a block form submission.
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID.
     * @return bool True if this is a block form, false otherwise.
     */
    private function is_block_form( $page_id, $widget_id ) {
        $settings = $this->get_block_settings( $page_id, $widget_id );
        return ! empty( $settings ) && is_array( $settings );
    }

    /**
     * Handle PayPal form submission for Gutenberg blocks.
     *
     * This intercepts the form submission before the Elementor handler.
     * If settings are found in transient (block form), process here.
     * Otherwise, return and let the Elementor handler process it.
     *
     * @since 1.0.0
     */
    public function block_paypal_form_handle() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // Bail immediately if not a Gutenberg block submission — zero DB overhead for Elementor forms.
        if ( empty( $_POST['better_payment_source'] ) || 'gutenberg' !== $_POST['better_payment_source'] ) {
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // Verify nonce.
        if ( ! check_admin_referer( 'better-payment-paypal', 'security' ) ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $page_id   = ! empty( $_POST['better_payment_page_id'] ) ? intval( $_POST['better_payment_page_id'] ) : 0;
        $widget_id = ! empty( $_POST['better_payment_widget_id'] ) ? sanitize_text_field( $_POST['better_payment_widget_id'] ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( empty( $page_id ) || empty( $widget_id ) ) {
            return; // Let the original handler deal with missing data.
        }

        // Check if this is a block form by looking for transient settings.
        if ( ! $this->is_block_form( $page_id, $widget_id ) ) {
            return; // Not a block form, let Elementor handler process it.
        }

        // This is a block form - handle the payment here.
        $this->process_paypal_payment( $page_id, $widget_id );
    }

    /**
     * Process PayPal payment for block forms.
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID.
     */
    private function process_paypal_payment( $page_id, $widget_id ) {
        $el_settings = $this->get_block_settings( $page_id, $widget_id );

        if ( empty( $el_settings ) ) {
            $this->redirect_previous_page();
            return;
        }

        if ( empty( $el_settings['better_payment_paypal_business_email'] ) ) {
            $this->redirect_previous_page();
            return;
        }

        if ( 'yes' === $el_settings['better_payment_paypal_live_mode'] ) {
            $path = 'paypal';
        } else {
            $path = 'sandbox.paypal';
        }

        $el_settings_currency    = $el_settings['better_payment_form_currency'];
        $woo_product_id          = ! empty( $el_settings['better_payment_form_woocommerce_product_id'] ) ? intval( $el_settings['better_payment_form_woocommerce_product_id'] ) : 0;
        $woo_product_ids         = ! empty( $el_settings['better_payment_form_woocommerce_product_ids'] ) ? $el_settings['better_payment_form_woocommerce_product_ids'] : array( 0 );
        $fluentcart_product_id   = ! empty( $el_settings['better_payment_form_fluentcart_product_id'] ) ? intval( $el_settings['better_payment_form_fluentcart_product_id'] ) : 0;
        $fluentcart_product_ids  = ! empty( $el_settings['better_payment_form_fluentcart_product_ids'] ) ? $el_settings['better_payment_form_fluentcart_product_ids'] : array( 0 );
        $is_layout_6             = ! empty( $el_settings['better_payment_form_layout'] ) && 'layout-6-pro' === $el_settings['better_payment_form_layout'];
        $is_fluentcart_layout    = $is_layout_6 && ! empty( $el_settings['better_payment_form_layout_6_ecommerce_platform'] ) && 'fluentcart' === $el_settings['better_payment_form_layout_6_ecommerce_platform'];
        $is_woo_layout           = $is_layout_6 && ( empty( $el_settings['better_payment_form_layout_6_ecommerce_platform'] ) || 'woocommerce' === $el_settings['better_payment_form_layout_6_ecommerce_platform'] );

        // Currency handling.
        if ( ! empty( $el_settings['better_payment_form_currency_use_woocommerce'] ) && 'yes' === $el_settings['better_payment_form_currency_use_woocommerce'] &&
            ! empty( $el_settings['better_payment_form_currency_woocommerce'] ) ) {
            $el_settings_currency = $el_settings['better_payment_form_currency_woocommerce'];
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! empty( $_POST['campaign_currency'] ) ) {
            $el_settings_currency = sanitize_text_field( $_POST['campaign_currency'] );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html( $el_settings_currency ) );

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $primary_payment_amount = isset( $_POST['primary_payment_amount'] ) ? floatval( $_POST['primary_payment_amount'] ) : 0;

        if ( empty( $_POST['primary_payment_amount'] ) && ! empty( $_POST['primary_payment_amount_radio'] ) ) {
            $primary_payment_amount = floatval( $_POST['primary_payment_amount_radio'] );
        }

        $primary_payment_amount_quantity = ! empty( $_POST['payment_amount_quantity'] ) ? intval( $_POST['payment_amount_quantity'] ) : '';
        if ( $is_woo_layout ) {
            $primary_payment_amount_quantity = 1;
        }

        $primary_payment_amount = ! empty( $primary_payment_amount_quantity ) ? $primary_payment_amount * $primary_payment_amount_quantity : $primary_payment_amount;

        if ( $primary_payment_amount <= 0 ) {
            $this->redirect_previous_page();
            return;
        }

        $order_id            = 'paypal_' . uniqid();
        $paypal_button_type  = ! empty( $el_settings['better_payment_paypal_button_type'] ) ? $el_settings['better_payment_paypal_button_type'] : '_xclick';
        $site_url            = get_permalink( $page_id );
        $return_url          = ! empty( $_POST['return'] ) ? wp_validate_redirect( esc_url_raw( $_POST['return'] ), $site_url ) : '';
        $cancel_return_url   = ! empty( $_POST['cancel_return'] ) ? wp_validate_redirect( esc_url_raw( $_POST['cancel_return'] ), $site_url ) : '';
        $cancel_return_url   = add_query_arg( 'better_payment_paypal_id', $order_id, $cancel_return_url );

        $request_data = array(
            'business'      => $el_settings['better_payment_paypal_business_email'],
            'currency_code' => $el_settings_currency,
            'rm'            => '2',
            'return'        => $return_url,
            'cancel_return' => $cancel_return_url,
            'item_number'   => $order_id,
            'item_name'     => ! empty( $el_settings['better_payment_form_title'] ) ? esc_html__( $el_settings['better_payment_form_title'], 'better-payment' ) : esc_html__( 'Better Payment', 'better-payment' ),
            'amount'        => $primary_payment_amount,
            'cmd'           => $paypal_button_type,
        );

        $product_ids = array(
            'woo_product_ids'       => $woo_product_ids,
            'fluentcart_product_ids' => $fluentcart_product_ids,
        );

        $detailed_product_info = $this->get_detailed_product_info( $product_ids );

        // Form fields data to send via email.
        $better_form_fields = array(
            'amount'               => sanitize_text_field( $el_settings_currency_symbol ) . $primary_payment_amount,
            'referer_page_id'      => $page_id,
            'referer_widget_id'    => $widget_id,
            'woo_product_id'       => $woo_product_id,
            'woo_product_ids'      => maybe_serialize( $woo_product_ids ),
            'fluentcart_product_id' => $fluentcart_product_id,
            'fluentcart_product_ids' => maybe_serialize( $fluentcart_product_ids ),
            'source'               => 'paypal',
            'amount_quantity'      => ! empty( $primary_payment_amount_quantity ) ? intval( $primary_payment_amount_quantity ) : '',
            'is_woo_layout'        => $is_woo_layout,
            'is_fluentcart_layout' => $is_fluentcart_layout,
            'detailed_product_info' => maybe_serialize( $detailed_product_info ),
        );

        $better_form_fields = array_merge( $better_form_fields, $this->fetch_better_form_fields( $el_settings, $_POST ) );

        if ( ! empty( $better_form_fields['primary_first_name'] ) ) {
            $request_data['primary_first_name'] = sanitize_text_field( $better_form_fields['primary_first_name'] );
        }

        if ( ! empty( $better_form_fields['primary_last_name'] ) ) {
            $request_data['primary_last_name'] = sanitize_text_field( $better_form_fields['primary_last_name'] );
        }

        if ( ! empty( $better_form_fields['primary_email'] ) ) {
            $request_data['primary_email'] = sanitize_email( $better_form_fields['primary_email'] );
        }

        if ( ! empty( $better_form_fields['primary_reference_number'] ) ) {
            $request_data['invoice'] = sanitize_text_field( $better_form_fields['primary_reference_number'] );
        }

        $campaign_id = ! empty( $_POST['campaign_id'] ) ? sanitize_text_field( $_POST['campaign_id'] ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        Handler::payment_create(
            array(
                'amount'          => floatval( $primary_payment_amount ),
                'order_id'        => $order_id,
                'payment_date'    => gmdate( 'Y-m-d H:i:s' ),
                'source'          => 'paypal',
                'form_fields_info' => maybe_serialize( $better_form_fields ),
                'currency'        => sanitize_text_field( $el_settings_currency ),
                'referer'         => 'gutenberg-block',
                'campaign_id'     => $campaign_id,
            )
        );

        $paypal_url  = "https://www.$path.com/cgi-bin/webscr?";
        $paypal_url .= http_build_query( $request_data );

        wp_redirect( esc_url_raw( $paypal_url ) );
        exit;
    }

    /**
     * Handle Stripe token request for Gutenberg blocks.
     *
     * @since 1.0.0
     */
    public function block_stripe_get_token() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // Bail immediately if not a Gutenberg block submission — zero DB overhead for Elementor forms.
        if ( empty( $_POST['setting_data']['source'] ) || 'gutenberg' !== $_POST['setting_data']['source'] ) {
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // Verify nonce.
        if ( ! check_admin_referer( 'better-payment', 'security' ) ) {
            wp_send_json_error( esc_html__( 'Nonce verification failed.', 'better-payment' ) );
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $page_id   = isset( $_POST['setting_data']['page_id'] ) ? intval( $_POST['setting_data']['page_id'] ) : 0;
        $widget_id = isset( $_POST['setting_data']['widget_id'] ) ? sanitize_text_field( $_POST['setting_data']['widget_id'] ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( empty( $page_id ) || empty( $widget_id ) ) {
            return; // Let the original handler deal with missing data.
        }

        // Check if this is a block form by looking for settings.
        if ( ! $this->is_block_form( $page_id, $widget_id ) ) {
            return; // Not a block form, let Elementor handler process it.
        }

        // This is a block form - handle the Stripe payment here.
        $this->process_stripe_payment( $page_id, $widget_id );
    }

    /**
     * Handle Paystack token request for Gutenberg blocks.
     *
     * @since 1.0.0
     */
    public function block_paystack_get_token() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // Bail immediately if not a Gutenberg block submission — zero DB overhead for Elementor forms.
        if ( empty( $_POST['setting_data']['source'] ) || 'gutenberg' !== $_POST['setting_data']['source'] ) {
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        // Verify nonce.
        if ( ! check_admin_referer( 'better-payment', 'security' ) ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $page_id   = isset( $_POST['setting_data']['page_id'] ) ? intval( $_POST['setting_data']['page_id'] ) : 0;
        $widget_id = isset( $_POST['setting_data']['widget_id'] ) ? sanitize_text_field( $_POST['setting_data']['widget_id'] ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( empty( $page_id ) || empty( $widget_id ) ) {
            return; // Let the original handler deal with missing data.
        }

        // Check if this is a block form by looking for transient settings.
        if ( ! $this->is_block_form( $page_id, $widget_id ) ) {
            return; // Not a block form, let Elementor handler process it.
        }

        // This is a block form - handle the Paystack payment here.
        $this->process_paystack_payment( $page_id, $widget_id );
    }

    /**
     * Process Stripe payment for block forms.
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID.
     */
    private function process_stripe_payment( $page_id, $widget_id ) {
        $el_settings = $this->get_block_settings( $page_id, $widget_id );

        if ( empty( $el_settings ) ) {
            wp_send_json_error( esc_html__( 'Setting Data is missing', 'better-payment' ) );
        }

        $better_payment_keys = array(
            'public_key' => 'yes' === sanitize_text_field( $el_settings['better_payment_stripe_live_mode'] ) ? sanitize_text_field( $el_settings['better_payment_stripe_public_key_live'] ) : sanitize_text_field( $el_settings['better_payment_stripe_public_key'] ),
            'secret_key' => 'yes' === sanitize_text_field( $el_settings['better_payment_stripe_live_mode'] ) ? sanitize_text_field( $el_settings['better_payment_stripe_secret_key_live'] ) : sanitize_text_field( $el_settings['better_payment_stripe_secret_key'] ),
        );

        if ( empty( $better_payment_keys['public_key'] ) || empty( $better_payment_keys['secret_key'] ) ) {
            wp_send_json_error( esc_html__( 'Stripe Key missing', 'better-payment' ) );
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $amount = isset( $_POST['fields']['primary_payment_amount'] ) ? floatval( $_POST['fields']['primary_payment_amount'] ) : 0;

        if ( empty( $_POST['fields']['primary_payment_amount'] ) && ! empty( $_POST['fields']['primary_payment_amount_radio'] ) ) {
            $amount = floatval( $_POST['fields']['primary_payment_amount_radio'] );
        }

        $amount_quantity = ! empty( $_POST['fields']['payment_amount_quantity'] ) ? intval( $_POST['fields']['payment_amount_quantity'] ) : '';
        $amount          = ! empty( $amount_quantity ) ? $amount * $amount_quantity : $amount;

        if ( $amount <= 0 ) {
            wp_send_json_error( esc_html__( 'Invalid payment amount.', 'better-payment' ) );
            return;
        }

        $header_info = array(
            'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $better_payment_keys['secret_key'] ) . ':' ),
            'Stripe-Version' => '2019-05-16',
        );

        $order_id             = 'stripe_' . uniqid();
        $el_settings_currency = $el_settings['better_payment_form_currency'];

        if ( ! empty( $el_settings['better_payment_form_currency_use_woocommerce'] ) && 'yes' === $el_settings['better_payment_form_currency_use_woocommerce'] &&
            ! empty( $el_settings['better_payment_form_currency_woocommerce'] ) ) {
            $el_settings_currency = $el_settings['better_payment_form_currency_woocommerce'];
        }
        if ( ! empty( $_POST['fields']['campaign_currency'] ) ) {
            $el_settings_currency = sanitize_text_field( $_POST['fields']['campaign_currency'] );
        }

        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html( $el_settings_currency ) );

        $redirection_url_success = get_permalink( $page_id );
        $redirection_url_error   = get_permalink( $page_id );

        $redirection_url_success = add_query_arg(
            array(
                'better_payment_stripe_status' => 'success',
                'better_payment_widget_id'     => $widget_id,
            ),
            $redirection_url_success
        );

        $redirection_url_error = add_query_arg(
            array(
                'better_payment_error_status' => 'error',
                'better_payment_widget_id'    => $widget_id,
            ),
            $redirection_url_error
        );

        // Build form fields.
        $woo_product_id         = ! empty( $el_settings['better_payment_form_woocommerce_product_id'] ) ? intval( $el_settings['better_payment_form_woocommerce_product_id'] ) : 0;
        $woo_product_ids        = ! empty( $el_settings['better_payment_form_woocommerce_product_ids'] ) ? $el_settings['better_payment_form_woocommerce_product_ids'] : array( 0 );
        $fluentcart_product_id  = ! empty( $el_settings['better_payment_form_fluentcart_product_id'] ) ? intval( $el_settings['better_payment_form_fluentcart_product_id'] ) : 0;
        $fluentcart_product_ids = ! empty( $el_settings['better_payment_form_fluentcart_product_ids'] ) ? $el_settings['better_payment_form_fluentcart_product_ids'] : array( 0 );

        $product_ids = array(
            'woo_product_ids'       => $woo_product_ids,
            'fluentcart_product_ids' => $fluentcart_product_ids,
        );

        $detailed_product_info = $this->get_detailed_product_info( $product_ids );

        $better_form_fields = array(
            'amount'                => sanitize_text_field( $el_settings_currency_symbol ) . $amount,
            'referer_page_id'       => $page_id,
            'referer_widget_id'     => $widget_id,
            'woo_product_id'        => $woo_product_id,
            'woo_product_ids'       => maybe_serialize( $woo_product_ids ),
            'fluentcart_product_id' => $fluentcart_product_id,
            'fluentcart_product_ids' => maybe_serialize( $fluentcart_product_ids ),
            'source'                => 'stripe',
            'amount_quantity'       => ! empty( $amount_quantity ) ? intval( $amount_quantity ) : '',
            'detailed_product_info' => maybe_serialize( $detailed_product_info ),
        );

        $better_form_fields = array_merge( $better_form_fields, $this->fetch_better_form_fields( $el_settings, $_POST['fields'] ) );

        $item_name = ! empty( $el_settings['better_payment_form_title'] ) ? esc_html__( $el_settings['better_payment_form_title'], 'better-payment' ) : esc_html__( 'Better Payment', 'better-payment' );

        // The Stripe Price ID (when Payment Source = Stripe) is display-only —
        // it pre-fills the amount input on the frontend (see layout-1/2/3.php line input value).
        // The payment session always uses price_data with the submitted amount,
        // matching the Elementor widget behaviour for layouts 1/2/3.
        $line_item = array(
            'price_data' => array(
                'currency'     => sanitize_text_field( $el_settings_currency ),
                'unit_amount'  => intval( $amount * 100 ),
                'product_data' => array(
                    'name' => $item_name,
                ),
            ),
            'quantity'   => 1,
        );

        $request_body = array(
            'line_items'                 => array( $line_item ),
            'mode'                       => 'payment',
            'locale'                     => 'auto',
            'payment_method_types'       => array( 'card' ),
            'billing_address_collection' => 'required',
            'client_reference_id'        => time(),
            'metadata'                   => array(
                'order_id' => $order_id,
            ),
            'success_url' => esc_url_raw( $redirection_url_success ) . '&better_payment_stripe_id=' . $order_id,
            'cancel_url'  => add_query_arg(
                array(
                    'better_payment_stripe_id' => $order_id,
                ),
                esc_url_raw( $redirection_url_error )
            ),
        );

        $request_body['payment_intent_data'] = array(
            'capture_method' => 'automatic',
            'description'    => $item_name,
            'metadata'       => array(
                'order_id' => $order_id,
            ),
        );

        $primary_email = ! empty( $better_form_fields['primary_email'] ) ? sanitize_email( $better_form_fields['primary_email'] ) : '';

        if ( ! empty( $primary_email ) ) {
            $request_body['customer_email']                                    = $primary_email;
            $request_body['metadata']['customer_email']                        = $primary_email;
            $request_body['payment_intent_data']['metadata']['customer_email'] = $primary_email;
        }

        // Build customer name from first/last name fields (mirrors widget behaviour).
        $customer_name = '';
        if ( ! empty( $better_form_fields['primary_first_name'] ) ) {
            $customer_name = sanitize_text_field( $better_form_fields['primary_first_name'] );
        }
        if ( ! empty( $better_form_fields['primary_last_name'] ) ) {
            $customer_name = trim( $customer_name . ' ' . sanitize_text_field( $better_form_fields['primary_last_name'] ) );
        }

        if ( ! empty( $customer_name ) ) {
            $request_body['metadata']['customer_name']                        = $customer_name;
            $request_body['payment_intent_data']['metadata']['customer_name'] = $customer_name;
        }

        $request = wp_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            array(
                'headers' => $header_info,
                'body'    => $request_body,
            )
        );

        if ( is_wp_error( $request ) ) {
            wp_send_json_error( sanitize_text_field( $request->get_error_message() ) );
            return;
        }

        $response_ar = json_decode( wp_remote_retrieve_body( $request ) );

        if ( null === $response_ar ) {
            wp_send_json_error( esc_html__( 'Invalid response from Stripe.', 'better-payment' ) );
            return;
        }

        if ( ! empty( $response_ar->payment_intent ) || ( ! empty( $response_ar->mode ) && 'subscription' === $response_ar->mode ) ) {
            $campaign_id = ! empty( $_POST['fields']['campaign_id'] ) ? sanitize_text_field( $_POST['fields']['campaign_id'] ) : '';

            Handler::payment_create(
                array(
                    'amount'           => floatval( $amount ),
                    'order_id'         => $order_id,
                    'payment_date'     => gmdate( 'Y-m-d H:i:s' ),
                    'source'           => 'stripe',
                    'transaction_id'   => sanitize_text_field( $response_ar->payment_intent ),
                    'customer_info'    => maybe_serialize( $response_ar ),
                    'form_fields_info' => maybe_serialize( $better_form_fields ),
                    'obj_id'           => sanitize_text_field( $response_ar->id ),
                    'status'           => sanitize_text_field( $response_ar->payment_status ),
                    'currency'         => sanitize_text_field( $el_settings_currency ),
                    'referer'          => 'gutenberg-block',
                    'campaign_id'      => $campaign_id,
                )
            );

            wp_send_json_success(
                array(
                    'stripe_data'       => sanitize_text_field( $response_ar->id ),
                    'stripe_public_key' => sanitize_text_field( $better_payment_keys['public_key'] ),
                )
            );
        } else {
            $error_message = 'Something went wrong!';

            if ( isset( $response_ar->error ) ) {
                $error_message = sanitize_text_field( $response_ar->error->message );
            }

            wp_send_json_error( $error_message );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Process Paystack payment for block forms.
     *
     * @param int    $page_id   The page ID.
     * @param string $widget_id The widget/block ID.
     */
    private function process_paystack_payment( $page_id, $widget_id ) {
        $el_settings = $this->get_block_settings( $page_id, $widget_id );

        if ( empty( $el_settings ) ) {
            wp_send_json_error( esc_html__( 'Setting Data is missing', 'better-payment' ) );
        }

        if ( empty( $el_settings['better_payment_paystack_public_key'] ) || empty( $el_settings['better_payment_paystack_secret_key'] ) ) {
            wp_send_json_error( esc_html__( 'Paystack Key missing', 'better-payment' ) );
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $amount = isset( $_POST['fields']['primary_payment_amount'] ) ? floatval( $_POST['fields']['primary_payment_amount'] ) : 0;

        if ( empty( $_POST['fields']['primary_payment_amount'] ) && ! empty( $_POST['fields']['primary_payment_amount_radio'] ) ) {
            $amount = floatval( $_POST['fields']['primary_payment_amount_radio'] );
        }

        $amount_quantity = ! empty( $_POST['fields']['payment_amount_quantity'] ) ? intval( $_POST['fields']['payment_amount_quantity'] ) : '';
        $amount          = ! empty( $amount_quantity ) ? $amount * $amount_quantity : $amount;

        if ( $amount <= 0 ) {
            wp_send_json_error( esc_html__( 'Invalid payment amount.', 'better-payment' ) );
            return;
        }

        $header_info = array(
            'Authorization' => 'Bearer ' . sanitize_text_field( $el_settings['better_payment_paystack_secret_key'] ),
            'Cache-Control: no-cache',
        );

        $order_id             = 'paystack_' . uniqid();
        $el_settings_currency = $el_settings['better_payment_form_currency'];

        if ( ! empty( $el_settings['better_payment_form_currency_use_woocommerce'] ) && 'yes' === $el_settings['better_payment_form_currency_use_woocommerce'] &&
            ! empty( $el_settings['better_payment_form_currency_woocommerce'] ) ) {
            $el_settings_currency = $el_settings['better_payment_form_currency_woocommerce'];
        }
        if ( ! empty( $_POST['fields']['campaign_currency'] ) ) {
            $el_settings_currency = sanitize_text_field( $_POST['fields']['campaign_currency'] );
        }

        $el_settings_currency_symbol = $this->get_currency_symbol( esc_html( $el_settings_currency ) );

        $redirection_url_success = get_permalink( $page_id );
        $redirection_url_error   = get_permalink( $page_id );

        $redirection_url_success = add_query_arg(
            array(
                'better_payment_paystack_status' => 'success',
                'better_payment_widget_id'       => $widget_id,
            ),
            $redirection_url_success
        );

        $redirection_url_error = add_query_arg(
            array(
                'better_payment_error_status' => 'error',
                'better_payment_widget_id'    => $widget_id,
            ),
            $redirection_url_error
        );

        // Build form fields.
        $woo_product_id         = ! empty( $el_settings['better_payment_form_woocommerce_product_id'] ) ? intval( $el_settings['better_payment_form_woocommerce_product_id'] ) : 0;
        $woo_product_ids        = ! empty( $el_settings['better_payment_form_woocommerce_product_ids'] ) ? $el_settings['better_payment_form_woocommerce_product_ids'] : array( 0 );
        $fluentcart_product_id  = ! empty( $el_settings['better_payment_form_fluentcart_product_id'] ) ? intval( $el_settings['better_payment_form_fluentcart_product_id'] ) : 0;
        $fluentcart_product_ids = ! empty( $el_settings['better_payment_form_fluentcart_product_ids'] ) ? $el_settings['better_payment_form_fluentcart_product_ids'] : array( 0 );

        $product_ids = array(
            'woo_product_ids'       => $woo_product_ids,
            'fluentcart_product_ids' => $fluentcart_product_ids,
        );

        $detailed_product_info = $this->get_detailed_product_info( $product_ids );

        $better_form_fields = array(
            'amount'                => sanitize_text_field( $el_settings_currency_symbol ) . $amount,
            'referer_page_id'       => $page_id,
            'referer_widget_id'     => $widget_id,
            'woo_product_id'        => $woo_product_id,
            'woo_product_ids'       => maybe_serialize( $woo_product_ids ),
            'fluentcart_product_id' => $fluentcart_product_id,
            'fluentcart_product_ids' => maybe_serialize( $fluentcart_product_ids ),
            'source'                => 'paystack',
            'amount_quantity'       => ! empty( $amount_quantity ) ? intval( $amount_quantity ) : '',
            'detailed_product_info' => maybe_serialize( $detailed_product_info ),
        );

        $better_form_fields = array_merge( $better_form_fields, $this->fetch_better_form_fields( $el_settings, $_POST['fields'] ) );

        $primary_email = ! empty( $better_form_fields['primary_email'] ) ? sanitize_email( $better_form_fields['primary_email'] ) : '';

        $request_body = array(
            'amount'       => intval( $amount * 100 ),
            'currency'     => sanitize_text_field( $el_settings_currency ),
            'email'        => $primary_email,
            'callback_url' => esc_url_raw( $redirection_url_success ) . '&better_payment_paystack_id=' . $order_id,
            'metadata'     => array(
                'cancel_action' => add_query_arg(
                    array(
                        'better_payment_paystack_id' => $order_id,
                    ),
                    esc_url_raw( $redirection_url_error )
                ),
            ),
        );

        $request = wp_remote_post(
            'https://api.paystack.co/transaction/initialize',
            array(
                'headers' => $header_info,
                'body'    => $request_body,
            )
        );

        if ( is_wp_error( $request ) ) {
            wp_send_json_error( sanitize_text_field( $request->get_error_message() ) );
            return;
        }

        $response_ar = json_decode( wp_remote_retrieve_body( $request ) );

        if ( null === $response_ar ) {
            wp_send_json_error( esc_html__( 'Invalid response from Paystack.', 'better-payment' ) );
            return;
        }

        if ( empty( $response_ar->status ) || empty( $response_ar->data ) ) {
            $error_message = ! empty( $response_ar->message ) ? sanitize_text_field( $response_ar->message ) : 'Something went wrong!';

            if ( isset( $response_ar->error ) ) {
                $error_message = sanitize_text_field( $response_ar->error->message );
            }

            wp_send_json_error( $error_message );
        }

        $campaign_id = ! empty( $_POST['fields']['campaign_id'] ) ? sanitize_text_field( $_POST['fields']['campaign_id'] ) : '';

        Handler::payment_create(
            array(
                'amount'           => floatval( $amount ),
                'order_id'         => $order_id,
                'payment_date'     => gmdate( 'Y-m-d H:i:s' ),
                'source'           => 'paystack',
                'transaction_id'   => '',
                'customer_info'    => maybe_serialize( $response_ar ),
                'form_fields_info' => maybe_serialize( $better_form_fields ),
                'status'           => 'unpaid',
                'currency'         => sanitize_text_field( $el_settings_currency ),
                'referer'          => 'gutenberg-block',
                'campaign_id'      => $campaign_id,
            )
        );

        $authorization_url = ! empty( $response_ar->data->authorization_url ) ? esc_url_raw( $response_ar->data->authorization_url ) : '';

        wp_send_json_success(
            array(
                'authorization_url' => $authorization_url,
            )
        );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Redirect to referer page.
     *
     * @since 1.0.0
     */
    public function redirect_previous_page() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $location = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : home_url();
        wp_safe_redirect( esc_url_raw( $location ) );
        exit();
    }

    /**
     * Get detailed product information.
     *
     * @param array $product_ids Array of product IDs.
     * @param array $product_quantities Array of product quantities.
     * @return array Detailed product information.
     */
    public function get_detailed_product_info( $product_ids, $product_quantities = array() ) {
        $detailed_product_info = array();

        if ( function_exists( 'wc_get_product' ) && ! empty( $product_ids['woo_product_ids'] ) ) {
            foreach ( $product_ids['woo_product_ids'] as $key => $product_id ) {
                if ( empty( $product_id ) ) {
                    continue;
                }

                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $quantity    = isset( $product_quantities[ $key ] ) ? intval( $product_quantities[ $key ] ) : 1;
                    $price       = floatval( $product->get_price() );
                    $total_price = $price * $quantity;

                    $detailed_product_info['woo_products'][ $product_id ] = array(
                        'name'        => sanitize_text_field( $product->get_name() ),
                        'product_id'  => intval( $product_id ),
                        'permalink'   => esc_url( $product->get_permalink() ),
                        'image_src'   => esc_url( wp_get_attachment_url( $product->get_image_id() ) ),
                        'price'       => $price,
                        'quantity'    => $quantity,
                        'total_price' => $total_price,
                    );
                }
            }
        }

        return $detailed_product_info;
    }

    /**
     * Fetch form fields from POST data.
     *
     * @param array $el_settings     Widget/block settings.
     * @param array $post_data_form_fields POST data.
     * @return array Form fields data.
     */
    public function fetch_better_form_fields( $el_settings, $post_data_form_fields ) {
        $better_form_fields = array();

        $post_data_primary_first_name = '';
        $post_data_primary_last_name  = '';
        $post_data_primary_email      = '';

        $post_fields = $post_data_form_fields;

        $layout = ! empty( $el_settings['better_payment_form_layout'] ) ? sanitize_text_field( $el_settings['better_payment_form_layout'] ) : 'layout-1';

        // Handle different layouts.
        switch ( $layout ) {
            case 'layout-4-pro':
                $el_settings['better_payment_form_fields'] = isset( $el_settings['better_payment_form_fields_layout_4_5_6'] ) ? $el_settings['better_payment_form_fields_layout_4_5_6'] : array();
                break;

            case 'layout-5-pro':
                $el_settings['better_payment_form_fields'] = isset( $el_settings['better_payment_form_fields_layout_4_5_6_desc'] ) ? $el_settings['better_payment_form_fields_layout_4_5_6_desc'] : array();
                break;

            case 'layout-6-pro':
                $el_settings['better_payment_form_fields'] = isset( $el_settings['better_payment_form_fields_layout_4_5_6_woo'] ) ? $el_settings['better_payment_form_fields_layout_4_5_6_woo'] : array();
                break;

            default:
                break;
        }

        $form_fields = isset( $el_settings['better_payment_form_fields'] ) ? $el_settings['better_payment_form_fields'] : array();

        if ( ! empty( $form_fields ) && is_array( $form_fields ) ) {
            foreach ( $form_fields as $form_field ) {
                $field_type = ! empty( $form_field['better_payment_primary_field_type'] ) ? sanitize_text_field( $form_field['better_payment_primary_field_type'] ) : '';
                $field_name = ! empty( $form_field['better_payment_field_name_heading'] ) ? sanitize_text_field( $form_field['better_payment_field_name_heading'] ) : '';

                switch ( $field_type ) {
                    case 'primary_first_name':
                        $post_data_primary_first_name = isset( $post_fields['primary_first_name'] ) ? sanitize_text_field( $post_fields['primary_first_name'] ) : '';
                        $better_form_fields['primary_first_name'] = $post_data_primary_first_name;
                        break;

                    case 'primary_last_name':
                        $post_data_primary_last_name = isset( $post_fields['primary_last_name'] ) ? sanitize_text_field( $post_fields['primary_last_name'] ) : '';
                        $better_form_fields['primary_last_name'] = $post_data_primary_last_name;
                        break;

                    case 'primary_email':
                        $post_data_primary_email = isset( $post_fields['primary_email'] ) ? sanitize_email( $post_fields['primary_email'] ) : '';
                        $better_form_fields['primary_email'] = $post_data_primary_email;
                        break;

                    default:
                        if ( isset( $post_fields[ $field_type ] ) ) {
                            $better_form_fields[ $field_type ] = sanitize_text_field( $post_fields[ $field_type ] );
                        }
                        break;
                }
            }
        }

        return $better_form_fields;
    }
}
