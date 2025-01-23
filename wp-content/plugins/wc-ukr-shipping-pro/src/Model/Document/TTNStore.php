<?php

namespace kirillbdev\WCUkrShipping\Model\Document;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Model\OrderProduct;
use kirillbdev\WCUkrShipping\Services\Address\AddressService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNStore
{
    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var TranslateService
     */
    private $translateService;

    /**
     * @var \WC_Order_Item_Shipping
     */
    private $orderShipping;

    /**
     * @var OrderProduct[]
     */
    private $orderProducts = [];

    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * @var array
     */
    private $data = [];

    /**
     * TTNStore constructor.
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->order = wc_get_order((int)$orderId);
        $this->translateService = wcus_container_singleton('translate_service');

        if ( ! $this->order) {
            throw new \InvalidArgumentException('Order #' . (int)$orderId . ' not found.');
        }

        $this->orderShipping = WCUSHelper::getOrderShippingMethod($this->order);
        $this->api = new NovaPoshtaApi();
        $factory = new ProductFactory();

        foreach ($this->order->get_items() as $item) {
            /** @var \WC_Order_Item_Product $item */
            $product = $factory->makeOrderItemProduct($item);
            $this->orderProducts[] = $product;
        }
    }

    public function collect()
    {
        $this->collectCommonData();
        $this->collectSeatsData();
        $this->calculateVolumeWeight();
        $this->calculateCost();
        $this->collectBackwardDelivery();
        $this->collectPaymentControl();
        $this->collectSender();
        $this->collectRecipient();
        $this->collectHelpers();

        return apply_filters('wcus_collect_ttn_form', $this->data, $this->order);
    }

    private function collectCommonData()
    {
        $payerType = apply_filters(
            'wcus_ttn_form_payer_type',
            wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default'),
            $this->order
        );
        if (!in_array($payerType, ['Sender', 'Recipient'], true)) {
            throw new \InvalidArgumentException("Invalid param `payerType`");
        }

        $paymentMethod = apply_filters('wcus_ttn_form_payment_method', 'Cash', $this->order);
        if (!in_array($paymentMethod, ['Cash', 'NonCash'], true)) {
            throw new \InvalidArgumentException("Invalid param 'paymentMethod'");
        }

        $volumeEnable = apply_filters('wcus_ttn_form_volume_enable', false, $this->order);
        if (!is_bool($volumeEnable)) {
            throw new \InvalidArgumentException("Parameter 'volumeEnable' must be bool");
        }

        $date = apply_filters('wcus_ttn_form_date', new \DateTime(), $this->order);
        if (!($date instanceof \DateTimeInterface)) {
            throw new \InvalidArgumentException("Parameter 'date' must be correct date");
        }

        $cargoType = apply_filters('wcus_ttn_form_cargo_type', 'Parcel', $this->order);
        $validCargo = [
            'Cargo',
            'Documents',
            'TiresWheels',
            'Pallet',
            'Parcel',
        ];
        if (!in_array($cargoType, $validCargo, true)) {
            throw new \InvalidArgumentException("Invalid parameter 'cargoType'");
        }

        $seatsAmount = apply_filters('wcus_ttn_form_seats_amount', 1, $this->order);
        if (!is_int($seatsAmount) || $seatsAmount < 1) {
            throw new \InvalidArgumentException("Invalid parameter 'seatsAmount'");
        }

        $this->data['ttn'] = [
            'order_id' => $this->order->get_id(),
            'payer_type' => $payerType,
            'payment_method' => $paymentMethod,
            'volume_enable' => $volumeEnable,
            'global_params' => 1,
            'weight' => $this->calculateWeight(),
            'date' => $date->format('Y-m-d'),
            'cargo_type' => $cargoType,
            'seats_amount' => $seatsAmount,
            'description' => apply_filters('wcus_ttn_form_description', wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_description'), $this->order),
            'barcode' => apply_filters('wcus_ttn_form_barcode', '', $this->order),
            'additional' => apply_filters('wcus_ttn_form_additional', '', $this->order)
        ];
    }

    private function collectSeatsData()
    {
        $dimensions = $this->getTotalDimensions();

        $this->data['ttn']['seats'] = [
            [
                'id' => 0,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'length' => $dimensions['length'],
                'weight' => $this->calculateWeight(),
                'box' => 0
            ]
        ];
    }

    /**
     * @return float
     */
    private function calculateWeight()
    {
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        $limitWeight = max(0.1, (float)wcus_get_option('ttn_weight_default'));

        return max($weight, $limitWeight);
    }

    private function calculateVolumeWeight()
    {
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $volume = (float)$product->getWidth() * (float)$product->getHeight() * (float)$product->getLength() / 4000;
            $weight += $volume * $product->getQuantity();
        }

        $this->data['ttn']['volume_weight'] = $weight ? round($weight, 2) : 0.1;
    }

    private function calculateCost()
    {
        $this->data['ttn']['cost'] = apply_filters('wcus_ttn_form_cost', $this->getShipmentCost(), $this->order);
    }

    private function collectBackwardDelivery()
    {
        $codPaymentId = wcus_get_option('cod_payment_id');
        $this->data['ttn']['backward_delivery'] = $codPaymentId && $codPaymentId === $this->order->get_payment_method()
            ? 1
            : 0;
        $this->data['ttn']['backward_delivery_type'] = 'Money';
        $this->data['ttn']['backward_delivery_payer'] = 'Recipient';

        /**
         * Enable third-party code to control cost of COD feature
         * @since 1.16.6
         */
        $cost = apply_filters('wcus_ttn_form_cod_cost', $this->getShipmentCost(), $this->order);
        $this->data['ttn']['backward_delivery_cost'] = ceil($cost);
    }

    private function collectPaymentControl()
    {
        if ((int)wcus_get_option('ttn_pay_control_default') && (int)$this->data['ttn']['backward_delivery']) {
            $this->data['ttn']['backward_delivery'] = 0;
            $this->data['ttn']['payment_control'] = 1;
        }
        else {
            $this->data['ttn']['payment_control'] = 0;
        }

        /**
         * Enable third-party code to control cost of Payment Control feature
         * @since 1.16.6
         */
        $cost = apply_filters('wcus_ttn_form_payment_control_cost', $this->getShipmentCost(), $this->order);
        $this->data['ttn']['payment_control_cost'] = ceil($cost);
    }

    private function collectSender()
    {
        $senderRef = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_ref');
        $senderContactRef = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_contact_ref');

        $counterpartyRef = $senderRef;

        try {
            if (!$senderRef) {
                $response = $this->api->getCounterParties('Sender');

                if ($response && $response['success']) {
                    $counterpartyRef = $response['data'][0]['Ref'];
                }
            }

            $contactsResponse = $this->api->getCounterpartyContacts($counterpartyRef);
            $contact = [];

            if ($contactsResponse['success']) {
                $contact = $contactsResponse['data'][0];

                if ($senderContactRef) {
                    foreach ($contactsResponse['data'] as $data) {
                        if ($data['Ref'] === $senderContactRef) {
                            $contact = $data;

                            break;
                        }
                    }
                }
            }

            $this->data['sender']['exception'] = '';
            $this->data['sender']['ref'] = $counterpartyRef;
            $this->data['sender']['contact_ref'] = $contact['Ref'] ?? '';
            $this->data['sender']['firstname'] = $contact['FirstName'] ?? '';
            $this->data['sender']['lastname'] = $contact['LastName'] ?? '';
            $this->data['sender']['middlename'] = $contact['MiddleName'] ?? '';
            $this->data['sender']['phone'] = $contact['Phones'] ?? '';
        } catch (ApiServiceException $e) {
            $this->data['sender']['exception'] = 'ApiServiceException: ' . $e->getMessage();
        }

        $this->data['sender']['area_ref'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_area');
        $this->data['sender']['city_ref'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_city');
        $this->data['sender']['warehouse_ref'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_warehouse');
        $this->data['sender']['service_type'] = 'Address' === wc_ukr_shipping_get_option('wcus_send_from_default')
            ? 'Doors'
            : 'Warehouse';

        $this->data['sender']['settlement'] = [
            'value' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_ref'),
            'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_name')),
                'area' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_area')),
                'region' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_region'))
            ]
        ];

        $this->data['sender']['street'] = [
            'value' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_ref'),
            'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_name'))
            ]
        ];

        $this->data['sender']['house'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_house');
        $this->data['sender']['flat'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat');
        $this->data['sender']['default_city'] = [
            'name' => '',
            'value' => '',
        ];
        $this->data['sender']['default_warehouse'] = [
            'name' => '',
            'value' => ''
        ];

        /** @var AddressProviderInterface $addressProvider */
        $addressProvider = wcus_container()->make(AddressProviderInterface::class);
        if ($this->data['sender']['city_ref']) {
            $city = $addressProvider->searchCityByRef($this->data['sender']['city_ref']);
            if ($city !== null) {
                $this->data['sender']['default_city'] = [
                    'name' => $this->translateService->translateCityName($city),
                    'value' => $city->getRef(),
                ];
            }
        }

        if ($this->data['sender']['warehouse_ref']) {
            $warehouse = $addressProvider->searchWarehouseByRef($this->data['sender']['warehouse_ref']);
            if ($warehouse !== null) {
                $this->data['sender']['default_warehouse'] = [
                    'name' => $this->translateService->translateWarehouseName($warehouse),
                    'value' => $warehouse->getRef(),
                ];
            }
            $this->checkPoshtomatDelivery($this->data['sender']['warehouse_ref']);
        }
    }

    private function collectRecipient()
    {
        $maybeDifferentAddress = (int)$this->order->get_meta('wc_ukr_shipping_np_different_address');

        $data['firstname'] = $maybeDifferentAddress
            ? $this->order->get_shipping_first_name()
            : $this->order->get_billing_first_name();

        $data['lastname'] = $maybeDifferentAddress
            ? $this->order->get_shipping_last_name()
            : $this->order->get_billing_last_name();

        $data['middlename'] = $this->order->get_meta('wcus_middlename');

        $data['phone'] = $this->order->get_billing_phone();
        if ($maybeDifferentAddress && $this->order->get_meta('wcus_shipping_phone')) {
            $data['phone'] = $this->order->get_meta('wcus_shipping_phone');
        }

        $data['phone'] = apply_filters(
            'wcus_ttn_form_recipient_phone',
            WCUSHelper::preparePhone($data['phone']),
            $this->order
        );
        $data['email'] = $this->order->get_billing_email();

        $this->data['recipient']['firstname'] = $data['firstname'];
        $this->data['recipient']['lastname'] = $data['lastname'];
        $this->data['recipient']['middlename'] = $data['middlename'];
        $this->data['recipient']['phone'] = $data['phone'];
        $this->data['recipient']['email'] = $data['email'];
        $this->data['recipient']['type'] = 'private_person';

        $this->data['recipient']['edrpou'] = '';
        $this->data['recipient']['organization_ref'] = '';
        $this->data['recipient']['organization_ownership'] = '';
        $this->data['recipient']['organization_ownership_form'] = '';
        $this->data['recipient']['organization_name'] = '';

        $shippingAddress = $this->order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME)
            ? new ShippingRecipientAddress($this->order, $this->orderShipping)
            : new CustomRecipientAddress($this->order);

        $shippingAddress->writeData($this->data);

        if ($this->order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME)) {
            $warehouseRef = $this->orderShipping->get_meta('wcus_warehouse_ref');
            if ($warehouseRef) {
                $this->checkPoshtomatDelivery($warehouseRef);
            }
        }
    }

    private function collectHelpers()
    {
        // todo: refactor (same in ShippingCostState.php)
        $addressService = new AddressService();

        $this->data['helpers']['default_cities'] = $addressService->getDefaultCities();
    }

    private function getTotalDimensions()
    {
        $width = 0;
        $height = 0;
        $length = 0;

        foreach ($this->orderProducts as $product) {
            $width += $product->getWidth() * $product->getQuantity();
            $height += $product->getHeight() * $product->getQuantity();
            $length += $product->getLength() * $product->getQuantity();
        }

        return [
            'width' => $width,
            'height' => $height,
            'length' => $length
        ];
    }

    private function getShipmentCost(): float
    {
        return $this->order->get_subtotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->order->get_total_discount();
    }

    private function checkPoshtomatDelivery(string $warehouseRef): void
    {
        /** @var AddressProviderInterface $addressProvider */
        $addressProvider = wcus_container()->make(AddressProviderInterface::class);
        $warehouse = $addressProvider->searchWarehouseByRef($warehouseRef);
        if ($warehouse !== null) {
            if (false !== strpos($warehouse->getNameUa(), 'Поштомат') || false !== strpos($warehouse->getNameRu(), 'Почтомат')) {
                $this->data['ttn']['global_params'] = 0;
            }
        }
    }
}
