<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Http\Response;

if (!defined('ABSPATH')) {
    exit;
}

class AddressService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    public function __construct($api)
    {
        $this->api = $api;
    }

    public function searchSettlements($query)
    {
        $query = WCUSHelper::prepareApiString($query);

        try {
            $result = $this->api->searchSettlements($query);

            if ($result['success']) {
                Response::makeAjax('success', $result['data'][0]['Addresses']);
            }

            Response::makeAjax('error');
        } catch (ApiServiceException $e) {
            Response::makeException($e->getMessage());
        }
    }

    /**
     * @param $query
     * @throws ApiServiceException
     */
    public function searchSettlementsV2($query)
    {
        $query = WCUSHelper::prepareApiString($query);
        $result = $this->api->searchSettlements($query);

        if ($result['success']) {
            $settlements = [];

            foreach ($result['data'][0]['Addresses'] as $address) {
                $settlements[] = [
                    'ref' => $address['Ref'],
                    'name' => $address['MainDescription'],
                    'area' => $address['Area'],
                    'region' => $address['Region'],
                    'full' => $address['Present']
                ];
            }

            return $settlements;
        }

        return [];
    }

    /**
     * Legacy
     *
     * @param $query
     * @param $ref
     */
    public function searchSettlementStreets($query, $ref)
    {
        $query = WCUSHelper::prepareApiString($query);

        try {
            $result = $this->api->searchSettlementStreets($query, $ref);

            if ($result['success']) {
                Response::makeAjax('success', $result['data'][0]['Addresses']);
            }

            Response::makeAjax('error');
        } catch (ApiServiceException $e) {
            Response::makeException($e->getMessage());
        }
    }

    /**
     * @param string $query
     * @param string $ref
     *
     * @return array
     *
     * @throws ApiServiceException
     */
    public function searchStreets($query, $ref)
    {
        $query = WCUSHelper::prepareApiString($query);
        $result = $this->api->searchSettlementStreets($query, $ref);

        if ($result['success']) {
            $streets = [];

            foreach ($result['data'][0]['Addresses'] as $address) {
                $streets[] = [
                    'ref' => $address['SettlementStreetRef'],
                    'name' => $address['SettlementStreetDescription'],
                    'full' => $address['Present']
                ];
            }

            return $streets;
        }

        return [];
    }
}