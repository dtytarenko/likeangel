<?php

namespace kirillbdev\WCUkrShipping\Services\Invoice;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\DoorsAddress;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\Settlement;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\WarehouseAddress;
use kirillbdev\WCUkrShipping\Model\Invoice\Sender;

if ( ! defined('ABSPATH')) {
    exit;
}

class SenderService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * SenderService constructor.
     * @param NovaPoshtaApi $api
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * @return Sender
     * @throws ApiServiceException
     */
    public function getSenderInfo()
    {
        $address = $this->getSenderAddress();
        $sender = new Sender(
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_ref'),
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_contact_ref'),
            $address
        );

        $response = $this->api->getCounterpartyContacts($sender->ref);

        if ($response['success']) {
            $contact = $response['data'][0];

            $sender->firstname = $contact['FirstName'];
            $sender->lastname = $contact['LastName'];
            $sender->middlename = $contact['MiddleName'];
            $sender->phone = $contact['Phones'];
        }

        return $sender;
    }

    /**
     * @return DoorsAddress|WarehouseAddress
     */
    private function getSenderAddress()
    {
        if ('Address' === wc_ukr_shipping_get_option('wcus_send_from_default')) {
            return new DoorsAddress(
                new Settlement(
                    $this->sanitize(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_name')),
                    $this->sanitize(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_area')),
                    $this->sanitize(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_region'))
                ),
                $this->sanitize(wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_name')),
                wc_ukr_shipping_get_option('wc_ukr_shipping_np_house'),
                wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat')
            );
        }
        else {
            return new WarehouseAddress(
                wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_city'),
                wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_warehouse')
            );
        }
    }

    /**
     * @param string $str
     * @return string
     */
    private function sanitize($str)
    {
        return WCUSHelper::prepareApiString(WCUSHelper::prepareUIString($str));
    }
}