<?php

namespace kirillbdev\WCUkrShipping\Contracts\Customer;

if ( ! defined('ABSPATH')) {
    exit;
}

interface CustomerStorageInterface
{
    const KEY_LAST_CITY_REF = 'wcus_last_city_ref';
    const KEY_LAST_WAREHOUSE_REF = 'wcus_last_warehouse_ref';
    const KEY_LAST_SETTLEMENT = 'wcus_last_settlement';
    const KEY_LAST_STREET = 'wcus_last_street';
    const KEY_LAST_HOUSE = 'wcus_last_house';
    const KEY_LAST_FLAT = 'wcus_last_flat';

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key);

    /**
     * @param string $key
     * @param mixed $value
     */
    public function add(string $key, $value);
}