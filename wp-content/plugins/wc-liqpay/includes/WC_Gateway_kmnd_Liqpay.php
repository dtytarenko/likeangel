<?php

/** payment gateway
 *  class WC_Gateway_kmnd_Liqpay
 */

class WC_Gateway_kmnd_Liqpay extends WC_Payment_Gateway {

    private $_checkout_url = 'https://www.liqpay.ua/api/3/checkout';
    protected $_supportedCurrencies = array('EUR','UAH','USD','RUB','RUR');

    public function __construct() {

            global $woocommerce;
            $this->id = 'liqpay';
            $this->has_fields = false;
            $this->method_title = 'liqPay';
            $this->method_description = __('Payment system LiqPay', 'wc-liqpay');
            $this->init_form_fields();
            $this->init_settings();
            $this->public_key = $this->get_option('public_key');
            $this->private_key = $this->get_option('private_key');
//            $this->sandbox = $this->get_option('sandbox');
            $this->connection_status = $this->get_option('connection_status');

            if ($this->get_option('lang') == 'uk/en' && !is_admin()) {
                $this->lang = call_user_func($this->get_option('lang_function'));
                if ($this->lang == 'uk') {
                    $key = 0;
                } else {
                    $key = 1;   
                }

                $array_explode = explode('::', $this->get_option('title'));
                $this->title = $array_explode[$key];
                $array_explode = explode('::', $this->get_option('description'));
                $this->description = $array_explode[$key];
                $array_explode = explode('::', $this->get_option('pay_message'));
                $this->pay_message = $array_explode[$key];

            } else {

                $this->lang = $this->get_option('lang');
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->pay_message = $this->get_option('pay_message');

            }

            $this->icon = $this->get_option('icon');
            $this->status = $this->get_option('status');
            $this->redirect_page = $this->get_option('redirect_page');
            $this->redirect_page_error = $this->get_option('redirect_page_error');
            $this->button = $this->get_option('button');


//            add_action('woocommerce_receipt_liqpay', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')); 
            add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_ipn_response'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }

    }
    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        global $woocommerce;
        $success = isset($_POST['data']) && isset($_POST['signature']);
        if ($success) {

            // is the unique signature of each request
            $received_signature = $this->clean_data($_POST['signature']);

            // json string parameters encoded by the function base64
            $parsed_data = $this->decode_params($_POST['data']);
            $received_public_key = !empty($parsed_data['public_key']) ? $this->clean_data($parsed_data['public_key']) : '';
            $order_id = !empty($parsed_data['order_id']) ? sanitize_key($parsed_data['order_id']) : '';
            $status = !empty($parsed_data['status']) ? sanitize_key($parsed_data['status']) : '';

        }

    }
    public function admin_options() { ?>
        <h3><?php esc_html_e('Payment system LiqPay', 'wc-liqpay'); ?></h3>
        <?php if(!empty($this->connection_status) && $this->connection_status !='success') : ?>
            <div class="inline error">
                <p class='warning'><?php esc_html_e('Last returned result is liqpay:', 'wc-liqpay'); ?> 
                    <a href="https://www.liqpay.ua/uk/documentation/api/information/status/doc" 
                    target="_blank" 
                    rel="noopener noreferrer"><?php echo esc_html($this->connection_status);?></a>
                </p>
            </div>
        <?php endif;
            if ( $this->is_valid_for_use() ) : ?>

        <table class="form-table"><?php $this->generate_settings_html(); ?></table>

        <?php  else : ?>
        <div class="inline error">
            <p>
                <strong><?php esc_html_e('Gateway disabled', 'wc-liqpay'); ?></strong>:
                <?php esc_html_e('Liqpay does not support your stores currencies .', 'wc-liqpay'); ?>
            </p>
        </div>
    <?php endif;

    }

    /** 
     * form_fields 
     * */

    public function init_form_fields() {

        $this->form_fields = array(
                'enabled'     => array(
                    'title'   => __('Turn on/Switch off', 'wc-liqpay'),
                    'type'    => 'checkbox',
                    'label'   => __('Turn on', 'wc-liqpay'),
                    'default' => 'yes',
                ),

                'title'       => array(
                    'title'       => __('Heading', 'wc-liqpay'),
                    'type'        => 'textarea',
                    'description' => __('Title that appears on the checkout page', 'wc-liqpay'),
                    'default'     => __('LiqPay'),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __('Description', 'wc-liqpay'),
                    'type'        => 'textarea',
                    'description' => __('Description that appears on the checkout page', 'wc-liqpay'),
                    'default'     => __('Pay using the payment system LiqPay::Pay with LiqPay payment system', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'pay_message' => array(
                    'title'       => __('Message before payment', 'wc-liqpay'),
                    'type'        => 'textarea',
                    'description' => __('Message before payment', 'wc-liqpay'),
                    'default'     => __('Thank you for your order, click the button below to continue::Thank you for your order, click the button'),
                    'desc_tip'    => true,
                ),

                'public_key'  => array(
                    'title'       => __('Public key', 'wc-liqpay'),
                    'type'        => 'text',
                    'description' => __('Public key LiqPay. Required parameter', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'private_key' => array(
                    'title'       => __('Private key', 'wc-liqpay'),
                    'type'        => 'text',
                    'description' => __('Private key LiqPay. Required parameter', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'lang' => array(
                    'title'       => __('Language', 'wc-liqpay'),
                    'type'        => 'select',
                    'default'     => 'uk',
                    'options'     => array('uk'=> __('uk'), 'en'=> __('en')),
                    'description' => __('Interface language (For uk + en install multi-language plugin. Separating languages ​​with :: .)', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'lang_function'     => array(
                    'title'       => __('Language detection function', 'wc-liqpay'),
                    'type'        => 'text',
                    'default'     => 'pll_current_language',
                    'description' => __('The function of determining the language of your plugin', 'wc-liqpay'),
                    'desc_tip'    => true,

                ),

                'icon'     => array(
                    'title'       => __('Logotype', 'wc-liqpay'),
                    'type'        => 'text',
                    'default'     =>  WC_LIQPAY_DIR.'assets/images/logo_liqpay.svg',
                    'description' => __('Full path to the logo, located on the order page', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'button'     => array(
                    'title'       => __('Button', 'wc-liqpay'),
                    'type'        => 'text',
                    'default'     => '',
                    'description' => __('Full path to the image of the button to go to LiqPay', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'status'     => array(
                    'title'       => __('Order status', 'wc-liqpay'),
                    'type'        => 'text',
                    'default'     => 'processing',
                    'description' => __('Order status after successful payment', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

//                'sandbox'     => array(
//                    'title'       => __('Test mode', 'wc-liqpay'),
//                    'label'       => __('Turn on', 'wc-liqpay'),
//                    'type'        => 'checkbox',
//                    'description' => __('This mode will help to test the payment without withdrawing funds from the cards', 'wc-liqpay'),
//                    'desc_tip'    => true,
//                ),

                'redirect_page'     => array(
                    'title'       => __('Redirect page URL', 'wc-liqpay'),
                    'type'        => 'url',
                    'default'     => '',
                    'description' => __('URL page to go to after gateway LiqPay', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),

                'redirect_page_error'     => array(
                    'title'       => __('URL error Payment page', 'wc-liqpay'),
                    'type'        => 'url',
                    'default'     => '',
                    'description' => __('URL page to go to after gateway LiqPay', 'wc-liqpay'),
                    'desc_tip'    => true,
                ),
        );

    }

    function is_valid_for_use() {

        if (!in_array(get_option('woocommerce_currency'), array('RUB', 'UAH', 'USD', 'EUR'))) {
            return false;
        }
        return true;
    }

    /**
     * @param $order_id
     * @return string
     */
    private function getDescription($order_id)
    {
        switch ($this->lang) {
            case 'ru' :
                $description = 'Оплата заказа № ' . $order_id;
                break;
            case 'en' :
                $description = 'Order payment # ' . $order_id;
                break;
            case 'uk' :
                $description = 'Оплата замовлення № ' . $order_id;
                break;
            default :
                $description = 'Оплата заказа № ' . $order_id;
        }

        return $description;
    }


    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {

        } else {
            $order->payment_complete();
        }

        if (trim($this->redirect_page) == '') {
            $redirect_page_url = $this->get_return_url($order);
        } else {
            $redirect_page_url = trim($this->redirect_page) . '?wc_order_id=' .$order_id;
        }

        $result_url = add_query_arg('wc-api', 'wc_gateway_' . $this->id, home_url('/'));

//// Add order items to rro_info
//        $rro_info = array(
//            'items' => array(),
//            // 'delivery_emails' => array(),
//        );
//
//        foreach ($order->get_items() as $item_id => $item) {
//            $product = $item->get_product();
//
//            $rro_info['items'][] = array(
//                'amount' => $item->get_quantity(),
//                'price' => $product->get_price(),
//                'cost' => $item->get_total(),
//                'id' => $product->get_id(),
//            );
//        }

        // Remove cart.
        //WC()->cart->empty_cart();
        require_once(__DIR__ . '/LiqPay.php');
        $LigPay = new LiqPay($this->get_option('public_key'), $this->get_option('private_key'));
        $request = array(
            'version' => '3',
            'action' => 'pay',
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'description' => $this->getDescription($order->get_id()),
            'order_id' => $order->get_id(),
            'server_url'  => esc_attr($result_url),
            //'result_url' => $this->get_return_url($order),
            'result_url'  => esc_url($redirect_page_url),
            'language' => $this->get_option('lang'),
//            'rro_info' => $rro_info,
        );

        $request = apply_filters('wc_liqpay_request_filter', $request);

        $url = $LigPay->cnb_link($request);
        return array(
            'result' => 'success',
            'redirect' => $url,
        );
    }

    function check_ipn_response() {

        global $woocommerce;
        $success = isset($_POST['data']) && isset($_POST['signature']);
        if ($success) {

            // is the unique signature of each request
            $received_signature = $this->clean_data($_POST['signature']);

            // json string parameters encoded by the function base64
            $parsed_data = $this->decode_params( $_POST['data'] );
            $received_public_key = !empty($parsed_data['public_key']) ? $this->clean_data($parsed_data['public_key']) : '';
            $order_id = !empty($parsed_data['order_id']) ? sanitize_key($parsed_data['order_id']) : '';
            $status = !empty($parsed_data['status']) ? sanitize_key($parsed_data['status']) : '';

            // is the generation of a unique signature for each request

            $str_signature = $this->private_key . $this->clean_data($_POST['data']) . $this->private_key;
            $generated_signature = $this->str_to_sign($str_signature);
            // upd status (sanitize the decoded data)

            $this->update_option( 'connection_status', $status );
            // comparison of the keys that are $generated_signature and that were returned to us $received_signature

            if ( $received_signature != $generated_signature || $this->public_key != $received_public_key) {
                wp_die('IPN Request Failure');

            }

            $order = new WC_Order($order_id);
            if ($status == 'success' || ($status == 'sandbox' && $this->sandbox == 'yes')) {
                $order->update_status($this->status, esc_html__('Order has been paid (payment received)', 'wc-liqpay'));
                $order->add_order_note(esc_html__('The client paid for his order', 'wc-liqpay'));
                $woocommerce->cart->empty_cart();
            } else {

                $order->update_status('failed', esc_html__('Payment has not been received', 'wc-liqpay'));
                wp_redirect($order->get_cancel_order_url());
                exit;
            }

        } else {
                wp_die('IPN Request Failure');
        }
    }

    private function cnb_params($params) {

        $params['public_key'] = $this->public_key;

        if (!isset($params['version'])) {

            throw new InvalidArgumentException('version is null');
        }

        if (!isset($params['amount'])) {
            throw new InvalidArgumentException('amount is null');
        }

        if (!isset($params['currency'])) {
            throw new InvalidArgumentException('currency is null');
        }

        if (!in_array($params['currency'], $this->_supportedCurrencies)) {
            throw new InvalidArgumentException('currency is not supported');
        }

        if ($params['currency'] == 'RUR') {
            $params['currency'] = 'RUB';
        }

        if (!isset($params['description'])) {
            throw new InvalidArgumentException('description is null');
        }

        return $params;

    }


    /**
     * cnb_signature
     */

    public function cnb_signature($params) {

        $params      = $this->cnb_params($params);
        $private_key = $this->private_key;
        $json      = $this->encode_params($params );
        $signature = $this->str_to_sign($private_key . $json . $private_key);
        return $signature;
    }
    /**
     * str_to_sign
     */

    public function str_to_sign($str) {
        $signature = base64_encode(sha1($str,1));
        return $signature;
    }
    /**
     * encode_params
     */
    private function encode_params($params){
        return base64_encode(json_encode($params));
    }
    
   /**
    * decode_params
    */

    public function decode_params($params){
        return json_decode(base64_decode($params), true);
    }

    /**
     *  private function to sanitize a string from user input or from the database.
     * 
     * @param string $str String to sanitize.
     * @return string Sanitized string.
     */

    private function clean_data($str){
        if ( is_object( $str ) || is_array( $str ) ) {
            return '';
        }
        $str = (string) $str;
        $filtered = wp_check_invalid_utf8( $str );
        $filtered = trim(preg_replace( '/[\r\n\t ]+/', ' ', $filtered ));
        $filtered = stripslashes($filtered);
        $filtered = htmlspecialchars($filtered);
        return $filtered;
    }
}
