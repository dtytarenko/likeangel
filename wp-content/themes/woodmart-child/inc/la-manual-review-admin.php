<?php
/**
 * Простий інтерфейс для додавання відгуку вручну через адмінку
 * Працює з WooCommerce, додає зірковий рейтинг
 */

add_action('admin_menu', function () {
    add_menu_page(
        'Додати відгук вручну',
        '➕ Додати відгук',
        'manage_woocommerce',
        'la_add_review',
        'la_render_manual_review_form',
        'dashicons-testimonial',
        57
    );
});

function la_render_manual_review_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['la_submit'])) {
        $post_id = intval($_POST['la_product']);
        $author = sanitize_text_field($_POST['la_author']);
        $email = sanitize_email($_POST['la_email']);
        $content = sanitize_textarea_field($_POST['la_content']);
        $rating = max(1, min(5, intval($_POST['la_rating'])));

        $comment_id = wp_insert_comment([
            'comment_post_ID' => $post_id,
            'comment_author' => $author,
            'comment_author_email' => $email,
            'comment_content' => $content,
            'comment_approved' => 1,
            'comment_type' => '',
            'user_id' => 0,
        ]);

        if ($comment_id) {
            add_comment_meta($comment_id, 'rating', $rating);
            echo '<div class="notice notice-success"><p>✅ Відгук додано.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Помилка при додаванні.</p></div>';
        }
    }

    echo '<div class="wrap"><h1>➕ Додати відгук вручну</h1><form method="post"><table class="form-table">';
    echo '<tr><th><label>Товар (ID)</label></th><td><input name="la_product" class="regular-text" required></td></tr>';
    echo '<tr><th><label>Ім’я</label></th><td><input name="la_author" class="regular-text" required></td></tr>';
    echo '<tr><th><label>Email</label></th><td><input name="la_email" class="regular-text" type="email" required></td></tr>';
    echo '<tr><th><label>Рейтинг (1–5)</label></th><td><select name="la_rating">';
    for ($i = 1; $i <= 5; $i++) echo "<option value=\"$i\">$i ★</option>";
    echo '</select></td></tr>';
    echo '<tr><th><label>Текст відгуку</label></th><td><textarea name="la_content" rows="5" class="large-text" required></textarea></td></tr>';
    echo '</table><p><button type="submit" class="button button-primary" name="la_submit">Додати відгук</button></p></form></div>';
}
