<?php

/*
  Plugin Name: DNTrade API Plugin
  Description: Плагін для інтеграції з програмою обліку DNTrade.
  Version: 1.0
  Author: ТОВ "ДНТрейд"
 */

// Підключення файлу з адміністративною частиною плагіну
require_once(plugin_dir_path(__FILE__) . 'includes/admin/admin.php');

// Прийняття запитів на ваш плагін
add_action('rest_api_init', function () {
    register_rest_route('dntrade-api/v1', '/check', array(
	'methods' => 'GET',
	'callback' => 'dntrade_api_check',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/getstatuses', array(
	'methods' => 'GET',
	'callback' => 'dntrade_api_get_order_statuses',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/categories', array(
	'methods' => 'GET',
	'callback' => 'dntrade_api_get_product_categories',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/product', array(
	'methods' => 'POST',
	'callback' => 'dntrade_api_get_product_info',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/products', array(
	'methods' => 'POST',
	'callback' => 'dntrade_api_get_products',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/orders', array(
	'methods' => 'POST',
	'callback' => 'dntrade_api_get_orders',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/upload', array(
	'methods' => 'POST',
	'callback' => 'dntrade_api_create_or_update_products',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));

    register_rest_route('dntrade-api/v1', '/setstatus', array(
	'methods' => 'POST',
	'callback' => 'dntrade_api_update_order_status',
	'permission_callback' => 'dntrade_api_check_token_permission'
    ));
});

// Перевірка токену доступу
function dntrade_api_check_token_permission() {
    $token = null;

    // Отримання токену з заголовка Authorization
    $auth_header = isset($_SERVER['HTTP_APIKEY']) ? $_SERVER['HTTP_APIKEY'] : '';
    if (!empty($auth_header)) {	
	$token = $auth_header;	
    }
    if (empty(get_option('dntrade_api_token'))) {
	return false;
    }
    // Перевірка токену
    if ($token === get_option('dntrade_api_token')) {
	return true; // Токен вірний
    } else {
	return false; // Неправильний токен
    }
}

// Обробка отриманих даних
function dntrade_api_check() {
    // Повернення результату у форматі JSON
    return new WP_REST_Response(array(
	'status' => 1
	    ), 200);
}

function dntrade_api_get_order_statuses() {
    // Отримання всіх статусів замовлень WooCommerce
    $order_statuses = wc_get_order_statuses();
    $formatted_statuses = array();
    foreach ($order_statuses as $key => $value) {
	$formatted_statuses[] = array(
	    'id' => $key,
	    'title' => $value,
	);
    }

    // Повернення статусів у форматі JSON
    return new WP_REST_Response(array('status' => 1, 'statuses' => $formatted_statuses), 200);
}

function dntrade_api_get_product_categories() {
    $categories = array();

    // Отримуємо всі категорії WooCommerce
    $wc_categories = get_terms(array(
	'taxonomy' => 'product_cat',
	'hide_empty' => false,
    ));

    // Перебираємо кожну категорію
    foreach ($wc_categories as $category) {
	// Отримуємо URL зображення категорії (якщо воно є)
	$categoryImage = get_term_meta($category->term_id, 'thumbnail_id', true);
	$categoryImageURL = wp_get_attachment_url($categoryImage);

	// Додаємо категорію у форматі ['id' => ..., 'title' => ..., 'image' => ..., 'parent_id' => ...]
	$categories[] = array(
	    'id' => $category->term_id,
	    'title' => $category->name,
	    'image' => $categoryImageURL,
	    'parent_id' => ($category->parent != 0) ? $category->parent : null,
	);
    }

    // Повертаємо категорії у форматі JSON
    return new WP_REST_Response(['status' => 1, 'data' => $categories], 200);
}

function dntrade_api_get_product_info(WP_REST_Request $request) {
    // Отримуємо id товару з запиту POST
    $product_id = $request->get_param('id');

    // Отримання інформації про товар за його ідентифікатором
    $product = wc_get_product($product_id);

    if (!$product) {
	return new WP_REST_Response(array(
	    'error' => 'Товар не знайдено'
		), 404);
    }
    // Викликаємо функцію, що повертає інформацію про товар за його id
    $response = dntrade_api_get_product_item($product);
    return new WP_REST_Response(['status' => 1, 'product' => $response], 200);
}

