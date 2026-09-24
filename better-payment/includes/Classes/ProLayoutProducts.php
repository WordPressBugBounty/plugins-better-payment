<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The product list Pro's Payment Form layout 6 shows beside the form.
 *
 * Only Pro renders layout 6, so this copy exists for Pro releases that do not
 * build the list themselves. `partials/layout-vars.php` calls it while
 * lite_builds() is true; once Pro declares `layout_6_product_details` on
 * `better_payment/pro_provides`, Pro's own partial builds the list and Lite skips
 * this. A later Lite release deletes the class.
 *
 * Pro's `Frontend\Layout6Products` carries identical woocommerce() and
 * fluentcart() bodies, and Pro's Layout6ProductsParityTest fails when they drift.
 *
 * What a layout-6 form may charge is not decided here: PaymentRequestGuard prices
 * the products server-side. This only feeds the order summary.
 *
 * @since 2.4.0
 */
class ProLayoutProducts {

    /**
     * Feature slug Pro declares once it builds the list itself.
     */
    const FEATURE = 'layout_6_product_details';

    /**
     * Whether Lite's partial still builds the layout-6 product list.
     *
     * Only for an active Pro that does not build it itself; never on a free
     * install, which renders no layout 6.
     *
     * @return bool
     */
    public static function lite_builds(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * Product details and price total for WooCommerce product ids.
     *
     * @param mixed $product_ids WooCommerce product ids.
     * @return array{details: array, total: float|int}
     */
    public static function woocommerce( $product_ids ): array {
        $product_details     = [];
        $product_price_total = 0;

        if ( ! function_exists( 'wc_get_product' ) ) {
            return [
                'details' => $product_details,
                'total'   => $product_price_total,
            ];
        }

        foreach ( (array) $product_ids as $single_product_id ) {
            $product = wc_get_product( $single_product_id );

            if ( ! $product ) {
                continue;
            }

            $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );

            $product_details[ $single_product_id ] = [
                'product_name'            => $product->get_name(),
                'product_permalink'       => get_permalink( $product->get_id() ),
                'product_price'           => $product->get_price(),
                'product_image_src_array' => $product_image_src_array,
                'product_image_src'       => is_array( $product_image_src_array ) ? $product_image_src_array[0] : '',
            ];

            $product_price_total += floatval( $product_details[ $single_product_id ]['product_price'] );
        }

        return [
            'details' => $product_details,
            'total'   => $product_price_total,
        ];
    }

    /**
     * Product details and price total for FluentCart product ids.
     *
     * @param mixed $product_ids FluentCart product ids.
     * @return array{details: array, total: float|int}
     */
    public static function fluentcart( $product_ids ): array {
        $product_details     = [];
        $product_price_total = 0;

        if ( ! function_exists( 'fluentCart' ) || ! class_exists( '\FluentCart\App\Models\Product' ) ) {
            return [
                'details' => $product_details,
                'total'   => $product_price_total,
            ];
        }

        foreach ( (array) $product_ids as $single_product_id ) {
            try {
                $fluentcart_product = \FluentCart\App\Models\Product::query()
                    ->with( [ 'detail', 'variants' ] )
                    ->find( $single_product_id );

                if ( ! $fluentcart_product ) {
                    continue;
                }

                // FluentCart stores prices in cents.
                $fluentcart_price = 0;
                if ( ! empty( $fluentcart_product->variants ) && count( $fluentcart_product->variants ) > 0 ) {
                    $fluentcart_price = $fluentcart_product->variants[0]->item_price;
                } elseif ( ! empty( $fluentcart_product->detail ) ) {
                    $fluentcart_price = $fluentcart_product->detail->min_price;
                }

                if ( ! empty( $fluentcart_price ) ) {
                    $fluentcart_price = floatval( $fluentcart_price / 100 );
                }

                $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );

                $product_details[ $single_product_id ] = [
                    'product_name'            => $fluentcart_product->post_title,
                    'product_permalink'       => get_permalink( $fluentcart_product->ID ),
                    'product_price'           => $fluentcart_price,
                    'product_image_src_array' => $product_image_src_array,
                    'product_image_src'       => is_array( $product_image_src_array ) ? $product_image_src_array[0] : '',
                ];

                $product_price_total += floatval( $fluentcart_price );
            } catch ( \Exception $e ) {
                // A product FluentCart cannot load is left out of the list.
                continue;
            }
        }

        return [
            'details' => $product_details,
            'total'   => $product_price_total,
        ];
    }
}
