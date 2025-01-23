<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Provider;

use kirillbdev\WCUkrShipping\Address\Dto\SearchWarehouseResultDto;
use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\Address\Model\Warehouse;

if ( ! defined('ABSPATH')) {
    exit;
}

class FallbackAddressProvider implements AddressProviderInterface
{
    private AddressProviderInterface $addressProvider;
    private AddressProviderInterface $fallbackProvider;

    public function __construct(
        AddressProviderInterface $addressProvider,
        AddressProviderInterface $fallbackProvider
    ) {
        $this->addressProvider = $addressProvider;
        $this->fallbackProvider = $fallbackProvider;
    }

    public function searchCitiesByQuery(string $query): array
    {
        try {
            return $this->addressProvider->searchCitiesByQuery($query);
        } catch (\Exception $e) {
            return $this->fallbackProvider->searchCitiesByQuery($query);
        }
    }

    public function searchCityByRef(string $ref): ?City
    {
        try {
            return $this->addressProvider->searchCityByRef($ref);
        } catch (\Exception $e) {
            return $this->fallbackProvider->searchCityByRef($ref);
        }
    }

    public function searchWarehousesByQuery(
        string $cityRef,
        string $query,
        int $page,
        array $types = []
    ): SearchWarehouseResultDto {
        try {
            return $this->addressProvider->searchWarehousesByQuery($cityRef, $query, $page, $types);
        } catch (\Exception $e) {
            return $this->fallbackProvider->searchWarehousesByQuery($cityRef, $query, $page, $types);
        }
    }

    public function searchWarehouseByRef(string $ref): ?Warehouse
    {
        try {
            return $this->addressProvider->searchWarehouseByRef($ref);
        } catch (\Exception $e) {
            return $this->fallbackProvider->searchWarehouseByRef($ref);
        }
    }
}
