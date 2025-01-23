<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class CloudStatusChangedEvent implements EventInterface
{
    private string $newStatus;

    public function __construct(string $newStatus)
    {
        $this->newStatus = $newStatus;
    }

    public function canProcess(Context $context): bool
    {
        return $context->getTtn()['cloud_status'] === $this->newStatus;
    }
}
