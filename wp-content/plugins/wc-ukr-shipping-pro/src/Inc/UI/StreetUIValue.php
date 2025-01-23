<?php

namespace kirillbdev\WCUkrShipping\Inc\UI;

use kirillbdev\WCUkrShipping\Contracts\Address\StreetFinderInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class StreetUIValue
{
    public static function fromFinder(StreetFinderInterface $finder)
    {
        $street = $finder->getStreet();

        if ($street) {
            return [
                'name' => $street->full,
                'value' => $street->ref,
                'meta' => [
                    'name' => $street->name
                ]
            ];
        }

        return [
            'name' => '',
            'value' => '',
            'meta' => [
                'name' => ''
            ]
        ];
    }
}