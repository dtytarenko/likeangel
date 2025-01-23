<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\Address\AddressService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class EditOrderState extends AppState
{
    private AddressProviderInterface $addressProvider;
    private TranslateService $translateService;

    public function __construct(TranslateService $translateService)
    {
        $this->addressProvider = wcus_container()->make(AddressProviderInterface::class);
        $this->translateService = $translateService;
    }

    protected function getState()
    {
        $orderShipping = WCUSHelper::getOrderShippingMethod(wc_get_order($this->params['order_id']));

        if ( ! $orderShipping) {
            return [];
        }

        $state = [
            'default_cities' => $this->getDefaultCities(),
            'order_id' => $this->params['order_id'],
            'shipping_type' => $orderShipping->get_meta('wcus_city_ref') ? 'warehouse' : 'address'
        ];

        $currentCity = $this->addressProvider->searchCityByRef($orderShipping->get_meta('wcus_city_ref'));
        if ($currentCity === null) {
            $state['current_city'] = [
                'value' => '',
                'name' => '',
            ];
        } else {
            $state['current_city'] = [
                'value' => $currentCity->getRef(),
                'name' => $this->translateService->translateCityName($currentCity),
            ];
        }

        $currentWarehouse = $this->addressProvider->searchWarehouseByRef($orderShipping->get_meta('wcus_warehouse_ref'));
        if ($currentWarehouse === null) {
            $state['current_warehouse'] = [
                'value' => '',
                'name' => '',
            ];
        } else {
            $state['current_warehouse'] = [
                'value' => $currentWarehouse->getRef(),
                'name' => $this->translateService->translateWarehouseName($currentWarehouse),
            ];
        }

        $state['current_settlement'] = [
            'value' => $orderShipping->get_meta('wcus_settlement_ref'),
            'name' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_settlement_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_settlement_name')),
                'area' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_settlement_area')),
                'region' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_settlement_region'))
            ]
        ];

        $state['current_street'] = [
            'value' => $orderShipping->get_meta('wcus_street_ref'),
            'name' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_street_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString($orderShipping->get_meta('wcus_street_name'))
            ]
        ];

        $state['current_house'] = $orderShipping->get_meta('wcus_house');
        $state['current_flat'] = $orderShipping->get_meta('wcus_flat');

        return $state;
    }

    private function getDefaultCities()
    {
        // todo: refactor
        $addressService = new AddressService();

        return $addressService->getDefaultCities();
    }
}