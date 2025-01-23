<?php

namespace WCPM\Classes\Pixels\Facebook;

// TODO disable Yoast SEO Open Graph wp_option: wpseo_social => opengraph => true / false

use WCPM\Classes\Helpers;
use WCPM\Classes\Pixels\Pixel;
use WCPM\Classes\Product;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Microdata output for the Facebook catalog
 *
 * Sources:
 * https://www.facebook.com/business/help/1175004275966513?id=725943027795860
 * https://developers.facebook.com/docs/marketing-api/catalog/guides/microdata-tags
 * https://developers.facebook.com/docs/marketing-api/catalog/reference/#json-ld
 * https://business.facebook.com/ads/microdata/debug
 * https://developers.facebook.com/tools/debug/
 *
 * https://stackoverflow.com/questions/8653970/how-should-i-handle-schema-org-markup-for-a-product-with-multiple-sizes-prices
 * https://developers.facebook.com/docs/marketing-api/catalog/guides/product-variants/
 * https://schema.org/ProductGroup
 *
 * https://www.schemaapp.com/schema-markup/schema-org-variable-products-productmodels-offers/
 *
 * https://validator.schema.org/
 *
 * https://developers.google.com/search/docs/advanced/structured-data/product#single-product-page
 * https://support.google.com/merchants/answer/6386198?hl=en#zippy=%2Csingle-product%2Cexample
 * TODO maybe add inProductGroupWithID for variants for Google as specified here  https://support.google.com/merchants/answer/6386198?hl=en#zippy=%2Csingle-product%2Cexample
 *
 * https://stackoverflow.com/questions/33453563/how-to-mark-data-using-schema-org-and-json-ld-on-a-websites-home-page/33457312#33457312
 *
 * Rules:
 * - ViewContent event not necessary, only the FB pixel needs to load
 * - FB seems to only process the first entry, nothing more
 * - Full script tag with one single product works
 * - If a completely new product / variant is loaded, it takes much longer until it shows up
 * - Graph doesn't work
 * - Nesting offers doesn't work
 *
 * Check:
 * - If other plugins (like SEO plugins) insert json-ld above, it might fail
 */
class Facebook_Microdata extends Pixel {

	protected $pixel_name = 'facebook';

	public function __construct( $options ) {
		parent::__construct($options);
	}

	// https://stackoverflow.com/a/34494648/4688612
	// https://stackoverflow.com/a/53102717/4688612
	public function inject_schema( $product ) {

		/**
		 * It can happen that no product object is passed to the function.
		 * In that case we terminate seamlessly.
		 */
		if (Product::is_not_wc_product($product)) {
			return 0;
		}

		/**
		 * Only process simple and variable products.
		 * Out of stock variations trigger $product = false errors
		 */
//		if ('simple' !== $product->get_type() || 'variable' !== $product->get_type()) {
//			return 0;
//		}

		// v1: simply add one product after the other within the script tag, comma separated
//		$this->get_script_tag_v1($product);

		// https://stackoverflow.com/a/33457312/4688612
		// https://stackoverflow.com/a/30506476/4688612
		// repeat the entire script tag (with tags)
		// for each product one
		$this->generate_microdata_v2a($product);

		// https://stackoverflow.com/a/33457312/4688612
		// https://stackoverflow.com/a/30506476/4688612
		// Add each product under a @graph
//		return $this->generate_microdata_v3($product);
	}

