<?php
/**
 * KeyCRM Integration.
 *
 * @package  WC_Keycrm_Proxy
 * @category Integration
 * @author   KeyCRM
 */

if ( ! class_exists( 'WC_Keycrm_Proxy' ) ) :

    /**
     * Class WC_Keycrm_Proxy
     */
    class WC_Keycrm_Proxy
    {
        protected $keycrm;
        protected $corporateEnabled;

        public function __construct($api_url, $api_key, $corporateEnabled = false)
        {
            $this->corporateEnabled = $corporateEnabled;

            if ( ! class_exists( 'WC_Keycrm_Client_V5' ) ) {
                include_once(WC_Integration_Keycrm::checkCustomFile('include/api/class-wc-keycrm-client-v5.php'));
            }

            $this->keycrm = new WC_Keycrm_Client_V5($api_url, $api_key, 'v5');
        }

        /**
         * getCorporateEnabled
         *
         * @return bool
         */
        public function getCorporateEnabled()
        {
            return $this->corporateEnabled;
        }

        private static function reduceErrors($errors)
        {
            $result = '';

            foreach ($errors as $key => $error) {
                $result .= " [$key] => $error";
            }

            return $result;
        }

        /**
         * Response will be omitted in debug logs for those methods
         *
         * @return string[]
         */
        private function methodsWithoutDebugResponse()
        {
            $methodsList = array('statusesList', 'paymentTypesList', 'deliveryTypesList', 'orderMethodsList');

            foreach ($methodsList as $key => $method) {
                $method = get_class($this->keycrm) . '::' . $method;
                $methodsList[$key] = $method;
            }

            return $methodsList;
        }

        public function __call($method, $arguments)
        {
            $result = '';
            $response = null;
            $called = sprintf('%s::%s', get_class($this->keycrm), $method);

            try {
                WC_Keycrm_Logger::debug(
                    $called,
                    array(empty($arguments) ? '[no params]' : print_r($arguments, true))
                );
                /** @var \WC_Keycrm_Response $response */
                $response = call_user_func_array(array($this->keycrm, $method), $arguments);

                if (is_string($response)) {
                    WC_Keycrm_Logger::debug($called, array($response));
                    return $response;
                }

                if (empty($response)) {
                    WC_Keycrm_Logger::add(sprintf("[%s] null (no response whatsoever)", $called));
                    return null;
                }

                if ($response->isSuccessful()) {
                    // Don't print long lists in debug logs (errors while calling this will be easy to detect anyway)
                    // Also don't call useless array_map at all while debug mode is off.
                    if (keycrm_is_debug()) {
                        if (in_array(
                            $called,
                            $this->methodsWithoutDebugResponse()
                        )) {
                            WC_Keycrm_Logger::debug($called, array('[request was successful, but response is omitted]'));
                        } else {
                            WC_Keycrm_Logger::debug($called, array($response->getRawResponse()));
                        }
                    }

                    $result = ' Ok';
                } else {
                    $result = sprintf(
                        $called ." : Error: [HTTP-code %s] %s",
                        $response->getStatusCode(),
                        $response->getErrorString()
                    );

                    if (isset($response['errors'])) {
                        $result .= self::reduceErrors($response['errors']);
                    }

                    WC_Keycrm_Logger::debug($called, array($response->getErrorString()));
                    WC_Keycrm_Logger::debug($called, array($response->getRawResponse()));
                }

                WC_Keycrm_Logger::add(sprintf("[%s] %s", $called, $result));
            } catch (WC_Keycrm_Exception_Curl $exception) {
                WC_Keycrm_Logger::debug(get_class($this->keycrm).'::'.$called, array($exception->getMessage()));
                WC_Keycrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Keycrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            } catch (WC_Keycrm_Exception_Json $exception) {
                WC_Keycrm_Logger::debug(get_class($this->keycrm).'::'.$called, array($exception->getMessage()));
                WC_Keycrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Keycrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            } catch (InvalidArgumentException $exception) {
                WC_Keycrm_Logger::debug(get_class($this->keycrm).'::'.$called, array($exception->getMessage()));
                WC_Keycrm_Logger::debug('', array($exception->getTraceAsString()));
                WC_Keycrm_Logger::add(sprintf("[%s] %s - %s", $called, $exception->getMessage(), $result));
            }

            return !empty($response) ? $response : new WC_Keycrm_Response(900, '{}');
        }
    }
endif;