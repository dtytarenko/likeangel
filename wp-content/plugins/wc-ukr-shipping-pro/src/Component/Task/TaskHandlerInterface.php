<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Task;

if (!defined('ABSPATH')) {
    exit;
}

interface TaskHandlerInterface
{
    public function handle(?TaskInterface $task): void;
}
