<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   KeyCRM <integration@keycrm.app>
 * @license  http://keycrm.app Proprietary
 * @link     http://keycrm.app
 * @see      http://help.keycrm.app
 */

class WC_Keycrm_Order extends WC_Keycrm_Abstracts_Data
{
    /** @var bool */
    public $is_new = true;

    protected $filter_name = 'order';

    protected $data = array(
        'externalId' => 0,
        'status' => '',
        'number' => '',
        'createdAt' => '',
        'firstName' => '',
        'lastName' => '',
        'email' => '',
        'paymentType' => '',
        'customerComment' => '',
        'paymentStatus' => '',
        'phone' => '',
        'countryIso' => ''
    );

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * WC_Keycrm_Order constructor.
     *
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param WC_Order $order
     *
     * @return self
     */
    public function build($order)
    {
        $firstName = $order->get_shipping_first_name();
        $lastName = $order->get_shipping_last_name();

        if (empty($firstName) && empty($lastName)) {
            $firstName = $order->get_billing_first_name();
            $lastName = $order->get_billing_last_name();
        }

        $dateCreate = $order->get_date_created();

        $data = array(
            'externalId' => $order->get_id(),
            'createdAt' => !empty($dateCreate) ? $dateCreate->date('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => strtolower($order->get_billing_email()),
            'customerComment' => $order->get_customer_note(),
            'phone' => $order->get_billing_phone(),
            'countryIso' => $order->get_shipping_country()
        );

        if ($data['countryIso'] == '--') {
            $countries = new WC_Countries();
            $data['countryIso'] = $countries->get_base_country();
        }

        $this->set_data_fields($data);
        $this->set_number($order);

        if (isset($this->settings[$order->get_status()])) {
            $this->set_data_field('status', $this->settings[$order->get_status()]);
        }

        return $this;
    }

    /**
     * @param WC_Order $order
     */
    protected function set_payment_data($order)
    {
        if ($order->get_payment_method() && isset($this->settings[$order->get_payment_method()])) {
            $this->set_data_field('paymentType', $this->settings[$order->get_payment_method()]);
        }

        if ($order->is_paid()) {
            $this->set_data_field('paymentStatus', 'paid');
        }
    }

    /**
     * @param WC_Order $order
     */
    protected function set_number($order)
    {
        if (isset($this->settings['update_number']) && $this->settings['update_number'] == WC_Keycrm_Base::YES ) {
            $this->set_data_field('number', $order->get_order_number());
        } else {
            unset($this->data['number']);
        }
    }

    public function reset_data()
    {
        $this->data = array(
            'externalId' => '',
            'status' => '',
            'number' => '',
            'createdAt' => '',
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'paymentType' => '',
            'customerComment' => '',
            'paymentStatus' => '',
            'phone' => '',
            'countryIso' => ''
        );
    }
}
