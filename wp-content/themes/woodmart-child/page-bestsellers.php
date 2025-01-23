<?php
/*
Template Name: Bestsellers Page
*/
function add_custom_bestsellers_classes( $classes ) {
    if ( is_page( 'bestsellers' ) ) { // Перевіряємо, чи це сторінка "Бестселери"
        $classes[] = 'archive';
        $classes[] = 'tax-product_cat';
        $classes[] = 'woocommerce-page';
        $classes[] = 'woodmart-archive-shop';
    }
    return $classes;
}
add_filter( 'body_class', 'add_custom_bestsellers_classes' );

if ( is_active_sidebar( 'shop-sidebar' ) ) {
    dynamic_sidebar( 'shop-sidebar' );
}

get_header( 'shop' ); // Підключаємо header для магазину

the_content();

get_sidebar(); // Підключаємо бічну панель для фільтрації

get_footer(); // Підключаємо footer
?>
