<?php

namespace kirillbdev\WCUkrShipping\Model\Order;

use kirillbdev\WCUkrShipping\Contracts\AddressInterface;
use kirillbdev\WCUkrShipping\Contracts\OrderDataInterface;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Model\Address\AdminEditOrderAddress;
use kirillbdev\WCUkrShipping\Model\OrderProduct;

if ( ! defined('ABSPATH')) {
    exit;
}

class AdminEditOrderData implements OrderDataInterface
{
    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var array
     */
    private $data;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * AdminEditOrderData constructor.
     *
     * @param \WC_Order $order
     * @param array $data
     */
    public function __construct($order, $data)
    {
        $this->order = $order;
        $this->data = $data;
        $this->address = new AdminEditOrderAddress($data);
    }

    /**
     * @return float
     */
    public function getSubTotal()
    {
        return (float)$this->order->get_subtotal();
    }

    /**
     * @return float
     */
    public function getDiscountTotal()
    {
        return (float)$this->order->get_discount_total();
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return (float)$this->order->get_total();
    }

    /**
     * @return float
     */
    public function getCalculatedTotal()
    {
        return $this->getSubTotal() + (float)$this->order->get_total_fees() + (float)$this->order->get_total_tax('') - $this->getDiscountTotal();
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->order->get_payment_method();
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
    public function isAddressShipping()
    {
        return $this->address->isAddressShipping();
    }

    /**
     * @return bool
     */
    public function isDifferentAddressShipping()
    {
        return true;
    }

    /**
     * @return OrderProduct[]
     */
    public function getProducts()
    {
        $products = [];
        $productFactory = new ProductFactory();
        $items = $this->order->get_items();

        foreach ($items as $item) {
            $product = $productFactory->makeOrderItemProduct($item);

            if ($item) {
                $products[] = $product;
            }
        }

        return $products;
    }

    public function getShippingType(): ?string
    {
        return null;
    }
}