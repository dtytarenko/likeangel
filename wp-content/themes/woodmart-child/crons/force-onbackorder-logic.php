<?php
require_once dirname(__FILE__, 5) . '/wp-load.php';
global $wpdb;

// Оновлюємо _stock_status лише для тих, хто має backorders = notify, але ще не onbackorder
$affected_stock = $wpdb->query("
    UPDATE {$wpdb->prefix}postmeta AS pm_stock
    JOIN (
        SELECT pm1.post_id
        FROM {$wpdb->prefix}postmeta AS pm1
        JOIN {$wpdb->prefix}postmeta AS pm2 ON pm1.post_id = pm2.post_id
        WHERE pm1.meta_key = '_backorders' AND pm1.meta_value = 'notify'
          AND pm2.meta_key = '_stock_status' AND pm2.meta_value != 'onbackorder'
    ) AS filtered
    ON pm_stock.post_id = filtered.post_id
    SET pm_stock.meta_value = 'onbackorder'
    WHERE pm_stock.meta_key = '_stock_status';
");

// Отримуємо ID змінених варіацій — для очищення кешу (необов'язково, але бажано)
$variation_ids = $wpdb->get_col("
    SELECT DISTINCT pm.post_id
    FROM {$wpdb->prefix}postmeta AS pm
    WHERE pm.meta_key = '_backorders' AND pm.meta_value = 'notify'
");

// Чистимо кеш
$affected_visibility = 0;
foreach ($variation_ids as $variation_id) {
    wc_delete_product_transients($variation_id);
    clean_post_cache($variation_id);
    ++$affected_visibility;
}

// Логування
file_put_contents(
    __DIR__ . '/backorder.log',
    date('Y-m-d H:i:s') . " Stock updated: $affected_stock | Cache cleared: $affected_visibility\n",
    FILE_APPEND
);
