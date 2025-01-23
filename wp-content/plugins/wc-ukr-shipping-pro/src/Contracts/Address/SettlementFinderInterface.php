<?php

namespace kirillbdev\WCUkrShipping\Contracts\Address;

use kirillbdev\WCUkrShipping\Dto\Address\SettlementDto;

if ( ! defined('ABSPATH')) {
    exit;
}

interface SettlementFinderInterface
{
    public function getSettlement(): ?SettlementDto;
}