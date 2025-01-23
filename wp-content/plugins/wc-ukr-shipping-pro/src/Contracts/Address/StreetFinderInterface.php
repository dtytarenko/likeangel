<?php

namespace kirillbdev\WCUkrShipping\Contracts\Address;

use kirillbdev\WCUkrShipping\Dto\Address\StreetDto;

if ( ! defined('ABSPATH')) {
    exit;
}

interface StreetFinderInterface
{
    public function getStreet(): ?StreetDto;
}