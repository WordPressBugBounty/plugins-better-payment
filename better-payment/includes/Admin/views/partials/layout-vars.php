<?php
$bp_helper_obj = new Better_Payment\Lite\Classes\Helper();
$layout_action = !empty($extraDatas['action']) ? $extraDatas['action'] : '';
$layout_setting_meta = !empty($extraDatas['setting_meta']) ? $extraDatas['setting_meta'] : '';
$layout_dynamic_payment_hide_show = !empty($settings["better_payment_form_payment_source"]) && !in_array($settings["better_payment_form_payment_source"], ['woocommerce', 'fluentcart']) ? 'is-hidden' : '';
$layout_put_amount_field_hide_show = !empty($settings["better_payment_form_payment_source"]) && in_array($settings["better_payment_form_payment_source"], ['woocommerce', 'fluentcart']) ? 'is-hidden' : '';
$layout_form_content_offset_class = !empty($settings["better_payment_form_payment_source"]) && !in_array($settings["better_payment_form_payment_source"], ['woocommerce', 'fluentcart']) ? '' : ''; //is-offset-2

$layout_form_transaction_details_heading = !empty($settings["better_payment_form_transaction_details_heading"]) ? $settings["better_payment_form_transaction_details_heading"] : '';
$layout_form_transaction_details_sub_heading = !empty($settings["better_payment_form_transaction_details_sub_heading"]) ? $settings["better_payment_form_transaction_details_sub_heading"] : '';
$layout_form_transaction_details_product_title = !empty($settings["better_payment_form_transaction_details_product_title"]) ? $settings["better_payment_form_transaction_details_product_title"] : '';
$layout_form_transaction_details_amount_text = !empty($settings["better_payment_form_transaction_details_amount_text"]) ? $settings["better_payment_form_transaction_details_amount_text"] : '';
$is_payment_type_woocommerce = !empty($settings["better_payment_form_payment_source"]) && 'woocommerce' === $settings["better_payment_form_payment_source"] ? true : false;
$is_payment_type_fluentcart = !empty($settings["better_payment_form_payment_source"]) && 'fluentcart' === $settings["better_payment_form_payment_source"] ? true : false;
$is_payment_type_stripe = !empty($settings["better_payment_form_payment_source"]) && 'stripe' === $settings["better_payment_form_payment_source"] ? true : false;
$is_payment_recurring = ! empty( $settings["better_payment_form_payment_type"] ) && 'recurring' === $settings["better_payment_form_payment_type"] ? 1 : 0;
$is_payment_split_payment = ! empty( $settings["better_payment_form_payment_type"] ) && 'split-payment' === $settings["better_payment_form_payment_type"] ? 1 : 0;
$payment_type_text = ! empty( $settings["better_payment_form_payment_type"] ) ? sanitize_text_field( $settings["better_payment_form_payment_type"] ) : 'One Time';
$layout_put_amount_field_hide_show = ( $is_payment_recurring || $is_payment_split_payment ) ? 'is-hidden' : $layout_put_amount_field_hide_show;
$is_layout_for_woocommerce = ! empty( $settings["better_payment_form_layout"] ) && in_array( $settings["better_payment_form_layout"], ['layout-6-pro'] ) && ( empty( $settings["better_payment_form_layout_6_ecommerce_platform"] ) || 'woocommerce' === $settings["better_payment_form_layout_6_ecommerce_platform"] ) ? 1 : 0;
$is_layout_for_fluentcart = ! empty( $settings["better_payment_form_layout"] ) && 'layout-6-pro' === $settings["better_payment_form_layout"] && ! empty( $settings["better_payment_form_layout_6_ecommerce_platform"] ) && 'fluentcart' === $settings["better_payment_form_layout_6_ecommerce_platform"] ? 1 : 0;

