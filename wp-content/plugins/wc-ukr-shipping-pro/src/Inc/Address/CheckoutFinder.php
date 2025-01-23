<?php

namespace kirillbdev\WCUkrShipping\Inc\Address;

use kirillbdev\WCUkrShipping\Contracts\Address\CityFinderInterface;
use kirillbdev\WCUkrShipping\Contracts\Address\SettlementFinderInterface;
use kirillbdev\WCUkrShipping\Contracts\Address\StreetFinderInterface;
use kirillbdev\WCUkrShipping\Contracts\Address\WarehouseFinderInterface;
use kirillbdev\WCUkrShipping\Contracts\Customer\CustomerStorageInterface;
use kirillbdev\WCUkrShipping\Dto\Address\CityDto;
use kirillbdev\WCUkrShipping\Dto\Address\SettlementDto;
use kirillbdev\WCUkrShipping\Dto\Address\StreetDto;
use kirillbdev\WCUkrShipping\Dto\Address\WarehouseDto;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutFinder implements CityFinderInterface, WarehouseFinderInterface, SettlementFinderInterface, StreetFinderInterface
{
    /**
     * @var int
     */
    private $saveEnable;

    /**
     * @var CustomerStorageInterface
     */
    private $customerStorage;

    public function __construct()
    {
        $this->saveEnable = (int)get_option(WCUS_OPTION_SAVE_CUSTOMER_ADDRESS);
        $this->customerStorage = wcus_container()->make(CustomerStorageInterface::class);
    }

    public function getCity(): ?CityDto
    {
        if ($this->saveEnable) {
            $finder = new RepositoryCityFinder($this->customerStorage->get(CustomerStorageInterface::KEY_LAST_CITY_REF));

            return $finder->getCity();
        }

        return null;
    }

    public function getWarehouse(): ?WarehouseDto
    {
        if ($this->saveEnable) {
            $finder = new RepositoryWarehouseFinder($this->customerStorage->get(CustomerStorageInterface::KEY_LAST_WAREHOUSE_REF));

            return $finder->getWarehouse();
        }

        return null;
    }

    public function getSettlement(): ?SettlementDto
    {
        if ($this->saveEnable) {
            $settlement = $this->customerStorage->get(CustomerStorageInterface::KEY_LAST_SETTLEMENT);
            $dto = new SettlementDto();
            $dto->ref = $settlement['ref'] ?? '';
            $dto->full = $settlement['full'] ?? '';
            $dto->name = $settlement['name'] ?? '';
            $dto->area = $settlement['area'] ?? '';
            $dto->region = $settlement['region'] ?? '';

            return $dto;
        }

        return null;
    }

    public function getStreet(): ?StreetDto
    {
        if ($this->saveEnable) {
            $street = $this->customerStorage->get(CustomerStorageInterface::KEY_LAST_STREET);
            $dto = new StreetDto();
            $dto->ref = $street['ref'] ?? '';
            $dto->full = $street['full'] ?? '';
            $dto->name = $street['name'] ?? '';

            return $dto;
        }

        return null;
    }
}