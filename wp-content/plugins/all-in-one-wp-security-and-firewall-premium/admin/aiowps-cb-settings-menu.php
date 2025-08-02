<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPS_CB_Settings_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Country Blocking menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPS_CB_SETTINGS_MENU_SLUG;
	
	/**
	 * Constructor adds menu for Country Blocking
	 */
	public function __construct() {
		parent::__construct(__('Country blocking', 'all-in-one-wp-security-and-firewall-premium'));
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
				'title' => __('Secondary settings', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_secondary_settings'),
			),
			'login_settings' => array(
				'title' => __('Login settings', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_login_settings'),
			),
			'tab3' => array(
				'title' => __('Whitelist', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_whitelist'),
			),
			'tab4' => array(
				'title' => __('Advanced settings', 'all-in-one-wp-security-and-firewall-premium'),
				'render_callback' => array($this, 'render_advanced_settings'),
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
		global $aio_wp_security_premium, $aio_wp_security;
		$countries_array = $aio_wp_security_premium->country_tasks_obj->country_codes;
		$post_array = $_POST;

		if (isset($post_array['aiowps_save_country_blocking_settings'])) { // Do form submission tasks
			
			$error = '';
			$nonce = isset($post_array['_wpnonce']) ? $post_array['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-country-blocking-settings-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
		
			$redirect_url = isset($post_array['aiowps_cb_redirect_url']) ? esc_url_raw($post_array['aiowps_cb_redirect_url']) : '';
			$redirect_url = esc_url($redirect_url, array('http', 'https'));
			if ('' == $redirect_url) {
				$error .= '<br>' . __('You entered an incorrect format for the "Redirect URL" field; it has been set to the default value.', 'all-in-one-wp-security-and-firewall-premium');
				$redirect_url = 'http://127.0.0.1';
			}
			
			$blocked_countries = array();
			foreach ($post_array as $key => $val) {
				if (strpos($key, 'aiowps_country_checkbox') !== false) {
					if (array_key_exists($val, $countries_array)) {
						$blocked_countries[] = sanitize_text_field($val);
					}
				}
			}
			
			if ($error) {
				$this->show_msg_error(__('Attention.', 'all-in-one-wp-security-and-firewall-premium').$error);
			}

			// Save all the form values to the options
			$aio_wp_security_premium->configs->set_value('aiowps_cb_enable_country_blocking', isset($post_array['aiowps_cb_enable_country_blocking']) ? '1' : '');
			$aio_wp_security_premium->configs->set_value('aiowps_cb_blocking_action', sanitize_text_field($post_array['aiowps_cb_blocking_action']));
			$aio_wp_security_premium->configs->set_value('aiowps_cb_blocked_countries', $blocked_countries);
			$aio_wp_security_premium->configs->set_value('aiowps_cb_redirect_url', $redirect_url);
			$aio_wp_security_premium->configs->save_config();

			// Recalculate points after the feature status/options have been altered
			// $aiowps_feature_mgr->check_feature_status_and_recalculate_points();
			$aio_wp_security_premium->show_msg_settings_updated();
		}
	
		$aio_wp_security_premium->include_template('wp-admin/country-blocking/general-settings.php', false, array());

	}
	
	/**
	 * Renders the submenu's secondary settings tab
	 *
	 * @return void
	 */
	public function render_secondary_settings() {
		global $aio_wp_security_premium, $aio_wp_security;
		$countries_array = $aio_wp_security_premium->country_tasks_obj->country_codes;
		$post_array = $_POST;
		if (isset($post_array['aiowps_save_secondary_cb_settings'])) { // Do form submission tasks
			$post_ids = '';
			$error = '';

			$nonce = isset($post_array['_wpnonce']) ? $post_array['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-secondary-country-blocking-settings-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
		

			$redirect_url = isset($post_array['aiowps_cb_secondary_redirect_url']) ? esc_url_raw($post_array['aiowps_cb_secondary_redirect_url']) : '';
			$redirect_url = esc_url($redirect_url, array('http', 'https'));
			if ('' == $redirect_url) {
				$error .= __('You entered an incorrect format for the "Redirect URL" field; it has been set to the default value.', 'all-in-one-wp-security-and-firewall-premium');
				$redirect_url = 'http://127.0.0.1';
			}
			
			if (isset($post_array['aiowps_cb_enable_secondary_country_blocking']) && empty($post_array['aiowps_secondary_cb_protected_post_ids'])) {
				$this->show_msg_error(__('You must submit at least one page or post ID', 'all-in-one-wp-security-and-firewall-premium'));
			} else {
				if (!empty($post_array['aiowps_secondary_cb_protected_post_ids'])) {
					$post_ids_raw = sanitize_textarea_field($post_array['aiowps_secondary_cb_protected_post_ids']);
					$post_ids = preg_replace('/[^\d\n]/', '', $post_ids_raw); // Strip whitespaces and remove any non numeric character
				}
			}
			
			$blocked_countries = array();
			foreach ($post_array as $key => $val) {
				if (strpos($key, 'aiowps_secondary_country_checkbox') !== false) {
					if (array_key_exists($val, $countries_array)) {
						$blocked_countries[] = sanitize_text_field($val);
					}
				}
			}
			
			if ($error) {
				$this->show_msg_error(__('Attention.', 'all-in-one-wp-security-and-firewall-premium'). ' ' . $error);
			}

			// Save all the form values to the options
			$aio_wp_security_premium->configs->set_value('aiowps_cb_enable_secondary_country_blocking', isset($post_array['aiowps_cb_enable_secondary_country_blocking']) ? '1' : '');
			$aio_wp_security_premium->configs->set_value('aiowps_cb_secondary_blocking_action', sanitize_text_field($post_array['aiowps_cb_secondary_blocking_action']));
			$aio_wp_security_premium->configs->set_value('aiowps_secondary_cb_protected_post_ids', $post_ids);
			$aio_wp_security_premium->configs->set_value('aiowps_cb_secondary_blocked_countries', $blocked_countries);
			$aio_wp_security_premium->configs->set_value('aiowps_cb_secondary_redirect_url', $redirect_url);
			$aio_wp_security_premium->configs->save_config();

			// Recalculate points after the feature status/options have been altered
			// $aiowps_feature_mgr->check_feature_status_and_recalculate_points();
			$aio_wp_security_premium->show_msg_settings_updated();
		}

		$aio_wp_security_premium->include_template('wp-admin/country-blocking/secondary-settings.php', false, array());

	}
	
	/**
	 * Renders the submenu's whitelist tab
	 *
	 * @return void
	 */
	public function render_whitelist() {
		global $aio_wp_security;
		global $aio_wp_security_premium;
		$post_array = $_POST;
		$result = 1;

		if (isset($post_array['aiowps_save_cb_whitelist_settings'])) {
			
			$nonce = isset($post_array['_wpnonce']) ? $post_array['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-cb-whitelist-settings-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
			
			if (!empty($post_array['aiowps_cb_allowed_ip_addresses'])) {
				$ip_list_array = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline(sanitize_textarea_field($post_array['aiowps_cb_allowed_ip_addresses']));
				$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ip_list_array, 'whitelist');
				if (!is_wp_error($validated_ip_list_array)) {
					// success case
					$aio_wp_security_premium->configs->set_value('aiowps_cb_allowed_ip_addresses', implode("\n", $validated_ip_list_array));
					$post_array['aiowps_cb_allowed_ip_addresses'] = ''; // Clear the post variable for the banned address list
				} else {
					$result = -1;
					$this->show_msg_error(nl2br($validated_ip_list_array->get_error_message()));
				}
			} else {
				$aio_wp_security_premium->configs->set_value('aiowps_cb_allowed_ip_addresses', ''); // Clear the IP address config value
			}

			if (1 == $result) {
				$aio_wp_security_premium->configs->set_value('aiowps_cb_enable_whitelisting', isset($post_array['aiowps_cb_enable_whitelisting']) ? '1' : '');
				$aio_wp_security_premium->configs->save_config(); // Save the configuration
				$aio_wp_security_premium->show_msg_settings_updated();
			}
		}

		$aio_wp_security_premium->include_template('wp-admin/country-blocking/render-whitelist.php', false, array('aio_wp_security' => $aio_wp_security, 'result' => $result));

	}
	
	/**
	 * Renders the submenu's advanced settings tab
	 *
	 * @return void
	 */
	public function render_advanced_settings() {
		global $aio_wp_security_premium, $aio_wp_security;
		
		$post_array = $_POST;

		if (isset($post_array['aiowps_save_cb_advanced_settings'])) {
			$nonce = isset($post_array['_wpnonce']) ? $post_array['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-cb-ip-settings-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}
		

			$aio_wp_security_premium->configs->set_value('aiowps_cb_ajax_enabled', isset($post_array['aiowps_cb_ajax_enabled']) ? '1' : '');
			$aio_wp_security_premium->configs->save_config(); // Save the configuration
			$aio_wp_security_premium->show_msg_settings_updated();
		}
	
		$aio_wp_security_premium->include_template('wp-admin/country-blocking/advanced-settings.php', false, array());

	}

	/**
	 * Renders the submenu's login settings tab
	 *
	 * @return void
	 */
	public function render_login_settings() {
		global $aio_wp_security, $aio_wp_security_premium;

		$roles_array = $aio_wp_security_premium->profile_tasks_obj->user_roles;
		
		if (isset($_POST['aiowps_save_cb_login_settings'])) {
			$error = '';

			$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-cb-login-settings-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}

			$redirect_url = isset($_POST['aiowps_cb_login_global_url']) ? esc_url_raw($_POST['aiowps_cb_login_global_url']) : '';
			if ('' == $redirect_url) {
				$error .= __('You entered an incorrect format for the "Global redirect URL" field; it has been set to the default value.', 'all-in-one-wp-security-and-firewall-premium');
				$redirect_url = 'http://127.0.0.1';
			}
			
			// Retrieve allowed roles
			$allowed_roles = array();
			foreach ($_POST as $key => $val) {
				if (preg_match('/^aiowps_user_roles_checkbox/', $key)) {
					$role = sanitize_text_field($val);
					if (array_key_exists($role, $roles_array)) {
						$allowed_roles[] = $role;
					}
				}
			}

			if ($error) {
				$this->show_msg_error(__('Attention', 'all-in-one-wp-security-and-firewall-premium').'<br>'.$error);
			}
			
			$aio_wp_security_premium->configs->set_value('aiowps_cb_login_enabled', isset($_POST['aiowps_cb_login_enabled']) ? '1' : '');
			$aio_wp_security_premium->configs->set_value('aiowps_cb_login_allowed_roles', $allowed_roles);
			$aio_wp_security_premium->configs->set_value('aiowps_cb_login_global_url_enabled', isset($_POST['aiowps_cb_login_global_url_enabled']) ? '1' : '');
			$aio_wp_security_premium->configs->set_value('aiowps_cb_login_global_url', $redirect_url);
			$aio_wp_security_premium->configs->save_config(); // Save the configuration
			$aio_wp_security_premium->show_msg_settings_updated();
		}

		$aio_wp_security_premium->include_template('wp-admin/country-blocking/login-settings.php', false, array());
	}
}//end class