if( !empty($_GET[ 'campaign_currency' ]) ) {
    $layout_form_currency = sanitize_text_field( $_GET[ 'campaign_currency' ] );
} else {
    $layout_form_currency = !empty($settings["better_payment_form_currency"]) ? sanitize_text_field( $settings["better_payment_form_currency"] ) : '';
}

$layout_form_currency_symbol = $layout_form_currency ? $bp_helper_obj->get_currency_symbol(esc_html($layout_form_currency)) : '<i class="bp-icon bp-logo-2"></i>';

$currency_alignment             = ! empty ( $settings['better_payment_form_currency_alignment'] ) ? $settings['better_payment_form_currency_alignment'] : 'left';

$product_details = [];
$product_name = '';
$product_permalink = '';
$product_price = '';
$payment_amount_field_exists = 0;
$is_payment_amount_field_hidden = 0;
$valid_html_tags = wp_kses_allowed_html( 'post' );
$sidebar_show = ! empty( $settings['better_payment_form_sidebar_show'] ) && 'yes' === $settings['better_payment_form_sidebar_show'];
if ( $is_payment_type_woocommerce || $is_layout_for_woocommerce ) {
    if(
        !empty($settings['better_payment_form_currency_use_woocommerce']) && 'yes' === $settings['better_payment_form_currency_use_woocommerce'] &&
        !empty($settings['better_payment_form_currency_woocommerce'])
    ) {
        $layout_form_currency_symbol = $bp_helper_obj->get_currency_symbol( esc_html($settings['better_payment_form_currency_woocommerce']) );
    }

    //Fetch product data using product ID
    $layout_form_woocommerce_product_id = !empty($settings["better_payment_form_woocommerce_product_id"]) ? intval($settings["better_payment_form_woocommerce_product_id"]) : 0;
    $layout_form_woocommerce_product_ids = !empty($settings["better_payment_form_woocommerce_product_ids"]) ? $settings["better_payment_form_woocommerce_product_ids"] : [0];
    $product_price_total = 0;

    if (function_exists('wc_get_product')) {
        if ( $is_layout_for_woocommerce ) {
            $product_details = [];

            foreach( $layout_form_woocommerce_product_ids as $single_product_id ){
                $bp_woocommerce_product = wc_get_product($single_product_id);
                $product = $bp_woocommerce_product;

                if ($product) {
                    $product_details[$single_product_id]['product_name'] = $product->get_name();
                    $product_details[$single_product_id]['product_permalink'] = get_permalink($product->get_id());
                    $product_details[$single_product_id]['product_price'] = $product->get_price();
                    $product_details[$single_product_id]['product_image_src_array'] = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );

                    $product_image_src_array = $product_details[$single_product_id]['product_image_src_array'];
                    $product_details[$single_product_id]['product_image_src'] = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';

                    $product_price_total += floatval( $product_details[$single_product_id]['product_price'] );
                }
            }
        } else {
            $bp_woocommerce_product = wc_get_product($layout_form_woocommerce_product_id);
            $product = $bp_woocommerce_product;

            if ($product) {
                $product_name = $product->get_name();
                $product_permalink = get_permalink($product->get_id());
                $product_price = $product->get_price();
                $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
                $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
            }
        }
    }
}

