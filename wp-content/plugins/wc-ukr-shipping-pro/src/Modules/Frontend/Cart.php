<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class Cart implements ModuleInterface
{
    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_filter('woocommerce_shipping_rate_cost', [ $this, 'shippingCost' ], 15, 2);
    }

    /**
     * @param float $cost
     * @param \WC_Shipping_Rate $rate
     *
     * @return float|int
     */
    public function shippingCost($cost, $rate)
    {
        if (WC_UKR_SHIPPING_NP_SHIPPING_NAME !== $rate->get_method_id()) {
            return $cost;
        }

        if ( ! is_cart()) {
            return $cost;
        }

        $type = wc_ukr_shipping_get_option('wc_ukr_shipping_np_price_type');

        if ('fixed' === $type) {
            $cost = $this->calculateFixedCost();
        }
        elseif ('relative_to_total' === $type) {
            $cost = $this->calculateRelativeCost();
        }

        // Force cast to float
        // todo: refactor
        $cost = (float)$cost;

        if (in_array(WC_UKR_SHIPPING_NP_SHIPPING_NAME, wc_get_chosen_shipping_method_ids())) {
            $total = (float)wc()->cart->get_subtotal() + (float)wc()->cart->get_fee_total() + (float)wc()->cart->get_taxes_total(true, false) - (float)wc()->cart->get_discount_total();

            if ((int)wcus_get_option('cost_view_only')) {
                wc()->cart->set_total($total);
            }
            else {
                wc()->cart->set_total($total + $cost);
            }
        }

        return $cost;
    }

    /**
     * @return float
     */
    private function calculateFixedCost()
    {
        return (float)wc_ukr_shipping_get_option('wc_ukr_shipping_np_price');
    }

    /**
     * @return float|int
     */
    private function calculateRelativeCost()
    {
        $relativePrices = wc_ukr_shipping_get_option('wc_ukr_shipping_np_relative_price');
        $cost = 0;

        if ( ! $relativePrices) {
            $relativePrices = [
                ['total' => 0, 'price' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_price')]
            ];
        } else {
            $relativePrices = json_decode($relativePrices, true);
        }

        $cartTotal = (float)wc()->cart->get_subtotal() + (float)wc()->cart->get_fee_total() + (float)wc()->cart->get_taxes_total(true, false) - (float)wc()->cart->get_discount_total();
        foreach ($relativePrices as $relativePrice) {
            if ($cartTotal > $relativePrice['total']) {
                $cost = $relativePrice['price'];
            }
        }

        return $cost;
    }
}