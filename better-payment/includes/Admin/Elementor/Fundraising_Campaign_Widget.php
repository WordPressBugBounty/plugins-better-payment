<?php

namespace Better_Payment\Lite\Admin\Elementor;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Classes\Handler;
use Better_Payment\Lite\Classes\Helper as ClassesHelper;
use Better_Payment\Lite\Traits\Helper;
use \Elementor\Controls_Manager as Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use \Elementor\Repeater;
use Elementor\Utils;
use \Elementor\Widget_Base as Widget_Base;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The elementor widget class
 *
 * @since 0.0.1
 */
class Fundraising_Campaign_Widget extends Widget_Base {

    use Helper;

    private $better_payment_campaign_global_settings = [];

    /**
	 * @var mixed|void
	 */
	protected $pro_enabled;

    /**
	 * Login_Register constructor.
	 * Initializing the Login_Register widget class.
	 * @inheritDoc
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->pro_enabled       = apply_filters( 'better_payment/pro_enabled', false );
	}

    public function get_name() {
        return 'fundraising-campaign';
    }

    public function get_title() {
        return esc_html__( 'Fundraising Campaign', 'better-payment' );
    }

    public function get_icon() {
        return 'bp-icon bp-fundraising-campaign';
    }

    public function get_categories() {
        return ['better-payment'];
    }

    public function get_keywords() {
        return [
            'payment', 'better-payment' ,'paypal', 'stripe', 'sell', 'donate', 'transaction', 'online-transaction', 'paystack', 'fundraising', 'campaign', 'better payment'
        ];
    }

    public function get_custom_help_url() {
        return 'https://betterpayment.co/docs/configure-fundraising-campaign-in-better-payment';
    }

    public function get_style_depends() {
        return apply_filters( 'better_payment/elementor/editor/get_style_depends', [  'fundraising-campaign-style' ] );
    }

    public function get_script_depends() {
        return apply_filters( 'better_payment/elementor/editor/get_script_depends', [ 'fundraising-campaign-script' ] );
    }

    function generate_short_unique_id( int $length = 8 ) {
        $timestamp = microtime(true);
        $random = random_int(0, 999999);
        $base = base_convert((int)($timestamp * 1000000) + $random, 10, 36);

        return strtoupper( substr(str_pad($base, $length, '0', STR_PAD_LEFT), -$length) );
    }


    protected function register_controls() {
        $this->better_payment_campaign_global_settings = DB::get_settings();

        $is_edit_mode = Plugin::instance()->editor->is_edit_mode();

        if ( $is_edit_mode && ( ! current_user_can('manage_options') ) ) {
            $this->better_payment_campaign_global_settings = [];
        }

        $this->start_controls_section(
            'better_payment_campaign_settings_general',
            [
                'label' => esc_html__( 'General', 'better-payment' ),
            ]
        );

        $campaign_id = $this->generate_short_unique_id();

        $this->add_control(
            'better_payment_campaign_id',
            [
                'label'       => esc_html__( 'Campaign ID', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => $campaign_id,
                'default'     => $campaign_id,
                'description' => esc_html__('Unique campaign ID required. It is auto-generated; if modified, don\'t make it repetitive.', 'better-payment'),
                'label_block' => false,
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_layout',
            [
                'label'      => esc_html__( 'Campaign Layout', 'better-payment' ),
                'type'       => Controls_Manager::SELECT,
                'default'    => 'layout-1',
                'options'    => $this->better_payment_campaign_layouts(),
            ]
        );

        $better_payment_helper = new ClassesHelper();
        $better_payment_general_currency = $this->better_payment_campaign_global_settings['better_payment_settings_general_general_currency'];

        $this->add_control(
            'better_payment_campaign_currency',
            [
                'label'      => esc_html__( 'Currency', 'better-payment' ),
                'type'       => Controls_Manager::SELECT,
                'default'    => esc_html($better_payment_general_currency),
                'options'    => $better_payment_helper->get_currency_list(),
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_header_enable',
            [
                'label'        => __( 'Header', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'separator'    => 'before',
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_overview_enable',
            [
                'label'        => __( 'Overview', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        if ( !$this->pro_enabled ) {
			$this->add_control( 'better_payment_campaign_updates_pro', [
				'label'   => sprintf( __( 'Updates %s', 'better-payment' ), '<i class="bpc-pro-labe eicon-pro-icon"></i>' ),
				'type'    => Controls_Manager::SWITCHER,
				'classes' => 'bpc-pro-control',
			] );
		}

        do_action('better_payment/elementor/editor/campaign_pro_sections', $this);

        $this->add_control(
            'better_payment_campaign_general_footer_team_enable_layout_1',
            [
                'label'        => __( 'Our Team', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_footer_related_campaign_enable_layout_2',
            [
                'label'        => __( 'Related Campaigns', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->end_controls_section();

        $this->campaign_header_settings();
        $this->campaign_form_settings();
        $this->campaign_overview_settings();
        $this->campaign_our_team_settings();
        do_action('better_payment/elementor/editor/campaign_pro_updates_section', $this);
        $this->related_campaign_settings();

        $this->form_style();
    }

    public function campaign_header_settings() {
        $this->start_controls_section(
            'better_payment_campaign_header_title',
            [
                'label'      => esc_html__( 'Header', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_title_enable',
            [
                'label'        => __( 'Title', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_title_text',
            [
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'ai'     => [
                    'active' => false,
                ],
                'placeholder' => __( 'Campaign Title Content', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_title_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_short_description_enable',
            [
                'label'        => __( 'Subtitle', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_short_description',
            [
                'type'        => Controls_Manager::TEXTAREA,
                'label_block' => true,
                'ai'     => [
                    'active' => false,
                ],
                'placeholder' => __( 'Campaign Subtitle Description', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_short_description_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_images_heading',
            [
                'label'     => __( 'Images', 'better-payment' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->start_controls_tabs( 'better_payment_campaign_header_images_tabs' );

        $this->start_controls_tab(
            'better_payment_campaign_header_image_one_tab',
            [
                'label' => __( 'One', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', 'layout-3' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_image_one_enable',
            [
                'label'        => __( 'Show', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', 'layout-3' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_image_one',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', 'layout-3' ],
                    'better_payment_campaign_general_image_one_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_header_image_two_tab',
            [
                'label' => __( 'Two', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_image_two_enable',
            [
                'label'        => __( 'Show', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_image_two',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2' ],
                    'better_payment_campaign_general_image_two_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_header_image_three_tab',
            [
                'label' => __( 'Three', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_image_three_enable',
            [
                'label'        => __( 'Show', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'better_payment_campaign_general_header_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_image_three',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                    'better_payment_campaign_general_image_three_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    public function campaign_form_settings() {
        $this->start_controls_section(
            'better_payment_campaign_form_settings',
            [
                'label'     => esc_html__( 'Form', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_title_text_layout_1',
            [
                'label'       => __( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your form title',
                'default' => 'Charity Raised',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_title_text_layout_2',
            [
                'label'       => __( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your form title',
                'default' => 'Save Lives and Bring Hope to Children in Gaza',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_sub_title_text',
            [
                'label'       => __( 'Sub Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your form sub title',
                'default' => 'Urgent Cause',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_image',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'default' => [
                    'url' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/form.webp',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_amount_label',
            [
                'label'       => __( 'Goal Amount Label', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'placeholder' => 'Enter your goal amount label',
                'default' => 'Goal',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_amount',
            [
                'label'       => __( 'Goal Amount', 'better-payment' ),
                'type'        => Controls_Manager::NUMBER,
                'placeholder' => 'Enter your goal amount',
                'default' => 5000,
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_percentage_enable',
            [
                'label'        => __( 'Goal Percentage', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_bar_line_enable',
            [
                'label'        => __( 'Goal Bar Line', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2' ],
                ],
            ]
        );

        $amount_list_repeater_l1 = new Repeater();
        $amount_list_repeater_l1->add_control(
            'better_payment_campaign_form_amount_list_val_layout_1',
            [
                'label' => esc_html__( 'Amount', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'default' => 5,
                'placeholder' => 'Enter your amount',
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_layout_1',
            [
                'label'       => esc_html__( 'Amount List', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $amount_list_repeater_l1->get_controls(),
                'default'     => [
                    [
                        'better_payment_campaign_form_amount_list_val_layout_1' => 10
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_1' => 20
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_1' => 30
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_1' => 80
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_1' => 100
                    ],
                ],
                'title_field' => '<i class="{{ better_payment_campaign_form_amount_list_val_layout_1 }}" aria-hidden="true"></i> {{{ better_payment_campaign_form_amount_list_val_layout_1 }}}',
                'condition'   => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $amount_list_repeater_l3 = new Repeater();
        $amount_list_repeater_l3->add_control(
            'better_payment_campaign_form_amount_list_val_layout_3',
            [
                'label' => esc_html__( 'Amount', 'better-payment' ),
                'type'  => Controls_Manager::NUMBER,
                'min'   => 1,
                'default' => 5,
                'placeholder' => 'Enter your amount',
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_layout_3',
            [
                'label'       => esc_html__( 'Amount List', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $amount_list_repeater_l3->get_controls(),
                'default'     => [
                    [
                        'better_payment_campaign_form_amount_list_val_layout_3' => 10
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_3' => 20
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_3' => 40
                    ],
                    [
                        'better_payment_campaign_form_amount_list_val_layout_3' => 80
                    ],
                ],
                'title_field' => '<i class="{{ better_payment_campaign_form_amount_list_val_layout_3 }}" aria-hidden="true"></i> {{{ better_payment_campaign_form_amount_list_val_layout_3 }}}',
                'condition'   => [
                    'better_payment_campaign_layout' => [ 'layout-3' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_total_donation_enable',
            [
                'label'        => __( 'Total Donation', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_total_donation_label',
            [
                'label'       => __( 'Total Donation Label', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your total donation label',
                'default' => 'Donations',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_form_total_donation_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_amount_raised_enable',
            [
                'label'        => __( 'Raised Amount', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_goal_amount_raised_label',
            [
                'label'       => __( 'Raised Amount Label', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your raised amount label',
                'default' => 'Raised',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_form_goal_amount_raised_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_form_placeholder_text_layout_1',
            [
                'label'       => __( 'Custom Placeholder Text', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your placeholder text',
                'default' => 'Enter your amount',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_placeholder_text_layout_3',
            [
                'label'       => __( 'Custom Placeholder Text', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your placeholder text',
                'default' => '0',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_text',
            [
                'label'       => __( 'Button Text', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your button text',
                'default' => 'Donate Now',
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_link',
            [
                'label' => __( 'Button Link', 'better-payment' ),
                'type' => Controls_Manager::URL,
                'default' => [
                    'url' => '#',
                ],
                'dynamic'               => [
                    'active'       => true,
                ],
                'show_external' => false,
                'placeholder' => __( 'https://example.com/payment-form-page/', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_link_info',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'raw' => sprintf( 
                    __( '<p>*You must create a separate payment form page with Better Payment and add the URL. Follow <a href="%1$s" target="_blank">this doc.</a></p>', 'better-payment' ), 
                    esc_url('//betterpayment.co/docs/configure-form-settings-in-better-payment/'), 
                ),
                'content_classes' => 'elementor-control-alert elementor-panel-alert elementor-panel-alert-info',
                'ai' => [
                    'active' => false,
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function campaign_overview_settings() {
        $this->start_controls_section(
            'better_payment_campaign_overview_settings',
            [
                'label'     => esc_html__( 'Overview', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_overview_enable' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_title_text',
            [
                'label'       => __( 'Tab Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Enter your overview title',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_enable' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_description_title',
            [
                'label'       => esc_html__( 'Descriptions', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_overview_description_one_enable',
            [
                'label'        => __( 'Top Description', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'   => [
                    'better_payment_campaign_general_overview_enable' => 'yes'
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_description_one',
            [
                'label'       => __( 'Description Field', 'better-payment' ),
                'type'        => Controls_Manager::TEXTAREA,
                'label_block' => true,
                'placeholder' => 'Enter your campaign description',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                    'better_payment_campaign_general_overview_description_one_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_overview_description_two_enable',
            [
                'label'        => __( 'Bottom Description', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'   => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_description_two',
            [
                'label'       => __( 'Description Field', 'better-payment' ),
                'type'        => Controls_Manager::TEXTAREA,
                'label_block' => true,
                'placeholder' => 'Enter your campaign description',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                    'better_payment_campaign_general_overview_description_two_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ]
            ]
        );

        $this->add_control(
            'better_paymentcampaign_overview_images_heading',
            [
                'label'     => __( 'Images', 'better-payment' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_overview_images_enable',
            [
                'label'        => __( 'Show', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'   => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ]
            ]
        );

        $this->start_controls_tabs( 'better_payment_campaign_overview_images_tabs' );

        $this->start_controls_tab(
            'better_payment_campaign_overview_image_one_tab',
            [
                'label' => __( 'One', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_image_one',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_overview_image_two_tab',
            [
                'label' => __( 'Two', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_image_two',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_overview_image_three_tab',
            [
                'label' => __( 'Three', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_image_three',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_images_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'better_paymentcampaign_overview_our_mission_heading',
            [
                'label'     => __( 'Our Mission', 'better-payment' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'better_payment_campaign_general_overview_mossion_enable',
            [
                'label'        => __( 'Show', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'   => [
                    'better_payment_campaign_general_overview_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_title_text',
            [
                'label'       => __( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'Our Mission',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $our_mission_repeater = new Repeater();
        $our_mission_repeater->add_control(
            'better_payment_campaign_overview_our_mission_item',
            [
                'label' => esc_html__('Mission', 'better-payment'),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Mission item',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_items',
            [
                'label'       => esc_html__( 'Missions', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $our_mission_repeater->get_controls(),
                'default'     => [
                    [
                        'better_payment_campaign_overview_our_mission_item' => 'Provide nutritious meals to children in need',
                    ],
                    [
                        'better_payment_campaign_overview_our_mission_item' => 'Offer educational support and resources',
                    ],
                    [
                        'better_payment_campaign_overview_our_mission_item' => 'Create safe and nurturing environments',
                    ],
                    [
                        'better_payment_campaign_overview_our_mission_item' => 'Promote awareness and advocacy for child rights',
                    ],
                ],
                'title_field' => '{{{ better_payment_campaign_overview_our_mission_item }}}',
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_section();
    }

    public function campaign_our_team_settings() {
        $this->start_controls_section(
            'better_payment_campaign_our_team_settings',
            [
                'label'     => esc_html__( '⮑ Our Team', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_footer_our_team_title_text',
            [
                'label'       => __( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => 'Our Team',
                'placeholder' => 'Our Team',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );
        
        $team_member_repeater = new Repeater();
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_name',
            [
                'label' => esc_html__('Name', 'better-payment'),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Annette Black',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_image',
            [
                'label' => __('Choose Image', 'better-payment'),
                'type' => Controls_Manager::MEDIA,
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_designation',
            [
                'label' => esc_html__('Designation', 'better-payment'),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => 'Designation',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_enable',
            [
                'label'        => __( 'Social Links', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'better-payment' ),
                'label_off'    => __( 'Hide', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_facebook',
            [
                'label' => esc_html__('Facebook', 'better-payment'),
                'type'  => Controls_Manager::URL,
                'placeholder' => 'https://www.facebook.com/',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );

        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_facebook_icon',
            [
                'label' => esc_html__('Facebook Icon', 'better-payment'),
                'type'  => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fab fa-facebook-f',
                    'library' => 'fa-brands',
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_twitter',
            [
                'label' => esc_html__('X/Twitter', 'better-payment'),
                'type'  => Controls_Manager::URL,
                'placeholder' => 'https://www.twitter.com/ or https://www.x.com/',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );

        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_twitter_icon',
            [
                'label' => esc_html__('X/Twitter Icon', 'better-payment'),
                'type'  => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fab fa-x-twitter',
                    'library' => 'fa-brands',
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_linkedin',
            [
                'label' => esc_html__('LinkedIn', 'better-payment'),
                'type'  => Controls_Manager::URL,
                'placeholder' => 'https://www.linkedin.com/',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );

        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_linkedin_icon',
            [
                'label' => esc_html__('LinkedIn Icon', 'better-payment'),
                'type'  => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fab fa-linkedin-in',
                    'library' => 'fa-brands',
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );
        
        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_instagram',
            [
                'label' => esc_html__('Instagram', 'better-payment'),
                'type'  => Controls_Manager::URL,
                'placeholder' => 'https://www.instagram.com/',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );

        $team_member_repeater->add_control(
            'better_payment_campaign_team_member_social_links_instagram_icon',
            [
                'label' => esc_html__('Instagram Icon', 'better-payment'),
                'type'  => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fab fa-instagram',
                    'library' => 'fa-brands',
                ],
                'condition' => [
                    'better_payment_campaign_team_member_social_links_enable' => 'yes',
                ]
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_team_members',
            [
                'label'       => esc_html__( 'Team Members', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $team_member_repeater->get_controls(),
                'default'     => [
                    [
                        'better_payment_campaign_team_member_name' => 'Annette Black',
                        'better_payment_campaign_team_member_image' => [
                            'url' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/team-1.webp',
                        ],
                    ],
                    [
                        'better_payment_campaign_team_member_name' => 'Darrell Steward',
                        'better_payment_campaign_team_member_image' => [
                            'url' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/team-2.webp',
                        ],
                    ],
                    [
                        'better_payment_campaign_team_member_name' => 'Theresa Webb',
                        'better_payment_campaign_team_member_image' => [
                            'url' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/team-3.webp',
                        ],
                    ],
                    [
                        'better_payment_campaign_team_member_name' => 'Guy Hawkins',
                        'better_payment_campaign_team_member_image' => [
                            'url' => BETTER_PAYMENT_ASSETS . '/img/campaign/layout-1/team-4.webp',
                        ],
                    ],
                ],
                'title_field' => '{{{ better_payment_campaign_team_member_name }}}',
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );
        
        $this->end_controls_section();
    }

    public function related_campaign_settings() {
        $this->start_controls_section(
            'better_payment_campaign_related_campaign_settings',
            [
                'label'     => esc_html__( 'Related Campaigns', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ]
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_related_campaign_sub_title_text',
            [
                'label'       => __( 'Sub Title', 'better-payment' ),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'placeholder' => 'We Helped More Than 3,400 Children With Your Generosity',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ]
            ]
        );
        
        $related_campaign_repeater = new Repeater();
        $related_campaign_repeater->add_control(
            'better_payment_campaign_related_campaign_id',
            [
                'label' => esc_html__('Campaign ID', 'better-payment'),
                'type'  => Controls_Manager::TEXT,
                'placeholder' => '42b4249',
                'label_block' => true,
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_related_campaigns',
            [
                'label'       => esc_html__( 'Campaigns', 'better-payment' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $related_campaign_repeater->get_controls(),
                'default'     => [
                    [
                        'better_payment_campaign_related_campaign_id' => '',
                    ],
                    [
                        'better_payment_campaign_related_campaign_id' => '',
                    ],
                    [
                        'better_payment_campaign_related_campaign_id' => '',
                    ],
                    [
                        'better_payment_campaign_related_campaign_id' => '',
                    ],
                ],
                'title_field' => '{{{ better_payment_campaign_related_campaign_id }}}',
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_related_campaigns_info',
            [
                'type'        => Controls_Manager::RAW_HTML,
                'raw' => sprintf( 
                    __( '<p class="better-payment-dynamic-value-info" style="word-break: break-word;">Under Items, provide other campaign IDs. The first campaign will be featured. Follow <a href="%1$s" target="_blank">this doc</a> to retrieve campaign ID.</p>', 'better-payment' ), 
                    esc_url('//betterpayment.co/docs/retrieve-fundraising-campaign-id')
                ),
                'content_classes' => 'elementor-control-alert elementor-panel-alert elementor-panel-alert-info',
                'ai' => [
                    'active' => false,
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    public function form_style() {
        $this->campaign_container_style();
        $this->campaign_header_style();
        $this->campaign_form_style();
        $this->campaign_tab_navigation_style();
        $this->campaign_overview_style();
        $this->campaign_team_style();
        do_action('better_payment/elementor/editor/campaign_pro_updates_section_style', $this);
        $this->related_campaign_style();
    }

    public function campaign_container_style() {
        $this->start_controls_section(
            'better_payment_campaign_container_style',
            [
                'label' => esc_html__( 'Container', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'better_payment_campaign_container_bg_color',
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3
                ',
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_container_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_container_margin',
            [
                'label'      => esc_html__( 'Margin', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'better_payment_campaign_container_border',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3
                ',
            ]
        );

        $this->add_control(
            'better_payment_campaign_container_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'better_payment_campaign_container_box_shadow',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3
                ',
            ]
        );

        $this->end_controls_section();
    }

    public function campaign_header_style() {
        $this->start_controls_section(
            'better_payment_campaign_header_style',
            [
                'label' => esc_html__( 'Header', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'better_payment_campaign_header_background',
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section',
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_content',
            [
                'label'     => __( 'Content', 'better-payment' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_header_content_tabs'
        );

        $this->start_controls_tab(
            'better_payment_campaign_header_title_tab',
            [
                'label' => __( 'Title', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_primary' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_header_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_primary, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary
                ',
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_header_title_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_header_short_desc_tab',
            [
                'label' => __( 'Short Description', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_header_short_desc_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_secondary' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_xm' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_secondary' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_header_short_desc_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_secondary, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_secondary,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_xm, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_secondary
                ',
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_header_short_desc_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-text_wrapper .bp-text_secondary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-text_xm' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-text_wrapper .bp-text_secondary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_images',
            [
                'label'     => __( 'Images', 'better-payment' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_images_tabs'
        );

        $this->start_controls_tab(
            'better_payment_campaign_image_one_tab',
            [
                'label' => __( 'One', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_one_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_one_margin',
            [
                'label'      => esc_html__( 'Margin', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'better_payment_campaign_image_one_border',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_image_one_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_one_width',
            [
                'label'      => esc_html__( 'Width', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1200,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_one_height',
            [
                'label'      => esc_html__( 'Height', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1200,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-left .bp-left-img-wrapper img' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-img_wrapper img' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_image_two_tab',
            [
                'label' => __( 'Two', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_two_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_two_margin',
            [
                'label'      => esc_html__( 'Margin', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'better_payment_campaign_image_two_border',
                'selector' => '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_image_two_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_two_width',
            [
                'label'      => esc_html__( 'Width', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1200,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_image_two_height',
            [
                'label'      => esc_html__( 'Height', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1200,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-donation_thoughts-section .bp-image_wrapper-right .bp-right-img-wrapper img' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    public function campaign_form_style() {
        $this->start_controls_section(
            'better_payment_campaign_form_style',
            [
                'label' => esc_html__( 'Form', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'better_payment_campaign_form_background_color',
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'better_payment_campaign_form_border',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [ 
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_form_tabs',
            [
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
                'separator' => 'before',
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_form_title_tab',
            [
                'label'     => __( 'Title', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper .bp-form_header' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-header' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper .bp-form_header,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-header
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_sub_title_tab',
            [
                'label'     => __( 'Sub Title', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_sub_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_sub_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_sub_title_background_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_sub_title_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'better_payment_campaign_form_sub_title_border',
                'label'       => __( 'Border', 'better-payment' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_sub_title_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card .card-tag' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', ],
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_form_progress_bar_heading',
            [
                'label'       => esc_html__( 'Progress Bar', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_form_progress_bar_tabs',
            [
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_amount_tab',
            [
                'label'     => __( 'Amount', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_amount_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top .bp-aamount' => 'color: {{VALUE}}'
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_progress_bar_amount_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top .bp-aamount
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_percentage_tab',
            [
                'label'     => __( 'Percentage', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_percentage_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top .bp-progress_output' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top span.bp-amount-label' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_progress_bar_percentage_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top .bp-progress_output, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-form_top span.bp-amount-label
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_bar_tab',
            [
                'label'     => __( 'Bar', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-progress_bar::-webkit-progress-value' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_bg_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-progress_bar::-webkit-progress-bar' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        ); 

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_height',
            [
                'label'      => esc_html__( 'Height', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 1,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-progress_bar' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-progress_bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_form_progress_bar_heading_l2',
            [
                'label'       => esc_html__( 'Progress Bar', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_form_progress_bar_tabs_l2',
            [
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_bar_tab_l2',
            [
                'label'     => __( 'Bar', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );
        
        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_color_l2',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form .bp-progress_bar::-webkit-progress-value' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_bg_color_l2',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form .bp-progress_bar::-webkit-progress-bar' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        ); 

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_height_l2',
            [
                'label'      => esc_html__( 'Height', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 1,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form .bp-progress_bar' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_bar_border_radius_l2',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form .bp-progress_bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_amount_tab_l2',
            [
                'label'     => __( 'Donations', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_amount_color_l2',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form label.bp-donations' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_progress_bar_amount_typography_l2',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-progress_bar-card form label.bp-donations
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_progress_bar_percentage_tab_l2',
            [
                'label'     => __( 'Raised', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_progress_bar_percentage_color_l2',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 form .bp-text_m' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_progress_bar_percentage_typography_l2',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 form .bp-text_m
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_form_amount_list_heading',
            [
                'label'       => esc_html__( 'Amount List', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_form_amount_list_tabs',
            [
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_form_amount_list_normal_tab',
            [
                'label'     => __( 'Normal', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_normal_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_amount_list_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_normal_background_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );
        
        $this->add_responsive_control(
            'better_payment_campaign_form_amount_list_normal_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'better_payment_campaign_form_amount_list_normal_border',
                'label'       => __( 'Border', 'better-payment' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label, {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_normal_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_amount_list_active_tab',
            [
                'label'     => __( 'Active', 'better-payment' ),
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_active_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_active' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg.active_border' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_list_active_background_color',
            [
                'label'     => __( 'Background Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_active' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg.active_border' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'better_payment_campaign_form_amount_list_active_border',
                'label'       => __( 'Border', 'better-payment' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-radio_active, {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_item .bp-payment_item_bg.active_border',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_form_amount_field_heading',
            [
                'label'     => __( 'Amount Field', 'better-payment' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_field_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount' => 'color: {{VALUE}}',
                    '.better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount::placeholder' => 'color: {{VALUE}}',
                    '.better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .bp-payment_form-input' => 'color: {{VALUE}}',
                    '.better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .bp-payment_form-input::placeholder' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_amount_field_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount,
                    .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .bp-payment_form-input
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_field_background_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount' => 'background-color: {{VALUE}}',
                    '.better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .bp-payment_form-input' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_form_amount_field_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '.better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .bp-payment_form-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_field_border_bottom_width',
            [
                'label' => esc_html__('Border Bottom Width', 'better-payment'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_field_border_bottom_style',
            [
                'label' => esc_html__('Border Style', 'better-payment'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    '' => 'Default',
                    'none' => 'None',
                    'solid' => 'Solid',
                    'dashed' => 'Dashed',
                    'double' => 'Double',
                    'dotted' => 'Dotted',
                    'groove' => 'Groove',
                ],
                'selectors' => [
                    '{{WRAPPER}} .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild' => 'border-bottom-style: {{VALUE}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_amount_field_border_bottom_color',
            [
                'label' => esc_html__('Border Color', 'better-payment'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild' => 'border-bottom-color: {{VALUE}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_currency_color_l3',
            [
                'label'     => __( 'Currency Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .layout-3-currency' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_currency_typography_l3',
                'selector' => '
                    {{WRAPPER}} .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .input-fild .layout-3-currency
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'better_payment_campaign_form_amount_field_border',
                'label'       => __( 'Border', 'better-payment' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control( 
            'better_payment_campaign_form_amount_field_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .campaign-custom-amount' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_heading',
            [
                'label'     => __( 'Button', 'better-payment' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_form_button_tabs'
        );

        $this->start_controls_tab(
            'better_payment_campaign_form_button_normal_tab',
            [
                'label' => __( 'Normal', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_normal_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_form_button_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn
                ',
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_icon_color',
            [
                'label'     => __( 'Icon Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn svg path' => 'stroke: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn svg path' => 'stroke: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_normal_background_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_form_button_normal_padding',
            [
                'label'      => esc_html__( 'Padding', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'better_payment_campaign_form_button_normal_border',
                'label'       => __( 'Border', 'better-payment' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn
                ',
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_normal_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_form_button_hover_tab',
            [
                'label' => __( 'Hover', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_hover_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn:hover' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_hover_icon_color',
            [
                'label'     => __( 'Icon Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn:hover svg path' => 'stroke: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn:hover svg path' => 'stroke: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-2', 'layout-3', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_hover_background_color',
            [
                'label'     => __( 'Background', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn:hover' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn:hover' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_form_button_hover_border_color',
            [
                'label'     => __( 'Border Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-charity_raised-section .bp-charity_raised-card .bp-form_wrapper form .bp-donate_btn:hover' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donation_hero-section .bp-text_wrapper .bp-donate_btn:hover' => 'border-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-donation_hero-section .bp-hero_wrapper .bp-text_wrapper .bp-payment_form .bp-donation_btn:hover' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    public function campaign_overview_style() {
        $this->start_controls_section(
            'better_payment_campaign_overview_style',
            [
                'label' => esc_html__( 'Overview', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_title_heading_l_3',
            [
                'label'       => esc_html__( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_title_color_l3',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-help_wrapper .bp-text_xxl' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_overview_title_typography_l3',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-help_wrapper .bp-text_xxl
                ',
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-3', ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_short_desc_heading',
            [
                'label'       => esc_html__( 'Short Description', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_short_desc_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-section-body .bp-text_secondary' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-tab_content .bp-overviow_text-wrapper .bp-overview_text' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-help_wrapper .bp-text_secondary' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_overview_short_desc_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-section-body .bp-text_secondary, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-tab_content .bp-overviow_text-wrapper .bp-overview_text,
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-help_wrapper .bp-text_secondary
                ',
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_heading',
            [
                'label'       => esc_html__( 'Our Mission', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_overview_our_mission_tabs',
            [
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_overview_our_mission_tab',
            [
                'label'     => __( 'Title', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-text_l' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_overview_our_mission_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-text_l
                ',
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_overview_our_mission_items_tab',
            [
                'label'     => __( 'List', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_items_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_text-wrapper .bp-overview_list .bp-overview_list-item p' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_overview_our_mission_items_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_text-wrapper .bp-overview_list .bp-overview_list-item p
                ',
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_our_mission_items_bullet_color',
            [
                'label'     => __( 'Bullet Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_text-wrapper .bp-overview_list .bp-overview_list-item span svg path' => 'fill: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_overview_mossion_enable' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                    'better_payment_campaign_general_overview_enable' => 'yes',
                ]
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();
        
        $this->end_controls_section();
    }

    public function campaign_tab_navigation_style() {
        $this->start_controls_section(
            'better_payment_campaign_tab_navigation_style',
            [
                'label' => esc_html__( 'Tabs', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-text_xxl' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_overview_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button, 
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_3 .bp-text_xxl
                ',
            ]
        );

        $this->add_control(
            'better_payment_campaign_overview_active_tab_background_color',
            [
                'label'     => __( 'Active Tab Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button::after' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-content_navigation-section .bp-tab_navigation .bp-tab_button::after' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_layout' => [ 'layout-1', 'layout-2', ],
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function campaign_team_style() {

        $this->start_controls_section(
            'better_payment_campaign_team_style',
            [
                'label' => __( '⮑ Our Team', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_title_heading',
            [
                'label'       => esc_html__( 'Title', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_title_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-section-footer.bp-overview_team-wrapper .bp-text_xxl' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_team_title_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-section-footer.bp-overview_team-wrapper .bp-text_xxl
                ',
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_name_heading',
            [
                'label'       => esc_html__( 'Name', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_member_name_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .member-name' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_team_member_name_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .member-name
                ',
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_designation_heading',
            [
                'label'       => esc_html__( 'Designation', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_member_designation_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .member-designation' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'better_payment_campaign_team_member_designation_typography',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .member-designation
                ',
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_social_links_heading',
            [
                'label'       => esc_html__( 'Social Links', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs(
            'better_payment_campaign_team_social_links_tabs',
            [
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->start_controls_tab(
            'better_payment_campaign_team_social_links_normal_tab',
            [
                'label' => esc_html__( 'Normal', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_social_links_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane .bp-team-social_links-wrapper a svg' => 'fill: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        // size
        $this->add_responsive_control(
            'better_payment_campaign_team_social_links_size',
            [
                'label'      => esc_html__( 'Size', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane .bp-team-social_links-wrapper a svg' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'better_payment_campaign_team_social_links_hover_tab',
            [
                'label' => esc_html__( 'Hover', 'better-payment' ),
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_social_links_hover_color',
            [
                'label'     => __( 'Color', 'better-payment' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane .bp-team-social_links-wrapper a svg:hover' => 'fill: {{VALUE}}',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'better_payment_campaign_team_image_heading',
            [
                'label'       => esc_html__( 'Image', 'better-payment' ),
                'type'        => Controls_Manager::HEADING,
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'better_payment_campaign_team_member_image_size',
            [
                'label'      => esc_html__( 'Size', 'better-payment' ),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 1200,
                        'step' => 1,
                    ],
                ],
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .img-box img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'better_payment_campaign_team_member_image_border',
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .img-box img
                ',
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );

        $this->add_control(
            'better_payment_campaign_team_member_image_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'better-payment' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .better-payment .better-payment_campaign-layout_1 .bp-content_navigation-section .bp-tab_content .bp-tab_pane#overview .bp-overview_team-wrapper .img-box img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'better_payment_campaign_general_footer_team_enable_layout_1' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-1' ],
                ]
            ]
        );
        
        $this->end_controls_section();
    }

    public function related_campaign_style() {
        $this->start_controls_section(
            'better_payment_related_campaign_style',
            [
                'label' => __( 'Related Campaigns', 'better-payment' ),
                'tab'   => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'better_payment_related_campaign_background_color',
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '
                    {{WRAPPER}} .better-payment .better-payment_campaign-layout_2 .bp-donate_section .bp-donate_section-wrapper
                ',
                'condition' => [
                    'better_payment_campaign_general_footer_related_campaign_enable_layout_2' => 'yes',
                    'better_payment_campaign_layout' => [ 'layout-2' ],
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the widget output on the frontend.
     *
     * @since 1.0.0
     */
    protected function render() {
        $is_edit_mode = Plugin::instance()->editor->is_edit_mode();

        if ( $is_edit_mode && ( ! current_user_can('manage_options') ) ) {
            return;
        }
        
        $settings = $this->get_settings_for_display();

        $payment_field = "number";
        $bp_widget_page_id = get_the_ID();
        $bp_widget_id = $this->get_id();
        $campaign_id_prefix = sanitize_text_field( 'bp_campaign_' . $bp_widget_page_id . '_' );
        $campaign_id_postfix = sanitize_text_field( $settings[ 'better_payment_campaign_id' ] );
        $better_payment_campaign_id = $campaign_id_prefix . $campaign_id_postfix;
        $bp_goal_amount = sanitize_text_field($settings[ 'better_payment_campaign_form_goal_amount' ]);
        $bp_currency = sanitize_text_field($settings[ 'better_payment_campaign_currency' ]);

        global $wpdb;
        $table   = "{$wpdb->prefix}better_payment";
        $bpc_raised_amount_data = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table WHERE campaign_id=%s", sanitize_text_field( $better_payment_campaign_id ) )
        );

