<?php

namespace Better_Payment\Lite\Admin;

use Better_Payment\Lite\Controller;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings persistence helpers.
 *
 * This class used to render the PHP admin — the settings page, the transactions
 * list and single view, the analytics page — and register their AJAX handlers.
 * The admin is React now (Admin\ReactAdmin + API\AdminAPI) and this class was
 * never instantiated, so all of that was unreachable and has been removed. What
 * remains are the three static helpers live code still calls: the quick-setup
 * wizard (Setup_Wizard::save_setup_wizard_data) and the Elementor bootstrap in
 * better-payment.php.
 *
 * @since 0.0.1
 */
class Settings extends Controller
{
	/**
	 * This function is responsible for
	 * save all settings data, including checking the disable field to prevent
	 * users manipulation.
	 *
	 * @param array $values
	 * @return void
	 * @since 0.0.1
	 */
	public static function save_settings($posted_fields = [])
	{
		$saved_settings = DB::get_settings();
		$default_settings = DB::default_settings();

		$saved_settings = wp_parse_args($default_settings, $saved_settings); // newly added default values to merge

		$data = [];
		$new_posted_fields = [];

		if (!empty($posted_fields)) {
			foreach ($posted_fields as $posted_field) {
				$new_posted_fields[$posted_field['name']] = $posted_field['value'];
			}
		}

		if (count($new_posted_fields)) {

			foreach ($saved_settings as $saved_settings_item_key => $saved_settings_item_value) {
				if (isset($new_posted_fields[$saved_settings_item_key]) && $new_posted_fields[$saved_settings_item_key] != '') {
					$data[$saved_settings_item_key] = self::sanitize_field($saved_settings_item_key, $new_posted_fields[$saved_settings_item_key]);
				} else {
					$data[$saved_settings_item_key] = $saved_settings_item_value;
				}
			}
		}

		if (count($data)) {
			DB::update_settings($data);
		}

		return;
	}

	/**
	 * Save default settings
	 *
	 * @since 0.0.1
	 */
	public static function save_default_settings($reset = 0)
	{
		$default_settings = DB::default_settings();
		$saved_settings = DB::get_settings();

		//Do nothing : if already saved && not reset
		if (!empty($saved_settings) && (!$reset)) {
			return false;
		}

		return DB::update_settings($default_settings);
	}

	/**
	 * This function is responsible for the data sanitization
	 *
	 * @param array $field
	 * @param string|array $value
	 * @return string|array
	 * @since 0.0.1
	 */
	public static function sanitize_field($field, $value)
	{
		if (isset($field['sanitize']) && !empty($field['sanitize'])) {
			if (function_exists($field['sanitize'])) {
				$value = call_user_func($field['sanitize'], $value);
			}
			return $value;
		}

		if (is_array($field) && isset($field['type'])) {
			switch ($field['type']) {
				case 'text':
					$value = sanitize_text_field($value);
					break;
				case 'textarea':
					$value = sanitize_textarea_field($value);
					break;
				case 'email':
					$value = sanitize_email($value);
					break;
				default:
					return $value;
					break;
			}
		} else {
			$value = sanitize_text_field($value);
		}

		return $value;
	}
}
