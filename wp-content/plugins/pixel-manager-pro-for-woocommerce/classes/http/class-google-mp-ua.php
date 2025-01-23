<?php

namespace WCPM\Classes\Http;

use WC_Order_Refund;
use WCPM\Classes\Pixels\Google\Google;
use WCPM\Classes\Product;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * GA3 Measurement Protocol developer resources
 * https://developers.google.com/analytics/devguides/collection/protocol/v1
 * https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters
 *
 * On initial order completion
 * woocommerce_order_status_completed
 * woocommerce_payment_complete
 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.121
 *
 * Subscriptions
 * https://stackoverflow.com/a/55912713/4688612
 * https://stackoverflow.com/a/42798968/4688612
 *
 *
 * https://developer.wordpress.org/plugins/http-api/
 * https://stackoverflow.com/a/42868240/4688612
 * https://stackoverflow.com/a/31861577/4688612
 * WC session storage: https://stackoverflow.com/a/52422613/4688612¿
 *
 * https://developers.google.com/gtagjs/reference/api#get
 */
class Google_MP_UA extends Google_MP {


	protected $google;

	public function __construct( $options ) {

		parent::__construct($options);

		$this->google = new Google($options);

		$this->logger_context = [ 'source' => 'PMW-Google-MP-GA3' ];

		$this->mp_purchase_hit_key       = 'wpm_google_analytics_ua_mp_purchase_hit';
		$this->mp_full_refund_hit_key    = 'wpm_google_analytics_ua_mp_full_refund_hit';
		$this->mp_partial_refund_hit_key = 'wpm_google_analytics_ua_mp_partial_refund_hit';

		$this->cid_key = 'google_cid_' . $this->options_obj->google->analytics->universal->property_id;

		$server_url             = 'www.google-analytics.com';
		$endpoint               = '/collect';
		$debug                  = $this->use_debug_endpoint ? '/debug' : '';
		$this->server_base_path = 'https://' . $server_url . $debug . $endpoint;
	}

	public function send_purchase_hit( $order, $cid = null ) {

		// only approve, if several conditions are met
		if ($this->approve_purchase_hit_processing($order, $cid, $this->cid_key) === false) {

//			error_log('purchase hit disapproved');
			return;
		}

//		error_log('purchase hit approved');

		$data_hit_type = [
			'v'   => 1,
			't'   => 'pageview',
			'tid' => (string) $this->options_obj->google->analytics->universal->property_id,
			'ni'  => true, // it's a non-interaction hit
		];

		$data_user_identifier = $this->get_user_identifier($order, $cid);

		$order_url = $order->get_checkout_order_received_url();

		$data_page = [
			'dh' => (string) wp_parse_url($order_url, PHP_URL_HOST),
			'dp' => (string) wp_parse_url($order_url, PHP_URL_PATH) . '?' . wp_parse_url($order_url, PHP_URL_QUERY),
			'dt' => 'Order Received',
		];

		if ($this->get_tracking_parameter_from_order($order, $this->cid_key, 'referrer')) {
			$data_page['dr'] = $this->get_tracking_parameter_from_order($order, $this->cid_key, 'referrer');
		}

		if ($this->get_tracking_parameter_from_order($order, $this->cid_key, 'gclid')) {
			$data_page['gclid'] = $this->get_tracking_parameter_from_order($order, $this->cid_key, 'gclid');
		}

		if ($this->get_tracking_parameter_from_order($order, $this->cid_key, 'user_agent')) {
			$data_page['ua'] = $this->get_tracking_parameter_from_order($order, $this->cid_key, 'user_agent');
		}

		$data_transaction = [
			'ti'  => (string) $order->get_order_number(),
			'ta'  => (string) get_bloginfo('name'),                    // Transaction Affiliation
			'tr'  => (float) $order->get_total(),                            // Transaction Revenue
			'tt'  => (float) $order->get_total_tax(),                        // Transaction Tax
			'ts'  => (float) $order->get_shipping_total(),                    // Transaction Shipping
			'tcc' => implode(',', $order->get_coupon_codes()),    // Coupon Code
			'cu'  => (string) $order->get_currency(),
			'pa'  => 'purchase',
		];

		$data_products = $this->get_all_order_products($order);

		$payload = array_merge(
			$data_hit_type,
			$data_user_identifier,
			$data_page,
			$data_transaction,
			$data_products
		);

//        error_log(print_r($payload, true));

		$this->send_hit($this->compile_request_url($payload));

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data($this->mp_purchase_hit_key, true);
		$order->save();
	}

