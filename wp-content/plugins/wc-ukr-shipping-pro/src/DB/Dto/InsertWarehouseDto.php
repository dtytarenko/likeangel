<?php

namespace kirillbdev\WCUkrShipping\DB\Dto;

if ( ! defined('ABSPATH')) {
    exit;
}

class InsertWarehouseDto
{
    private $ref;
    private $description;
    private $descriptionRu;
    private $cityRef;
    private $number;
    private $warehouseType;

    public function __construct(
        string $ref,
        string $description,
        string $descriptionRu,
        string $cityRef,
        int $number,
        int $warehouseType
    ) {
        $this->ref = $ref;
        $this->description = $description;
        $this->descriptionRu = $descriptionRu;
        $this->cityRef = $cityRef;
        $this->number = $number;
        $this->warehouseType = $warehouseType;
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
    public function getCityRef(): string
    {
        return $this->cityRef;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getWarehouseType(): int
    {
        return $this->warehouseType;
    }
}