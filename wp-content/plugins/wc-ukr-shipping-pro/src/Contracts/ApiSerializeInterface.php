<?php

namespace kirillbdev\WCUkrShipping\Contracts;

if ( ! defined('ABSPATH')) {
    exit;
}

interface ApiSerializeInterface
{
    /**
     * Serialize object for the possibility of transfer via api.
     *
     * @return array
     */
    public function serialize();
}