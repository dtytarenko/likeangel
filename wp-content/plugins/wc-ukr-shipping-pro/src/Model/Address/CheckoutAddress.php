<?php

namespace kirillbdev\WCUkrShipping\Model\Address;

use kirillbdev\WCUkrShipping\Contracts\AddressInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutAddress implements AddressInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $type;

    /**
     * @param array $data
     * @param string $shippingType
     */
    public function __construct($data, $shippingType)
    {
        $this->data = $data;
        $this->type = $shippingType;
    }

    /**
     * @return string
     */
    public function getAreaRef()
    {
        return $this->data['wcus_np_' . $this->type . '_area'];
    }

    /**
     * @return string
     */
    public function getCityRef()
    {
        if (isset($this->data['wcus_ajax']) && 1 === (int)$this->data['wcus_ajax']) {
            return $this->data['wcus_city_ref'];
        }

        return $this->isAddressShipping() && 1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')
            ? $this->data['wcus_np_' . $this->type . '_settlement_ref']
            : $this->data['wcus_np_' . $this->type . '_city'];
    }

    public function getCityName(): string
    {
        return $this->data['wcus_np_' . $this->type . '_city_name'] ?? '';
    }

    /**
     * @return string
     */
    public function getWarehouseRef()
    {
        return $this->data['wcus_np_' . $this->type . '_warehouse'];
    }

    public function getWarehouseName(): string
    {
        return $this->data['wcus_np_' . $this->type . '_warehouse_name'] ?? '';
    }

    /**
     * @return string
     */
    public function getCustomAddress()
    {
        return $this->data['wcus_np_' . $this->type . '_custom_address'];
    }

    /**
     * @return bool
     */
    public function isAddressShipping()
    {
        if (isset($this->data['wcus_ajax']) && 1 === (int)$this->data['wcus_ajax']) {
            return (int)$this->data['wcus_address_shipping'];
        }

        $key = 'wcus_np_' . $this->type . '_custom_address_active';

        if (isset($this->data[ $key ]) && (int)$this->data[ $key ]) {
            return true;
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getSettlementInfo($key)
    {
        $key = 'wcus_np_' . $this->type . '_settlement_' . $key;

        return empty($this->data[$key])
            ? ''
            : $this->data[$key];
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getStreetInfo($key)
    {
        $key = 'wcus_np_' . $this->type . '_street_' . $key;

        return empty($this->data[$key])
            ? ''
            : $this->data[$key];
    }

    /**
     * @return string
     */
    public function getHouse()
    {
        $key = 'wcus_np_' . $this->type . '_house';

        return empty($this->data[$key])
            ? ''
            : $this->data[$key];
    }

    /**
     * @return string
     */
    public function getFlat()
    {
        $key = 'wcus_np_' . $this->type . '_flat';

        return empty($this->data[$key])
            ? ''
            : $this->data[$key];
    }
}