<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Provider;

use kirillbdev\WCUkrShipping\Address\Dto\SearchWarehouseResultDto;
use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\Address\Model\Warehouse;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class MySqlAddressProvider implements AddressProviderInterface
{
    private CityRepository $cityRepository;
    private WarehouseRepository $warehouseRepository;

    public function __construct(CityRepository $cityRepository, WarehouseRepository $warehouseRepository)
    {
        $this->cityRepository = $cityRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $result = $this->cityRepository->searchCitiesByQuery($query);

        return array_map(function (array $row) {
            return new City($row['ref'], $row['area_ref'], $row['description'], $row['description_ru']);
        }, $result);
    }

    public function searchCityByRef(string $ref): ?City
    {
        $result = $this->cityRepository->getCityByRef($ref);
        if ($result === null) {
            return null;
        }

        return new City($result->ref, $result->area_ref, $result->description, $result->description_ru);
    }

    public function searchWarehousesByQuery(
        string $cityRef,
        string $query,
        int $page,
        array $types = []
    ): SearchWarehouseResultDto {
        $total = $this->warehouseRepository->countByQuery($query, $cityRef, $types);
        $warehouses = array_map(function (array $row) {
            return new Warehouse($row['ref'], $row['city_ref'], $row['description'], $row['description_ru']);
        }, $this->warehouseRepository->searchByQuery($query, $cityRef, $page, 20, $types));

        return new SearchWarehouseResultDto($warehouses, $total);
    }

    public function searchWarehouseByRef(string $ref): ?Warehouse
    {
        $result = $this->warehouseRepository->getWarehouseByRef($ref);
        if ($result === null) {
            return null;
        }

        return new Warehouse($result->ref, $result->city_ref, $result->description, $result->description_ru);
    }
}
