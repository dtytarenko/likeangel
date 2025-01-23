<?php

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUkrShipping\License\Controller\LicenseController;
use kirillbdev\WCUkrShipping\License\Service\LicenseService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;

if ( ! defined('ABSPATH')) {
    exit;
}

class Updater implements ModuleInterface
{
    private const PLUGIN_SLOG = 'wc-ukr-shipping-pro';

    private LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function init(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkForUpdates']);
    }

    public function routes()
    {
        return [
            new Route('wcus_verify_license', LicenseController::class, 'verifyLicense'),
            new Route('wcus_deactivate_license', LicenseController::class, 'deactivateLicense'),
        ];
    }

    public function checkForUpdates($transient)
    {
        if (empty($transient->checked)
            || !wc_ukr_shipping_get_option('wc_ukr_shipping_license_key')
            || (int)wc_ukr_shipping_get_option('wcus_license_connected') !== 1) {
            return $transient;
        }

        $packageKey = self::PLUGIN_SLOG . '/' . self::PLUGIN_SLOG . '.php';
        try {
            $package = $this->licenseService->checkUpdates(wc_ukr_shipping_get_option('wc_ukr_shipping_license_key'));

            if ($package === null) {
                unset($transient->response[$packageKey]);
            } else {
                $transient->response[$packageKey] = json_decode(json_encode($package));
            }
        } catch (\Exception $e) {
            unset($transient->response[$packageKey]);
        }

        return $transient;
    }
}
