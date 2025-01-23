<?php

namespace kirillbdev\WCUkrShipping\Dto\Repository;

if ( ! defined('ABSPATH')) {
    exit;
}

class CreateTTNDto
{
    /**
     * @var int
     */
    public $orderId;

    /**
     * @var string
     */
    public $ttnId;

    /**
     * @var string
     */
    public $ttnRef;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $statusCode;
}