<?php

namespace WCPM\Classes\Pixels\Google;

use WCPM\Classes\Admin\Environment;
use WCPM\Classes\Helpers;
use WCPM\Classes\Options;
use WCPM\Classes\Product;
use WCPM\Classes\Pixels\Pixel;
use WCPM\Classes\Shop;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Google extends Pixel {

	private $google_ads_conversion_identifiers;

	public function is_ga4_debug_mode_active() {

		$debug_mode = apply_filters_deprecated('wooptpm_enable_ga_4_mp_event_debug_mode', [ false ], '1.13.0', 'pmw_enable_ga_4_mp_event_debug_mode');
		$debug_mode = apply_filters_deprecated('wpm_enable_ga_4_mp_event_debug_mode', [ $debug_mode ], '1.31.2', 'pmw_enable_ga_4_mp_event_debug_mode');

		return apply_filters('pmw_enable_ga_4_mp_event_debug_mode', $debug_mode);
	}

	public function __construct( $options ) {

		parent::__construct($options);

		$this->google_business_vertical = $this->get_google_business_vertical($this->options['google']['ads']['google_business_vertical']);

		$this->pixel_name = 'google';
	}

	public function get_order_item_data( $order_item ) {

		$product = $order_item->get_product();

		if (Product::is_not_wc_product($product)) {

//			wc_get_logger()->debug('get_order_item_data received an order item which is not a valid product: ' . $order_item->get_id(), ['source' => 'PMW']);
			return [];
		}

		$dyn_r_ids = Product::get_dyn_r_ids($product);

		/**
		 * Get the name of the product.
		 * For Variations, fall back to the name in the parent product
		 * because on some installs the name is not saved in the Variation.
		 */
		if ($product->get_type() === 'variation') {
			$parent_product = wc_get_product($product->get_parent_id());
			$name           = $parent_product->get_name();
			$brand          = Product::get_brand_name($parent_product->get_id());
		} else {
			$name  = $product->get_name();
			$brand = Product::get_brand_name($product->get_id());
		}

		return [
			'id'             => (string) $dyn_r_ids[$this->get_ga_id_type()],
			'name'           => (string) $name,
			'quantity'       => (int) $order_item['quantity'],
			'affiliation'    => (string) get_bloginfo('name'),
			//            'coupon' => '',
			//            'discount' => 0,
			'brand'          => (string) $brand,
			// https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters#pr_ca
			'category'       => implode(',', Product::get_product_category($product->get_id())),
			'category_array' => Product::get_product_category($product->get_id()),
			'variant'        => (string) ( $product->get_type() === 'variation' ) ? Product::get_formatted_variant_text($product) : '',
			//            'tax'      => 0,
			'price'          => (float) $this->wpm_get_order_item_price($order_item),
			//            'list_name' => ,
			//            'currency' => '',
		];
	}

	public function get_ga_id_type() {

		$ga_id_type = 'post_id';

		$ga_id_type = apply_filters_deprecated('wooptpm_product_id_type_for_google_analytics', [ $ga_id_type ], '1.13.0', 'pmw_product_id_type_for_google_analytics');
		$ga_id_type = apply_filters_deprecated('wpm_product_id_type_for_google_analytics', [ $ga_id_type ], '1.31.2', 'pmw_product_id_type_for_google_analytics');

		// Change the output of the product ID type for Google Analytics
		return apply_filters('pmw_product_id_type_for_google_analytics', $ga_id_type);
	}

	public function google_active() {

		if ($this->options_obj->google->analytics->universal->property_id) {
			return true;
		} elseif ($this->options_obj->google->analytics->ga4->measurement_id) {
			return true;
		} elseif ($this->options_obj->google->ads->conversion_id) {
			return true;
		} else {
			return false;
		}
	}

	public function is_google_ads_active() {

		if ($this->options['google']['ads']['conversion_id']) {
			return true;
		} else {
			return false;
		}
	}

	public function is_ga4_data_api_active() {

		if (
			$this->options['google']['analytics']['ga4']['data_api']['property_id']
			&& 0 < count($this->options['google']['analytics']['ga4']['data_api']['credentials'])
		) {
			return true;
		}
		return false;
	}

	public function is_google_analytics_active() {

		if ($this->is_google_analytics_ua_active() || $this->is_google_analytics_4_active()) {
			return true;
		} else {
			return false;
		}
	}

	public function is_google_analytics_ua_active() {

		if ($this->options_obj->google->analytics->universal->property_id) {
			return true;
		} else {
			return false;
		}
	}

	protected function is_google_analytics_4_active() {

		if ($this->options_obj->google->analytics->ga4->measurement_id) {
			return true;
		} else {
			return false;
		}
	}

	public function is_google_analytics_4_mp_active() {

		if ($this->options_obj->google->analytics->ga4->measurement_id && $this->options_obj->google->analytics->ga4->api_secret) {
			return true;
		} else {
			return false;
		}
	}

	public function is_google_optimize_active() {

		if ($this->options_obj->google->optimize->container_id) {
			return true;
		} else {
			return false;
		}
	}

	public function is_google_active() {

		return (
			Options::is_google_ads_active()
			|| Options::is_google_analytics_active()
			|| Options::is_google_optimize_active()
		);
	}

	public function wpm_get_order_item_price( $order_item ) {

		if (Environment::is_woo_discount_rules_active()) {

			$item_value = $order_item->get_meta('_advanced_woo_discount_item_total_discount');

			if (isset($item_value['discounted_price']) && 0 !== $item_value['discounted_price']) {
				return (float) $item_value['discounted_price'];
			} elseif (isset($item_value['initial_price']) && 0 !== $item_value['initial_price']) {
				return (float) $item_value['initial_price'];
			}
		}

		return (float) $order_item->get_order()->get_item_total($order_item, Product::output_product_prices_with_tax());
//		return (float) $product->get_price();
	}

	public function add_categories_to_ga4_product_items( $item_details_array, $categories ) {

		$categories = array_unique($categories);

		// Remove empty categories and reindex the array
		$categories = array_values(array_filter($categories));

		if (count($categories) > 0) {

			$max_categories = 5;

			$item_details_array['item_category'] = $categories[0];

			$max = min(count($categories), $max_categories);

			for ($i = 1; $i < $max; $i++) {
				$item_details_array['item_category' . ( $i + 1 )] = $categories[$i];
			}
		}

		return $item_details_array;
	}

	public function get_google_business_vertical( $id ) {

		$verticals = [
			0 => 'retail',
			1 => 'education',
			2 => 'flights',
			3 => 'hotel_rental',
			4 => 'jobs',
			5 => 'local',
			6 => 'real_estate',
			7 => 'travel',
			8 => 'custom',
		];

		return $verticals[$id];
	}

	public function get_google_ads_conversion_ids() {

		$this->google_ads_conversion_identifiers[$this->options['google']['ads']['conversion_id']] = $this->options['google']['ads']['conversion_label'];

		$this->google_ads_conversion_identifiers = apply_filters_deprecated('wgact_google_ads_conversion_identifiers', [ $this->google_ads_conversion_identifiers ], '1.10.2', 'pmw_google_ads_conversion_identifiers');
		$this->google_ads_conversion_identifiers = apply_filters_deprecated('wooptpm_google_ads_conversion_identifiers', [ $this->google_ads_conversion_identifiers ], '1.13.0', 'pmw_google_ads_conversion_identifiers');
		$this->google_ads_conversion_identifiers = apply_filters_deprecated('wpm_google_ads_conversion_identifiers', [ $this->google_ads_conversion_identifiers ], '1.31.2', 'pmw_google_ads_conversion_identifiers');

		$this->google_ads_conversion_identifiers = apply_filters('pmw_google_ads_conversion_identifiers', $this->google_ads_conversion_identifiers);

		$formatted_conversion_ids = [];

//		if (Environment_Check::get_instance()->is_woocommerce_active() && Shop::wpm_is_order_received_page()) {
//		if (Environment_Check::get_instance()->is_woocommerce_active() && Shop::wpm_is_order_received_page()) {
//			foreach ($this->google_ads_conversion_identifiers as $conversion_id => $conversion_label) {
//				$conversion_id = $this->extract_google_ads_id($conversion_id);
//				if ($conversion_id) {
//					$formatted_conversion_ids['AW-' . $conversion_id] = $conversion_label;
//				}
//			}
//		} else {
//			foreach ($this->google_ads_conversion_identifiers as $conversion_id => $conversion_label) {
//				$conversion_id = $this->extract_google_ads_id($conversion_id);
//				if ($conversion_id) {
//					$formatted_conversion_ids['AW-' . $conversion_id] = '';
//				}
//			}
//		}

		if (Environment::is_woocommerce_active()) {
			foreach ($this->google_ads_conversion_identifiers as $conversion_id => $conversion_label) {
				$conversion_id = $this->extract_google_ads_id($conversion_id);
				if ($conversion_id) {
					$formatted_conversion_ids['AW-' . $conversion_id] = $conversion_label;
				}
			}
		}


		return $formatted_conversion_ids;
	}

	protected function extract_google_ads_id( $string ) {

		$re = '/\d{9,11}/';

		if ($string) {

			preg_match($re, $string, $matches, PREG_OFFSET_CAPTURE, 0);

			if (is_array($matches[0])) {

				return $matches[0][0];
			}
		}

		return '';
	}

	/**
	 * Address (first name, last name, postal code, and country are required).
	 * You can optionally provide street address, city, and region as additional match keys.
	 *
	 * Source: https://support.google.com/google-ads/answer/9888145
	 *
	 * 14.02.2023. Soon hashed values are being supported. Currently in alpha.
	 * https://support.google.com/google-ads/answer/12785474?hl=en-AU&ref_topic=11337914#zippy=%2Cfind-enhanced-conversions-fields-on-your-conversion-page%2Cidentify-and-define-your-enhanced-conversions-fields
	 *
	 * @param $order
	 * @return array
	 */
	public function get_google_ads_enhanced_conversion_data( $order ) {

		$customer_data = [];

		if ($order->get_billing_email()) {

			$email = strtolower($order->get_billing_email());

			$customer_data['sha256_email_address'] = $this->normalize_and_hash_for_enhanced_conversions($email);
		}

		if ($order->get_billing_phone()) {

			$phone_number = Helpers::get_e164_formatted_phone_number((string) $order->get_billing_phone(), (string) $order->get_billing_country());

			$customer_data['sha256_phone_number'] = $this->normalize_and_hash_for_enhanced_conversions($phone_number);
		}

		if ($this->is_shipping_address_set($order)) {

			$billing_address = $this->get_billing_address_details($order);

			if ($this->address_requirements_are_met($billing_address)) {
				$customer_data['address'][] = $billing_address;
			}

			$shipping_address = $this->get_shipping_address_details($order);

			if ($this->address_requirements_are_met($shipping_address)) {
				$customer_data['address'][] = $shipping_address;
			}

		} else {
			$billing_address = $this->get_billing_address_details($order);

			if ($this->address_requirements_are_met($billing_address)) {
				$customer_data['address'] = $billing_address;
			}
		}

		return $customer_data;
	}

	// Address (first name, last name, postal code, and country are required).
	protected function address_requirements_are_met( $billing_address ) {

		$required_keys        = [ 'first_name', 'last_name', 'postal_code', 'country' ];
		$required_keys_sha256 = [ 'sha256_first_name', 'sha256_last_name', 'postal_code', 'country' ];

		// If $billing_address contains all keys in $required_keys or in $required_keys_sha256 return true, else false
		return empty(array_diff($required_keys, array_keys($billing_address))) || empty(array_diff($required_keys_sha256, array_keys($billing_address)));
	}

	protected function get_billing_address_details( $order ) {

		$customer_data = [];

		if ($order->get_billing_first_name()) {

			$fist_name = (string) $order->get_billing_first_name();

			$customer_data['sha256_first_name'] = $this->normalize_and_hash_for_enhanced_conversions($fist_name);
		}
		if ($order->get_billing_last_name()) {

			$last_name = (string) $order->get_billing_last_name();

			$customer_data['sha256_last_name'] = $this->normalize_and_hash_for_enhanced_conversions($last_name);
		}
		if ($order->get_billing_address_1()) {
			$customer_data['street'] = (string) $order->get_billing_address_1();
		}
		if ($order->get_billing_city()) {
			$customer_data['city'] = (string) $order->get_billing_city();
		}
		if ($order->get_billing_state()) {
			$customer_data['region'] = (string) $order->get_billing_state();
		}
		if ($order->get_billing_postcode()) {
			$customer_data['postal_code'] = (string) $order->get_billing_postcode();
		}
		if ($order->get_billing_country()) {
			$customer_data['country'] = (string) $order->get_billing_country();
		}

		return $customer_data;
	}

	protected function get_shipping_address_details( $order ) {

		$customer_data = [];

		if ($order->get_shipping_first_name()) {

			$fist_name = (string) $order->get_shipping_first_name();

			$customer_data['sha256_first_name'] = $this->normalize_and_hash_for_enhanced_conversions($fist_name);
		}
		if ($order->get_shipping_last_name()) {

			$last_name = (string) $order->get_shipping_last_name();

			$customer_data['sha256_last_name'] = $this->normalize_and_hash_for_enhanced_conversions($last_name);
		}
		if ($order->get_shipping_address_1()) {
			$customer_data['street'] = (string) $order->get_shipping_address_1();
		}
		if ($order->get_shipping_city()) {
			$customer_data['city'] = (string) $order->get_shipping_city();
		}
		if ($order->get_shipping_state()) {
			$customer_data['region'] = (string) $order->get_shipping_state();
		}
		if ($order->get_shipping_postcode()) {
			$customer_data['postal_code'] = (string) $order->get_shipping_postcode();
		}
		if ($order->get_shipping_country()) {
			$customer_data['country'] = (string) $order->get_shipping_country();
		}

		return $customer_data;
	}

	private function is_shipping_address_set( $order ) {

		// https://woocommerce.github.io/code-reference/files/woocommerce-includes-admin-meta-boxes-class-wc-meta-box-order-data.html#source-view.446
		if ($order->get_formatted_shipping_address()) {
			return true;
		} else {
			return false;
		}
	}

	public function get_gmc_language() {
		return strtoupper(substr(get_locale(), 0, 2));
	}

	// https://developers.google.com/gtagjs/devguide/linker
	public function get_google_linker_settings() {
		$linker_settings = apply_filters_deprecated('wooptpm_google_cross_domain_linker_settings', [ null ], '1.13.0', 'pmw_google_cross_domain_linker_settings');
		$linker_settings = apply_filters_deprecated('wpm_google_cross_domain_linker_settings', [ $linker_settings ], '1.31.2', 'pmw_google_cross_domain_linker_settings');

		return apply_filters('pmw_google_cross_domain_linker_settings', $linker_settings);
	}

	public function get_google_consent_mode_ads_data_redaction_setting__premium_only() {
		/**
		 * As per Google advise 17.11.2022 (Google Snackable event), don't enable
		 * ads_data_redaction by default while URL passthrough is enabled.
		 */
		$ads_data_redaction = apply_filters_deprecated('wooptpm_google_ads_data_redaction', [ false ], '1.13.0', 'wpm_google_ads_data_redaction');
		$ads_data_redaction = apply_filters_deprecated('wooptpm_google_ads_data_redaction', [ $ads_data_redaction ], '1.31.0', 'pmw_google_ads_data_redaction');
		return (bool) apply_filters('pmw_google_ads_data_redaction', $ads_data_redaction);
	}

	public function get_google_consent_mode_url_passthrough_setting__premium_only() {
		$url_passthrough = apply_filters_deprecated('wooptpm_google_url_passthrough', [ true ], '1.13.0', 'wpm_google_url_passthrough');
		$url_passthrough = apply_filters_deprecated('wpm_google_url_passthrough', [ $url_passthrough ], '1.31.0', 'pmw_google_url_passthrough');
		return (bool) apply_filters('pmw_google_url_passthrough', $url_passthrough);
	}

	public function get_ga4_parameters( $id ) {

		$ga_4_parameters = [];

		$user_id = Shop::get_user_id();

		if (
			$this->options_obj->google->user_id
			&& null !== $user_id
		) {
			$ga_4_parameters['user_id'] = $user_id;
		}

		$ga_4_parameters = apply_filters_deprecated('wooptpm_ga_4_parameters', [ $ga_4_parameters, $id ], '1.13.0', 'pmw_ga_4_parameters');
		$ga_4_parameters = apply_filters_deprecated('wpm_ga_4_parameters', [ $ga_4_parameters, $id ], '1.31.2', 'pmw_ga_4_parameters');

		return apply_filters('pmw_ga_4_parameters', $ga_4_parameters, $id);
	}

	public function get_ga_ua_parameters( $id ) {

		$ga_ua_parameters = [
			'anonymize_ip'     => true, // must be a string for correct output
			'link_attribution' => (bool) $this->options_obj->google->analytics->link_attribution, // must be a string for correct output
		];

		if ($this->options_obj->google->user_id && is_user_logged_in()) {
			$ga_ua_parameters['user_id'] = get_current_user_id();
		}

		$ga_ua_parameters = apply_filters_deprecated('woopt_pm_analytics_parameters', [ $ga_ua_parameters, $id ], '1.10.10', 'pmw_ga_ua_parameters');
		$ga_ua_parameters = apply_filters_deprecated('wooptpm_ga_ua_parameters', [ $ga_ua_parameters, $id ], '1.13.0', 'pmw_ga_ua_parameters');
		$ga_ua_parameters = apply_filters_deprecated('wpm_ga_ua_parameters', [ $ga_ua_parameters, $id ], '1.31.2', 'pmw_ga_ua_parameters');

		return apply_filters('pmw_ga_ua_parameters', $ga_ua_parameters, $id);
	}

	public function get_all_refund_products( $refund ) {

		$data       = [];
		$item_index = 1;

		foreach ($refund->get_items() as $item_id => $item) {

//            $product = new WC_Product($refund_item->get_product_id());

			$order_item_data = $this->get_order_item_data($item);

			$data['pr' . $item_index . 'id'] = $order_item_data['id'];
			$data['pr' . $item_index . 'qt'] = -1 * $order_item_data['quantity'];
			$data['pr' . $item_index . 'pr'] = $order_item_data['price'];

			$item_index++;
		}

		return $data;
	}

	/**
	 * Normalize and hash for enhanced conversions
	 * https://support.google.com/google-ads/answer/12785317
	 *
	 * @param $string
	 *
	 * @return string
	 * @since 1.32.0
	 */
	private function normalize_and_hash_for_enhanced_conversions( $string ) {

		$string = Helpers::trim_string($string);
		$string = strtolower($string);

		return Helpers::hash_string($string);
	}
}
