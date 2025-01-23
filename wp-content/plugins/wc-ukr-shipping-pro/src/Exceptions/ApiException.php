<?php

namespace kirillbdev\WCUkrShipping\Exceptions;

if ( ! defined('ABSPATH')) {
    exit;
}

class ApiException extends \Exception
{
    private $errors;

    public function __construct($errors = [], $message = 'API error')
    {
        parent::__construct($message);

        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}