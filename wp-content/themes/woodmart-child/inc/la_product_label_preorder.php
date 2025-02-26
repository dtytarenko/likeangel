<?php
/**
 * LikeAngel: Приховує бейдж "Немає в наявності" на архіві категорій
 * якщо у товару увімкнено "дозволяти резервування".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Захист від прямого доступу
}

add_filter( 'woodmart_product_label_output', 'la_remove_out_of_stock_label_for_backorders', 10, 1 );
/**
 * Видаляє з масиву лейблів (labels) елемент "out-of-stock product-label",
 * якщо для товару (чи варіації) дозволені backorders.
 */
function la_remove_out_of_stock_label_for_backorders( $labels ) {
	global $product;

	// Якщо товар дійсно out-of-stock, але дозволений backorder
	if ( $product && ! $product->is_in_stock() && $product->backorders_allowed() ) {
		foreach ( $labels as $index => $label_html ) {
			// Шукаємо в HTML клас "out-of-stock product-label"
			if ( false !== strpos( $label_html, 'out-of-stock product-label' ) ) {
				unset( $labels[ $index ] );
			}
		}
	}

	return $labels;
}
