<?php

/**
 * Profit Margin calculation class
 */

namespace WCPM\Classes;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Profit_Margin {

	public static function get_order_profit_margin( $order ) {

		$cogs_margin = self::get_order_item_profit_margin($order);

		$cogs_margin -= $order->get_total_discount();
		$cogs_margin -= Shop::get_order_fees($order);

		return Helpers::format_decimal($cogs_margin);
	}

	private static function get_order_item_profit_margin( $order ) {

		$order_items = $order->get_items();

		$order_value = 0;

		foreach ($order_items as $item) {

			$product = $item->get_product();

			if (Product::is_not_wc_product($product)) {
				continue;
			}

			$qty      = $item->get_quantity();
			$item_cog = self::get_cog_for_product($product);

			$order_value += $item->get_total() - ( $qty * $item_cog );
		}

		return $order_value;
	}

	private static function get_cog_for_product( $product ) {

		/**
		 * Use custom meta key if none of the above works
		 */


		if (self::get_custom_cog_meta_key()) {
			$cog = self::get_cog_for_product_from_meta($product, self::get_custom_cog_meta_key());

			if (isset($cog)) {
				return floatval($cog);
			}
		}

		/**
		 * Try to get COG from one of the COG plugins
		 */

		// WooCommerce Cost of Goods (SkyVerge)
		if (class_exists('WC_COG_Product') && method_exists('WC_COG_Product', 'get_cost')) {

			$cog = \WC_COG_Product::get_cost($product);

			return floatval($cog);
		}

		// Cost of Goods for WooCommerce (WPFactory)
		if (class_exists('Alg_WC_Cost_of_Goods_Products') && method_exists('Alg_WC_Cost_of_Goods_Products', 'get_product_cost')) {

			$cog = ( new \Alg_WC_Cost_of_Goods_Products() )->get_product_cost($product->get_id());

			return floatval($cog);
		}

		/**
		 * Fallback to retrieving directly from postmeta if the COG plugin gets deactivated for some reason
		 */

		// WooCommerce Cost of Goods (SkyVerge)
		$cog = self::get_cog_for_product_from_meta($product, '_wc_cog_cost');

		if (isset($cog)) {
			return floatval($cog);
		}

		// Cost of Goods for WooCommerce (WPFactory)
		$cog = self::get_cog_for_product_from_meta($product, '_alg_wc_cog_cost');

		if (isset($cog)) {
			return floatval($cog);
		}

		/**
		 * Fallback to zero if none of the above works
		 */

		return 0;
	}

	/**
	 * Set a custom Cost Of Goods Sold meta key.
	 *
	 * @return mixed|null
	 * @since 1.30.6
	 */
	public static function get_custom_cog_meta_key() {

		return apply_filters('pmw_custom_cogs_meta_key', null);
	}

	private static function get_cog_for_product_from_meta( $product, $meta_key ) {

		$cog = get_post_meta($product->get_id(), $meta_key, true);

		// If item is a variation and the COG is set, use the variation COG, otherwise try to use the parent COG
		if (empty($cog) && $product->is_type('variation')) {
			$cog = get_post_meta($product->get_parent_id(), $meta_key, true);
		}

		return $cog;
	}
}
