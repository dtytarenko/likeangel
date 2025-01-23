<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\DB\Criteria\FindAutomationRulesCriteria;
use kirillbdev\WCUSCore\Facades\DB;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutomationRulesRepository
{
    public function findById(int $id): ?\stdClass
    {
        $rule = DB::table(DB::prefixedTable('wc_ukr_shipping_automation_rules'))
            ->where('id', $id)
            ->first();

        if ($rule !== null) {
            $rule->actions = DB::table(DB::prefixedTable('wc_ukr_shipping_automation_actions'))
                ->where('rule_id', $rule->id)
                ->get([
                    'name',
                    'action_data',
                ]);
            foreach ($rule->actions as &$action) {
                $action['action_data'] = json_decode($action['action_data'], true);
            }
        }

        return $rule;
    }

    public function findActiveByEvent(string $event): array
    {
        $rules = DB::table(DB::prefixedTable('wc_ukr_shipping_automation_rules'))
            ->where('event_name', $event)
            ->where('active', 1)
            ->get();

        return array_map(function (array $rule) {
            $rule['event_data'] = json_decode($rule['event_data'], true);

            return $rule;
        }, $rules);
    }

    public function findActionsByRuleId(int $ruleId, int $parentId = 0, int $level = 0): array
    {
        $actions = DB::table(DB::prefixedTable('wc_ukr_shipping_automation_actions'))
            ->where('rule_id', $ruleId)
            ->where('parent_id', $parentId)
            ->where('level', $level)
            ->orderBy('id', 'asc')
            ->get();

        return array_map(function (array $action) {
            $action['action_data'] = json_decode($action['action_data'], true);

            return $action;
        }, $actions);
    }

    public function findByCriteria(FindAutomationRulesCriteria $criteria): array
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_automation_rules'))
            ->orderBy($criteria->getOrderColumn(), $criteria->getOrderDirection())
            ->skip(($criteria->getPage() - 1) * $criteria->getLimit())
            ->limit($criteria->getLimit())
            ->get();
    }

    public function getTotalRules(): int
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_automation_rules'))->count();
    }

    public function insert(
        string $name,
        string $eventName,
        bool $active,
        array $data,
        array $actions
    ): int {
        global $wpdb;

        $now = date('Y-m-d H:i:s');
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO `{$wpdb->prefix}wc_ukr_shipping_automation_rules` 
                (`name`, `event_name`, `event_data`, `active`, `created_at`, `updated_at`)
                VALUES (%s, %s, %s, %d, %s, %s)",
                $name,
                $eventName,
                json_encode($data),
                $active ? 1 : 0,
                $now,
                $now
            )
        );

        if ($wpdb->last_error) {
            throw new \Exception("Query exception: $wpdb->last_error");
        }

        $ruleId = $wpdb->insert_id;
        $this->storeActions($wpdb->insert_id, $actions);

        return $ruleId;
    }

    public function update(
        int $id,
        string $name,
        string $eventName,
        bool $active,
        array $data,
        array $actions
    ): int {
        global $wpdb;

        $now = date('Y-m-d H:i:s');
        $wpdb->update(
            "{$wpdb->prefix}wc_ukr_shipping_automation_rules",
            [
                'name' => $name,
                'event_name' => $eventName,
                'event_data' => json_encode($data),
                'active' => (int)$active,
                'updated_at' => $now,
            ],
            ['id' => $id]
        );

        if ($wpdb->last_error) {
            throw new \Exception("Query exception: $wpdb->last_error");
        }

        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$wpdb->prefix}wc_ukr_shipping_automation_actions`
            WHERE `rule_id` = %d",
            $id
        ));
        $this->storeActions($id, $actions);

        return $id;
    }

    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$wpdb->prefix}wc_ukr_shipping_automation_rules`
            WHERE `id` = %d",
            $id
        ));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$wpdb->prefix}wc_ukr_shipping_automation_actions`
            WHERE `rule_id` = %d",
            $id
        ));
    }

    private function storeActions(int $ruleId, array $actions): void
    {
        global $wpdb;

        $now = date('Y-m-d H:i:s');
        foreach ($actions as $action) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO `{$wpdb->prefix}wc_ukr_shipping_automation_actions` 
                (`rule_id`, `name`, `action_data`, `created_at`, `updated_at`)
                VALUES (%d, %s, %s, %s, %s)",
                    $ruleId,
                    $action['type'],
                    json_encode($action['params']),
                    $now,
                    $now
                )
            );
        }
    }
}
