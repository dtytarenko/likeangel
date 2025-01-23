<?php

/**
 * Class MonsterInsights_eCommerce_Helper
 */
class MonsterInsights_eCommerce_Helper {

	/**
	 * Is the current WooCommerce instance in test mode?
	 *
	 * @var bool
	 */
	private static $wc_is_test_mode;
	/**
	 * Is the current Easy Digital Downloads instance in test mode?
	 *
	 * @var bool
	 */
	private static $edd_is_test_mode;
	/**
	 * Is the current Lifter LMS instance in test mode?
	 *
	 * @var bool
	 */
	private static $llms_is_test_mode;
	/**
	 * Is the current MemberPress instance in test mode?
	 *
	 * @var bool
	 */
	private static $mepr_is_test_mode;

	/**
	 * Is the current MemberMouse instance in test mode?
	 *
	 * @var bool
	 */
	private static $mm_is_test_mode;

	/**
	 * Check if WooCommerce is in test mode. If an order id is passed it checks if the gateway used for that order is in test mode.
	 *
	 * @param int $order_id (optional) The order id.
	 *
	 * @return bool
	 */
	public static function woocommerce_test_mode( $order_id = 0 ) {

		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		if ( $order_id > 0 ) {
			return self::wc_is_test_mode( $order_id );
		}
		if ( ! isset( self::$wc_is_test_mode ) ) {
			self::$wc_is_test_mode = self::wc_is_test_mode();
		}

		return self::$wc_is_test_mode;
	}

