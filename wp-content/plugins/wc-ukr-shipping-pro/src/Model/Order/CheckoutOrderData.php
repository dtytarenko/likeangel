<?php

namespace kirillbdev\WCUkrShipping\Model\Order;

use kirillbdev\WCUkrShipping\Contracts\AddressInterface;
use kirillbdev\WCUkrShipping\Contracts\OrderDataInterface;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Model\Address\CheckoutAddress;
use kirillbdev\WCUkrShipping\Model\OrderProduct;

if ( ! defined('ABSPATH')) {
    exit;
}

class CheckoutOrderData implements OrderDataInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $data;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * CheckoutOrder constructor.
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;

        $this->init();
    }

    /**
     * @return float
     */
    public function getSubTotal()
    {
        return (float)wc()->cart->get_subtotal();
    }

    /**
     * @return float
     */
    public function getDiscountTotal()
    {
        return (float)wc()->cart->get_discount_total();
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return (float)wc()->cart->get_total('');
    }

    /**
     * @return float
     */
    public function getCalculatedTotal()
    {
        return $this->getSubTotal() + (float)wc()->cart->get_fee_total() + (float)wc()->cart->get_taxes_total(true, false) - $this->getDiscountTotal();
    }

    /**
     * @return OrderProduct[]
     */
    public function getProducts()
    {
        $products = [];
        $productFactory = new ProductFactory();
        $items = wc()->cart->get_cart();

        foreach ($items as $item) {
            $product = $productFactory->makeCartItemProduct($item);

            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * @return string
     */
    public function getShippingCity()
    {
        return $this->address->getCityRef();
    }

    /**
     * @return bool
     */
    public function isAddressShipping()
    {
        return $this->address->isAddressShipping();
    }

    private function init()
    {
        if (isset($this->data['ship_to_different_address']) && 1 === (int)$this->data['ship_to_different_address']) {
            $this->type = 'shipping';
        } else {
            $this->type = 'billing';
        }

        $this->address = new CheckoutAddress($this->data, $this->type);
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        if ( ! empty($this->data['payment_method'])) {
            return $this->data['payment_method'];
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isCODPayment()
    {
        return $this->getPaymentMethod() === wcus_get_option('cod_payment_id');
    }

    /**
     * @return AddressInterface
     */
    public function getShippingAddress()
    {
        return $this->address;
    }

    /**
     * @return bool
     */
    public function isDifferentAddressShipping()
    {
        return 'shipping' === $this->type;
    }

    public function getShippingType(): ?string
    {
        return $this->data['shipping_type'] ?? null;
    }
}