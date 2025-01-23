<?php

namespace kirillbdev\WCUkrShipping\Model;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Contracts\AddressInterface;
use kirillbdev\WCUkrShipping\Contracts\OrderDataInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\HardcodedAreaRepository;
use kirillbdev\WCUkrShipping\Services\CalculationService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class WCUSOrder implements OrderInterface
{
    /**
     * @var \WC_Order
     */
    private $wcOrder;

    private AddressProviderInterface $addressProvider;
    private TranslateService $translateService;
    private HardcodedAreaRepository $hardcodedAreaRepository;

    /**
     * @param \WC_Order $wcOrder
     * @param HardcodedAreaRepository
     */
    public function __construct($wcOrder)
    {
        $this->wcOrder = $wcOrder;
        $this->addressProvider = wcus_container()->make(AddressProviderInterface::class);
        $this->translateService = wcus_container()->make(TranslateService::class);
        $this->hardcodedAreaRepository = new HardcodedAreaRepository();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->wcOrder->get_id();
    }

    public function getOrigin(): \WC_Order
    {
        return $this->wcOrder;
    }

    /**
     * @param OrderDataInterface $data
     *
     * @throws \WC_Data_Exception
     */
    public function save($data, bool $forceCalculation = false)
    {
        // todo: refactor this
        if ( ! is_admin()) {
            if ( ! $data->isDifferentAddressShipping()) {
                $this->wcOrder->set_shipping_first_name($this->wcOrder->get_billing_first_name());
                $this->wcOrder->set_shipping_last_name($this->wcOrder->get_billing_last_name());
            } else {
                $this->wcOrder->update_meta_data('wc_ukr_shipping_np_different_address', 1);
            }

            if (1 === (int)wc_ukr_shipping_get_option('wcus_inject_additional_fields')) {
                if ($data->isDifferentAddressShipping()) {
                    $this->wcOrder->set_shipping_phone(esc_attr($_POST['wcus_shipping_phone']));
                    $this->wcOrder->update_meta_data('wcus_shipping_phone', esc_attr($_POST['wcus_shipping_phone']));
                    $this->wcOrder->update_meta_data('wcus_middlename', esc_attr($_POST['wcus_shipping_middlename']));
                } else {
                    $this->wcOrder->update_meta_data('wcus_middlename', esc_attr($_POST['wcus_billing_middlename']));
                }
            }
        }

        $address = $data->getShippingAddress();

        if ($data->isAddressShipping()) {
            $this->saveAddressShipping($address);
        } else {
            $this->saveWarehouseShipping($address);
        }

        if ($forceCalculation) {
            $calculationService = new CalculationService();
            $cost = $calculationService->calculateCost($data);
            $this->wcOrder->set_shipping_total($cost);
            $this->wcOrder->calculate_shipping();
            $this->wcOrder->calculate_totals();
        }

        $this->wcOrder->update_meta_data('wcus_data_version', 2);
    }

    /**
     * @param string $state
     *
     * @throws \WC_Data_Exception
     */
    public function setState($state)
    {
        $state = sanitize_text_field(wp_unslash($state));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $this->wcOrder->set_billing_state($state);
        } else {
            $this->wcOrder->set_shipping_state($state);
        }
    }

    /**
     * @param string $city
     *
     * @throws \WC_Data_Exception
     */
    public function setCity($city)
    {
        $city = sanitize_text_field(wp_unslash($city));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $this->wcOrder->set_billing_city($city);
        } else {
            $this->wcOrder->set_shipping_city($city);
        }
    }

    /**
     * @param string $address
     *
     * @throws \WC_Data_Exception
     */
    public function setAddress($address)
    {
        $address = sanitize_text_field(wp_unslash($address));
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $this->wcOrder->set_billing_address_1($address);
        } else {
            $this->wcOrder->set_shipping_address_1($address);
        }
    }

    /**
     * @param AddressInterface $address
     */
    private function saveAddressShipping($address)
    {
        if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui') || is_admin()) {
            $this->setCity($address->getSettlementInfo('full'));
            $this->setAddress(sprintf(
                '%s, %s%s',
                $address->getStreetInfo('full'),
                $address->getHouse(),
                $address->getFlat() ? (' кв. ' . $address->getFlat()) : ''
            ));
        } else {
            $this->saveCity($address);
            $this->setAddress($address->getCustomAddress());
        }
    }

    private function saveWarehouseShipping(AddressInterface $address): void
    {
        $this->saveCity($address);

        if ($address->getWarehouseName() !== '') { // New UI
            $this->setAddress($address->getWarehouseName());
        } else {
            $warehouse = $this->addressProvider->searchWarehouseByRef($address->getWarehouseRef());
            if ($warehouse !== null) {
                $this->setAddress(
                    $this->translateService->getCurrentLanguage() === 'ua'
                        ? $warehouse->getNameUa()
                        : $warehouse->getNameRu()
                );
            }
        }
    }

    private function saveCity(AddressInterface $address): void
    {
        $city = $this->addressProvider->searchCityByRef($address->getCityRef());
        if ($city !== null) {
            $area = $this->hardcodedAreaRepository->findByRef($city->getAreaRef());
            if ($area !== null) {
                $this->setState(
                    $this->translateService->getCurrentLanguage() === 'ua'
                        ? $area->getNameUa()
                        : $area->getNameRu()
                );
            }
        }

        if ($address->getCityName() !== '') { // New UI
            $this->setCity($address->getCityName());
        } elseif ($city !== null) {
            $this->setCity(
                $this->translateService->getCurrentLanguage() === 'ua' ? $city->getNameUa() : $city->getNameRu()
            );
        }
    }

    public function getCity(): string
    {
        return get_option('woocommerce_ship_to_destination') === 'billing_only'
            ? $this->wcOrder->get_billing_city()
            : $this->wcOrder->get_shipping_city();
    }

    public function getAddress1(): string
    {
        return get_option('woocommerce_ship_to_destination') === 'billing_only'
            ? $this->wcOrder->get_billing_address_1()
            : $this->wcOrder->get_shipping_address_1();
    }
}
