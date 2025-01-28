<?php

declare(strict_types=1);

namespace kirillbdev\WCUSCore\Exceptions;

if ( ! defined('ABSPATH')) {
    exit;
}

class MigrateException extends \Exception
{
    private string $migration;

    public function __construct(string $migration, string $message)
    {
        parent::__construct($message);
        $this->migration = $migration;
    }

    public function getMigration(): string
    {
        return $this->migration;
    }
}
