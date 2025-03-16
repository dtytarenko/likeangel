<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Bezpeka
}

/**
 * Сортування товарів за "реальним" станом складу:
 * - Якщо _stock > 0 => in stock (код 0)
 * - Якщо _stock <= 0 і _backorders ∈ {yes,notify} => onbackorder (код 1)
 * - Інакше => outofstock (код 2)
 *
 * Потім сортуємо ASC (0 -> 1 -> 2), аби "в наявності" були першими, "передзамовлення" другими,
 * а "відсутні" - останніми.
 */

/**
 *  Хук "posts_clauses" переписує ORDER BY:
 *   1. Приєднуємо метадані: _stock, _backorders
 *   2. Використовуємо CASE, щоб визначити групу (0,1,2)
 *   3. Додаємо це на початок сортування
 */
function la_sort_by_actual_stock_and_backorders( $clauses, $query ) {
	global $wpdb;

	// Переконуємось, що це не адмінка і це WooCommerce-запит товарів
	if ( is_admin() ) {
		return $clauses;
	}
	if ( 'product_query' !== $query->get( 'wc_query' ) ) {
		return $clauses;
	}

	// Приєднуємо поле _stock (запас)
	$clauses['join'] .= "
		LEFT JOIN {$wpdb->postmeta} AS pm_stock
			ON pm_stock.post_id = {$wpdb->posts}.ID
			AND pm_stock.meta_key = '_stock'
	";

	// Приєднуємо поле _backorders
	$clauses['join'] .= "
		LEFT JOIN {$wpdb->postmeta} AS pm_backorders
			ON pm_backorders.post_id = {$wpdb->posts}.ID
			AND pm_backorders.meta_key = '_backorders'
	";

	// CASE:
	//   WHEN stock>0 => 0 ("in stock")
	//   WHEN stock<=0 AND backorders in ('yes','notify') => 1 ("on backorder")
	//   ELSE => 2 ("out of stock")
	$case_expression = "
		CASE
			WHEN ( pm_stock.meta_value + 0 ) > 0 THEN 0
			WHEN ( pm_stock.meta_value + 0 ) <= 0
			     AND pm_backorders.meta_value IN ( 'yes', 'notify' )
			THEN 1
			ELSE 2
		END
	";

	// Додаємо цей CASE на початок ORDER BY, щоб ASC: 0 -> 1 -> 2
	$clauses['orderby'] = $case_expression . " ASC, " . $clauses['orderby'];

	return $clauses;
}

// Підключаємо з високим пріоритетом, щоб перебити інші сортування
add_filter( 'posts_clauses', 'la_sort_by_actual_stock_and_backorders', 9999, 2 );
