<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class MonsterInsights_eCommerce_EDD_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/** @var int What number is this to output * */
	private $position = 1;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_EDD_Integration();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function hooks() {
		// If ec.js isn't already requested, add it now
		add_filter( 'monsterinsights_frontend_tracking_options_analytics_before_scripts', array(
			$this,
			'require_ec'
		), 10, 1 );

		// Setup Funnel steps for EDD
		$this->funnel_steps = $this->get_funnel_steps();

		// Impression: User sees the product in a list
		add_action( 'edd_purchase_link_end', array( $this, 'impression' ), 10, 2 );

		// Click: user then clicks on product listing to view more about product
		// Note: EDD has no standard for link clicks on lists, so we can't use this in the funnel yet.

		// View details: user views product details
		add_action( 'template_redirect', array( $this, 'product_detail' ), 10, 2 );

		// Add to cart
		add_action( 'edd_pre_add_to_cart', array( $this, 'add_to_cart' ), 10, 2 );

		// Update cart quantity
		add_action( 'wp_ajax_edd_update_quantity', array( $this, 'change_cart_quantity' ), 5 );
		add_action( 'wp_ajax_nopriv_edd_update_quantity', array( $this, 'change_cart_quantity' ), 5 );

		// Remove from Cart
		add_action( 'edd_pre_remove_from_cart', array( $this, 'remove_from_cart' ), 10, 1 );

		// Checkout Page
		add_action( 'edd_before_checkout_cart', array( $this, 'checkout_page' ) );

		// Save CID on checkout
		add_action( 'edd_insert_payment', array( $this, 'save_user_cid' ), 10, 2 );

		// Add Order to GA
		add_action( 'edd_update_payment_status', array( $this, 'add_order' ), 10, 3 );

		// Remove Order from GA
		add_action( 'edd_update_payment_status', array( $this, 'remove_order' ), 10, 3 );

		// Catch all EDD changes
		add_action( 'edd_payment_saved', array( $this, 'catch_edd_payment_saves' ), 10, 2 );

		// PayPal Redirect
		add_filter( 'edd_paypal_redirect_args', array( $this, 'change_paypal_return_url' ) );
	}

	public function edd_get_price( $download_id = 0, $price_id = 0 ) {
		$prices = edd_get_variable_prices( $download_id );
		$amount = 0.00;
		if ( is_array( $prices ) && ! empty( $prices ) ) {
			if ( isset( $prices[ $price_id ] ) ) {
				$amount = $prices[ $price_id ]['amount'];
			} else {
				$amount = edd_get_download_price( $download_id );
			}
		} else {
			$amount = edd_get_download_price( $download_id );
		}

		return apply_filters( 'edd_get_price_option_amount', edd_sanitize_amount( $amount ), $download_id, $price_id );
	}


	public function require_ec( $options ) {
		if ( empty( $options['ec'] ) ) {
			$options['ec'] = "'require', 'ec'";
		}

		return $options;
	}

	public function impression( $download_id, $args ) {

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}
		// If this is a single download page, exit if for the same product
		if ( is_singular( 'download' ) && get_the_ID() === (int) $download_id ) {
			return;
		}

		$download       = new EDD_Download( $download_id );
		$categories     = get_the_terms( $download->ID, 'download_category' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );
		$variation      = ! empty( $args['price_id'] ) ? absint( $args['price_id'] ) : '';

		// @todo: Do we want to make impressions unique per product? Current thoughts are no, it should be true to count displayed
		//      like ads.
		$data = array(
			'id'       => $download->ID,
			'name'     => $download->post_title,
			'list'     => $this->get_list_type( $download->ID ),
			'brand'    => '', // @todo: use this for FES
			'category' => $first_category, // @todo: Possible  hierarchy the cats in the future
			'variant'  => $variation,
			'position' => $this->position, // @todo: possibly don't send
			'price'    => $this->edd_get_price( $download->ID, $variation ),
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
		// No standard yet in EDD core :(
	}


	public function product_detail() {

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		//  Return if not a single download page, or if the ID !== the one displayed
		if ( ! is_singular( 'download' ) ) {
			return;
		}

		$product_id = get_the_ID();

		// If page reload, then return
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		$product_data = $this->get_product_details( $product_id );
		$event_js     = sprintf( "__gtagTracker( 'event', 'view_item', { items: [%s] });", json_encode( $product_data ) );
		$this->enqueue_js( 'event', $event_js );
	}

	private function get_tracked_item_info( $download_id, $item_options, $override_quantity = null, $override_price = null ) {
		$download       = new EDD_Download( $download_id );
		$categories     = get_the_terms( $download->ID, 'download_category' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );
		$price_id       = isset( $item_options['price_id'] ) ? $item_options['price_id'] : null;
		$prices         = $download->get_prices();
		$price_options  = ! is_null( $price_id ) && array_key_exists( $price_id, $prices ) && ! is_null( $prices[ $price_id ] ) ? $prices[ $price_id ] : [];

		$variation      = isset( $price_id ) && isset( $price_options['name'] ) ? $prices[ $price_id ]['name'] : '';
		if ( is_null( $override_price ) ) {
			$price = isset( $price_id ) && ! isset( $price_options[ 'amount' ] ) ? $price_options['amount'] : '';
			if ( empty( $price ) ) {
				$price = $download->get_price();
			 }
		} else {
			$price = $override_price;
		}

		if ( empty( $override_quantity ) ) {
			$quantity = isset( $item_options['quantity'] ) ? (int) $item_options['quantity'] : 1;
		} else {
			$quantity = $override_quantity;
		}

		return array(
			'item_id'       => $download->ID,
			'item_name'     => $download->post_title,
			'item_category' => $first_category,
			'item_variant'  => $variation,
			'price'         => $price,
			'quantity'      => $quantity,
		);
	}

	private function track_add_to_cart_v4( $download_id, $cart_options, $override_quantity = null ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$item = $this->get_tracked_item_info( $download_id, $cart_options, $override_quantity );

		$events = array(
			array(
				'name'   => 'add_to_cart',
				'params' => array(
					'value' => $item['quantity'] * $item['price'],
					'items' => array( $item ),
				),
			),
		);

		$args = array(
			'events' => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) && is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id();
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function add_to_cart( $download_id, $options ) {
		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		$this->track_add_to_cart_v4( $download_id, $options );
	}

	public function change_cart_quantity() {
		// If we don't have the quantity & download id of change, then return
		if ( empty( $_POST['quantity'] ) || empty( $_POST['download_id'] ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		// Get download ID
		$download = new EDD_Download( $_POST['download_id'] );

		// Let's see if this is for a variation
		$options = isset( $_POST['options'] ) ? maybe_unserialize( stripslashes( $_POST['options'] ) ) : array();

		$original_quantity = edd_get_cart_item_quantity( $download->ID, $options );
		$new_quantity      = absint( $_POST['quantity'] );

		// If we are not really changing quantity, return
		if ( $original_quantity === $new_quantity ) {
			return;
		}


		$diff        = $new_quantity - $original_quantity;
		$download_id = sanitize_text_field( wp_unslash( $_POST['download_id'] ) );

		if ( $diff > 0 ) {
			$this->track_add_to_cart_v4( $download_id, $options, $diff );
		} elseif ( $diff < 0 ) {
			$this->track_remove_from_cart_v4( $download_id, $options, - $diff );
		}
	}

	private function track_remove_from_cart_v4( $download_id, $cart_options, $override_quantity = null ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$item = $this->get_tracked_item_info( $download_id, $cart_options, $override_quantity );

		$args = array(
			'events' => array(
				array(
					'name'   => 'remove_from_cart',
					'params' => array(
						'currency' => edd_get_currency(),
						'items'    => array( $item ),
						'value'    => $item['quantity'] * $item['price'],
					)
				)
			)
		);

		if ( monsterinsights_get_option( 'userid', false ) && is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id(); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function remove_from_cart( $cart_key ) {
		$cart_contents = edd_get_cart_contents();

		// If the cart key provided is invalid, not much we can do.
		if ( ! isset( $cart_contents[ $cart_key ] ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$do_not_track = ! monsterinsights_track_user( get_current_user_id() );
			if ( $do_not_track ) {
				return;
			}
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		$this->track_remove_from_cart_v4( $cart_contents[ $cart_key ]['id'], $cart_contents[ $cart_key ]['options'] );
	}

	private function get_tracked_cart_items_v4( $cart_contents, $is_checkout = false ) {
		$items = array();
		foreach ( $cart_contents as $key => $item ) {
			$override_price = null;

			if ( $is_checkout ) {
				$override_price = empty( $item['price'] ) ? 0 : $item['price'];
			}

			$v4_item = $this->get_tracked_item_info( $item['id'], $item['item_number']['options'], $item['quantity'], $override_price );

			$items[] = $v4_item;
		}

		return $items;
	}

	private function track_checkout_v4( $cart_contents ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$value = 0;
		if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
			foreach ( $cart_contents as $cart_content ) {
				$value = $cart_content['price'] + $value;
			}
		}

		$events = array(
			array(
				'name'   => 'begin_checkout',
				'params' => array(
					'currency' => edd_get_currency(),
					'value'    => edd_get_cart_total(),
					'items'    => $this->get_tracked_cart_items_v4( $cart_contents ),
				)
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

		// If not EDD checkout page, return
		if ( ! edd_is_checkout() ) {
			return;
		}

		// If page refresh, don't re-track
		if ( monsterinsights_is_page_reload() ) {
			return;
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		$cart_contents = edd_get_cart_content_details();

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

		$this->track_checkout_v4( $cart_contents );
	}

	public function save_user_cid( $payment_id ) {
		$tracked_already = get_post_meta( $payment_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$payment      = new EDD_Payment( $payment_id );
			$do_not_track = ! monsterinsights_track_user( $payment->user_id );
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

	private function track_purchase_v4( $payment_id, $discount, $cart_contents ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$affiliate_id = MonsterInsights_eCommerce_Helper::easy_affiliate()->get_easy_affiliation_edd_affiliate_id( $payment_id );

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id' => $payment_id,
					'value'          => edd_get_payment_amount( $payment_id ),
					'tax'            => edd_use_taxes() ? edd_get_payment_tax( $payment_id ) : 0,
					'coupon'         => empty( $discount ) ? '' : $discount,
					'currency'       => edd_get_currency(),
					'items'          => $this->get_tracked_cart_items_v4( $cart_contents, true ),
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
			$affiliate_id = MonsterInsights_eCommerce_Helper::get_affiliate_wp_affiliate_id( $payment_id, 'edd' );

			if ( is_int( $affiliate_id ) && $affiliate_id > 0 ) {
				$events[0]['params']['affiliate_label'] = $affiliate_id;
			}
		}

		$args = array(
			'client_id' => monsterinsights_get_client_id( $payment_id ),
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = edd_get_payment_user_id( $payment_id ); // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	public function add_order( $payment_id, $new_status, $old_status ) {

		// If not a completed or published payment, then return
		if ( 'publish' !== $new_status && 'edd_subscription' !== $new_status && 'complete' !== $new_status ) {
			return;
		}

		// Don't track test sessions.
		if ( MonsterInsights_eCommerce_Helper::edd_test_mode() ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( 'edd_subscription' === $new_status && $skip_renewals ) {
			return;
		}

		$is_in_ga = get_post_meta( $payment_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $payment_id );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			$payment      = new EDD_Payment( $payment_id );
			$do_not_track = ! monsterinsights_track_user( $payment->user_id );
			if ( $do_not_track ) {
				return;
			}
		}

		$payment_meta = edd_get_payment_meta( $payment_id );

		// If there's no cart contents, then return
		if ( empty( $payment_meta['cart_details'] ) ) {
			return;
		}

		$cart_contents = $payment_meta['cart_details'];
		$discount      = ! empty( $payment_meta['user_info']['discount'] ) ? $payment_meta['user_info']['discount'] : 'none';
		$discount      = $discount != 'none' ? explode( ',', $discount ) : null;
		$discount      = is_array( $discount ) ? reset( $discount ) : $discount;

		$this->track_purchase_v4( $payment_id, $discount, $cart_contents );

		update_post_meta( $payment_id, '_monsterinsights_is_in_ga', 'yes' );
	}

	private function track_refund_v4( $payment_id, $cart_contents ) {
		if ( ! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
		     ! function_exists( 'monsterinsights_mp_collect_v4' ) ||
		     ! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$items = array();

		foreach ( $cart_contents as $key => $item ) {
			$items[] = array(
				'item_id'  => $item['id'],
				'quantity' => $item['quantity'],
			);
		}

		$events = array(
			array(
				'name'   => 'refund',
				'params' => array(
					'currency'       => edd_get_currency(),
					'transaction_id' => $payment_id,
					'value'          => edd_get_payment_amount( $payment_id ),
					'items'          => $items,
				),
			),
		);

		$args = array(
			'events'    => $events,
			'client_id' => monsterinsights_get_client_id( $payment_id ),
		);

		monsterinsights_mp_collect_v4( $args );
	}

	public function remove_order( $payment_id, $new_status, $old_status ) {
		// If not a refunded or revoked order skip
		if ( $new_status !== 'refunded' ) {
			return;
		}

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		if ( 'edd_subscription' === $old_status && $skip_renewals ) {
			return;
		}

		// If not in GA or skip is on, then skip
		$is_in_ga = get_post_meta( $payment_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $payment_id );
		if ( $is_in_ga !== 'yes' || $skip_ga ) {
			return;
		}

		$payment_meta = edd_get_payment_meta( $payment_id );

		// If there's no cart contents, then return
		if ( empty( $payment_meta['cart_details'] ) ) {
			return;
		}

		$cart_contents = $payment_meta['cart_details'];

		$this->track_refund_v4( $payment_id, $cart_contents );

		delete_post_meta( $payment_id, '_monsterinsights_is_in_ga' );
	}

	public function catch_edd_payment_saves( $payment_id, $payment ) {
		if ( $payment->status === 'refunded' ) {
			$this->remove_order( $payment_id, 'refunded', 'pending' );
		} else if ( 'publish' === $payment->status ) {
			$this->add_order( $payment_id, 'publish', 'pending' );
		} else if ( 'edd_subscription' === $payment->status ) {
			$this->add_order( $payment_id, 'edd_subscription', 'pending' );
		} else if ( 'complete' === $payment->status ) {
			$this->add_order( $payment_id, 'complete', 'pending' );
		}
		// Else we don't care
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
	public function change_paypal_return_url( $paypal_args ) {
		$paypal_args['return'] = add_query_arg( array( 'utm_nooverride' => '1' ), $paypal_args['return'] );

		return $paypal_args;
	}

	public function get_list_type( $download_id = 0 ) {
		$list_type = '';
		if ( is_search() ) {
			$list_type = __( 'Search', 'ga-ecommerce' );
		} elseif ( is_tax( 'download_category' ) ) {
			$list_type = __( 'Product category', 'ga-ecommerce' );
		} elseif ( is_tax( 'download_tag' ) ) {
			$list_type = __( 'Product tag', 'ga-ecommerce' );
		} elseif ( is_post_type_archive( 'download' ) ) {
			$list_type = __( 'Archive', 'ga-ecommerce' );
		} elseif ( is_singular( 'download' ) && (int) get_the_ID() !== (int) $download_id ) {
			$list_type = __( 'Recommended Products', 'ga-ecommerce' );
		} elseif ( edd_is_checkout() ) {
			$list_type = __( 'Recommended Products', 'ga-ecommerce' );
		} else {
			// @todo: we could use the current url for the list name
		}

		return $list_type; // @todo: allow filtering?
	}

	private function enhanced_ecommerce_add_product( $product_id, $price_id = false, $quantity = 1 ) {
		$data = $this->get_product_details( $product_id, $price_id, $quantity );

		return $this->get_add_product_js( $data );
	}

	private function get_product_details( $product_id, $price_id = false, $quantity = 1 ) {
		$download       = new EDD_Download( $product_id );
		$categories     = get_the_terms( $download->ID, 'download_category' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );
		$price_options  = $download->get_prices();
		$price_id       = ( $price_id === false || $price_id === null ) ? $price_id : '';
		$variation      = isset( $price_options[ $price_id ] ) ? $price_options[ $price_id ]['name'] : '';

		$data = array(
			'id'       => $download->ID,
			'name'     => $download->post_title,
			'quantity' => $quantity,
			'brand'    => '', // @todo: use this for FES
			'category' => $first_category, // @todo: Possible  hierarchy the cats in the future
			'variant'  => $variation,
			'position' => '',
			'price'    => $this->edd_get_price( $download->ID, $variation ),
		);

		return $data;
	}

	private function get_funnel_steps() {
		return array(
			// @todo: Unlike WooCommerce, EDD currently has no standard for click on lists
			// so we can't use it in the funnel yet :(
			//'clicked_product' => array(
			//	'action' => 'click',
			//	'step'   => 1,
			//),
			'viewed_product'     => array(
				'action' => 'detail',
				'step'   => 1,
			),
			'added_to_cart'      => array(
				'action' => 'add',
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
}
