<?php
/**
 * Class WC_Gateway_Morkva_Liqpay file.
 *
 * @package WooCommerce\Gateways
 * 
 */

# This prevents a public user from directly accessing your .php files
if (!defined('ABSPATH')) 
{
    # Exit if accessed directly
    exit; 
}

/**
 * WC_Gateway_Morkva_Liqpay Class
 * 
 */
class WC_Gateway_Morkva_Liqpay extends WC_Payment_Gateway
{
    /**
     * Constructor for the gateway
     */
    public function __construct()
    {
        # Setup general properties
        $this->setup_properties();

        # Load the settings
        $this->init_form_fields();
        $this->init_settings();

        # Get settings
        # Save Gateway title
        $this->title = $this->get_option('title');

        # Save Gateway description
        $this->description = $this->get_option('description');

        # Save Gateway instruction
        $this->instructions = $this->get_option('instructions');

        # Save Gateway default language
        $this->lang = $this->get_option('lang', 'uk');

        # Save Gateway enabled method switcher
        $this->enable_for_methods = $this->get_option('enable_for_methods', array());

        # Save Gateway enabled virtual
        $this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes';

        # Save type taxonomies support
        $this->supports = array(
                'products',
                'refunds',
        );

        # Check woo plugin version
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) 
        {
            # Version Woocommerce 2.0.0 
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_response'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } 
        else 
        {
            # Version Woocommerce 1.6.6
            add_action('init', array(&$this, 'check_response'));
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }

        # Add payment image
        add_filter( 'woocommerce_gateway_icon', array( $this, 'morkva_liqpay_gateway_icon' ), 100, 2 );  
    }

    /**
     * Setup general properties for the gateway
     * 
     */
    protected function setup_properties()
    {
        # Save slug of Morkva Liqpay
        $this->id = 'morkva-liqpay';
        $this->icon = apply_filters('woocommerce_cod_icon', '');
        $this->method_title = 'Morkva LiqPay Pro';
        $this->method_description = __('A payment service that allows you to make instant payments on the Internet and with Visa and MasterCard payment cards worldwide.', 'mrkv-liqpay-extended-pro');
        $this->has_fields = false;
    }

    /**
     * Add custom gateway icon
     * 
     * @var string Icon
     * @var string Payment id
     * */
    function morkva_liqpay_gateway_icon( $icon, $id ) 
    {
        if ( $id === 'morkva-liqpay' ) 
        {
            if($this->get_option( 'hide_image' ) == 'no')
            {
                $height_btn = '';

                if($this->get_option( 'liqpay_image_height' )  != 'no' && $this->get_option( 'liqpay_image_height' )  != '')
                {
                    $height_btn = 'style="width: auto; height: ' . $this->get_option( 'liqpay_image_height' ) . 'px; padding-top: 0.6%;"';
                }
                else
                {
                    $height_btn = 'style="width: 100%; max-width: 100px; padding-top: 0"';
                }

                if($this->get_option( 'url_liqpay_img' ))
                {
                    return '<img ' . $height_btn . ' src="' . $this->get_option( 'url_liqpay_img' ) . '" > '; 
                }
                else
                {
                    if($this->get_option( 'liqpay_image_type_black' ) != 'no')
                    {
                        return '<img ' . $height_btn . ' src="' . plugins_url( '../img/logo_liqpay_for_white.svg', __FILE__ ) . '" > '; 
                    }
                    elseif($this->get_option( 'liqpay_image_type_mini' ) != 'no')
                    {
                        return '<img ' . $height_btn . ' src="' . plugins_url( '../img/morkva-liqpay-logo.svg', __FILE__ ) . '" > '; 
                    }
                    else
                    {
                        return '<img ' . $height_btn . ' src="' . plugins_url( '../img/logo_liqpay_for_black.svg', __FILE__ ) . '" > '; 
                    }
                }
            }
        }
        
        return $icon;
    }

