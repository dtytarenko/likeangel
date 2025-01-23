<?php

if(file_exists('custom-property-handler.php'))
    include 'custom-property-handler.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function globalConvert_kcrm ($path, $parameters)
{
    switch ($path) {
        case '/orders/create';
            $parameters['order'] = covertOrder_kcrm($parameters['order'], $parameters['source']);
            $parameters['url'] = '/order';
            return $parameters;
            break;
        case '/reference/delivery-types';
            $parameters['url'] = '/order/delivery-service';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/payment-types';
            $parameters['url'] = '/order/payment-method';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/api-versions';
            $parameters['url'] = '/order/tag';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/statuses';
            $parameters['url'] = '/order/status';
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
        case '/reference/order-methods';
            $parameters['url'] = '/order/source';
            $parameters['filter'] = ['driver' => 'wordpress'];
            $parameters['limit'] = '50';
            $parameters['page'] = '1';
            return $parameters;
            break;
    }


//    return $result;
}

function covertOrder_kcrm ($order, $source)
{
    global $woocommerce;

    // WC_Keycrm_Logger::add(sprintf("order data: %s", json_encode($order, JSON_UNESCAPED_UNICODE)));

    $order = json_decode($order, 1);
    $wcOrder = wc_get_order($order['externalId']);
    WC_Keycrm_Logger::add(sprintf("order data: \n\n%s\n", json_encode($order, JSON_UNESCAPED_UNICODE)));
    WC_Keycrm_Logger::add(sprintf("wcOrder data: \n\n%s\n", json_encode($wcOrder->get_data(), JSON_UNESCAPED_UNICODE)));
    $wcItems = [];
    foreach ($wcOrder->get_items() as $item_id => $item) {
        $wcItems[$item_id] = $item;
        // prevent combined properties for products with the same SKU
        $usedItems[$item_id] = false;
        WC_Keycrm_Logger::add(sprintf("wcOrderItem data: \n\n%s\n", json_encode($item->get_data(), JSON_UNESCAPED_UNICODE)));
        WC_Keycrm_Logger::add(sprintf("wcOrderItemProduct data: \n\n%s\n", json_encode(wc_get_product($item->get_product())->get_data(), JSON_UNESCAPED_UNICODE)));
    }


    // new WooCommerce 3.7+
    if (isset($wcOrder->get_coupon_codes)) {
        $cupon = $wcOrder->get_coupon_codes();
    } else if (isset($wcOrder->get_used_coupons)) { // old WooCommerce <3.7
        $cupon = $wcOrder->get_used_coupons();
    }
    $address = !empty($wcOrder->get_shipping_address_1()) ? $wcOrder->get_shipping_address_1() : $wcOrder->get_billing_address_1();
    $address2 = $wcOrder->get_shipping_address_2();

    $kItems = [];
    $k_props = [];

    foreach ($order['items'] as $itemKey => $item) {
        $itemProduct = wc_get_product($item['offer']['externalId']);
        // WC_Keycrm_Logger::add(sprintf("itemProduct: \n\n%s\n", json_encode($itemProduct->get_data(), JSON_UNESCAPED_UNICODE)));

        $itemImage = false;
        if ($wcItems) {

            foreach ($usedItems as $i => $value) {
                $wcProduct = wc_get_product($wcItems[$i]->get_product());
                // WC_Keycrm_Logger::add(sprintf("wc_get_product: \n\n%s\n", json_encode($wcProduct->get_data(), JSON_UNESCAPED_UNICODE)));

                if($wcProduct && $wcProduct->get_id() == $item['offer']['externalId'] && !$usedItems[$i]){
                    $wcItemMetaData = $wcItems[$i]->get_meta_data();

                    if (!empty($wcItemMetaData)){
                        foreach ($wcItemMetaData as $attributeIndex => $attributeValue) {
                            $attributeValueData = $attributeValue->get_data();

                            if (is_string($attributeValueData['value'])) {
                                $k_props[] = [
                                    'name' => $attributeValueData['key'],
                                    'value' => $attributeValueData['value']
                                ];
                            } else if (function_exists('customPropertyHandler')) {
                                $k_props = array_merge($k_props, customPropertyHandler($attributeValueData));
                            } else {
                                WC_Keycrm_Logger::add(sprintf("WARNING! Only STRING AttributeValueData is supported. Given: \n\n%s", json_encode($attributeValueData, JSON_UNESCAPED_UNICODE)));
                            }
                        }
                    }
                    if (wp_get_attachment_image_src($wcProduct->get_image_id(), 'full')) {
                        $itemImage = wp_get_attachment_image_src($wcProduct->get_image_id(), 'full')[0];
                    }
                    $usedItems[$i] = true;
                    break;
                }
            }
        }
        $kItems[$itemKey] = [
            'price' => $item['initialPrice'],
            // 'discount_percent' => $item['quantity'], #todo не найдено моделирования
            'discount_amount' => $item['discountManualAmount'],
            'quantity' => $item['quantity'],
            'name' => $item['productName'],
            'picture' => substr(get_the_post_thumbnail_url($item['offer']['externalId']), 0, 4) === 'http' ? get_the_post_thumbnail_url($item['offer']['externalId']) : ($itemImage ? $itemImage : null)
        ];
        if ($itemProduct && $itemProduct->get_sku() != '') {
            $kItems[$itemKey]['sku'] = $itemProduct->get_sku(); // todo Нужно выводить в класс или в функцию отдельно вне модуля
        }
        if (!empty($k_props)) {
            $kItems[$itemKey]['properties'] = $k_props;
        }
        $k_props = array();
    }

    $k_order = [
        'source_uuid' => $order['externalId'], //'4815162342',
        'source_id' => (int)$source, //11, //'4815162342',
        'status_id' => (int)$order['status'], //1,
        'promocode' => isset($cupon) && isset($cupon[0]) ? $cupon[0] : '', //'MERRYCHRISTMAS',
        'total_discount' => $order['discountManualAmount'], //30.5,
        'shipping_price' => $order['delivery']['cost'] ? $order['delivery']['cost'] : 0, //2.5,
        'manager_comment' => $order['managerComment'] ? $order['managerComment'] : '', //NULL,
        'buyer_comment' => $order['customerComment'] ? $order['customerComment'] : '', //'Hello from buyer',
        // 'gift_message' => $order['id'], //'Happy Birthday Charlie',
        // 'is_gift' => $order['id'], //true,
        'ordered_at' => $order['createdAt'], //'2020-05-16 17:00:07',
        'buyer' => [
            'full_name' =>
              $order['firstName'] .
              ($wcOrder->get_meta('patronymic') ? ' ' . $wcOrder->get_meta('patronymic') : ''). ' '
              . $order['lastName'],
            // our user guide skipped email
            'email' => ($order['email'] !== 'skip@dummyemail.com' ? $order['email'] : ''),
            'phone' => $order['phone'],
        ],
        'shipping' => [
            'shipping_address_city' => $order['delivery']['address']['city'] ? $order['delivery']['address']['city'] : $wcOrder->get_shipping_city(),
            'shipping_address_country' => $order['countryIso'] ? $order['countryIso'] : $wcOrder->get_shipping_country(),
            'shipping_address_region' => $order['delivery']['address']['region'] ? $order['delivery']['address']['region'] : $wcOrder->get_shipping_state(),
            'shipping_address_zip' => $order['delivery']['address']['index'] ? $order['delivery']['address']['index'] : $wcOrder->get_shipping_postcode(),
            'shipping_receive_point' => $address. ' '. $address2,
        ],
        //        'marketing' => [ #todo не реализовано
        //            'utm_source' => '',
        //            'utm_medium' => '',
        //            'utm_campaign' => '',
        //            'utm_term' => '',
        //            'utm_content' => '',
        //        ],
        'marketing' => [
            'utm_source' => isset($_POST['wc_order_attribution_utm_source']) ? $_POST['wc_order_attribution_utm_source'] : '',
            'utm_medium' => isset($_POST['wc_order_attribution_utm_medium']) ? $_POST['wc_order_attribution_utm_medium'] : '',
            'utm_campaign' => isset($_POST['wc_order_attribution_utm_campaign']) ? $_POST['wc_order_attribution_utm_campaign'] : '',
            'utm_term' => isset($_POST['wc_order_attribution_utm_term']) ? $_POST['wc_order_attribution_utm_term'] : '',
            'utm_content' => isset($_POST['wc_order_attribution_utm_content']) ? $_POST['wc_order_attribution_utm_content'] : ''
        ],
        'products' => $kItems,
        'payments' => [
            [
                'payment_method_id' => (int)$order['payments'][0]['type'], //integer
                'amount' => $wcOrder->get_total(),
                // 'description' => $order['delivery']['index'], #todo не найдено аналога в CMS
                'payment_date' => !isset($order['payments'][0]['status']) ? null : date('Y-m-d H:i:s'), // дата оплаты. Признак оплаты в WP  явно не определен
                'status' => $wcOrder->get_total() > 0 ? 'not_paid' : 'paid',
            ]
        ],
    ];
    if (isset($order['delivery']['code'])) {
        $k_order['shipping']['delivery_service_id'] = (int)$order['delivery']['code'];
    }
    if ($order['payments'][0]['type'] == null){
        $k_order['payments'] = [];
    }
    WC_Keycrm_Logger::add(sprintf("KeyCRM Order data: %s", json_encode($k_order, JSON_UNESCAPED_UNICODE)));
    error_log(print_r($k_order, true));
    return json_encode($k_order);
}


function allowedPath($path)
{
    $check =  in_array(
        $path,
        [
            '/reference/delivery-types',
            '/reference/payment-types',
            '/reference/statuses',
            '/api-versions',
            '/orders/create',
            '/reference/order-methods',
        ]
    );
    return $check;
}

function convertResponce($responseBody, $path){
    $body = json_decode($responseBody, 1);
    switch ($path) {
        case '/reference/delivery-types':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['deliveryTypes'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['deliveryTypes'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/payment-types':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['paymentTypes'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['paymentTypes'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/statuses':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['statuses'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['statuses'][$bodyItemKey]['name'] = $bodyItem['name'];
            }
            unset($body['data']);
            break;
        case '/reference/order-methods':
            foreach ($body['data'] as $bodyItemKey => $bodyItem){
                $body['orderMethods'][$bodyItemKey]['code'] = $bodyItem['id'];
                $body['orderMethods'][$bodyItemKey]['name'] = $bodyItem['name'];
                if (!$bodyItem['deleted_at']) {
                    $body['orderMethods'][$bodyItemKey]['active'] = true;
                }
            }
            unset($body['data']);
            break;

    }
    return json_encode($body);
}
