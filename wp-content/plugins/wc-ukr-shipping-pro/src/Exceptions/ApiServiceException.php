<?php

namespace kirillbdev\WCUkrShipping\Exceptions;

if ( ! defined('ABSPATH')) {
    exit;
}

class ApiServiceException extends \Exception
{
    public function __construct($message = 'API service is temporarily unavailable.')
    {
        parent::__construct($message);
    }
}