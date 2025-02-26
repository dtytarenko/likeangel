<?php
/**
 * Morkva Liqpay Payment Module
 *
 *
 * @category        Morkva LiqPay
 * @package         mrkv-liqpay-extended/mrkv-liqpay-extended
 * @version         3.0
 * @author          Morkva
 *
 */

/**
 * Payment method liqpay process
 *
 */
class MorkvaLiqPay
{
     /**
     * @param string Constant of currency
     * 
     * */
    const CURRENCY_UAH = 'UAH';
    /**
     * @param string Constant of currency
     * 
     * */
    const CURRENCY_USD = 'USD';
    /**
     * @param string Constant of currency
     * 
     * */
    const CURRENCY_EUR = 'EUR';

    /**
     * @param string Main API Liqpay url
     * 
     * */
    private $_api_url = 'https://www.liqpay.ua/api/';

    /**
     * @param string Main API Liqpay Version 3 url
     * 
     * */
    private $_checkout_url = 'https://www.liqpay.ua/api/3/checkout';

    /**
     * @param array All currencies
     * 
     * */
    protected $_supportedCurrencies = array(
        self::CURRENCY_UAH,
        self::CURRENCY_USD,
        self::CURRENCY_EUR
    );

    /**
     * @param string Public Key
     * 
     * */
    private $_public_key;

    /**
     * @param string Private Key
     * 
     * */
    private $_private_key;

    /**
     * @param string Server response
     * 
     * */
    private $_server_response_code = null;

    /**
     * Constructor Liqpay class
     *
     * @param string $public_key
     * @param string $private_key
     * @param string $api_url (optional)
     *
     * @throws InvalidArgumentException
     */
    public function __construct($public_key, $private_key, $api_url = null)
    {
        # Check Public Key
        if (empty($public_key)) 
        {
            # Stop job and show error
            throw new InvalidArgumentException(__('Public Key is not entered in the settings', 'mrkv-liqpay-extended-pro'));
        }

        # Check Private Key
        if (empty($private_key)) 
        {
            # Stop job and show error
            throw new InvalidArgumentException(__('Private Key is not entered in the settings', 'mrkv-liqpay-extended-pro'));
        }

        # Save Public Key
        $this->_public_key = $public_key;
        # Save Private Key
        $this->_private_key = $private_key;
        
        # If url not null
        if (null !== $api_url) 
        {
            # Save api url
            $this->_api_url = $api_url;
        }
    }

    /**
     * Return last api response http code
     *
     * @return string|null
     */
    public function get_response_code()
    {
        # Return response code
        return $this->_server_response_code;
    }

    /**
     * Create link for request
     *
     * @param array $params
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function cnb_link($params)
    {
        # Save language code
        $language = 'uk';

        # Combine all parameters
        $params    = $this->cnb_params($params);
        # Encode all parameters
        $data      = $this->encode_params($params);
        # Create signature
        $signature = $this->cnb_signature($params);

        # Return request url 
        return $this->_checkout_url . '?' . build_query(array('data' => $data, 'signature' => $signature));
    }
    
    /**
     * Return raw data for custom form
     *
     * @param $params All params
     * @return array
     */
    public function cnb_form_raw($params)
    {
        # Get all params
        $params = $this->cnb_params($params);
        
        # Return all data
        return array(
            'url'       => $this->_checkout_url,
            'data'      => $this->encode_params($params),
            'signature' => $this->cnb_signature($params)
        );
    }

    /**
     * Create signature data
     *
     * @param array $params
     *
     * @return string
     */
    public function cnb_signature($params)
    {
        # Get all params
        $params      = $this->cnb_params($params);
        # Get Private key 
        $private_key = $this->_private_key;

        # Encode json params
        $json      = $this->encode_params($params);
        # Create signature
        $signature = $this->str_to_sign($private_key . $json . $private_key);

        # Return signature
        return $signature;
    }

    /**
     * Return all params function
     *
     * @param array $params All params
     *
     * @return array $params
     */
    private function cnb_params($params)
    {
        # Set Public Key
        $params['public_key'] = $this->_public_key;

        # Check version data
        if (!isset($params['version'])) 
        {
            # Show Error message
            throw new InvalidArgumentException(__('The version value is not set', 'mrkv-liqpay-extended-pro'));
        }

        # Check Amount of order
        if (!isset($params['amount'])) 
        {
            # Show Error message
            throw new InvalidArgumentException(__('The value of the request amount is not set', 'mrkv-liqpay-extended-pro'));
        }

        # Check Currency data
        if (!isset($params['currency'])) 
        {
            # Show Error message
            throw new InvalidArgumentException(__('Currency value is not set', 'mrkv-liqpay-extended-pro'));
        }

        # Check currency support
        if (!in_array($params['currency'], $this->_supportedCurrencies)) 
        {
            # Show Error message
            throw new InvalidArgumentException(__('Currency is not supported', 'mrkv-liqpay-extended-pro'));
        }

        # Check description data
        if (!isset($params['description'])) 
        {
            # Show Error message
            throw new InvalidArgumentException(__('The description value is not set', 'mrkv-liqpay-extended-pro'));
        }

        # Return all params
        return $params;
    }

    /**
     * Encode json params data
     *
     * @param array $params All params
     * @return string
     */
    private function encode_params($params)
    {
        # Return params
        return base64_encode(json_encode($params));
    }

    /**
     * Decode json params data
     *
     * @param string $params All params
     * @return array
     */
    public function decode_params($params)
    {
        # Return params
        return json_decode(base64_decode($params), true);
    }

    /**
     * Convert strig to signature
     *
     * @param string $str
     *
     * @return string
     */
    public function str_to_sign($str)
    {
        # Set signature
        $signature = base64_encode(sha1($str, 1));

        # Return signature
        return $signature;
    }
}
