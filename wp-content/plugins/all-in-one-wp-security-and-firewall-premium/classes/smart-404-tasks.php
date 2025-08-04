<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Process 404 tasks
 */
class AIOWPS_Smart404_Tasks extends AIOWPS_Premium_Base_Tasks {

	public $current_ip;

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct();

		global $aio_wp_security_premium, $aio_wp_security;

		if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_smart_404')) {
			add_filter('aiowps_pre_add_to_permanent_block', array($this, 'add_country_to_db_save'));
		}

		if ('1' == $aio_wp_security->configs->get_value('aiowps_enable_404_logging') || '1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_smart_404')) {
			add_filter('aiowps_filter_event_logger_data', array($this, 'do_404_event_logger_tasks')); // this filter fires just before an event is saved to the DB
		}
	}

	/**
	 * Adds country information to data and saves it to the database if 'country_origin' is empty.
	 *
	 * @param array $data An array containing data to be saved, including 'blocked_ip'.
	 *
	 * @return array The modified data with added 'country_origin' information.
	 */
	public function add_country_to_db_save($data) {
		if (empty($data['country_origin'])) {
			$user_ip = $data['blocked_ip'];
			$ip_type = WP_Http::is_ip_address($data['blocked_ip']);
			if (false === $ip_type) {
				return $data;
			}

			$country_code = $this->get_country_code_from_ip($user_ip);

			// add the country code to the array
			if (false !== $country_code) {
				$data['country_origin'] = $country_code;
			}
			return $data;
		}
		return $data;
	}

	/**
	 * Performs general tasks when a 404 event occurs, including IP checks and potential blocking.
	 *
	 * @return void
	 */
	public function do_smart_404_general_tasks() {
		global $aio_wp_security, $aio_wp_security_premium;

		// TODO - when 404 occurs:
		// 0) Check if 404
		// 1) check IP address and count
		// 2) if count exceeds max amount add to block list
		// Check if 404
		if (is_404() && !is_user_logged_in()) {
			$ip = AIOWPSecurity_Utility_IP::get_user_ip_address(); // Get the IP address of user
			$this->current_ip = $ip;
			// To prevent logging 404 event twice let's check if the aiowps 404 feature is active because that will also log the 404
			$aiowps_enable_404_detection = $aio_wp_security->configs->get_value('aiowps_enable_404_logging');
			if (empty($aiowps_enable_404_detection)) {
				// This means a 404 event has occurred - let's log it!
				AIOWPSecurity_Utility::event_logger('404');
			}
			$is_genuine_search_bot = AIOWPS_Premium_Utilities::is_genuine_search_bot();
			if (true === $is_genuine_search_bot)
				return; // do not block google/bing/yahoo bots

			// If this IP is whitelisted just return
			if ('1' == $aio_wp_security_premium->configs->get_value('enable_smart_404_whitelist') && AIOWPS_Premium_Utilities::is_whitelisted($this->current_ip, '404')) {
				return;
			}

			// Instant 404 blocking checks
			if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_instant_404_string_block')) {
				$url = isset($_SERVER['REQUEST_URI']) ? esc_attr($_SERVER['REQUEST_URI']) : '';
				$url_parts = parse_url($url);
				$blocking_strings = $aio_wp_security_premium->configs->get_value('smart_404_instant_block_strings');
				if (!empty($blocking_strings)) {
					$array_blocking_strings = explode(PHP_EOL, $blocking_strings);
					foreach ($array_blocking_strings as $string) {
						$string = str_replace(array("\n", "\r"), '', $string); // remove any newline chars some setups were adding
						if (empty($string))
							continue;
						if (isset($url_parts['path'])) {
							if (strpos($url_parts['path'], $string) !== false) {
								// add this IP to block list
								AIOWPSecurity_Blocking::add_ip_to_block_list($this->current_ip, '404');
							}
						}
						if (isset($url_parts['query'])) {
							if (strpos($url_parts['query'], $string) !== false) {
								// add this IP to block list
								AIOWPSecurity_Blocking::add_ip_to_block_list($this->current_ip, '404');
							}
						}
					}
				}
			}

			// General 404 blocking checks
			if ('1' == $aio_wp_security_premium->configs->get_value('aiowps_enable_smart_404')) {
				$max_404_attempts = $aio_wp_security_premium->configs->get_value('aiowps_max_404_attempts');
				$count_404 = $this->get_404_count($this->current_ip);
				if ($count_404 >= $max_404_attempts) {
					// add this IP to block list
					AIOWPSecurity_Blocking::add_ip_to_block_list($this->current_ip, '404');
				}
			}
		}
	}

	/**
	 * Performs tasks for the 404 event logger based on provided data.
	 *
	 * @param array $data - An array containing 404 event data.
	 *
	 * @return array The modified 404 event data after performing tasks.
	 */
	public function do_404_event_logger_tasks($data) {
		global $aio_wp_security_premium;
		$user_ip = $data['ip_or_host'];
		$ip_type = WP_Http::is_ip_address($user_ip);
		if (false === $ip_type) {
			return $data;
		}

		$country_code = $this->get_country_code_from_ip($user_ip);

		// add the country code to the array
		if (false !== $country_code) {
			$data['country_code'] = $country_code;
			// Also increment the count for this country in the all-time count setting
			$all_time_404_array = maybe_unserialize($aio_wp_security_premium->configs->get_value('smart_404_all_time_404_count'));
			if (empty($all_time_404_array)) {
				$all_time_404_array = array($country_code => 1);
			} else {
				if (empty($all_time_404_array[$country_code])) {
					$all_time_404_array[$country_code] = 1;
				} else {
					$all_time_404_array[$country_code] = $all_time_404_array[$country_code] + 1;
				}
			}
			$all_time_404_array = maybe_serialize($all_time_404_array);
			$aio_wp_security_premium->configs->set_value('smart_404_all_time_404_count', $all_time_404_array);
			$aio_wp_security_premium->configs->save_config(); // Save it
		}
		return $data;
	}

	/**
	 * Retrieves the count of 404 events for a specific IP address within a specified retry interval.
	 *
	 * @param string $ip_address The IP address for which to retrieve the count.
	 *
	 * @return int The count of 404 events for the specified IP address.
	 */
	public function get_404_count($ip_address) {
		global $wpdb, $aio_wp_security_premium;
		$retry_interval_404 = $aio_wp_security_premium->configs->get_value('aiowps_404_retry_time_period');
		$now = current_time('mysql', true);
		$now_date_time = new DateTime($now);

		$sql = $wpdb->prepare('SELECT COUNT(ID) FROM ' . AIOWPSEC_TBL_EVENTS . ' WHERE event_date + INTERVAL ' . $retry_interval_404 . ' MINUTE > "' . $now_date_time->format('Y-m-d H:i:s') . '" AND event_type=%s AND ' . 'ip_or_host=%s', '404', $ip_address);
		$count_404_events = $wpdb->get_var($sql);
		return $count_404_events;
	}
}
