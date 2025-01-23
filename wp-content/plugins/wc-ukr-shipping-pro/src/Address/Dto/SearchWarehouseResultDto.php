<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Dto;

use kirillbdev\WCUkrShipping\Address\Model\Warehouse;

final class SearchWarehouseResultDto
{
    /**
     * @var Warehouse[]
     */
    private array $warehouses;

    private int $total;

    /**
     * @param Warehouse[] $warehouses
     * @param int $total
     */
    public function __construct(array $warehouses, int $total)
    {
        $this->warehouses = $warehouses;
        $this->total = $total;
    }

    public function getWarehouses(): array
    {
        return $this->warehouses;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
