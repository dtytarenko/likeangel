<?php

namespace WCPM\Classes;

use WCPM\Classes\Admin\Documentation;
use WCPM\Classes\Admin\Environment;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Shop {

	private static $clv_orders_by_billing_email;
	private static $pmw_ist_order_received_page;
	private static $order;

	public static function track_user( $user_id = null ) {

		$user = null;

		if (0 === $user_id) {                                    // If anonymous visitor then track
			return true;
		} elseif ($user_id && 0 <= $user_id) {                   // If user ID is known, get the user
			$user = get_user_by('id', $user_id);
		} elseif (null === $user_id && is_user_logged_in()) {    // If user id is not given, but the user is logged in, get the user
			$user = wp_get_current_user();
		}

		// Find out if the user has a role that is restricted from tracking
		if ($user) {

			foreach ($user->roles as $role) {
				if (in_array($role, Options::get_options_obj()->shop->disable_tracking_for, true)) {
					return false;
				}
			}
		}

		return true;
	}

	public static function do_not_track_user( $user_id = null ) {
		return !self::track_user($user_id);
	}

	public static function get_order_user_id( $order ) {

		if ($order->meta_exists('_wpm_customer_user')) {
			return (int) $order->get_meta('_wpm_customer_user', true);
		} else {
			return (int) $order->get_meta('_customer_user', true);
		}
	}

	/**
	 * Return the filtered order total value that is being used for paid ads order total tracking.
	 * It can output different values depending on the order total tracking type.
	 * And it can be filtered for custom order value calculations.
	 *
	 * The apply_multipliers bool is used to distinguish if the multipliers should be applied or not.
	 * For the browser pixel output on the purchase confirmation page we need the multipliers to be applied.
	 * But, for instance for the customer lifetime value calculation we don't want the multipliers to be applied,
	 * because the CLV is calculated based on all existing and effective orders.
	 *
	 * @return string
	 */
	public static function pmw_get_order_total( $order, $apply_multipliers = false ) {

		$order_total = $order->get_total();

		if (in_array(Options::get_options_obj()->shop->order_total_logic, [ '0', 'order_subtotal' ], true)) {

			// Order subtotal
			$order_total = $order->get_subtotal() - $order->get_total_discount() - self::get_order_fees($order);

		} elseif (in_array(Options::get_options_obj()->shop->order_total_logic, [ '1', 'order_total' ], true)) {

			// Order total
			$order_total = $order->get_total();

		} elseif (in_array(Options::get_options_obj()->shop->order_total_logic, [ '2', 'order_profit_margin' ], true)) {

			// Order profit margin
			$order_total = Profit_Margin::get_order_profit_margin($order);
		}

		// Apply multipliers
		if (wpm_fs()->can_use_premium_code__premium_only()) {
			if ($apply_multipliers) {
				$order_total = self::apply_subscription_multiplier__premium_only($order_total, $order);
			}
		}

		// deprecated filters to adjust the order value
		$order_total = apply_filters_deprecated('wgact_conversion_value_filter', [ $order_total, $order ], '1.10.2', 'pmw_marketing_conversion_value_filter');
		$order_total = apply_filters_deprecated('wooptpm_conversion_value_filter', [ $order_total, $order ], '1.13.0', 'pmw_marketing_conversion_value_filter');
		$order_total = apply_filters_deprecated('wpm_conversion_value_filter', [ $order_total, $order ], '1.31.2', 'pmw_marketing_conversion_value_filter');

		// filter to adjust the order value
		$order_total = apply_filters('pmw_marketing_conversion_value_filter', $order_total, $order);

		return Helpers::format_decimal((float) $order_total, 2);
	}

	public static function is_backend_manual_order( $order ) {

		// Only continue if this is a back-end order
		if ($order->meta_exists('_created_via') && 'admin' === $order->get_meta('_created_via', true)) {
			return true;
		} else {
			return false;
		}
	}

	public static function conversion_pixels_already_fired_html() {
		?>

		<!--	----------------------------------------------------------------------------------------------------
				The conversion pixels have not been fired. Possible reasons:
					- The user role has been disabled for tracking.
					- The order payment has failed.
					- The pixels have already been fired. To prevent double counting, the pixels are only fired once.

				If you want to test the order you have two options:
					- Turn off order duplication prevention in the advanced settings
					- Add the '&nodedupe' parameter to the order confirmation URL like this:
					  https://example.test/checkout/order-received/123/?key=wc_order_123abc&nodedupe

				More info on testing: <?php esc_html_e(Documentation::get_link('test_order')); ?>
				----------------------------------------------------------------------------------------------------
		-->
		<?php
	}

	public static function is_nodedupe_parameter_set() {

		$_get = Helpers::get_input_vars(INPUT_GET);

		if (isset($_get['nodedupe'])) {
			return true;
		} else {
			return false;
		}
	}

	public static function has_conversion_pixel_already_fired( $order ) {

		if (wpm_fs()->can_use_premium_code__premium_only()) {

			if ($order->meta_exists('_wpm_conversion_pixel_fired')) {
				return true;
			}

			if ($order->meta_exists('_wooptpm_conversion_pixel_fired')) {
				return true;
			}

			return false;
		}

		return false;
	}

	public static function is_order_confirmation_allowed_status( $order ) {

		if (
			$order->has_status('failed')
			|| $order->has_status('cancelled')
			|| $order->has_status('refunded')
		) {
			return false;
		}

		return true;
	}

	public static function is_order_confirmation_not_allowed_status( $order ) {
		return !self::is_order_confirmation_allowed_status($order);
	}

	public static function can_order_confirmation_be_processed( $order ) {

		$conversion_prevention = apply_filters_deprecated('wgact_conversion_prevention', [ false, $order ], '1.10.2', 'pmw_conversion_prevention');
		$conversion_prevention = apply_filters_deprecated('wooptpm_conversion_prevention', [ $conversion_prevention, $order ], '1.13.0', 'pmw_conversion_prevention');
		$conversion_prevention = apply_filters_deprecated('wpm_conversion_prevention', [ $conversion_prevention, $order ], '1.31.2', 'pmw_conversion_prevention');

		// If the conversion prevention filter is set to true, the order confirmation will not be processed
		$conversion_prevention = apply_filters('pmw_conversion_prevention', $conversion_prevention, $order);

		// If the order deduplication is deactivated, we can process the order confirmation
		if (self::is_order_deduplication_deactivated()) {
			return true;
		}

		// If order is in failed, cancelled or refunded status, skip the order confirmation
		if (self::is_order_confirmation_not_allowed_status($order)) {
			return false;
		}

		// If this user role is not allowed to be tracked, skip the order confirmation
		if (self::do_not_track_user()) {
			return false;
		}

		// If the conversion prevention filter is set to true, skip the order confirmation
		if ($conversion_prevention) {
			return false;
		}

		// if the conversion pixels have not been fired yet, we can process the order confirmation
		if (self::has_conversion_pixel_already_fired($order) !== true) {
			return true;
		}

		return false;
	}

	public static function is_order_deduplication_deactivated() {

		if (!Options::get_options_obj()->shop->order_deduplication) {
			return true;
		}

		if (self::is_nodedupe_parameter_set()) {
			return true;
		}

		return false;
	}

	public static function is_order_deduplication_enabled() {
		return !self::is_order_deduplication_deactivated();
	}

	public static function is_browser_on_shop() {

		$_server = Helpers::get_input_vars(INPUT_SERVER);

//		error_log(print_r($_server, true));
//		error_log(print_r($_server['HTTP_HOST'], true));
//		error_log('get_site_url(): ' . parse_url(get_site_url(), PHP_URL_HOST));
//		error_log('parse url https://www.exampel.com : ' . parse_url('https://www.exampel.com', PHP_URL_HOST));

		// Servers like Siteground don't seem to always provide $_server['HTTP_HOST']
		// In that case we need to pretend that we're on the same server
		if (!isset($_server['HTTP_HOST'])) {
			return true;
		}

		if (wp_parse_url(get_site_url(), PHP_URL_HOST) === $_server['HTTP_HOST']) {
			return true;
		} else {
			return false;
		}
	}

	public static function was_order_created_while_wpm_premium_was_active( $order ) {
		if ($order->meta_exists('_wpm_premium_active')) {
			return true;
		} else {
			return false;
		}
	}

	public static function was_order_created_while_wpm_was_active( $order ) {
		if ($order->meta_exists('_wpm_process_through_wpm')) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_backend_subscription_renewal_order( $order ) {

		// Only continue if this is a back-end order
		if ($order->meta_exists('_created_via') && 'subscription' === $order->get_meta('_created_via', true)) {
			return true;
		} else {
			return false;
		}
	}

	// https://wordpress.stackexchange.com/a/95440/68337
	// https://wordpress.stackexchange.com/a/31435/68337
	// https://developer.wordpress.org/reference/functions/get_the_title/
	// https://codex.wordpress.org/Data_Validation#Output_Sanitation
	// https://developer.wordpress.org/reference/functions/wp_specialchars_decode/
	public static function pmw_get_the_title( $post = 0 ) {

		$post  = get_post($post);
		$title = isset($post->post_title) ? $post->post_title : '';

		return wp_specialchars_decode($title);
	}

	public static function get_all_order_ids() {

		return wc_get_orders([
			'post_status' => wc_get_is_paid_statuses(),
			'limit'       => -1,
			'return'      => 'ids',
		]);
	}

	public static function get_count_of_all_order_ids() {

		return count(self::get_all_order_ids());
	}

	public static function get_all_order_ids_by_billing_email( $billing_email ) {

		return wc_get_orders([
			'billing_email' => sanitize_email($billing_email),
			'post_status'   => wc_get_is_paid_statuses(),
			'limit'         => -1,
			'return'        => 'ids',
		]);
	}

	public static function get_count_of_order_ids_by_billing_email( $billing_email ) {

		return count(self::get_all_order_ids_by_billing_email($billing_email));
	}

	public static function can_clv_query_be_run( $billing_email ) {

		// Abort if is not a valid email
		if (!Helpers::is_email($billing_email)) {
			return false;
		}

		// Abort if memory_limit is too low
		if (!Environment::is_memory_limit_higher_than('100M')) {
			return false;
		}

		// Abort if customer has too many orders
		$maximum_number_of_orders = apply_filters('pmw_maximum_number_of_orders_for_clv_query', 500);

		// Abort if customer has too many orders
		if (self::get_count_of_order_ids_by_billing_email($billing_email) > $maximum_number_of_orders) {
			return false;
		}

		// Abort if the wc_get_orders query doesn't properly accept the 'billing_email' parameter
		if (self::get_count_of_all_order_ids() === self::get_count_of_order_ids_by_billing_email($billing_email)) {
			return false;
		}

		return true;
	}

	public static function get_all_paid_orders_by_billing_email( $billing_email ) {

		if (self::$clv_orders_by_billing_email) {
			return self::$clv_orders_by_billing_email;
		} else {

			$orders = wc_get_orders([
				'billing_email' => sanitize_email($billing_email),
				'post_status'   => wc_get_is_paid_statuses(),
				'limit'         => -1,
				//										'return'        => 'ids',
			]);

			self::$clv_orders_by_billing_email = $orders;

			return $orders;
		}
	}

	public static function get_clv_value_filtered_by_billing_email( $billing_email ) {

		$orders = self::get_all_paid_orders_by_billing_email($billing_email);

		$value = 0;

		foreach ($orders as $order) {
			$value += (float) self::pmw_get_order_total($order);
		}

		return Helpers::format_decimal($value, 2);
	}

	// https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
	// https://github.com/woocommerce/woocommerce/blob/5d7f6acbcb387f1d51d51305bf949d07fa3c4b08/includes/data-stores/class-wc-customer-data-store.php#L401
	public static function get_clv_order_total_by_billing_email( $billing_email ) {

		$orders = self::get_all_paid_orders_by_billing_email($billing_email);

		$value = 0;

		foreach ($orders as $order) {
			$value += $order->get_total();
		}

		return Helpers::format_decimal($value, 2);
	}

	/**
	 * Don't count in the current order
	 * https://stackoverflow.com/a/46216073/4688612
	 * https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query#description
	 */
	public static function is_existing_customer( $order ) {

		$query_arguments = [
			'return'      => 'ids',
			'exclude'     => [ $order->get_id() ],
			'post_status' => wc_get_is_paid_statuses(),
			'limit'       => 1,
		];

		if (is_user_logged_in()) {
			$current_user                = wp_get_current_user();
			$query_arguments['customer'] = sanitize_email($current_user->user_email);

		} else {
			$query_arguments['billing_email'] = sanitize_email($order->get_billing_email());
		}

		$orders = wc_get_orders($query_arguments);

		return count($orders) > 0;
	}

	public static function is_new_customer( $order ) {
		return !self::is_existing_customer($order);
	}

	public static function woocommerce_3_and_above() {

		global $woocommerce;

		if (version_compare($woocommerce->version, 3.0, '>=')) {
			return true;
		} else {
			return false;
		}
	}

	public static function get_order_currency( $order ) {

		// use the right function to get the currency depending on the WooCommerce version
		return self::woocommerce_3_and_above() ? $order->get_currency() : $order->get_order_currency();
	}

	/**
	 * As security measure always check for the order key and only return the order if there is a match.
	 *
	 * @return bool|\WC_Order|\WC_Order_Refund
	 */
	public static function get_order_from_order_received_page() {

		$_get = Helpers::get_input_vars(INPUT_GET);

		// key is for WooCommerce
		// wcf-key is for CartFlows

		$order_key = null;

		// for CartFlows keys
		if (isset($_get['wcf-key'])) {
			$order_key = $_get['wcf-key'];
		}

		// for WooCommerce keys
		if (isset($_get['key'])) {
			$order_key = $_get['key'];
		}

		if ($order_key) {

			$order_by_order_key  = wc_get_order(wc_get_order_id_by_order_key($order_key));
			$order_by_query_vars = self::get_order_from_query_vars();

			// If there is an $order_by_query_vars, then we can compare the order IDs.
			// If they don't match, then we return null.
			// Otherwise, we return the $order_by_order_key.
			if (
				$order_by_query_vars
				&& $order_by_order_key
			) {
				if ($order_by_order_key->get_id() == $order_by_query_vars->get_id()) {
					return $order_by_order_key;
				}

				return null;
			}

			if ($order_by_order_key) {
				return $order_by_order_key;
			}

			return null;

		} else {

			// get current page, including query string

			if (isset($_SERVER['REQUEST_URI'])) {
				$page = esc_url_raw($_SERVER['REQUEST_URI']);
			} else {
				$page = '';
			}

			wc_get_logger()->debug(
				"WooCommerce couldn't retrieve the order ID from order key in the URL: " . $page,
				[ 'source' => 'PMW' ]
			);

			return false;
		}
	}

	public static function get_order_from_query_vars() {

		global $wp;

		if (!isset($wp->query_vars['order-received'])) {
			return false;
		}

		$order_id = absint($wp->query_vars['order-received']);

		if ($order_id && 0 != $order_id && wc_get_order($order_id)) {

			return wc_get_order($order_id);
		} else {

			wc_get_logger()->debug(
				'WooCommerce couldn\'t retrieve the order ID from $wp->query_vars[\'order-received\']',
				[ 'source' => 'PMW' ]
			);

			wc_get_logger()->debug(
				print_r($wp->query_vars, true),
				[ 'source' => 'PMW' ]
			);

			return false;
		}
	}

	public static function is_valid_order_key_in_url() {

		$_get = Helpers::get_input_vars(INPUT_GET);

		$order_key = null;

		/**
		 * Parameter key is for WooCommerce
		 * Parameter wcf-key is for CartFlows
		 * Parameter ctp_order_key is for StoreApps Custom Thankyou Page
		 */
		if (isset($_get['key'])) {
			$order_key = $_get['key'];    // for WooCommerce
		} elseif (isset($_get['wcf-key'])) {
			$order_key = $_get['wcf-key'];    // for CartFlows
		} elseif (isset($_get['ctp_order_key'])) {
			$order_key = $_get['ctp_order_key'];    // for StoreApps Custom Thankyou Page
		}

		if ($order_key && wc_get_order_id_by_order_key($order_key)) {
			return true;
		} else {
			return false;
		}
	}

	public static function add_parent_category_id( $category, $list_suffix ) {

		if ($category->parent > 0) {

			$parent_category = get_term_by('id', $category->parent, 'product_cat');
			$list_suffix     = '.' . $parent_category->slug . $list_suffix;
			$list_suffix     = self::add_parent_category_id($parent_category, $list_suffix);
		}

		return $list_suffix;
	}

	public static function get_list_id_suffix() {

		$list_suffix = '';

		if (is_product_category()) {
			$category    = get_queried_object();
			$list_suffix = '.' . $category->slug;
			$list_suffix = self::add_parent_category_id($category, $list_suffix);
		} elseif (is_product_tag()) {
			$tag         = get_queried_object();
			$list_suffix = '.' . $tag->slug;
		}

		return $list_suffix;
	}

	public static function add_parent_category_name( $category, $list_suffix ) {

		if ($category->parent > 0) {

			$parent_category = get_term_by('id', $category->parent, 'product_cat');
			$list_suffix     = ' | ' . wp_specialchars_decode($parent_category->name) . $list_suffix;
			$list_suffix     = self::add_parent_category_name($parent_category, $list_suffix);
		}

		return $list_suffix;
	}

	public static function get_list_name_suffix() {

		$list_suffix = '';

		if (is_product_category()) {

			$category    = get_queried_object();
			$list_suffix = ' | ' . wp_specialchars_decode($category->name);
			$list_suffix = self::add_parent_category_name($category, $list_suffix);
		} elseif (is_product_tag()) {
			$tag         = get_queried_object();
			$list_suffix = ' | ' . wp_specialchars_decode($tag->name);
		}

		return $list_suffix;
	}

	/**
	 * Calculate and return the order fees.
	 *
	 * First, add the fees that have been saved to the order using the WooCommerce fees API.
	 * Then add the fees that have been saved by popular payment gateways to the order
	 * using the WooCommerce order meta.
	 * Then provide a filter to allow shop managers to calculate and add their own fees.
	 *
	 * @param $order
	 * @return float
	 */
	public static function get_order_fees( $order ) {

		$order_fees = 0;

		// Add fees that have been saved to the order
		if ($order->get_total_fees()) {
			$order_fees = $order->get_total_fees();
		}

		// Add Stripe fees
		// because Stripe doesn't save the fee on the order fees
		$order_fees += self::get_fee_by_postmeta_key($order, '_stripe_fee');

		// Add _paypal_transaction_fee
		// because PayPal doesn't save the fee on the order fees
		// https://stackoverflow.com/a/56129332/4688612
		$order_fees += self::get_fee_by_postmeta_key($order, '_paypal_transaction_fee');

		// Add ppcp_paypal_fees
		// because PayPal doesn't save the fee on the order fees
		if ($order->meta_exists('_ppcp_paypal_fees')) {
			$ppcp_paypal_fees = $order->get_meta('_ppcp_paypal_fees', true);

			if (!empty($ppcp_paypal_fees['paypal_fee']['value'])) {
				$order_fees += $ppcp_paypal_fees['paypal_fee']['value'];
			}
		}

		return (float) apply_filters('pmw_order_fees', $order_fees, $order);
	}

	/**
	 * Get the fee from the order meta.
	 *
	 * @param $order
	 * @param $postmeta_key
	 * @return float
	 */
	private static function get_fee_by_postmeta_key( $order, $postmeta_key ) {

		$fee = $order->get_meta($postmeta_key, true);

		if (empty($fee)) {
			return 0;
		}

		return (float) $fee;
	}

	/**
	 * Get the order from the order received page.
	 *
	 * Cache the order in a static variable to avoid multiple database queries.
	 *
	 * @return WC_Order|bool
	 */
	public static function pmw_get_current_order() {

		if (self::$order) {
			return self::$order;
		}

		self::$order = self::get_order_from_order_received_page();

		return self::$order;
	}

	/**
	 * PMW uses its own function to check if a visitor is on the order received page.
	 * There are various plugins which modify the checkout workflow and/or the
	 * order received page, which is why we can't only rely on the WooCommerce function.
	 *
	 * @return bool
	 */
	public static function pmw_is_order_received_page() {

		/**
		 * Get cached value if available.
		 *
		 * There are several places in the code where we check if we are on the order received page.
		 * And the function is quite expensive when checking the database, so we cache the result.
		 */
		if (is_bool(self::$pmw_ist_order_received_page)) {
			return self::$pmw_ist_order_received_page;
		}

		// if woocommerce is deactivated return false
		if (!Environment::is_woocommerce_active()) {
			self::$pmw_ist_order_received_page = false;
			return false;
		}

		/**
		 * If a purchase order was created by a shop manager
		 * and the customer is viewing the PO page
		 * don't fire the conversion pixels.
		 * (order key is available in the URL, but
		 * it's not a completed order yet)
		 **/
		if (is_checkout_pay_page()) {
			self::$pmw_ist_order_received_page = false;
			return false;
		}

		// For safety, check if valid order key is in the URL
		self::$pmw_ist_order_received_page = self::is_valid_order_key_in_url();

		return self::$pmw_ist_order_received_page;
	}

	/**
	 * Apply a subscription modifier if one or more subscriptions are in the order.
	 *
	 * @return float|int
	 */
	public static function apply_subscription_multiplier__premium_only( $order_total, $order ) {

		// Return if the multiplier is 1
		if (self::get_subscription_value_multiplier() == 1) {
			return $order_total;
		}

		/**
		 * Find the percentage of the order total that is subscription
		 * and apply the multiplier to that percentage. Then find out
		 * how much the order total would increase by if we added the
		 * subscription percentage to the order total. Then return that
		 * modified order total.
		 *
		 * Doing all of that with relative values makes this function work globally,
		 * no matter if we are processing the order total, subtotal, profit margin, etc.
		 */

		$value_subscription = 0;
		$value_rest         = 0;

		foreach ($order->get_items() as $item) {

			// error log the product type

			$product = $item->get_product();

			if ($product->get_type() == 'subscription') {
				$value_subscription += $item->get_total();
			} else {
				$value_rest += $item->get_total();
			}
		}

		return $order_total * ( ( $value_subscription * self::get_subscription_value_multiplier() + $value_rest ) / ( $value_subscription + $value_rest ) );
	}

	private static function get_subscription_value_multiplier() {
		return Options::get_options_obj()->shop->subscription_value_multiplier;
	}

	public static function is_wcs_renewal_order( $order ) {
		return function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order);
	}


	/**
	 * Disable tracking of subscription renewals.
	 *
	 * @return bool
	 */
	public static function track_subscription_renewal() {
		return apply_filters('pmw_subscription_renewal_tracking', true);
	}

	/**
	 * Check if tracking of subscription renewals disabled.
	 *
	 * @return bool
	 */
	public static function do_not_track_subscription_renewal() {
		return !self::track_subscription_renewal();
	}

	public static function get_user_id() {

		if (is_user_logged_in()) {
			return (string) get_current_user_id();
		}

		// If we're on the purchase confirmation page, we can get the user ID from the order and return it
		if (self::pmw_is_order_received_page()) {
			$order = self::get_order_from_order_received_page();
			if ($order) {
				return (string) $order->get_user_id();
			}
		}

		return null;
	}
}
