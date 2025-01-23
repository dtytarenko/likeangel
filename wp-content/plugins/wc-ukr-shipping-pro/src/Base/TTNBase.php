<?php

namespace kirillbdev\WCUkrShipping\Base;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Http\Response;
use kirillbdev\WCUkrShipping\Model\TTNFormData;

if (!defined('ABSPATH')) {
    exit;
}

abstract class TTNBase
{
    /**
     * @var NovaPoshtaApi
     */
    protected $api;

    /**
     * @var array
     */
    protected $data;

    public function __construct()
    {
        $this->api = WCUkrShipping::instance()->singleton('api');
        $this->data = [];
    }

    /**
     * @param TTNFormData $data
     */
    abstract protected function collectSenderData($data);

    /**
     * @param TTNFormData $data
     */
    abstract protected function collectRecipientData($data);

    /**
     * @param TTNFormData $data
     *
     * @return mixed
     */
    public function save($data)
    {
        try {
            $this->collectMainData($data);
            $this->collectSenderData($data);
            $this->collectRecipientData($data);

            $response = $this->api->createTTN($this->data);

            if ( ! $response['success']) {
                Response::makeAjax('error', $this->mapTTNErrors($response['errorCodes'], $response['errors']));
            }

            return $response['data'][0];
        } catch (ApiServiceException $e) {
            Response::makeAjax('error', [$e->getMessage()]);
        }
    }

    /**
     * @param TTNFormData $data
     */
    protected function collectMainData($data)
    {
        $this->data = [
            'PayerType' => $data->getPayerType(),
            'PaymentMethod' => $data->getPaymentMethod(),
            'DateTime' => $data->getDate(),
            'CargoType' => $data->getCargoType(),
            'ServiceType' => $data->getServiceType(),
            'SeatsAmount' => $data->getSeatsAmount(),
            'Description' => $data->getDescription(),
            'Cost' => $data->getCost()
        ];

        if ((int)$data->getCommonData('poshtomat_delivery', 0)) {
            $seat = $data->getCommonData('poshtomat', []);

            $this->data['OptionsSeat'] = [
                [
                    'volumetricWidth' => $seat['width'],
                    'volumetricLength' => $seat['length'],
                    'volumetricHeight' => $seat['height'],
                    'weight' => $seat['weight']
                ]
            ];
        } else {
            if ((int)$data->getCommonData('global_params', 0)) {
                $this->data['Weight'] = $data->getWeight();

                if ($data->isVolumeEnable()) {
                    $this->data['VolumeGeneral'] = $data->getVolumeWeight();
                }
            }
            else {
                $totalWeight = 0;
                foreach ($data->getCommonData('seats', []) as $seat) {
                    $totalWeight += (float)$seat['weight'];
                    $this->data['OptionsSeat'][] = [
                        'volumetricWidth' => $seat['width'],
                        'volumetricLength' => $seat['length'],
                        'volumetricHeight' => $seat['height'],
                        'weight' => $seat['weight'],
                        'specialCargo' => $seat['box'],
                    ];
                }
                $this->data['Weight'] = $totalWeight;
            }
        }

        if ($data->getBarcode()) {
            $this->data['InfoRegClientBarcodes'] = $data->getBarcode();
        }

        if ($data->getAdditionalInformation()) {
            $this->data['AdditionalInformation'] = $data->getAdditionalInformation();
        }

        if ($data->needPaymentControl()) {
            $this->data['AfterpaymentOnGoodsCost'] = $data->getPaymentControlCost();
        }
        elseif ($data->needBackwardDelivery()) {
            $this->data['BackwardDeliveryData'] = [
                [
                    'PayerType' => $data->getBackwardPayerType(),
                    'CargoType' => $data->getBackwardCargoType(),
                    'RedeliveryString' => $data->getBackwardCost()
                ]
            ];
        }

        if (in_array($data->getServiceType(), ['WarehouseDoors', 'DoorsDoors', 'DoorsWarehouse'])) {
            $this->data['NewAddress'] = 1;
        }
    }

    /**
     * todo: refactor
     *
     * @param $codes
     * @param $messages
     *
     * @return mixed
     */
    protected function mapTTNErrors($codes, $messages)
    {
        return array_values($messages);
    }
}