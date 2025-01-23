<?php

namespace kirillbdev\WCUkrShipping\DB;

if (!defined('ABSPATH')) {
    exit;
}

class OptionsRepository
{
    private static $migratedFrom = [
        'np_api_cost_city' => 'wc_ukr_shipping_np_sender_city'
    ];

    /**
     * @param string $key
     * @return mixed|null
     */
    public static function getOption($key)
    {
        $defaults = [
            'wc_ukr_shipping_np_method_title' => 'Нова Пошта',
            'wcus_np_free_shipping_title_active' => 0,
            'wcus_np_free_shipping_title' => 'Нова Пошта',
            'wc_ukr_shipping_np_block_title' => 'Укажите адрес доставки',
            'wc_ukr_shipping_np_placeholder_area' => 'Выберите область',
            'wc_ukr_shipping_np_placeholder_city' => 'Выберите город',
            'wc_ukr_shipping_np_placeholder_warehouse' => 'Выберите отделение',
            'wc_ukr_shipping_np_address_title' => 'Нужна адресная доставка',
            'wc_ukr_shipping_np_address_placeholder' => 'Введите адрес',
            'wc_ukr_shipping_np_settlement_label' => 'Населенный пункт',
            'wc_ukr_shipping_np_settlement_placeholder' => 'Начните вводить слово или часть слова',
            'wc_ukr_shipping_np_street_label' => 'Улица',
            'wc_ukr_shipping_np_street_placeholder' => 'Начните вводить слово или часть слова',
            'wc_ukr_shipping_np_house_label' => 'Номер дома',
            'wc_ukr_shipping_np_house_placeholder' => '',
            'wc_ukr_shipping_np_flat_label' => 'Номер квартиры',
            'wc_ukr_shipping_np_flat_placeholder' => '',
            'wc_ukr_shipping_np_not_found_text' => 'Ничего не найдено',
            'wc_ukr_shipping_np_price_type' => 'fixed',
            'wc_ukr_shipping_np_price' => 50,
            'wc_ukr_shipping_np_block_pos' => 'billing',
            'wc_ukr_shipping_np_cargo_type' => 'Cargo',
            'wc_ukr_shipping_np_service_type' => 'WarehouseWarehouse',
            'wc_ukr_shipping_np_ttn_payer_default' => 'Sender',
            'wc_ukr_shipping_np_ttn_description' => 'Экспресс-накладная',
            'wc_ukr_shipping_np_mail_subject' => 'Для вашего заказа создана накладная',
            'wc_ukr_shipping_np_mail_tpl' => 'Здравствуйте, для Вашего заказа была сформирована экспресс-накладная: {{ttn_number}}',
            'wc_ukr_shipping_np_auto_send_mail' => 0,
            'wc_ukr_shipping_np_new_ui' => 1,
            'wc_ukr_shipping_np_address_api_ui' => 0,
            'wc_ukr_shipping_np_save_warehouse' => 0,
            'wc_ukr_shipping_np_translates_type' => WCUS_TRANSLATE_TYPE_PLUGIN,
            'wcus_redelivery_calculate' => 0,
            'wcus_np_cod_method_title' => 'Новая почта (наложенный платеж)',
            'wcus_send_from_default' => 'Warehouse',
            'wcus_np_validate_error' => 'Укажите отделение <strong>Новой Почты</strong>',
            'wcus_inject_additional_fields' => 1,
            'wcus_np_address_provider' => 'db',
            'wcus_rates_use_dimensions' => 0,
            'wcus_use_cloud_address_api' => 1,
            'wcus_tracking_auto_send' => 1,
        ];

        $result = get_option($key, $defaults[$key] ?? null);

        return $result === null ? $result : self::castOption($key, $result);
    }

    public static function getOptionV2($key, $default = null)
    {
        $defaults = [
            'address_calc_enable' => 0,
            'address_calc_type' => 'fixed',
            'address_fixed_cost' => 60,
            'cod_payment_id' => 'cod',
            'cod_payment_active' => 1,
            'cost_view_only' => 0,
            'calculate_volume_weight' => 0,
            'ttn_any_shipping' => 0,
            'ttn_pay_control_default' => 0,
            'ttn_weight_default' => 0,
            'checkout_new_ui' => 0,

            'l10n_error_settlement' => 'Выберите населенный пункт',
            'l10n_error_street' => 'Выберите улицу',
            'l10n_error_house' => 'Укажите номер дома'
        ];

        if (get_option("wcus_$key") === null) {
            if (isset(self::$migratedFrom[ $key ]) && null !== get_option(self::$migratedFrom[ $key ], null)) {
                update_option("wcus_$key", get_option(self::$migratedFrom[ $key ]));
            }
        }

        return get_option('wcus_' . $key, isset($defaults[$key]) ? $defaults[$key] : $default);
    }

