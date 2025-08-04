<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Contains static methods.
 */
class AIOWPS_Premium_Utilities {

	/**
	 * Disables caching for the current page.
	 *
	 * @return void
	 */
	public static function do_not_cache() {
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some date in past
		define('DONOTCACHEPAGE', true);
		define('DONOTCACHEDB', true);
		define('DONOTCDN', true);
		define('DONOTCACHEOBJECT', true);
	}

	/**
	 * Extracts the path from a given URL, removing the scheme, host, fragment, and query string.
	 *
	 * @param string $url - The input URL.
	 *
	 * @return string The extracted URI path.
	 */
	public static function extract_uri($url) {
		$url = preg_replace('/^https?:\/\/[^\/]+/i', '', $url); // strip off method and host
		$url = preg_replace('/\#.*$/', '', $url); // strip off fragment
		$url = preg_replace('/\?.*$/', '', $url); // strip off query string
		return $url;
	}

	/**
	 * Checks if a visitor IP address is whitelisted by comparing the visitor IP address to the whitelist addresses.
	 * It will also check if a visitor's address falls inside a whitelisted IP address range.
	 *
	 * @param string $ip_address     - User IP Address.
	 * @param string $whitelist_name - Either country or 404.
	 *
	 * @return boolean TRUE if whitelisted or FALSE if not
	 */
	public static function is_whitelisted($ip_address, $whitelist_name = 'country') {
		if (!in_array($whitelist_name, array('country', '404'))) {
			throw new InvalidArgumentException('The AIOWPS_Premium_Utilities::is_whitelisted() function\'s $whitelist_name parameter can be either country or 404. Input was: '. $whitelist_name);
		}

		global $aio_wp_security_premium;

		if ('country' == $whitelist_name) {
			$config_name = 'aiowps_cb_allowed_ip_addresses';
		} elseif ('404' == $whitelist_name) {
			$config_name = 'smart_404_ip_whitelist';
		}

		$whitelisted_ips = $aio_wp_security_premium->configs->get_value($config_name);

		// TODO: Remove this in future, temporary check for the old method existing so that it does not matter what order the user updates the plugins in
		if (method_exists('AIOWPSecurity_Utility_IP', 'is_ip_whitelisted')) {
			return AIOWPSecurity_Utility_IP::is_ip_whitelisted($ip_address, $whitelisted_ips);
		} else {
			return AIOWPSecurity_Utility_IP::is_userip_whitelisted($whitelisted_ips);
		}
	}
	
	/**
	 * Checks if the incoming request IP is from a genuine search bot
	 * Currently caters for Google, Bing, Yahoo
	 *
	 * @return bool
	 */
	public static function is_genuine_search_bot() {
		$user_agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

		if (preg_match('/Googlebot/i', $user_agent, $matches)) {
			// If user agent says it is googlebot start doing checks
			$ip = AIOWPSecurity_Utility_IP::get_user_ip_address();

			$name = gethostbyaddr($ip); // let's get the internet hostname using the given IP address
			// TODO - maybe add check if gethostbyaddr() fails
			$host_ip = gethostbyname($name); // Reverse lookup - let's get the IP using the name
			if (preg_match('/Googlebot/i', $name, $matches)) {
				if ($host_ip == $ip) {
					// Genuine googlebot
					return true;
				} else {
					// fake googlebot
					return false;
				}
			} else {
				// fake googlebot
				return false;
			}
		} elseif (preg_match('/bingbot/i', $user_agent, $matches)) {
			// If user agent says it is bingbot start doing checks
			$ip = AIOWPSecurity_Utility_IP::get_user_ip_address();
			$name = gethostbyaddr($ip); // let's get the internet hostname using the given IP address
			// TODO - maybe add check if gethostbyaddr() fails
			$host_ip = gethostbyname($name); // Reverse lookup - let's get the IP using the name
			if (preg_match('/msnbot/i', $name, $matches)) {
				if ($host_ip == $ip) {
					// Genuine bingbot
					return true;
				} else {
					// fake bot
					return false;
				}
			} else {
				// fake bot
				return false;
			}

		} elseif (preg_match('/Slurp/i', $user_agent, $matches)) {
			// If user agent says it is yahoobot start doing checks
			$ip = AIOWPSecurity_Utility_IP::get_user_ip_address();
			$name = gethostbyaddr($ip); // let's get the internet hostname using the given IP address
			// TODO - maybe add check if gethostbyaddr() fails
			$host_ip = gethostbyname($name); // Reverse lookup - let's get the IP using the name
			if (preg_match('/yahoo/i', $name, $matches)) {
				if ($host_ip == $ip) {
					// Genuine yahoobot
					return true;
				} else {
					// fake bot
					return false;
				}
			} else {
				// fake bot
				return false;
			}

		} elseif (preg_match('/Facebook/i', $user_agent, $matches)) {
			// If user agent says it is fbbot start doing checks
			$ip = AIOWPSecurity_Utility_IP::get_user_ip_address();
			$name = gethostbyaddr($ip); // let's get the internet hostname using the given IP address
			// TODO - maybe add check if gethostbyaddr() fails
			$host_ip = gethostbyname($name); // Reverse lookup - let's get the IP using the name
			if (preg_match('/facebook/i', $name, $matches)) {
				if ($host_ip == $ip) {
					// Genuine
					return true;
				} else {
					// fake bot
					return false;
				}
			} else {
				// fake bot
				return false;
			}

		}
		return false; // not one of the search bots
	}

