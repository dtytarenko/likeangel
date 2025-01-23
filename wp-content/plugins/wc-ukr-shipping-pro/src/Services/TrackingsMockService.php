<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

class TrackingsMockService
{
    public function createTracking(string $trackingNumber): void
    {
        $licenseKey = wc_ukr_shipping_get_option('wc_ukr_shipping_license_key');
        $isConnected = (int)wc_ukr_shipping_get_option('wcus_license_connected') === 1;
        if (!$licenseKey || !$isConnected) {
            return;
        }

        $response = wp_remote_post('https://api.wcukraineshipping.com/v2/trackings/create', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Wcus-License-Key' => $licenseKey,
                'Wcus-Site-Url' => site_url(),
            ],
            'body' => json_encode([
                'tracking_number' => $trackingNumber,
                'carrier' => 'nova_poshta',
            ]),
            'timeout' => 5,
        ]);
    }
}
