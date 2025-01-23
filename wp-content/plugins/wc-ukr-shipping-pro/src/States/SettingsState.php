<?php

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Exceptions\Cloud\CloudCommunicationException;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\License\Service\LicenseService;
use kirillbdev\WCUkrShipping\Services\Address\AddressService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class SettingsState extends AppState
{
    private AddressProviderInterface $addressProvider;
    private TranslateService $translateService;
    private LicenseService $licenseService;

    public function __construct(TranslateService $translateService, LicenseService $licenseService)
    {
        $this->addressProvider = wcus_container()->make(AddressProviderInterface::class);
        $this->translateService = $translateService;
        $this->licenseService = $licenseService;
    }

    protected function getState()
    {
        return [
            'shipping_cost' => $this->getShippingCostState(),
            'address_cost' => $this->getAddressCostState(),
            'cod' => $this->getCODState(),
            'ttn' => $this->getTTNState(),
            'freeShippingTitle' => $this->getFreeShippingTitle(),
            'license' => $this->getLicenseState(),
        ];
    }

    private function getShippingCostState()
    {
        $totalCost = wc_ukr_shipping_get_option('wc_ukr_shipping_np_relative_price');

        $state = [
            'calc_type' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_price_type'),
            'fixed_price' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_price'),
            'cargo_type' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_cargo_type'),
            'default_cities' => $this->getDefaultCities(),
            'sender_city_ref' => wcus_get_option('np_api_cost_city'),
            'sender_city' => $this->tryGetCityData(wcus_get_option('np_api_cost_city')),
            'total_cost' => $totalCost
                ? json_decode($totalCost, true)
                : [
                    [ 'total' => 0, 'price' => 50 ]
                ],
        ];

        return $state;
    }

    private function getAddressCostState()
    {
        $totalRelativeCost = wcus_get_option('address_total_cost');

        return [
            'calc_enable' => (int)wcus_get_option('address_calc_enable'),
            'calc_type' => wcus_get_option('address_calc_type'),
            'fixed_price' => wcus_get_option('address_fixed_cost'),
            'total_cost' => $totalRelativeCost
                ? json_decode($totalRelativeCost, true)
                : [
                    [ 'total' => 0, 'price' => 50 ]
                ]
        ];
    }

    private function getCODState()
    {
        $gateways = wc()->payment_gateways()->payment_gateways();
        $paymentMethods = [];

        foreach ($gateways as $id => $gateway) {
            $paymentMethods[] = [
                'id' => $id,
                'name' => $gateway->get_title()
            ];
        }

        return [
            'payment_methods' => $paymentMethods,
            'cod_payment_id' => wcus_get_option('cod_payment_id'),
            'active' => (int)wcus_get_option('cod_payment_active')
        ];
    }

    private function getTTNState()
    {
        $state = [
            'default_cities' => $this->getDefaultCities()
        ];

        $senderCityRef = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_city');
        $senderWarehouseRef = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_warehouse');

        $state['sender_city'] = $this->tryGetCityData($senderCityRef);
        $state['sender_warehouse'] = $this->tryGetWarehouseData($senderWarehouseRef);
        $state['sender_settlement'] = [
            'value' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_ref'),
            'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_name')),
                'area' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_area')),
                'region' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_region'))
            ]
        ];

        $state['sender_street'] = [
            'value' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_ref'),
            'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_full')),
            'meta' => [
                'name' => WCUSHelper::prepareUIString(wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_name'))
            ]
        ];

        $state['sender_house'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_house');
        $state['sender_flat'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat');

        return $state;
    }

    private function getFreeShippingTitle(): array
    {
        return [
            'active' => wc_ukr_shipping_get_option(WCUS_OPTION_NP_FREE_SHIPPING_TITLE_ACTIVE),
            'title' => wc_ukr_shipping_get_option(WCUS_OPTION_NP_FREE_SHIPPING_TITLE),
        ];
    }

    private function getLicenseState(): array
    {
        $licenseKey = wc_ukr_shipping_get_option('wc_ukr_shipping_license_key');
        $isConnected = (int)wc_ukr_shipping_get_option('wcus_license_connected') === 1;
        $licenseInfo = [
            'expired_at' => '',
            'status' => '',
            'features' => [
                'cloud_address_api' => false,
                'cloud_direct_address_api' => false,
            ],
        ];

        if ($licenseKey && $isConnected) {
            try {
                $licenseInfo = $this->licenseService->getLicenseInfo($licenseKey);
            } catch (CloudCommunicationException $e) {
                $licenseInfo['expired_at'] = 'Unknown';
                $licenseInfo['status'] = 'Unable to fetch data from license server. Try again later.';
            } catch (\Exception $e) {
                update_option('wcus_license_connected', 0);
                $isConnected = false;
            }
        }

        return [
            'licenseKey' => $licenseKey,
            'isConnected' =>$isConnected,
            'licenseInfo' => $licenseInfo,
        ];
    }

    private function getDefaultCities()
    {
        // todo: refactor
        $addressService = new AddressService();

        return $addressService->getDefaultCities();
    }

    private function tryGetCityData(?string $ref): array
    {
        if ($ref === null) {
            return [
                'name' => '',
                'value' => ''
            ];
        }

        $city = $this->addressProvider->searchCityByRef($ref);
        if ($city === null) {
            return [
                'name' => '',
                'value' => $ref,
            ];
        }

        return [
            'name' => $this->translateService->getCurrentLanguage() === 'ua'
                ? $city->getNameUa()
                : $city->getNameRu(),
            'value' => $city->getRef(),
        ];
    }

    private function tryGetWarehouseData(?string $ref): array
    {
        if ($ref === null) {
            return [
                'name' => '',
                'value' => ''
            ];
        }

        $warehouse = $this->addressProvider->searchWarehouseByRef($ref);
        if ($warehouse === null) {
            return [
                'name' => '',
                'value' => $ref,
            ];
        }

        return [
            'name' => $this->translateService->getCurrentLanguage() === 'ua'
                ? $warehouse->getNameUa()
                : $warehouse->getNameRu(),
            'value' => $warehouse->getRef(),
        ];
    }
}