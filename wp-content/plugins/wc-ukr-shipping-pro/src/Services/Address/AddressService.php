<?php

namespace kirillbdev\WCUkrShipping\Services\Address;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class AddressService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * @var CityRepository
     */
    private $cityRepository;

    /**
     * @var TranslateService
     */
    private $translateService;

    public function __construct()
    {
        $this->api = new NovaPoshtaApi();
        $this->cityRepository = new CityRepository();
        $this->translateService = new TranslateService();
    }

    public function searchSettlements($query)
    {
        $query = WCUSHelper::prepareApiString($query);
        $result = $this->api->searchSettlements($query);

        if ( ! $result['success']) {
            return [];
        }

        return $result['data'][0]['Addresses'];
    }

    public function searchStreets($query, $settlementRef)
    {
        $query = WCUSHelper::prepareApiString($query);
        $result = $this->api->searchSettlementStreets($query, $settlementRef);

        if ( ! $result['success']) {
            return [];
        }

        return $result['data'][0]['Addresses'];
    }

    public function getDefaultCities()
    {
        $locale = preg_replace(
            '/_.+$/',
            '',
            is_admin() ? get_user_locale() : $this->translateService->getCurrentLanguage()
        );

        if ($locale === 'uk') {
            $locale = 'ua';
        }

        return array_map(function($item) use($locale) {
            return [
                'name' => $item[$locale === 'ua' ? 'description' : 'description_ru'],
                'value' => $item['ref']
            ];
        }, WCUSHelper::getDefaultCities());
    }
}