if ( $is_payment_type_fluentcart || $is_layout_for_fluentcart ) {
    $layout_form_fluentcart_product_id = !empty($settings["better_payment_form_fluentcart_product_id"]) ? intval($settings["better_payment_form_fluentcart_product_id"]) : 0;
    $layout_form_fluentcart_product_ids = !empty($settings["better_payment_form_fluentcart_product_ids"]) ? $settings["better_payment_form_fluentcart_product_ids"] : [0];
    $product_price_total = 0;

    if (function_exists('fluentCart') && class_exists( '\FluentCart\App\Models\Product' ) ) {
        if ( $is_layout_for_fluentcart ) {
            $product_details = [];

            foreach( $layout_form_fluentcart_product_ids as $single_product_id ){
                try {
                    $fluentcart_product = \FluentCart\App\Models\Product::query()
                        ->with(['detail', 'variants'])
                        ->find($single_product_id);

                    if ($fluentcart_product) {
                        $product_details[$single_product_id]['product_name'] = $fluentcart_product->post_title;
                        $product_details[$single_product_id]['product_permalink'] = get_permalink($fluentcart_product->ID);

                        $fluentcart_price = 0;
                        if (!empty($fluentcart_product->variants) && count($fluentcart_product->variants) > 0) {
                            $fluentcart_price = $fluentcart_product->variants[0]->item_price;
                        } elseif (!empty($fluentcart_product->detail)) {
                            $fluentcart_price = $fluentcart_product->detail->min_price;
                        }

                        if ( ! empty( $fluentcart_price ) ) {
                            $fluentcart_price = floatval( $fluentcart_price / 100 );
                        }

                        $product_details[$single_product_id]['product_price'] = $fluentcart_price;
                        $product_details[$single_product_id]['product_image_src_array'] = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );

                        $product_image_src_array = $product_details[$single_product_id]['product_image_src_array'];
                        $product_details[$single_product_id]['product_image_src'] = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';

                        $product_price_total += floatval( $product_details[$single_product_id]['product_price'] );
                    }
                } catch (Exception $e) {
                    // Handle exception silently
                }
            }
        } else {
            try {
                $fluentcart_product = \FluentCart\App\Models\Product::query()
                    ->with(['detail', 'variants'])
                    ->find($layout_form_fluentcart_product_id);

                if ($fluentcart_product) {
                    $product_name = $fluentcart_product->post_title;
                    $product_permalink = get_permalink($fluentcart_product->ID);

                    $fluentcart_price = 0;
                    if (!empty($fluentcart_product->variants) && count($fluentcart_product->variants) > 0) {
                        $fluentcart_price = $fluentcart_product->variants[0]->item_price;
                    } elseif (!empty($fluentcart_product->detail)) {
                        $fluentcart_price = $fluentcart_product->detail->min_price;
                    }

                    if ( ! empty( $fluentcart_price ) ) {
                        $fluentcart_price = floatval( $fluentcart_price / 100 );
                    }

                    $product_price = $fluentcart_price;
                    $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );
                    $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
                }
            } catch (Exception $e) {
                // Handle exception silently
            }
        }
    }
}

$layout_form_currency_left      = 'left'    === $currency_alignment ? $layout_form_currency_symbol : '';
$layout_form_currency_right     = 'right'   === $currency_alignment ? $layout_form_currency_symbol : '' ;

$stripe_public_key = 'yes' === sanitize_text_field( $settings[ 'better_payment_stripe_live_mode' ] ) ? sanitize_text_field( $settings[ 'better_payment_stripe_public_key_live' ] ) : sanitize_text_field( $settings[ 'better_payment_stripe_public_key' ] );
$stripe_secret_key = 'yes' === sanitize_text_field( $settings[ 'better_payment_stripe_live_mode' ] ) ? sanitize_text_field( $settings[ 'better_payment_stripe_secret_key_live' ] ) : sanitize_text_field( $settings[ 'better_payment_stripe_secret_key' ] );
$amount_quantity_text = ! empty( $settings['better_payment_show_amount_quantity_text'] ) ? $settings['better_payment_show_amount_quantity_text'] : 'Amount Quantity';

if ( $is_payment_type_stripe ) {
    $payment_source_stripe_price_id = ! empty( $settings['better_payment_form_payment_source_stripe_price_id'] ) ? sanitize_text_field( $settings['better_payment_form_payment_source_stripe_price_id'] ) : '';

    $stripe_price_details = $bp_helper_obj->get_stripe_price_details( $payment_source_stripe_price_id, $stripe_secret_key );

    if ( ! empty( $stripe_price_details ) ) {
        $product_price = ! empty( $stripe_price_details['unit_amount'] ) ? floatval( $stripe_price_details['unit_amount'] ) / 100 : '';
    }
}