function dntrade_api_get_products(WP_REST_Request $request) {
    // Отримання параметрів limit та offset з запиту POST
    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');

    // Отримання товарів з урахуванням limit та offset
    $args = array(
	'limit' => $limit,
	'offset' => $offset,
    );
    $products = wc_get_products($args);

    // Побудова масиву з інформацією про товари
    $response = array();
    foreach ($products as $product) {
	// Отримання інформації про кожен товар
	$response[] = dntrade_api_get_product_item($product);
	if ($product->is_type('variable')) {
            $variations = $product->get_children(); 
            foreach ($variations as $variation_id) {
                $variation = new WC_Product_Variation($variation_id);
                // Структура для кожної варіації
                $response[] = array(
                    'id' => $variation->get_id(),
                    'title' => $variation->get_name(),
                    'code' => $variation->get_sku(),
                    'sku' => $variation->get_sku(),
                    'quantity' => $variation->get_stock_quantity(),
                    'status' => (int) ($variation->get_status() == "publish"),
                    'link' => get_permalink($variation->get_id()),
                    'unit_title' => 'шт',
                    'image' => $variation->get_image_id() ? wp_get_attachment_url($variation->get_image_id()) : null,
                    'parent' => null, // Варіації не мають категорій
                    'price' => $variation->get_price(),
                    'description' => $variation->get_description(),
                );
            }
        }
    }

    // Повернення товарів у форматі JSON
    return new WP_REST_Response(['status' => 1, 'data' => $response], 200);
}

function dntrade_api_get_orders(WP_REST_Request $request) {
    // Отримання параметрів з POST-запиту
    $from_date = $request->get_param('from_date');
    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');

    if ($from_date) {
	$from_date = date("Y-m-d H:i:s", strtotime($from_date));
    }

    if (empty($limit) || $limit > 100) {
	$limit = 100;
    }

    // Отримання замовлень з використанням параметрів limit та offset
    $args = array(
	'limit' => $limit,
	'offset' => $offset,
	'date_created' => $from_date
    );
    $orders = wc_get_orders($args);

    // Побудова масиву з інформацією про замовлення
    $order_data = array();
    foreach ($orders as $order) {
	$order_info = array(
	    'order_id' => $order->get_id(),
	    'customer_id' => $order->get_customer_id(),
	    'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
	    'email' => $order->get_billing_email(),
	    'phone' => $order->get_billing_phone(),
	    'payment_method' => $order->get_payment_method(),
	    'address' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . ', ' .
                         $order->get_shipping_company() . ', ' .
                         $order->get_shipping_address_1() . ($order->get_shipping_address_2() ? ', ' . $order->get_shipping_address_2() : '') . ', ' .
                         $order->get_shipping_city() . ', ' .
                         $order->get_shipping_state() . ', ' .
                         $order->get_shipping_postcode() . ', ' .
                         $order->get_shipping_country(),
	    'shipping_method' => $order->get_shipping_method(),
	    'comment' => $order->get_customer_note(),
	    'total' => $order->get_total(),
	    'order_status' => $order->get_status(),
	    'date_added' => $order->get_date_created()->date('Y-m-d H:i:s'),
	    'date_modified' => $order->get_date_modified()->date('Y-m-d H:i:s')
	);

	// Отримання товарів у замовленні
	$order_products = array();
	foreach ($order->get_items() as $item_id => $item) {
	    $product = $item->get_product();
	    $order_products[] = array(
		'id' => $product->get_id(),
		'product' => dntrade_api_get_product_item($product),
		'quantity' => $item->get_quantity(),
		'price' => $item->get_total() / $item->get_quantity(),
		'total_price' => $item->get_total(),
		'tax' => $item->get_subtotal_tax(),
		'reward' => ''
	    );
	}

	$order_info['products'] = $order_products;
	$order_data[] = $order_info;
    }


    // Повернення інформації про замовлення у форматі JSON
    return new WP_REST_Response(array('status' => 1, 'orders' => $order_data), 200);
}

function dntrade_api_create_or_update_products(WP_REST_Request $request) {
    $products = json_decode($request->get_param('products'));
    // Масив для зберігання нових ідентифікаторів товарів
    $new_ids = array();

    // Перевірка наявності товарів в POST-запиті
    if (!isset($products) || empty($products)) {
	return new WP_REST_Response(array('status' => 0, 'message' => 'Параметр products обов\'язковий'), 400);
    }

    // Перебір кожного товару
    foreach ($products as $item) {
	// Побудова даних для оновлення або створення товару
	$data = array(
	    'name' => isset($item->title) ? $item->title : null,
	    'price' => isset($item->price) ? $item->price : null,
	    'price_old' => isset($item->price_old) ? $item->price_old : null,
	    'quantity' => isset($item->quantity) ? $item->quantity : null,
	    'description' => isset($item->description) ? $item->description : null,
	    'sku' => isset($item->sku) ? $item->sku : null,
	    'status' => isset($item->status) ? $item->status : null,
		// Додавання інших даних, які можуть бути в товарі
	);

	// Завантаження зображення товару (якщо воно вказано)
	if (isset($item->images) && !empty($item->images)) {
	    // Отримання шляху до зображення
	    $image_url = $item->images[0];
	    if (strlen($image_url) > 10) {
		// Завантаження зображення
		$image_id = dntrade_api_upload_image_from_url($image_url);

		// Додавання зображення до даних товару
		if ($image_id) {
		    $data['image_id'] = $image_id;
		}
	    }
	}

	// Додавання категорії до товару (якщо вона вказана)
	if (isset($item->parent_id) && !empty($item->parent_id)) {
	    // Отримання категорії за її ID
	    $category = get_term_by('id', $item->parent_id, 'product_cat');

	    // Перевірка, чи знайдено категорію
	    if ($category) {
		$data['categories'] = array($category->term_id); // Додавання категорії до даних товару
	    }
	}
	// Оновлення або створення товару	
	if (!$item->is_new) {
	    // Оновлення існуючого товару
	    dntrade_api_update_product($item->id, $data);
	} else {
	    // Створення нового товару
	    $product_id = dntrade_api_create_product($data);
	    $new_ids[$item->system_id] = $product_id;
	}
    }

    // Повернення результату операції
    return new WP_REST_Response(array('status' => 1, 'new' => $new_ids), 200);
}

