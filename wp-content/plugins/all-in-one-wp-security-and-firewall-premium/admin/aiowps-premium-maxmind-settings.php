<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPS_Premium_MaxMind_settings {
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter('aiowpsecurity_setting_tabs', array($this, 'aiowpsecurity_setting_tabs'));
	}

	/**
	 * Builds the Tabs that should be displayed
	 *
	 * @param array $tabs - an array containing tabs to be displayed
	 *
	 * @return array Returns all tabs with callback function name
	 */
	public function aiowpsecurity_setting_tabs($tabs = array()) {
		$tabs['integration'] = array(
			'title' => __('Integration', 'all-in-one-wp-security-and-firewall-premium'),
			'render_callback' => array($this, 'render_integration_tab'),
		);
		return $tabs;
	}

	/**
	 * Display the Integration tab & handle the operations
	 */
	public function render_integration_tab() {
		global $aio_wp_security_premium, $aio_wp_security;
		if (isset($_POST['aiowps_save_maxmind_settings'])) {
			$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
			$result = AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, 'aiowpsec-maxmind-key-save-nonce');
			if (is_wp_error($result)) {
				$aio_wp_security->debug_logger->log_debug($result->get_error_message(), 4);
				wp_die($result);
			}

			if (empty($_POST['aiowps_premium_maxmind_key'])) {
				$aio_wp_security_premium->configs->set_value('aiowps_premium_maxmind_key', '');
				$aio_wp_security_premium->configs->save_config();
				$aio_wp_security_premium->show_msg_settings_updated();
			} else {
				// Save the configuration
				$tmp_database_path = $aio_wp_security_premium->aiowps_premium_download_maxmind_database(sanitize_text_field($_POST['aiowps_premium_maxmind_key']));
				if (!is_wp_error($tmp_database_path)) {
					$aio_wp_security_premium->configs->set_value('aiowps_premium_maxmind_key', sanitize_text_field($_POST['aiowps_premium_maxmind_key']));
					$aio_wp_security_premium->configs->save_config();
					$aio_wp_security_premium->show_msg_settings_updated();
				}
			}
			
		}
		
		$aio_wp_security_premium->include_template('wp-admin/settings/maxmind-settings.php', false, array());
	}
}
