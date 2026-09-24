<?php

namespace Better_Payment\Lite\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Better Payment Pro's payment types, as Lite's payment handlers ran them inline.
 *
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when
 * ProCompatibility::MIN_PRO_VERSION >= 1.4.4.
 * Pro copy: better-payment-pro/includes/Frontend/PaymentTypes.php.
 * Slug: pro_payment_types (PaymentEntitlement::FEATURE).
 *
 * What moved here from PaymentRequestGuard, Actions, Handler and the e-mail template:
 * - what a Pro form may charge: recurring and split-payment price ids (only the ones the
 *   form lists), layout 6's product basket at the store's prices, and layouts 4/5 held to
 *   their own fields repeater and presets;
 * - the Stripe checkout of recurring, split and mixed forms: subscription mode, the
 *   installment count, the mixed period and its reused recurring Price;
 * - the fields repeater of layouts 4/5/6 and layout 6's `is_woo_layout` /
 *   `is_fluentcart_layout` transaction fields;
 * - on return: the subscription details, the recurring / split flags Pro's split-payment
 *   finalizer reads, and the thank-you page's Payment Type and "Paid Amount" rows;
 * - the split-payment fields an e-mail hides or relabels.
 *
 * Only PaymentEntitlement calls it, and only while
 * ProCompatibility::lite_copy_applies( PaymentEntitlement::FEATURE ) is true: an active
 * Pro that does not ship this code. A free install never reaches it, because
 * PaymentEntitlement refuses a Pro form's payment first.
 *
 * Each public method takes the arguments of the Lite hook Pro registers its copy on, so
 * the two class bodies are the same code. Pro's PaymentTypesParityTest fails when they
 * differ: change both, or neither.
 *
 * @since 2.4.0
 */
final class ProPaymentTypes {

    /**
     * `better_payment/payment_guard/resolve_pro_payment`: what a Pro form's request may charge.
     *
     * @param mixed               $resolution Null; this is the resolver.
     * @param array               $base       The guard's resolution so far: currency, verified campaign_id, nothing charged.
     * @param array               $settings   Stored form settings.
     * @param array               $fields     Submitted fields, unslashed.
     * @param array               $context    `page_id`, `gateway`, `campaign` (verified campaign or null).
     * @param PaymentRequestGuard $guard      The guard whose checks the resolution uses.
     * @return array|\WP_Error
     */
    public static function resolve_form_payment( $resolution, array $base, array $settings, array $fields, array $context, PaymentRequestGuard $guard ) {
        $layout       = PaymentRequestGuard::setting( $settings, 'better_payment_form_layout', 'layout-1' );
        $payment_type = PaymentRequestGuard::setting( $settings, 'better_payment_form_payment_type', 'one-time' );
        $gateway      = isset( $context['gateway'] ) ? sanitize_key( $context['gateway'] ) : '';
        $campaign     = isset( $context['campaign'] ) && is_array( $context['campaign'] ) ? $context['campaign'] : null;
        $form_fields  = self::form_fields( [], $settings );

        $base['coupon_code'] = PaymentRequestGuard::coupon_code( $form_fields, $fields );

        if ( in_array( $payment_type, [ 'recurring', 'split-payment' ], true ) ) {
            return self::resolve_subscription( $base, $settings, $fields, $payment_type, $gateway, $guard );
        }

        // A subscription checkout is only ever rendered for a recurring/split form.
        if ( 'subscription' === PaymentRequestGuard::field( $fields, 'better_payment_recurring_mode' ) ) {
            return PaymentRequestGuard::invalid_price();
        }

        $amount = 'layout-6-pro' === $layout
            ? self::resolve_product_list_amount( $settings, $fields, $guard )
            : $guard->resolve_amount( $settings, $fields, $form_fields, self::preset_amounts( $settings, $layout ), $campaign );

        if ( is_wp_error( $amount ) ) {
            return $amount;
        }

        return array_merge( $base, $amount );
    }

    /**
     * `better_payment/payment_form/pro_form_fields`: the fields repeater a Pro form renders.
     *
     * @param mixed $form_fields [] from Lite.
     * @param array $settings    Stored form settings.
     * @return array
     */
    public static function form_fields( $form_fields, array $settings ) {
        $layout = PaymentRequestGuard::setting( $settings, 'better_payment_form_layout', 'layout-1' );
        $keys   = [
            'layout-4-pro' => 'better_payment_form_fields_layout_4_5_6',
            'layout-5-pro' => 'better_payment_form_fields_layout_4_5_6_desc',
            'layout-6-pro' => 'better_payment_form_fields_layout_4_5_6_woo',
        ];

        return PaymentRequestGuard::repeater( $settings, isset( $keys[ $layout ] ) ? $keys[ $layout ] : 'better_payment_form_fields' );
    }

