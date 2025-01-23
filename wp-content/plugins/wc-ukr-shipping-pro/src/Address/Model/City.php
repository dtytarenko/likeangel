<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Address\Model;

final class City
{
    private string $ref;
    private string $areaRef;
    private string $nameUa;
    private string $nameRu;

    public function __construct(string $ref, string $areaRef, string $nameUa, string $nameRu)
    {
        $this->ref = $ref;
        $this->areaRef = $areaRef;
        $this->nameUa = $nameUa;
        $this->nameRu = $nameRu;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getAreaRef(): string
    {
        return $this->areaRef;
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
