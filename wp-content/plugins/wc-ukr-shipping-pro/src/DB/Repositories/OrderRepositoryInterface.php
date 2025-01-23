<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Repositories;

interface OrderRepositoryInterface
{
    public function getOrdersWithTTN(int $offset, int $limit): array;
    public function getOrderInfo(int $orderId): array;
    public function getOrderShippingMethod(int $orderId): ?\stdClass;
    public function getCountOrderPages(int $limit): int;
}
