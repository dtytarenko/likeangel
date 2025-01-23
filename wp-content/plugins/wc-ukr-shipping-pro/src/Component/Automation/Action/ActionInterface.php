<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Automation\Action;

use kirillbdev\WCUkrShipping\Component\Automation\Context;

if ( ! defined('ABSPATH')) {
    exit;
}

interface ActionInterface
{
    public function execute(Context $context): void;
}
