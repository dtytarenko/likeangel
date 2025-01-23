<?php

namespace kirillbdev\WCUkrShipping\Model\Document;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class ShippingRecipientAddress
{
    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var \WC_Order_Item_Shipping
     */
    private $orderShipping;

    /**
     * ShippingRecipientAddress constructor.
     *
     * @param $shippingMethod
     */
    public function __construct($order, $orderShipping)
    {
        $this->order = $order;
        $this->orderShipping = $orderShipping;
    }

    public function writeData(&$data)
    {
        $data['recipient']['service_type'] = $this->orderShipping->get_meta('wcus_warehouse_ref') ? 'Warehouse' : 'Doors';
        $data['recipient']['area_ref'] = $this->orderShipping->get_meta('wcus_area_ref');
        $data['recipient']['city_ref'] = $this->orderShipping->get_meta('wcus_city_ref');
        $data['recipient']['warehouse_ref'] = $this->orderShipping->get_meta('wcus_warehouse_ref');
        $data['recipient']['default_city'] = [
            'name' => '',
            'value' => ''
        ];
        $data['recipient']['default_warehouse'] = [
            'name' => '',
            'value' => ''
        ];

        /** @var AddressProviderInterface $addressProvider */
        $addressProvider = wcus_container()->make(AddressProviderInterface::class);
        /** @var TranslateService $translateService */
        $translateService = wcus_container()->make(TranslateService::class);

        if ($data['recipient']['city_ref']) {
            $city = $addressProvider->searchCityByRef($data['recipient']['city_ref']);

            if ($city !== null) {
                $data['recipient']['default_city'] = [
                    'name' => $translateService->translateCityName($city),
                    'value' => $city->getRef(),
                ];
            }
        }

        if ($data['recipient']['warehouse_ref']) {
            $warehouse = $addressProvider->searchWarehouseByRef($data['recipient']['warehouse_ref']);

            if ($warehouse !== null) {
                $data['recipient']['default_warehouse'] = [
                    'name' => $translateService->translateWarehouseName($warehouse),
                    'value' => $warehouse->getRef(),
                ];
            }
        }

        if ($this->orderShipping->get_meta('wcus_address')) {
            $billingOnly = 'billing_only' === get_option('woocommerce_ship_to_destination');

            $data['recipient']['custom_address'] = sprintf(
                '%s<br/>%s<br/>%s',
                $billingOnly ? $this->order->get_billing_state() : $this->order->get_shipping_state(),
                $billingOnly ? $this->order->get_billing_city() : $this->order->get_shipping_city(),
                $billingOnly ? $this->order->get_billing_address_1() : $this->order->get_shipping_address_1()
            );
        }
        else {
            $data['recipient']['custom_address'] = '';
        }

        $data['recipient']['settlement'] = [
            'value' => $this->orderShipping->get_meta('wcus_settlement_ref'),
            'name' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_settlement_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_settlement_name')),
                'area' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_settlement_area')),
                'region' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_settlement_region'))
            ]
        ];

        $data['recipient']['street'] = [
            'value' => $this->orderShipping->get_meta('wcus_street_ref'),
            'name' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_street_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString($this->orderShipping->get_meta('wcus_street_name'))
            ]
        ];

        $data['recipient']['house'] = $this->orderShipping->get_meta('wcus_house');
        $data['recipient']['flat'] = $this->orderShipping->get_meta('wcus_flat');
    }
}