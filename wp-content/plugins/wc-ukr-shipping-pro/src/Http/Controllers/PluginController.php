<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class PluginController extends Controller
{
    private const RESOURCE_URL = 'https://kirillbdev.pro/api/v1/products/changelog';
    private const PLUGIN_UID = 'wc-ukr-shipping-pro';

    public function changelog(Request $request)
    {
        $response = wp_remote_get(self::RESOURCE_URL . '?product=' . self::PLUGIN_UID, [
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $response->get_error_message()
            ]);
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Bad request'
            ]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $this->jsonResponse([
            'success' => true,
            'data' => $body['changelog']
        ]);
    }
}