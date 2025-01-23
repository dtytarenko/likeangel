<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class CarrierStatusChangedEvent implements EventInterface
{
    /**
     * @var string[]
     */
    private array $newStatus;

    public function __construct(array $newStatus)
    {
        $this->newStatus = $newStatus;
    }

    public function canProcess(Context $context): bool
    {
        return in_array($context->getTtn()['status_code'], $this->newStatus);
    }
}
