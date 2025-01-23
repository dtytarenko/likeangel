<?php

namespace kirillbdev\WCUkrShipping\Modules\Frontend;

use kirillbdev\WCUkrShipping\Lib\Event\Checkout\ShippingMethodLabelFilterEvent;
use kirillbdev\WCUkrShipping\Lib\Event\EventName;
use kirillbdev\WCUkrShipping\Model\Order\CheckoutOrderData;
use kirillbdev\WCUkrShipping\Services\CalculationService;
use kirillbdev\WCUkrShipping\Services\TranslateService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class ShippingMethod implements ModuleInterface
{
    /**
     * @var TranslateService
     */
    private $translateService;

    public function __construct()
    {
        $this->translateService = wcus_container_singleton('translate_service');
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_filter('woocommerce_shipping_methods', [ $this, 'registerShippingMethod' ]);
        add_filter('woocommerce_shipping_rate_label', [ $this, 'getRateLabel' ], 10, 2);
        add_filter('wcus_novaposhta_shipping_method_label', [$this, 'getFreeShippingLabel'], 10, 2);
        add_filter('woocommerce_cart_shipping_packages', [$this, 'calculatePackageRateHash']);
        add_filter('woocommerce_calculated_total', [$this, 'calculateCartTotal'], 10, 2);

        add_filter('woocommerce_update_order_review_fragments', function ($fragments) {
            $fragments['wcus_selected_shipping'] = wc_get_chosen_shipping_method_ids();

            return $fragments;
        });
    }

    public function registerShippingMethod($methods)
    {
        include_once WC_UKR_SHIPPING_PLUGIN_DIR . '/src/Classes/NovaPoshtaShipping.php';

        $methods[WC_UKR_SHIPPING_NP_SHIPPING_NAME] = 'NovaPoshtaShipping';

        return $methods;
    }

    public function getRateLabel($label, $rate)
    {
        if (WC_UKR_SHIPPING_NP_SHIPPING_NAME === $rate->get_method_id()) {
            $methodLabel = $this->translateService->getTranslates()['method_title'];

            if (!$this->isUpdatingCheckout()) {
                return $methodLabel;
            }

            parse_str($_POST['post_data'], $post);
            $orderData = new CheckoutOrderData($post);
            $calculationService = new CalculationService();
            $cost = $calculationService->calculateCost($orderData);

            if ($cost !== null) {
                // Since 1.12.6
                $label = apply_filters(
                    EventName::NOVAPOSHTA_SHIPPING_METHOD_LABEL,
                    $methodLabel,
                    new ShippingMethodLabelFilterEvent((float)wc()->cart->get_subtotal(), $cost)
                );
            }
        }

        return $label;
    }

    public function calculatePackageRateHash(array $packages): array
    {
        // We need to perform calculation only for ajax refresh checkout and place order
        $orderData = null;
        if (isset($_GET['wc-ajax'])) {
            if ($_GET['wc-ajax'] === 'update_order_review' && ! empty($_POST['post_data'])) {
                parse_str(sanitize_text_field($_POST['post_data']), $post);
                $orderData = new CheckoutOrderData($post);
            } elseif ($_GET['wc-ajax'] === 'checkout') {
                $orderData = new CheckoutOrderData($_POST);
            }
        }

        $chosenMethods = wc_get_chosen_shipping_method_ids();
        foreach ($packages as $key => &$package) {
            if (isset($chosenMethods[$key]) && $chosenMethods[$key] === WC_UKR_SHIPPING_NP_SHIPPING_NAME
                && $orderData !== null) {
                $shippingType = $orderData->isAddressShipping() ? 'doors' : 'warehouse';
                if ($orderData->getShippingType() !== null) {
                    $shippingType = $orderData->getShippingType();
                }

                $package['wcus_rates_hash'] = md5(
                    sprintf(
                        'wcus_rates:%s|%s|%s',
                        $orderData->getShippingAddress()->getCityRef(),
                        $orderData->getPaymentMethod(),
                        $shippingType
                    )
                );
            }
        }

        return $packages;
    }

    public function calculateCartTotal(float $total, \WC_Cart $cart): float
    {
        if ( ! in_array(WC_UKR_SHIPPING_NP_SHIPPING_NAME, wc_get_chosen_shipping_method_ids(), true)) {
            return $total;
        }

        if ((int)wcus_get_option('cost_view_only') === 1) {
            return $total - $cart->get_shipping_total();
        }

        return $total;
    }

    public function getFreeShippingLabel(string $methodLabel, ShippingMethodLabelFilterEvent $event): string
    {
        if ((int)$event->getShippingCost() === 0
            && wc_ukr_shipping_get_option(WCUS_OPTION_NP_FREE_SHIPPING_TITLE_ACTIVE) === 1) {

            $label = wc_ukr_shipping_get_option(WCUS_OPTION_NP_FREE_SHIPPING_TITLE);
            if (wc_ukr_shipping_get_option('wc_ukr_shipping_np_translates_type') === WCUS_TRANSLATE_TYPE_MO_FILE) {
                $label = wcus_i18n('Nova Poshta (free)');
            }

            return apply_filters(
                EventName::NOVAPOSHTA_FREE_SHIPPING_LABEL,
                $label,
                wcus_get_current_language()
            );
        }

        return $methodLabel;
    }

    private function isUpdatingCheckout(): bool
    {
        return isset($_GET['wc-ajax'])
            && $_GET['wc-ajax'] === 'update_order_review'
            && !empty($_POST['post_data']);
    }
}
