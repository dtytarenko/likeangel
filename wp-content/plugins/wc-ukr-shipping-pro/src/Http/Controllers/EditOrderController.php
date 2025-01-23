<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Model\Order\AdminEditOrderData;
use kirillbdev\WCUkrShipping\Model\OrderShipping;
use kirillbdev\WCUkrShipping\Model\WCUSOrder;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class EditOrderController extends Controller
{
    public function saveShippingAddress(Request $request)
    {
        if ( ! (int)$request->get('order_id', 0)) {
            return $this->jsonResponse([
                'success' => false
            ]);
        }

        $wcOrder = wc_get_order($request->get('order_id'));

        if ( ! $wcOrder) {
            return $this->jsonResponse([
                'success' => false
            ]);
        }

        $this->clearOldOrderData($wcOrder);
        $orderData = new AdminEditOrderData($wcOrder, $request->get('shipping_address', []));

        $shippingItem = WCUSHelper::getOrderShippingMethod($wcOrder);
        $shipping = new OrderShipping($shippingItem);
        $shipping->save($orderData, true);
        $shippingItem->save();

        $order = new WCUSOrder($wcOrder);
        $order->save($orderData, true);
        $wcOrder->save();

        return $this->jsonResponse([
            'success' => true
        ]);
    }

    /**
     * @param \WC_Order $order
     */
    private function clearOldOrderData($order)
    {
        $order->set_shipping_state('');
    }
}