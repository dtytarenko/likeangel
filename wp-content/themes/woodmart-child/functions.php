<?php
/**
 * Enqueue script and styles for child theme
 * І автоматичний include усіх PHP-файлів з папки inc
 */

// 1) Підключаємо всі PHP-скрипти з wp-content/themes/woodmart-child/inc/
foreach ( glob( get_stylesheet_directory() . '/inc/*.php' ) as $inc_file ) {
    require_once $inc_file;
}

// 2) Підключаємо стилі child-теми
function woodmart_child_enqueue_styles(): void {
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'woodmart-style' ),
        woodmart_get_theme_info( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

// 3) Прибираємо зайві суфікси з назв продуктів
function custom_change_product_title( $title, $product ): string {
    $patternFirst = "/One size,|, S-M|, L-XL|, S|, XS|, L|XL,|L,|S-M,|S,|M,|XS,/";
    $patternLast  = "/&#8211; S/";
    $title = preg_replace( $patternFirst, '', $title );
    $title = preg_replace( $patternLast,  '', $title );
    return $title;
}
add_filter( 'the_title', 'custom_change_product_title', 10, 2 );

// 4) Відключаємо XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// 5) DataLayer для сторінки “Дякуємо за замовлення”
add_action( 'wp_footer', function() {
    if ( ! is_order_received_page() ) {
        return;
    }

    global $wp_query;
    $order_id = $wp_query->get( 'order-received' );
    if ( ! $order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $transaction_id = $order->get_transaction_id() ?: $order_id;
    $items = [];
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        $items[] = [
            'name'                    => $item->get_name(),
            'id'                      => $product->get_id(),
            'price'                   => $product->get_price(),
            'brand'                   => get_post_meta( $product->get_id(), '_product_brand', true ) ?: 'Likeangel',
            'category'                => wc_get_product_category_list( $product->get_id(), ', ' ),
            'variant'                 => $product->get_attribute( 'pa_color' ),
            'quantity'                => $item->get_quantity(),
            'google_business_vertical'=> 'retail',
        ];
    }
    $event_model = [
        'value'          => $order->get_total(),
        'transaction_id' => (string) $transaction_id,
        'items'          => $items,
    ];
    ?>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event:      "purchase",
            eventModel: <?php echo wp_json_encode( $event_model ); ?>
        });
    </script>
    <?php
});

// 6) Вимикаємо автозаповнення для телефону на сторінці checkout
add_filter( 'woocommerce_checkout_fields', 'remove_billing_phone_autocomplete' );
function remove_billing_phone_autocomplete( $fields ) {
    $fields['billing']['billing_phone']['autocomplete'] = 'off';
    return $fields;
}

// 7) Підключаємо backorder-скрипт після ініціалізації функцій
add_action( 'wp', 'la_include_backorder_functions' );
function la_include_backorder_functions() {
    // у inc/la_backorder-functions.php вже підключено весь бекенд-код
}
add_action( 'wp_enqueue_scripts', 'la_enqueue_backorder_script' );
function la_enqueue_backorder_script() {
    wp_enqueue_script(
        'backorder-script',
        get_stylesheet_directory_uri() . '/js/backorder.js',
        array( 'jquery' ),
        null,
        true
    );
}

// 8) Виправляємо опис у WOOFeed
add_filter( 'wpwoofeed_product_description', function( $description, $product ) {
    $title      = $product->get_name();
    $attributes = explode( ' - ', $description );
    if ( count( $attributes ) === 2 ) {
        $color = trim( $attributes[0] );
        $size  = trim( $attributes[1] );
        return "$title $color, $size";
    }
    return $description;
}, 10, 2 );

// 9) Guard-скрипт у кошик/чекаут
add_action( 'wp_enqueue_scripts', 'likeangel_enqueue_checkout_guard' );
function likeangel_enqueue_checkout_guard() {
    if ( is_cart() || is_checkout() || wp_doing_ajax() ) {
        return;
    }
    wp_enqueue_script(
        'likeangel-cart-checkout-guard',
        get_stylesheet_directory_uri() . '/js/cart-checkout-guard.js',
        array(),
        null,
        true
    );
}

// 10) Дозволяємо показувати всі варіації незалежно від “Показати варіант продукту”
add_filter( 'woocommerce_hide_invisible_variations', '__return_false' );

// 11) Фікс: робимо варіацію purchasable при backorders
add_filter( 'woocommerce_variation_is_purchasable', function( $purchasable, $variation ) {
    if ( $variation->managing_stock() && $variation->get_stock_quantity() <= 0 && $variation->backorders_allowed() ) {
        return true;
    }
    return $purchasable;
}, 999, 2 );

