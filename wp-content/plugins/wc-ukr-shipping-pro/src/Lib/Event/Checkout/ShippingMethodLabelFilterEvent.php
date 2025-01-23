<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Lib\Event\Checkout;

final class ShippingMethodLabelFilterEvent
{
    private float $cartSubTotal;
    private ?float $shippingCost;

    public function __construct(float $cartSubTotal, ?float $shippingCost)
    {
        $this->cartSubTotal = $cartSubTotal;
        $this->shippingCost = $shippingCost;
    }

    public function getCartSubTotal(): float
    {
        return $this->cartSubTotal;
    }

    public function getShippingCost(): ?float
    {
        return $this->shippingCost;
    }
}
