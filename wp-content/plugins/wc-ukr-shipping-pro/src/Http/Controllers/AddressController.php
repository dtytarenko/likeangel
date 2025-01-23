<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Address\Model\Warehouse;
use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Address\Provider\CloudAddressProvider;
use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\Services\Address\AddressService;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddressController extends Controller
{
    /**
     * @var AddressService
     */
    private $addressService;

    /**
     * @var AddressProviderInterface
     */
    private $addressProvider;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
        $this->addressProvider = wcus_container()->make(AddressProviderInterface::class);
    }

    public function searchCities(Request $request)
    {
        if (  ! $request->get('query')) {
            return $this->jsonResponse([
                'success' => true,
                'data' => []
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->mapCities(
                $this->addressProvider->searchCitiesByQuery($request->get('query')),
                $request->get('lang', '')
            )
        ]);
    }

    public function searchWarehouses(Request $request)
    {
        if ( ! $request->get('city_ref') || ! (int)$request->get('page')) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        if ((int)get_option(WCUS_OPTION_ONLY_CARGO_WAREHOUSES)) {
            $types = [
                WCUS_WAREHOUSE_TYPE_CARGO,
            ];
        } else {
            $types = [
                WCUS_WAREHOUSE_TYPE_REGULAR,
                WCUS_WAREHOUSE_TYPE_CARGO,
            ];
        }
        $result = $this->addressProvider->searchWarehousesByQuery(
            $request->get('city_ref'),
            $request->get('query', ''),
            (int)$request->get('page'),
            $types
        );

        if (!count($result->getWarehouses())) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $items = $this->mapWarehouses(
            $result->getWarehouses(),
            $request->get('lang', '')
        );

        $offset = ((int)$request->get('page') - 1) * 20 + count($items);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => $items,
                'more' => $offset < $result->getTotal(),
            ]
        ]);
    }

    public function adminSearchWarehouses(Request $request)
    {
        if ( ! $request->get('city_ref') || ! (int)$request->get('page')) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $result = $this->addressProvider->searchWarehousesByQuery(
            $request->get('city_ref'),
            $request->get('query', ''),
            (int)$request->get('page')
        );

        if (!count($result->getWarehouses())) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $items = $this->mapWarehouses(
            $result->getWarehouses(),
            $request->get('lang', '')
        );

        $offset = ((int)$request->get('page') - 1) * 20 + count($items);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => $items,
                'more' => $offset < $result->getTotal(),
            ]
        ]);
    }

    public function searchPoshtomats(Request $request)
    {
        if ( ! $request->get('city_ref') || ! (int)$request->get('page')) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $result = $this->addressProvider->searchWarehousesByQuery(
            $request->get('city_ref'),
            $request->get('query', ''),
            (int)$request->get('page'),
            [
                WCUS_WAREHOUSE_TYPE_POSHTOMAT,
            ]
        );

        if (!count($result->getWarehouses())) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => [],
                    'more' => false
                ]
            ]);
        }

        $items = $this->mapWarehouses(
            $result->getWarehouses(),
            $request->get('lang', '')
        );

        $offset = ((int)$request->get('page') - 1) * 20 + count($items);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => $items,
                'more' => $offset < $result->getTotal(),
            ]
        ]);
    }

    public function searchSettlements(Request $request)
    {
        $settlements = $this->addressService->searchSettlements($request->get('query'));

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->mapSettlements($settlements)
        ]);
    }

    public function searchStreets(Request $request)
    {
        $streets = $this->addressService->searchStreets($request->get('query'), $request->get('settlement_ref'));

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->mapStreets($streets)
        ]);
    }

    /**
     * @param City[] $cities
     * @param $locale
     * @return array[]
     */
    private function mapCities(array $cities, string $locale): array
    {
        return array_map(function (City $item) use ($locale) {
            return [
                'value' => $item->getRef(),
                'name' => $locale === 'ru' ? $item->getNameRu() : $item->getNameUa(),
            ];
        }, $cities);
    }

    /**
     * @param Warehouse[] $warehouses
     * @param string $locale
     * @return array
     */
    private function mapWarehouses(array $warehouses, string $locale): array
    {
        return array_map(function (Warehouse $item) use ($locale) {
            return [
                'value' => $item->getRef(),
                'name' => $locale === 'ru' ? $item->getNameRu() : $item->getNameUa(),
            ];
        }, $warehouses);
    }

    private function mapSettlements($settlements)
    {
        return array_map(function($item) {
            return [
                'value' => $item['Ref'],
                'name' => $item['Present'],
                'meta' => [
                    'name' => $item['MainDescription'],
                    'area' => $item['Area'],
                    'region' => $item['Region']
                ]
            ];
        }, $settlements);
    }

    private function mapStreets($streets)
    {
        return array_map(function($item) {
            return [
                'value' => $item['SettlementStreetRef'],
                'name' => $item['Present'],
                'meta' => [
                    'name' => $item['SettlementStreetDescription']
                ]
            ];
        }, $streets);
    }
}