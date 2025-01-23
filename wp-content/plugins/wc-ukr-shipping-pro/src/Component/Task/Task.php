<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Component\Task;

if (!defined('ABSPATH')) {
    exit;
}

class Task implements TaskInterface
{
    private string $name;
    private ?string $param1;
    private ?string $param2;
    private ?string $param3;
    private ?string $param4;

    public function __construct(
        string $name,
        ?string $param1 = null,
        ?string $param2 = null,
        ?string $param3 = null,
        ?string $param4 = null
    ) {
        $this->name = $name;
        $this->param1 = $param1;
        $this->param2 = $param2;
        $this->param3 = $param3;
        $this->param4 = $param4;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParam1(): ?string
    {
        return $this->param1;
    }

    public function getParam2(): ?string
    {
        return $this->param2;
    }

    public function getParam3(): ?string
    {
        return $this->param3;
    }

    public function getParam4(): ?string
    {
        return $this->param4;
    }
}
