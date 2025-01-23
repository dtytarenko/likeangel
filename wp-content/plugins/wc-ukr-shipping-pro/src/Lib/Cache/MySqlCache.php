<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Lib\Cache;

class MySqlCache implements CacheInterface
{
    private \wpdb $db;

    public function __construct(\wpdb $db)
    {
        $this->db = $db;
    }

    public function get(string $key): ?string
    {
        $row = $this->findRowByKey($key);
        if ($row === null) {
            return null;
        }

        if ((int)$row['ttl'] > 0 && microtime(true) - (float)$row['updated_at'] > (int)$row['ttl']) {
            $this->db->query(
                "DELETE FROM `{$this->db->prefix}wc_ukr_shipping_cache` WHERE id = " . (int)$row['id']
            );

            return null;
        }

        return $row['value'];
    }

    public function set(string $key, string $value, ?int $ttl = null): bool
    {
        $row = $this->findRowByKey($key);
        $now = microtime(true);

        if ($row === null) {
            $result = $this->db->insert(
                "{$this->db->prefix}wc_ukr_shipping_cache",
                [
                    'key' => $key,
                    'value' => $value,
                    'ttl' => $ttl,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%f',
                    '%f',
                ]
            );
        } else {
            $result = $this->db->update(
                "{$this->db->prefix}wc_ukr_shipping_cache",
                [
                    'value' => $value,
                    'ttl' => $ttl,
                    'updated_at' => $now,
                ],
                [
                    'id' => (int)$row['id'],
                ],
                [
                    '%s',
                    '%d',
                    '%f',
                ]
            );
        }

        return (int)$result > 0;
    }

    public function has(string $key): bool
    {
        $row = $this->findRowByKey($key);

        if ((int)$row['ttl'] > 0 && microtime(true) - (float)$row['updated_at'] > (int)$row['ttl']) {
            return false;
        }

        return true;
    }

    private function findRowByKey(string $key): ?array
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * 
                FROM `{$this->db->prefix}wc_ukr_shipping_cache` 
                WHERE `key` = %s",
                $key
            ),
            ARRAY_A
        );

        if (!$row || !is_array($row)) {
            return null;
        }

        return $row;
    }
}
