<?php

namespace kirillbdev\WCUkrShipping\Foundation\Dependencies;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\DB\Mappers\OrderListMapper;
use kirillbdev\WCUkrShipping\DB\Repositories\InvoiceRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\OrderRepositoryInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\Services\Backend\OrderService;
use kirillbdev\WCUkrShipping\Services\Invoice\InvoiceInfoService;
use kirillbdev\WCUkrShipping\Services\Invoice\InvoiceService;
use kirillbdev\WCUkrShipping\Services\Invoice\RecipientService;
use kirillbdev\WCUkrShipping\Services\Invoice\SenderService;

if ( ! defined('ABSPATH')) {
    exit;
}

final class Services
{
    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [
            OrderService::class => function ($container) {
                return new OrderService(
                    $container->make(OrderRepositoryInterface::class),
                    $container->make(OrderListMapper::class)
                );
            },
            InvoiceService::class => function ($container) {
                return new InvoiceService($container->make(NovaPoshtaApi::class), $container->make(InvoiceRepository::class));
            },
            InvoiceInfoService::class => function ($container) {
                return new InvoiceInfoService($container->make(WarehouseRepository::class));
            },
            SenderService::class => function ($container) {
                return new SenderService($container->make(NovaPoshtaApi::class));
            },
            RecipientService::class => function ($container) {
                return new RecipientService($container->make(NovaPoshtaApi::class));
            }
        ];
    }
}