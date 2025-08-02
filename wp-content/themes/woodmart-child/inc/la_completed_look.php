<?php
/**
 * Додає в Elementor Products widget новий Query Type “complete_look”
 * і підставляє туди товари з ACF Relationship поля `complete_look`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Додаємо пункт у селект джерела даних
add_filter( 'elementor/woocommerce/product_query_type', function( $types ) {
    $types['complete_look'] = __( 'Образ', 'likeangel' );
    return $types;
} );

// Обробляємо запит по цьому типу
add_action( 'elementor/query/complete_look', function( $query ) {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    // Отримуємо ACF-поле (Relationship) для поточного товару
    $products = get_field( 'complete_look', $post_id );
    if ( empty( $products ) ) {
        return;
    }

    // Якщо ACF повертає об’єкти, перетворюємо на ID
    if ( is_array( $products ) && is_object( $products[0] ) ) {
        $ids = wp_list_pluck( $products, 'ID' );
    } else {
        $ids = (array) $products;
    }

    // Фільтруємо WP_Query
    $query->set( 'post__in',  $ids );
    $query->set( 'orderby',   'post__in' );
} );
