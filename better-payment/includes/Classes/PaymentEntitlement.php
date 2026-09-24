<?php

namespace Better_Payment\Lite\Classes;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Which payment requests need Better Payment Pro, and who handles them.
 *
 * Pro has no payment handlers: its Payment Form layouts 4, 5 and 6 and its recurring,
 * split-payment and mixed payment types post to Lite's handlers. Those handlers are
 * `nopriv` and their nonce is printed on every public form, so a Pro form saved on a site
 * without Pro — or a forged request naming one — would otherwise run Pro's payment types.
 * This class is where that stops, for all nine handlers at once:
 *
 * - Without Pro, PaymentRequestGuard refuses a Pro form's payment, or a request posting a
 *   field only a Pro form renders (`bp_pro_required`), before any gateway is contacted or any
 *   transaction row is stored. A free layout is a one-time form on every install, whatever
 *   payment type its saved settings still hold.
 * - With a Pro that ships the payment types itself (declares FEATURE on
 *   `better_payment/pro_provides`), each step is asked of Pro through a hook. A step that
 *   decides what is charged or how the checkout is built is refused
 *   (`bp_pro_payment_unhandled`) when no callback answers it; every other step keeps what
 *   Lite built.
 * - With an older Pro that does not, Lite's transitional copy, ProPaymentTypes, answers.
 *
 * Hooks (each fires only while `better_payment/pro_enabled` is true):
 * - `better_payment/payment_guard/resolve_pro_payment` (null, $base, $settings, $fields, $context, $guard)
 *   → resolution array | WP_Error. Nothing returned refuses the payment.
 * - `better_payment/stripe_checkout/pro_request` (null, $checkout, $context)
 *   → [ 'request' => array, 'form_fields' => array ] | WP_Error. Nothing returned refuses it.
 * - `better_payment/payment_form/pro_form_fields` ([], $settings) → the fields repeater a Pro form renders.
 * - `better_payment/payment_form/pro_transaction_fields` ($form_fields_info, $settings) → the stored fields.
 * - `better_payment/stripe_payment/pro_verification_args` ($args, $form_fields_info) → `expand` + recurring/split flags.
 * - `better_payment/stripe_payment/pro_verified_session` ($details, $session, $args) → stored + returned details.
 * - `better_payment/success_page/pro_rows` ([], $position, $settings, $payment, $context) → thank-you page rows.
 * - `better_payment/email/pro_form_field_rows` ($rows, $context, $form_fields_info) → e-mail field rows.
 *
 * This class is permanent. When ProCompatibility::MIN_PRO_VERSION reaches 1.4.4, delete
 * ProPaymentTypes and the lite_copy_applies() branch in answer(); the entitlement check
 * and the hooks stay.
 *
 * @since 2.4.0
 */
final class PaymentEntitlement {

    /**
     * Feature slug Pro declares on `better_payment/pro_provides` once it ships the payment types itself.
     *
     * One slug for every step: they share the markers written at checkout (`mode`,
     * `is_payment_split_payment`, `is_payment_mixed`) and read back on return, so a Pro that
     * built the checkout but not the verification would leave split payments uncapped.
     */
    const FEATURE = 'pro_payment_types';

    /**
     * Payment Form layouts only Pro renders.
     */
    const PRO_LAYOUTS = [ 'layout-4-pro', 'layout-5-pro', 'layout-6-pro' ];

    /**
     * Request fields only a Pro payment form renders: a subscription mode, a plan, an
     * installment, and a mixed form's one-time/recurring choice and period.
     */
    const PRO_REQUEST_FIELDS = [
        'better_payment_recurring_mode',
        'better_payment_recurring_price_id',
        'split_payment_installment',
        'better_payment_mixed_selected_type',
        'better_payment_mixed_interval',
        'better_payment_mixed_interval_type',
        'better_payment_mixed_interval_day',
        'better_payment_mixed_interval_week',
        'better_payment_mixed_interval_month',
        'better_payment_mixed_interval_year',
    ];

    /**
     * Whether a form is a Pro payment form: one of Pro's layouts (layout 6 is the product basket).
     *
     * Only the layout counts. The recurring, split-payment and mixed payment types exist only
     * on Pro's layouts 4 and 5, while a free layout (1-3, or a block) can still carry a saved
     * payment type from an earlier layout choice or from when Pro was active — Elementor keeps
     * the saved value of a control nobody registers. That leftover means nothing: a free layout
     * renders a one-time form and is charged as one.
     *
     * @param array $settings Stored form settings.
     * @return bool
     */
    public static function is_pro_form( array $settings ): bool {
        return in_array( PaymentRequestGuard::setting( $settings, 'better_payment_form_layout', '' ), self::PRO_LAYOUTS, true );
    }

