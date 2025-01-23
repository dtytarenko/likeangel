<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GiveWP E-Commerce class for advance e-commerce tracking.
 *
 * @since 7.4.0
 */
class MonsterInsights_eCommerce_GiveWP_Integration extends MonsterInsights_Enhanced_eCommerce_Integration {

	// Holds instance of eCommerce object to ensure no double instantiation of hooks
	private static $instance;

	/** @var int What number is this to output * */
	private $position = 1;

	/** @var bool Has tracked on the page for the detail * */
	private $has_tracked_detail = false;

	/**
	 * Return Single instance of the class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new MonsterInsights_eCommerce_GiveWP_Integration();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Necessary hooks to track down data.
	 */
	private function hooks() {

		// Impression: User sees the product in a list
		add_action( 'template_redirect', array( $this, 'impression' ) );

		// Click: user then clicks on product listing to view more about product
		add_action( 'template_redirect', array( $this, 'product_click' ) );

		// View details:
		add_action( 'give_donate_form', array( $this, 'product_detail' ), 10, 2 );

		add_action( 'give_insert_payment', array( $this, 'save_user_cid' ), 10, 2 );

		// Add Order to GA
		add_action( 'give_complete_donation', array( $this, 'add_order' ), 10, 1 );

		// Remove Order from GA
		add_action( 'give_update_payment_status', array( $this, 'remove_order' ), 10, 3 );
	}

	/**
	 * Add GA Impression when a user visits category page.
	 *
	 * @return void
	 */
	public function impression() {

		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::givewp_is_test_mode() ) {
			return;
		}

		// If it is not a GiveWP form category page. Bail out.
		if ( ! is_give_category() ) {
			return;
		}

		$forms         = array();
		$give_category = get_queried_object();
		$forms         = $this->get_give_forms_by_category( $give_category->term_id );

