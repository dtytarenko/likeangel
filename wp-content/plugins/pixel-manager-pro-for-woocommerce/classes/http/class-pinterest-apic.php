<?php

/**
 * Pinterest API for Conversions
 *
 * Sources:
 * https://developers.pinterest.com/docs/conversions/updated/
 * https://help.pinterest.com/en/business/article/the-pinterest-api-for-conversions
 * https://developers.pinterest.com/docs/api/v5/#operation/events/create
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

class Pinterest_APIC extends S2S {

	protected static $identifiers_key;
	protected static $pinterest_apic_purchase_hit_key;
	protected static $request_url;
	protected static $options;
	protected static $options_obj;
	protected static $post_request_args;
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
			//			'headers'     => [
			//				'Access-Token' => Options::get_pinterest_apic_token(),
			//				'Content-Type' => 'application/json',
			//			],
			// Add a authorization bearer token to the header
			'headers'     => [
				'Authorization' => 'Bearer ' . Options::get_pinterest_apic_token(),
				'Content-Type'  => 'application/json',
			],
			'cookies'     => [],
			'sslverify'   => !Geolocation::is_localhost(),
		];

		self::$identifiers_key                 = 'pinterest_user_identifiers_' . Options::get_pinterest_ad_account_id();
		self::$pinterest_apic_purchase_hit_key = 'pmw_pinterest_apic_purchase_hit';
		self::$pixel_name                      = 'pinterest';

		// https://developers.pinterest.com/docs/api/v5/#operation/events/create
		$api_version       = 'v5';
		self::$request_url = 'https://api.pinterest.com/' . $api_version . '/ad_accounts/' . Options::get_pinterest_ad_account_id() . '/events';

		// For testing
		self::$request_url                   = apply_filters('experimental_pmw_pinterest_apic_request_url', self::$request_url);
		self::$post_request_args['blocking'] = self::should_send_requests_blocking();

		// Process Pinterest events sent through Ajax
		add_action('wp_ajax_pmw_pinterest_apic_event', [ __CLASS__, 'pmw_pinterest_apic_event' ]);
		add_action('wp_ajax_nopriv_pmw_pinterest_apic_event', [ __CLASS__, 'pmw_pinterest_apic_event' ]);

		// Save the Pinterest session identifiers on the order so that we can use them later when the order gets paid or completed
		// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.403
		add_action('woocommerce_checkout_order_created', [ __CLASS__, 'set_identifiers_on_order' ]);

		// Process the purchase through Pinterest APIC when they are paid,
		// or when they are manually completed.
		add_action('woocommerce_order_status_on-hold', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_order_status_processing', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_payment_complete', [ __CLASS__, 'send_purchase_hit_order_id' ]);
		add_action('woocommerce_order_status_completed', [ __CLASS__, 'send_purchase_hit_order_id' ]);
	}

	/**
	 * Function to make the Pinterest APIC event request be sent blocking for more logging.
	 * This is useful for debugging.
	 *
	 * @return bool
	 * @since 1.31.2
	 */
	public static function should_send_requests_blocking() {

		return
			apply_filters('pmw_send_s2s_requests_blocking_for_' . self::$pixel_name, false)
			|| Helpers::should_all_s2s_requests_be_sent_blocking();
	}

	public static function send_purchase_hit_order_id( $order_id ) {
		self::send_purchase_hit(wc_get_order($order_id));
	}

	private static function is_one_pinterest_cookie_set( $pinterest_identifiers ) {
		return (
			isset($pinterest_identifiers['epik'])
			|| isset($pinterest_identifiers['derived_epik'])
			|| isset($pinterest_identifiers['pin_unauth'])
			|| isset($pinterest_identifiers['pinterest_ct_rt'])
			|| isset($pinterest_identifiers['pinterest_ct'])
			|| isset($pinterest_identifiers['pinterest_ct_ua'])
			|| isset($pinterest_identifiers['pinterest_sess'])
		);
	}

	/**
	 * Handle Pinterest purchase hit
	 **/
	public static function send_purchase_hit( $order ) {

		// Don't continue if it's a user that we don't want to track
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {
			return;
		}

		// Don't continue if the purchase hit has already been sent
		if ($order->meta_exists(self::$pinterest_apic_purchase_hit_key)) {
			return;
		}

		$pinterest_identifiers = self::get_identifiers_from_order($order);

		/**
		 * Privacy filter
		 *
		 * If user didn't provide any of the following cookies he probably doesn't want to be tracked -> stop processing
		 * _epik, _derived_epik, _pin_unauth, _pinterest_ct_rt, _pinterest_ct, _pinterest_ct_ua, _pinterest_sess
		 **/
		if (
			!self::is_one_pinterest_cookie_set($pinterest_identifiers)
			&& !Options::is_pinterest_apic_process_anonymous_hits()
		) {
			return;
		}

		if (!isset($pinterest_identifiers['timestamp'])) {
			return;
		}

		// Add event data
		$apic_event_data = [
			'event_name'       => 'checkout',
			'action_source'    => 'web',
			'event_time'       => $pinterest_identifiers['timestamp'],
			//			'advertiser_id'    => (int) Options::get_pinterest_ad_account_id(),
			'event_id'         => (string) $order->get_id(),
			'event_source_url' => (string) $order->get_checkout_order_received_url(),
			'opt_out'          => false,

		];

		// Add user data
		$apic_event_data['user_data'] = self::get_user_data_for_order($pinterest_identifiers, $order);

		// add order data
		$apic_event_data['custom_data'] = [
			'currency'    => (string) $order->get_currency(),
			'value'       => (string) Shop::pmw_get_order_total($order, true),
			// Save the amount of products in num_items
			'num_items'   => (int) $order->get_item_count(),
			// order_id
			'order_id'    => (string) $order->get_id(),
			// Save an array of the product IDs into the product_ids array
			'content_ids' => self::get_product_ids($order),
			// Save into contents an array of the products with their quantity and item_price
			'contents'    => self::get_order_contents_with_quantity_and_item_price($order),
		];

//		error_log('Pinterest APIC hit data: ' . print_r($apic_event_data, true) . PHP_EOL);

//		self::$post_request_args['body'] = wp_json_encode($apic_event_data);

		self::$post_request_args['body'] = wp_json_encode([
			'data' => [ $apic_event_data ],
		]);

		$response = wp_remote_post(self::$request_url, self::$post_request_args);

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data(self::$pinterest_apic_purchase_hit_key, true);
		$order->save();

		self::report_response($response, 'checkout');
	}

	private static function get_order_contents_with_quantity_and_item_price( $order ) {
		$data = [];
		foreach ($order->get_items() as $order_item) {
			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::get_options_obj()->general->variations_output);
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$data[] = [
				'quantity'   => (int) $order_item->get_quantity(),
				'item_price' => (string) Helpers::format_decimal($product->get_price(), 2),
			];
		}
		return $data;
	}

	private static function get_product_ids( $order ) {

		$data = [];

		foreach ($order->get_items() as $order_item) {

			$product_id = Product::get_variation_or_product_id($order_item->get_data(), Options::get_options_obj()->general->variations_output);
			$product    = wc_get_product($product_id);

			// Only add if WC retrieves a valid product
			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$dyn_r_ids           = Product::get_dyn_r_ids($product);
			$product_id_compiled = $dyn_r_ids[Product::get_dyn_r_id_type('pinterest')];


			$data[] = (string) $product_id_compiled;
		}

		return $data;
	}

	public static function send_event_hit( $browser_event_data ) {

		if (!Options::is_pinterest_apic_active()) {
			return;
		}

		if (Shop::do_not_track_user(get_current_user_id())) {
			return;
		}

		/**
		 * Privacy filter
		 *
		 * If user didn't provide any of the following cookies  he probably doesn't want to be tracked -> stop processing.
		 * _pin_unauth, _pinterest_ct_rt, _pinterest_ct, _pinterest_ct_ua, _pinterest_sess,
		 * If one of the cookies are available, continue with minimally required identifiers.
		 * The shop owner can choose to add all available identifiers.
		 *
		 * https://help.pinterest.com/en/business/article/pinterest-tag-parameters-and-cookies
		 **/
		if (
			!isset($browser_event_data['user_data']['trackingPermitted'])
			&& !Options::is_pinterest_apic_process_anonymous_hits()
		) {
			return;
		}

		if (Options::is_pinterest_advanced_matching_active()) {

			$user_ip = Geolocation::get_user_ip();

			if ($user_ip) {
				$browser_event_data['user_data']['client_ip_address'] = $user_ip;
			}
		}

//		$browser_event_data['advertiser_id'] = (int) Options::get_pinterest_ad_account_id();

		self::$post_request_args['body'] = wp_json_encode([
			'data' => [ $browser_event_data ],
		]);

		$response = wp_remote_post(self::$request_url, self::$post_request_args);

		self::report_response($response, $browser_event_data['event_name']);
	}

	protected static function get_user_data_for_order( $pinterest_identifiers, $order ) {

		$user_data_output = [];

		// Required parameters
		if (self::is_one_pinterest_cookie_set($pinterest_identifiers)) {
			$user_data_output['client_ip_address'] = $pinterest_identifiers['ip'];
			$user_data_output['client_user_agent'] = $pinterest_identifiers['user_agent'];
		} else {
			$user_data_output['client_ip_address'] = Helpers::get_random_ip();
			$user_data_output['client_user_agent'] = User_Agent::get_random_user_agent();
		}

		if (isset($pinterest_identifiers['derived_epik'])) {
			$user_data_output['click_id'] = $pinterest_identifiers['derived_epik'];
		}

		// Optional parameters
		if (isset($pinterest_identifiers['epik'])) {
			$user_data_output['click_id'] = $pinterest_identifiers['epik'];
		}

		if (Options::is_pinterest_advanced_matching_active()) {

			$user_data_input = Helpers::get_user_data_object($order);

			if (isset($user_data_input->email->sha256)) {
				$user_data_output['em'] = [ $user_data_input->email->sha256 ];
			}

			if (isset($user_data_input->phone->pinterest)) {
				$user_data_output['ph'] = [ $user_data_input->phone->pinterest ];
			}

			if (isset($user_data_input->first_name->pinterest)) {
				$user_data_output['fn'] = [ $user_data_input->first_name->pinterest ];
			}

			if (isset($user_data_input->last_name->pinterest)) {
				$user_data_output['ln'] = [ $user_data_input->last_name->pinterest ];
			}

			if (isset($user_data_input->city->pinterest)) {
				$user_data_output['ct'] = [ $user_data_input->city->pinterest ];
			}

			if (isset($user_data_input->state->pinterest)) {
				$user_data_output['st'] = [ $user_data_input->state->pinterest ];
			}

			if (isset($user_data_input->zip->pinterest)) {
				$user_data_output['zp'] = [ $user_data_input->zip->pinterest ];
			}

			if (isset($user_data_input->country->pinterest)) {
				$user_data_output['country'] = [ $user_data_input->country->pinterest ];
			}

			if (isset($user_data_input->id->sha256)) {
				$user_data_output['external_id'] = [ $user_data_input->id->sha256 ];
			}
		}

		return $user_data_output;
	}

	protected static function get_identifiers_from_browser() {

		$_server = Helpers::get_input_vars(INPUT_SERVER);
		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

		$pinterest_identifiers = [];

		if (isset($_cookie['_epik']) && self::is_valid_epik($_cookie['_epik'])) {
			$pinterest_identifiers['epik'] = $_cookie['_epik'];
		}

		if (isset($_cookie['_derived_epik']) && self::is_valid_derived_epik($_cookie['_derived_epik'])) {
			$pinterest_identifiers['derived_epik'] = $_cookie['_derived_epik'];
		}

		// Set the _pin_unauth cookie if it exists
		if (isset($_cookie['_pin_unauth']) && self::is_valid_generic_pinterest_cookie($_cookie['_pin_unauth'])) {
			$pinterest_identifiers['pin_unauth'] = $_cookie['_pin_unauth'];
		}

		// Set the _pinterest_ct_rt cookie if it exists
		if (isset($_cookie['_pinterest_ct_rt']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct_rt'])) {
			$pinterest_identifiers['pinterest_ct_rt'] = $_cookie['_pinterest_ct_rt'];
		}

		// Set the _pinterest_ct cookie if it exists
		if (isset($_cookie['_pinterest_ct']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct'])) {
			$pinterest_identifiers['pinterest_ct'] = $_cookie['_pinterest_ct'];
		}

		// Set the _pinterest_ct_ua cookie if it exists
		if (isset($_cookie['_pinterest_ct_ua']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_ct_ua'])) {
			$pinterest_identifiers['pinterest_ct_ua'] = $_cookie['_pinterest_ct_ua'];
		}

		// Set the _pinterest_sess cookie if it exists
		if (isset($_cookie['_pinterest_sess']) && self::is_valid_generic_pinterest_cookie($_cookie['_pinterest_sess'])) {
			$pinterest_identifiers['pinterest_sess'] = $_cookie['_pinterest_sess'];
		}

		if (Geolocation::get_user_ip()) {
			$pinterest_identifiers['ip'] = Geolocation::get_user_ip();
		}

		if (isset($_server['HTTP_USER_AGENT'])) {
			$pinterest_identifiers['user_agent'] = $_server['HTTP_USER_AGENT'];
		}

		return $pinterest_identifiers;
	}

	private static function is_valid_epik( $cookie ) {
		return preg_match('/^dj0yJnU9[a-zA-Z0-9\-\_]{100}/', $cookie);
	}

	private static function is_valid_derived_epik( $cookie ) {
		return preg_match('/^dj0yJnU9[a-zA-Z0-9\-\_]{134}/', $cookie);
	}

	private static function is_valid_generic_pinterest_cookie( $cookie ) {
		return preg_match('/^["a-zA-Z0-9=]*$/', $cookie);
	}

	/**
	 * Process Pinterest APIC event
	 */
	public static function send_pinterest_apic_event( $data ) {

		if (!$data) {
			wp_send_json_error();
		}

		self::send_event_hit($data);
	}

	public static function set_identifiers_on_order( $order ) {

		if (WC()->session->get(self::$identifiers_key)) {    // If the identifiers have been set on the session, get them from the session

			$pinterest_identifiers = WC()->session->get(self::$identifiers_key);

			$pinterest_identifiers['timestamp'] = time();

			$order->update_meta_data(self::$identifiers_key, $pinterest_identifiers);
			$order->save();

		} elseif (!$order->meta_exists(self::$identifiers_key)) { // Only run this if we haven't set a value already

			// Prevent reading out from an iframe
			if (Helpers::is_iframe()) {
				return;
			}

			$_cookie = Helpers::get_input_vars(INPUT_COOKIE);

			if (isset($_cookie['_epik']) || isset($_cookie['_derived_epik'])) {            // If we can get the identifiers from the browser cookies

				$pinterest_identifiers = self::get_identifiers_from_browser();
			}

			$pinterest_identifiers['timestamp'] = gmdate('c');

			$order->update_meta_data(self::$identifiers_key, $pinterest_identifiers);
			$order->save();
		}
	}

	protected static function get_identifiers_from_order( $order ) {

		/**
		 * If a client pays an order
		 * that the admin created in the back-end
		 * and identifiers are set in the browser
		 * then we update the order with the identifiers.
		 */
		if (
			!is_admin() &&
			Shop::is_backend_manual_order($order) &&
			self::get_identifiers_from_browser()
		) {

			$pinterest_identifiers = self::get_identifiers_from_browser();

			self::update_pinterest_identifiers_on_order($order, self::$identifiers_key, $pinterest_identifiers);

			return $pinterest_identifiers;
		}

		$pinterest_identifiers = $order->get_meta(self::$identifiers_key, true);

		if (is_array($pinterest_identifiers)) {
			return $pinterest_identifiers;
		}

		return self::get_random_base_identifiers();
	}

	protected static function update_pinterest_identifiers_on_order( $order, $pinterest_key, $pinterest_identifiers ) {

		$data = $order->get_meta($pinterest_key, true);

		if (isset($pinterest_identifiers['ttp'])) {
			$data['ttp'] = $pinterest_identifiers['ttp'];
		}

		if (isset($pinterest_identifiers['ttclid'])) {
			$data['ttclid'] = $pinterest_identifiers['ttclid'];
		}

		if (isset($pinterest_identifiers['ip'])) {
			$data['ip'] = $pinterest_identifiers['ip'];
		}

		if (isset($pinterest_identifiers['user_agent'])) {
			$data['user_agent'] = $pinterest_identifiers['user_agent'];
		}

		$pinterest_identifiers['timestamp'] = gmdate('c');

		$order->update_meta_data($pinterest_key, $data);
		$order->save();
	}

	protected static function get_random_base_identifiers() {
		return [
			'epik' => self::generate_random_epik(),
		];
	}

	private static function generate_random_epik() {
		// Generate a random string with the length of 100 and contains small letters and numbers
		return 'dj0yJnU9' . self::generate_random_partial_epik_string(100);
	}

	// It can contain small letters, capital letters and numbers
	private static function generate_random_partial_epik_string() {
		return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 100);
	}
}
