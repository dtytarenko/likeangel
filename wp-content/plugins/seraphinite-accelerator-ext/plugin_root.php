<?php
/*
Plugin Name: Seraphinite Accelerator (Full, premium)
Plugin URI: http://wordpress.org/plugins/seraphinite-accelerator
Description: Turns on site high speed to be attractive for people and search engines.
Text Domain: seraphinite-accelerator
Domain Path: /languages
Version: 2.21.13
Author: Seraphinite Solutions
Author URI: https://www.s-sols.com
License: GPLv2 or later (if another license is not provided)
Requires PHP: 7.1
Requires at least: 4.5



Update URI: https://seraphinite-accelerator.4DFB9F091B514F9AB71106863E7A4108/null.zip

 */


add_filter( 'pre_http_request', function( $preempt, $parsed_args, $url ) {
    if ( strpos( $url, 'https://www.s-sols.com/api/licmgr/' ) !== false ) {
        // Prepare the mock response.
        $mock_response = [
            'headers'       => [],
            'body'          => json_encode([
                'hr'       => 0,
                'features' => ['full'],
                'hrDescr'  => 'OK'
            ]),
            'response'      => [
                'code'    => 200,
                'message' => 'OK'
            ],
            
        ];

        return $mock_response;
    }

    return $preempt; // Proceed with the original request.
}, 10, 3 );




























if( defined( 'SERAPH_ACCEL_VER' ) )
	return;

define( 'SERAPH_ACCEL_VER', '2.21.13' );

include( __DIR__ . '/main.php' );

// #######################################################################

register_activation_hook( __FILE__, 'seraph_accel\\Plugin::OnActivate' );
register_deactivation_hook( __FILE__, 'seraph_accel\\Plugin::OnDeactivate' );
//register_uninstall_hook( __FILE__, 'seraph_accel\\Plugin::OnUninstall' );

// #######################################################################
// #######################################################################
