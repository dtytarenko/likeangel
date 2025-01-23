<?php

/****************************************************************************/
/**************************** Send request to GA ****************************/
/***************************************************************************/
function wpadmin_v2_fb_send_request($data) {

	$pixel_id = (get_option('wpadmin_fbid')) ? get_option('wpadmin_fbid') : 0;
	$api_secret = (get_option('wpadmin_fbapisecr')) ? get_option('wpadmin_fbapisecr') : 0;
	$api_secret_ver = (get_option('wpadmin_fbapiver')) ? get_option('wpadmin_fbapiver') :  'v19.0';
	
	
	if (defined('test_event_code')) {
		$data['test_event_code'] = test_event_code;
	}
	
	if (get_option('wpadmin_fbtestcode')) {
		$data['test_event_code'] = get_option('wpadmin_fbtestcode');
	}
	
	$url = 'https://graph.facebook.com/' . $api_secret_ver . '/' . $pixel_id . '/events?access_token=' . $api_secret;
	
	// Параметри HTTP запиту
	$options = array(
		'http' => array(
			'protocol_version' => 1.1,
			'header'  => 'Content-type: application/json',
            'ignore_errors' => true,
            'max_redirects' => 5,
			'method'  => 'POST',
			'content' => json_encode($data),
			'timeout' => 5,
		),		
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true
		),
	);
	
	
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	
	wpadmin_log('----------------------');
	wpadmin_log(json_encode($data, JSON_PRETTY_PRINT));
	wpadmin_log('----------------------');
	wpadmin_log($url);
	wpadmin_log('----------------------');

	// Перевірка на помилки
	if ($result === false) {
		throw new Exception('Error while sending request to  Measurement Protocol.');
	}
	
	return $result;
}



/********************************************************************/
/**************************** Get GA CID ****************************/
/********************************************************************/
function getClientIdFromFBCookie() {		
    if (isset($_COOKIE['_ga'])) {
        $gaCookie = $_COOKIE['_ga'];
        $parts = explode('.', $gaCookie);
        
        if (count($parts) >= 4) {
			
            return $parts[2].'.'.$parts[3];
        }
    }
    $gaCookie = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
	
    return $gaCookie;
}


function wpadmin_v2_fb_client_data_by_user($user_id = 0) {

	// Get an instance of the WC_Customer Object from the user ID
	$customer = new WC_Customer( $user_id );

	$user_data['username']     = $customer->get_username(); // Get username
	$user_data['email']   = $customer->get_email(); // Get account email
	//$user_data['first_name']   = $customer->get_first_name();
	//$user_data['last_name']    = $customer->get_last_name();
	$user_data['display_name'] = $customer->get_display_name();

	// Customer billing information details (from account)
	$user_data['first_name'] = $customer->get_billing_first_name();
	$user_data['last_name']  = $customer->get_billing_last_name();
	$user_data['company']    = $customer->get_billing_company();
	$user_data['address_1']  = $customer->get_billing_address_1();
	$user_data['address_2']  = $customer->get_billing_address_2();
	$user_data['city']       = $customer->get_billing_city();
	$user_data['state']      = $customer->get_billing_state();
	$user_data['postcode']   = $customer->get_billing_postcode();
	$user_data['country']    = $customer->get_billing_country();
	$user_data['phone']      = $customer->get_billing_phone();

	// Customer shipping information details (from account)
	/*$shipping_first_name = $customer->get_shipping_first_name();
	$shipping_last_name  = $customer->get_shipping_last_name();
	$shipping_company    = $customer->get_shipping_company();
	$shipping_address_1  = $customer->get_shipping_address_1();
	$shipping_address_2  = $customer->get_shipping_address_2();
	$shipping_city       = $customer->get_shipping_city();
	$shipping_state      = $customer->get_shipping_state();
	$shipping_postcode   = $customer->get_shipping_postcode();
	$shipping_country    = $customer->get_shipping_country();*/
	return $user_data;
}

function wpadmin_v2_fb_client_data_by_order($order_id = 0) {
	// First we get an instance of the WC_Order Object from the Order ID 
	$order = wc_get_order( $order_id );
	
	if ($order) {
	
		$customer_id = $order->get_customer_id(); // Or $order->get_user_id();
	 
		// Get the WP_User Object instance
		$user = $order->get_user(); //false if guest
		 
		// Get the Customer billing email address
		$billing_email = $order->get_billing_email();
		 
		// Get the Customer billing phone
		$billing_phone = $order->get_billing_phone();
	}
	
}

