<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

if ( ! defined('ABSPATH')) {
    exit;
}

class ActionFactory
{
    public function createFromRaw(array $action): ActionInterface
    {
        switch ($action['name']) {
            case 'update_order_status':
                return new UpdateOrderStatusAction($action['action_data']['newStatus']);
            case 'send_email':
                return new SendEmailAction(
                    $action['action_data']['subject'],
                    $action['action_data']['message'],
                    $action['action_data']['destination'] ?? 'customer'
                );
            case 'add_order_note':
                return new AddOrderNoteAction($action['action_data']['message']);
            default:
                throw new \LogicException("Invalid action '{$action['name']}'");
        }
    }
}
