<?php
/**
 * Plugin Name: WC Ukraine Shipping PRO
 * Plugin URI: https://kirillbdev.pro/wc-ukr-shipping-pro/
 * Description: Integration of Nova Poshta delivery service for WooCommerce
 * Version: 1.17.6
 * Author: Qodax Software
 * Tested up to: 6.6
 * WC tested up to: 9.3
 * Requires at least: 5.5
 * Requires PHP: 7.4
*/

if ( ! defined('ABSPATH')) {
  exit;
}

include_once __DIR__ . '/vendor/autoload.php';

add_action('admin_notices', function (): void {
    $deactivateFlag = $_GET['wcus_lite_deactivate'] ?? '';
    if ($deactivateFlag === '1') {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong>WC Ukraine Shipping:</strong>
                <?php esc_html_e('Lite version was deactivated', 'wc-ukr-shipping-pro'); ?>
            </p>
        </div>
        <?php
    }
});

add_action('plugins_loaded', function (): void {
    if (defined('WC_UKR_SHIPPING_PLUGIN_NAME')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-includes/pluggable.php';

        if (is_plugin_active(WC_UKR_SHIPPING_PLUGIN_NAME)) {
            // Deactivate lite version (cause actually constant refer to it)
            deactivate_plugins(WC_UKR_SHIPPING_PLUGIN_NAME);
            // WP does not allow us to send a custom meaningful message, so just tell the plugin has been deactivated.
            wp_safe_redirect(add_query_arg('wcus_lite_deactivate', '1'));
            exit;
        }
    } else {
        define('WC_UKR_SHIPPING_PLUGIN_NAME', plugin_basename(__FILE__));
        define('WC_UKR_SHIPPING_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('WC_UKR_SHIPPING_PLUGIN_ENTRY', __FILE__);
        define('WC_UKR_SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

        define('WCUS_PLUGIN_VERSION', '1.17.6');
        define('WCUS_TRANSLATE_DOMAIN', 'wc-ukr-shipping-pro');
        define('WCUS_TRANSLATE_TYPE_PLUGIN', 0);
        define('WCUS_TRANSLATE_TYPE_MO_FILE', 1);

        define('WC_UKR_SHIPPING_NP_SHIPPING_NAME', 'nova_poshta_shipping');
        define('WC_UKR_SHIPPING_NP_SHIPPING_TITLE', 'Нова Пошта');


        include_once __DIR__ . '/constants.php';
        include_once __DIR__ . '/globals.php';

        add_action( 'before_woocommerce_init', function() {
            if (class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });

        kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance()->init();
    }
});
