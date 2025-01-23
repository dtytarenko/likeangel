<?php

namespace kirillbdev\WCUkrShipping\Services\Invoice;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\DoorsAddress;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\Settlement;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\WarehouseAddress;
use kirillbdev\WCUkrShipping\Model\Invoice\Recipient;

if ( ! defined('ABSPATH')) {
    exit;
}

class RecipientService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * RecipientService constructor.
     * @param NovaPoshtaApi $api
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * @param int $orderId
     */
    public function getRecipientFromOrderId($orderId)
    {
        $order = wc_get_order($orderId);
        $maybeDifferentAddress = (int)$order->get_meta('wc_ukr_shipping_np_different_address');

        $firstname = $maybeDifferentAddress
            ? $order->get_shipping_first_name()
            : $order->get_billing_first_name();

        $lastname = $maybeDifferentAddress
            ? $order->get_shipping_last_name()
            : $order->get_billing_last_name();

        $middlename = $order->get_meta('wcus_middlename');

        $phone = $order->get_billing_phone();
        if ($maybeDifferentAddress && $order->get_meta('wcus_shipping_phone')) {
            $phone = $order->get_meta('wcus_shipping_phone');
        }

        $phone = apply_filters(
            'wcus_ttn_form_recipient_phone',
            WCUSHelper::preparePhone($phone),
            $order
        );

        $email = $order->get_billing_email();

        $recipient = new Recipient();
        $recipient->firstname = $this->sanitize($firstname);
        $recipient->lastname = $this->sanitize($lastname);
        $recipient->middlename = $this->sanitize($middlename);
        $recipient->phone = $phone;
        $recipient->email = $email;

        if ($order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME)) {
            $shipping = WCUSHelper::getOrderShippingMethod($order);

            if ($shipping->get_meta('wcus_warehouse_ref')) {
                $address = new WarehouseAddress(
                    $shipping->get_meta('wcus_city_ref'),
                    $shipping->get_meta('wcus_warehouse_ref')
                );
            }
            else {
                $address = new DoorsAddress(
                    new Settlement(
                        $this->sanitize($shipping->get_meta('wcus_settlement_name')),
                        $this->sanitize($shipping->get_meta('wcus_settlement_area')),
                        $this->sanitize($shipping->get_meta('wcus_settlement_region'))
                    ),
                    $this->sanitize($shipping->get_meta('wcus_street_name')),
                    $shipping->get_meta('wcus_house'),
                    $shipping->get_meta('wcus_flat')
                );
            }

            $recipient->address = $address;
        }

        return $recipient;
    }

    /**
     * @param Recipient $recipient
     * @throws ApiServiceException
     */
    public function createPrivatePerson($recipient)
    {
        $response = $this->api->createCounterparty($recipient->serialize());

        if ($response['success']) {
            $recipient->ref = $response['data'][0]['Ref'];
            $recipient->contactRef = $response['data'][0]['ContactPerson']['data'][0]['Ref'];
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