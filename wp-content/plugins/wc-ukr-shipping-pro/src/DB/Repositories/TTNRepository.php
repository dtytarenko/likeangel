<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\Dto\Repository\CreateTTNDto;
use kirillbdev\WCUSCore\Facades\DB;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNRepository
{
    public function findById(int $id): ?\stdClass
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_ttn'))
            ->where('id', $id)
            ->first();
    }

    public function findByNumber(string $ttnNumber): array
    {
        return DB::table(DB::prefixedTable('wc_ukr_shipping_np_ttn'))
            ->where('ttn_id', $ttnNumber)
            ->get();
    }

    public function createTTN(CreateTTNDto $dto): int
    {
        global $wpdb;

        DB::table(DB::prefixedTable('wc_ukr_shipping_np_ttn'))
            ->insert([
                'order_id' => $dto->orderId,
                'ttn_id' => $dto->ttnId,
                'ttn_ref' => $dto->ttnRef,
                'status' => $dto->status,
                'status_code' => $dto->statusCode
            ], [
                'order_id' => '%d'
            ]);

        return $wpdb->insert_id;
    }

    public function deleteById(int $id): void
    {
        global $wpdb;
        $wpdb->delete(DB::prefixedTable('wc_ukr_shipping_np_ttn'), [
            'id' => $id
        ], [
            'id' => '%d'
        ]);
    }

    public function updateStatus(int $id, string $cloudStatus, string $status, string $statusAdditional): void
    {
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}wc_ukr_shipping_np_ttn",
            [
                'cloud_status' => $cloudStatus,
                'status' => $statusAdditional,
                'status_code' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $id],
            [],
            ['%d']
        );
    }
}