        $bpc_total_amount_raised = 0;
        $bpc_total_payment_count = 0;

        if ( ! empty( $bpc_raised_amount_data ) ) {
            foreach ( $bpc_raised_amount_data as $payment_record ) {
                if ( ! empty( $payment_record->status ) && 'paid' === $payment_record->status ) {
                    $bpc_total_amount_raised += floatval( $payment_record->amount );
                    $bpc_total_payment_count++;
                }
            }
        }

        $bpc_goal_percentage = 0;
        if ( ! empty( $bp_goal_amount ) && $bp_goal_amount > 0 && $bpc_total_amount_raised > 0 ) {
            $bpc_goal_percentage = floor( ( $bpc_total_amount_raised / floatval( $bp_goal_amount ) ) * 100 );
        }

        wp_enqueue_script( 'fundraising-campaign-script' );

        $action       = esc_url( admin_url( 'admin-post.php' ) );

        $setting_meta = wp_json_encode( [
            'page_id'   => $bp_widget_page_id,
            'widget_id' => esc_attr( $bp_widget_id ),
            'campaign_id' => esc_attr( $better_payment_campaign_id ),
            'goal_amount' => esc_attr( $bp_goal_amount ),
            'currency' => esc_attr( $bp_currency ),
            'campaign_id_prefix' => esc_attr( $campaign_id_prefix ),
            'campaign_id_postfix' => esc_attr( $campaign_id_postfix ),
            'raised_amount' => esc_attr( $bpc_total_amount_raised ),
        ] );

