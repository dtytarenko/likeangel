<?php

namespace kirillbdev\WCUkrShipping\Inc\Address;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Contracts\Address\WarehouseFinderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\Dto\Address\WarehouseDto;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class RepositoryWarehouseFinder implements WarehouseFinderInterface
{
    /**
     * @var string|null
     */
    private $ref;

    public function __construct(?string $ref)
    {
        $this->ref = $ref;
    }

    public function getWarehouse(): ?WarehouseDto
    {
        if ( ! $this->ref) {
            return null;
        }

        /** @var AddressProviderInterface $provider */
        $provider = wcus_container()->make(AddressProviderInterface::class);
        /** @var TranslateService $translateService */
        $translateService = wcus_container()->make(TranslateService::class);
        $warehouse = $provider->searchWarehouseByRef($this->ref);

        if ($warehouse === null) {
            return null;
        }

        $dto = new WarehouseDto();
        $dto->ref = $warehouse->getRef();
        $dto->name = $translateService->getCurrentLanguage() === 'ru'
            ? $warehouse->getNameRu()
            : $warehouse->getNameUa();

        return $dto;
    }
}