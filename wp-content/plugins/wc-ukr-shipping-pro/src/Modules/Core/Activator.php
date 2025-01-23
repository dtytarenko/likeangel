<?php

namespace kirillbdev\WCUkrShipping\Modules\Core;

use kirillbdev\WCUkrShipping\DB\Migrations\CreateAutomationActionsTable_20240923215754;
use kirillbdev\WCUkrShipping\DB\Migrations\CreateAutomationRulesTable_20240923215722;
use kirillbdev\WCUkrShipping\DB\Migrations\CreateCacheTable_20240923214011;
use kirillbdev\WCUkrShipping\DB\Migrations\CreateTtnTable_20240923213940;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\DB\Migrator;
use kirillbdev\WCUSCore\Exceptions\MigrateException;

class Activator implements ModuleInterface
{
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('init', [ $this, 'activate' ]);
        register_activation_hook(WC_UKR_SHIPPING_PLUGIN_ENTRY, [$this, 'activate']);
    }

    public function activate()
    {
        $this->migrator->addMigration(new CreateTtnTable_20240923213940());
        $this->migrator->addMigration(new CreateCacheTable_20240923214011());
        $this->migrator->addMigration(new CreateAutomationRulesTable_20240923215722());
        $this->migrator->addMigration(new CreateAutomationActionsTable_20240923215754());

        try {
            // All base tables (both in lite and pro versions) are added in core package
            $this->migrator->run();
        } catch (MigrateException $e) {
            // do nothing yet
        }
    }
}
