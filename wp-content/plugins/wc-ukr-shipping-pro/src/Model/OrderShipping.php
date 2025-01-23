<?php

namespace kirillbdev\WCUkrShipping\Model;

use kirillbdev\WCUkrShipping\Contracts\AddressInterface;
use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\Contracts\OrderDataInterface;
use kirillbdev\WCUkrShipping\Services\CalculationService;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderShipping
{
    /**
     * @var \WC_Order_Item_Shipping
     */
    private $item;

    /**
     * @var array
     */
    private $data;

    /**
     * @param \WC_Order_Item_Shipping $item
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Store order shipping data.
     *
     * @param OrderDataInterface $orderData
     */
    public function save($orderData, bool $forceCalculation = false)
    {
        $this->data = $orderData;
        $this->clearOldShippingData();

        if ($orderData->isAddressShipping()) {
            $this->saveAddressShipping($orderData->getShippingAddress());
        } else {
            $this->saveWarehouseShipping($orderData->getShippingAddress());
        }

        if ($forceCalculation) {
            $calculationService = new CalculationService();
            $cost = $calculationService->calculateCost($orderData);
            $this->item->set_total($cost);
        }
    }

    /**
     * @param AddressInterface $address
     */
    private function saveAddressShipping($address)
    {
        // fixme: This logic may be attached through event model
        /** @var CustomerStorageInterface $customerStorage */
        $customerStorage = wcus_container()->make(CustomerStorageInterface::class);

        if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui') || is_admin()) {
            $this->item->add_meta_data('wcus_settlement_ref', $this->sanitizeValue($address->getSettlementInfo('ref')));
            $this->item->add_meta_data('wcus_settlement_full', $this->sanitizeValue($address->getSettlementInfo('full')));
            $this->item->add_meta_data('wcus_settlement_name', $this->sanitizeValue($address->getSettlementInfo('name')));
            $this->item->add_meta_data('wcus_settlement_area', $this->sanitizeValue($address->getSettlementInfo('area')));
            $this->item->add_meta_data('wcus_settlement_region', $this->sanitizeValue($address->getSettlementInfo('region')));

            $this->item->add_meta_data('wcus_street_ref', $this->sanitizeValue($address->getStreetInfo('ref')));
            $this->item->add_meta_data('wcus_street_name', $this->sanitizeValue($address->getStreetInfo('name')));
            $this->item->add_meta_data('wcus_street_full', $this->sanitizeValue($address->getStreetInfo('full')));
            $this->item->add_meta_data('wcus_house', $this->sanitizeValue($address->getHouse()));
            $this->item->add_meta_data('wcus_flat', $this->sanitizeValue($address->getFlat()));
            $this->item->add_meta_data('wcus_api_address', 1);

            // fixme: This logic may be attached through event model
            $customerStorage->add(CustomerStorageInterface::KEY_LAST_SETTLEMENT, [
                'full' => $this->sanitizeValue($address->getSettlementInfo('full')),
                'ref' => $this->sanitizeValue($address->getSettlementInfo('ref')),
                'name' => $this->sanitizeValue($address->getSettlementInfo('name')),
                'area' => $this->sanitizeValue($address->getSettlementInfo('area')),
                'region' => $this->sanitizeValue($address->getSettlementInfo('region'))
            ]);
            $customerStorage->add(CustomerStorageInterface::KEY_LAST_STREET, [
                'full' => $this->sanitizeValue($address->getStreetInfo('full')),
                'ref' => $this->sanitizeValue($address->getStreetInfo('ref')),
                'name' => $this->sanitizeValue($address->getStreetInfo('name'))
            ]);
            $customerStorage->add(CustomerStorageInterface::KEY_LAST_HOUSE, $this->sanitizeValue($address->getHouse()));
            $customerStorage->add(CustomerStorageInterface::KEY_LAST_FLAT, $this->sanitizeValue($address->getFlat()));
        } else {
            $this->item->add_meta_data('wcus_city_ref', $this->sanitizeValue($address->getCityRef()));
            $this->item->add_meta_data('wcus_address', $this->sanitizeValue($address->getCustomAddress()));

            // fixme: This logic may be attached through event model
            $customerStorage->add(CustomerStorageInterface::KEY_LAST_CITY_REF, $this->sanitizeValue($address->getCityRef()));
        }
    }

    /**
     * @param AddressInterface $address
     */
    private function saveWarehouseShipping($address)
    {
        $this->item->add_meta_data('wcus_city_ref', $this->sanitizeValue($address->getCityRef()));
        $this->item->add_meta_data('wcus_warehouse_ref', $this->sanitizeValue($address->getWarehouseRef()));

        // fixme: This logic may be attached through event model
        /** @var CustomerStorageInterface $customerStorage */
        $customerStorage = wcus_container()->make(CustomerStorageInterface::class);
        $customerStorage->add(CustomerStorageInterface::KEY_LAST_CITY_REF, $this->sanitizeValue($address->getCityRef()));
        $customerStorage->add(CustomerStorageInterface::KEY_LAST_WAREHOUSE_REF, $this->sanitizeValue($address->getWarehouseRef()));
    }

    private function clearOldShippingData()
    {
        $keys = [
            'wcus_settlement_ref',
            'wcus_settlement_full',
            'wcus_settlement_name',
            'wcus_settlement_area',
            'wcus_settlement_region',
            'wcus_street_ref',
            'wcus_street_name',
            'wcus_street_full',
            'wcus_house',
            'wcus_flat',
            'wcus_api_address',
            'wcus_area_ref',
            'wcus_city_ref',
            'wcus_warehouse_ref',
            'wcus_address'
        ];

        foreach ($keys as $key) {
            $this->item->delete_meta_data($key);
        }
    }

    private function sanitizeValue(string $value): string
    {
        return sanitize_text_field(wp_unslash($value));
    }
}
