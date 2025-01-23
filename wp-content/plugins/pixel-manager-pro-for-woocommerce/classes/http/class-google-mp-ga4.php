<?php

namespace WCPM\Classes\Http;

use DateTime;
use WC_Order_Refund;
use WCPM\Classes\Pixels\Google\Google;
use WCPM\Classes\Product;
use WCPM\Classes\Shop;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// On initial order completion
// woocommerce_order_status_completed
// woocommerce_payment_complete
// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.121

// Subscriptions
// https://stackoverflow.com/a/55912713/4688612
// https://stackoverflow.com/a/42798968/4688612


// https://developer.wordpress.org/plugins/http-api/
// https://stackoverflow.com/a/42868240/4688612
// https://stackoverflow.com/a/31861577/4688612
// WC session storage: https://stackoverflow.com/a/52422613/4688612¿

// https://developers.google.com/gtagjs/reference/api#get

class Google_MP_GA4 extends Google_MP {

	protected $google;

	public function __construct( $options ) {

		parent::__construct($options);

		$this->google = new Google($options);

		$this->logger_context = [ 'source' => 'PMW-Google-MP-GA4' ];

		$this->mp_purchase_hit_key       = 'wpm_google_analytics_4_mp_purchase_hit';
		$this->mp_full_refund_hit_key    = 'wpm_google_analytics_4_mp_full_refund_hit';
		$this->mp_partial_refund_hit_key = 'wpm_google_analytics_4_mp_partial_refund_hit';

		$measurement_id = $this->options_obj->google->analytics->ga4->measurement_id;

		$this->cid_key = 'google_cid_' . $measurement_id;

		$server_url = 'www.google-analytics.com';
		$endpoint   = '/mp/collect';
		$api_secret = $this->options_obj->google->analytics->ga4->api_secret;

		$debug                  = $this->use_debug_endpoint ? '/debug' : '';
		$this->server_base_path = 'https://' . $server_url . $debug . $endpoint . '?measurement_id=' . $measurement_id . '&api_secret=' . $api_secret;
	}

	// We pass the $order and the $cid
	// The $cid is only necessary if it is a subscription renewal order
	// https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference/events#purchase
	public function send_purchase_hit( $order, $cid = null ) {

//        error_log('processing GA4 Measurement Protocol purchase hit');

		/**
		 * Only run, if the hit has not been sent already (check in db)
		 * Also run it on subscription renewals,
		 * but not on orders before premium activation (orders missing a cid)
		 */

		if ($this->approve_purchase_hit_processing($order, $cid, $this->cid_key) === false) {
			return;
		}

		$payload = [
			'client_id'            => $this->get_cid_from_order($order, $this->cid_key),
			'non_personalized_ads' => false,
			'events'               => [
				'name'   => 'purchase',
				'params' => [
					'transaction_id' => (string) $order->get_order_number(),
					'value'          => (float) $order->get_total(),
					'currency'       => (string) $order->get_currency(),
					'tax'            => (float) $order->get_total_tax(),
					'shipping'       => (float) $order->get_shipping_total(),
					'affiliation'    => (string) get_bloginfo('name'),
					'coupon'         => implode(',', $order->get_coupon_codes()),
					'items'          => (array) $this->get_all_order_products($order),
					'page_location'  => (string) $order->get_checkout_order_received_url(),
				],
			],
		];

		// https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag#recommended_parameters_for_reports

		$ga4_session_id = (int) $this->get_ga4_session_id_from_order($order, $this->cid_key);

		// If we have a session id, add it to the payload
		if ($ga4_session_id) {

			$payload['events']['params']['session_id'] = $ga4_session_id;

			// TODO test sending engagement_time_msec as additional parameter to display the purchase in the standard reports
			// https://developers.google.com/analytics/devguides/collection/protocol/ga4/sending-events?client_type=gtag#recommended_parameters_for_reports
			$payload['events']['params']['engagement_time_msec'] = 100;
		}

		$user_id = Shop::get_user_id();

		if (
			$this->options_obj->google->user_id
			&& null !== $user_id
		) {
			$payload['user_id'] = $user_id;
		}

		// Add timestamp_micros if the order was created in the past. In microseconds.
		// https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference?client_type=gtag#payload_post_body
//		if ($this->is_order_older_than_two_minutes($order)) {
//			$payload['timestamp_micros'] = (int) $order->get_date_created()->getTimestamp() * 1000000;
//
//			// Plus one second to make sure the timestamp is in the past
//			$payload['timestamp_micros'] += 1000000;
//		}

		// Add timestamp_micros in microseconds.
		// https://developers.google.com/analytics/devguides/collection/protocol/ga4/reference?client_type=gtag#payload_post_body
		$payload['timestamp_micros'] = (int) $order->get_date_created()->getTimestamp() * 1000000;

		if ($this->google->is_ga4_debug_mode_active()) {
			error_log('GA4 event debug mode enabled');

			$payload['events']['params']['debug_mode'] = true;
		}

		$this->send_hit($this->server_base_path, $payload);

		// Now we let the server know, that the hit has already been successfully sent.
		$order->update_meta_data($this->mp_purchase_hit_key, true);
		$order->save();
	}

