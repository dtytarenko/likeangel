<?php

namespace kirillbdev\WCUkrShipping\Http\Middleware;

use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class VerifyAttachTTNRequest
{
    /**
     * @param Request $request
     */
    public function handle(Request $request)
    {
        if ( ! $request->get('ttn') || ! $request->get('order_id')) {
            wp_send_json([
                'success' => false,
                'error' => 'Invalid request'
            ]);
        }
    }
}