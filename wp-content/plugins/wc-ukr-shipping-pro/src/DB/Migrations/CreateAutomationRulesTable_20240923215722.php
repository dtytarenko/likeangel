<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateAutomationRulesTable_20240923215722 extends Migration
{
    public function name(): string
    {
        return  'create_automation_rules_table_20240923215722';
    }

    /**
     * @param \wpdb $db
     */
    public function up($db): void
    {
        $collate = $db->get_charset_collate();

        $db->query("
          CREATE TABLE IF NOT EXISTS `{$db->prefix}wc_ukr_shipping_automation_rules` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `event_name` VARCHAR(50) NOT NULL,
            `event_data` TEXT DEFAULT NULL,
            `active` TINYINT(1) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY `i_event` (`event_name`)
          ) ENGINE=InnoDB $collate
        ");
    }
}
