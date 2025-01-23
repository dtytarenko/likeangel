<?php

namespace kirillbdev\WCUkrShipping\DB;

if (!defined('ABSPATH')) {
    exit;
}

class TTNRepository
{
    public function getTTNById($id)
    {
        global $wpdb;

        return $wpdb->get_row("
			SELECT *
			FROM {$wpdb->prefix}wc_ukr_shipping_np_ttn
			WHERE id = '" . (int)$id . "'
		", ARRAY_A);
    }

    public function getTTNByOrderId($orderId)
    {
        global $wpdb;

        return $wpdb->get_row("
			SELECT *
			FROM {$wpdb->prefix}wc_ukr_shipping_np_ttn
			WHERE order_id = '" . (int)$orderId . "'
		", ARRAY_A);
    }

    public function updateStatus($id, $status, $statusCode)
    {
        global $wpdb;

        $wpdb->query("
            UPDATE {$wpdb->prefix}wc_ukr_shipping_np_ttn
            SET `status` = '$status', status_code = '$statusCode', updated_at = '" . date('Y-m-d H:i:s') . "'
            WHERE id = '" . (int)$id . "'
        ");
    }

    public function deleteTTN($id)
    {
        global $wpdb;

        $wpdb->query("
            DELETE FROM {$wpdb->prefix}wc_ukr_shipping_np_ttn
            WHERE id = '" . (int)$id . "'
        ");
    }
}