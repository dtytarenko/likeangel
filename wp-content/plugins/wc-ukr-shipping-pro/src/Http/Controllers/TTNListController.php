<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\DB\TTNRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;
use kirillbdev\WCUkrShipping\Services\Document\TTNListService;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNListController extends Controller
{
    /**
     * @var TTNListService
     */
    private $ttnListService;

    /**
     * @var NovaPoshtaApi
     */
    private $api;

    public function __construct()
    {
        $this->ttnListService = new TTNListService();
        $this->api = wcus_container_singleton('api');
    }

    /**
     * @param Request $request
     */
    public function ttnList($request)
    {
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'items' => $this->ttnListService->getTTNList()
            ]
        ]);
    }

    /**
     * @param Request $request
     */
    public function updateStatus($request)
    {
        $ttnRepository = new TTNRepository();
        $ttn = $ttnRepository->getTTNById($request->get('ttn_id', ''));

        if ( ! $ttn) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Указаной накладной не существует'
            ]);
        }

        try {
            $response = $this->api->getDocumentStatus($ttn['ttn_id']);

            if ($response['success']) {
                $ttnRepository->updateStatus($ttn['id'], $response['data'][0]['Status'], $response['data'][0]['StatusCode']);

                return $this->jsonResponse([
                    'success' => true,
                    'data' => [
                        'status' => $response['data'][0]['Status']
                    ]
                ]);
            }

            return $this->jsonResponse([
                'success' => false
            ]);
        } catch (ApiServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'exception' => $e->getMessage()
            ]);
        }
    }
}