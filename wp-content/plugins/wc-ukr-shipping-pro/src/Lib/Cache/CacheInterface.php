<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Lib\Cache;

interface CacheInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value, ?int $ttl = null): bool;

    public function has(string $key): bool;
}
