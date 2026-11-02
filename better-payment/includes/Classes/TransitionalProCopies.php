<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lite's last copies of Pro code, kept for Better Payment Pro 1.4.6 and older (the second hand-off).
 *
 * Ten Pro features still lived in Lite after the first hand-off. Nine get a hook here: Better
 * Payment Pro 1.4.7 fills it and declares the slug below on `better_payment/pro_provides`, and this
 * class keeps Lite's copy working for an older Pro only — every copy goes through applies(), which
 * is false on a free install (there is nothing to serve) and false beside Pro 1.4.7+ (Pro serves
 * it). The tenth, the Analytics chart placeholder CSS (`src/admin/styles/transitional/`), needs no
 * slug: it styles a class only Pro renders, and Pro 1.4.7 ships an identical rule.
 *
 * The next Lite release raises ProCompatibility::MIN_PRO_VERSION to 1.4.7 and deletes this file
 * together with every `transitional/` folder in `src/` (see docs/contributing/extending.md →
 * "Second hand-off"). tests/Unit/ProHandoffTwoTest.php fails if any of them outlives that floor.
 *
 * @since 2.4.4
 */
final class TransitionalProCopies {

    /**
     * Payment Form layout 6's product list: in the stored transaction, on the thank-you page,
     * in the e-mail item table and in the transaction details REST response.
     */
    const LAYOUT_6_PRODUCT_DATA = 'layout_6_product_data';

    /**
     * The layout 4-6 selectors of the Payment Form's style controls.
     */
    const PRO_LAYOUT_STYLE_SELECTORS = 'elementor_pro_layout_style_selectors';

    /**
     * Layouts 4 and 5's "Amount Quantity" label (`$amount_quantity_text` in partials/layout-vars.php).
     */
    const PRO_LAYOUT_QUANTITY_TEXT = 'elementor_pro_layout_quantity_text';

    /**
     * The User Dashboard block preview's subscription pagination and its recurring / split split.
     */
    const BLOCK_SUBSCRIPTION_ROWS = 'user_dashboard_block_subscription_rows';

    /**
     * The Campaign Builder's repeater control (the FAQ widget's item editor).
     */
    const BUILDER_REPEATER_CONTROL = 'campaign_builder_repeater_control';

    /**
     * The four PayPal refund credential keys in `Admin\DB::default_settings()`.
     */
    const PAYPAL_REFUND_CREDENTIAL_DEFAULTS = 'paypal_refund_credential_defaults';

    /**
     * The User Dashboard widget's Active / Inactive / Cancel button style controls (and their
     * selectors in the table body's typography), which style buttons only Pro's Subscriptions tab renders.
     */
    const USER_DASHBOARD_BUTTON_STYLES = 'user_dashboard_pro_button_styles';

    /**
     * The User Dashboard widget's Analytics Reports / Recurring / Split Subscriptions label controls
     * and the `$bp_settings` keys Pro's cards print them from.
     */
    const USER_DASHBOARD_PANEL_LABELS = 'user_dashboard_pro_panel_labels';

    /**
     * The Payment Form widget's "Payment Type" thank-you text control; only Pro's payment types print that row.
     */
    const PAY_TYPE_TEXT_CONTROL = 'payment_form_pay_type_text_control';

    /**
     * Every feature of this hand-off.
     */
    const FEATURES = array(
        self::LAYOUT_6_PRODUCT_DATA,
        self::PRO_LAYOUT_STYLE_SELECTORS,
        self::PRO_LAYOUT_QUANTITY_TEXT,
        self::BLOCK_SUBSCRIPTION_ROWS,
        self::BUILDER_REPEATER_CONTROL,
        self::PAYPAL_REFUND_CREDENTIAL_DEFAULTS,
        self::USER_DASHBOARD_BUTTON_STYLES,
        self::USER_DASHBOARD_PANEL_LABELS,
        self::PAY_TYPE_TEXT_CONTROL,
    );

    /**
     * The Pro release that took all ten over.
     */
    const PRO_VERSION = '1.4.7';