    /**
     * `better_payment/payment_form/pro_transaction_fields`: layout 6's platform flags.
     *
     * Stored on every form's transaction, right after `amount_quantity`, where the handlers
     * always put them.
     *
     * @param array $form_fields_info Fields the handler built.
     * @param array $settings         Stored form settings.
     * @return array
     */
    public static function transaction_fields( array $form_fields_info, array $settings ) {
        $is_layout_6          = ! empty( $settings['better_payment_form_layout'] ) && 'layout-6-pro' === $settings['better_payment_form_layout'];
        $is_fluentcart_layout = $is_layout_6 && ! empty( $settings['better_payment_form_layout_6_ecommerce_platform'] ) && 'fluentcart' === $settings['better_payment_form_layout_6_ecommerce_platform'];
        $is_woo_layout        = $is_layout_6 && ( empty( $settings['better_payment_form_layout_6_ecommerce_platform'] ) || 'woocommerce' === $settings['better_payment_form_layout_6_ecommerce_platform'] );

        return self::insert_after(
            $form_fields_info,
            'amount_quantity',
            [
                'is_woo_layout'        => $is_woo_layout,
                'is_fluentcart_layout' => $is_fluentcart_layout,
            ]
        );
    }

    /**
     * `better_payment/stripe_checkout/pro_request`: a Pro form's Stripe Checkout Session.
     *
     * A one-time Pro form's session is returned as built. Recurring and split forms become
     * subscription checkouts for the price the guard allowed; a mixed form's recurring
     * choice needs a period the form offers and subscribes to that plan's reused Price.
     *
     * @param mixed $result   Null; this builds the checkout.
     * @param array $checkout `request` (Checkout Session body) and `form_fields` (stored transaction fields).
     * @param array $context  `settings`, `fields` (unslashed), `guarded`, `order_id`, `widget_id`, `header_info`.
     * @return array|\WP_Error `request` and `form_fields`.
     */
    public static function stripe_checkout( $result, array $checkout, array $context ) {
        $request              = isset( $checkout['request'] ) && is_array( $checkout['request'] ) ? $checkout['request'] : [];
        $better_form_fields   = isset( $checkout['form_fields'] ) && is_array( $checkout['form_fields'] ) ? $checkout['form_fields'] : [];
        $el_settings          = isset( $context['settings'] ) && is_array( $context['settings'] ) ? $context['settings'] : [];
        $fields               = isset( $context['fields'] ) && is_array( $context['fields'] ) ? $context['fields'] : [];
        $guarded              = isset( $context['guarded'] ) && is_array( $context['guarded'] ) ? $context['guarded'] : [];
        $header_info          = isset( $context['header_info'] ) && is_array( $context['header_info'] ) ? $context['header_info'] : [];
        $order_id             = isset( $context['order_id'] ) ? (string) $context['order_id'] : '';
        $widget_id            = isset( $context['widget_id'] ) ? (string) $context['widget_id'] : '';
        $amount               = isset( $guarded['amount'] ) ? $guarded['amount'] : 0;
        $el_settings_currency = isset( $guarded['currency'] ) ? $guarded['currency'] : '';

        $is_payment_split_payment = ! empty( $el_settings['better_payment_form_payment_type'] ) && 'split-payment' === $el_settings['better_payment_form_payment_type'];
        $is_payment_recurring     = ! empty( $guarded['is_recurring'] );
        $recurring_price_id       = isset( $guarded['recurring_price_id'] ) ? $guarded['recurring_price_id'] : '';

        $mixed_payment_selection_data = self::get_mixed_payment_selection_data( $el_settings, $fields );

        if ( is_wp_error( $mixed_payment_selection_data ) ) {
            return $mixed_payment_selection_data;
        }

        $is_payment_mixed_recurring    = ! empty( $mixed_payment_selection_data['is_mixed_recurring'] );
        $mixed_dynamic_recurring_price = [];

        if ( $is_payment_mixed_recurring ) {
            $mixed_dynamic_recurring_price = self::create_mixed_recurring_stripe_price(
                $header_info,
                $amount,
                $el_settings_currency,
                $order_id,
                $widget_id,
                $mixed_payment_selection_data,
                $el_settings
            );

            if ( is_wp_error( $mixed_dynamic_recurring_price ) ) {
                return $mixed_dynamic_recurring_price;
            }

            // Reuse the recurring checkout flow with the plan's recurring Stripe Price.
            $is_payment_recurring = true;
            $recurring_price_id   = ! empty( $mixed_dynamic_recurring_price['price_id'] ) ? sanitize_text_field( $mixed_dynamic_recurring_price['price_id'] ) : '';
        }

        if ( ! empty( $mixed_payment_selection_data['is_payment_mixed'] ) ) {
            $better_form_fields['is_payment_mixed']            = 1;
            $better_form_fields['mixed_payment_selected_type'] = ! empty( $mixed_payment_selection_data['selected_type'] ) ? sanitize_text_field( $mixed_payment_selection_data['selected_type'] ) : 'one-time';
        }

        if ( $is_payment_mixed_recurring ) {
            $better_form_fields['mixed_payment_selected_type']            = 'recurring';
            $better_form_fields['mixed_payment_recurring_interval_count'] = ! empty( $mixed_payment_selection_data['interval_count'] ) ? absint( $mixed_payment_selection_data['interval_count'] ) : 1;
            $better_form_fields['mixed_payment_recurring_interval']       = ! empty( $mixed_payment_selection_data['interval'] ) ? sanitize_text_field( $mixed_payment_selection_data['interval'] ) : 'month';
            $better_form_fields['mixed_payment_dynamic_price_id']         = ! empty( $mixed_dynamic_recurring_price['price_id'] ) ? sanitize_text_field( $mixed_dynamic_recurring_price['price_id'] ) : '';
            $better_form_fields['mixed_payment_dynamic_product_id']       = ! empty( $mixed_dynamic_recurring_price['product_id'] ) ? sanitize_text_field( $mixed_dynamic_recurring_price['product_id'] ) : '';
        }

        $installment_price_id = '';

        if ( $is_payment_split_payment ) {
            $installment_price_id            = isset( $guarded['installment_price_id'] ) ? $guarded['installment_price_id'] : '';
            $installment_price_iteration     = '';
            $split_payment_installments_data = ! empty( $el_settings['better_payment_split_installment_price_ids'] ) ? $el_settings['better_payment_split_installment_price_ids'] : [];

            if ( is_array( $split_payment_installments_data ) && count( $split_payment_installments_data ) ) {
                foreach ( $split_payment_installments_data as $split_payment_installment_data ) {
                    $item_price_id = ! empty( $split_payment_installment_data['better_payment_split_installment_price_id'] ) ? sanitize_text_field( $split_payment_installment_data['better_payment_split_installment_price_id'] ) : '';

                    if ( $installment_price_id === $item_price_id ) {
                        $installment_price_iteration = ! empty( $split_payment_installment_data['better_payment_split_installment_iteration'] ) ? sanitize_text_field( $split_payment_installment_data['better_payment_split_installment_iteration'] ) : '';
                        break;
                    }
                }
            }

            $better_form_fields['is_payment_split_payment']            = 1;
            $better_form_fields['split_payment_installment_price_id']  = $installment_price_id;
            $better_form_fields['split_payment_total_amount_price_id'] = $recurring_price_id;
            $better_form_fields['split_payment_installment_iteration'] = ( ! empty( $installment_price_id ) && ! empty( $installment_price_iteration ) ) ? $installment_price_iteration : 1;
            $better_form_fields['split_payment_total_amount']          = $amount * $better_form_fields['split_payment_installment_iteration'];
        }

        if ( $is_payment_recurring || $is_payment_split_payment ) {
            $request['mode'] = 'subscription';
            unset( $request['line_items'][0] );
            unset( $request['payment_intent_data'] );
            $request['line_items'][0]['price']    = $is_payment_split_payment && ! empty( $installment_price_id ) ? $installment_price_id : $recurring_price_id;
            $request['line_items'][0]['quantity'] = 1;

            $better_form_fields['mode']               = $request['mode'];
            $better_form_fields['recurring_price_id'] = $request['line_items'][0]['price'];
        }

        return [
            'request'     => $request,
            'form_fields' => $better_form_fields,
        ];
    }

