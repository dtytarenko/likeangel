<?php

namespace kirillbdev\WCUkrShipping\Operations;

use kirillbdev\WCUkrShipping\Contracts\NotificatorInterface;
use kirillbdev\WCUkrShipping\Exceptions\ApiException;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Services\Invoice\InvoiceInfoService;
use kirillbdev\WCUkrShipping\Services\Invoice\InvoiceService;
use kirillbdev\WCUkrShipping\Services\Invoice\RecipientService;
use kirillbdev\WCUkrShipping\Services\Invoice\SenderService;
use kirillbdev\WCUkrShipping\Model\Invoice\Invoice;

if ( ! defined('ABSPATH')) {
    exit;
}

class AutoInvoiceOperation
{
    /**
     * @var SenderService
     */
    private $senderService;

    /**
     * @var RecipientService
     */
    private $recipientService;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceInfoService
     */
    private $invoiceInfoService;

    /**
     * @var NotificatorInterface
     */
    private $notificator;

    /**
     * AutoInvoiceOperation constructor.
     * @param SenderService $senderService
     * @param RecipientService $recipientService
     * @param InvoiceService $invoiceService
     * @param InvoiceInfoService $invoiceInfoService
     * @param NotificatorInterface $notificator
     */
    public function __construct(
        $senderService,
        $recipientService,
        $invoiceService,
        $invoiceInfoService,
        $notificator
    ) {
        $this->senderService = $senderService;
        $this->recipientService = $recipientService;
        $this->invoiceService = $invoiceService;
        $this->invoiceInfoService = $invoiceInfoService;
        $this->notificator = $notificator;
    }

    /**
     * @param int $orderId
     * @return Invoice
     * @throws ApiException|ApiServiceException
     */
    public function createInvoiceFromOrderId($orderId)
    {
        $info = $this->invoiceInfoService->getInfoFromOrderId($orderId);
        $backwardDelivery = $this->invoiceInfoService->getBackwardDelivery();
        $paymentControl = $this->invoiceInfoService->getPaymentControl();
        $sender = $this->senderService->getSenderInfo();
        $recipient = $this->recipientService->getRecipientFromOrderId($orderId);
        $invoice = new Invoice($info, $sender, $recipient, $orderId);

        if ($paymentControl !== null) {
            $invoice->setPaymentControl($paymentControl);
        } elseif ($backwardDelivery !== null) {
            $invoice->setBackwardDelivery($backwardDelivery);
        }

        if (in_array($invoice->getServiceType(), [ Invoice::$SERVICE_DOORS_WAREHOUSE, Invoice::$SERVICE_WAREHOUSE_WAREHOUSE ])) {
            $this->recipientService->createPrivatePerson($invoice->recipient);
        }

        $this->invoiceService->createApiInvoice($invoice);
        $this->notificator->notifyUserByOrderId($orderId);

        return $invoice;
    }
}