        $better_payment_campaigns = get_option( 'better_payment_campaigns', [] );
        $better_payment_campaigns[ esc_attr( $bp_widget_id ) ] = $setting_meta;
        update_option( 'better_payment_campaigns', $better_payment_campaigns );

        
        $better_payment_placeholder_class = '';
        if ( !empty($settings[ 'better_payment_placeholder_switch' ]) && $settings[ 'better_payment_placeholder_switch' ] != 'yes' ) {
            $better_payment_placeholder_class = 'better-payment-hide-placeholder';
        }        

        $data = [
            'payment_field' => $payment_field,
            'action'       => $action,
            'setting_meta' => $setting_meta,
            'better_payment_placeholder_class' => $better_payment_placeholder_class,
            'better_payment_campaign_id' => $better_payment_campaign_id,
            'campaign_id_prefix' => $campaign_id_prefix,
            'bpc_total_amount_raised' => $bpc_total_amount_raised,
            'bpc_total_payment_count' => $bpc_total_payment_count,
            'bpc_goal_percentage' => $bpc_goal_percentage,
        ];

        $widgetObj = $this;

        $better_payment_campaign_layout = sanitize_text_field($settings[ 'better_payment_campaign_layout' ]);
        $better_payment_campaign_layout = in_array($better_payment_campaign_layout, array_keys( $this->better_payment_campaign_layouts() ) ) ? $better_payment_campaign_layout : 'layout-1';

