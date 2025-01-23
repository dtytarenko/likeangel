<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Http\Api\ApiV2;
use kirillbdev\WCUkrShipping\Http\Response;
use kirillbdev\WCUkrShipping\Services\AddressService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class Ajax implements ModuleInterface
{
    private NovaPoshtaApi $api;
    private ApiV2 $ajaxApiV2;
    private TranslateService $translateService;

    public function __construct()
    {
        $this->api = WCUkrShipping::instance()->singleton('api');
        $this->ajaxApiV2 = new ApiV2();
        $this->translateService = wcus_container_singleton('translate_service');
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        if (wp_doing_ajax()) {
            $this->initRoutes();
            $this->ajaxApiV2->initRoutes();
        }
    }

    public function initRoutes()
    {
        add_action('wp_ajax_wc_ukr_shipping_search_settlements', [$this, 'searchSettlements']);
        add_action('wp_ajax_nopriv_wc_ukr_shipping_search_settlements', [$this, 'searchSettlements']);

        add_action('wp_ajax_wc_ukr_shipping_search_settlement_streets', [$this, 'searchSettlementStreets']);
        add_action('wp_ajax_nopriv_wc_ukr_shipping_search_settlement_streets', [$this, 'searchSettlementStreets']);

        add_action('wp_ajax_wc_ukr_shipping_get_counterparty_contacts', [$this, 'getCounterpartyContacts']);
    }

    public function searchSettlements()
    {
        $this->verifyRequest();

        if (empty($_POST['search_query'])) {
            Response::makeAjax('error');
        }

        $addressService = new AddressService($this->api);
        $addressService->searchSettlements($_POST['search_query']);
    }

    public function searchSettlementStreets()
    {
        $this->verifyRequest();

        if (empty($_POST['search_query']) || empty($_POST['ref'])) {
            Response::makeAjax('error');
        }

        $addressService = new AddressService($this->api);
        $addressService->searchSettlementStreets($_POST['search_query'], $_POST['ref']);
    }

    public function getCounterpartyContacts()
    {
        if (empty($_POST['sender_ref'])) {
            Response::makeAjax('error');
        }

        try {
            $response = $this->api->getCounterpartyContacts($_POST['sender_ref']);

            if (!$response['success']) {
                Response::makeAjax('error');
            }

            Response::makeAjax('success', $response['data']);
        } catch (ApiServiceException $e) {
            Response::makeException($e->getMessage());
        }
    }

    /**
     * @deprecated
     */
    private function verifyRequest()
    {
        if (empty($_POST['body']['token']) || !wp_verify_nonce($_POST['body']['token'], 'wc-ukr-shipping')) {
            Response::makeAjax('error');
        }
    }

    private function verifyApiRequest()
    {
        if (empty($_POST['_token']) || ! wp_verify_nonce($_POST['_token'], 'wc-ukr-shipping')) {
            Response::makeAjax('error');
        }
    }
}