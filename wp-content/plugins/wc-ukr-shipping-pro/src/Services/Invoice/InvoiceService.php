<?php

namespace kirillbdev\WCUkrShipping\Services\Invoice;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\DB\Repositories\InvoiceRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiException;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Model\Invoice\Invoice;

if ( ! defined('ABSPATH')) {
    exit;
}

class InvoiceService
{
    /**
     * @var NovaPoshtaApi
     */
    private $api;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * InvoiceService constructor.
     * @param NovaPoshtaApi $api
     * @param InvoiceRepository $invoiceRepository
     */
    public function __construct($api, $invoiceRepository)
    {
        $this->api = $api;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * @param Invoice $invoice
     * @throws ApiServiceException|ApiException
     */
    public function createApiInvoice($invoice)
    {
        $ttnResponse = $this->api->createTTN($invoice->serialize());

        if ($ttnResponse['success']) {
            $invoice->setDocumentNumber($ttnResponse['data'][0]['IntDocNumber']);
            $invoice->setRef($ttnResponse['data'][0]['Ref']);
            $invoice->setId($this->invoiceRepository->createInvoice($invoice));
        }
        else {
            throw new ApiException($ttnResponse['errors'], 'TTN creating failed.');
        }
    }
}