<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * LifterLMS EE Tracking Integration
 */
class MonsterInsights_eCommerce_LifterLMS_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	/**
	 * Cookie Meta key name
	 *
	 * @var string
	 */
	public $cookie_meta_key = '_monsterinsights_cookie';

	/**
	 * Singleton Instance Holder.
	 *
	 * @var [type]
	 */
	private static $instance;

	/**
	 * Post types supported by this integration.
	 *
	 * @var array
	 */
	protected $post_types = array();

	/**
	 * UUID Meta key name
	 *
	 * @var string
	 */
	public $uuid_meta_key = '_yoast_gau_uuid';

	/**
	 * Retrieve singleton instance.
	 *
	 * @return [type]
	 * @see    {Reference}
	 * @link   {URL}
	 * @since  [version]
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_LifterLMS_Integration();
		}

		return self::$instance;
	}

	/**
	 * Private Constructor.
	 *
	 * @since [version]
	 */
	public function __construct() {
		parent::__construct();
		$this->post_types = apply_filters( 'monsterinsights_llms_post_types', array( 'course', 'llms_membership' ) );
		$this->hooks();
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 * @since [version]
	 */
	private function hooks() {

		// Setup Funnel steps for LifterLMS.
		$this->funnel_steps = $this->get_funnel_steps();

		// Funnel Step Hooks.
		add_action( 'lifterlms_before_loop_item', array( $this, 'do_step_clicked_product' ) );
		foreach ( $this->post_types as $type ) {
			$type = str_replace( 'llms_', '', $type );
			add_action( 'lifterlms_single_' . $type . '_after_summary', array( $this, 'do_step_viewed_product' ) );
		}
		add_action( 'lifterlms_after_checkout_form', array( $this, 'do_step_started_checkout' ) );

		// Add Order to GA.
		add_action( 'lifterlms_transaction_status_succeeded', array( $this, 'add_order' ), 10 );

		// Remove Order from GA.
		add_action( 'lifterlms_transaction_status_refunded', array( $this, 'remove_order' ), 10 );

		// Store client id.
		add_action( 'lifterlms_new_pending_order', array( $this, 'save_user_cid' ) );
	}

	/**
	 * Determine if a user can be tracked
	 *
	 * @param int $uid WP_User ID
	 *
	 * @return bool
	 * @since [version]
	 */
	public function can_user_be_tracked( $uid ) {
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( $uid );
			if ( $do_not_track ) {
				return false;
			}
		}

		return true;
	}

	private function track_purchase_v4( $order, $order_id, $txn ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $order_id,
					'value'          => $txn->get( 'amount' ),
					'coupon'         => $order->get( 'coupon_code' ),
					'currency'       => $order->get( 'currency' ),
					'session_id'     => get_post_meta( $order_id, '_monsterinsights_ga_session_id', true ),
					'items'          => $this->get_cart_items_v4( $order->get( 'plan_id' ) ),
				),
			),
		);

		$args = array(
			'events'    => $events,
			'client_id' => monsterinsights_get_client_id( $order_id ),
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->get( 'user_id' ); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Add an order to GA.
	 *
	 * @param LLMS_Transaction $txn Transaction object.
	 *
	 * @return void
	 * @since [version]
	 */
	public function add_order( $txn ) {

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::llms_test_mode() ) {
			return;
		}

		$order    = $txn->get_order();
		$order_id = $order->get( 'id' );
		$txn_id   = $txn->get( 'id' );

		$is_in_ga = get_post_meta( $order_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $order_id );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! $this->can_user_be_tracked( $order->get( 'user_id' ) ) ) {
			return;
		}
		if ( ! $this->can_track_order( $order, $txn_id ) ) {
			return;
		}

		$this->track_purchase_v4( $order, $order_id, $txn );

		update_post_meta( $order_id, '_monsterinsights_is_in_ga', 'yes' );
	}

	private function can_track_order( $order, $txn_id ) {
		// If we're skipping renewals we must check if this is a renewal.
		if ( $order->is_recurring() && apply_filters( 'monsterinsights_ecommerce_skip_renewals', true ) ) {

			// Retrieve the first transaction on the order.
			$txns = $order->get_transactions( array(
				array(
					'status'   => array( 'llms-txn-succeeded', 'llms-txn-refunded' ),
					'per_page' => 1,
					'type'     => array( 'recurring', 'single' ),
					// If a manual payment is recorded it's counted a single payment and that should count.
				)
			) );

			// quit early if no transactions are found or the transaction ID is not in the list of transactions.
			if ( empty( $txns['transactions'] ) || ! isset($txns['transactions'][$txn_id]) ) {
				return false;
			}

			// If the first transaction is not equal to the added transaction it should be skipped.
			// this might be overchecking since $txns['transactions'] is an array with transaction id as key, which is always the same with `->get( 'id' )`.
			// I'll keep it for "legacy" reasons.
			if ( $txn_id !== $txns['transactions'][$txn_id]->get( 'id' ) ) {
				return false;
			}
		}

		return true;
	}

	private function track_refund_v4( $order, $order_id, $txn ) {
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
					'transaction_id' => $order_id,
					'value'          => $txn->get( 'refund_amount' ),
					'currency'       => $order->get( 'currency' ),
				),
			),
		);

		$args = array(
			'events'    => $events,
			'client_id' => monsterinsights_get_client_id( $order_id ),
		);

		if ( $txn->get_refundable_amount() > 0 ) {
			$args['events'][0]['params']['items'] = array(
				array(
					'item_id'  => $order->get( 'product_id' ),
					'quantity' => 1,
				)
			);
		}

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->get( 'user_id' ); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Send refund data to GA.
	 *
	 * @param LLMS_Transaction $txn Transaction object.
	 *
	 * @return void
	 * @since [version]
	 */
	public function remove_order( $txn ) {

		$txn_id   = $txn->get( 'id' );
		$order    = $txn->get_order();
		$order_id = $order->get( 'id' );

		// If not in GA or skip is on, then skip
		$is_in_ga = get_post_meta( $order_id, '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $order_id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		if ( ! $this->can_track_order( $order, $txn_id ) ) {
			return;
		}

		$this->track_refund_v4( $order, $order_id, $txn );

		update_post_meta( $order_id, '_monsterinsights_refund_is_in_ga', 'yes' );

		update_post_meta( $txn_id, '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	private function get_cart_items_v4( $plan ) {
		$ua_items = $this->get_cart_items( $plan );

		return array(
			array(
				'item_id'       => $ua_items['pr1id'],
				'item_name'     => $ua_items['pr1nm'],
				'item_category' => $ua_items['pr1ca'],
				'item_variant'  => $ua_items['pr1va'],
				'price'         => $ua_items['pr1pr'],
				'quantity'      => 1,
			),
		);
	}

	/**
	 * Retrieve an array of "cart" item data for use in various GA Events.
	 *
	 * @param mixed $plan LLMS_Access_Plan or WP_Post ID of the plan.
	 *
	 * @return array
	 * @since [version]
	 */
	protected function get_cart_items( $plan ) {

		$plan       = is_numeric( $plan ) ? llms_get_post( $plan ) : $plan;
		$product_id = $plan->get( 'product_id' );

		if ( $plan->is_free() ) {
			$price = 0;
		} else {
			$price_key = 'price';
			if ( $plan->has_trial() ) {
				$price_key = 'trial_price';
			} else if ( $plan->is_on_sale() ) {
				$price_key = 'sale_price';
			}

			$price = $plan->get( $price_key );
		}

		return array(
			'pr1id' => $product_id, // Product ID
			'pr1nm' => get_the_title( $product_id ), // Product Name
			'pr1ca' => $this->get_product_category( $product_id ), // Product Category
			'pr1va' => $plan->get( 'title' ), // Product Variation Title
			'pr1pr' => MonsterInsights_eCommerce_Helper::round_price( $price ), // Product Price
			'pr1qt' => 1, // Product Quantity
			'pr1ps' => 1, // Product Order
		);
	}

	/**
	 * Setup LifterLMS Funnel Steps
	 *
	 * @return array
	 * @since [version]
	 */
	private function get_funnel_steps() {
		return array(
			'clicked_product'    => array(
				'action' => 'click',
				'step'   => 1,
			),
			'viewed_product'     => array(
				'action' => 'detail',
				'step'   => 2,
			),
			'started_checkout'   => array(
				'action' => 'checkout',
				'step'   => 3,
			),
			'completed_purchase' => array(
				'action' => 'purchase',
				'step'   => 4,
			),
		);
	}

	/**
	 * Funnel Step: Clicked Product.
	 *
	 * @return void
	 * @since [version]
	 */
	public function do_step_clicked_product() {

		// Don't do it on feeds.
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::llms_test_mode() ) {
			return;
		}

		global $post;
		if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
			return;
		}

		$product = llms_get_post( $post );
		if ( ! $product ) {
			return;
		}

		$id   = $product->get( 'id' );
		$list = $this->get_list_type();

		$js =
			"var element = document.querySelector( '.llms-loop .post-" . esc_js( $id ) . " a' );
			element && element.addEventListener('click', e => {
				" . $this->enhanced_ecommerce_add_product( $id ) . "
				" . $this->get_funnel_js( 'clicked_product', array( 'list' => $list ) ) . "
				" . $this->get_event_js( array(
				'hitType'       => 'event',
				'eventCategory' => $product->get_post_type_label( 'name' ),
				'eventAction'   => 'click',
				'eventLabel'    => htmlentities( $product->get( 'title' ), ENT_QUOTES, 'UTF-8' ),
			) ) . "
			});";

		$this->enqueue_js( 'event', $js );
	}

	/**
	 * Funnel Step: Viewed Course/Membership
	 *
	 * @return void
	 * @since [version]
	 */
	public function do_step_viewed_product() {

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// If page reload, then return
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::llms_test_mode() ) {
			return;
		}

		$product_id = get_the_ID();
		$product    = llms_get_post( $product_id );
		if ( ! $product ) {
			return;
		}

		$product_data = $this->get_product_details( $product_id );
		$event_js     = sprintf( "__gtagTracker( 'event', 'view_item', { items: [%s] });", json_encode( $product_data ) );
		$this->enqueue_js( 'event', $event_js );
	}

	private function track_begin_checkout_v4( $plan ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$items = $this->get_cart_items_v4( $plan );

		$events = array(
			array(
				'name'   => 'begin_checkout',
				'params' => array(
					'items' => $items,
				),
			),
		);

		$args = array(
			'events' => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) && is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Funnel Step: Checkout Started.
	 *
	 * @return void
	 * @since [version]
	 */
	public function do_step_started_checkout() {

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		if ( ! function_exists( 'llms_filter_input' ) ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::llms_test_mode() ) {
			return;
		}

		// "Cart" is empty.
		$plan_id = llms_filter_input( INPUT_GET, 'plan', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $plan_id ) {
			return;
		}

		// Invalid Access Plan.
		$plan = llms_get_post( $plan_id );
		if ( ! $plan ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! $this->can_user_be_tracked( get_current_user_id() ) ) {
			return;
		}

		$this->track_begin_checkout_v4( $plan );
	}

	/**
	 * Add JS scripts to be output.
	 *
	 * @param string $type JS type [event|impression].
	 * @param string $javascript JS script.
	 *
	 * @return void
	 * @since [version]
	 */
	public function enqueue_js( $type, $javascript ) {

		if ( ! isset( $this->queued_js[ $type ] ) ) {
			$this->queued_js[ $type ] = array();
		}

		$this->queued_js[ $type ][] = $javascript;
	}

	/**
	 * Generate addProduct script
	 *
	 * @param int $product_id WP_Post ID
	 *
	 * @return string
	 * @since [version]
	 */
	private function enhanced_ecommerce_add_product( $product_id ) {

		$data = $this->get_product_details( $product_id );

		return $this->get_add_product_js( $data );
	}

	private function get_product_details( $product_id ) {
		global $wp_query;

		$data = array(
			'id'       => $product_id,
			'name'     => get_the_title( $product_id ),
			'position' => $wp_query->current_post + 1,
			'category' => $this->get_product_category( $product_id ),
		);

		$tax = $this->get_product_category_tax( $product_id );
		if ( $tax ) {
			$terms = wp_list_pluck( (array) get_the_terms( $product_id, $tax ), 'name' );
			if ( $terms && ! empty( $terms[0] ) ) {
				$data['category'] = $terms[0];
			}
		}

		return $data;
	}

	/**
	 * Retrieve the first category term for a given product
	 *
	 * @param int $product_id WP_Post ID.
	 *
	 * @return string
	 * @since [version]
	 */
	protected function get_product_category( $product_id ) {

		$cat = '';

		$tax = $this->get_product_category_tax( $product_id );
		if ( $tax ) {
			$terms = wp_list_pluck( (array) get_the_terms( $product_id, $tax ), 'name' );
			if ( $terms ) {
				$cat = $terms[0];
			}
		}

		return $cat;
	}


	/**
	 * Retrieve the GA list for the current page type.
	 *
	 * @return string
	 * @since [version]
	 */
	protected function get_list_type() {
		global $wp_query;
		$list_type = '';
		if ( is_search() ) {
			$list_type = __( 'Search', 'ga-ecommerce' );
		} else if ( is_tax( 'course_cat' ) ) {
			$list_type = __( 'Course category', 'ga-ecommerce' );
		} else if ( is_tax( 'membership_cat' ) ) {
			$list_type = __( 'Membership category', 'ga-ecommerce' );
		} else if ( is_tax( 'course_tag' ) ) {
			$list_type = __( 'Course tag', 'ga-ecommerce' );
		} else if ( is_tax( 'membership_tag' ) ) {
			$list_type = __( 'Membership tag', 'ga-ecommerce' );
		} else if ( is_front_page() || is_home() ) {
			$list_type = __( 'Homepage', 'ga-ecommerce' );
		} else if ( is_post_type_archive( $this->post_types ) ) {
			$list_type = __( 'Archive', 'ga-ecommerce' );
		} else if ( $wp_query->get_queried_object() ) {
			$query     = $wp_query->get_queried_object();
			$list_type = isset( $query->post_title ) ? $query->post_title : '';
		}

		return apply_filters( 'monsterinsights_llms_get_list_type', $list_type );
	}

	/**
	 * Get Taxonomy name for a given product.
	 *
	 * @param int $product_id WP_Post ID.
	 *
	 * @return string|false
	 * @since [version]
	 */
	protected function get_product_category_tax( $product_id ) {

		$post_type = get_post_type( $product_id );
		if ( 'course' === $post_type ) {
			return 'course_cat';
		} else if ( 'llms_membership' === $post_type ) {
			return 'membership_cat';
		}

		return false;
	}

	/**
	 * Store GA Client ID on an order when a new order is created.
	 *
	 * @param LLMS_Order $order Order object.
	 *
	 * @return void
	 * @since [version]
	 */
	public function save_user_cid( $order ) {

		$order_id = $order->get( 'id' );

		$tracked_already = get_post_meta( $order_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		// Skip tracking?
		if ( ! $this->can_user_be_tracked( $order->get( 'user_id' ) ) ) {
			update_post_meta( $order_id, '_monsterinsights_ecommerce_do_not_track', true );
		}

		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			update_post_meta( $order_id, '_yoast_gau_uuid', $ga_uuid );
			update_post_meta( $order_id, '_monsterinsights_cookie', $cookie );
		}

        if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
            $this->save_user_session_id( $order_id, $measurement_id );
        }
	}

}
