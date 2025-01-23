<?php

namespace kirillbdev\WCUkrShipping\DB\Dto;

if ( ! defined('ABSPATH')) {
    exit;
}

class InsertAreaDto
{
    private $ref;
    private $description;

    public function __construct(string $ref, string $description)
    {
        $this->ref = $ref;
        $this->description = $description;
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
}