<?php
/**
 * Enqueue script and styles for child theme
 */
require_once get_stylesheet_directory() . '/inc/la_404_redirect.php';
require_once get_stylesheet_directory() . '/inc/la_login_redirect.php';
require_once get_stylesheet_directory() . '/inc/la_sort_instock_first.php';
require_once get_stylesheet_directory() . '/inc/la_manual_review_admin.php';
require_once get_stylesheet_directory() . '/inc/la_generate_payment_link.php';
require_once get_stylesheet_directory() . '/inc/la_utm_tracking.php';
require_once get_stylesheet_directory() . '/inc/la_product_label_stock.php';

// Looks (Образи) functionality - repurposed from FBT
require_once get_stylesheet_directory() . '/inc/la-looks-rename.php';
require_once get_stylesheet_directory() . '/inc/la-looks-sync.php';
require_once get_stylesheet_directory() . '/inc/la-looks-elementor.php';

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

add_filter( 'woocommerce_checkout_fields', 'remove_billing_phone_autocomplete' );
function remove_billing_phone_autocomplete( $fields ) {
    $fields['billing']['billing_phone']['autocomplete'] = 'off';
    return $fields;
}

function la_include_backorder_functions() {
   require_once get_stylesheet_directory() . '/inc/la_backorder-functions.php';
}
add_action( 'wp', 'la_include_backorder_functions' );

function la_enqueue_backorder_script() {
    wp_enqueue_script(
        'backorder-script',
        get_stylesheet_directory_uri() . '/js/backorder.js',
        array('jquery'),
        null,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'la_enqueue_backorder_script' );

add_filter('wpwoofeed_product_description', function($description, $product) {
    $title = $product->get_name();
    $attributes = explode(' - ', $description);
    if (count($attributes) === 2) {
        $color = trim($attributes[0]);
        $size = trim($attributes[1]);
        return "$title $color, $size";
    }
    return $description;
}, 10, 2);


function likeangel_enqueue_checkout_guard() {
    if ( is_cart() || is_checkout() || wp_doing_ajax() ) return;
    wp_enqueue_script(
        'likeangel-cart-checkout-guard',
        get_stylesheet_directory_uri() . '/js/cart-checkout-guard.js',
        array(),
        null,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'likeangel_enqueue_checkout_guard' );

// Дозволяємо показувати всі варіації незалежно від "Показати варіант продукту"
add_filter( 'woocommerce_hide_invisible_variations', '__return_false' );

// Фікс: робимо варіацію доступною до покупки навіть якщо вона без запасу, але дозволене резервування
add_filter( 'woocommerce_variation_is_purchasable', function( $purchasable, $variation ) {
    if ( $variation->managing_stock() && $variation->get_stock_quantity() <= 0 && $variation->backorders_allowed() ) {
        return true;
    }
    return $purchasable;
}, 999, 2 );

// Фікс: забезпечуємо правильні дані для варіацій у JSON
add_filter( 'woocommerce_available_variation', function( $variation_data, $product, $variation ) {
    if ( $variation->managing_stock() && $variation->get_stock_quantity() <= 0 && $variation->backorders_allowed() ) {
        $variation_data['availability_html'] = '<p class="stock available-on-backorder wd-style-default">Відправка через 10-14 днів</p>';
        $variation_data['add_to_cart_text'] = 'Передзамовлення';
        $variation_data['variation_add_to_cart_text'] = 'Передзамовлення';
        $variation_data['is_on_backorder'] = true;
        $variation_data['is_purchasable'] = true;
        $variation_data['is_in_stock'] = true;
        $variation_data['is_out_of_stock'] = false;
    }
    return $variation_data;
}, 999, 3 );

add_filter( 'woodmart_product_label_output', 'la_hide_out_of_stock_label_on_single_if_variations_available', 10, 1 );

function la_hide_out_of_stock_label_on_single_if_variations_available( $labels ) {
	if ( ! is_product() ) {
		// Категорії обробляє інший фільтр
		return $labels;
	}

	global $product;

	// Якщо варіативний товар — перевіримо всі варіації
	if ( $product && $product->is_type('variable') ) {
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation && ( $variation->is_in_stock() || $variation->backorders_allowed() ) ) {
				// Хоч одна варіація доступна — ховаємо бейдж
				foreach ( $labels as $index => $label_html ) {
					if ( strpos( $label_html, 'out-of-stock product-label' ) !== false ) {
						unset( $labels[ $index ] );
					}
				}
				break;
			}
		}
	}
	// Для простих товарів: якщо є backorder — також приховуємо
	elseif ( $product && ! $product->is_in_stock() && $product->backorders_allowed() ) {
		foreach ( $labels as $index => $label_html ) {
			if ( strpos( $label_html, 'out-of-stock product-label' ) !== false ) {
				unset( $labels[ $index ] );
			}
		}
	}

	return $labels;
}
