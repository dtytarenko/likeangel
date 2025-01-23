<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Migrations;

use kirillbdev\WCUSCore\DB\Migration;

class CreateAutomationActionsTable_20240923215754 extends Migration
{
    public function name(): string
    {
        return  'create_automation_actions_table_20240923215754';
    }

    /**
     * @param \wpdb $db
     */
    public function up($db): void
    {
        $collate = $db->get_charset_collate();

        $db->query("
            CREATE TABLE IF NOT EXISTS `{$db->prefix}wc_ukr_shipping_automation_actions` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `rule_id` int(10) unsigned NOT NULL,
                `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
                `level` int(10) unsigned NOT NULL DEFAULT '0',
                `name` varchar(50) NOT NULL,
                `action_data` text DEFAULT NULL,
                `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `i_rule` (`rule_id`)
            ) ENGINE=InnoDB $collate
        ");
    }
}