    /**
     * `better_payment/stripe_payment/pro_verification_args`: a subscription session's expansion and flags.
     *
     * @param array $args             `expand`, `is_payment_recurring`, `is_payment_split_payment`.
     * @param array $form_fields_info The transaction's stored fields.
     * @return array
     */
    public static function stripe_verification_args( array $args, array $form_fields_info ) {
        $is_payment_recurring = ! empty( $form_fields_info['mode'] ) && 'subscription' === $form_fields_info['mode'];

        array_unshift( $args['expand'], 'subscription.latest_invoice.payment_intent' );

        $args['is_payment_recurring']     = $is_payment_recurring;
        $args['is_payment_split_payment'] = ( $is_payment_recurring ) && ( ! empty( $form_fields_info['is_payment_split_payment'] ) && 1 === intval( $form_fields_info['is_payment_split_payment'] ) );

        return $args;
    }

    /**
     * `better_payment/stripe_payment/pro_verified_session`: a subscription's details and the thank-you page flags.
     *
     * @param array  $details `form_fields_info`, `transaction_id`, `email`, `frontend_data`.
     * @param object $session The retrieved Checkout Session.
     * @param array  $args    `is_payment_recurring`, `is_payment_split_payment`.
     * @return array
     */
    public static function stripe_verified_session( array $details, $session, array $args ) {
        $form_fields_info         = $details['form_fields_info'];
        $is_payment_recurring     = ! empty( $args['is_payment_recurring'] );
        $is_payment_split_payment = ! empty( $args['is_payment_split_payment'] );

        if ( $is_payment_recurring && ! empty( $session->subscription->id ) ) {
            $transaction_id   = sanitize_text_field( $session->subscription->id );
            $subscription_obj = $session->subscription;

            $form_fields_info['subscription_id']                    = sanitize_text_field( $transaction_id );
            $form_fields_info['subscription_customer_id']           = ! empty( $session->customer ) ? sanitize_text_field( $session->customer ) : '';
            $form_fields_info['subscription_plan_id']               = ! empty( $subscription_obj->items->data[0]->plan->id ) ? sanitize_text_field( $subscription_obj->items->data[0]->plan->id ) : '';
            $form_fields_info['subscription_interval']              = ! empty( $subscription_obj->items->data[0]->plan->interval_count ) ? intval( $subscription_obj->items->data[0]->plan->interval_count ) : '';
            $form_fields_info['subscription_interval']             .= ! empty( $subscription_obj->items->data[0]->plan->interval ) ? ' ' . sanitize_text_field( $subscription_obj->items->data[0]->plan->interval ) : $form_fields_info['subscription_interval'];
            $form_fields_info['subscription_current_period_start'] = ! empty( $subscription_obj->current_period_start ) ? intval( $subscription_obj->current_period_start ) : 0;
            $form_fields_info['subscription_current_period_end']   = ! empty( $subscription_obj->current_period_end ) ? intval( $subscription_obj->current_period_end ) : 0;
            $form_fields_info['subscription_status']                = sanitize_text_field( $session->status );
            $form_fields_info['subscription_created_date']          = sanitize_text_field( $subscription_obj->created );

            $details['transaction_id'] = $transaction_id;
        }

        if ( $is_payment_recurring ) {
            $details['email'] = ! empty( $session->customer_email ) ? sanitize_email( $session->customer_email ) : '';
        }

        $details['form_fields_info'] = $form_fields_info;
        $details['frontend_data']    = array_merge(
            $details['frontend_data'],
            [
                'is_payment_recurring'        => $is_payment_recurring,
                'is_payment_split_payment'    => $is_payment_split_payment,
                'is_payment_mixed'            => ! empty( $form_fields_info['is_payment_mixed'] ) ? intval( $form_fields_info['is_payment_mixed'] ) : 0,
                'mixed_payment_selected_type' => ! empty( $form_fields_info['mixed_payment_selected_type'] ) ? sanitize_text_field( $form_fields_info['mixed_payment_selected_type'] ) : '',
            ]
        );

        if ( $is_payment_split_payment ) {
            $details['frontend_data']['total_amount'] = ! empty( $form_fields_info['split_payment_total_amount'] )
                ? sanitize_text_field( $form_fields_info['split_payment_total_amount'] )
                : 0;
        }

        return $details;
    }

