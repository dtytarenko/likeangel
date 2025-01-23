<?php

namespace kirillbdev\WCUkrShipping\Services\Backend;

use kirillbdev\WCUkrShipping\DB\Mappers\OrderListMapper;
use kirillbdev\WCUkrShipping\DB\Repositories\OrderRepositoryInterface;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderService
{
    private OrderListMapper $orderListMapper;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository, OrderListMapper $orderListMapper)
    {
        $this->orderRepository = $orderRepository;
        $this->orderListMapper = $orderListMapper;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getOrdersFromRequest($request)
    {
        $limit = (int)$request->get('limit', 20);
        $offset = ((int)$request->get('page', 1) - 1) * $limit;
        $orders = $this->orderRepository->getOrdersWithTTN($offset, $limit);

        foreach ($orders as &$order) {
            $order['info'] = $this->orderRepository->getOrderInfo($order['id']);
            $order['shipping_method'] = $this->orderRepository->getOrderShippingMethod($order['id']);
        }

        return $this->orderListMapper->fetchOrders($orders);
    }

    /**
     * @param Request $request
     * @return int
     */
    public function getCountPagesFromRequest($request)
    {
        return $this->orderRepository->getCountOrderPages((int)$request->get('limit', 20));
    }
}