<?php

namespace kirillbdev\WCUkrShipping\Model\Invoice;

use kirillbdev\WCUkrShipping\Contracts\ApiSerializeInterface;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\InvoiceAddress;
use kirillbdev\WCUkrShipping\Model\Invoice\Parameters\InvoiceParameters;

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Class Invoice
 * @property int $id
 * @property int $orderId
 * @property int $documentNumber
 * @property string $ref
 */
class Invoice implements ApiSerializeInterface
{
    public static $SERVICE_WAREHOUSE_WAREHOUSE = 'WarehouseWarehouse';
    public static $SERVICE_WAREHOUSE_DOORS = 'WarehouseDoors';
    public static $SERVICE_DOORS_DOORS = 'DoorsDoors';
    public static $SERVICE_DOORS_WAREHOUSE = 'DoorsWarehouse';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var string
     */
    private $ref;

    /**
     * @var int
     */
    private $documentNumber;

    /**
     * @var InvoiceInfo
     */
    private $info;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var Recipient
     */
    private $recipient;

    private ?BackwardDelivery $backwardDelivery = null;
    private ?PaymentControl $paymentControl = null;

    /**
     * Invoice constructor.
     * @param InvoiceInfo $info
     * @param InvoiceParameters $parameters
     * @param Sender $sender
     * @param Recipient $recipient
     * @param int $orderId
     */
    public function __construct($info, $sender, $recipient, $orderId = 0)
    {
        $this->info = $info;
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->orderId = $orderId;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setRef($ref)
    {
        $this->ref = $ref;
    }

    public function setDocumentNumber($number)
    {
        $this->documentNumber = $number;
    }

    /**
     * @param BackwardDelivery $backwardDelivery
     */
    public function setBackwardDelivery($backwardDelivery)
    {
        $this->backwardDelivery = $backwardDelivery;
    }

    public function setPaymentControl(PaymentControl $paymentControl): void
    {
        $this->paymentControl = $paymentControl;
    }

    /**
     * @return string
     */
    public function getServiceType()
    {
        return $this->sender->address->getType() . $this->recipient->address->getType();
    }

    /**
     * @return array
     */
    public function serialize()
    {
        $data = $this->serializeInfo();
        $data = $this->serializeParameters($data);
        $data = $this->serializeBackwardDelivery($data);
        $data = $this->serializePaymentControl($data);
        $data = $this->serializeSender($data);
        $data = $this->serializeRecipient($data);

        return $data;
    }

    /**
     * @return array
     */
    private function serializeInfo()
    {
        $data = [
            'PayerType' => $this->info->payerType,
            'PaymentMethod' => $this->info->paymentMethod,
            'DateTime' => $this->info->date,
            'CargoType' => $this->info->cargoType,
            'ServiceType' => $this->getServiceType(),
            'Description' => $this->info->description,
            'Cost' => $this->info->cost,
            'InfoRegClientBarcodes' => $this->info->barcode,
            'AdditionalInformation' => $this->info->additional
        ];

        if ($this->needNewAddress()) {
            $data['NewAddress'] = 1;
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function serializeParameters($data)
    {
        $limitWeight = max(0.1, (float)wcus_get_option('ttn_weight_default'));
        $weight = max($this->info->parameters->weight, $limitWeight);

        if (InvoiceParameters::$TYPE_GLOBAL === $this->info->parameters->getType()) {
            $data['SeatsAmount'] = $this->info->parameters->seatsAmount;
            $data['Weight'] = $weight;

            if ($this->info->parameters->volumeEnable) {
                $data['VolumeGeneral'] = $this->info->parameters->volumeWeight;
            }
        }
        elseif (InvoiceParameters::$TYPE_SEAT === $this->info->parameters->getType()) {
            $data['SeatsAmount'] = count($this->info->parameters->seats);
            $data['Weight'] = $this->info->parameters->getTotalWeight();
            $data['OptionsSeat'] = [];

            foreach ($this->info->parameters->seats as $seat) {
                $data['OptionsSeat'][] = [
                    'volumetricWidth' => $seat->width,
                    'volumetricLength' => $seat->length,
                    'volumetricHeight' => $seat->height,
                    'weight' => $seat->weight
                ];
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function serializeBackwardDelivery($data)
    {
        if ($this->backwardDelivery !== null) {
            $data['BackwardDeliveryData'] = [
                [
                    'PayerType' => $this->backwardDelivery->payerType,
                    'CargoType' => $this->backwardDelivery->cargoType,
                    'RedeliveryString' => $this->backwardDelivery->cost
                ]
            ];
        }

        return $data;
    }

    private function serializePaymentControl(array $data): array
    {
        if ($this->paymentControl !== null) {
            $data['AfterpaymentOnGoodsCost'] = $this->paymentControl->getCost();
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function serializeSender($data)
    {
        $data['Sender'] = $this->sender->ref;
        $data['ContactSender'] = $this->sender->contactRef;
        $data['SendersPhone'] = $this->sender->phone;
        $data = array_merge($data, $this->serializeAddress($this->sender->address, 'Sender'));

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function serializeRecipient($data)
    {
        if ($this->needNewAddress()) {
            $data['RecipientName'] = $this->recipient->getFullName();
            $data['RecipientType'] = 'PrivatePerson';
        }
        else {
            $data['Recipient'] = $this->recipient->ref;
            $data['ContactRecipient'] = $this->recipient->contactRef;
        }

        $data['RecipientsPhone'] = $this->recipient->phone;
        $data = array_merge($data, $this->serializeAddress($this->recipient->address, 'Recipient'));

        return $data;
    }

    /**
     * @param InvoiceAddress $address
     * @param string $counterpartyType
     * @return array
     */
    private function serializeAddress($address, $counterpartyType)
    {
        if (InvoiceAddress::$TYPE_WAREHOUSE === $address->getType()) {
            return [
                "City$counterpartyType" => $address->cityRef,
                "{$counterpartyType}Address" => $address->warehouseRef
            ];
        }
        elseif (InvoiceAddress::$TYPE_DOORS === $address->getType()) {
            return [
                "{$counterpartyType}CityName" => $address->getSettlementName(),
                "{$counterpartyType}Area" => $address->getSettlementArea(),
                "{$counterpartyType}AreaRegions" => $address->getSettlementRegion(),
                "{$counterpartyType}AddressName" => $address->streetName,
                "{$counterpartyType}House" => $address->house,
                "{$counterpartyType}Flat" => $address->flat
            ];
        }

        return [];
    }

    /**
     * @return bool
     */
    private function needNewAddress()
    {
        return in_array($this->getServiceType(), [
            Invoice::$SERVICE_WAREHOUSE_DOORS,
            Invoice::$SERVICE_DOORS_DOORS,
            Invoice::$SERVICE_DOORS_WAREHOUSE
        ]);
    }
}