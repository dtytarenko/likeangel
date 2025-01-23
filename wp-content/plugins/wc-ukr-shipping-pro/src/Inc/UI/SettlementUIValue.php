<?php

namespace kirillbdev\WCUkrShipping\Inc\UI;

use kirillbdev\WCUkrShipping\Contracts\Address\SettlementFinderInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class SettlementUIValue
{
    public static function fromFinder(SettlementFinderInterface $finder)
    {
        $settlement = $finder->getSettlement();

        if ($settlement) {
            return [
                'name' => $settlement->full,
                'value' => $settlement->ref,
                'meta' => [
                    'name' => $settlement->name,
                    'area' => $settlement->area,
                    'region' => $settlement->region
                ]
            ];
        }

        return [
            'name' => '',
            'value' => '',
            'meta' => [
                'name' => '',
                'area' => '',
                'region' => ''
            ]
        ];
    }
}