function wpadmin_v2_fb_item_view($item_id = 0, $location = '') {
	
	
	if (!$item_id OR $item_id == 0) return null;
	$cid = $gid = getClientIdFromFBCookie();
	
	$product_data = wpadmin_v2_get_product_data($item_id);	
	
	$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = explode(',', $product_data['cats']);
	$price = $product_data['price'];
	$item_variant = $product_data['vars'];
	
	//$cur_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
	
	
	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$user_data = wpadmin_v2_fb_client_data_by_user($current_user->ID);
		$em = hash('sha256', $user_data['email']); 
		$ph = hash('sha256', $user_data['phone']);
		$ln = hash('sha256', $user_data['last_name']); 
		$fn = hash('sha256', $user_data['first_name']);
		$country = hash('sha256', $user_data['country']);
	} else {
		$em = hash('sha256', ''); 
		$ph = hash('sha256', '');
		$ln = hash('sha256', ''); 
		$fn = hash('sha256', '');
		$country = hash('sha256', '');
	}   
	
	$eventData = array(
					'data' => array(
									array(
										'event_name' => 'ViewContent', 
										'event_time' => time(), 
										'event_id' => $item_id.hash('sha256', time()), 
										'event_source_url' => $location, 
										'action_source' => 'website',
										'user_data' => array(
											'client_ip_address' => wpadmin_v2_get_ip_address(), 
											'client_user_agent' => wpadmin_v2_get_user_agent(), 
											'em' => $em, 
											'ph' => $ph, 
											'ln' => $ln, 
											'fn' => $fn, 
											'country' => $country,								
										),
										'custom_data' => array(
											'content_type' => 'product', 
											'content_ids' => array($item_id),
											'currency' => get_woocommerce_currency(), 
											'value' => $price, 						
										),
									)
					),
					/*'test_event_code' => 'TEST35891'	*/		
	);
	
	return $eventData;
}

function wpadmin_v2_fb_add_to_cart($item_id,$quantity) {
	
	if (!$item_id OR $item_id == 0) return null;
	
	$cid = $gid = getClientIdFromFBCookie();
	
	$product_data = wpadmin_v2_get_product_data($item_id);
	
	
	$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = $product_data['cats'];
	$price = $product_data['price'];
	$total = $price * $quantity;
	$item_variant = $product_data['vars'];
	
	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$user_data = wpadmin_v2_fb_client_data_by_user($current_user->ID);
		$em = hash('sha256', $user_data['email']); 
		$ph = hash('sha256', $user_data['phone']);
		$ln = hash('sha256', $user_data['last_name']); 
		$fn = hash('sha256', $user_data['first_name']);
		$country = hash('sha256', $user_data['country']);
	} else {
		$em = hash('sha256', ''); 
		$ph = hash('sha256', '');
		$ln = hash('sha256', ''); 
		$fn = hash('sha256', '');
		$country = hash('sha256', '');
	}   
	// Дані про подію додавання товару в кошик
	
	$eventData = array(
					'data' => array(
						array(
							'event_name' => 'AddToCart', 
							'event_time' => time(), 
							'event_id' => $item_id.hash('sha256', time()), 
							'action_source' => 'website', 
							'user_data' => array(
								'client_ip_address' => wpadmin_v2_get_ip_address(), 
								'client_user_agent' => wpadmin_v2_get_user_agent(), 
								'em' => $em, 
								'ph' => $ph, 
								'ln' => $ln, 
								'fn' => $fn, 
								'country' => $country,								
							),
							'custom_data' => array(
								'content_type' => 'product', 
								'content_ids' => array($item_id),
								'currency' => get_woocommerce_currency(), 
								'value' => $total, 						
							),
						)
					),
	);
	
	return $eventData;
	
}

function wpadmin_v2_fb_change_cart($item_id,$quantity) {
	
	if (!$item_id OR $item_id == 0) return null;
	
	$cid = $gid = getClientIdFromFBCookie();

	$product_data = wpadmin_v2_get_product_data($item_id);
	
	/*$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = $product_data['cats'];*/
	$price = $product_data['price'];
	$total = $price * $quantity;
	/*$item_variant = $product_data['vars'];*/
	
	// Дані про подію додавання товару в кошик
	
	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$user_data = wpadmin_v2_fb_client_data_by_user($current_user->ID);
		$em = hash('sha256', $user_data['email']); 
		$ph = hash('sha256', $user_data['phone']);
		$ln = hash('sha256', $user_data['last_name']); 
		$fn = hash('sha256', $user_data['first_name']);
		$country = hash('sha256', $user_data['country']);
	} else {
		$em = hash('sha256', ''); 
		$ph = hash('sha256', '');
		$ln = hash('sha256', ''); 
		$fn = hash('sha256', '');
		$country = hash('sha256', '');
	}   
	// Дані про подію додавання товару в кошик
	
	$eventData = array(
					'data' => array(
						array(
							'event_name' => 'AddToCart', 
							'event_time' => time(), 
							'event_id' => $item_id.hash('sha256', time()), 
							'action_source' => 'website', 
							'user_data' => array(
								'client_ip_address' => wpadmin_v2_get_ip_address(), 
								'client_user_agent' => wpadmin_v2_get_user_agent(), 
								'em' => $em, 
								'ph' => $ph, 
								'ln' => $ln, 
								'fn' => $fn, 
								'country' => $country,								
							),
							'custom_data' => array(
								'content_type' => 'product', 
								'content_ids' => array($item_id),
								'currency' => get_woocommerce_currency(), 
								'value' => $total, 						
							),
						)
					),
	);
	
	return $eventData;
	
}

