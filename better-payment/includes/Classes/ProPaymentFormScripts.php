<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Lite's copy of the Payment Form quantity total for Pro layouts 4 and 5.
 *
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when
 * ProCompatibility::MIN_PRO_VERSION >= 1.4.4 (with src/js/payment-form-pro-quantity.js
 * and its Assets entry).
 * Pro copy: better-payment-pro/src/js/payment-form-quantity.js. Slug: `payment_form_pro_scripts`.
 *
 * Only Pro's layouts 4 and 5 render the quantity field
 * (`.bp-custom-payment-amount-quantity`). better-payment.js used to multiply the
 * transaction details total by it on every install. It now writes the plain amount
 * and triggers `better_payment_amount_changed` on the document, and the listener in
 * src/js/payment-form-pro-quantity.js multiplies. That script is a registration-time
 * dependency of `better-payment` (it loads wherever better-payment.js does), added
 * only while ProCompatibility::lite_copy_applies() is true. The event fires after the
 * plain total is written, so load order does not matter.
 *
 * The slug is a Payment Form one rather than a User Dashboard one: the field belongs
 * to the Payment Form widget, and the script loads with the payment form, not with
 * the dashboard.
 *
 * @since 2.4.0
 */
final class ProPaymentFormScripts {

    /**
     * Feature slug Pro declares once it ships the script.
     */
    const FEATURE = 'payment_form_pro_scripts';

    /**
     * Lite's script handle (registered in Assets).
     */
    const HANDLE = 'better-payment-payment-form-pro-quantity';

    /**
     * Dependencies to add to `better-payment`.
     *
     * @return string[]
     */
    public static function script_depends(): array {
        return ProCompatibility::lite_copy_applies( self::FEATURE ) ? array( self::HANDLE ) : array();
    }
}
