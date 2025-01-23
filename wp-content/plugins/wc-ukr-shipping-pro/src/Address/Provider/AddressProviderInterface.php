<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Provider;

use kirillbdev\WCUkrShipping\Address\Dto\SearchWarehouseResultDto;
use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\Address\Model\Warehouse;

if ( ! defined('ABSPATH')) {
    exit;
}

interface AddressProviderInterface
{
    /**
     * @param string $query
     * @return City[]
     */
    public function searchCitiesByQuery(string $query): array;

    public function searchCityByRef(string $ref): ?City;

    public function searchWarehousesByQuery(
        string $cityRef,
        string $query,
        int $page,
        array $types = []
    ): SearchWarehouseResultDto;

    public function searchWarehouseByRef(string $ref): ?Warehouse;
}
