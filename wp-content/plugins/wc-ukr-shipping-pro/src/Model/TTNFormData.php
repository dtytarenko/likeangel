<?php

namespace kirillbdev\WCUkrShipping\Model;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;

if (!defined('ABSPATH')) {
    exit;
}

class TTNFormData
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function has($key)
    {
        return !empty($this->data[$key]);
    }

    /**
     * @return string
     */
    public function getServiceType()
    {
        return $this->data['sender']['service_type'] . $this->data['recipient']['service_type'];
    }

    /**
     * @return string
     */
    public function getRecipientFirstname()
    {
        return WCUSHelper::prepareApiString($this->getRecipientData('firstname'));
    }

    /**
     * @return string
     */
    public function getRecipientLastname()
    {
        return WCUSHelper::prepareApiString($this->getRecipientData('lastname'));
    }

    /**
     * @return string
     */
    public function getRecipientMiddlename()
    {
        return WCUSHelper::prepareApiString($this->getRecipientData('middlename'));
    }

    /**
     * @return string
     */
    public function getRecipientPhone()
    {
        return $this->getRecipientData('phone');
    }

    /**
     * @return string
     */
    public function getRecipientEmail()
    {
        return $this->getRecipientData('email');
    }

    /**
     * @return string
     */
    public function getPayerType()
    {
        return $this->getCommonData('payer_type');
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getCommonData('payment_method');
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return date('d.m.Y', strtotime($this->getCommonData('date')));
    }

    /**
     * @return string
     */
    public function getCargoType()
    {
        return $this->getCommonData('cargo_type');
    }

    /**
     * @return int|float
     */
    public function getWeight()
    {
        return $this->getCommonData('weight', 0);
    }

    /**
     * @return int
     */
    public function getSeatsAmount()
    {
        return $this->getCommonData('seats_amount', 1);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getCommonData('description');
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->getCommonData('cost', 0);
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->getSenderData('ref');
    }

    /**
     * @return string
     */
    public function getContactSender()
    {
        return $this->getSenderData('contact_ref');
    }

    /**
     * @return string
     */
    public function getSenderPhone()
    {
        return $this->getSenderData('phone');
    }

    public function getSenderCity()
    {
        return $this->getSenderData('city_ref');
    }

    public function getSenderAddress()
    {
        return $this->getSenderData('warehouse_ref');
    }

    public function getSenderSettlementName()
    {
        return WCUSHelper::prepareApiString(
            $this->getSenderData('settlement_name')
        );
    }

    public function getSenderSettlementArea()
    {
        return WCUSHelper::prepareApiString(
            $this->getSenderData('settlement_area')
        );
    }

    public function getSenderSettlementRegion()
    {
        return WCUSHelper::prepareApiString(
            $this->getSenderData('settlement_region')
        );
    }

    public function getSenderStreet()
    {
        return WCUSHelper::prepareApiString(
            $this->getSenderData('street_name')
        );
    }

    public function getSenderHouse()
    {
        return $this->getSenderData('house');
    }

    public function getSenderFlat()
    {
        return $this->getSenderData('flat');
    }

    public function getRecipientCity()
    {
        return $this->getRecipientData('city_ref');
    }

    public function getRecipientAddress()
    {
        return $this->getRecipientData('warehouse_ref');
    }

    public function getRecipientSettlementName()
    {
        return WCUSHelper::prepareApiString(
            $this->getRecipientData('settlement_name')
        );
    }

    public function getRecipientSettlementArea()
    {
        return WCUSHelper::prepareApiString(
            $this->getRecipientData('settlement_area')
        );
    }

    public function getRecipientSettlementRegion()
    {
        return WCUSHelper::prepareApiString(
            $this->getRecipientData('settlement_region')
        );
    }

    public function getRecipientStreet()
    {
        return WCUSHelper::prepareApiString(
            $this->getRecipientData('street_name')
        );
    }

    public function getRecipientHouse()
    {
        return $this->getRecipientData('house');
    }

    public function getRecipientFlat()
    {
        return $this->getRecipientData('flat');
    }

    public function isVolumeEnable()
    {
        return 'true' === $this->getCommonData('volume_enable');
    }

    public function getVolumeWeight()
    {
        return (float)$this->getCommonData('volume_weight') * 0.004;
    }

    public function getBarcode()
    {
        return $this->getCommonData('barcode');
    }

    public function getAdditionalInformation()
    {
        return $this->getCommonData('additional');
    }

    public function needBackwardDelivery()
    {
        return (int)$this->getCommonData('backward_delivery');
    }

    public function getBackwardPayerType()
    {
        return $this->getCommonData('backward_delivery_payer');
    }

    public function getBackwardCargoType()
    {
        return $this->getCommonData('backward_delivery_type');
    }

    public function getBackwardCost()
    {
        return (int)$this->getCommonData('backward_delivery_cost', 0);
    }

    public function needPaymentControl()
    {
        return (int)$this->getCommonData('payment_control');
    }

    public function getPaymentControlCost()
    {
        return $this->getCommonData('payment_control_cost');
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getValue($key, $default = '')
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCommonData($key, $default = '')
    {
        return isset($this->data['ttn'][ $key ])
            ? $this->data['ttn'][ $key ]
            : $default;
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    public function getSenderData($key, $default = '')
    {
        return isset($this->data['sender'][ $key ])
            ? $this->data['sender'][ $key ]
            : $default;
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    public function getRecipientData($key, $default = '')
    {
        return isset($this->data['recipient'][ $key ])
            ? $this->data['recipient'][ $key ]
            : $default;
    }
}