	protected function get_script_tag_v2a( $microdata ) {

		// @formatter:off
		?>

		<!-- START Facebook Microdata script -->
		<script type="application/ld+json">
			<?php echo wp_json_encode($microdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

		</script>
		<!-- END Facebook Microdata script -->
		<?php
		// @formatter:on
	}

	protected function generate_microdata_v2a( $product ) {

		if ('variable' === $product->get_type()) {

			// Much faster than get_available_variations() if
			// we only need the IDs
			$variation_ids = $product->get_children();

			/**
			 * Facebook only reads the first variant from the array. This
			 * is so on the 21 April 2022.
			 * So we simply shuffle the output array of variants
			 * and over time each variant will be read out from the first
			 * place and imported into Facebook.
			 * That even works with cached sites. It'll just take longer.
			 */
			shuffle($variation_ids);

//			foreach ($variation_ids as $variation_id) {
//				$microdata = $this->get_v2a_microdata(wc_get_product($variation_id));
//				$this->get_script_tag_v2a($microdata);
//			}

			$product = wc_get_product($variation_ids[0]);

			if (Product::is_not_wc_product($product)) {
				return;
			}

			$microdata = $this->get_v2a_microdata($product);
			$this->get_script_tag_v2a($microdata);

		} else {
			$microdata = $this->get_v2a_microdata($product);
			$this->get_script_tag_v2a($microdata);
		}
	}

	protected function get_v2a_microdata( $product ) {

		$product_dyn_r_ids = Product::get_dyn_r_ids($product);

		$microdata = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'productID'   => $product_dyn_r_ids[Product::get_dyn_r_id_type('facebook')],
			'name'        => $product->get_name(),
			'description' => $this->get_description($product),
			'url'         => get_permalink(),
			'image'       => wp_get_attachment_url($product->get_image_id()),
			'offers'      => [
				[
					'@type'         => 'Offer',
					'price'         => Helpers::format_decimal($product->get_price(), 2),
					'priceCurrency' => get_woocommerce_currency(),
					'itemCondition' => $this->get_schema_condition($product),
					'availability'  => $this->get_schema_stock_status($product),
				],
			],
		];

		if (Product::get_brand_name($product->get_id())) {
			$microdata['brand'] = Product::get_brand_name($product->get_id());
		}

		if ($product->get_sku()) {
			$microdata['sku'] = $product->get_sku();
		}

		if ($product->get_gallery_image_ids()) {

			$additional_image_ids = $product->get_gallery_image_ids();

			$additional_image_ids = array_slice($additional_image_ids, 0, 20);

			$additional_image_urls = [];

			foreach ($additional_image_ids as $key => $id) {
				$additional_image_urls[] = wp_get_attachment_url($id);
			}

			$microdata['additional_image_link'] = implode(',', $additional_image_urls);
		}


		// If the product is a variation, add the item_group_id to the output
		if ($product->is_type('variation')) {

			$parent_product_id        = wp_get_post_parent_id($product->get_id());
			$parent_product           = wc_get_product($parent_product_id);
			$parent_product_dyn_r_ids = Product::get_dyn_r_ids($parent_product);

			if (Product::get_brand_name($parent_product->get_id())) {
				$microdata['brand'] = Product::get_brand_name($parent_product->get_id());
			}

			$microdata['additionalProperty'] = [
				[
					'@type'      => 'PropertyValue',
					'propertyID' => 'item_group_id',
					'value'      => $parent_product_dyn_r_ids[Product::get_dyn_r_id_type('facebook')],
				],
			];
		}

		return $microdata;
	}

	private function get_description( $product ) {

		$max_length = 4997;

		// If the variant doens't have a description, use the one from the parent
		if ('' === $product->get_description() && 'variation' === $product->get_type()) {
			$product = wc_get_product($product->get_parent_id());
		}

		$text = wp_strip_all_tags($product->get_description());

		if (strlen($text) > $max_length) {
			$text = substr($text, 0, $max_length) . '...';
		}

		return $text;
	}

	// https://schema.org/ItemAvailability
	// Possible WC values: instock, outofstock, onbackorder
	private function get_schema_stock_status( $product ) {

		$wc_stock_status = $product->get_stock_status($product);

		if ('instock' === $wc_stock_status) {
			return 'InStock';
		} elseif ('outofstock' === $wc_stock_status) {
			return 'OutOfStock';
		} elseif ('onbackorder' === $wc_stock_status) {
			return 'PreOrder';
		} else {
			return '';
		}
	}

	// https://schema.org/OfferItemCondition
	// Possible WC values: Standard WC doesn't offer condition
	private function get_schema_condition( $product ) {
		return 'NewCondition';
	}
}
