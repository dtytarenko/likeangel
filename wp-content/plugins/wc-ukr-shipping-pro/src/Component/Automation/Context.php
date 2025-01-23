<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation;

if ( ! defined('ABSPATH')) {
    exit;
}

class Context
{
    private string $event;
    private \WC_Order $order;
    private array $ttn;

    public function __construct(string $event, \WC_Order $order, array $ttn)
    {
        $this->event = $event;
        $this->order = $order;
        $this->ttn = $ttn;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getOrder(): \WC_Order
    {
        return $this->order;
    }

    public function getTtn(): array
    {
        return $this->ttn;
    }
}
