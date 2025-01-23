<?php

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\States\AppState;
use kirillbdev\WCUSCore\Foundation\Container;

if ( ! defined('ABSPATH')) {
    exit;
}

class StateService
{
    /**
     * @var AppState[]
     */
    private static $state = [];

    /**
     * @param string $key
     * @param string $class
     */
    public static function addState($key, $stateClass, $params = [])
    {
        /** @var AppState $state */
        $state = Container::instance()->make($stateClass);
        $state->bindParams($params);

        self::$state[ $key ] = $state;
    }

    /**
     * @return AppState[]
     */
    public static function getState()
    {
        return self::$state;
    }
}