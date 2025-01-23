<?php

namespace WCPM\Classes;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use stdClass;
use WCPM\Classes\Admin\Environment;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Helpers {

	private static $user_data;


	/**
	 * This function takes any of the input types and sanitizes them automatically.
	 *
	 * @param string $input_type
	 *        The type of input to sanitize. Can be INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
	 *
	 * @param bool   $raw
	 *        Whether to apply raw sanitization or not. Default is false.
	 *
	 * @return mixed
	 */
	public static function get_input_vars( $input_type, $raw = false ) {

		$data = filter_input_array($input_type, FILTER_UNSAFE_RAW);

		return self::generic_sanitization($data, $raw);
	}

	private static function sanitize_string( $string, $raw = false ) {

		if ($raw) {
			return filter_var($string, FILTER_UNSAFE_RAW);
		}

		// Don't use FILTER_SANITIZE_STRING as it is deprecated from PHP 8.x
		// Don't use FILTER_SANITIZE_FULL_SPECIAL_CHARS as it will reformat Umlauts like ä, ö, ü
		// Don't use strip_tags() as it will remove information.
		// https://stackoverflow.com/a/69207369/4688612
		// https://gist.github.com/alewolf/e9235adeaf26dc024bab4f53825ec6da

		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	}

	public static function generic_sanitization( $input, $raw = false ) {

		if (is_array($input)) {
			$sanitized_array = [];

			foreach ($input as $key => $value) {
				$sanitized_array[$key] = self::generic_sanitization($value, $raw);
			}

			return $sanitized_array;
		}

		if (is_object($input)) {
			$sanitized_object = new stdClass();

			foreach ($input as $key => $value) {
				$sanitized_object->$key = self::generic_sanitization($value, $raw);
			}

			return $sanitized_object;
		}

		if (is_string($input)) {
			return self::sanitize_string($input, $raw);
		}

		if (is_bool($input)) {
			// No sanitization needed for boolean values
			return $input;
		}

		// If the input is of any other type (e.g., int, float), no sanitization is needed
		return $input;
	}

	public static function is_allowed_notification_page( $page ) {

		$allowed_pages = [ 'page_wpm', 'index.php', 'dashboard' ];

		foreach ($allowed_pages as $allowed_page) {
			if (strpos($page, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	public static function is_wc_hpos_enabled() {
		return class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function is_orders_page() {
		global $pagenow;

		$_get = self::get_input_vars(INPUT_GET);

		return ( 'edit.php' === $pagenow && isset($_get['post_type']) && 'shop_order' === $_get['post_type'] ) || ( isset($_get['page']) && 'wc-orders' === $_get['page'] );
	}

	// If is single order page return true
	// TODO Check if it works with HPOS enabled
	public static function is_edit_order_page() {
//		global $pagenow;
//
//		$_get = self::get_input_vars(INPUT_GET);
//
//		error_log('current screen id: ' . get_current_screen()->id);

		return 'shop_order' === get_current_screen()->id;

//		return
//			'post.php' === $pagenow
//			&& isset($_get['post'])
//			&& 'shop_order' === get_post_type($_get['post'])
//			&& isset($_get['action'])
//			&& 'edit' === $_get['action'];
	}

	public static function is_email( $email ) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function is_url( $url ) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	public static function clean_product_name_for_output( $name ) {
		return html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	public static function get_e164_formatted_phone_number( $number, $country ) {

		try {
			$phone_util    = PhoneNumberUtil::getInstance();
			$number_parsed = $phone_util->parse($number, $country);
			return $phone_util->format($number_parsed, PhoneNumberFormat::E164);
		} catch (NumberParseException $e) {
			/**
			 * Don't error log the exception. It leads to more confusion than it helps:
			 * https://wordpress.org/support/topic/php-errors-in-version-1-27-0/
			 */
			return $number;
		}
	}


	// https://stackoverflow.com/a/60199374/4688612
	public static function is_iframe() {
		if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && 'iframe' === $_SERVER['HTTP_SEC_FETCH_DEST']) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_percentage( $counter, $denominator ) {
		return $denominator > 0 ? round($counter / $denominator * 100) : 0;
	}

	public static function get_user_country_code( $user ) {

		// If the country code is set on the user, return it
		if (isset($user->billing_country)) {
			return $user->billing_country;
		}

		// Geolocate the user IP and return the country code
		return Geolocation::get_visitor_country();
	}

	public static function is_dashboard() {

		global $pagenow;

		// Don't check for the plugin settings page. Notifications have to be handled there.
		$allowed_pages = [
			'index.php',
			'dashboard',
		];

		foreach ($allowed_pages as $allowed_page) {
			if (strpos($pagenow, $allowed_page) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Trim a settings page input string by removing all whitespace, newlines, tabs, double quotes, single quotes and backticks.
	 *
	 * @return string
	 * @since 1.27.10
	 */
	public static function trim_string( $string ) {

		$string = trim($string);

		// Return the string with all newlines, tabs, double quotes, single quotes and backticks removed. Keep spaces within the string.
		$string = preg_replace('/[\n\r\t"\'`]/', '', $string);

		// Remove all quotes
		$string = str_replace('"', '', $string);

		// Remove html quote entities
		$string = str_replace('&quot;', '', $string);

		// Remove a ; at the end of the string
		return rtrim($string, ';');
	}

	/**
	 * Filter to enable PMW lazy loading.
	 *
	 * @return bool
	 * @since 1.28.0
	 */
	public static function lazy_load_pmw__premium_only() {

		if (
			Options::lazy_load_requirements()
			&& Options::get_options_obj()->general->lazy_load_pmw
		) {
			return true;
		}

		if (apply_filters('experimental_lazy_load_pmw', false)) {
			return true;
		}

		return false;
	}

	/**
	 * Filter to enable PMW web worker with Partytown.
	 *
	 * @return bool
	 * @since 1.28.0
	 */
	public static function partytown__premium_only() {
		return (bool) apply_filters('experimental_pmw_partytown', false);
	}

	/**
	 * Filter to return the Facebook fbevents.js script URL.
	 *
	 * It allows to either return the latest version or a specific version.
	 *
	 * @return string
	 * @since 1.30.0
	 */
	public static function get_facebook_fbevents_js_url() {

		$fbevents_standard_url = 'https://connect.facebook.net/en_US/fbevents.js';

		if (apply_filters('pmw_facebook_fbevents_script_version', '')) {
			return $fbevents_standard_url . '?v=' . apply_filters('pmw_facebook_fbevents_script_version', '');
		}

		if (apply_filters('pmw_facebook_fbevents_script_url', '')) {
			return apply_filters('pmw_facebook_fbevents_script_url', '');
		}

		return $fbevents_standard_url;
	}

	/**
	 * Check if there is a scheduled action for a given hook and a given group.
	 *
	 * @param string $hook
	 * @param string $group
	 *
	 * @return bool
	 *
	 * @since 1.30.8
	 */
	public static function has_scheduled_action( $hook, $group = '' ) {
		return as_has_scheduled_action(
			$hook,
			[],
			$group
		);
	}

	/**
	 * Given a datetime string, return the unix timestamp for the local timezone.
	 *
	 * @param $datetime_string
	 * @return false|string
	 *
	 * @since 1.30.8
	 */
	public static function wp_strtotime_to_unix_timestamp( $datetime_string ) {
		return wp_date('U', strtotime($datetime_string . ' ' . wp_timezone_string()));
	}

	public static function is_admin_page( $page_ids = [] ) {

		// If no page IDs are given, check if the current page is an admin page
		if (empty($page_ids)) {
			return false;
		}

		// Check if the current page is an admin page and if it is one of the given page IDs
//		return is_admin() && in_array(get_current_screen()->id, $page_ids);

		if (
			!isset($_GET['page'])
			|| !in_array($_GET['page'], $page_ids)
		) {
			return false;
		}

		return true;
	}

	public static function hash_string( $string, $algo = 'sha256' ) {

		// If the given algorithm is supported, hash the string
		if (in_array($algo, hash_algos(), true)) {
			return hash($algo, $string);
		}

		return $string;
	}

	public static function get_random_ip() {

		$ip = '';

		// Generate a random IP address
		for ($i = 0; $i < 4; $i++) {
			$ip .= rand(0, 255);
			if ($i < 3) {
				$ip .= '.';
			}
		}

		return $ip;
	}

	/**
	 * We are controlling the entire output in all formats from here. Why?
	 * Because each pixel has different requirements for each data field.
	 * Hashed, not hashed, lower case, not lower case, phone with + or without +,
	 * etc.
	 *
	 * @return array
	 */
	public static function get_user_data( $order = null ) {

		// if the user_data array is already set and not empty, return it
		if (!empty(self::$user_data)) {
			return self::$user_data;
		}

		$data = [];

		$order
			= (
			Environment::is_woocommerce_active()
			&& !$order
			&& Shop::pmw_is_order_received_page()
			&& Shop::pmw_get_current_order()
		)
			? Shop::pmw_get_current_order()
			: $order;


		// If the order is not null get the $current_user from the order
		if (
			$order
			&& $order->get_user()
		) {
			$current_user = $order->get_user();
		} else {
			$current_user = is_user_logged_in() ? wp_get_current_user() : null;
		}

		// If the user is logged in, get the user data
		if ($current_user) {
			$data['id']['raw']    = get_current_user_id();
			$data['id']['sha256'] = self::hash_string($data['id']['raw']);
		}

		/**
		 * Determine the details.
		 *
		 * If logged in use the logged-in user data.
		 * On the order page, override the logged-in user data with the order data.
		 */

		// Email
		$email = is_user_logged_in() && $current_user->user_email ? $current_user->user_email : '';
		$email = $order && $order->get_billing_email() ? $order->get_billing_email() : $email;

		// First name
		$first_name = is_user_logged_in() && $current_user->first_name ? $current_user->first_name : '';
		$first_name = $order && $order->get_billing_first_name() ? $order->get_billing_first_name() : $first_name;

		// Last name
		$last_name = is_user_logged_in() && $current_user->last_name ? $current_user->last_name : '';
		$last_name = $order && $order->get_billing_last_name() ? $order->get_billing_last_name() : $last_name;

		// Phone
		$phone = is_user_logged_in() && $current_user->billing_phone ? $current_user->billing_phone : '';
		$phone = $order && $order->get_billing_phone() ? $order->get_billing_phone() : $phone;

		// City
		$city = is_user_logged_in() && $current_user->billing_city ? $current_user->billing_city : '';
		$city = $order && $order->get_billing_city() ? $order->get_billing_city() : $city;

		// State
		$state = is_user_logged_in() && $current_user->billing_state ? $current_user->billing_state : '';
		$state = $order && $order->get_billing_state() ? $order->get_billing_state() : $state;

		// Postcode
		$postcode = is_user_logged_in() && $current_user->billing_postcode ? $current_user->billing_postcode : '';
		$postcode = $order && $order->get_billing_postcode() ? $order->get_billing_postcode() : $postcode;

		// Country
		$country = is_user_logged_in() && $current_user->billing_country ? $current_user->billing_country : '';
		$country = $order && $order->get_billing_country() ? $order->get_billing_country() : $country;

		// Add the details to the data array and return it
		// Only add the data if it exists
		$data = $email ? self::get_user_object_email($data, $email) : $data;
		$data = $first_name ? self::get_user_object_first_name($data, $first_name) : $data;
		$data = $last_name ? self::get_user_object_last_name($data, $last_name) : $data;
		$data = $phone ? self::get_user_object_phone($data, $phone, $current_user) : $data;
		$data = $city ? self::get_user_object_city($data, $city) : $data;
		$data = $state ? self::get_user_object_state($data, $state) : $data;
		$data = $postcode ? self::get_user_object_postcode($data, $postcode) : $data;
		$data = $country ? self::get_user_object_country($data, $country) : $data;

		self::$user_data = $data;

		return $data;
	}

	public static function get_user_data_object( $order = null ) {
		$data = self::get_user_data($order);
		return json_decode(wp_json_encode($data));
	}

	private static function get_user_object_email( $data, $email ) {

		$email = self::trim_string($email);
		$email = strtolower($email);

		$data['email']['raw']      = $email;
		$data['email']['sha256']   = self::hash_string($email);
		$data['email']['facebook'] = self::hash_string($email);

		return $data;
	}

	private static function get_user_object_first_name( $data, $first_name ) {

		$first_name = self::trim_string($first_name);

		$data['first_name']['raw']       = $first_name;
		$data['first_name']['sha256']    = self::hash_string($first_name);
		$data['first_name']['facebook']  = strtolower($first_name);
		$data['first_name']['pinterest'] = self::hash_string(strtolower($first_name));


		return $data;
	}

	private static function get_user_object_last_name( $data, $last_name ) {

		$last_name = self::trim_string($last_name);

		$data['last_name']['raw']       = $last_name;
		$data['last_name']['sha256']    = self::hash_string($last_name);
		$data['last_name']['facebook']  = strtolower($last_name);
		$data['last_name']['pinterest'] = self::hash_string(strtolower($last_name));

		return $data;
	}

	private static function get_user_object_phone( $data, $phone, $current_user ) {

		$phone = self::trim_string($phone);

		$data['phone']['raw']            = $phone;
		$data['phone']['e164']           = self::get_e164_formatted_phone_number(strtolower($phone), self::get_user_country_code($current_user));
		$data['phone']['sha256']['raw']  = self::hash_string($phone);
		$data['phone']['sha256']['e164'] = self::hash_string($data['phone']['e164']);
		$data['phone']['facebook']       = str_replace('+', '', strtolower($phone));
		$data['phone']['pinterest']      = self::hash_string(preg_replace('/[^0-9]/', '', $data['phone']['e164']));

		return $data;
	}

	private static function get_user_object_city( $data, $city ) {

		$city = self::trim_string($city);

		$data['city']['raw']       = $city;
		$data['city']['sha256']    = self::hash_string($city);
		$data['city']['facebook']  = strtolower($city);
		$data['city']['pinterest'] = self::hash_string(strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $city)));

		return $data;
	}

	private static function get_user_object_state( $data, $state ) {

		$state = self::trim_string($state);

		$data['state']['raw']       = $state;
		$data['state']['sha256']    = self::hash_string($state);
		$data['state']['facebook']  = preg_replace('/[a-zA-Z]{2}-/', '', strtolower($state));
		$data['state']['pinterest'] = self::hash_string(strtolower($state));

		return $data;
	}

	private static function get_user_object_postcode( $data, $postcode ) {

		$postcode = self::trim_string($postcode);

		$data['postcode']['raw']       = $postcode;
		$data['postcode']['facebook']  = strtolower($postcode);
		$data['postcode']['pinterest'] = self::hash_string(preg_replace('/[^0-9]/', '', $postcode));

		return $data;
	}

	private static function get_user_object_country( $data, $country ) {

		$country = self::trim_string($country);

		$data['country']['raw']       = $country;
		$data['country']['facebook']  = strtolower($country);
		$data['country']['pinterest'] = self::hash_string(strtolower($country));

		return $data;
	}

	/**
	 * Filter to define if all s2s requests should be sent blocking
	 *
	 * @return bool
	 */
	public static function should_all_s2s_requests_be_sent_blocking() {
		return (bool) apply_filters('pmw_send_all_s2s_requests_blocking', false);
	}

	public static function format_decimal( $number, $dp = false, $trim_zeros = false ) {

		if (function_exists('wc_format_decimal')) {
			return wc_format_decimal($number, $dp, $trim_zeros);
		} else {
			if (is_string($number)) {

				// Remove all spaces
				$number = str_replace(' ', '', $number);

				// Convert multiple dots to one
				// $number = preg_replace('/\.{2,}/', '.', $number);
				$number = preg_replace('/\.(?![^.]+$)|[^0-9.-]/', '', $number);

				// echo $number . PHP_EOL;

				$number = (float) preg_replace('/[^0-9\.\-]/', '', $number);
			}

			if (!is_float($number)) {
				return false;
			}

			if (false !== $dp) {
				$number = round($number, $dp);
			}

			$number = (string) $number;

			if ($trim_zeros) {
				$number = rtrim($number, '0');
				$number = rtrim($number, '.');
			}

			return $number;
		}
	}

	public static function is_experiment() {
		return defined('EXPERIMENTAL_PMW') && EXPERIMENTAL_PMW;
	}
}
