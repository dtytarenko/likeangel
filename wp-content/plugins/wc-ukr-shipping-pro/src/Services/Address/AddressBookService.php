<?php

namespace kirillbdev\WCUkrShipping\Services\Address;

use kirillbdev\WCUkrShipping\Api\CloudApi;
use kirillbdev\WCUkrShipping\DB\Dto\InsertAreaDto;
use kirillbdev\WCUkrShipping\DB\Dto\InsertCityDto;
use kirillbdev\WCUkrShipping\DB\Dto\InsertWarehouseDto;
use kirillbdev\WCUkrShipping\DB\Repositories\AreaRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseSyncRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiErrorException;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddressBookService
{
    private CloudApi $api;
    private WarehouseSyncRepository $syncRepository;

    public function __construct(CloudApi $api, WarehouseSyncRepository $syncRepository)
    {
        $this->api = $api;
        $this->syncRepository = $syncRepository;
    }

    /**
     * @throws ApiErrorException
     * @throws \kirillbdev\WCUkrShipping\Exceptions\ApiServiceException
     */
    public function loadAreas()
    {
        /** @var AreaRepository $areaRepository */
        $areaRepository = wcus_container()->make(AreaRepository::class);
        $areas = $this->api->getAreas();
        $areaRepository->clearAreas();

        foreach ($areas as $area) {
            $areaRepository->insertArea(
                new InsertAreaDto($area->getRef(), $area->getNameUa())
            );
        }
    }

    public function loadCities(int $page): int
    {
        /** @var CityRepository $cityRepository */
        $cityRepository = wcus_container()->make(CityRepository::class);
        $cities = $this->api->getCities(
            $page,
            apply_filters('wcus_api_city_limit', 2000)
        );

        $this->syncRepository->updateStage(WarehouseSyncRepository::STAGE_CITY, $page);

        if ($page === 1) {
            $cityRepository->clearCities();
        }

        $chunkSize = apply_filters('wcus_update_cities_chunk_size', 200);
        foreach (array_chunk($cities, $chunkSize) as $chunk) {
            $cityRepository->bulkUpsertCities($chunk);
        }

        return count($cities);
    }

    public function loadWarehouses(int $page): int
    {
        /** @var WarehouseRepository $warehouseRepository */
        $warehouseRepository = wcus_container()->make(WarehouseRepository::class);
        $warehouses = $this->api->getWarehouses(
            $page,
            apply_filters('wcus_api_warehouse_limit', 2000)
        );
        $this->syncRepository->updateStage(WarehouseSyncRepository::STAGE_WAREHOUSE, $page);

        if ($page === 1) {
            $warehouseRepository->clearWarehouses();
        }

        $chunkSize = apply_filters('wcus_update_warehouses_chunk_size', 200);
        foreach (array_chunk($warehouses, $chunkSize) as $chunk) {
            $warehouseRepository->bulkUpsertWarehouses($chunk);
        }

        if (count($warehouses) === 0) {
            $this->syncRepository->setCompleteSync();
        }

        return count($warehouses);
    }

    private function getWarehouseType(array $warehouse): int
    {
        if ($warehouse['TypeOfWarehouse'] === '9a68df70-0267-42a8-bb5c-37f427e36ee4') {
            return WCUS_WAREHOUSE_TYPE_CARGO;
        }

        if (strpos($warehouse['Description'], 'Поштомат') !== false) {
            return WCUS_WAREHOUSE_TYPE_POSHTOMAT;
        }

        return WCUS_WAREHOUSE_TYPE_REGULAR;
    }
}