    /**
     * Whether a request posts a field only a Pro payment form renders.
     *
     * No free layout, block or Lite script posts these. On a free layout such a request is
     * forged; on a free install it asks for a Pro payment.
     *
     * @param array $fields Submitted fields, unslashed.
     * @return bool
     */
    public static function posts_pro_fields( array $fields ): bool {
        foreach ( self::PRO_REQUEST_FIELDS as $key ) {
            if ( '' !== PaymentRequestGuard::field( $fields, $key ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether Lite's transitional copy answers: an active Pro that does not ship the payment types.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * The refusal of a Pro form's payment on a site without Pro.
     *
     * @return WP_Error
     */
    public static function refusal(): WP_Error {
        return new WP_Error( 'bp_pro_required', __( 'This payment form requires Better Payment Pro, which is not active on this site.', 'better-payment' ) );
    }

    /**
     * The refusal of a Pro form's payment nothing handled.
     *
     * @return WP_Error
     */
    public static function unhandled(): WP_Error {
        return new WP_Error( 'bp_pro_payment_unhandled', __( 'This payment form cannot take payments right now. Please try again later.', 'better-payment' ) );
    }

    /**
     * What a Pro form's request may charge. Called by PaymentRequestGuard::resolve_form_payment().
     *
     * The resolver answers the amount, quantity, price ids and coupon. The verified campaign
     * and the currency are Lite's and are kept whatever it returns.
     *
     * @param PaymentRequestGuard $guard    The guard, whose checks the resolver uses.
     * @param array               $base     The guard's resolution so far (all keys, nothing charged).
     * @param array               $settings Stored form settings.
     * @param array               $fields   Submitted fields, unslashed.
     * @param array               $context  `page_id`, `gateway`, `campaign` (verified campaign or null).
     * @return array|WP_Error
     */
    public static function resolve_form_payment( PaymentRequestGuard $guard, array $base, array $settings, array $fields, array $context ) {
        if ( ! ProCompatibility::pro_enabled() ) {
            return self::refusal();
        }

        $resolution = self::answer( 'better_payment/payment_guard/resolve_pro_payment', 'resolve_form_payment', [ null, $base, $settings, $fields, $context, $guard ] );

        if ( is_wp_error( $resolution ) ) {
            return $resolution;
        }

        if ( ! is_array( $resolution ) || ! isset( $resolution['amount'] ) || ! is_numeric( $resolution['amount'] )
            || ! is_finite( (float) $resolution['amount'] ) || (float) $resolution['amount'] < 0 ) {
            return self::unhandled();
        }

        $resolved = array_merge( $base, $resolution );

        $resolved['campaign_id'] = $base['campaign_id'];
        $resolved['currency']    = $base['currency'];

        return $resolved;
    }

    /**
     * The Stripe Checkout Session request for a Pro form. Called by the widget's Stripe handler.
     *
     * @param array $checkout `request` (the Checkout Session body) and `form_fields` (the transaction's stored fields).
     * @param array $context  `settings`, `fields` (unslashed), `guarded` (the guard's resolution),
     *                        `order_id`, `widget_id`, `header_info` (Stripe request headers).
     * @return array|WP_Error `request` and `form_fields`.
     */
    public static function stripe_checkout( array $checkout, array $context ) {
        if ( ! ProCompatibility::pro_enabled() ) {
            return self::refusal();
        }

        $result = self::answer( 'better_payment/stripe_checkout/pro_request', 'stripe_checkout', [ null, $checkout, $context ] );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( ! is_array( $result ) || ! isset( $result['request'], $result['form_fields'] )
            || ! is_array( $result['request'] ) || ! is_array( $result['form_fields'] ) ) {
            return self::unhandled();
        }

        return [
            'request'     => $result['request'],
            'form_fields' => $result['form_fields'],
        ];
    }

    /**
     * The fields repeater whose submitted values a transaction records.
     *
     * A free form's is `better_payment_form_fields`. A Pro form records none on a site
     * without Pro (its payment was refused), and whatever Pro answers otherwise.
     *
     * @param array $settings Stored form settings.
     * @return array
     */
    public static function form_fields( array $settings ): array {
        if ( ! self::is_pro_form( $settings ) ) {
            return PaymentRequestGuard::repeater( $settings, 'better_payment_form_fields' );
        }

        if ( ! ProCompatibility::pro_enabled() ) {
            return [];
        }

        $form_fields = self::answer( 'better_payment/payment_form/pro_form_fields', 'form_fields', [ [], $settings ] );

        return is_array( $form_fields ) ? $form_fields : [];
    }

    /**
     * The fields a transaction stores, with any Pro adds (layout 6's platform flags).
     *
     * @param array $form_fields_info Fields the handler built.
     * @param array $settings         Stored form settings.
     * @return array
     */
    public static function transaction_fields( array $form_fields_info, array $settings ): array {
        if ( ! ProCompatibility::pro_enabled() ) {
            return $form_fields_info;
        }

        $result = self::answer( 'better_payment/payment_form/pro_transaction_fields', 'transaction_fields', [ $form_fields_info, $settings ] );

        return is_array( $result ) ? $result : $form_fields_info;
    }

    /**
     * How a Stripe Checkout Session is retrieved on return, and whether it is a subscription.
     *
     * @param array $args             `expand` (string[]), `is_payment_recurring`, `is_payment_split_payment`.
     * @param array $form_fields_info The transaction's stored fields.
     * @return array
     */
    public static function stripe_verification_args( array $args, array $form_fields_info ): array {
        if ( ! ProCompatibility::pro_enabled() ) {
            return $args;
        }

        $result = self::answer( 'better_payment/stripe_payment/pro_verification_args', 'stripe_verification_args', [ $args, $form_fields_info ] );

        if ( ! is_array( $result ) || ! isset( $result['expand'] ) || ! is_array( $result['expand'] ) ) {
            return $args;
        }

        $result['expand']                   = array_values( array_filter( $result['expand'], 'is_string' ) );
        $result['is_payment_recurring']     = ! empty( $result['is_payment_recurring'] );
        $result['is_payment_split_payment'] = ! empty( $result['is_payment_split_payment'] );

        return $result;
    }

    /**
     * What a verified Stripe Checkout Session stores and returns beyond a one-time payment.
     *
     * @param array  $details `form_fields_info`, `transaction_id`, `email`, `frontend_data` (extra keys for the thank-you page).
     * @param object $session The retrieved Checkout Session.
     * @param array  $args    stripe_verification_args().
     * @return array
     */
    public static function stripe_verified_session( array $details, $session, array $args ): array {
        if ( ! ProCompatibility::pro_enabled() ) {
            return $details;
        }

        $result = self::answer( 'better_payment/stripe_payment/pro_verified_session', 'stripe_verified_session', [ $details, $session, $args ] );

        if ( ! is_array( $result ) || ! array_key_exists( 'form_fields_info', $result )
            || ! isset( $result['frontend_data'] ) || ! is_array( $result['frontend_data'] ) ) {
            return $details;
        }

        return [
            'form_fields_info' => $result['form_fields_info'],
            'transaction_id'   => isset( $result['transaction_id'] ) && is_scalar( $result['transaction_id'] ) ? (string) $result['transaction_id'] : $details['transaction_id'],
            'email'            => isset( $result['email'] ) && is_scalar( $result['email'] ) ? (string) $result['email'] : $details['email'],
            'frontend_data'    => $result['frontend_data'],
        ];
    }

    /**
     * Thank-you page rows only Pro's payment types show — asked only for a Pro form, so a free
     * layout's leftover payment type never labels its thank-you page.
     *
     * @param string $position 'after_payment_method' | 'after_merchant_details'.
     * @param mixed  $settings Form settings.
     * @param mixed  $payment  The verified payment (a `*_payment_success()` result).
     * @param array  $context  `currency_symbol` (escaped).
     * @return array[] `label` and `value`, both HTML.
     */
    public static function success_rows( $position, $settings, $payment, array $context ): array {
        if ( ! ProCompatibility::pro_enabled() || ! is_array( $settings ) || ! self::is_pro_form( $settings ) ) {
            return [];
        }

        $rows = self::answer(
            'better_payment/success_page/pro_rows',
            'success_rows',
            [ [], (string) $position, $settings, is_array( $payment ) ? $payment : [], $context ]
        );

        $valid = [];

        foreach ( is_array( $rows ) ? $rows : [] as $row ) {
            if ( is_array( $row ) && isset( $row['label'], $row['value'] ) && is_scalar( $row['label'] ) && is_scalar( $row['value'] ) ) {
                $valid[] = [
                    'label' => (string) $row['label'],
                    'value' => (string) $row['value'],
                ];
            }
        }

        return $valid;
    }

    /**
     * The form-field rows a payment e-mail lists, after Pro hides or relabels its own fields.
     *
     * @param array[] $rows             `key` (stored field key), `label`, `value`, in stored order.
     * @param string  $context          'summary' (the e-mail's From section) | 'all_fields' (`[bp-all-fields]`).
     * @param mixed   $form_fields_info The transaction's stored fields.
     * @return array[]
     */
    public static function email_rows( array $rows, $context, $form_fields_info ): array {
        if ( ! ProCompatibility::pro_enabled() ) {
            return $rows;
        }

        $result = self::answer( 'better_payment/email/pro_form_field_rows', 'email_rows', [ $rows, (string) $context, is_array( $form_fields_info ) ? $form_fields_info : [] ] );

        if ( ! is_array( $result ) ) {
            return $rows;
        }

        $valid = [];

        foreach ( $result as $row ) {
            if ( is_array( $row ) && array_key_exists( 'key', $row ) && array_key_exists( 'label', $row ) && array_key_exists( 'value', $row ) ) {
                $valid[] = $row;
            }
        }

        return $valid;
    }

    /**
     * One step of a Pro payment: Lite's transitional copy for an older Pro, otherwise the hook.
     *
     * Every caller checks ProCompatibility::pro_enabled() first, so a free install never
     * reaches the copy or the hook.
     *
     * @param string $hook   Filter name.
     * @param string $method ProPaymentTypes method with the filter's arguments.
     * @param array  $args   Filter arguments, the filtered value first.
     * @return mixed
     */
    private static function answer( $hook, $method, array $args ) {
        // Transitional: delete this branch with ProPaymentTypes (MIN_PRO_VERSION >= 1.4.4).
        if ( self::lite_copy_applies() ) {
            return call_user_func_array( [ ProPaymentTypes::class, $method ], $args );
        }

        return apply_filters_ref_array( $hook, $args );
    }
}
