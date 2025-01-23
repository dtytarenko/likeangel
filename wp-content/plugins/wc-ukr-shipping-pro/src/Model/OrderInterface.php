<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Model;

if (!defined('ABSPATH')) {
    exit;
}

interface OrderInterface
{
    public function getCity(): string;

    public function getAddress1(): string;
}