    public function get_icon_url()
    {
        if($this->get_option( 'hide_image' ) == 'no')
        {
            if($this->get_option( 'url_liqpay_img' ))
            {
                return $this->get_option( 'url_liqpay_img' ); 
            }
            else
            {
                if($this->get_option( 'liqpay_image_type_black' ) != 'no')
                {
                    return plugins_url( '../img/logo_liqpay_for_white.svg', __FILE__ ); 
                }
                elseif($this->get_option( 'liqpay_image_type_mini' ) != 'no')
                {
                    return plugins_url( '../img/morkva-liqpay-logo.svg', __FILE__ ); 
                }
                else
                {
                    return plugins_url( '../img/logo_liqpay_for_black.svg', __FILE__ ); 
                }
            }
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     * 
     */
    public function init_form_fields()
    {
        $all_order_statuses = wc_get_order_statuses();
        $correct_order_statuses = array();

        foreach($all_order_statuses as $k => $v)
        {
            $k = str_replace('wc-', '', $k);
            $correct_order_statuses[$k] = $v;
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/disable', 'mrkv-liqpay-extended-pro'),
                'label' => __('Enable', 'mrkv-liqpay-extended-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => __('Morkva LiqPay - Instant payments all over the world', 'mrkv-liqpay-extended-pro'),
                'default' => '',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'mrkv-liqpay-extended-pro'),
                'type' => 'textarea',
                'description' => __('The text that will be visible on the checkout page if you choose the payment method LiqPay', 'mrkv-liqpay-extended-pro'),
                'default' => '',
                'desc_tip' => false,
            ),
            'instructions' => array(
                'title' => __('Instructions that will be sent by email', 'mrkv-liqpay-extended-pro'),
                'type' => 'textarea',
                'description' => __('The text that will be sent to the buyer in the order confirmation letter if the payment method LiqPay is selected', 'mrkv-liqpay-extended-pro'),
                'default' => '',
                'desc_tip' => false,
            ),
            'public_key' => array(
                'title' => __('API public_key', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'private_key' => array(
                'title' => __('API private_key', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'test_enabled' => array(
                'title' => __('Test mode', 'mrkv-liqpay-extended-pro'),
                'label' => __('Enable', 'mrkv-liqpay-extended-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'test_enabled_admin' => array(
                'title' => __('Test mode for the administrator', 'mrkv-liqpay-extended-pro'),
                'label' => __('Enable', 'mrkv-liqpay-extended-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'test_public_key' => array(
                'title' => __('Test API public_key', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'test_private_key' => array(
                'title' => __('Test API private_key', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'liqpay_image_type_black' => array(
                'title' => __( 'Image style', 'mrkv-liqpay-extended-pro' ),
                'type' => 'checkbox',
                'label' => __( 'For white background', 'mrkv-liqpay-extended-pro' ) . '<span></span><p style="padding: 20px;"><img style="width: 200px;" src="' . LIQPAY_PLUGIN_URL . 'img/logo_liqpay_for_white.svg"></p>',
                'default' => 'yes'
            ),
            'liqpay_image_type_white' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => __( 'For black background', 'mrkv-liqpay-extended-pro' ) . '<span></span><p style="background: #676767; padding: 20px; border-radius: 10px;"><img style="width: 200px;" src="' . LIQPAY_PLUGIN_URL . 'img/logo_liqpay_for_black.svg"></p>',
                'default' => 'no'
            ),
            'liqpay_image_type_mini' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => __( 'Only icon', 'mrkv-liqpay-extended-pro' ) . '<span></span><p style="padding: 20px;"><img style="width: 100px;" src="' . LIQPAY_PLUGIN_URL . 'img/morkva-liqpay-logo.svg"></p>',
                'default' => 'no'
            ),
            'liqpay_image_height' => array(
                'title' => __( 'Image height(px)', 'mrkv-liqpay-extended-pro' ),
                'type' => 'number',
                'label' => '',
                'default' => ''
            ),
            'hide_image' => array(
                'title' => __( 'Hide logo', 'mrkv-liqpay-extended-pro' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'If checked, Liqpay logo or custom logo will not be displayed by the payment method title', 'mrkv-liqpay-extended-pro' ) . '</span>',
                'default' => 'no'
            ),
            'url_liqpay_img' => array(
                'title'       => __( 'Custom logo url', 'mrkv-liqpay-extended-pro' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'Enter full url to image', 'mrkv-liqpay-extended-pro' ),
                'default'     => '',
            ),
            'liqpay_order_status' => array(
                'title' => __( 'Status of completed payment', 'mrkv-liqpay-extended-pro' ),
                'type' => 'select',
                'description' => __( 'Select the status to which the order status will change after successful payment', 'mrkv-liqpay-extended-pro' ),
                'label' => '',
                'options' => $correct_order_statuses,
                'default' => 'processing',
            ),
        );
    }

    /**
     * Return description of order
     * 
     * @param integer $order_id Order id in Woo
     * @return string
     */
    private function getDescription($order_id)
    {
        # Create description
        $description = __('Payment for the order № ', 'mrkv-liqpay-extended-pro') . $order_id;

        # Return description
        return $description;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id)
    {
        # Get order data by id
        $order = wc_get_order($order_id);

        # Check order total 
        if ($order->get_total() > 0) 
        {
            # Send email notification
            $this->pending_new_order_notification($order->get_id());
        } 

        # Remove cart data
        WC()->cart->empty_cart();

        # Include Api Morkva liqpay
        require_once(__DIR__ . '/classes/MorkvaLiqPay.php');

        # Check test mode
        if($this->get_option( 'test_enabled_admin' ) == 'yes' && ( current_user_can('editor') || current_user_can('administrator') ))
        {
            # Use test keys
            $morkva_liqPay = new MorkvaLiqPay($this->get_option('test_public_key'), $this->get_option('test_private_key'));
        }
        
        elseif($this->get_option( 'test_enabled' ) == 'yes' && $this->get_option( 'test_enabled_admin' ) != 'yes')
        {
            # Use test keys
            $morkva_liqPay = new MorkvaLiqPay($this->get_option('test_public_key'), $this->get_option('test_private_key'));
        }
        else
        {   
            # Use main keys
            $morkva_liqPay = new MorkvaLiqPay($this->get_option('public_key'), $this->get_option('private_key'));
        }
        
        # Create argument of query
        $arrayData = array(
            'version' => '3',
            'action' => 'pay',
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'description' => $this->getDescription($order->get_id()),
            'order_id' => $order->get_id(),
            'result_url' => $this->get_return_url($order),
            'language' => 'uk',
            'server_url' => WC()->api_request_url( 'WC_Gateway_Morkva_Liqpay' )
        );

        # Create result link
        $url = $morkva_liqPay->cnb_link($arrayData);

        # Return result 
        return array( 
            'result' => 'success',
            'redirect' => $url,
        );
    }

    /**
     * Output for the order received page
     */
    public function thankyou_page()
    {
        # Stop job to five seconds
        sleep(5);

        # Get order data 
        $order = wc_get_order($order_id);

        # Check order status
        if (!$order->has_status($this->status) && $this->cancel_pay) 
        {
            # Show info by payment
            echo wp_kses_post(wpautop(wptexturize($this->cancel_pay)));
        }

        # Show Instruction for user 
        if ($this->instructions) 
        {
            # Show info by payment
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order Order object.
     * @param bool $sent_to_admin Sent to admin.
     * @param bool $plain_text Email format: plain text or HTML.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        # Check email instruction
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) 
        {
            # Show info
            echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
        }
    }

    /**
     * New order notification function
     * 
     * @param $order_id Order id
     */
    private function pending_new_order_notification($order_id)
    {
        # Get order data 
        $order = wc_get_order($order_id);

        # Only for "pending" order status
        if (!$order->has_status('pending')) return;

        # Get an instance of the WC_Email_New_Order object
        $wc_email = WC()->mailer()->get_emails()['WC_Email_New_Order'];

        # Create email data
        $wc_email->settings['subject'] = '{site_title} - ' . __('New order', 'mrkv-liqpay-extended-pro') . ' ({order_number}) - {order_date}';
        $wc_email->settings['heading'] = __('New order', 'mrkv-liqpay-extended-pro');

        # Send email
        $wc_email->trigger($order_id);
    }

    /**
     * Check response from LiqPay
     * 
     * @param $inputData All data
     * @return mixed|string|void
     */
    public function check_response($inputData)
    {
        # Get Woo global data
        global $woocommerce;

        # Check data response 
        $success = isset($_POST['data']) && isset($_POST['signature']);

        # If payment success
        if ($success) 
        {
            $data = '';
            $received_signature = '';

            # Get response data
            if(isset($_POST['data'])){
                $data = sanitize_text_field($_POST['data']);
            }
            # Get response signature
            if(isset($_POST['signature'])){
                $received_signature = sanitize_text_field($_POST['signature']); 
            }

            # Parse JSON data
            $parsed_data = json_decode(base64_decode($data)); 
            
            # Save main data response
            $received_public_key = $parsed_data->public_key;
            $order_id = $parsed_data->order_id; 
            $status = $parsed_data->status; 
            $sender_phone = $parsed_data->sender_phone;
            $amount = $parsed_data->amount;
            $currency = $parsed_data->currency;
            $transaction_id = $parsed_data->transaction_id;

            file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . ' Status: ' .  print_r($status, 1), FILE_APPEND); 

            # Get order data
            $order = new WC_Order($order_id);

            file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . print_r($status, 1), FILE_APPEND); 
            file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . ' order_id: ' .  print_r($order_id, 1), FILE_APPEND); 
            file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . ' order_id: ' .  print_r($parsed_data, 1), FILE_APPEND); 



            # Check status response 
            if ($status == 'success' || ($status == 'sandbox')) 
            {
                if(!$order->get_meta('_mrkv_liqpay_callback'))
                {
                    $message = '';

                    if(isset($parsed_data) && isset($parsed_data->sender_card_mask2) && !$order->get_meta('_mrkv_liqpay_sender_card_mask2'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_sender_card_mask2', $parsed_data->sender_card_mask2 );
                        update_post_meta( $order_id, '_mrkv_liqpay_sender_card_mask2', $parsed_data->sender_card_mask2 );


                        $message .= ' ' . __('sender_card_mask2:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->sender_card_mask2 . '<br>';
                    }

                    if(isset($parsed_data) && isset($parsed_data->sender_card_type) && !$order->get_meta('_mrkv_liqpay_sender_card_type'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_sender_card_type', $parsed_data->sender_card_type );
                        update_post_meta( $order_id, '_mrkv_liqpay_sender_card_type', $parsed_data->sender_card_type );

                        $message .= ' ' . __('sender_card_type:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->sender_card_type . '<br>';
                    }

                    if(isset($parsed_data) && isset($parsed_data->acq_id)  && !$order->get_meta('_mrkv_liqpay_acq_id'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_acq_id', $parsed_data->acq_id );
                        update_post_meta( $order_id, '_mrkv_liqpay_acq_id', $parsed_data->acq_id );

                        $message .= ' ' . __('acq_id:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->acq_id . '<br>';
                    }

                    if(isset($parsed_data) && isset($parsed_data->agent_commission) && !$order->get_meta('_mrkv_liqpay_agent_commission'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_agent_commission', $parsed_data->agent_commission );
                        update_post_meta( $order_id, '_mrkv_liqpay_agent_commission', $parsed_data->agent_commission );

                        $message .= ' ' . __('agent_commission:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->agent_commission . '<br>';
                    }

                    if(isset($parsed_data) && isset($parsed_data->liqpay_order_id) && !$order->get_meta('_mrkv_liqpay_liqpay_order_id'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_liqpay_order_id', $parsed_data->liqpay_order_id );
                        update_post_meta( $order_id, '_mrkv_liqpay_liqpay_order_id', $parsed_data->liqpay_order_id );

                        $message .= ' ' . __('liqpay_order_id:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->liqpay_order_id . '<br>';
                    }

                    if(isset($parsed_data) && isset($parsed_data->receiver_commission) && !$order->get_meta('_mrkv_liqpay_receiver_commission'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_receiver_commission', $parsed_data->receiver_commission );
                        update_post_meta( $order_id, '_mrkv_liqpay_receiver_commission', $parsed_data->receiver_commission );

                        $message .= ' ' . __('receiver_commission:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->receiver_commission . '<br>';
                    }
                    
                    if(isset($parsed_data) && isset($parsed_data->commission_credit) && !$order->get_meta('_mrkv_liqpay_commission_credit'))
                    {
                        $order->update_meta_data( '_mrkv_liqpay_commission_credit', $parsed_data->commission_credit );
                        update_post_meta( $order_id, '_mrkv_liqpay_commission_credit', $parsed_data->commission_credit );

                        $message .= ' ' . __('commission_credit:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->commission_credit . '<br>';
                    }

                    $order->update_meta_data( '_mrkv_liqpay_callback', 'sent' );
                    update_post_meta( $order_id, '_mrkv_liqpay_callback', 'sent' );

                    // Save the order.
                    $order->save();

                    # Add to order note payment status
                    $order->add_order_note(__('LiqPay payment has been completed successfully.<br/>LiqPay payment ID:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->liqpay_order_id ); 

                    if(isset($parsed_data) && isset($parsed_data->amount_debit) && isset($order_id)){
                        file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . 'testestestetse', FILE_APPEND);      
                        file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . print_r($parsed_data->amount_debit , 1), FILE_APPEND);      
                        file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . 'testestestetse', FILE_APPEND);                 

                        $order->update_meta_data( '_mrkv_liqpay_total_amount', $parsed_data->amount_debit );
                        // Save amount uah
                        update_post_meta( $order_id, '_mrkv_liqpay_total_amount', $parsed_data->amount_debit );

                        // Save the order.
                        $order->save();
                    }

                    do_action('mrkv_keycrm_send_paid_data', $order->get_id(), 'morkva-liqpay');

                    $new_order_status = ($this->get_option( 'liqpay_order_status' ) && $this->get_option( 'liqpay_order_status' ) != '') ? $this->get_option( 'liqpay_order_status' ) : 'processing';

                    # Update order status
                    $order->update_status($new_order_status);

                    # Switch payment to complete
                    $order->payment_complete();

                    // Save the order.
                    $order->save();
                }
            } 
            else 
            {
                # Update status to failed
                $order->update_status('failed', __('Error during payment', 'mrkv-liqpay-extended-pro'));

                # Stop server work
                exit;
            }
        } 
        else 
        {
            # Stop Wordpress job
            wp_die('IPN Request Failure');
        }
    }   
}
