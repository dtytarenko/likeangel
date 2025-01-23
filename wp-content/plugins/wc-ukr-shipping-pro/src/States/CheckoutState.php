<?php

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\Inc\Address\CheckoutFinder;
use kirillbdev\WCUkrShipping\Inc\UI\CityUIValue;
use kirillbdev\WCUkrShipping\Inc\UI\SettlementUIValue;
use kirillbdev\WCUkrShipping\Inc\UI\StreetUIValue;
use kirillbdev\WCUkrShipping\Inc\UI\WarehouseUIValue;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutState extends AppState
{
    protected function getState()
    {
        $finder = new CheckoutFinder();
        /** @var CustomerStorageInterface $customerStorage */
        $customerStorage = wcus_container()->make(CustomerStorageInterface::class);
        $saveEnable = (int)get_option(WCUS_OPTION_SAVE_CUSTOMER_ADDRESS);

        return [
            'city' => CityUIValue::fromFinder($finder),
            'warehouse' => WarehouseUIValue::fromFinder($finder),
            'settlement' => SettlementUIValue::fromFinder($finder),
            'street' => StreetUIValue::fromFinder($finder),
            'house' => $saveEnable ? $customerStorage->get(CustomerStorageInterface::KEY_LAST_HOUSE) : '',
            'flat' => $saveEnable ? $customerStorage->get(CustomerStorageInterface::KEY_LAST_FLAT) : '',
            'shippingTypeDefault' => apply_filters('wcus_checkout_default_shipping_type', 'warehouse'),
            'shippingTypePriority' => apply_filters('wcus_checkout_shipping_type_priority', [
                'warehouse' => 1,
                'doors' => 1,
                'poshtomat' => 1,
            ])
        ];
    }
}