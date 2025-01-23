<?php

namespace kirillbdev\WCUkrShipping\DB\Mappers;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrderListMapper
{
    /**
     * @param array $data
     * @return array
     */
    public function fetchOrders($data)
    {
        $orders = [];

        foreach ($data as $item) {
            $order = [
                'id' => (int)$item['id'],
                'selected' => false,
                'ttn_id' => $item['ttn_id'],
                'ttn_db_id' => $item['ttn_db_id'],
                'ttn_ref' => $item['ttn_ref'],
                'cloud_status' => $item['cloud_status'],
                'carrier_status' => $item['carrier_status'],
                'carrier_status_additional' => $item['carrier_status_additional'],
                'edit_url' => get_admin_url(null, 'post.php?post=' . (int)$item['id'] . '&action=edit'),
                'status' => wc_get_order_status_name($item['status']),
                'state' => 'default',
                'shipping_method' => $item['shipping_method'] ? $item['shipping_method']->order_item_name : '',
                'errors' => [],
                'created_at' => date('d.m.Y H:i', strtotime($item['created_at']))
            ];

            foreach ($item['info'] as $info) {
                $order[ $this->mapKey($info['meta_key']) ] = $this->mapValue($info['meta_key'], $info['meta_value']);
            }

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * @param string $key
     * @return string
     */
    private function mapKey($key)
    {
        switch ($key) {
            case '_billing_first_name':
                return 'firstname';
            case '_billing_last_name':
                return 'lastname';
            case '_order_total':
                return 'total';
            default:
                return $key;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    private function mapValue($key, $value)
    {
        switch ($key) {
            case '_order_total':
                return wc_price($value);
            default:
                return $value;
        }
    }
}