<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateCacheTable_20240923214011 extends Migration
{
    public function name(): string
    {
        return 'create_cache_table_20240923214011';
    }

    public function up(\wpdb $db): void
    {
        $collate = $db->get_charset_collate();

        $db->query("
            CREATE TABLE IF NOT EXISTS {$db->prefix}wc_ukr_shipping_cache (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `key` VARCHAR(128) NOT NULL,
                `value` LONGTEXT DEFAULT NULL,
                `ttl` INT(10) UNSIGNED DEFAULT NULL,
                `created_at` decimal(16,6) NOT NULL,
                `updated_at` decimal(16,6) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `u_key` (`key`)
          ) $collate
        ");
    }
}
