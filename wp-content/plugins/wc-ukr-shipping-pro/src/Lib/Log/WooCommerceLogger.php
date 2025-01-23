<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Lib\Log;

final class WooCommerceLogger implements LoggerInterface
{
    public function warning(string $message, array $context = []): void
    {
        $logger = wc_get_logger();

        if ($logger !== null) {
            $logger->warning($message, array_merge([
                'tag' => 'wc_ukraine_shipping_pro',
            ], $context));
        }
    }
}