        $template_file = BETTER_PAYMENT_ADMIN_VIEWS_PATH . '/elementor/fundraising-campaign/' . $better_payment_campaign_layout . '.php';

        $better_payment_campaign_content = '';

        if ( file_exists($template_file) ) {
            ob_start();
            include $template_file;
            $better_payment_campaign_content = ob_get_contents();
            ob_end_clean();
        }

        $better_payment_campaign_content = apply_filters( 'better_payment/elementor/editor/get_layout_content', $better_payment_campaign_content, $settings, $this, $data );

        echo $better_payment_campaign_content;
    }
    
    public function render_campaign_hidden_fields( $settings ) {
        $better_payment_campaign_id = 'bp_campaign_' . get_the_ID() . '_' . sanitize_text_field($settings[ 'better_payment_campaign_id' ]);
        $better_payment_campaign_currency = sanitize_text_field($settings[ 'better_payment_campaign_currency' ]);

        $hidden_fields = '<input type="hidden" class="better_payment_campaign_id" name="better_payment_campaign_id" value="' . esc_attr( $better_payment_campaign_id ) . '">
            <input type="hidden" class="better_payment_campaign_currency" name="better_payment_campaign_currency" value="' . esc_attr( $better_payment_campaign_currency ) . '">';

        return $hidden_fields;
    }

    public function better_payment_campaign_layouts() {
        $layouts = apply_filters('better_payment/elementor/widget/campaign_layouts', [
            'layout-1' => 'Layout 1',
            'layout-2' => 'Layout 2',
            'layout-3' => 'Layout 3'
        ]);

        return $layouts;
    }
}
