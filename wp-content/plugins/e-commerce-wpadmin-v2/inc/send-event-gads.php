<?php

//add additional data to product card to use in pageview event
function action_woocommerce_shop_loop_item_title() {
	$product_id = get_the_ID();
	$product_data = wpadmin_v2_get_product_data($product_id);
	$price = $product_data['price'];

	echo '
			<span style="display:none;" id="wp-product-data-'.$product_id.'" data-product_id = "'.$product_id.'" data-product_price = "'.$price.'" ></span>
	   
	';
};
add_action( 'woocommerce_before_single_product_summary', 'action_woocommerce_shop_loop_item_title', 5 );


/**********************************************************************************/
// function wpadmin_header_remarket_scripts() {
// 	$wpadmin_gadsid = (get_option('wpadmin_gadsid')) ? get_option('wpadmin_gadsid') : '';	//Google Ads tag ID	
// 	$wpadmin_add_gads = (get_option('wpadmin_add_gads')) ? get_option('wpadmin_add_gads') : 'no'; //Додати на сайт код Google ADS
// 	$wpadmin_gadsremarket = (get_option('wpadmin_gadsremarket')) ? get_option('wpadmin_gadsremarket') : ''; //Мітка події конверсії
// 	$wpadmin_remarket_gads_code = (get_option('wpadmin_remarket_gads_code')) ? get_option('wpadmin_remarket_gads_code') : 'no'; //Додати на сайт код динамічного ремаркетингу?

// 	if( (is_wc_endpoint_url( 'order-pay' )  OR is_wc_endpoint_url( 'order-received' ) )&& !empty( $_GET[ 'key' ]  ) ) {
		
//         $order_id = wc_get_order_id_by_order_key($_GET["key"]);
//         $payorder = wc_get_order($order_id);
//         $payitems = $payorder->get_items();

//         if (!isset($_COOKIE['purchase_'.$order_id])) {
//             if (!current_user_can('edit_others_pages')) {
//                 setcookie('purchase_' . $order_id, $order_id, strtotime('+30 day'));
//             }

// 			/****************************GADS order data****************************/
//             $google_html_products_ads = '';			
//             foreach ($payitems as $item_id => $item) {
//                 $google_products_pay = Array();
//                 //get product id
//                 $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();				
// 				$product_data = wpadmin_v2_get_product_data($product_id);

//                 //fill data array for analytics
//                 $google_products_pay['name'] = $product_data['name'];
//                 $google_products_pay['id'] = $product_id;
//                 $google_products_pay['price'] = $product_data['var_price'];
//                 $google_products_pay['brand'] = $product_data['brand'];
//                 $google_products_pay['category'] = $product_data['cats'];
//                 $google_products_pay['variant'] = $product_data['vars'];
//                 $google_products_pay['quantity'] = $item->get_quantity();
				
// 				$cat_4 = explode(",", $product_data['cats']);
// 				$all_cat_4 = '';
// 				$k=0;
// 				foreach ( $cat_4 as $cat ) {
// 					$k++;
// 					$ksuf = ($k == 1)? '' : $k;
// 					$all_cat_4 .= 'item_category'.$ksuf.': "'.$cat.'",';				
// 				}
				
// 				$google_html_products_ads .= ' 
// 				{
// 					"name": "'.$google_products_pay['name'].'", 
// 					"id": "'.$google_products_pay['id'].'", 
// 					"price": "'.$google_products_pay['price'].'",
// 					"brand": "'.$google_products_pay['brand'].'",
// 					"category": "'.$google_products_pay['category'].'",
// 					"variant": "'.$google_products_pay['variant'].'",
// 					"quantity": "'.$google_products_pay['quantity'].'", 
// 					"google_business_vertical": "retail",
// 				},
// 				';
//             }
//             echo "
//                 <script>
//                  jQuery( document ).ready(function($) {
// 					window.dataLayer = window.dataLayer || [];
// 					function gtag(){dataLayer.push(arguments);}
					
// 					gtag('event', 'purchase', {
// 					'send_to': '" . $wpadmin_gadsid . "',
// 					'value': '" . $payorder->get_total() . "',
// 					'items': [".$google_html_products_ads."]
// 					}); 
//                  });
//                 </script>
//             ";
// 		/********************* order data *****************************/	
		
// 		/****************************GADS Remarket order data****************************/

// 		if ($wpadmin_gadsid != '' && $wpadmin_gadsremarket != '' && $wpadmin_remarket_gads_code == 'yes') {

