<?php
if (get_option('wpadmin_fbtestcode')) {
	define("test_event_code", get_option('wpadmin_fbtestcode'));
}
define("test_event_debug", "1");


/*********************************************************************/
/**************************** Add to cart ****************************/
/*********************************************************************/
add_action( 'woocommerce_add_to_cart', 'wpadmin_v2_woocommerce_add_to_cart', 10, 6 );
function wpadmin_v2_woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	
	
	$item_id = ($variation_id) ? $variation_id : $product_id;	
	if (!$item_id OR $item_id == 0) return;
	
	$eventData = wpadmin_v2_ga4_add_to_cart($item_id,$quantity);
	
	try {
		$response = wpadmin_v2_ga4_send_request($eventData);
		error_log("Request to analytics about add_to_cart_ Event wass success!",0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
	$eventDatafb = wpadmin_v2_fb_add_to_cart($item_id,$quantity);	
	

	try {
		$response = wpadmin_v2_fb_send_request($eventDatafb);
		error_log("Request to FB about about add_to_cart_ Event wass success!",0);
		error_log(print_r($response,true),0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
}



/**************************** Add to cart - after item quantity changed ****************************/
add_action( 'woocommerce_after_cart_item_quantity_update', 'wpadmin_v2_limit_cart_item_quantity', 20, 4 );
function wpadmin_v2_limit_cart_item_quantity( $cart_item_key, $quantity, $old_quantity, $cart ){
	
	$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
	$variation_id = $cart->cart_contents[ $cart_item_key ]['variation_id'];
	$item_id = ($variation_id) ? $variation_id : $product_id;
	
	if (!$item_id OR $item_id == 0) return;
	
	$eventData = wpadmin_v2_ga4_change_cart($item_id,$quantity);

	try {
		$response = wpadmin_v2_ga4_send_request($eventData);
		error_log("Request to analytics about change quantity in add_to_cart_ Event wass success!",0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
	$eventDatafb = wpadmin_v2_fb_change_cart($item_id,$quantity);	
	
	try {
		$response = wpadmin_v2_fb_send_request($eventDatafb);
		error_log("Request to FB about change quantity in add_to_cart_ Event wass success!",0);
		error_log(print_r($response,true),0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
}


/**************************** Begin Checkout ****************************/
add_action('woocommerce_before_checkout_form', 'wpadmin_v2_woocommerce_begin_checkout');
function wpadmin_v2_woocommerce_begin_checkout() {
	
	$eventData = wpadmin_v2_ga4_checkout();
	
	try {
		$response = wpadmin_v2_ga4_send_request($eventData);
		error_log("Request to analytics about begin_checkout Event wass success!",0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
	$eventDatafb = wpadmin_v2_fb_checkout();	
	

	try {
		$response = wpadmin_v2_fb_send_request($eventDatafb);
		error_log("Request to FB about begin_checkout Event wass success!",0);
		error_log(print_r($response,true),0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
}




/**************************** Purchase ****************************/
add_action('woocommerce_new_order', 'wpadmin_v2_woocommerce_checkout_order_processed', 10, 2);
function wpadmin_v2_woocommerce_checkout_order_processed($order_id, $order) {
    if (!$order_id || $order_id == 0) return;

    $eventData = wpadmin_v2_ga4_purchase($order_id, $order);

    try {
        $response = wpadmin_v2_ga4_send_request($eventData);
        error_log("Запит до аналітики для події new_order успішно виконаний", 0);
    } catch (Exception $e) {
        error_log("Помилка: " . $e->getMessage(), 0);
    }
}





/**************************** Product/variation view ****************************/

function wpadmin_v2_woocommerce_item_view($item_id = 0, $location = '') {
	
	if (!$item_id OR $item_id == 0) return;	
	
	$eventData = wpadmin_v2_ga4_item_view($item_id);

	try {
		$response = wpadmin_v2_ga4_send_request($eventData);
		error_log("Request to analytics about view_item Event wass success!",0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
	$eventDatafb = wpadmin_v2_fb_item_view($item_id,$location);	
	
	try {
		$response = wpadmin_v2_fb_send_request($eventDatafb);
		error_log("Request to FB about view_item Event wass success!",0);
		error_log(print_r($response,true),0);
	} catch (Exception $e) {
		error_log("Some error: " . $e->getMessage(),0);
	}
	
}
