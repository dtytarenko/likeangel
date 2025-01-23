<?php

namespace kirillbdev\WCUkrShipping\Services\Document;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Component\Automation\Context;
use kirillbdev\WCUkrShipping\DB\Repositories\TTNRepository;
use kirillbdev\WCUkrShipping\Dto\Repository\CreateTTNDto;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Exceptions\TTNServiceException;
use kirillbdev\WCUkrShipping\Model\Event\Document\CarrierStatusChangedEvent;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\TrackingsMockService;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNService
{
    private NovaPoshtaApi $api;
    private TTNRepository $ttnRepository;
    private AutomationService $automationService;
    private TrackingsMockService $trackingService;

    public function __construct(
        NovaPoshtaApi $api,
        TTNRepository $ttnRepository,
        AutomationService $automationService,
        TrackingsMockService $trackingService
    ) {
        $this->api = $api;
        $this->ttnRepository = $ttnRepository;
        $this->automationService = $automationService;
        $this->trackingService = $trackingService;
    }

    /**
     * @throws TTNServiceException
     */
    public function attachTTN(string $ttnNumber, int $orderId, bool $addToTracking): int
    {
        try {
            $result = $this->api->getDocumentStatus($ttnNumber);

            if (!$result['success']) {
                $error = isset($result['errors']) && is_array($result['errors'])
                    ? reset($result['errors'])
                    : 'Unknown error';
                throw new TTNServiceException(
                    sprintf("Can't attach ttn #%s: %s", $ttnNumber, $error)
                );
            } elseif (in_array($result['data'][0]['StatusCode'], ['2', '3'])) {
                throw new TTNServiceException(
                    sprintf("Can't attach ttn #%s: Wrong status code", $ttnNumber)
                );
            }

            $data = $result['data'][0];
            $ttnDto = new CreateTTNDto();
            $ttnDto->orderId = $orderId;
            $ttnDto->ttnId = $ttnNumber;
            $ttnDto->ttnRef = '';
            $ttnDto->status = $data['Status'];
            $ttnDto->statusCode = $data['StatusCode'];
            /** @var TTNRepository $ttnRepository */
            $ttnRepository = wcus_container()->make(TTNRepository::class);
            $id = $ttnRepository->createTTN($ttnDto);
            if ($addToTracking) {
                $this->trackingService->createTracking($ttnNumber);
            }

            return $id;
        } catch (ApiServiceException $e) {
            throw new TTNServiceException($e->getMessage(), $e);
        }
    }

    public function deleteTTN(int $id): \stdClass
    {
        /** @var TTNRepository $ttnRepository */
        $ttnRepository = wcus_container()->make(TTNRepository::class);
        $ttn = $ttnRepository->findById($id);

        if ( ! $ttn) {
            throw new TTNServiceException('TTN not found.');
        }

        try {
            if ($ttn->ttn_ref) {
                $this->api->deleteTTN($ttn->ttn_ref);
            }
            $ttnRepository->deleteById($id);

            return $ttn;
        } catch (ApiServiceException $e) {
            throw new TTNServiceException($e->getMessage(), $e);
        }
    }

    public function updateStatus(string $ttnNumber, string $cloudStatus, int $status, string $statusAdditional): void
    {
        $availableStatuses = [1, 2, 3, 4, 41, 5, 6, 7, 8, 9, 10, 11, 12, 101, 102, 103, 104, 105, 106, 111, 112];
        if (!in_array($status, $availableStatuses, true)) {
            throw new \InvalidArgumentException("Invalid ttn status $status");
        }

        $ttnList = $this->ttnRepository->findByNumber($ttnNumber);
        if (count($ttnList) === 0) {
            throw new \LogicException("Unable to find TTNs by number $ttnNumber");
        }

        foreach ($ttnList as $ttn) {
            $needToDispatchCloudEvent = false;
            $needToDispatchCarrierEvent = false;
            if ($ttn['cloud_status'] !== $cloudStatus) {
                $needToDispatchCloudEvent = true;
            }
            if ((int)$ttn['status_code'] !== $status) {
                $needToDispatchCarrierEvent = true;
            }
            if (!$needToDispatchCloudEvent && !$needToDispatchCarrierEvent) {
                continue;
            }

            $oldStatus = $ttn['status_code'];
            $oldStatusText = $ttn['status'];
            $this->ttnRepository->updateStatus((int)$ttn['id'], $cloudStatus, $status, $statusAdditional);
            $ttn = (array)$this->ttnRepository->findById((int)$ttn['id']); // Load fresh data

            $order = wc_get_order((int)$ttn['order_id']);
            if ($order && $needToDispatchCloudEvent) {
                $this->automationService->executeEvent(
                    'cloud_status_changed',
                    new Context('cloud_status_changed', $order, $ttn)
                );
            }
            if ($order && $needToDispatchCarrierEvent) {
                $this->automationService->executeEvent(
                    'carrier_status_changed',
                    new Context('carrier_status_changed', $order, $ttn)
                );

                /**
                 * Fires after TTN carrier status was updated.
                 *
                 * @since 1.16.7
                 */
                do_action(
                    'wcus_ttn_carrier_status_updated',
                    new CarrierStatusChangedEvent(
                        $oldStatus,
                        $oldStatusText,
                        $status,
                        $statusAdditional,
                        $ttn,
                        $order
                    )
                );
            }
        }
    }
}
