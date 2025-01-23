<?php

namespace kirillbdev\WCUkrShipping\Helpers;

if ( ! defined('ABSPATH')) {
    exit;
}

class WCUSHelper
{
    /**
     * @param \WC_Order $order
     *
     * @return \WC_Order_Item_Shipping|null
     */
    public static function getOrderShippingMethod($order)
    {
        $shippingMethods = $order->get_shipping_methods();

        if (empty($shippingMethods)) {
            return null;
        }

        return array_shift($shippingMethods);
    }

    /**
     * @param string $phone
     *
     * @return string
     */
    public static function preparePhone($phone)
    {
        $phone = str_replace(['+', '-', ' ', '(', ')'], '', $phone);

        if (preg_match('/^\d{12,12}$/', $phone)) {
            return $phone;
        }

        if (preg_match('/^\d{10,10}$/', $phone)) {
            return '38' . $phone;
        }

        if (preg_match('/^\d{11,11}$/', $phone)) {
            return '3' . $phone;
        }

        return $phone;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function prepareApiString($str)
    {
        return wp_unslash(str_replace("`", "'", $str));
    }

    public static function prepareUIString($str)
    {
        return wp_unslash(wp_specialchars_decode($str, ENT_QUOTES));
    }

    public static function getDefaultCities()
    {
        return [
            [
                'ref' => '8d5a980d-391c-11dd-90d9-001a92567626',
                'description' => 'Київ',
                'description_ru' => 'Киев',
            ],
            [
                'ref' => 'db5c88e0-391c-11dd-90d9-001a92567626',
                'description' => 'Харків',
                'description_ru' => 'Харьков',
            ],
            [
                'ref' => 'db5c88f0-391c-11dd-90d9-001a92567626',
                'description' => 'Дніпро',
                'description_ru' => 'Днепр',
            ],
            [
                'ref' => 'db5c88c6-391c-11dd-90d9-001a92567626',
                'description' => 'Запоріжжя',
                'description_ru' => 'Запорожье',
            ],
            [
                'ref' => 'db5c88d0-391c-11dd-90d9-001a92567626',
                'description' => 'Одеса',
                'description_ru' => 'Одесса',
            ],
            [
                'ref' => 'db5c88f5-391c-11dd-90d9-001a92567626',
                'description' => 'Львів',
                'description_ru' => 'Львов',
            ],
            [
                'ref' => 'db5c890d-391c-11dd-90d9-001a92567626',
                'description' => 'Кривий Ріг',
                'description_ru' => 'Кривой Рог',
            ],
            [
                'ref' => 'db5c888c-391c-11dd-90d9-001a92567626',
                'description' => 'Миколаїв',
                'description_ru' => 'Николаев',
            ],
            [
                'ref' => 'db5c897c-391c-11dd-90d9-001a92567626',
                'description' => 'Чернігів',
                'description_ru' => 'Чернигов',
            ],
            [
                'ref' => 'db5c88e5-391c-11dd-90d9-001a92567626',
                'description' => 'Суми',
                'description_ru' => 'Сумы',
            ],
            [
                'ref' => 'db5c88de-391c-11dd-90d9-001a92567626',
                'description' => 'Вінниця',
                'description_ru' => 'Винница',
            ],
            [
                'ref' => 'db5c8902-391c-11dd-90d9-001a92567626',
                'description' => 'Черкаси',
                'description_ru' => 'Черкассы',
            ],
            [
                'ref' => 'db5c88cc-391c-11dd-90d9-001a92567626',
                'description' => 'Херсон',
                'description_ru' => 'Херсон',
            ],
            [
                'ref' => 'db5c8892-391c-11dd-90d9-001a92567626',
                'description' => 'Полтава',
                'description_ru' => 'Полтава',
            ],
            [
                'ref' => 'db5c88c4-391c-11dd-90d9-001a92567626',
                'description' => 'Житомир',
                'description_ru' => 'Житомир',
            ],
            [
                'ref' => 'db5c8927-391c-11dd-90d9-001a92567626',
                'description' => 'Краматорськ',
                'description_ru' => 'Краматорск',
            ],
            [
                'ref' => 'db5c896a-391c-11dd-90d9-001a92567626',
                'description' => 'Рівне',
                'description_ru' => 'Ровно',
            ],
            [
                'ref' => 'db5c8904-391c-11dd-90d9-001a92567626',
                'description' => 'Івано-Франківськ',
                'description_ru' => 'Ивано-Франковск',
            ],
            [
                'ref' => '8d5a9813-391c-11dd-90d9-001a92567626',
                'description' => 'Кременчук',
                'description_ru' => 'Кременчуг',
            ],
            [
                'ref' => 'db5c8900-391c-11dd-90d9-001a92567626',
                'description' => 'Тернопіль',
                'description_ru' => 'Тернополь',
            ],
            [
                'ref' => 'db5c893b-391c-11dd-90d9-001a92567626',
                'description' => 'Луцьк',
                'description_ru' => 'Луцк',
            ],
            [
                'ref' => 'db5c88ce-391c-11dd-90d9-001a92567626',
                'description' => 'Біла Церква',
                'description_ru' => 'Белая Церковь',
            ],
            [
                'ref' => 'e221d642-391c-11dd-90d9-001a92567626',
                'description' => 'Чернівці',
                'description_ru' => 'Черновцы',
            ],
            [
                'ref' => 'db5c88ac-391c-11dd-90d9-001a92567626',
                'description' => 'Хмельницький',
                'description_ru' => 'Хмельницкий',
            ],
            [
                'ref' => 'db5c8914-391c-11dd-90d9-001a92567626',
                'description' => 'Кам\'янець-Подільський',
                'description_ru' => 'Каменец-Подольский',
            ],
        ];
    }
}