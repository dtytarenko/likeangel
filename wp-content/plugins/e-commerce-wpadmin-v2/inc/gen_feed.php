<?php

function get_gf_crumb_v2( $id = 0) {
	
	$get_google_crumbs_arr = Array();	
	
	$terms = wc_get_product_terms(
		$id,
		'product_cat',
		apply_filters(
			'woocommerce_breadcrumb_product_terms_args',
			array(
				'orderby' => 'parent',
				'order'   => 'DESC',
			)
		)
	);

	if ( $terms ) {
		$main_term = apply_filters( 'woocommerce_breadcrumb_main_term', $terms[0], $terms );
		//echo $main_term->name . ' / ';
		$ancestors = get_ancestors( $main_term->term_id, 'product_cat' );
		$ancestors = array_reverse( $ancestors );

		foreach ( $ancestors as $ancestor ) {
			$ancestor = get_term( $ancestor, 'product_cat' );
			
			if ( ! is_wp_error( $ancestor ) && $ancestor ) {
				$get_google_crumbs_arr[] = $ancestor->name;
			}
		}
		$get_google_crumbs_arr[] =  $main_term->name;
	}
	
	$product_type = '';
	foreach ( $get_google_crumbs_arr as $key => $crumb ) {
		$product_type .= esc_html( $crumb );
		if ( sizeof( $get_google_crumbs_arr ) !== $key + 1 ) {
			$product_type .=  ' > ';
		}
	}
				
	return $product_type;
}

