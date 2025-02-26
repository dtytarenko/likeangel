<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Liqpay Aquiring Gateway Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Morkva_Liqpay_Payparts_Blocks extends AbstractPaymentMethodType 
{
    /**
     * The gateway instance.
     *
     * @var WC_Gateway_Morkva_Liqpay_Payparts
     */
    private $gateway;

    /**
     * Payment method slug.
     *
     * @var string
     */
    protected $name = 'morkva-liqpay-payparts';

    /**
     * Initializes payment method type.
     */
    public function initialize() 
    {
        # Get payment gateway settings
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );

        # Initialize payment gateway
        $this->gateway = new WC_Gateway_Morkva_Liqpay_Payparts();
    }

    /**
     * Returns if this payment method active
     *
     * @return boolean
     */
    public function is_active() 
    {
        # Check if method enabled
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts registered
     *
     * @return array
     */
    public function get_payment_method_script_handles() 
    {
        # Register script
        wp_register_script(
            'morkva-liqpay-payparts-blocks-integration',
            LIQPAY_PLUGIN_URL . 'js/frontend/morkva-liqpay-payparts-blocks.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ),
            null,
            true
        );

        # Return data
        return array( 'morkva-liqpay-payparts-blocks-integration' );
    }

    /**
     * Returns payment method data availible
     *
     * @return array
     */
    public function get_payment_method_data() 
    {
        # Create payment data
        $payment_data = array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon' => $this->gateway->get_icon_url()
        );

        # Return payment data
        return $payment_data;
    }
}