<?php

namespace kirillbdev\WCUkrShipping\Http;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Services\AddressService;

if ( ! defined('ABSPATH')) {
    exit;
}

class AdminAjax
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * @var NovaPoshtaRepository
     */
    private $npRepository;

    public function __construct()
    {
        $this->api = WCUkrShipping::instance()->singleton('api');
        $this->npRepository = WCUkrShipping::instance()->make('np_repository');

        if (wp_doing_ajax()) {
            $this->initRoutes();
        }
    }

    private function initRoutes()
    {
        add_action('wp_ajax_wcus_api_v1_get_cities', [ $this, 'apiV1GetCities' ]);
        add_action('wp_ajax_wcus_api_v1_get_warehouses', [ $this, 'apiV1GetWarehouses' ]);
        add_action('wp_ajax_wcus_api_v1_search_settlements', [ $this, 'apiV1SearchSettlements' ]);
        add_action('wp_ajax_wcus_api_v1_search_streets', [ $this, 'apiV1SearchStreets' ]);
    }

    public function apiV1GetCities()
    {
        $this->verifyApiRequest();
        $cities = $this->npRepository->getCities($_POST['ref']);

        Response::makeAjax('success', $cities);
    }

    public function apiV1GetWarehouses()
    {
        $this->verifyApiRequest();
        $cities = $this->npRepository->getWarehouses($_POST['ref']);

        Response::makeAjax('success', $cities);
    }

    public function apiV1SearchSettlements()
    {
        $this->verifyApiRequest();

        try {
            $addressService = new AddressService($this->api);
            $result = $addressService->searchSettlementsV2($_POST['query']);

            Response::makeAjax('success', $result);
        }
        catch (ApiServiceException $e) {
            Response::makeException($e->getMessage());
        }
    }

    public function apiV1SearchStreets()
    {
        $this->verifyApiRequest();

        try {
            $addressService = new AddressService($this->api);
            $result = $addressService->searchStreets($_POST['query'], $_POST['settlement_ref']);

            Response::makeAjax('success', $result);
        }
        catch (ApiServiceException $e) {
            Response::makeException($e->getMessage());
        }
    }

    private function verifyApiRequest()
    {
        if (empty($_POST['_token']) || ! wp_verify_nonce($_POST['_token'], 'wc-ukr-shipping')) {
            Response::makeAjax('error');
        }
    }
}