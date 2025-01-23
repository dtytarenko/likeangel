<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Model\Event\Document;

final class CarrierStatusChangedEvent
{
    private string $oldStatus;
    private string $oldStatusText;
    private string $newStatus;
    private string $newStatusText;
    private array $ttn;
    private \WC_Order $order;

    public function __construct(
        string $oldStatus,
        string $oldStatusText,
        string $newStatus,
        string $newStatusText,
        array $ttn,
        \WC_Order $order
    ) {
        $this->oldStatus = $oldStatus;
        $this->oldStatusText = $oldStatusText;
        $this->newStatus = $newStatus;
        $this->newStatusText = $newStatusText;
        $this->ttn = $ttn;
        $this->order = $order;
    }

    public function __get(string $name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        throw new \InvalidArgumentException("Property '$name' does not exist.");
    }
}
