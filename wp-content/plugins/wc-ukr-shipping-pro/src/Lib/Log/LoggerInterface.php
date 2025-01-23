<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Lib\Log;

// todo: implement PSR logging
interface LoggerInterface
{
    public function warning(string $message, array $context = []): void;
}
