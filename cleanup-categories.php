<?php
/**
 * cleanup-categories.php
 *
 * 1) Залишаємо 13 потрібних батьківських категорій, решту top-level видаляємо.
 * 2) У кожній із 13 створюємо (якщо нема) підкатегорії "Новинки" (slug=novinki) і "Бестселери" (slug=bestsellery).
 * 3) Переносимо всі товари, що мають будь-які дублікати/зайві "Новинки"/"Бестселери" (включно з top-level),
 *    у правильну підкатегорію під їхні основні категорії. Прибираємо зайві категорії у товарів.
 * 4) Видаляємо непотрібні дублікати "Новинки"/"Бестселери", якщо вони порожні.
 */

// 0) Підключимо WordPress
require_once __DIR__ . '/wp-load.php';

// -----------------------------
// 1) Визначаємо потрібні 13 батьківських категорій
//    Нижче приклад, де ви вписуєте slug'и чи ID тих категорій, які мають лишатися топ-рівнем.
$keep_top_level_slugs = array(
    'aksessuary', // Аксесуари - змініть на реальний slug!
    'bluza',     // Блуза
    'organza',   // Блузи з Органзи
    'golfy',     // Гольфи
    'dzhempery', // Джемпери
    'jacket',   // Жакети
    'kardigany', // Кардигани
    'svitera',    // Светри
    'rubashki',  // Сорочки (або rubashki, див. ваш slug)
    'jubki',     // Спідниці
    'platja',    // Сукні
    'topi',      // Топи
    'brjuki',    // Штани
    // Якщо у вас інші слуги, підставте їх сюди
);

// -----------------------------
// 2) Видаляємо всі інші top-level категорії, крім цих 13
$top_categories = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => 0,
));

if ( ! empty($top_categories) && ! is_wp_error($top_categories) ) {
    foreach ( $top_categories as $cat ) {
        if ( ! in_array($cat->slug, $keep_top_level_slugs, true ) ) {
            // Видаляємо цю категорію
            // Але перед видаленням – товари не зникнуть, WP просто перепризначить їм іншу категорію (якщо є).
            $res = wp_delete_term( $cat->term_id, 'product_cat' );
            if ( is_wp_error($res) ) {
                echo "Не вдалося видалити top-level {$cat->name}: " . $res->get_error_message() . "\n";
            } else {
                echo "Видалено top-level: {$cat->name}\n";
            }
        }
    }
}

// -----------------------------
// 3) У кожній із 13 потрібних top-level створимо/перевіримо підкатегорії "Новинки" (slug=novinki) і "Бестселери" (slug=bestsellery).
$main_parents = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => 0,
    'slug'       => $keep_top_level_slugs,
));

foreach ( $main_parents as $p ) {
    // Спробуємо знайти/створити "Новинки"
    $child = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $p->term_id,
        'slug'       => 'novinki'
    ));
    if ( empty($child) ) {
        $res = wp_insert_term(
            'Новинки',
            'product_cat',
            array(
                'slug'   => 'novinki',
                'parent' => $p->term_id,
            )
        );
        if ( is_wp_error($res) ) {
            echo "Помилка створення 'Новинки' під {$p->name}: " . $res->get_error_message() . "\n";
        } else {
            echo "Створено 'Новинки' під {$p->name}\n";
        }
    }

    // Спробуємо знайти/створити "Бестселери"
    $child = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $p->term_id,
        'slug'       => 'bestsellery'
    ));
    if ( empty($child) ) {
        $res = wp_insert_term(
            'Бестселери',
            'product_cat',
            array(
                'slug'   => 'bestsellery',
                'parent' => $p->term_id,
            )
        );
        if ( is_wp_error($res) ) {
            echo "Помилка створення 'Бестселери' під {$p->name}: " . $res->get_error_message() . "\n";
        } else {
            echo "Створено 'Бестселери' під {$p->name}\n";
        }
    }
}

// -----------------------------
// 4) Тепер переносимо товари з усіх дублікованих / зайвих "Новинки"/"Бестселери" у правильні
//    (а саме 'novinki' і 'bestsellery' під тими 13 головними).

// Знайдемо УСІ категорії, які містять слово 'novinki' чи 'bestseller' у slug (це і дублікати, і оригінальні)
$all_nov_bests = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'search'     => '', // опціонально
));

$problematic_ids = array();
if ( ! empty($all_nov_bests) && ! is_wp_error($all_nov_bests) ) {
    foreach ( $all_nov_bests as $term ) {
        $slug = $term->slug;
        // Перевіримо, чи slug містить "novinki" або "bestseller"
        if (
            false !== mb_strpos($slug, 'novinki') 
            || false !== mb_strpos($slug, 'bestseller')
        ) {
            // Додаємо до списку "проблемних" (в т.ч. може бути "novinki", "bestsellery", "novinki-2", "bestsellery-novinki-123" і т.д.)
            $problematic_ids[] = $term->term_id;
        }
    }
}

// Якщо нема таких категорій - усе вже чисто.
if ( empty($problematic_ids) ) {
    echo "Немає категорій з 'novinki' чи 'bestseller' у slug. Можливо, все вже чисто.\n";
    exit;
}

// Беремо усі товари, що мають бодай один із цих "problematic_ids"
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $problematic_ids,
        ),
    ),
);
$products = get_posts( $args );

echo "Знайдено товарів із категоріями 'Новинки'/'Бестселери' (у різних варіантах): " . count($products) . "\n";