	// https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide#measuring-refunds
	public function send_full_refund_hit( $order_id ) {

		$order = wc_get_order($order_id);

//		error_log('sending full refund');

		// only run, if the hit has not been sent already (check in db)
//		if (get_post_meta($order->get_id(), $this->mp_full_refund_hit_key)) {
//			return;
//		}

		if ($order->meta_exists($this->mp_full_refund_hit_key)) {
			return;
		}

//        error_log('processing Measure Protocol full refund hit');

		$data_hit_type = [
			'v'   => 1,
			't'   => 'event',
			'ec'  => 'Ecommerce',
			'ea'  => 'Refund',
			'tid' => (string) $this->options_obj->google->analytics->universal->property_id,
			'ni'  => true,
			'cid' => $this->get_cid_from_order($order, $this->cid_key),
		];

		$data_transaction = [
			'ti' => (string) $order->get_order_number(),
			'pa' => 'refund',
		];

		$payload = array_merge(
			$data_hit_type,
			$data_transaction
		);

//		error_log('processing full refund');
//		error_log('payload array:');
//		error_log(print_r($payload, true));
//
//
//		error_log('compiled payload url:');
//		error_log($this->compile_request_url($payload));

//		error_log('send full refund');
//		error_log(print_r($payload, true));

		$this->send_hit($this->compile_request_url($payload));

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data($this->mp_full_refund_hit_key, true);
		$order->save();
	}

	public function send_partial_refund_hit( $order_id, $refund_id ) {

		$order  = wc_get_order($order_id);
		$refund = new WC_Order_Refund($refund_id);

		// only run, if the hit has not been sent already (check in db)
		if ($this->has_partial_refund_hit_already_been_sent($order, $refund_id, $this->mp_partial_refund_hit_key)) {
			return;
		}

//        error_log('processing GA UA Measurement Protocol partial refund hit');

		$data_hit_type = [
			'v'   => 1,
			't'   => 'event',
			'ec'  => 'Ecommerce',
			'ea'  => 'Refund',
			'tid' => (string) $this->options_obj->google->analytics->universal->property_id,
			'ni'  => true, // it's a non-interaction hit
			'cid' => $this->get_cid_from_order($order, $this->cid_key),
			'cu'  => $order->get_currency(),
		];

		$data_transaction = [
			'ti' => (string) $order->get_order_number(),
			'pa' => 'refund',
		];

		$data_products = ( new Google($this->options) )->get_all_refund_products($refund);

		$payload = array_merge(
			$data_hit_type,
			$data_transaction,
			$data_products
		);

//		error_log('send partial refund');
//		error_log(print_r($payload, true));

		$this->send_hit($this->compile_request_url($payload));

		// Now we let the server know, that the hit has already been successfully sent.
		$this->save_partial_refund_hit_to_db($order, $refund_id, $this->mp_partial_refund_hit_key);
	}

	protected function compile_request_url( $payload ) {

		// set the locale to avoid issues on a subset of shops
		// https://www.php.net/manual/en/function.http-build-query.php#123906
		setlocale(LC_ALL, 'us_En');

//		error_log('query string');
//		error_log($this->server_base_path . '?' . http_build_query($payload));

		return $this->server_base_path . '?' . http_build_query($payload);
	}


	// https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters#pr_id
	protected function get_all_order_products( $order ) {

		$data       = [];
		$item_index = 1;

		foreach (Product::pmw_get_order_items($order) as $item) {

			$order_item_data = $this->google->get_order_item_data($item);

			$data['pr' . $item_index . 'id'] = $order_item_data['id'];
			$data['pr' . $item_index . 'nm'] = $order_item_data['name'];
			$data['pr' . $item_index . 'va'] = $order_item_data['variant'];
			$data['pr' . $item_index . 'br'] = $order_item_data['brand'];
			$data['pr' . $item_index . 'ca'] = $order_item_data['category'];
			$data['pr' . $item_index . 'qt'] = $order_item_data['quantity'];
			$data['pr' . $item_index . 'pr'] = $order_item_data['price'];

			$item_index++;
		}

		return $data;
	}

	protected function get_user_identifier( $order, $cid = null ) {

		$data = [];

		// We only add this if also user_id tracking has been enabled in the shop.
		// Otherwise, Google can't attribute the hit to the previous measurements.
		if ($this->options_obj->google->user_id && $order->get_user_id()) {
			$data['uid'] = (string) $order->get_user_id();
		}

		if ($cid) {
			// If this is a subscription renewal we take the cid from the original order
			$data['cid'] = $cid;
		} else {
			// We always send a cid. If we were able successfully capture one from the session,
			// we use that one. Otherwise, we send a random cid.
			$data['cid'] = $this->get_cid_from_order($order, $this->cid_key);
		}

		return $data;
	}
}
