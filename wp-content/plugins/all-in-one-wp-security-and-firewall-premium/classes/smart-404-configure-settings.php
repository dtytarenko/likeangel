<?php

if (!defined('ABSPATH')) exit;

class AIOWPS_SMART_404_Configure_Settings {

	/**
	 * Class constructor
	 */
	public function __construct() {
		
	}

	/**
	 * This function sets default settings for smart 404
	 *
	 * @return void
	 */
	public static function set_default_settings() {
		global $aio_wp_security_premium;
		$aio_wp_security_premium->configs->set_value('smart_404_all_time_404_count', ''); // will hold serialised array of all-time 404 count by country code

		// Main activation flag
		$aio_wp_security_premium->configs->set_value('aiowps_enable_smart_404', '');// Checkbox
		
		$aio_wp_security_premium->configs->set_value('aiowps_max_404_attempts', '10');
		$aio_wp_security_premium->configs->set_value('aiowps_404_retry_time_period', '10');

		// Instant 404 blocking
		$aio_wp_security_premium->configs->set_value('aiowps_enable_instant_404_string_block', '');// Checkbox
		$aio_wp_security_premium->configs->set_value('smart_404_instant_block_strings', '');

		// whitelist
		$aio_wp_security_premium->configs->set_value('enable_smart_404_whitelist', '');// Checkbox
		$aio_wp_security_premium->configs->set_value('smart_404_ip_whitelist', '');


		// TODO - keep adding default options for any fields that require it
		
		// Save it
		$aio_wp_security_premium->configs->save_config();
	}

	/**
	 * Add default option values for AIO WP Security Premium.
	 *
	 * This function initializes and sets default values for various options in the AIO WP Security Premium plugin.
	 * These options include settings for smart 404 handling, instant 404 blocking, whitelist configuration, and more.
	 * After adding the default values, the function saves the configuration.
	 */
	public static function add_option_values() {
		global $aio_wp_security_premium;
		$aio_wp_security_premium->configs->add_value('smart_404_all_time_404_count', ''); // will hold serialised array of all-time 404 count by country code

		$aio_wp_security_premium->configs->add_value('aiowps_enable_smart_404', '');// Checkbox

		$aio_wp_security_premium->configs->add_value('aiowps_max_404_attempts', '10');
		$aio_wp_security_premium->configs->add_value('aiowps_404_retry_time_period', '10');

		// Instant 404 blocking
		$aio_wp_security_premium->configs->add_value('aiowps_enable_instant_404_string_block', '');// Checkbox
		$aio_wp_security_premium->configs->add_value('smart_404_instant_block_strings', '');


		// whitelist
		$aio_wp_security_premium->configs->add_value('enable_smart_404_whitelist', '');// Checkbox
		$aio_wp_security_premium->configs->add_value('smart_404_ip_whitelist', '');

		
		// TODO - keep adding default options for any fields that require it
		
		// Save it
		$aio_wp_security_premium->configs->save_config();
	}
}
