<?php

namespace WCPM\Classes\Pixels\Google;

use WCPM\Classes\Admin\Environment;
use WCPM\Classes\Helpers;
use WCPM\Classes\Options;
use WCPM\Classes\Shop;
use WCPM\Classes\Http\Google_MP_GA4;
use WCPM\Classes\Http\Google_MP_UA;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Google_Pixel_Manager {

	private $google_pixel;
//	private $google_analytics_ua_http_mp;
	private $google_analytics_4_http_mp;
	private $cid_key_ga_ua;
	private $cid_key_ga4;

	protected $options_obj;

	public function __construct( $options ) {

		$this->google_pixel = new Google($options);
		$this->options_obj  = Options::get_options_obj();

		if (wpm_fs()->can_use_premium_code__premium_only()) {

			/**
			 * Google Measurement Protocol
			 * */

			if (( new Google($options) )->is_google_analytics_active()) {

				/**
				 * Refund hooks
				 * woocommerce_order_status_refunded
				 * woocommerce_order_refunded
				 * woocommerce_order_partially_refunded
				 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L614
				 *
				 * More refund hooks
				 * woocommerce_order_fully_refunded
				 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L616
				 *
				 * how to tell if order is fully refunded
				 * https://github.com/woocommerce/woocommerce/blob/b19500728b4b292562afb65eb3a0c0f50d5859de/includes/wc-order-functions.php#L774
				 */

//				$this->google_analytics_ua_http_mp = new Google_MP_UA($options);
				$this->google_analytics_4_http_mp = new Google_MP_GA4($options);

				$this->cid_key_ga_ua = 'google_cid_' . $this->options_obj->google->analytics->universal->property_id;
				$this->cid_key_ga4   = 'google_cid_' . $this->options_obj->google->analytics->ga4->measurement_id;

				if (Environment::is_woocommerce_active()) {
					// Save the Google cid on the order so that we can use it later when the order gets paid or completed
					// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.403
					add_action('woocommerce_checkout_order_created', [ $this, 'google_analytics_save_cid_on_order__premium_only' ]);

					/**
					 * Process the purchase through the GA Measurement Protocol when they are paid, when they change to processing,
					 * or when they are manually set to completed.
					 * https://docs.woocommerce.com/document/managing-orders/
					 *
					 * Maybe also use woocommerce_pre_payment_complete
					 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.105
					 * */

					add_action('woocommerce_order_status_changed', [ $this, 'wpm_woocommerce_order_status_changed' ], 10, 4);

					add_action('woocommerce_payment_complete', [ $this, 'google_analytics_mp_report_purchase__premium_only' ]);

					foreach ($this->get_order_paid_statuses() as $status) {
						add_action('woocommerce_order_status_' . $status, [ $this, 'google_analytics_mp_report_purchase__premium_only' ]);
					}

					// Process total and partial refunds
					add_action('woocommerce_order_fully_refunded', [ $this, 'google_analytics_mp_send_full_refund__premium_only' ], 10, 2);
					add_action('woocommerce_order_partially_refunded', [ $this, 'google_analytics_mp_send_partial_refund__premium_only' ], 10, 2);

					/**
					 * Process WooCommerce Subscription renewals
					 * https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
					 * */
					add_action('woocommerce_subscription_renewal_payment_complete', [ $this, 'google_analytics_mp_report_subscription_purchase_renewal__premium_only' ], 10, 2);
				}
			}
		}
	}

	public function wpm_woocommerce_order_status_changed( $order_id, $old_status, $new_status, $order ) {

		/**
		 * If admin sends a payment link to a client
		 * we want to set the clients cid
		 */
		if ('on-hold' === $new_status && !is_admin()) {
			$this->google_analytics_save_cid_on_order__premium_only($order);
		}
	}

	// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-order.html#source-view.364
	protected function get_order_paid_statuses() {

		$statuses = wc_get_is_paid_statuses();

		// Add additional custom order statuses to trigger the Measurement Protocol purchase hit
		return apply_filters_deprecated('wpm_register_custom_order_confirmation_statuses', [ $statuses ], '1.30.3', 'Use the woocommerce_order_is_paid_statuses filter instead');
	}

	public function google_analytics_mp_send_partial_refund__premium_only( $order_id, $refund_id ) {

		$order = wc_get_order($order_id);

		// Don't track the order if the user is excluded from tracking
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {
			$this->log_prevented_order_report_for_user($order);
			return;
		}

//		if ($this->google_pixel->is_google_analytics_ua_active()) {
//			$this->google_analytics_ua_http_mp->send_partial_refund_hit($order_id, $refund_id);
//		}
		if ($this->google_pixel->is_google_analytics_4_mp_active()) {
			$this->google_analytics_4_http_mp->send_partial_refund_hit($order_id, $refund_id);
		}
	}

	public function google_analytics_mp_send_full_refund__premium_only( $order_id, $refund_id ) {

		$order = wc_get_order($order_id);

		// Don't track the order if the user is excluded from tracking
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {
			$this->log_prevented_order_report_for_user($order);
			return;
		}

//		if ($this->google_pixel->is_google_analytics_ua_active()) {
//			$this->google_analytics_ua_http_mp->send_full_refund_hit($order_id);
//		}
		if ($this->google_pixel->is_google_analytics_4_mp_active()) {
			$this->google_analytics_4_http_mp->send_full_refund_hit($order_id);
		}
	}

	public function google_analytics_mp_report_subscription_purchase_renewal__premium_only( $subscription, $renewal_order ) {

		if (!self::track_google_analytics_subscription_renewal()) {
			return;
		}

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			return;
		}

		// Don't track the order if the user is excluded from tracking
		if (Shop::do_not_track_user(Shop::get_order_user_id($parent_order))) {
			$this->log_prevented_order_report_for_user($parent_order);
			return;
		}

		// Get cid from parent order
		$cid = $this->google_analytics_4_http_mp->get_cid_from_order($parent_order, $this->cid_key_ga_ua);

//		if ($this->google_pixel->is_google_analytics_ua_active()) {
//			$this->google_analytics_ua_http_mp->send_purchase_hit($renewal_order, $cid);
//		}
		if ($this->google_pixel->is_google_analytics_4_mp_active()) {
			$this->google_analytics_4_http_mp->send_purchase_hit($renewal_order, $cid);
		}
	}

	protected function log_prevented_order_report_for_user( $order ) {

		if (!is_user_logged_in()) {
			return;
		}

		$user_info = get_user_by('id', Shop::get_order_user_id($order));

		if (!is_object($user_info)) {
			return;
		}

		wc_get_logger()->debug(
			'Prevented order ID '
			. $order->get_id()
			. ' to be reported through the Measurement Protocol for user '
			. $user_info->user_login
			. ' (roles: '
			. implode(', ', $user_info->roles)
			. ')', [ 'source' => 'PMW' ]
		);
	}

	public function google_analytics_save_cid_on_order__premium_only( $order ) {

//		if ($this->google_pixel->is_google_analytics_ua_active()) {
//			$this->google_analytics_ua_http_mp->set_wc_session_data_on_order($order, $this->cid_key_ga_ua);
//		}

		if ($this->google_pixel->is_google_analytics_4_mp_active()) {
			$this->google_analytics_4_http_mp->set_wc_session_data_on_order($order, $this->cid_key_ga4);
		}
	}

	public function google_analytics_mp_report_purchase__premium_only( $order_id ) {

		$order = wc_get_order($order_id);

//		error_log('check tracking');
//
//		error_log('order user id: ' . $order->get_user_id());
//		error_log('new order user id: ' . Shop::wpm_get_order_user_id($order));

		// Don't track the order if the user is excluded from tracking
		if (Shop::do_not_track_user(Shop::get_order_user_id($order))) {

			$this->log_prevented_order_report_for_user($order);
			return;
		}

//		error_log('check tracking passed');

		// The Measurement Protocol has only been enabled for EEC
//		if ($this->google_pixel->is_google_analytics_ua_active()) {
//			$this->google_analytics_ua_http_mp->send_purchase_hit($order);
//		}
		if ($this->google_pixel->is_google_analytics_4_mp_active()) {
			$this->google_analytics_4_http_mp->send_purchase_hit($order);
		}
	}

	public function inject_order_received_page_dedupe( $order, $order_total, $is_new_customer ) {
		if (Options::is_google_ads_active() && wpm_fs()->can_use_premium_code__premium_only()) {
			$this->save_gclid_in_order__premium_only($order);
		}
	}

	public function save_gclid_in_order__premium_only( $order ) {

		// https://developer.wordpress.org/reference/functions/metadata_exists/
		if (!$order->meta_exists('_wpm_gclid')) {
			$gclid = $this->get_gclid__premium_only();

			if (null !== $gclid) {
				$order->update_meta_data('_wpm_gclid', $this->get_gclid__premium_only());
				$order->save();
			}
		}
	}

	protected function get_gclid__premium_only() {

		$_cookie = Helpers::get_input_vars(INPUT_COOKIE);
		$_get    = Helpers::get_input_vars(INPUT_GET);

		if (isset($_cookie['_gcl_aw'])) {
			return $this->extract_gclid_from_string__premium_only($_cookie['_gcl_aw']);
		} elseif (is_array($_get) && array_key_exists('gclid', $_get)) {
			return $this->extract_gclid_from_string__premium_only($_get['gclid']);
		} else {
			return '';
		}
	}

	protected function extract_gclid_from_string__premium_only( $string ) {

		$re = '/[\d\w-]{20,120}/m';

		preg_match($re, $string, $matches, PREG_OFFSET_CAPTURE, 0);

		if (isset($matches[0][0])) {
			return $matches[0][0];
		} else {
			return null;
		}
	}

	public function inject_everywhere() {
		// $this->google_pixel->inject_everywhere();
	}

	public function inject_product_category() {
		// all handled on front-end
	}

	public function inject_product_tag() {
		// all handled on front-end
	}

	public function inject_shop_top_page() {
		// all handled on front-end
	}

	public function inject_search() {
		// all handled on front-end
	}

	public function inject_product( $product, $product_attributes ) {
		// handled on front-end
	}

	public function inject_cart( $cart, $cart_total ) {
		// all handled on front-end
	}

	protected function inject_opening_script_tag() {
	}

	protected function inject_closing_script_tag() {
	}

	/**
	 * Disable tracking of subscription renewals in Google Analytics.
	 *
	 * @return bool
	 */
	public static function track_google_analytics_subscription_renewal() {

		// Abort if general subscription renewal tracking is disabled
		if (Shop::do_not_track_subscription_renewal()) {
			return false;
		}

		return apply_filters('pmw_google_analytics_subscription_renewal_tracking', true);
	}
}
