<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\Model\Invoice\Invoice;

if ( ! defined('ABSPATH')) {
    exit;
}

class InvoiceRepository
{
    /**
     * @param Invoice $invoice
     */
    public function createInvoice($invoice)
    {
        global $wpdb;

        $dateCreated = date('Y-m-d H:i:s');

        $wpdb->query("
          INSERT INTO {$wpdb->prefix}wc_ukr_shipping_np_ttn (order_id, ttn_id, ttn_ref, status, status_code, created_at, updated_at)
          VALUES ('" . (int)$invoice->orderId . "', '{$invoice->documentNumber}', '{$invoice->ref}', 'Відправник самостійно створив цю накладну, але ще не надав до відправки', '1', '$dateCreated', '$dateCreated')
        ");

        return $wpdb->insert_id;
    }

    /**
     * @param string $ref
     */
    public function deleteByRef($ref)
    {
        global $wpdb;

        $wpdb->delete("{$wpdb->prefix}wc_ukr_shipping_np_ttn", [
            'ttn_ref' => $ref
        ]);
    }
}