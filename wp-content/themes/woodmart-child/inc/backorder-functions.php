<?php

// 1) Підміняємо дані про наявність у varіаціях
add_filter( 'woocommerce_available_variation', function( $variation_data, $variable_product, $variation ) {
    $stock_qty   = (int) $variation->get_stock_quantity();
    $backorders  = $variation->get_backorders(); // 'no', 'notify', 'yes'
    
    // Прапори (JS їх використовує для визначення стану)
    $variation_data['is_on_backorder'] = false;
    $variation_data['is_out_of_stock'] = false;

    /**
     * Тут зберігаємо "справжні" (кастомні) тексти у власному полі.
     * Початково вважаємо, що товар у наявності:
     */
    $variation_data['my_custom_button_text']       = __( 'Додати в кошик', 'woocommerce' );
    $variation_data['my_custom_availability_html'] = '<p class="stock in-stock">' . __( 'Є в наявності', 'woocommerce' ) . '</p>';

    /**
     * 1) Якщо backorders = 'yes' | 'notify' і запас (stock) <= 0 => "Передзамовлення"
     */
    if ( in_array( $backorders, ['yes','notify'], true ) && $stock_qty <= 0 ) {
        $variation_data['my_custom_button_text']       = __( 'Передзамовлення', 'woocommerce' );
        $variation_data['my_custom_availability_html'] = '<p class="stock available-on-backorder">'
            . __( 'Відправка через 10-14 днів', 'woocommerce' ) . '</p>';
        $variation_data['is_on_backorder']             = true;
    }
    /**
     * 2) Якщо backorders = 'no' і stock <= 0 => "Відсутній у продажу"
     */
    elseif ( $backorders === 'no' && $stock_qty <= 0 ) {
        $variation_data['my_custom_button_text']       = __( 'Товар тимчасово відсутній', 'woocommerce' );
        $variation_data['my_custom_availability_html'] = '<p class="stock out-of-stock">'
            . __( 'Товар тимчасово відсутній', 'woocommerce' ) . '</p>';
        $variation_data['is_out_of_stock']             = true;
    }

    /**
     * "Гасимо" стандартне availability_html і button_text,
     * щоб WooCommerce НЕ показував нічого до вибору варіації
     * (і не було "миготіння").
     */
    $variation_data['availability_html'] = '';
    $variation_data['button_text']       = '';

    return $variation_data;
}, 999, 3 );