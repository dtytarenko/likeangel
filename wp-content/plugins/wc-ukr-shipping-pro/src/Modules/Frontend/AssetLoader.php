<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Services\Address\AddressService;
use kirillbdev\WCUkrShipping\Services\StateService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUkrShipping\States\CheckoutState;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class AssetLoader implements ModuleInterface
{
    /**
     * @var AddressService
     */
    private $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    public function init()
    {
        add_action('wp_head', [ $this, 'loadCheckoutStyles' ]);
        add_action('wp_enqueue_scripts', [ $this, 'loadAssets' ]);
    }

    public function loadCheckoutStyles()
    {
        if ( ! wc_ukr_shipping_is_checkout()) {
            return;
        }

        ?>
        <style>
            .wc-ukr-shipping-np-fields {
                padding: 1px 0;
            }

            .wcus-state-loading:after {
                border-color: <?= get_option('wc_ukr_shipping_spinner_color', '#dddddd'); ?>;
                border-left-color: #fff;
            }
        </style>
        <?php
    }

    public function loadAssets()
    {
        if ( ! wc_ukr_shipping_is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'wcus_checkout_css',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/css/style.min.css'
        );

        if ((int)wcus_get_option('checkout_new_ui')) {
            wp_enqueue_script(
                'wcus_checkout_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/checkout2.min.js',
                [ 'jquery' ],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/checkout2.min.js'),
                true
            );

            StateService::addState('checkout', CheckoutState::class);
            wp_localize_script(
                    'wcus_checkout_js',
                    'WCUS_APP_STATE',
                    json_decode(json_encode(StateService::getState()), true)
            );
        } else {
            wp_enqueue_script(
                'wcus_checkout_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/checkout.min.js',
                ['jquery'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/checkout.min.js'),
                true
            );
        }

        $this->initGlobals();
    }

    private function initGlobals()
    {
        $translator = new TranslateService();
        $translates = $translator->getTranslates();

        $globals = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'nonce' => wp_create_nonce('wc-ukr-shipping'),
            'lang' => $translator->getCurrentLanguage(),
            'disableDefaultBillingFields' => apply_filters('wc_ukr_shipping_prevent_disable_default_fields', false) === false
                ? 1
                : 0,
            'apiAddressEnable' => (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')
                ? 1
                : 0,
            'priceType' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_price_type'),
            'cost_view_only' => (int)wcus_get_option('cost_view_only'),
            'options' => [
                'address_shipping_enable' => (int)wc_ukr_shipping_get_option('wc_ukr_shipping_address_shipping'),
                'show_poshtomats' => (int)get_option(WCUS_OPTION_SHOW_POSHTOMATS) === 1,
                'addressProvider' => (int)wc_ukr_shipping_get_option('wcus_license_connected') === 1
                    && (int)wc_ukr_shipping_get_option('wcus_use_cloud_address_api') === 1 ? 'cloud' : 'self',
            ]
        ];

        if ((int)wcus_get_option('checkout_new_ui')) {
            $globals['default_cities'] = $this->addressService->getDefaultCities();
            $globals['i18n'] = [
                'fields_title' => __('Shipping address', 'wc-ukr-shipping-pro'),
                'shipping_type_warehouse' => __('to the warehouse', 'wc-ukr-shipping-pro'),
                'shipping_type_doors' => __('to the doors', 'wc-ukr-shipping-pro'),
                'shipping_type_poshtomat' => __('to the poshtomat', 'wc-ukr-shipping-pro'),
                'ui' => [
                    'city_placeholder' => __('City', 'wc-ukr-shipping-pro'),
                    'warehouse_placeholder' => __('Warehouse', 'wc-ukr-shipping-pro'),
                    'poshtomat_placeholder' => __('Poshtomat', 'wc-ukr-shipping-pro'),
                    'settlement_placeholder' => __('Settlement', 'wc-ukr-shipping-pro'),
                    'street_placeholder' => __('Street', 'wc-ukr-shipping-pro'),
                    'house_placeholder' => __('House', 'wc-ukr-shipping-pro'),
                    'flat_placeholder' => __('Flat', 'wc-ukr-shipping-pro'),
                    'text_search' => __('Enter value for search', 'wc-ukr-shipping-pro'),
                    'text_loading' => __('Loading...', 'wc-ukr-shipping-pro'),
                    'text_more' => __('Load more...', 'wc-ukr-shipping-pro'),
                    'text_not_found' => __('Nothing found', 'wc-ukr-shipping-pro'),
                    'text_more_chars' => __('Enter more chars', 'wc-ukr-shipping-pro'),
                    'custom_address_placeholder' => __('Enter full address', 'wc-ukr-shipping-pro')
                ]
            ];

            $globals['i18n'] = array_replace_recursive(
                $globals['i18n'],
                apply_filters('wcus_checkout_i18n', $globals['i18n'], $translator->getCurrentLanguage())
            );
        } else {
            $globals['i10n'] = [
                'placeholder_area' => $translates['placeholder_area'],
                'placeholder_city' => $translates['placeholder_city'],
                'placeholder_warehouse' => $translates['placeholder_warehouse'],
                'not_found' => $translates['not_found']
            ];
        }

        wp_localize_script('wcus_checkout_js', 'wc_ukr_shipping_globals', $globals);
    }
}