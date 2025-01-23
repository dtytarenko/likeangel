<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_MemberMouse_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/**
	 * @var string DB_VERSION The version of the membermouse metadata db table; pegged to the plugin version
	 */
	 const DB_VERSION = "mi_mm_db_version";

	/**
	 * @var string TABLE_NAME The name of the db table used to store transaction-level metadata. The configured wpdb prefix will be added to this table name
	 */
	const TABLE_NAME = "mi_mm_monsterinsights_meta";

	/**
	 * The mm_payment_received hook is the earliest available common point in the initial checkout and one-click buy flows where we are guaranteed a user id
	 *
	 * @var string
	 */
	public $store_user_id_hook = 'mm_payment_received';

	/**
	 * When order is completed, one of these hooks will be fired. Thanks to MI's system, orders will only be
	 * tracked into GA once regardless of how many of these are fired per order, and regardless of how many times.
	 *
	 * @var array
	 */
	public $add_to_ga_hooks = array( 'mm_payment_received' );

	public $add_rebill_to_ga_hooks = array( 'mm_payment_rebill' );

	/**
	 * When order is refunded, one of these hooks will be fired. Thanks to MI's system, orders will only be
	 * tracked out of GA once regardless of how many of these are fired per order, and regardless of how many times.
	 *
	 * @var array
	 */
	public $remove_from_ga_hooks = array( 'mm_refund_issued' );

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_MemberMouse_Integration();
			self::$instance->sync_table();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function hooks() {
		// Setup Funnel steps for MemberMouse
		$this->funnel_steps = $this->get_funnel_steps();

		// Store cookie
		add_action( $this->store_user_id_hook, array( $this, 'store_user_id' ), 9 );

		// Checkout Page
		add_action( 'template_redirect', function () {
			if ( class_exists( "MM_CorePage" ) ) {
				$corePageInfo = MM_CorePage::getCorePageInfo( get_the_ID() );
				if ( ! is_null( $corePageInfo ) && ( $corePageInfo->core_page_type_id == MM_CorePageType::$CHECKOUT ) ) {
					$this->checkout_page();
				}
			}
		}, 10 );

		// When to add to GA
		foreach ( $this->add_to_ga_hooks as $hook ) {
			add_action( $hook, function ( $data ) {
				$this->do_transaction( $data );
			}, 10 );
		}

		// When to add rebills to GA
		foreach ( $this->add_rebill_to_ga_hooks as $hook ) {
			add_action( $hook, function ( $data ) {
				$this->do_transaction( $data, true );
			}, 10 );
		}

		// When to remove from GA
		foreach ( $this->remove_from_ga_hooks as $hook ) {
			add_action( $hook, array( $this, 'undo_transaction' ), 10 );
		}

		// PayPal Redirect
		add_filter( 'mm_paypal_standard_return_url', array( $this, 'change_paypal_return_url' ) );
	}

	/**
	 * Store the visitor ID and attached experiments and variations, as stored in the cookie, with the transaction.
	 *
	 * @param array $data Order data as defined in MM_Event::packageOrderData
	 *
	 * @since 7.3.0
	 *
	 */
	public function store_user_id( $data ) {
		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid && isset( $data["order_transaction_id"] ) && isset( $data["order_id"] ) ) {
			$cookie = monsterinsights_get_cookie();
			$this->store_transaction_meta( $data["order_transaction_id"], $data["order_id"], $ga_uuid, $cookie );
		}

		if ( ( $measurement_id = monsterinsights_get_v4_id_to_output() ) && isset( $data["order_transaction_id"] ) ) {
			$this->save_user_session_id( $data["order_transaction_id"], $measurement_id );
		}
	}


	protected function save_user_session_id( $payment_id, $measurement_id ) {
		if ( function_exists( 'monsterinsights_get_browser_session_id' ) ) {
			$session_id = monsterinsights_get_browser_session_id( $measurement_id );
			$this->store_user_session_id( $payment_id, $session_id );
		}
	}

	private function track_checkout_v4( $product_id ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}
		$product = new MM_Product( $product_id );

		$items = array(
			array(
				'item_id'   => $product_id,
				'item_name' => $product->getName(),
				'price'     => $product->getPrice( false ),
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

	public function checkout_page() {

		if ( method_exists( "MM_CheckoutForm", "resolveCheckoutInfo" ) ) {
			//MM version >= 2.4.1
			$checkout_info = MM_CheckoutForm::resolveCheckoutInfo();
			if ( $checkout_info == null ) {
				return;
			}
			$product_id = $checkout_info->productId;
		} else {
			//MM version < 2.4.1
			$checkout_form = new MM_CheckoutForm();
			$product_id    = $checkout_form->productId;
		}

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::mm_test_mode() ) {
			return;
		}

		$this->track_checkout_v4( $product_id );
	}

	private function track_transaction_v4( $data, $cid, $discount, $is_rebill ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$productsData = json_decode( stripslashes( $data["order_products"] ) );
		$product      = reset( $productsData );

		$items = array(
			array(
				'item_id'   => $product->id,
				'item_name' => $product->name,
				'price'     => $product->amount,
				'quantity'  => 1,
			),
		);

		$session_id = $this->get_user_session_id( $data['order_transaction_id'] );
		$events     = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $data['order_transaction_id'],
					'items'          => $items,
					'value'          => $data['order_total'],
					'tax'            => 0.00,
					'shipping'       => $data['order_shipping'],
					'coupon'         => $discount,
					'currency'       => $data['order_currency'],
					'session_id'     => $session_id ?: "",
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

	/**
	 * @param array $data Member data + Order Data.
	 * @param boolean $is_rebill Indicates if the transaction is a rebill or initial payment
	 */
	public function do_transaction( $data, $is_rebill = false ) {
		if ( ! is_array( $data ) || ! isset( $data['order_transaction_id'] ) ) {
			return;
		}

		$common_vars = $this->get_common_vars( $data );
		if ( $common_vars === false ) {
			return;
		}
		list( $txn_id, $order_id, $cid, $meta ) = $common_vars;

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::mm_test_mode() ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( $skip_renewals && $is_rebill ) {
			return;
		}

		$is_in_ga = ( $meta['monsterinsights_is_in_ga'] === true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $txn_id );
		if ( $is_in_ga || $skip_ga ) {
			return;
		}

		$discount = '';
		if ( isset( $data["order_coupons"] ) ) {
			$coupon_data = json_decode( stripslashes( $data["order_coupons"] ) );
			if ( is_array( $coupon_data ) && ( count( $coupon_data ) > 0 ) ) {
				$first_coupon = reset( $coupon_data ); //currently MemberMouse only supports one coupon per order
				$discount     = $first_coupon->code;
			}
		}

		$this->track_transaction_v4( $data, $cid, $discount, $is_rebill );

		// Update in GA
		$this->store_transaction_meta( $txn_id, $order_id, $cid, $meta['monsterinsights_cookie'], true );
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
					'transaction_id' => $data['order_transaction_id'],
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

	public function undo_transaction( $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		$common_vars = $this->get_common_vars( $data );
		if ( $common_vars === false ) {
			return;
		}
		list( $txn_id, $order_id, $cid, $meta ) = $common_vars;

		$is_in_ga = $meta['monsterinsights_refund_is_in_ga'];

		$skip_ga = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $txn_id );
		if ( $is_in_ga || $skip_ga ) {
			return;
		}

		$this->track_refund_v4( $data, $cid );

		$this->mark_refunded_in_transaction_meta( $txn_id );
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
	 * Do common setup for do_transaction and undo_transaction
	 * Returns transaction_id, $order_id, transaction metadata, and ga_uuid in an array
	 *
	 * @param array $data The data array supplies to one of the membermouse hooks
	 *
	 * @return boolean|array The array of common variables, or boolean false if there was an error
	 */
	protected function get_common_vars( $data ) {

		if ( ! is_array( $data ) ) {
			return false;
		}

		$meta = $this->get_transaction_meta( $data['order_transaction_id'] );
		if ( $meta === false ) {
			return false;
		}

		$cid = $meta['ga_uuid'];

		// If no CID, attempt to grab it from the original transaction.
		if ( empty( $cid ) && ! empty( $data['order_id'] ) ) {
			$cid = $this->get_uuid_by_order( $data['order_id'] );
		}

		$vars = [ $data['order_transaction_id'], $data['order_id'], $cid, $meta ];

		return $vars;
	}

	/**
	 * Returns the metadata table name, with the database-prefix prepended. Convenience method
	 *
	 * @return string metadata table name
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create table to store MI specific metadata as MemberMouse don't have any meta table order.
	 *
	 * @return void
	 */
	protected function sync_table() {

		static $synced = false;

		if ( ! $synced ) {
			//if version changes, trigger dbdelta
			$version = get_option( self::DB_VERSION );

			$plugin = MonsterInsights_eCommerce::get_instance();

			if ( ( $version === false ) || ( $version != $plugin->version ) ) {
				include_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); //provides access to dbDelta

				$table_name = self::get_table_name();
				$idx_name   = $table_name . "_idx1";

				$sql = "CREATE TABLE {$table_name} (
					transaction_id INT(11) UNSIGNED NOT NULL,
					order_id INT(11) UNSIGNED,
					ga_uuid VARCHAR(255) DEFAULT NULL,
					monsterinsights_cookie TEXT DEFAULT NULL,
					monsterinsights_is_in_ga TINYINT DEFAULT 0,
					monsterinsights_refund_is_in_ga TINYINT DEFAULT 0,
					ga_session_id TEXT DEFAULT NULL,
					PRIMARY KEY  (transaction_id),
					KEY {$idx_name} (order_id)
					) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_520_ci;";

				dbDelta( $sql );
				update_option( self::DB_VERSION, $plugin->version );
				$synced = true;
			}
		}
	}

	/**
	 * Returns metadata associated with a transaction id
	 *
	 * @param int $transaction_id The transaction id to retrieve metadata for
	 *
	 * @return boolean|object Returns the metadata associated with the transaction id, or boolean false on error/not-found
	 */
	public function get_transaction_meta( $transaction_id ) {
		global $wpdb;

		if ( empty( $transaction_id ) ) {
			return false;
		}

		$table_name = self::get_table_name();
		$sql        = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE transaction_id=%d", $transaction_id );
		$row        = $wpdb->get_row( $sql, ARRAY_A );

		if ( $row == null ) {
			return false;
		}

		$row['monsterinsights_is_in_ga']        = ( $row['monsterinsights_is_in_ga'] == 1 );
		$row['monsterinsights_refund_is_in_ga'] = ( $row['monsterinsights_refund_is_in_ga'] == 1 );

		return $row;
	}

	/**
	 * Store transaction-level metadata. transaction_id is a foreign key into the mm_transaction_log, and serves at the primary key here
	 *
	 * @param int $transaction_id The id of the transaction to store metadata for
	 * @param int $order_id The id of the order associated with the transaction id
	 * @param string $ga_uuid The GA UUID
	 * @param string $cookie The MonsterInsights cookie
	 */
	public function store_transaction_meta( $transaction_id, $order_id, $ga_uuid, $cookie, $monsterinsights_is_in_ga = null ) {
		global $wpdb;

		$table = self::get_table_name();
		if ( $monsterinsights_is_in_ga == null ) {
			$monsterinsights_is_in_ga = boolval( $monsterinsights_is_in_ga ) ? 1 : 0;
			$sql                      = "INSERT INTO {$table} ( transaction_id, ga_uuid, order_id, monsterinsights_cookie ) " .
			                            "VALUES ( %d , %s , %d , %s ) ON DUPLICATE KEY UPDATE ga_uuid = %s, monsterinsights_cookie = %s";
			$sql                      = $wpdb->prepare( $sql, $transaction_id, $ga_uuid, $order_id, $cookie, $ga_uuid, $cookie );
		} else {
			$monsterinsights_is_in_ga = boolval( $monsterinsights_is_in_ga ) ? 1 : 0;
			$sql                      = "INSERT INTO {$table} ( transaction_id, ga_uuid, order_id, monsterinsights_cookie, monsterinsights_is_in_ga ) " .
			                            "VALUES ( %d , %s , %d , %s, %d ) ON DUPLICATE KEY UPDATE ga_uuid = %s, monsterinsights_cookie = %s , monsterinsights_is_in_ga = %d";
			$sql                      = $wpdb->prepare( $sql, $transaction_id, $ga_uuid, $order_id, $cookie, $monsterinsights_is_in_ga, $ga_uuid, $cookie, $monsterinsights_is_in_ga );
		}
		$res = $wpdb->query( $sql );
		if ( $res === false ) {
			delete_option( self::DB_VERSION ); //there was an error; Delete the version string to trigger sync_table again on the next run
		}

		return $res;
	}


	/**
	 * Update metadata for a transaction to indicate the refund was captured in GA
	 *
	 * @param $transaction_id int id of the transaction to update metadata for
	 *
	 * @return int|false 1 on success, or false on error
	 */
	public function mark_refunded_in_transaction_meta( $transaction_id ) {
		global $wpdb;

		if ( empty( $transaction_id ) ) {
			return false;
		}

		$result = $wpdb->update( self::get_table_name(), [ 'monsterinsights_refund_is_in_ga' => 1 ], [ "transaction_id" => $transaction_id ] );

		return $result;
	}


	/**
	 * Stores the GA user session id linked to a transaction
	 *
	 * @param int $transaction_id
	 * @param string $session_id
	 */
	public function store_user_session_id( $transaction_id, $session_id ) {
		global $wpdb;

		$table = self::get_table_name();
		$sql   = "INSERT INTO {$table} ( transaction_id, ga_session_id ) VALUES ( %d , %s ) ON DUPLICATE KEY UPDATE ga_session_id = %s";
		$sql   = $wpdb->prepare( $sql, $transaction_id, $session_id, $session_id );

		return $wpdb->query( $sql );
	}

	/**
	 * Returns the GA user session id  linked to a transaction
	 *
	 * @param int $transaction_id
	 *
	 * @return string|null The ga_session_id linked to the transaction, null if it is not set or if metadata for the transaction is not found
	 */
	public function get_user_session_id( $transaction_id ) {
		global $wpdb;

		$table = self::get_table_name();
		$sql   = "SELECT ga_session_id FROM {$table} WHERE transaction_id = %d";
		$sql   = $wpdb->prepare( $sql, $transaction_id );

		return $wpdb->get_var( $sql );
	}
}