    /**
     * `better_payment/success_page/pro_rows`: the thank-you page's Payment Type and split "Paid Amount" rows.
     *
     * @param array  $rows     [] from Lite.
     * @param string $position 'after_payment_method' | 'after_merchant_details'.
     * @param array  $settings Form settings.
     * @param array  $payment  The verified payment.
     * @param array  $context  `currency_symbol` (escaped).
     * @return array[]
     */
    public static function success_rows( array $rows, $position, array $settings, array $payment, array $context ) {
        if ( 'after_payment_method' === $position && ! empty( $settings['better_payment_form_payment_type'] ) ) {
            $pay_type_text = ! empty( $settings['better_payment_form_success_message_pay_type_text'] )
                ? esc_html( $settings['better_payment_form_success_message_pay_type_text'] )
                : esc_html__( 'Payment Type', 'better-payment' );
            $payment_type  = esc_html( $settings['better_payment_form_payment_type'] );

            if ( 'one-time' === $payment_type ) {
                $payment_type_value = esc_html__( 'One Time Payment', 'better-payment' );
            } elseif ( 'recurring' === $payment_type ) {
                $payment_type_value = esc_html__( 'Recurring Payment', 'better-payment' );
            } elseif ( 'mixed' === $payment_type ) {
                $mixed_payment_selected_type = ! empty( $payment['mixed_payment_selected_type'] ) ? sanitize_text_field( $payment['mixed_payment_selected_type'] ) : '';
                $is_mixed_recurring          = 'recurring' === $mixed_payment_selected_type;

                if ( empty( $mixed_payment_selected_type ) ) {
                    $is_mixed_recurring = ! empty( $payment['is_payment_recurring'] ) && empty( $payment['is_payment_split_payment'] );
                }

                $payment_type_value = $is_mixed_recurring
                    ? esc_html__( 'Recurring Payment', 'better-payment' )
                    : esc_html__( 'One Time Payment', 'better-payment' );
            } else {
                $payment_type_value = esc_html__( 'Split Payment', 'better-payment' );
            }

            $rows[] = [
                'label' => $pay_type_text,
                'value' => $payment_type_value,
            ];
        }

        if ( 'after_merchant_details' === $position && ! empty( $payment['is_payment_split_payment'] ) ) {
            $paid_amount_text = ! empty( $settings['better_payment_form_success_message_paid_amount_text'] )
                ? esc_html( $settings['better_payment_form_success_message_paid_amount_text'] )
                : esc_html__( 'Paid Amount', 'better-payment' );
            $currency_symbol  = isset( $context['currency_symbol'] ) && is_scalar( $context['currency_symbol'] ) ? (string) $context['currency_symbol'] : '';

            $rows[] = [
                'label' => $paid_amount_text,
                'value' => $currency_symbol . esc_html( isset( $payment['amount'] ) ? $payment['amount'] : '' ) . ' out of ' . $currency_symbol . esc_html( isset( $payment['total_amount'] ) ? $payment['total_amount'] : '' ),
            ];
        }

        return $rows;
    }

