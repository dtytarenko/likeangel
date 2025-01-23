<?php

namespace kirillbdev\WCUkrShipping\Model\Address;

use kirillbdev\WCUkrShipping\Contracts\AddressInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class AdminEditOrderAddress implements AddressInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * AdminEditOrderAddress constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getAreaRef()
    {
        return $this->data['area_ref'];
    }

    /**
     * @return string
     */
    public function getCityRef()
    {
        return $this->isAddressShipping()
            ? $this->getSettlementInfo('ref')
            : $this->data['city_ref'];
    }

    public function getCityName(): string
    {
        return $this->data['city_name'];
    }

    /**
     * @return string
     */
    public function getWarehouseRef()
    {
        return $this->data['warehouse_ref'];
    }

    public function getWarehouseName(): string
    {
        return $this->data['warehouse_name'];
    }

    /**
     * @return bool
     */
    public function isAddressShipping()
    {
        return 'address' === $this->data['shipping_type'];
    }

    /**
     * @return string
     */
    public function getCustomAddress()
    {
        return '';
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getSettlementInfo($key)
    {
        if (isset($this->data['settlement'][ $key ])) {
            return $this->data['settlement'][ $key ];
        }

        return '';
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getStreetInfo($key)
    {
        if (isset($this->data['street'][ $key ])) {
            return $this->data['street'][ $key ];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getHouse()
    {
        return $this->data['house'];
    }

    /**
     * @return string
     */
    public function getFlat()
    {
        return $this->data['flat'];
    }
}