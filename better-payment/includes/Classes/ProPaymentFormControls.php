<?php

namespace Better_Payment\Lite\Classes;

use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Transitional copy for Better Payment Pro <= 1.4.3; delete when ProCompatibility::MIN_PRO_VERSION >= 1.4.4.
 * Pro copy: better-payment-pro/includes/Admin/Elementor/PaymentFormControls.php. Slug: elementor_payment_form_pro_controls.
 *
 * The Payment Form widget controls only Pro layouts 4, 5 and 6 use, layout 6's
 * "WooCommerce / FluentCart is not installed" notices, and the payment-type
 * condition of the widget's PayPal, Paystack and amount-list controls.
 *
 * `Admin\Elementor\Better_Payment_Widget` calls each method only while
 * lite_copy_applies() is true: an active Pro that does not declare the slug. A free
 * install never registers these controls (a Pro layout shows only its upgrade
 * banner there), and a current Pro registers its own copy on the
 * `better_payment/elementor/editor/pro_layout_*` actions fired at the same spots.
 *
 * Control ids must never change: saved Elementor data, PaymentRequestGuard and the
 * payment handlers read them. Elementor keeps a saved value whose control is not
 * registered, so a site that loses Pro keeps its configuration. Pro's
 * PaymentFormControlsParityTest fails when the two copies drift.
 *
 * @since 2.4.0
 */
final class ProPaymentFormControls {

    /**
     * Feature slug Pro declares once it registers these controls itself.
     */
    const FEATURE = 'elementor_payment_form_pro_controls';

    /**
     * Whether Lite registers its copy of the controls.
     *
     * @return bool
     */
    public static function lite_copy_applies(): bool {
        return ProCompatibility::lite_copy_applies( self::FEATURE );
    }