function wpadmin_v2_fb_checkout() {
	
	if (!WC()->cart->is_empty()) {
		
		$cid = $gid = getClientIdFromFBCookie();
		
		//$cart = WC()->cart;
		$cart = WC()->cart->get_cart();
		
		$content_ids = array();
		$contents = array();
		
		// Loop over $cart items
		foreach ( $cart as $item_key => $item ) {
			
		    if( $item['variation_id'] > 0 ){
				$product_id = $item['variation_id']; // variable product
			} else {
				$product_id = $item['product_id']; // simple product
			}
			
			$product_data = wpadmin_v2_get_product_data($product_id);
			
			$content_ids[] = $product_id;
			/*if ( WC()->cart->display_prices_including_tax() ) {
				$price = wc_get_price_including_tax( $product );
			} else {
				$price = wc_get_price_excluding_tax( $product );
			}*/
			
			$contents[] = array(
								'id' => $product_id, 
								'quantity' => $item['quantity'], 
								'item_price' => $product_data['price'], 
							);			
		}		
		
		
		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$user_data = wpadmin_v2_fb_client_data_by_user($current_user->ID);
			$em = hash('sha256', $user_data['email']); 
			$ph = hash('sha256', $user_data['phone']);
			$ln = hash('sha256', $user_data['last_name']); 
			$fn = hash('sha256', $user_data['first_name']);
			$country = hash('sha256', $user_data['country']);
		} else {
			$em = hash('sha256', ''); 
			$ph = hash('sha256', '');
			$ln = hash('sha256', ''); 
			$fn = hash('sha256', '');
			$country = hash('sha256', '');
		}   
		// Дані про подію додавання товару в кошик
	
		$eventData = array(
						'data' => array(
							array(
								'event_name' => 'InitiateCheckout', 
								'event_time' => time(), 
								'event_id' => hash('sha256', time()), 
								'action_source' => 'website', 
								'user_data' => array(
									'client_ip_address' => wpadmin_v2_get_ip_address(), 
									'client_user_agent' => wpadmin_v2_get_user_agent(), 
									'em' => $em, 
									'ph' => $ph, 
									'ln' => $ln, 
									'fn' => $fn, 
									'country' => $country,								
								),
								'custom_data' => array(
									'currency' => get_woocommerce_currency(), 
									'value' => WC()->cart->total, 	
									'content_type' => 'product', 
									'content_ids' => $content_ids,
									'contents' => $contents,					
								),
							)
						),
		);
	
		return $eventData;
		
	} else {
		return null;
	}
	
}

function wpadmin_v2_fb_purchase($order_id, $order) {
	
	if (!$order_id OR $order_id == 0) return null;
	
	$cid = $gid = getClientIdFromFBCookie();
	
	$content_ids = array();
	$contents = array();
	
	foreach ( $order->get_items() as $item_id => $item ) {
		
		//$product = $item->get_product();

		if( $item['variation_id'] > 0 ){
			$product_id = $item['variation_id']; // variable product
		} else {
			$product_id = $item['product_id']; // simple product
		}
		
		$product_data = wpadmin_v2_get_product_data($product_id);
		
		$content_ids[] = $product_id;	
		
		/*if ( WC()->cart->display_prices_including_tax() ) {
			$price = wc_get_price_including_tax( $product );
		} else {
			$price = wc_get_price_excluding_tax( $product );
		}*/
		
		$contents[] = array(
							'id' => $product_id, 
							'quantity' => $item->get_quantity(), 
							'item_price' => $product_data['price'], 
					);
	}

	
	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$user_data = wpadmin_v2_fb_client_data_by_user($current_user->ID);
		$em = hash('sha256', $user_data['email']); 
		$ph = hash('sha256', $user_data['phone']);
		$ln = hash('sha256', $user_data['last_name']); 
		$fn = hash('sha256', $user_data['first_name']);
		$country = hash('sha256', $user_data['country']);
	} else {
		$em = hash('sha256', ''); 
		$ph = hash('sha256', '');
		$ln = hash('sha256', ''); 
		$fn = hash('sha256', '');
		$country = hash('sha256', '');
	}   
	// Дані про подію додавання товару в кошик

	$eventData = array(
					'data' => array(
						array(
							'event_name' => 'Purchase', 
							'event_time' => time(), 
							'event_id' => $order_id.hash('sha256', time()), 
							'action_source' => 'website', 
							'user_data' => array(
								'client_ip_address' => wpadmin_v2_get_ip_address(), 
								'client_user_agent' => wpadmin_v2_get_user_agent(), 
								'em' => $em, 
								'ph' => $ph, 
								'ln' => $ln, 
								'fn' => $fn, 
								'country' => $country,								
							),
							'custom_data' => array(
								'currency' => get_woocommerce_currency(), 
								'value' => $order->get_total(), 	
								'content_type' => 'product', 
								'content_ids' => $content_ids,
								'contents' => $contents,					
							),
						)
					),
	);

	return $eventData;
	
}