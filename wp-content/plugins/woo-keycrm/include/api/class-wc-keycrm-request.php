<?php

/**
 * PHP version 5.3
 *
 * Request class
 *
 * @category Integration
 * @package  WC_Keycrm_Request
 * @author   KeyCRM <dev@keycrm.app>
 * @license  https://opensource.org/licenses/MIT MIT License
 */

if ( ! class_exists( 'WC_Keycrm_Exception_Curl' ) ) {
    include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-exception-curl.php'));
}

if ( ! class_exists( 'WC_Keycrm_Response' ) ) {
    include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-response.php'));
}

class WC_Keycrm_Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $url;
    protected $defaultParameters;

    /**
     * Client constructor.
     *
     * @param string $url               api url
     * @param array  $defaultParameters array of parameters
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($url, array $defaultParameters = array())
    {
        if (false === stripos($url, 'https://')) {
            throw new \InvalidArgumentException(
                'API schema requires HTTPS protocol'
            );
        }

        $this->url = $url;
        $this->defaultParameters = $defaultParameters;
    }

    /**
     * Make HTTP request
     *
     * @param string $path       request url
     * @param string $method     (default: 'GET')
     * @param array  $parameters (default: array())
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @throws \InvalidArgumentException
     * @throws WC_Keycrm_Exception_Curl
     *
     * @return WC_Keycrm_Response
     */
    public function makeRequest(
        $path,
        $method,
        array $parameters = array()
    ) {
        $allowedMethods = array(self::METHOD_GET, self::METHOD_POST);

        if (!in_array($method, $allowedMethods, false)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Method "%s" is not valid. Allowed methods are %s',
                    $method,
                    implode(', ', $allowedMethods)
                )
            );
        }
        $parameters = array_merge($this->defaultParameters, $parameters);
        $url = $this->url . $path;




#todo тестовый урл
        if (!allowedPath($path)){
            return new WC_Keycrm_Response(777);
        }
        $setts = get_option( 'woocommerce_integration-keycrm_settings', null);
        $parameters['source'] = $setts['order_methods'];

        $parameters = globalConvert_kcrm($path, $parameters); //добавлено
        if (isset($parameters['url'])){
            $apiKey = $this->defaultParameters['apiKey'];
            $urlArr = explode('/api/', $this->url);
            $url = trim($urlArr[0], '/api').$parameters['url'];
            unset($parameters['apiKey'], $parameters['url'], $parameters['source']);
            if (isset ($parameters['order'])) {
                $parameters = $parameters['order'];
            }
        }




        if (self::METHOD_GET === $method && count($parameters)) {
            $url .= '?' . http_build_query($parameters, '', '&');
        }

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_FAILONERROR, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 30);

        if (self::METHOD_POST === $method) {
            curl_setopt($curlHandler, CURLOPT_POST, true);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [ //добавлено
            'Authorization: ' . 'Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        
        $responseBody = curl_exec($curlHandler);
        $responseBody = convertResponce($responseBody, $path);
        $statusCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $errno = curl_errno($curlHandler);
        $error = curl_error($curlHandler);

        curl_close($curlHandler);

        if ($errno) {
            throw new WC_Keycrm_Exception_Curl($error, $errno);
        }

        return new WC_Keycrm_Response($statusCode, $responseBody);
    }
}
