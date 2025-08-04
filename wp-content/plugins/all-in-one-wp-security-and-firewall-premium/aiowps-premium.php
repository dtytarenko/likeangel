<?php
// @codingStandardsIgnoreStart
/*
Plugin Name: All-in-One Security and Firewall Premium
Version: 1.0.8
Update URI: https://aiosplugin.com/
Plugin URI: https://aiosplugin.com/
Author: All In One WP Security & Firewall Team, DavidAnderson
Author URI: https://aiosplugin.com/
Description: A Premium addon for the All In One Security & Firewall plugin which provides accurate country blocking and security functions for 404 events.
License: GPLv3 or later
Text Domain: all-in-one-wp-security-and-firewall-premium
Domain Path: /languages
Network: true
*/
// @codingStandardsIgnoreEnd

if (!defined('ABSPATH')) exit;

if (version_compare(phpversion(), '5.6.0', '<')) {
	add_action('all_admin_notices', 'aiowps_premium_php_version_notice');
	return;
}

/**
 * The notice to display if the user does not have the required PHP version.
 *
 * @return Void
 */
function aiowps_premium_php_version_notice() {

	if (!current_user_can('manage_options')) {
		return;
	}

	$premium_plugin_name = htmlspecialchars(__('All In One WP Security & Firewall Premium', 'all-in-one-wp-security-and-firewall-premium'));

	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php echo $premium_plugin_name; ?></strong></p>
		<p><?php printf(__('%s plugin has been deactivated.', 'all-in-one-wp-security-and-firewall-premium'), $premium_plugin_name); ?></p>
		<p><?php printf(__('This plugin requires PHP version %s.', 'all-in-one-wp-security-and-firewall-premium'), '<strong>5.6+</strong>'); ?></p>
		<p><?php printf(__('Your current PHP version is %s.', 'all-in-one-wp-security-and-firewall-premium'), '<strong>'.phpversion().'</strong>'); ?></p>
		<p><?php _e('You will need to ask your web hosting company to upgrade.', 'all-in-one-wp-security-and-firewall-premium'); ?></p>
	</div>
	<?php

	deactivate_plugins(__FILE__, true);
}

add_action('plugins_loaded', 'aios_premium_plugins_loaded', 99);

/**
 * AIOS premium plugin loaded.
 *
 * @return void
 */
function aios_premium_plugins_loaded() {
	if (!class_exists('AIO_WP_Security')) {
		include_once(__DIR__.'/aiowps-premium-free-required-notice.php');
		$aiowps_free_required_notice = new AIOWPS_Free_Required_Notice();
		$aiowps_free_required_notice->add_hooks();
	}
}

require_once(__DIR__.'/aiowps-premium-core.php');

register_activation_hook(__FILE__, array('AIOWPS_PREMIUM', 'activate_handler'));
register_deactivation_hook(__FILE__, array('AIOWPS_PREMIUM', 'deactivate_handler'));
register_uninstall_hook(__FILE__, array('AIOWPS_PREMIUM', 'uninstall_handler'));
