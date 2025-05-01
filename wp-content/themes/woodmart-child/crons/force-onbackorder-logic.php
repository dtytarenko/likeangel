<?php
/**
 * Скрипт для примусового оновлення stock_status = onbackorder
 * для всіх варіацій, у яких backorders = notify та stock <= 0
 * 
 * Рекомендується запускати через crontab + WP-CLI:
 * php wp-cli.phar --path=/path/to/wordpress eval 'require_once "wp-content/force-onbackorder-logic.php";'
 */

 require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

global $wpdb;

$affected = $wpdb->query("
    UPDATE wp_postmeta AS stock_status
    JOIN (
        SELECT s.post_id
        FROM wp_postmeta AS s
        JOIN wp_postmeta AS b ON s.post_id = b.post_id
        JOIN wp_postmeta AS m ON s.post_id = m.post_id
        WHERE s.meta_key = '_stock' AND CAST(s.meta_value AS SIGNED) <= 0
          AND b.meta_key = '_backorders' AND b.meta_value = 'notify'
          AND m.meta_key = '_manage_stock' AND m.meta_value = 'yes'
    ) AS filtered
    ON stock_status.post_id = filtered.post_id
    SET stock_status.meta_value = 'onbackorder'
    WHERE stock_status.meta_key = '_stock_status';
");

// Логування (можна вимкнути)
file_put_contents(__DIR__ . '/backorder.log', date('Y-m-d H:i:s') . " Updated rows: $affected\n", FILE_APPEND);
