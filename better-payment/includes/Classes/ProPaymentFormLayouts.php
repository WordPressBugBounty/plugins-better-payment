<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when ProCompatibility::MIN_PRO_VERSION >= 1.4.4.
 * Pro copy: better-payment-pro/includes/Admin/Elementor/LayoutRenderer.php. Slug: elementor_pro_layout_rendering.
 *
 * How the Payment Form widget renders Pro layouts 4, 5 and 6 for a Pro that does not
 * render them itself:
 *
 * - template_file(): Pro's layout template (the widget used to read Pro's
 *   BETTER_PAYMENT_PRO_ADMIN_VIEWS_PATH constant itself);
 * - layout_vars(): the payment type and layout-6 platform `partials/layout-vars.php`
 *   hands the template;
 * - default_text_items(): the form-field repeater `render_attribute_default_text()`
 *   reads for a Pro layout;
 * - render_amount_element(): the amount list layouts 4 and 5 print (`'version' => 'v2'`).
 *
 * Every caller gates on lite_copy_applies(). A free install never renders a Pro layout (it
 * shows the upgrade banner), and a current Pro declares the slug and does all of this
 * in its own LayoutRenderer. Pro's LayoutRendererParityTest keeps the copies identical.
 *
 * @since 2.4.0
 */
final class ProPaymentFormLayouts {

    /**
     * Feature slug Pro declares once it renders its layouts itself.
     */
    const FEATURE = 'elementor_pro_layout_rendering';

    /**
     * Whether Lite renders the Pro-layout pieces: an active Pro that does not declare the slug.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * Where an older Pro keeps a layout template, or '' when Pro is not loaded.
     *
     * @param string $layout A `-pro` layout key the widget already whitelisted.
     * @return string
     */
    public static function template_file( $layout ): string {
        if ( ! defined( 'BETTER_PAYMENT_PRO_ADMIN_VIEWS_PATH' ) ) {
            return '';
        }

        return (string) constant( 'BETTER_PAYMENT_PRO_ADMIN_VIEWS_PATH' ) . '/elementor/layouts/' . $layout . '.php';
    }

    /**
     * The payment type (layouts 4 and 5) and eCommerce platform (layout 6) a Pro layout renders with.
     *
     * @param array $settings Payment Form widget settings.
     * @return array{is_payment_recurring: int, is_payment_split_payment: int, payment_type_text: string, is_layout_for_woocommerce: int, is_layout_for_fluentcart: int}
     */
    public static function layout_vars( $settings ): array {
        return [
            'is_payment_recurring'      => ! empty( $settings["better_payment_form_payment_type"] ) && 'recurring' === $settings["better_payment_form_payment_type"] ? 1 : 0,
            'is_payment_split_payment'  => ! empty( $settings["better_payment_form_payment_type"] ) && 'split-payment' === $settings["better_payment_form_payment_type"] ? 1 : 0,
            'payment_type_text'         => ! empty( $settings["better_payment_form_payment_type"] ) ? sanitize_text_field( $settings["better_payment_form_payment_type"] ) : 'One Time',
            'is_layout_for_woocommerce' => ! empty( $settings["better_payment_form_layout"] ) && in_array( $settings["better_payment_form_layout"], ['layout-6-pro'] ) && ( empty( $settings["better_payment_form_layout_6_ecommerce_platform"] ) || 'woocommerce' === $settings["better_payment_form_layout_6_ecommerce_platform"] ) ? 1 : 0,
            'is_layout_for_fluentcart'  => ! empty( $settings["better_payment_form_layout"] ) && 'layout-6-pro' === $settings["better_payment_form_layout"] && ! empty( $settings["better_payment_form_layout_6_ecommerce_platform"] ) && 'fluentcart' === $settings["better_payment_form_layout_6_ecommerce_platform"] ? 1 : 0,
        ];
    }

    /**
     * The form-field repeater a Pro layout reads its dynamic default amount from.
     *
     * @param array $settings Payment Form widget settings.
     * @return mixed The repeater rows, or an empty array.
     */
    public static function default_text_items( $settings ) {
        $items  = [];
        $layout = ! empty( $settings["better_payment_form_layout"] ) ? $settings["better_payment_form_layout"] : '';

        switch( $layout ) {
            case 'layout-4-pro':
                $items = ! empty( $settings['better_payment_form_fields_layout_4_5_6'] ) ? $settings['better_payment_form_fields_layout_4_5_6'] : [];
                break;
            case 'layout-5-pro':
                $items = ! empty( $settings['better_payment_form_fields_layout_4_5_6_desc'] ) ? $settings['better_payment_form_fields_layout_4_5_6_desc'] : [];
                break;
            case 'layout-6-pro':
                $items = ! empty( $settings['better_payment_form_fields_layout_4_5_6_woo'] ) ? $settings['better_payment_form_fields_layout_4_5_6_woo'] : [];
                break;

            default:
                break;
        }

        return $items;
    }

    /**
     * Print the amount list of layouts 4 and 5 (`better_payment_amount_layout_4_5_6`).
     *
     * @param \Elementor\Widget_Base $widget   The Payment Form widget.
     * @param array                  $settings Payment Form widget settings.
     * @return void
     */
    public static function render_amount_element( $widget, $settings ) {
        $items = ! empty( $settings['better_payment_amount_layout_4_5_6'] ) && is_array( $settings['better_payment_amount_layout_4_5_6'] ) ? $settings['better_payment_amount_layout_4_5_6'] : [];

        $layout_form_currency_symbol    = $widget->get_currency_symbol( esc_html( ! empty( $settings[ 'better_payment_form_currency' ] ) ? $settings[ 'better_payment_form_currency' ] : '' ) );
        $currency_alignment             = ! empty ( $settings['better_payment_form_currency_alignment'] ) ? $settings['better_payment_form_currency_alignment'] : 'left';
        $layout_form_currency_left      = 'left'    === $currency_alignment ? $layout_form_currency_symbol : '';
        $layout_form_currency_right     = 'right'   === $currency_alignment ? $layout_form_currency_symbol : '' ;

        foreach ( $items as $item ) {
            $uid = uniqid();
            ?>
                <label class="payment-amount">
                    <input type="radio" value="<?php echo floatval( $item[ 'better_payment_amount_val' ] ); ?>"
                        id="bp_payment_amount-<?php echo esc_attr($uid); ?>" class="bp-form__control bp-form_pay-radio "
                        name="primary_payment_amount_radio">
                    <span class="text"><?php printf( "%s%s%s", esc_html( $layout_form_currency_left ), floatval( $item[ 'better_payment_amount_val' ] ), esc_html( $layout_form_currency_right ) ); ?></span>
                </label>

            <?php
        }
    }
}
