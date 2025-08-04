<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPS_SMART_404_Settings_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Smart 404 menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPS_SMART_404_SETTINGS_MENU_SLUG;

	/**
	 * Constructor adds menu for Smart 404
	 */
	public function __construct() {
		parent::__construct(__('Smart 404', 'all-in-one-wp-security-and-firewall-premium'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'tab1' => array(
				'title' => __('General settings', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_general_settings'),
			),
			'tab2' => array(
				'title' => __('Blocked IPs', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_blocked_ips'),
			),
			'tab3' => array(
				'title' => __('Statistics', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_statistics'),
			),
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}
	
	/**
	 * Renders the submenu's general settings tab
	 *
	 * @return void
	 */
	public function render_general_settings() {
		global $aio_wp_security, $aio_wp_security_premium, $aiowps_feature_mgr;
		$result = '';
		if (isset($_POST['aiowps_smart404_settings_save'])) { // Do form submission tasks
			$error = '';
			$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-smart-404-nonce');
		
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}

			$max_404_attempt_val = sanitize_text_field($_POST['aiowps_max_404_attempts']);
			if (!is_numeric($max_404_attempt_val)) {
				$error .= '<br />'.__('You entered a non numeric value for the max login attempts field, it has been set to the default value.', 'all-in-one-wp-security-and-firewall-premium');
				$max_404_attempt_val = '10'; // Set it to the default value for this field
			}

			$time_period_404 = sanitize_text_field($_POST['aiowps_404_retry_time_period']);
			if (!is_numeric($time_period_404)) {
				$error .= '<br />'.__('You entered a non numeric value for the 404 retry time period field, it has been set to the default value.', 'all-in-one-wp-security-and-firewall-premium');
				$time_period_404 = '10'; // Set it to the default value for this field
			}


			if ($error) {
				$this->show_msg_error(__('Attention.', 'all-in-one-wp-security-and-firewall-premium').$error);
			}

			// Save all the form values to the options
			$aio_wp_security_premium->configs->set_value('aiowps_enable_smart_404', isset($_POST['aiowps_enable_smart_404']) ? '1' : '');
			$aio_wp_security_premium->configs->set_value('aiowps_max_404_attempts', absint($max_404_attempt_val));
			$aio_wp_security_premium->configs->set_value('aiowps_404_retry_time_period', absint($time_period_404));

			$aio_wp_security_premium->configs->save_config();
			$aio_wp_security_premium->show_msg_settings_updated();

			// Recalculate points after the feature status/options have been altered
			$aiowps_feature_mgr->check_feature_status_and_recalculate_points();
		}

		// instant blocking settings save
		if (isset($_POST['save_404_instant_block_settings'])) {
			$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-instant-404-block-nonce');

			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
		
			if (isset($_POST['aiowps_enable_instant_404_string_block']) && empty($_POST['smart_404_instant_block_strings'])) {
				$result = -1;
				$this->show_msg_error(__('You must submit at least one blocking string', 'all-in-one-wp-security-and-firewall-premium'));
			} else {
				if (!empty($_POST['smart_404_instant_block_strings'])) {
					$blocking_strings = stripslashes($_POST['smart_404_instant_block_strings']); // Use stripslashes here as user may want to have a string that contains things that sanitize_text_field() will filter out
					$aio_wp_security_premium->configs->set_value('smart_404_instant_block_strings', $blocking_strings);
					$_POST['smart_404_instant_block_strings'] = ''; // Clear the post variable

					$aio_wp_security_premium->configs->set_value('aiowps_enable_instant_404_string_block', isset($_POST['aiowps_enable_instant_404_string_block']) ? '1' : '');
				} else {
					$aio_wp_security_premium->configs->set_value('smart_404_instant_block_strings', ''); // Clear the config value
				}
				$aio_wp_security_premium->configs->save_config(); // Save the configuration
				$aio_wp_security_premium->show_msg_settings_updated();

				// Recalculate points after the feature status/options have been altered
				$aiowps_feature_mgr->check_feature_status_and_recalculate_points();

				$result = 1;
			}
		}


		// whitelist settings
		$your_ip_address = AIOWPSecurity_Utility_IP::get_user_ip_address();
		if (isset($_POST['save_smart_404_whitelist_settings'])) {
			$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'smart-404-whitelist-nonce');
		
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}

			if (isset($_POST['enable_smart_404_whitelist']) && empty($_POST['smart_404_ip_whitelist'])) {
				$this->show_msg_error(__('You must submit at least one IP address.', 'all-in-one-wp-security-and-firewall-premium'));
			} else {
				$result = 1;
				if (!empty($_POST['smart_404_ip_whitelist'])) {
					$ip_list_array = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline(sanitize_textarea_field($_POST['smart_404_ip_whitelist']));
					$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ip_list_array, 'whitelist');
					if (!is_wp_error($validated_ip_list_array)) {
						// success case
						$aio_wp_security_premium->configs->set_value('smart_404_ip_whitelist', implode("\n", $validated_ip_list_array));
						$_POST['smart_404_ip_whitelist'] = ''; // Clear the post variable for the banned address list
					} else {
						$result = -1;
						$this->show_msg_error(nl2br($validated_ip_list_array->get_error_message()));
					}
				} else {
					$aio_wp_security_premium->configs->set_value('smart_404_ip_whitelist', ''); // Clear the IP address config value
				}

				if (1 == $result) {
					$aio_wp_security_premium->configs->set_value('enable_smart_404_whitelist', isset($_POST['enable_smart_404_whitelist']) ? '1' : '');
					$aio_wp_security_premium->configs->save_config(); // Save the configuration

					$aio_wp_security_premium->show_msg_settings_updated();

					// Recalculate points after the feature status/options have been altered
					$aiowps_feature_mgr->check_feature_status_and_recalculate_points();
				}
			}
		}

		$aio_wp_security_premium->include_template('wp-admin/smart-404/general-settings.php', false, array('aiowps_feature_mgr' => $aiowps_feature_mgr, 'your_ip_address' => $your_ip_address, 'aio_wp_security' => $aio_wp_security));
	}
	
	/**
	 * Renders the submenu's blocked IPs tab
	 *
	 * @return void
	 */
	public function render_blocked_ips() {
		global $aio_wp_security, $aio_wp_security_premium;
		// echo "<script type='text/javascript' src='//www.google.com/jsapi'></script>";//Include the google chart library

		echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
		include_once 'smart-404-list-blocked-ip.php'; // For rendering the AIOWPSecurity_List_Table
		$blocked_ip_list = new AIOWPSecurity_List_404_Blocked_IP(); // For rendering the AIOWPSecurity_List_Table

		if (isset($_GET['action']) && 'unblock_ip' == $_GET['action']) {
			// Do list table form row action tasks
			if (empty($_GET['aiowps_nonce'])) return;

			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($_GET['aiowps_nonce'], 'unblock_ip');
		
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
			// Unblock link was clicked for a row in list table
			$blocked_ip_list->unblock_ip_address(strip_tags($_GET['blocked_id']));
		}

		$aio_wp_security_premium->include_template('wp-admin/smart-404/blocked-ips.php', false, array('blocked_ip_list' => $blocked_ip_list));
	}
	
	/**
	 * Renders the submenu's statistics tab
	 *
	 * @return void
	 */
	public function render_statistics() {
		global $aio_wp_security_premium;
		$data_blocked = AIOWPS_Premium_Utilities::get_all_404_blocked();
		$blocked_ip_count = AIOWPS_Premium_Utilities::count_blocked_countries($data_blocked); // sort into simple array showing country and count

		/**
		 * For line chart - start
		 */
		// for line chart showing top 5 countries producing most 404 in last x days
		$last_x = 10; // set last number of days for line chart
		$last_x_days_404_data = AIOWPS_Premium_Utilities::get_last_n_days_404($last_x); // get 404 events for last x days
		$last_x_days_count = AIOWPS_Premium_Utilities::count_last_n_days($last_x_days_404_data);

		// extract countries
		$countries_10day_array = $last_x_days_count[0]; // list of countries
		$data_10day_array = $last_x_days_count[1]; // data
		// Let's get the dates for the last 10 days
		$dates_last_x = array();
		for ($i = 0; $i < $last_x; $i++) {
			$dates_last_x[] = date("d M", strtotime($i." days ago"));
		}
		$dates_last_x = array_reverse($dates_last_x);
		$line_chart_data = "";
		// $line_chart_data .= "['Date', '404 Count', ";
		$line_chart_data .= "['Date', ";
		foreach ($countries_10day_array as $key => $value) {
			$line_chart_data .= "'" . $key . "', ";
		}
		$line_chart_data = substr($line_chart_data, 0, -2); // remove the comma and space at end
		$line_chart_data .= '],';

		$sorted_data = array();
		foreach ($dates_last_x as $day_mth) {
			$current_404_total = 0;
			foreach ($countries_10day_array as $key => $value) {
				$current_404_total = 0;
				foreach ($data_10day_array as $xyz) {
					$xyz_date = $xyz['event_date'];
					$datetime = new DateTime($xyz_date);
					$f_date = $datetime->format('d M');
					if ($f_date == $day_mth && $key == $xyz['country_code']) {
						++$current_404_total;
					}

				}
				$sorted_data[$day_mth][$key] = $current_404_total;
			}
		}

		// we now have our sorted data so lets put together the rest of the google charts string
		foreach ($sorted_data as $key => $value) {
			$line_chart_data .= "['".$key."', ";
			foreach ($value as $tot) {
				$line_chart_data .= $tot.", ";
			}
			$line_chart_data = substr($line_chart_data, 0, -2); // remove the comma and space at end
			$line_chart_data .= '],';
		}
		$line_chart_data = substr($line_chart_data, 0, -1); // remove the comma at end
		$line_chart_widget_title = 'Last '.$last_x.' days - 404 events by country';
		/**
		 * For line chart - end
		 */

		// Get all-time top 5 404 counts by country
		$country_404_count = $aio_wp_security_premium->configs->get_value('smart_404_all_time_404_count');
		$country_404_count = maybe_unserialize($country_404_count);
		if (!empty($country_404_count)) {
			arsort($country_404_count);
			$country_404_count = array_slice($country_404_count, 0, 5, true); // get top 5
		}

		$ip_top_10_count = AIOWPS_Premium_Utilities::count_404_ips($last_x_days_404_data); // sort into simple array showing IP and count
		$ip_top_10_count = array_slice($ip_top_10_count, 0, 10, true);

		$aio_wp_security_premium->include_template('wp-admin/smart-404/statistics.php', false, array('data_blocked' => $data_blocked, 'blocked_ip_count' => $blocked_ip_count, 'last_x_days_404_data' => $last_x_days_404_data, 'line_chart_data' => $line_chart_data, 'line_chart_widget_title' => $line_chart_widget_title, 'country_404_count' => $country_404_count, 'ip_top_10_count' => $ip_top_10_count));
		
	}
}//end class
