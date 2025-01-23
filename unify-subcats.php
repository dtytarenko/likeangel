<?php
/**
 * unify-subcats.php
 *
 * 1) Для КОЖНОЇ (із заданого списку) головної категорії шукаємо підкатегорії,
 *    у slug яких зустрічається "novinki" чи "bestseller".
 * 2) "Зливаємо" всі дублікати у єдину підкатегорію "Новинки" (slug=novinki) та "Бестселери" (slug=bestsellery).
 * 3) Видаляємо дублікати з товарами 0, або якщо товари вже перенесені.
 *
 * Обережно з великими базами. Тестуйте на staging.
 */

// Підключаємо WordPress
require_once __DIR__ . '/wp-load.php';

// ВАШ СПИСОК ГОЛОВНИХ slug:
$main_parents_slugs = array(
    // Приклади:
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
    'brjuki',    // Штаниі у вас. 
);

// 1) Отримаємо об'єкти цих головних категорій
$main_parents = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'slug'       => $main_parents_slugs,
    'parent'     => 0,
));

if ( empty($main_parents) || is_wp_error($main_parents) ) {
    echo "Не знайдено жодної з вказаних головних категорій.\n";
    exit;
}

// Проходимося по кожній
foreach ( $main_parents as $parent_cat ) {

    echo "=== Обробляємо головну категорію: {$parent_cat->name} ({$parent_cat->slug}) ===\n";

    // 2) Знайти ВСІ підкатегорії, у slug яких "novinki" або "bestseller"
    $subcats = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_cat->term_id,
    ));

    // Масиви, куди складемо знайдені терміни
    $novinki_terms = array(); 
    $bestsell_terms = array();

    // Визначимо, чи вже існує "основна" підкатегорія novinki/bestsellery
    $main_novinki_id = 0;
    $main_bestsell_id = 0;

    foreach ( $subcats as $sc ) {
        $slug = $sc->slug;
        // Перевіримо, чи містить novinki
        if ( false !== mb_strpos($slug, 'novinki') ) {
            $novinki_terms[] = $sc;
            if ( 'novinki' === $slug ) {
                // Це "правильний" slug, значить воно — наша основна підкатегорія
                $main_novinki_id = $sc->term_id;
            }
        }
        // Перевіримо, чи містить bestseller
        if ( false !== mb_strpos($slug, 'bestseller') ) {
            $bestsell_terms[] = $sc;
            if ( 'bestsellery' === $slug ) {
                $main_bestsell_id = $sc->term_id;
            }
        }
    }

    // 2.1 Якщо не знайшли основну підкатегорію "Новинки", то створимо
    if ( empty($main_novinki_id) ) {
        // Але чи взагалі є хоч одна підкатегорія "novinki"? Якщо ні, можливо, створимо чисто нову
        $res = wp_insert_term(
            'Новинки',
            'product_cat',
            array(
                'slug'   => 'novinki',
                'parent' => $parent_cat->term_id,
            )
        );
        if ( ! is_wp_error($res) ) {
            $main_novinki_id = $res['term_id'];
            echo "Створено основну підкатегорію 'Новинки' під {$parent_cat->name}\n";
        } else {
            echo "Помилка створення 'Новинки' під {$parent_cat->name}: " . $res->get_error_message() . "\n";
        }
    }

    // 2.2 Якщо не знайшли основну підкатегорію "Бестселери", то створимо
    if ( empty($main_bestsell_id) ) {
        $res = wp_insert_term(
            'Бестселери',
            'product_cat',
            array(
                'slug'   => 'bestsellery',
                'parent' => $parent_cat->term_id,
            )
        );
        if ( ! is_wp_error($res) ) {
            $main_bestsell_id = $res['term_id'];
            echo "Створено основну підкатегорію 'Бестселери' під {$parent_cat->name}\n";
        } else {
            echo "Помилка створення 'Бестселери' під {$parent_cat->name}: " . $res->get_error_message() . "\n";
        }
    }

    // 3) Тепер "зливаємо" товари з усіх novinki-... у $main_novinki_id 
    //    (крім самої основної, якщо вона вже є).
    foreach ( $novinki_terms as $nt ) {
        if ( $nt->term_id == $main_novinki_id || $main_novinki_id == 0 ) {
            // Якщо це і є основна, або ми так і не змогли створити основну, пропускаємо
            continue;
        }
        // Перенести товари з $nt->term_id у $main_novinki_id
        unify_term_items($nt->term_id, $main_novinki_id);
    }

    // 4) "Зливаємо" товари з усіх bestsell-... у $main_bestsell_id
    foreach ( $bestsell_terms as $bt ) {
        if ( $bt->term_id == $main_bestsell_id || $main_bestsell_id == 0 ) {
            continue;
        }
        unify_term_items($bt->term_id, $main_bestsell_id);
    }

    // 5) Після перенесення — видаляємо дублікати (у яких 0 товарів)
    //    В теорії unify_term_items уже зняв усі товари з дубліката, тож якщо не лишилося товарів, можемо видалити.
    foreach ( $novinki_terms as $nt ) {
        if ( $nt->term_id != $main_novinki_id ) {
            // Перевіримо, чи ще є товари
            $term_check = get_term( $nt->term_id, 'product_cat' );
            if ( $term_check && ! is_wp_error($term_check) && $term_check->count == 0 ) {
                wp_delete_term( $term_check->term_id, 'product_cat' );
                echo "Видалено дубль novinki: {$term_check->name} (ID:{$term_check->term_id})\n";
            }
        }
    }
    foreach ( $bestsell_terms as $bt ) {
        if ( $bt->term_id != $main_bestsell_id ) {
            $term_check = get_term( $bt->term_id, 'product_cat' );
            if ( $term_check && ! is_wp_error($term_check) && $term_check->count == 0 ) {
                wp_delete_term( $term_check->term_id, 'product_cat' );
                echo "Видалено дубль bestsellery: {$term_check->name} (ID:{$term_check->term_id})\n";
            }
        }
    }

    echo "=== Завершено для {$parent_cat->name} ===\n\n";
}

echo "Усі дублікати об’єднано!\n";

/**
 * Функція, що переносить усі товари з одного терміна в інший (у тій самій таксономії),
 * і потім знімає старий термін із товарів.
 */
function unify_term_items( $source_term_id, $target_term_id ) {
    // Знайдемо всі товари, які мають source_term_id
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $source_term_id,
            ),
        ),
    );
    $posts = get_posts($args);
    if ( empty($posts) ) {
        return; // Нема товарів - нічого робити
    }

    foreach ( $posts as $p ) {
        // Отримуємо всі категорії товару
        $all_terms = wp_get_post_terms( $p->ID, 'product_cat', array('fields'=>'ids') );
        // Видаляємо з масиву $source_term_id
        $all_terms = array_diff( $all_terms, array( $source_term_id ) );
        // Додаємо $target_term_id
        $all_terms[] = $target_term_id;

        // Оновлюємо товар
        wp_set_post_terms( $p->ID, $all_terms, 'product_cat' );
    }
}

