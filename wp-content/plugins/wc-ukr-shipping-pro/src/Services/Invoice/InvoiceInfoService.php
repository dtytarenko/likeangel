<?php

namespace kirillbdev\WCUkrShipping\Services\Invoice;

use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseRepository;
use kirillbdev\WCUkrShipping\Factories\ProductFactory;
use kirillbdev\WCUkrShipping\Helpers\WCUSHelper;
use kirillbdev\WCUkrShipping\Model\Invoice\BackwardDelivery;
use kirillbdev\WCUkrShipping\Model\Invoice\InvoiceInfo;
use kirillbdev\WCUkrShipping\Model\Invoice\Parameters\GlobalParameters;
use kirillbdev\WCUkrShipping\Model\Invoice\Parameters\Seat;
use kirillbdev\WCUkrShipping\Model\Invoice\Parameters\SeatParameters;
use kirillbdev\WCUkrShipping\Model\Invoice\PaymentControl;
use kirillbdev\WCUkrShipping\Model\OrderProduct;

if ( ! defined('ABSPATH')) {
    exit;
}

class InvoiceInfoService
{
    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var OrderProduct[]
     */
    private $orderProducts = [];

    /**
     * @var WarehouseRepository
     */
    private $warehouseRepository;

    /**
     * InvoiceInfoService constructor.
     * @param WarehouseRepository $warehouseRepository
     */
    public function __construct($warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @param int $orderId
     * @return InvoiceInfo
     */
    public function getInfoFromOrderId($orderId)
    {
        $this->order = wc_get_order((int)$orderId);
        $factory = new ProductFactory();

        foreach ($this->order->get_items() as $item) {
            $product = $factory->makeOrderItemProduct($item);
            $this->orderProducts[] = $product;
        }

        if ($this->needPoshtomatDelivery()) {
            $parameters = new SeatParameters([
                new Seat($this->calculateWeight(), $this->calculateWidth(), $this->calculateHeight(), $this->calculateLength())
            ]);
        } else {
            $volumeEnable = apply_filters('wcus_ttn_form_volume_enable', false, $this->order);
            if (!is_bool($volumeEnable)) {
                throw new \InvalidArgumentException("Parameter 'volumeEnable' must be bool");
            }

            $seatsAmount = apply_filters('wcus_ttn_form_seats_amount', 1, $this->order);
            if (!is_int($seatsAmount) || $seatsAmount < 1) {
                throw new \InvalidArgumentException("Invalid parameter 'seatsAmount'");
            }

            $parameters = new GlobalParameters(
                $this->calculateWeight(),
                $this->calculateVolumeWeight(),
                $seatsAmount,
                $volumeEnable
            );
        }

        $paymentMethod = apply_filters('wcus_ttn_form_payment_method', 'Cash', $this->order);
        if (!in_array($paymentMethod, ['Cash', 'NonCash'], true)) {
            throw new \InvalidArgumentException("Invalid param 'paymentMethod'");
        }

        $date = apply_filters(
            'wcus_ttn_form_date',
            new \DateTime('now', new \DateTimeZone(wp_timezone_string())),
            $this->order
        );
        if (!($date instanceof \DateTimeInterface)) {
            throw new \InvalidArgumentException("Parameter 'date' must be correct date");
        }

        $cargoType = apply_filters('wcus_ttn_form_cargo_type', 'Parcel', $this->order);
        $validCargo = [
            'Cargo',
            'Documents',
            'TiresWheels',
            'Pallet',
            'Parcel',
        ];
        if (!in_array($cargoType, $validCargo, true)) {
            throw new \InvalidArgumentException("Invalid parameter 'cargoType'");
        }

        $barcode = apply_filters('wcus_ttn_form_barcode', '', $this->order);
        $additional = apply_filters('wcus_ttn_form_additional', '', $this->order);
        $payerType = apply_filters('wcus_ttn_form_payer_type', wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default'), $this->order);
        $description = apply_filters('wcus_ttn_form_description', wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_description'), $this->order);

        return new InvoiceInfo(
            $parameters,
            $payerType,
            $paymentMethod,
            apply_filters('wcus_ttn_form_cost', $this->getOrderCost($this->order), $this->order),
            $date->format('d.m.Y'),
            $cargoType,
            $description,
            $barcode,
            $additional
        );
    }

    public function getBackwardDelivery(): ?BackwardDelivery
    {
        $codPaymentId = wcus_get_option('cod_payment_id');
        if ($codPaymentId && $codPaymentId === $this->order->get_payment_method()) {
            /**
             * Enable third-party code to control cost of COD feature
             * @since 1.16.6
             */
            $cost = apply_filters('wcus_ttn_form_cod_cost', $this->getOrderCost($this->order), $this->order);

            return new BackwardDelivery(
                BackwardDelivery::$PAYER_TYPE_RECIPIENT,
                BackwardDelivery::$CARGO_TYPE_MONEY,
                (int)ceil($cost)
            );
        }

        return null;
    }

    public function getPaymentControl(): ?PaymentControl
    {
        $codPaymentId = wcus_get_option('cod_payment_id');
        $payControlActive = (int)wcus_get_option('ttn_pay_control_default') === 1;
        if ($payControlActive && $codPaymentId === $this->order->get_payment_method()) {
            /**
             * Enable third-party code to control cost of Payment Control feature
             * @since 1.16.6
             */
            $cost = apply_filters(
                'wcus_ttn_form_payment_control_cost',
                $this->getOrderCost($this->order),
                $this->order
            );

            return new PaymentControl((int)ceil($cost));
        }

        return null;
    }

    /**
     * @return float
     */
    private function calculateWeight()
    {
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $weight += $product->getWeight() * $product->getQuantity();
        }

        return $weight ? round($weight, 2) : 0.1;
    }

    /**
     * @return float
     */
    private function calculateVolumeWeight()
    {
        $weight = 0;

        foreach ($this->orderProducts as $product) {
            $volume = (float)$product->getWidth() * (float)$product->getHeight() * (float)$product->getLength() / 4000;
            $weight += $volume * $product->getQuantity();
        }

        return $weight ? round($weight, 2) : 0.1;
    }

    /**
     * @return float
     */
    private function calculateWidth()
    {
        return array_reduce($this->orderProducts, function ($current, $product) {
            return $current += $product->getWidth() * $product->getQuantity();
        }, 0);
    }

    /**
     * @return float
     */
    private function calculateHeight()
    {
        return array_reduce($this->orderProducts, function ($current, $product) {
            return $current += $product->getHeight() * $product->getQuantity();
        }, 0);
    }

    /**
     * @return float
     */
    private function calculateLength()
    {
        return array_reduce($this->orderProducts, function ($current, $product) {
            return $current += $product->getLength() * $product->getQuantity();
        }, 0);
    }

    /**
     * @return bool
     */
    private function needPoshtomatDelivery()
    {
        // Step 1: detect if sender address is poshtomat
        $warehouseRef =  wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_warehouse');
        if ($warehouseRef) {
            $warehouse = $this->warehouseRepository->getWarehouseByRef($warehouseRef);

            if ($warehouse && (int)$warehouse->warehouse_type === WCUS_WAREHOUSE_TYPE_POSHTOMAT) {
                return true;
            }
        }

        // Step 2: detect if recipient address is poshtomat
        $shipping = WCUSHelper::getOrderShippingMethod($this->order);
        if ($shipping
            && WC_UKR_SHIPPING_NP_SHIPPING_NAME === $shipping->get_method_id()
            && $shipping->get_meta('wcus_warehouse_ref')
        ) {
            $warehouse = $this->warehouseRepository->getWarehouseByRef($shipping->get_meta('wcus_warehouse_ref'));

            if ($warehouse && (int)$warehouse->warehouse_type === WCUS_WAREHOUSE_TYPE_POSHTOMAT) {
                return true;
            }
        }

        return false;
    }

    private function getOrderCost(\WC_Order $order): float
    {
        return $order->get_subtotal() + (float)$order->get_total_fees() + (float)$order->get_total_tax('') - $order->get_total_discount();
    }
}
