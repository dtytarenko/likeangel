<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Inits the admin dashboard side of things.
 * Main admin file which loads all settings panels and sets up admin menus.
 */
class AIOWPS_Country_Tasks extends AIOWPS_Premium_Base_Tasks {

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * This function performs the country check
	 *
	 * @return void
	 */
	public function perform_country_check() {
		global $aio_wp_security_premium;

		// This is the redirect URL for blocked users
		$redirect_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_redirect_url');

		$current_page_url = AIOWPSecurity_Utility::get_current_page_url();
		
		if ($current_page_url == $redirect_url) {
			$request_uri = $redirect_url;
		} else {
			// $_SERVER['REQUEST_URI'];
			$request_uri = AIOWPS_Premium_Utilities::extract_uri($current_page_url);
		}
		
		$is_blocked = $this->is_blocked();

		if ($is_blocked) {
			if ($request_uri != $redirect_url) {
				// Redirect blocked users to the configured URL
				AIOWPSecurity_Utility::redirect_to_url($redirect_url);
			}
			// Allow the page to load if we have landed on a configured redirection URL
		}
	}

	/**
	 * This function is for the secondary function check
	 *
	 * @param string|int $page_id - id of the page being checked
	 *
	 * @return false|void
	 */
	public function perform_secondary_country_check($page_id) {
		global $aio_wp_security_premium;

		$post_ids = $aio_wp_security_premium->configs->get_value('aiowps_secondary_cb_protected_post_ids');
		$post_id_array = preg_split('/\R/', $post_ids);
		
		if (!in_array($page_id, $post_id_array)) {
			return false;
		}

		$redirect_url = $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_redirect_url');
		
		$current_page_url = AIOWPSecurity_Utility::get_current_page_url();
		
		if ($current_page_url == $redirect_url) {
			$request_uri = $redirect_url;
		} else {
			// $_SERVER['REQUEST_URI'];
			$request_uri = AIOWPS_Premium_Utilities::extract_uri($current_page_url);
		}

		$is_blocked = $this->is_blocked('secondary');
		
		if ($is_blocked) {
			// This country is blocked - do blocking tasks
			if ($request_uri == $redirect_url) {
				// Allow page to load - ie, if we have landed on a configured redirection URL then allow through
			} else {
				// redirect blocked user to configured URL
				AIOWPSecurity_Utility::redirect_to_url($redirect_url);
			}
		}
	}

	/**
	 * Whether the request is blocked or not.
	 *
	 * @param string $block_list - either primary or secondary
	 * @return boolean
	 */
	public function is_blocked($block_list = 'primary') {
		global $aio_wp_security_premium;

		// Print special deubug if required
		if (AIOWPS_CB_ENABLE_SPECIAL_DEBUG == 1) {
			error_log("\n".date_i18n('Y-m-d H:i:s')." - Checking country blocking....\n", 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
			error_log("\n".date_i18n('Y-m-d H:i:s')." \$_SERVER = \n", 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
			error_log(print_r($_SERVER, true), 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
		}

		$is_genuine_search_bot = AIOWPS_Premium_Utilities::is_genuine_search_bot();
		if (true === $is_genuine_search_bot) return false; // do not block google/bing/yahoo bots
		
		$user_ip = AIOWPSecurity_Utility_IP::get_user_ip_address();
		$ip_type = WP_Http::is_ip_address($user_ip);
		if (empty($ip_type)) {
			// $aio_wp_security->debug_logger->log_debug("Country blocking addon - perform_country_check: ".$user_ip." is not a valid IP!",4);
			error_log("perform_country_check: ".$user_ip." is not a valid IP.", 3, AIOWPS_PREMIUM_PATH."/cb_addon.log");
			return true;
		}

		$country_code = $this->get_country_code_from_ip($user_ip);
		if (false === $country_code) {
			return false;
		}

		if ('secondary' == $block_list) {
			$blocked_countries_array = $aio_wp_security_premium->configs->get_value('aiowps_cb_secondary_blocked_countries');
		} else {
			$blocked_countries_array = $aio_wp_security_premium->configs->get_value('aiowps_cb_blocked_countries');
		}

		// Check whitelist
		if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_cb_enable_whitelisting') && AIOWPS_Premium_Utilities::is_whitelisted($user_ip, 'country')) {
			if (1 == AIOWPS_CB_ENABLE_SPECIAL_DEBUG) {
				error_log("\n".date_i18n('Y-m-d H:i:s')." - This IP is whitelisted.\n", 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
			}
			// Visitor IP is whitelisted - allow page to load
			return false;
		} elseif (in_array($country_code, $blocked_countries_array)) {
			if (isset($_GET['troubleshoot_aiowps_cb'])) {
				error_log("\n".date_i18n('Y-m-d H:i:s')." - Blocking IP = ".$user_ip."\n", 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
			}
			// This country is blocked - do blocking tasks
			// Print special deubug if required
			if (AIOWPS_CB_ENABLE_SPECIAL_DEBUG == 1) {
				error_log("\n".date_i18n('Y-m-d H:i:s')." - Blocking IP = ".$user_ip."\n", 3, AIOWPS_PREMIUM_PATH."/cb_addon_test.log");
			}
			return true;
		}
		return false;
	}
}
