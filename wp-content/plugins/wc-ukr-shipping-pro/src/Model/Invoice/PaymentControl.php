<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Model\Invoice;

if ( ! defined('ABSPATH')) {
    exit;
}

class PaymentControl
{
    private int $cost;

    public function __construct(int $cost)
    {
        $this->cost = $cost;
    }

    public function getCost(): int
    {
        return $this->cost;
    }
}
