<?php

namespace kirillbdev\WCUkrShipping\Foundation\Dependencies;

use kirillbdev\WCUkrShipping\DB\OptionsRepository;
use kirillbdev\WCUkrShipping\DB\Repositories\TTNRepository;
use kirillbdev\WCUkrShipping\Http\Controllers\OptionsController;
use kirillbdev\WCUkrShipping\Http\Controllers\OrdersController;
use kirillbdev\WCUkrShipping\Operations\AutoInvoiceOperation;
use kirillbdev\WCUkrShipping\Services\AutomationService;
use kirillbdev\WCUkrShipping\Services\Backend\OrderService;
use kirillbdev\WCUkrShipping\Services\TrackingsMockService;

if ( ! defined('ABSPATH')) {
    exit;
}

final class Controllers
{
    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [
            OptionsController::class => function ($container) {
                return new OptionsController($container->make(OptionsRepository::class));
            },
            OrdersController::class => function ($container) {
                return new OrdersController(
                    $container->make(OrderService::class),
                    $container->make(AutoInvoiceOperation::class),
                    $container->make(TrackingsMockService::class),
                    $container->make(AutomationService::class),
                    $container->make(TTNRepository::class)
                );
            }
        ];
    }
}