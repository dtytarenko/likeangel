<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\Base\TTNBase;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\Concrete\DoorsDoorsTTN;
use kirillbdev\WCUkrShipping\Concrete\DoorsWarehouseTTN;
use kirillbdev\WCUkrShipping\Concrete\WarehouseDoorsTTN;
use kirillbdev\WCUkrShipping\Concrete\WarehouseWarehouseTTN;
use kirillbdev\WCUkrShipping\Contracts\NotificatorInterface;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\TTNRepository;
use kirillbdev\WCUkrShipping\Exceptions\TTNServiceException;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\Document\TTNService;
use kirillbdev\WCUkrShipping\Services\TrackingsMockService;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;
use kirillbdev\WCUkrShipping\Model\TTNFormData;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNController extends Controller
{
    private TrackingsMockService $trackingsMockService;
    private AutomationService $automationService;
    private TTNRepository $ttnRepository;

    public function __construct(
        TrackingsMockService $trackingsMockService,
        AutomationService $automationService,
        TTNRepository $ttnRepository
    ) {
        $this->trackingsMockService = $trackingsMockService;
        $this->automationService = $automationService;
        $this->ttnRepository = $ttnRepository;
    }

    /**
     * @param Request $request
     */
    public function saveTTN($request)
    {
        $data = new TTNFormData($request->all());
        $ttn = $this->makeTTN($data->getServiceType());

        $result = $ttn->save($data);

        $repository = new NovaPoshtaRepository();
        $ttnId = $repository->saveTTN($result, $data->getCommonData('order_id', 0));

        // Trackings API
        $autoTracking = $request->get('options', [])['autoTracking'] ?? 0;
        if ((int)$autoTracking === 1) {
            $this->trackingsMockService->createTracking($result['IntDocNumber']);
        }
        $this->executeAutomation('ttn_created', $ttnId, (int)$data->getCommonData('order_id'));

        if ((int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_auto_send_mail')) {
            wcus_container_singleton(NotificatorInterface::class)->notifyUserByOrderId($data->getCommonData('order_id', 0));
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'ttn_db_id' => $ttnId,
                'ttn' => $result,
                'order_url' => get_admin_url( null, 'post.php?post=' . (int)$data->getCommonData('order_id') . '&action=edit')
            ]
        ]);
    }

    public function attachTTN(Request $request): ResponseInterface
    {
        try {
            /** @var TTNService $ttnService */
            $ttnService = wcus_container()->make(TTNService::class);
            $id = $ttnService->attachTTN(
                $request->get('ttn'),
                (int)$request->get('order_id'),
                (int)$request->get('add_to_tracking') === 1
            );
            $this->executeAutomation('ttn_attached', $id, (int)$request->get('order_id'));

            return $this->jsonResponse([
                'success' => true
            ]);
        } catch (TTNServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteTTN(Request $request)
    {
        try {
            /** @var TTNService $ttnService */
            $ttnService = wcus_container()->make(TTNService::class);
            $ttn = $ttnService->deleteTTN((int)$request->get('ttn_id', 0));
            $this->executeAutomation('ttn_deleted', (array)$ttn, (int)$ttn->order_id);

            return $this->jsonResponse([
                'success' => true
            ]);
        }
        catch (TTNServiceException $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function makeTTN(string $serviceType): ?TTNBase
    {
        switch ($serviceType) {
            case 'WarehouseWarehouse':
                return new WarehouseWarehouseTTN();
            case 'WarehouseDoors':
                return new WarehouseDoorsTTN();
            case 'DoorsDoors':
                return new DoorsDoorsTTN();
            case 'DoorsWarehouse':
                return new DoorsWarehouseTTN();
        }

        return null;
    }

    private function executeAutomation(string $event, $ttnId, int $orderId): void
    {
        if (is_array($ttnId)) {
            $ttn = $ttnId;
        } else {
            $ttn = $this->ttnRepository->findById($ttnId);
        }
        $order = wc_get_order($orderId);

        if ($ttn === null || !$order) {
            return;
        }

        $this->automationService->executeEvent(
            $event,
            new Context($event, $order, (array)$ttn)
        );
    }
}
