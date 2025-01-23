<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Services\StateService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class AssetLoader implements ModuleInterface
{
    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_enqueue_scripts', [ $this, 'loadAdminAssets' ]);
        add_action('admin_footer', [ $this, 'adminFooter' ]);

        add_action( 'init', function () {
            wp_set_script_translations(
                'wcus_settings_js',
                'wc-ukr-shipping-pro',
                WC_UKR_SHIPPING_PLUGIN_DIR . 'lang'
            );
        });
    }

    public function loadAdminAssets()
    {
        $screen = get_current_screen();

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

        wp_enqueue_style(
            'wcus_admin_css',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/css/admin.min.css',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/css/admin.min.css')
        );

        wp_enqueue_script(
            'wcus_core_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/core.min.js',
            [],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/core.min.js'),
            true
        );

        wp_enqueue_script(
            'wcus_ttn_global_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/ttn-global.min.js',
            ['wcus_core_js'],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/ttn-global.min.js'),
            true
        );

        $this->initSettingsScript($screen);
        $this->initOrderEditScript($screen);
        $this->initOrderListScript($screen);
        $this->initPluginsAssets($screen);
        $this->initAutomationFormAssets($screen);
        $this->initGlobals();
    }

    public function adminFooter($data)
    {
        $this->initState();
        ?>
          <div id="wcus-modals"></div>
        <?php
    }

    private function initState()
    {
        ?>
          <script>
            window.WCUS_APP_STATE = <?= json_encode(StateService::getState()); ?>;
          </script>
        <?php
    }

    private function initGlobals()
    {
        $translateService = new TranslateService();
        $i18n = $this->getFrontendTranslates();
        $i18n = apply_filters('wcus_admin_i18n', $i18n);

        $globals = [
            'homeUrl' => home_url(),
            'adminUrl' => admin_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc-ukr-shipping'),
            'lang' => $translateService->getCurrentLanguage(),
            'i18n' => $i18n,
            'orderStatuses' => wc_get_order_statuses(),
            'options' => [
                'wcus_tracking_auto_send' => (int)get_option('wcus_tracking_auto_send'),
            ]
        ];

        wp_localize_script('wcus_core_js', 'wc_ukr_shipping_globals', $globals);
    }

    private function initSettingsScript($screen)
    {
        if ('toplevel_page_wc_ukr_shipping_options' === $screen->id) {
            wp_enqueue_script(
                'wcus_settings_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/settings.min.js',
                ['wcus_core_js', 'wp-i18n'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/settings.min.js'),
                true
            );
        }
    }

    private function initOrderEditScript($screen)
    {
        $screens = [
            'shop_order',
        ];

        /** @var CustomOrdersTableController $controller */
        $controller = wcus_wc_container_safe_get(CustomOrdersTableController::class);
        if ($controller !== null && $controller ->custom_orders_table_usage_is_enabled()) {
            $screens[] = wc_get_page_screen_id('shop-order');
        }

        if (in_array($screen->id, $screens, true)) {
            wp_enqueue_script(
                'wcus_order_edit_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/order-edit.min.js',
                ['jquery', 'wcus_core_js'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/order-edit.min.js'),
                true
            );
        }
    }

    private function initOrderListScript($screen)
    {
        if ('wc-ukr-shipping_page_wc_ukr_shipping_ttn_list' === $screen->id) {
            wp_enqueue_script(
                'wcus_order_list_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/order-list.min.js',
                ['wcus_core_js'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/order-list.min.js'),
                true
            );
        }
    }

    private function initPluginsAssets($screen)
    {
        if ($screen->id === 'plugins') {
            wp_enqueue_script(
                'wcus_plugin_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/plugin.min.js',
                ['wcus_core_js'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/plugin.min.js'),
                true
            );
        }
    }

    private function initAutomationFormAssets($screen)
    {
        if (in_array($screen->id, ['admin_page_wcus_automation_rule_create', 'admin_page_wcus_automation_rule_edit'], true)) {
            wp_enqueue_script(
                'wcus_automation_form_js',
                WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/automation.min.js',
                ['wcus_core_js'],
                filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/automation.min.js'),
                true
            );
        }
    }

    private function getFrontendTranslates(): array
    {
        return [
            'Warehouses data of Nova Poshta' => __('Warehouses data of Nova Poshta', 'wc-ukr-shipping-pro'),
            'Last update date' => __('Last update date', 'wc-ukr-shipping-pro'),
            'Status' => __('Status', 'wc-ukr-shipping-pro'),
            'Continue update' => __('Continue update', 'wc-ukr-shipping-pro'),
            'Update warehouses' => __('Update warehouses', 'wc-ukr-shipping-pro'),
            'Not completed' => __('Not completed', 'wc-ukr-shipping-pro'),
            'Completed' => __('Completed', 'wc-ukr-shipping-pro'),
            'Unknown' => __('Unknown', 'wc-ukr-shipping-pro'),
            'Load areas' => __('Load areas', 'wc-ukr-shipping-pro'),
            'Load cities' => __('Load cities', 'wc-ukr-shipping-pro'),
            'Load warehouses' => __('Load warehouses', 'wc-ukr-shipping-pro'),
            'Warehouses db updated successfully' => __('Warehouses db updated successfully', 'wc-ukr-shipping-pro'),
            'City' => __('City', 'wc-ukr-shipping-pro'),
            'Warehouse' => __('Warehouse', 'wc-ukr-shipping-pro'),
            'Settlement' => __('Settlement', 'wc-ukr-shipping-pro'),
            'Street' => __('Street', 'wc-ukr-shipping-pro'),
            'House' => __('House', 'wc-ukr-shipping-pro'),
            'Flat' => __('Flat', 'wc-ukr-shipping-pro'),
            'Shipping cost' => __('Shipping cost', 'wc-ukr-shipping-pro'),
            'Shipping cost type' => __('Shipping cost type', 'wc-ukr-shipping-pro'),
            'Fixed' => __('Fixed', 'wc-ukr-shipping-pro'),
            'From order amount' => __('From order amount', 'wc-ukr-shipping-pro'),
            'Nova Poshta API' => __('Nova Poshta API', 'wc-ukr-shipping-pro'),
            'Cargo type' => __('Cargo type', 'wc-ukr-shipping-pro'),
            'Cargo' => __('Cargo', 'wc-ukr-shipping-pro'),
            'Documents' => __('Documents', 'wc-ukr-shipping-pro'),
            'TiresWheels' => __('TiresWheels', 'wc-ukr-shipping-pro'),
            'Pallet' => __('Pallet', 'wc-ukr-shipping-pro'),
            'Parcel' => __('Parcel', 'wc-ukr-shipping-pro'),
            'Address shipping cost' => __('Address shipping cost', 'wc-ukr-shipping-pro'),
            'Enable separated cost' => __('Enable separated cost', 'wc-ukr-shipping-pro'),
            'Fixed cost' => __('Fixed cost', 'wc-ukr-shipping-pro'),
            'Order amount' => __('Order amount', 'wc-ukr-shipping-pro'),
            'Add' => __('Add', 'wc-ukr-shipping-pro'),
            'Delete' => __('Delete', 'wc-ukr-shipping-pro'),
            'COD' => __('COD', 'wc-ukr-shipping-pro'),
            'COD method' => __('COD method', 'wc-ukr-shipping-pro'),
            'Take COD into account when calculating shipping cost' => __('Take COD into account when calculating shipping cost', 'wc-ukr-shipping-pro'),
            'Separate name for free shipping' => __('Separate name for free shipping', 'wc-ukr-shipping-pro'),
            'Enable separate name' => __('Enable separate name', 'wc-ukr-shipping-pro'),
            'Free shipping name' => __('Free shipping name', 'wc-ukr-shipping-pro'),
            'Default sender warehouse' => __('Default sender warehouse', 'wc-ukr-shipping-pro'),
            'Default sender address' => __('Default sender address', 'wc-ukr-shipping-pro'),
            'License key' => __('License key', 'wc-ukr-shipping-pro'),
        ];
    }
}