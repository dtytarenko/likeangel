<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_Two_Factor_Authentication_Administrate_Other_Users {
	
	private $simba_tfa;
	
	/**
	 * Class constructor
	 *
	 * @param Object $simba_tfa
	 */
	public function __construct($simba_tfa) {
		
		$this->simba_tfa = $simba_tfa;
		
		add_action('simba_tfa_users_settings', array($this, 'simba_tfa_users_settings'));
		add_action('wp_ajax_simbatfa_choose_user', array($this, 'wp_ajax_simbatfa_choose_user'));
		add_action('wp_ajax_simbatfa_user_get_codes', array($this, 'wp_ajax_simbatfa_user_get_codes'));
		add_action('wp_ajax_simbatfa_user_activation', array($this, 'wp_ajax_simbatfa_user_activation'));
		add_action('wp_ajax_simbatfa_user_privkey_reset', array($this, 'wp_ajax_simbatfa_user_privkey_reset'));
	}
	
	
	/**
	 * Called by the WP action wp_ajax_simbatfa_user_get_codes
	 *
	 * @calls die()
	 */
	public function wp_ajax_simbatfa_user_get_codes() {
		if (empty($_REQUEST['userid']) || !is_numeric($_REQUEST['userid']) || empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'simbatfa_user_get_codes')) die('Security check (4).');
		
		if (!current_user_can('edit_users')) die('Security check (10).');
		
		$user_id = (int) $_REQUEST['userid'];
		
		if (!$this->simba_tfa->is_activated_for_user($user_id)){
			echo  '<p><em>'.__('Two factor authentication is not available for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';;
		} else {
			if (!$this->simba_tfa->is_activated_by_user($user_id)) {
				echo '<p><em>'.__('Two factor authentication is not activated for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';
			} else {
				$this->simba_tfa->get_controller('totp')->current_codes_box($user_id);
			}
		}
		
		die();
	}
	
	/**
	 * Runs upon the WP action wp_ajax_simbatfa_user_activation
	 */
	public function wp_ajax_simbatfa_user_activation() {
		
		if (empty($_REQUEST['userid']) || !is_numeric($_REQUEST['userid']) || empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'simbatfa_user_activation')) die('Security check (5).');
		
		if (!current_user_can('edit_users')) die('Security check (9).');
		
		if (!$this->simba_tfa->is_activated_for_user($_REQUEST['userid'])){
			echo  '<p><em>'.__('Two factor authentication is not available for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';
		} else {
			$activate_or_not = empty($_REQUEST['activate']) ? false : true;
			
			$activate_string = $activate_or_not ? 'true' : 'no';
			$this->simba_tfa->change_tfa_enabled_status($_REQUEST['userid'], $activate_string);
			
			if ($activate_or_not) {
				echo  '<p><em>'.__('Two factor authentication has been activated for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';
			} else {
				echo  '<p><em>'.__('Two factor authentication has been de-activated for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';
			}
		}
		exit;
	}
	
	public function wp_ajax_simbatfa_user_privkey_reset() {
		if (empty($_REQUEST['user_id']) || !is_numeric($_REQUEST['user_id']) || empty($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'simbatfa_user_privkey_reset')) die('Security check (7).');
		
		if (!current_user_can('edit_users')) die('Security check (8).');
		
		$user_id = (int) $_REQUEST['user_id'];
		
		$totp_controller = $this->simba_tfa->get_controller('totp');
		
		if (!$this->simba_tfa->is_activated_for_user($user_id)){
			echo  '<p><em>'.__('Two factor authentication is not available for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';;
		} else {
			if (!$this->simba_tfa->is_activated_by_user($user_id)) {
				echo '<p><em>'.__('Two factor authentication is not activated for this user.', 'all-in-one-wp-security-and-firewall-premium').'</em></p>';
			} else {
				$totp_controller->reset_private_key_and_emergency_codes($user_id, false);
				$totp_controller->current_codes_box($user_id);
			}
		}
		
		exit;
	}
	
	/**
	 * Called upon the WP action wp_ajax_simbatfa_choose_user
	 */
	public function wp_ajax_simbatfa_choose_user() {
		if (empty($_REQUEST['q']) || empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'simbatfa-choose-user')) die('Security check (6).');
		
		if (!current_user_can('edit_users')) die('Security check (11).');
		
		// https://codex.wordpress.org/Class_Reference/WP_User_Query
		
		$args = array(
			'search' => '*'.stripslashes($_REQUEST['q']).'*',
					  'fields' => array('ID', 'user_login', 'user_email', 'user_nicename'),
					  'search_columns' => array('user_login', 'user_email')
		);
		
		// Search all blogs on a multisite
		if (is_multisite()) $args['blog_id'] = 0;
		
		$res = array();
		
		$user_query = new WP_User_Query($args);
		
		if (!empty($user_query->results)) {
			foreach ($user_query->results as $user) {
				$res[] = array(
					'id' => $user->ID,
				   'text' => sprintf("%s - %s (%s)", $user->user_nicename, $user->user_login, $user->user_email),
				);
			}
		}
		
		$results = json_encode(array('results' => $res));
		
		echo $results;
		die;
	}
	
	/**
	 * Runs upon the WP action simba_tfa_users_settings. Prints output.
	 */
	public function simba_tfa_users_settings() {
		$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		wp_deregister_script('select2');
		wp_register_script('select2', $this->simba_tfa->includes_url().'/select2'.$suffix.'.js', array('jquery'), '4.0.12');
		wp_register_script('tfa-admin-select2', plugins_url('', __FILE__).'/includes/tfa-admin-select2.js', array('select2'), filemtime(__DIR__.'/includes/tfa-admin-select2.js'));
		wp_enqueue_script('tfa-admin-select2');
		wp_enqueue_style('select2', $this->simba_tfa->includes_url().'/select2.css', array(), '4.0.12');
		wp_localize_script('tfa-admin-select2', 'simbatfa_administrate_other_users', $this->translation_strings());
		
		?>
		<div class="simba_tfa_users">
		<p>
		<h3><?php _e('Show codes for a particular user', 'all-in-one-wp-security-and-firewall-premium');?></h3>
		<select class="simba_tfa_choose_user" style="width: 240px;">
		</select>
		<button class="simba_tfa_user_get_codes button button-primary"><?php _e('Get status and codes', 'all-in-one-wp-security-and-firewall-premium');?></button>
		<button class="simba_tfa_user_deactivate button button-primary"><?php _e('De-activate TFA', 'all-in-one-wp-security-and-firewall-premium');?></button>
		<button class="simba_tfa_user_activate button button-primary"><?php _e('Activate TFA', 'all-in-one-wp-security-and-firewall-premium');?></button>
		</p>
		<p class="simba_tfa_user_results">
		</p>
		</div>
		<?php
		// Enqueue jquery qrcode
		$this->simba_tfa->get_controller('totp')->add_footer();
	}
	
	/**
	 * Get textual strings used from JavaScript
	 *
	 * @return Array
	 */
	private function translation_strings() {
		return apply_filters('simba_tfa_administrate_other_users_translation_strings', array(
			'choose_valid_user' => __('You must first choose a valid user.', 'all-in-one-wp-security-and-firewall-premium'),
			'get_codes_nonce' => wp_create_nonce('simbatfa_user_get_codes'),
			'user_activation_nonce' => wp_create_nonce('simbatfa_user_activation'),
			'warning_reset' => __('Warning: if you reset this key then the user will have to update his apps with the new one. Are you sure you want this?', 'all-in-one-wp-security-and-firewall-premium'),
			'privkey_reset_nonce' => wp_create_nonce('simbatfa_user_privkey_reset'),
			'choose_user_url' => addslashes(admin_url('admin-ajax.php?action=simbatfa_choose_user&_wpnonce=').wp_create_nonce('simbatfa-choose-user'))
		));
	}

}
