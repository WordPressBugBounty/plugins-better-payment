<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Classes\Helper;
use Better_Payment\Lite\Traits\Helper as TraitsHelper;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The plugin handler class
 * 
 * @since 0.0.1
 */
class Handler extends Controller{
    use TraitsHelper;

    /**
     * PayPal button
     * 
     * @since 0.0.1
     */
    public static function paypal_button( $widget_id = '' , $settings = [], $args = [] ) {
        global $wp;
        
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $paypal_button_text             = ! empty( $settings["better_payment_form_form_buttons_paypal_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_paypal_button_text"] ) : __( $args['button_text_default'], 'better-payment' );

        $return_url = add_query_arg( $wp->query_vars, get_the_permalink() . '?better_payment_paypal_status=success&better_payment_widget_id=' . $widget_id );
        $cancel_url = add_query_arg( $wp->query_vars, get_the_permalink() . '?better_payment_error_status=error&better_payment_widget_id=' . $widget_id );

        ob_start();
        $error_info = [
             'business_email' => !empty($settings['better_payment_paypal_business_email'])
        ];
        ?>
        <input type="hidden" name="return" value="<?php echo esc_url( $return_url ); ?>">
        <input type="hidden" name="action" value="paypal_form_handle">
        <input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce('better-payment-paypal') ); ?>">
        <input type="hidden" name="cancel_return" value="<?php echo esc_url( $cancel_url ); ?>">
        <button data-paypal-info = <?php echo esc_attr(wp_json_encode( $error_info) ) ; ?> class="button is-medium is-fullwidth better-payment-paypal-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'paypal') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $paypal_button_text ); ?></button>
        
        <?php
        $paypal_button_html = ob_get_clean();
        $paypal_button_html = apply_filters( 'better_payment/elementor/editor/paypal_button_html', $paypal_button_html, $widget_id, $settings );

