<?php

namespace kirillbdev\WCUkrShipping\Http\Api;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\Http\Response;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class ApiV2
{
    /**
     * @var NovaPoshtaRepository
     */
    private $novaPoshtaRepository;

    /**
     * @var TranslateService
     */
    private $translator;

    public function __construct()
    {
        $this->novaPoshtaRepository = new NovaPoshtaRepository();
        $this->translator = WCUkrShipping::instance()->singleton('translate_service');
    }

    public function initRoutes()
    {
        add_action('wp_ajax_wcus_api_v2_get_cities', [ $this, 'getCities' ]);
        add_action('wp_ajax_nopriv_wcus_api_v2_get_cities', [ $this, 'getCities' ]);

        add_action('wp_ajax_wcus_api_v2_get_warehouses', [ $this, 'getWarehouses' ]);
        add_action('wp_ajax_nopriv_wcus_api_v2_get_warehouses', [ $this, 'getWarehouses' ]);
    }

    public function getCities()
    {
        $this->validateRequest();

        if (empty($_POST['value'])) {
            Response::makeAjax('error');
        }

        // todo: refactor
        $cities = $this->novaPoshtaRepository->getCities($_POST['value']);
        $result = [];

        if ($cities ) {
            $result = array_map(function ($city) {
                return [
                    'name' => $this->translator->translateCity($city),
                    'value' => $city['ref']
                ];
            }, $cities);
        }

        Response::makeAjax('success', [
            'items' => $result
        ]);
    }

    public function getWarehouses()
    {
        $this->validateRequest();

        if (empty($_POST['value'])) {
            Response::makeAjax('error');
        }

        // todo: refactor
        $warehouses = $this->novaPoshtaRepository->getWarehouses($_POST['value']);
        $result = [];

        if ($warehouses ) {
            foreach ($warehouses as $warehouse) {
                $result[] = [
                    'name' => $this->translator->translateWarehouse($warehouse),
                    'value' => $warehouse['ref']
                ];
            }
        }

        Response::makeAjax('success', [
            'items' => $result
        ]);
    }

    private function validateRequest()
    {
        if (empty($_POST['_token']) || ! wp_verify_nonce($_POST['_token'], 'wc-ukr-shipping')) {
            Response::makeAjax('error');
        }
    }
}