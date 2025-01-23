<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\License\Service;

use kirillbdev\WCUkrShipping\Exceptions\Cloud\CloudCommunicationException;

if ( ! defined('ABSPATH')) {
    exit;
}

class LicenseService
{
    private const API_URL = 'https://kirillbdev.pro/api/v2';

    public function getLicenseInfo(string $licenseKey): array
    {
        $response = $this->sendRequest('/license/info', [
            'license_key' => $licenseKey,
            'site_url' => $this->getSiteUrl(),
        ]);

        if (!$response['success']) {
            throw new \Exception("External API error: '{$response['errorMessage']}'");
        }

        return $response['data'];
    }

    public function verifyLicense(string $licenseKey): array
    {
        $response = $this->sendRequest('/license/verify', [
            'license_key' => $licenseKey,
            'site_url' => $this->getSiteUrl(),
            'product' => 'wc-ukr-shipping-pro',
        ]);

        if ($response['success']) {
            update_option('wc_ukr_shipping_license_key', $licenseKey);
            update_option('wcus_license_connected', 1);

            return $this->getLicenseInfo($licenseKey);
        }

        throw new \Exception("External API error: '{$response['errorMessage']}'");
    }

    public function deactivateLicense(string $licenseKey): void
    {
        $response = $this->sendRequest('/license/deactivate', [
            'license_key' => $licenseKey,
            'site_url' => $this->getSiteUrl(),
        ]);

        if ($response['success']) {
            update_option('wcus_license_connected', 0);
        } else {
            throw new \Exception("External API error: '{$response['errorMessage']}'");
        }
    }

    public function checkUpdates(string $licenseKey): ?array
    {
        $response = $this->sendRequest('/updates/check', [
            'current_version' => WCUS_PLUGIN_VERSION,
            'license_key' => $licenseKey,
            'site_url' => $this->getSiteUrl(),
        ]);

        if ($response['success']) {
            return $response['data']['package'];
        }

        throw new \Exception("External API error: '{$response['errorMessage']}'");
    }

    private function sendRequest(string $uri, array $payload): array
    {
        $response = wp_remote_post(self::API_URL . $uri, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            throw new CloudCommunicationException(0, "An unexpected error occurred while sending the request");
        }

        $code = (int)wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            throw new CloudCommunicationException($code, "License provider return bad response code - $code");
        }

        $json = json_decode($response['body'], true);
        if (json_last_error()) {
            throw new CloudCommunicationException($code, "License provider return malformed response");
        }

        return $json;
    }

    private function getSiteUrl(): string
    {
        return site_url();
    }
}
