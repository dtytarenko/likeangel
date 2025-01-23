<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Provider;

use kirillbdev\WCUkrShipping\Address\Dto\SearchWarehouseResultDto;
use kirillbdev\WCUkrShipping\Address\Exception\Provider\LicenseNotFoundException;
use kirillbdev\WCUkrShipping\Address\Exception\Provider\UnauthorizedException;
use kirillbdev\WCUkrShipping\Address\Model\City;
use kirillbdev\WCUkrShipping\Address\Model\Warehouse;
use kirillbdev\WCUkrShipping\Lib\Cache\CacheInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class CloudAddressProvider implements AddressProviderInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function searchCitiesByQuery(string $query): array
    {
        $response = wp_remote_post("https://api.wcukraineshipping.com/v1/addresses/searchCities", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getLicenseKey(),
            ],
            'body' => json_encode([
                'mode' => 'byQuery',
                'query' => $query,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \Exception("An unexpected error occurred while sending the request");
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new UnauthorizedException("Cloud provider return unauthorized or forbidden response code");
        }

        $result = json_decode($response['body'], true);
        $cities = [];

        foreach ($result['cities'] as $city) {
            $cities[] = new City($city['ref'], $city['areaRef'], $city['nameUa'], $city['nameRu']);
        }

        return $cities;
    }

    public function searchCityByRef(string $ref): ?City
    {
        $cacheKey = 'np.cities.ref.' . md5($ref);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $decoded = json_decode($cached, true);
            if (json_last_error() === 0) {
                return new City($decoded['ref'], $decoded['areaRef'], $decoded['nameUa'], $decoded['nameRu']);
            }
        }

        $response = wp_remote_post("https://api.wcukraineshipping.com/v1/addresses/searchCities", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getLicenseKey(),
            ],
            'body' => json_encode([
                'mode' => 'byRef',
                'ref' => $ref,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \Exception("An unexpected error occurred while sending the request");
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new UnauthorizedException("Cloud provider return unauthorized or forbidden response code");
        }

        $result = json_decode($response['body'], true);

        if (count($result['cities']) === 0) {
            return null;
        }

        $city = $result['cities'][0];
        $this->cache->set($cacheKey, json_encode($city), 3600 * 24);

        return new City($city['ref'], $city['areaRef'], $city['nameUa'], $city['nameRu']);
    }

    public function searchWarehousesByQuery(
        string $cityRef,
        string $query,
        int $page,
        array $types = []
    ): SearchWarehouseResultDto {
        $payload = [
            'mode' => 'byQuery',
            'query' => $query,
            'cityRef' => $cityRef,
            'page' => $page,
        ];

        if (count($types) > 0) {
            $payload['types'] = $this->mapTypes($types);
        }

        $response = wp_remote_post("https://api.wcukraineshipping.com/v1/addresses/searchWarehouses", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getLicenseKey(),
            ],
            'body' => json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            throw new \Exception("An unexpected error occurred while sending the request");
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new UnauthorizedException("Cloud provider return unauthorized or forbidden response code");
        }

        $result = json_decode($response['body'], true);
        $warehouses = [];

        foreach ($result['warehouses'] as $warehouse) {
            $warehouses[] = new Warehouse($warehouse['ref'], $warehouse['areaRef'], $warehouse['nameUa'], $warehouse['nameRu']);
        }

        return new SearchWarehouseResultDto($warehouses, $result['total']);
    }

    public function searchWarehouseByRef(string $ref): ?Warehouse
    {
        $cacheKey = 'np.warehouses.ref.' . md5($ref);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $decoded = json_decode($cached, true);
            if (json_last_error() === 0) {
                return new Warehouse($decoded['ref'], $decoded['areaRef'], $decoded['nameUa'], $decoded['nameRu']);
            }
        }

        $response = wp_remote_post("https://api.wcukraineshipping.com/v1/addresses/searchWarehouses", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getLicenseKey(),
            ],
            'body' => json_encode([
                'mode' => 'byRef',
                'ref' => $ref,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \Exception("An unexpected error occurred while sending the request");
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new UnauthorizedException("Cloud provider return unauthorized or forbidden response code");
        }

        $result = json_decode($response['body'], true);

        if (count($result['warehouses']) === 0) {
            return null;
        }

        $warehouse = $result['warehouses'][0];
        $this->cache->set($cacheKey, json_encode($warehouse), 3600 * 24);

        return new Warehouse($warehouse['ref'], $warehouse['areaRef'], $warehouse['nameUa'], $warehouse['nameRu']);
    }

    /**
     * @throws LicenseNotFoundException
     */
    private function getLicenseKey(): string
    {
        $key = wc_ukr_shipping_get_option(WCUS_OPTION_CLOUD_TOKEN);

        if (empty($key)) {
            throw new LicenseNotFoundException("Unable to find cloud token");
        }

        return $key;
    }

    private function mapTypes(array $types): array
    {
        $result = [];

        foreach ($types as $type) {
            switch ($type) {
                case WCUS_WAREHOUSE_TYPE_REGULAR:
                    $result[] = 'regular';
                    break;
                case WCUS_WAREHOUSE_TYPE_CARGO:
                    $result[] = 'cargo';
                    break;
                case WCUS_WAREHOUSE_TYPE_POSHTOMAT:
                    $result[] = 'poshtomat';
                    break;
                default:
                    throw new \InvalidArgumentException("Incorrect warehouse type provided");
            }
        }

        return $result;
    }
}
