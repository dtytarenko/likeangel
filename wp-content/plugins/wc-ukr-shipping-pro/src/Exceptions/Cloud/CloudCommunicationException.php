<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Exceptions\Cloud;

if ( ! defined('ABSPATH')) {
    exit;
}

class CloudCommunicationException extends \Exception
{
    private int $responseCode;

    public function __construct(int $responseCode, string $message)
    {
        parent::__construct($message);
        $this->responseCode = $responseCode;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }
}
