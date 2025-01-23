<?php

namespace kirillbdev\WCUkrShipping\Inc\Address;

use kirillbdev\WCUkrShipping\Address\Provider\AddressProviderInterface;
use kirillbdev\WCUkrShipping\Contracts\Address\CityFinderInterface;
use kirillbdev\WCUkrShipping\DB\Repositories\CityRepository;
use kirillbdev\WCUkrShipping\Dto\Address\CityDto;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if ( ! defined('ABSPATH')) {
    exit;
}

class RepositoryCityFinder implements CityFinderInterface
{
    /**
     * @var string|null
     */
    private $ref;

    public function __construct(?string $ref)
    {
        $this->ref = $ref;
    }

    public function getCity(): ?CityDto
    {
        if ( ! $this->ref) {
            return null;
        }

        /** @var AddressProviderInterface $provider */
        $provider = wcus_container()->make(AddressProviderInterface::class);
        /** @var TranslateService $translateService */
        $translateService = wcus_container()->make(TranslateService::class);
        $city = $provider->searchCityByRef($this->ref);

        if ( ! $city) {
            return null;
        }

        $dto = new CityDto();
        $dto->ref = $city->getRef();
        $dto->name = $translateService->getCurrentLanguage() === 'ru'
            ? $city->getNameRu()
            : $city->getNameUa();

        return $dto;
    }
}