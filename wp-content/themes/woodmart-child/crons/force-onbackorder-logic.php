<?php
/**
 * Примусовий скрипт для встановлення onbackorder для товарів з backorders = notify
 * Версія: спрощена, без перевірок складу та manage_stock
 */

 require_once dirname(__FILE__, 5) . '/wp-load.php';

global $wpdb;

$affected = $wpdb->query("
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

file_put_contents(__DIR__ . '/backorder.log', date('Y-m-d H:i:s') . " Updated rows: $affected\n", FILE_APPEND);
