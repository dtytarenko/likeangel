<?php
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

# Check if class exist
if (!class_exists('MRKV_LIQPAY_ORDERS'))
{
	/**
	 * Class for setup woo settings
	 */
	class MRKV_LIQPAY_ORDERS
	{
		/**
		 * Constructor for woo settings
		 * */
		function __construct()
		{
			# Check payparts rules
			add_filter( 'woocommerce_available_payment_gateways', array($this, 'mrkv_liqpay_turn_off_payparts'));

			# Add metabox to order edit
			add_action('add_meta_boxes', array( $this, 'mrkv_liqpay_add_meta_boxes' ));

			# Check AJAX Payparts status
			add_action( 'wp_ajax_submit_morkva_liqpay_check_status', array( $this, 'mrkv_liqpay_check_payparts_status' ));
			add_action( 'wp_ajax_nopriv_submit_morkva_liqpay_check_status', array( $this, 'mrkv_liqpay_check_payparts_status' ));

			add_action('woocommerce_order_status_changed', array($this, 'mrkv_liqpay_afterpay_create'), 99, 3);
		}

		/**
	     * Create afterpay 
	     *
	     * @param string $order_id Order ID
	     * @param string $old_status old order status
	     * @param string $new_status new order status
	     */
	    public function mrkv_liqpay_afterpay_create($order_id, $old_status, $new_status)
	    {
	    	# Get order by id
            $order = wc_get_order($order_id);

        	if($order)
        	{
        		# Get payment method
	            $payment_method = $order->get_payment_method();

	            # Check monopay method
	            if($payment_method == 'morkva-liqpay-prepay')
	            {
	            	$wc_gateways      = new WC_Payment_Gateways();
		    		$payment_gateways = $wc_gateways->get_available_payment_gateways();
		    		$liqpay_payment_gateway = $payment_gateways['morkva-liqpay-prepay']; 

		    		if($liqpay_payment_gateway->get_afterpay_status())
		    		{
		    			$status_liqpay = str_replace('wc-', '', $liqpay_payment_gateway->get_afterpay_status());

		    			if($status_liqpay == $new_status)
		    			{
		    				$amount_main = $order->get_total() - $liqpay_payment_gateway->get_prepay_number();
		    				do_action('mrkv_checkbox_pro_create_afterpay_receipt', $order, $amount_main);
		    			}
		    		}
	            }
        	}
	    }

		/**
		 * Check payparts rules
		 * @param array Available Gateways
		 * @return array Available Gateways
		 * */
		public function mrkv_liqpay_turn_off_payparts($available_gateways)
		{
		    # Check admin list
		    if( is_admin() ) {
		        return $available_gateways;
		    }

		    # Check cart object exist
		    if(WC()->cart)
		    {
		    	# Cart/Checkout page
			    $cart_total = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );

			    # Check cart total
			    if ($cart_total && $cart_total < 500 && isset($available_gateways[ 'morkva-liqpay-payparts' ])) {
			        unset( $available_gateways[ 'morkva-liqpay-payparts' ] ); // unset Liqpay Payparts
			    }
		    }

		    # Return result
		    return $available_gateways;
		}

		/**
	     * Generating meta boxes
	     *
	     * @since 1.0.0
	     */
	    public function mrkv_liqpay_add_meta_boxes()
	    {
	    	# Check hpos
	        if(class_exists( CustomOrdersTableController::class )){
	            $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
	            ? wc_get_page_screen_id( 'shop-order' )
	            : 'shop_order';
	        }
	        else{
	            $screen = 'shop_order';
	        }

	        # Check order id
	    	if (isset($_GET["post"]) || isset($_GET["id"])) 
	    	{
	    		# Set order id
	    		$order_id = '';

	    		# Check get data
	            if(isset($_GET["post"]))
	            {
	            	# Set order id
	                $order_id = $_GET["post"];    
	            }
	            else
	            {
	            	# Set order id
	                $order_id = $_GET["id"];
	            }

	            # Get order by id
	            $order = wc_get_order($order_id);

            	if($order)
            	{
            		# Get payment method
		            $payment_method = $order->get_payment_method();

		            # Check monopay method
		            if('morkva-liqpay-payparts' == $payment_method)
		            {
		            	# Add metabox
		         		add_meta_box('mrkv_liqpay_order', __('Liqpay Payparts', 'mrkv-liqpay-extended-pro'), array( $this, 'mrkv_liqpay_add_plugin_meta_box' ), $screen, 'side', 'core');   
		            }
            	}
	    	}
	    }

	    /**
	     * Add content liqpay to metabox
	     * */
	    public function mrkv_liqpay_add_plugin_meta_box()
	    {
	    	# Check order id
	    	if (isset($_GET["post"]) || isset($_GET["id"])) 
	    	{
	    		# Set order id
	    		$order_id = '';

	    		# Check get data
	            if(isset($_GET["post"]))
	            {
	            	# Set order id
	                $order_id = $_GET["post"];    
	            }
	            else
	            {
	            	# Set order id
	                $order_id = $_GET["id"];
	            }

	            ?>
	            	<div class="mrkv_liqpay_payparts_metabox">
	            		<div class="mrkv_liqpay_payparts__status_call btn button"><?php echo __('Check status', 'mrkv-liqpay-extended-pro'); ?></div>
		            		<svg style="display: none; position:absolute; right:0;" version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" x="0px" y="0px"
						  viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
						    <path fill="#000" d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
						      <animateTransform 
						         attributeName="transform" 
						         attributeType="XML" 
						         type="rotate"
						         dur="1s" 
						         from="0 50 50"
						         to="360 50 50" 
						         repeatCount="indefinite" />
						  </path>
						</svg>
	            	</div>
	            	<?php 
	            		# Get order by id
	            		$order = wc_get_order($order_id);

	            		$mrkv_liqpay_payment_status = $order->get_meta('mrkv_liqpay_payment_status');

	            		if($mrkv_liqpay_payment_status)
	            		{
            				?>
            					<div style="margin-top: 10px;" class="mrkv_liqpay_payparts_metabox__status">
            						<?php echo __('Status:', 'mrkv-liqpay-extended-pro') . ' ' . $mrkv_liqpay_payment_status; ?>
            					</div>
            				<?php
	            		}
	            	?>
	            	<script>
	            		jQuery('.mrkv_liqpay_payparts__status_call').click(function(){
			      		 	var order_id = '<?php echo $order_id; ?>';

			      		 	if(order_id)
			      		 	{
			      		 			jQuery.ajax({
						            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
						            type: 'POST',
						            data: 'action=submit_morkva_liqpay_check_status&order_id=' + order_id + '', 
						            beforeSend: function( xhr ) {
						                jQuery('.mrkv_liqpay_payparts_metabox svg').show();
						            },
						            success: function( data ) {
						                location.reload();
						            }
						        });
			      		 	}
				    	});
	            	</script>	
	            <?php
	        }
	    }

	    /**
	     * Check liqpay payparts status
	     * */
	    public function mrkv_liqpay_check_payparts_status()
	    {
	    	# Check Post data
	    	if(isset($_POST[ 'order_id' ]))
			{
				# Get order id
				$order_id = $_POST[ 'order_id' ];

				# Include Api Morkva liqpay
		        require_once(__DIR__ . '/classes/MorkvaLiqPayPartPay.php');

		        # Check test mode
		        $morkva_liqPay = new MorkvaLiqPayPartPay();

		        # Get store id by liqpay payparts gateway
	    		$wc_gateways      = new WC_Payment_Gateways();
	    		$payment_gateways = $wc_gateways->get_available_payment_gateways();
	    		$liqpay_payment_gateway = $payment_gateways['morkva-liqpay-payparts'];

	    		# Get store ID
	    		$liqpay_store_id = $liqpay_payment_gateway->get_liqpay_store_id();

	    		# Get liqpay password
	    		$liqpay_password = $liqpay_payment_gateway->get_liqpay_password();

	    		# Create signature
	    		$signature = base64_encode(sha1($liqpay_password .  $liqpay_store_id . $order_id . $liqpay_password, true ));

	    		# Create params
	    		$params = array(
	    			"storeId" => $liqpay_store_id,
	    			"orderId" => $order_id,
	    			"signature" => $signature
	    		);

	    		# Send request to status
	    		$result_status = $morkva_liqPay->get_liqpay_payparts_status($params);

	    		# Check answer
	    		if(isset($result_status['paymentState']))
	    		{
	    			# Get order by id
	            	$order = wc_get_order($order_id);

	            	# Set meta
	            	$order->update_meta_data('mrkv_liqpay_payment_status', $result_status['paymentState']);

	            	# Save order data
	            	$order-> save();

	            	if($result_status['paymentState'] == 'SUCCESS')
	            	{
	            		# Update order status
		                $order->update_status('processing');

		                # Switch payment to complete
		                $order->payment_complete();

		                # Add to order note payment status
		                $order->add_order_note(__('LiqPay payment<br/> Payment made in installments successfully:  ', 'mrkv-liqpay-extended-pro')); 

		                do_action('send_order_payment_to_salesdrive', $order_id);
	            	}
	            	elseif(isset($result_status['state']) && isset($result_status['message']))
		    		{
		    			# Get order by id
		            	$order = wc_get_order($order_id);

		            	# Set meta
		            	$order->update_meta_data('mrkv_liqpay_payment_status', $result_status['state'] . ' ' . $result_status['message']);

		            	# Save order data
		            	$order-> save();
		    		}
	    		}
			}

			die;
	    }
	}
}