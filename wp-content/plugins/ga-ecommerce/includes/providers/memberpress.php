<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_MemberPress_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/**
	 * @var string $uuid_meta_key The name of the meta key used to store the UUID
	 */
	public $uuid_meta_key = '_yoast_gau_uuid';
	public $cookie_meta_key = '_monsterinsights_cookie';

	/**
	 * Meta key name for storing GA session_id.
	 *
	 * @var string
	 */
	public $ga_session_id_meta_key = '_monsterinsights_ga_session_id';

	/**
	 * When order is processed, there is a payment pending created. From that moment the user_id can be saved
	 *
	 * @var string
	 */
	public $store_user_id_hook = 'mepr-txn-status-pending';

	/**
	 * When order is completed, one of these hooks will be fired. Thanks to MI's system, orders will only be
	 * tracked into GA once regardless of how many of these are fired per order, and regardless of how many times.
	 *
	 * @var array
	 */
	public $add_to_ga_hooks = array( 'mepr-txn-status-complete', 'mepr-txn-status-confirmed' );

	/**
	 * When order is refunded, one of these hooks will be fired. Thanks to MI's system, orders will only be
	 * tracked out of GA once regardless of how many of these are fired per order, and regardless of how many times.
	 *
	 * @var array
	 */
	public $remove_from_ga_hooks = array( 'mepr-txn-status-refunded' );

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_MemberPress_Integration();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function hooks() {
		// Setup Funnel steps for MemberPress
		$this->funnel_steps = $this->get_funnel_steps();

		// Store cookie
		add_action( $this->store_user_id_hook, array( $this, 'store_user_id' ), 10 );

		// Checkout Page
		add_action( 'mepr-above-checkout-form', array( $this, 'checkout_page' ) );

		// When to add to GA
		foreach ( $this->add_to_ga_hooks as $hook ) {
			add_action( $hook, array( $this, 'do_transaction' ), 10 );
		}

		// When to remove from GA
		foreach ( $this->remove_from_ga_hooks as $hook ) {
			add_action( $hook, array( $this, 'undo_transaction' ), 10 );
		}

		// PayPal Redirect
		add_filter( 'mepr_gateway_pay_pal_ec_return_notify_url', array( $this, 'change_paypal_return_url' ), 10, 1 );
		add_filter( 'mepr_gateway_pay_pal_standard_return_notify_url', array(
			$this,
			'change_paypal_return_url'
		), 10, 1 );
	}

	/**
	 * Store the visitor ID and attached experiments and variations, as stored in the cookie, with the transaction.
	 *
	 * @param MeprTransaction $txn Transaction.
	 *
	 * @since 7.3.0
	 *
	 */
	public function store_user_id( $txn ) {
		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			$txn->update_meta( $this->uuid_meta_key, $ga_uuid );
			$txn->update_meta( $this->cookie_meta_key, $cookie );
		}

		if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
			$session_id = monsterinsights_get_browser_session_id( $measurement_id );
			$txn->update_meta( $this->ga_session_id_meta_key, $session_id );
		}
	}

	private function track_checkout_v4( $product_id ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}
		$obj = new MeprProduct( $product_id );

		$items = array(
			array(
				'item_id'   => $product_id,
				'item_name' => $obj->post_title,
				'price'     => $obj->price,
				'quantity'  => 1,
			),
		);

		$args = array(
			'events' => array(
				array(
					'name'   => 'begin_checkout',
					'params' => array(
						'items' => $items,
					),
				),
			),
		);

		if ( monsterinsights_get_option( 'userid', false ) && is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function checkout_page( $product_id ) {

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::mepr_test_mode() ) {
			return;
		}

		$this->track_checkout_v4( $product_id );
	}

	public function save_user_cid( $payment_id ) {
		$tracked_already = get_post_meta( $payment_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			update_post_meta( $payment_id, '_yoast_gau_uuid', $ga_uuid );
			update_post_meta( $payment_id, '_monsterinsights_cookie', $cookie );
		}
	}

	private function track_transaction_v4( $txn, $cid, $discount, $mepr_options ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$obj   = new MeprProduct( $txn->product_id );
		$items = array(
			array(
				'item_id'   => $txn->product_id,
				'item_name' => $obj->post_title,
				'price'     => $txn->amount,
				'quantity'  => 1,
			),
		);

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $txn->id,
					'items'          => $items,
					'value'          => $txn->total,
					'tax'            => $txn->tax_amount,
					'shipping'       => 0.00,
					'coupon'         => $discount,
					'currency'       => $mepr_options->currency_code,
					'session_id'     => $this->get_ga_session_id( $txn ),
				),
			),
		);

		if ( MonsterInsights_eCommerce_Helper::easy_affiliate()->is_easy_affiliate_active() ) {

			$affiliate_id = MonsterInsights_eCommerce_Helper::easy_affiliate()->get_easy_affiliation_woo_affiliate_id( $txn );

			if ( is_int( $affiliate_id ) && $affiliate_id > 0 ) {
				$events[0]['params']['affiliate_label'] = $affiliate_id;
			}
		}

		if ( MonsterInsights_eCommerce_Helper::is_affiliate_wp_active() ) {

			$affiliate_id = MonsterInsights_eCommerce_Helper::get_affiliate_wp_affiliate_id( $txn, 'memberpress' );

			if ( is_int( $affiliate_id ) && $affiliate_id > 0 ) {
				$events[0]['params']['affiliate_label'] = $affiliate_id;
			}
		}

		$args = array(
			'client_id' => $cid,
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $txn->user_id; // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * @param MeprTransaction $txn The transaction object.
	 */
	public function do_transaction( $txn ) {
		if ( ! is_object( $txn ) ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::mepr_test_mode() ) {
			return;
		}

		// Don't report transactions that are not payments.
		if ( ! empty( $txn->txn_type ) && MeprTransaction::$payment_str !== $txn->txn_type ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( $skip_renewals && $txn->is_rebill() ) {
			return;
		}

		$is_in_ga = $txn->get_meta( '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $txn->id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$mepr_options = MeprOptions::fetch();
		$cid          = $txn->get_meta( $this->uuid_meta_key, true );
		$discount     = $txn->coupon_id ? get_the_title( $txn->coupon_id ) : '';

		// If no CID, attempt to grab it from the original transaction.
		if ( empty( $cid ) ) {
			$sub       = new MeprSubscription( $txn->subscription_id );
			$first_txn = $sub->first_txn();
			$cid       = $first_txn->get_meta( $this->uuid_meta_key, true );
		}

		$this->track_transaction_v4( $txn, $cid, $discount, $mepr_options );

		// Update in GA
		$txn->update_meta( '_monsterinsights_is_in_ga', 'yes' );
	}

	private function track_refund_v4( $txn, $cid ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$events = array(
			array(
				'name'   => 'refund',
				'params' => array(
					'transaction_id' => $txn->id,
					'value'          => $txn->total,
				),
			),
		);

		$args = array(
			'client_id' => $cid,
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $txn->user_id;
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function undo_transaction( $txn ) {
		if ( ! is_object( $txn ) ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( $skip_renewals && $txn->is_rebill() ) {
			return;
		}

		// Don't report transactions that are not payments.
		if ( ! empty( $txn->txn_type ) && MeprTransaction::$payment_str !== $txn->txn_type ) {
			return;
		}

		$is_in_ga = $txn->get_meta( '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $txn->id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$mepr_options = MeprOptions::fetch();
		$cid          = $txn->get_meta( $this->uuid_meta_key, true );
		$discount     = $txn->coupon_id ? get_the_title( $txn->coupon_id ) : '';

		// If no CID, attempt to grab it from the original transaction.
		if ( empty( $cid ) ) {
			$sub       = new MeprSubscription( $txn->subscription_id );
			$first_txn = $sub->first_txn();
			$cid       = $first_txn->get_meta( $this->uuid_meta_key, true );
		}

		$this->track_refund_v4( $txn, $cid );

		$txn->update_meta( '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	/**
	 * Add utm_nooverride to the PayPal return URL so the original source of the transaction won't be overridden.
	 *
	 * @param array $paypal_args
	 *
	 * @return array
	 * @link  https://support.bigcommerce.com/questions/1693/How+to+properly+track+orders+in+Google+Analytics+when+you+accept+PayPal+as+a+method+of+payment.
	 *
	 * @since 7.3.0
	 *
	 */
	public function change_paypal_return_url( $paypal_url ) {
		// If already added, remove
		$paypal_url = remove_query_arg( 'utm_nooverride', $paypal_url );

		// Add UTM no override
		$paypal_url = add_query_arg( 'utm_nooverride', '1', $paypal_url );

		return $paypal_url;
	}

	private function get_funnel_steps() {
		return array(
			'started_checkout'   => array(
				'action' => 'checkout',
				'step'   => 1,
			),
			'completed_purchase' => array(
				'action' => 'purchase',
				'step'   => 2,
			),
		);
	}

	/**
	 * Get GA session_id from meta.
	 *
	 * @param MeprTransaction $txn
	 *
	 * @return string
	 */
	private function get_ga_session_id( $txn ) {
		$session_id = $txn->get_meta( $this->ga_session_id_meta_key, true );

		if ( $session_id ) {
			return $session_id;
		}

		// For subscription sometimes we do not get meta-data from real transaction.
		if ( $txn_sub = $txn->subscription() ) {
			return $txn_sub->first_txn()->get_meta( $this->ga_session_id_meta_key, true );
		}

		return '';
	}
}
