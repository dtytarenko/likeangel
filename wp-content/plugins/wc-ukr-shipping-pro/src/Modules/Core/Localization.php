<?php

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUSCore\Contracts\ModuleInterface;

class Localization implements ModuleInterface
{
    public function init(): void
    {
        // Very critical to init this module on plugins_loaded
        load_plugin_textdomain('wc-ukr-shipping-pro', false, 'wc-ukr-shipping-pro/lang');
    }
}
