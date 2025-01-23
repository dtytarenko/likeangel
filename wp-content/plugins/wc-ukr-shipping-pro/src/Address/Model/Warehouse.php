<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Model;

final class Warehouse
{
    private string $ref;
    private string $cityRef;
    private string $nameUa;
    private string $nameRu;

    public function __construct(string $ref, string $cityRef, string $nameUa, string $nameRu)
    {
        $this->ref = $ref;
        $this->cityRef = $cityRef;
        $this->nameUa = $nameUa;
        $this->nameRu = $nameRu;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getCityRef(): string
    {
        return $this->cityRef;
    }

    public function getNameUa(): string
    {
        return $this->nameUa;
    }

    public function getNameRu(): string
    {
        return $this->nameRu;
    }
}
