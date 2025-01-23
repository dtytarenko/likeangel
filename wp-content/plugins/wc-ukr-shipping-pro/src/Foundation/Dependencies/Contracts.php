<?php

namespace kirillbdev\WCUkrShipping\Foundation\Dependencies;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Address\Provider\CloudAddressProvider;
use kirillbdev\WCUkrShipping\Address\Provider\CloudV2AddressProvider;
use kirillbdev\WCUkrShipping\Address\Provider\FallbackAddressProvider;
use kirillbdev\WCUkrShipping\Address\Provider\MySqlAddressProvider;
use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\HposOrderRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\OrderRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\OrderRepositoryInterface;
use kirillbdev\WCUkrShipping\Inc\Customer\LoggedCustomerStorage;
use kirillbdev\WCUkrShipping\Inc\Customer\SessionCustomerStorage;
use kirillbdev\WCUkrShipping\Lib\Cache\CacheInterface;
use kirillbdev\WCUkrShipping\Lib\Cache\MySqlCache;
use kirillbdev\WCUkrShipping\Lib\Log\WooCommerceLogger;

if ( ! defined('ABSPATH')) {
    exit;
}

final class Contracts
{
    public static function getDependencies(): array
    {
        return [
            CustomerStorageInterface::class => function ($container) {
                $customerId = wc()->customer->get_id();

                return $container->make($customerId ? LoggedCustomerStorage::class : SessionCustomerStorage::class);
            },
            AddressProviderInterface::class => function ($container) {
                $cache = $container->make(CacheInterface::class);

                // Actual (via license key)
                $licenseConnected = (int)wc_ukr_shipping_get_option('wcus_license_connected');
                if ($licenseConnected) {
                    return new FallbackAddressProvider(
                        new CloudV2AddressProvider($cache),
                        $container->make(MySqlAddressProvider::class)
                    );
                }

                // Legacy (via cloud token)
                $cloudToken = wc_ukr_shipping_get_option(WCUS_OPTION_CLOUD_TOKEN);

                if (!empty($cloudToken)) {
                    return new FallbackAddressProvider(
                        new CloudAddressProvider($cache),
                        $container->make(MySqlAddressProvider::class)
                    );
                }

                return $container->make(MySqlAddressProvider::class);
            },
            CacheInterface::class => function ($container) {
                global $wpdb;

                return new MySqlCache($wpdb);
            },
            OrderRepositoryInterface::class => function ($container) {
                $controller = wcus_wc_container_safe_get(CustomOrdersTableController::class);
                return $controller !== null && $controller ->custom_orders_table_usage_is_enabled()
                    ? $container->make(HposOrderRepository::class)
                    : $container->make(OrderRepository::class);
            }
        ];
    }
}
