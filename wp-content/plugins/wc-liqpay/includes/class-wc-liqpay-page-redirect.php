<?php
/**
 * class-wc-liqpay-page-redirect
 */

 class Wc_Liqpay_Page_Redirect
 {
    protected $page_error;
    protected $page_thk;
    protected $options;
    protected $id;

    protected $order;
    
    public function __construct( )
    {
        $this->load_dep(); 

        $this->options = !empty( get_option('woocommerce_liqpay_settings') ) ? get_option('woocommerce_liqpay_settings') : [];    
        $this->page_error = !empty($this->options['redirect_page_error'] ) ? $this->options['redirect_page_error'] : '';
        $this->page_thk = !empty($this->options['redirect_page'] ) ? $this->options['redirect_page'] : '';

        $this->id = 'liqpay';
  
    }


    private function load_dep()
    {
        require_once WP_PLUGIN_DIR.'/woocommerce/woocommerce.php';
        global $woocommerce;
        if(empty($woocommerce)) {
            $woocommerce = new WooCommerce();
        }
    }


    /** 
    * redirect to error page ligpay 
    **/

    public function redirect_to_error_pay(  ) {

        global $woocommerce;
        $redirect_pag = trim($this->page_thk);
        $order_id = null;

        $url_request = urldecode($_SERVER['REQUEST_URI'] );
        $url_request = preg_replace("/\?.+/", "", $url_request); 

            if( stripos( $url_request , 'order-received' ) !== false  && !empty( $_GET['key'] ) ) {
                
                $order_id = wc_get_order_id_by_order_key( sanitize_key( $_GET[ 'key' ] ) );
            
            }elseif( !empty( $_GET['wc_order_id'] ) && 
                    ( stripos( $redirect_pag, $url_request ) !== false ) ){
                $order_id = sanitize_key( $_GET['wc_order_id'] );

            }

        return $order_id;

    }

    public function redirect_to_error(){

        if($this->page_error !== '' ){

            global $woocommerce;
            $order_id = $this->redirect_to_error_pay(  );

            if(empty($order_id)) return;

            $order = wc_get_order( $order_id );

            if(empty($order)) return;


            $status_list = ['cancelled','on-hold','pending','failed', 'refunded'];
            $status = $order->get_status();

            if( $order->get_payment_method() == $this->id && in_array($status, $status_list) ){

                    wp_redirect( esc_url($this->page_error) );
                
                exit;
            }
        }
    }

 }