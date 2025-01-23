<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Task;

if (!defined('ABSPATH')) {
    exit;
}

interface TaskInterface
{
    public function getName(): string;
    public function getParam1(): ?string;
    public function getParam2(): ?string;
    public function getParam3(): ?string;
    public function getParam4(): ?string;
}
