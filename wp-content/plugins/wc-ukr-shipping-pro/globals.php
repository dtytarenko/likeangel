<?php

use kirillbdev\WCUkrShipping\Model\WCUSOrder;

if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! function_exists('wc_ukr_shipping')) {

    function wc_ukr_shipping()
    {
        return \kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance();
    }

}

if ( ! function_exists('wcus_container')) {

    function wcus_container(): \kirillbdev\WCUSCore\Foundation\Container
    {
        return \kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance()->getContainer();
    }

}

if ( ! function_exists('wc_ukr_shipping_render_view')) {

    function wc_ukr_shipping_render_view($view, $data = [])
    {
        return \kirillbdev\WCUkrShipping\Classes\View::render($view, $data);
    }

}

if ( ! function_exists('wc_ukr_shipping_import_svg')) {

    function wc_ukr_shipping_import_svg($image)
    {
        return file_get_contents(WC_UKR_SHIPPING_PLUGIN_DIR . '/image/' . $image);
    }

}

if ( ! function_exists('wc_ukr_shipping_get_option')) {

    function wc_ukr_shipping_get_option($key)
    {
        return \kirillbdev\WCUkrShipping\DB\OptionsRepository::getOption($key);
    }

}

if ( ! function_exists('wcus_get_option')) {

    function wcus_get_option($key, $default = null)
    {
        return \kirillbdev\WCUkrShipping\DB\OptionsRepository::getOptionV2($key, $default);
    }

}

if ( ! function_exists('wc_ukr_shipping_is_checkout')) {

    function wc_ukr_shipping_is_checkout()
    {
        return function_exists('is_checkout') && is_checkout();
    }

}

if ( ! function_exists('wcus_container_make')) {

    function wcus_container_make($abstract)
    {
        return \kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance()->make($abstract);
    }

}

if ( ! function_exists('wcus_container_singleton')) {

    function wcus_container_singleton($abstract)
    {
        return \kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance()->singleton($abstract);
    }

}

if ( ! function_exists('wcus_is_current_screen')) {

    function wcus_is_current_screen($id)
    {
        return get_current_screen() && get_current_screen()->id === $id;
    }

}

if (!function_exists('wcus_i18n')) {

    function wcus_i18n(string $text): string
    {
        return __($text, WCUS_TRANSLATE_DOMAIN);
    }

}

if (!function_exists('wcus_get_current_language')) {

    function wcus_get_current_language(): string
    {
        /** @var \kirillbdev\WCUkrShipping\Services\TranslateService $translateService */
        $translateService = wcus_container_singleton('translate_service');

        return $translateService->getCurrentLanguage();
    }

}

if (!function_exists('wcus_wc_container_safe_get')) {

    function wcus_wc_container_safe_get(string $alias)
    {
        if (!function_exists('wc_get_container')) {
            return null;
        }

        try {
            return wc_get_container()->get($alias);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('wcus_wrap_order')) {

    function wcus_wrap_order(\WC_Order $wcOrder): WCUSOrder
    {
        return new WCUSOrder($wcOrder);
    }
}
