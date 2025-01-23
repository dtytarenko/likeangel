<?php

namespace kirillbdev\WCUkrShipping\Inc\Customer;

use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class LoggedCustomerStorage implements CustomerStorageInterface
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return get_user_meta(wc()->customer->get_id(), $key, true) ?: null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function add(string $key, $value)
    {
        update_user_meta(wc()->customer->get_id(), $key, $value);
    }
}