<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUSCore\Facades\DB;

class HposOrderRepository implements OrderRepositoryInterface
{
    public function getOrdersWithTTN(int $offset, int $limit): array
    {
        return DB::table(DB::prefixedTable('wc_orders') . ' as o')
            ->leftJoin(DB::prefixedTable('wc_ukr_shipping_np_ttn') . ' as ttn', 'o.id = ttn.order_id')
            ->where('o.status', '!=', 'trash')
            ->orderBy('o.id', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->get([
                'o.id',
                'o.date_created_gmt as created_at',
                'o.status',
                'ttn.ttn_id',
                'ttn.id as ttn_db_id',
                'ttn.ttn_ref',
                'ttn.cloud_status',
                'ttn.status as carrier_status_additional',
                'ttn.status_code as carrier_status'
            ]);
    }

    public function getOrderInfo(int $orderId): array
    {
        $order = wc_get_order($orderId);
        if ( ! $order) {
            return [];
        }

        // todo: refactor, add shared structure between two repos
        return [
            [
                'meta_key' => '_billing_last_name',
                'meta_value' => $order->get_billing_last_name(),
            ],
            [
                'meta_key' => '_billing_first_name',
                'meta_value' => $order->get_billing_first_name(),
            ],
            [
                'meta_key' => '_order_total',
                'meta_value' => $order->get_total(),
            ],
        ];
    }

    public function getOrderShippingMethod(int $orderId): ?\stdClass
    {
        return DB::table(DB::woocommerceOrderItems())
            ->where('order_id', (int)$orderId)
            ->where('order_item_type', 'shipping')
            ->first([
                'order_item_name'
            ]);
    }

    public function getCountOrderPages(int $limit): int
    {
        $pageCount = DB::table(DB::prefixedTable('wc_orders'))
            ->where('status', '!=', 'trash')
            ->count();

        return ceil($pageCount / $limit);
    }
}