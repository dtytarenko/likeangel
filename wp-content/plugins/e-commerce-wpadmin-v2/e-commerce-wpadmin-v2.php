<?php
/**
 * Plugin Name: WPadmin E-commerce integration v2
 * Plugin URI: http://wpadmin.pro/
 * Description: Google and FB feeds, analytics code integration with order process
 * Version: 2.2.1
 * Text Domain: wpadminpro
 * Domain Path: /languages
 * Author: Dmytro Kondriuk
 * Author URI: http://wpadmin.pro/
 * Update URI: https://wordpress.co.ua/plugins-update/
 * License: GPL12
 */
__('WPadmin E-commerce integration v2', 'wpadminpro');

defined( 'ABSPATH' ) || exit;
define( 'WPAECV2_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPAECV2_FILE', plugin_basename( __FILE__ ) );
define( 'WPAECV2_SLUG', plugin_basename( __DIR__ ) );

function wpadmin_v2_load_plugin_textdomain() {
    load_plugin_textdomain( 'wpadminpro', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );	
}
add_action( 'plugins_loaded', 'wpadmin_v2_load_plugin_textdomain' );

function wpadmin_check_update(){
		// set auto-update params
	$plugin_current_version = '2.2.1';
	$plugin_remote_path     = 'https://wordpress.co.ua/plugin-update';
	$plugin_slug            =  plugin_basename(__FILE__);
	$license_user           = 'plugin';
	$license_key            = 'trial';

	// only perform Auto-Update call if a license_user and license_key is given
	if ( $license_user && $license_key && $plugin_remote_path )
	{
		new wp_autoupdate ($plugin_current_version, $plugin_remote_path, $plugin_slug, $license_user, $license_key);
	}
}

add_action('init', 'wpadmin_check_update');

require_once( 'inc/admin.php' );
require_once( 'wp_autoupdate.php' );
require_once( 'inc/gen_feed.php' );
require_once( 'inc/send-event.php' );
require_once( 'inc/send-event-ga4.php' );
require_once( 'inc/send-event-gads.php' );
require_once( 'inc/send-event-fb.php' );
require_once( 'inc/functions.php' );