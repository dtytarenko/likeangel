require_once dirname(__FILE__, 5) . '/wp-load.php';
global $wpdb;

// Оновлюємо stock_status
$affected_stock = $wpdb->query("
    UPDATE wp_postmeta AS pm1
    JOIN (
        SELECT post_id
        FROM wp_postmeta
        WHERE meta_key = '_backorders' AND meta_value = 'notify'
    ) AS filtered
    ON pm1.post_id = filtered.post_id
    SET pm1.meta_value = 'onbackorder'
    WHERE pm1.meta_key = '_stock_status';
");

// Отримати ID варіацій, які мають backorders = notify
$variation_ids = $wpdb->get_col("
    SELECT post_id
    FROM wp_postmeta
    WHERE meta_key = '_backorders' AND meta_value = 'notify'
");

$affected_visibility = 0;

// Проходимо циклом, оновлюємо modified_date + чистимо кеші
foreach ($variation_ids as $variation_id) {
    wp_update_post([
        'ID' => $variation_id,
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', 1)
    ]);

    wc_delete_product_transients($variation_id);
    clean_post_cache($variation_id);
    ++$affected_visibility;
}

// Запис у лог
file_put_contents(
    __DIR__ . '/backorder.log',
    date('Y-m-d H:i:s') . " Stock: $affected_stock | Touched visibility: $affected_visibility\n",
    FILE_APPEND
);
