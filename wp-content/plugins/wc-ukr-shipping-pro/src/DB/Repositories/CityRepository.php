<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\Model\Address\City;
use kirillbdev\WCUSCore\Facades\DB;

if ( ! defined('ABSPATH')) {
    exit;
}

class CityRepository
{
    public function getCitiesByRefs($refs)
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_cities'))
            ->whereIn('ref', $refs)
            ->get();
    }

    public function searchCitiesByQuery($query)
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_cities'))
            ->whereLike('description', $query . '%')
            ->orWhereLike('description_ru', $query . '%')
            ->orderBy('description')
            ->get();
    }

    public function getCityByRef($ref)
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_cities'))
            ->where('ref', $ref)
            ->first();
    }

    public function clearCities()
    {
        DB::table(DB::prefixedTable('wc_ukr_shipping_np_cities'))->truncate();
    }

    /**
     * @param City[] $cities
     * @return void
     */
    public function bulkUpsertCities(array $cities): void
    {
        if (count($cities) === 0) {
            return;
        }

        global $wpdb;
        $insertValues = [];

        foreach ($cities as $city) {
            $values = esc_sql([
                $city->getRef(),
                $city->getNameUa(),
                $city->getNameRu(),
                $city->getAreaRef(),
            ]);
            $values = array_map(function (string $value) {
                return "'$value'";
            }, $values);
            $insertValues[] = '(' . implode(',', $values) . ')';
        }

        $wpdb->query(
            "INSERT INTO `{$wpdb->prefix}wc_ukr_shipping_np_cities` 
                    (`ref`, `description`, `description_ru`, `area_ref`)
                VALUES " . implode(',', $insertValues) . " 
                ON DUPLICATE KEY UPDATE 
                    `description` = VALUES(`description`),
                    `description_ru` = VALUES(`description_ru`),
                    `area_ref` = VALUES(`area_ref`)"
        );
    }
}