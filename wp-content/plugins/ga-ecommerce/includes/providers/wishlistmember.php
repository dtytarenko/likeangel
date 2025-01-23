<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Shopping Cart supported at this moment.
// 1. Paypal Checkout - Tracking of checkout, sales, recurring and refunds.
// 2. Paypal Payment Standard (Legacy) - Tracking of sales, recurring and refunds.
// 3. Stripe - Tracking of Checkout, sales, recurring and refunds.

class MonsterInsights_eCommerce_WishListMember_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	public $store_user_id_hook = 'wishlistmember_user_registered';

	/**
	 * Different registration scenarios based on the settings of the Level.
	 * 1. Auto-Create account is OFF, user gets redirected first to the WLM reg page and manually completes the registration.
	 * 		- For this one we store the data first and sets the "already_processed" to empty. This way we don't process the same transaction twice.
	 * 2. Auto-Create Account is ON and there's no delay. Account is immediately created.
	 * 3. Auto-Create account is ON and there's a delay.
	 * 4. Existing user purchases another Level. (Stripe has a diffrent way of processing this too so we've added wishlistmember_existing_member_purchase.)
	 *
	 *
	 * @var array
	 */
	public $add_to_ga_hooks = array( 'wishlistmember_user_registered', 'wishlistmember_finish_incomplete_registration', 'wishlistmember_existing_member_purchase' );

	/**
	 *
	 * @var array
	 */
	public $add_rebill_to_ga_hooks = array( 'wishlistmember_shoppingcart_rebill' );

	/**
	 *
	 * @var array
	 */
	public $remove_from_ga_hooks = array( 'wishlistmember_shoppingcart_refund' );

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_WishListMember_Integration();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Trigger necessary hooks from here.
	 */
	private function hooks() {
		// Store cookie
		add_action( $this->store_user_id_hook, array( $this, 'store_user_id' ), 9 );

		/**
		 * Checkout Page
		 * Note: WishList Member doesn't have a default checkout page for all its payment buttons.
		 * Workaround is to set the page where the button (eg. Paypal Checkout link, Stripe button shortcode) got clicked
		 * and set it as the checkout page.
		 */
		add_action( 'wishlistmember_button_checkout', array( $this, 'checkout_page' ) );

		// When to add to GA
		foreach ( $this->add_to_ga_hooks as $hook ) {
			add_action( $hook, function ( $data ) {
				$this->do_transaction( false );
			}, 10 );
		}

		// When to add rebills to GA
		foreach ( $this->add_rebill_to_ga_hooks as $hook ) {
			add_action( $hook, function ( $data ) {
				$this->do_transaction( true );
			}, 10 );
		}

		// When to remove from GA
		foreach ( $this->remove_from_ga_hooks as $hook ) {
			add_action( $hook, array( $this, 'undo_transaction' ), 10 );
		}

		// PayPal Checkout Redirect (Return URL)
		add_filter( 'wishlistmember_paypalec_get_return_url', array( $this, 'change_paypal_return_url' ) );
	}

	/**
	 * Store the temporary transaction including the GAUID. This is used whe an incomplete account is created
	 * and is then used when the inc account is completed.
	 * Data are stored and fetched from  wlm_post_data().
	 *
	 */
	public function store_user_id() {
		if ( false !== strpos( wlm_post_data()['email'], 'temp_' ) ) {

			$ga_uuid  = monsterinsights_get_client_id();
			$level_id = ( wlm_post_data()['sku'] ) ? wlm_post_data()['sku']: wlm_post_data()['wpm_id'];
			$txn_id   = wlm_post_data()['sctxnid'];

			if ( ( $measurement_id = monsterinsights_get_v4_id_to_output() ) && isset( wlm_post_data()['sctxnid']) ) {
				$session_id = $measurement_id;
			}

			if ( $ga_uuid && isset( $txn_id ) && isset( wlm_post_data()['paid_amount'] ) ) {
				$cookie = monsterinsights_get_cookie();
				$txn_data = maybe_serialize(
					array(
						'level_id'          => sanitize_text_field( wlm_post_data()['sku'] ),
						'ga_uuid'           => sanitize_text_field( $ga_uuid ),
						'mi_cookie'         => sanitize_text_field( $cookie ),
						'paid_amount'       => sanitize_text_field( wlm_post_data()['paid_amount'] ),
						'txn_id'            => sanitize_text_field( $txn_id ),
						'shopping_cart'     => sanitize_text_field( wlm_post_data()['sc_type'] ),
						'already_processed' => '',
						'session_id'        => sanitize_text_field( $session_id )
					)
				);

				// Save this inside WishList Member's options table.
				wishlistmember_instance()->save_option( 'mi_' . $txn_id, $txn_data );
			}
		}
	}

	/**
	 * Callback for payment button page which we have set here as a checkout page.
	 */
	public function checkout_page( $product_detail ) {
		// If page refresh, don't re-track.
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		if ( $this->is_checkout_page_testmode( $product_detail ) ) {
			return;
		}

		$this->track_checkout_v4( $product_detail );
	}

	/**
	 * Send begin_checkout event.
	 */
	private function track_checkout_v4( $product ) {

		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$items = array(
			array(
				'item_id'   => strval( $product['sku'] ),
				'item_name' => $product['name'],
				'price'     => floatval( $product['amount'] ),
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

	/**
	 * Prepare data for purchase event.
	 * Data are stored/fetched from the wlm_post_data() function.
	 */
	public function do_transaction( $is_rebill = false ) {

		// Don't process Temp accounts.
		// Incomplete registrations are handled by function store_user_id().
		if ( false !== strpos( wlm_post_data()['email'], 'temp_' ) ) {
			return;
		}

		$paid_amount = sanitize_text_field( wlm_post_data()['paid_amount'] );
		$sc          = sanitize_text_field( wlm_post_data()['sc_type'] );
		$txn_id      = sanitize_text_field( wlm_post_data()['sctxnid'] );
		$level_id    = sanitize_text_field( ( wlm_post_data()['sku'] ) ? wlm_post_data()['sku'] : wlm_post_data()['wpm_id'] );

		// If the incomplete account has a delay in the Level's settings and got completed by finish_incomplete_registration()
		// then let's get the level ID via the wlm_post_data()['levels'].
		if( !$level_id ) {
			$level_id = array_key_first( (array) wlm_post_data()['levels'] );
		}

		// IN case there's no $txn_id let's get it based on the email address and the level's ID/SKU.
		if ( !$txn_id ) {
			$user_data   = wlmapi_get_member_by( 'user_email', wlm_post_data()['email']);
			$user_id     = $user_data['members']['member'][0]['id'];
			$member_data = wlmapi_get_level_member_data( $level_id, $user_id );
			$txn_id      = $member_data['member']['level']->TxnID;
		}

		// Still no txn_id, don't continue.
		if ( !$txn_id ) {
			return;
		}

		// Don't track test mode.
		if ( $this->is_transaction_testmode() ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( $skip_renewals && $is_rebill ) {
			return;
		}

		// Check if there's any entry on the WLM options table that matches the transaction ID.
		// Need to check if the transaction has already been processed in GA, if so let's skip it.
		$existing_transaction = wishlistmember_instance()->get_option( 'mi_'.$txn_id );

		$is_in_ga = $existing_transaction['already_processed'] ?? '';
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $txn_id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		// For rebills/recurring.
		if ( $is_rebill && wlm_post_data()['is_wlm_sc_rebill'] ) {

			// Check if there's a user that has that transaction ID, otherwise don't process it.
			$user = wishlistmember_instance()->get_user_id_from_txn_id( $txn_id );
			if( ! $user ) {
				return;
			}

			// Check if the rebill has already been processed.
			$rebill_already_processed = $existing_transaction['rebill_already_processed'];
			if ( $rebill_already_processed === 'yes' ) {
				return;
			}
		}

		$ga_uuid = monsterinsights_get_client_id();
		$mi_cookie = monsterinsights_get_cookie();

		if( $existing_transaction ) {
			if ( isset( $existing_transaction['ga_uuid'] ) && ! empty( $existing_transaction['ga_uuid'] )  ) {
				$ga_uuid     = $existing_transaction['ga_uuid'];
				$paid_amount = ( $paid_amount ) ? $paid_amount: $existing_transaction['paid_amount'];
				$level_id    = $existing_transaction['level_id'];
				$sc          = $existing_transaction['shopping_cart'];
				$session_id  = $existing_transaction['session_id'];
			}
		}

		// Let's get the name of the Membership Level.
		$wpm_levels = wishlistmember_instance()->get_option( 'wpm_levels' );
		$level_name = $wpm_levels[$level_id]['name'];
		$product_name = $sc . ' - ' . $level_name;

		$order_currency = 'USD';

		if( ! $paid_amount ) {
			return;
		}

		$data = array(
			'txn_id'         => $txn_id,
			'order_total'    => $paid_amount,
			'order_currency' => $order_currency,
			'order_shipping' => '',
			'member_id'      => $user_id ?? null,
			'product_id'     => $level_id,
			'product_name'   => $product_name,
			'session_id'     => $session_id ?? ''
		);

		$this->track_transaction_v4( $data, $ga_uuid );

		$txn_data = maybe_serialize(
			array(
				'level_id'                 => sanitize_text_field( $level_id ),
				'ga_uuid'                  => sanitize_text_field( $ga_uuid ),
				'mi_cookie'                => sanitize_text_field( $mi_cookie ),
				'paid_amount'              => sanitize_text_field( $paid_amount ),
				'transaction_id'           => sanitize_text_field( $txn_id ),
				'shopping_cart'            => sanitize_text_field( $sc ),
				'already_processed'        => 'yes',
				'rebill_already_processed' => ($is_rebill) ? 'yes': '',
				'member_id'                => $user_id ?? null,
				'session_id'               => empty( $existing_transaction['session_id'] ) ? '' : $existing_transaction['session_id'],
			)
		);

		wishlistmember_instance()->save_option( 'mi_' . $txn_id, $txn_data );
	}

	private function track_transaction_v4( $data, $cid ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$items = array(
			array(
				'item_id'   => $data['product_id'],
				'item_name' => $data['product_name'],
				'price'     => $data['order_total'],
				'quantity'  => 1,
			),
		);

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $data['txn_id'],
					'items'          => $items,
					'value'          => $data['order_total'],
					'tax'            => 0.00,
					'shipping'       => $data['order_shipping'],
					'currency'       => $data['order_currency'],
					'session_id'     => $data['session_id'],
				),
			),
		);

		$args = array(
			'client_id' => $cid,
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $data['member_id']; // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function undo_transaction() {

		if ( !wlm_post_data()['is_wlm_sc_refund'] ) {
			return;
		}

		$txn_id = sanitize_text_field( wlm_post_data()['sctxnid'] );

		// Check if there's a user that has that transaction ID, otherwise don't process it.
		$user = wishlistmember_instance()->get_user_id_from_txn_id( $txn_id );
		if( ! $user ) {
			return;
		}

		// Check if there's any entry on the WLM options table that matches the transaction ID.
		// Need to check if the transaction has already been processed in GA, if so let's skip it.
		$existing_transaction = wishlistmember_instance()->get_option( 'mi_'.$txn_id );

		$is_in_ga = $existing_transaction['refund_already_processed'];

		$skip_ga = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $txn_id );
		if ( $is_in_ga || $skip_ga ) {
			return;
		}

		$data = array(
			'txn_id'      => $txn_id,
			'order_total' => wlm_post_data()['paid_amount'],
			'member_id'   => ( $existing_transaction['member_id'] ) ? $existing_transaction['member_id']: $user
		);

		$cid = $existing_transaction['ga_uuid'];

		$this->track_refund_v4( $data, $cid );

		$existing_transaction['refund_already_processed'] = 'yes';

		$txn_data = maybe_serialize( $existing_transaction );

		wishlistmember_instance()->save_option( 'mi_' . $txn_id, $txn_data );
	}

	private function track_refund_v4( $data, $cid ) {

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
					'transaction_id' => $data['txn_id'],
					'value'          => $data['order_total'],
				),
			),
		);

		$args = array(
			'client_id' => $cid,
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $data['member_id'];
		}

		monsterinsights_mp_collect_v4( $args );
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

	/**
	 * Check the checkout page is in testmode or not.
	 */
	private function is_checkout_page_testmode( $product_detail ) {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		return $product_detail['testmode'];
	}

	/**
	 * Check is transaction in testmode.
	 */
	private function is_transaction_testmode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		return wlm_post_data()['testmode'];
	}
}