    /**
     * `better_payment/email/pro_form_field_rows`: the split-payment fields an e-mail hides or relabels.
     *
     * A split payment's amount is relabelled only once `is_payment_split_payment` has been
     * seen, in stored order, as the e-mail always read it.
     *
     * @param array[] $rows             `key`, `label`, `value`, in stored order.
     * @param string  $context          'summary' (the From section) | 'all_fields' (`[bp-all-fields]`).
     * @param array   $form_fields_info The transaction's stored fields.
     * @return array[]
     */
    public static function email_rows( array $rows, $context, array $form_fields_info ) {
        $is_summary = 'summary' === $context;
        $hidden     = $is_summary
            ? [ 'is_woo_layout', 'is_fluentcart_layout', 'is_payment_split_payment', 'split_payment_total_amount', 'split_payment_total_amount_price_id', 'split_payment_installment_price_id', 'recurring_price_id' ]
            : [ 'is_woo_layout', 'is_payment_split_payment', 'split_payment_total_amount', 'split_payment_total_amount_price_id', 'split_payment_installment_price_id' ];
        $is_split   = false;
        $shown      = [];

        foreach ( $rows as $row ) {
            $key = isset( $row['key'] ) && is_scalar( $row['key'] ) ? (string) $row['key'] : '';

            if ( 'is_payment_split_payment' === $key ) {
                $is_split = true;
            }

            if ( in_array( $key, $hidden, true ) ) {
                continue;
            }

            if ( $is_summary && 'split_payment_installment_iteration' === $key ) {
                $row['label'] = ucwords( str_replace( '_', ' ', 'installment_iteration' ) );
            }

            if ( $is_split && 'Amount' === ucwords( str_replace( '_', ' ', $key ) ) ) {
                $row['label'] = $is_summary ? __( 'Total Amount', 'better-payment' ) : __( 'Product Price: ', 'better-payment' );
            }

            $shown[] = $row;
        }

        return $shown;
    }

    /**
     * The presets a layout renders as radios.
     *
     * @param array  $settings Form settings.
     * @param string $layout   Form layout.
     * @return float[]
     */
    private static function preset_amounts( array $settings, $layout ) {
        $is_pro_layout = in_array( $layout, [ 'layout-4-pro', 'layout-5-pro', 'layout-6-pro' ], true );

        return PaymentRequestGuard::preset_amounts(
            $settings,
            $is_pro_layout ? 'better_payment_show_amount_list_layout_4_5_6' : 'better_payment_show_amount_list',
            $is_pro_layout ? 'better_payment_amount_layout_4_5_6' : 'better_payment_amount'
        );
    }

    /**
     * Recurring and split-payment forms: the price ids must be ones the form offers.
     *
     * The price decides what Stripe charges, so a request-supplied price id is as dangerous
     * as a request-supplied amount — any price in the merchant's Stripe account would do.
     *
     * @param array               $resolved     Base resolution.
     * @param array               $settings     Form settings.
     * @param array               $fields       Submitted fields.
     * @param string              $payment_type 'recurring' | 'split-payment'.
     * @param string              $gateway      Gateway handling the request.
     * @param PaymentRequestGuard $guard        The guard.
     * @return array|\WP_Error
     */
    private static function resolve_subscription( array $resolved, array $settings, array $fields, $payment_type, $gateway, PaymentRequestGuard $guard ) {
        // Recurring/split layouts render Stripe only; any other gateway would charge a
        // one-time amount of the visitor's choosing instead of the plan.
        if ( 'stripe' !== $gateway ) {
            return new \WP_Error( 'bp_subscription_gateway', __( 'This payment plan can only be paid with Stripe.', 'better-payment' ) );
        }

        $is_split   = 'split-payment' === $payment_type;
        $main_price = PaymentRequestGuard::setting( $settings, 'better_payment_form_recurring_price_id', '' );
        $list_key   = $is_split ? 'better_payment_split_installment_price_ids' : 'better_payment_recurring_price_ids';
        $item_key   = $is_split ? 'better_payment_split_installment_price_id' : 'better_payment_recurring_price_id';
        $allowed    = [ $main_price ];

        foreach ( (array) ( isset( $settings[ $list_key ] ) ? $settings[ $list_key ] : [] ) as $item ) {
            if ( is_array( $item ) && ! empty( $item[ $item_key ] ) ) {
                $allowed[] = sanitize_text_field( $item[ $item_key ] );
            }
        }

        $allowed  = array_values( array_filter( $allowed ) );
        $price_id = PaymentRequestGuard::field( $fields, 'better_payment_recurring_price_id' );
        $price_id = '' === $price_id ? $main_price : $price_id;

        if ( '' === $price_id || ! in_array( $price_id, $allowed, true ) ) {
            return PaymentRequestGuard::invalid_price();
        }

        // A split installment that matches none of the offered prices is refused, never charged at the posted id.
        $installment_id = $is_split ? PaymentRequestGuard::field( $fields, 'split_payment_installment' ) : '';
        if ( '' !== $installment_id && ! in_array( $installment_id, $allowed, true ) ) {
            return PaymentRequestGuard::invalid_price();
        }

        $charged_price = '' !== $installment_id ? $installment_id : $price_id;
        $unit_amount   = $guard->stripe_price( $charged_price, $settings );

        if ( null === $unit_amount ) {
            return PaymentRequestGuard::price_unavailable();
        }

        $resolved['amount']               = $unit_amount;
        $resolved['unit_amount']          = $unit_amount;
        $resolved['is_recurring']         = true;
        $resolved['recurring_price_id']   = $price_id;
        $resolved['installment_price_id'] = $installment_id;

        return $resolved;
    }

