<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_WooCommerce_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/** @var int What number is this to output * */
	private $position = 1;

	/** @var bool Has tracked on the page for the detail * */
	private $has_tracked_detail = false;

	/** @var @var int Will populate with order ID if a order is fully refunded */
	private $remove_from_ga = 0;

	/**
	 * @var int In case no product list type can be detected, assume the impressions are
	 *          for one single product list with each product occupying a sequential position
	 *          on the list
	 */
	private $unlisted_product_position = 0;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_WooCommerce_Integration();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function hooks() {

		// Setup Funnel steps for WooCommerce
		$this->funnel_steps = $this->get_funnel_steps();

		// Impression: User sees the product in a list
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'impression' ) );

		// Click: user then clicks on product listing to view more about product
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'product_click' ) );

		// View details: user views product details
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'product_detail' ), 1 );
		add_action( 'init', array( $this, 'check_for_us_plugin' ), 12 );

		// Add to cart
		add_action( 'woocommerce_add_to_cart', array( $this, 'add_to_cart' ), 10, 4 );

		// Update cart quantity
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'change_cart_quantity' ), 10, 3 );

		// Remove from Cart
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'remove_from_cart' ) );
		add_action( 'woocommerce_remove_cart_item', array( $this, 'remove_from_cart' ) );

		// Checkout Page
		add_action( 'woocommerce_after_checkout_form', array( $this, 'checkout_page' ) );

		// Save CID on checkout
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_user_cid' ), 10, 1 );
		add_action( 'wc_bolt_process_payment', array( $this, 'save_user_cid' ), 10, 1 );

		// Add Order to GA.
		foreach ( MonsterInsights_eCommerce_Helper::woocommerce_ga_order_statuses() as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( $this, 'add_order' ), 10 );
		}

		// Remove Order from GA
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'remove_order' ), 10, 3 );
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'refund_full_order' ), 10, 2 );

		// PayPal Redirect
		add_filter( 'woocommerce_get_return_url', array( $this, 'change_paypal_return_url' ) );
	}

	public function impression() {
		global $product, $woocommerce_loop;

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$variation_id = version_compare( WC_VERSION, '3.0', '<' ) ? $product->id : $product->get_id();
		$product_id   = wp_get_post_parent_id( $variation_id );
		$product      = null;
		$variation    = '';

		// We need to see if the product_id in question is the post ID of a
		// variation, or of the parent post. We need the parent's ID for a variable product
		if ( $product_id == false ) {
			// If getting the parent post ID failed, this is the post id of a non-variable
			// product, or the parent post ID for a variable product.
			$product_id = $variation_id; // Set the product ID back to the variation ID
			$product    = wc_get_product( $product_id );
		} else {
			// That product ID was the post ID for a variation.
			$product = wc_get_product( $variation_id );
			if ( method_exists( $product, 'get_name' ) ) {
				$variation = $product->get_name();
			} else {
				$variation = $product->post->post_title;
			}
		}

		$categories     = get_the_terms( $product_id, 'product_cat' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );

		$list = $this->get_list_type( $product_id );
		if ( empty( $list ) ) {
			$this->unlisted_product_position ++;
			$position = $this->unlisted_product_position;
		} else {
			$position = isset( $woocommerce_loop['loop'] ) ? $woocommerce_loop['loop'] : 1;
		}

		$data = array(
			'id'       => $product_id,
			'name'     => get_the_title( $product_id ),
			'list'     => $list,
			'brand'    => '', // @todo: use this for WC Product Vendors
			'category' => $first_category, // @todo: Possible  hierarchy the cats in the future
			'variant'  => $variation,
			'position' => $position,
			'price'    => MonsterInsights_eCommerce_Helper::round_price( $product->get_price() ),
		);

		// @todo: Author + other custom dimensions scoped to products
		$this->position = $this->position + 1;

		// Unset empty values to reduce request size
		foreach ( $data as $key => $value ) {
			if ( empty( $value ) ) {
				unset( $data[ $key ] );
			}
		}
		$this->add_impression( $data );
	}

	public function product_click() {
		global $product;

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$list       = $this->get_list_type();
		$properties = array(
			'eventCategory' => 'Products',
			'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
		);
		$id         = version_compare( WC_VERSION, '3.0', '<' ) ? $product->id : $product->get_id();
		$js         =
			"var element = document.querySelector( '.products .post-" . esc_js( $id ) . " a' );
			element && element.addEventListener('click', e => {
				if ( element.classList.contains( 'add_to_cart_button' ) ) {
					return;
				};
				" . $this->enhanced_ecommerce_add_product( $id ) . "
				" . $this->get_funnel_js( 'clicked_product', array( 'list' => $list ) ) . "
				" . $this->get_event_js( array(
				'hitType'       => 'event',
				'eventCategory' => 'Products',
				'eventAction'   => 'click',
				'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			) ) . "
			});";

		$this->enqueue_js( 'event', $js );
	}

	public function check_for_us_plugin() {
		if ( function_exists( 'us_woocommerce_before_main_content' ) ) {
			remove_action( 'woocommerce_before_main_content', 'us_woocommerce_before_main_content', 10 );
			add_action( 'woocommerce_before_main_content', array(
				$this,
				'better_us_woocommerce_before_main_content'
			), 10 );
		}
	}

	public function better_us_woocommerce_before_main_content() {
		if ( ! is_single() ) {
			return;
		}
		$this->product_detail();
		us_woocommerce_before_main_content();
	}

	public function product_detail() {

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
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

		$product_id = get_the_ID();

		$product_data = $this->get_product_details( $product_id );
		$event_js     = sprintf( "__gtagTracker( 'event', 'view_item', { items: [%s] });", json_encode( $product_data ) );
		$this->enqueue_js( 'event', $event_js );
	}

	private function get_tracked_item_v4( $product_id, $variation_id, $quantity, $price = null ) {
		$variation = '';
		$product   = null;

		if ( ! empty( $variation_id ) ) {
			$product = wc_get_product( $variation_id );
			if ( method_exists( $product, 'get_name' ) ) {
				$variation = $product->get_name();
			} else {
				$variation = $product->post->post_title;
			}

			// If no parent ID is found, that means this variation ID is actually the product ID
			if ( empty( $product_id ) ) {
				$product_id = $variation_id;
			}
		} else {
			$product = wc_get_product( $product_id );
		}

		if ( is_object( $product ) && ! empty( $product ) ) {
			if ( is_null( $price ) ) {
				$price = $product->get_price();
			}
		} else {
			if ( ! empty( $product_id ) ) {
				if ( is_null( $price ) ) {
					$price = get_post_meta( $product_id, '_regular_price', true );
				}
			}
		}

		$categories     = get_the_terms( $product_id, 'product_cat' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );

		return array(
			'item_id'       => $product_id,
			'item_name'     => get_the_title( $product_id ),
			'item_category' => $first_category,
			'item_variant'  => $variation,
			'price'         => $price,
			'quantity'      => (int) $quantity,
		);
	}

	private function track_add_to_cart_v4( $product_id, $variation_id, $quantity ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$item = $this->get_tracked_item_v4( $product_id, $variation_id, $quantity );

		$quantity = 0;
		$price = 0;
		if(isset($item['quantity'])){
			$quantity = (int) $item['quantity'];
		}
		if(isset($item['price'])){
			$price = (int) $item['price'];
		}

		$events = array(
			array(
				'name'   => 'add_to_cart',
				'params' => array(
					'value'    => $quantity * $price,
					'currency' => get_woocommerce_currency(),
					'items'    => array( $item ),
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

	public function add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id = false ) {

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		$this->track_add_to_cart_v4( $product_id, $variation_id, $quantity );
	}

	public function change_cart_quantity( $cart_key, $quantity, $old_quantity ) {
		// If the cart key provided is invalid, not much we can do.
		if ( ! isset( WC()->cart->cart_contents[ $cart_key ] ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		$item = WC()->cart->cart_contents[ $cart_key ];

		$original_quantity = $old_quantity;
		$new_quantity      = $quantity;

		// If we are not really changing quantity, return
		if ( $original_quantity === $new_quantity ) {
			return;
		}

		$product_id   = $item['product_id'];
		$variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : null;
		$variation    = '';
		$product      = null;

		if ( ! empty( $item['variation_id'] ) ) {
			$product = wc_get_product( $item['variation_id'] );
			if ( method_exists( $product, 'get_name' ) ) {
				$variation = $product->get_name();
			} else {
				$variation = $product->post->post_title;
			}
		} else {
			$product = wc_get_product( $product_id );
		}

		$categories     = get_the_terms( $product_id, 'product_cat' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );

		$diff = $new_quantity - $original_quantity;

		if ( $diff > 0 ) {
			$this->track_add_to_cart_v4( $product_id, $variation_id, $diff );
		} elseif ( $diff < 0 ) {
			$this->track_remove_from_cart_v4( $product_id, $variation_id, - $diff );
		}
	}

	private function track_remove_from_cart_v4( $product_id, $variation_id, $quantity ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$item = $this->get_tracked_item_v4( $product_id, $variation_id, $quantity );

		$quantity = 0;
		$price = 0;
		if(isset($item['quantity'])){
			$quantity = (int) $item['quantity'];
		}
		if(isset($item['price'])){
			$price = (int) $item['price'];
		}

		$events = array(
			array(
				'name'   => 'remove_from_cart',
				'params' => array(
					'value'    => $quantity * $price,
					'currency' => get_woocommerce_currency(),
					'items'    => array( $item ),
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

	public function remove_from_cart( $cart_key ) {
		// If the cart key provided is invalid, not much we can do.
		if ( ! isset( WC()->cart->cart_contents[ $cart_key ] ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		$item = WC()->cart->cart_contents[ $cart_key ];

		$product_id   = $item['product_id'];
		$variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : null;

		$this->track_remove_from_cart_v4( $product_id, $variation_id, $item['quantity'] );
	}

	private function track_begin_checkout_v4( $cart_contents ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$items = array();

		foreach ( $cart_contents as $item ) {
			$product_id   = $item['product_id'];
			$variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : null;

			$item = $this->get_tracked_item_v4( $product_id, $variation_id, $item['quantity'] );
			unset( $item['original_price'] );

			$items[] = $item;
		}

		$events = array(
			array(
				'name'   => 'begin_checkout',
				'params' => array(
					'items' => $items,
				),
			)
		);

		$args = array(
			'events' => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) && is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function checkout_page() {

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		$cart_contents = WC()->cart->get_cart();

		// If there's no cart contents, then return
		if ( empty( $cart_contents ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		$this->track_begin_checkout_v4( $cart_contents );
	}

	public function save_user_cid( $payment_id ) {
		$tracked_already = get_post_meta( $payment_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$order        = wc_get_order( $payment_id );
			$do_not_track = ! monsterinsights_track_user( $order->get_user_id() );
			if ( $do_not_track ) {
				update_post_meta( $payment_id, '_monsterinsights_ecommerce_do_not_track', true );
			}
		}

		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			update_post_meta( $payment_id, '_yoast_gau_uuid', $ga_uuid );
			update_post_meta( $payment_id, '_monsterinsights_cookie', $cookie );
		}

        if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
            $this->save_user_session_id( $payment_id, $measurement_id );
        }
	}

	private function track_purchase_v4( $order, $payment_id, $discount ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$cart_contents = $order->get_items();
		$items         = array();

		foreach ( $cart_contents as $key => $item ) {
			$variation_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$product_id   = $variation_id > 0 ? wp_get_post_parent_id( $variation_id ) : 0;

			$item = $this->get_tracked_item_v4( $product_id, $variation_id, $item->get_quantity(), $order->get_item_total( $item ) );
			unset( $item['original_price'] );

			$items[] = $item;
		}

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $order->get_order_number(),
					'value'          => $order->get_total(),
					'tax'            => $order->get_total_tax(),
					'shipping'       => method_exists( $order, 'get_shipping_total' ) ? $order->get_shipping_total() : $order->get_total_shipping(),
					'coupon'         => $discount,
					'currency'       => $order->get_currency(),
					'items'          => $items,
                    'session_id'     => get_post_meta( $payment_id, '_monsterinsights_ga_session_id', true )
				),
			),
		);

		if ( MonsterInsights_eCommerce_Helper::easy_affiliate()->is_easy_affiliate_active() ) {

			$affiliate_id = MonsterInsights_eCommerce_Helper::easy_affiliate()->get_easy_affiliation_woo_affiliate_id( $payment_id );

			if ( is_int( $affiliate_id ) && $affiliate_id > 0 ) {
				$events[0]['params']['affiliate_label'] = $affiliate_id;
			}
		}

		if ( MonsterInsights_eCommerce_Helper::is_affiliate_wp_active() ) {

			$affiliate_id = MonsterInsights_eCommerce_Helper::get_affiliate_wp_affiliate_id( $payment_id, 'woocommerce' );

			if ( is_int( $affiliate_id ) && $affiliate_id > 0 ) {
				$events[0]['params']['affiliate_label'] = $affiliate_id;
			}
		}

		$args = array(
			'payment_id' => $payment_id,
			'client_id'  => monsterinsights_get_client_id( $payment_id ),
			'events'     => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->get_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function add_order( $payment_id ) {

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $payment_id, 'renewal' ) && $skip_renewals ) {
			return;
		}
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode( $payment_id ) ) {
			return;
		}

		$is_in_ga = get_post_meta( $payment_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $payment_id );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$order = wc_get_order( $payment_id );

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( $order->get_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		$discount = '';
		$coupons  = method_exists( $order, 'get_coupon_codes' ) ? $order->get_coupon_codes() : $order->get_used_coupons();
		if ( sizeof( $coupons ) > 0 ) {
			foreach ( $coupons as $code ) {
				if ( ! $code ) {
					continue;
				} else {
					$discount = $code;
					break;
				}
			}
		}

		$this->track_purchase_v4( $order, $payment_id, $discount );

		update_post_meta( $payment_id, '_monsterinsights_is_in_ga', 'yes' );
	}

	public function refund_full_order( $order_id, $refund_id ) {
		$this->remove_order( $order_id, $refund_id, true );
	}

	private function track_refund_v4( $order_id, $refund_id, $is_total_refund ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$order         = wc_get_order( $order_id );
		$refund        = ! empty( $refund_id ) ? wc_get_order( $refund_id ) : $order;
		$cart_contents = $refund->get_items();

		$items = array();

		if ( ! $is_total_refund ) {
			foreach ( $cart_contents as $key => $item ) {
				if ( $item['qty'] >= 1 && $refund->get_item_total( $item ) <= 0 ) {
					$items[] = array(
						'item_id'  => $key,
						'quantity' => $item->get_quantity(),
					);
				}
			}
		}

		$events = array(
			array(
				'name'   => 'refund',
				'params' => array(
					'transaction_id' => $order->get_order_number(),
					'value'          => $refund->get_amount(),
					'items'          => $items,
				)
			)
		);

		$args = array(
			'events'    => $events,
			'client_id' => monsterinsights_get_client_id( $order_id ),
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $order->get_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function remove_order( $order_id, $refund_id, $is_total_refund = false ) {

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id, 'renewal' ) && $skip_renewals ) {
			return;
		}

		// If not in GA or skip is on, then skip
		$is_in_ga = get_post_meta( $refund_id, '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $order_id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$this->track_refund_v4( $order_id, $refund_id, $is_total_refund );

		update_post_meta( $refund_id, '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	/**
	 * Add utm_nooverride to the PayPal return URL so the original source of the transaction won't be overridden.
	 *
	 * @param array $paypal_args
	 *
	 * @return array
	 * @link  https://support.bigcommerce.com/questions/1693/How+to+properly+track+orders+in+Google+Analytics+when+you+accept+PayPal+as+a+method+of+payment.
	 *
	 * @since 6.0.0
	 *
	 */
	public function change_paypal_return_url( $paypal_url ) {
		// If already added, remove
		$paypal_url = remove_query_arg( 'utm_nooverride', $paypal_url );

		// Add UTM no override
		$paypal_url = add_query_arg( 'utm_nooverride', '1', $paypal_url );

		return $paypal_url;
	}

	public function get_list_type( $product_id = 0 ) {
		global $wp_query;
		$list_type = '';
		if ( is_search() ) {
			$list_type = __( 'Search', 'ga-ecommerce' );
		} elseif ( is_product_category() ) {
			$list_type = __( 'Product category', 'ga-ecommerce' );
		} elseif ( is_product_tag() ) {
			$list_type = __( 'Product tag', 'ga-ecommerce' );
		} elseif ( is_front_page() || is_home() ) {
			$list_type = __( 'Homepage', 'ga-ecommerce' );
		} elseif ( is_post_type_archive( 'product' ) ) {
			$list_type = __( 'Archive', 'ga-ecommerce' );
		} elseif ( is_singular( 'product' ) && (int) get_the_ID() !== (int) $product_id ) {
			$list_type = __( 'Product Related/Upsells', 'ga-ecommerce' );
		} elseif ( is_cart() ) {
			$list_type = __( 'Cart Cross Sell', 'ga-ecommerce' );
		} elseif ( $wp_query->get_queried_object() ) {
			$query     = $wp_query->get_queried_object();
			$list_type = $query->post_title;
		}

		return $list_type; // @todo: allow filtering?
	}

	private function enhanced_ecommerce_add_product( $product_id, $quantity = 1 ) {

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::woocommerce_test_mode() ) {
			return;
		}

		$data = $this->get_product_details( $product_id, $quantity );

		return $this->get_add_product_js( $data );
	}

	private function get_product_details( $product_id, $quantity = 1 ) {
		$variation_id = $product_id;
		$product_id   = wp_get_post_parent_id( $variation_id );
		$product      = null;
		$variation    = '';

		// We need to see if the product_id in question is the post ID of a
		// variation, or of the parent post. We need the parent's ID for a variable product
		if ( $product_id == false ) {
			// If getting the parent post ID failed, this is the post id of a non-variable
			// product, or the parent post ID for a variable product.
			$product_id = $variation_id; // Set the product ID back to the variation ID
			$product    = wc_get_product( $product_id );
		} else {
			// That product ID was the post ID for a variation.
			$product = wc_get_product( $variation_id );
			if ( method_exists( $product, 'get_name' ) ) {
				$variation = $product->get_name();
			} else {
				$variation = $product->post->post_title;
			}
		}

		$categories     = get_the_terms( $product_id, 'product_cat' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );

		$data = array(
			'id'       => $product_id,
			'name'     => get_the_title( $product_id ),
			'brand'    => '', // @todo: use this for WC Product Vendors
			'category' => $first_category, // @todo: Possible  hierarchy the cats in the future
			'variant'  => $variation,
			'quantity' => $quantity,
			'position' => isset( $woocommerce_loop['loop'] ) ? $woocommerce_loop['loop'] : '',
			'price'    => MonsterInsights_eCommerce_Helper::round_price( $product->get_price() ),
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
			'added_to_cart'      => array(
				'action' => 'add',
				'step'   => 3,
			),
			'started_checkout'   => array(
				'action' => 'checkout',
				'step'   => 4,
			),
			'completed_purchase' => array(
				'action' => 'purchase',
				'step'   => 5,
			),
		);
	}
}
