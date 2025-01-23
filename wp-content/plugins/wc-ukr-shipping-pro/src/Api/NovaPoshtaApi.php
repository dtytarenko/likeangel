<?php

namespace kirillbdev\WCUkrShipping\Api;

use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\Contracts\ApiResponseInterface;
use kirillbdev\WCUkrShipping\Contracts\HttpRequest;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Http\Response\CollectionResponse;
use kirillbdev\WCUkrShipping\Http\Response\ErrorResponse;
use kirillbdev\WCUkrShipping\Http\Response\ExceptionResponse;

class NovaPoshtaApi
{
    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct()
    {
        $this->request = WCUkrShipping::instance()->singleton(HttpRequest::class);

        $this->apiKey = get_option('wc_ukr_shipping_np_api_key', '');
    }

    /**
     * @throws ApiServiceException
     */
    public function getAreas(): array
    {
        $data['modelName'] = 'Address';
        $data['calledMethod'] = 'getAreas';
        $data['apiKey'] = $this->apiKey;

        return $this->sendRequest($data);
    }

    /**
     * @param int $page
     *
     * @return ApiResponseInterface
     */
    public function getCities($page)
    {
        $data['modelName'] = 'Address';
        $data['calledMethod'] = 'getCities';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'Page' => $page,
            'Limit' => apply_filters('wcus_api_city_limit', 500)
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param int $page
     *
     * @return ApiResponseInterface
     */
    public function getWarehouses($page)
    {
        $data['modelName'] = 'AddressGeneral';
        $data['calledMethod'] = 'getWarehouses';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'Page' => $page,
            'Limit' => apply_filters('wcus_api_warehouse_limit', 500)
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param float $weight
     * @param float $cost
     * @param string $cityRecipient
     * @param string $serviceType
     * @param array $seats
     *
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function getDocumentPrice($weight, $cost, $cityRecipient, $serviceType, $seats = [])
    {
        $data['modelName'] = 'InternetDocument';
        $data['calledMethod'] = 'getDocumentPrice';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'Page' => $cityRecipient,
            'CitySender' => wcus_get_option('np_api_cost_city', ''),
            'CityRecipient' => $cityRecipient,
            'Weight' => $weight,
            'ServiceType' => $serviceType,
            'Cost' => $cost,
            'CargoType' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_cargo_type'),
            'RedeliveryCalculate' => [
                'CargoType' => 'Money',
                'Amount' => (int)$cost
            ],
            'OptionsSeat' => [],
        ];

        if (count($seats) > 0) {
            foreach ($seats as $seat) {
                $data['methodProperties']['OptionsSeat'][] = [
                    'cost' => $seat['cost'],
                    'volumetricWidth' => $seat['width'],
                    'volumetricLength' => $seat['length'],
                    'volumetricHeight' => $seat['height'],
                    'weight' => $seat['weight'],
                ];
            }
        } else {
            unset($data['methodProperties']['OptionsSeat']);
        }

        return $this->sendRequest($data);
    }

    /**
     * @throws ApiServiceException
     */
    public function createCounterparty(array $counterpartyData)
    {
        $data['modelName'] = 'Counterparty';
        $data['calledMethod'] = 'save';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = $counterpartyData;

        return $this->sendRequest($data);
    }

    /**
     * @param array $data
     *
     * @throws ApiServiceException
     */
    public function createCounterpartyContact($data)
    {
        $data['modelName'] = 'ContactPerson';
        $data['calledMethod'] = 'save';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = $data;

        return $this->sendRequest($data);
    }

    /**
     * @param string $type
     *
     * @throws ApiServiceException
     */
    public function getCounterparties($type)
    {
        $data['modelName'] = 'Counterparty';
        $data['calledMethod'] = 'getCounterparties';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'CounterpartyProperty' => $type
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param string $counterpartyRef
     *
     * @throws ApiServiceException
     */
    public function getCounterpartyContacts($counterpartyRef)
    {
        $data['modelName'] = 'Counterparty';
        $data['calledMethod'] = 'getCounterpartyContactPersons';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'Ref' => $counterpartyRef
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param $ttnData
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function createTTN($ttnData)
    {
        $data['modelName'] = 'InternetDocument';
        $data['calledMethod'] = 'save';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = $ttnData;

        return $this->sendRequest($data);
    }

    /**
     * @param $ref
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function deleteTTN($ref)
    {
        $data['modelName'] = 'InternetDocument';
        $data['calledMethod'] = 'delete';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'DocumentRefs' => $ref
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param $query
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function searchSettlements($query)
    {
        $data['modelName'] = 'Address';
        $data['calledMethod'] = 'searchSettlements';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'CityName' => $query,
            'Limit' => 20
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param $query
     * @param $ref
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function searchSettlementStreets($query, $ref)
    {
        $data['modelName'] = 'Address';
        $data['calledMethod'] = 'searchSettlementStreets';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'StreetName' => $query,
            'SettlementRef' => $ref,
            'Limit' => 25
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param $documentId
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function getDocumentStatus($documentId)
    {
        $documents = is_array($documentId) ? $documentId : [$documentId];
        $params = [];

        foreach ($documents as $document) {
            $params[] = [
                'DocumentNumber' => $document,
                'Phone' => ''
            ];
        }

        $data['modelName'] = 'TrackingDocument';
        $data['calledMethod'] = 'getStatusDocuments';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'Documents' => $params
        ];

        return $this->sendRequest($data);
    }

    /**
     * New. Make Contact person Address.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws ApiServiceException
     */
    public function createPersonAddress($data)
    {
        $data['modelName'] = 'AddressContactPersonGeneral';
        $data['calledMethod'] = 'save';
        $data['apiKey'] = $this->apiKey;
        $data['methodProperties'] = [
            'SettlementRef' => $data['settlement_ref'],
            'AddressRef' => $data['address_ref'],
            'AddressType' => $data['address_type'],
            'BuildingNumber' => $data['building_number'],
            'ContactPersonRef' => $data['contact_ref'],
            'Flat' => $data['flat']
        ];

        return $this->sendRequest($data);
    }

    /**
     * @param array $data
     * @return mixed
     *
     * @throws ApiServiceException
     */
    private function sendRequest($data)
    {
        $response = $this->request->post('https://api.novaposhta.ua/v2.0/json/', json_encode($data), [
            'Content-Type' => 'application/json'
        ]);

        return json_decode($response, true);
    }
}