        echo $paypal_button_html;
    }

    /**
     * Stripe button
     * 
     * @since 0.0.1
     */
    public static function stripe_button( $widget_id = '' , $settings = [], $args = [] ) {
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $stripe_button_text             = ! empty( $settings["better_payment_form_form_buttons_stripe_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_stripe_button_text"] ) : __( $args['button_text_default'], 'better-payment' );

        ob_start();
        ?>
        <button class="button is-medium is-fullwidth better-payment-stripe-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'stripe') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $stripe_button_text ); ?></button>
        <?php
        $stripe_button_html = ob_get_clean();
        $stripe_button_html = apply_filters( 'better_payment/elementor/editor/stripe_button_html', $stripe_button_html, $widget_id, $settings );

        echo wp_kses( $stripe_button_html,  (new Handler())->bp_allowed_tags() );
    }

    /**
     * Paystack button
     * 
     * @since 0.0.1
     */
    public static function paystack_button( $widget_id = '' , $settings = [], $args = [] ) {
        $args['extra_classes']          = ! empty( $args['extra_classes'] ) ? $args['extra_classes'] : '';
        $args['extra_classes']          = is_array($args['extra_classes']) ? implode(' ', $args['extra_classes']) : $args['extra_classes'];
        $args['button_text_default']    = ! empty( $args['button_text_default'] ) ? sanitize_text_field( $args['button_text_default'] ) : 'Proceed to Payment';

        $paystack_button_text           = ! empty( $settings["better_payment_form_form_buttons_paystack_button_text"] ) ? sanitize_text_field( $settings["better_payment_form_form_buttons_paystack_button_text"] ) : __( $args['button_text_default'], 'better-payment' );
        
        ob_start();
        ?>
        <button class="button is-medium is-fullwidth better-payment-paystack-bt <?php echo esc_attr( self::hide_show_payment_button_class($settings, 'paystack') ); ?> <?php echo esc_attr( $args['extra_classes'] ); ?>" ><?php echo esc_html( $paystack_button_text ); ?></button>
        <?php
        $paystack_button_html = ob_get_clean();
        $paystack_button_html = apply_filters( 'better_payment/elementor/editor/paystack_button_html', $paystack_button_html, $widget_id, $settings );

        echo wp_kses( $paystack_button_html,  (new Handler())->bp_allowed_tags() );
    }

    public static function hide_show_payment_button_class( $settings, $type = 'paypal' ){
        $button_hidden_class = 'is-hidden';
        $types = ['paypal', 'stripe', 'paystack'];
        
        if( ! in_array($type, $types) ){
            return $button_hidden_class;
        }

        $is_type_enable = ! empty( $settings[ "better_payment_form_{$type}_enable" ] ) && 'yes' === $settings[ "better_payment_form_{$type}_enable" ];
        
        if( ! $is_type_enable ){
            return $button_hidden_class;
        }
        
        $is_paypal_enable = ! empty( $settings[ "better_payment_form_paypal_enable" ] ) && 'yes' === $settings[ "better_payment_form_paypal_enable" ];
        $is_stripe_enable = ! empty( $settings[ "better_payment_form_stripe_enable" ] ) && 'yes' === $settings[ "better_payment_form_stripe_enable" ];
        $is_paystack_enable = ! empty( $settings[ "better_payment_form_paystack_enable" ] ) && 'yes' === $settings[ "better_payment_form_paystack_enable" ];
        
        switch( $type ){
            case 'paypal':
                $button_hidden_class = ''; 
                break;

            case 'stripe':
                if( ( ! $is_paypal_enable ) ){
                    $button_hidden_class = ''; 
                }
                
                break;

            case 'paystack':
                if( ( ! $is_paypal_enable ) && ( ! $is_stripe_enable ) ){
                    $button_hidden_class = ''; 
                }
                break;

            default:
                break;
        }

        return $button_hidden_class;
    }

    /**
     * Payment create in db
     * 
     * @since 0.0.1
     */
    public static function payment_create( $data ) {
        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        $wpdb->insert( $table, $data );
        if ( $wpdb->insert_id ) {
            return $wpdb->insert_id;
        } else {
            return false;
        }
    }

    /**
     * Mamage response
     * 
     * @since 0.0.1
     */
    public static function manage_response( $settings = [], $widget_id = '' ) {
        $status = false;

        //if widget id is different then return false
        if ( !empty( $_REQUEST[ 'better_payment_widget_id' ] ) && $_REQUEST[ 'better_payment_widget_id' ] !== $widget_id ) {
            return $status;
        }
        
        if ( !empty( $_REQUEST[ 'better_payment_error_status' ] ) ) {
            // Determine order_id based on payment method
            $order_id = '';
            if ( !empty( $_REQUEST['better_payment_stripe_id'] ) ) {
                $order_id = sanitize_text_field( $_REQUEST['better_payment_stripe_id'] );
            } elseif ( !empty( $_REQUEST['better_payment_paystack_id'] ) ) {
                $order_id = sanitize_text_field( $_REQUEST['better_payment_paystack_id'] );
            } elseif ( !empty( $_REQUEST['better_payment_paypal_id'] ) ) {
                $order_id = sanitize_text_field( $_REQUEST['better_payment_paypal_id'] );
            }
            
            // Fetch transaction data from database
            $transaction_data = self::get_failed_transaction_data( $order_id );
            
            self::failed_message_template( $settings, $transaction_data );
            $redirection_url_error = ! empty( $settings['better_payment_form_error_page_url']['url'] ) ? esc_url( $settings['better_payment_form_error_page_url']['url'] ) : '';
            
            if( $redirection_url_error ){
                ?>
                <script>
                    setTimeout(function(){
                        window.location.replace("<?php echo esc_url( $redirection_url_error ); ?>");
                    }, 2000);
                </script>
                <?php
            }

            return $status;
        }

        if ( !empty( $_REQUEST[ 'better_payment_paypal_status' ] ) && $_REQUEST[ 'better_payment_paypal_status' ] == 'success' ) {
            $status = self::paypal_payment_success( $settings );
        } elseif ( !empty( $_REQUEST[ 'better_payment_stripe_status' ] ) && $_REQUEST[ 'better_payment_stripe_status' ] == 'success' ) {
            $status = self::stripe_payment_success( $settings );
        } elseif ( !empty( $_REQUEST[ 'better_payment_paystack_status' ] ) && $_REQUEST[ 'better_payment_paystack_status' ] == 'success' ) {
            $status = self::paystack_payment_success( $settings );
        }

        if ( $status ) {
            self::success_message_template( $settings, $status );
            $redirection_url_success = ! empty( $settings['better_payment_form_success_page_url']['url'] ) ? esc_url( $settings['better_payment_form_success_page_url']['url'] ) : '';
            
            if( $redirection_url_success ){
                ?>
                <script>
                    setTimeout(function(){
                        window.location.replace("<?php echo esc_url( $redirection_url_success ); ?>");
                    }, 2000);
                </script>
                <?php 
            }
        }
        self::remove_arg();

        return $status;
    }

    /**
     * Paypal payment success
     * 
     * @since 0.0.1
     */
    public static function paypal_payment_success( $settings = [] ) {
        $data = $_REQUEST;
        $frontend_data = [
            'amount' => ! empty( $data['payment_gross'] ) ? floatval( $data['payment_gross'] ) : 0,
            'email' => ! empty( $data['payer_email'] ) ? sanitize_email( $data['payer_email'] ) : '',
            'transaction_id' => ! empty( $data[ 'txn_id' ] ) ? sanitize_text_field( $data[ 'txn_id' ] ) : '',
            'currency' => ! empty( $data['mc_currency'] ) ? sanitize_text_field( $data['mc_currency'] ) : __( 'USD', 'better-payment' ),
            'method' => 'paypal',
        ];
        if ( !empty( $data[ 'payment_status' ] ) && !empty( $data[ 'payer_id' ] ) && !empty( $data[ 'payer_status' ] ) ) {
            global $wpdb;
            $table   = "{$wpdb->prefix}better_payment";
            $results = $wpdb->get_row(
                $wpdb->prepare( "SELECT id,form_fields_info,referer FROM $table WHERE order_id=%s and status is NULL limit 1", sanitize_text_field( $_REQUEST[ 'item_number' ] ) )
            );
            
            if ( !empty( $results->id ) ) {
                $updated = $wpdb->update(
                    $table,
                    array(
                        'transaction_id' => sanitize_text_field( $data[ 'txn_id' ] ),
                        'status'         => sanitize_text_field( $data[ 'payment_status' ] ),
                        'email'          => sanitize_email( $data[ 'payer_email' ] ),
                        'customer_info'  => maybe_serialize( $data ),
                    ),
                    array( 'ID' => $results->id )
                );
                
                if ( false !== $updated ) {
                    //Send email notification
                    if ( 
                        ( isset($settings[ 'better_payment_form_email_enable' ]) && $settings[ 'better_payment_form_email_enable' ] == 'yes' ) 
                        || ( $results->referer === 'elementor-form' )
                        ) {
                        $is_elementor_form = ! empty( $results->referer ) && $results->referer === 'elementor-form'  ? 1 : 0;
                        self::better_email_notification(sanitize_text_field( $data[ 'txn_id' ] ), sanitize_email( $data[ 'payer_email' ] ), $settings, 'PayPal', $results->form_fields_info, $is_elementor_form);
                    }

                    return $frontend_data;
                }
            }
        }else if($data['better_payment_paypal_status'] === 'success'){
            return ( isset($data[ 'txn_id' ]) && !empty( sanitize_text_field( $data[ 'txn_id' ] ) ) ) ? $frontend_data : __('Payment under processing!', 'better-payment');
        }
        return false;
    }

    /**
     * Stripe payment success
     * 
     * @since 0.0.1
     */
    public static function stripe_payment_success( $settings = [] ) {

        $data = $_REQUEST;

        if ( !empty( $data[ 'better_payment_stripe_id' ] ) ) {
            global $wpdb;
            $table   = "{$wpdb->prefix}better_payment";
            $results = $wpdb->get_row(
                $wpdb->prepare( "SELECT id, obj_id, amount, transaction_id, currency, form_fields_info, referer FROM $table WHERE order_id=%s and status = 'unpaid' limit 1", sanitize_text_field( $data[ 'better_payment_stripe_id' ] ) )
            );
            
            if ( !empty( $results->obj_id ) && !empty( $settings[ 'better_payment_stripe_secret_key' ] ) ) {
                $header = array(
                    'Authorization'  => 'Basic ' . base64_encode( sanitize_text_field( $settings[ 'better_payment_stripe_secret_key' ] ) . ':' ),
                    'Stripe-Version' => '2019-05-16',
                );

                $request = [
                    'expand' => [
                        'subscription.latest_invoice.payment_intent',
                        'payment_intent'
                    ]
                ];
                $form_fields_info = maybe_unserialize($results->form_fields_info);
                $is_payment_recurring = ! empty( $form_fields_info['mode'] ) && 'subscription' === $form_fields_info['mode'];
                $is_payment_split_payment = ( $is_payment_recurring ) && ( ! empty( $form_fields_info['is_payment_split_payment'] ) && 1 === intval( $form_fields_info['is_payment_split_payment'] ) );
                
                $action_data = [
                    'form_fields_info' => $form_fields_info,
                    'is_payment_recurring' => $is_payment_recurring,
                    'is_payment_split_payment' => $is_payment_split_payment,
                    'checkout_session_id' => $results->obj_id,
                ];

                $response = wp_safe_remote_post(
                    'https://api.stripe.com/v1/checkout/sessions/' . $results->obj_id,
                    array(
                        'method'  => 'GET',
                        'headers' => $header,
                        'timeout' => 70,
                        'body'    => $request
                    )
                );

                if ( is_wp_error( $response ) || empty( $response[ 'body' ] ) ) {
                    return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
                }

                $response = json_decode( $response[ 'body' ] );
                $transaction_id = ! empty( $results->transaction_id ) ? sanitize_text_field( $results->transaction_id ) : '';
                
                if ( $is_payment_recurring && ! empty( $response->subscription->id ) ) {
                    $transaction_id = sanitize_text_field( $response->subscription->id );
                    $subscription_obj = $response->subscription;
                    $form_fields_info['subscription_id'] = sanitize_text_field( $transaction_id );
                    $form_fields_info['subscription_customer_id'] = ! empty( $response->customer ) ? sanitize_text_field( $response->customer ) : '';
                    $form_fields_info['subscription_plan_id'] = ! empty( $subscription_obj->items->data[0]->plan->id ) ? sanitize_text_field( $subscription_obj->items->data[0]->plan->id ) : '';
                    $form_fields_info['subscription_interval'] = ! empty( $subscription_obj->items->data[0]->plan->interval_count ) ? intval( $subscription_obj->items->data[0]->plan->interval_count ) : '';
                    $form_fields_info['subscription_interval'] .= ! empty( $subscription_obj->items->data[0]->plan->interval ) ? ' ' . sanitize_text_field( $subscription_obj->items->data[0]->plan->interval ) : $form_fields_info['subscription_interval'];
                    $form_fields_info['subscription_current_period_start'] = ! empty( $subscription_obj->current_period_start ) ? intval( $subscription_obj->current_period_start ) : 0;
                    $form_fields_info['subscription_current_period_end'] = ! empty( $subscription_obj->current_period_end ) ? intval( $subscription_obj->current_period_end ) : 0;
                    $form_fields_info['subscription_status'] = sanitize_text_field( $response->status );
                    $form_fields_info['subscription_created_date'] = sanitize_text_field( $subscription_obj->created );
                }

                if ( isset( $response->error->message ) ) {
                    return new \WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'better-payment' ) );
                }

                $customer_email = $is_payment_recurring && ( ! empty( $response->customer_email ) ) ? sanitize_email( $response->customer_email ) : '';
                $customer_email_optional = '';
                if (
                    isset( $response->payment_intent ) &&
                    isset( $response->payment_intent->charges ) &&
                    isset( $response->payment_intent->charges->data ) &&
                    ! empty( $response->payment_intent->charges->data )
                ) {
                    $charge = current( $response->payment_intent->charges->data );
            
                    if (
                        isset( $charge->billing_details ) &&
                        isset( $charge->billing_details->email ) &&
                        ! empty( $charge->billing_details->email )
                    ) {
                        $customer_email_optional = sanitize_email( $charge->billing_details->email );
                    }
                }

                $form_fields_info['is_coupon_applied'] = 0;
                $exact_paid_amount = ! empty( $response->amount_total ) ? floatval( $response->amount_total ) : $results->amount * 100;
                $exact_paid_amount = $exact_paid_amount / 100;
                $form_fields_info['exact_paid_amount'] = $exact_paid_amount;
                if( (int)$exact_paid_amount != (int)$results->amount ) {
                    $form_fields_info['paid_amount_diff'] = $results->amount - $exact_paid_amount;
                    $form_fields_info['is_coupon_applied'] = 1;
                }
                
                $updated = $wpdb->update(
                    $table,
                    array(
                        'amount'        => $exact_paid_amount,
                        'status'        => sanitize_text_field( $response->payment_status ),
                        'email'         => $is_payment_recurring ? $customer_email : $customer_email_optional,
                        'customer_info' => maybe_serialize( $response ),
                        'form_fields_info' => maybe_serialize( $form_fields_info ),
                        'transaction_id' => $transaction_id,
                    ),
                    array( 'ID' => $results->id )
                );

                do_action('better_payment/stripe_payment/success', $action_data);
                
                $frontend_data = [
                    'amount' => $exact_paid_amount,
                    'email' => $is_payment_recurring ? $customer_email : $customer_email_optional,
                    'transaction_id' => $transaction_id,
                    'currency' => ! empty( $results->currency ) ? $results->currency : __( 'USD', 'better-payment' ),
                    'method' => 'stripe',
                    'is_payment_recurring' => $is_payment_recurring,
                    'is_payment_split_payment' => $is_payment_split_payment,
                ];

                if ( $is_payment_split_payment ) {
                    $total_amount = ! empty( $form_fields_info['split_payment_total_amount'] ) 
                        ? sanitize_text_field( $form_fields_info['split_payment_total_amount'] ) 
                        : 0;
                    $total_installment = ! empty( $form_fields_info['split_payment_installment_iteration'] ) 
                        ? sanitize_text_field( $form_fields_info['split_payment_installment_iteration'] ) 
                        : 1;

                    // $frontend_data['amount'] = floatval( $total_amount / $total_installment );
                    $frontend_data['total_amount'] = $total_amount;
                }

                if ( false !== $updated ) {
                    //Send email notification
                    $better_customer_email = $is_payment_recurring ? $customer_email : $customer_email_optional;
                    
                    if ( 
                        (isset($settings[ 'better_payment_form_email_enable' ]) && $settings[ 'better_payment_form_email_enable' ] == 'yes' ) 
                        || ( $results->referer === 'elementor-form' )
                        ) {
                        $is_elementor_form = ! empty( $results->referer ) && $results->referer === 'elementor-form'  ? 1 : 0;
                        self::better_email_notification($transaction_id, $better_customer_email, $settings, 'Stripe', $results->form_fields_info, $is_elementor_form);                    
                    }

                    return $frontend_data;
                }
            }
        }
        return false;
    }
    
    /**
     * Paystack payment success
     * 
     * @since 0.0.1
     */
    public static function paystack_payment_success( $settings = [] ) {
        $data = $_REQUEST;

        if ( ! empty( $data[ 'better_payment_paystack_id' ] ) ) {
            global $wpdb;
            $table   = "{$wpdb->prefix}better_payment";
            $results = $wpdb->get_row(
                $wpdb->prepare( "SELECT id,obj_id,transaction_id,form_fields_info,referer FROM $table WHERE order_id=%s and status = 'unpaid' limit 1", sanitize_text_field( $data[ 'better_payment_paystack_id' ] ) )
            );
            
            $header_info = array(
                'Authorization'  => 'Bearer ' . sanitize_text_field( $settings[ 'better_payment_paystack_secret_key' ] ),
                "Cache-Control: no-cache",
            );
            
            $response = wp_safe_remote_get(
                'https://api.paystack.co/transaction/verify/'. sanitize_text_field( $data[ 'reference' ] ),
                array(
                    'headers' => $header_info,
                    'timeout' => 70,
                )
            );

            $response = json_decode(wp_remote_retrieve_body($response));
    
            if ( ! empty( $response->status ) ) {
                $updated = $wpdb->update(
                    $table,
                    array(
                        'status'        => ! empty( $response->data->status ) ? sanitize_text_field( $response->data->status ) : '',
                        'amount'        => ! empty( $response->data->amount ) ? floatval( ( $response->data->amount ) / 100 ) : 0,
                        'obj_id'        => ! empty( $response->data->id ) ? sanitize_text_field( $response->data->id ) : '',
                        'transaction_id' => ! empty( $response->data->reference ) ? sanitize_text_field( $response->data->reference ) : '',
                        'customer_info' => ! empty( $response->data->customer ) ? maybe_serialize( $response->data->customer ) : '',
                        'email'         => ! empty( $response->data->customer->email ) ? sanitize_email( $response->data->customer->email ) : '',
                    ),
                    array( 'ID' => $results->id )
                );
            }

            $better_customer_email = ! empty( $response->data->customer->email ) ? maybe_serialize( $response->data->customer->email ) : '';
            $transaction_id = ! empty( $response->data->reference ) ? sanitize_text_field( $response->data->reference ) : '';

            $frontend_data = [
                'amount' => ! empty( $response->data->amount ) ? floatval( ( $response->data->amount ) / 100 ) : 0,
                'email' => ! empty( $better_customer_email ) ? $better_customer_email : '',
                'transaction_id' => $transaction_id,
                'currency' => ! empty( $response->data->currency ) ? sanitize_text_field( $response->data->currency ) : __( 'USD', 'better-payment' ),
                'method' => 'paystack',
            ];

            if ( ! empty( $updated ) && $better_customer_email ) {
                //Send email notification
                if ( 
                    (isset($settings[ 'better_payment_form_email_enable' ]) && $settings[ 'better_payment_form_email_enable' ] == 'yes' ) 
                    || ( $results->referer === 'elementor-form' )
                    ) {
                    $is_elementor_form = ! empty( $results->referer ) && $results->referer === 'elementor-form'  ? 1 : 0;
                    self::better_email_notification($transaction_id, $better_customer_email, $settings, 'Paystack', $results->form_fields_info, $is_elementor_form);                    
                }

                return $frontend_data;
            }
        }
        return false;
    }

    /**
     * Success message template
     * 
     * @since 0.0.1
     */
    public static function success_message_template( $settings = [], $tr_id = '' ) {
        if ( empty( $tr_id ) || is_wp_error($tr_id)) {
            return false;
        }
        wp_enqueue_script('better-payment-admin-script');
        $image_url = BETTER_PAYMENT_ASSETS . '/img/success.svg';
        $show_icon = 0;
        $default_icon = 1;
        $helper_obj = new Helper();

        $store_name = ( isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && !empty( $settings['better_payment_form_title'] ) ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo('name') );

        $payment_desc_text = !empty( $settings['better_payment_form_success_message_heading'] ) 
            ? esc_html($settings['better_payment_form_success_message_heading']) 
            : esc_html( __('You paid', 'better-payment') . ' [currency_symbol][amount] ' . __('to', 'better-payment') . ' [store_name]');

        $bp__currency_symbol = esc_html( ! empty( $tr_id['currency'] ) ? $helper_obj->get_currency_symbol( $tr_id['currency'] ) : '' );

        $payment_desc_text = str_replace( '[currency_symbol]', '<span class="bp-bold">' . $bp__currency_symbol . '</span>', $payment_desc_text );

        $payment_desc_text = str_replace( '[amount]', '<span class="bp-bold">' . esc_html( ! empty( $tr_id['amount'] ) ? $tr_id['amount'] : 0 ) . '</span>', $payment_desc_text );
        
        $payment_desc_text = str_replace( '[store_name]', '<span class="bp-bold">' . esc_html( $store_name ) . '</span>', $payment_desc_text );

        $payment_mail_text = !empty( $settings['better_payment_form_success_message_sub_heading'] ) ? esc_html($settings['better_payment_form_success_message_sub_heading']) : esc_html( __('Payment Confirmation email will be sent to ', 'better-payment') . '[customer_email]');

        $payment_mail_text = str_replace( '[customer_email]', '<span class="bp-bold">' . esc_html( ! empty( $tr_id['email'] ) ? $tr_id['email'] : '' ) . '</span>', $payment_mail_text );

        if ( !empty( $settings[ 'better_payment_form_success_message_icon' ][ 'library' ] ) ) {
            if ( $settings[ 'better_payment_form_success_message_icon' ][ 'library' ] == 'svg' ) {
                $image_url = $settings[ 'better_payment_form_success_message_icon' ][ 'value' ][ 'url' ];
                $default_icon = 0;
            } else {
                $show_icon = 1;
                $image_url = $settings[ 'better_payment_form_success_message_icon' ][ 'value' ];
                $default_icon = 0;
            }
        }


        $allowed_payment_methods = [
            'paypal' => BETTER_PAYMENT_ASSETS . '/img/paypal-2.svg',
            'stripe' => BETTER_PAYMENT_ASSETS . '/img/stripe-2.svg',
            'paystack' => BETTER_PAYMENT_ASSETS . '/img/paystack-2.svg',
        ];
        $payment_method_logo = isset( $tr_id['method'] ) && isset( $allowed_payment_methods[ $tr_id['method'] ] ) ? $allowed_payment_methods[ $tr_id['method'] ] : ''; 

        $better_payment_form_success_message_thanks = !empty( $settings['better_payment_form_success_message_thanks'] ) 
            ? esc_html( $settings['better_payment_form_success_message_thanks'] ) 
            : esc_html__('Thank You!', 'better-payment');
        $better_payment_form_success_message_transaction = !empty( $settings['better_payment_form_success_message_transaction'] ) 
            ? esc_html__( $settings['better_payment_form_success_message_transaction'], 'better-payment' ) 
            : esc_html__('Transaction ID', 'better-payment');
        $better_payment_form_success_message_amount_text = !empty( $settings['better_payment_form_success_message_amount_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_amount_text'] ) 
            : esc_html__( 'Amount', 'better-payment' );
        $better_payment_form_success_message_currency_text = !empty( $settings['better_payment_form_success_message_currency_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_currency_text'] ) 
            : esc_html__( 'Currency', 'better-payment' );
        $better_payment_form_success_message_pay_method_text = !empty( $settings['better_payment_form_success_message_pay_method_text'] ) ? esc_html( $settings['better_payment_form_success_message_pay_method_text'] ) : esc_html__( 'Payment Method', 'better-payment' );
        $better_payment_form_success_message_pay_type_text = !empty( $settings['better_payment_form_success_message_pay_type_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_pay_type_text'] ) 
            : esc_html__( 'Payment Type', 'better-payment' );
        $better_payment_form_payment_type = !empty( $settings['better_payment_form_payment_type'] ) 
            ? esc_html( $settings['better_payment_form_payment_type'] ) 
            : '';
        if ( ! empty( $better_payment_form_payment_type ) ) {
            if (  'one-time' === $better_payment_form_payment_type ) {
                $better_payment_form_payment_type_value = esc_html__( 'One Time Payment', 'better-payment' );
            } elseif (  'recurring' === $better_payment_form_payment_type ) {
                $better_payment_form_payment_type_value = esc_html__( 'Recurring Payment', 'better-payment' );
            } else {
                $better_payment_form_payment_type_value = esc_html__( 'Split Payment', 'better-payment' );
            }
        }
        $better_payment_form_success_message_merchant_details_text = !empty( $settings['better_payment_form_success_message_merchant_details_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_merchant_details_text'] ) 
            : esc_html__( 'Merchant Details', 'better-payment' );
        $better_payment_form_success_message_merchant_details_value = isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && ! empty( $settings['better_payment_form_title'] ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo( 'name' ) );
        $better_payment_form_success_message_paid_amount_text = !empty( $settings['better_payment_form_success_message_paid_amount_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_paid_amount_text'] ) 
            : esc_html__( 'Paid Amount', 'better-payment' );
        $better_payment_form_success_message_purchase_details_text = !empty( $settings['better_payment_form_success_message_purchase_details_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_purchase_details_text'] ) 
            : esc_html__( 'Purchase Details', 'better-payment' );
        $better_payment_form_success_message_print_text = !empty( $settings['better_payment_form_success_message_print_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_print_text'] ) 
            : esc_html__( 'Print', 'better-payment' );
        $better_payment_form_success_message_view_details_btn_text = !empty( $settings['better_payment_form_success_message_view_details_btn_text'] ) 
            ? esc_html( $settings['better_payment_form_success_message_view_details_btn_text'] ) 
            : esc_html__( 'View Details', 'better-payment' );
        $better_payment_form_success_page_view_details_url = !empty( $settings['better_payment_form_success_page_view_details_url']['url'] ) ? esc_url( $settings['better_payment_form_success_page_view_details_url']['url'] ) : 'javascript:void(0)';
        ?>
        <section class="bp-thank_page">
            <div class="bp-thank_page-wrapper">
                <div class="bp-thank_page-logo">
                    <?php if( $default_icon ): ?>
                        <span class="bp-thank_page-logo_wrapper">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/success-2.svg' ); ?>" alt="Better Payment logo">
                        </span>
                    <?php endif; ?>
                    <?php if( ! $default_icon ): ?>
                        <?php if( $show_icon ): ?>
                        <span class="bp-thank_page-custom-logo_wrapper-success">
                            <?php 
                                // Use Elementor's Icons_Manager to properly render icons with FontAwesome support
                                if (!empty($settings['better_payment_form_success_message_icon']['value'])) {
                                    \Elementor\Icons_Manager::render_icon($settings['better_payment_form_success_message_icon'], ['aria-hidden' => 'true']);
                                } else {
                                    // Fallback to default icons
                                    echo '<i class="' . esc_attr($image_url) . '"></i>';
                                }
                            ?>
                        </span>
                        <?php endif; ?>
                        <?php if( ! $show_icon ): ?>
                            <span class="bp-thank_page-logo_wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="Success logo">
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="bp-thank_page-text">
                    <h2 class="bp-font_ibm bp-page_header">
                        <?php echo wp_kses_post( $better_payment_form_success_message_thanks ); ?>
                        <span> &#127881;</span>
                    </h2>
                    <p class="bp-font_ibm bp-reason_for-text">
                        <?php echo wp_kses_post( $payment_desc_text ); ?>
                    </p>
                    <?php if (isset( $settings['better_payment_form_email_enable'] ) && !empty( $tr_id['email'] )): ?>
                        <p class="bp-font_ibm bp-payment_info">
                            <?php echo wp_kses_post( $payment_mail_text ); ?>
                        </p>
                    <?php endif; ?>

                    <div class="bp-flex bp-transaction_info-wrapper">
                        <p class="bp-flex bp-font_ibm  bp-transaction_info"> <span class="line-height-0">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/wallet.svg' ); ?>" alt="Better Payment wallet"> 
                            </span>
                            <span>
                                <?php echo wp_kses_post( $better_payment_form_success_message_transaction ); ?>: 
                            </span>
                            <span class="bp-bold bp-transaction_id"><?php echo esc_html( ! empty( $tr_id['transaction_id']) ? $tr_id['transaction_id'] : '' ); ?></span>
                        </p>
                    
                        <button class="bp-transaction_data-copy_btn ">
                            <svg width="20" height="20" viewBox="0 0 20 20"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M15.0002 15.601C16.3257 15.601 17.4002 14.5265 17.4002 13.201V8.40098C17.4002 5.38399 17.4002 3.87549 16.463 2.93823C15.5257 2.00098 14.0172 2.00098 11.0002 2.00098H7.80024C6.47476 2.00098 5.40024 3.07549 5.40024 4.40098M10.2002 18.001H7.80024C5.5375 18.001 4.40613 18.001 3.70319 17.298C3.00024 16.5951 3.00024 15.4637 3.00024 13.201V9.20098C3.00024 6.93823 3.00024 5.80686 3.70319 5.10392C4.40613 4.40098 5.5375 4.40098 7.80024 4.40098H10.2002C12.463 4.40098 13.5944 4.40098 14.2973 5.10392C15.0002 5.80686 15.0002 6.93824 15.0002 9.20098V13.201C15.0002 15.4637 15.0002 16.5951 14.2973 17.298C13.5944 18.001 12.463 18.001 10.2002 18.001Z"
                                stroke-width="1.152" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bp-thank_page-info">
                    <ul class="bp-page_info-list">
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_amount_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold"><?php echo $bp__currency_symbol . esc_html( ! empty( $tr_id['amount'] ) ? $tr_id['amount'] : 0 ); ?></span>
                        </li>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class=" bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_currency_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold"><?php echo esc_html( ! empty( $tr_id['currency'] ) ? $tr_id['currency'] : '' ); ?></span>
                        </li>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_pay_method_text );
                                ?>
                            </span>
                            <div class="bp-payment_method-logo-wrapper">
                                <?php if ( !empty( $payment_method_logo ) ) : ?>
                                    <img src="<?php echo esc_url( $payment_method_logo ); ?>" alt="<?php echo esc_attr( $tr_id['method'] ); ?>" class="bp-payment_method-logo">
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php if( !empty( $better_payment_form_payment_type ) ): ?>
                            <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                                <span class="bp-info_content">
                                    <?php
                                        echo wp_kses_post( $better_payment_form_success_message_pay_type_text );
                                    ?>
                                </span>
                                <span class="bp-info_content  bp-bold"><?php echo wp_kses_post( $better_payment_form_payment_type_value ); ?></span>
                            </li>
                        <?php endif; ?>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_merchant_details_text );
                                ?>
                            </span>
                            <span class="bp-info_content  bp-bold">
                                <?php echo wp_kses_post( $better_payment_form_success_message_merchant_details_value );  ?>
                            </span>
                        </li>
                        <?php if( isset( $tr_id['is_payment_split_payment'] ) && $tr_id['is_payment_split_payment'] ): ?>
                            <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                                <span class="bp-info_content">
                                    <?php
                                        echo wp_kses_post( $better_payment_form_success_message_paid_amount_text );
                                    ?>
                                </span>
                                <span class="bp-info_content  bp-bold">
                                    <?php echo $bp__currency_symbol . esc_html($tr_id['amount']); ?> out of <?php echo $bp__currency_symbol . esc_html($tr_id['total_amount']); ?>
                                </span>
                            </li>
                        <?php endif; ?>
                        <?php
                        $has_woo_products = !empty($settings['better_payment_form_woocommerce_product_id']) || !empty($settings['better_payment_form_woocommerce_product_ids']);
                        $has_fluentcart_products = !empty($settings['better_payment_form_fluentcart_product_id']) || !empty($settings['better_payment_form_fluentcart_product_ids']);

                        if( $has_woo_products || $has_fluentcart_products ):
                        ?>
                        <li class="bp-flex bp-font_ibm  bp-page_info-list_item">
                            <span class="bp-info_content">
                                <?php
                                    echo wp_kses_post( $better_payment_form_success_message_purchase_details_text );
                                ?>
                            </span>
                            <?php
                            $product_names = [];

                            if( !empty( $settings['better_payment_form_woocommerce_product_id'] ) ):
                                $product_names[] = get_the_title( $settings['better_payment_form_woocommerce_product_id'] );
                            endif;

                            if( !empty( $settings['better_payment_form_woocommerce_product_ids'] ) ):
                                $product_ids = $settings['better_payment_form_woocommerce_product_ids'];
                                foreach( $product_ids as $product_id ):
                                    $product_names[] = get_the_title( $product_id );
                                endforeach;
                            endif;

                            if( !empty( $settings['better_payment_form_fluentcart_product_id'] ) && function_exists('fluentCart') && class_exists( '\FluentCart\App\Models\Product' ) ):
                                try {
                                    $fluentcart_product = \FluentCart\App\Models\Product::query()->find($settings['better_payment_form_fluentcart_product_id']);
                                    if ($fluentcart_product) {
                                        $product_names[] = sanitize_text_field( $fluentcart_product->post_title );
                                    }
                                } catch ( \Exception $e ) {
                                    //
                                }
                            endif;

                            if( !empty( $settings['better_payment_form_fluentcart_product_ids'] ) && function_exists('fluentCart') && class_exists( '\FluentCart\App\Models\Product' ) ):
                                $product_ids = $settings['better_payment_form_fluentcart_product_ids'];
                                foreach( $product_ids as $product_id ):
                                    try {
                                        $fluentcart_product = \FluentCart\App\Models\Product::query()->find($product_id);
                                        if ($fluentcart_product) {
                                            $product_names[] = sanitize_text_field( $fluentcart_product->post_title );
                                        }
                                    } catch ( \Exception $e ) {
                                        //
                                    }
                                endforeach;
                            endif;

                            if( !empty($product_names) ):
                                echo '<span class="bp-info_content  bp-bold">' . implode( ', ', $product_names ) . '</span>';
                            endif;
                            ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bp-flex bp-button_group">
                    <button class="bp-flex bp-font_ibm bp-print_btn">
                        <span style="line-height: 0;">
                            <img src="<?php echo esc_url( BETTER_PAYMENT_ASSETS . '/img/print.svg' ); ?>" alt="Better Payment print">
                        </span>
                        <span class="bp-font_ibm">
                            <?php echo wp_kses_post( $better_payment_form_success_message_print_text ); ?>
                        </span>
                    </button>
                    
                    <div class="bp-flex bp-btn_wrapper">
                        <a href="<?php echo $better_payment_form_success_page_view_details_url; ?>" target="_blank" class="bp-font_ibm bp-details_btn">
                            <?php echo wp_kses_post( $better_payment_form_success_message_view_details_btn_text ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Error message template
     * 
     * @since 0.0.1
     */
    public static function failed_message_template( $settings = [], $transaction_data = false ) {

        $image_url = BETTER_PAYMENT_ASSETS . '/img/fail.svg';
        $show_icon = 0;
        $default_icon = 1;
        $failed_heading = !empty( $settings['better_payment_form_error_message_heading'] ) ? esc_html( $settings['better_payment_form_error_message_heading'] ) : esc_html__( 'Payment Failed!', 'better-payment' );
        $failed_sub_heading = !empty( $settings['better_payment_form_error_message_sub_heading'] ) ? esc_html( $settings['better_payment_form_error_message_sub_heading'] ) : esc_html__( 'Your payment has failed. Please check your payment details', 'better-payment' );
        $transaction_id_text = !empty( $settings['better_payment_form_error_message_transaction_id_text'] ) ? esc_html( $settings['better_payment_form_error_message_transaction_id_text'] ) : esc_html__( 'Transaction ID', 'better-payment' );
        $show_details_button = !empty( $settings['better_payment_form_error_details_button_switch'] ) && $settings['better_payment_form_error_details_button_switch'] == 'yes' ? true : false;
        $details_button_text = !empty( $settings['better_payment_form_error_details_button_text'] ) ? esc_html( $settings['better_payment_form_error_details_button_text'] ) : esc_html__( 'View Details', 'better-payment' );
        $better_payment_form_error_details_page_url = !empty( $settings['better_payment_form_error_details_page_url']['url'] ) ? esc_url( $settings['better_payment_form_error_details_page_url']['url'] ) : 'javascript:void(0)';
        $store_name = ( isset( $settings['better_payment_form_payment_source'] ) && $settings['better_payment_form_payment_source'] === 'manual' && !empty( $settings['better_payment_form_title'] ) ) 
            ? esc_html($settings['better_payment_form_title']) 
            : esc_html( get_bloginfo('name') );

        // Get transaction details
        $transaction_amount = '';
        $transaction_id = '';
        $currency_symbol = '';
        
        if ( !empty( $transaction_data ) ) {
            $transaction_amount = !empty( $transaction_data['amount'] ) ? floatval( $transaction_data['amount'] ) : '';
            $currency_symbol = !empty( $transaction_data['currency_symbol'] ) ? esc_html( $transaction_data['currency_symbol'] ) : '';
            $transaction_id = !empty( $transaction_data['transaction_id'] ) ? esc_html( $transaction_data['transaction_id'] ) : '';
        }

        if ( !empty( $settings[ 'better_payment_form_error_message_icon' ][ 'library' ] ) ) {
            if ( $settings[ 'better_payment_form_error_message_icon' ][ 'library' ] == 'svg' ) {
                $image_url = $settings[ 'better_payment_form_error_message_icon' ][ 'value' ][ 'url' ];
                $default_icon = 0;
            } else {
                $show_icon = 1;
                $image_url = $settings[ 'better_payment_form_error_message_icon' ][ 'value' ];
                $default_icon = 0;
            }
        }
        // dd($settings[ 'better_payment_form_error_message_icon' ]);
        ?>
        <section class="payment-failed-screen-section">
            <div class="bp-thank_page-wrapper">
                <div class="bp-thank_page-logo">
                    <?php if( $default_icon ): ?>
                    <span class="bp-thank_page-logo_wrapper">
                        <svg width="58" height="58" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M40.804 10.7998H16.8039C13.9409 10.8041 11.1964 11.9434 9.17192 13.9678C7.14746 15.9923 6.00822 18.7368 6.00391 21.5998H51.604C51.5997 18.7368 50.4604 15.9923 48.436 13.9678C46.4115 11.9434 43.667 10.8041 40.804 10.7998ZM6.00391 26.3998V35.9998C6.00822 38.8628 7.14746 41.6073 9.17192 43.6318C11.1964 45.6562 13.9409 46.7955 16.8039 46.7998H40.804C43.667 46.7955 46.4115 45.6562 48.436 43.6318C50.4604 41.6073 51.5997 38.8628 51.604 35.9998V26.3998H6.00391ZM24.0039 38.3998H16.8039C16.1674 38.3998 15.557 38.1469 15.1069 37.6968C14.6568 37.2468 14.4039 36.6363 14.4039 35.9998C14.4039 35.3633 14.6568 34.7529 15.1069 34.3028C15.557 33.8527 16.1674 33.5998 16.8039 33.5998H24.0039C24.6404 33.5998 25.2509 33.8527 25.7009 34.3028C26.151 34.7529 26.4039 35.3633 26.4039 35.9998C26.4039 36.6363 26.151 37.2468 25.7009 37.6968C25.2509 38.1469 24.6404 38.3998 24.0039 38.3998Z" fill="#F44336"></path>
                            <circle cx="45.5" cy="41.5" r="6.5" fill="white"></circle>
                            <foreignObject x="21.4686" y="17.3701" width="47.2066" height="47.3399">
                                <div xmlns="http://www.w3.org/1999/xhtml" style="backdrop-filter:blur(6.36px);clip-path:url(#bgblur_0_3595_8284_clip_path);height:100%;width:100%">
                                </div>
                            </foreignObject>
                            <g filter="url(#filter0_d_3595_8284)" data-figma-bg-blur-radius="12.71">
                                <path d="M55.765 38.782C55.3067 36.6386 54.2191 34.6825 52.6429 33.1664C50.6276 31.1788 47.9133 30.0699 45.0893 30.0804C42.1969 30.0848 39.4243 31.2411 37.3791 33.2958C35.334 35.3505 34.183 38.136 34.1786 41.0417V41.8849C34.2614 43.0459 34.5336 44.1853 34.9843 45.2576C36.0299 47.7766 37.9733 49.8131 40.4344 50.9688C42.8955 52.1245 45.6972 52.3162 48.2915 51.5064C50.8859 50.6967 53.0862 48.9437 54.4622 46.5904C55.8381 44.2371 56.2907 41.4528 55.7314 38.782H55.765ZM46.4154 44.7686L45.0893 43.4364L43.7632 44.7686C43.6072 44.9266 43.4215 45.0521 43.217 45.1377C43.0124 45.2233 42.793 45.2674 42.5714 45.2674C42.3498 45.2674 42.1304 45.2233 41.9259 45.1377C41.7213 45.0521 41.5357 44.9266 41.3796 44.7686C41.2223 44.6118 41.0974 44.4253 41.0122 44.2198C40.927 44.0143 40.8831 43.7939 40.8831 43.5713C40.8831 43.3486 40.927 43.1282 41.0122 42.9227C41.0974 42.7172 41.2223 42.5307 41.3796 42.374L41.8664 41.8849L42.7057 41.0417L41.3796 39.7095C41.0636 39.3919 40.886 38.9613 40.886 38.5122C40.886 38.0631 41.0636 37.6324 41.3796 37.3149C41.6957 36.9973 42.1244 36.8189 42.5714 36.8189C43.0184 36.8189 43.4471 36.9973 43.7632 37.3149L45.0893 38.6471L46.4154 37.3149C46.7314 36.9973 47.1601 36.8189 47.6071 36.8189C48.0541 36.8189 48.4828 36.9973 48.7989 37.3149C49.115 37.6324 49.2926 38.0631 49.2926 38.5122C49.2926 38.9613 49.115 39.3919 48.7989 39.7095L47.4729 41.0417L48.2618 41.8343L48.7989 42.374C48.9563 42.5307 49.0811 42.7172 49.1664 42.9227C49.2516 43.1282 49.2954 43.3486 49.2954 43.5713C49.2954 43.7939 49.2516 44.0143 49.1664 44.2198C49.0811 44.4253 48.9563 44.6118 48.7989 44.7686C48.6429 44.9266 48.4572 45.0521 48.2527 45.1377C48.0481 45.2233 47.8287 45.2674 47.6071 45.2674C47.3855 45.2674 47.1661 45.2233 46.9616 45.1377C46.7571 45.0521 46.5714 44.9266 46.4154 44.7686Z" fill="#F44336" fill-opacity="0.5" shape-rendering="crispEdges"></path>
                            </g>
                            <defs>
                                <filter id="filter0_d_3595_8284" x="21.4686" y="17.3701" width="47.2066" height="47.3399" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                    <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"></feColorMatrix>
                                    <feOffset dx="-4"></feOffset>
                                    <feGaussianBlur stdDeviation="2"></feGaussianBlur>
                                    <feComposite in2="hardAlpha" operator="out"></feComposite>
                                    <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.04 0"></feColorMatrix>
                                    <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3595_8284"></feBlend>
                                    <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3595_8284" result="shape"></feBlend>
                                </filter>
                                <clipPath id="bgblur_0_3595_8284_clip_path" transform="translate(-21.4686 -17.3701)">
                                    <path d="M55.765 38.782C55.3067 36.6386 54.2191 34.6825 52.6429 33.1664C50.6276 31.1788 47.9133 30.0699 45.0893 30.0804C42.1969 30.0848 39.4243 31.2411 37.3791 33.2958C35.334 35.3505 34.183 38.136 34.1786 41.0417V41.8849C34.2614 43.0459 34.5336 44.1853 34.9843 45.2576C36.0299 47.7766 37.9733 49.8131 40.4344 50.9688C42.8955 52.1245 45.6972 52.3162 48.2915 51.5064C50.8859 50.6967 53.0862 48.9437 54.4622 46.5904C55.8381 44.2371 56.2907 41.4528 55.7314 38.782H55.765ZM46.4154 44.7686L45.0893 43.4364L43.7632 44.7686C43.6072 44.9266 43.4215 45.0521 43.217 45.1377C43.0124 45.2233 42.793 45.2674 42.5714 45.2674C42.3498 45.2674 42.1304 45.2233 41.9259 45.1377C41.7213 45.0521 41.5357 44.9266 41.3796 44.7686C41.2223 44.6118 41.0974 44.4253 41.0122 44.2198C40.927 44.0143 40.8831 43.7939 40.8831 43.5713C40.8831 43.3486 40.927 43.1282 41.0122 42.9227C41.0974 42.7172 41.2223 42.5307 41.3796 42.374L41.8664 41.8849L42.7057 41.0417L41.3796 39.7095C41.0636 39.3919 40.886 38.9613 40.886 38.5122C40.886 38.0631 41.0636 37.6324 41.3796 37.3149C41.6957 36.9973 42.1244 36.8189 42.5714 36.8189C43.0184 36.8189 43.4471 36.9973 43.7632 37.3149L45.0893 38.6471L46.4154 37.3149C46.7314 36.9973 47.1601 36.8189 47.6071 36.8189C48.0541 36.8189 48.4828 36.9973 48.7989 37.3149C49.115 37.6324 49.2926 38.0631 49.2926 38.5122C49.2926 38.9613 49.115 39.3919 48.7989 39.7095L47.4729 41.0417L48.2618 41.8343L48.7989 42.374C48.9563 42.5307 49.0811 42.7172 49.1664 42.9227C49.2516 43.1282 49.2954 43.3486 49.2954 43.5713C49.2954 43.7939 49.2516 44.0143 49.1664 44.2198C49.0811 44.4253 48.9563 44.6118 48.7989 44.7686C48.6429 44.9266 48.4572 45.0521 48.2527 45.1377C48.0481 45.2233 47.8287 45.2674 47.6071 45.2674C47.3855 45.2674 47.1661 45.2233 46.9616 45.1377C46.7571 45.0521 46.5714 44.9266 46.4154 44.7686Z"></path>
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                    <?php else: ?>
                        <?php if( $show_icon ): ?>
                        <span class="bp-thank_page-custom-logo_wrapper-error">
                            <?php 
                                // Use Elementor's Icons_Manager to properly render icons with FontAwesome support
                                if (!empty($settings['better_payment_form_error_message_icon']['value'])) {
                                    \Elementor\Icons_Manager::render_icon($settings['better_payment_form_error_message_icon'], ['aria-hidden' => 'true']);
                                } else {
                                    // Fallback to default icons
                                    echo '<i class="' . esc_attr($image_url) . '"></i>';
                                }
                            ?>
                        </span>
                        <?php else: ?>
                            <span class="bp-thank_page-logo_wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="Error logo">
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="bp-thank_page-text">
                    <h2 class="bp-font_ibm bp-page_header"><?php echo wp_kses_post( $failed_heading ); ?></h2>
                    <p class="bp-font_ibm bp-reason_for-text">Payment of <?php echo $currency_symbol . $transaction_amount; ?> to <?php echo $store_name; ?> failed.</p>
                    <p class="bp-font_ibm bp-payment_info"><?php echo wp_kses_post( $failed_sub_heading ); ?></p>

                    <div class="bp-flex bp-transaction_info-wrapper">
                        <p class="bp-flex bp-font_ibm  bp-transaction_info"> <span>
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_2914_6421)">
                                    <path d="M12.2002 12.825C11.8821 12.825 11.6242 13.0829 11.6242 13.401C11.6242 13.7191 11.8821 13.977 12.2002 13.977V12.825ZM17.0002 13.401L17.4075 13.8083C17.5156 13.7002 17.5762 13.5537 17.5762 13.401C17.5762 13.2482 17.5156 13.1017 17.4075 12.9937L17.0002 13.401ZM14.993 14.5937C14.768 14.8186 14.768 15.1833 14.993 15.4083C15.2179 15.6332 15.5826 15.6332 15.8075 15.4083L14.993 14.5937ZM15.8075 11.3937C15.5826 11.1687 15.2179 11.1687 14.993 11.3937C14.768 11.6186 14.768 11.9833 14.993 12.2083L15.8075 11.3937ZM10.6002 16.377C10.9184 16.377 11.1762 16.1191 11.1762 15.801C11.1762 15.4829 10.9184 15.225 10.6002 15.225V16.377ZM16.4242 9.40098C16.4242 9.71909 16.6821 9.97698 17.0002 9.97698C17.3184 9.97698 17.5762 9.71909 17.5762 9.40098H16.4242ZM7.40024 13.177C7.71836 13.177 7.97624 12.9191 7.97624 12.601C7.97624 12.2829 7.71836 12.025 7.40024 12.025V13.177ZM4.20024 12.025C3.88213 12.025 3.62424 12.2829 3.62424 12.601C3.62424 12.9191 3.88213 13.177 4.20024 13.177V12.025ZM9.80024 13.177C10.1184 13.177 10.3762 12.9191 10.3762 12.601C10.3762 12.2829 10.1184 12.025 9.80024 12.025V13.177ZM9.40024 12.025C9.08213 12.025 8.82424 12.2829 8.82424 12.601C8.82424 12.9191 9.08213 13.177 9.40024 13.177V12.025ZM1.00024 7.22498C0.682128 7.22498 0.424244 7.48286 0.424244 7.80098C0.424244 8.11909 0.682128 8.37698 1.00024 8.37698V7.22498ZM17.0002 8.37698C17.3184 8.37698 17.5762 8.11909 17.5762 7.80098C17.5762 7.48286 17.3184 7.22498 17.0002 7.22498V8.37698ZM12.2002 13.977H17.0002V12.825H12.2002V13.977ZM16.593 12.9937L14.993 14.5937L15.8075 15.4083L17.4075 13.8083L16.593 12.9937ZM17.4075 12.9937L15.8075 11.3937L14.993 12.2083L16.593 13.8083L17.4075 12.9937ZM7.40024 3.57698H10.6002V2.42498H7.40024V3.57698ZM10.6002 15.225H7.40024V16.377H10.6002V15.225ZM7.40024 15.225C5.87547 15.225 4.78983 15.2238 3.96572 15.113C3.15819 15.0044 2.68857 14.8002 2.3448 14.4564L1.53021 15.271C2.1237 15.8645 2.87695 16.1289 3.81222 16.2547C4.73093 16.3782 5.90803 16.377 7.40024 16.377V15.225ZM0.424244 9.40098C0.424244 10.8932 0.423021 12.0703 0.546538 12.989C0.672282 13.9243 0.936721 14.6775 1.53021 15.271L2.3448 14.4564C2.00103 14.1127 1.79684 13.643 1.68827 12.8355C1.57747 12.0114 1.57624 10.9258 1.57624 9.40098H0.424244ZM10.6002 3.57698C12.125 3.57698 13.2107 3.5782 14.0348 3.689C14.8423 3.79757 15.3119 4.00176 15.6557 4.34553L16.4703 3.53094C15.8768 2.93745 15.1235 2.67301 14.1883 2.54727C13.2696 2.42375 12.0925 2.42498 10.6002 2.42498V3.57698ZM17.5762 9.40098C17.5762 7.90876 17.5775 6.73166 17.4539 5.81296C17.3282 4.87768 17.0638 4.12443 16.4703 3.53094L15.6557 4.34553C15.9995 4.6893 16.2037 5.15892 16.3122 5.96646C16.423 6.79056 16.4242 7.8762 16.4242 9.40098H17.5762ZM7.40024 2.42498C5.90803 2.42498 4.73093 2.42375 3.81222 2.54727C2.87695 2.67301 2.1237 2.93745 1.53021 3.53094L2.3448 4.34553C2.68857 4.00176 3.15819 3.79757 3.96572 3.689C4.78983 3.5782 5.87547 3.57698 7.40024 3.57698V2.42498ZM1.57624 9.40098C1.57624 7.8762 1.57747 6.79056 1.68827 5.96646C1.79684 5.15892 2.00103 4.6893 2.3448 4.34553L1.53021 3.53094C0.936721 4.12443 0.672282 4.87768 0.546538 5.81296C0.423021 6.73166 0.424244 7.90876 0.424244 9.40098H1.57624ZM7.40024 12.025H4.20024V13.177H7.40024V12.025ZM9.80024 12.025H9.40024V13.177H9.80024V12.025ZM1.00024 8.37698H17.0002V7.22498H1.00024V8.37698Z" fill="#8F9AB0"></path>
                                </g>
                                <defs>
                                    <clipPath id="clip0_2914_6421">
                                        <rect width="18" height="18" fill="white"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                            </span>
                            <span><?php echo wp_kses_post( $transaction_id_text ); ?>:</span>
                            <span class="bp-bolt bp-transaction_id"><?php echo $transaction_id; ?></span>
                        </p>

                        <button class="bp-transaction_data-copy_btn ">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.0002 15.601C16.3257 15.601 17.4002 14.5265 17.4002 13.201V8.40098C17.4002 5.38399 17.4002 3.87549 16.463 2.93823C15.5257 2.00098 14.0172 2.00098 11.0002 2.00098H7.80024C6.47476 2.00098 5.40024 3.07549 5.40024 4.40098M10.2002 18.001H7.80024C5.5375 18.001 4.40613 18.001 3.70319 17.298C3.00024 16.5951 3.00024 15.4637 3.00024 13.201V9.20098C3.00024 6.93823 3.00024 5.80686 3.70319 5.10392C4.40613 4.40098 5.5375 4.40098 7.80024 4.40098H10.2002C12.463 4.40098 13.5944 4.40098 14.2973 5.10392C15.0002 5.80686 15.0002 6.93824 15.0002 9.20098V13.201C15.0002 15.4637 15.0002 16.5951 14.2973 17.298C13.5944 18.001 12.463 18.001 10.2002 18.001Z" stroke-width="1.152"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php if( $show_details_button ): ?>
                <div class="bp-btn_wrapper">
                    <a href="<?php echo $better_payment_form_error_details_page_url; ?>" target="_blank" class="bp-font_ibm bp-details_btn"><?php echo $details_button_text; ?></a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Remove args from url
     * 
     * @since 0.0.1
     */
    public static function remove_arg() {
        ?>
        <script>

            if(typeof params === 'undefined'){
                let params = '';                
            }

            params = new URLSearchParams(location.search);

            if (params.has('better_payment_paypal_status') || params.has('better_payment_stripe_status') || params.has('better_payment_paystack_status')) {
                params.delete('better_payment_paypal_status');
                params.delete('better_payment_stripe_status');
                params.delete('better_payment_paystack_status');
                params.delete('better_payment_widget_id');
                params.delete('better_payment_stripe_id');
                params.delete('better_payment_paystack_id');
                window.history.replaceState({}, '', `${location.pathname}?${params}`);
            }
        </script>
        <?php
    }

    /**
     * Get failed transaction data
     * 
     * @since 1.0.0
     */
    public static function get_failed_transaction_data( $order_id = '' ) {
        if ( empty( $order_id ) ) {
            return false;
        }
        
        global $wpdb;
        $table = "{$wpdb->prefix}better_payment";
        
        $result = $wpdb->get_row(
            $wpdb->prepare( 
                "SELECT amount, currency, transaction_id, order_id, source FROM $table WHERE order_id=%s LIMIT 1", 
                $order_id 
            )
        );
        
        if ( !empty( $result ) ) {
            $helper_obj = new Helper();
            return [
                'amount' => floatval( $result->amount ),
                'currency' => sanitize_text_field( $result->currency ),
                'currency_symbol' => $helper_obj->get_currency_symbol( $result->currency ),
                'transaction_id' => !empty( $result->transaction_id ) ? sanitize_text_field( $result->transaction_id ) : 'N/A',
                'method' => sanitize_text_field( $result->source ),
            ];
        }
        
        return false;
    }

    /**
     * Email template
     * 
     * @since 0.0.1
     */
    public static function better_email_notification($transaction_id, $customer_email, $settings, $referrer='Stripe', $form_fields_info='', $is_elementor_form = 0){
        //Send Email: customer and admin
        $better_txn_id = sanitize_text_field( $transaction_id );
        $better_cus_email = sanitize_email( $customer_email );
        $default_subject = sprintf(__('Better Payment transaction on %s', 'better-payment'), esc_html(get_option('blogname')));
        $better_payment_global_settings = DB::get_settings();

        $better_payment_email_to = !empty($better_payment_global_settings['better_payment_settings_general_email_to']) ? $better_payment_global_settings['better_payment_settings_general_email_to'] : get_option( 'admin_email' );

        $better_payment_email_subject = !empty($better_payment_global_settings['better_payment_settings_general_email_subject']) ? $better_payment_global_settings['better_payment_settings_general_email_subject'] :  $default_subject;
        $better_payment_email_content = !empty($better_payment_global_settings['better_payment_settings_general_email_message_admin']) ? $better_payment_global_settings['better_payment_settings_general_email_message_admin'] : 'Email Content';
        $better_payment_email_from = !empty($better_payment_global_settings['better_payment_settings_general_email_from_email']) ? $better_payment_global_settings['better_payment_settings_general_email_from_email'] : '';
        $better_payment_email_from_name = !empty($better_payment_global_settings['better_payment_settings_general_email_from_name']) ? $better_payment_global_settings['better_payment_settings_general_email_from_name'] : '';
        $better_payment_email_reply_to = !empty($better_payment_global_settings['better_payment_settings_general_email_reply_to']) ? $better_payment_global_settings['better_payment_settings_general_email_reply_to'] : '';
        $better_payment_email_cc = !empty($better_payment_global_settings['better_payment_settings_general_email_cc']) ? $better_payment_global_settings['better_payment_settings_general_email_cc'] : '';
        $better_payment_email_bcc = !empty($better_payment_global_settings['better_payment_settings_general_email_bcc']) ? $better_payment_global_settings['better_payment_settings_general_email_bcc'] : '';
        $better_payment_email_content_type = !empty($better_payment_global_settings['better_payment_settings_general_email_send_as']) ? $better_payment_global_settings['better_payment_settings_general_email_send_as'] : 'html';
        
        $better_payment_email_subject_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_subject_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_subject_customer'] : $default_subject;
        $better_payment_email_content_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_message_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_message_customer'] : 'Email Content';
        $better_payment_email_from_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_from_email_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_from_email_customer'] : '';
        $better_payment_email_from_name_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_from_name_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_from_name_customer'] : '';
        $better_payment_email_reply_to_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_reply_to_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_reply_to_customer'] : '';
        $better_payment_email_cc_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_cc_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_cc_customer'] : '';
        $better_payment_email_bcc_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_bcc_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_bcc_customer'] : '';
        $better_payment_email_content_type_customer = !empty($better_payment_global_settings['better_payment_settings_general_email_send_as_customer']) ? $better_payment_global_settings['better_payment_settings_general_email_send_as_customer'] : 'html';
        $args = [];

        $args['email_form_name'] = ! empty( $settings['better_payment_form_title'] ) ? sanitize_text_field( $settings['better_payment_form_title'] ) : 'N/A';
        $args['email_content_heading_show'] = ! empty( $settings['better_payment_email_content_heading'] ) && 'yes' === $settings['better_payment_email_content_heading'] ? 1 : 0;
        $args['email_content_from_section_show'] = ! empty( $settings['better_payment_email_content_from_section'] ) && 'yes' === $settings['better_payment_email_content_from_section'] ? 1 : 0;
        $args['email_content_to_section_show'] = ! empty( $settings['better_payment_email_content_to_section'] ) && 'yes' === $settings['better_payment_email_content_to_section'] ? 1 : 0;
        $args['email_content_transaction_summary_show'] = ! empty( $settings['better_payment_email_content_transaction_summary'] ) && 'yes' === $settings['better_payment_email_content_transaction_summary'] ? 1 : 0;
        $args['email_content_footer_text_show'] = ! empty( $settings['better_payment_email_content_footer_text'] ) && 'yes' === $settings['better_payment_email_content_footer_text'] ? 1 : 0;
        $args['email_content_customer'] = ! empty( $settings['better_payment_email_content_customer'] ) ? $settings['better_payment_email_content_customer'] : '';
        $args['email_content_admin'] = ! empty( $settings['better_payment_email_content'] ) ? $settings['better_payment_email_content'] : '';
        $args['is_elementor_form'] = $is_elementor_form;

        if ( ! empty( $settings['better_payment_email_content_greeting'] ) ) {
            $args['email_content_greeting'] = $settings['better_payment_email_content_greeting'];
        }
        
        if ( ! empty( $settings['better_payment_form_email_logo']['url'] ) ) {
            $args['email_logo_url'] = $settings['better_payment_form_email_logo']['url'];
        }
        
        if ( ! empty( $settings['better_payment_form_email_attachment']['id'] ) ) {
            $args['email_attachment_id'] = intval( $settings['better_payment_form_email_attachment']['id'] );
            $args['email_attachment_path'] = get_attached_file( $args['email_attachment_id'] );
        }
        
        if ( ! empty( $settings['better_payment_form_email_attachment_pdf_show'] ) && ! empty( $settings['better_payment_form_email_attachment_pdf']['id'] ) ) {
            $args['email_attachment_id'] = intval( $settings['better_payment_form_email_attachment_pdf']['id'] );
            $args['email_attachment_path'] = get_attached_file( $args['email_attachment_id'] );
        }

        if(isset($settings['better_payment_email_to'])){
            $better_payment_email_to = sanitize_email($settings['better_payment_email_to']);
        }

        if(isset($settings['better_payment_email_subject'])){
            $better_payment_email_subject = sanitize_text_field($settings['better_payment_email_subject']);
        }

        if(isset($settings['better_payment_email_subject_customer'])){
            $better_payment_email_subject_customer = sanitize_text_field($settings['better_payment_email_subject_customer']);
        }

        if(isset($settings['better_payment_email_content_type'])){
            $better_payment_email_content_type = sanitize_text_field($settings['better_payment_email_content_type']);
        }
        
        if(isset($settings['better_payment_email_content_type_customer'])){
            $better_payment_email_content_type_customer = sanitize_text_field($settings['better_payment_email_content_type_customer']);
        }

        $args['email_content_type'] = $better_payment_email_content_type;
        $args['email_content_type_customer'] = $better_payment_email_content_type_customer;

		$line_break = $better_payment_email_content_type == 'html' ? '<br>' : "\n";
		$line_break_customer = $better_payment_email_content_type_customer == 'html' ? '<br>' : "\n";

        global $wpdb;
        $table   = "{$wpdb->prefix}better_payment";
        $transaction_obj = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE transaction_id=%s limit 1", sanitize_text_field( $transaction_id ) )
        );

        if(isset($settings['better_payment_email_content'])){
            $better_payment_email_content = sanitize_text_field($settings['better_payment_email_content']);
            $better_payment_email_content = self::better_render_email_body( $better_payment_email_content, $form_fields_info, $line_break, 'admin', $transaction_obj, $args );
        } else if ( !empty($better_payment_email_content) ){
            $better_payment_email_content = self::better_render_email_body( $better_payment_email_content, $form_fields_info, $line_break, 'admin', $transaction_obj, $args );
        }

        if(isset($settings['better_payment_email_content_customer'])){
            $better_payment_email_content_customer = sanitize_text_field($settings['better_payment_email_content_customer']);
            $better_payment_email_content_customer = self::better_render_email_body( $better_payment_email_content_customer, $form_fields_info, $line_break_customer, 'customer', $transaction_obj, $args );
        } else if ( !empty($better_payment_email_content_customer) ){
            $better_payment_email_content_customer = self::better_render_email_body( $better_payment_email_content_customer, $form_fields_info, $line_break, 'customer', $transaction_obj, $args ); //$line_break_customer not used : need to check type on parent method and add headers accordingly.
        }

        if(isset($settings['better_payment_email_from'])){
            $better_payment_email_from = sanitize_email($settings['better_payment_email_from']);
        }

        if(isset($settings['better_payment_email_from_name'])){
            $better_payment_email_from_name = sanitize_text_field($settings['better_payment_email_from_name']);
        }

        if(isset($settings['better_payment_email_reply_to'])){
            $better_payment_email_reply_to = sanitize_email($settings['better_payment_email_reply_to']);
        }

        if(isset($settings['better_payment_email_cc'])){
            $better_payment_email_cc = sanitize_email($settings['better_payment_email_cc']);
        }

        if(isset($settings['better_payment_email_bcc'])){
            $better_payment_email_bcc = sanitize_email($settings['better_payment_email_bcc']);
        }

        //Customer Email
        if(isset($settings['better_payment_email_from_customer'])){
            $better_payment_email_from_customer = sanitize_email($settings['better_payment_email_from_customer']);
        }

        if(isset($settings['better_payment_email_from_name_customer'])){
            $better_payment_email_from_name_customer = sanitize_text_field($settings['better_payment_email_from_name_customer']);
        }

        if(isset($settings['better_payment_email_reply_to_customer'])){
            $better_payment_email_reply_to_customer = sanitize_email($settings['better_payment_email_reply_to_customer']);
        }

        if(isset($settings['better_payment_email_cc_customer'])){
            $better_payment_email_cc_customer = sanitize_email($settings['better_payment_email_cc_customer']);
        }

        if(isset($settings['better_payment_email_bcc_customer'])){
            $better_payment_email_bcc_customer = sanitize_email($settings['better_payment_email_bcc_customer']);
        }

        if($better_payment_email_content == ''){
            $better_payment_email_content = __('New better payment transaction! ', 'better-payment');
        }

        if($better_payment_email_content_customer == ''){
            $better_payment_email_content_customer = __('New better payment transaction! ', 'better-payment');
        }

        $better_payment_email_headers = [];
        $better_payment_email_headers_customer = [];

        if($better_payment_email_content_type == 'html'){
            $better_payment_email_headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        
        if($better_payment_email_content_type_customer == 'html'){
            $better_payment_email_headers_customer[] = 'Content-Type: text/html; charset=UTF-8';
        }

        if($better_payment_email_from != ''){
            $better_payment_email_headers[] = "From: $better_payment_email_from_name <$better_payment_email_from>";
        }

        if($better_payment_email_cc != ''){
            $better_payment_email_headers[] = "Cc: <$better_payment_email_cc>";
        }

        if($better_payment_email_bcc != ''){
            $better_payment_email_headers[] = "BCc: $better_payment_email_bcc";
        }

        if($better_payment_email_reply_to != ''){
           $better_payment_email_headers[] = "Reply-To: $better_payment_email_reply_to";
        }

        //Customer Email
        if($better_payment_email_from_customer != ''){
            $better_payment_email_headers_customer[] = "From: $better_payment_email_from_name_customer <$better_payment_email_from_customer>";
        }

        $form_fields_info_array = [];
        
        if( ! empty( $form_fields_info ) ){
            $form_fields_info_array = maybe_unserialize( $form_fields_info );
        }

        $form_fields_info_cus_email = ! empty( $form_fields_info_array['primary_email'] ) ? sanitize_email( $form_fields_info_array['primary_email'] ) : '';
        
        if($better_payment_email_cc_customer != '' || ! empty( $form_fields_info_cus_email ) ){
            $better_payment_email_cc_customer_multiple = implode(',', [$form_fields_info_cus_email, $better_payment_email_cc_customer]);
            $better_payment_email_headers_customer[] = "Cc: $better_payment_email_cc_customer_multiple";
        }

        if($better_payment_email_bcc_customer != ''){
            $better_payment_email_headers_customer[] = "BCc: $better_payment_email_bcc_customer";
        }

        if($better_payment_email_reply_to_customer != ''){
           $better_payment_email_headers_customer[] = "Reply-To: $better_payment_email_reply_to_customer";
        }

        $email_attachments = ! empty( $args['email_attachment_path'] ) ? [ $args['email_attachment_path'] ] : [];
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');

        $file_info = count( $email_attachments ) ? pathinfo($email_attachments[0]) : [];
        $file_extension = count( $file_info ) ? strtolower($file_info['extension']) : '';

        if ( ! in_array( $file_extension, $allowed_extensions ) ){
            $email_attachments = [];
        }

        self::send_better_email($better_cus_email, $better_payment_email_subject_customer, $better_payment_email_content_customer, $better_payment_email_headers_customer, $email_attachments); //customer mail
        self::send_better_email($better_payment_email_to, $better_payment_email_subject, $better_payment_email_content, $better_payment_email_headers); //admin mail
    }

    /**
     * Sends an HTML email.
     *
     * @since  0.0.2
     *
     * @param string $to
     * @param string $subject
     * @param array  $message
     *
     * @return bool false indicates mail sending failed while true doesn't gurantee that mail sent.
     * true just indicate script processed successfully without any errors. 
     * Ref: https://developer.wordpress.org/reference/functions/wp_mail/
     */
    public static function send_better_email( $to, $subject, $message, $headers=[], $attachments = [] ) {
        $response = false;

        $mailValidated = filter_var($to, FILTER_VALIDATE_EMAIL);

        if ($mailValidated) {
            $response = wp_mail( $to, $subject, $message, $headers, $attachments );
        }

        return $response;
    }

    /**
     * Email body
     *
     * 
     * @return string
     * @since  0.0.1
     */
    public static function better_render_email_body( $email_body, $form_fields_info, $line_break, $type = 'admin', $transaction_obj = null, $args = [] ) {
        $transaction_id = 
        $currency =
        $payment_date =
        $customer_name =
        $customer_first_name = 
        $customer_last_name = 
        $amount = '';
        $helper_obj = new Helper();
        
        if(null !== $transaction_obj && is_object($transaction_obj)){
           $transaction_id = $transaction_obj->transaction_id;
           $amount = $transaction_obj->amount;
           $status = $transaction_obj->status;
           $source = $transaction_obj->source;
           $currency = $transaction_obj->currency;
           $currency_symbol    = $helper_obj->get_currency_symbol( esc_html($currency) );
           $payment_date = wp_date(get_option('date_format').' '.get_option('time_format'), strtotime($transaction_obj->payment_date));
        }

        $is_empty_email_body = empty($email_body);

        $email_body = do_shortcode( $email_body );
        $bp_form_fields_html_content = $email_body;
        
        $bp_all_fields_shortcode = 'bp-all-fields';
        $referer_content_page_link = '#';
		
        $field_text = __('Field', 'better-payment');
        $value_text = __('Entry', 'better-payment');

        $form_fields_info_arr = maybe_unserialize($form_fields_info);

        $email_logo_url = ! empty( $args['email_logo_url'] ) ? $args['email_logo_url'] : '';
        $email_content_greeting = ! empty( $args['email_content_greeting'] ) ? 1 : 0;
        $email_form_name = ! empty( $args['email_form_name'] ) ? $args['email_form_name'] : 'N/A';

        $bp_form_fields_html_header = '<table style="margin-bottom: 20px; font-size: 14px; border-collapse: collapse; width: 100%;">';

        $bp_form_fields_html_footer = "</table>";

        $content = ''. $line_break;
        
        foreach ( $form_fields_info_arr as $key => $field ) {
            if ( $key === 'is_payment_split_payment' ) {
                $is_payment_split_payment = 1;
            }
            //Hide few fields
            if(
                $key === 'referer_page_id' || $key === 'referer_widget_id' || $key === 'source' || $key === 'el_form_fields'
                || $key === 'is_woo_layout'
                || $key === 'is_payment_split_payment'
                || $key === 'split_payment_total_amount'
                || $key === 'split_payment_total_amount_price_id'
                || $key === 'split_payment_installment_price_id'
            ) {
                if($key === 'referer_page_id'){
                    $referer_content_page_link = !empty($field) ? get_permalink( $field ) : $referer_content_page_link;
                }

                continue;
            }

            if($key === 'primary_first_name'){
                $key = 'first_name';
                $customer_first_name = $field;
            }
            if($key === 'primary_last_name'){
                $key = 'last_name';
                $customer_last_name = $field;
            }

            if($key === 'primary_email'){
                $key = 'email';
            }

            if($key === 'primary_payment_amount'){
                $key = 'payment_amount';
            }
            
            $key_formatted = self::better_title_case($key);
            if($key_formatted === 'Amount'){
                $key_formatted = __('Paid', 'better-payment');

                if ( ! empty( $is_payment_split_payment ) ) {
                    $key_formatted = __('Product Price: ', 'better-payment');
                }
            }

            $content .= "<tr>
                            <td style='padding: 7px; border: 1px solid #666;'> <strong>$key_formatted</strong> </td>
                            <td style='padding: 7px; border: 1px solid #666;'> $field </td>
                        </tr>";
        }

        if($customer_first_name) {
            $customer_name = $customer_first_name . ' ';
        }
        if($customer_last_name) {
            $customer_name .= $customer_last_name . ' ';
        }

        $allowed_sources = ['paypal', 'stripe', 'paystack'];
        $source_image_url = BETTER_PAYMENT_ASSETS . '/img/stripe.png';
        $source_image_alt = 'Stripe';
        
        if( in_array( strtolower( $transaction_obj->source ), $allowed_sources ) ){
            $source_image_url = strtolower( $transaction_obj->source ) == 'paypal' ? BETTER_PAYMENT_ASSETS . '/img/paypal.png' : BETTER_PAYMENT_ASSETS . "/img/{$transaction_obj->source}.png";
            $source_image_alt = strtolower( $transaction_obj->source ) == 'paypal' ? 'PayPal' : ucfirst( strtolower( $transaction_obj->source ) );
        }
        
        $amount_quantity = ! empty( $form_fields_info_arr['amount_quantity'] ) ? intval( $form_fields_info_arr['amount_quantity'] ) : 1;
        // #ToDo Product id or ids with comma
        $woo_product_id = ! empty( $form_fields_info_arr['woo_product_id'] ) ? intval( $form_fields_info_arr['woo_product_id'] ) : 0;
        $fluentcart_product_id = ! empty( $form_fields_info_arr['fluentcart_product_id'] ) ? intval( $form_fields_info_arr['fluentcart_product_id'] ) : 0;
        $woo_product_ids = ! empty( $form_fields_info_arr['woo_product_ids'] ) ? maybe_unserialize( $form_fields_info_arr['woo_product_ids'] ) : [0];
        $fluentcart_product_ids = ! empty( $form_fields_info_arr['fluentcart_product_ids'] ) ? maybe_unserialize( $form_fields_info_arr['fluentcart_product_ids'] ) : [0];
        $product_name = '';
        $product_permalink = '';
        $product_image_src = '';

        // Handle WooCommerce products
        if (function_exists('wc_get_product') && $woo_product_id ) {
            $bp_woocommerce_product = wc_get_product($woo_product_id);
            $product = $bp_woocommerce_product;

            if ($product) {
                $product_name = $product->get_name();
                $product_permalink = get_permalink($product->get_id());
                $product_price = $product->get_price();
                $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' );
                $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
            }
        }

        if (function_exists('fluentCart') && $fluentcart_product_id ) {
            try {
                $fluentcart_product = \FluentCart\App\Models\Product::query()
                    ->with(['detail', 'variants'])
                    ->find($fluentcart_product_id);

                if ($fluentcart_product) {
                    $product_name = $fluentcart_product->post_title;
                    $product_permalink = get_permalink($fluentcart_product->ID);

                    // Get price from the first variant or detail
                    $product_price = 0;
                    if (!empty($fluentcart_product->variants) && count($fluentcart_product->variants) > 0) {
                        $product_price = $fluentcart_product->variants[0]->item_price;
                    } elseif (!empty($fluentcart_product->detail)) {
                        $product_price = $fluentcart_product->detail->min_price;
                    }

                    $product_image_src_array = wp_get_attachment_image_src( get_post_thumbnail_id( $fluentcart_product->ID ), 'single-post-thumbnail' );
                    $product_image_src = is_array( $product_image_src_array ) && count( $product_image_src_array ) ? $product_image_src_array[0] : '';
                }
            } catch ( \Exception $e ) {
                // Handle error silently
            }
        }

        $detailed_product_info = isset( $form_fields_info_arr['detailed_product_info'] ) ? maybe_unserialize( $form_fields_info_arr['detailed_product_info'] ) : [];

        $all_data = [
            'amount' => $amount,
            'amount_quantity' => $amount_quantity,
            'woo_product_id' => $woo_product_id,
            'woo_product_ids' => $woo_product_ids,
            'fluentcart_product_id' => $fluentcart_product_id,
            'fluentcart_product_ids' => $fluentcart_product_ids,
            'product_name' => $product_name,
            'product_permalink' => $product_permalink,
            'product_image_src' => $product_image_src,
            'detailed_product_info' => $detailed_product_info,
            'amount_single' => $amount_quantity > 0 ? floatval( $amount / $amount_quantity ) : $amount,
            'currency' => ! empty( $currency ) ? $currency : '',
            'currency_symbol' => ! empty( $currency_symbol ) ? $currency_symbol : '',
            'payment_date_time' => ! empty( $payment_date ) ? $payment_date : '',
            'transaction_id' => ! empty( $transaction_id ) ? $transaction_id : '',
            'payment_date_only' => wp_date(get_option('date_format'), strtotime($transaction_obj->payment_date)),
            'status' => ucfirst( $status ),
            'form_fields_info_arr' => $form_fields_info_arr,
            // 'admin_name' => $admin_name,
            'customer_name' => $customer_name,
            'site_title' => get_bloginfo( 'name' ),
            'site_admin_email' => get_bloginfo( 'admin_email' ),
            'form_name' => $email_form_name,
            'payment_method' => ucfirst( $source ),
            'email_logo_url' => $email_logo_url,
            'source_image_url' => $source_image_url,
            'source_image_alt' => $source_image_alt,
            'view_transaction_link' => admin_url("admin.php?page=better-payment-transactions&action=view&id={$transaction_obj->id}"),
            'email_content_heading_show' => intval( $args['email_content_heading_show'] ),
            'email_content_from_section_show' => intval( $args['email_content_from_section_show'] ),
            'email_content_to_section_show' => intval( $args['email_content_to_section_show'] ),
            'email_content_transaction_summary_show' => intval( $args['email_content_transaction_summary_show'] ),
            'email_content_footer_text_show' => intval( $args['email_content_footer_text_show'] ),
            'email_content_customer' => wp_kses_post( $args['email_content_customer'] ),
            'email_content_admin' => wp_kses_post( $args['email_content_admin'] ),
            'is_elementor_form' => intval( $args['is_elementor_form'] ),
            'email_content_type' => $args['email_content_type'] ?? 'html',
            'email_content_type_customer' => $args['email_content_type_customer'] ?? 'html',
        ];

        $bp_form_fields_html_body = $content;
        $bp_form_fields_html_content = $bp_form_fields_html_header . $bp_form_fields_html_body . $bp_form_fields_html_footer ;

        //Replace shortcode with form fields info
        $email_body = str_replace( "[$bp_all_fields_shortcode]", $bp_form_fields_html_content, $email_body );

        // Email V2
        // General content
        $form_name = 

        ob_start();

        include BETTER_PAYMENT_ADMIN_VIEWS_PATH . "/template-email-notification.php";
        
        $email_body = ob_get_contents();
        ob_end_clean();

		return $email_body;
	}

    /**
     * String helper method
     * helps to convert string to title case
     * 
     * @return string
     * @since  0.0.1
     */
    public static function better_title_case($string){
        $string = str_replace('_',' ', $string);
        $string = ucwords($string);
        return $string;
    }
}
