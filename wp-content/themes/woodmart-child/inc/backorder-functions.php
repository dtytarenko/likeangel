<?php
/**
 * Функції для підтримки статусу "На замовлення"
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
// 3) Текст кнопки на сторінці товару
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
    if ( $variation->get_backorders() === 'notify' ) {
        $variation_data['availability_html'] = '<p class="stock available-on-backorder wd-style-default">'
            . __( 'Відправка через 10-14 днів', 'woocommerce' ) . '</p>';

        $variation_data['add_to_cart_text']           = __( 'Передзамовлення', 'woocommerce' );
        $variation_data['button_text']                = __( 'Передзамовлення', 'woocommerce' );
        $variation_data['variation_add_to_cart_text'] = __( 'Передзамовлення', 'woocommerce' );

        $variation_data['is_on_backorder'] = true;
    } else {
        $variation_data['is_on_backorder'] = false;
    }

    return $variation_data;
}
