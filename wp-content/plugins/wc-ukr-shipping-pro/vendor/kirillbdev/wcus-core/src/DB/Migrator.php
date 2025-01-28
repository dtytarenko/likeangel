<?php

namespace kirillbdev\WCUSCore\DB;

use kirillbdev\WCUSCore\DB\Migrations\CreateNpMainTables_20240831172858;
use kirillbdev\WCUSCore\Exceptions\MigrateException;

class Migrator
{
    private const OPTION_HISTORY = 'wcus_migrations_history';

    private \wpdb $db;

    /**
     * @var Migration[]
     */
    private $migrations = [];

    /**
     * @var callable[]
     */
    private $rollbackExecutors = [];

    /**
     * @var array
     */
    private $history = [];

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->collate = $wpdb->get_charset_collate();

        $history = get_option(self::OPTION_HISTORY);
        if ($history) {
            $this->history = json_decode($history, true);
        } else {
            $this->history = [];
        }

        // Add main migrations for both versions
        $this->addMigration(new CreateNpMainTables_20240831172858());

        // Add main rollback executor
        $this->addRollbackExecutor([$this, 'executeDropMainTables']);
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function addMigration(Migration $migration): void
    {
        if ( ! isset($this->migrations[ $migration->name() ])) {
            $this->migrations[ $migration->name() ] = $migration;
        }
    }

    public function addRollbackExecutor(callable $executor): void
    {
        $this->rollbackExecutors[] = $executor;
    }

    public function run(): void
    {
        foreach ($this->migrations as $migration) {
            if ( ! in_array($migration->name(), $this->history, true)) {
                try {
                    $migration->up($this->db);
                    if ($this->db->last_error) {
                        throw new MigrateException($migration->name(), $this->db->last_error);
                    }

                    $this->history[] = $migration->name();
                } catch (MigrateException $e) {
                    $this->storeHistory();
                    throw $e;
                }
            }
        }

        $this->storeHistory();
    }

    public function reRun()
    {
        foreach ($this->rollbackExecutors as $executor) {
            call_user_func($executor, $this->db);

            if ($this->db->last_error) {
                throw new MigrateException('', 'Rollback executor fails');
            }
        }

        $this->history = [];
        $this->storeHistory();
        $this->run();
    }

    // todo: need to refactor this
    public function executeDropMainTables(\wpdb $wpdb): void
    {
        $prefix = $wpdb->prefix;

        $wpdb->query("DROP TABLE IF EXISTS `{$prefix}wc_ukr_shipping_np_areas`");
        $wpdb->query("DROP TABLE IF EXISTS `{$prefix}wc_ukr_shipping_np_cities`");
        $wpdb->query("DROP TABLE IF EXISTS `{$prefix}wc_ukr_shipping_np_warehouses`");
    }

    private function storeHistory(): void
    {
        update_option(self::OPTION_HISTORY, json_encode($this->history));
    }
}
