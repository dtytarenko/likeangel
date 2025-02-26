<?php
/**
 * Class WC_Gateway_Morkva_Liqpay_Payparts file.
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
 * WC_Gateway_Morkva_Liqpay_Payparts Class
 * 
 */
class WC_Gateway_Morkva_Liqpay_Payparts extends WC_Payment_Gateway
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
        $this->description = __('Payment service. Issue an invoice in "Payment by installments" and "Instant installments"', 'mrkv-liqpay-extended-pro'); 

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

        add_filter( 'woocommerce_gateway_description', array($this, 'morkva_liqpay_gateway_description'), 25, 2 );

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
        $this->id = 'morkva-liqpay-payparts';
        $this->icon = apply_filters('woocommerce_cod_icon', '');
        $this->method_title = __('Morkva LiqPay PayParts', 'mrkv-liqpay-extended-pro');
        $this->method_description = __('Payment service. Issue an invoice in "Payment by installments" and "Instant installments"', 'mrkv-liqpay-extended-pro');
        $this->has_fields = false;
    }

    public function morkva_liqpay_gateway_description($description, $gateway_id)
    {
        if('morkva-liqpay-payparts' === $gateway_id && !isset($_GET['section']))
        {
            return $this->get_method_liqpay_description();
        }
        
        return $description; 
    }

    public function get_method_liqpay_description()
    {
        # Create partpay form calculator
        $frame_partpay = '<p style="display:flex; align-items: center;">' . __('Number of payments:', 'mrkv-liqpay-extended-pro');
        $frame_partpay .= '<select id="mrkv_liqpay_extend_pro_months" name="mrkv_liqpay_extend_pro_months" style="padding:0;margin-left: 20px; width: 50px; margin-bottom: 0;">';

        # Loop all months partpay
        for ($month = 2; $month <= $this->get_option('liq_max_months'); $month++) { 
            $frame_partpay .= '<option value="' . $month . '">' . $month . '</option>';            
        }

        $frame_partpay .= '</select><p>';

        $cart_contents_total = 1;

        if(WC() && WC()->cart){
            $cart_contents_total = WC()->cart->get_total( 'edit' );
        }

        $text_cact_months =  __('in ', 'mrkv-liqpay-extended-pro') . '<span>' . ($cart_contents_total / 2) . '</span>' . __(' uah/payments', 'mrkv-liqpay-extended-pro');

        $frame_partpay .= '<p class="mrkv_liqpay_calc" data-cart-total="' . $cart_contents_total . '">' . $text_cact_months . '</p>';

        $frame_partpay .= '<script> jQuery("body").on("change", "#mrkv_liqpay_extend_pro_months", function() { var months = jQuery(this).find(":selected").val(); var cart_total = jQuery(".mrkv_liqpay_calc").attr("data-cart-total");  var final_amount = Math.round((cart_total / months) * 100) / 100; jQuery(".mrkv_liqpay_calc span").text(final_amount); }); </script>';

        # Show partpay form calculator
        return $frame_partpay;
    }

    /**
     * Add custom gateway icon
     * 
     * @var string Icon
     * @var string Payment id
     * */
    function morkva_liqpay_gateway_icon( $icon, $id ) 
    {
        if ( $id === 'morkva-liqpay-payparts' ) 
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
        # Create payparts max months options
        $liq_max_months_options = array();

        # Loop all values
        for ($i=2; $i < 25; $i++) { 
            # Save value
            $liq_max_months_options[$i] = $i;
        }

        $all_order_statuses = wc_get_order_statuses();
        $correct_order_statuses = array();

        foreach($all_order_statuses as $k => $v)
        {
            $k = str_replace('wc-', '', $k);
            $correct_order_statuses[$k] = $v;
        }

        # Add settings fields
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
            'instructions' => array(
                'title' => __('Instructions that will be sent by email', 'mrkv-liqpay-extended-pro'),
                'type' => 'textarea',
                'description' => __('The text that will be sent to the buyer in the order confirmation letter if the payment method Lykpay is selected', 'mrkv-liqpay-extended-pro'),
                'default' => '',
                'desc_tip' => false,
            ),
            'storeId' => array(
                'title' => __('Store ID (StoreId)', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'liq_password' => array(
                'title' => __('Your store password', 'mrkv-liqpay-extended-pro'),
                'type' => 'password',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
                'placeholder' => '',
            ),
            'liq_max_months' => array(
                'title' => __('Maximum number of payments', 'mrkv-liqpay-extended-pro'),
                'type' => 'select',
                'description' => '',
                'default' => '25',
                'desc_tip' => true,
                'placeholder' => '',
                'options' => $liq_max_months_options
            ),
            'liq_debug_mode' => array(
                'title' => __('Debug mode', 'mrkv-liqpay-extended-pro'),
                'label' => __('Enable', 'mrkv-liqpay-extended-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'enabled_one_product' => array(
                'title' => __('If the order is with discounts, transfer the entire order as one item', 'mrkv-liqpay-extended-pro'),
                'label' => __('Enable', 'mrkv-liqpay-extended-pro'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'liq_one_product_name' => array(
                'title' => __('Name of the main product', 'mrkv-liqpay-extended-pro'),
                'type' => 'text',
                'description' => '',
                'default' => __('Product', 'mrkv-liqpay-extended-pro'),
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
        $description = __('Payment for the order (Payment by installments) № ', 'mrkv-liqpay-extended-pro') . $order_id;

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
        else 
        {
            # Payment complete
            $order->payment_complete();
        }

        # Remove cart data
        WC()->cart->empty_cart();

        # Include Api Morkva liqpay
        require_once(__DIR__ . '/classes/MorkvaLiqPayPartPay.php');

        # Check test mode
        $morkva_liqPay = new MorkvaLiqPayPartPay();

        # Set partcs count
        $partsCount = 2;

        # Check if user change default value parts count
        if(isset($_POST['mrkv_liqpay_extend_pro_months'])){
            # Set new count
            $partsCount = $_POST['mrkv_liqpay_extend_pro_months'];
        }

        # Create products array
        $liqpay_products = array();

        # Create products string
        $products_string = '';

        $order_total_main = "" . round($order->get_total(), 2);

        $order_items_total_condition = 0;

        foreach($order->get_items() as $item_id => $item)
        {
            $total_item = "" . round($item->get_total() / $item->get_quantity(), 2);
            $order_items_total_condition = $order_items_total_condition + $total_item;
        }

        if($this->get_option('enabled_one_product') == 'yes' && $order_total_main != $order_items_total_condition)
        {
            $custom_items = array(
                array(
                    'name' => $this->get_option('liq_one_product_name'),
                    'count' => 1,
                    'price' => $order_total_main
                )
            );

            # Loop all order items
            foreach ( $custom_items as $item ) {
                $total_item = "" . $item['price'];
                # Add product to array
                $liqpay_products[] = array(
                    'name' => $item['name'],
                    'count' => $item['count'],
                    'price' => $total_item
                );

                $total_item_string = $total_item * 100;

                # Add product to string
                $products_string .= $item['name'] . $item['count'] . $total_item_string;
            }
        }
        else
        {
            # Loop all order items
            foreach ( $order->get_items() as $item_id => $item ) {
                $total_item = "" . round($item->get_total() / $item->get_quantity(), 2);
                # Add product to array
                $liqpay_products[] = array(
                    'name' => $item->get_name(),
                    'count' => $item->get_quantity(),
                    'price' => $total_item
                );

                $total_item_string = $total_item * 100;

                # Add product to string
                $products_string .= $item->get_name() . $item->get_quantity() . $total_item_string;
            }
        }

        # Create signature
        $signature = base64_encode(sha1($this->get_option('liq_password') .  $this->get_option('storeId') . $order->get_id() . ($order_total_main * 100) . $partsCount . 'PP' . WC()->api_request_url( 'WC_Gateway_Morkva_Liqpay_Payparts' ) . $this->get_return_url($order) . $products_string .  $this->get_option('liq_password'), true ));
        
        # Create argument of query
        $arrayData = array(
            'storeId' => $this->get_option('storeId'),
            'orderId' => $order->get_id(),
            'amount' => $order_total_main,
            'partsCount' => $partsCount,
            'merchantType' => 'PP',
            'products' => $liqpay_products,
            'responseUrl' => WC()->api_request_url( 'WC_Gateway_Morkva_Liqpay_Payparts' ),
            'redirectUrl' => $this->get_return_url($order),
            'signature' => $signature
        );



        # Create result link
        $url = $morkva_liqPay->get_liqpay_partpay_url($arrayData, $order, $this->get_option('liq_debug_mode'));

        if(!$url){
            $url = $this->get_return_url($order);
        }

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

        # Get content
        $mrkv_liqpay_callback_json = @file_get_contents('php://input');

        # Get callback data
        $mrkv_liqpay_callback = json_decode($mrkv_liqpay_callback_json, true);

        # Check data response 
        $success = isset($mrkv_liqpay_callback['orderId']) && isset($mrkv_liqpay_callback['signature']);

        if($this->get_option('liq_debug_mode') == 'yes')
        {
            # Add message to order
            $order->add_order_note('Callback: ' . print_r($mrkv_liqpay_callback_json, 1), $is_customer_note = 0, $added_by_user = false);
        }

        # If payment success
        if ($success) 
        {
            $order_id = $mrkv_liqpay_callback['orderId'];
            $received_signature = '';
            $status = $mrkv_liqpay_callback['paymentState'];

            # Get response signature
            if(isset($mrkv_liqpay_callback['signature'])){
                $received_signature = sanitize_text_field($mrkv_liqpay_callback['signature']); 
            }

            # Get order data
            $order = new WC_Order($order_id);

           // file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . print_r($status, 1), FILE_APPEND); 
           // file_put_contents(__DIR__.'/log/debug.log', date('d-m-Y H:i:s') . PHP_EOL . ' order_id: ' .  print_r($order_id, 1), FILE_APPEND);  



            # Check status response 
            if ($status == 'SUCCESS') 
            {
                # Add to order note payment status
                $order->add_order_note(__('LiqPay payment Payment in installments was made successfully.<br/>Payment ID LiqPay:  ', 'mrkv-liqpay-extended-pro') . $parsed_data->liqpay_order_id ); 

                do_action('send_order_payment_to_salesdrive', $order_id);
                do_action('mrkv_keycrm_send_paid_data', $order_id, 'morkva-liqpay-payparts');

                $new_order_status = ($this->get_option( 'liqpay_order_status' ) && $this->get_option( 'liqpay_order_status' ) != '') ? $this->get_option( 'liqpay_order_status' ) : 'processing';

                # Update order status
                $order->update_status($new_order_status);

                # Switch payment to complete
                $order->payment_complete();

                $order->save();
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

    /**
     * Get store ID
     * @return string Store ID 
     * */
    public function get_liqpay_store_id()
    {
        return $this->get_option('storeId');
    } 

    /**
     * Get liqpay password
     * @return string Liqpay password 
     * */
    public function get_liqpay_password()
    {
        return $this->get_option('liq_password');
    } 
}
