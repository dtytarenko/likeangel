<?php

namespace kirillbdev\WCUkrShipping\Classes;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Contracts\HttpRequest;
use kirillbdev\WCUkrShipping\Contracts\NotificatorInterface;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\Foundation\Dependencies\Contracts;
use kirillbdev\WCUkrShipping\Foundation\Dependencies\Controllers;
use kirillbdev\WCUkrShipping\Foundation\Dependencies\Operations;
use kirillbdev\WCUkrShipping\Foundation\Dependencies\Modules;
use kirillbdev\WCUkrShipping\Foundation\Dependencies\Services;
use kirillbdev\WCUkrShipping\Http\AdminAjax;
use kirillbdev\WCUkrShipping\Http\WpHttpRequest;
use kirillbdev\WCUkrShipping\Services\NotifyService;
use kirillbdev\WCUkrShipping\Services\PrintService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUSCore\Foundation\Kernel;

if ( ! defined('ABSPATH')) {
    exit;
}

final class WCUkrShipping extends Kernel
{
    private static $instance = null;

    /**
     * @var Container
     */
    private $containerLegacy;

    private $adminAjax;
    private $orderShippingItem;

    protected function __construct()
    {
        parent::__construct();
        $this->instantiateContainer();
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function getContainer(): \kirillbdev\WCUSCore\Foundation\Container
    {
        return $this->container;
    }

    public function init()
    {
        parent::init();

        $this->adminAjax = new AdminAjax();
        $this->orderShippingItem = new OrderShippingItem();
    }

    public function singleton($abstract)
    {
        return $this->containerLegacy->singleton($abstract);
    }

    public function make($abstract)
    {
        return $this->containerLegacy->get($abstract);
    }

    private function instantiateContainer()
    {
        $this->containerLegacy = new Container();

        $this->containerLegacy->singleton(HttpRequest::class, WpHttpRequest::class);
        $this->containerLegacy->singleton(NotificatorInterface::class, NotifyService::class);

        $this->containerLegacy->singleton('api', NovaPoshtaApi::class);
        $this->containerLegacy->singleton('print_service', PrintService::class);
        $this->containerLegacy->singleton('translate_service', TranslateService::class);

        $this->containerLegacy->bind('np_repository', NovaPoshtaRepository::class);
    }

    /**
     * Returns list of kernel modules.
     * @return string[]
     */
    public function modules()
    {
        return [
            // Core
            \kirillbdev\WCUkrShipping\Modules\Core\PluginInfo::class,
            \kirillbdev\WCUkrShipping\Modules\Core\Activator::class,
            \kirillbdev\WCUkrShipping\Modules\Core\Localization::class,
            \kirillbdev\WCUkrShipping\Modules\Core\Updater::class,
            // Backend
            \kirillbdev\WCUkrShipping\Modules\Backend\Ajax::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\AssetLoader::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\OptionsPage::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\EditOrder::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\OrderList::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\Orders::class,
            \kirillbdev\WCUkrShipping\Modules\Backend\Jobs::class,
            // Document
            \kirillbdev\WCUkrShipping\Modules\Document\TTNCreator::class,
            // Frontend
            \kirillbdev\WCUkrShipping\Modules\Frontend\AssetLoader::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Cart::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Checkout::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\CheckoutValidator::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\OrderCreator::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\ShippingMethod::class,
            \kirillbdev\WCUkrShipping\Modules\Frontend\Address::class
        ];
    }

    /**
     * Returns plugin entities dependencies.
     *
     * @return array
     */
    public function dependencies()
    {
        $dependencies = [];
        $groups = [
            Controllers::class,
            Modules::class,
            Contracts::class,
            Operations::class,
            Services::class
        ];

        foreach ($groups as $group) {
            $dependencies = array_merge($dependencies, $group::getDependencies());
        }

        return $dependencies;
    }

    public function viewPath()
    {
        return WC_UKR_SHIPPING_PLUGIN_DIR . 'views';
    }
}