// 			if (!isset($_COOKIE['purchase_'.$order_id])) {
// 				if (!current_user_can('edit_others_pages')) {
// 					setcookie('purchase_' . $order_id, $order_id, strtotime('+30 day'));
// 				}

// 				$html_google_ads = '
// 					<script>
// 					window.dataLayer = window.dataLayer || [];
// 					function gtag(){dataLayer.push(arguments);}
					
// 					gtag("event", "purchase", {
// 						"send_to": "'.$wpadmin_gadsid.'/'.$wpadmin_gadsremarket.'", 
// 						"value": '.$payorder->get_total().', 
// 						"currency": "'.get_woocommerce_currency().'",
// 						"transaction_id": "'.$order_id.'" 
// 					});
// 					</script>

// 				';
				
// 				if ($payorder) {
				
// 					$customer_id = $payorder->get_customer_id(); // Or $order->get_user_id();
				 
// 					// Get the WP_User Object instance
// 					$user = $payorder->get_user(); //false if guest
					 
// 					// Get the Customer billing email address
// 					$billing_email = strtolower(ltrim(rtrim($payorder->get_billing_email())));
					 
// 					// Get the Customer billing phone
// 					$billing_phone = strtolower(ltrim(rtrim($payorder->get_billing_phone())));
					
// 					if (!empty($billing_email) OR !empty($billing_phone)) {
// 						$html_google_ads .= '
// 																	<script>
// 																		window.dataLayer = window.dataLayer || [];
// 																		function gtag(){dataLayer.push(arguments);}
																		
// 																		gtag(\'set\', \'user_data\', {';
// 						if (!empty($billing_email)) $html_google_ads .= '"email": "' . $billing_email . '",';
// 						if (!empty($billing_phone)) $html_google_ads .= '"phone_number": "' . $billing_phone . '"';
// 						$html_google_ads .= '							});
// 																	</script>
// 						';
// 					}
// 				}

// 				echo $html_google_ads;

// 			}
			
// 		}
			
			

//         }
//     }
// 	if ( is_product() && ($wpadmin_gadsid != '')) {
		
// 		//gads remarket pageview
// 		$product_id = get_the_ID();		
		
// 		if ($wpadmin_remarket_gads_code == 'yes') {
// 			wp_enqueue_script( 'wpadminpro-remarket',  plugin_dir_url(__DIR__ ) . 'js/wpadminpro-remarket.js', array( 'jquery' ), null, true );
// 			$args_remarket = array( 
// 				'remarket_gads_code' => '1',
// 				'product_id' => $product_id,
// 				'gadsid' => $wpadmin_gadsid,
// 			);
// 			wp_localize_script( 'wpadminpro-remarket', 'wpadminpro_remarket_object', $args_remarket);
// 		}
// 		///////////////////////////////////////////////////

// 		// commented because API add to cart added
// 		if (isset($_POST['add-to-cart'])) {
			
			
// 			if ($_POST['variation_id']) {
// 				$product_id = $_POST['variation_id'];				
// 			} else {
// 				$product_id = $_POST['add-to-cart'];
// 			}

//             $product_q = $_POST['quantity'];
			
// 			$product_data = wpadmin_v2_get_product_data($product_id);

//             echo "  
// 			<script>
//                 jQuery( document ).ready(function($) {
					
// 					console.log('POST addtocart variation func php'); 
// 					window.dataLayer = window.dataLayer || [];
// 					function gtag(){dataLayer.push(arguments);}
					
// 					gtag('event', 'add_to_cart', {
// 					'send_to': '" . $wpadmin_gadsid . "',
// 					'value': " . $product_data['var_price'] . ",
// 					'items': [{
// 						'id': " . $product_id . ",
// 						'google_business_vertical': 'retail'
// 					}]
// 					});
					               
					
//                 } );
//                 </script>
//             ";

//         }
// 	} 
	
// }


// add_action( 'wp_head', 'wpadmin_header_remarket_scripts', 100 );

/*

//Enqueue  Scripts
function wpadmin_v2_enqueue_remarket_scripts() {
	
	$wpadmin_gadsid = (get_option('wpadmin_gadsid')) ? get_option('wpadmin_gadsid') : '';
	
	if (is_product()) {
		
		
	} 
}
add_action('wp_enqueue_scripts', 'wpadmin_v2_enqueue_remarket_scripts');*/