<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class UpdateOrderStatusAction implements ActionInterface
{
    private string $newStatus;

    public function __construct(string $newStatus)
    {
        $this->newStatus = $newStatus;
    }

    public function execute(Context $context): void
    {
        $context->getOrder()->update_status($this->newStatus);
    }
}
