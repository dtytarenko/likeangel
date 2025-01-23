<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Event;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

class SimpleEvent implements EventInterface
{
    public function canProcess(Context $context): bool
    {
        return true;
    }
}
