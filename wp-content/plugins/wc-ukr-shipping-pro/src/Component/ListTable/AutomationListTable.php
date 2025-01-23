<?php

namespace kirillbdev\WCUkrShipping\Component\ListTable;

use kirillbdev\WCUkrShipping\DB\Criteria\FindAutomationRulesCriteria;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutomationListTable extends \WP_List_Table
{
    private const LIMIT = 20;

    private AutomationRulesRepository $automationRulesRepository;

    function __construct(AutomationRulesRepository $automationRulesRepository)
    {
        parent::__construct([
            'singular' => 'wcus_automation_rule',
            'plural'   => 'wcus_automation_rules',
            'ajax'     => false,
        ]);

        $this->automationRulesRepository = $automationRulesRepository;
        $this->bulk_action_handler();
    }

    function prepare_items(): void
    {
        $rules = $this->automationRulesRepository->findByCriteria(
            new FindAutomationRulesCriteria(
                    $this->get_pagenum(),
                    self::LIMIT,
                    $_REQUEST['orderby'] ?? 'created_at',
                    $_REQUEST['order'] ?? 'desc'
            )
        );
        $this->set_pagination_args([
            'total_items' => $this->automationRulesRepository->getTotalRules(),
            'per_page' => self::LIMIT,
        ]);

        $this->items = array_map(function (array $row) {
            $row['active'] = __((int)$row['active'] ? 'Yes' : 'No');

            return (object)$row;
        }, $rules);
    }

    function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', 'wc-ukr-shipping-pro'),
            'event_name' => __('Event', 'wc-ukr-shipping-pro'),
            'event_data' => __('Event data', 'wc-ukr-shipping-pro'),
            'active' => __('Active', 'wc-ukr-shipping-pro'),
            'created_at' => __('Created At', 'wc-ukr-shipping-pro'),
        ];
    }

    function get_sortable_columns(): array
    {
        return [
            'created_at' => ['created_at', 'desc'],
        ];
    }

    protected function get_bulk_actions(): array
    {
        return [];
    }

    function extra_tablenav($which): void
    {

    }

    /**
     * @param object $item
     * @param string $colname
     */
    function column_default($item, $colname): string
    {
        if($colname === 'name') {
            $actions = [];
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=wcus_automation_rule_edit&id=' . (int)$item->id),
                __('Edit','wc-ukr-shipping-pro')
            );
            $actions['delete'] = sprintf(
                '<a href="%s" onclick = "return confirm( \'%s\' );">%s</a>',
                wp_nonce_url('?page=wcus_automation&action=delete&id=' . $item->id, 'wcus_automation_delete'),
                esc_js(__( 'Confirm action', 'wc-ukr-shipping-pro')),
                esc_html__( 'Delete', 'wc-ukr-shipping-pro')
            );

            return esc_html( $item->name ) . $this->row_actions( $actions );
        } else {
            return $item->$colname ?? '';
        }
    }

    /**
     * @param object $item
     * @return void
     */
    function column_cb($item): void
    {
        echo '<input type="checkbox" name="licids[]" id="cb-select-'. $item->id .'" value="'. $item->id .'" />';
    }

    private function bulk_action_handler(): void
    {

    }
}
