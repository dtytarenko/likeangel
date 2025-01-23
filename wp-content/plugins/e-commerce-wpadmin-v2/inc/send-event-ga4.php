<?php


/****************************************************************************/
/**************************** Send request to GA ****************************/
/***************************************************************************/
function wpadmin_v2_ga4_send_request($data) {

	$api_secret = (get_option('wpadmin_ga4secr')) ? get_option('wpadmin_ga4secr') : '';
	$measurement_id = (get_option('wpadmin_ga4mid')) ? get_option('wpadmin_ga4mid') : '';
	$url = 'https://www.google-analytics.com/mp/collect?measurement_id=' . $measurement_id . '&api_secret=' . $api_secret;
	
	if (defined('test_event_debug')) {		
		$data['events'][0]['params']['debug_mode'] = true;
	}
	
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
	
	wpadmin_log(json_encode($data, JSON_PRETTY_PRINT));

	// Перевірка на помилки
	if ($result === false) {
		throw new Exception('Error while sending request to  Measurement Protocol.');
	}
	
	return $result;
}



/********************************************************************/
/**************************** Get GA CID ****************************/
/********************************************************************/
function getClientIdFromGaCookie() {		
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


function getSessionIdFromGaCookie() {
	$measurement_id = (get_option('wpadmin_ga4mid')) ? get_option('wpadmin_ga4mid') : false;
	// Cookie name example: '_ga_1YS1VWHG3V'.
	if ($measurement_id) {
		$cookie_name = '_ga_' . str_replace('G-', '', $measurement_id);
		if (isset($_COOKIE[$cookie_name])) {
			// Cookie value example: 'GS1.1.1659710029.4.1.1659710504.0'.
			// Session Id:                  ^^^^^^^^^^.
			$parts = explode('.', $_COOKIE[$cookie_name]);
			return $parts[2];
		}
	}
	
    return '';
}


function wpadmin_v2_ga4_item_view($item_id = 0) {
	
	if (!$item_id OR $item_id == 0) return null;
	$cid = $gid = getClientIdFromGaCookie();
	$sid = getSessionIdFromGaCookie();
	
	$product_data = wpadmin_v2_get_product_data($item_id);
	
	$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = explode(',', $product_data['cats']);
	$price = $product_data['price'];
	$item_variant = $product_data['vars'];
	
	
	$eventData = array(
		'client_id' => $cid, // ID клієнта (відвідувача)
		'non_personalized_ads' => false,
		'events' => array(
			array(
				'name' => 'view_item', 
				'params' => array(
					'items'  => array(array(
						'item_id'  => $item_id ,
						'item_name'  => $item_name ,
						'quantity'  => 1 ,
						'item_brand'  => $item_brand ,
						'price'  => $price ,
						'item_variant'  => $item_variant ,
						'currency' => get_woocommerce_currency(),
					)),
					'currency'  => get_woocommerce_currency(),
					'value'  => $price,
					'session_id' => $sid, 
					/*'debug_mode' => true,*/
				)
			)
		)
	);
	
	$i=0;
	
	foreach ($item_category as $itemcat) {
		if ($i == 0) {
			$eventData['events'][0]['params']['items'][0]['item_category'] = $itemcat;
		} else {			
			$eventData['events'][0]['params']['items'][0]['item_category'.$i] = $itemcat;
		}
		$i++;
	}
	
	return $eventData;
}

function wpadmin_v2_ga4_add_to_cart($item_id,$quantity) {
	
	if (!$item_id OR $item_id == 0) return null;
	
	$cid = $gid = getClientIdFromGaCookie();
	$sid = getSessionIdFromGaCookie();
	
	$product_data = wpadmin_v2_get_product_data($item_id);
	
	
	$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = $product_data['cats'];
	$price = $product_data['price'];
	$total = $price * $quantity;
	$item_variant = $product_data['vars'];

	// Дані про подію додавання товару в кошик
	
	$eventData = array(
		'client_id' => $cid, // ID клієнта (відвідувача)
		'non_personalized_ads' => false,
		'events' => array(
			array(
				'name' => 'add_to_cart', 
				'params' => array(
					'items'  => array(array(
						'item_id'  => $item_id ,
						'item_name'  => $item_name ,
						'quantity'  => $quantity ,
						'item_brand'  => $item_brand ,
						'item_category'  => $item_category ,
						'price'  => $price ,
						'item_variant'  => $item_variant ,
						'currency' => get_woocommerce_currency(),
					)),
					'currency'  => get_woocommerce_currency(),
					'value'  => $total,
					'session_id' => $sid, 
					/*'debug_mode' => true,*/
				)
			)
		)
	);
	
	return $eventData;
	
}

function wpadmin_v2_ga4_change_cart($item_id,$quantity) {
	
	if (!$item_id OR $item_id == 0) return null;
	
	$cid = $gid = getClientIdFromGaCookie();
	$sid = getSessionIdFromGaCookie();

	$product_data = wpadmin_v2_get_product_data($item_id);
	
	$item_name = $product_data['name'];
	$item_brand = $product_data['brand'];
	$item_category = $product_data['cats'];
	$price = $product_data['price'];
	$total = $price * $quantity;
	$item_variant = $product_data['vars'];
	
	// Дані про подію додавання товару в кошик
	
	$eventData = array(
		'client_id' => $cid, // ID клієнта (відвідувача)
		'non_personalized_ads' => false,
		'events' => array(
			array(
				'name' => 'add_to_cart', 
				'params' => array(
					'items'  => array(array(
						'item_id'  => $item_id ,
						'item_name'  => $item_name ,
						'quantity'  => $quantity ,
						'item_brand'  => $item_brand ,
						'item_category'  => $item_category ,
						'price'  => $price ,
						'item_variant'  => $item_variant ,
						'currency' => get_woocommerce_currency(),
					)),
					'currency'  => get_woocommerce_currency(),
					'value'  => $total,
					'session_id' => $sid, 
					/*'debug_mode' => true,*/
				)
			)
		)
	);
	
	return $eventData;
	
}

function wpadmin_v2_ga4_checkout() {
	
	if (!WC()->cart->is_empty()) {
		
		$cid = $gid = getClientIdFromGaCookie();
		$sid = getSessionIdFromGaCookie();
		
		//$cart = WC()->cart;
		$cart = WC()->cart->get_cart();
		
		// Loop over $cart items
		foreach ( $cart as $item_key => $item ) {
		   if( $item['variation_id'] > 0 ){
				$product_id = $item['variation_id']; // variable product
			} else {
				$product_id = $item['product_id']; // simple product
			}
			
			$product_data = wpadmin_v2_get_product_data($product_id);
			
			
			
			$getitems[$product_id] = array(
				"item_id" => $product_id,
				"item_name" => $product_data['name'],
				"currency" => get_woocommerce_currency(),
				"item_brand" => $product_data['brand'],
				//"item_category" => $product_data['cats'],
				"item_variant" => $product_data['vars'],
				"price" => $product_data['price'],
				"quantity" => $item['quantity'],
			);
			
			
			$i=1;
			$item_category = explode(',', $product_data['cats']);
			foreach ($item_category as $itemcat) {
				if ($i == 1) {
					$getitems[$product_id]['item_category'] = $itemcat;
				} else {			
					$getitems[$product_id]['item_category'.$i] = $itemcat;
				}
				$i++;
			}
		}
		
		$eventData = array(
			'client_id' => $cid, // ID клієнта (відвідувача)
			'non_personalized_ads' => false,
			'events' => array(
				array(
					'name' => 'begin_checkout', 
					'params' => array(
						//'items'  => array($getitems),
						'currency'  => get_woocommerce_currency(),
						'value'  => WC()->cart->total,
						'session_id' => $sid, 
						/*"coupon": "SUMMER_FUN",*/
						/*'debug_mode' => true,*/
					)
				)
			)
		);
		
		foreach ($getitems as $gitem) {
			$eventData['events'][0]['params']['items'][] = $gitem;
		}
		
		return $eventData;
		
	} else {
		return null;
	}
	
}

function wpadmin_v2_ga4_purchase($order_id, $order) {
    if (!$order_id || $order_id == 0) return null;

    $cid = getClientIdFromGaCookie();
    $sid = getSessionIdFromGaCookie();

    $getitems = [];

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
        $product_data = wpadmin_v2_get_product_data($product_id);

        $getitems[] = [
            "item_id" => $product_id,
            "item_name" => $product_data['name'],
            "currency" => get_woocommerce_currency(),
            "item_brand" => $product_data['brand'],
            "item_variant" => $product_data['vars'],
            "price" => $product_data['price'],
            "quantity" => $item->get_quantity(),
        ];
    }

    $transaction_id = $order->get_transaction_id() ?: $order_id;

    ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'purchase',
            transaction_id: '<?php echo $transaction_id; ?>',
            value: <?php echo $order->get_total(); ?>,
            currency: '<?php echo get_woocommerce_currency(); ?>',
            shipping: <?php echo $order->get_total_shipping(); ?>,
            tax: <?php echo $order->get_total_tax(); ?>,
            items: <?php echo json_encode($getitems); ?>
        });
        console.log('Подія purchase додана з transaction_id: <?php echo $transaction_id; ?>');
    </script>
    <?php

    return [
        'client_id' => $cid,
        'non_personalized_ads' => false,
        'events' => [
            [
                'name' => 'purchase',
                'params' => [
                    'transaction_id' => $transaction_id,
                    'currency' => get_woocommerce_currency(),
                    'value' => $order->get_total(),
                    'shipping' => $order->get_total_shipping(),
                    'tax' => $order->get_total_tax(),
                    'items' => $getitems,
                ]
            ]
        ]
    ];
}



