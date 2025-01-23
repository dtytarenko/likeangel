<?php

namespace kirillbdev\WCUkrShipping\Exceptions;

if ( ! defined('ABSPATH')) {
    exit;
}

class TTNServiceException extends \Exception
{
    public function __construct($message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