    /**
     * Wire the PHP copies. Each callback checks its feature on every call, so a Pro activated,
     * updated or deactivated since the last request is answered correctly.
     *
     * @return void
     */
    public static function register(): void {
        // Before Pro's own callback (priority 10), as the handlers used to build these keys.
        add_filter( 'better_payment/payment_form/pro_transaction_fields', array( __CLASS__, 'layout_6_transaction_fields' ), 5, 3 );
        add_filter( 'better_payment/success_page/product_names', array( __CLASS__, 'layout_6_product_names' ), 10, 2 );
        add_filter( 'better_payment/email/product_rows', array( __CLASS__, 'layout_6_email_rows' ), 10, 2 );
        add_filter( 'better_payment/admin_api/transaction_details', array( __CLASS__, 'layout_6_transaction_details' ) );
        add_filter( 'better_payment/elementor/widget/pro_layout_selector', array( __CLASS__, 'pro_layout_selector' ), 10, 2 );
        add_filter( 'better_payment/elementor/widget/pro_layout_selectors', array( __CLASS__, 'pro_layout_selectors' ), 10, 2 );
        add_filter( 'better_payment_option_default_settings', array( __CLASS__, 'paypal_refund_credential_defaults' ) );
        add_action( 'better_payment/elementor/user_dashboard/table_style_after', array( __CLASS__, 'user_dashboard_button_style_controls' ) );
        add_filter( 'better_payment/elementor/user_dashboard/pro_selectors', array( __CLASS__, 'user_dashboard_pro_selectors' ), 10, 2 );
        add_action( 'better_payment/elementor/user_dashboard/dashboard_content_view_all_after', array( __CLASS__, 'user_dashboard_analytics_label_control' ) );
        add_action( 'better_payment/elementor/user_dashboard/dashboard_content_recent_transactions_after', array( __CLASS__, 'user_dashboard_subscription_label_controls' ) );
        // Before Pro's own callback (priority 10), as the widget used to build these keys itself.
        add_filter( 'better_payment/elementor/user_dashboard/bp_settings', array( __CLASS__, 'user_dashboard_panel_labels' ), 5, 2 );
        add_action( 'better_payment/elementor/editor/success_message_pay_method_text_after', array( __CLASS__, 'pay_type_text_control' ) );
    }

    /**
     * Whether Lite's copy of a feature runs on this request.
     *
     * @param string $feature One of FEATURES.
     * @return bool
     */
    public static function applies( string $feature ): bool {
        return in_array( $feature, self::FEATURES, true ) && ProCompatibility::lite_copy_applies( $feature );
    }

    /**
     * The flags the Campaign Builder and the User Dashboard block read, `{ slug: bool }`.
     *
     * @return array<string, bool>
     */
    public static function client_flags(): array {
        $flags = array();

        foreach ( array( self::BLOCK_SUBSCRIPTION_ROWS, self::BUILDER_REPEATER_CONTROL ) as $feature ) {
            $flags[ $feature ] = self::applies( $feature );
        }

        return $flags;
    }

    // ------------------------------------------------------------------ layout 6's product list

    /**
     * `better_payment/payment_form/pro_transaction_fields`: the product keys the payment
     * handlers stored on every transaction before 2.4.4.
     *
     * @param mixed $form_fields_info Fields the handler built.
     * @param mixed $settings         Form settings.
     * @param mixed $guarded          The guard's resolution.
     * @return mixed
     */
    public static function layout_6_transaction_fields( $form_fields_info, $settings = array(), $guarded = array() ) {
        if ( ! is_array( $form_fields_info ) || ! self::applies( self::LAYOUT_6_PRODUCT_DATA ) ) {
            return $form_fields_info;
        }

        $settings   = is_array( $settings ) ? $settings : array();
        $guarded    = is_array( $guarded ) ? $guarded : array();
        $woo        = ! empty( $settings['better_payment_form_woocommerce_product_ids'] ) ? $settings['better_payment_form_woocommerce_product_ids'] : array( 0 );
        $fluentcart = ! empty( $settings['better_payment_form_fluentcart_product_ids'] ) ? $settings['better_payment_form_fluentcart_product_ids'] : array( 0 );
        $quantities = isset( $guarded['product_quantities'] ) && is_array( $guarded['product_quantities'] ) ? $guarded['product_quantities'] : array();

        $form_fields_info['woo_product_ids']        = maybe_serialize( $woo );
        $form_fields_info['fluentcart_product_ids'] = maybe_serialize( $fluentcart );
        $form_fields_info['detailed_product_info']  = maybe_serialize(
            self::detailed_product_info(
                array(
                    'woo_product_ids'        => $woo,
                    'fluentcart_product_ids' => $fluentcart,
                ),
                $quantities
            )
        );

        return $form_fields_info;
    }