foreach ( $products as $product ) {
    $term_objects = wp_get_post_terms( $product->ID, 'product_cat', array('fields'=>'all') );
    $new_cat_ids  = array(); // Сюди зберемо категорії, які залишимо

    // Дізнаємося, чи товар узагалі в одній із 13 головних
    // (якщо ні, призначимо йому якусь "фолбек" – наприклад "svetry")
    $parent_top_ids = array(); 
    foreach ( $term_objects as $tobj ) {
        // Перевіримо, чи це одна з 13 топових
        if ( in_array($tobj->slug, $keep_top_level_slugs, true) && $tobj->parent == 0 ) {
            $parent_top_ids[] = $tobj->term_id;
        }
    }

    // 4.1 Якщо товар НЕ має жодної з 13 батьківських категорій,
    //     можна призначити "svetry" (чи іншу довільну) як fallback
    if ( empty($parent_top_ids) ) {
        $fallback_slug = 'svetry'; // або іншу, яку хочете
        $fallback_term = get_term_by('slug', $fallback_slug, 'product_cat');
        if ( $fallback_term && ! is_wp_error($fallback_term) ) {
            $parent_top_ids[] = $fallback_term->term_id;
        }
    }

    // 4.2 Зберемо в $new_cat_ids усі категорії товару, які:
    //     - НЕ є "зайвими" новинками/бестселерами
    //     - НЕ видалені top-level
    foreach ( $term_objects as $tobj ) {
        // Якщо його ID у списку $problematic_ids (тобто це "зайві" чи будь-які novinki/bestsellery), пропустимо
        // (перепризначимо нижче).
        if ( in_array($tobj->term_id, $problematic_ids, true) ) {
            continue;
        }
        // Інакше залишаємо
        $new_cat_ids[] = $tobj->term_id;
    }

    // 4.3 Для кожної батьківської (із $parent_top_ids) – додамо дві підкатегорії, які товар мав раніше
    //     Але нам треба зрозуміти, чи товар мав "Новинки", "Бестселери", чи обидва.

    // Перевіримо, чи серед "problematic_ids" для цього товару є ті, в slug котрих "novinki",
    // і чи є ті, в slug котрих "bestseller".
    $had_novinki    = false;
    $had_bestseller = false;

    foreach ( $term_objects as $tobj ) {
        if ( in_array($tobj->term_id, $problematic_ids, true) ) {
            if ( false !== mb_strpos($tobj->slug, 'novinki') ) {
                $had_novinki = true;
            }
            if ( false !== mb_strpos($tobj->slug, 'bestseller') ) {
                $had_bestseller = true;
            }
        }
    }

    // Якщо товар мав novinki, додамо йому підкатегорію "Новинки" під кожним батьком.
    if ( $had_novinki ) {
        foreach ( $parent_top_ids as $ptid ) {
            $sub_nov = get_terms(array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'slug'       => 'novinki',
                'parent'     => $ptid,
            ));
            if ( ! empty($sub_nov) && ! is_wp_error($sub_nov) ) {
                // Беремо перший знайдений
                $new_cat_ids[] = $sub_nov[0]->term_id;
            } else {
                // Якщо не знайдено (що дивно), можна створити.
                $res = wp_insert_term(
                    'Новинки',
                    'product_cat',
                    array(
                        'slug'   => 'novinki',
                        'parent' => $ptid,
                    )
                );
                if ( ! is_wp_error($res) ) {
                    $new_cat_ids[] = $res['term_id'];
                }
            }
        }
    }

    // Аналогічно для "Бестселери"
    if ( $had_bestseller ) {
        foreach ( $parent_top_ids as $ptid ) {
            $sub_best = get_terms(array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'slug'       => 'bestsellery',
                'parent'     => $ptid,
            ));
            if ( ! empty($sub_best) && ! is_wp_error($sub_best) ) {
                $new_cat_ids[] = $sub_best[0]->term_id;
            } else {
                // створити, якщо не існує
                $res = wp_insert_term(
                    'Бестселери',
                    'product_cat',
                    array(
                        'slug'   => 'bestsellery',
                        'parent' => $ptid,
                    )
                );
                if ( ! is_wp_error($res) ) {
                    $new_cat_ids[] = $res['term_id'];
                }
            }
        }
    }

    // 4.4 Тепер оновимо товар
    $new_cat_ids = array_unique($new_cat_ids);
    wp_set_post_terms( $product->ID, $new_cat_ids, 'product_cat' );
    // echo "Оновлено товар #{$product->ID}\n"; // для діагностики
}

echo "Товари, що мали 'Новинки'/'Бестселери', переосмислено.\n";

// -----------------------------
// 5) (За потреби) видаляємо порожні категорії-дублікати
//    Ті, що мають slug із 'novinki' чи 'bestseller', але не належать до батьків із $keep_top_level_slugs
//    і не мають товарів.
//    Якщо бажаєте залишити ручний контроль - можна пропустити цей блок
$all_nov_bests = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
));

foreach ( $all_nov_bests as $term ) {
    $slug = $term->slug;
    // Чи це не "наш" основний slug (novinki/bestsellery) ПІД потрібним батьком?
    $parent_data = get_term( $term->parent, 'product_cat' );
    if ( false !== mb_strpos($slug, 'novinki') || false !== mb_strpos($slug, 'bestseller') ) {
        // Перевіримо, чи має товари
        $count = $term->count; // скільки товарів
        // Перевіримо, чи його батько - один з тих 13
        $is_proper_child = ( $parent_data && in_array($parent_data->slug, $keep_top_level_slugs, true) );

        if ( $count == 0 || ! $is_proper_child ) {
            // Видаляємо термін
            $res = wp_delete_term( $term->term_id, 'product_cat' );
            if ( is_wp_error($res) ) {
                echo "Не вдалося видалити дубль {$term->name} (ID:{$term->term_id}): " . $res->get_error_message() . "\n";
            } else {
                echo "Видалено дубль: {$term->name} (ID:{$term->term_id})\n";
            }
        }
    }
}

echo "Готово! Перевірте структуру в адмінці.\n";
