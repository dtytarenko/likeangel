<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;
use kirillbdev\WCUkrShipping\Services\Document\CounterpartyService;

if ( ! defined('ABSPATH')) {
    exit;
}

class CounterpartyController extends Controller
{
    /**
     * @var CounterpartyService
     */
    private $counterpartyService;

    public function __construct()
    {
        $this->counterpartyService = new CounterpartyService();
    }

    /**
     * @param Request $request
     */
    public function create($request)
    {
        try {
            $result = $this->counterpartyService->createFromRequest($request);

            if ( ! empty($result['errors'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $result['errors']
                ]);
            }
            else {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            }
        }
        catch (ApiServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * @param Request $request
     */
    public function createContact($request)
    {
        try {
            $result = $this->counterpartyService->createContactFromRequest($request);

            if ( ! empty($result['errors'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => $result['errors']
                ]);
            }
            else {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            }
        }
        catch (ApiServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * @param Request $request
     */
    public function createOrganization($request)
    {
        try {
            $result = $this->counterpartyService->createOrganizationFromRequest($request);

            return $this->jsonResponse([
                'success' => true,
                'data' => $result
            ]);
        }
        catch (ApiServiceException $e) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    $e->getMessage()
                ]
            ]);
        }
    }
}