    /**
     * Name, price, quantity and image of each product a layout 6 form sells.
     *
     * @param array $product_ids        `woo_product_ids` and `fluentcart_product_ids`.
     * @param array $product_quantities Quantities, keyed like the product ids.
     * @return array `woo_products` and `fluentcart_products`, each keyed by product id.
     */
    public static function detailed_product_info( array $product_ids, array $product_quantities = array() ) {
        $detailed_product_info = array();

        if ( function_exists( 'wc_get_product' ) && ! empty( $product_ids['woo_product_ids'] ) ) {
            foreach ( (array) $product_ids['woo_product_ids'] as $key => $product_id ) {
                if ( empty( $product_id ) ) {
                    continue;
                }

                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $image    = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
                    $quantity = ! empty( $product_quantities[ $key ] ) ? intval( $product_quantities[ $key ] ) : 1;
                    $price    = floatval( $product->get_price() );

                    $detailed_product_info['woo_products'][ $product_id ] = array(
                        'name'        => $product->get_name(),
                        'product_id'  => intval( $product_id ),
                        'permalink'   => esc_url( get_permalink( $product->get_id() ) ),
                        'image_src'   => esc_url( is_array( $image ) ? $image[0] : '' ),
                        'price'       => $price,
                        'quantity'    => $quantity,
                        'total_price' => floatval( $price * $quantity ),
                    );
                }
            }
        }

