<?php
/**
 * Class EasyAffiliate Integration Helper
 *
 * @since 8.0.2
 */

class MonsterInsights_EasyAffiliate {

	/**
	 * Check if EasyAffiliate plugin is active or not.
	 *
	 * @return boolean
	 * @since 8.0.2
	 *
	 */
	public static function is_easy_affiliate_active() {
		return defined( 'ESAF_EDITION' );
	}

	/**
	 * Check if EasyAffiliate Pro is installed.
	 *
	 * @return boolean
	 * @since 8.0.2
	 *
	 */
	public static function is_easy_affiliate_pro() {

		if ( self::is_easy_affiliate_active() ) {
			if ( 'easy-affiliate-pro' === ESAF_EDITION ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Fetch WooCommerce Order Status set by user from EasyAffiliate Options.
	 *
	 * @return string
	 * @since 8.0.2
	 *
	 */
	public function easy_affiliate_woo_status() {

		if ( self::is_easy_affiliate_active() && class_exists( '\EasyAffiliate\Models\Options' ) ) {
			$options = \EasyAffiliate\Models\Options::fetch();

			if ( is_object( $options ) ) {
				return esc_attr( $options->woocommerce_integration_order_status );
			}
		}

		return '';
	}

	/**
	 * Get Affiliation ID for WooCommerce.
	 *
	 * @param object $order Woo Order Class Object
	 * @param int $payment_id Woo Payment ID
	 *
	 * @return int
	 * @since 8.0.2
	 *
	 */
	public function get_easy_affiliation_woo_affiliate_id( $payment_id ) {
		if ( self::is_easy_affiliate_active() ) {
			if ( get_post_meta( $payment_id, 'ar_affiliate', true ) ) {
				return absint( get_post_meta( $payment_id, 'ar_affiliate', true ) );
			}
		}

		return null;
	}

	/**
	 * Get Affiliation ID for MemberPress.
	 *
	 * @param object $txn MemberPress Transaction Object
	 *
	 * @return int
	 * @since 8.0.2
	 *
	 */
	public function get_easy_affiliation_memberpress_affiliate_id( $txn ) {
		if ( self::is_easy_affiliate_active() && class_exists( '\EasyAffiliate\Models\Transaction' ) ) {

			$mp_txn_id = $txn->id; // MemberPress Transaction ID

			$ea_mp_transaction = \EasyAffiliate\Models\Transaction::get_one( [
				'source'   => 'memberpress',
				'order_id' => $mp_txn_id
			] );

			if ( $ea_mp_transaction ) {
				return absint( $ea_mp_transaction->affiliate_id ); // The wp_users->ID of the Affiliate
			}
		}

		return null;
	}

	/**
	 * Get Affiliation ID for EDD.
	 *
	 * @param int $payment_id EDD Payment ID
	 *
	 * @return int
	 * @since 8.0.2
	 *
	 */
	public function get_easy_affiliation_edd_affiliate_id( $payment_id ) {
		if ( self::is_easy_affiliate_active() && class_exists( '\EasyAffiliate\Models\Transaction' ) ) {

			$ea_edd_transaction = \EasyAffiliate\Models\Transaction::get_one( [
				'source'   => 'easy_digital_downloads',
				'order_id' => $payment_id
			] );

			if ( $ea_edd_transaction ) {
				return absint( $ea_edd_transaction->affiliate_id ); // The wp_users->ID of the Affiliate
			}
		}

		return null;
	}
}
