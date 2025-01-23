<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_RCP_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	// Holds instance of RCP_Payments class.
	private $rcp_payments;

	/** @var bool Has tracked on the page for the detail * */
	private $has_tracked_detail = false;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_RCP_Integration();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function hooks() {

		// View details: user views product details
		add_action( 'template_redirect', array( $this, 'product_detail' ), 1 );

		// Checkout Page
		add_action( 'rcp_after_register_form_fields', array( $this, 'checkout_page' ) );

		// Add Order to GA
		add_action( 'rcp_create_payment', array( $this, 'save_user_cid' ), 10, 2 );
		add_action( 'rcp_update_payment_status_complete', array( $this, 'add_order' ), 10 );

		// Remove Order from GA
		add_action( 'rcp_update_payment_status_refunded', array( $this, 'remove_order' ), 10 );
	}

	/**
	 * Track the detail of the page which has been restricted.
	 *
	 * @return void
	 */
	public function product_detail() {
		global $post;

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// If the page is not restricted content.
		if ( ! MonsterInsights_eCommerce_Helper::is_rcp_restricted_content() ) {
			return;
		}

		// Return if this product detail is already tracked. Prevents
		// double tracking as there could be multiple buy buttons on the page.
		if ( $this->has_tracked_detail ) {
			return;
		}

		$this->has_tracked_detail = true;

		// If page reload, then return
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		$product_id = $post->ID;

		$product_data = $this->get_product_details( $product_id );
		$event_js     = sprintf( "__gtagTracker( 'event', 'view_item', { items: [%s] });", json_encode( $product_data ) );

		$this->enqueue_js( 'event', $event_js );
	}

	private function track_checkout_v4() {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$events = array(
			array(
				'name'   => 'begin_checkout',
				'params' => array(),
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
	 * Track when user lands on the checkout page.
	 *
	 * @return void
	 */
	public function checkout_page() {
		global $post;

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		$current_page_id      = $post->ID;
		$rcp_register_page_id = MonsterInsights_eCommerce_Helper::get_rcp_settings( true, 'registration_page' );

		if ( absint( $current_page_id ) !== absint( $rcp_register_page_id ) ) {
			return;
		}

		$registration = new RCP_Registration();

		if ( 'renewal' === $registration->get_registration_type() ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) && is_user_logged_in() ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		$this->track_checkout_v4();
	}

	/**
	 * Save the User Unique GA ID.
	 *
	 * @param int $payment_id ID of the payment.
	 * @param array $payment_args Payment arguments.
	 *
	 * @return void
	 */
	public function save_user_cid( $payment_id, $payment_args ) {
		$rcp_payments = MonsterInsights_eCommerce_Helper::rcp_payments();
		$order        = MonsterInsights_eCommerce_Helper::get_rcp_payment( $payment_id );

		$tracked_already = $rcp_payments->get_meta( $payment_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( $order->user_id );
			if ( $do_not_track ) {
				$rcp_payments->update_meta( $payment_id, '_monsterinsights_ecommerce_do_not_track', true );
			}
		}

		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			$rcp_payments->update_meta( $payment_id, '_yoast_gau_uuid', $ga_uuid );
			$rcp_payments->update_meta( $payment_id, '_monsterinsights_cookie', $cookie );
		}

        if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
			if ( function_exists( 'monsterinsights_get_browser_session_id' ) ) {
				$session_id = monsterinsights_get_browser_session_id( $measurement_id );
				$rcp_payments->update_meta( $payment_id, '_monsterinsights_ga_session_id', $session_id );
			}
        }
	}

	private function track_purchase_v4( $order ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$discount = $order->discount_code ? $order->discount_code : '';

		$rcp_payments = MonsterInsights_eCommerce_Helper::rcp_payments();

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $order->id,
					'value'          => floatval( $order->amount ),
					'coupon'         => $discount,
					'currency'       => rcp_get_currency(),
					'items'          => array(
						array(
							'item_id'   => $order->object_id,
							'item_name' => $order->subscription,
							'price'     => $order->amount,
							'quantity'  => 1,
						),
					),
					'session_id'     => $rcp_payments->get_meta( $order->id, '_monsterinsights_ga_session_id', true ),
				),
			),
		);

		$args = array(
			'events'    => $events,
			'client_id' => MonsterInsights_eCommerce_Helper::get_rcp_client_id( $order->id ),
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->user_id; // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Add order details to GA
	 *
	 * @param int $payment_id ID of the newly created payment.
	 * @param array $args Meta data realated to the payment.
	 *
	 * @return void
	 */
	public function add_order( $payment_id ) {

		$rcp_payments = MonsterInsights_eCommerce_Helper::rcp_payments();
		$order        = MonsterInsights_eCommerce_Helper::get_rcp_payment( $payment_id );

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::is_rcp_test_mode() ) {
			return;
		}

		$is_in_ga = $rcp_payments->get_meta( $payment_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $payment_id );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		if ( 'renewal' === $order->transaction_type ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( $order->user_id );
			if ( $do_not_track ) {
				return;
			}
		}

		$this->track_purchase_v4( $order, $rcp_payments );

		$rcp_payments->update_meta( $payment_id, '_monsterinsights_is_in_ga', 'yes' );
	}

	private function track_refund_v4( $order ) {
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
					'transaction_id' => $order->id,
					'value'          => $order->amount,
					'currency'       => rcp_get_currency(),
					'items'          => array(
						array(
							'item_id'   => $order->object_id,
							'item_name' => $order->subscription,
							'price'     => $order->amount,
							'quantity'  => 1,
						),
					),
				),
			),
		);

		$args = array(
			'client_id' => MonsterInsights_eCommerce_Helper::get_rcp_client_id( $order->id ),
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->user_id;
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Remove an order from GA.
	 *
	 * @param int $refund_id ID of the payment to refund.
	 *
	 * @return void
	 */
	public function remove_order( $refund_id ) {

		$rcp_payments = MonsterInsights_eCommerce_Helper::rcp_payments();

		// If not in GA or skip is on, then skip
		$is_in_ga = $rcp_payments->get_meta( $refund_id, '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $refund_id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$order = MonsterInsights_eCommerce_Helper::get_rcp_payment( $refund_id );

		if ( 'renewal' === $order->transaction_type ) {
			return;
		}

		$this->track_refund_V4( $order );

		$rcp_payments->update_meta( $refund_id, '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	/**
	 * Get details of the product.
	 *
	 * @param int $product_id ID of the product.
	 * @param int $quantity Quantity of the product.
	 *
	 * @return array
	 */
	private function get_product_details( $product_id, $quantity = 1 ) {

		$post_type = get_post_type( $product_id );

		$category = '';

		if ( ! is_page() ) {
			if ( is_singular() ) {
				$categories = $this->custom_taxonomies_terms( $product_id, get_queried_object()->post_type );
				$category   = reset( $categories );
			}
		}

		$data = array(
			'id'       => $product_id,
			'name'     => get_the_title( $product_id ),
			'brand'    => '', // @todo: use this for WC Product Vendors
			'category' => $category,
			'variant'  => $post_type,
			'quantity' => $quantity,
			'position' => 1,
			'price'    => 0,
		);

		$to_replace = array(
			'list'     => 'list_name',
			'position' => 'list_position',
		);

		foreach ( $to_replace as $analytics => $gtag ) {
			if ( isset( $data[ $analytics ] ) ) {
				$data[ $gtag ] = $data[ $analytics ];
				unset( $data[ $analytics ] );
			}
		}

		return $data;
	}

	/**
	 * Get terms associated with the restricted content.
	 *
	 * @param int $id ID of the post.
	 * @param string $post_type Type of the post.
	 *
	 * @return array
	 */
	private function custom_taxonomies_terms( $id, $post_type ) {

		$cats = [];

		// get post type taxonomies
		$taxonomies = get_object_taxonomies( $post_type );

		foreach ( $taxonomies as $taxonomy ) {
			// get the terms related to post
			$terms = get_the_terms( $id, $taxonomy );

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( has_term( $term->term_id, $term->taxonomy, $id ) ) {
						$cats[] = $term->name;
					}
				}
			}
		}

		return $cats;
	}
}
