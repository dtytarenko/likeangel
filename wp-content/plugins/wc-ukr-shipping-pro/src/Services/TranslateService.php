<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\Address\Model\Warehouse;

if (!defined('ABSPATH')) {
    exit;
}

class TranslateService
{
    private $areaTranslates = [
        '71508128-9b87-11de-822f-000c2965ae0e' => 'АРК',
        '71508129-9b87-11de-822f-000c2965ae0e' => 'Винницкая',
        '7150812a-9b87-11de-822f-000c2965ae0e' => 'Волынская',
        '7150812b-9b87-11de-822f-000c2965ae0e' => 'Днепропетровская',
        '7150812c-9b87-11de-822f-000c2965ae0e' => 'Донецкая',
        '7150812d-9b87-11de-822f-000c2965ae0e' => 'Житомирская',
        '7150812e-9b87-11de-822f-000c2965ae0e' => 'Закарпатская',
        '7150812f-9b87-11de-822f-000c2965ae0e' => 'Запорожская',
        '71508130-9b87-11de-822f-000c2965ae0e' => 'Ивано-Франковская',
        '71508131-9b87-11de-822f-000c2965ae0e' => 'Киевская',
        '71508132-9b87-11de-822f-000c2965ae0e' => 'Кировоградская',
        '71508133-9b87-11de-822f-000c2965ae0e' => 'Луганская',
        '71508134-9b87-11de-822f-000c2965ae0e' => 'Львовская',
        '71508135-9b87-11de-822f-000c2965ae0e' => 'Николаевская',
        '71508136-9b87-11de-822f-000c2965ae0e' => 'Одесская',
        '71508137-9b87-11de-822f-000c2965ae0e' => 'Полтавская',
        '71508138-9b87-11de-822f-000c2965ae0e' => 'Ровенская',
        '71508139-9b87-11de-822f-000c2965ae0e' => 'Сумская',
        '7150813a-9b87-11de-822f-000c2965ae0e' => 'Тернопольская',
        '7150813b-9b87-11de-822f-000c2965ae0e' => 'Харьковская',
        '7150813c-9b87-11de-822f-000c2965ae0e' => 'Херсонская',
        '7150813d-9b87-11de-822f-000c2965ae0e' => 'Хмельницкая',
        '7150813e-9b87-11de-822f-000c2965ae0e' => 'Черкасская',
        '7150813f-9b87-11de-822f-000c2965ae0e' => 'Черновицкая',
        '71508140-9b87-11de-822f-000c2965ae0e' => 'Черниговская'
    ];

    public function translateCityName(City $city): string
    {
        return $this->getCurrentLanguage() === 'ua'
            ? $city->getNameUa()
            : $city->getNameRu();
    }

    public function translateWarehouseName(Warehouse $warehouse): string
    {
        return $this->getCurrentLanguage() === 'ua'
            ? $warehouse->getNameUa()
            : $warehouse->getNameRu();
    }

    /**
     * @deprecated Use translateCityName
     * @param array $city
     *
     * @return string
     */
    public function translateCity($city)
    {
        if ( ! $city) {
            return '';
        }

        return 'ru' === $this->getCurrentLanguage()
            ? $city['description_ru']
            : $city['description'];
    }

    /**
     * @deprecated Use translateWarehouseName
     * @param array $warehouse
     *
     * @return string
     */
    public function translateWarehouse($warehouse)
    {
        if ( ! $warehouse) {
            return '';
        }

        return 'ru' === $this->getCurrentLanguage()
            ? $warehouse['description_ru']
            : $warehouse['description'];
    }

