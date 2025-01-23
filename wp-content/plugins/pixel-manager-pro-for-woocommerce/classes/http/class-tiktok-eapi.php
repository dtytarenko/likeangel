<?php

/**
 * TikTok Events API
 * https://ads.tiktok.com/marketing_api/docs?id=1739584858677313
 * https://ads.tiktok.com/marketing_api/tools/tools-list/payload-helper/ads-measurement/events-api/events-api-web
 * ttclid: https://ads.tiktok.com/marketing_api/docs?id=1739584860883969
 * _ttp: https://ads.tiktok.com/help/article?aid=10007540
 */

namespace WCPM\Classes\Http;

use WCPM\Classes\Geolocation;
use WCPM\Classes\Helpers;
use WCPM\Classes\Options;
use WCPM\Classes\Shop;
use WCPM\Classes\Product;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class TikTok_EAPI extends S2S {

	protected static $identifiers_key;
	protected static $request_url;
	protected static $options;
	protected static $options_obj;
	protected static $post_request_args;
	protected static $tiktok_eapi_purchase_hit_key;
	protected static $pixel_name;

	private static $instance;

	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		/**
		 * Initialize options
		 */

		self::$options     = Options::get_options();
		self::$options_obj = Options::get_options_obj();

		self::$post_request_args = [
			'body'        => '',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => [
				'Access-Token' => self::$options_obj->tiktok->eapi->token,
				'Content-Type' => 'application/json',
			],
			'cookies'     => [],
			'sslverify'   => !Geolocation::is_localhost(),
		];

		$pixel_id                           = self::$options_obj->tiktok->pixel_id;
		self::$identifiers_key              = 'tiktok_user_identifiers_' . $pixel_id;
		self::$tiktok_eapi_purchase_hit_key = 'pmw_tiktok_eapi_purchase_hit';
		self::$pixel_name                   = 'tiktok';

		// https://ads.tiktok.com/marketing_api/docs?id=1735712062490625
		$api_version       = 'v1.3';
		self::$request_url = 'https://business-api.tiktok.com/open_api/' . $api_version . '/pixel/track/';

		// For testing
		self::$request_url                   = apply_filters('experimental_pmw_tiktok_eapi_request_url', self::$request_url);
		self::$post_request_args['blocking'] = self::should_send_requests_blocking();


		// Process TikTok events sent through Ajax
		add_action('wp_ajax_pmw_tiktok_eapi_event', [ __CLASS__, 'pmw_tiktok_eapi_event' ]);
		add_action('wp_ajax_nopriv_pmw_tiktok_eapi_event', [ __CLASS__, 'pmw_tiktok_eapi_event' ]);

		// Save the TikTok session identifiers on the order so that we can use them later when the order gets paid or completed
		// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.403
		add_action('woocommerce_checkout_order_created', [ __CLASS__, 'set_identifiers_on_order' ]);

		// Process the purchase through TikTok EAPI when they are paid,
		// or when they are manually completed.
		add_action('woocommerce_order_status_on-hold', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_order_status_processing', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_payment_complete', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_order_status_completed', [ __CLASS__, 'send_purchase_hit_order_id' ]);
	}

	public static function send_purchase_hit_order_id( $order_id ) {
		self::send_purchase_hit(wc_get_order($order_id));
	}

	/**
	 * Handle TikTok purchase hit
	 **/
	public static function send_purchase_hit( $order, $ttp = null, $ttclid = null ) {

		// Don't continue if it's a user that we don't want to track
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {
			return;
		}

		// Don't continue if the purchase hit has already been sent
		if ($order->meta_exists(self::$tiktok_eapi_purchase_hit_key)) {
			return;
		}

		$tiktok_identifiers = self::get_identifiers_from_order($order);

		/**
		 * Privacy filter
		 * If user didn't provide ttp or ttclid he probably doesn't want to be tracked -> stop processing
		 * If tpp and/or ttclid are available, continue with minimally required identifiers
		 * The shop owner can choose to add all available identifiers
		 **/
		if (( !isset($tiktok_identifiers['ttp']) && !isset($tiktok_identifiers['ttclid']) ) && !self::process_anonymous_hits()) {
			return;
		}

		// Add event data
		$eapi_event_data = [
			'pixel_code' => self::$options_obj->tiktok->pixel_id,
			'event'      => 'CompletePayment',
			'event_id'   => (string) $order->get_id(),
		];

		if (isset($tiktok_identifiers['timestamp'])) {
			$eapi_event_data['timestamp'] = $tiktok_identifiers['timestamp'];
		} else {
			return;
		}

		if (self::$options_obj->tiktok->eapi->test_event_code) {
			$eapi_event_data['test_event_code'] = self::$options_obj->tiktok->eapi->test_event_code;
		}

		// Add user data
		$eapi_event_data['context'] = self::get_context_for_order($tiktok_identifiers, $order);

		// add order data
		$eapi_event_data['properties'] = [
			'value'    => (float) Shop::pmw_get_order_total($order, true),
			'currency' => (string) $order->get_currency(),
			'contents' => self::get_order_contents($order),
		];

		self::$post_request_args['body'] = wp_json_encode($eapi_event_data);

		$response = wp_remote_post(self::$request_url, self::$post_request_args);

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data(self::$tiktok_eapi_purchase_hit_key, true);
		$order->save();

		self::report_response($response, 'CompletePayment');
	}

	private static function get_order_contents( $order ) {

		$contents = [];

		foreach ($order->get_items() as $order_item) {

			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::get_options_obj()->general->variations_output);
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$dyn_r_ids           = Product::get_dyn_r_ids($product);
			$product_id_compiled = $dyn_r_ids[Product::get_dyn_r_id_type('tiktok')];

			$contents[] = [
				'price'        => (float) Helpers::format_decimal($product->get_price(), 2),
				'quantity'     => (int) $order_item->get_quantity(),
				'content_id'   => (string) $product_id_compiled,
				'content_type' => 'product',
			];
		}

		return $contents;
	}

	public static function send_event_hit( $browser_event_data ) {

		if (!Options::is_tiktok_eapi_enabled()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		/**
		 * Privacy filter
		 * If user didn't provide ttp or ttclid he probably doesn't want to be tracked -> stop processing
		 * If ttp and/or ttclid are available, continue with minimally required identifiers
		 * The shop owner can choose to add all available identifiers
		 **/
		if (( !isset($browser_event_data['context']['user']['ttp']) && !isset($browser_event_data['ad']['callback']['ttclid']) ) && !self::process_anonymous_hits()) {
			return;
		}

		$eapi_event_data = $browser_event_data;

		if (!isset($browser_event_data['context']['user']['ttp']) && !isset($browser_event_data['ad']['callback']['ttclid'])) {
			$eapi_event_data['context']['user']['ttp'] = self::generate_random_ttp();
		}

		$eapi_event_data['pixel_code'] = self::$options_obj->tiktok->pixel_id;

		// Time in ISO 8601 format
		$eapi_event_data['timestamp'] = gmdate('c');

		if (isset($eapi_event_data['context']['user']['sha256_email'])) {
			$eapi_event_data['context']['user']['email'] = $eapi_event_data['context']['user']['sha256_email'];
			unset($eapi_event_data['context']['user']['sha256_email']);
		}

		if (isset($eapi_event_data['context']['user']['sha256_phone_number'])) {
			$eapi_event_data['context']['user']['phone_number'] = $eapi_event_data['context']['user']['sha256_phone_number'];
			unset($eapi_event_data['context']['user']['sha256_phone_number']);
		}

		if (self::is_advanced_matching_active()) {
			if (Geolocation::get_user_ip()) {
				$eapi_event_data['context']['ip'] = Geolocation::get_user_ip();
			}
		}

		if (self::$options_obj->tiktok->eapi->test_event_code) {
			$eapi_event_data['test_event_code'] = self::$options_obj->tiktok->eapi->test_event_code;
		}

		self::$post_request_args['body'] = wp_json_encode($eapi_event_data);

		$response = wp_remote_post(self::$request_url, self::$post_request_args);

		self::report_response($response, $eapi_event_data['event']);
	}

	protected static function process_anonymous_hits() {
		return self::$options_obj->tiktok->eapi->process_anonymous_hits;
	}

	protected static function is_advanced_matching_active() {
		return self::$options_obj->tiktok->advanced_matching;
	}

	protected static function get_context_for_order( $tiktok_identifiers, $order ) {

		$context         = [];
		$context['user'] = [];

		/**
		 * If ttp exists we set all real data
		 * If ttp doesn't exist, we only set required fields with random data
		 * TODO anonymize as per visitor privacy settings
		 * Recommended field
		 */
		if (isset($tiktok_identifiers['ttp'])) {
			$context['user']['ttp'] = $tiktok_identifiers['ttp'];
		}

		// Set ttclid
		if (isset($tiktok_identifiers['ttclid'])) {
			$context['ad']['callback'] = $tiktok_identifiers['ttclid'];
		}

		if (!isset($tiktok_identifiers['ttp']) && !isset($tiktok_identifiers['ttclid'])) {
			$context['user']['ttp'] = self::generate_random_ttp();
		}

		// Get the order received URL and remove the query string from the URL because TikTok throws warnings otherwise.
		$context['page']['url'] = strtok($order->get_checkout_order_received_url(), '?');

		if (isset($tiktok_identifiers['referrer'])) {
			$context['page']['referrer'] = $tiktok_identifiers['referrer'];
		}

		// https://ads.tiktok.com/marketing_api/docs?id=1727541103358977
		// https://ads.tiktok.com/marketing_api/tools/tools-list/payload-helper/ads-measurement/events-api/events-api-web
		if (self::is_advanced_matching_active()) {

			/**
			 * Client IP address
			 * Recommended field
			 */
			if (isset($tiktok_identifiers['ip'])) {
				$context['ip'] = $tiktok_identifiers['ip'];
			}

			/**
			 * User agent
			 * Recommended field
			 */
			if (isset($tiktok_identifiers['user_agent'])) {
				$context['user_agent'] = $tiktok_identifiers['user_agent'];
			}


			// Set the user ID
			if (Shop::get_order_user_id($order) !== 0) {
				$context['user']['external_id'] = hash('sha256', Shop::get_order_user_id($order));
			}

			// Set the user email
			$context['user']['email'] = hash('sha256', trim(strtolower($order->get_billing_email())));

			if ($order->get_billing_phone()) {

				$phone = $order->get_billing_phone();
				$phone = Helpers::get_e164_formatted_phone_number($phone, $order->get_billing_country());
				$phone = hash('sha256', $phone);

				$context['user']['phone_number'] = $phone;
			}

			if (is_user_logged_in()) {

				$wp_user_info = get_userdata(get_current_user_id());

				// set em (email)
				$context['user']['email'] = hash('sha256', trim(strtolower($wp_user_info->user_email)));

				// Set user_id
				// must be sent by the browser simultaneously
				if (get_current_user_id() !== 0) {
					$context['user']['external_id'] = hash('sha256', get_current_user_id());
				}
			}
		}

		return $context;
	}

	protected static function get_identifiers_from_browser() {

		$_server = Helpers::get_input_vars(INPUT_SERVER);
		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		$tiktok_identifiers = [];

		if (isset($_cookie['_ttp']) && self::is_valid_ttp($_cookie['_ttp'])) {
			$tiktok_identifiers['ttp'] = $_cookie['_ttp'];
		}

		if (isset($_cookie['_ttclid']) && self::is_valid_ttclid($_cookie['_ttclid'])) {
			$tiktok_identifiers['ttclid'] = $_cookie['_ttclid'];
		}

		if (Geolocation::get_user_ip()) {
			$tiktok_identifiers['ip'] = Geolocation::get_user_ip();
		}

		if (isset($_server['HTTP_USER_AGENT'])) {
			$tiktok_identifiers['user_agent'] = $_server['HTTP_USER_AGENT'];
		}

		if (isset($_cookie['wpmReferrer'])) {
			$tiktok_identifiers['referrer'] = $_cookie['wpmReferrer'];

			// If the transport protocol is missing, add it
			if (strpos($tiktok_identifiers['referrer'], 'http') === false) {
				$tiktok_identifiers['referrer'] = 'https://' . $tiktok_identifiers['referrer'];
			}
		}

		return $tiktok_identifiers;
	}

	// TODO new regex for ttp
	protected static function is_valid_ttp( $ttp ) {

		$re = '/^[\da-zA-Z-]{20,50}$/';

		// Check if $ttp matches the regex. If yes, return true, else return false
		return preg_match($re, $ttp) === 1;
	}

	// https://ads.tiktok.com/marketing_api/docs?id=1701890980108353
	// TODO new regex for ttclid
	protected static function is_valid_ttclid( $ttclid ) {

		$re = '/^[\da-zA-z-]{5,600}$/';

		return preg_match($re, $ttclid) === 1;
	}

	/**
	 * Process TikTok EAPI event through Ajax
	 * We don't check the nonce because
	 * 1. the nonce could be cached on the frontend
	 * 2. we are only passing through the data to a third party server
	 */
	public static function pmw_tiktok_eapi_event() {

		$_post = Helpers::get_input_vars(INPUT_POST);

		if (!isset($_post['data'])) {
			wp_die();
		}

		$browser_event_data = $_post['data'];

		self::send_event_hit($browser_event_data);

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	/**
	 * Process TikTok EAPI event through REST API
	 */
	public static function send_tiktok_eapi_event( $data ) {

		if (!$data) {
			wp_send_json_error();
		}

		self::send_event_hit($data);
	}

	public static function set_identifiers_on_order( $order ) {

		if (WC()->session->get(self::$identifiers_key)) {    // If the TikTok identifiers have been set on the session, get them from the session

			$tiktok_identifiers = WC()->session->get(self::$identifiers_key);

			// Set time in ISO 8601 format
			$tiktok_identifiers['timestamp'] = gmdate('c');

			$order->update_meta_data(self::$identifiers_key, $tiktok_identifiers);
			$order->save();

		} elseif (!$order->meta_exists(self::$identifiers_key)) { // Only run this if we haven't set a value already

			// Prevent reading out from an iframe
			if (Helpers::is_iframe()) {
				return;
			}

			$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

			if (isset($_cookie['_ttp']) || isset($_cookie['_ttclid'])) {            // If we can get the identifiers from the browser cookies

				$tiktok_identifiers = self::get_identifiers_from_browser();
			}

			$tiktok_identifiers['timestamp'] = gmdate('c');

			$order->update_meta_data(self::$identifiers_key, $tiktok_identifiers);
			$order->save();
		}
	}

	protected static function get_identifiers_from_order( $order ) {

		/**
		 * If a client pays an order
		 * that the admin created in the back-end
		 * and ttp / ttclid are available in the browser
		 * then return the ttp / ttclid from the browser.
		 */
		if (
			!is_admin() &&
			Shop::is_backend_manual_order($order) &&
			self::get_identifiers_from_browser()
		) {

			$tiktok_identifiers = self::get_identifiers_from_browser();

			self::update_tiktok_identifiers_on_order($order, self::$identifiers_key, $tiktok_identifiers);

			return $tiktok_identifiers;
		}

		$tiktok_identifiers = $order->get_meta(self::$identifiers_key, true);

		if (is_array($tiktok_identifiers)) {
			return $tiktok_identifiers;
		}

		return self::get_random_base_identifiers();
	}

	protected static function update_tiktok_identifiers_on_order( $order, $tiktok_key, $tiktok_identifiers ) {

		$data = $order->get_meta($tiktok_key, true);

		if (isset($tiktok_identifiers['ttp'])) {
			$data['ttp'] = $tiktok_identifiers['ttp'];
		}

		if (isset($tiktok_identifiers['ttclid'])) {
			$data['ttclid'] = $tiktok_identifiers['ttclid'];
		}

		if (isset($tiktok_identifiers['ip'])) {
			$data['ip'] = $tiktok_identifiers['ip'];
		}

		if (isset($tiktok_identifiers['user_agent'])) {
			$data['user_agent'] = $tiktok_identifiers['user_agent'];
		}

		$tiktok_identifiers['timestamp'] = gmdate('c');

		$order->update_meta_data($tiktok_key, $data);
		$order->save();
	}

	protected static function get_random_base_identifiers() {
		return [
			'ttp' => self::generate_random_ttp(),
		];
	}

	private static function generate_random_ttp() {
		$random_ttp = [
			self::generate_random_partial_ttp_string(8),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(4),
			self::generate_random_partial_ttp_string(12),
		];

		return implode('-', $random_ttp);
	}

	private static function generate_random_partial_ttp_string( $length ) {
		// Generate a random string with the length of $length and contains small letters and numbers
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		return substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 1, $length);
	}
}
