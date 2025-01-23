<?php

namespace kirillbdev\WCUkrShipping\DB;

if (!defined('ABSPATH')) {
    exit;
}

class NovaPoshtaRepository
{
    public function getAreas()
    {
        global $wpdb;

        $areas = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_ukr_shipping_np_areas", ARRAY_A);

        // todo: вынести в сервис
        $mapped = [];

        foreach ($areas as $area) {
            $mapped[$area['ref']] = $area;
        }

        return array_values(apply_filters('wcus_get_areas', $mapped));
    }

    /**
     * @param string $ref
     *
     * @return array|null
     */
    public function getAreaByRef($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT description 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_areas 
            WHERE ref = '" . esc_attr($ref) . "'
        ", ARRAY_A);
    }

    /**
     * testing
     *
     * @param string $ref
     *
     * @return array|null
     */
    public function getAreaByRefV2($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_areas 
            WHERE ref = '" . esc_attr($ref) . "'
        ", ARRAY_A);
    }

    public function getAllCities()
    {
        global $wpdb;

        return $wpdb->get_results("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_cities 
            ORDER BY description", ARRAY_A
        );
    }

    public function getCities($areaRef)
    {
        global $wpdb;

        return $wpdb->get_results("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_cities 
            WHERE area_ref='" . esc_attr($areaRef) . "' 
            ORDER BY description", ARRAY_A
        );
    }

    /**
     * @param string $ref
     *
     * @return array|null
     */
    public function getCityByRef($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT description 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_cities 
            WHERE ref = '" . esc_attr($ref) . "'
    ", ARRAY_A);
    }

    /**
     * testing
     *
     * @param string $ref
     *
     * @return array|null
     */
    public function getCityByRefV2($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_cities 
            WHERE ref = '" . esc_attr($ref) . "'
        ", ARRAY_A);
    }

    public function getWarehouses($cityRef)
    {
        global $wpdb;

        $warehouses = $wpdb->get_results("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_warehouses 
            WHERE city_ref='" . esc_attr($cityRef) . "' 
            ORDER BY number ASC
        ", ARRAY_A);

        if (0 === (int)get_option('wcus_show_poshtomats', 1)) {
            return array_filter($warehouses, function ($warehouse) {
                return false === strpos($warehouse['description'], 'Поштомат');
            });
        }

        return $warehouses;
    }

    /**
     * @param string $ref
     *
     * @return array|null
     */
    public function getWarehouseByRef($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT description 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_warehouses 
            WHERE ref = '" . esc_attr($ref) . "'
        ", ARRAY_A);
    }

    /**
     * testing
     *
     * @param string $ref
     *
     * @return array|null
     */
    public function getWarehouseByRefV2($ref)
    {
        global $wpdb;

        return $wpdb->get_row("
            SELECT * 
            FROM {$wpdb->prefix}wc_ukr_shipping_np_warehouses 
            WHERE ref = '" . esc_attr($ref) . "'
        ", ARRAY_A);
    }

    public function saveTTN($data, $orderId = 0)
    {
        global $wpdb;

        $dateCreated = date('Y-m-d H:i:s');

        $wpdb->query("
            INSERT INTO {$wpdb->prefix}wc_ukr_shipping_np_ttn (order_id, ttn_id, ttn_ref, status, status_code, created_at, updated_at)
            VALUES ('" . (int)$orderId . "', '{$data['IntDocNumber']}', '{$data['Ref']}', 'Відправник самостійно створив цю накладну, але ще не надав до відправки', '1', '$dateCreated', '$dateCreated')
        ");

        return $wpdb->insert_id;
    }

    public function dropTables()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS wc_ukr_shipping_np_areas");
        $wpdb->query("DROP TABLE IF EXISTS wc_ukr_shipping_np_cities");
        $wpdb->query("DROP TABLE IF EXISTS wc_ukr_shipping_np_warehouses");
        $wpdb->query("DROP TABLE IF EXISTS wc_ukr_shipping_np_ttn");
        $wpdb->query("DROP TABLE IF EXISTS wc_ukr_shipping_np_api_cache");

        delete_option('wcus_migrations_history');
    }
}