    /**
     * Layout 6: the total of the configured WooCommerce/FluentCart products at their real prices.
     *
     * Quantities are the visitor's to choose, prices are not. Stripe and Paystack post one
     * quantity per product; PayPal's plain form POST keeps only the last `payment_amount_quantity`
     * input, so with several products and a single quantity the total can only be held to its
     * floor — every product at least once — which is still never less than the listed price.
     *
     * @param array               $settings Form settings.
     * @param array               $fields   Submitted fields.
     * @param PaymentRequestGuard $guard    The guard.
     * @return array|\WP_Error
     */
    private static function resolve_product_list_amount( array $settings, array $fields, PaymentRequestGuard $guard ) {
        $platform    = 'fluentcart' === PaymentRequestGuard::setting( $settings, 'better_payment_form_layout_6_ecommerce_platform', '' ) ? 'fluentcart' : 'woocommerce';
        $product_ids = isset( $settings[ "better_payment_form_{$platform}_product_ids" ] ) ? (array) $settings[ "better_payment_form_{$platform}_product_ids" ] : [];
        $product_ids = array_filter( array_map( 'absint', $product_ids ) );

        if ( empty( $product_ids ) ) {
            return PaymentRequestGuard::price_unavailable();
        }

        $posted       = PaymentRequestGuard::posted_amount( $fields, 'primary_payment_amount' );
        $raw_quantity = isset( $fields['payment_amount_quantity'] ) ? $fields['payment_amount_quantity'] : null;
        $expected     = 0.0;
        $exact        = true;
        $quantities   = [];

        foreach ( $product_ids as $key => $product_id ) {
            if ( is_array( $raw_quantity ) ) {
                $quantity = isset( $raw_quantity[ $key ] ) ? intval( $raw_quantity[ $key ] ) : 1;
            } elseif ( null !== $raw_quantity && '' !== $raw_quantity && 1 === count( $product_ids ) ) {
                $quantity = intval( $raw_quantity );
            } else {
                $quantity = 1;
                $exact    = 1 === count( $product_ids );
            }

            if ( $quantity < 1 ) {
                return PaymentRequestGuard::invalid_quantity();
            }

            $price = $guard->product_price( $platform, $product_id );
            if ( null === $price ) {
                return PaymentRequestGuard::price_unavailable();
            }

            $expected          += $price * $quantity;
            $quantities[ $key ] = $quantity;
        }

        $allowed = $exact ? PaymentRequestGuard::same_amount( $posted, $expected ) : $posted + PaymentRequestGuard::TOLERANCE >= $expected;

        if ( $expected <= 0 || ! $allowed ) {
            return PaymentRequestGuard::invalid_amount();
        }

        $amount = $exact ? $expected : $posted;

        return [
            'amount'             => $amount,
            'unit_amount'        => $amount,
            'quantity'           => 1,
            'product_quantities' => $quantities,
        ];
    }

    /**
     * Parse a mixed (one-time or recurring) form's choice.
     *
     * @param array $el_settings Form settings.
     * @param array $fields      Submitted fields.
     * @return array|\WP_Error
     */
    private static function get_mixed_payment_selection_data( $el_settings = [], $fields = [] ) {
        $selection_data = [
            'is_payment_mixed'   => 0,
            'selected_type'      => 'one-time',
            'is_mixed_recurring' => 0,
            'interval_count'     => 1,
            'interval'           => 'month',
        ];

        $is_payment_mixed = ! empty( $el_settings['better_payment_form_payment_type'] ) && 'mixed' === sanitize_text_field( $el_settings['better_payment_form_payment_type'] );
        if ( ! $is_payment_mixed ) {
            return $selection_data;
        }

        $selection_data['is_payment_mixed'] = 1;
        $selected_type                      = ! empty( $fields['better_payment_mixed_selected_type'] ) ? sanitize_text_field( $fields['better_payment_mixed_selected_type'] ) : '';
        if ( ! empty( $fields['payment_type'] ) && in_array( $fields['payment_type'], [ 'one-time', 'recurring' ], true ) ) {
            $selected_type = sanitize_text_field( $fields['payment_type'] );
        }

        $selected_type                        = in_array( $selected_type, [ 'one-time', 'recurring' ], true ) ? $selected_type : 'one-time';
        $selection_data['selected_type']      = $selected_type;
        $selection_data['is_mixed_recurring'] = 'recurring' === $selected_type ? 1 : 0;

        if ( ! $selection_data['is_mixed_recurring'] ) {
            return $selection_data;
        }

        $allowed_intervals    = [ 'day', 'week', 'month', 'year' ];
        $mixed_interval_value = ! empty( $fields['better_payment_mixed_interval'] ) ? sanitize_text_field( $fields['better_payment_mixed_interval'] ) : '';

        if ( empty( $mixed_interval_value ) ) {
            $interval_type = ! empty( $fields['better_payment_mixed_interval_type'] ) ? sanitize_key( $fields['better_payment_mixed_interval_type'] ) : '';

            if ( empty( $interval_type ) ) {
                if ( ! empty( $fields['better_payment_mixed_interval_day'] ) ) {
                    $interval_type = 'day';
                } elseif ( ! empty( $fields['better_payment_mixed_interval_week'] ) ) {
                    $interval_type = 'week';
                } elseif ( ! empty( $fields['better_payment_mixed_interval_month'] ) ) {
                    $interval_type = 'month';
                } elseif ( ! empty( $fields['better_payment_mixed_interval_year'] ) ) {
                    $interval_type = 'year';
                }
            }

            $interval_type  = in_array( $interval_type, $allowed_intervals, true ) ? $interval_type : '';
            $interval_count = 0;

            switch ( $interval_type ) {
                case 'day':
                    $interval_count = ! empty( $fields['better_payment_mixed_interval_day'] ) ? absint( $fields['better_payment_mixed_interval_day'] ) : 0;
                    break;
                case 'week':
                    $interval_count = ! empty( $fields['better_payment_mixed_interval_week'] ) ? absint( $fields['better_payment_mixed_interval_week'] ) : 0;
                    break;
                case 'year':
                    $interval_count = ! empty( $fields['better_payment_mixed_interval_year'] ) ? absint( $fields['better_payment_mixed_interval_year'] ) : 0;
                    break;
                case 'month':
                default:
                    $interval_count = ! empty( $fields['better_payment_mixed_interval_month'] ) ? absint( $fields['better_payment_mixed_interval_month'] ) : 0;
                    break;
            }

            if ( ! empty( $interval_count ) && ! empty( $interval_type ) ) {
                $mixed_interval_value = "{$interval_count}|{$interval_type}";
            }
        }

        if ( empty( $mixed_interval_value ) || false === strpos( $mixed_interval_value, '|' ) ) {
            return new \WP_Error( 'mixed_interval_missing', __( 'Recurring time period is required for mixed recurring payment.', 'better-payment' ) );
        }

        list( $interval_count_raw, $interval_raw ) = array_pad( explode( '|', $mixed_interval_value, 2 ), 2, '' );
        $interval_count                            = absint( $interval_count_raw );
        $interval                                  = sanitize_key( $interval_raw );

        if ( empty( $interval_count ) || ! in_array( $interval, $allowed_intervals, true ) ) {
            return new \WP_Error( 'mixed_interval_invalid', __( 'Invalid recurring time period selected for mixed payment.', 'better-payment' ) );
        }

        // The period decides how often the amount is charged, so — like the amount — it must
        // be one the form offers: a "$10 monthly" plan must not become "$10 yearly".
        if ( ! in_array( "{$interval_count}|{$interval}", self::mixed_interval_options( $el_settings ), true ) ) {
            return new \WP_Error( 'mixed_interval_invalid', __( 'Invalid recurring time period selected for mixed payment.', 'better-payment' ) );
        }

        $selection_data['interval_count'] = $interval_count;
        $selection_data['interval']       = $interval;

        return $selection_data;
    }