		if ( is_array( $forms ) && ! empty( $forms ) ) {

			foreach ( $forms as $index => $form ) {
				$product_id = $form->ID;
				$data       = array(
					'id'       => $product_id,
					'name'     => get_the_title( $product_id ),
					'list'     => $this->get_list_type( $product_id ),
					'category' => $give_category->name,
					'position' => $this->position
				);

				$this->position = $this->position + 1;

				$this->add_impression( $data );
			}
		}
	}

	/**
	 * Track when a user has clicked on a product/form on category page.
	 *
	 * @return void
	 */
	public function product_click() {
		// Don't do it on feeds
		if ( is_feed() ) {
			return;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::givewp_is_test_mode() ) {
			return;
		}

		// If it is not a GiveWP form category page. Bail out.
		if ( ! is_give_category() ) {
			return;
		}

		$forms         = array();
		$give_category = get_queried_object();
		$forms         = $this->get_give_forms_by_category( $give_category->term_id );

		if ( is_array( $forms ) && ! empty( $forms ) ) {

			foreach ( $forms as $form ) {
				$id = $form->ID;
				$js =
					"
					var give_forms_" . esc_js( $id ) . "_div = document.querySelectorAll( '.give_forms' );
					for (var i = 0; i < give_forms_" . esc_js( $id ) . "_div.length; i++) {
						if ( give_forms_" . esc_js( $id ) . "_div[i].classList.contains( 'post-" . esc_js( $id ) . "' ) ) {
							var link = give_forms_" . esc_js( $id ) . "_div[i].querySelector('a');
							link.addEventListener('click', e => {
								e.preventDefault();
								" . $this->enhanced_ecommerce_add_product( $id ) . "
								window.location = e.target.href;
							})
						}
					}
					";

				$this->enqueue_js( 'event', $js );
			}
		}
	}

	/**
	 * Track data when a user has viewed a form/product.
	 *
	 * @param int $final_output Form HTML via shortcode.
	 * @param array $atts GiveWP Form Shortcode Attributes.
	 *
	 * @return mixed
	 */
	public function product_detail( $final_output, $atts ) {

		// Don't do it on feeds
		if ( is_feed() ) {
			return $final_output;
		}

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::givewp_is_test_mode() ) {
			return $final_output;
		}

		// Return if this product detail is already tracked. Prevents
		// double tracking as there could be multiple buy buttons on the page.
		if ( $this->has_tracked_detail ) {
			return $final_output;
		}

		$this->has_tracked_detail = true;

		// If page reload, then return
		if ( monsterinsights_is_page_reload() ) {
			return $final_output;
		}

		$product_id = array_key_exists( 'form_id', $atts ) ? $atts['form_id'] : $atts['id'];

		// NOTE: Below code is only for gtag.js and will not work with
		// analytics.js
		$product_data = $this->get_product_details( $product_id );
		$event_js     = sprintf( "__gtagTracker( 'event', 'view_item', { items: [%s] });", json_encode( $product_data ) );
		$this->enqueue_js( 'event', $event_js );

		return $final_output;
	}

	/**
	 * Save GA user ID when a donation is completed.
	 *
	 * @param int $payment_id ID of the payment. From wp_posts table.
	 * @param array $payment_data Payment Data.
	 *
	 * @return void
	 */
	public function save_user_cid( $payment_id, $payment_data ) {

		$tracked_already = give_get_payment_meta( $payment_id, '_yoast_gau_uuid', true );

		// Don't track checkout complete if already sent
		if ( ! empty( $tracked_already ) ) {
			return;
		}

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			if ( ! give_is_guest_payment( $payment_id ) ) {
				$donor_id     = give_get_payment_user_id( $payment_id );
				$do_not_track = ! monsterinsights_track_user( $donor_id );
				if ( $do_not_track ) {
					give_update_payment_meta( $payment_id, '_monsterinsights_ecommerce_do_not_track', true );
				}
			}
		}

		$ga_uuid = monsterinsights_get_client_id();
		if ( $ga_uuid ) {
			$cookie = monsterinsights_get_cookie();
			give_update_payment_meta( $payment_id, '_yoast_gau_uuid', $ga_uuid );
			give_update_payment_meta( $payment_id, '_monsterinsights_cookie', $cookie );
		}

        if ( $measurement_id = monsterinsights_get_v4_id_to_output() ) {
            $this->save_user_session_id( $payment_id, $measurement_id );
        }
	}

    protected function save_user_session_id( $payment_id, $measurement_id )
    {
        if ( function_exists( 'monsterinsights_get_browser_session_id' ) ) {
            $session_id = monsterinsights_get_browser_session_id($measurement_id);
            give_update_payment_meta($payment_id, '_monsterinsights_ga_session_id', $session_id);
        }
    }

	private function track_purchase_v4( $donation_id, $total, $payment_id, $currency, $donor_id ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$events = array(
			array(
				'name'   => 'purchase',
				'params' => array(
					'transaction_id'    => $donation_id,
					'value'             => $total,
					'currency'          => $currency,
					'transaction_id'    => $donation_id,
					'value'             => $total,
					'currency'          => $currency,
          'session_id'        => get_post_meta( $payment_id, '_monsterinsights_ga_session_id', true )
				)
			)
		);

		$args = array(
			'client_id' => monsterinsights_get_client_id( $payment_id ),
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $donor_id; // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Add a order when a donation is completed.
	 *
	 * @param int $payment_id ID of the payment. From wp_posts table.
	 *
	 * @return void
	 */
	public function add_order( $payment_id ) {
		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::givewp_is_test_mode() ) {
			return;
		}

		$payment_info = get_post( $payment_id );

		$skip_renewals = apply_filters( 'monsterinsights_ecommerce_skip_renewals', true );
		$status        = $payment_info->post_status;

		if ( 'give_subscription' === $status && $skip_renewals ) {
			return;
		}

		$is_in_ga = give_get_payment_meta( $payment_id, '_monsterinsights_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_do_transaction_skip_ga', false, $payment_id );

		// If it's already in GA or filtered to skip, then skip adding
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$donor_id = ( give_is_guest_payment( $payment_id ) ) ? absint( give_get_payment_donor_id( $payment_id ) ) : give_get_payment_user_id( $payment_id );

		// Skip tracking if not a trackable user.
		if ( ! function_exists( 'monsterinsights_debug_output' ) ) {
			if ( ! give_is_guest_payment( $payment_id ) ) {
				$donor_id     = give_get_payment_user_id( $payment_id );
				$do_not_track = ! monsterinsights_track_user( $donor_id );
				if ( $do_not_track ) {
					return;
				}
			}
		}

		$total       = give_get_payment_total( $payment_id );
		$currency    = give_get_payment_currency_code( $payment_id );
		$donation_id = MonsterInsights_eCommerce_Helper::givewp_donation_id( $payment_id );

		$this->track_purchase_v4( $donation_id, $total, $payment_id, $currency, $donor_id );

		give_update_payment_meta( $payment_id, '_monsterinsights_is_in_ga', 'yes' );
	}

	private function track_refund_v4( $donation_id, $total, $payment_id, $donor_id ) {
		if (
			! function_exists( 'monsterinsights_get_v4_id_to_output' ) ||
			! function_exists( 'monsterinsights_mp_collect_v4' ) ||
			! monsterinsights_get_v4_id_to_output()
		) {
			return;
		}

		$events = array(
			array(
				'name'   => 'refund',
				'params' => array(
					'transaction_id' => $donation_id,
					'value'          => $total,
				),
			),
		);

		$args = array(
			'client_id' => monsterinsights_get_client_id( $payment_id ),
			'events'    => $events,
		);

		if ( monsterinsights_get_option( 'userid', false ) ) {
			$args['user_id'] = $donor_id; // UserID tracking
		}

		monsterinsights_mp_collect_v4( $args );
	}

	/**
	 * Remove donation from GA report when the status of the donation changes
	 * to any of the following 'refunded', 'failed', 'cancelled', 'abandoned',
	 * 'revoked'
	 *
	 * @param int $payment_id ID of the payment.
	 * @param string $new_status New status of the payment.
	 * @param string $old_status Old/Current status of the payment.
	 *
	 * @return void
	 */
	public function remove_order( $payment_id, $new_status, $old_status ) {

		$negative_statuses = MonsterInsights_eCommerce_Helper::givewp_negative_statutes();

		if ( ! in_array( $new_status, $negative_statuses, true ) ) {
			return;
		}

		// If not in GA or skip is on, then skip
		$is_in_ga = give_get_payment_meta( $payment_id, '_monsterinsights_refund_is_in_ga', true );
		$skip_ga  = apply_filters( 'monsterinsights_ecommerce_undo_transaction_skip_ga', false, $payment_id );
		if ( $is_in_ga === 'yes' || $skip_ga ) {
			return;
		}

		$donor_id    = ( give_is_guest_payment( $payment_id ) ) ? absint( give_get_payment_donor_id( $payment_id ) ) : give_get_payment_user_id( $payment_id );
		$total       = give_get_payment_total( $payment_id );
		$donation_id = MonsterInsights_eCommerce_Helper::givewp_donation_id( $payment_id );

		$this->track_refund_v4( $donation_id, $total, $payment_id, $donor_id );

		give_update_payment_meta( $payment_id, '_monsterinsights_refund_is_in_ga', 'yes' );
	}

	/**
	 * Get form list type.
	 *
	 * @return string
	 */
	public function get_list_type( $product_id = 0 ) {
		global $wp_query;
		$list_type = '';
		if ( is_give_category() ) {
			$list_type = __( 'Product category', 'ga-ecommerce' );
		} elseif ( is_give_tag() ) {
			$list_type = __( 'Product tag', 'ga-ecommerce' );
		} elseif ( is_singular( 'give_forms' ) && (int) get_the_ID() !== (int) $product_id ) {
			$list_type = __( 'Single Product', 'ga-ecommerce' );
		} elseif ( is_front_page() || is_home() ) {
			$list_type = __( 'Homepage', 'ga-ecommerce' );
		} elseif ( $wp_query->get_queried_object() ) {
			$query     = $wp_query->get_queried_object();
			$list_type = isset( $query->post_title ) ? $query->post_title : '';
		}

		return apply_filters( 'monsterinsights_ga_ecommerce_get_list_type', $list_type );
	}

	/**
	 * Helper function to add product info to GA JS.
	 *
	 * @param int $payment_id ID of the payment.
	 * @param int $quantity Quantity.
	 *
	 * @return string
	 */
	private function enhanced_ecommerce_add_product( $product_id, $quantity = 1 ) {

		// Don't track test mode.
		if ( MonsterInsights_eCommerce_Helper::givewp_is_test_mode() ) {
			return;
		}

		$data = $this->get_product_details( $product_id, $quantity );

		return $this->get_add_product_js( $data );
	}

	/**
	 * Fetch product/form details.
	 *
	 * @param int $product_id ID of the product/form.
	 * @param int $quantity Quantity.
	 *
	 * @return array
	 */
	private function get_product_details( $product_id, $quantity = 1 ) {

		$categories     = get_the_terms( $product_id, 'give_forms_category' );
		$category_names = is_array( $categories ) ? wp_list_pluck( $categories, 'name' ) : array();
		$first_category = reset( $category_names );

		$data = array(
			'id'       => $product_id,
			'name'     => get_the_title( $product_id ),
			'category' => $first_category, // @todo: Possible  hierarchy the cats in the future
			'quantity' => $quantity,
			'position' => 1,
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
	 * Helper function to get GiveWP Forms by category ID.
	 *
	 * @param int $category_term_id Category term ID from give_forms_category tax.
	 *
	 * @return array/mixed
	 * @since 7.4.0
	 *
	 */
	private function get_give_forms_by_category( $category_term_id = 0 ) {

		if ( ! is_int( $category_term_id ) && $category_term_id <= 0 ) {
			return;
		}

		// Setup the arguments to fetch the donation forms.
		$forms_query = new Give_Forms_Query(
			array(
				'post_status' => 'publish',
				'tax_query'   => array(
					array(
						'taxonomy' => 'give_forms_category',
						'field'    => 'term_id',
						'terms'    => $category_term_id,
					),
				),
			)
		);

		// Fetch the donation forms.
		$forms = $forms_query->get_forms();

		return $forms;
	}
}