    public function save($data)
    {
        // Remove license key (if present) for re-saving
        unset($data['wc_ukr_shipping']['license_key']);

        foreach ($data['wc_ukr_shipping'] as $key => $value) {
            if (is_array($value)) {
                update_option('wc_ukr_shipping_' . $key, json_encode($value));
            } else {
                update_option('wc_ukr_shipping_' . $key, $this->sanitizeOption($key, $value));
            }
        }

        update_option('wc_ukr_shipping_np_settlement_name', $this->sanitizeOption('wcus_options_settlement_name', $data['wcus_options_settlement_name']));
        update_option('wc_ukr_shipping_np_settlement_area', $this->sanitizeOption('wcus_options_settlement_area', $data['wcus_options_settlement_area']));
        update_option('wc_ukr_shipping_np_settlement_region', $this->sanitizeOption('wcus_options_settlement_region', $data['wcus_options_settlement_region']));
        update_option('wc_ukr_shipping_np_settlement_ref', $this->sanitizeOption('wcus_options_settlement_ref', $data['wcus_options_settlement_ref']));
        update_option('wc_ukr_shipping_np_settlement_full', $this->sanitizeOption('wcus_options_settlement_full', $data['wcus_options_settlement_full']));

        update_option('wc_ukr_shipping_np_street_name', $this->sanitizeOption('wcus_options_street_name', $data['wcus_options_street_name']));
        update_option('wc_ukr_shipping_np_street_ref', $this->sanitizeOption('wcus_options_street_ref', $data['wcus_options_street_ref']));
        update_option('wc_ukr_shipping_np_street_full', $this->sanitizeOption('wcus_options_street_full', $data['wcus_options_street_full']));

        // New style options (released in 1.6.0)

        foreach ($data['wcus'] as $key => $value) {
            if (is_array($value)) {
                update_option('wcus_' . $key, json_encode($value));
            } else {
                update_option('wcus_' . $key, $this->sanitizeOption($key, $value));
            }
        }

        if (!isset($data['wc_ukr_shipping']['address_shipping'])) {
            update_option('wc_ukr_shipping_address_shipping', 0);
        }

        // Save different address cost

        if (isset($data['wcus']['address_calc_enable']) && (int)$data['wcus']['address_calc_enable']) {
            update_option('wcus_address_calc_enable', 1);
            update_option('wcus_address_calc_type', $data['wcus']['address_calc_type']);

            if (isset($data['wcus']['address_fixed_cost'])) {
                update_option('wcus_address_fixed_cost', $data['wcus']['address_fixed_cost']);
            }

            if (isset($data['wcus']['address_total_cost'])) {
                update_option('wcus_address_total_cost', json_encode($data['wcus']['address_total_cost']));
            }
        }
        else {
            update_option('wcus_address_calc_enable', 0);
        }

        if (isset($data['wcus']['cod_payment_active']) && (int)$data['wcus']['cod_payment_active']) {
            update_option('wcus_cod_payment_active', 1);
        }
        else {
            update_option('wcus_cod_payment_active', 0);
        }

        if ( ! isset($data['wcus']['cost_view_only'])) {
            update_option('wcus_cost_view_only', 0);
        }

        // Flush WooCommerce Shipping Cache
        delete_option('_transient_shipping-transient-version');
    }

    public function deleteAll()
    {
        delete_option('_transient_shipping-transient-version');
    }

    private function sanitizeOption($key, $value)
    {
        $esc_keys = [
            'np_mail_tpl',
            'np_validate_error',
            'l10n_error_settlement',
            'l10n_error_street',
            'l10n_error_house'
        ];

        if (in_array($key, $esc_keys)) {
            return esc_html($value);
        }

        return sanitize_text_field($value);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    private static function castOption(string $key, $value)
    {
        $casts = [
            'wcus_np_free_shipping_title_active' => 'INT',
            'wc_ukr_shipping_np_translates_type' => 'INT',
        ];

        if (!isset($casts[$key])) {
            return $value;
        }

        switch ($casts[$key]) {
            case 'INT':
                return (int)$value;
            default:
                throw new \LogicException("Cannot cast option '$key' due wrong cast type");
        }
    }
}