function dntrade_api_update_order_status(WP_REST_Request $request) {
    $order_id = $request->get_param('order_id');
    if (empty($order_id)) {
	return new WP_REST_Response(array('status' => 0, 'message' => "Параметр order_id обов'язковий"), 400);
    }

    $status = $request->get_param('status');
    if (empty($status)) {
	return new WP_REST_Response(array('status' => 0, 'message' => "Параметр status обов'язковий"), 400);
    }

    // Оновлення статусу замовлення
    $order = wc_get_order($order_id);
    if (!$order) {
	return new WP_REST_Response(array('status' => 0, 'message' => 'Замовлення не знайдено'), 404);
    }

    // Встановлення нового статусу замовлення
    $order->update_status($status);

    // Повернення результату оновлення
    return new WP_REST_Response(array('status' => 1), 200);
}

function dntrade_api_get_product_item($product) {
    // Отримання категорій товару
    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
    $category = !empty($categories) ? $categories[0] : null;

    // Побудова масиву з інформацією про товар
    $response = array(
	'id' => $product->get_id(),
	'title' => $product->get_name(),
	'code' => $product->get_sku(),
	'sku' => $product->get_sku(),
	'quantity' => $product->get_stock_quantity(),
	'status' => (int) ($product->get_status() == "publish"),
	'link' => get_permalink($product->get_id()),
	'unit_title' => 'шт',
	'image' => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : null,
	'parent' => !empty($category) ? array(
    'id' => $category->term_id,
    'title' => $category->name,
    'parent_id' => $category->parent != 0 ? $category->parent : null
	) : null,
	'price' => $product->get_price(),
	'description' => $product->get_description()
    );

    return $response;
}

function dntrade_api_upload_image_from_url($image_url) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);

    if (wp_mkdir_p($upload_dir['path'])) {
	$file = $upload_dir['path'] . '/' . $filename;
    } else {
	$file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);

    $attachment = array(
	'post_mime_type' => $wp_filetype['type'],
	'post_title' => sanitize_file_name($filename),
	'post_content' => '',
	'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $file);

    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}

function dntrade_api_create_product($data) {
    $post = array(
	'post_author' => 1,
	'post_content' => $data['description'],
	'post_status' => $data["status"] ? "publish" : "draft",
	'post_title' => $data['name'],
	'post_type' => "product",
    );

    $post_id = wp_insert_post($post);

    if ($post_id) {		
	// Додаткові дані для товару можна додати тут
	// Додавання категорії
	if (!empty($data['categories'])) {
	    wp_set_object_terms($post_id, $data['categories'], 'product_cat');
	}

	// Додаємо зображення до товару
	if (!empty($data['image_id'])) {
	    set_post_thumbnail($post_id, $data['image_id']);
	}	
	dntrade_api_update_product($post_id, $data);
    }

    return $post_id;
}

function dntrade_api_update_product($product_id, $data) {
    if (!empty($data['name'])) {
	$post = array(
	    'ID' => $product_id,
	    'post_title' => $data['name']
	);

	wp_update_post($post);
    }
    if ($data['sku'] !== null) {
	update_post_meta($product_id, '_sku', $data['sku']);
    }
    if ($data['price_old'] !== null && $data['price'] !== null) {
	if ($data['price_old'] > $data['price']) {
	    update_post_meta($product_id, '_regular_price', $data['price_old']);
	    update_post_meta($product_id, '_sale_price', $data['price']);	    
	} else {
	    update_post_meta($product_id, '_regular_price', $data['price']);
	    update_post_meta($product_id, '_sale_price', $data['price']);
	}
	update_post_meta($product_id, '_price', $data['price']);
    }
    if ($data['quantity'] !== null) {
	update_post_meta($product_id, '_manage_stock', 1);
	update_post_meta($product_id, '_stock', $data['quantity']);
	if ($data['quantity'] > 0) {
	   update_post_meta($product_id, '_stock_status', "instock"); 
	} else {
	    update_post_meta($product_id, '_stock_status', "outofstock"); 
	}
    }

    // Додаткові дані для оновлення товару можна додати тут
    // Оновлення категорії
    if (!empty($data['categories'])) {
	wp_set_object_terms($product_id, $data['categories'], 'product_cat');
    }
}