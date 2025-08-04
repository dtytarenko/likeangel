<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Trusted_Devices {
	
	private $simba_tfa;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_action('simba_tfa_user_settings_after_advanced_settings', array($this, 'user_settings_after_advanced_settings'));
		add_filter('simba_tfa_trusted_devices_config', array($this, 'simba_tfa_trusted_devices_config'));
		add_filter('simba_tfa_user_can_trust', array($this, 'simba_tfa_user_can_trust'), 10, 2);
		
	}
	
	/**
	 * Runs upon the WP filter simba_tfa_trusted_devices_config
	 *
	 * @return String
	 */
	public function simba_tfa_trusted_devices_config() {
		ob_start();
		echo '<p>';
		$this->simba_tfa->list_user_roles_checkboxes('trusted_', 0);
		
		$trusted_for = $this->simba_tfa->get_option('tfa_trusted_for');
		$trusted_for = (false === $trusted_for) ? 30 : (string) absint($trusted_for);
		
		echo '<p>'.sprintf(__("When a device is trusted, don't require a two-factor code for another %s days", 'all-in-one-wp-security-and-firewall-premium'), '<input type="number" style="width:60px;" step="1" min="0" name="tfa_trusted_for" id="tfa_trusted_for" value="'.esc_attr($trusted_for).'">').'</p>';
		
		echo '</p>';
		
		submit_button();
		
		return ob_get_clean();
	}
	
	/**
	 * Get the saved setting for whether a particular user level can be trusted
	 *
	 * @param Boolean $can_trust - the result
	 * @param Integer $user_id	 - WordPress user ID
	 */
	public function simba_tfa_user_can_trust($can_trust, $user_id) {
		return $this->simba_tfa->user_property_active($user_id, 'trusted_');
	}
	
	/**
	 * Runs upon the WP action simba_tfa_user_settings_after_advanced_settings
	 */
	public function user_settings_after_advanced_settings() {
		
		?>
		
		<h2 style="clear:both;"><?php _e('Trusted devices', 'all-in-one-wp-security-and-firewall-premium'); ?></h2>
		
		<div id="tfa_trusted_devices_box" class="tfa_settings_form" style="margin-top: 20px;">
		
		<?php
		$this->simba_tfa->include_template(
			'trusted-devices-inner-box.php',
			array('trusted_devices' => $this->simba_tfa->user_get_trusted_devices())
		);
		?>
		
		</div>
		
		<?php
	}
}
