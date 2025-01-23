<?php

namespace kirillbdev\WCUkrShipping\Model\Invoice\Parameters;

if ( ! defined('ABSPATH')) {
    exit;
}

class SeatParameters extends InvoiceParameters
{
    /**
     * @var Seat[]
     */
    private $seats;

    /**
     * SeatParameters constructor.
     * @param Seat[] $seats
     */
    public function __construct($seats)
    {
        $this->seats = $seats;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return InvoiceParameters::$TYPE_SEAT;
    }

    public function getTotalWeight(): float
    {
        $weight = 0;
        foreach ($this->seats as $seat) {
            $weight += $seat->weight;
        }

        return $weight;
    }
}