// 12) Фікс: налаштовуємо availability_json для backorder-варіацій
add_filter( 'woocommerce_available_variation', function( $variation_data, $product, $variation ) {
    if ( $variation->managing_stock() && $variation->get_stock_quantity() <= 0 && $variation->backorders_allowed() ) {
        $variation_data['availability_html']           = '<p class="stock available-on-backorder wd-style-default">Відправка через 10-14 днів</p>';
        $variation_data['add_to_cart_text']            = 'Передзамовлення';
        $variation_data['variation_add_to_cart_text']  = 'Передзамовлення';
        $variation_data['is_on_backorder']             = true;
        $variation_data['is_purchasable']              = true;
        $variation_data['is_in_stock']                 = true;
        $variation_data['is_out_of_stock']             = false;
    }
    return $variation_data;
}, 999, 3 );

// 13) Приховуємо бейдж “out-of-stock” якщо є хоч одна backorder-варіація
add_filter( 'woodmart_product_label_output', 'la_hide_out_of_stock_label_on_single_if_variations_available', 10, 1 );
function la_hide_out_of_stock_label_on_single_if_variations_available( $labels ) {
    if ( ! is_product() ) {
        return $labels;
    }
    global $product;
    if ( $product && $product->is_type( 'variable' ) ) {
        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation && ( $variation->is_in_stock() || $variation->backorders_allowed() ) ) {
                foreach ( $labels as $i => $html ) {
                    if ( strpos( $html, 'out-of-stock product-label' ) !== false ) {
                        unset( $labels[ $i ] );
                    }
                }
                break;
            }
        }
    } elseif ( $product && ! $product->is_in_stock() && $product->backorders_allowed() ) {
        foreach ( $labels as $i => $html ) {
            if ( strpos( $html, 'out-of-stock product-label' ) !== false ) {
                unset( $labels[ $i ] );
            }
        }
    }
    return $labels;
}
add_action('save_post_woodmart_woo_fbt', function($post_id) {
    // 1. Назва тегу = назва комплекту
    $bundle_title = get_the_title($post_id);
    $tag_slug = sanitize_title($bundle_title);

    // 2. Створити тег, якщо не існує
    if (!term_exists($tag_slug, 'product_tag')) {
        wp_insert_term($bundle_title, 'product_tag', ['slug' => $tag_slug]);
    }

    // 3. Витягнути всі ID продуктів, які мають цей тег (по всьому магазину)
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $tag_slug,
            ),
        ),
        'fields' => 'ids'
    );
    $products_with_tag = get_posts($args);

    // 4. Товари, які зараз у комплекті
    $products_raw = get_post_meta($post_id, '_woodmart_fbt_products', true);
    $products = maybe_unserialize($products_raw);
    $ids = [];
    if (is_array($products)) {
        foreach ($products as $item) {
            if (is_array($item) && !empty($item['id'])) {
                $ids[] = intval($item['id']);
            }
        }
    }

    // 5. Видалити тег у всіх, хто його мав, але тепер не в комплекті
    $to_remove = array_diff($products_with_tag, $ids);
    foreach ($to_remove as $prod_id) {
        $current_tags = wp_get_post_terms($prod_id, 'product_tag', array('fields' => 'slugs'));
        $current_tags = array_diff($current_tags, array($tag_slug));
        wp_set_post_terms($prod_id, $current_tags, 'product_tag', false);
    }

    // 6. Додати тег тільки актуальним продуктам комплекту
    foreach ($ids as $product_id) {
        $current_tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'slugs'));
        if (!in_array($tag_slug, $current_tags)) {
            $current_tags[] = $tag_slug;
            wp_set_post_terms($product_id, $current_tags, 'product_tag', false);
        }
    }
}, 20, 1);

add_action( 'save_post_woodmart_woo_fbt', 'la_sync_product_looks', 100 );
function la_sync_product_looks( $post_id ) {
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $products = get_post_meta( $post_id, '_woodmart_fbt_products', true );
    $new_ids  = array();

    if ( is_array( $products ) ) {
        foreach ( $products as $item ) {
            if ( empty( $item['id'] ) ) {
                continue;
            }

            $pid = (int) $item['id'];

            if ( 'product_variation' === get_post_type( $pid ) ) {
                $pid = (int) wp_get_post_parent_id( $pid );
            }

            if ( $pid ) {
                $new_ids[] = $pid;
            }
        }
    }

    $new_ids = array_unique( $new_ids );

    $old_ids = get_posts( array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'woodmart_fbt_bundles_id',
                'value'   => $post_id,
                'compare' => 'LIKE',
            ),
        ),
    ) );

    $to_remove = array_diff( $old_ids, $new_ids );
    foreach ( $to_remove as $product_id ) {
        $bundles = array_map( 'intval', (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true ) );
        $bundles = array_diff( $bundles, array( (int) $post_id ) );
        update_post_meta( $product_id, 'woodmart_fbt_bundles_id', $bundles );
    }

    foreach ( $new_ids as $product_id ) {
        $bundles = array_map( 'intval', (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true ) );
        if ( ! in_array( (int) $post_id, $bundles, true ) ) {
            $bundles[] = (int) $post_id;
            update_post_meta( $product_id, 'woodmart_fbt_bundles_id', $bundles );
        }
    }
}
