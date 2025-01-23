<?php

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateTtnTable_20240923213940 extends Migration
{
    public function name(): string
    {
        return 'create_ttn_table_20240923213940';
    }

    public function up(\wpdb $db): void
    {
        $collate = $db->get_charset_collate();

        $db->query("
            CREATE TABLE IF NOT EXISTS `{$db->prefix}wc_ukr_shipping_np_ttn` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `order_id` int(10) NOT NULL DEFAULT '0',
              `ttn_id` varchar(100) NOT NULL,
              `ttn_ref` varchar(255) NOT NULL,
              `cloud_status` varchar(100) DEFAULT NULL,
              `status` varchar(255) DEFAULT NULL,
              `status_code` varchar(255) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT NULL,
              `updated_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `i_order` (`order_id`),
              KEY `i_ttn` (`ttn_id`)
            ) ENGINE=InnoDB $collate
        ");
    }
}
