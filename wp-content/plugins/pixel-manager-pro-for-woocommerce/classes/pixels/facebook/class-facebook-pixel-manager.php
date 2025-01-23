<?php

namespace WCPM\Classes\Pixels\Facebook;

use WCPM\Classes\Http\Facebook_CAPI;
use WCPM\Classes\Shop;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Facebook_Pixel_Manager {

	protected $facebook_capi;

	public function __construct( $options ) {

		if (wpm_fs()->can_use_premium_code__premium_only() && $options['facebook']['capi']['token']) {

			$this->facebook_capi = new Facebook_CAPI($options);

			// Save the Facebook session identifiers on the order so that we can use them later when the order gets paid or completed
			// https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-checkout.html#source-view.403
			add_action('woocommerce_checkout_order_created', [ $this, 'facebook_save_session_identifiers_on_order__premium_only' ]);

			// Process the purchase through Facebook CAPI when they are paid,
			// or when they are manually completed.

			add_action('woocommerce_order_status_on-hold', [ $this, 'facebook_capi_report_purchase__premium_only' ]);
			add_action('woocommerce_order_status_processing', [ $this, 'facebook_capi_report_purchase__premium_only' ]);
			add_action('woocommerce_payment_complete', [ $this, 'facebook_capi_report_purchase__premium_only' ]);
			add_action('woocommerce_order_status_completed', [ $this, 'facebook_capi_report_purchase__premium_only' ]);

			/**
			 * Process WooCommerce Subscription renewals
			 * https://docs.woocommerce.com/document/subscriptions/develop/action-reference/
			 * https://github.com/wp-premium/woocommerce-subscriptions/blob/master/includes/class-wc-subscription.php
			 * https://developers.facebook.com/docs/marketing-api/conversions-api/subscription-lifecycle-events/
			 * */

			add_action(
				'woocommerce_subscription_payment_complete',
				[ $this, 'facebook_capi_report_subscription_payment_complete__premium_only' ]
			);

			if ($this->track_facebook_capi_subscription_renewal()) {
				add_action(
					'woocommerce_subscription_renewal_payment_complete',
					[ $this, 'facebook_capi_report_subscription_purchase_renewal__premium_only' ],
					10, 2
				);
			}

			add_action(
				'woocommerce_subscription_status_cancelled',
				[ $this, 'facebook_capi_report_subscription_cancellation__premium_only' ]
			);

			add_action(
				'woocommerce_subscription_status_updated',
				[ $this, 'facebook_capi_report_subscription_update__premium_only' ],
				10, 3
			);

		}
	}

	/**
	 * Subscription initial order
	 */
	public function facebook_capi_report_subscription_payment_complete__premium_only( $subscription ) {

		// Only process if this is the initial subscription payment
		if ($subscription->get_payment_count() !== 1) {
			return;
		}

		$this->facebook_capi->send_subscription_hit('Subscribe', $subscription, $subscription->get_parent());
	}

	/**
	 * Subscription renewal
	 */
	public function facebook_capi_report_subscription_purchase_renewal__premium_only( $subscription, $renewal_order ) {

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus the parent order is missing.
		if (!$parent_order) {
			return;
		}

		$this->facebook_capi->send_subscription_hit('RecurringSubscriptionPayment', $subscription, $parent_order, $renewal_order);
	}

	/**
	 * Subscription cancellation
	 */
	public function facebook_capi_report_subscription_cancellation__premium_only( $subscription ) {

//		error_log('facebook_capi_report_subscription_cancellation__premium_only');

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			return;
		}

//		error_log('processing subscription cancellation');
		$this->facebook_capi->send_subscription_hit('CancelSubscription', $subscription, $parent_order);
	}

	/**
	 * Subscription update
	 *
	 * There is one use case that is not ideal: When the customer manually renews a subscription, it will be cancelled and reactivated.
	 */
	public function facebook_capi_report_subscription_update__premium_only( $subscription, $new_status, $old_status ) {

//		error_log('old status: ' . $old_status . ' new status: ' . $new_status);

		$parent_order = $subscription->get_parent();

		// Abort if the order was created manually, and thus no parent order exists.
		if (!$parent_order) {
			return;
		}

		/**
		 * Don't process on-hold status because it triggered by essentially every order simply when the payment is pending:
		 * https://woocommerce.com/document/managing-orders/#visual-diagram-to-illustrate-order-statuses
		 */

		// Putting a subscription on hold
//		if ('active' === $old_status && 'on-hold' === $new_status) {
//			$this->facebook_capi->send_subscription_hit('CancelSubscription', $subscription, $parent_order);
//		}
//
//		// Only run if we are reactivating a subscription which is on hold
//		if ('on-hold' === $old_status && 'active' === $new_status) {
//			$this->facebook_capi->send_subscription_hit('Subscribe', $subscription, $parent_order, null, true);
//		}
	}

	public function facebook_save_session_identifiers_on_order__premium_only( $order ) {
		$this->facebook_capi->set_identifiers_on_order($order);
	}

	public function facebook_capi_report_purchase__premium_only( $order_id ) {

		$order = wc_get_order($order_id);

		// If the order is a subscription renewal, only continue if renewal tracking is enabled.
		if (
			Shop::is_wcs_renewal_order($order)
			&& $this->do_not_track_facebook_capi_subscription_renewal()
		) {
			return;
		}

		$this->facebook_capi->send_purchase_hit($order);
	}

	public function track_facebook_capi_subscription_renewal() {

		// If Shop::track_subscription_renewal is false, return false.
		if (Shop::do_not_track_subscription_renewal()) {
			return false;
		}

		return (bool) apply_filters('pmw_facebook_subscription_renewal_tracking', true);
	}

	public function do_not_track_facebook_capi_subscription_renewal() {
		return !$this->track_facebook_capi_subscription_renewal();
	}
}
