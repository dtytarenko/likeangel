<?php
/**
 * create-subcats.php
 * Тимчасовий файл для запуску коду з WP-CLI.
 * Запускається через: wp eval-file create-subcats.php
 */

// Завантажимо середовище WP:
require_once( 'wp-load.php' );

// Тепер пишемо основну функцію (без add_action):
function likeangel_create_subcategories() {
    // Якщо потрібно — можемо зразу вимкнути перевірку get_option,
    // щоб при кожному запуску цей код виконувався.
    // або додати її (якщо треба лише 1 раз):
    if ( get_option( 'likeangel_subcats_created_via_cli' ) ) {
        WP_CLI::log( 'Підкатегорії вже були створені. Виходимо.' );
        return;
    }

    // Отримуємо всі “головні” (parent=0) категорії товарів, крім slug “novinki” та “bestsellery”
    $excluded_slugs = array( 'novinki', 'bestsellery' );
    $main_cats = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => 0,
    ) );

    if ( ! empty( $main_cats ) && ! is_wp_error( $main_cats ) ) {
        foreach ( $main_cats as $cat ) {
            // Пропускаємо, якщо slug у списку виключених
            if ( in_array( $cat->slug, $excluded_slugs, true ) ) {
                WP_CLI::log( "Пропускаємо категорію: {$cat->name}" );
                continue;
            }

            // Спробуємо створити підкатегорію "Новинки"
            $res_nov = wp_insert_term(
                'Новинки',
                'product_cat',
                array(
                    'slug'   => 'novinki',
                    'parent' => $cat->term_id,
                )
            );
            if ( is_wp_error( $res_nov ) ) {
                WP_CLI::warning( "Не вдалося створити 'Новинки' під {$cat->name}: " . $res_nov->get_error_message() );
            } else {
                WP_CLI::success( "Створено 'Новинки' для {$cat->name}" );
            }

            // Спробуємо створити підкатегорію "Бестселери"
            $res_bes = wp_insert_term(
                'Бестселери',
                'product_cat',
                array(
                    'slug'   => 'bestsellery',
                    'parent' => $cat->term_id,
                )
            );
            if ( is_wp_error( $res_bes ) ) {
                WP_CLI::warning( "Не вдалося створити 'Бестселери' під {$cat->name}: " . $res_bes->get_error_message() );
            } else {
                WP_CLI::success( "Створено 'Бестселери' для {$cat->name}" );
            }
        }
    }

    // Запишемо опцію, що ми створили підкатегорії (якщо потрібно).
    update_option( 'likeangel_subcats_created_via_cli', 1 );
    WP_CLI::success( 'Підкатегорії створено!' );
}

// Викликаємо функцію:
likeangel_create_subcategories();