	/**
	 * Check if test mode is used. For orders check if order gateway is in test mode to avoid not tracking orders confirmed at a later time.
	 *
	 * @param int $order_id
	 *
	 * @return bool
	 */
	private static function wc_is_test_mode( $order_id = 0 ) {

		// If checking for a specific order, make sure the gateway used is in test mode.
		if ( $order_id ) {
			$order   = wc_get_order( $order_id );
			$gateway = $order->get_payment_method();

			return self::wc_is_gateway_in_test_mode( $gateway );
		}

		// Attempt to detect if test mode is enabled and don't track those sessions.
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		foreach ( $gateways as $gateway ) {
			if ( self::wc_is_gateway_in_test_mode( $gateway ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $gateway
	 *
	 * @return bool
	 */
	private static function wc_is_gateway_in_test_mode( $gateway ) {
		// Test mode Stripe or PayPal style.
		if ( isset( $gateway->testmode ) && true === $gateway->testmode ) {
			return true;
		}
		// Test mode PayPal express style.
		if ( isset( $gateway->smart_button_environment ) ) {
			// handle express checkout
			if ( 'no' === $gateway->payment_mode && 'live' !== $gateway->environment ) {
				return true;
			}
			// handle smart button
			if ( 'yes' === $gateway->payment_mode && 'live' !== $gateway->smart_button_environment ) {
				return true;
			}
		} elseif ( isset( $gateway->environment ) ) {
			// handle gateway environment
			if ( 'live' !== $gateway->environment ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if EDD is in test mode.
	 *
	 * @return bool
	 */
	public static function edd_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		if ( ! isset( self::$edd_is_test_mode ) ) {
			self::$edd_is_test_mode = edd_is_test_mode();
		}

		return self::$edd_is_test_mode;
	}

	/**
	 * Get the test mode status for Lifter LMS.
	 *
	 * @return bool
	 */
	public static function llms_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		if ( ! isset( self::$llms_is_test_mode ) ) {
			self::$llms_is_test_mode = self::lifterlms_is_test_mode();
		}

		return self::$llms_is_test_mode;
	}

	/**
	 * Check if Lifter LMS gateway is in test mode.
	 *
	 * @return bool
	 */
	private static function lifterlms_is_test_mode() {

		if ( ! function_exists( 'LLMS' ) ) {
			return false;
		}

		foreach ( LLMS()->payment_gateways()->get_payment_gateways() as $obj ) {
			if ( $obj->is_enabled() && $obj->supports( 'test_mode' ) && $obj->is_test_mode_enabled() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the test mode status for MemberMouse.
	 *
	 * @return bool
	 */
	public static function mm_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		if ( ! isset( self::$mm_is_test_mode ) ) {
			self::$mm_is_test_mode = self::membermouse_is_test_mode();
		}

		return self::$mm_is_test_mode;
	}

	/**
	 * Check if MemberMouse is in test mode. MemberMouse can have multiple payment gateways configured simultaneously, so the
	 * default gateway is checked first, then any offsite gateways (ie. paypal) are checked, and finally false is returned if no
	 * gateways (or the Test Gateway) is configured. Also returns false if a test mode override key is in session
	 *
	 * @return bool
	 */
	private static function membermouse_is_test_mode() {

		if ( !class_exists( 'MemberMouse',false ) || !class_exists( 'MM_PaymentServiceFactory',false )) {
			return false;
		}

		$onsiteService = MM_PaymentServiceFactory::getOnsitePaymentService();
		if ( $onsiteService != null ) {
			return $onsiteService->inTestMode();
		} else {
			$offsiteService = MM_PaymentServiceFactory::getOffsitePaymentService();
			if ( $offsiteService != null ) {
				return $offsiteService->inTestMode();
			}
		}

		//no payment gateway has been set up
		return false;
	}

	/**
	 * @return bool
	 * @throws MeprInvalidGatewayException
	 */
	public static function mepr_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		if ( ! isset( self::$mepr_is_test_mode ) ) {
			self::$mepr_is_test_mode = self::memberpress_is_test_mode();
		}

		return self::$mepr_is_test_mode;
	}

	/**
	 * Check if Gateways of MemberPress are set to test mode.
	 *
	 * @return bool
	 * @throws MeprInvalidGatewayException
	 */
	private static function memberpress_is_test_mode() {
		if ( ! class_exists( 'MeprOptions' ) ) {
			return false;
		}

		$mepr_options    = MeprOptions::fetch();
		$payment_methods = $mepr_options->payment_methods( false );

		foreach ( $payment_methods as $payment_method ) {
			/**
			 *
			 * @var $payment_method MeprBaseRealGateway
			 */
			if ( $payment_method->is_test_mode() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Format the price to 2 decimals for GA tracking.
	 *
	 * @param string|float|int $original_price The price as we get it from the eCommerce plugin.
	 *
	 * @return mixed|void
	 */
	public static function round_price( $original_price ) {

		$price = number_format( (float) $original_price, 2, '.', '' );

		return apply_filters( 'monsterinsights_ecommerce_price_rounding', $price, $original_price );
	}

	/**
	 * Check if GiveWP Test mode is enabled or not.
	 *
	 * @return bool
	 * @since 7.4.0
	 */
	public static function givewp_is_test_mode() {

		if ( function_exists( 'Give' ) && function_exists( 'give_is_test_mode' ) ) {
			return give_is_test_mode();
		}

		return true;
	}

	/**
	 * Payment statuses for GiveWP
	 *
	 * This would primarily be required when removing an order/donation.
	 *
	 * @return array
	 * @since 7.4.0
	 */
	public static function givewp_negative_statutes() {
		return array( 'refunded', 'failed', 'cancelled', 'abandoned', 'revoked' );
	}

	/**
	 * Get Donation ID which is different from Donation Post ID/Actual Donation ID.
	 *
	 * @param int $payment_id ID of the payment.
	 *
	 * @return int
	 * @since 7.4.0
	 */
	public static function givewp_donation_id( $payment_id ) {
		$payment = new Give_Payment( $payment_id );

		return isset( $payment->number ) ? $payment->number : $payment_id;
	}

	/**
	 * Restrict Content Pro - Get settings from options table.
	 *
	 * @param bool   $single Get a single value from the array or not.
	 * @param string $single Key value to fetch.
	 *
	 * @return mixed
	 * @since 7.4.0
	 */
	public static function get_rcp_settings( $single = false, $key = '' ) {

		$rcp_settings = get_option( 'rcp_settings' );

		if ( $rcp_settings ) {
			if ( $single ) {
				if ( array_key_exists( $key, $rcp_settings ) && '' !== $rcp_settings[ $key ] ) {
					return $rcp_settings[ $key ];
				} else {
					return '';
				}
			}

			return $rcp_settings;
		}
	}

	/**
	 * Restrict Content Pro - Check if Test Mode is on.
	 *
	 * @param string $gateway Slug of gateway.
	 *
	 * @return bool
	 * @since 7.4.0
	 */
	public static function is_rcp_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		$rcp_settings = self::get_rcp_settings();

		if ( ! empty( $rcp_settings ) ) {
			if ( isset( $rcp_settings['sandbox'] ) && '1' === $rcp_settings['sandbox'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Restrict Content Pro - Get payment details by payment id.
	 *
	 * @param int    $id Payment ID.
	 * @param string $meta_key Payment meta for example: transaction_id.
	 *
	 * @return mixed
	 * @since 7.4.0
	 */
	public static function get_rcp_payment( $id = 0, $meta_key = '' ) {

		if ( 0 === absint( $id ) ) {
			return '';
		}

		if ( class_exists( 'Restrict_Content_Pro' ) ) {
			$payments = self::rcp_payments();
			$payment  = $payments->get_payment( $id );

			if ( '' !== $meta_key ) {
				if ( $payment->meta_key ) {
					return $payment->$meta_key;
				} else {
					return '';
				}
			}

			return $payment;
		}

		return '';
	}

	/**
	 * Restrict Content Pro - Check if a post or a page has it's content restricted.
	 *
	 * @return bool
	 * @since 7.4.0
	 */
	public static function is_rcp_restricted_content() {
		global $post;

		$content_restricted = false;

		$id = 0;

		if ( is_page() || is_single() ) {
			$id = $post->ID;
		}

		if ( rcp_is_restricted_content( $id ) ) {
			$content_restricted = true;
		} elseif ( is_object( $post ) && has_shortcode( $post->post_content, 'restrict' ) ) {
			$content_restricted = true;
		}

		return $content_restricted;
	}

	/**
	 * Restrict Content Pro - Return instance of class RCP_Payments.
	 *
	 * @return object
	 * @since 7.4.0
	 */
	public static function rcp_payments() {
		return new RCP_Payments();
	}

	/**
	 * Get MonsterInsights_EasyAffiliate class instance.
	 *
	 * @return object
	 * @since 8.0.2
	 */
	public static function easy_affiliate() {
		return new MonsterInsights_EasyAffiliate();
	}

	/**
	 * Check if AffiliateWP is active.
	 *
	 * @return boolean
	 * @since 8.2.0
	 */
	public static function is_affiliate_wp_active() {
		if ( function_exists( 'affiliate_wp' ) && defined( 'AFFILIATEWP_VERSION' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get affiliate id from AffiliateWP.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $source Name of the ecommerce platform. For e:g woocommerce, edd or memeberpress.
	 *
	 * @return int
	 * @since 8.2.0
	 */
	public static function get_affiliate_wp_affiliate_id( $order_id, $source ) {
		if ( ! self::is_affiliate_wp_active() ) {
			return 0;
		}

		$referral_info = self::get_affiliate_wp_referral( $order_id, $source );

		if ( is_wp_error( $referral_info ) ) {
			return 0;
		}

		if ( ! is_object( $referral_info ) ) {
			return 0;
		}

		return absint( $referral_info->affiliate_id );
	}

	/**
	 * Get referral object from the order ID.
	 *
	 * @param int    $order_id Order ID
	 * @param string $source Integration source.
	 *
	 * @return \AffWP\Referral|false|WP_Error
	 */
	private static function get_affiliate_wp_referral( $order_id, $source ) {
		if ( ! function_exists( 'affwp_get_referral_by' ) ) {
			$result = affiliate_wp()->referrals->get_by_with_context( 'reference', $order_id, $source );
			if ( ! is_object( $result ) ) {
				return new \WP_Error(
					'invalid_referral_field',
					sprintf( 'No referral could be retrieved with a(n) \'%1$s\' field value of %2$s.', 'reference', $order_id )
				);
			}

			return affwp_get_referral( intval( $result->referral_id ) );
		}

		return affwp_get_referral_by( 'reference', $order_id, $source );
	}

	/**
	 * Get GA Client ID for RCP.
	 *
	 * Since we are saving GA UUID inside RCP payment custom table
	 * we need this function to get the client ID from RCP table as
	 * oppose to post meta table.
	 *
	 * @param int $payment_id Order ID
	 *
	 * @return mixed
	 * @since 8.3.4
	 */
	public static function get_rcp_client_id( $payment_id = 0 ) {
		if ( ! $payment_id && ! is_int( $payment_id ) ) {
			return;
		}

		$rcp_payments = self::rcp_payments();

		$user_cid  = monsterinsights_get_uuid();
		$saved_cid = ! empty( $payment_id ) ? $rcp_payments->get_meta( $payment_id, '_yoast_gau_uuid', true ) : false;

		if ( ! empty( $payment_id ) && ! empty( $saved_cid ) ) {
			return $saved_cid;
		} elseif ( ! empty( $user_cid ) ) {
			return $user_cid;
		} else {
			return monsterinsights_generate_uuid();
		}
	}

	/**
	 * Check the page is refreshed or not.
	 * Here we are using COOKIE as HTTP_REFERER is no more promising.
	 *
	 * @return bool
	 */
	public static function is_page_reload() {
		// Here you get the URI.
		$current_page = sanitize_text_field( $_SERVER['REQUEST_URI'] );

		// If the session current page is not set.
		if ( ! isset( $_COOKIE['monsterinsights_current_page'] ) ) {
			// Set the current page to the session.
			setcookie( 'monsterinsights_current_page', $current_page, 0, COOKIEPATH );

			return false;
		}

		// If the session URL and server URI match then the page is reloaded.
		if ( $_COOKIE['monsterinsights_current_page'] === $current_page ) {
			return true;
		}

		// Set the current page to the session.
		setcookie( 'monsterinsights_current_page', $current_page, 0, COOKIEPATH );

		return false;
	}

	/**
	 * Get the value of charitable test-mode enable or not.
	 */
	public static function charitable_test_mode() {
		// Allow users to override this and send data for test transactions.
		if ( apply_filters( 'monsterinsights_ecommerce_track_test_payments', false ) ) {
			return false;
		}

		return charitable_get_option( 'test_mode', 0 );
	}

	/**
	 * Get WooCommerce order statuses on which we will send request to GA.
	 *
	 * @return array
	 */
	public static function woocommerce_ga_order_statuses() {
		$statuses = array( 'processing', 'completed' );

		return apply_filters( 'monsterinsights_woocommerce_add_to_ga_order_statuses', $statuses );
	}
}