    /**
     * @return array
     */
    public function getTranslates()
    {
        $translates = [];

        if (WCUS_TRANSLATE_TYPE_MO_FILE === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_translates_type')) {
            $translates = [
                'method_title' => __('Nova Poshta', 'wc-ukr-shipping-pro'),
                'method_title_cod' => __('Nova Poshta (COD)', 'wc-ukr-shipping-pro'),
                'block_title' => __('Shipping address', 'wc-ukr-shipping-pro'),
                'placeholder_area' => __('Select area', 'wc-ukr-shipping-pro'),
                'placeholder_city' => __('Select city', 'wc-ukr-shipping-pro'),
                'placeholder_warehouse' => __('Select warehouse', 'wc-ukr-shipping-pro'),
                'address_title' => __('Need address shipping', 'wc-ukr-shipping-pro'),
                'address_placeholder' => __('Enter address', 'wc-ukr-shipping-pro'),
                'settlement_label' => __('Settlement', 'wc-ukr-shipping-pro'),
                'settlement_placeholder' => __('Start typing a word or part of a word', 'wc-ukr-shipping-pro'),
                'street_label' => __('Street', 'wc-ukr-shipping-pro'),
                'street_placeholder' => __('Start typing a word or part of a word', 'wc-ukr-shipping-pro'),
                'house_label' => __('House', 'wc-ukr-shipping-pro'),
                'house_placeholder' => '',
                'flat_label' => __('Flat', 'wc-ukr-shipping-pro'),
                'flat_placeholder' => '',
                'not_found' => __('Nothing found', 'wc-ukr-shipping-pro'),
                'validate_error' => __('Select warehouse of <strong>Nova Poshta</strong>', 'wc-ukr-shipping-pro'),
                'checkout_error_settlement' => __('Select settlement', 'wc-ukr-shipping-pro'),
                'checkout_error_street' => __('Select street', 'wc-ukr-shipping-pro'),
                'checkout_error_house' => __('Enter house number', 'wc-ukr-shipping-pro')
            ];
        } else {
            $translates = [
                'method_title' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_method_title'),
                'method_title_cod' => wc_ukr_shipping_get_option('wcus_np_cod_method_title'),
                'block_title' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_block_title'),
                'placeholder_area' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_area'),
                'placeholder_city' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_city'),
                'placeholder_warehouse' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_warehouse'),
                'address_title' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_title'),
                'address_placeholder' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_placeholder'),
                'settlement_label' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_label'),
                'settlement_placeholder' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_placeholder'),
                'street_label' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_label'),
                'street_placeholder' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_placeholder'),
                'house_label' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_house_label'),
                'house_placeholder' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_house_placeholder'),
                'flat_label' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat_label'),
                'flat_placeholder' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat_placeholder'),
                'not_found' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_not_found_text'),
                'validate_error' => wc_ukr_shipping_get_option('wcus_np_validate_error'),
                'checkout_error_settlement' => wcus_get_option('l10n_error_settlement'),
                'checkout_error_street' => wcus_get_option('l10n_error_street'),
                'checkout_error_house' => wcus_get_option('l10n_error_house')
            ];
        }

        return apply_filters('wc_ukr_shipping_get_nova_poshta_translates', $translates);
    }

    public function translateAreas($areas)
    {
        if ('ru' === $this->getCurrentLanguage()) {
            foreach ($areas as &$area) {
                if (isset($this->areaTranslates[$area['ref']])) {
                    $area['description'] = $this->areaTranslates[$area['ref']];
                }
            }
        }

        return $areas;
    }

    public function getCurrentLanguage(): string
    {
        if (is_admin()) {
            $lang = preg_replace('/_.+$/', '', get_user_locale());
        } else {
            $lang = get_option('wc_ukr_shipping_np_lang', 'ru');
        }

        if (function_exists('wpml_get_current_language')) {
            if ($this->isLanguageAvailable(wpml_get_current_language())) {
                $lang = wpml_get_current_language();
            }
        } elseif (wp_doing_ajax() && !empty($_COOKIE['pll_language']) && $this->isLanguageAvailable($_COOKIE['pll_language'])) {
            $lang = $_COOKIE['pll_language'];
        } elseif (function_exists('pll_current_language')) {
            if ($this->isLanguageAvailable(pll_current_language())) {
                $lang = pll_current_language();
            }
        }

        // Cast language to normal ukrainian (Polylang compatibility)
        if ($lang === 'uk') {
            $lang = 'ua';
        }

        return apply_filters('wc_ukr_shipping_language', $lang);
    }

    private function isLanguageAvailable($lang)
    {
        return in_array($lang, ['ru', 'uk', 'ua']);
    }
}