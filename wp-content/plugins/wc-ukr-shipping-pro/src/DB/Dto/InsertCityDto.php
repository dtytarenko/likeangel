<?php

namespace kirillbdev\WCUkrShipping\DB\Dto;

if ( ! defined('ABSPATH')) {
    exit;
}

class InsertCityDto
{
    private $ref;
    private $description;
    private $descriptionRu;
    private $areaRef;

    public function __construct(string $ref, string $description, string $descriptionRu, string $areaRef)
    {
        $this->ref = $ref;
        $this->description = $description;
        $this->descriptionRu = $descriptionRu;
        $this->areaRef = $areaRef;
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->ref;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDescriptionRu(): string
    {
        return $this->descriptionRu;
    }

    /**
     * @return string
     */
    public function getAreaRef(): string
    {
        return $this->areaRef;
    }
}