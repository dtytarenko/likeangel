<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Helpers\OptionsHelper;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutValidator implements ModuleInterface
{
    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('woocommerce_checkout_process', [$this, 'validateFields']);
        add_filter('woocommerce_checkout_fields', [$this, 'removeDefaultFieldsFromValidation'], 99);
    }

    public function removeDefaultFieldsFromValidation($fields)
    {
        if (!wp_doing_ajax() || empty($_POST)) {
            return $fields;
        }

        if (!$this->isNovaPoshtaSelected()) {
            unset($fields['billing']['wcus_billing_middlename']);
            unset($fields['shipping']['wcus_shipping_middlename']);
            unset($fields['shipping']['wcus_shipping_phone']);

            return $fields;
        }

        if ($this->maybeDisableDefaultFields()) {
            foreach (['billing', 'shipping'] as $type) {
                unset($fields[$type][$type . '_address_1']);
                unset($fields[$type][$type . '_address_2']);
                unset($fields[$type][$type . '_city']);
                unset($fields[$type][$type . '_state']);
                unset($fields[$type][$type . '_postcode']);
            }
        }

        if (!OptionsHelper::maybeNeedAdditionalFields()) {
            return $fields;
        }

        $type = $this->getTypeToValidate();

        if ('billing' === $type) {
            unset($fields['shipping']['wcus_shipping_phone']);
        }

        if ($this->maybeAddressShippingSelected($type)) {
            if ('billing' === $type) {
                unset($fields['shipping']['wcus_shipping_middlename']);
            } else {
                unset($fields['billing']['wcus_billing_middlename']);
            }
        } else {
            unset($fields['shipping']['wcus_shipping_middlename']);
            unset($fields['billing']['wcus_billing_middlename']);
        }

        return $fields;
    }

    public function validateFields()
    {
        if ($this->isNovaPoshtaSelected() && $this->checkoutValidationActive()) {
            $type = $this->getTypeToValidate();

            if ($this->maybeAddressShippingSelected($type)) {
                $this->validateAddressShipping($type);

                return;
            }

            $this->validateWarehouseShipping($type);
        }
    }

    private function validateAddressShipping($type)
    {
        if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) {
            if (empty($_POST['wcus_np_' . $type . '_settlement_name'])) {
                $this->printErrorNotice('checkout_error_settlement');
            }

            if (empty($_POST['wcus_np_' . $type . '_street_name'])) {
                $this->printErrorNotice('checkout_error_street');
            }

            if (empty($_POST['wcus_np_' . $type . '_house'])) {
                $this->printErrorNotice('checkout_error_house');
            }
        } else {
            if (empty($_POST['wcus_np_' . $type . '_city'])
                || empty($_POST['wcus_np_' . $type . '_custom_address'])
            ) {
                $this->printErrorNotice('validate_error');
            }
        }
    }

    private function validateWarehouseShipping($type)
    {
        if (empty($_POST['wcus_np_' . $type . '_city'])
            || empty($_POST['wcus_np_' . $type . '_warehouse'])
        ) {
            $this->printErrorNotice('validate_error');
        }
    }

    private function maybeAddressShippingSelected($type)
    {
        return isset($_POST['wcus_np_' . $type . '_custom_address_active'])
            && 1 === (int)$_POST['wcus_np_' . $type . '_custom_address_active'];
    }

    private function printErrorNotice($key)
    {
        $translator = wcus_container_singleton('translate_service');
        $translates = $translator->getTranslates();

        $message = empty($translates[ $key ])
            ? 'WCUS validation error'
            : $translates[ $key ];

        wc_add_notice(wp_specialchars_decode($message), 'error');
    }

    private function getTypeToValidate()
    {
        if (isset($_POST['ship_to_different_address']) && 1 === (int)$_POST['ship_to_different_address']) {
            return 'shipping';
        }

        return 'billing';
    }

    private function maybeDisableDefaultFields()
    {
        return isset($_POST['shipping_method']) &&
            preg_match('/^' . WC_UKR_SHIPPING_NP_SHIPPING_NAME . '.*/i', $_POST['shipping_method'][0]) &&
            apply_filters('wc_ukr_shipping_prevent_disable_default_fields', false) === false;
    }

    private function isNovaPoshtaSelected()
    {
        if (isset($_POST['shipping_method']) && preg_match('/^' . WC_UKR_SHIPPING_NP_SHIPPING_NAME . '.*/i', $_POST['shipping_method'][0])) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function checkoutValidationActive()
    {
        return true === apply_filters('wcus_checkout_validation_active', true);
    }
}