	// Check if the order is older than two minutes, return true if it is
	private function is_order_older_than_two_minutes( $order ) {

		$now        = new DateTime();
		$order_date = $order->get_date_created();

		$diff = $now->diff($order_date);

		// If the order is older than two minutes, return true
		if ($diff->i >= 2) {
			return true;
		}

		return false;
	}

	public function send_full_refund_hit( $order_id ) {
		$order = wc_get_order($order_id);

		// only run, if the hit has not been sent already (check in db)
//		if (get_post_meta($order->get_id(), $this->mp_full_refund_hit_key)) {
//			return;
//		}

		if ($order->meta_exists($this->mp_full_refund_hit_key)) {
			return;
		}

//        error_log('processing Measure Protocol full refund hit');

		$payload = [
			'client_id' => (string) $this->get_cid_from_order($order, $this->cid_key),
			'events'    => [
				'name'   => 'refund',
				'params' => [
					'transaction_id' => (string) $order->get_order_number(),
				],
			],
		];

		if ($this->google->is_ga4_debug_mode_active()) {
//            error_log('event debug mode enabled');
			$payload['events']['params']['debug_mode'] = true;
		}

//        error_log(print_r($payload, true));

		$this->send_hit($this->server_base_path, $payload);

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

		$payload = [
			'client_id' => $this->get_cid_from_order($order, $this->cid_key),
			'events'    => [
				'name'   => 'refund',
				'params' => [
					'transaction_id' => (string) $order->get_order_number(),
					'currency'       => (string) $order->get_currency(),
					'items'          => (array) $this->get_all_order_products($refund),
				],
			],
		];

		if ($this->google->is_ga4_debug_mode_active()) {
//            error_log('event debug mode enabled');
			$payload['events']['params']['debug_mode'] = true;
		}

//        error_log(print_r($payload, true));

		$this->send_hit($this->server_base_path, $payload);

		// Now we let the server know, that the hit has already been successfully sent.

		$this->save_partial_refund_hit_to_db($order, $refund_id, $this->mp_partial_refund_hit_key);
	}


	protected function get_all_order_products( $order ) {

		$items = [];

		foreach (Product::pmw_get_order_items($order) as $item_id => $item) {

			$order_item_data = $this->google->get_order_item_data($item);

			$item_details = [
				'item_id'      => $order_item_data['id'],
				'item_name'    => $order_item_data['name'],
				//                'coupon'        => '',
				//                'discount'      => '',
				//                'affiliation'   => '',
				'item_brand'   => $order_item_data['brand'],
				'item_variant' => $order_item_data['variant'],
				'price'        => $order_item_data['price'],
				//                'currency'      => '',
				'quantity'     => $order_item_data['quantity'],
			];

			$item_details = $this->google->add_categories_to_ga4_product_items($item_details, $order_item_data['category_array']);

			$items[] = $item_details;
		}

		return $items;
	}
}
