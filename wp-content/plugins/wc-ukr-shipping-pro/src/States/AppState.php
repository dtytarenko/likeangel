<?php

namespace kirillbdev\WCUkrShipping\States;

if ( ! defined('ABSPATH')) {
    exit;
}

abstract class AppState implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $params = [];

    /**
     * @return array
     */
    abstract protected function getState();

    public function bindParams(array $params)
    {
        $this->params = $params;
    }

    public function jsonSerialize(): array
    {
        return $this->getState();
    }
}