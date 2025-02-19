<?php
require_once('wp-load.php'); // Підключає WordPress

function update_variation_descriptions_meta_with_log() {
    global $wpdb;

    // Отримуємо всі варіації товарів
    $variations = $wpdb->get_results("
        SELECT ID, post_parent 
        FROM wp_posts 
        WHERE post_type = 'product_variation'
    ");

    $updated_count = 0;
    $log = [];

    foreach ($variations as $variation) {
        // Отримуємо короткий опис батьківського товару
        $parent_excerpt = $wpdb->get_var($wpdb->prepare("
            SELECT post_excerpt 
            FROM wp_posts 
            WHERE ID = %d 
            AND post_type = 'product'
        ", $variation->post_parent));

        if (!empty($parent_excerpt)) {
            // Перевіряємо, чи існує _variation_description
            $existing_meta = $wpdb->get_var($wpdb->prepare("
                SELECT meta_id 
                FROM wp_postmeta 
                WHERE post_id = %d AND meta_key = '_variation_description'
            ", $variation->ID));

            if ($existing_meta) {
                // Оновлюємо значення
                $wpdb->update(
                    'wp_postmeta',
                    ['meta_value' => $parent_excerpt],
                    ['post_id' => $variation->ID, 'meta_key' => '_variation_description'],
                    ['%s'],
                    ['%d', '%s']
                );
            } else {
                // Додаємо нове значення
                $wpdb->insert(
                    'wp_postmeta',
                    ['post_id' => $variation->ID, 'meta_key' => '_variation_description', 'meta_value' => $parent_excerpt],
                    ['%d', '%s', '%s']
                );
            }

            // Додаємо запис у лог
            $log[] = "Варіація ID: {$variation->ID} → Батьківський товар ID: {$variation->post_parent} | Новий опис: " . htmlspecialchars($parent_excerpt);
            $updated_count++;
        }
    }

    // Виводимо список змінених варіацій
    echo "<h2>Оновлено $updated_count варіацій:</h2>";
    echo "<ul>";
    foreach ($log as $entry) {
        echo "<li>$entry</li>";
    }
    echo "</ul>";
}

// Виконати оновлення з логами
update_variation_descriptions_meta_with_log();
?>
