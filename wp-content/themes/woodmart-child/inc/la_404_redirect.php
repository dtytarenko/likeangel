<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Безпека
}

/**
 * Мінімалістична логіка редиректу 404 -> Головна
 */
function woodmart_child_redirect_404_to_home() {
    // Перевіряємо, чи 404
    if ( is_404() ) {
        // Використовуємо 301 Moved Permanently (підходить для пошуковиків).
        // Замість home_url() можна вказати інший URL, якщо потрібно.
        wp_safe_redirect( home_url(), 301 );
        exit;
    }
}

// Вмикаємо редирект під час завантаження шаблону (до виводу 404 сторінки).
add_action( 'template_redirect', 'woodmart_child_redirect_404_to_home' );
