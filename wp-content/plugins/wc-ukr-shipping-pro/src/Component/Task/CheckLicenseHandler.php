<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Task;

use kirillbdev\WCUkrShipping\Exceptions\Cloud\CloudCommunicationException;
use kirillbdev\WCUkrShipping\License\Service\LicenseService;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckLicenseHandler implements TaskHandlerInterface
{
    private LicenseService $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function handle(?TaskInterface $task): void
    {
        $licenseKey = wc_ukr_shipping_get_option('wc_ukr_shipping_license_key');
        if ($licenseKey) {
            try {
                $licenseInfo = $this->licenseService->getLicenseInfo($licenseKey);
                if ($licenseInfo['status'] === 'expired') {
                    update_option('wcus_use_cloud_address_api', 0);
                }
            } catch (CloudCommunicationException $e) {
                // Do nothing
            } catch (\Exception $e) {
                // Do nothing
            }
        }
    }
}
