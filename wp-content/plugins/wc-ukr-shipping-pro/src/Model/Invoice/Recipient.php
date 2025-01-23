<?php

namespace kirillbdev\WCUkrShipping\Model\Invoice;

use kirillbdev\WCUkrShipping\Contracts\ApiSerializeInterface;
use kirillbdev\WCUkrShipping\Model\Invoice\Address\InvoiceAddress;

if ( ! defined('ABSPATH')) {
    exit;
}

class Recipient implements ApiSerializeInterface
{
    /**
     * @var string
     */
    public $ref;

    /**
     * @var string
     */
    public $contactRef;

    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $middlename;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var InvoiceAddress
     */
    public $address;

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return "$this->lastname $this->firstname $this->middlename";
    }

    /**
     * Serialize object for the possibility of transfer via api.
     *
     * @return array
     */
    public function serialize()
    {
        return [
            'CounterpartyType' => 'PrivatePerson',
            'CounterpartyProperty' => 'Recipient',
            'FirstName' => $this->firstname,
            'LastName' => $this->lastname,
            'MiddleName' => $this->middlename,
            'Phone' => $this->phone,
            'Email' => $this->email
        ];
    }
}