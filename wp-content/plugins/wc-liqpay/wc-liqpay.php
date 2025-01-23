<?php
/*
Plugin Name: Payment Gateway for LiqPay for Woocommerce
Plugin URI:
Description: Plugin for paying for products through the LiqPay service. Works in conjunction with the Woocommerce plugin
Version: 1.10
Requires at least: 5.7.2
Requires PHP: 7.4
Author: komanda.dev
License: GPL v2 or later
Text Domain: wc-liqpay
Domain Path: /languages
Author URI: https://komanda.dev/
*/

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', 'liqpay_payment_gateway_init', 0);

function liqpay_payment_gateway_init() {

    /** dir path plugin */

    define("WC_LIQPAY_DIR", plugin_dir_url( __FILE__ )); 

    if (!class_exists('WC_Payment_Gateway')) return;

    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function($links ){
        array_unshift( $links, '<a href="admin.php?page=wc-settings&tab=checkout&section=liqpay">' . __( 'Settings', 'wc-liqpay' ) . '</a>' );
        return $links;
    });

    load_plugin_textdomain( 'wc-liqpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    add_action( 'muplugins_loaded', 'mu_kmnd_liqpay_init' );

    function mu_kmnd_liqpay_init() {

        load_muplugin_textdomain( 'wc-liqpay', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    }

    require_once plugin_dir_path(__FILE__) . 'includes/WC_Gateway_kmnd_Liqpay.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-liqpay-page-redirect.php';

      /** redirect to error page liqpay */
      $redirect_error = new Wc_Liqpay_Page_Redirect();
      
    add_action('template_redirect', function() use ($redirect_error){
     
          $redirect_error->redirect_to_error();
    });

    function kmnd_liqpay($methods) {

        $methods[] = 'WC_Gateway_kmnd_Liqpay';

        return $methods;

    }

    add_filter('woocommerce_payment_gateways', 'kmnd_liqpay');

}

