<?php
/**
 * Morkva Liqpay PartPay Payment Module
 *
 *
 * @category        Morkva LiqPay
 * @package         mrkv-liqpay-extended-pro/mrkv-liqpay-extended-pro
 * @version         3.0
 * @author          Morkva
 *
 */

class MorkvaLiqPayPartPay
{
	/**
     * @param string Main API Liqpay url
     * 
     * */
    private $_api_url = 'https://payparts2.privatbank.ua/ipp/v2/payment/create';

    /**
     * @param string Return Liqpay Partpay url
     * 
     * */
    private $_return_url = 'https://payparts2.privatbank.ua/ipp/v2/payment';

    /**
     * @param string Return Liqpay Partpay url
     * 
     * */
    private $_payment_status_url = 'https://payparts2.privatbank.ua/ipp/v2/payment/state';

	/**
	 * Constructor PartPay
	 * */
	public function __construct()
    {

    }

    /**
     * Send requset 
     * @param array Params
     * @return mixed Answer
     * */
    public function get_liqpay_partpay_url($params, $order, $debug){
    	# Create request header
        $mrkv_mono_headers = array(
            'Content-type'  => 'application/json',
            'Accept' => 'application/json'
        );

    	# Create request args
        $mrkv_liqpay_partpay_args = array(
            'method'      => 'POST',
            'body'        => json_encode($params),
            'headers'     => $mrkv_mono_headers
        );

        # Send request
        $mrkv_liqpay_partpay_request = wp_safe_remote_post($this->_api_url, $mrkv_liqpay_partpay_args);

        # Create result data
        $result = '';

        if($debug == 'yes')
        {
            # Add message to order
            $order->add_order_note('Request: ' . print_r(json_encode($params, 1)), $is_customer_note = 0, $added_by_user = false);
        }

        # Check result
        if(is_array($mrkv_liqpay_partpay_request) && isset($mrkv_liqpay_partpay_request['body'])){
        	# Decode json
        	$result = json_decode($mrkv_liqpay_partpay_request['body']);

            if($debug == 'yes')
            {
                # Add message to order
                $order->add_order_note('Result: ' . print_r($result, 1), $is_customer_note = 0, $added_by_user = false);
            }

        	if(isset($result) && isset($result->state) && ($result->state == 'SUCCESS') && isset($result->token)){
        		return $this->_return_url . '?token=' . $result->token;
        	}
        }
        else{
        	return '';
        }
    }

    /**
     * Send requset status
     * @param array Params
     * @return array Answer
     * */
    public function get_liqpay_payparts_status($params)
    {
        # Create request header
        $mrkv_mono_headers = array(
            'Content-type'  => 'application/json',
            'Accept' => 'application/json'
        );

        # Create request args
        $mrkv_liqpay_partpay_args = array(
            'method'      => 'POST',
            'body'        => json_encode($params),
            'headers'     => $mrkv_mono_headers
        );

        # Send request
        $mrkv_liqpay_partpay_request = wp_safe_remote_post($this->_payment_status_url, $mrkv_liqpay_partpay_args);

        # Create result data
        $result = '';

        # Check result
        if(is_array($mrkv_liqpay_partpay_request) && isset($mrkv_liqpay_partpay_request['body'])){
            # Decode json
            $result = json_decode($mrkv_liqpay_partpay_request['body'], true);

            # Return result
            return $result;
        }
    }
}