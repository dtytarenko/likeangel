<?php

namespace kirillbdev\WCUkrShipping\Services\Checkout;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutService
{
    public function renderCheckoutFields($type)
    {
        ?>
        <div id="wcus-<?= $type ?>-fields"></div>
        <?php
    }
}