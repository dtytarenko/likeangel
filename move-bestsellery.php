<?php
/**
 * move-top-level-novinki-bestsellery.php
 * 
 * Кроки:
 * 1) Знаходимо старі top-level категорії: novinki, bestsellery.
 * 2) Витягаємо усі товари, що мають ці категорії.
 * 3) Для кожного товару:
 *    - визначаємо решту батьківських категорій
 *    - додаємо потрібну підкатегорію "Новинки"/"Бестселери"
 *    - знімаємо стару top-level "Новинки"/"Бестселери"
 * 4) (Після виконання) можемо викликати wp_delete_term(...) для видалення top-level novinki/bestsellery
 */

require_once 'wp-load.php'; // шлях підлаштуйте

$old_slugs = array( 'novinki', 'bestsellery' );

// Отримаємо їхні об’єкти
$old_terms = get_terms( array(
    'taxonomy'   => 'product_cat',
    'slug'       => $old_slugs,
    'hide_empty' => false,
    'parent'     => 0, // саме top-level
) );

if ( empty( $old_terms ) || is_wp_error( $old_terms ) ) {
    echo "Top-level Новинки/Бестселери не знайдено.\n";
    exit;
}

// Зберігаємо ID у масив
$old_term_ids = wp_list_pluck( $old_terms, 'term_id' );

// Для зручності визначимо, який ID відповідає 'novinki', який 'bestsellery'
$old_novinki_id = 0;
$old_bestsell_id = 0;
foreach ( $old_terms as $t ) {
    if ( 'novinki' === $t->slug ) {
        $old_novinki_id = $t->term_id;
    } elseif ( 'bestsellery' === $t->slug ) {
        $old_bestsell_id = $t->term_id;
    }
}

// Знайдемо всі товари, які мають одну з цих категорій
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $old_term_ids,
        ),
    ),
);
$products = get_posts( $args );

if ( empty( $products ) ) {
    echo "Не знайдено товарів, що відносяться до top-level Новинки/Бестселери.\n";
} else {
    foreach ( $products as $product ) {
        $terms = wp_get_post_terms( $product->ID, 'product_cat', array('fields'=>'all') );
        
        $updated_term_ids = array();
        $has_novinki    = false;
        $has_bestseller = false;
        $parent_cat_ids = array();

        // 1) Перебираємо всі категорії товару
        foreach ( $terms as $tm ) {
            // Якщо це top-level "Новинки"
            if ( $tm->term_id == $old_novinki_id ) {
                $has_novinki = true;
                continue; // не додаємо
            }
            // Якщо це top-level "Бестселери"
            if ( $tm->term_id == $old_bestsell_id ) {
                $has_bestseller = true;
                continue;
            }
            // Якщо не старий top-level, зберігаємо в оновлений список
            $updated_term_ids[] = $tm->term_id;

            // Якщо цей термін є top-level (parent=0), збережемо його ID
            if ( $tm->parent == 0 ) {
                $parent_cat_ids[] = $tm->term_id;
            }
        }

        // 2) Якщо товар мав "Новинки" чи "Бестселери", додамо для кожного батька
        if ( $has_novinki || $has_bestseller ) {
            // Якщо товар не мав жодної іншої bатьківської категорії —
            // треба призначити якийсь fallback (інакше товар залишиться без "основної" категорії).
            if ( empty( $parent_cat_ids ) ) {
                // Наприклад, візьмемо ID якоїсь "Основної" категорії за замовчуванням (самі оберіть)
                $fallback_slug = 'svetry'; // замініть на потрібне
                $fallback_term = get_term_by( 'slug', $fallback_slug, 'product_cat' );
                if ( $fallback_term && ! is_wp_error( $fallback_term ) ) {
                    $parent_cat_ids[] = $fallback_term->term_id;
                    $updated_term_ids[] = $fallback_term->term_id;
                }
            }

            foreach ( $parent_cat_ids as $pc_id ) {
                // Знайти/створити підкатегорію
                $child_slug = $has_novinki ? 'novinki' : 'bestsellery'; 
                $child_name = $has_novinki ? 'Новинки' : 'Бестселери';

                // get_terms ( slug => $child_slug, parent => $pc_id )
                $child_sub = get_terms( array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => $pc_id,
                    'slug'       => $child_slug
                ) );
                
                // Якщо не знайшли — створюємо
                if ( empty($child_sub) || is_wp_error($child_sub) ) {
                    $res = wp_insert_term(
                        $child_name,
                        'product_cat',
                        array(
                            'slug'   => $child_slug, 
                            'parent' => $pc_id,
                        )
                    );
                    if ( ! is_wp_error($res) ) {
                        $child_id = $res['term_id'];
                    } else {
                        // Якщо помилка (дублікат slug, тощо) — можливо, треба інша логіка
                        continue;
                    }
                } else {
                    // беремо перший знайдений
                    $child_id = $child_sub[0]->term_id;
                }

                $updated_term_ids[] = $child_id;
            }
        }

        // 3) Присвоюємо товару новий набір категорій
        wp_set_post_terms( $product->ID, array_unique($updated_term_ids), 'product_cat' );
    }
    echo "Товари оновлено!\n";
}

// 4) (Опційно) видаляємо old top-level "Новинки" і "Бестселери"
if ( $old_novinki_id ) {
    wp_delete_term( $old_novinki_id, 'product_cat' );
}
if ( $old_bestsell_id ) {
    wp_delete_term( $old_bestsell_id, 'product_cat' );
}
echo "Старі категорії Новинки/Бестселери видалені (якщо були).\n";
