<?php

namespace kirillbdev\WCUSCore\DB;

abstract class Migration
{
    abstract public function name(): string;

    abstract public function up(\wpdb $db): void;
}