    /**
     * The recurring Stripe Price for a mixed form's plan: reused when it exists, created once when not.
     *
     * @param array  $header_info    Stripe request headers.
     * @param float  $amount         Amount the guard allowed.
     * @param string $currency       Currency.
     * @param string $order_id       Order id.
     * @param string $widget_id      Widget id.
     * @param array  $selection_data get_mixed_payment_selection_data().
     * @param array  $el_settings    Form settings.
     * @return array|\WP_Error `price_id`, `product_id`.
     */
    private static function create_mixed_recurring_stripe_price( $header_info = [], $amount = 0, $currency = '', $order_id = '', $widget_id = '', $selection_data = [], $el_settings = [] ) {
        $unit_amount = (int) round( floatval( $amount ) * 100 );
        if ( $unit_amount < 1 ) {
            return new \WP_Error( 'mixed_amount_invalid', __( 'Payment Amount field is required!', 'better-payment' ) );
        }

        $product_id = ! empty( $el_settings['better_payment_one_time_recurring_product_id'] )
            ? sanitize_text_field( $el_settings['better_payment_one_time_recurring_product_id'] )
            : '';

        if ( empty( $product_id ) ) {
            return new \WP_Error(
                'mixed_product_id_missing',
                __( 'Stripe Product ID is required for mixed recurring payments. Please provide it in the widget settings.', 'better-payment' )
            );
        }

        $interval       = ! empty( $selection_data['interval'] ) ? sanitize_key( $selection_data['interval'] ) : 'month';
        $interval_count = ! empty( $selection_data['interval_count'] ) ? absint( $selection_data['interval_count'] ) : 1;
        $currency       = ! empty( $currency ) ? strtolower( sanitize_text_field( $currency ) ) : 'usd';

        $form_name = ! empty( $el_settings['better_payment_form_title'] ) ? sanitize_text_field( $el_settings['better_payment_form_title'] ) : __( 'Better Payment', 'better-payment' );

        $interval_label = $interval;
        if ( $interval_count > 1 ) {
            $interval_label = $interval_count . ' ' . $interval . 's';
        }

        // One Price per plan, not one per request. This runs for anonymous visitors, and a
        // Price created on every checkout attempt piled objects into the merchant's Stripe
        // account without limit. A plan is reused through a lookup key derived from exactly
        // what it charges, so the Price carries no per-order data (the order id lives on the
        // Checkout Session instead).
        $lookup_key = self::mixed_price_lookup_key( $product_id, $currency, $unit_amount, $interval, $interval_count );
        $existing   = self::find_mixed_recurring_stripe_price( $header_info, $lookup_key, $product_id, $currency, $unit_amount, $interval, $interval_count );

        if ( '' !== $existing ) {
            return [
                'price_id'   => $existing,
                'product_id' => $product_id,
            ];
        }

        $price_request = [
            'currency'    => $currency,
            'unit_amount' => $unit_amount,
            'recurring'   => [
                'interval'       => $interval,
                'interval_count' => $interval_count,
            ],
            'product'     => $product_id,
            'lookup_key'  => $lookup_key,
            'nickname'    => sprintf( '%s - %s', $form_name, $interval_label ),
            'metadata'    => [
                'is_mixed_payment'     => '1',
                'mixed_interval'       => $interval,
                'mixed_interval_count' => strval( $interval_count ),
            ],
        ];

        $response = wp_safe_remote_post(
            'https://api.stripe.com/v1/prices',
            [
                'method'  => 'POST',
                'headers' => $header_info,
                'body'    => $price_request,
                'timeout' => 70,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'mixed_price_request_failed', $response->get_error_message() );
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_ar   = ! empty( $response_body ) ? json_decode( $response_body ) : null;
        $error_message = ! empty( $response_ar->error->message ) ? sanitize_text_field( $response_ar->error->message ) : __( 'Unable to create recurring Stripe price for mixed payment.', 'better-payment' );

        if ( empty( $response_ar ) || empty( $response_ar->id ) ) {
            // A concurrent request may have claimed the lookup key a moment earlier.
            $existing = self::find_mixed_recurring_stripe_price( $header_info, $lookup_key, $product_id, $currency, $unit_amount, $interval, $interval_count );

            if ( '' !== $existing ) {
                return [
                    'price_id'   => $existing,
                    'product_id' => $product_id,
                ];
            }

            return new \WP_Error( 'mixed_price_create_failed', $error_message );
        }

        return [
            'price_id'   => sanitize_text_field( $response_ar->id ),
            'product_id' => $product_id,
        ];
    }

    /**
     * The periods a mixed-payment form offers, as `{count}|{interval}` — the exact values
     * layouts 4/5 render into the "Recurring time period" select.
     *
     * @param array $el_settings Form settings.
     * @return string[]
     */
    private static function mixed_interval_options( $el_settings = [] ) {
        $items   = ! empty( $el_settings['better_payment_mixed_installment_options'] ) && is_array( $el_settings['better_payment_mixed_installment_options'] )
            ? $el_settings['better_payment_mixed_installment_options']
            : [];
        $options = [];

        foreach ( $items as $option ) {
            if ( ! is_array( $option ) ) {
                continue;
            }

            $type = ! empty( $option['better_payment_mixed_interval_type'] ) ? sanitize_key( $option['better_payment_mixed_interval_type'] ) : 'month';
            $type = in_array( $type, [ 'day', 'week', 'month', 'year' ], true ) ? $type : 'month';

            $count = ! empty( $option[ "better_payment_mixed_interval_{$type}" ] ) ? absint( $option[ "better_payment_mixed_interval_{$type}" ] ) : 0;

            if ( empty( $count ) && ! empty( $option['better_payment_mixed_interval'] ) ) {
                $count = absint( $option['better_payment_mixed_interval'] );
            }

            $options[] = ( $count ? $count : 1 ) . '|' . $type;
        }

        return $options;
    }

    /**
     * Stripe lookup key for a mixed recurring plan.
     *
     * @param string $product_id     Stripe product id.
     * @param string $currency       Lower-case currency.
     * @param int    $unit_amount    Minor units.
     * @param string $interval       day|week|month|year.
     * @param int    $interval_count Interval count.
     * @return string
     */
    private static function mixed_price_lookup_key( $product_id, $currency, $unit_amount, $interval, $interval_count ) {
        return 'bp_mixed_' . md5( implode( '|', [ $product_id, $currency, (int) $unit_amount, $interval, (int) $interval_count ] ) );
    }

    /**
     * An active Stripe Price already created for this exact plan, or ''.
     *
     * The lookup key is ours, but a Price is only reused while it still charges exactly this
     * plan — anything edited or mismatched in Stripe is ignored and a fresh one is created.
     *
     * @return string Price id, or '' when there is none to reuse.
     */
    private static function find_mixed_recurring_stripe_price( $header_info, $lookup_key, $product_id, $currency, $unit_amount, $interval, $interval_count ) {
        $response = wp_safe_remote_get(
            'https://api.stripe.com/v1/prices?' . http_build_query(
                [
                    'lookup_keys' => [ $lookup_key ],
                    'active'      => 'true',
                    'limit'       => 1,
                ]
            ),
            [
                'headers' => $header_info,
                'timeout' => 70,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ) );
        $price = isset( $body->data ) && is_array( $body->data ) && ! empty( $body->data[0] ) ? $body->data[0] : null;

        if ( ! is_object( $price ) || empty( $price->id ) ) {
            return '';
        }

        $price_product = isset( $price->product ) && is_object( $price->product ) ? ( isset( $price->product->id ) ? $price->product->id : '' ) : ( isset( $price->product ) ? $price->product : '' );

        $same_plan = isset( $price->unit_amount, $price->currency, $price->recurring->interval, $price->recurring->interval_count )
            && (int) $price->unit_amount === (int) $unit_amount
            && strtolower( (string) $price->currency ) === $currency
            && (string) $price_product === (string) $product_id
            && (string) $price->recurring->interval === $interval
            && (int) $price->recurring->interval_count === (int) $interval_count;

        return $same_plan ? sanitize_text_field( $price->id ) : '';
    }

    /**
     * Insert entries into an ordered array right after a key, or at the end when it is absent.
     *
     * @param array  $items  Array.
     * @param string $after  Key to insert after.
     * @param array  $insert Entries.
     * @return array
     */
    private static function insert_after( array $items, $after, array $insert ) {
        $position = array_search( $after, array_keys( $items ), true );

        if ( false === $position ) {
            return array_merge( $items, $insert );
        }

        return array_slice( $items, 0, $position + 1, true ) + $insert + array_slice( $items, $position + 1, null, true );
    }
}
