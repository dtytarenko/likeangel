<?php

namespace kirillbdev\WCUkrShipping\Http\Controllers;

use kirillbdev\WCUkrShipping\DB\OptionsRepository;
use kirillbdev\WCUSCore\Http\Controller;
use kirillbdev\WCUSCore\Http\Request;

if ( ! defined('ABSPATH')) {
    exit;
}

class OptionsController extends Controller
{
    /**
     * @var OptionsRepository
     */
    private $optionsRepository;

    /**
     * OptionsController constructor.
     * @param OptionsRepository $optionsRepository
     */
    public function __construct($optionsRepository)
    {
        $this->optionsRepository = $optionsRepository;
    }

    /**
     * @param Request $request
     */
    public function saveOptions($request)
    {
        parse_str($request->get('body'), $data);
        $result = $this->validateData($data);

        if (true !== $result) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => $result
            ]);
        }

        $this->optionsRepository->save($data);

        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'api_key' => get_option('wc_ukr_shipping_np_api_key', '')
            ]
        ]);
    }

    /**
     * @param array $data
     * @return array|bool
     */
    private function validateData($data)
    {
        $errors = [];

        if ( ! isset($data['wc_ukr_shipping']['np_method_title']) || strlen($data['wc_ukr_shipping']['np_method_title']) === 0) {
            $errors['wc_ukr_shipping_np_method_title'] = 'Заполните поле';
        }

        if ( ! isset($data['wc_ukr_shipping']['np_address_title']) || strlen($data['wc_ukr_shipping']['np_address_title']) === 0) {
            $errors['wc_ukr_shipping_np_address_title'] = 'Заполните поле';
        }

        if (!empty($data['wcus']['cloud_token'])) {
            if ((int)$data['wcus']['checkout_new_ui'] === 0) {
                $errors['wcus_cloud_token'] = __('This feature works only with new UI', 'wc-ukr-shipping-pro');
            }
        }

        return $errors ?: true;
    }
}