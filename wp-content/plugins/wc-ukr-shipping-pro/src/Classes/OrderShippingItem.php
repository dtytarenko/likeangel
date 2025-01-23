<?php

namespace kirillbdev\WCUkrShipping\Classes;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Lib\Event\Checkout\ShippingMethodLabelFilterEvent;
use kirillbdev\WCUkrShipping\Lib\Event\EventName;

if (!defined('ABSPATH')) {
    exit;
}

class OrderShippingItem
{
    public function __construct()
    {
        add_filter('woocommerce_order_shipping_method', [$this, 'renderShippingName'], 10, 2);
        add_filter('woocommerce_order_shipping_to_display', [$this, 'renderShippingAddress'], 10, 2);
        add_filter('woocommerce_order_item_display_meta_key', [$this, 'getMetaLabel'], 10, 2);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'getMetaValue'], 10, 3);
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'getHiddenKeys']);
    }

    public function renderShippingName($name, $order): string
    {
        if (is_admin()) {
            return $name;
        }

        $shipping = WCUSHelper::getOrderShippingMethod($order);

        if (!$shipping || WC_UKR_SHIPPING_NP_SHIPPING_NAME !== $shipping->get_method_id()) {
            return $name;
        }

        return apply_filters(
            EventName::NOVAPOSHTA_SHIPPING_METHOD_LABEL,
            $name,
            new ShippingMethodLabelFilterEvent((float)$order->get_subtotal(), (float)$order->get_shipping_total())
        );
    }

    public function renderShippingAddress($value, $order)
    {
        $shipping = WCUSHelper::getOrderShippingMethod($order);

        if (!$shipping || WC_UKR_SHIPPING_NP_SHIPPING_NAME !== $shipping->get_method_id()) {
            return $value;
        }

        $address = '';

        if ($shipping->get_meta('wcus_api_address')) {
            $address = sprintf(
                '<br/>%s<br/>%s, %s',
                $shipping->get_meta('wcus_settlement_full'),
                $shipping->get_meta('wcus_street_full'),
                $shipping->get_meta('wcus_house')
            );

            if ($shipping->get_meta('wcus_flat')) {
                $address .= ' кв. ' . $shipping->get_meta('wcus_flat');
            }
        } else {
            $wrapperOrder = wcus_wrap_order($order);
            $city = $wrapperOrder->getCity();

            $customAddress = $shipping->get_meta('wcus_address');
            if ($customAddress) {
                $address = sprintf(
                    '<br/>%s<br/>%s',
                    $city,
                    $customAddress
                );
            } else {
                $address = sprintf(
                    '<br/>%s<br/>%s',
                    $city,
                    $wrapperOrder->getAddress1()
                );
            }
        }

        return $value . $address;
    }

    public function getMetaLabel($key, $meta)
    {
        switch ($key) {
            case 'wcus_settlement_full':
                return 'Населенный пункт';
            case 'wcus_street_full':
                return 'Улица';
            case 'wcus_house':
                return 'Номер дома';
            case 'wcus_flat':
                return 'Номер квартиры';
            case 'wcus_city_ref':
                return 'Город';
            case 'wcus_warehouse_ref':
                return 'Отделение';
            case 'wcus_address':
                return 'Адрес (без API)';
        }

        return $key;
    }

    public function getMetaValue($value, $meta, $orderItem)
    {
        $order = wcus_wrap_order($orderItem->get_order());

        if ('wcus_city_ref' === $meta->key) {
            return $order->getCity();
        } elseif ('wcus_warehouse_ref' === $meta->key) {
            return $order->getAddress1();
        }

        return $value;
    }

    public function getHiddenKeys($keys)
    {
        $keys[] = 'wcus_settlement_ref';
        $keys[] = 'wcus_settlement_name';
        $keys[] = 'wcus_settlement_area';
        $keys[] = 'wcus_street_ref';
        $keys[] = 'wcus_street_name';
        $keys[] = 'wcus_api_address';
        $keys[] = 'wcus_settlement_region';
        $keys[] = 'wcus_area_ref';

        return $keys;
    }
}
