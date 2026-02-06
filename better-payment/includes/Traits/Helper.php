<?php

namespace Better_Payment\Lite\Traits;

use Better_Payment\Lite\Classes\Plugin_Usage_Tracker;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper trait
 * 
 * @since 0.0.1
 */
trait Helper
{
    use Elements, ElementorHelper, WordPressHelper, TemplateQuery;

    public function bp_template_render($filePath, $variables = array(), $print = true)
    {
        $output = NULL;

        if (file_exists($filePath)) {
            // Extract the variables to a local namespace
            extract($variables);

            // Start output buffering
            ob_start();

            // Include the template file
            include $filePath;

            // End buffering and return its contents
            $output = ob_get_clean();
        }

        if ( $print ) {
			print $output;
        }
        
        return $output;
    }

	/**
     * List of allowed html tag for wp_kses
     *
	 * bp_allowed_tags
	 * @return array
	 */
	public function bp_allowed_tags() {
		return [
			'a'       => [
				'href'   => [],
				'title'  => [],
				'class'  => [],
				'rel'    => [],
				'id'     => [],
				'style'  => [],
				'target' => [],
			],
			'q'       => [
				'cite'  => [],
				'class' => [],
				'id'    => [],
			],
			'img'     => [
				'src'    => [],
				'alt'    => [],
				'height' => [],
				'width'  => [],
				'class'  => [],
				'id'     => [],
				'style'  => []
			],
			'span'    => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'dfn'     => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'time'    => [
				'datetime' => [],
				'class'    => [],
				'id'       => [],
				'style'    => [],
			],
			'cite'    => [
				'title' => [],
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'hr'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'b'       => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'p'       => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'i'       => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'u'       => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			's'       => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'br'      => [],
			'em'      => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'code'    => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'mark'    => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'small'   => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'abbr'    => [
				'title' => [],
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'strong'  => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'del'     => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'ins'     => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'sub'     => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'sup'     => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'div'     => [
				'class' => [],
				'id'    => [],
				'style' => []
			],
			'strike'  => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'acronym' => [],
			'h1'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'h2'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'h3'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'h4'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'h5'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'h6'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'button'  => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'data-paypal-info' => [],
			],
			'center'  => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'ul'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'ol'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'li'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
			],
			'table'   => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'dir'   => [],
				'align' => [],
			],
			'thead'   => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'align' => [],
			],
			'tbody'   => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'align' => [],
			],
			'tfoot'   => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'align' => [],
			],
			'th'      => [
				'class'   => [],
				'id'      => [],
				'style'   => [],
				'align'   => [],
				'colspan' => [],
				'rowspan' => [],
			],
			'tr'      => [
				'class' => [],
				'id'    => [],
				'style' => [],
				'align' => [],
			],
			'td'      => [
				'class'   => [],
				'id'      => [],
				'style'   => [],
				'align'   => [],
				'colspan' => [],
				'rowspan' => [],
			],
		];
	}

	/**
     * Optional usage tracker
     *
     * @since v1.1.1
     */
    public function start_plugin_tracking()
    {
        $tracker = Plugin_Usage_Tracker::get_instance( BETTER_PAYMENT_FILE, [
            'opt_in'       => true,
            'goodbye_form' => true,
            'item_id'      => '64e1f724b5e14edb343e'
        ] );
        $tracker->set_notice_options(array(
            'notice' => 'Want to help make <strong>Better Payment</strong> even more awesome? You can get a <strong>10% discount coupon</strong> for Pro upgrade if you allow.',
            'extra_notice' => 'We collect non-sensitive diagnostic data and plugin usage information.
            Your site URL, WordPress & PHP version, plugins & themes and email address to send you the
            discount coupon. This data lets us make sure this plugin always stays compatible with the most
            popular plugins and themes. No spam, I promise.',
        ));
        $tracker->init();
    }

	/**
	 * List of currencies not supported by paypal or stripe
	 *
	 * @since v1.3.1
	 */
	public function bp_unsupported_currencies( $method = '' ) {
		$method = sanitize_text_field( strtolower( $method ) );
		$all_currencies = [
			'paypal' => [
				'AED',
				'BGN',
				'BAM',
				'KES',
				'NGN',
				'RON',
				'RSD',
				'ZAR',
				'GHS',
			],
			'stripe' => [],
		];

		return !empty( $all_currencies[ $method ] ) ? $all_currencies[ $method ] : [];
	}

	/**
	 * List of currencies supported by paystack
	 *
	 * @since v1.3.1
	 */
	public function bp_supported_currencies( $method = '' ) {
		$method = sanitize_text_field( strtolower( $method ) );
		$supported_currencies = [
			'paystack' => [
				'GHS',
				'KES',
				'NGN',
				'USD',
				'ZAR'
			],
		];

		return !empty( $supported_currencies[ $method ] ) ? $supported_currencies[ $method ] : [];
	}

	/**
     * Get comprehensive currency data
     *
     * @since 0.0.2
     * @return array Array of currency data with codes, names, and symbols
     */
    private function get_currency_data() {
        return [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
            'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => '$'],
            'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв'],
			'BAM' => ['name' => 'Bosnia and Herzegovina Convertible Mark', 'symbol' => 'KM'],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => '$'],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF'],
            'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč'],
            'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr'],
			'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '₵'],
            'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => '$'],
            'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'ft'],
            'ILS' => ['name' => 'Israeli Shekel', 'symbol' => '₪'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
            'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'Ksh.'],
            'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$'],
            'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'MYR'],
            'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦'],
            'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr'],
            'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => '$'],
            'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱'],
            'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł'],
            'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei'],
			'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'din.'],
            'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽'],
            'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr'],
            'SGD' => ['name' => 'Singapore Dollar', 'symbol' => '$'],
            'THB' => ['name' => 'Thai Baht', 'symbol' => '฿'],
            'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺'],
            'TWD' => ['name' => 'Taiwan Dollar', 'symbol' => '$'],
            'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R'],
        ];
    }

    /**
     * Get currency symbols list
     *
     * @since 0.0.2
     * @return array Array of currency codes and their symbols
     */
	public function get_currency_symbols_list() {
        $currency_data = $this->get_currency_data();
        $symbols = [];

        foreach ($currency_data as $code => $data) {
            $symbols[$code] = $data['symbol'];
        }

		return $symbols;
	}

	/**
     * Get currency list
     *
     * @since 0.0.2
     * @return array Array of currency codes as key-value pairs
     */
    public function get_currency_list() {
        $currency_data = $this->get_currency_data();
        $currency_list = [];

        foreach ($currency_data as $code => $data) {
            $currency_list[$code] = $code;
        }

        return $currency_list;
    }

    /**
     * Get currency names list
     *
     * @since 0.0.2
     * @return array Array of currency codes and their full names
     */
    public function get_currency_names_list() {
        $currency_data = $this->get_currency_data();
        $names = [];

        foreach ($currency_data as $code => $data) {
            $names[$code] = $data['name'];
        }

        return $names;
    }

    /**
     * Get currency symbol by code
     *
     * @since 0.0.2
     * @param string $currency_code The currency code (e.g., 'USD', 'EUR')
     * @return string The currency symbol or the code if symbol not found
     */
    public function get_currency_symbol($currency_code) {
        $currency_data = $this->get_currency_data();

        if (isset($currency_data[$currency_code])) {
            return $currency_data[$currency_code]['symbol'];
        }

        return $currency_code; // Fallback to currency code
    }

    /**
     * Get currency name by code
     *
     * @since 0.0.2
     * @param string $currency_code The currency code (e.g., 'USD', 'EUR')
     * @return string The currency name or the code if name not found
     */
    public function get_currency_name($currency_code) {
        $currency_data = $this->get_currency_data();

        if (isset($currency_data[$currency_code])) {
            return $currency_data[$currency_code]['name'];
        }

        return $currency_code; // Fallback to currency code
    }

	/**
     * Widget settings
     * 
     * @since 1.0.0
     */
    public function get_elementor_widget_settings( $page_id, $widget_id ) {
        $document = \Elementor\Plugin::$instance->documents->get( $page_id );
        $settings = [];
        if ( $document ) {
            $elements    = \Elementor\Plugin::instance()->documents->get( $page_id )->get_elements_data();
            $widget_data = $this->find_element_recursive( $elements, $widget_id );
            $widget      = ! empty( $widget_data ) && is_array( $widget_data ) ? \Elementor\Plugin::instance()->elements_manager->create_element_instance( $widget_data ) : '';
            if ( ! empty( $widget ) ) {
                $settings = $widget->get_settings_for_display();
            }
        }
        return $settings;
    }

	/**
     * Find element recursive
     * 
     * @since 1.0.0
     */
    public function find_element_recursive( $elements, $form_id ) {

        foreach ( $elements as $element ) {
            if ( $form_id === $element[ 'id' ] ) {
                return $element;
            }

            if ( !empty( $element[ 'elements' ] ) ) {
                $element = $this->find_element_recursive( $element[ 'elements' ], $form_id );

                if ( $element ) {
                    return $element;
                }
            }
        }

        return false;
    }

	public function get_stripe_price_details( $price_id = '', $secret_key = '' ) {
		if ( empty( $price_id ) || empty( $secret_key ) ) {
			return [];
		}

		$api_url = 'https://api.stripe.com/v1/prices/' . $price_id;

		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $secret_key,
			],
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'Stripe API error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		// dd( $data );

		if ( ! isset( $data ) || ! is_array( $data ) ) {
			error_log( 'Stripe API response format error.' );
			return [];
		}

		return $data;
	}

    /**
     * Initialize widget usage
     *
     * @since 1.0.0
     */
    public function bp_init_widget_usage() {
        // Run initial scan if not done yet
        if (!get_option('bp_widget_usage_initial_scan_done', false)) {
            $this->scan_existing_pages_for_widgets();
            update_option('bp_widget_usage_initial_scan_done', true);
        }
    }

    /**
     * Track widget usage when a post is saved
     *
     * @since 1.0.0
     * @param int $post_id
     */
    public function bp_widget_usage_on_save($post_id) {
		// add capability check
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only track pages that support Elementor
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, ['page', 'post'])) {
            return;
        }

        // Check if this page uses Elementor
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        if (empty($elementor_data)) {
            return;
        }

        // Parse and check for Better Payment widgets
        $this->update_widget_usage_flags($post_id, $elementor_data);
    }

    /**
     * Scan all existing pages for Better Payment widgets
     *
     * @since 1.0.0
     */
    public function scan_existing_pages_for_widgets() {
        global $wpdb;

        // Get all posts/pages with Elementor data
        $posts_with_elementor = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta}
             WHERE meta_key = '_elementor_data'
             AND meta_value != ''
             AND meta_value != '[]'"
        );

        foreach ($posts_with_elementor as $post_data) {
            $this->update_widget_usage_flags($post_data->post_id, $post_data->meta_value);
        }
    }

    /**
     * Update widget usage flags based on page content
     *
     * @since 1.0.0
     * @param int $post_id
     * @param string $elementor_data
     */
    public function update_widget_usage_flags($post_id, $elementor_data) {
        // Parse Elementor data
        $elements = json_decode($elementor_data, true);
        if (!is_array($elements)) {
            return;
        }

        // Check for Better Payment widgets
        $widget_usage = $this->detect_better_payment_widgets($elements);

        // Update overall usage flag
        $any_widget_used = $widget_usage['better-payment'];

        if ($any_widget_used) {
            update_option('better_payment_any_widget_used', true);
        } else {
            delete_option('better_payment_any_widget_used');
        }
    }

    /**
     * Recursively detect Better Payment widgets in Elementor data
     *
     * @since 1.0.0
     * @param array $elements
     * @return array
     */
    public function detect_better_payment_widgets($elements) {
        $widget_usage = [
            'better-payment' => false,
        ];

        foreach ($elements as $element) {
            // Check if this element is a Better Payment widget
            if (isset($element['widgetType'])) {
                $widget_type = $element['widgetType'];

                if ($widget_type === 'better-payment') {
                    $widget_usage['better-payment'] = true;
                } elseif ($widget_type === 'better-payment-user-dashboard') {
                    $widget_usage['better-payment'] = true;
                } elseif ($widget_type === 'fundraising-campaign') {
                    $widget_usage['better-payment'] = true;
                }
            }

            // Recursively check nested elements
            if (!empty($element['elements']) && is_array($element['elements'])) {
                $nested_usage = $this->detect_better_payment_widgets($element['elements']);

                // Merge results (OR operation)
                $widget_usage['better-payment'] = $widget_usage['better-payment'] || $nested_usage['better-payment'];
            }
        }

        return $widget_usage;
    }

    /**
     * Check if any Better Payment widget is being used
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_any_better_payment_widget_used() {
        return get_option('better_payment_any_widget_used', false) ? '1' : '0';
    }

	/**
	 * Check if dismissible section should be shown
	 *
	 * @since 1.4.2
	 * @return bool
	 */
	public function bp_section_dismissed() {
		$progress_bar_dismissed = get_option('better_payment_progress_bar_dismissed', false);
		$plugin_installed_fresh = get_option('better_payment_plugin_installed_fresh', false);
		$progress_bar_dismissed_expiry_date = get_option('better_payment_progress_bar_dismissed_expiry_date', 0);

		if ($progress_bar_dismissed) {
			return true;
		}

		return $plugin_installed_fresh === 'yes' && time() > $progress_bar_dismissed_expiry_date;
	}

	public function bp_calculate_progress_steps($settings) {
		$steps = [];

		// Step 1: Check if Elementor is installed and active
		$elementor_active = defined('ELEMENTOR_VERSION') && class_exists('Elementor\Plugin');
		$steps[] = [
			'completed' => $elementor_active,
			'text' => __('Install ( if not installed yet ) and Activate Elementor', 'better-payment')
		];

		// Step 2: Check if API keys are configured
		// Check PayPal keys
		$paypal_business_email = !empty($settings['better_payment_settings_payment_paypal_email']);

		// Check Stripe keys
		$is_stripe_live_mode = $settings['better_payment_settings_payment_stripe_live_mode'] === 'yes' ? true : false;
		if ( $is_stripe_live_mode ) {
			$stripe_public = !empty($settings['better_payment_settings_payment_stripe_live_public']);
			$stripe_secret = !empty($settings['better_payment_settings_payment_stripe_live_secret']);
		} else {
			$stripe_public = !empty($settings['better_payment_settings_payment_stripe_test_public']);
			$stripe_secret = !empty($settings['better_payment_settings_payment_stripe_test_secret']);
		}

		// Check Paystack keys
		$is_paystack_live_mode = $settings['better_payment_settings_payment_paystack_live_mode'] === 'yes' ? true : false;
		if ( $is_paystack_live_mode ) {
			$paystack_public = !empty($settings['better_payment_settings_payment_paystack_live_public']);
			$paystack_secret = !empty($settings['better_payment_settings_payment_paystack_live_secret']);
		} else {
			$paystack_public = !empty($settings['better_payment_settings_payment_paystack_test_public']);
			$paystack_secret = !empty($settings['better_payment_settings_payment_paystack_test_secret']);
		}

		$api_keys_configured = ( isset($paypal_business_email) && $paypal_business_email ) || ( isset($stripe_public) && isset($stripe_secret) && $stripe_public && $stripe_secret ) || ( isset($paystack_public) && isset($paystack_secret) && $paystack_public && $paystack_secret );
		$payment_settings_url = admin_url('admin.php?page=better-payment-admin&tab=settings&id=paypal');

		$steps[] = [
			'completed' => $api_keys_configured,
			'text' => sprintf(__('Insert sandbox/test API keys for Stripe, PayPal & Paystack on <a href="%s">Payment Settings</a>', 'better-payment'), $payment_settings_url)
		];

		// Step 3: Check if widget has been added
		$widget_used = $this->is_any_better_payment_widget_used() === '1' ? true : false;

		$steps[] = [
			'completed' => $widget_used,
			'text' => __('Edit page with Elementor and add Better Payment widget to use it. You can check this', 'better-payment') . ' <a target="_blank" href="//betterpayment.co/docs/" target="_blank">' . __('doc', 'better-payment') . '</a>'
		];

		// Step 4: Check if widget has been customized
		$widget_customized = $widget_used;

		$steps[] = [
			'completed' => $widget_customized,
			'text' => __('Customize the widget as your own and save the changes', 'better-payment')
		];

		return $steps;
	}
}
