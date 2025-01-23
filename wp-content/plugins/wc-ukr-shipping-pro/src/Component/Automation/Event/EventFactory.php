<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

if ( ! defined('ABSPATH')) {
    exit;
}

class EventFactory
{
    public function createFromRule(array $rule): EventInterface
    {
        switch ($rule['event_name']) {
            case 'cloud_status_changed':
                return new CloudStatusChangedEvent($rule['event_data']['newStatus']);
            case 'carrier_status_changed':
                return new CarrierStatusChangedEvent($rule['event_data']['newStatus']);
            case 'ttn_created':
            case 'ttn_attached':
            case 'ttn_deleted':
                return new SimpleEvent();
            default:
                throw new \LogicException("Invalid event '{$rule['event_name']}'");
        }
    }
}
