<?php

namespace kirillbdev\WCUkrShipping\Services\Document;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class CounterpartyService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    public function __construct()
    {
        $this->api = wcus_container_singleton('api');
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws ApiServiceException
     */
    public function createFromRequest($request)
    {
        $response = $this->api->createCounterparty([
            'FirstName' => WCUSHelper::prepareApiString($request->get('firstname')),
            'LastName' => WCUSHelper::prepareApiString($request->get('lastname')),
            'MiddleName' => WCUSHelper::prepareApiString($request->get('middlename', '')),
            'Phone' => $request->get('phone'),
            'Email' => $request->get('email'),
            'CounterpartyType' => 'PrivatePerson',
            'CounterpartyProperty' => 'Recipient'
        ]);

        if ( ! $response['success']) {
            return [
                'errors' => $response['errors']
            ];
        }

        return [
            'ref' => $response['data'][0]['Ref'],
            'contact_ref' => $response['data'][0]['ContactPerson']['data'][0]['Ref']
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws ApiServiceException
     */
    public function createContactFromRequest($request)
    {
        $response = $this->api->createCounterpartyContact([
            'CounterpartyRef' => $request->get('ref'),
            'FirstName' => WCUSHelper::prepareApiString($request->get('firstname')),
            'LastName' => WCUSHelper::prepareApiString($request->get('lastname')),
            'MiddleName' => WCUSHelper::prepareApiString($request->get('middlename', '')),
            'Phone' => $request->get('phone')
        ]);

        if ( ! $response['success']) {
            return [
                'errors' => $response['errors']
            ];
        }

        return [
            'contact_ref' => $response['data'][0]['Ref']
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws ApiServiceException
     */
    public function createOrganizationFromRequest($request)
    {
        $response = $this->api->createCounterparty([
            'EDRPOU' => $request->get('edrpou'),
            'CounterpartyType' => 'Organization',
            'CounterpartyProperty' => 'Recipient'
        ]);

        if ( ! $response['success']) {
            return [];
        }

        return [
            'ref' => $response['data'][0]['Ref'],
            'name' => $response['data'][0]['FirstName'],
            'ownership' => $response['data'][0]['OwnershipFormDescription'],
            'ownership_form' => $response['data'][0]['OwnershipForm'],
            'edrpou' => $response['data'][0]['EDRPOU']
        ];
    }
}