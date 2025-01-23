<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\DB\Repositories\TTNRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiException;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Operations\AutoInvoiceOperation;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\Backend\OrderService;
use kirillbdev\WCUkrShipping\Services\TrackingsMockService;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OrdersController extends Controller
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var AutoInvoiceOperation
     */
    private $autoInvoiceOperation;

    private TrackingsMockService $trackingsMockService;
    private AutomationService $automationService;
    private TTNRepository $ttnRepository;

    public function __construct(
        OrderService $orderService,
        AutoInvoiceOperation $autoInvoiceOperation,
        TrackingsMockService $trackingsMockService,
        AutomationService $automationService,
        TTNRepository $ttnRepository
    ) {
        $this->orderService = $orderService;
        $this->autoInvoiceOperation = $autoInvoiceOperation;
        $this->trackingsMockService = $trackingsMockService;
        $this->automationService = $automationService;
        $this->ttnRepository = $ttnRepository;
    }

    /**
     * @param Request $request
     */
    public function getOrders($request)
    {
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'orders' => $this->orderService->getOrdersFromRequest($request),
                'count_pages' => $this->orderService->getCountPagesFromRequest($request)
            ]
        ]);
    }

    /**
     * @param Request $request
     */
    public function generateTTN($request)
    {
        try {
            $invoice = $this->autoInvoiceOperation->createInvoiceFromOrderId($request->get('order_id', 0));
            // Trackings API
            $autoTracking = $request->get('options', [])['autoTracking'] ?? 0;
            if ((int)$autoTracking === 1) {
                $this->trackingsMockService->createTracking((string)$invoice->documentNumber);
            }

            // Execute automation
            $ttn = $this->ttnRepository->findById($invoice->id);
            $order = wc_get_order((int)$request->get('order_id'));
            if ($ttn && $order) {
                $this->automationService->executeEvent(
                    'ttn_created',
                    new Context('ttn_created', $order, (array)$ttn)
                );
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'ttn_id' => $invoice->documentNumber,
                    'ttn_db_id' => $invoice->id,
                    'ttn_ref' => $invoice->ref,
                    // todo: next line should be refactored
                    'carrier_status' => '1',
                    'carrier_status_additional' => 'Відправник самостійно створив цю накладну, але ще не надав до відправки',
                ]
            ]);
        }
        catch (ApiServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
        catch (ApiException $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => $e->getErrors()
            ]);
        }
    }
}