        if ( function_exists( 'fluentCart' ) && class_exists( '\FluentCart\App\Models\Product' ) && ! empty( $product_ids['fluentcart_product_ids'] ) ) {
            foreach ( (array) $product_ids['fluentcart_product_ids'] as $key => $product_id ) {
                if ( empty( $product_id ) ) {
                    continue;
                }

                try {
                    $fluentcart_product = \FluentCart\App\Models\Product::query()
                        ->with( array( 'detail', 'variants' ) )
                        ->find( $product_id );

                    if ( $fluentcart_product ) {
                        $product_price = 0;
                        if ( ! empty( $fluentcart_product->variants ) && count( $fluentcart_product->variants ) > 0 ) {
                            $product_price = $fluentcart_product->variants[0]->item_price;
                        } elseif ( ! empty( $fluentcart_product->detail ) ) {
                            $product_price = $fluentcart_product->detail->min_price;
                        }

                        if ( ! empty( $product_price ) ) {
                            $product_price = floatval( $product_price / 100 );
                        }

                        $image    = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );
                        $quantity = ! empty( $product_quantities[ $key ] ) ? intval( $product_quantities[ $key ] ) : 1;

                        $detailed_product_info['fluentcart_products'][ $product_id ] = array(
                            'name'        => sanitize_text_field( $fluentcart_product->post_title ),
                            'product_id'  => intval( $product_id ),
                            'permalink'   => esc_url( get_permalink( $fluentcart_product->ID ) ),
                            'image_src'   => esc_url( is_array( $image ) ? $image[0] : '' ),
                            'price'       => $product_price,
                            'quantity'    => $quantity,
                            'total_price' => floatval( $product_price * $quantity ),
                        );
                    }
                } catch ( \Exception $e ) {
                    // A FluentCart lookup that fails lists nothing for that product.
                    continue;
                }
            }
        }

        return $detailed_product_info;
    }

    /**
     * `better_payment/success_page/product_names`: layout 6's products on the thank-you page.
     *
     * @param mixed $names    Names so far.
     * @param mixed $settings Form settings.
     * @return mixed
     */
    public static function layout_6_product_names( $names, $settings = array() ) {
        if ( ! is_array( $names ) || ! is_array( $settings ) || ! self::applies( self::LAYOUT_6_PRODUCT_DATA ) ) {
            return $names;
        }

        if ( ! empty( $settings['better_payment_form_woocommerce_product_ids'] ) ) {
            foreach ( (array) $settings['better_payment_form_woocommerce_product_ids'] as $product_id ) {
                $names[] = get_the_title( $product_id );
            }
        }

        if ( ! empty( $settings['better_payment_form_fluentcart_product_ids'] ) && function_exists( 'fluentCart' ) && class_exists( '\FluentCart\App\Models\Product' ) ) {
            foreach ( (array) $settings['better_payment_form_fluentcart_product_ids'] as $product_id ) {
                try {
                    $fluentcart_product = \FluentCart\App\Models\Product::query()->find( $product_id );
                    if ( $fluentcart_product ) {
                        $names[] = sanitize_text_field( $fluentcart_product->post_title );
                    }
                } catch ( \Exception $e ) {
                    continue;
                }
            }
        }

        return $names;
    }

    /**
     * `better_payment/email/product_rows`: one e-mail item row per layout 6 product.
     *
     * @param mixed $rows             Rows so far.
     * @param mixed $form_fields_info The transaction's stored fields.
     * @return mixed
     */
    public static function layout_6_email_rows( $rows, $form_fields_info = array() ) {
        if ( ! is_array( $rows ) || ! is_array( $form_fields_info ) || ! self::applies( self::LAYOUT_6_PRODUCT_DATA ) ) {
            return $rows;
        }

        $details = isset( $form_fields_info['detailed_product_info'] ) ? maybe_unserialize( $form_fields_info['detailed_product_info'] ) : array();

        if ( ! is_array( $details ) ) {
            return $rows;
        }

        foreach ( array( 'woo_products', 'fluentcart_products' ) as $platform ) {
            foreach ( isset( $details[ $platform ] ) && is_array( $details[ $platform ] ) ? $details[ $platform ] : array() as $product ) {
                if ( is_array( $product ) ) {
                    $rows[] = array(
                        'name'        => isset( $product['name'] ) ? $product['name'] : '',
                        'image_src'   => isset( $product['image_src'] ) ? $product['image_src'] : '',
                        'price'       => isset( $product['price'] ) ? $product['price'] : 0,
                        'quantity'    => isset( $product['quantity'] ) ? $product['quantity'] : 0,
                        'total_price' => isset( $product['total_price'] ) ? $product['total_price'] : 0,
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * `better_payment/admin_api/transaction_details`: the `woo_products` Pro's receipt lists.
     *
     * @param mixed $transaction The row, `form_fields_info` unserialized.
     * @return mixed
     */
    public static function layout_6_transaction_details( $transaction ) {
        if ( ! is_object( $transaction ) || ! self::applies( self::LAYOUT_6_PRODUCT_DATA ) ) {
            return $transaction;
        }

        $form_fields_info = isset( $transaction->form_fields_info ) && is_array( $transaction->form_fields_info ) ? $transaction->form_fields_info : array();
        $details          = maybe_unserialize( isset( $form_fields_info['detailed_product_info'] ) ? $form_fields_info['detailed_product_info'] : array() );

        $transaction->woo_products = is_array( $details ) && isset( $details['woo_products'] ) && is_array( $details['woo_products'] ) ? array_values( $details['woo_products'] ) : array();

        return $transaction;
    }

    // ------------------------------------------------------------------ layouts 4-6

    /**
     * `better_payment/elementor/widget/pro_layout_selector`: a group control's layout 4-6 selectors.
     *
     * @param mixed  $extra      Selectors so far.
     * @param string $control_id Control name.
     * @return mixed
     */
    public static function pro_layout_selector( $extra, $control_id = '' ) {
        $map = self::pro_layout_selector_map();

        if ( ! self::applies( self::PRO_LAYOUT_STYLE_SELECTORS ) || ! isset( $map['selector'][ $control_id ] ) ) {
            return $extra;
        }

        return ( is_string( $extra ) && '' !== $extra ? $extra . ', ' : '' ) . $map['selector'][ $control_id ];
    }

    /**
     * `better_payment/elementor/widget/pro_layout_selectors`: a control's layout 4-6 selector => declaration pairs.
     *
     * @param mixed  $extra      Pairs so far.
     * @param string $control_id Control name.
     * @return mixed
     */
    public static function pro_layout_selectors( $extra, $control_id = '' ) {
        $map = self::pro_layout_selector_map();

        if ( ! is_array( $extra ) || ! self::applies( self::PRO_LAYOUT_STYLE_SELECTORS ) || ! isset( $map['selectors'][ $control_id ] ) ) {
            return $extra;
        }

        return $extra + $map['selectors'][ $control_id ];
    }

    /**
     * The layout 4-6 selectors the Payment Form's style controls carried before 2.4.4, by control.
     *
     * Better Payment Pro 1.4.7 registers the identical map (`Admin\Elementor\LayoutStyleSelectors`);
     * Pro's tests compare the two while this copy exists.
     *
     * @return array{selector: array<string, string>, selectors: array<string, array<string, string>>}
     */
    public static function pro_layout_selector_map(): array {
        return array(
        'selector'  => array(
            'better_payment_form_sidebar_background' => '{{WRAPPER}} .better-payment .transaction-details-wrap, {{WRAPPER}} .better-payment .donation-image-wrap, {{WRAPPER}} .better-payment .order-details-wrap',
            'better_payment_form_sidebar_border' => '{{WRAPPER}} .better-payment .transaction-details-wrap, {{WRAPPER}} .better-payment .donation-image-wrap, {{WRAPPER}} .better-payment .order-details-wrap',
            'better_payment_form_sidebar_box_shadow' => '{{WRAPPER}} .better-payment .transaction-details-wrap, {{WRAPPER}} .better-payment .donation-image-wrap, {{WRAPPER}} .better-payment .order-details-wrap',
            'better_payment_form_sidebar_text_title_typography' => '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-title, {{WRAPPER}} .order-details-wrap .title',
            'better_payment_form_sidebar_text_sub_title_typography' => '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-paragraph',
            'better_payment_form_sidebar_text_amount_typography' => '{{WRAPPER}} .transaction-details-wrap .total-amount-text, {{WRAPPER}} .order-details-wrap .total-amount-text',
            'better_payment_form_sidebar_text_amount_summary_typography' => '{{WRAPPER}} .transaction-details-wrap .total-amount-number, {{WRAPPER}} .transaction-details-wrap .total-amount-number span, {{WRAPPER}} .order-details-wrap .total-amount-number',
            'better_payment_form_container_background' => '{{WRAPPER}} .better-payment .general-form-wrap, {{WRAPPER}} .better-payment .donation-form-wrap, {{WRAPPER}} .better-payment .woo-form-wrap',
            'better_payment_form_container_border' => '{{WRAPPER}} .better-payment .general-form-wrap, {{WRAPPER}} .better-payment .donation-form-wrap, {{WRAPPER}} .better-payment .woo-form-wrap',
            'better_payment_form_container_box_shadow' => '{{WRAPPER}} .better-payment .general-form-wrap, {{WRAPPER}} .better-payment .donation-form-wrap, {{WRAPPER}} .better-payment .woo-form-wrap',
            'better_payment_form_fields_input_border' => '{{WRAPPER}} .better-payment .general-form-wrap input, {{WRAPPER}} .better-payment .donation-form-wrap input, {{WRAPPER}} .better-payment .woo-form-wrap input',
            'better_payment_form_fields_input_typography' => '{{WRAPPER}} .better-payment .general-form-wrap input, {{WRAPPER}} .better-payment .donation-form-wrap input, {{WRAPPER}} .better-payment .woo-form-wrap input',
            'better_payment_form_fields_input_box_shadow' => '{{WRAPPER}} .better-payment .general-form-wrap input, {{WRAPPER}} .better-payment .donation-form-wrap input, {{WRAPPER}} .better-payment .woo-form-wrap input',
            'better_payment_form_fields_payment_method_border_active' => '{{WRAPPER}} .better-payment .general-form-wrap .payment-method-items input:checked + .payment-method-image-wrap, {{WRAPPER}} .better-payment .woo-form-wrap .payment-method-items input:checked + .payment-option',
            'better_payment_form_fields_payment_method_border_inactive' => '{{WRAPPER}} .better-payment .general-form-wrap .payment-method-items .payment-method-image-wrap, {{WRAPPER}} .better-payment .woo-form-wrap .payment-method-items .payment-option',
            'better_payment_form_fields_amount_border' => '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount input:checked + .text, {{WRAPPER}} .better-payment .donation-form-wrap .payment-amount input:checked + .text, {{WRAPPER}} .better-payment .woo-form-wrap .payment-amount input:checked + .text',
            'better_payment_form_fields_amount_typography' => '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text, {{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text, {{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text',
            'better_payment_form_fields_amount_box_shadow' => '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text, {{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text, {{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text',
            'better_payment_form_button_normal_border' => '{{WRAPPER}} .better-payment .general-form-wrap .button, {{WRAPPER}} .better-payment .donation-form-wrap .button, {{WRAPPER}} .better-payment .woo-form-wrap .button',
            'better_payment_form_button_normal_typography' => '{{WRAPPER}} .better-payment .general-form-wrap .button, {{WRAPPER}} .better-payment .donation-form-wrap .button, {{WRAPPER}} .better-payment .woo-form-wrap .button',
            'better_payment_form_button_normal_box_shadow' => '{{WRAPPER}} .better-payment .general-form-wrap .button, {{WRAPPER}} .better-payment .donation-form-wrap .button, {{WRAPPER}} .better-payment .woo-form-wrap .button',
        ),
        'selectors' => array(
            'better_payment_form_sidebar_margin' => array(
                '{{WRAPPER}} .better-payment .transaction-details-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-image-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .order-details-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_padding' => array(
                '{{WRAPPER}} .better-payment .transaction-details-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .better-payment .donation-image-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .better-payment .order-details-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_sidebar_border_radius' => array(
                '{{WRAPPER}} .better-payment .transaction-details-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-image-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .order-details-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_text_title_color' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-title' => 'color: {{VALUE}}',
                '{{WRAPPER}} .order-details-wrap .title' => 'color: {{VALUE}}',
            ),
            'better_payment_form_sidebar_text_title_margin' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .order-details-wrap .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_text_title_padding' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .order-details-wrap .title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_sidebar_text_sub_title_color' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-paragraph' => 'color: {{VALUE}}',
            ),
            'better_payment_form_sidebar_text_sub_title_margin' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-paragraph' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_text_sub_title_padding' => array(
                '{{WRAPPER}} .transaction-details-wrap .transaction-details-header-paragraph' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_sidebar_text_amount_title_color' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-text' => 'color: {{VALUE}}',
                '{{WRAPPER}} .order-details-wrap .total-amount-text' => 'color: {{VALUE}}',
            ),
            'better_payment_form_sidebar_text_amount_margin' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .order-details-wrap .total-amount-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_text_amount_padding' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .order-details-wrap .total-amount-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_sidebar_text_amount_summary_color' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-number' => 'color: {{VALUE}}',
                '{{WRAPPER}} .transaction-details-wrap .total-amount-number span' => 'color: {{VALUE}}',
                '{{WRAPPER}} .order-details-wrap .total-amount-number' => 'color: {{VALUE}}',
            ),
            'better_payment_form_sidebar_text_amount_summary_margin' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-number' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .order-details-wrap .total-amount-number' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_sidebar_text_amount_summary_padding' => array(
                '{{WRAPPER}} .transaction-details-wrap .total-amount-number' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .order-details-wrap .total-amount-number' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_container_margin' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_container_padding' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .better-payment .donation-form-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                '{{WRAPPER}} .better-payment .woo-form-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
            ),
            'better_payment_form_container_border_radius' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_fields_input_bg' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'background-color: {{VALUE}}',
            ),
            'better_payment_form_fields_input_text_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'color: {{VALUE}}',
            ),
            'better_payment_form_fields_input_placeholder_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input::placeholder' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input::placeholder' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input::placeholder' => 'color: {{VALUE}}',
            ),
            'better_payment_form_fields_input_spacing' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'margin-bottom: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_input_padding' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_fields_input_text_indent' => array(
                '{{WRAPPER}} .general-form-wrap input' => 'text-indent: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'text-indent: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'text-indent: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_input_width' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="text"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="email"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="number"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="text"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="email"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="number"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="text"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="email"]' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="number"]' => 'width: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_input_height' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="text"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="email"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .general-form-wrap input[type="number"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="text"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="email"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap input[type="number"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="text"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="email"]' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap input[type="number"]' => 'height: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_input_radius' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_fields_amount_width' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'width: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_amount_height' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'height: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_amount_spacing' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'margin-bottom: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_fields_amount_normal_bg' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'background-color: {{VALUE}}',
            ),
            'better_payment_form_fields_amount_normal_text_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'color: {{VALUE}}',
            ),
            'better_payment_form_fields_amount_selected_bg' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount input:checked + .text' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount input:checked + .text' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount input:checked + .text' => 'background-color: {{VALUE}}',
            ),
            'better_payment_form_fields_amount_selected_text_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount input:checked + .text' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount input:checked + .text' => 'color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount input:checked + .text' => 'color: {{VALUE}}',
            ),
            'better_payment_form_fields_amount_border_radius' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .payment-amount .text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap .payment-amount .text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap .payment-amount .text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_button_width' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'flex: none;width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'flex: none;width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'flex: none;width: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_button_normal_bg' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'background-color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'background-color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'background: {{VALUE}}',
            ),
            'better_payment_form_button_normal_text_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'color: {{VALUE}} !important',
            ),
            'better_payment_form_button_normal_border_radius' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_button_normal_padding' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
            'better_payment_form_button_normal_margin' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button' => 'margin-top: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button' => 'margin-top: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button' => 'margin-top: {{SIZE}}{{UNIT}}',
            ),
            'better_payment_form_button_hover_bg' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button:hover' => 'background-color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button:hover' => 'background-color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button:hover' => 'background-color: {{VALUE}} !important',
            ),
            'better_payment_form_button_hover_text_color' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button:hover' => 'color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button:hover' => 'color: {{VALUE}} !important',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button:hover' => 'color: {{VALUE}} !important',
            ),
            'better_payment_form_button_hover_border' => array(
                '{{WRAPPER}} .better-payment .general-form-wrap .button:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .donation-form-wrap .button:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .better-payment .woo-form-wrap .button:hover' => 'border-color: {{VALUE}}',
            ),
        ),
        );
    }

    /**
     * The "Amount Quantity" label layouts 4 and 5 print, from their setting.
     *
     * @param mixed $settings Widget settings.
     * @return string
     */
    public static function amount_quantity_text( $settings ): string {
        if ( is_array( $settings ) && ! empty( $settings['better_payment_show_amount_quantity_text'] ) && self::applies( self::PRO_LAYOUT_QUANTITY_TEXT ) ) {
            return (string) $settings['better_payment_show_amount_quantity_text'];
        }

        return 'Amount Quantity';
    }

    // ------------------------------------------------------------------ PayPal refund credentials

    /**
     * `better_payment_option_default_settings`: the PayPal refund credential keys Lite's defaults
     * carried before 2.4.4. `Admin\Settings::save_settings()` (the quick-setup save) keeps only
     * keys in the defaults, so an older Pro's refund fields would otherwise stop saving there.
     *
     * @param mixed $defaults Default settings.
     * @return mixed
     */
    public static function paypal_refund_credential_defaults( $defaults ) {
        if ( ! is_array( $defaults ) || ! self::applies( self::PAYPAL_REFUND_CREDENTIAL_DEFAULTS ) ) {
            return $defaults;
        }

        return $defaults + array(
            'better_payment_settings_payment_paypal_live_client_id' => '',
            'better_payment_settings_payment_paypal_test_client_id' => '',
            'better_payment_settings_payment_paypal_live_secret'    => '',
            'better_payment_settings_payment_paypal_test_secret'    => '',
        );
    }

    // ------------------------------------------------------------------ User Dashboard widget

    /**
     * Whether a User Dashboard / Payment Form widget copy runs: its feature applies and Elementor
     * handed us a widget to register on.
     *
     * @param string $feature One of FEATURES.
     * @param mixed  $widget  The widget.
     * @return bool
     */
    private static function registers( string $feature, $widget ): bool {
        return is_object( $widget ) && method_exists( $widget, 'add_control' ) && self::applies( $feature );
    }

    /**
     * `better_payment/elementor/user_dashboard/table_style_after`: the Active / Inactive / Cancel
     * button style controls the widget registered before 2.4.4. Control types are Elementor's
     * `Controls_Manager` values, spelled out so this file never needs Elementor loaded.
     *
     * @param mixed $widget The User Dashboard widget.
     * @return void
     */
    public static function user_dashboard_button_style_controls( $widget ): void {
        if ( ! self::registers( self::USER_DASHBOARD_BUTTON_STYLES, $widget ) ) {
            return;
        }

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_label',
            [
                'label' => esc_html__( 'Table Body » Buttons', 'better-payment' ),
                'type'  => 'heading',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_active_label',
            [
                'label' => esc_html__( 'Active Button', 'better-payment' ),
                'type'  => 'heading',
            ]
        );

        $widget->start_controls_tabs( 'better_payment_user_dashboard_table_body_buttons_active_controls_tabs' );

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_active_control_normal', [
            'label' => esc_html__( 'Normal', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_active_normal_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.active'		=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_active_normal_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.active' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_active_control_hover', [
            'label' => esc_html__( 'Hover', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_active_hover_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.active:hover'	=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_active_hover_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.active:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_inactive_label',
            [
                'label' => esc_html__( 'Inactive Button', 'better-payment' ),
                'type'  => 'heading',
            ]
        );

        $widget->start_controls_tabs( 'better_payment_user_dashboard_table_body_buttons_inactive_controls_tabs' );

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_inactive_control_normal', [
            'label' => esc_html__( 'Normal', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_inactive_normal_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.inactive'		=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_inactive_normal_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.inactive' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_inactive_control_hover', [
            'label' => esc_html__( 'Hover', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_inactive_hover_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.inactive:hover'		=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_inactive_hover_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.inactive:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_cancel_label',
            [
                'label' => esc_html__( 'Cancel Button', 'better-payment' ),
                'type'  => 'heading',
            ]
        );

        $widget->start_controls_tabs( 'better_payment_user_dashboard_table_body_buttons_cancel_controls_tabs' );

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_cancel_control_normal', [
            'label' => esc_html__( 'Normal', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_cancel_normal_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.cancel'		=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_cancel_normal_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.cancel' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab( 'better_payment_user_dashboard_table_body_buttons_cancel_control_hover', [
            'label' => esc_html__( 'Hover', 'better-payment' ),
        ] );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_cancel_hover_text_color',
            [
                'label'     => esc_html__( 'Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.cancel:hover'		=> 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_user_dashboard_table_body_buttons_cancel_hover_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'better-payment' ),
                'type'      => 'color',
                'selectors' => [
                    '{{WRAPPER}} button.cancel:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();
    }

    /**
     * `better_payment/elementor/user_dashboard/pro_selectors`: the button selectors the table
     * body's typography carried before 2.4.4.
     *
     * @param mixed  $extra      Selectors already added.
     * @param string $control_id Control id.
     * @return mixed
     */
    public static function user_dashboard_pro_selectors( $extra, $control_id = '' ) {
        if ( ! is_array( $extra ) || 'better_payment_user_dashboard_table_body_typography' !== $control_id || ! self::applies( self::USER_DASHBOARD_BUTTON_STYLES ) ) {
            return $extra;
        }

        return array_values( array_unique( array_merge( $extra, array( '{{WRAPPER}} button.active', '{{WRAPPER}} button.cancel', '{{WRAPPER}} button.inactive' ) ) ) );
    }

    /**
     * `better_payment/elementor/user_dashboard/dashboard_content_view_all_after`: the Analytics
     * Reports label control.
     *
     * @param mixed $widget The User Dashboard widget.
     * @return void
     */
    public static function user_dashboard_analytics_label_control( $widget ): void {
        if ( ! self::registers( self::USER_DASHBOARD_PANEL_LABELS, $widget ) ) {
            return;
        }

        $widget->add_control( 
            'better_payment_user_dashboard_dashboard_analytics_reports_label', 
            [
                'label'       => esc_html__( 'Analytics Reports', 'better-payment' ),
                'type'        => 'text',
                'label_block' => false,
                'default'     => esc_html__( 'Analytics Reports', 'better-payment' ),
                'ai' => [
                    'active' => false,
                ],
            ] 
        );
    }

    /**
     * `better_payment/elementor/user_dashboard/dashboard_content_recent_transactions_after`: the
     * Recurring and Split Subscriptions label controls.
     *
     * @param mixed $widget The User Dashboard widget.
     * @return void
     */
    public static function user_dashboard_subscription_label_controls( $widget ): void {
        if ( ! self::registers( self::USER_DASHBOARD_PANEL_LABELS, $widget ) ) {
            return;
        }

        $widget->add_control( 
            'better_payment_user_dashboard_dashboard_recurring_subscriptions_label', 
            [
                'label'       => esc_html__( 'Recurring Subscriptions', 'better-payment' ),
                'type'        => 'text',
                'label_block' => false,
                'default'     => esc_html__( 'Recurring Subscriptions', 'better-payment' ),
                'ai' => [
                    'active' => false,
                ],
            ] 
        );

        $widget->add_control( 
            'better_payment_user_dashboard_dashboard_split_subscriptions_label', 
            [
                'label'       => esc_html__( 'Split Subscriptions', 'better-payment' ),
                'type'        => 'text',
                'label_block' => false,
                'default'     => esc_html__( 'Split Subscriptions', 'better-payment' ),
                'ai' => [
                    'active' => false,
                ],
            ] 
        );
    }

    /**
     * `better_payment/elementor/user_dashboard/bp_settings`: the three card labels the widget put
     * in `$bp_settings` before 2.4.4. A key already set is left alone.
     *
     * @param mixed $bp_settings Settings the widget built.
     * @param mixed $settings    Widget settings.
     * @return mixed
     */
    public static function user_dashboard_panel_labels( $bp_settings, $settings = array() ) {
        if ( ! is_array( $bp_settings ) || ! self::applies( self::USER_DASHBOARD_PANEL_LABELS ) ) {
            return $bp_settings;
        }

        $settings = is_array( $settings ) ? $settings : array();
        $labels   = array(
            'dashboard_analytics_reports_label'       => array( 'better_payment_user_dashboard_dashboard_analytics_reports_label', __( 'Analytics Reports', 'better-payment' ) ),
            'dashboard_recurring_subscriptions_label' => array( 'better_payment_user_dashboard_dashboard_recurring_subscriptions_label', __( 'Recurring Subscriptions', 'better-payment' ) ),
            'dashboard_split_subscriptions_label'     => array( 'better_payment_user_dashboard_dashboard_split_subscriptions_label', __( 'Split Subscriptions', 'better-payment' ) ),
        );

        foreach ( $labels as $key => list( $setting, $default ) ) {
            if ( ! isset( $bp_settings[ $key ] ) ) {
                $bp_settings[ $key ] = ! empty( $settings[ $setting ] ) ? $settings[ $setting ] : $default;
            }
        }

        return $bp_settings;
    }

    // ------------------------------------------------------------------ Payment Form widget

    /**
     * `better_payment/elementor/editor/success_message_pay_method_text_after`: the "Payment Type"
     * thank-you text control the widget registered before 2.4.4.
     *
     * @param mixed $widget The Payment Form widget.
     * @return void
     */
    public static function pay_type_text_control( $widget ): void {
        if ( ! self::registers( self::PAY_TYPE_TEXT_CONTROL, $widget ) ) {
            return;
        }

        $widget->add_control(
            'better_payment_form_success_message_pay_type_text',
            array(
                'label'       => __( 'Payment Type', 'better-payment' ),
                'type'        => 'text',
                'default'     => __( 'Payment Type', 'better-payment' ),
                'placeholder' => __( 'Payment Type', 'better-payment' ),
                'dynamic'     => array(
                    'active' => true,
                ),
                'label_block' => true,
                'ai'          => array(
                    'active' => false,
                ),
            )
        );
    }
}
