<?php

namespace kirillbdev\WCUkrShipping\Modules\Document;

use kirillbdev\WCUkrShipping\Http\Controllers\CounterpartyController;
use kirillbdev\WCUkrShipping\Http\Controllers\TTNController;
use kirillbdev\WCUkrShipping\Services\Document\TTNService;
use kirillbdev\WCUSCore\Http\Routing\Route;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNCreator implements ModuleInterface
{
    private TTNService $ttnService;

    public function __construct(TTNService $ttnService)
    {
        $this->ttnService = $ttnService;
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('rest_api_init', [$this, 'registerTrackingCallback']);
    }

    /**
     * @return Route[]
     */
    public function routes()
    {
        return [
            new Route('wcus_counterparty_create', CounterpartyController::class, 'create'),
            new Route('wcus_counterparty_contact_create', CounterpartyController::class, 'createContact'),
            new Route('wcus_counterparty_create_organization', CounterpartyController::class, 'createOrganization'),
            new Route('wcus_ttn_save', TTNController::class, 'saveTTN')
        ];
    }

    public function registerTrackingCallback(): void
    {
        register_rest_route('wc-ukr-shipping/v1', 'track', [
            'methods' => 'POST',
            'callback' => [$this, 'trackStatus'],
            'permission_callback' => function (WP_REST_Request $request) {
                return true;
            }
        ]);
    }

    function trackStatus(WP_REST_Request $request): WP_REST_Response
    {
        $json = json_decode($request->get_body(), true);
        if (json_last_error() || empty($json['signature'])) {
            return new WP_REST_Response([
                'success' => false,
                'errorMessage' => 'Bad request format',
            ], 200);
        }

        $key = wc_ukr_shipping_get_option('wc_ukr_shipping_license_key');
        if (empty($key)) {
            return new WP_REST_Response([
                'success' => false,
                'errorMessage' => 'Internal server error',
            ], 200);
        }

        // Compare signatures
        $signature = hash('sha256', "$key." . base64_encode(json_encode($json['data'] ?? [], JSON_UNESCAPED_UNICODE)));
        if ($signature !== $json['signature']) {
            return new WP_REST_Response([
                'success' => false,
                'errorMessage' => 'Request not valid',
            ], 200);
        }

        $data = $json['data'];

        try {
            $this->ttnService->updateStatus(
                $data['tracking_number'],
                $data['status'],
                (int)$data['carrier_status'],
                $data['carrier_status_additional']
            );
        } catch (\Exception $e) {
            // Do nothing yet
        }

        return new WP_REST_Response([
            'success' => true,
        ], 200);
    }
}
