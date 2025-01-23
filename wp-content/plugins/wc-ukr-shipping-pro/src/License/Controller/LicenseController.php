<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\License\Controller;

use kirillbdev\WCUkrShipping\License\Service\LicenseService;
use kirillbdev\WCUSCore\Http\Contracts\ResponseInterface;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class LicenseController extends Controller
{
    private LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function verifyLicense(Request $request): ResponseInterface
    {
        if (empty($request->get('licenseKey'))) {
            return $this->jsonResponse([
                'success' => false,
                'data' => [
                    'License key validation error',
                ]
            ]);
        }

        try {
            return $this->jsonResponse([
                'success' => true,
                'data' => $this->licenseService->verifyLicense($request->get('licenseKey')),
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => [
                    $e->getMessage(),
                ]
            ]);
        }
    }

    public function deactivateLicense(Request $request): ResponseInterface
    {
        $licenseKey = wc_ukr_shipping_get_option('wc_ukr_shipping_license_key');
        if (!$licenseKey) {
            update_option('wcus_license_connected', 0);
            return $this->jsonResponse([
                'success' => true,
            ]);
        }

        try {
            $this->licenseService->deactivateLicense($licenseKey);
        } catch (\Exception $e) {
            update_option('wcus_license_connected', 0);
        } finally {
            return $this->jsonResponse([
                'success' => true,
            ]);
        }
    }
}