	/**
	 * Retrieves all entries from the database where the block reason is '404'.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return array An array containing the 404 blocked entries.
	 */
	public static function get_all_404_blocked() {
		global $wpdb;
		$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . AIOWPSEC_TBL_PERM_BLOCK . " WHERE block_reason=%s", '404'), ARRAY_A);
		return $data;
	}

	/**
	 * Retrieves all events from the database where the type is '404'.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return array An array containing the 404 events.
	 */
	public static function get_all_404_events() {
		global $wpdb;
		$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".AIOWPSEC_TBL_EVENTS." WHERE event_type=%s", '404'), ARRAY_A);
		return $data;
	}

	/**
	 * Retrieves all events from today from the database where the type is '404'.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return array An array containing today's 404 events.
	 */
	public static function get_todays_404_events() {
		global $wpdb;
		$now_date = current_time('mysql');
		$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".AIOWPSEC_TBL_EVENTS." WHERE DATE(event_date) = DATE('".$now_date."') AND event_type=%s", '404'), ARRAY_A);
		return $data;
	}

	/**
	 * Retrieves 404 events from the database for the last N days.
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param int $days - The number of days to look back for 404 events.
	 *
	 * @return array An array containing the 404 events from the last N days.
	 */
	public static function get_last_n_days_404($days) {
		global $wpdb;
		$now_date = current_time('mysql');
		$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".AIOWPSEC_TBL_EVENTS." WHERE event_date >= DATE_ADD('".$now_date."', INTERVAL -%d DAY) AND event_type=%s AND country_code IS NOT NULL", $days, '404'), ARRAY_A);
		return $data;
	}

	/**
	 * Counts the occurrences of blocked countries based on input data.
	 *
	 * @param array $data - An array containing data with 'country_origin' information.
	 *
	 * @return array An associative array with country codes as keys and their occurrence counts as values.
	 */
	public static function count_blocked_countries($data) {
		$country_count = array();
		$countries = array();
		foreach ($data as $item) {
			$countries[] = $item['country_origin'];
		}
		$country_count = array_count_values($countries);
		arsort($country_count);
		return $country_count;
	}

	/**
	 * Counts the occurrences of 404 countries based on input data.
	 *
	 * @param array $data An array containing data with 'country_code' information.
	 *
	 * @return array An associative array with country codes as keys and their occurrence counts as values.
	 */
	public static function count_404_countries($data) {
		$country_count = array();
		$countries = array();
		foreach ($data as $item) {
			if (empty($item['country_code'])) continue;
			$countries[] = $item['country_code'];
		}
		$country_count = array_count_values($countries);
		arsort($country_count);
		return $country_count;
	}

	/**
	 * Counts the occurrences of 404 IPs based on input data.
	 *
	 * @param array $data An array containing data with 'ip_or_host' information.
	 *
	 * @return array An associative array with IPs as keys and their occurrence counts as values.
	 */
	public static function count_404_ips($data) {
		$ip_count = array();
		$ips = array();
		foreach ($data as $item) {
			if (empty($item['ip_or_host'])) continue;
			$ips[] = $item['ip_or_host'];
		}
		$ip_count = array_count_values($ips);
		arsort($ip_count);
		return $ip_count;
	}

	/**
	 * Filters data for the google line chart
	 * Gets top 5 countries with most 404 based on the given DB data
	 *
	 * @param array $data - this is an array of 404 events results from the aiowps_events_table
	 *
	 * @return array
	 */
	public static function count_last_n_days($data) {
		$country_count = array();
		$countries = array();
		foreach ($data as $item) {
			if (empty($item['country_code'])) continue;
			$countries[] = $item['country_code'];
		}
		$country_count = array_count_values($countries);
		arsort($country_count);
		// Now let's get only the top five countries producing most 404 in last 10 days
		$top_5_count = array_slice($country_count, 0, 5, true);


		/*
		 * Array
			(
				[CA] => 10
				[BR] => 6
				[US] => 6
				[JP] => 3
				[SE] => 3
			)
		 */
		// Now let's loop through the full data array and only get the elements for the top 5 countries
		$data_filtered = array();
		foreach ($data as $x) {
			// if(in_array($x['country_code'],$top_5_count)){
			$cc = $x['country_code'];
			if (isset($top_5_count[$cc])) {
				$data_filtered[] = $x;
			}
		}
		return array($top_5_count, $data_filtered);
	}

	/**
	 * Check whether MaxMind db is downloaded using WooCommerce.
	 *
	 * @return boolean True if WooCommerce MaxMind Database exist otherwise false.
	 */
	public static function woocommerce_maxmind_db_exists() {
		if (class_exists('WC_Integration_MaxMind_Database_Service')) {
			$integration_options = get_option('woocommerce_maxmind_geolocation_settings');
			$wc_maxmind_prefix = $integration_options['database_prefix'];
			$wc_maxmind_service = new WC_Integration_MaxMind_Database_Service($wc_maxmind_prefix);
			$wc_maxmind_service_db_path = $wc_maxmind_service->get_database_path();
			if (file_exists($wc_maxmind_service_db_path) && is_readable($wc_maxmind_service_db_path)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Check whether current admin page is Admin Dashboard page or not.
	 *
	 * @return boolean True if Admin Dashboard page, Otherwise false.
	 */
	public static function is_admin_dashboard_page() {
		static $is_admin_dashboard_page;
		if (isset($is_admin_dashboard_page)) {
			return $is_admin_dashboard_page;
		}
		global $pagenow;
		$is_admin_dashboard_page = 'index.php' == $pagenow;
		return $is_admin_dashboard_page;
	}
	
	/**
	 * Check whether current admin page is plugin page or not.
	 *
	 * @return boolean True if Admin Plugin page, Otherwise false.
	 */
	public static function is_plugin_admin_page() {
		static $is_plugin_admin_page;
		if (isset($is_plugin_admin_page)) {
			return $is_plugin_admin_page;
		}
		global $pagenow;
		$is_plugin_admin_page = 'plugins.php' == $pagenow;
		return $is_plugin_admin_page;
	}

	/**
	 * Check whether current admin page is All In One WP Security admin page or not.
	 *
	 * @return boolean True if All In One WP Security admin page, Otherwise false.
	 */
	public static function is_aiowps_admin_page() {
		static $is_aiowps_admin_page;
		if (isset($is_aiowps_admin_page)) {
			return $is_aiowps_admin_page;
		}
		global $pagenow;
		$page = defined('AIOWPSEC_MENU_SLUG_PREFIX') ? AIOWPSEC_MENU_SLUG_PREFIX : 'aiowpsec';
		$is_aiowps_admin_page = ('admin.php' == $pagenow && isset($_GET['page']) && false !== strpos($_GET['page'], $page));
		return $is_aiowps_admin_page;
	}
	
	/**
	 * Check whether current admin page is profile/user edit page or not.
	 *
	 * @return boolean True if profile/user edit page, Otherwise false.
	 */
	public static function is_aiowps_user_profile_page() {
		static $is_aiowps_user_profile_page;
		if (isset($is_aiowps_user_profile_page)) {
			return $is_aiowps_user_profile_page;
		}
		global $pagenow;
		$is_aiowps_user_profile_page = ('user-edit.php' == $pagenow || 'profile.php' == $pagenow);
		return $is_aiowps_user_profile_page;
	}
}
