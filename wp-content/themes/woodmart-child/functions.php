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

/**
 * Функція для підтримки статусу "На замовлення"
 * Оновлює текст доступності та кнопки для товарів зі stock_status = onbackorder.
 */

// -------------------------
// 1) Текст доступності (Availability)

add_filter( 'woocommerce_get_availability_text', 'la_backorder_availability_text', 999, 2 );
function la_backorder_availability_text( $availability, $product ) {
    if ( $product->get_stock_status() === 'onbackorder' ) {
        $availability = __( 'Відправка через 10-14 днів', 'woocommerce' );
    }
    return $availability;
}

// -------------------------
// 2) Текст кнопки у загальних випадках (наприклад, архіви товарів)

add_filter( 'woocommerce_product_add_to_cart_text', 'la_backorder_add_to_cart_text', 999, 2 );
function la_backorder_add_to_cart_text( $text, $product ) {
    if ( $product->get_stock_status() === 'onbackorder' ) {
        $text = __( 'Передзамовлення', 'woocommerce' );
    }
    return $text;
}

// -------------------------
// 3) Текст кнопки на сторінці товару (Single Product Page)

add_filter( 'woocommerce_product_single_add_to_cart_text', 'la_backorder_single_add_to_cart_text', 999, 2 );
function la_backorder_single_add_to_cart_text( $text, $product ) {
    if ( $product->get_stock_status() === 'onbackorder' ) {
        $text = __( 'Передзамовлення', 'woocommerce' );
    }
    return $text;
}

// -------------------------
// 4) Додаємо статус backorder у дані варіації

add_filter( 'woocommerce_available_variation', 'la_backorder_variation_data', 999, 3 );
function la_backorder_variation_data( $variation_data, $product, $variation ) {
    if ( $variation->get_stock_status() === 'onbackorder' ) {
        // Availability
        $variation_data['availability_html'] = '<p class="stock available-on-backorder wd-style-default">'
            . __( 'Відправка через 10-14 днів', 'woocommerce' ) . '</p>';

        // Кнопка
        $variation_data['add_to_cart_text']           = __( 'Передзамовлення', 'woocommerce' );
        $variation_data['button_text']                = __( 'Передзамовлення', 'woocommerce' );
        $variation_data['variation_add_to_cart_text'] = __( 'Передзамовлення', 'woocommerce' );

        // Додаємо спеціальний прапорець для JS
        $variation_data['is_on_backorder'] = true;
    } else {
        $variation_data['is_on_backorder'] = false;
    }

    return $variation_data;
}

// -------------------------
// 5) Додаємо JS у footer для оновлення кнопки і доступності на сторінці товару

add_action( 'wp_footer', 'la_backorder_js_override', 999 );
function la_backorder_js_override() {
    // Підключимо скрипт ТІЛЬКИ на сторінках товарів (is_product())
    if ( ! is_product() ) return;
    ?>
    <script>
    jQuery(function($) {
        $(document).on('show_variation', '.variations_form', function(event, variation) {
            // Логіка для визначення статусу "На замовлення"
            var isOnBackorder = variation.is_on_backorder || false;

            // Якщо варіація "На замовлення"
            if (isOnBackorder) {
                // Оновлюємо текст кнопки
                $('.single_add_to_cart_button').text('Передзамовлення');

                // Оновлюємо текст доступності
                $('.single_variation_wrap .stock').html(variation.availability_html);
            } else {
                // Якщо обрана варіація не є "На замовлення"
                $('.single_add_to_cart_button').text('Додати в кошик');
                $('.single_variation_wrap .stock').text('Є в наявності');
            }
        });
    });
    </script>
    <?php
}

// Прибираємо автозапонення номеру на сторінці чекаут
add_filter( 'woocommerce_checkout_fields', 'remove_billing_phone_autocomplete' );
function remove_billing_phone_autocomplete( $fields ) {
    $fields['billing']['billing_phone']['autocomplete'] = 'off';

    return $fields;
}

