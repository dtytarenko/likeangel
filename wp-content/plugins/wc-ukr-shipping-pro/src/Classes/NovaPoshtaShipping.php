<?php

use kirillbdev\WCUkrShipping\Model\Order\CheckoutOrderData;
use kirillbdev\WCUkrShipping\Services\CalculationService;

if (!defined('ABSPATH')) {
    exit;
}

class NovaPoshtaShipping extends WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        $this->id = WC_UKR_SHIPPING_NP_SHIPPING_NAME;
        $this->method_title = WC_UKR_SHIPPING_NP_SHIPPING_TITLE;
        $this->method_description = '';

        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        $this->init_settings();
        $this->init_form_fields();

        $translator = \kirillbdev\WCUkrShipping\Classes\WCUkrShipping::instance()->singleton('translate_service');
        $translates = $translator->getTranslates();

        $this->title = $translates['method_title'];

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * @param array $package
     */
    public function calculate_shipping($package = [])
    {
        if ( ! $this->shouldCalculated()) {
            $this->add_rate([
                'label' => $this->title,
                'cost' => 0,
                'package' => $package,
            ]);
            return;
        }

        if ($_GET['wc-ajax'] === 'update_order_review') {
            parse_str(sanitize_text_field($_POST['post_data']), $post);
            $orderData = new CheckoutOrderData($post);
        } elseif ($_GET['wc-ajax'] === 'checkout') {
            $orderData = new CheckoutOrderData($_POST);
        }
        $calculationService = new CalculationService();
        $cost = $calculationService->calculateCost($orderData);

        $rate = [
            'label' => $this->title,
            'cost' => $cost,
            'package' => $package,
        ];
        $this->add_rate($rate);
    }

    /**
     * Is this method available?
     * @param array $package
     * @return bool
     */
    public function is_available($package)
    {
        return $this->is_enabled();
    }

    private function shouldCalculated(): bool
    {
        if ( ! isset($_GET['wc-ajax'])) {
            return false;
        }

        return ($_GET['wc-ajax'] === 'update_order_review' && ! empty($_POST['post_data']))
            || $_GET['wc-ajax'] === 'checkout';
    }
}