    /**
     * Payment Settings: payment type, recurring / split / mixed prices and the Stripe webhook (layouts 4 and 5).
     *
     * @param \Elementor\Widget_Base $widget The Payment Form widget.
     */
    public static function payment_controls( $widget ) {
        $widget->add_control(
            'better_payment_form_payment_type',
            [
                'label'      => esc_html__( 'Payment Type', 'better-payment' ),
                'description' => esc_html__( 'Recurring and Split Payment is available for Stripe only at the moment!', 'better-payment' ),
                'type'       => Controls_Manager::SELECT,
                'default'    => 'one-time',
                'options'    => [
                    'one-time' => 'One Time',
                    'recurring' => 'Recurring',
                    'mixed'     => 'One Time + Recurring (Both)',
                    'split-payment' => 'Split Payment',
                ],
                'condition'   => [
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
            ]
        );

        $widget->add_control(
            'better_payment_form_recurring_price_id',
            [
                'label'       => esc_html__( 'Default Price ID', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'description' => sprintf( 
                    __( '<p>Create a product from Stripe dashboard and <a href="%1$s" target="_blank">get the (default) price id.</a></p>', 'better-payment' ), 
                    esc_url('//betterpayment.co/docs/retrieve-price-id-for-recurring-payments/'), 
                ),
                'placeholder' => 'price_G0FvDp6vZvdwRZ',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition'   => [
                    'better_payment_form_payment_type' => [ 'recurring', 'split-payment' ],
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
                'separator' => 'before',
            ]
        );

        $repeater_split_payment = new Repeater();

        $repeater_split_payment->add_control(
            'better_payment_split_installment_name',
            [
                'label' => esc_html__( 'Installment Name', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Installment Name',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $repeater_split_payment->add_control(
            'better_payment_split_installment_price_id',
            [
                'label' => esc_html__( 'Price ID', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'price_G0FvDp6vZvdwRZ',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $repeater_split_payment->add_control(
            'better_payment_split_installment_iteration',
            [
                'label' => esc_html__( 'Iterations', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'max'   => 36,
            ]
        );
        
        $widget->add_control(
            'better_payment_split_installment_price_ids_note',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'raw' => sprintf( 
                    __( '<p>Now add more prices to the product from Stripe dashboard and <a href="%1$s" target="_blank">get the price id for each installment.</a></p>', 'better-payment' ), 
                    esc_url('//betterpayment.co/docs/configure-split-payment-into-subscriptions/'), 
                ),
                'content_classes' => 'elementor-control-alert elementor-panel-alert elementor-panel-alert-info',
                'ai' => [
                    'active' => false,
                ],
                'condition'   => [
                    'better_payment_form_payment_type' => 'split-payment',
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
            ]
        );

        $widget->add_control(
            'better_payment_split_installment_price_ids',
            [
                'label'       => esc_html__( 'Installments', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'separator'   => 'after',
                'default'     => [
                    [
                        'better_payment_split_installment_name' => '3 Months',
                        'better_payment_split_installment_price_id' => '',
                        'better_payment_split_installment_iteration' => 3,
                    ],
                    [
                        'better_payment_split_installment_name' => '6 Months',
                        'better_payment_split_installment_price_id' => '',
                        'better_payment_split_installment_iteration' => 6,
                    ],
                    [
                        'better_payment_split_installment_name' => '9 Months',
                        'better_payment_split_installment_price_id' => '',
                        'better_payment_split_installment_iteration' => 9,
                    ],
                    [
                        'better_payment_split_installment_name' => '12 Months',
                        'better_payment_split_installment_price_id' => '',
                        'better_payment_split_installment_iteration' => 12,
                    ],
                ],
                'fields'      => $repeater_split_payment->get_controls(),
                'title_field' => '<i class="{{ better_payment_split_installment_name }}" aria-hidden="true"></i> {{{ better_payment_split_installment_name }}}',
                'condition'   => [
                    'better_payment_form_payment_type' => 'split-payment',
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
            ]
        );

        $repeater_recurring_payment = new Repeater();
 
        $repeater_recurring_payment->add_control(
            'better_payment_recurring_name',
            [
                'label' => esc_html__( 'Interval Name', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Interval Name',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
         
        $repeater_recurring_payment->add_control(
            'better_payment_recurring_price_id',
            [
                'label' => esc_html__( 'Price ID', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'price_G0FvDp6vZvdwRZ',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $widget->add_control(
            'better_payment_recurring_price_ids',
            [
                'label'       => esc_html__( 'Intervals', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'separator'   => 'after',
                'default'     => [
                    [
                        'better_payment_recurring_name' => 'Monthly',
                        'better_payment_recurring_price_id' => '',
                    ],
                    [
                        'better_payment_recurring_name' => 'Yearly',
                        'better_payment_recurring_price_id' => '',
                    ],
                ],
                'fields'      => $repeater_recurring_payment->get_controls(),
                'title_field' => '<i class="{{ better_payment_recurring_name }}" aria-hidden="true"></i> {{{ better_payment_recurring_name }}}',
                'condition'   => [
                    'better_payment_form_payment_type' => 'recurring',
                    'better_payment_form_layout' => 'layout-5-pro',
                ],
            ]
        );

        $widget->add_control(
            'better_payment_one_time_recurring_product_id',
            [
                'label' => esc_html__( 'Product ID', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'prod_G0FvDp6vZvdwRZ',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition'  => [
                    'better_payment_form_payment_type' => 'mixed',
                    'better_payment_form_layout' => ['layout-4-pro', 'layout-5-pro'],
                ]
            ]
        );
        
        $repeater_mixed_payment = new Repeater();

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_interval_type',
            [
                'label'   => esc_html__( 'Interval Type', 'better-payment' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'day',
                'options' => [
                    'day'   => 'Day',
                    'week'  => 'Week',
                    'month' => 'Month',
                    'year'  => 'Year',
                ],
            ]
        );
        
        $repeater_mixed_payment->add_control(
            'better_payment_mixed_name',
            [
                'label' => esc_html__( 'Interval Name', 'better-payment' ),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Interval Name',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_interval_day',
            [
                'label' => esc_html__( 'Interval', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'max'   => 1095,
                'condition'   => [
                    'better_payment_mixed_interval_type' => ['day'],
                ],
            ]
        );

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_interval_month',
            [
                'label' => esc_html__( 'Interval', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'max'   => 36,
                'condition'   => [
                    'better_payment_mixed_interval_type' => ['month'],
                ],
            ]
        );

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_interval_week',
            [
                'label' => esc_html__( 'Interval', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'max'   => 156,
                'condition'   => [
                    'better_payment_mixed_interval_type' => ['week'],
                ],
            ]
        );

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_interval_year',
            [
                'label' => esc_html__( 'Interval', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'max'   => 3,
                'condition'   => [
                    'better_payment_mixed_interval_type' => ['year'],
                ],
            ]
        );

        $repeater_mixed_payment->add_control(
            'better_payment_mixed_installment_options_note1',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'raw' => sprintf( 
                    __( '<p>Maximum interval: 3 years or equivalent.</p>', 'better-payment' ),  
                ),
                'content_classes' => 'elementor-control-alert elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $widget->add_control(
            'better_payment_mixed_installment_options',
            [
                'label'       => esc_html__( 'Mixed Intervals', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'separator'   => 'after',
                'default'     => [
                    [
                        'better_payment_mixed_name' => 'Monthly',
                        'better_payment_mixed_interval_month' => 1,
                        'better_payment_mixed_interval_type' => 'month',
                    ],
                    [
                        'better_payment_mixed_name' => 'Daily',
                        'better_payment_mixed_interval_day' => 1,
                        'better_payment_mixed_interval_type' => 'day',
                    ],
                    [
                        'better_payment_mixed_name' => 'Weekly',
                        'better_payment_mixed_interval_week' => 1,
                        'better_payment_mixed_interval_type' => 'week',
                    ],
                    [
                        'better_payment_mixed_name' => 'Yearly',
                        'better_payment_mixed_interval_year' => 1,
                        'better_payment_mixed_interval_type' => 'year',
                    ],
                    [
                        'better_payment_mixed_name' => 'Every 3 Months',
                        'better_payment_mixed_interval_month' => 3,
                        'better_payment_mixed_interval_type' => 'month',
                    ],
                    [
                        'better_payment_mixed_name' => 'Every 6 Months',
                        'better_payment_mixed_interval_month' => 6,
                        'better_payment_mixed_interval_type' => 'month',
                    ],
                ],
                'fields'      => $repeater_mixed_payment->get_controls(),
                'title_field' => '<i class="{{ better_payment_mixed_name }}" aria-hidden="true"></i> {{{ better_payment_mixed_name }}}',
                'condition'   => [
                    'better_payment_form_payment_type' => 'mixed',
                    'better_payment_form_layout' => ['layout-4-pro', 'layout-5-pro'],
                ],
            ]
        );

        $widget->add_control(
            'better_payment_form_recurring_webhook_stripe_secret',
            [
                'label'       => esc_html__( 'Webhook Secret', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'description' => sprintf( 
                    __( '<p>Create a webhook endpoint from Stripe dashboard and <a href="%1$s" target="_blank">get the webhook secret.</a></p>', 'better-payment' ), 
                    esc_url('//betterpayment.co/docs/add-webhook-endpoint-recurring-better-payment/'), 
                ),
                'placeholder' => 'whsec_...',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition'   => [
                    'better_payment_form_payment_type' => [ 'recurring', 'split-payment' ],
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
            ]
        );

        $widget->add_control(
            'better_payment_form_recurring_webhook_stripe',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'raw' => sprintf( 
                    __( '<p><a href="%1$s" target="_blank">Your webhook endpoint url »</a><br>%2$s</p>', 'better-payment' ), 
                    esc_url('//wpdeveloper.com/docs-category/better-payment/'), 
                    esc_url_raw( get_permalink( get_the_ID() ) . '?webhook-stripe=1' ) 
                ),
                'content_classes' => 'elementor-control-alert elementor-panel-alert elementor-panel-alert-info',
                'ai' => [
                    'active' => false,
                ],
                'condition'   => [
                    'better_payment_form_payment_type' => [ 'recurring', 'split-payment' ],
                    'better_payment_form_layout!' => ['layout-1', 'layout-2', 'layout-3', 'layout-6-pro'],
                ],
            ]
        );
    }

    /**
     * Payment Settings: layout 6's eCommerce platform and product pickers.
     *
     * @param \Elementor\Widget_Base $widget The Payment Form widget.
     */
    public static function product_controls( $widget ) {
        $widget->add_control( 'better_payment_form_layout_6_woocommerce_notice', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => sprintf( __( '<a href="%1$s" target="_blank"><strong>WooCommerce</strong></a> is not installed/activated on your site. Please install and activate <a href="%1$s" target="_blank"><strong>WooCommerce</strong></a> first.', 'better-payment' ), esc_url('plugin-install.php?s=woocommerce&tab=search&type=term') ),
            'content_classes' => 'better-payment-warning',
            'conditions' => [
                'relation' => 'and',
                'terms' => [
                    [
                        'name' => 'better_payment_form_layout',
                        'value' => 'layout-6-pro' . ( ! class_exists('woocommerce') ? '' : 'exist' ),
                    ],
                    [
                        'relation' => 'or',
                        'terms' => [
                            [
                                'name' => 'better_payment_form_payment_source',
                                'value' => 'woocommerce',
                            ],
                            [
                                'name' => 'better_payment_form_layout_6_ecommerce_platform',
                                'value' => '',
                            ],
                            [
                                'name' => 'better_payment_form_layout_6_ecommerce_platform',
                                'value' => 'woocommerce',
                            ],
                        ],
                    ],
                ],
            ],
        ] );

        $widget->add_control(
            'better_payment_form_layout_6_ecommerce_platform',
            [
                'label'      => esc_html__( 'eCommerce Platform', 'better-payment' ),
                'type'       => Controls_Manager::SELECT,
                'default'    => 'woocommerce',
                'options'    => [
                    'woocommerce' => 'WooCommerce',
                    'fluentcart' => 'FluentCart',
                ],
                'condition' => [
                    'better_payment_form_layout' => ['layout-6-pro'],
                ],
            ]
        );

        $widget->add_control('better_payment_form_woocommerce_product_ids', [
            'label' => esc_html__('Select Products', 'better-payment'),
            'type'        => 'better-payment-select2',
            'label_block' => true,
            'multiple' => true,
            'source_name' => 'post_type',
            'source_type' => 'product',
            'placeholder' => __( ' ', 'better-payment' ),
            'condition' => [
                'better_payment_form_layout' => ['layout-6-pro'],
                'better_payment_form_layout_6_ecommerce_platform' => 'woocommerce',
            ],
        ]);

        $widget->add_control('better_payment_form_fluentcart_product_ids', [
            'label' => esc_html__('Select Products', 'better-payment'),
            'type'        => 'better-payment-select2',
            'label_block' => true,
            'multiple' => true,
            'source_name' => 'post_type',
            'source_type' => 'fluent-products',
            'placeholder' => __( ' ', 'better-payment' ),
            'condition' => [
                'better_payment_form_layout' => ['layout-6-pro'],
                'better_payment_form_layout_6_ecommerce_platform' => 'fluentcart',
            ],
        ]);

        $widget->add_control( 'better_payment_form_layout_6_fluentcart_notice', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => sprintf( __( '<a href="%1$s" target="_blank"><strong>FluentCart</strong></a> is not installed/activated on your site. Please install and activate <a href="%1$s" target="_blank"><strong>FluentCart</strong></a> first.', 'better-payment' ), esc_url('plugin-install.php?s=fluentcart&tab=search&type=term') ),
            'content_classes' => 'better-payment-warning',
            'conditions' => [
                'relation' => 'and',
                'terms' => [
                    [
                        'name' => 'better_payment_form_layout',
                        'value' => 'layout-6-pro',
                    ],
                    [
                        'name' => 'better_payment_form_layout_6_ecommerce_platform',
                        'value' => 'fluentcart' . ( ! function_exists('fluentCart') ? '' : 'exist' ),
                    ],
                ],
            ],
        ] );
    }

    /**
     * Form Settings: the form-field repeaters of layouts 4, 5 and 6.
     *
     * @param \Elementor\Widget_Base $widget               The Payment Form widget.
     * @param \Elementor\Repeater    $form_fields_repeater The shared form-field repeater.
     */
    public static function form_fields_controls( $widget, $form_fields_repeater ) {
        $form_fields_first_name_layout_4_5_6 = [
            'better_payment_field_name_heading' => esc_html__( 'First name', 'better-payment' ),
            'better_payment_field_name_placeholder' => esc_html__( 'First name', 'better-payment' ),
            'better_payment_field_type' => 'text',
            'better_payment_primary_field_type' => 'primary_first_name',
            'better_payment_field_name_show' => 'yes',
            'better_payment_field_name_required' => 'no',
            'better_payment_field_name_display_inline' => 'inline-block',
            'better_payment_field_name_display_inline_width' => [
                'size' => '48',
                'unit' => '%',
            ],
        ];

        $form_fields_last_name_layout_4_5_6 = [
            'better_payment_field_name_heading' => esc_html__( 'Last name', 'better-payment' ),
            'better_payment_field_name_placeholder' => esc_html__( 'Last name', 'better-payment' ),
            'better_payment_field_type' => 'text',
            'better_payment_primary_field_type' => 'primary_last_name',
            'better_payment_field_name_show' => 'yes',
            'better_payment_field_name_required' => 'no',
            'better_payment_field_name_display_inline' => 'inline-block',
            'better_payment_field_name_display_inline_width' => [
                'size' => '48',
                'unit' => '%',
            ],
        ];

        $form_field_email_layout_4_5_6 = [
            'better_payment_field_name_heading' => esc_html__( 'Email', 'better-payment' ),
            'better_payment_field_name_placeholder' => esc_html__( 'Enter your email', 'better-payment' ),
            'better_payment_field_type' => 'email',
            'better_payment_primary_field_type' => 'primary_email',
            'better_payment_field_name_show' => 'yes',
            'better_payment_field_name_required' => 'yes',
        ];

        $form_field_payment_amount_layout_4_5_6 = [
            'better_payment_field_name_heading' => esc_html__( 'Amount', 'better-payment' ),
            'better_payment_field_name_placeholder' => esc_html__( 'Other amount', 'better-payment' ),
            'better_payment_field_type' => 'number',
            'better_payment_primary_field_type' => 'primary_payment_amount',
            'better_payment_field_name_show' => 'yes',
            'better_payment_field_name_required' => 'yes',
        ];

        $form_field_payment_amount_layout_4_5_6_desc = [
            'better_payment_field_name_heading' => esc_html__( 'Amount to pay', 'better-payment' ),
            'better_payment_field_name_placeholder' => esc_html__( 'Other amount', 'better-payment' ),
            'better_payment_field_type' => 'number',
            'better_payment_primary_field_type' => 'primary_payment_amount',
            'better_payment_field_name_show' => 'yes',
            'better_payment_field_name_required' => 'yes',
        ];

        $form_fields_defaults_layout_4_5_6 = [
            $form_fields_first_name_layout_4_5_6,
            $form_fields_last_name_layout_4_5_6,
            $form_field_email_layout_4_5_6,
            $form_field_payment_amount_layout_4_5_6,
        ];

        $form_fields_defaults_layout_4_5_6_desc = [
            $form_field_payment_amount_layout_4_5_6_desc,
            $form_fields_first_name_layout_4_5_6,
            $form_fields_last_name_layout_4_5_6,
            $form_field_email_layout_4_5_6,
        ];

        $form_fields_defaults_layout_4_5_6_woo = [
            $form_fields_first_name_layout_4_5_6,
            $form_fields_last_name_layout_4_5_6,
            $form_field_email_layout_4_5_6,
        ];

        $widget->add_control(
			'better_payment_form_fields_layout_4_5_6',
			[
				'label' => esc_html__( 'Form Fields', 'better-payment' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $form_fields_repeater->get_controls(),
				'default' => $form_fields_defaults_layout_4_5_6,
				'title_field' => '{{{ better_payment_field_name_heading }}}',
                'condition' => [
                    'better_payment_form_layout' => ['layout-4-pro'],
                ],
			]
		);
        
        $widget->add_control(
			'better_payment_form_fields_layout_4_5_6_desc',
			[
				'label' => esc_html__( 'Form Fields', 'better-payment' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $form_fields_repeater->get_controls(),
				'default' => $form_fields_defaults_layout_4_5_6_desc,
				'title_field' => '{{{ better_payment_field_name_heading }}}',
                'condition' => [
                    'better_payment_form_layout' => ['layout-5-pro'],
                ],
			]
		);
        
        $widget->add_control(
			'better_payment_form_fields_layout_4_5_6_woo',
			[
				'label' => esc_html__( 'Form Fields', 'better-payment' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $form_fields_repeater->get_controls(),
				'default' => $form_fields_defaults_layout_4_5_6_woo,
				'title_field' => '{{{ better_payment_field_name_heading }}}',
                'condition' => [
                    'better_payment_form_layout' => ['layout-6-pro'],
                ],
			]
		);
    }

    /**
     * Amount list: the "Show Amount List" switcher of layouts 4, 5 and 6.
     *
     * @param \Elementor\Widget_Base $widget The Payment Form widget.
     */
    public static function amount_list_toggle_control( $widget ) {
        $widget->add_control(
            'better_payment_show_amount_list_layout_4_5_6',
            [
                'label'        => __( 'Show Amount List', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'better_payment_form_layout' => ['layout-4-pro', 'layout-5-pro', 'layout-6-pro'],
                    'better_payment_form_payment_type!' => [ 'recurring', 'split-payment' ],
                ],
            ]
        );
    }

    /**
     * Amount list: the amounts of layouts 4, 5 and 6.
     *
     * @param \Elementor\Widget_Base $widget   The Payment Form widget.
     * @param \Elementor\Repeater    $repeater The amount repeater.
     */
    public static function amount_list_control( $widget, $repeater ) {
        $widget->add_control(
            'better_payment_amount_layout_4_5_6',
            [
                'label'       => esc_html__( 'Amount List', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'default'     => [
                    [
                        'better_payment_amount_val' => 10
                    ],
                    [
                        'better_payment_amount_val' => 30
                    ],
                    [
                        'better_payment_amount_val' => 50
                    ],
                    [
                        'better_payment_amount_val' => 100
                    ],
                    [
                        'better_payment_amount_val' => 500
                    ],
                ],
                'fields'      => $repeater->get_controls(),
                'title_field' => '<i class="{{ better_payment_amount_val }}" aria-hidden="true"></i> {{{ better_payment_amount_val }}}',
                'condition'   => [
                    'better_payment_show_amount_list_layout_4_5_6' => 'yes',
                    'better_payment_form_payment_type!' => [ 'recurring', 'split-payment' ],
                    'better_payment_form_layout' => ['layout-4-pro', 'layout-5-pro', 'layout-6-pro'],
                ],
            ]
        );
    }

    /**
     * Form Custom Text: the form title and sub-title of layouts 4, 5 and 6.
     *
     * @param \Elementor\Widget_Base $widget The Payment Form widget.
     */
    public static function form_title_controls( $widget ) {
        $widget->add_control(
            'better_payment_form_form_title_text',
            [
                'label'       => esc_html__( 'Form Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_form_layout' => ['layout-4-pro', 'layout-5-pro', 'layout-6-pro'],
                ],
            ]
        );

        $widget->add_control(
            'better_payment_form_form_sub_title_text',
            [
                'label'       => esc_html__( 'Form Sub-Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_form_layout' => ['layout-5-pro'],
                ],
            ]
        );
    }

    /**
     * The payment-type condition Pro adds to three of Lite's own controls.
     *
     * PayPal and Paystack cannot take a recurring or split payment, and the amount
     * list does not apply to one, so those controls hide once either is chosen.
     * Lite asks for it on `better_payment/elementor/editor/pro_layout_control_condition`
     * rather than hardcoding it: the condition names `better_payment_form_payment_type`,
     * which only exists while Pro's controls are registered, and Elementor hides —
     * and nulls when rendering — a control whose condition names a missing control.
     *
     * @param mixed  $condition  The control's condition.
     * @param string $control_id The control id.
     * @return array
     */
    public static function control_condition( $condition, $control_id = '' ) {
        $condition = is_array( $condition ) ? $condition : [];

        if ( in_array( $control_id, [ 'better_payment_form_paypal_enable', 'better_payment_form_paystack_enable', 'better_payment_amount' ], true ) ) {
            $condition['better_payment_form_payment_type!'] = [ 'recurring', 'split-payment' ];
        }

        return $condition;
    }
}