function google_feed_v2($lang = 'ua', $feed = 'google', $error = false, $cache = true, $count = -1 ) {
	if ($error) {
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}
	
	$cache_key = 'wpadmin-xml-output-'.$feed. '-' .$lang;
	//delete_transient( $cache_key );
	if (!$cache) delete_transient( $cache_key );
		
	if ( !$output = get_transient( $cache_key ) ) {


		if (get_option('wpadmin_brand_taxonomy')) {
			$brand_attr = get_option('wpadmin_brand_taxonomy');
		} else {
			$brand_attr = '';
		}
		if (get_option('wpadmin_feed_color_attr')) {
			$color_attr = get_option('wpadmin_feed_color_attr');
		} else {
			$color_attr = '';
		}
		if (get_option('wpadmin_feed_size_attr')) {
			$size_attr = get_option('wpadmin_feed_size_attr');
		} else {
			$size_attr = '';
		}
		if (get_option('wpadmin_feed_custom_label0')) {
			$custom_label0 = get_option('wpadmin_feed_custom_label0');
		} else {
			$custom_label0 = '';
		}
		if (get_option('wpadmin_feed_custom_label1')) {
			$custom_label1 = get_option('wpadmin_feed_custom_label1');
		} else {
			$custom_label1 = '';
		}

		$brand_name = (get_option('wpadmin_brand')) ? wpadmin_clear_text_v2(get_option('wpadmin_brand')) : wpadmin_clear_text_v2(__('BRAND NAME','wpadminpro'));

		$args = [
			'status'    => 'publish',
			/*'orderby' => 'name',
			'order'   => 'ASC',*/
			'limit' => $count,
			//'return' => 'ids',
		];
		
		if (get_option('wpadmin_outofst') != 'yes') $args['stock_status'] = 'instock';


		if( class_exists( 'SitePress' ) ) {
			global $sitepress;
			$sitepress->switch_lang($lang);
		} elseif (function_exists('pll_current_language')) {
			$args['lang'] = $lang;
		}

		$all_products = wc_get_products($args);
		$google_product = Array();

		foreach ($all_products as $key => $product) {
			
			$product_id = $product->get_id();
			//$product_name = $product->get_name();
			//$product_price = $product->get_price();
			//$product_image = wp_get_attachment_image_src( $product->get_image_id(),'full' )[0];
			//$product_stock_status = $product->get_stock_status();
			
			//Get Brands
			$brand_terms = get_the_term_list( $product_id, $brand_attr, '', ', ' ); //product_brand
			
			if (!is_wp_error( $brand_terms ) && $brand_terms) {
				$product_info['brand']  = strip_tags($brand_terms);
			} else {
				$product_info['brand'] = $brand_name;
			}

			//Check description and change it to short if first is blank
			$prod_description = ($product->get_description() <> '') ? $product->get_description() : $product->get_short_description();
			$attachment_ids = $product->get_gallery_image_ids();
			
			
			//???????????????????????????????????
			/*$product_info['item_group_id'] = '';			
			$product_info['additional_variant_attribute'] = '';*/
			//$product_info['pa_color'] = '';
			//$product_info['pa_size'] = '';
			
			if ($product->is_type( 'variable' )) {
				
				/*if (!$cache) wp_cache_delete( 'wpadminec', 'available-variations' );
				
				$available_variations = wp_cache_get( 'wpadminec', 'available-variations' );

				if ( false === $available_variations ) {
					$available_variations = $product->get_available_variations('objects');
					wp_cache_set( 'wpadminec', $available_variations, 'available-variations', HOUR_IN_SECONDS );
				}*/
				
				$available_variations = $product->get_available_variations('objects');

				foreach( $available_variations as $variation ) {
					
					if (get_option('wpadmin_outofst') == 'no' && ($variation->get_stock_status() == 'outofstock')) {
						continue;						
					} 

					$product_info['id'] = $variation->get_id();					
					
					$product_info['title'] = wpadmin_clear_text_v2($variation->get_title() . ' ' . product_get_variant_line_v2( $variation->get_id(), true));

					$product_info['description'] = wpadmin_clear_text_v2(($variation->get_description() <> '') ? $variation->get_description() : $variation->get_short_description());
					if ($product_info['description'] == '') {		
						$product_info['description'] = wpadmin_clear_text_v2(($product->get_description() <> '') ? $product->get_description() : $product->get_short_description());
					}
					
					$product_info['link'] = htmlspecialchars(get_permalink($product_info['id']), ENT_XML1, 'UTF-8');
					
					$product_info['image'] = wp_get_attachment_image_url(($variation->get_image_id() <> '') ? $variation->get_image_id() : $product->get_image_id(), 'full');
					
					$product_info['price'] = $variation->get_price().' '.get_woocommerce_currency();
					
					$product_info['product_type'] = get_gf_crumb_v2($product_id);
					
					$product_info['stock'] = ( $variation->get_stock_status() == 'instock')? 'in_stock' : 'out_of_stock';
					$product_info['stock_fb'] = ( $variation->get_stock_status() == 'instock')? 'in stock' : 'out of stock';
					
					$product_info['condition_fb'] = 'new';
					
					$product_info['item_group_id'] = $product_id;
					//$product_info['additional_variant_attribute'] = $variation->get_formatted_name();					
					//$attributes   = $variation->get_variation_attributes();
					//$product_info['pa_color'] = (isset($attributes['attribute_pa_color'])) ? $attributes['attribute_pa_color'] : '';//???????????????????????????????????
					//$product_info['pa_size'] = (isset($attributes['attribute_pa_size'])) ? $attributes['attribute_pa_size'] : '';//???????????????????????????????????
					
					$product_info['color'] = ($color_attr <> '') ? $variation->get_attribute( $color_attr ) : '';
					$product_info['size'] = ($size_attr <> '') ? $variation->get_attribute( $size_attr ) : '';
					$product_info['custom_label0'] = ($custom_label0 <> '' && $product->get_attribute( $custom_label0 ) <> '') ? $product->get_attribute( $custom_label0 ) : '';
					$product_info['custom_label1'] = ($custom_label1 <> '' && $product->get_attribute( $custom_label1 ) <> '') ? $product->get_attribute( $custom_label1 ) : '';
					$product_info['custom_label0'] = ($custom_label0 <> '' && $variation->get_attribute( $custom_label0 ) <> '') ? $variation->get_attribute( $custom_label0 ) : $product_info['custom_label0'];
					$product_info['custom_label1'] = ($custom_label1 <> '' && $variation->get_attribute( $custom_label1 ) <> '') ? $variation->get_attribute( $custom_label1 ) : $product_info['custom_label1'];
					
					if ($attachment_ids) {
						$Original_image_url = Array();
						$Original_image_url_fb = Array();
						foreach( $attachment_ids as $attachment_id ) {
							$Original_image_url[] = '<g:additional_image_link>' . wp_get_attachment_url( $attachment_id ) . '</g:additional_image_link>';
							$Original_image_url_fb[] = wp_get_attachment_url( $attachment_id );							
						}
						$Original_image_url_echo = implode('',$Original_image_url);
						$Original_image_url_echo_fb = '<g:additional_image_link>'.	implode(',',$Original_image_url_fb) . '</g:additional_image_link>';
					} else {
						$Original_image_url_echo = '';
						$Original_image_url_echo_fb = '';
					}
					$product_info['addimage'] = $Original_image_url_echo;
					$product_info['addimage_fb'] = $Original_image_url_echo_fb;

					$google_product[] = $product_info;					
				}
			} else {
				
				$product_info['id'] = $product_id;
				
				$product_info['title'] = wpadmin_clear_text_v2($product->get_title());
				
				$product_info['description'] = wpadmin_clear_text_v2(($product->get_description() <> '') ? $product->get_description() : $product->get_short_description());
				
				$product_info['link'] = htmlspecialchars(get_permalink($product_id), ENT_XML1, 'UTF-8');
				
				$product_info['image'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
				
				$product_info['price'] = $product->get_price().' '.get_woocommerce_currency();
				
				$product_info['product_type'] = get_gf_crumb_v2($product_id);
				
				$product_info['stock'] = ( $product->get_stock_status() == 'instock')? 'in_stock' : 'out_of_stock';
					$product_info['stock_fb'] = ( $product->get_stock_status() == 'instock')? 'in stock' : 'out of stock';
					
				$product_info['condition_fb'] = 'new';
				
				$product_info['item_group_id'] = $product_id;

				if ($attachment_ids) {
					$Original_image_url = Array();
					$Original_image_url_fb = Array();
					foreach( $attachment_ids as $attachment_id ) {
						$Original_image_url[] = '<g:additional_image_link>' . wp_get_attachment_url( $attachment_id ) . '</g:additional_image_link>';						
						$Original_image_url_fb[] = wp_get_attachment_url( $attachment_id );						
					}
					$Original_image_url_echo = implode('',$Original_image_url);
					$Original_image_url_echo_fb = '<g:additional_image_link>'.	implode(',',$Original_image_url_fb) . '</g:additional_image_link>';
				} else {
					$Original_image_url_echo = '';
					$Original_image_url_echo_fb = '';
				}
				$product_info['addimage'] = $Original_image_url_echo;
				$product_info['addimage_fb'] = $Original_image_url_echo_fb;
				
				$product_info['color'] = ($color_attr <> '') ? $product->get_attribute( $color_attr ) : ''; 
				$product_info['size'] = ($size_attr <> '') ? $product->get_attribute( $size_attr ) : '';
				
				$product_info['custom_label0'] = ($custom_label0 <> '' && $product->get_attribute( $custom_label0 ) <> '') ? $product->get_attribute( $custom_label0 ) : '';
				$product_info['custom_label1'] = ($custom_label1 <> '' && $product->get_attribute( $custom_label1 ) <> '') ? $product->get_attribute( $custom_label1 ) : '';

				$google_product[] = $product_info;
			}
		}




		$output  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$output .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
		$output .= '  <channel>';
		$output .= '    <title>' . htmlspecialchars(strip_tags(get_bloginfo ('name'))) . '</title>';
		$output .= '    <description>' . htmlspecialchars(strip_tags(get_bloginfo ('description'))) . '</description>';
		$output .= '    <link>' . get_bloginfo ('url') . '</link>';


		foreach ($google_product as $product) {
			
			if(function_exists('wpm_get_default_language')){				
				if (wpm_get_default_language() != $lang) $product['link'] = wpm_translate_url($product['link'], $lang);
			}
			if ($feed == 'fb') {
				$output .= '<item>';
				if ($product['item_group_id'] <> '' ) $output .= '<g:item_group_id>' . $product['item_group_id'] . '</g:item_group_id>';
				$output .= '<g:id>' . $product['id'] . '</g:id>';
				$output .= '<title><![CDATA[' . $product['title'] . ']]></title>';
				$output .= '<description><![CDATA[' . $product['description'] . ']]></description>';
				$output .= '<link>' . $product['link'] . '</link>';
				$output .= '<g:image_link>' . $product['image'] . '</g:image_link>';
				$output .= '<g:price>' . $product['price'] . '</g:price>';
				$output .= '<g:brand><![CDATA[' . $product['brand'] . ']]></g:brand>';
				$output .= '<g:condition>' .$product['condition_fb']. '</g:condition>';
				$output .= '<g:availability><![CDATA[' . $product['stock_fb'] . ']]></g:availability>';
				$output .= $product['addimage_fb'];
				if ($product['color'] <> '' ) $output .= '<color>' . urldecode($product['color']) . '</color>';//???????????????????????????????????
				if ($product['size'] <> '' ) $output .= '<size>' . urldecode($product['size']) . '</size>';//???????????????????????????????????
				if ($product['custom_label0'] <> '' ) $output .= '<g:custom_label_0>' . urldecode($product['custom_label0']) . '</g:custom_label_0>';//???????????????????????????????????
				if ($product['custom_label1'] <> '' ) $output .= '<g:custom_label_1>' . urldecode($product['custom_label1']) . '</g:custom_label_1>';//???????????????????????????????????
			} else {
				$output .= '<item>';
				if ($product['item_group_id'] <> '' ) $output .= '<g:item_group_id>' . $product['item_group_id'] . '</g:item_group_id>';
				$output .= '<g:id>' . $product['id'] . '</g:id>';
				$output .= '<g:title><![CDATA[' . $product['title'] . ']]></g:title>';
				$output .= '<g:description><![CDATA[' . $product['description'] . ']]></g:description>';
				$output .= '<g:link>' . $product['link'] . '</g:link>';
				$output .= '<g:image_link>' . $product['image'] . '</g:image_link>';
				$output .= '<g:price>' . $product['price'] . '</g:price>';
				$output .= '<g:product_type>' . $product['product_type'] . '</g:product_type>';
				$output .= '<g:brand><![CDATA[' . $product['brand'] . ']]></g:brand>';
				$output .= '<g:availability><![CDATA[' . $product['stock'] . ']]></g:availability>';
				$output .= $product['addimage'];
				if ($product['color'] <> '' ) $output .= '<g:color>' . urldecode($product['color']) . '</g:color>';//???????????????????????????????????
				if ($product['size'] <> '' ) $output .= '<g:size>' . urldecode($product['size']) . '</g:size>';//???????????????????????????????????
				if ($product['custom_label0'] <> '' ) $output .= '<g:custom_label_0>' . urldecode($product['custom_label0']) . '</g:custom_label_0>';//???????????????????????????????????
				if ($product['custom_label1'] <> '' ) $output .= '<g:custom_label_1>' . urldecode($product['custom_label1']) . '</g:custom_label_1>';//???????????????????????????????????
			}
			$output .= '</item>';
		}


		$output .= '  </channel>';
		$output .= '</rss>';
		set_transient( $cache_key, $output, 6 * HOUR_IN_SECONDS );
	}
    echo $output;
}