<?php

declare(strict_types=1);

namespace kirillbdev\WCUSCore\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateNpMainTables_20240831172858 extends Migration
{
    public function name(): string
    {
        return 'create_np_main_tables_20240831172858';
    }

    public function up(\wpdb $db): void
    {
        $collate = $db->get_charset_collate();
        $prefix = $db->prefix;

        // Nova Poshta Areas
        $db->query("
          CREATE TABLE IF NOT EXISTS `{$prefix}wc_ukr_shipping_np_areas` (
            `ref` VARCHAR(36) NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`ref`)
          ) $collate
        ");

        if ($db->last_error) { // Force return to throw exception
            return;
        }

        // Nova Poshta Cities
        $db->query("
          CREATE TABLE IF NOT EXISTS `{$prefix}wc_ukr_shipping_np_cities` (
            `ref` VARCHAR(36) NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            `description_ru` VARCHAR(255) NOT NULL,
            `area_ref` VARCHAR(36) DEFAULT NULL,
            PRIMARY KEY (`ref`),
            KEY `area_ref` (`area_ref`)
          ) $collate
        ");

        if ($db->last_error) {  // Force return to throw exception
            return;
        }

        // Nova Poshta Warehouses
        $db->query("
          CREATE TABLE IF NOT EXISTS `{$prefix}wc_ukr_shipping_np_warehouses` (
              `ref` varchar(36) NOT NULL,
              `description` varchar(255) NOT NULL,
              `description_ru` varchar(255) NOT NULL,
              `city_ref` varchar(36) DEFAULT NULL,
              `number` int(10) NOT NULL DEFAULT '0',
              `warehouse_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`ref`),
              KEY `city_ref` (`city_ref`),
              KEY `number_index` (`number`),
              KEY `warehouse_type_index` (`warehouse_type`)
          ) $collate
        ");
    }
}
