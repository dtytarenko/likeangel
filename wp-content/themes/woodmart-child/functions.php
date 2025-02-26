<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

function custom_change_product_title($title, $product) {
    $patternFirst = "/One size,|, S-M|, L-XL|, S|, XS|, L|XL,|L,|S-M,|S,|M,|XS,/";
    $patternLast = "/&#8211; S/";

    $title = preg_replace($patternFirst, '', $title);
    $title = preg_replace($patternLast, '', $title);
    return $title;
}

add_filter('the_title', 'custom_change_product_title', 10, 2);


add_filter('xmlrpc_enabled', '__return_false');
add_action('wp_footer', function() {
    if (is_order_received_page()) {
        global $wp_query;
        $order_id = isset($wp_query->query_vars['order-received']) ? $wp_query->query_vars['order-received'] : null;

        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $transaction_id = $order->get_transaction_id() ?: $order_id;

        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = [
                'name' => $item->get_name(),
                'id' => $product->get_id(),
                'price' => $product->get_price(),
                'brand' => get_post_meta($product->get_id(), '_product_brand', true) ?: 'Likeangel',
                'category' => wc_get_product_category_list($product->get_id(), ', '),
                'variant' => $product->get_attribute('pa_color'),
                'quantity' => $item->get_quantity(),
                'google_business_vertical' => 'retail',
            ];
        }

        // Формуємо eventModel з додаванням transaction_id
        $event_model = [
            'value' => $order->get_total(),
            'transaction_id' => (string) $transaction_id,
            'items' => $items,
        ];

        ?>
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: "purchase",
                eventModel: <?php echo json_encode($event_model); ?>
            });
        </script>
        <?php
    }
});

// Прибираємо автозапонення номеру на сторінці чекаут
add_filter( 'woocommerce_checkout_fields', 'remove_billing_phone_autocomplete' );
function remove_billing_phone_autocomplete( $fields ) {
    $fields['billing']['billing_phone']['autocomplete'] = 'off';

    return $fields;
}

// Підключаємо функції backorder тільки на сторінках товарів
function la_include_backorder_functions() {
    if ( is_product() ) {
        require_once get_stylesheet_directory() . '/inc/backorder-functions.php';
    }
}
add_action( 'wp', 'la_include_backorder_functions' );

// Підключаємо JavaScript тільки на сторінках товарів
function la_enqueue_backorder_script() {
    if ( is_product() ) {
        wp_enqueue_script(
            'backorder-script',
            get_stylesheet_directory_uri() . '/js/backorder.js',
            array('jquery'),
            null,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'la_enqueue_backorder_script' );


// Оновлення фіду та додавання назви товару в  <g:description> XML файлу (Google feed) генерованого плагіном Product Catalog Pro
add_filter('wpwoofeed_product_description', function($description, $product) {
    $title = $product->get_name();
    $attributes = explode(' - ', $description); // Розбиваємо поточний description
    if (count($attributes) === 2) {
        $color = trim($attributes[0]);  // Колір
        $size = trim($attributes[1]);   // Розмір
        return "$title $color, $size";  // Формуємо правильний порядок
    }
    return $description; // Якщо формат не відповідає, повертаємо як є
}, 10, 2);


/**
 * Підключаємо логіку приховування бейджа "Немає в наявності"
 * (якщо увімкнені backorders) тільки на сторінках категорій товарів.
 */
function la_include_hide_out_of_stock_functions() {
	// Перевіряємо, чи це архів категорії товарів (product_cat).
	if ( is_product_category() || is_product_tag() || is_shop() || is_product_tag()) {
		require_once get_stylesheet_directory() . '/inc/la_product_label_preorder.php';
	}
}
add_action( 'wp', 'la_include_hide_out_of_stock_functions' );
